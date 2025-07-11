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
    '6.2',
    [
        'date'        => '2025-06-04T10:32:10+0200',
        'requires'    => [['core', '2.34']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'blog' => '#params.dc-revisions',
        ],

        'details'    => 'https://open-time.net/?q=dcRevisions',
        'support'    => 'https://github.com/franck-paul/dcRevisions',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/dcRevisions/main/dcstore.xml',
        'license'    => 'gpl2',
    ]
);
