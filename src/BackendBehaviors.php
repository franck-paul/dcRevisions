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

use ArrayObject;
use dcCore;
use dcNamespace;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\pages\BackendActions as PagesBackendActions;
use Exception;
use form;

class BackendBehaviors
{
    /**
     * Display plugin settings
     */
    public static function adminBlogPreferencesForm(): string
    {
        if (dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            echo
            '<div class="fieldset"><h4 id="dc-revisions">' . __('Revisions') . '</h4>' .
            '<p><label class="classic" for="dcrevisions_enable">' .
            form::checkbox('dcrevisions_enable', 1, (bool) My::settings()->enable) .
            __('Enable entries\' versionning on this blog') . '</label></p>' .
            '</div>';
        }

        return '';
    }

    /**
     * Register plugin settings
     */
    public static function adminBeforeBlogSettingsUpdate(): string
    {
        if (dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            My::settings()->put('enable', empty($_POST['dcrevisions_enable']) ? false : true, dcNamespace::NS_BOOL);
        }

        return '';
    }

    /**
     * Add revision form on entry form
     *
     * @param      MetaRecord|null  $post   The post
     */
    public static function adminPostForm(?MetaRecord $post): string
    {
        $id  = isset($post) && !$post->isEmpty() ? $post->post_id : null;
        $url = sprintf(dcCore::app()->adminurl->get('admin.post', [
            'id'    => '%1$s',
            'patch' => '%2$s',
        ], '&', true), $id, '%s');
        $purge_url = sprintf(dcCore::app()->adminurl->get('admin.post', [
            'id'       => '%1$s',
            'revpurge' => 1,
        ], '&', true), $id);

        $params = [
            'post_id'   => $id,
            'post_type' => 'post',
        ];

        if (is_null($id)) {
            $rs = MetaRecord::newFromArray([]);
        } else {
            $rs = dcCore::app()->blog->revisions->getRevisions($params);
        }

        $list = new RevisionsList($rs);

        echo
        '<details class="area" id="revisions-area">' .
        '<summary>' . __('Revisions:') . '</summary>' .
        $list->display($url) .
        ($list->count() ? '<a href="' . $purge_url . '" class="button delete" id="revpurge">' . __('Purge all revisions') . '</a>' : '') .
        '</details>';

        return '';
    }

    /**
     * Return HTML plugin specific header on post form
     *
     * @return     string
     */
    public static function adminPostHeaders(): string
    {
        return
        Page::jsJson('dcrevisions', [
            'post_type' => 'post',
            'msg'       => [
                'excerpt'                => __('Excerpt'),
                'content'                => __('Content'),
                'current'                => __('Current'),
                'revision'               => __('Rev.'),
                'content_identical'      => __('Content identical'),
                'confirm_apply_patch'    => __('CAUTION: This operation will replace all the content by the previous one. Are you sure to want apply this patch on this page?'),
                'confirm_purge_revision' => __('CAUTION: This operation will delete all the revisions. Are you sure to want to do this?'),
            ],
        ]) .
        My::jsLoad('_revision.js') . "\n" .
        My::cssLoad('style.css') . "\n";
    }

