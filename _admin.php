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

if (!defined('DC_CONTEXT_ADMIN')) { return; }

// dead but useful code, in order to have translations
__('dcRevisions').__('Allows entries\'s versionning');

$core->addBehavior('adminBlogPreferencesForm',array('dcRevisionsBehaviors','adminBlogPreferencesForm'));
$core->addBehavior('adminBeforeBlogSettingsUpdate',array('dcRevisionsBehaviors','adminBeforeBlogSettingsUpdate'));

$core->blog->settings->addNameSpace('dcrevisions');

if ($core->blog->settings->dcrevisions->enable) {

	$core->addBehavior('adminPostHeaders',array('dcRevisionsBehaviors','adminPostHeaders'));
	$core->addBehavior('adminPostForm',array('dcRevisionsBehaviors','adminPostForm'));

	$core->addBehavior('adminBeforePostUpdate',array('dcRevisionsBehaviors','adminBeforePostUpdate'));

	$core->addBehavior('adminPageHeaders',array('dcRevisionsBehaviors','adminPageHeaders'));
	$core->addBehavior('adminPageForm',array('dcRevisionsBehaviors','adminPageForm'));

	$core->addBehavior('adminBeforePageUpdate',array('dcRevisionsBehaviors','adminBeforePageUpdate'));

	$core->rest->addFunction('getPatch',array('dcRevisionsRestMethods','getPatch'));

	$core->blog->revisions = new dcRevisions($core);

	if (isset($_GET['id']) && isset($_GET['patch']) && 
		preg_match('/post.php\?id=[0-9]+(.*)$/',$_SERVER['REQUEST_URI']))
	{
		$redir_url = 'post.php?id=%s&upd=1';
		$core->blog->revisions->setPatch($_GET['id'],$_GET['patch'],'post',$redir_url,'adminBeforePostUpdate','adminAfterPostUpdate');
	}
	elseif (isset($_GET['id']) && isset($_GET['patch']) && 
		preg_match('/plugin.php\?p=pages\&act=page\&id=[0-9]+(.*)$/',$_SERVER['REQUEST_URI'])) 
	{
		$redir_url = 'plugin.php?p=pages&act=page&id=%s&upd=1';
		$core->blog->revisions->setPatch($_GET['id'],$_GET['patch'],'page',$redir_url,'adminBeforePageUpdate','adminAfterPageUpdate');
	}
}
?>