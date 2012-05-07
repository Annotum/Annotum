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

/**
 * Get all (as of 1.2, there will be only 1) immediate clones
 *
 * @param int $post_id 
 * @return array|bool False if no clones found, array otherwise
 */
function annowf_get_clones($post_id) {
	return get_post_meta($post_id, '_anno_posts_cloned', true);
}

/**
 * Get the post that this one is cloned from
 *
 * @param int $post_id 
 * @return int|bool False if no parent found, post ID otherwise
 */
function annwf_get_cloned_from($post_id) {
	return get_post_meta($post_id, '_anno_cloned_from', true);
}


/**
 * Load cloned metabox when cloned from or clone exists
 */
function annowf_clone_meta_box_setup() {
	global $post;
	
	// Clone data meta box. Only display if something has been cloned from this post, or it is a clone itself.
	// Pass these in as callback args to prevent another 2 get_post_meta calls
	$clones = annowf_get_clones($post->ID);
	$cloned_from = annwf_get_cloned_from($post->ID);
	if (!empty($posts_cloned) || !empty($cloned_from)) {
		add_meta_box('anno-cloned', _x('Versions', 'Meta box title', 'anno'), 'annowf_cloned_meta_box', 'article', 'side', 'low', array('cloned_from' => $cloned_from, 'clones' => $clones));
	}
}
add_action('add_meta_boxes_article', 'annowf_clone_meta_box_setup');

/**
 * Metabox for posts that have been cloned from this post
 */ 
function annowf_cloned_meta_box($post, $metabox) {
	$cloned_from = $metabox['args']['cloned_from'];
	$cloned_from_post = get_post($cloned_from_post);
	if (!$cloned_from_post) {
		return;
	}
?>
	<dl class="anno-versions">
<?php
	if (!empty($cloned_from)) {
		$cloned_post = get_post($cloned_from);
?>
		<dt><?php echo _x('Cloned From', 'Cloned meta box text', 'anno'); ?></dt>
		<dd><?php echo '<a href="'.esc_url(get_edit_post_link($cloned_from)).'">'.esc_html($cloned_post->post_title).'</a>'; ?></dd>
<?php	
	}
	
	$posts_cloned = $metabox['args']['clones'];
	if (!empty($posts_cloned) && is_array($posts_cloned)) {
?>
		<dt><?php echo _x('Clone', 'Cloned meta box text', 'anno'); ?></dt>
<?php
		foreach ($posts_cloned as $cloned_post_id) {
			$cloned_post = get_post($cloned_post_id);
			if (!empty($cloned_post)) {
				echo '<dd><a href="'.esc_url(get_edit_post_link($cloned_post_id)).'">'.esc_html($cloned_post->post_title).'</a></dd>';
			}
		}
	}
?>
	</dl>
<?php
}

/**
 * Check if a post is a clone of another
 *
 * @param int $post_id 
 * @return bool
 */
function annowf_is_clone($post_id) {
	$parent = annwf_get_cloned_from($post_id);
	// *NOTE* This does not check existance of post
	return is_numeric($parent) && $parent != 0;	
}

/**
 * Check if a post has clones
 *
 * @param int $post_id 
 * @return bool
 */
function annowf_has_clone($post_id) {
	$clones = annowf_get_clones($post_id);
	// *NOTE* Does not check existance of posts
	return !empty($clones) && is_array($clones);
}


/**
 * Disable the title input visuall
 */
function annowf_clone_admin_js() {
	global $post;
	// jQuery already loaded
?>
<script type="text/javascript">
	(function($) { 
		$(function() {
	   $('input#title').prop('disabled', true);
	  });
	})(jQuery);
</script>
<?php	
}

/**
 * Load JS hook if a user cannot edit the title
 */
function annowf_clone_prevent_title_edit() {
	global $post;
	if (!anno_user_can('administrator') && annowf_is_clone($post->ID)) {
		add_action('admin_head', 'annowf_clone_admin_js');
	}	
}
add_action('admin_head', 'annowf_clone_prevent_title_edit', 0);


/**
 * Prevent any insert cloned posts from changing the title,
 * Unless a user is an admin
 *
 * @param array $data 
 * @param array $postarr 
 */
function annowf_clone_prevent_title_save($data, $postarr) {
	if (
		!anno_user_can('administrator') 
		&& isset($postarr['ID']) 
		&& annowf_is_clone($postarr['ID']) 
		&& $data['post_type'] == 'article'
	) {
		// Reset data to the old 
		$old_post = get_post($postarr['ID']);
		if ($old_post) {
			$data['post_title'] = $old_post->post_title;
		}
	}
	
	return $data;
}
add_action('wp_insert_post_data', 'annowf_clone_prevent_title_save', 10, 2);


/**
 * Clones a post and inserts it into the DB. Maintains all post properties (no post_meta). Also
 * saves the association on both posts.
 *
 * @param int $orig_id The original ID of the post to clone from
 * @return int|bool The newly created (clone) post ID. false if post failed to insert.
 * @todo Clone post-meta
 */
function annowf_clone_post($orig_id) {
	global $current_user;

	$post = get_post($orig_id);	
	if (empty($post)) {
		return false;
	}
	
	
	$article_tags = wp_get_object_terms($orig_id, 'article_tag');
	// Need the slugs for non-heirarchical, no params to return the slug
	$ti_article_tags = array();
	foreach ($article_tags as $article_tag) {
		$ti_article_tags[] = $article_tag->slug;
	}

	$article_categories = wp_get_object_terms($orig_id, 'article_category', array('fields' => 'ids'));
	array_walk($article_categories, 'intval');

	// Form the new cloned post
	$new_post = array(
		'post_author' => $current_user->ID,
		'post_status' => 'draft',
		'post_title' => $post->post_title,
		'post_content_filtered' => $post->post_content_filtered,
		'post_content' => $post->post_content,
		'post_excerpt' => $post->post_excerpt,
		'post_type' => $post->post_type,
		'post_parent' => $post->post_parent,
		'tax_input' => array(
			'article_tag' => $ti_article_tags,
			'article_category' => $article_categories,
		),
	);
		
	remove_filter('wp_insert_post_data', 'anno_insert_post_data', null, 2);
	$new_id = wp_insert_post($new_post);
	add_filter('wp_insert_post_data', 'anno_insert_post_data', null, 2);

	// Add to clone/cloned post meta
	if ($new_id) {
		$posts_cloned = get_post_meta($orig_id, '_anno_posts_cloned', true);
		if (!is_array($posts_cloned)) {
			$posts_cloned = array($new_id);
		}
		else {
			$posts_cloned[] = $new_id;
		}
		update_post_meta($orig_id, '_anno_posts_cloned', $posts_cloned);
		update_post_meta($new_id, '_anno_cloned_from', $orig_id);
	}
	
	
	
	// Create cloned images here too
	// Featured image association
	// Image source replacement
	// @TODO
	
	return $new_id;
}
?>