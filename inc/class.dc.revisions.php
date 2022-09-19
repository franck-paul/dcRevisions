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
class dcRevisions
{
    public function getRevisions($params, $count_only = false)
    {
        if ($count_only) {
            $f = 'COUNT(revision_id)';
        } else {
            $f = 'R.revision_id, R.post_id, R.user_id, R.revision_type, ' .
                'R.revision_dt, R.revision_tz, R.revision_excerpt_diff, ' .
                'R.revision_excerpt_xhtml_diff, R.revision_content_diff, ' .
                'R.revision_content_xhtml_diff, U.user_url, U.user_name, ' .
                'U.user_firstname, U.user_displayname';
        }

        $strReq = 'SELECT ' . $f . ' FROM ' . dcCore::app()->prefix . 'revision R ' .
        'LEFT JOIN ' . dcCore::app()->prefix . 'user U ON R.user_id = U.user_id ';

        if (!empty($params['from'])) {
            $strReq .= $params['from'] . ' ';
        }

        $strReq .= "WHERE R.blog_id = '" . dcCore::app()->con->escape(dcCore::app()->blog->id) . "' ";

        if (!empty($params['post_id'])) {
            if (is_array($params['post_id'])) {
                array_walk(
                    $params['post_id'],
                    function (&$v) {
                        if ($v !== null) {
                            $v = (int) $v;
                        }
                    }
                );
            } else {
                $params['post_id'] = [(int) $params['post_id']];
            }
            $strReq .= 'AND R.post_id ' . dcCore::app()->con->in($params['post_id']);
        }

        if (!empty($params['revision_id'])) {
            if (is_array($params['revision_id'])) {
                array_walk(
                    $params['revision_id'],
                    function (&$v) {
                        if ($v !== null) {
                            $v = (int) $v;
                        }
                    }
                );
            } else {
                $params['revision_id'] = [(int) $params['revision_id']];
            }
            $strReq .= 'AND R.revision_id ' . dcCore::app()->con->in($params['revision_id']);
        }

        if (isset($params['post_type'])) {
            if (is_array($params['post_type']) && !empty($params['post_type'])) {
                $strReq .= 'AND R.revision_type ' . dcCore::app()->con->in($params['post_type']);
            } elseif ($params['post_type'] != '') {
                $strReq .= "AND R.revision_type = '" . dcCore::app()->con->escape($params['post_type']) . "' ";
            }
        }

        if (!empty($params['sql'])) {
            $strReq .= $params['sql'] . ' ';
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $strReq .= 'ORDER BY ' . dcCore::app()->con->escape($params['order']) . ' ';
            } else {
                $strReq .= 'ORDER BY revision_dt DESC ';
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $strReq .= dcCore::app()->con->limit($params['limit']);
        }

        $rs = dcCore::app()->con->select($strReq);
        $rs->extend('dcRevisionsExtensions');

        return $rs;
    }

