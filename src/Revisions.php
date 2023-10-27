<?php
/**
 * @brief dcRevisions, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author TomTom, Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcRevisions;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Diff\Diff;
use Dotclear\Helper\Network\Http;
use Exception;

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
     * @param      array<string, mixed>     $params      The parameters
     * @param      bool                     $countOnly   The count only
     *
     * @return     MetaRecord  The revisions.
     */
    public function getRevisions(array $params, bool $countOnly = false): MetaRecord
    {
        if ($countOnly) {
            $f = 'COUNT(revision_id)';
        } else {
            $f = 'R.revision_id, R.post_id, R.user_id, R.revision_type, ' .
                'R.revision_dt, R.revision_tz, R.revision_excerpt_diff, ' .
                'R.revision_excerpt_xhtml_diff, R.revision_content_diff, ' .
                'R.revision_content_xhtml_diff, U.user_url, U.user_name, ' .
                'U.user_firstname, U.user_displayname';
        }

        $strReq = 'SELECT ' . $f . ' FROM ' . App::con()->prefix() . self::REVISION_TABLE_NAME . ' R ' .
        'LEFT JOIN ' . App::con()->prefix() . 'user U ON R.user_id = U.user_id ';

        if (!empty($params['from'])) {
            $strReq .= $params['from'] . ' ';
        }

        $strReq .= "WHERE R.blog_id = '" . App::con()->escapeStr(App::blog()->id()) . "' ";

        if (!empty($params['post_id'])) {
            if (is_array($params['post_id'])) {
                array_walk(
                    $params['post_id'],
                    static function (&$v) {
                        if ($v !== null) {
                            $v = (int) $v;
                        }
                    }
                );
            } else {
                $params['post_id'] = [(int) $params['post_id']];
            }

            $strReq .= 'AND R.post_id ' . App::con()->in($params['post_id']);
        }

        if (!empty($params['revision_id'])) {
            if (is_array($params['revision_id'])) {
                array_walk(
                    $params['revision_id'],
                    static function (&$v) {
                        if ($v !== null) {
                            $v = (int) $v;
                        }
                    }
                );
            } else {
                $params['revision_id'] = [(int) $params['revision_id']];
            }

            $strReq .= 'AND R.revision_id ' . App::con()->in($params['revision_id']);
        }

        if (isset($params['post_type'])) {
            if (is_array($params['post_type']) && $params['post_type'] !== []) {
                $strReq .= 'AND R.revision_type ' . App::con()->in($params['post_type']);
            } elseif ($params['post_type'] != '') {
                $strReq .= "AND R.revision_type = '" . App::con()->escapeStr($params['post_type']) . "' ";
            }
        }

        if (!empty($params['sql'])) {
            $strReq .= $params['sql'] . ' ';
        }

        if (!$countOnly) {
            if (!empty($params['order'])) {
                $strReq .= 'ORDER BY ' . App::con()->escapeStr($params['order']) . ' ';
            } else {
                $strReq .= 'ORDER BY revision_dt DESC ';
            }
        }

        if (!$countOnly && !empty($params['limit'])) {
            $strReq .= App::con()->limit($params['limit']);
        }

        $rs = new MetaRecord(App::con()->select($strReq));
        $rs->extend(RevisionsExtensions::class);

        return $rs;
    }

    /**
     * Adds a revision.
     *
     * @param      Cursor  $cur     The pcur
     * @param      string  $postID  The post identifier
     * @param      string  $type    The type
     */
    public function addRevision(Cursor $cur, string $postID, string $type): void
    {
        $rs = new MetaRecord(App::con()->select(
            'SELECT MAX(revision_id) FROM ' . App::con()->prefix() . self::REVISION_TABLE_NAME
        ));
        $revisionID = $rs->f(0) + 1;

        $rs = App::blog()->getPosts(['post_id' => $postID, 'post_type' => $type]);

        $old = [
            'post_excerpt'       => $rs->post_excerpt       ?? '',
            'post_excerpt_xhtml' => $rs->post_excerpt_xhtml ?? '',
            'post_content'       => $rs->post_content       ?? '',
            'post_content_xhtml' => $rs->post_content_xhtml ?? '',
        ];
        $new = [
            'post_excerpt'       => $cur->post_excerpt       ?? '',
            'post_excerpt_xhtml' => $cur->post_excerpt_xhtml ?? '',
            'post_content'       => $cur->post_content       ?? '',
            'post_content_xhtml' => $cur->post_content_xhtml ?? '',
        ];

        $diff = $this->getDiff($new, $old);

        $insert = false;
        foreach ($diff as $v) {
            if ($v !== '') {
                $insert = true;
            }
        }

        if ($insert) {
            $revisionCursor                              = App::con()->openCursor(App::con()->prefix() . 'revision');
            $revisionCursor->revision_id                 = $revisionID;
            $revisionCursor->post_id                     = $postID;
            $revisionCursor->user_id                     = App::auth()->userID();
            $revisionCursor->blog_id                     = App::blog()->id();
            $revisionCursor->revision_dt                 = date('Y-m-d H:i:s');
            $revisionCursor->revision_tz                 = App::auth()->getInfo('user_tz');
            $revisionCursor->revision_type               = $type;
            $revisionCursor->revision_excerpt_diff       = $diff['post_excerpt'];
            $revisionCursor->revision_excerpt_xhtml_diff = $diff['post_excerpt_xhtml'];
            $revisionCursor->revision_content_diff       = $diff['post_content'];
            $revisionCursor->revision_content_xhtml_diff = $diff['post_content_xhtml'];

            App::con()->writeLock(App::con()->prefix() . 'revision');
            $revisionCursor->insert();
            App::con()->unlock();
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
     * @param      string       $postID       The post id
     * @param      string       $type         The type
     * @param      null|string  $redirectURL  The redirect url
     *
     * @throws     Exception
     */
    public function purge(string $postID, string $type, ?string $redirectURL = null): void
    {
        if (!$this->canPurge($postID, $type)) {
            throw new Exception(__('You are not allowed to delete revisions of this entry'));
        }

        try {
            // Purge all revisions of the entry
            $strReq = 'DELETE FROM ' . App::con()->prefix() . self::REVISION_TABLE_NAME . ' ' .
                "WHERE post_id = '" . App::con()->escapeStr($postID) . "' ";
            App::con()->execute($strReq);

            if (!App::error()->flag() && $redirectURL !== null) {
                Notices::addSuccessNotice(__('All revisions have been deleted.'));
                Http::redirect(sprintf($redirectURL, $postID));
            }
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }
    }

    /**
     * Sets the patch.
     *
     * @param      string     $postID           The post id
     * @param      string     $revisionID       The revision id
     * @param      string     $type             The type
     * @param      string     $redirectURL      The redirect url
     * @param      string     $beforeBehaviour  The before behaviour
     * @param      string     $afterBehaviour   The after behaviour
     *
     * @throws     Exception
     */
    public function setPatch(string $postID, string $revisionID, string $type, string $redirectURL, string $beforeBehaviour, string $afterBehaviour): void
    {
        if (!$this->canPatch($revisionID)) {
            throw new Exception(__('You are not allowed to patch this entry with this revision'));
        }

        try {
            $patch = $this->getPatch($postID, $revisionID, $type);

            $rs = App::blog()->getPosts(['post_id' => $postID, 'post_type' => $type]);

            $cur = App::con()->openCursor(App::con()->prefix() . App::blog()::POST_TABLE_NAME);

            $cur->post_title        = $rs->post_title;
            $cur->cat_id            = $rs->cat_id ?: null;
            $cur->post_dt           = $rs->post_dt ? date('Y-m-d H:i:00', (int) strtotime($rs->post_dt)) : '';
            $cur->post_format       = $rs->post_format;
            $cur->post_password     = $rs->post_password;
            $cur->post_lang         = $rs->post_lang;
            $cur->post_notes        = $rs->post_notes;
            $cur->post_status       = $rs->post_status;
            $cur->post_selected     = (int) $rs->post_selected;
            $cur->post_open_comment = (int) $rs->post_open_comment;
            $cur->post_open_tb      = (int) $rs->post_open_tb;
            $cur->post_type         = $rs->post_type;

            $cur->post_excerpt       = $patch['post_excerpt'];
            $cur->post_excerpt_xhtml = $patch['post_excerpt_xhtml'];
            $cur->post_content       = $patch['post_content'];
            $cur->post_content_xhtml = $patch['post_content_xhtml'];

            # --BEHAVIOR-- adminBeforeXXXXUpdate
            App::behavior()->callBehavior($beforeBehaviour, $cur, $postID);

            App::auth()->sudo(App::blog()->updPost(...), $postID, $cur);

            # --BEHAVIOR-- adminAfterXXXXUpdate
            App::behavior()->callBehavior($afterBehaviour, $cur, $postID);

            Http::redirect(sprintf($redirectURL, $postID));
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }
    }

    /**
     * Gets the patch.
     *
     * @param      string  $postID      The post id
     * @param      string  $revisionID  The revision id
     * @param      string  $type        The type
     *
     * @return     array<string, string>   The patch.
     */
    public function getPatch(string $postID, string $revisionID, string $type): array
    {
        $params = [
            'post_id'   => $postID,
            'post_type' => $type,
        ];

        $rs        = App::blog()->getPosts($params);
        $revisions = $this->getRevisions($params);

        $patch = [
            'post_excerpt'       => $rs->post_excerpt,
            'post_excerpt_xhtml' => $rs->post_excerpt_xhtml,
            'post_content'       => $rs->post_content,
            'post_content_xhtml' => $rs->post_content_xhtml,
        ];

        $map = [
            // Entry field => Revision field
            'post_excerpt'       => 'revision_excerpt_diff',
            'post_excerpt_xhtml' => 'revision_excerpt_xhtml_diff',
            'post_content'       => 'revision_content_diff',
            'post_content_xhtml' => 'revision_content_xhtml_diff',
        ];

        while ($revisions->fetch()) {
            foreach ($patch as $field => $value) {
                $revisionField = $map[$field] ?? null;
                if ($revisionField !== null) {
                    $patch[$field] = Diff::uniPatch($value, $revisions->{$revisionField});
                }
            }

            if ($revisions->revision_id === $revisionID) {
                break;
            }
        }

        return $patch;
    }

    /**
     * Determines ability to patch.
     *
     * @param      string  $revisionID  The revision id
     *
     * @return     bool    True if able to patch, False otherwise.
     */
    protected function canPatch(string $revisionID): bool
    {
        $rs = $this->getRevisions(['revision_id' => $revisionID]);

        return $rs->canPatch();
    }

    /**
     * Determines ability to purge.
     *
     * @param      string  $postID  The post id
     * @param      string  $type    The type
     *
     * @return     bool    True if able to purge, False otherwise.
     */
    protected function canPurge(string $postID, string $type): bool
    {
        $rs = App::blog()->getPosts(['post_id' => $postID, 'post_type' => $type]);

        return $rs->isEditable();
    }
}
