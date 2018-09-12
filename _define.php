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

if (!defined('DC_RC_PATH')) {return;}

$this->registerModule(
    "dcRevisions",                        // Name
    "Allows entries's versionning",       // Description
    "Tomtom, Franck Paul & contributors", // Author
    '0.4',                                // Version
    [
        'requires'    => [['core', '2.13']],   // Dependencies
        'permissions' => 'usage,contentadmin', // Permissions
        'type'        => 'plugin'             // Type
    ]
);
