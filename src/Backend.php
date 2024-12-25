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
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        // dead but useful code, in order to have translations
        __('dcRevisions') . __('Allows entries\'s versionning');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            'adminBlogPreferencesFormV2'    => BackendBehaviors::adminBlogPreferencesForm(...),
            'adminBeforeBlogSettingsUpdate' => BackendBehaviors::adminBeforeBlogSettingsUpdate(...),
        ]);

        $settings = My::settings();
        if ($settings->enable) {
            App::behavior()->addBehaviors([
                'adminPostHeaders' => BackendBehaviors::adminPostHeaders(...),
                'adminPostForm'    => BackendBehaviors::adminPostForm(...),

                'adminBeforePostUpdate' => BackendBehaviors::adminBeforePostUpdate(...),

                'adminPageHeaders' => BackendBehaviors::adminPageHeaders(...),
                'adminPageForm'    => BackendBehaviors::adminPageForm(...),

                'adminBeforePageUpdate' => BackendBehaviors::adminBeforePageUpdate(...),

                /* Add behavior callbacks for posts actions */
                'adminPostsActions' => BackendBehaviors::adminPostsActions(...),
                'adminPagesActions' => BackendBehaviors::adminPagesActions(...),
            ]);

            // REST method
            App::rest()->addFunction('getPatch', BackendRest::getPatch(...));

            // Init Revision object
            App::blog()->revisions = new Revisions();

            if (isset($_GET['id']) && (isset($_GET['patch']) || isset($_GET['revpurge']))) {
                // We have a post or a page ID
                if (preg_match('/index.php\?process=Post\&id=\d+(.*)$/', (string) $_SERVER['REQUEST_URI'])) {
                    // It's a post
                    $redirURL = App::backend()->url()->get('admin.post', ['id' => '%s'], '&', true);
                    if (isset($_GET['patch'])) {
                        // Patch
                        $redirURL .= '&upd=1';
                        App::blog()->revisions->setPatch($_GET['id'], $_GET['patch'], 'post', $redirURL, 'adminBeforePostUpdate', 'adminAfterPostUpdate');
                    } else {
                        // Purge
                        App::blog()->revisions->purge($_GET['id'], 'post', $redirURL);
                    }
                } elseif (preg_match('/index.php\?process=Plugin\&p=pages\&act=page\&id=\d+(.*)$/', (string) $_SERVER['REQUEST_URI'])) {
                    // It's a page
                    $redirURL = App::backend()->url()->get('admin.plugin.pages', ['act' => 'page', 'id' => '%s'], '&', true);
                    if (isset($_GET['patch'])) {
                        // Patch
                        $redirURL .= '&upd=1';
                        App::blog()->revisions->setPatch($_GET['id'], $_GET['patch'], 'page', $redirURL, 'adminBeforePageUpdate', 'adminAfterPageUpdate');
                    } else {
                        // Purge
                        App::blog()->revisions->purge($_GET['id'], 'page', $redirURL);
                    }
                }
            }
        }

        return true;
    }
}
