<?php
/**
 * @brief dcRevisions, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author TomTom, Franck Paul and contributors
 *
 * @copyright TomTom, Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class dcRevisionsBehaviors
{
    public static function adminBlogPreferencesForm($core, $settings)
    {
        if (dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check('contentadmin', dcCore::app()->blog->id)) {
            $settings->addNameSpace('dcrevisions');

            echo
            '<div class="fieldset"><h4 id="dc-revisions">' . __('Revisions') . '</h4>' .
            '<p><label class="classic" for="dcrevisions_enable">' .
            form::checkbox('dcrevisions_enable', 1, $settings->dcrevisions->enable) .
            __('Enable entries\' versionning on this blog') . '</label></p>' .
                '</div>';
        }
    }

    public static function adminBeforeBlogSettingsUpdate($settings)
    {
        if (dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check('contentadmin', dcCore::app()->blog->id)) {
            $settings->addNameSpace('dcrevisions');
            $settings->dcrevisions->put('enable', empty($_POST['dcrevisions_enable']) ? false : true);
        }
    }

    public static function adminPostForm($post)
    {
        $id        = isset($post) && !$post->isEmpty() ? $post->post_id : null;
        $url       = sprintf('post.php?id=%1$s&amp;patch=%2$s', $id, '%s');
        $purge_url = sprintf('post.php?id=%1$s&amp;revpurge=1', $id);

        $params = [
            'post_id'   => $id,
            'post_type' => 'post',
        ];

        if (is_null($id)) {
            $rs       = staticRecord::newFromArray([]);
            $rs->core = dcCore::app();
        } else {
            $rs = dcCore::app()->blog->revisions->getRevisions($params);
        }

        $list = new dcRevisionsList($rs);

        echo '<div class="area" id="revisions-area"><label>' . __('Revisions:') . '</label>' . $list->display($url) .
            ($list->count() ? '<a href="' . $purge_url . '" class="button delete" id="revpurge">' . __('Purge all revisions') . '</a>' : '') .
            '</div>';
    }

    public static function adminPostHeaders()
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
        dcPage::cssModuleLoad('dcRevisions/style.css', 'screen', dcCore::app()->getVersion('dcrevisions')) . "\n";
    }

    public static function adminBeforePostUpdate($cur, $post_id)
    {
        try {
            dcCore::app()->blog->revisions->addRevision($cur, $post_id, 'post');
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function adminPageForm($post)
    {
        global $redir_url;

        $id        = isset($post) && !$post->isEmpty() ? $post->post_id : null;
        $url       = sprintf($redir_url . '&amp;id=%1$s&amp;patch=%2$s', $id, '%s');
        $purge_url = sprintf($redir_url . '&amp;id=%1$s&amp;revpurge=1', $id);

        $params = [
            'post_id'   => $id,
            'post_type' => 'page',
        ];

        $rs = dcCore::app()->blog->revisions->getRevisions($params);

        if (is_null($id)) {
            $rs       = staticRecord::newFromArray([]);
            $rs->core = dcCore::app();
        }

        $list = new dcRevisionsList($rs);

        echo '<div class="area" id="revisions-area"><label>' . __('Revisions:') . '</label>' . $list->display($url) .
            ($list->count() ? '<a href="' . $purge_url . '" class="button delete" id="revpurge">' . __('Purge all revisions') . '</a>' : '') .
            '</div>';
    }

    public static function adminPageHeaders()
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
        dcPage::cssModuleLoad('dcRevisions/style.css', 'screen', dcCore::app()->getVersion('dcrevisions')) . "\n";
    }

    public static function adminBeforePageUpdate($cur, $post_id)
    {
        try {
            dcCore::app()->blog->revisions->addRevision($cur, $post_id, 'page');
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function adminPostsActionsPage($core, $ap)
    {
        // Add menuitem in actions dropdown list
        if (dcCore::app()->auth->check('contentadmin', dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Revisions') => [__('Purge all revisions') => 'revpurge']],
                ['dcRevisionsBehaviors', 'adminPostsDoReplacements']
            );
        }
    }

    public static function adminPagesActionsPage($core, $ap)
    {
        // Add menuitem in actions dropdown list
        if (dcCore::app()->auth->check('contentadmin', dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Revisions') => [__('Purge all revisions') => 'revpurge']],
                ['dcRevisionsBehaviors', 'adminPagesDoReplacements']
            );
        }
    }

    public static function adminPostsDoReplacements($core, dcPostsActionsPage $ap, $post)
    {
        self::adminEntriesDoReplacements(dcCore::app(), $ap, $post, 'post');
    }

    public static function adminPagesDoReplacements($core, dcPostsActionsPage $ap, $post)
    {
        self::adminEntriesDoReplacements(dcCore::app(), $ap, $post, 'page');
    }

    public static function adminEntriesDoReplacements($core, dcPostsActionsPage $ap, $post, $type = 'post')
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
                            html::escapeHTML(dcCore::app()->blog->name) => '',
                            __('Pages')                                 => 'plugin.php?p=pages',
                            __('Purge all revisions')                   => '',
                        ]
                    )
                );
            } else {
                $ap->beginPage(
                    dcPage::breadcrumb(
                        [
                            html::escapeHTML(dcCore::app()->blog->name) => '',
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
