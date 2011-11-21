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
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

$main =  trailingslashit(get_bloginfo('template_directory')) . 'assets/main/';
$v = ANNO_VER;

// Styles
wp_enqueue_style('anno', $main.'css/main.css', array(), $v, 'screen, print');

// Right-to-left languages
if (is_rtl()) {
	// Override stylesheet
	wp_enqueue_style('anno-rtl', $main.'css/rtl.css', array('anno'), $v, 'screen');
}

// Scripts
wp_enqueue_script('modernizr', $main.'js/libs/modernizr-1.7.min.js', array(), $v);
wp_register_script('jquery-cf-placeholder', $main.'js/libs/jquery.placeholder.min.js', array('jquery'), $v);
wp_register_script('jquery-cycle-lite', $main.'js/libs/jquery.cycle.lite.1.1.min.js', array('jquery'), $v);

wp_enqueue_script('anno-main', $main.'js/main.js', array('jquery-cf-placeholder', 'jquery-cycle-lite', 'jquery-ui-tabs'), $v);
wp_localize_script('anno-main', 'ANNO_DICTIONARY', array(
	'previous' => __('Previous', 'anno'),
	'next' => __('Next', 'anno'),
	'xofy' => __('%1$s of %2$s', 'anno')
));

if ( is_singular() ) {
	wp_enqueue_script( 'comment-reply' );
}

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
add_action('wp_head', 'anno_css3_pie', 8);
?>