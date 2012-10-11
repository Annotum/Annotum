<?php 

/**
 * Hook to add the snapshot meta box
 */
function anno_snapshot_add_meta_box($post) {
	if ($post->post_status == 'publish') {
		add_meta_box('snapshotdiv', _x('Author Snapshot Data', 'Meta box title', 'anno'), 'anno_snapshot_meta_box', 'article', 'normal', 'high');
	}
}
add_action('add_meta_boxes_article', 'anno_snapshot_add_meta_box', 1);

/**
 * Which keys are allowed to managed in the snapshot data for manual editing, utilizes anno_user_meta
 * @return array Array using key => label format
 */
function anno_snapshot_allowed_keys() {
	global $anno_user_meta;
	$allowed_keys = array(
		'id' => __('ID', 'anno'),
		'bio' => __('Bio', 'anno'),
		'surname' => __('Surname', 'anno'),
		'given_names' => __('Given Names', 'anno'),
	);
	$allowed_keys += anno_sanitize_user_meta_keys($anno_user_meta);
	return $allowed_keys;
}

/**
 * Generates meta box markup for snapshot editing, including JS for snapshot management
 * Javascript conditionally loaded in
 */
function anno_snapshot_meta_box($post) {
	$snapshot = get_post_meta($post->ID, '_anno_author_snapshot', true);
	if (!$snapshot) {
		$snapshot = array();
	}
	// Which keys are allowed to be edited
	$allowed_keys = anno_snapshot_allowed_keys();
?>
<input type="hidden" name="anno_snapshot_edit_save" value="1" />

<?php
	echo '
<div id="snapshot-wrapper">';
	foreach ($snapshot as $snapshot_key => $user_data) {
		echo anno_snapshot_user_markup($user_data, $allowed_keys);
	}
   	echo '
</div>
<div class="snapshot-actions">
	<div id="snapshot-status" class="hidden"></div>
	<input type="text" id="snapshot-user-input" class="user-input" />
	<a href="#" id="snapshot-add-another" class="button-secondary">'.__('Add User to Snapshot', 'pop').'</a>
</div>';
}

function anno_snapshot_user_markup($user_data, $allowed_keys) {
	// Generate title for the handle
	$uid = isset($user_data['id']) ? $user_data['id'] : uniqid();
	$title = '';
	if (isset($user_data['prefix'])) {
		$title = $user_data['prefix'];
	}
	if (isset($user_data['given_names'])) {
		$title .= empty($title) ? $user_data['given_names'] : ' '.$user_data['given_names'];
	}
	if (isset($user_data['surname'])) {
		$title .= empty($title) ? $user_data['surname'] : ' '.$user_data['surname'];
	}
	if (isset($user_data['suffix'])) {
		$title .= empty($title) ? $user_data['suffix'] : ' '.$user_data['suffix'];
	}
	
	if (empty($title)) {
		$title = $uid;
	}
$markup = '
	<fieldset class="snapshot-item">
		<div class="snapshot-handlediv" title="'.__('Click to toggle', 'anno').'">+</div>
		<h4 id="'.esc_attr('snapshot-handle-'.$uid).'"><span class="snapshot-title">'.$title.'</span> - <a href="#" class="snapshot-remove">'.__('remove', 'anno').'</a></h4>
		<div class="inside hidden">';

	foreach ($user_data as $key => $value) {
		$markup .= '<p class="snapshot-datum">';
		if (array_key_exists($key, $allowed_keys)) {
			$id = esc_attr($key.'-'.$uid);
			$class = esc_attr('snapshot-'.$key);
			$name = esc_attr('anno_snapshot['.$uid.']['.$key.']');
			switch ($key) {
				case 'id': 
					$markup .= '<label for="'.$id.'">'.$allowed_keys[$key].': </label> <input type="text" id="'.$id.'" readonly="readonly" value="'.esc_attr($value).'" class="'.$class.'" name="'.$name.'" data-id="'.esc_attr($uid).'" />';
					break;
				case 'bio':
					$markup .= '<label for="'.$id.'">'.$allowed_keys[$key].': </label> <textarea id="'.$id.'" class="'.$class.'" data-id="'.esc_attr($uid).'" name="'.$name.'">'.esc_textarea($value).'</textarea>';
					break;
				default:
					$markup .= '<label for="'.$id.'">'.$allowed_keys[$key].': </label> <input type="text" id="'.$id.'" value="'.esc_attr($value).'" class="'.$class.'" data-id="'.esc_attr($uid).'" name="'.$name.'" />';
					break;
			}
		}
		$markup .= '</p>';
	}
	$markup .= '
			</div>
		</fieldset>';

	return $markup;
}


