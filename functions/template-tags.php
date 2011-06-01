<?php
/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2010 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 * 
 * This file contains function wrappers for a few custom additions to the standard WordPress
 * template tag milieu.
 */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

/**
 * Get the subtitle data stored as post meta
 */
function anno_get_subtitle($post_id = false) {
	if (!$post_id) {
		$post_id = get_the_ID();
	}
	return get_post_meta($post_id, '_anno_subtitle', true);
}

/**
 * Check if an article has a subtitle
 */
function anno_has_subtitle($post_id = false) {
	return anno_get_subtitle($post_id) ? true : false;
}

/**
 * Output subtitle data stored as post meta
 */
function anno_the_subtitle() {
	echo anno_get_subtitle();
}

/**
 * Article Category is a custom taxonomy for articles
 */
function anno_the_terms($taxonomy = 'article_category', $before = '', $sep = '', $after = '') {
	$post_id = get_the_ID();
	echo get_the_term_list($post_id, $taxonomy, $before, $sep, $after);
}

?>