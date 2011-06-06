<?php
/**
 * @package plos
 * This file is part of the Plos theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2010 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

function plos_setup() {
	add_action('wp_head', 'plos_css3_pie', 8);
}
add_action('after_setup_theme', 'plos_setup');

function plos_css3_pie() {
	$assets_root = get_bloginfo('template_url') . '/assets/main/';
	?>
	<!--[if lte IE 8]>
	<style type="text/css" media="screen">
		
		.callout-item,
		.featured-posts ul,
		.featured-posts .carousel-item,
		#sidebar .widget .title,
		#respond #submit,
		.tools-nav .nav li a {
			behavior: url(<?php echo $assets_root; ?>js/libs/css3pie/PIE.php);
		}
	</style>
	<![endif]-->
<?php
}

function plos_assets() {
	if (!is_admin()) {
		$main =  trailingslashit(get_bloginfo('stylesheet_directory')) . 'assets/main/';
		$v = ANNO_VER;

		// Styles
		wp_enqueue_style('plos', $main.'css/main.css', array('anno'), $v, 'screen');
		if (is_rtl()) {
			wp_enqueue_style('plos-rtl', $main.'css/rtl.css', array('anno-rtl'), $v, 'screen');
		}
	}
}
add_action('wp', 'plos_assets');

?>