/**
 * Forms snapshot data based on a user id
 *  
 * @param int $author_id ID of the author
 * @param int $post_id ID of the post, if the user is not found, remove them as an author on the post (used in initial snapshot)
 * @param array $author_meta Current snapshot data to check against. Used in creating initial snapshot
 * @return array Array of data, empty array if user did not exist
 */ 
function anno_snapshot_user_data($author_id, $post_id = false, $author_meta = array()) {
	global $anno_user_meta;

	$data = array();

	if (array_key_exists($author_id, $author_meta)) {
		return $author_meta[$author_id];
	}

	$author = get_userdata($author_id);
	if ($author) {
		global $anno_user_meta;
		$data = array(
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
					$sanitized_key = substr($key, 6);
				}
				$data[$sanitized_key] = get_user_meta($author->ID, $key, true);
			}
		}
		
	}
	else {
		if ($post_id) {
			// WP User doesnt exist, remove it from the list of authors.
			delete_post_meta($post_id, '_anno_author_'.$author_id);
		}
	}
	return $data;
}

/**
 * Ajax response to adding a user to the snapshot
 */ 
function anno_snapshot_add_user() {
	$response = array(
		'result' => '',
		'html' => '',
	);

	$user_login = trim($_POST['user']);
	$user = get_user_by('login', $user_login);

	// Check if the user already exists if we're adding via email
	if (empty($user) && anno_is_valid_email($user_login)) {
		$users = get_users(array('search' => $user_login));
		if (!empty($users) && is_array($users)) {
			$user = $users[0];
		}
	}
		
	if (!empty($user)) {
		$user_data = anno_snapshot_user_data($user->ID);
		$response['html'] = anno_snapshot_user_markup($user_data, anno_snapshot_allowed_keys());
		$response['result'] = 'success';
		$response['status'] = __('User Added', 'anno');
		
	}
	else {
		$response['status'] = sprintf(_x('User \'%s\' not found', 'Adding user error message for snapshot meta box', 'anno'), $user_login);
		$response['result'] = 'error';
	}

	echo json_encode($response);
	die();
}
add_action('wp_ajax_anno-add-user-snapshot', 'anno_snapshot_add_user');

/**
 * Save manual snapshot edited data
 */ 
function anno_snapshot_edit_save($data, $postarr) {
	if (isset($_POST['anno_snapshot_edit_save']) && !empty($postarr['post_ID'])) {
		//remove_action('wp_insert_post', 'anno_users_snapshot', 10, 2);
		$post_id = $postarr['post_ID'];

		$authors = empty($_POST['anno_snapshot']) ? array() : $_POST['anno_snapshot'];
		update_post_meta($post_id, '_anno_author_snapshot', $authors);

		// Delete all anno authors meta, it will be reassigned in the next step, no built in WP methods to do this in a single query
		$anno_author_ids = anno_get_authors($post_id);

		foreach ($anno_author_ids as $key => $id) {
			$anno_author_ids[$key] = '_anno_author_'.$id;
			$formats[] = '%s';
		}

		global $wpdb;
		array_unshift($anno_author_ids, $post_id);

		$sql = "DELETE FROM $wpdb->postmeta WHERE `post_id` = %d AND `meta_key` LIKE '_anno_author_%%' AND `meta_key` NOT IN ('_anno_author_snapshot', '_anno_author_order')";
		
		$sql = $wpdb->prepare($sql, $anno_author_ids);
		$wpdb->query($sql);

		delete_post_meta($post_id, '_anno_author_order');

		// Add the users back into the meta

		foreach ($authors as $id => $author_data) {
			// This also updates _anno_author_order
			anno_add_user_to_post('author', $id, $post_id);
		}

		// Check if primary author has been removed (WP Owner) assign to current user, do not add them to the snapshot
		if (!in_array($data['post_author'], array_keys($authors))) {
			// Do not re-add the old author
			remove_action('post_updated', 'annowf_switch_authors', '10, 3');
			$data['post_author'] = get_current_user_id();
		}
	}
	
	return $data;
}
add_filter('wp_insert_post_data', 'anno_snapshot_edit_save', 1, 2);

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
			$author_meta[$author_id] = anno_snapshot_user_data($author_id, $post_id, $author_meta);
		}
		update_post_meta($post_id, '_anno_author_snapshot', $author_meta);
	}
}
add_action('wp_insert_post', 'anno_users_snapshot', 10, 2);
