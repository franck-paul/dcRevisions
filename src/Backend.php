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

use dcCore;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_CONTEXT_ADMIN');

        // dead but useful code, in order to have translations
        __('dcRevisions') . __('Allows entries\'s versionning');

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'adminBlogPreferencesFormV2'    => [BackendBehaviors::class, 'adminBlogPreferencesForm'],
            'adminBeforeBlogSettingsUpdate' => [BackendBehaviors::class, 'adminBeforeBlogSettingsUpdate'],
        ]);

        if (dcCore::app()->blog->settings->dcrevisions->enable) {
            dcCore::app()->addBehaviors([
                'adminPostHeaders' => [BackendBehaviors::class, 'adminPostHeaders'],
                'adminPostForm'    => [BackendBehaviors::class, 'adminPostForm'],

                'adminBeforePostUpdate' => [BackendBehaviors::class, 'adminBeforePostUpdate'],

                'adminPageHeaders' => [BackendBehaviors::class, 'adminPageHeaders'],
                'adminPageForm'    => [BackendBehaviors::class, 'adminPageForm'],

                'adminBeforePageUpdate' => [BackendBehaviors::class, 'adminBeforePageUpdate'],

                /* Add behavior callbacks for posts actions */
                'adminPostsActions' => [BackendBehaviors::class, 'adminPostsActions'],
                'adminPagesActions' => [BackendBehaviors::class, 'adminPagesActions'],
            ]);

            dcCore::app()->rest->addFunction('getPatch', [BackendRest::class, 'getPatch']);

            dcCore::app()->blog->revisions = new Revisions();

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

        return true;
    }
}
