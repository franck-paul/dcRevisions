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
Clearbricks::lib()->autoload([
    'dcRevisions'            => __DIR__ . '/inc/class.dc.revisions.php',
    'dcRevisionsRestMethods' => __DIR__ . '/_services.php',
    'dcRevisionsBehaviors'   => __DIR__ . '/inc/class.dc.revisions.behaviors.php',
    'dcRevisionsExtensions'  => __DIR__ . '/inc/class.dc.revisions.extensions.php',
    'dcRevisionsList'        => __DIR__ . '/inc/class.dc.revisions.list.php',
]);
