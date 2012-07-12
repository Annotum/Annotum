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
if (CFCT_DEBUG) { cfct_banner(__FILE__); }

$author = esc_attr(get_query_var('author'));
$user = get_user_by('id', $author);

get_header();

// Override 
global $wp_query;
// A second query because we wanted is_author to be true all the way up to here
// Though the query should not force a lookup on post_author, but rather post meta described below
if ($user) {
	$wp_query = new WP_Query(array(
		'post_type' => array('article', 'post'),
		'meta_query' => array(
			array( 
				'key' => '_anno_author_'.$user->ID,
			),
		),
	));
}

?>
<div id="main-body" class="clearfix">
	<?php 

		if (is_object($user)):
			global $anno_user_meta;
			foreach ($anno_user_meta as $key => $value) {
				$property = str_replace('_anno_', '', $key);
				$user->$property = get_user_meta($user->ID, $key, true);
			}
			?>
				<h2 class="section-title"><span><?php _e('Author Profile', 'anno'); ?></span></h2>
				<div class="author-header">
					<?php echo get_avatar($user->user_email, 140); ?>
					<h2 class="author-name"><?php echo anno_format_name(esc_html($user->prefix), esc_html($user->first_name), esc_html($user->last_name), esc_html($user->suffix)); ?></h2>
					<div class="author-link">
						<a href="<?php echo esc_html($user->user_url); ?>"><?php echo esc_html($user->user_url); ?></a>
					</div>
					<?php
						foreach ($anno_user_meta as $key => $label) {
							if (!empty($user->$key) && !in_array($key, array('_anno_prefix', '_anno_suffix'))) {
								echo '<p class="author-meta '.esc_attr('author-'.anno_sanitize_meta_key($key)).'">'.esc_html($label).': '.$user->$key.'</p>';
							}
						}
					?>
					<p class="author-meta author-bio"><?php echo esc_html($user->description); ?></p>
				</div><!-- .author-header -->
			
			<?php 
			
		endif;
		
		?>
		<h2 class="section-title"><span><?php _e('Recent Posts', 'anno'); ?></span></h2>	
		<?php
		cfct_loop();
		cfct_misc('nav-posts');
		?> 
</div><!-- #main-content -->
<div id="main-sidebar" class="clearfix">
	<?php get_sidebar(); ?>
</div><!-- #main-sidebar -->
<?php get_footer(); ?>