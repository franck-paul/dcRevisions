<?php

/**
 * @brief dcRevisions, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author TomTom, Franck Paul and contributors
 *
 * @copyright TomTom, Franck Paul contact@open-time.net
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

if (isset($this) && is_object($this) && method_exists($this, 'registerModule') && isset($this->id) && is_string($this->id)) {
    $this->registerModule(
        'dcRevisions',
        'Allows entries versionning',
        'Tomtom, Franck Paul & contributors',
        '9.0',
        [
            'date'        => '2026-08-03T09:50:54+0200',
            'requires'    => [['core', '2.39']],
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
}
