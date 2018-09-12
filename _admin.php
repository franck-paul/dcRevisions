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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

// dead but useful code, in order to have translations
__('dcRevisions') . __('Allows entries\'s versionning');

$core->addBehavior('adminBlogPreferencesForm', ['dcRevisionsBehaviors', 'adminBlogPreferencesForm']);
$core->addBehavior('adminBeforeBlogSettingsUpdate', ['dcRevisionsBehaviors', 'adminBeforeBlogSettingsUpdate']);

$core->blog->settings->addNameSpace('dcrevisions');

if ($core->blog->settings->dcrevisions->enable) {

    $core->addBehavior('adminPostHeaders', ['dcRevisionsBehaviors', 'adminPostHeaders']);
    $core->addBehavior('adminPostForm', ['dcRevisionsBehaviors', 'adminPostForm']);

    $core->addBehavior('adminBeforePostUpdate', ['dcRevisionsBehaviors', 'adminBeforePostUpdate']);

    $core->addBehavior('adminPageHeaders', ['dcRevisionsBehaviors', 'adminPageHeaders']);
    $core->addBehavior('adminPageForm', ['dcRevisionsBehaviors', 'adminPageForm']);

    $core->addBehavior('adminBeforePageUpdate', ['dcRevisionsBehaviors', 'adminBeforePageUpdate']);

    $core->rest->addFunction('getPatch', ['dcRevisionsRestMethods', 'getPatch']);

    $core->blog->revisions = new dcRevisions($core);

    if (isset($_GET['id']) && isset($_GET['patch']) &&
        preg_match('/post.php\?id=[0-9]+(.*)$/', $_SERVER['REQUEST_URI'])) {
        $redir_url = 'post.php?id=%s&upd=1';
        $core->blog->revisions->setPatch($_GET['id'], $_GET['patch'], 'post', $redir_url, 'adminBeforePostUpdate', 'adminAfterPostUpdate');
    } elseif (isset($_GET['id']) && isset($_GET['patch']) &&
        preg_match('/plugin.php\?p=pages\&act=page\&id=[0-9]+(.*)$/', $_SERVER['REQUEST_URI'])) {
        $redir_url = 'plugin.php?p=pages&act=page&id=%s&upd=1';
        $core->blog->revisions->setPatch($_GET['id'], $_GET['patch'], 'page', $redir_url, 'adminBeforePageUpdate', 'adminAfterPageUpdate');
    }
}
