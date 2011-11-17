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


get_header();

global $post;
if (have_posts()) : while (have_posts()) : the_post(); 

$title = get_the_title($post->post_parent);
if (empty($title) && !empty($post->post_parent)) {
	$title = __('article', 'anno');
}

if (!empty($title)) {
?>
<a href="<?php echo get_permalink($post->post_parent); ?>" rev="attachment">&larr; <?php printf(_x('back to &#8220;%s&#8221;', 'pagination on attachments', 'anno'), $title); ?></a>
<?php 
}

?>
<a href="<?php echo wp_get_attachment_url($post->ID); ?>"><?php echo wp_get_attachment_image( $post->ID, 'full' ); ?></a>

<h1><?php the_title(); ?></h1>

<?php 

if ( !empty($post->post_excerpt) ) the_excerpt(); // this is the "caption"

the_content();

if (cfct_get_adjacent_image_link(false) != '') {
	next_image_link();
}
if (cfct_get_adjacent_image_link(true) != '') {
	previous_image_link();
}
	
?>

<?php endwhile; else: ?>

<p><?php _e('Sorry, no attachments matched your criteria.', 'anno'); ?></p>

<?php endif; ?>

<?php 

wp_footer();

get_footer();

?>