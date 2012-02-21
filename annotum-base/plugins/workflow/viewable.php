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
 *
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
 */
function annov_modify_list_where($where) {	
	$user_id = get_current_user_id();
	// Self removing filter
	remove_filter('posts_where', 'annov_modify_list_where');
	
	return "AND wp_posts.post_type = 'article' 
		AND (
			wp_posts.post_status = 'publish' 
			OR wp_posts.post_status = 'future' 
			OR wp_posts.post_status = 'draft' 
			OR wp_posts.post_status = 'pending' 
			OR wp_posts.post_author = $user_id 
			AND wp_posts.post_status = 'private'
		)
		AND (
			wp_postmeta.meta_key = '_anno_author_$user_id' 
			OR wp_posts.post_status = 'publish'
		)";
}

/**
 * Prevent a user from accessing the back end view screen for an article if they are not
 * an author or have authoring capabilities
 */
function annov_prevent_view() {
	global $pagenow;
	if ($pagenow == 'post.php') {
		global $post;
		// Some disconnect from workflow, use WP post_status instead of the Annotum post state meta
 		if ($post->post_type == 'article' && !anno_user_can('edit_post', null, $post->ID) && $post->post_status !== 'publish') {
			// Remove all capabilities for that user in this instance
			add_filter('user_has_cap', 'annowf_user_has_cap_filter');
		}
	}
}
add_action('wp_init', 'annov_prevent_view');

?>