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

define('CFCT_DEBUG', false);
define('CFCT_PATH', trailingslashit(TEMPLATEPATH));
define('ANNO_VER', '1.0');

include_once(CFCT_PATH.'carrington-core/carrington.php');
include_once(CFCT_PATH.'functions/article-post-type.php');
include_once(CFCT_PATH.'functions/appendix-repeater.php');
include_once(CFCT_PATH.'functions/taxonomies.php');
include_once(CFCT_PATH.'functions/capabilities.php');
include_once(CFCT_PATH.'functions/featured-articles.php');
include_once(CFCT_PATH.'functions/template-tags.php');
include_once(CFCT_PATH.'plugins/load.php');

function anno_setup() {
	$path = trailingslashit(TEMPLATEPATH);

	// i18n support
	load_theme_textdomain('anno', $path . 'languages');
	$locale = get_locale();
	$locale_file = $path . '/languages/' . $locale . '.php';
	if ( is_readable( $locale_file ) ) {
		require_once( $locale_file );
	}
	
	add_theme_support('automatic-feed-links');
	add_theme_support('post-thumbnails', array( 'article', 'post' ) );
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

/**
 * Enqueue and add CSS and JS assets.
 * Hook into 'wp' action when conditional checks like is_single() are available.
 */
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
		$main =  trailingslashit(get_bloginfo('template_directory')) . 'assets/main/';
		$v = ANNO_VER;
		
		// Styles
		wp_enqueue_style('anno', $main.'css/main.css', array(), $v, 'screen');
		
		// Right-to-left languages
		if (is_rtl()) {
			// Override stylesheet
			wp_enqueue_style('anno-rtl', $main.'css/rtl.css', array('anno'), $v, 'screen');
		}
		
		// Scripts
		wp_enqueue_script('modernizr', $main.'js/libs/modernizr-1.7.min.js', array(), $v);
		wp_enqueue_script('jquery-cf-placeholder', $main.'js/libs/jquery.placeholder.min.js', array('jquery'), $v);
		wp_enqueue_script('jquery-ui-tabs');
		
		wp_enqueue_script('anno-main', $main.'js/main.js', array('jquery-cf-placeholder'), $v);
		wp_localize_script('anno-main', 'ANNO_DICTIONARY', array(
			'previous' => __('Previous', 'anno'),
			'next' => __('Next', 'anno'),
			'xofy' => __('%1$s of %2$s')
		));

		if ( is_singular() ) {
			wp_enqueue_script( 'comment-reply' );
		}
		
		/* Home page featured post cycler */
		if (is_home()) {
			wp_enqueue_script('jquery-cycle-lite', $main.'js/libs/jquery.cycle.lite.1.1.min.js', array('jquery'), $v);
		}
	}
}
add_action('wp', 'anno_assets');

/*
 * Outputs a few extra semantic tags in the <head> area.
 * Hook into 'wp_head' action.
 */
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
	$has_img = 'has-featured-image';
	
	/* An array of classes that we want to create an additional faux compound class for.
	This lets us avoid having to do something like
	.article-excerpt.has-featured-image, which doesn't work in IE6.
	Instead, we can do .article-excerpt-has-featured-image. While a bit verbose,
	it will nonetheless do the trick. */
	$compoundify = array(
		'article-excerpt'
	);
	
	if (has_post_thumbnail()) {
		$classes[] = $has_img;
		
		foreach ($compoundify as $compound_plz) {
			if (in_array($compound_plz, $classes)) {
				$classes[] = $compound_plz . '-' . $has_img;
			}
		}
	}
	
	return $classes;
}
add_filter('post_class', 'anno_post_class', 10, 2);

/**
 * Determines whether or not an email address is valid
 * 
 * @param string email to check
 * @return bool true if it is a valid email, false otherwise
 */ 
function anno_is_valid_email($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Function to limit front-end display of comments. 
 * Wrap this filter around comments_template();
 * 
 * @todo Update to WP_Comment_Query filter when WP updates core to use non-hardcoded queries.
 */
function anno_internal_comments_query($query) {
	$query = str_replace('WHERE', 'WHERE comment_type NOT IN (\'article_general\', \'article_review\') AND', $query);
	return $query;
}

?>