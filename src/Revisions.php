<?php

/**
 * @brief dcRevisions, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author TomTom, Franck Paul and contributors
 *
 * @copyright Franck Paul contact@open-time.net
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcRevisions;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Helper\Diff\Diff;
use Dotclear\Helper\Network\Http;
use Exception;

/**
 * @todo switch to SqlStatement
 */
class Revisions
{
    // Constants

    /**
     * Table name
     *
     * @var        string
     */
    public const REVISION_TABLE_NAME = 'revision';

    /**
     * Gets the revisions list.
     *
     * @param      array<string, mixed>     $params       The parameters
     * @param      bool                     $count_only   The count only
     *
     * @return     MetaRecord  The revisions.
     */
    public function getRevisions(array $params, bool $count_only = false): MetaRecord
    {
        $sql = new SelectStatement();

        if ($count_only) {
            $sql->column($sql->count('revision_id'));
        } else {
            $sql->columns([
                'R.revision_id',
                'R.post_id',
                'R.user_id',
                'R.revision_type',
                'R.revision_dt',
                'R.revision_tz',
                'R.revision_excerpt_diff',
                'R.revision_excerpt_xhtml_diff',
                'R.revision_content_diff',
                'R.revision_content_xhtml_diff',
                'U.user_url',
                'U.user_name',
                'U.user_firstname',
                'U.user_displayname',
            ]);
        }

        $sql
            ->from($sql->as(App::db()->con()->prefix() . self::REVISION_TABLE_NAME, 'R'))
            ->join(
                (new JoinStatement())
                    ->from($sql->as(App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME, 'U'))
                    ->on('R.user_id = U.user_id')
                    ->statement()
            )
            ->where('R.blog_id = ' . $sql->quote(App::blog()->id()))
        ;

        if (!empty($params['from']) && is_string($params['from'])) {
            $sql->from($sql->escape($params['from']));
        }

        if (!empty($params['post_id'])) {
            $post_ids = $sql->sanitizeIn($params['post_id'], 'int', false);
            if ($post_ids !== []) {
                $sql->and('R.post_id ' . $sql->in($post_ids));
            }
        }

        if (!empty($params['revision_id'])) {
            $revision_ids = $sql->sanitizeIn($params['revision_id'], 'int', false);
            if ($revision_ids !== []) {
                $sql->and('R.revision_id ' . $sql->in($revision_ids));
            }
        }

        if (isset($params['post_type'])) {
            if (is_array($params['post_type']) && $params['post_type'] !== []) {
                $post_types = $sql->sanitizeIn($params['post_type'], 'string', false);
                if ($post_types !== []) {
                    $sql->and('R.revision_type ' . $sql->in($post_types));
                }
            } elseif (is_string($params['post_type']) && $params['post_type'] !== '') {
                $sql->and('R.revision_type = ' . $sql->quote($params['post_type']));
            }
        }

        if (!empty($params['sql']) && is_string($params['sql'])) {
            $sql->sql($params['sql']);
        }

        if (!$count_only) {
            if (!empty($params['order']) && is_string($params['order'])) {
                $sql->order($sql->escape($params['order']));
            } else {
                $sql->order('revision_dt DESC');
            }
        }

        if (!$count_only && isset($params['limit'])) {
            /**
             * @var list<string|int|null>   $values
             */
            $values = is_array($params['limit']) ? array_values($params['limit']) : [$params['limit']];
            // Make $values an array of integer values
            $values = array_map(fn (int|string|null $v): int => (int) $v, $values);

            /**
             * @var array{0: int, 1?: int}  $limit
             */
            $limit = [
                $values[0],
            ];
            if (isset($values[1])) {
                $limit[1] = $values[1];
            }

            $sql->limit($limit);
        }

        $rs = $sql->select();
        if ($rs) {
            $rs->extend(RevisionsExtensions::class);
        } else {
            $rs = MetaRecord::newFromArray([]);
        }

        return $rs;
    }

