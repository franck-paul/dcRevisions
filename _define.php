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
    '5.11',
    [
        'date'        => '2025-02-26T16:08:57+0100',
        'requires'    => [['core', '2.33']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'blog' => '#params.dc-revisions',
        ],

        'details'    => 'https://open-time.net/?q=dcRevisions',
        'support'    => 'https://github.com/franck-paul/dcRevisions',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/dcRevisions/main/dcstore.xml',
    ]
);
