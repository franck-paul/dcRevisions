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

use dcBlog;
use dcCore;
use dcNamespace;
use Dotclear\Core\Process;
use Dotclear\Database\Structure;
use Exception;

class Install extends Process
{
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
            $old_version = dcCore::app()->getVersion(My::id());
            if (version_compare((string) $old_version, '3.0', '<')) {
                // Rename settings namespace
                if (dcCore::app()->blog->settings->exists('dcrevisions')) {
                    dcCore::app()->blog->settings->delNamespace(My::id());
                    dcCore::app()->blog->settings->renNamespace('dcrevisions', My::id());
                }
            }

            $settings = dcCore::app()->blog->settings->get(My::id());

            $settings->put('enable', false, dcNamespace::NS_BOOL, 'Enable revisions', false, true);

            // --INSTALL AND UPDATE PROCEDURES--
            $new_structure = new Structure(dcCore::app()->con, dcCore::app()->prefix);

            $new_structure->revision
                ->revision_id('bigint', 0, false)
                ->post_id('bigint', 0, false)
                ->user_id('varchar', 32, false)
                ->blog_id('varchar', 32, false)
                ->revision_dt('timestamp', 0, false, 'now()')
                ->revision_tz('varchar', 128, false, "'UTC'")
                ->revision_type('varchar', 50, true, null)
                ->revision_excerpt_diff('text', 0, true, null)
                ->revision_excerpt_xhtml_diff('text', 0, true, null)
                ->revision_content_diff('text', 0, true, null)
                ->revision_content_xhtml_diff('text', 0, true, null)
            ;

            $new_structure->revision->primary('pk_revision', 'revision_id');

            $new_structure->revision->index('idx_revision_post_id', 'btree', 'post_id');

            $new_structure->revision->reference('fk_revision_post', 'post_id', dcBlog::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');
            $new_structure->revision->reference('fk_revision_blog', 'blog_id', dcBlog::BLOG_TABLE_NAME, 'blog_id', 'cascade', 'cascade');

            $current_structure = new Structure(dcCore::app()->con, dcCore::app()->prefix);
            $current_structure->synchronize($new_structure);

            // Init
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }
}
