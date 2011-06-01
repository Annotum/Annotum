<?php

/**
 * Remove default WP Roles, as they are not being used. Remove publishing abilities from editor
 */ 
function anno_remove_roles_and_capabilities() {
	$wp_roles = new WP_Roles();
//TODO Remove subscriber, author roles? 
//	$wp_roles->remove_role('subscriber');
//	$wp_roles->remove_role('author');
	
// TODO Enable editing for all existing roles?
	$roles_to_modify = array(
		'administrator' => array(
			'edit_articles',
			'edit_article',
			'delete_article',
			'edit_others_articles',
			'publish_articles',
			'read_private_articles',
			'delete_others_articles',
			'delete_private_articles',
			'delete_published_articles',
			'edit_published_articles',	
		),
		'editor' => array(
			'edit_articles',
			'edit_article',
			'edit_others_articles',
			'delete_article',
			'delete_others_articles',
		),
		// Give contributors this access so they can view articles on the backend. Contributors cannot actually save/edit the articles for various states.
		// Enforced by worflow capabilities.
		'contributor' => array(
			'edit_articles',
			'edit_article',
			'edit_others_articles',
			'delete_article',
		),
	);
	
	foreach ($roles_to_modify as $role_name => $role_caps) {
		$role = get_role($role_name);
		if ($role) {
			foreach ($role_caps as $cap_name) {
				$role->add_cap($cap_name);
			}
		}
	}
}
add_action('admin_init', 'anno_remove_roles_and_capabilities');
?>