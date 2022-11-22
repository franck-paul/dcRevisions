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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

// dead but useful code, in order to have translations
__('dcRevisions') . __('Allows entries\'s versionning');

dcCore::app()->addBehavior('adminBlogPreferencesFormV2', [dcRevisionsBehaviors::class, 'adminBlogPreferencesForm']);
dcCore::app()->addBehavior('adminBeforeBlogSettingsUpdate', [dcRevisionsBehaviors::class, 'adminBeforeBlogSettingsUpdate']);

dcCore::app()->blog->settings->addNameSpace('dcrevisions');

if (dcCore::app()->blog->settings->dcrevisions->enable) {
    dcCore::app()->addBehavior('adminPostHeaders', [dcRevisionsBehaviors::class, 'adminPostHeaders']);
    dcCore::app()->addBehavior('adminPostForm', [dcRevisionsBehaviors::class, 'adminPostForm']);

    dcCore::app()->addBehavior('adminBeforePostUpdate', [dcRevisionsBehaviors::class, 'adminBeforePostUpdate']);

    dcCore::app()->addBehavior('adminPageHeaders', [dcRevisionsBehaviors::class, 'adminPageHeaders']);
    dcCore::app()->addBehavior('adminPageForm', [dcRevisionsBehaviors::class, 'adminPageForm']);

    dcCore::app()->addBehavior('adminBeforePageUpdate', [dcRevisionsBehaviors::class, 'adminBeforePageUpdate']);

    /* Add behavior callbacks for posts actions */
    dcCore::app()->addBehavior('adminPostsActions', [dcRevisionsBehaviors::class, 'adminPostsActions']);
    dcCore::app()->addBehavior('adminPagesActions', [dcRevisionsBehaviors::class, 'adminPagesActions']);

    dcCore::app()->rest->addFunction('getPatch', [dcRevisionsRestMethods::class, 'getPatch']);

    dcCore::app()->blog->revisions = new dcRevisions();

    if (isset($_GET['id']) && (isset($_GET['patch']) || isset($_GET['revpurge']))) {
        // We have a post or a page ID
        if (preg_match('/post.php\?id=\d+(.*)$/', $_SERVER['REQUEST_URI'])) {
            // It's a post
            $redir_url = 'post.php?id=%s';
            if (isset($_GET['patch'])) {
                // Patch
                $redir_url .= '&upd=1';
                dcCore::app()->blog->revisions->setPatch($_GET['id'], $_GET['patch'], 'post', $redir_url, 'adminBeforePostUpdate', 'adminAfterPostUpdate');
            } else {
                // Purge
                dcCore::app()->blog->revisions->purge($_GET['id'], 'post', $redir_url);
            }
        } elseif (preg_match('/plugin.php\?p=pages\&act=page\&id=\d+(.*)$/', $_SERVER['REQUEST_URI'])) {
            // It's a page
            $redir_url = 'plugin.php?p=pages&act=page&id=%s';
            if (isset($_GET['patch'])) {
                // Patch
                $redir_url .= '&upd=1';
                dcCore::app()->blog->revisions->setPatch($_GET['id'], $_GET['patch'], 'page', $redir_url, 'adminBeforePageUpdate', 'adminAfterPageUpdate');
            } else {
                // Purge
                dcCore::app()->blog->revisions->purge($_GET['id'], 'page', $redir_url);
            }
        }
    }
}
