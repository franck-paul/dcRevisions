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

if (!defined('DC_RC_PATH')) {return;}

class dcRevisionsRestMethods
{
    public static function getPatch()
    {
        global $core;

        $pid  = isset($_GET['pid']) ? $_GET['pid'] : null;
        $rid  = isset($_GET['rid']) ? $_GET['rid'] : null;
        $type = isset($_GET['type']) ? $_GET['type'] : 'post';

        if ($pid === null) {
            throw new Exception(__('No post ID'));
        }
        if ($rid === null) {
            throw new Exception(__('No revision ID'));
        }

        $p = $core->blog->getPosts(['post_id' => $pid, 'post_type' => $type]);
        $o = [
            'post_excerpt'       => $p->post_excerpt,
            'post_content'       => $p->post_content,
            'post_excerpt_xhtml' => $p->post_excerpt_xhtml,
            'post_content_xhtml' => $p->post_content_xhtml
        ];

        $n = $core->blog->revisions->getPatch($pid, $rid, $type);

        $rsp = new xmlTag();
        foreach ($o as $k => $v) {
            $rsp->insertNode(self::buildNode($v, $n[$k], 2, $k));
        }
        return $rsp;
    }

    public static function buildNode($src, $dst, $ctx, $root)
    {
        $udiff = diff::uniDiff($src, $dst, $ctx);
        $tdiff = new tidyDiff(htmlspecialchars($udiff), true);

        $rev = new xmlTag($root);

        foreach ($tdiff->getChunks() as $k => $chunk) {
            foreach ($chunk->getLines() as $line) {
                switch ($line->type) {
                    case 'context':
                        $node        = new xmlTag('context');
                        $node->oline = $line->lines[0];
                        $node->nline = $line->lines[1];
                        $node->insertNode($line->content);
                        $rev->insertNode($node);
                        break;
                    case 'delete':
                        $node        = new xmlTag('delete');
                        $node->oline = $line->lines[0];
                        $c           = str_replace(['\0', '\1'], ['<del>', '</del>'], $line->content);
                        $node->insertNode($c);
                        $rev->insertNode($node);
                        break;
                    case 'insert':
                        $node        = new xmlTag('insert');
                        $node->nline = $line->lines[1];
                        $c           = str_replace(['\0', '\1'], ['<ins>', '</ins>'], $line->content);
                        $node->insertNode($c);
                        $rev->insertNode($node);
                        break;
                }
            }
            if ($k < count($tdiff->getChunks()) - 1) {
                $node = new xmlTag('skip');
                $rev->insertNode($node);
            }
        }
        return $rev;
    }
}
