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

$author = esc_attr(get_query_var('author_name'));
$user = get_user_by('login', $author);

get_header();

global $wp_query;
$wp_query = new WP_Query(array(
	'post_type' => array('article', 'post'),
	'meta_query' => array(
		array( 
			'key' => '_anno_co_author',
			'value' => $user->ID,
		),
	),
));

?>
<div id="main-body" class="clearfix">
	<?php 

		if (is_object($user)) {
			global $anno_user_meta;
			foreach ($anno_user_meta as $key => $value) {
				$property = str_replace('_anno_', '', $key);
				$user->$property = get_user_meta($user->ID, $key, true);
			}
			
			echo 'Username: '.esc_html($user->user_login);
			echo '<br />Name Prefix: '.esc_html($user->prefix);
			echo '<br />First Name: '.esc_html($user->first_name);
			echo '<br />Last Name: '.esc_html($user->last_name);
			echo '<br />Name Suffix: '.esc_html($user->suffix);
			echo '<br />User Email: '.esc_html($user->user_email);
			echo '<br />User Url: '.esc_html($user->user_url);
			echo '<br />Degrees: '.esc_html($user->degrees);
			echo '<br />Affiliation: '.esc_html($user->affiliation);
			echo '<br />Description: '.esc_html($user->description);
			
		}
		cfct_loop();
		cfct_misc('nav-posts');
	?> 
</div><!-- #main-content -->
<div id="main-sidebar" class="clearfix">
	<?php get_sidebar(); ?>
</div><!-- #main-sidebar -->
<?php get_footer(); ?>