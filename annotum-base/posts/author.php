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

$author = esc_attr(get_query_var('author_name'));
$user = get_user_by('login', $author);

get_header();

global $wp_query;
$wp_query = new WP_Query(array(
	'post_type' => array('article', 'post'),
	'meta_query' => array(
		array( 
			'key' => '_anno_author_'.$user->ID,
		),
	),
));

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
				<h2 class="section-title"><span>Author Profile</span></h2>
				<div class="author-header">
					<?php echo get_avatar($user->user_email, 140); ?>
					<h2 class="author-name"><?php echo anno_format_name(esc_html($user->prefix), esc_html($user->first_name), esc_html($user->last_name), esc_html($user->suffix)); ?></h2>
					<div class="author-link">
						<a href="<?php echo esc_html($user->user_url); ?>"><?php echo esc_html($user->user_url); ?></a>
					</div>
		
					<p class="author-degrees"><?php echo esc_html($user->degrees) ?></p>
					<p class="author-affiliations"><?php echo esc_html($user->affiliation); ?></p>
					<p class="author-bio"><?php echo esc_html($user->description); ?></p>
				</div><!-- .author-header -->
			
			<?php 
			
		endif;
		
		?>
		<h2 class="section-title"><span>Recent Posts</span></h2>	
		<?php
		cfct_loop();
		cfct_misc('nav-posts');
		?> 
</div><!-- #main-content -->
<div id="main-sidebar" class="clearfix">
	<?php get_sidebar(); ?>
</div><!-- #main-sidebar -->
<?php get_footer(); ?>