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

dcCore::app()->addBehaviors([
    'adminBlogPreferencesFormV2'    => [dcRevisionsBehaviors::class, 'adminBlogPreferencesForm'],
    'adminBeforeBlogSettingsUpdate' => [dcRevisionsBehaviors::class, 'adminBeforeBlogSettingsUpdate'],
]);

if (dcCore::app()->blog->settings->dcrevisions->enable) {
    dcCore::app()->addBehaviors([
        'adminPostHeaders'      => [dcRevisionsBehaviors::class, 'adminPostHeaders'],
        'adminPostForm'         => [dcRevisionsBehaviors::class, 'adminPostForm'],

        'adminBeforePostUpdate' => [dcRevisionsBehaviors::class, 'adminBeforePostUpdate'],

        'adminPageHeaders'      => [dcRevisionsBehaviors::class, 'adminPageHeaders'],
        'adminPageForm'         => [dcRevisionsBehaviors::class, 'adminPageForm'],

        'adminBeforePageUpdate' => [dcRevisionsBehaviors::class, 'adminBeforePageUpdate'],

        /* Add behavior callbacks for posts actions */
        'adminPostsActions'     => [dcRevisionsBehaviors::class, 'adminPostsActions'],
        'adminPagesActions'     => [dcRevisionsBehaviors::class, 'adminPagesActions'],
    ]);

    dcCore::app()->rest->addFunction('getPatch', [dcRevisionsRestMethods::class, 'getPatch']);

    dcCore::app()->blog->revisions = new dcRevisions();

    if (isset($_GET['id']) && (isset($_GET['patch']) || isset($_GET['revpurge']))) {
        // We have a post or a page ID
        if (preg_match('/post.php\?id=\d+(.*)$/', (string) $_SERVER['REQUEST_URI'])) {
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
        } elseif (preg_match('/plugin.php\?p=pages\&act=page\&id=\d+(.*)$/', (string) $_SERVER['REQUEST_URI'])) {
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
