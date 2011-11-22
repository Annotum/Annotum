<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2011 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */

if (!defined('ANNO_PLUGIN_PATH')) {
	define('ANNO_PLUGIN_PATH', trailingslashit(get_template_directory()).'plugins/');
}

include_once(ANNO_PLUGIN_PATH.'workflow/workflow-init.php');
include_once(ANNO_PLUGIN_PATH.'cf-archive-title/cf-archive-title.php');
include_once(ANNO_PLUGIN_PATH.'cf-revisions-manager/cf-revision-manager.php');
include_once(ANNO_PLUGIN_PATH.'anno-pdf-download/anno-pdf-download.php');
include_once(ANNO_PLUGIN_PATH.'annotum-importers/knol-importer.php');
include_once(ANNO_PLUGIN_PATH.'annotum-importers/dtd-importer.php');



?>