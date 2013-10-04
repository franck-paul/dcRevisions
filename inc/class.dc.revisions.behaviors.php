<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dcRevisions, a plugin for Dotclear 2.
#
# Copyright (c) TomTom, Franck Paul and contributors
# carnet.franck.paul@gmail.com
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

class dcRevisionsBehaviors
{
	public static function adminBlogPreferencesForm($core,$settings)
	{
		if ($core->auth->isSuperAdmin() || $core->auth->check('contentadmin',$core->blog->id)) {
			$settings->addNameSpace('dcrevisions');

			echo
			'<div class="fieldset"><h4>'.__('Revisions').'</h4>'.
			'<p><label class="classic" for="dcrevisions_enable">'.
			form::checkbox('dcrevisions_enable', 1, $settings->dcrevisions->enable).
			__('Enable entries\' versionning on this blog').'</label></p>'.
			'</div>';
		}
	}

	public static function adminBeforeBlogSettingsUpdate($settings)
	{
		global $core;

		if ($core->auth->isSuperAdmin() || $core->auth->check('contentadmin',$core->blog->id)) {
			$settings->addNameSpace('dcrevisions');
			$settings->dcrevisions->put('enable',empty($_POST['dcrevisions_enable']) ? false : true);
		}
	}

	public static function adminPostForm($post)
	{
		global $core;

		$id = isset($post) && !$post->isEmpty() ? $post->post_id : null;
		$page = isset($_GET['page']) ? $_GET['page'] : 1;
		$url = sprintf('post.php?id=%1$s&amp;patch=%2$s',$id,'%s');

		$params = array(
			'post_id' => $id,
			'post_type' => 'post'
		);

		$rs = $core->blog->revisions->getRevisions($params);

		if (is_null($id)) {
			$rs = staticRecord::newFromArray(array());
			$rs->core = $core;
		}

		$list = new dcRevisionsList($rs);

		echo '<div class="area" id="revisions-area"><label>'.__('Revisions:').'</label>'.
		$list->display($url).
		'</div>';
	}

	public static function adminPostHeaders()
	{
		return
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		dcPage::jsVar('dotclear.post_type','post').
		dcPage::jsVar('dotclear.msg.excerpt',__('Excerpt')).
		dcPage::jsVar('dotclear.msg.content',__('Content')).
		dcPage::jsVar('dotclear.msg.current',__('Current')).
		dcPage::jsVar('dotclear.msg.revision',__('Rev.')).
		dcPage::jsVar('dotclear.msg.content_identical',__('Content identical')).
		dcPage::jsVar('dotclear.msg.confirm_apply_patch',
			__('CAUTION: This operation will replace all the content by the previous one. Are you sure to want apply this patch on this entry?')
		).
		"\n//]]>\n".
		"</script>\n".
		'<script type="text/javascript" src="index.php?pf=dcRevisions/js/_revision.js"></script>'."\n".
		'<link rel="stylesheet" type="text/css" href="index.php?pf=dcRevisions/style.css" />';
	}

	public static function adminBeforePostUpdate($cur,$post_id)
	{
		global $core;

		try {
			$core->blog->revisions->addRevision($cur,$post_id,'post');
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}

	public static function adminPageForm($post)
	{
		global $core,$redir_url;

		$id = isset($post) && !$post->isEmpty() ? $post->post_id : null;
		$url = sprintf($redir_url.'&amp;id=%1$s&amp;patch=%2$s',$id,'%s');

		$params = array(
			'post_id' => $id,
			'post_type' => 'page'
		);

		$rs = $core->blog->revisions->getRevisions($params);

		if (is_null($id)) {
			$rs = staticRecord::newFromArray(array());
			$rs->core = $core;
		}

		$list = new dcRevisionsList($rs);

		echo '<div class="area" id="revisions-area"><label>'.__('Revisions:').'</label>'.
		$list->display($url).
		'</div>';
	}

	public static function adminPageHeaders()
	{
		return
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		dcPage::jsVar('dotclear.post_type','page').
		dcPage::jsVar('dotclear.msg.excerpt',__('Excerpt')).
		dcPage::jsVar('dotclear.msg.content',__('Content')).
		dcPage::jsVar('dotclear.msg.current',__('Current')).
		dcPage::jsVar('dotclear.msg.content_identical',__('Content identical')).
		dcPage::jsVar('dotclear.msg.confirm_apply_patch',
			__('CAUTION: This operation will replace all the content by the previous one. Are you sure to want apply this patch on this page?')
		).
		"\n//]]>\n".
		"</script>\n".
		'<script type="text/javascript" src="index.php?pf=dcRevisions/js/_revision.js"></script>'."\n".
		'<link rel="stylesheet" type="text/css" href="index.php?pf=dcRevisions/style.css" />';
	}

	public static function adminBeforePageUpdate($cur,$post_id)
	{
		global $core;

		try {
			$core->blog->revisions->addRevision($cur,$post_id,'page');
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
}
