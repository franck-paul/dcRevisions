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
class dcRevisionsExtensions
{
    public static function getDate($rs, $format = null)
    {
        $format === null ? $format = $rs->core->blog->settings->system->date_format : $format;

        return dt::dt2str($format, $rs->revision_dt, $rs->revision_tz);
    }

    public static function getTime($rs, $format = null)
    {
        $format === null ? $format = $rs->core->blog->settings->system->time_format : $format;

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
        if ($rs->core->auth->isSuperAdmin()) {
            return true;
        }

        # If user is admin or contentadmin, true
        if ($rs->core->auth->check('contentadmin', $rs->core->blog->id)) {
            return true;
        }

        # No user id in result ? false
        if (!$rs->exists('user_id')) {
            return false;
        }

        # If user is usage and owner of the entry
        if ($rs->core->auth->check('usage', $rs->core->blog->id)
            && $rs->user_id == $rs->core->auth->userID()) {
            return true;
        }

        return false;
    }
}
