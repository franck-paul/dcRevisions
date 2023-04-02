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
use dcPage;
use dcPostsActions;
use dcSettings;
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
     *
     * @param      dcSettings  $settings  The settings
     */
    public static function adminBlogPreferencesForm(dcSettings $settings)
    {
        if (dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            echo
            '<div class="fieldset"><h4 id="dc-revisions">' . __('Revisions') . '</h4>' .
            '<p><label class="classic" for="dcrevisions_enable">' .
            form::checkbox('dcrevisions_enable', 1, $settings->dcrevisions->enable) .
            __('Enable entries\' versionning on this blog') . '</label></p>' .
                '</div>';
        }
    }

    /**
     * Register plugin settings
     *
     * @param      dcSettings  $settings  The settings
     */
    public static function adminBeforeBlogSettingsUpdate(dcSettings $settings)
    {
        if (dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $settings->dcrevisions->put('enable', empty($_POST['dcrevisions_enable']) ? false : true);
        }
    }

    /**
     * Add revision form on entry form
     *
     * @param      MetaRecord|null  $post   The post
     */
    public static function adminPostForm(?MetaRecord $post)
    {
        $id        = isset($post) && !$post->isEmpty() ? $post->post_id : null;
        $url       = sprintf('post.php?id=%1$s&amp;patch=%2$s', $id, '%s');
        $purge_url = sprintf('post.php?id=%1$s&amp;revpurge=1', $id);

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

        echo '<div class="area" id="revisions-area"><label>' . __('Revisions:') . '</label>' . $list->display($url) .
            ($list->count() ? '<a href="' . $purge_url . '" class="button delete" id="revpurge">' . __('Purge all revisions') . '</a>' : '') .
            '</div>';
    }

    /**
     * Return HTML plugin specific header on post form
     *
     * @return     string
     */
    public static function adminPostHeaders(): string
    {
        return
        dcPage::jsJson('dcrevisions', [
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
        dcPage::jsModuleLoad('dcRevisions/js/_revision.js', dcCore::app()->getVersion('dcrevisions')) . "\n" .
        dcPage::cssModuleLoad('dcRevisions/css/style.css', 'screen', dcCore::app()->getVersion('dcrevisions')) . "\n";
    }

    /**
     * Add a revision before post update
     *
     * @param      cursor  $cur      The cursor
     * @param      string  $postID   The post identifier
     */
    public static function adminBeforePostUpdate(Cursor $cur, string $postID)
    {
        try {
            dcCore::app()->blog->revisions->addRevision($cur, $postID, 'post');
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    /**
     * Add revision form on page form
     *
     * @param      MetaRecord|null  $post   The post
     */
    public static function adminPageForm(?MetaRecord $post)
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
    }

    /**
     * Return HTML plugin specific header on page form
     *
     * @return     string
     */
    public static function adminPageHeaders(): string
    {
        return
        dcPage::jsJson('dcrevisions', [
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
        dcPage::jsModuleLoad('dcRevisions/js/_revision.js', dcCore::app()->getVersion('dcrevisions')) . "\n" .
        dcPage::cssModuleLoad('dcRevisions/css/style.css', 'screen', dcCore::app()->getVersion('dcrevisions')) . "\n";
    }

    /**
     * Add a revision before page update
     *
     * @param      cursor  $cur      The cursor
     * @param      string  $postID   The post identifier
     */
    public static function adminBeforePageUpdate(Cursor $cur, string $postID)
    {
        try {
            dcCore::app()->blog->revisions->addRevision($cur, $postID, 'page');
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    /**
     * Add action for posts
     *
     * @param      dcPostsActions  $ap     Posts' actions
     */
    public static function adminPostsActions(dcPostsActions $ap)
    {
        // Add menuitem in actions dropdown list
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Revisions') => [__('Purge all revisions') => 'revpurge']],
                [BackendBehaviors::class, 'adminPostsDoReplacements']
            );
        }
    }

    /**
     * Add action for pages
     *
     * @param      PagesBackendActions  $ap     Pages' actions
     */
    public static function adminPagesActions(PagesBackendActions $ap)
    {
        // Add menuitem in actions dropdown list
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Revisions') => [__('Purge all revisions') => 'revpurge']],
                [BackendBehaviors::class, 'adminPagesDoReplacements']
            );
        }
    }

    /**
     * Do posts action
     *
     * @param      dcPostsActions  $ap     Posts' actions
     * @param      arrayObject     $post   The post
     */
    public static function adminPostsDoReplacements(dcPostsActions $ap, arrayObject $post)
    {
        self::adminEntriesDoReplacements($ap, $post, 'post');
    }

    /**
     * Do pages action
     *
     * @param      PagesBackendActions  $ap     Pages' actions
     * @param      arrayObject          $post   The post
     */
    public static function adminPagesDoReplacements(PagesBackendActions $ap, arrayObject $post)
    {
        self::adminEntriesDoReplacements($ap, $post, 'page');
    }

    /**
     * Do posts/pages action
     *
     * @param      dcPostsActions|PagesBackendActions   $ap     Posts'/Pages' actions
     * @param      arrayObject                          $post   The post
     */
    public static function adminEntriesDoReplacements($ap, arrayObject $post, string $type = 'post')
    {
        if (!empty($post['dopurge'])) {
            // Do replacements
            $posts = $ap->getRS();
            if ($posts->rows()) {
                while ($posts->fetch()) {
                    // Purge
                    dcCore::app()->blog->revisions->purge($posts->post_id, $type);
                }
                dcPage::addSuccessNotice(__('All revisions have been deleted.'));
                $ap->redirect(true);
            } else {
                $ap->redirect();
            }
        } else {
            // Ask confirmation for replacements
            if ($type == 'page') {
                $ap->beginPage(
                    dcPage::breadcrumb(
                        [
                            Html::escapeHTML(dcCore::app()->blog->name) => '',
                            __('Pages')                                 => 'plugin.php?p=pages',
                            __('Purge all revisions')                   => '',
                        ]
                    )
                );
            } else {
                $ap->beginPage(
                    dcPage::breadcrumb(
                        [
                            Html::escapeHTML(dcCore::app()->blog->name) => '',
                            __('Entries')                               => 'posts.php',
                            __('Purge all revisions')                   => '',
                        ]
                    )
                );
            }

            dcPage::warning(__('CAUTION: This operation will delete all the revisions. Are you sure to want to do this?'), false, false);

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<p><input type="submit" value="' . __('save') . '" /></p>' .

            dcCore::app()->formNonce() . $ap->getHiddenFields() .
            form::hidden(['dopurge'], 'true') .
            form::hidden(['action'], 'revpurge') .
                '</form>';
            $ap->endPage();
        }
    }
}
