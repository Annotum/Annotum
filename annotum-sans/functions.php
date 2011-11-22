<?php
/**
 * @package annotum_sans
 * This file is part of the Annotum Sans theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2011 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

function annotum_sans_setup() {
	add_action('wp_head', 'annotum_sans_css3_pie', 8);
}
add_action('after_setup_theme', 'annotum_sans_setup');

/**
 * Don't load any additional plugins outside of the required ones.
 */ 
if (!function_exists('anno_load_plugins')) {
	// Plugins specific to certain themes can be loaded with this function
	function anno_load_plugins() {}
}

function annotum_sans_css3_pie() {
	$assets_root = get_bloginfo('template_url') . '/assets/main/';
	?>
	<!--[if lte IE 8]>
	<style type="text/css" media="screen">
		
		.callout-item,
		.featured-posts ul,
		.featured-posts .carousel-item,
		#main-sidebar .widget .title,
		#respond #submit,
		.tools-nav .nav li a {
			behavior: url(<?php echo $assets_root; ?>js/libs/css3pie/PIE.php);
		}
	</style>
	<![endif]-->
<?php
}

function annotum_sans_assets() {
	if (!is_admin()) {
		$main =  trailingslashit(get_bloginfo('stylesheet_directory')) . 'assets/main/';
		$v = ANNO_VER;

		// Styles
		wp_enqueue_style('annotum-sans', $main.'css/main.css', array('anno'), $v, 'screen');
		if (is_rtl()) {
			wp_enqueue_style('annotum-sans-rtl', $main.'css/rtl.css', array('anno-rtl'), $v, 'screen');
		}
	}
}
add_action('wp', 'annotum_sans_assets');

?>