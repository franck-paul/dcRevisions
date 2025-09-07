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
use Dotclear\Helper\Process\TraitProcess;
use Exception;

class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            // Update
            $old_version = App::version()->getVersion(My::id());
            // Rename settings namespace
            if (version_compare((string) $old_version, '3.0', '<') && App::blog()->settings()->exists('dcrevisions')) {
                App::blog()->settings()->delWorkspace(My::id());
                App::blog()->settings()->renWorkspace('dcrevisions', My::id());
            }

            $settings = My::settings();

            $settings->put('enable', false, App::blogWorkspace()::NS_BOOL, 'Enable revisions', false, true);

            // --INSTALL AND UPDATE PROCEDURES--
            $new_structure = App::db()->structure();

            $new_structure->revision
                ->field('revision_id', 'bigint', 0, false)
                ->field('post_id', 'bigint', 0, false)
                ->field('user_id', 'varchar', 32, false)
                ->field('blog_id', 'varchar', 32, false)
                ->field('revision_dt', 'timestamp', 0, false, 'now()')
                ->field('revision_tz', 'varchar', 128, false, "'UTC'")
                ->field('revision_type', 'varchar', 50, true, null)
                ->field('revision_excerpt_diff', 'text', 0, true, null)
                ->field('revision_excerpt_xhtml_diff', 'text', 0, true, null)
                ->field('revision_content_diff', 'text', 0, true, null)
                ->field('revision_content_xhtml_diff', 'text', 0, true, null)
            ;

            $new_structure->revision->primary('pk_revision', 'revision_id');

            $new_structure->revision->index('idx_revision_post_id', 'btree', 'post_id');

            $new_structure->revision->reference('fk_revision_post', 'post_id', App::blog()::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');
            $new_structure->revision->reference('fk_revision_blog', 'blog_id', App::blog()::BLOG_TABLE_NAME, 'blog_id', 'cascade', 'cascade');

            $current_structure = App::db()->structure();
            $current_structure->synchronize($new_structure);

            // Init
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return true;
    }
}