    public function addRevision($pcur, $post_id, $type)
    {
        $rs = dcCore::app()->con->select(
            'SELECT MAX(revision_id) ' .
            'FROM ' . dcCore::app()->prefix . 'revision'
        );
        $revision_id = $rs->f(0) + 1;

        $rs = dcCore::app()->blog->getPosts(['post_id' => $post_id, 'post_type' => $type]);

        $old = [
            'post_excerpt'       => $rs->post_excerpt,
            'post_excerpt_xhtml' => $rs->post_excerpt_xhtml,
            'post_content'       => $rs->post_content,
            'post_content_xhtml' => $rs->post_content_xhtml,
        ];
        $new = [
            'post_excerpt'       => $pcur->post_excerpt,
            'post_excerpt_xhtml' => $pcur->post_excerpt_xhtml,
            'post_content'       => $pcur->post_content,
            'post_content_xhtml' => $pcur->post_content_xhtml,
        ];

        $diff = $this->getDiff($new, $old);

        $insert = false;
        foreach ($diff as $v) {
            if ($v !== '') {
                $insert = true;
            }
        }

        if ($insert) {
            $rcur                              = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'revision');
            $rcur->revision_id                 = $revision_id;
            $rcur->post_id                     = $post_id;
            $rcur->user_id                     = dcCore::app()->auth->userID();
            $rcur->blog_id                     = dcCore::app()->blog->id;
            $rcur->revision_dt                 = date('Y-m-d H:i:s');
            $rcur->revision_tz                 = dcCore::app()->auth->getInfo('user_tz');
            $rcur->revision_type               = $type;
            $rcur->revision_excerpt_diff       = $diff['post_excerpt'];
            $rcur->revision_excerpt_xhtml_diff = $diff['post_excerpt_xhtml'];
            $rcur->revision_content_diff       = $diff['post_content'];
            $rcur->revision_content_xhtml_diff = $diff['post_content_xhtml'];

            dcCore::app()->con->writeLock(dcCore::app()->prefix . 'revision');
            $rcur->insert();
            dcCore::app()->con->unlock();
        }
    }

    public function getDiff($n, $o)
    {
        $diff = [
            'post_excerpt'       => '',
            'post_excerpt_xhtml' => '',
            'post_content'       => '',
            'post_content_xhtml' => '',
        ];

        try {
            foreach ($diff as $k => $v) {
                $diff[$k] = diff::uniDiff($n[$k], $o[$k]);
            }

            return $diff;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public function purge($pid, $type, $redirect_url = null)
    {
        if (!$this->canPurge($pid, $type)) {
            throw new Exception(__('You are not allowed to delete revisions of this entry'));
        }

        try {
            // Purge all revisions of the entry
            $strReq = 'DELETE FROM ' . dcCore::app()->prefix . 'revision ' .
                "WHERE post_id = '" . dcCore::app()->con->escape($pid) . "' ";
            dcCore::app()->con->execute($strReq);

            if (!dcCore::app()->error->flag() && $redirect_url !== null) {
                dcPage::addSuccessNotice(__('All revisions have been deleted.'));
                http::redirect(sprintf($redirect_url, $pid));
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public function setPatch($pid, $rid, $type, $redirect_url, $before_behaviour, $after_behaviour)
    {
        if (!$this->canPatch($rid)) {
            throw new Exception(__('You are not allowed to patch this entry with this revision'));
        }

        try {
            $patch = $this->getPatch($pid, $rid, $type);

            $p = dcCore::app()->blog->getPosts(['post_id' => $pid, 'post_type' => $type]);

            $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'post');

            $cur->post_title        = $p->post_title;
            $cur->cat_id            = $p->cat_id ?: null;
            $cur->post_dt           = $p->post_dt ? date('Y-m-d H:i:00', strtotime($p->post_dt)) : '';
            $cur->post_format       = $p->post_format;
            $cur->post_password     = $p->post_password;
            $cur->post_lang         = $p->post_lang;
            $cur->post_notes        = $p->post_notes;
            $cur->post_status       = $p->post_status;
            $cur->post_selected     = (int) $p->post_selected;
            $cur->post_open_comment = (int) $p->post_open_comment;
            $cur->post_open_tb      = (int) $p->post_open_tb;
            $cur->post_type         = $p->post_type;

            $cur->post_excerpt       = $patch['post_excerpt'];
            $cur->post_excerpt_xhtml = $patch['post_excerpt_xhtml'];
            $cur->post_content       = $patch['post_content'];
            $cur->post_content_xhtml = $patch['post_content_xhtml'];

            # --BEHAVIOR-- adminBeforeXXXXUpdate
            dcCore::app()->callBehavior($before_behaviour, $cur, $pid);

            dcCore::app()->auth->sudo([dcCore::app()->blog, 'updPost'], $pid, $cur);

            # --BEHAVIOR-- adminAfterXXXXUpdate
            dcCore::app()->callBehavior($after_behaviour, $cur, $pid);

            http::redirect(sprintf($redirect_url, $pid));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public function getPatch($pid, $rid, $type)
    {
        $params = [
            'post_id'   => $pid,
            'post_type' => $type,
        ];

        $p = dcCore::app()->blog->getPosts($params);
        $r = $this->getRevisions($params);

        $patch = [
            'post_excerpt'       => $p->post_excerpt,
            'post_excerpt_xhtml' => $p->post_excerpt_xhtml,
            'post_content'       => $p->post_content,
            'post_content_xhtml' => $p->post_content_xhtml,
        ];

        $f = '';
        while ($r->fetch()) {
            foreach ($patch as $k => $v) {
                if ($k === 'post_excerpt') {
                    $f = 'revision_excerpt_diff';
                }
                if ($k === 'post_excerpt_xhtml') {
                    $f = 'revision_excerpt_xhtml_diff';
                }
                if ($k === 'post_content') {
                    $f = 'revision_content_diff';
                }
                if ($k === 'post_content_xhtml') {
                    $f = 'revision_content_xhtml_diff';
                }

                $patch[$k] = diff::uniPatch($v, $r->{$f});
            }

            if ($r->revision_id === $rid) {
                break;
            }
        }

        return $patch;
    }

    protected function canPatch($rid)
    {
        $r = $this->getRevisions(['revision_id' => $rid]);

        return ($r->canPatch());
    }

    protected function canPurge($pid, $type)
    {
        $rs = dcCore::app()->blog->getPosts(['post_id' => $pid, 'post_type' => $type]);

        return ($rs->isEditable());
    }
}
