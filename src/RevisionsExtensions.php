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

use dcCore;
use dcUtils;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
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
        $format === null ? $format = dcCore::app()->blog->settings->system->date_format : $format;

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
        $format === null ? $format = dcCore::app()->blog->settings->system->time_format : $format;

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
        return dcUtils::getUserCN($rs->user_id, $rs->user_name, $rs->user_firstname, $rs->user_displayname);
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
            $res = '<a href="%2$s">%1$s</a>';
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
        if (dcCore::app()->auth->isSuperAdmin()) {
            return true;
        }

        # If user is admin or contentadmin, true
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            return true;
        }

        # No user id in result ? false
        if (!$rs->exists('user_id')) {
            return false;
        }

        # If user is usage and owner of the entry
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_USAGE,
        ]), dcCore::app()->blog->id)
            && $rs->user_id == dcCore::app()->auth->userID()) {
            return true;
        }

        return false;
    }
}
