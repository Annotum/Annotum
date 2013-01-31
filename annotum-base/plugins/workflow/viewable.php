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
				'relation' => 'OR',
				array(
					'key' => '_anno_author_'.$user_id,
				),
				array(
					'key' => '_anno_reviewer_'.$user_id,
				),
			));
			add_filter('views_edit-article', 'annov_article_view_counts');
		}
	}
}
add_action('pre_get_posts', 'annov_modify_list_query');

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
			// Update post counts
			add_filter('views_upload', 'annov_media_view_counts');
			
			add_filter('posts_where', 'annonv_media_parent_in_where');
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
 * Filter to get all attachments the current user should be able to see
 */
function annonv_media_parent_in_where($where) {
	remove_filter('posts_where', 'annonv_media_parent_in_where');
		
	// Grab all posts this user is associated with
	$authored_posts = anno_get_authored_posts(false, array('article'));
	
	$parent_ids = array_unique($authored_posts);

	if (is_array($parent_ids) && !empty($parent_ids)) {
		global $wpdb;
		// Grab all attachments which are children of articles the user is associated with
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

/**
 * Filter to adjust counts for article listing page
 */
function annov_article_view_counts($views) {
	remove_filter('views_edit-article', 'annov_article_view_counts');

	global $wp_query;
	$post_status = $wp_query->get('post_status');
	$user_id = get_current_user_id();
	unset($views['mine']);
	$types = array(
		array('status' =>  NULL),
		array('status' => 'publish'),
		array('status' => 'draft'),
		array('status' => 'pending'),
		array('status' => 'trash')
	);
	foreach( $types as $type ) {
		$query = new WP_Query(array(
			'post_type'   => 'article',
			'post_status' => $type['status'],
			'fields' => 'ids',
			'posts_per_page' => -1,
			'cache_results' => false,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => '_anno_author_'.$user_id,
				),
				array(
					'key' => '_anno_reviewer_'.$user_id,
				),
			),
		));

		if ($type['status'] == NULL) {			
		    $class = (empty($post_status) || $post_status == 'all') ? ' class="current"' : '';
		    $views['all'] = sprintf('<a href="%s"'. $class .'>'.__('All','anno').' <span class="count">(%d)</span></a>',
		        admin_url('edit.php?post_type=article'),
		        count($query->posts));
		}
		elseif ($type['status'] == 'publish') {
			if (!empty($query->posts)) {
			    $class = $post_status == 'publish' ? ' class="current"' : '';
			    $views['publish'] = sprintf('<a href="%s"'. $class .'>'.__('Published','anno').' <span class="count">(%d)</span></a>',
			        admin_url('edit.php?post_status=publish&post_type=article'), count($query->posts));
			}
			else {
				unset($views['publish']);
			}
		}
		elseif ($type['status'] == 'draft') {
			if (!empty($query->posts)) {
			    $class = $post_status == 'draft' ? ' class="current"' : '';
			    $views['draft'] = sprintf('<a href="%s"'. $class .'>'._n('Draft','Drafts',sizeof($query->posts),'anno').' <span class="count">(%d)</span></a>',
			        admin_url('edit.php?post_status=draft&post_type=article'), count($query->posts));
			}
			else {
				unset($views['draft']);
			}
		}
		elseif ($type['status'] == 'pending') {
			if (!empty($query->posts)) {
		    	$class = $post_status == 'pending' ? ' class="current"' : '';
		    	$views['pending'] = sprintf('<a href="%s"'. $class .'>'.__('Pending','anno').' <span class="count">(%d)</span></a>',
		        	admin_url('edit.php?post_status=pending&post_type=article'), count($query->posts));
			}
			else {
				unset($views['pending']);
			}
		}
		elseif( $type['status'] == 'trash') {
			if (!empty($query->posts)) {
		    	$class = $wp_query->get('post_status') == 'trash' ? ' class="current"' : '';
		    	$views['trash'] = sprintf('<a href="%s"'. $class .'>'.__('Trash','anno').' <span class="count">(%d)</span></a>',
		        	admin_url('edit.php?post_status=trash&post_type=article'), count($query->posts));
			}
			else {
				unset($views['trash']);
			}
		}
		
		wp_reset_query();
	}
	return $views;
}

/**
 * Filter to adjust counts for article listing page
 */
