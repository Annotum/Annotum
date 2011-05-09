<?php

/**
 * Remove default WP Roles, as they are not being used. Remove publishing abilities from editor
 */ 
function anno_remove_roles_and_capabilities() {
	$wp_roles = new WP_Roles();
	$wp_roles->remove_role('subscriber');
	$wp_roles->remove_role('author');
	
	// Editors should not be able to publish or edit published pages
	$caps_to_remove = array(
		'publish_pages',
		'delete_published_pages',
		'publish_posts',
		'delete_published_posts',
	);
	$editor = get_role('editor');
	foreach ($caps_to_remove as $cap) {
		$editor->remove_cap($cap);
	}	
}
add_action('admin_init', 'anno_remove_roles_and_capabilities');

//TODO Restore roles/caps on switch_theme

?>