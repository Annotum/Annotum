<?php

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