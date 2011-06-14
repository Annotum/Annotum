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

function anno_the_authors() {
	global $post;
	$out = '';
	$author_id = get_the_author_meta('id');

	$authors = array();
	if (function_exists('annowf_get_post_users')) {
		$authors = annowf_get_post_users($post->ID, '_co_authors');
	}
	
	array_unshift($authors, $author_id);
	
	foreach ($authors as $id) {
		$author = get_userdata($id);
		$posts_url = get_author_posts_url($id);
		// Name
		$first_name = esc_attr($author->user_firstname);
		$last_name = esc_attr($author->user_lastname);
		
		if ($first_name && $last_name) {
			$fn = '<a href="'.$posts_url.'" class="url name"><span class="given-name">'.$first_name.'</span> <span class="family-name">'.$last_name.'</span></a>';
		}
		else {
			$fn = '<a href="'.$posts_url.'" class="fn url">' . esc_attr($author->display_name) . '</a>';
		}

		// Website
		$trimmed_url = substr($author->user_url, 0, 20);
		$trimmed_url = ($trimmed_url != $author->user_url ? $trimmed_url . '&hellip;' : $author->user_url);

		$website = $author->user_url ? '<span class="group">' . __('Website:', 'anno') . ' <a class="url" href="' . esc_url($author->user_url) . '">' . $trimmed_url . '</a></span>' : '';

		// Note
		$note = $author->user_description ? '<span class="group note">' . esc_attr($author->user_description) . '</span>' : '';

		// @TODO Honoraries (PHD, etc)
		// @TODO organization (MIT, etc)

		$card = '
<li>
	<div class="author vcard">
		'.$fn;

	if ($website || $note) {
		$card .= '
		<span class="extra">
			<span class="extra-in">
				'.$website.'
				'.$note.'
			</span>
		</span>';
	}

	$card .= '
	</div>
</li>';
	
		$out .= $card;
		$i++;
	}
	
	echo $out;
}

/**
 * Text-only excerpt -- safe for textareas.
 */
function anno_get_excerpt_text() {
	ob_start();
	the_excerpt();
	$text = ob_get_clean();
	$text = strip_tags($text);
	return trim($text);
}

function anno_excerpt_text() {
	echo anno_get_excerpt_text();
}
?>