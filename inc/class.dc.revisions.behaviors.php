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
        if ($core->auth->isSuperAdmin() || $core->auth->check('contentadmin', $core->blog->id)) {
            $settings->addNameSpace('dcrevisions');

            echo
            '<div class="fieldset"><h4>' . __('Revisions') . '</h4>' .
            '<p><label class="classic" for="dcrevisions_enable">' .
            form::checkbox('dcrevisions_enable', 1, $settings->dcrevisions->enable) .
            __('Enable entries\' versionning on this blog') . '</label></p>' .
                '</div>';
        }
    }

    public static function adminBeforeBlogSettingsUpdate($settings)
    {
        global $core;

        if ($core->auth->isSuperAdmin() || $core->auth->check('contentadmin', $core->blog->id)) {
            $settings->addNameSpace('dcrevisions');
            $settings->dcrevisions->put('enable', empty($_POST['dcrevisions_enable']) ? false : true);
        }
    }

    public static function adminPostForm($post)
    {
        global $core;

        $id        = isset($post) && !$post->isEmpty() ? $post->post_id : null;
        $url       = sprintf('post.php?id=%1$s&amp;patch=%2$s', $id, '%s');
        $purge_url = sprintf('post.php?id=%1$s&amp;revpurge=1', $id);

        $params = [
            'post_id'   => $id,
            'post_type' => 'post'
        ];

        $rs = $core->blog->revisions->getRevisions($params);

        if (is_null($id)) {
            $rs       = staticRecord::newFromArray([]);
            $rs->core = $core;
        }

        $list = new dcRevisionsList($rs);

        echo '<div class="area" id="revisions-area"><label>' . __('Revisions:') . '</label>' . $list->display($url) .
            ($list->count() ? '<a href="' . $purge_url . '" class="button delete" id="revpurge">' . __('Purge all revisions') . '</a>' : '') .
            '</div>';
    }

    public static function adminPostHeaders()
    {
        global $core;

        return
        '<script type="text/javascript">' . "\n" .
        dcPage::jsVar('dotclear.post_type', 'post') .
        dcPage::jsVar('dotclear.msg.excerpt', __('Excerpt')) .
        dcPage::jsVar('dotclear.msg.content', __('Content')) .
        dcPage::jsVar('dotclear.msg.current', __('Current')) .
        dcPage::jsVar('dotclear.msg.revision', __('Rev.')) .
        dcPage::jsVar('dotclear.msg.content_identical', __('Content identical')) .
        dcPage::jsVar('dotclear.msg.confirm_apply_patch',
            __('CAUTION: This operation will replace all the content by the previous one. Are you sure to want apply this patch on this entry?')) .
        dcPage::jsVar('dotclear.msg.confirm_purge_revision',
            __('CAUTION: This operation will delete all the revisions. Are you sure to want to do this?')) .
        "</script>\n" .
        dcPage::jsLoad(urldecode(dcPage::getPF('dcRevisions/js/_revision.js')), $core->getVersion('dcrevisions')) . "\n" .
        dcPage::cssLoad(urldecode(dcPage::getPF('dcRevisions/style.css')), 'screen', $core->getVersion('dcrevisions')) . "\n";
    }

    public static function adminBeforePostUpdate($cur, $post_id)
    {
        global $core;

        try {
            $core->blog->revisions->addRevision($cur, $post_id, 'post');
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    public static function adminPageForm($post)
    {
        global $core, $redir_url;

        $id        = isset($post) && !$post->isEmpty() ? $post->post_id : null;
        $url       = sprintf($redir_url . '&amp;id=%1$s&amp;patch=%2$s', $id, '%s');
        $purge_url = sprintf($redir_url . '&amp;id=%1$s&amp;revpurge=1', $id);

        $params = [
            'post_id'   => $id,
            'post_type' => 'page'
        ];

        $rs = $core->blog->revisions->getRevisions($params);

        if (is_null($id)) {
            $rs       = staticRecord::newFromArray([]);
            $rs->core = $core;
        }

        $list = new dcRevisionsList($rs);

        echo '<div class="area" id="revisions-area"><label>' . __('Revisions:') . '</label>' . $list->display($url) .
            ($list->count() ? '<a href="' . $purge_url . '" class="button delete" id="revpurge">' . __('Purge all revisions') . '</a>' : '') .
            '</div>';
    }

    public static function adminPageHeaders()
    {
        global $core;

        return
        '<script type="text/javascript">' . "\n" .
        dcPage::jsVar('dotclear.post_type', 'page') .
        dcPage::jsVar('dotclear.msg.excerpt', __('Excerpt')) .
        dcPage::jsVar('dotclear.msg.content', __('Content')) .
        dcPage::jsVar('dotclear.msg.current', __('Current')) .
        dcPage::jsVar('dotclear.msg.content_identical', __('Content identical')) .
        dcPage::jsVar('dotclear.msg.confirm_apply_patch',
            __('CAUTION: This operation will replace all the content by the previous one. Are you sure to want apply this patch on this page?')) .
        dcPage::jsVar('dotclear.msg.confirm_purge_revision',
            __('CAUTION: This operation will delete all the revisions. Are you sure to want to do this?')) .
        "</script>\n" .
        dcPage::jsLoad(urldecode(dcPage::getPF('dcRevisions/js/_revision.js')), $core->getVersion('dcrevisions')) . "\n" .
        dcPage::cssLoad(urldecode(dcPage::getPF('dcRevisions/style.css')), 'screen', $core->getVersion('dcrevisions')) . "\n";
    }

    public static function adminBeforePageUpdate($cur, $post_id)
    {
        global $core;

        try {
            $core->blog->revisions->addRevision($cur, $post_id, 'page');
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
}