    /**
     * Adds a revision.
     *
     * @param      Cursor  $cur     The pcur
     * @param      int     $post_id  The post identifier
     * @param      string  $type    The type
     */
    public function addRevision(Cursor $cur, int $post_id, string $type): void
    {
        $rs = new MetaRecord(App::db()->con()->select(
            'SELECT MAX(revision_id) FROM ' . App::db()->con()->prefix() . self::REVISION_TABLE_NAME
        ));
        $revision_id = $rs->cardinal() + 1;

        $rs = App::blog()->getPosts(['post_id' => $post_id, 'post_type' => $type]);

        $old = [
            'post_excerpt'       => $rs->strField('post_excerpt'),
            'post_excerpt_xhtml' => $rs->strField('post_excerpt_xhtml'),
            'post_content'       => $rs->strField('post_content'),
            'post_content_xhtml' => $rs->strField('post_content_xhtml'),
        ];

        $new = [
            'post_excerpt'       => is_string($cur->post_excerpt) ? $cur->post_excerpt : '',
            'post_excerpt_xhtml' => is_string($cur->post_excerpt_xhtml) ? $cur->post_excerpt_xhtml : '',
            'post_content'       => is_string($cur->post_content) ? $cur->post_content : '',
            'post_content_xhtml' => is_string($cur->post_content_xhtml) ? $cur->post_content_xhtml : '',
        ];

        $diff = $this->getDiff($new, $old);

        $insert = false;
        foreach ($diff as $v) {
            if ($v !== '') {
                $insert = true;
            }
        }

        if ($insert) {
            $revisionCursor                              = App::db()->con()->openCursor(App::db()->con()->prefix() . 'revision');
            $revisionCursor->revision_id                 = $revision_id;
            $revisionCursor->post_id                     = $post_id;
            $revisionCursor->user_id                     = App::auth()->userID();
            $revisionCursor->blog_id                     = App::blog()->id();
            $revisionCursor->revision_dt                 = date('Y-m-d H:i:s');
            $revisionCursor->revision_tz                 = App::auth()->getInfo('user_tz');
            $revisionCursor->revision_type               = $type;
            $revisionCursor->revision_excerpt_diff       = $diff['post_excerpt'];
            $revisionCursor->revision_excerpt_xhtml_diff = $diff['post_excerpt_xhtml'];
            $revisionCursor->revision_content_diff       = $diff['post_content'];
            $revisionCursor->revision_content_xhtml_diff = $diff['post_content_xhtml'];

            try {
                App::db()->con()->writeLock(App::db()->con()->prefix() . 'revision');
                $revisionCursor->insert();
                App::db()->con()->unlock();
            } catch (Exception $exception) {
                App::error()->add($exception->getMessage());
                App::db()->con()->unlock();
            }
        }
    }

