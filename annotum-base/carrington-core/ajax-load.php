<?php

// This file is part of the Carrington Core Platform for WordPress
// http://carringtontheme.com
//
// Copyright (c) 2008-2011 Crowd Favorite, Ltd. All rights reserved.
// http://crowdfavorite.com
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

/**
 * Add markup to a post for AJAX display of post content
 * 
 * @return The markup for a close link
 * 
**/
function cfct_ajax_post_content($post_id) {
	global $post, $posts, $wp_query, $wp;
	$post = get_post($post_id);
	
	// If the post wasn't found, or is not published or private, we're not interested.
	if (!is_object($post) || !in_array($post->post_status, array('publish', 'private')) ) {
		die();
	}
	
	// If the post is private, make sure the user is allowed to see it before we show it.
	if ($post->post_status == 'private') {
		$user = wp_get_current_user();
	
		if (!$user->ID || !($user->ID == $post->post_author || current_user_can('editor') || current_user_can('administrator'))) {
			die();
		}
	}
	
	$wp_query->in_the_loop = true;
	setup_postdata($post);
	remove_filter('the_content', 'st_add_widget');
	$wp->send_headers();
	cfct_content();
	
	echo apply_filters('cfct_ajax_post_content_close', '<div class="close" id="post_close_'.$post_id.'"><a href="#">'.__('Close', 'carrington').'</a></div>', $post_id);
}

/**
 * Function used in AJAX to display comments for a given post
 * 
**/
function cfct_ajax_post_comments($post_id) {
	global $post, $posts, $wp_query, $wp;
	$wp_query->is_single = true;
	$posts = get_posts('include='.$post_id);
	$post = $posts[0];
	if (is_null($post)) {
		$posts = get_pages('include='.$post_id);
		$post = $posts[0];
	}
	setup_postdata($post);
	$wp->send_headers();
	comments_template();
}

/**
 * Request handler for AJAX (see js/carrington.js)
 * 
**/
function cfct_ajax_load() {
	if (isset($_GET['cfct_action'])) {
		switch ($_GET['cfct_action']) {
			case 'post_content':
			case 'post_comments':
				if (isset($_GET['id'])) {
					$post_id = intval($_GET['id']);
				}
				else if (isset($_GET['url'])) {
					$post_id = url_to_postid($_GET['url']);
				}
				if ($post_id) {
					$action = 'cfct_ajax_'.sanitize_key($_GET['cfct_action']);
					if (function_exists($action)) {
						call_user_func($action, $post_id);
						die();
					}
				}
		}
	}
}

/**
 * Add attribute to comments link for AJAX loading of comments
 * 
 * @param string $attrs String of preexisting attributes
 * @return string $attrs Atrributes for the comments link
 * 
**/
function cfct_ajax_comment_link($attrs) {
	global $post;
	if (strpos($attrs, 'rev="') !== false) {
		$attrs = str_replace('rev="', 'rev="post-'.$post->ID.' ', $attrs);
	}
	else if (strpos($attrs, "rev='") !== false) {
		$attrs = str_replace("rev='", "rev='post-".$post->ID.' ', $attrs);
	}
	else {
		$attrs .= ' rev="post-'.$post->ID.'" ';
	}
	return $attrs;
}
add_filter('comments_popup_link_attributes', 'cfct_ajax_comment_link');

/**
 * Returns the number of posts to display on an archive page
 * 
 * @return int The number of posts to display on an archive page
**/
function cfct_posts_per_archive_page_setting() {
	$count = cfct_get_option('posts_per_archive_page');
	intval($count) > 0 ? $count = $count : $count = 25;
	return $count;
}

/**
 * Add a self-removing filter to handle category pages (except in admin)
 * 
**/ 
function cfct_add_posts_per_archive_page() {
	add_filter('pre_get_posts', 'cfct_posts_per_archive_page');
	add_filter('pre_get_posts', 'cfct_posts_per_category_page');
}
if (!is_admin()) {
	add_filter('parse_request', 'cfct_add_posts_per_archive_page');
}

/**
 * Set the posts per archive page number
 * 
 * @return array Filtered array with a set posts per archive page value
 * 
**/
function cfct_posts_per_archive_page($query) {
	remove_filter('pre_get_posts', 'cfct_posts_per_archive_page');
	$query->set('posts_per_archive_page', cfct_posts_per_archive_page_setting());
	return $query;
}

/**
 * Set the posts per category page number
 * 
 * @return array Filtered array with a set posts per category page value
 * 
**/
function cfct_posts_per_category_page($query) {
	remove_filter('pre_get_posts', 'cfct_posts_per_category_page');
	if (is_category()) {
		$query->set('posts_per_page', cfct_posts_per_archive_page_setting());
	}
	return $query;
}

?>