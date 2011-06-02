<?php


include_once(ANNO_PLUGIN_PATH.'/workflow/workflow-settings.php');

if (anno_workflow_enabled()) {
	// Used in generating save buttons and proccess state changes
	global $anno_post_save;
	$anno_post_save = array(
		'approve' => __('Approve', 'anno'),
		'publish' => __('Publish', 'anno'),	
		'reject' => __('Reject', 'anno'),
		'review' => __('Submit For Review', 'anno'),
		'revisions' => __('Request Revisions', 'anno'),
		'clone' => __('Clone', 'anno'),
	);
	include_once(ANNO_PLUGIN_PATH.'/workflow/users.php');
	include_once(ANNO_PLUGIN_PATH.'/workflow/workflow.php');
	include_once(ANNO_PLUGIN_PATH.'/workflow/internal-comments/internal-comments.php');
	include_once(ANNO_PLUGIN_PATH.'/workflow/publishing-meta-box.php');
	include_once(ANNO_PLUGIN_PATH.'/workflow/notification.php');
}

?>