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

if (!defined('DC_RC_PATH')) { return; }

$this->registerModule(
		/* Name */			"dcRevisions",
		/* Description*/		"Allows entries's versionning",
		/* Author */			"Tomtom, Franck Paul & contributors",
		/* Version */			'0.3',
		/* Permissions */		'usage,contentadmin'
);
?>