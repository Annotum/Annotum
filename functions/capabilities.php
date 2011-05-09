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
		'edit_published_pages',
		'publish_pages',
		'delete_published_pages',
		'edit_published_posts',
		'publish_posts',
		'delete_published_posts',
	);
	$editor = get_role('editor');
	foreach ($caps_to_remove as $cap) {
		$editor->remove_cap($cap);
	}
	
	// Admins/Publishing staff shouldn't be able to edit posts
	$caps_to_remove = array(
		'edit_published_pages',
		'delete_published_pages',
		'edit_published_posts',
		'delete_published_posts',
	);
	$editor = get_role('administrator');
	foreach ($caps_to_remove as $cap) {
		$editor->remove_cap($cap);
	}
	
}
add_action('admin_init', 'anno_remove_roles_and_capabilities');

//TODO Restore roles/caps on switch_theme

/**
 * This determines whether or not a user has the given abilities for a given post
 * 
 * @param int $user_id The user id to check for a capability
 * @param string $cap The capability to check
 * @param int $obj_id The ID of the object to check (post, comment etc..)
 * @return bool True if user has the given capability for the given post
 */ 
function anno_user_can($cap, $user_id = null, $obj_id = null) {
	if (is_null($user_id)) {
		global $current_user;
		$user_id = $current_user->ID;
	}
	if (is_null($post_id)) {
		// Assume post, since only one cap checks comments
		global $post;
		$post_id = $post->ID;
	}
	$post_state = get_post_meta($obj_id, '_post_state', true);
	$user_role = anno_role($user_id, $obj_id);
	$admin = 'administrator';
	$editor = 'editor';
	
	switch ($cap) {
		case 'view_post':
			// Published post state, or user is associated with the post
			if ($post_state == 'published' || $user_role) {
				return true;
			}
			break;
		case 'edit_post':
			// Allow edits for things such as typos
			if ($user_role == $admin) {
				return true;
			}
			// Not final, published or rejected
			else if ($user_role == $editor && $post_state && !in_array($post_state, array('final', 'published', 'rejected'))) {
				return true;
			}
			// Draft state, authors can edit
			else if (($user_role == 'author' || $user_role == 'co-author') && $post_state == 'draft') {
				return true;
			}
			break;
		case 'manage_general_comment':
			// Anyone who isn't a reviewer, attached to the post and not in published state
			if ($user_role && $user_role != 'reviewer' && $post_state != 'published') {
				return true;
			}
			break;
		case 'view_general_comment':
			// if user is author/co-author or editor+
			if ($user_role && $user_role != 'reviewer') {
				return true;
			}
			break;
		case 'manage_reviewer_comment':
			// if user is reviewer or editor+ and state is in review
			if ($user_role && !in_array($user_role, array('author', 'co-author')) && $post_state = 'in review') {
				return true;
			}
			break;
		case 'view_reviewer_comment':
			// if user is or editor+
			if (in_array($user_role, array($admin, $editor))) {
				return true;
			}
			// if user is reviewer and comment author = reviewer
			$comment = get_comment($obj_id);
			if ($user_role == 'reviewer' && $comment && $comment->user_id == $user_id) {
				return true;
			}
			break;
		case 'manage_co_authors':
			// If in draft state and author or editor+
			if ($user_role && !in_array($user_role, array('reviewer', 'co-author')) && $post_state == 'draft') {
				return true;
			}
			break;
		case 'manage_reviewers':
			// if in review state and user is editor+
			if (in_array($user_role, array($admin, $editor)) && $post_state == 'in review') {
				return true;
			}
			break;
		case 'alter_post_state':
			switch ($post_state) {
				case 'draft':
					// If not reviewer, and in draft state
					if($user_role && !in_array($user_role, array('reviewer', 'co-author')) && $post_state == 'draft') {
						return true;
					}
					break;
				case 'in_review':
				case 'reviewed':
					if (in_array($user_role, array($admin, $editor))) {
						return true;
					}
					break;
				case 'final':
					if ($user_role == $admin) {
						return true;
					}
					break;
				case 'published':
					// No one can change a published article's status
					return false;
					break;
				case 'rejected':
					// No one can change a rejected article's status
					return false;
					break;
				default:
					break;
			}
			break;
		case 'clone_post':
			// Anyone can clone the post when its published
			if ($post_state == 'published') {
				return true;
			}
			break;
		default:
			break;
	}
	// if we haven't returned, assume false
	return false;
}

/**
 * Returns the user's role for a given post. Returns editor or publishing staff even if that user
 * is also an author, co-author, or reviewer
 * 
 * @param int $user_id ID of the user to check
 * @param int $post_id ID of the post to check
 * @return string|false Role the given user has for a given post, false if user is not attached to the post in any way
 */ 
function anno_role($user_id, $post_id) {
	global $current_user;
	if ($current_user->has_cap('administrator')) {
		return 'administrator';
	}
	else if ($current_user->has_cap('editor')) {
		return 'editor';
	}

	$reviewers = anno_get_reviewers($post_id);
	if (in_array($user_id, $reviewers)) {
		return 'reviewer';
	}
	
	$co_authors = anno_get_co_authors($post_id);
	if (in_array($user_id, $co_authors)) {
		return 'co-author';
	}
	
	$post = get_post($post_id);
	if ($post && $post->post_author = $user_id) {
		return 'author';
	}
	
	return false;	
}

/**
 * Gets all co-authors for a given post id
 * @param int $post_id ID of the post to check
 * @return array|false Array of co-authors, or false if no co-authors have been set
 */ 
function anno_get_co_authors($post_id) {
	return get_post_meta($post_id, '_co_authors', true);
}

/**
 * Gets all reviewers for a given post id
 * @param int $post_id ID of the post to check
 * @return array|false Array of co-authors, or false if no co-authors have been set
 */
function anno_get_reviewers($post_id) {
	return get_post_meta($post_id, '_reviewers', true);
}



?>