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
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Html;

class RevisionsExtensions
{
    /**
     * Gets the date.
     *
     * @param      MetaRecord     $rs         Invisible parameter
     * @param      null|string  $format     The format
     *
     * @return     string    The date.
     */
    public static function getDate(MetaRecord $rs, ?string $format = null): string
    {
        if ($format === null) {
            $format = App::blog()->settings()->system->date_format;
        }

        return Date::dt2str($format, $rs->revision_dt, $rs->revision_tz);
    }

    /**
     * Gets the time.
     *
     * @param      MetaRecord     $rs      Invisible parameter
     * @param      null|string  $format  The format
     *
     * @return     string       The time.
     */
    public static function getTime(MetaRecord $rs, ?string $format = null): string
    {
        if ($format === null) {
            $format = App::blog()->settings()->system->time_format;
        }

        return Date::dt2str($format, $rs->revision_dt, $rs->revision_tz);
    }

    /**
     * Gets the author CN.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string    The author CN.
     */
    public static function getAuthorCN(MetaRecord $rs): string
    {
        return App::users()->getUserCN($rs->user_id, $rs->user_name, $rs->user_firstname, $rs->user_displayname);
    }

    /**
     * Gets the author link.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string    The author link.
     */
    public static function getAuthorLink(MetaRecord $rs): string
    {
        $res = '%1$s';
        $url = $rs->user_url;
        if ($url) {
            $res = (new Link())
                ->href('%2$s')
                ->text('%1$s')
            ->render();
        }

        return sprintf($res, Html::escapeHTML($rs->getAuthorCN()), Html::escapeHTML($url));
    }

    /**
     * Determines ability to patch.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     bool      True if able to patch, False otherwise.
     */
    public static function canPatch(MetaRecord $rs): bool
    {
        # If user is super admin, true
        if (App::auth()->isSuperAdmin()) {
            return true;
        }

        # If user is admin or contentadmin, true
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            return true;
        }

        # No user id in result ? false
        if (!$rs->exists('user_id')) {
            return false;
        }

        # If user is usage and owner of the entry
        return App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
        ]), App::blog()->id())
            && $rs->user_id == App::auth()->userID();
    }
}