    /**
     * Gets the difference.
     *
     * @param      array<string, string>  $new      New content
     * @param      array<string, string>  $old      Old content
     *
     * @return     array<string, string>  The difference.
     */
    public function getDiff(array $new, array $old): array
    {
        $diff = [
            'post_excerpt'       => '',
            'post_excerpt_xhtml' => '',
            'post_content'       => '',
            'post_content_xhtml' => '',
        ];

        try {
            foreach (array_keys($diff) as $k) {
                $diff[$k] = Diff::uniDiff($new[$k], $old[$k]);
            }
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return $diff;
    }

    /**
     * Remove entry revisions
     *
     * @param      int          $post_id       The post id
     * @param      string       $type          The type
     * @param      null|string  $redirect_url  The redirect url
     *
     * @throws     Exception
     */
    public function purge(int $post_id, string $type, ?string $redirect_url = null): void
    {
        if (!$this->canPurge($post_id, $type)) {
            throw new Exception(__('You are not allowed to delete revisions of this entry'));
        }

        try {
            // Purge all revisions of the entry
            $sql = new DeleteStatement();
            $sql
                ->from(App::db()->con()->prefix() . self::REVISION_TABLE_NAME)
                ->where('post_id = ' . $sql->quote((string) $post_id))
            ;
            $sql->delete();

            if (!App::error()->flag() && $redirect_url !== null) {
                App::backend()->notices()->addSuccessNotice(__('All revisions have been deleted.'));
                Http::redirect(sprintf($redirect_url, $post_id));
            }
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }
    }

    /**
     * Sets the patch.
     *
     * @param      int        $post_id           The post id
     * @param      int        $revision_id       The revision id
     * @param      string     $type              The type
     * @param      string     $redirect_url      The redirect url
     * @param      string     $before_behaviour  The before behaviour
     * @param      string     $after_behaviour   The after behaviour
     *
     * @throws     Exception
     */
    public function setPatch(int $post_id, int $revision_id, string $type, string $redirect_url, string $before_behaviour, string $after_behaviour): void
    {
        if (!$this->canPatch($revision_id)) {
            throw new Exception(__('You are not allowed to patch this entry with this revision'));
        }

        try {
            $patch = $this->getPatch($post_id, $revision_id, $type);

            $rs = App::blog()->getPosts(['post_id' => $post_id, 'post_type' => $type]);

            $cur = App::db()->con()->openCursor(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME);

            $cur->post_title        = $rs->strField('post_title', true);
            $cur->cat_id            = $rs->intField('cat_id', true);
            $cur->post_dt           = $rs->strField('post_dt', true);
            $cur->post_format       = $rs->strField('post_format', true);
            $cur->post_password     = $rs->strField('post_password', true);
            $cur->post_lang         = $rs->strField('post_lang', true);
            $cur->post_notes        = $rs->strField('post_notes', true);
            $cur->post_status       = $rs->intField('post_status', true);
            $cur->post_selected     = $rs->boolField('post_selected', true);
            $cur->post_open_comment = $rs->boolField('post_open_comment', true);
            $cur->post_open_tb      = $rs->boolField('post_open_tb', true);
            $cur->post_type         = $rs->strField('post_type', true);

            $cur->post_excerpt       = $patch['post_excerpt'];
            $cur->post_excerpt_xhtml = $patch['post_excerpt_xhtml'];
            $cur->post_content       = $patch['post_content'];
            $cur->post_content_xhtml = $patch['post_content_xhtml'];

            # --BEHAVIOR-- adminBeforeXXXXUpdate
            App::behavior()->callBehavior($before_behaviour, $cur, $post_id);

            App::auth()->sudo(App::blog()->updPost(...), $post_id, $cur);

            # --BEHAVIOR-- adminAfterXXXXUpdate
            App::behavior()->callBehavior($after_behaviour, $cur, $post_id);

            Http::redirect(sprintf($redirect_url, $post_id));
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }
    }

    /**
     * Gets the patch.
     *
     * @param      int     $post_id      The post id
     * @param      int     $revision_id  The revision id
     * @param      string  $type         The type
     *
     * @return     array{
     *                 post_excerpt: string,
     *                 post_excerpt_xhtml: string,
     *                 post_content: string,
     *                 post_content_xhtml: string
     *             }   The patch.
     */
    public function getPatch(int $post_id, int $revision_id, string $type): array
    {
        $params = [
            'post_id'   => $post_id,
            'post_type' => $type,
        ];

        $rs        = App::blog()->getPosts($params);
        $revisions = $this->getRevisions($params);

        $patch = [
            'post_excerpt'       => $rs->strField('post_excerpt'),
            'post_excerpt_xhtml' => $rs->strField('post_excerpt_xhtml'),
            'post_content'       => $rs->strField('post_content'),
            'post_content_xhtml' => $rs->strField('post_content_xhtml'),
        ];

        $map = [
            // Entry field => Revision field
            'post_excerpt'       => 'revision_excerpt_diff',
            'post_excerpt_xhtml' => 'revision_excerpt_xhtml_diff',
            'post_content'       => 'revision_content_diff',
            'post_content_xhtml' => 'revision_content_xhtml_diff',
        ];

        while ($revisions->fetch()) {
            $id = $revisions->intField('revision_id');
            if ($id === 0) {
                break;
            }

            $revision = [
                'revision_excerpt_diff'       => $revisions->strField('revision_excerpt_diff'),
                'revision_excerpt_xhtml_diff' => $revisions->strField('revision_excerpt_xhtml_diff'),
                'revision_content_diff'       => $revisions->strField('revision_content_diff'),
                'revision_content_xhtml_diff' => $revisions->strField('revision_content_xhtml_diff'),
            ];

            foreach ($patch as $field => $value) {
                $revisionField = $map[$field];
                $patch[$field] = Diff::uniPatch($value, $revision[$revisionField]);
            }

            if ($id === $revision_id) {
                break;
            }
        }

        return $patch;
    }

    /**
     * Determines ability to patch.
     *
     * @param      int     $revision_id  The revision id
     *
     * @return     bool    True if able to patch, False otherwise.
     */
    protected function canPatch(int $revision_id): bool
    {
        $rs = $this->getRevisions(['revision_id' => $revision_id]);

        return (bool) $rs->canPatch();
    }

    /**
     * Determines ability to purge.
     *
     * @param      int     $post_id  The post id
     * @param      string  $type     The type
     *
     * @return     bool    True if able to purge, False otherwise.
     */
    protected function canPurge(int $post_id, string $type): bool
    {
        $rs = App::blog()->getPosts(['post_id' => $post_id, 'post_type' => $type]);

        return (bool) $rs->isEditable();
    }
}
