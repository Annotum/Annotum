<?php

/**
 * This file contains functions that pertain to post specific roles such as a user or reviewer
 */

/**
 * This determines whether or not a user has the given abilities for a given post
 * 
 * @param string $cap The capability to check
 * @param int $user_id The user id to check for a capability. Defaults to current user (global)
 * @param int $post_id The ID of the object to check (post, comment etc..). Defaults to current post (global)
 * @return bool True if user has the given capability for the given post
 */ 
function anno_user_can($cap, $user_id = null, $post_id = null, $comment_id = null) {
	if (is_null($user_id)) {
		global $current_user;
		$user_id = $current_user->ID;
	}
	if (is_null($post_id)) {
		// Assume post, since only one cap checks comments
		global $post;
		$post_id = $post->ID;
	}
	$post_state = get_post_meta($post_id, '_post_state', true);
	if (empty($post_state)) {
		$post_state = 'draft';
	}
	$user_role = anno_role($user_id, $post_id);
	
	// Number of times this item has gone back to draft state.
	$post_round = get_post_meta($post_id, '_round', true);
	
	// WP role names
	$admin = 'administrator';
	$editor = 'editor';
	
	switch ($cap) {
		case 'trash_post':
			// Draft state, author or editor+
			if ($post_round < 1 && $post_state == 'draft' && $user_role && !in_array($user_role, array('reviewer', 'co-author'))) {
				return true;
			}
			break;
		case 'view_post':
			// Published post state, or user is associated with the post
			if ($post_state == 'published' || $user_role) {
				return true;
			}
			break;
		case 'edit_post':
			global $pagenow;
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
			// New Article
			else if ($pagenow == 'post-new.php') {
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
		case 'view_general_comments':
			// if user is author/co-author or editor+
			if ($user_role && $user_role != 'reviewer') {
				return true;
			}
			break;
		case 'manage_reviewer_comment':
			// if user is reviewer or editor+ and state is in review
			if ($user_role && !in_array($user_role, array('author', 'co-author')) && $post_state == 'in_review') {
				return true;
			}
			break;
		case 'view_reviewer_comment':
			// if user is or editor+
			if (in_array($user_role, array($admin, $editor))) {
				return true;
			}
			// if user is reviewer and comment author = reviewer
			$comment = anno_internal_comments_get_comment_root($comment_id);
			if ($user_role == 'reviewer' && $comment && $comment->user_id == $user_id) {
				return true;
			}
			break;
		case 'view_reviewer_comments':
			//Reviewer or editor+
			if ($user_role && !in_array($user_role, array('author', 'co-author'))) {
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
			if (in_array($user_role, array($admin, $editor)) && in_array($post_state, array('submitted', 'in_review'))) {
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
					// Must be an editor+
					if (in_array($user_role, array($admin, $editor))) {
						return true;
					}
					break;
					// Must be a part of the publishing staff
				case 'approved':
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
 * @param int $user_id The user id to check for a capability. Defaults to current user (global)
 * @param int $post_id The ID of the post to check. Defaults to current post (global)
 * @return string|false Role the given user has for a given post, false if user is not attached to the post in any way
 */ 
function anno_role($user_id = null, $post_id = null) {
	global $pagenow;
	if (is_null($user_id)) {
		global $current_user;
		$user = $current_user;
		$user_id = $user->ID;
	}
	else {
		$user = new WP_User($user_id);
	}
	// Pagenow to prevent loading in autodrafts
	if (is_null($post_id) && $pagenow != 'post-new.php') {
		global $post;
		$post_id = $post->ID;
	}
	else {
		$post = get_post($post_id);
	}
	
	if (!$user || !post_id) {
		return false;
	}
	
	if ($user->has_cap('administrator')) {
		return 'administrator';
	}
	else if ($user->has_cap('editor')) {
		return 'editor';
	}
	$reviewers = anno_get_reviewers($post_id);
	if (is_array($reviewers) && in_array($user_id, $reviewers)) {
		return 'reviewer';
	}
	
	$co_authors = anno_get_co_authors($post_id);
	if (is_array($co_authors) && in_array($user_id, $co_authors)) {
		return 'co-author';
	}
	
	if ($post && $post->post_author = $user_id) {
		return 'author';
	}
	
	return false;	
}

/**
 * Gets all co-authors for a given post id
 *
 * @param int $post_id ID of the post to check
 * @return array Array of co-authors (or empty array if none exist)
 */ 
function anno_get_co_authors($post_id = null) {
	return anno_get_post_users('_co_authors', $post_id);
}

/**
 * Gets all reviewers for a given post id
 *
 * @param int $post_id ID of the post to check
 * @return array Array of reviewers (or empty array if none exist)
 */
function anno_get_reviewers($post_id = null) {
	return anno_get_post_users('_reviewers', $post_id);
}

function anno_get_post_users($type, $post_id = null) {
	if ($post_id == null) {
		global $post;
		$post_id = $post->ID;
	}
	$users = get_post_meta($post_id, $type, true);
	if (!is_array($users)) {
		return array();
	}
	else {
		return $users;
	}
}

/**
 * Adds a co-author to a given post
 * 
 * @param int $user_id ID of the user being added to the post
 * @param int $post_id ID of the post to add the user to. Loads from global if nothing is passed.
 * @return bool True if successfully added, false otherwise
 */
function anno_add_co_author_to_post($user_id, $post_id = null) {
	return anno_add_post_user_to_post('_co_authors', $user_id, $post_id);
}

/**
 * Adds a reviewer to a given post
 * 
 * @param int $user_id ID of the user being added to the post
 * @param int $post_id ID of the post to add the user to. Loads from global if nothing is passed.
 * @return bool True if successfully added, false otherwise
 */
function anno_add_reviewer_to_post($user_id, $post_id = null) {
	return anno_add_post_user_to_post('_reviewers', $user_id, $post_id);
}

/**
 * Adds a user to a given post with a given role
 * 
 * @param string $type Type of user to add. Can be the meta_key or 
 * @param int $user_id ID of the user being added to the post
 * @param int $post_id ID of the post to add the user to. Loads from global if nothing is passed.
 * @return bool True if successfully added, false otherwise
 */ 
function anno_add_post_user_to_post($type, $user_id, $post_id = null) {
	if ($type == 'reviewers' || $type == 'co_authors') {
		$type = '_'.$type;
	}
	
	if ($post_id == null) {
		global $post;
		$post_id = $post->ID;
	}
	
	$users = get_post_meta($post_id, $type, true);
	if (!is_array($users)) {
		$users = array($user_id);
	}
	else {
		$users[] = $user_id;
	}
	
	return update_post_meta($post_id, $type, $users);
}

?>