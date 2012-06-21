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
function annowf_get_cloned_from_id($post_id) {
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
	$cloned_from = annowf_get_cloned_from_id($post->ID);
	
	if (!empty($clones) || !empty($cloned_from)) {

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
	$parent = annowf_get_cloned_from_id($post_id);
	$is_clone = false;
	$post = get_post(intval($parent));
	if (!empty($post)) {
		$is_clone = true;
	}
	return $is_clone;
}

/**
 * Check if a post has clones
 *
 * @param int $post_id 
 * @return bool
 */
function annowf_has_clone($post_id) {
	$clones = annowf_get_clones($post_id);
	$has_clone = false;
	if (is_array($clones)) {
		foreach ($clones as $clone_id) {
			$cloned_post = get_post(intval($clone_id));
			if (!empty($cloned_post)) {
				$has_clone = true;
			}
		}
	}

	return $has_clone;
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
	global $post, $pagenow;
	if ($pagenow == 'post.php' && !anno_user_can('administrator') && annowf_is_clone($post->ID) && $post->post_type == 'article') {
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
		
		annowf_clone_post_meta($orig_id, $new_id);
		annowf_clone_post_attachments($orig_id, $new_id);
	}
	
	return $new_id;
}

/**
 * Clone relevant post meta
 *
 * @param int $orig_post_id 
 * @param int $new_post_id 
 * @return bool false if one of the posts do not exist, true otherwise
 */
function annowf_clone_post_meta($orig_post_id, $new_post_id) {
	if (!get_post($orig_post_id) || !get_post($new_post_id)) {
		return false;
	}
		
	$clone_meta_keys = array(
		'_anno_appendices' => 1, 
		'_anno_appendices_html' => 1,
		'_anno_acknowledgements' => 1,
		'_anno_funding' => 1, 
		'_anno_subtitle' => 1, 
		'_anno_author_order' => 1, 
		'_anno_references' => 1,
	);
	// _post_state => draft applied on insert in annowf_clone_post()

	// Single query instead of looping through and using get_post_meta
	$meta_data = get_metadata('post', $orig_post_id);
	
	foreach ($meta_data as $meta_key => $meta_value) {
		if (isset($clone_meta_keys[$meta_key]) || strpos($meta_key, '_anno_author_') !== false) {
			// get_metadata returns and array of arrays. In no instance should there be an array with
			// more than one element (multiple rows with the same meta_key in db)
			update_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value[0]));
		}
	}
	
	return true;
}

/**
 * Clone attachments, account for potentially new thumbnail id
 * and content with potential of image should update too
 *
 * @param int $orig_post_id 
 * @param int $new_post_id 
 * @return bool false if one of the posts do not exist, true otherwise
 */
function annowf_clone_post_attachments($orig_post_id, $new_post_id) {
	// Used later for content remapping
	$new_post = get_post($new_post_id);
	if (!get_post($orig_post_id) || !$new_post) {
		return false;
	}
		
	$thumb_id = get_post_meta($orig_post_id, '_thumbnail_id', true);
	
	$query = new WP_Query(array(
		'posts_per_page' => -1,
		'post_type' => 'attachment',
		'post_parent' => $orig_post_id,
		'post_status' => 'inherit',
	));
	
	if (!empty($query->posts) && is_array($query->posts)) {
		$content_remap = array();
		
		foreach ($query->posts as $attachment) {
			// Get the file
			$orig_file_path = get_attached_file($attachment->ID, true);
			$orig_file = @file_get_contents($orig_file_path);
			if ($orig_file) {
				$attachment_array = (array) $attachment;
				$attachment_id = $attachment->ID;
				$attachment_array['post_parent'] = $new_post_id;
				// New attachment, unset ID to prevent updating the current
				unset($attachment_array['ID']);

				// Put the new file in the directory
				$new_file = wp_upload_bits(basename($orig_file_path), null, $orig_file);
				if (isset($new_file['file'])) {

					$new_attachment_id = wp_insert_attachment($attachment_array, $new_file['file']);
					
					if (!is_wp_error($new_attachment_id) && !empty($new_attachment_id)) {
						// Generate sizes
						wp_update_attachment_metadata($new_attachment_id, wp_generate_attachment_metadata($new_attachment_id, $new_file['file']));
						// New post should have cloned thumbnail ID
						if ($attachment_id == $thumb_id) {
							$thumb_id = $new_attachment_id;
						}
						
						// Generate all relevant URLs, store old urls => new urls
						foreach (get_intermediate_image_sizes() as $size) {
							$old_data = wp_get_attachment_image_src($attachment->ID, $size);
							$old_url = str_replace(' ', '%20', $old_data[0]);
							
							$new_data = wp_get_attachment_image_src($new_attachment_id, $size);
							$new_url = str_replace(' ', '%20', $new_data[0]);

							$content_remap[$old_url] = $new_url;
						}
					}
				}
			}
		}
		
		if (!empty($new_post_id)) {
			update_post_meta($new_post_id, '_thumbnail_id', $thumb_id);
		}

		// Replace post_content, post_content_filtered, post_excerpt, meta
		if (!empty($content_remap)) {
			$replacement_meta_keys = array(
		 		'_anno_appendices',
				'_anno_appendices_html',
		 		'_anno_funding',
				'_anno_acknowledgements', 
			);
			
			$meta_data = get_metadata('post', $new_post_id);
			$replacement_meta = array();
			
			foreach ($content_remap as $old_url => $new_url) {
				foreach ($replacement_meta_keys as $meta_key) {
					if (isset($meta_data[$meta_key][0])) {
						// get_metadata returns and array of arrays. In no instance should there be an array with
						// more than one element (multiple rows with the same meta_key in db)
						$replacement_meta[$meta_key] = str_replace($old_url, $new_url, maybe_unserialize($meta_data[$meta_key][0]));
					}
				}
					
				$new_post->post_content = str_replace($old_url, $new_url, $new_post->post_content);
				$new_post->post_content_filtered = str_replace($old_url, $new_url, $new_post->post_content_filtered);
				$new_post->post_excerpt = str_replace($old_url, $new_url, $new_post->post_excerpt);
			}
			
			// Update post
			remove_filter('wp_insert_post_data', 'anno_insert_post_data', null, 2);
			wp_update_post($new_post);
			add_filter('wp_insert_post_data', 'anno_insert_post_data', null, 2);
			
			// Update meta
			foreach ($replacement_meta_keys as $meta_key) {
				if (isset($replacement_meta[$meta_key])) {
					update_post_meta($new_post_id, $meta_key, $replacement_meta[$meta_key]);
				}
			}
			
		}	
	}	
	
	return true;
}

