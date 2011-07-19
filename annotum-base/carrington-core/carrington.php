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

// 	ini_set('display_errors', '1');
// 	ini_set('error_reporting', E_ALL);

define('CFCT_CORE_VERSION', '3.1');

// Path to Carrington Core parent directory (usually the theme).
if (!defined('CFCT_PATH')) {
	define('CFCT_PATH', trailingslashit(TEMPLATEPATH));
}

load_theme_textdomain('carrington');

include_once(CFCT_PATH.'carrington-core/admin.php');
include_once(CFCT_PATH.'carrington-core/templates.php');
include_once(CFCT_PATH.'carrington-core/utility.php');
include_once(CFCT_PATH.'carrington-core/ajax-load.php');
include_once(CFCT_PATH.'carrington-core/attachment.php');
include_once(CFCT_PATH.'carrington-core/compatibility.php');

cfct_load_plugins();

/**
 * Loads AJAX request handler
 * 
**/ 
function cfct_init() {
	cfct_admin_request_handler();
	if (cfct_get_option('ajax_load') == 'yes') {
		cfct_ajax_load();
	}
}
//add_action('init', 'cfct_init');

/**
 * Loads header code from Carrington Options
 * 
**/
function cfct_wp_head() {
	echo cfct_get_option('wp_head');
}
add_action('wp_head', 'cfct_wp_head');

/**
 * Loads footer code from Carrington Options
 * 
**/
function cfct_wp_footer() {
	echo cfct_get_option('wp_footer');
}
add_action('wp_footer', 'cfct_wp_footer');

/**
 * Loads about text from Carrington options for display in the sidebar
 * 
 * @return string Markup for the about text
 * 
**/
function cfct_about_text() {
	$about_text = cfct_get_option('about_text');
	if (!empty($about_text)) {
		$about_text = cfct_basic_content_formatting($about_text);
	}
	else {
		global $post, $wp_query;
		$orig_post = $post;
		isset($wp_query->query_vars['page']) ? $page = $wp_query->query_vars['page'] : $page = null;
// temporary - resetting below
		$wp_query->query_vars['page'] = null;
		remove_filter('the_excerpt', 'st_add_widget');
		$about_query = new WP_Query('pagename=about');
		while ($about_query->have_posts()) {
			$about_query->the_post();
			$about_text = get_the_excerpt().sprintf(__('<a class="more" href="%s">more &rarr;</a>', 'carrington'), get_permalink());
		}
		$wp_query->query_vars['page'] = $page;
		if (!empty($orig_post)) {
			$post = $orig_post;
			setup_postdata($post);
		}
	}
	if (function_exists('st_add_widget')) {
		add_filter('the_excerpt', 'st_add_widget');
	}
	return $about_text;
}

/**
 * Gets custom colors to be used with a themes
 * 
 * @return string Custom color
 * 
**/
function cfct_get_custom_colors($type = 'option') {
	global $cfct_color_options;
	$colors = array();
	foreach ($cfct_color_options as $option => $value) {
		switch ($type) {
			case 'preview':
				!empty($_GET[$option]) ? $colors[$option] = strip_tags(stripslashes($_GET[$option])) : $colors[$option] = '';
				break;
			case 'option':
			default:
				$colors[$option] = cfct_get_option($option);
				break;
		}
	}
	return $colors;
}

if (!defined('CFCT_DEBUG')) {
	define('CFCT_DEBUG', false);
}

?>