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
use Dotclear\Helper\Process\TraitProcess;

class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        // dead but useful code, in order to have translations
        __('dcRevisions');
        __('Allows entries\'s versionning');

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
            App::backend()->revisions = new Revisions();

            if (isset($_GET['id']) && (isset($_GET['patch']) || isset($_GET['revpurge']))) {
                // We have a post or a page ID
                $id = is_numeric($id = $_GET['id']) ? (int) $id : 0;
                if ($id > 0) {
                    $request_uri = isset($_SERVER['REQUEST_URI']) && is_string($request_uri = $_SERVER['REQUEST_URI']) ? $request_uri : '';
                    if ($request_uri !== '' && preg_match('/index.php\?process=Post\&id=\d+(.*)$/', $request_uri)) {
                        // It's a post
                        $redir_url = App::backend()->url()->get('admin.post', ['id' => '%s'], '&', true);
                        if (isset($_GET['patch'])) {
                            // Patch
                            $patch = is_numeric($patch = $_GET['patch']) ? (int) $patch : 0;
                            $redir_url .= '&upd=1';
                            App::backend()->revisions->setPatch($id, $patch, 'post', $redir_url, 'adminBeforePostUpdate', 'adminAfterPostUpdate');
                        } else {
                            // Purge
                            App::backend()->revisions->purge($id, 'post', $redir_url);
                        }
                    } elseif (preg_match('/index.php\?process=Plugin\&p=pages\&act=page\&id=\d+(.*)$/', $request_uri)) {
                        // It's a page
                        $redir_url = App::backend()->url()->get('admin.plugin.pages', ['act' => 'page', 'id' => '%s'], '&', true);
                        if (isset($_GET['patch'])) {
                            // Patch
                            $patch = is_numeric($patch = $_GET['patch']) ? (int) $patch : 0;
                            $redir_url .= '&upd=1';
                            App::backend()->revisions->setPatch($id, $patch, 'page', $redir_url, 'adminBeforePageUpdate', 'adminAfterPageUpdate');
                        } else {
                            // Purge
                            App::backend()->revisions->purge($id, 'page', $redir_url);
                        }
                    }
                }
            }
        }

        return true;
    }
}
