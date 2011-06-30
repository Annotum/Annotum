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
if (CFCT_DEBUG) { cfct_banner(__FILE__); }

get_header();
$show_post_type = get_option('anno_home_post_type', 'article');
if ($show_post_type == 'article') {
	global $wp_query;
	$wp_query = new WP_Query(array('post_type' => 'article'));
}
?>
<div id="main-body" class="clearfix">
	<?php cfct_misc('callouts'); ?>
	<h1 class="section-title"><span><?php _ex('Recent Articles', 'Heading text for home page', 'anno'); ?></span></h1>
	<div id="content">
	<?php
	cfct_loop();
	cfct_misc('nav-posts');
	?>
	</div>
</div><!-- #main-content -->
<div id="main-sidebar" class="clearfix">
	<?php get_sidebar(); ?>
</div><!-- #main-sidebar -->
<?php get_footer(); ?>