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
if (!defined('DC_RC_PATH')) {
    return;
}

$__autoload['dcRevisions']            = __DIR__ . '/inc/class.dc.revisions.php';
$__autoload['dcRevisionsRestMethods'] = __DIR__ . '/_services.php';
$__autoload['dcRevisionsBehaviors']   = __DIR__ . '/inc/class.dc.revisions.behaviors.php';
$__autoload['dcRevisionsExtensions']  = __DIR__ . '/inc/class.dc.revisions.extensions.php';
$__autoload['dcRevisionsList']        = __DIR__ . '/inc/class.dc.revisions.list.php';
