<?php

/**
 * @brief dcRevisions, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author TomTom, Franck Paul and contributors
 *
 * @copyright Franck Paul contact@open-time.net
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
     * @param      MetaRecord       $metaRecord     Invisible parameter
     * @param      null|string      $format         The format
     *
     * @return     string    The date.
     */
    public static function getDate(MetaRecord $metaRecord, ?string $format = null): string
    {
        $format ??= App::blog()->settings()->get('system')->getStr('date_format', false) ?: '%F';

        $revision_dt = $metaRecord->strField('revision_dt');
        $revision_dt = $metaRecord->strField('revision_dt') ?: 'now';

        $revision_tz = $metaRecord->strField('revision_tz', true);

        return Date::dt2str($format, $revision_dt, $revision_tz);
    }

    /**
     * Gets the time.
     *
     * @param      MetaRecord     $metaRecord      Invisible parameter
     * @param      null|string  $format  The format
     *
     * @return     string       The time.
     */
    public static function getTime(MetaRecord $metaRecord, ?string $format = null): string
    {
        $format ??= App::blog()->settings()->get('system')->getStr('time_format', false) ?: '%T';

        $revision_dt = $metaRecord->strField('revision_dt') ?: 'now';
        $revision_tz = $metaRecord->strField('revision_tz', true);

        return Date::dt2str($format, $revision_dt, $revision_tz);
    }

    /**
     * Gets the author CN.
     *
     * @param      MetaRecord  $metaRecord     Invisible parameter
     *
     * @return     string    The author CN.
     */
    public static function getAuthorCN(MetaRecord $metaRecord): string
    {
        return App::users()->getUserCN(
            $metaRecord->strField('user_id'),
            $metaRecord->strField('user_name'),
            $metaRecord->strField('user_firstname'),
            $metaRecord->strField('user_displayname')
        );
    }

    /**
     * Gets the author link.
     *
     * @param      MetaRecord  $metaRecord     Invisible parameter
     *
     * @return     string    The author link.
     */
    public static function getAuthorLink(MetaRecord $metaRecord): string
    {
        $res = '%1$s';
        $url = $metaRecord->strField('user_url');
        if ($url !== '') {
            $res = (new Link())
                ->href('%2$s')
                ->text('%1$s')
            ->render();
        }

        $author_cn = $metaRecord->getAuthorCN();

        return sprintf($res, Html::escapeHTML($author_cn), Html::escapeHTML($url));
    }

    /**
     * Determines ability to patch.
     *
     * @param      MetaRecord  $metaRecord     Invisible parameter
     *
     * @return     bool      True if able to patch, False otherwise.
     */
    public static function canPatch(MetaRecord $metaRecord): bool
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
        if (!$metaRecord->exists('user_id')) {
            return false;
        }

        # If user is usage and owner of the entry
        return App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
        ]), App::blog()->id())
            && $metaRecord->strField('user_id') === App::auth()->userID();
    }
}
