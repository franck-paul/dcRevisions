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

use dcAuth;
use dcCore;
use dcUtils;
use dt;
use html;

class RevisionsExtensions
{
    public static function getDate($rs, $format = null)
    {
        $format === null ? $format = dcCore::app()->blog->settings->system->date_format : $format;

        return dt::dt2str($format, $rs->revision_dt, $rs->revision_tz);
    }

    public static function getTime($rs, $format = null)
    {
        $format === null ? $format = dcCore::app()->blog->settings->system->time_format : $format;

        return dt::dt2str($format, $rs->revision_dt, $rs->revision_tz);
    }

    public static function getAuthorCN($rs)
    {
        return dcUtils::getUserCN($rs->user_id, $rs->user_name, $rs->user_firstname, $rs->user_displayname);
    }

    public static function getAuthorLink($rs)
    {
        $res = '%1$s';
        $url = $rs->user_url;
        if ($url) {
            $res = '<a href="%2$s">%1$s</a>';
        }

        return sprintf($res, html::escapeHTML($rs->getAuthorCN()), html::escapeHTML($url));
    }

    public static function canPatch($rs)
    {
        # If user is super admin, true
        if (dcCore::app()->auth->isSuperAdmin()) {
            return true;
        }

        # If user is admin or contentadmin, true
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            return true;
        }

        # No user id in result ? false
        if (!$rs->exists('user_id')) {
            return false;
        }

        # If user is usage and owner of the entry
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
        ]), dcCore::app()->blog->id)
            && $rs->user_id == dcCore::app()->auth->userID()) {
            return true;
        }

        return false;
    }
}
