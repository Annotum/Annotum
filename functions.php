<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2010 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

load_theme_textdomain('anno');

define('CFCT_DEBUG', false);
define('CFCT_PATH', trailingslashit(TEMPLATEPATH));
define('ANNO_VER', '1.0');

include_once(CFCT_PATH.'carrington-core/carrington.php');
include_once(CFCT_PATH.'functions/post-types.php');
include_once(CFCT_PATH.'functions/taxonomies.php');
include_once(CFCT_PATH.'functions/capabilities.php');
include_once(CFCT_PATH.'functions/featured-articles.php');
include_once(CFCT_PATH.'plugins/load.php');

function anno_setup() {
	add_theme_support('automatic-feed-links');
	add_theme_support('post-thumbnails', array( 'article' ) );
	add_image_size( 'post-excerpt', 140, 120, true);
	add_image_size( 'post-teaser', 100, 79, true);
	add_image_size( 'featured', 270, 230, true);
	
	$menus = array(
		'main' => 'Main Menu (Header)',
		'secondary' => 'Secondary Menu (Header)',
		'footer' => 'Footer Menu',
	);
	register_nav_menus($menus);
	
	$sidebar_defaults = array(
		'before_widget' => '<aside id="%1$s" class="widget clearfix %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h1 class="title">',
		'after_title' => '</h1>'
	);
	register_sidebar(array_merge($sidebar_defaults, array(
		'name' => 'Default Sidebar',
		'id' => 'default'
	)));
	add_action('wp_head', 'anno_css3_pie', 8);
}
add_action('after_setup_theme', 'anno_setup');

function anno_css3_pie() {
	$assets_root = get_bloginfo('template_url') . '/assets/main/';
	?>
	<!--[if lte IE 8]>
	<style type="text/css" media="screen">
		.featured-posts .control-panel .previous,
		.featured-posts .control-panel .next,
		.widget-recent-posts .nav .ui-tabs-selected,
		.widget-recent-posts .panel {
			behavior: url(<?php echo $assets_root; ?>js/libs/css3pie/PIE.php);
		}
	</style>
	<![endif]-->
<?php
}


function anno_assets() {
	if (!is_admin()) {
		$theme = trailingslashit(get_bloginfo('template_directory'));
		$main = $theme . 'assets/main/';
		
		// Styles
		wp_enqueue_style('anno', $main.'css/main.css', array(), ANNO_VER, 'screen');
		//wp_enqueue_style('anno-rtl', $main.'css/rtl.css', array('anno'), ANNO_VER, 'screen');

		// Scripts
		wp_enqueue_script('modernizr', $main.'js/libs/modernizr-1.7.min.js', array(), ANNO_VER);
		wp_enqueue_script('placeholder', $main.'js/libs/jquery.placeholder.js', array('jquery'), ANNO_VER);
		wp_enqueue_script('ui_tabs', $main.'js/libs/jquery-ui-tabs.min.js', array('jquery'), ANNO_VER);
		wp_enqueue_script('anno-main', $main.'js/main.js', array('placeholder'), ANNO_VER);

		if ( is_singular() ) { wp_enqueue_script( 'comment-reply' ); }
	}
}
add_action('wp', 'anno_assets');

function anno_head_extra() {
	echo '<link rel="pingback" href="'.get_bloginfo('pingback_url').'" />'."\n";
	$args = array(
		'type' => 'monthly',
		'format' => 'link'
	);
	wp_get_archives($args);
}
add_action('wp_head', 'anno_head_extra');

function anno_wp_nav_menu_args($args) {
	$args['fallback_cb'] = null;
	if ($args['container'] == 'div') {
		$args['container'] = 'nav';
	}
	if ($args['depth'] == 0) {
		$args['depth'] = 2;
	}
	if ($args['menu_class'] == 'menu') {
		$args['menu_class'] = 'nav';
	}
	
	return $args;
}
add_filter('wp_nav_menu_args', 'anno_wp_nav_menu_args');

function anno_post_class($classes, $class) {
	if (has_post_thumbnail()) {
		$classes[] = 'has-featured-image';
	}
	
	return $classes;
}
add_filter('post_class', 'anno_post_class', 10, 2);

?>