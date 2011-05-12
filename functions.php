<?php

// This file is part of the Carrington JAM Theme for WordPress
// http://carringtontheme.com
//
// Copyright (c) 2008-2010 Crowd Favorite, Ltd. All rights reserved.
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

load_theme_textdomain('anno');


define('CFCT_DEBUG', false);
define('CFCT_PATH', trailingslashit(TEMPLATEPATH));
define('ANNO_VER', '1.0');

include_once(CFCT_PATH.'carrington-core/carrington.php');
include_once(CFCT_PATH.'functions/sidebars.php');
include_once(CFCT_PATH.'functions/post-types.php');
include_once(CFCT_PATH.'functions/taxonomies.php');
include_once(CFCT_PATH.'functions/capabilities.php');
include_once(CFCT_PATH.'functions/post-capabilities.php');
include_once(CFCT_PATH.'plugins/load.php');

function anno_setup() {
	
	add_theme_support('automatic-feed-links');
}
add_action('after_setup_theme', 'anno_setup');

function anno_assets() {
	$theme = get_bloginfo('template_directory') . '/';
	// Styles
	wp_enqueue_style('anno', $theme.'style.css', array(), ANNO_VER, 'screen');
	
	// Scripts
	if ( is_singular() ) { wp_enqueue_script( 'comment-reply' ); }
}
add_action('wp', 'anno_assets');


?>