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
 * Includes the appropriate file for a page
 * 
 * @param string [$file] filename of the page template
 *  
**/
function cfct_page($file = '') {
	if (empty($file)) {
		$file = cfct_default_file('pages');
	}
	cfct_template_file('pages', $file);
}

/**
 * Includes the appropriate template file for a header
 *  
**/
function cfct_header() {
	$file = cfct_choose_general_template('header');
	cfct_template_file('header', $file);
}

/**
 * Includes the appropriate template file for a footer
 *  
**/
function cfct_footer() {
	$file = cfct_choose_general_template('footer');
	cfct_template_file('footer', $file);
}

/**
 * Includes the appropriate template file for a sidebar
 *  
**/
function cfct_sidebar() {
	$file = cfct_choose_general_template('sidebar');
	cfct_template_file('sidebar', $file);
}

/**
 * Includes the appropriate template file for a header
 *  
**/
function cfct_posts() {
	$file = cfct_choose_general_template('posts');
	cfct_template_file('posts', $file);
}

/**
 * Includes the appropriate template file for a single page
 *  
**/
function cfct_single() {
	$file = cfct_choose_general_template('single');
	cfct_template_file('single', $file);
}

/**
 * Includes the appropriate template file for an attachement
 *  
**/
function cfct_attachment() {
	$file = cfct_choose_general_template('attachment');
	cfct_template_file('attachment', $file);
}

/**
 * Includes the appropriate template file for the loop
 *  
**/
function cfct_loop() {
	$file = cfct_choose_general_template('loop');
	cfct_template_file('loop', $file);
}

/**
 * Includes the appropriate template file for the content
 *  
**/
function cfct_content() {
	$file = cfct_choose_content_template();
	cfct_template_file('content', $file);
}

/**
 * Chooses the appropriate template file for the content in a feed and returns that content
 *  
**/
function cfct_content_feed($content) {
	if (is_feed()) {
// find template
		$file = cfct_choose_content_template_feed();
		if ($file) {
// load template
			$content = cfct_template_content('content', $file);
		}
	}
	$content = str_replace(']]>', ']]&gt;', $content);
	return $content;
}
add_filter('the_content_feed', 'cfct_content_feed');

/**
 * Output feed content without infinite loop
 *  
**/
function cfct_the_content_feed() {
	remove_filter('the_content_feed', 'cfct_content_feed');
	the_content_feed('rss2');
	add_filter('the_content_feed', 'cfct_content_feed');
}	

/**
 * Includes the appropriate template file for the excerpt
 *  
**/
function cfct_excerpt() {
	$file = cfct_choose_content_template('excerpt');
	cfct_template_file('excerpt', $file);
}

/**
 * Chooses the appropriate template file for the excerpt in a feed and returns that content
 *  
**/
function cfct_excerpt_feed($content) {
	if (is_feed()) {
// find template
		$file = cfct_choose_content_template_feed('excerpt');
		if ($file) {
// load template
			$content = cfct_template_content('excerpt', $file);
		}
	}
	return $content;
}
add_filter('the_excerpt_rss', 'cfct_excerpt_feed');

/**
 * Output feed content without infinite loop
 *  
**/
function cfct_the_excerpt_feed() {
	remove_filter('the_excerpt_rss', 'cfct_excerpt_feed');
	the_excerpt_rss();
	add_filter('the_excerpt_rss', 'cfct_excerpt_feed');
}

/**
 * Includes the appropriate template file for comments
 *  
**/
function cfct_comments() {
	$file = cfct_choose_general_template('comments');
	cfct_template_file('comments', $file);
}

/**
 * Includes the appropriate template file for a single comment
 * 
 * @param $data not used
 *  
**/
function cfct_comment($data = null) {
	$file = cfct_choose_comment_template();
	cfct_template_file('comment', $file, $data);
}

/**
 * Includes the appropriate template file for a threaded comment
 * 
 * @param array $comment The comment currently being processed
 * @param array $args Custom arguments
 * @param int $depth The depth of a comment 
 *  
**/
function cfct_threaded_comment($comment, $args = array(), $depth) {
	$GLOBALS['comment'] = $comment;
	$data = array(
		'args' => $args,
		'depth' => $depth,
	);
	cfct_template_file('comments', 'threaded', $data);
}

/**
 * Includes the appropriate template file for a form
 *  
**/
function cfct_form($name = '') {
	$parts = cfct_leading_dir($name);
	cfct_template_file('forms/'.$parts['dir'], $parts['file']);
}

/**
 * Includes the appropriate template file based on a string
 * 
 * @param string $name The name of the string corresponding to the filename in /misc directory
 *  
**/
function cfct_misc($name = '') {
	$parts = cfct_leading_dir($name);
	cfct_template_file('misc/'.$parts['dir'], $parts['file']);
}

/**
 * Includes the appropriate template file for an error
 *  
**/
function cfct_error($name = '') {
	cfct_template_file('error', $name);
}

?>