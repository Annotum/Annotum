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
 * Custom capabilities for workflow
 */ 
function anno_remove_roles_and_capabilities() {
	$wp_roles = new WP_Roles();

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
			'read_article',
		),
		'editor' => array(
			'edit_articles',
			'edit_article',
			'edit_others_articles',
			'delete_article',
			'delete_others_articles',
			'read_article',
		),
		// Give contributors and authors this access so they can view articles on the backend. Contributors cannot actually save/edit the articles for various states.
		// Enforced by worflow capabilities.
		'contributor' => array(
			'edit_articles',
			'edit_article',
			'edit_others_articles',
			'delete_article',
			'read_article',
			'upload_files',
		),
		'author' => array(
			'edit_articles',
			'edit_article',
			'edit_others_articles',
			'delete_article',
			'read_article',
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