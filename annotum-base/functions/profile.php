<?php

/**
 * Array of user meta keys and their labels
 */  
global $anno_user_meta;
$anno_user_meta = array(
	'_anno_prefix' => _x('Name Prefix', 'form label', 'anno'),
	'_anno_suffix' => _x('Name Suffix', 'form label', 'anno'),
	'_anno_degrees' => _x('Degrees', 'form label', 'anno'),
	'_anno_affiliation' => _x('Affiliation', 'form label', 'anno'),
);

/**
 * Adds the menu page to WP.
 */ 
function anno_add_profile() {
	add_users_page( 
		_x('Annotum Profile', 'admin page title', 'anno'),
		_x('Annotum Profile', 'admin sidebar menu title', 'anno'),
		'read',
		'anno-profile',
		'anno_profile'
	);
}
add_action('admin_menu', 'anno_add_profile');

/**
 * User profile markup for Annotum specific items
 */ 
function anno_profile() {
	global $anno_user_meta;
	$current_user = wp_get_current_user();
?>
<div id="anno-profile-page" class="wrap">
	<h2><?php _ex('Annotum Profile', 'header', 'anno'); ?></h2>
	<?php if (isset($_GET['update']) && !empty($_GET['update'])) { ?>
	<div id="message" class="updated below-h2"><p><?php _ex('Profile Updated', 'admin status banner', 'anno'); ?></p></div>
	<?php } ?>
	<form method="post" action="<?php echo admin_url(); ?>">
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="first_name"><?php _ex('First Name', 'form label', 'anno'); ?></label></th>
					<td><span id="first_name"><?php echo esc_html($current_user->first_name); ?> &#40;<a href="<?php echo admin_url('profile.php#first_name'); ?>"><?php _ex('Change', 'profile link text', 'anno'); ?>&#41;</span></td>
				</tr>
				
				<tr>
					<th><label for="last_name"><?php _ex('Last Name', 'form label', 'anno'); ?></label></th>
					<td><span id="user_login"><?php echo esc_html($current_user->last_name); ?> &#40;<a href="<?php echo admin_url('profile.php#last_name'); ?>"><?php _ex('Change', 'profile link text', 'anno'); ?>&#41;</span></td>
				</tr>
				
				
<?php
	foreach ($anno_user_meta as $key => $label) {
		$meta_val = get_user_meta($current_user->ID, $key, true);
?>
				<tr>
					<th><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
					<td><input type="text" name="<?php echo esc_attr($key); ?>" class="regular-text" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($meta_val); ?>" />
				</tr>
<?php
	}
?>
			</tbody>
		</table>
		<p class="submit">
			<?php echo wp_nonce_field('anno_profile', 'anno_profile_nonce', true, false); ?>
			<input type="hidden" name="anno_action" value="update_profile" />
			<input type="hidden" name="user_id" value="<?php echo $current_user->ID; ?>">
			<input type="submit" value="<?php _ex('Update Profile', 'button label', 'anno'); ?>" class="button-primary" id="submit" />
		</p>
	</form>
</div>
<?php	
}

/**
 * User profile request handler. Handles saving of user meta.
 */ 
function anno_profile_request_handler() {
	if (isset($_POST['anno_action'])) {
		switch ($_POST['anno_action']) {
			case 'update_profile':
				if (isset($_POST['user_id'])) {
					check_admin_referer('anno_profile', 'anno_profile_nonce');
					global $anno_user_meta;
					foreach ($anno_user_meta as $meta_key => $label) {
						if (isset($_POST[$meta_key])) {
							$value = trim($_POST[$meta_key]);
						}
						else {
							$value = '';
						}
						update_user_meta(absint($_POST['user_id']), $meta_key, $value);
					}

					wp_redirect(admin_url('users.php?page=anno-profile&update=true'));
					die();
				}
				break;
			default:
				break;
		}
	}
}
add_action('init', 'anno_profile_request_handler', 0);

/**
 * Takes a snapshot of author/co-authors user data and stores it in post data
 * Only stores on publish and does not overwrite existing.
 */ 
//TODO Order
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
				$author_meta[$author->ID] = array(
					'id' => $author->ID,
					'surname' => $author->last_name,
					'given_names' => $author->first_name,
					'prefix' => get_user_meta($author->ID, '_anno_prefix', true),
					'suffix' => get_user_meta($author->ID, '_anno_suffix', true),
					'degrees' => get_user_meta($author->ID, '_anno_degrees', true),
					'affiliation' => get_user_meta($author->ID, '_anno_affiliation', true),
					'bio' => $author->user_description,
					'email' => $author->user_email,
					'link' => $author->user_url,
				);
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