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
 * Array of user meta keys and their labels
 */  
global $anno_user_meta;
$anno_user_meta = apply_filters('anno_user_meta', array(
	'_anno_prefix' => _x('Name Prefix', 'form label', 'anno'),
	'_anno_suffix' => _x('Name Suffix', 'form label', 'anno'),
	'_anno_degrees' => _x('Degrees', 'form label', 'anno'),
	'_anno_affiliation' => _x('Affiliation', 'form label', 'anno'),
	'_anno_institution' => _x('Institution', 'form label', 'anno'),
	'_anno_department' => _x('Department', 'form label', 'anno'),
	'_anno_country' => _x('Country', 'form label', 'anno'),
	'_anno_state' => _x('State', 'form label', 'anno'),
	'_anno_city' => _x('City', 'form label', 'anno'),
));

/**
 * User profile markup for Annotum specific items
 */ 
function anno_profile_fields() {
	global $anno_user_meta;
	if (is_array($anno_user_meta) && !empty($anno_user_meta)) {
		$current_user = wp_get_current_user();
?>
		<?php echo apply_filters('anno_profile_fields_title', __('<h3>Miscellaneous</h3>', 'anno')); ?>
		<table class="form-table">
			<tbody>		
				
<?php
		foreach ($anno_user_meta as $key => $label) {
			$meta_val = get_user_meta($current_user->ID, $key, true);
?>
				<tr>
					<th><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
					<td><input type="text" name="<?php echo esc_attr($key); ?>" class="regular-text" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($meta_val); ?>" />
				</tr>
<?php
		} // foreach
?>
			</tbody>
		</table>
		<input type="hidden" name="anno_profile_update" value="1">
<?php	
	} // if
}
add_action('show_user_profile', 'anno_profile_fields');
add_action('edit_user_profile', 'anno_profile_fields');

/**
 * Update Annotum specific user meta
 */
function anno_profile_update($user_id) {
	global $anno_user_meta;
	// anno_profile_update to ensure that we're updating from the user profile edit page
	if (is_array($anno_user_meta) && !empty($anno_user_meta) && isset($_POST['anno_profile_update'])) {
		foreach ($anno_user_meta as $key => $label) {
			// Set null value for clearing
			$value = isset($_POST[$key]) ? $_POST[$key] : '';
			update_user_meta($user_id, $key, $value);
		}
	}
}
add_action('personal_options_update', 'anno_profile_update');
add_action('edit_user_profile_update', 'anno_profile_updat');


/**
 * Takes a snapshot of author/co-authors user data and stores it in post data
 * Only stores on publish and does not overwrite existing.
 */ 
function anno_users_snapshot($post_id, $post) {
	if ($post->post_status == 'publish' && $post->post_type == 'article') {
		$authors = anno_get_authors($post->ID);
		$author_meta = get_post_meta($post_id, '_anno_author_snapshot', true);
		if (!is_array($author_meta)) {
			$author_meta = array();
		}
		
		foreach ($authors as $author_id) {
			if (array_key_exists($author_id, $author_meta)) {
				continue;
			}
			$author = get_userdata($author_id);
			if ($author) {
				global $anno_user_meta;
				$author_meta[$author->ID] = array(
					'id' => $author->ID,
					'surname' => $author->last_name,
					'given_names' => $author->first_name,
					'bio' => $author->user_description,
					'email' => $author->user_email,
					'link' => $author->user_url,
				);
				// Leverage anno_user_meta global
				if (is_array($anno_user_meta) && !empty($anno_user_meta)) {
					foreach ($anno_user_meta as $key => $label) {
						// Remove anno prefix if present
						if (strpos($key, '_anno_') === 0) {
							$key = substr($key, 6);
						}
						$author_meta[$author->ID][$key] = get_user_meta($author->ID, $key, true);
					}
				}
				
			}
			else {
				delete_post_meta($post->ID, '_anno_author_'.$author_id);
			}
		}
		update_post_meta($post_id, '_anno_author_snapshot', $author_meta);
	}
}
add_action('wp_insert_post', 'anno_users_snapshot', 10, 2);


?>