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
 * Filter for article listing screen to only show articles a user is author/co-author on unless they
 * are an editor or administrator
 */
function annov_modify_list_query($query) {
	global $pagenow;
	if (is_admin() && $pagenow == 'edit.php' && $query->get('post_type') == 'article') {
		if (!current_user_can('editor') && !current_user_can('administrator')) {
			$user_id = get_current_user_id();
			$query->set('meta_query', array( 
				array(
					'key' => '_anno_author_'.$user_id,
				),
			));
		}
	}
}
add_action('pre_get_posts', 'annov_modify_list_query');

/**
 * Add filter to modify where clause if on article listing screen
 */
function annov_add_modify_list_where($query) {
	global $pagenow;
	if (!current_user_can('editor') && !current_user_can('administrator')) {
		if (is_admin() && $pagenow == 'edit.php' && $query->get('post_type') == 'article') {
			add_filter('posts_where', 'annov_modify_list_where');
		}
	}
}
add_action('pre_get_posts', 'annov_add_modify_list_where');

/**
 * Adjust where clause to display posts this user is attributed to or published ones
 * 
 * Original Query:
 *  	AND wp_posts.post_type = 'article' 
 *  		AND (
 *  			wp_posts.post_status = 'publish' 
 *  			OR wp_posts.post_status = 'future' 
 *  			OR wp_posts.post_status = 'draft' 
 *  			OR wp_posts.post_status = 'pending' 
 *  			OR wp_posts.post_author = $user_id 
 *  			AND wp_posts.post_status = 'private'
 *  		)
 *  		AND (
 *  			wp_postmeta.meta_key = '_anno_author_$user_id' 
 *  		)
 *
 */
function annov_modify_list_where($where) {	
	$user_id = get_current_user_id();
	global $wpdb;
	// Self removing filter
	remove_filter('posts_where', 'annov_modify_list_where');

	return "AND $wpdb->posts.post_type = 'article' 
		AND (
			$wpdb->posts.post_status = 'publish' 
			OR $wpdb->posts.post_status = 'future' 
			OR $wpdb->posts.post_status = 'draft' 
			OR $wpdb->posts.post_status = 'pending' 
			OR $wpdb->posts.post_author = $user_id 
			AND $wpdb->posts.post_status = 'private'
		)
		AND (
			$wpdb->postmeta.meta_key = '_anno_author_$user_id' 
			OR $wpdb->posts.post_status = 'publish'
		)";
}

/**
 * Only list any media this user has uploaded AND
 * and media on posts they can co-author
 * 
 * @todo editor, admin check
 */ 
function annov_modify_media_list_query($query) {
	global $pagenow;
	remove_action('pre_get_posts', 'annov_modify_media_list_query');
	if (is_admin() && $pagenow == 'upload.php') {
		if (!current_user_can('editor') && !current_user_can('administrator')) {
			add_filter('posts_where', 'anonv_media_parent_in_where');
			$viewable_attachments = new WP_Query(array(
				'post_type' => 'attachment',
				'post_status' => 'inherit',
				'fields' => 'ids',
				'cache_results' => false,
			));
		
			$posts = $viewable_attachments->posts;
			if (empty($posts)) {
				$posts[] = -1;
			}
		
			// Also show all attachments the user owns, which may not be attached to a post
			$owned_posts = anno_get_owned_posts(null, array('attachment'), array('inherit'));
		
			$posts = array_merge($posts, $owned_posts);

			$posts = array_unique($posts);
		
			wp_reset_query();
			$query->set('post__in', $posts);
		}
	}
}
add_action('pre_get_posts', 'annov_modify_media_list_query');


/**
 * Filter to get all attachments this use should be able to see
 */
function anonv_media_parent_in_where($where) {
	remove_filter('posts_where', 'anonv_media_parent_in_where');
		
	// Grab all posts this user is associated with
	$authored_posts = anno_get_authored_posts(false, array('article'));
	
	// Grab all published posts
	$published_posts = anno_get_published_posts('any');

	$parent_ids = array_merge($authored_posts, $published_posts);
	$parent_ids = array_unique($parent_ids);
	

	if (is_array($parent_ids) && !empty($parent_ids)) {
		global $wpdb;
		// Grab all attachments which are children of published posts OR articles the user is associated with
		$where = "AND $wpdb->posts.post_type = 'attachment' 
		AND ($wpdb->posts.post_status = 'inherit')
		AND $wpdb->posts.post_parent IN (".implode(',', $parent_ids).")";
	}
	else {
		// Fall back to a statement which will return no posts
		$where = "AND 1=0";
	}
	return $where;
}


?>