    /**
     * Add a revision before post update
     *
     * @param      cursor  $cur      The cursor
     * @param      mixed   $postID   The post identifier    // to be switch to int with 2.28
     */
    public static function adminBeforePostUpdate(Cursor $cur, mixed $postID): string
    {
        try {
            dcCore::app()->blog->revisions->addRevision($cur, (string) $postID, 'post');
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return '';
    }

    /**
     * Add revision form on page form
     *
     * @param      MetaRecord|null  $post   The post
     */
    public static function adminPageForm(?MetaRecord $post): string
    {
        $base_url  = dcCore::app()->adminurl->get('admin.plugin.pages', ['act' => 'page']);
        $id        = isset($post) && !$post->isEmpty() ? $post->post_id : null;
        $url       = sprintf($base_url . '&amp;id=%1$s&amp;patch=%2$s', $id, '%s');
        $purge_url = sprintf($base_url . '&amp;id=%1$s&amp;revpurge=1', $id);

        $params = [
            'post_id'   => $id,
            'post_type' => 'page',
        ];

        $rs = dcCore::app()->blog->revisions->getRevisions($params);

        if (is_null($id)) {
            $rs = MetaRecord::newFromArray([]);
        }

        $list = new RevisionsList($rs);

        echo '<div class="area" id="revisions-area"><label>' . __('Revisions:') . '</label>' . $list->display($url) .
            ($list->count() ? '<a href="' . $purge_url . '" class="button delete" id="revpurge">' . __('Purge all revisions') . '</a>' : '') .
            '</div>';

        return '';
    }

    /**
     * Return HTML plugin specific header on page form
     *
     * @return     string
     */
    public static function adminPageHeaders(): string
    {
        return
        Page::jsJson('dcrevisions', [
            'post_type' => 'page',
            'msg'       => [
                'excerpt'                => __('Excerpt'),
                'content'                => __('Content'),
                'current'                => __('Current'),
                'revision'               => __('Rev.'),
                'content_identical'      => __('Content identical'),
                'confirm_apply_patch'    => __('CAUTION: This operation will replace all the content by the previous one. Are you sure to want apply this patch on this page?'),
                'confirm_purge_revision' => __('CAUTION: This operation will delete all the revisions. Are you sure to want to do this?'),
            ],
        ]) .
        My::jsLoad('_revision.js') . "\n" .
        My::cssLoad('style.css') . "\n";
    }

    /**
     * Add a revision before page update
     *
     * @param      cursor  $cur      The cursor
     * @param      string  $postID   The post identifier
     */
    public static function adminBeforePageUpdate(Cursor $cur, string $postID): string
    {
        try {
            dcCore::app()->blog->revisions->addRevision($cur, $postID, 'page');
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return '';
    }

    /**
     * Add action for posts
     *
     * @param      ActionsPosts  $ap     Posts' actions
     */
    public static function adminPostsActions(ActionsPosts $ap): string
    {
        // Add menuitem in actions dropdown list
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Revisions') => [__('Purge all revisions') => 'revpurge']],
                BackendBehaviors::adminPostsDoReplacements(...)
            );
        }

        return '';
    }

    /**
     * Add action for pages
     *
     * @param      PagesBackendActions  $ap     Pages' actions
     */
    public static function adminPagesActions(PagesBackendActions $ap): string
    {
        // Add menuitem in actions dropdown list
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Revisions') => [__('Purge all revisions') => 'revpurge']],
                BackendBehaviors::adminPagesDoReplacements(...)
            );
        }

        return '';
    }

    /**
     * Do posts action
     *
     * @param      ActionsPosts                 $ap     Posts' actions
     * @param      ArrayObject<string, mixed>   $post   The post
     */
    public static function adminPostsDoReplacements(ActionsPosts $ap, arrayObject $post): void
    {
        self::adminEntriesDoReplacements($ap, $post, 'post');
    }

    /**
     * Do pages action
     *
     * @param      PagesBackendActions          $ap     Pages' actions
     * @param      ArrayObject<string, mixed>   $post   The post
     */
    public static function adminPagesDoReplacements(PagesBackendActions $ap, arrayObject $post): void
    {
        self::adminEntriesDoReplacements($ap, $post, 'page');
    }

    /**
     * Do posts/pages action
     *
     * @param      ActionsPosts|PagesBackendActions     $ap     Posts'/Pages' actions
     * @param      ArrayObject<string, mixed>           $post   The post
     */
    private static function adminEntriesDoReplacements(ActionsPosts|PagesBackendActions $ap, arrayObject $post, string $type = 'post'): void
    {
        if (!empty($post['dopurge'])) {
            // Do replacements
            $posts = $ap->getRS();
            if ($posts->rows()) {
                while ($posts->fetch()) {
                    // Purge
                    dcCore::app()->blog->revisions->purge($posts->post_id, $type);
                }
                Notices::addSuccessNotice(__('All revisions have been deleted.'));
                $ap->redirect(true);
            } else {
                $ap->redirect();
            }
        } else {
            // Ask confirmation for replacements
            if ($type == 'page') {
                $ap->beginPage(
                    Page::breadcrumb(
                        [
                            Html::escapeHTML(dcCore::app()->blog->name) => '',
                            __('Pages')                                 => dcCore::app()->adminurl->get('admin.plugin.pages'),
                            __('Purge all revisions')                   => '',
                        ]
                    )
                );
            } else {
                $ap->beginPage(
                    Page::breadcrumb(
                        [
                            Html::escapeHTML(dcCore::app()->blog->name) => '',
                            __('Entries')                               => dcCore::app()->adminurl->get('admin.posts'),
                            __('Purge all revisions')                   => '',
                        ]
                    )
                );
            }

            Notices::warning(__('CAUTION: This operation will delete all the revisions. Are you sure to want to do this?'), false, false);

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<p><input type="submit" value="' . __('save') . '" /></p>' .
            $ap->getHiddenFields() .
            My::parsedHiddenFields([
                'dopurge' => 'true',
                'action'  => 'revpurge',
            ]) .
            '</form>';

            $ap->endPage();
        }
    }
}