function annov_media_view_counts($views) {
	remove_filter('views_upload', 'annov_media_view_counts');

	global $wp_query;
	$user_id = get_current_user_id();
	// Only care about the ones defined in this function
	$views = array();
	$class = '';
	
	// all
	add_filter('posts_where', 'annonv_media_parent_in_where');
	$viewable_attachments = new WP_Query(array(
		'post_type' => 'attachment',
		'post_status' => 'inherit',
		'fields' => 'ids',
		'cache_results' => false,
	));
	
	// Also show all attachments the user owns, which may not be attached to a post
	$owned_posts = anno_get_owned_posts(null, array('attachment'), array('inherit'));

	$posts = array_merge($viewable_attachments->posts, $owned_posts);
	$posts = array_unique($posts);
	
	$all_count = count($posts);
	
	// Filters passed in through get params
	if (empty($_GET)) {
		$class = ' class="current"';
	}
	else {
		$class = '';
	}
	
	$views['all'] = sprintf('<a href="%s"'. $class .'>'.__('All','anno').' <span class="count">(%d)</span></a>',
    	admin_url('upload.php'), $all_count);
	
	wp_reset_query();
	
	// Images
	
	// Grab image associated with post this user can edit
	add_filter('posts_where', 'annonv_media_parent_in_where');
	$image_attachments = new WP_Query(array(
		'post_type' => 'attachment',
		'post_status' => 'inherit',
		'fields' => 'ids',
		'cache_results' => false,
		'post_mime_type' => 'image',
	));
	// Also grab images the user owns (could be detached)
	$image_owned_attachments = new WP_Query(array(
		'post_type' => 'attachment',
		'post_status' => 'inherit',
		'fields' => 'ids',
		'cache_results' => false,
		'post_mime_type' => 'image',
		'author' => $user_id,
	));
	
	$posts = array_merge($image_attachments->posts, $image_owned_attachments->posts);
	$posts = array_unique($posts);
	
	$image_count = count($posts);
	
	if ($image_count > 0) {
		if ($wp_query->get('post_mime_type') == 'image') {
			$class = ' class="current"';
		}
		else {
			$class = '';
		}
	
		$views['image'] = sprintf('<a href="%s"'. $class .'>'._n('Image','Images',$image_count,'anno').' <span class="count">(%d)</span></a>',
	    	admin_url('upload.php?post_mime_type=image'), $image_count);
	}
	wp_reset_query();
			
	$owned_detached = new WP_Query(array(
		'post_type' => 'attachment',
		'post_status' => 'inherit',
		'fields' => 'ids',
		'cache_results' => false,
		'post_parent' => 0,
		'author' => $user_id,
	));
	
	$detached_count = count($owned_detached->posts);
	
	if ($detached_count > 0) {
		// Not showing up in wp_query query vars
		if (!empty($_GET['detached'])) {
			$class = ' class="current"';
		}
		else {
			$class = '';
		}
	
		$views['detached'] = sprintf('<a href="%s"'. $class .'>'.__('Detached','anno').' <span class="count">(%d)</span></a>',
	    	admin_url('upload.php?detached=1'), $detached_count);
	}
	
	wp_reset_query();
	
	return $views;	
}

/**
 * Enqueue style that hides attach button by default
 */
function annov_enqueue_css() {
	if (!current_user_can('editor') && !current_user_can('administrator')) {
		wp_enqueue_style('anno-listing-filter', trailingslashit(get_template_directory_uri()).'plugins/workflow/css/listing-filter.css');
	}
}
add_action('admin_print_styles-upload.php', 'annov_enqueue_css');

/**
 * Enqueue script that removes the attach button
 */
function annov_enqueue_js() {
	if (!current_user_can('editor') && !current_user_can('administrator')) {
		wp_enqueue_script('anno-listing-filter', trailingslashit(get_template_directory_uri()).'plugins/workflow/js/listing-filter.js', array('jquery'));
	}
}
add_action('admin_print_scripts-upload.php', 'annov_enqueue_js');

/**
 * Do not allow user to use the find_posts ajax method if they are not editor or admin
 */
function annov_disable_attach_find() {
	if (!current_user_can('editor') && !current_user_can('administrator')) {
		if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'find_posts') {
			wp_die(__( 'Cheatin&#8217; uh?', 'anno'));
		}
	}
}
add_action('admin_init', 'annov_disable_attach_find')

?>
