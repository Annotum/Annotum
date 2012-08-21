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

/**
 * 
 * @param string $key option name to get
 * @param mixed $default What to return if the given option is not set
 * @return mixed
 */ 
function annowf_get_option($key, $default = false) {
	$option = cfct_get_option('workflow_settings');

	if (!isset($option[$key])) {
		return $default;
	}
	else {
		return $option[$key];
	}
}

/**
 * Helper function to determine if the workflow is enabled
 * 
 * @param string $option Name of the workflow option to check. Defaults to workflow (entire workflow enabled/disabled)
 * @return mixed true(1) if the workflow is enabled, false(null) otherwise
 */ 
function anno_workflow_enabled($option = null) {
	if (empty($option)) {
		$option = 'workflow';
	}
	
	return annowf_get_option($option);
}

function annowf_setup() {
	if (anno_workflow_enabled()) {
		// Used in generating save buttons and proccess state changes
		global $anno_post_save;
		$anno_post_save = array(
			'approve' => _x('Approve', 'Publishing box action button text', 'anno'),
			'publish' => _x('Publish', 'Publishing box action button text', 'anno'),
			'reject' => _x('Reject', 'Publishing box action button text', 'anno'),
			'review' => _x('Submit For Review', 'Publishing box action button text', 'anno'),
			'revisions' => _x('Request Revisions', 'Publishing box action button text', 'anno'),
			'clone' => _x('Clone', 'Publishing box action button text', 'anno'),
			'revert' => _x('Revert To Draft', 'Publishing box action button text', 'anno'),
		);

		include_once(ANNO_PLUGIN_PATH.'/workflow/users.php');
		include_once(ANNO_PLUGIN_PATH.'/workflow/workflow.php');
		include_once(ANNO_PLUGIN_PATH.'/workflow/audit.php');
		include_once(ANNO_PLUGIN_PATH.'/workflow/internal-comments/internal-comments.php');
		include_once(ANNO_PLUGIN_PATH.'/workflow/publishing-meta-box.php');
		include_once(ANNO_PLUGIN_PATH.'/workflow/notification.php');
		include_once(ANNO_PLUGIN_PATH.'/workflow/clone.php');
		
		if (annowf_get_option('listing_filter')) {
			include_once(ANNO_PLUGIN_PATH.'/workflow/viewable.php');
		}
	}
}
add_action('after_setup_theme', 'annowf_setup');

?>