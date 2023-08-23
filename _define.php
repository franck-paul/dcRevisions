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
$this->registerModule(
    'dcRevisions',
    'Allows entries versionning',
    'Tomtom, Franck Paul & contributors',
    '4.0',
    [
        'requires'    => [['core', '2.27'], ['php', '8.1']],
        'permissions' => dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]),
        'type'     => 'plugin',
        'settings' => [
            'blog' => '#params.dc-revisions',
        ],

        'details'    => 'https://open-time.net/?q=dcRevisions',
        'support'    => 'https://github.com/franck-paul/dcRevisions',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/dcRevisions/master/dcstore.xml',
    ]
);
