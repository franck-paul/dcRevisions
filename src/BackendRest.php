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
use Dotclear\Helper\Diff\Diff;
use Dotclear\Helper\Diff\TidyDiff;
use Dotclear\Helper\Html\XmlTag;
use Exception;

class BackendRest
{
    /**
     * Gets the patch.
     *
     * @throws     Exception
     *
     * @return     XmlTag     The patch.
     */
    public static function getPatch(): XmlTag
    {
        // Get data helpers
        $_Int = fn (string $name, int $default = 0): int => isset($_GET[$name]) && is_numeric($val = $_GET[$name]) ? (int) $val : $default;
        $_Str = fn (string $name, string $default = ''): string => isset($_GET[$name]) && is_string($val = $_GET[$name]) ? $val : $default;

        $post_id     = $_Int('pid');
        $revision_id = $_Int('rid');
        $type        = $_Str('type', 'post');

        if ($post_id === 0) {
            throw new Exception(__('No post ID'));
        }

        if ($revision_id === 0) {
            throw new Exception(__('No revision ID'));
        }

        $rs  = App::blog()->getPosts(['post_id' => $post_id, 'post_type' => $type]);
        $old = [
            'post_excerpt'       => $rs->strField('post_excerpt'),
            'post_excerpt_xhtml' => $rs->strField('post_excerpt_xhtml'),
            'post_content'       => $rs->strField('post_content'),
            'post_content_xhtml' => $rs->strField('post_content_xhtml'),
        ];

        if (!App::backend()->revisions instanceof Revisions) {
            throw new Exception(__('Revisions not initialized'));
        }

        $new = App::backend()->revisions->getPatch($post_id, $revision_id, $type);

        $rsp = new XmlTag();
        foreach ($old as $field => $value) {
            $rsp->insertNode(self::buildNode($value, $new[$field], 2, $field));
        }

        return $rsp;
    }

    /**
     * Builds a node.
     *
     * @param      string  $src    The source
     * @param      string  $dst    The destination
     * @param      int     $ctx    The context
     * @param      string  $root   The root
     *
     * @return     XmlTag  The node.
     */
    private static function buildNode(string $src, string $dst, int $ctx, string $root): XmlTag
    {
        $uniDiff  = Diff::uniDiff($src, $dst, $ctx);
        $tidyDiff = new TidyDiff(htmlspecialchars($uniDiff), true);

        $rev = new XmlTag($root);

        foreach ($tidyDiff->getChunks() as $k => $chunk) {
            foreach ($chunk->getLines() as $line) {
                switch ($line->type) {
                    case 'context':
                        $node        = new XmlTag('context');
                        $node->oline = $line->lines[0];
                        $node->nline = $line->lines[1];
                        $node->insertNode($line->content);
                        $rev->insertNode($node);

                        break;
                    case 'delete':
                        $node        = new XmlTag('delete');
                        $node->oline = $line->lines[0];
                        $content     = str_replace(['\0', '\1'], ['<del>', '</del>'], $line->content);
                        $node->insertNode($content);
                        $rev->insertNode($node);

                        break;
                    case 'insert':
                        $node        = new XmlTag('insert');
                        $node->nline = $line->lines[1];
                        $content     = str_replace(['\0', '\1'], ['<ins>', '</ins>'], $line->content);
                        $node->insertNode($content);
                        $rev->insertNode($node);

                        break;
                }
            }

            if ($k < count($tidyDiff->getChunks()) - 1) {
                $node = new XmlTag('skip');
                $rev->insertNode($node);
            }
        }

        return $rev;
    }
}
