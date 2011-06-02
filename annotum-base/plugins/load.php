<?php

if (!defined('ANNO_PLUGIN_PATH')) {
	define('ANNO_PLUGIN_PATH', trailingslashit(TEMPLATEPATH).'/plugins/');
}

include_once(ANNO_PLUGIN_PATH.'workflow/workflow-load.php');
include_once(ANNO_PLUGIN_PATH.'cf-archive-title/cf-archive-title.php');

?>