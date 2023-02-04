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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

if (!dcCore::app()->newVersion(basename(__DIR__), dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version'))) {
    return;
}

dcCore::app()->blog->settings->dcrevisions->put(
    'enable',
    false,
    'boolean',
    'Enable revisions',
    false,
    true
);

# --INSTALL AND UPDATE PROCEDURES--
$s = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);

$s->revision
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

$s->revision->primary('pk_revision', 'revision_id');

$s->revision->index('idx_revision_post_id', 'btree', 'post_id');

$s->revision->reference('fk_revision_post', 'post_id', dcBlog::POST_TABLE_NAME, 'post_id', 'cascade', 'cascade');
$s->revision->reference('fk_revision_blog', 'blog_id', dcBlog::BLOG_TABLE_NAME, 'blog_id', 'cascade', 'cascade');

$si = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);

try {
    $changes = $si->synchronize($s);
} catch (Exception $e) {
    dcCore::app()->error->add($e);
}

return true;
