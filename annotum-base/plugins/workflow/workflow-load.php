<?php


include_once(ANNO_PLUGIN_PATH.'/workflow/workflow-settings.php');

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
}

?>