/**
 * Modify home query to only pull the latest posts
 */
function annowf_clone_home_filter($query) {
	remove_filter('pre_get_posts', 'anno_clone_home_filter');
	// Get all posts that are published and have the _anno_cloned_from meta
	if ($query->is_front_page() && $query->is_main_query()) {
		$clones = new WP_Query(array(
			'posts_per_page' => -1,
			'post_type' => 'article',
			'post_status' => 'publish',
			'meta_query' => array(
				'key' => '_anno_cloned_from',
				'value' => 0,
				'compare' => '!=',
			),
			'fields' => 'ids',			
		));
	
		// Exclude any clones
		if (!empty($clones->posts)) {
			$exclude_ids = annowf_get_cloned_from($clones->posts);
			if (!empty($exclude_ids)) {
 				$query->set('post__not_in', $exclude_ids);
			}
		}
	}
	
}
add_filter('pre_get_posts', 'annowf_clone_home_filter');

/**
 * Get all posts that a set of IDs are cloned from
 * 
 * @param array $post_ids Array of post_ids
 * @return array Array of post ids that this set of posts is cloned from
 */
function annowf_get_cloned_from($post_ids) {
	if (is_array($post_ids) && !empty($post_ids)) {
		global $wpdb;
		$post_ids_prepared = array();
		foreach ($post_ids as $post_id) {
			$post_ids_prepared[] = $wpdb->prepare('%d', $post_id);
		}
		if (!empty($post_ids_prepared)) {
			$query = "
				SELECT `meta_value` 
				FROM $wpdb->postmeta 
				WHERE `meta_key` = '_anno_cloned_from' 
				AND `post_id` IN (".implode(',', $post_ids_prepared).")
			";
			
			$exclude_ids = $wpdb->get_col($query);
			if (is_array($exclude_ids)) {
				return $exclude_ids;
			}
		}
	}
	return array();
}

/**
 * Gets ancestors this post has been cloned from
 *
 * @param int $post_id 
 * @return array Array of post ids in the clone line
 */
function annowf_clone_get_ancestors($post_id) {
	$ancestors = array();
	do {
		$cloned_from = $post_id = annowf_get_cloned_from_id($post_id);
		if (!empty($cloned_from)) {
			$ancestors[] = $cloned_from;
		}
	} while (!empty($cloned_from));
	return $ancestors;	
}

/**
 * Gets all subsequent child clones
 * Since this method's inception, all clones are linear
 * so it does not travel outside of the first clone's path
 *
 * @param int $post_id 
 * @return array Array of post IDs
 */
function annowf_clone_get_children($post_id) {
	$children = array();
	do {
		$child_array = annowf_get_clones($post_id);
		if (!empty($child_array) && is_array($child_array)) {
			$children[] = $post_id = $child_array[0];
		}
	} while (!empty($child_array));
	return $children;
}

function annowf_clone_get_family($post_id) {
	$ancestors = annowf_clone_get_ancestors($post_id);
	$children = annowf_clone_get_children($post_id);
	
	return array_unique(array_merge($ancestors, $children));
}

/**
 * Provide a dropdown of major revisions of an article
 */
function annowf_get_clone_dropdown($post_id) {
	$family_ids = annowf_clone_get_family($post_id);
	$markup = '';
	$inside = '';
	
	if (!empty($family_ids) && is_array($family_ids)) {
		// Only add this id if there are other revisions
		$family_ids[] = $post_id;
		// Only get articles that are published in the set of family ids
		$query = new WP_Query(array(
			'post__in' => $family_ids,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'post_type' => 'article',
		));

		if (!empty($query->posts)) {
			$i = count($query->posts);
			foreach ($query->posts as $post) {
				
				$inside .= '<option value="'.esc_attr(get_permalink($post->ID)).'"'.selected($post->ID, $post_id, false).'>'.esc_html(sprintf(_x('Edition %d - ', 'revision number', 'anno'), $i).mysql2date(get_option('date_format'), $post->post_date)).'</option>';
				$i--;
			}
		}
		wp_reset_query();
	}

	if (!empty($inside)) {
		$markup = '
	<select id="anno-revision-selector">
		<optgroup label="'.__('Revisions', 'anno').'">
		'.$inside.'
		</optgroup>
	</select>
	<script type="text/javascript">
	/* <![CDATA[ */
		var dropdown = document.getElementById("anno-revision-selector");
		function annoOnRevisionChange() {
			if ( !!dropdown.options[dropdown.selectedIndex].value ) {
				location.href = dropdown.options[dropdown.selectedIndex].value;
			}
		}
		dropdown.onchange = annoOnRevisionChange;
	/* ]]> */
	</script>';
	}
	
	return $markup;
}

?>