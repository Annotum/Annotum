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

// Translateable workflow states
global $annowf_states;
$annowf_states = array(
	'draft' => _x('Draft', 'Post state/status', 'anno'),
	'submitted' => _x('Submitted', 'Post state/status', 'anno'),
	'in_review' => _x('In Review', 'Post state/status', 'anno'),
	'approved' => _x('Approved', 'Post state/status', 'anno'),
	'rejected' => _x('Rejected', 'Post state/status', 'anno'),
	'published' => _x('Published', 'Post state/status', 'anno'),
);

/**
 * Remove/Add meta boxes
 */ 
function annowf_meta_boxes() {
	global $post;
	$post_state = annowf_get_post_state($post->ID);
	
	// Remove the WP Publish box
	remove_meta_box('submitdiv', 'article', 'side');

	// Remove author box
	remove_meta_box('authordiv', 'article', 'normal');
		
	// Remove slug div
	remove_meta_box('slugdiv', 'article', 'normal');
	
	if (!anno_user_can('manage_public_comments', null, $post->ID)) {
		remove_meta_box('commentsdiv', 'article', 'normal');
	}
	
	// Remove Revisions box in favor of audit log box
	if (anno_user_can('view_audit')) {
		add_meta_box('audit_log', _x('Audit Log', 'Meta box title', 'anno'), 'annowf_audit_log', 'article', 'normal', 'low');
	}
	
	// Remove taxonomy, edit, featured, boxes when a user is unable to save/edit
	if (!anno_user_can('edit_post', null, $post->ID)) {
		remove_meta_box('tagsdiv-article_tag', 'article', 'side');
		remove_meta_box('article_category_select', 'article', 'side');
		remove_meta_box('postimagediv', 'article', 'side', 'low');
	}
	
	//@TODO potential role hook here
	if (!(current_user_can('editor') || current_user_can('administrator'))) {
		remove_meta_box('commentstatusdiv', 'article', 'normal');
	}

	
	// Custom author select box. Only displays co-authors in the dropdown.

	add_meta_box('authordiv', _x('Author', 'Meta box title', 'anno'), 'annowf_author_meta_box', 'article', 'side', 'low');
	
	// Add the Annotum workflow publish box.
	global $annowf_states;
	add_meta_box('submitdiv', _x('Status:', 'Meta box title', 'anno').' '. esc_html($annowf_states[$post_state]), 'annowf_status_meta_box', 'article', 'side', 'high');

	if (anno_user_can('view_reviewers')) {
		add_meta_box('anno-reviewers', _x('Reviewers', 'Meta box title', 'anno'), 'annowf_reviewers_meta_box', 'article', 'side', 'low');
	}
	add_meta_box('anno-co-authors', _x('Co-Authors', 'Meta box title', 'anno'), 'annowf_co_authors_meta_box', 'article', 'side', 'low');
}
add_action('add_meta_boxes_article', 'annowf_meta_boxes');

/**
 * Enqueue css for workflow
 */
function annowf_css() {
	wp_enqueue_style('anno-workflow', trailingslashit(get_bloginfo('template_directory')).'plugins/workflow/css/workflow.css');
}
add_action('admin_print_styles-post-new.php', 'annowf_css');
add_action('admin_print_styles-post.php', 'annowf_css');

/**
 * Enqueue js for internal comments
 */
function annowf_js() {
	wp_enqueue_script('suggest');
	wp_enqueue_script('anno-workflow', trailingslashit(get_bloginfo('template_directory')).'plugins/workflow/js/workflow.js', array('jquery', 'suggest'));
	
	// Remove Auto-Save feature if a user cannot edit a post. *Note this prevents previewing inputted markup
	if (!anno_user_can('edit_post')) {
		wp_dequeue_script('autosave');
	}
}
add_action('admin_print_scripts-post-new.php', 'annowf_js');
add_action('admin_print_scripts-post.php', 'annowf_js');

/**
 * Article Save action for correcting WP status updating. Fires before insertion into DB
 */ 
function annowf_insert_post_data($post, $postarr) {
	if ($post['post_type'] == 'article' && $post['post_status'] != 'auto-draft') {
		
		global $anno_post_save;
		if (isset($_POST['revert']) && $_POST['revert'] == $anno_post_save['revert']) {
			$post['post_status'] = 'draft';
		}
		else if (isset($_POST['publish'])) {
			switch ($_POST['publish']) {
				case $anno_post_save['publish']:
					$post['post_status'] = 'publish';
					break;
				case $anno_post_save['reject']:
				case $anno_post_save['revisions']:
					$post['post_status'] = 'draft';
					break;
				case $anno_post_save['approve']:
				case $anno_post_save['review']:
					$post['post_status'] = 'pending';
					break;	
				default:
					break;
			}
		}
	}
	return $post;
}
add_filter('wp_insert_post_data', 'annowf_insert_post_data', 10, 2);

/**
 * Article state transistioning. Fires after post has been inserted into the database
 */ 
function annowf_transistion_state($post_id, $post, $post_before) {

	if ($post->post_type == 'article' && $post->post_status != 'auto-draft') {
		global $anno_post_save;
		$current_user = wp_get_current_user();		
		$old_state = get_post_meta($post->ID, '_post_state', true);
		$new_state = $old_state;
		
		if (empty($old_state)) {
			$old_state = 'draft';
		}
		
		if (isset($_POST['revert']) && $_POST['revert'] == $anno_post_save['revert']) {
			$new_state = 'draft';
		}
		else if (isset($_POST['publish'])) {
			switch ($_POST['publish']) {
				// Make sure theres the ability to revert posts if they get a state on accident.
				case $anno_post_save['revert']:
					$new_state = 'draft';
					break;
				case $anno_post_save['approve']:
					// Ensure proper state transitions
					if (in_array($old_state, array('submitted', 'in_review'))) {
						$new_state = 'approved';
					}
					break;
				case $anno_post_save['revisions']:
					if (in_array($old_state, array('submitted', 'in_review'))) {
						$new_state = 'draft';
					}
					break;
				case $anno_post_save['reject']:
					// Publishing staff can still reject even if editor approves
					if (in_array($old_state, array('submitted', 'in_review', 'approved'))) {
						$new_state = 'rejected';
					}
					break;
				case $anno_post_save['publish']:
					if ($old_state == 'approved') {
						$new_state = 'published';
					}
					break;
				case $anno_post_save['review']:	
					$reviewers = anno_get_reviewers($post->ID);
					if (is_array($reviewers) && count($reviewers) && in_array($old_state, array('submitted', 'draft'))) {
						$new_state = 'in_review';
					}
					else if ($old_state == 'draft') {
						$new_state = 'submitted';
					}
					break;
				default:
					break;
			}
		}
		
		$notification_type = $new_state;
		if ($old_state == 'draft' && $new_state == 'in_review') {
			$notification_type = 're_review';
		}
		// Send back for revisions
		if ($new_state == 'draft' && !empty($old_state) && $old_state != 'draft') {
			$round = annowf_get_round($post_id);
			update_post_meta($post_id, '_round', intval($round) + 1);
			$notification_type = 'revisions';
		}
		
		if ($new_state != $old_state) {
			if (empty($new_state)) {
				$new_state = 'draft';
			}
			update_post_meta($post->ID, '_post_state', $new_state);
			do_action('anno_state_change', $new_state, $old_state);
			
			// Save to audit log
			annowf_save_audit_item($post->ID, $current_user->ID, 2, array($old_state, $new_state));
			
			// Send notifications, but not for published to draft state
			if (anno_workflow_enabled('notifications') && !($old_state == 'published' && $new_state == 'draft')) {
				annowf_send_notification($notification_type, $post);
				// Dont send notification if re-review,
				// reviwers get personalized email
				if ($notification_type != 're_review') {
					$reviewer_ids = anno_get_reviewers($post->ID);
					if (!empty($reviewer_ids)) {
						foreach ($reviewer_ids as $reviewer_id) {
							$reviewer = get_user_by('id', $reviewer_id);
							if (!empty($reviewer) && !is_wp_error($reviewer)) {
								annowf_send_notification('reviewer_update', $post, null, array($reviewer->user_email), $reviewer, array('status' => $notification_type));
							}
						}
					}
				}
			}
		}
		
		// Author has changed, add original author as co-author, remove new author from co-authors
		if ($post->post_author !== $post_before->post_author) {
			annowf_add_user_to_post('author', $post_before->post_author, $post->ID);
			annowf_remove_user_from_post('author', $post->post_author, $post->ID);
			if (anno_workflow_enabled('notifications')) {
				annowf_send_notification('primary_author', $post, null, array(anno_user_email($post->post_author)));
			}
		}
	}
}
add_action('post_updated', 'annowf_transistion_state', 10, 3);

/**
 * Store revisions in the audit log.
 */
function anno_put_post_revision($rev_id) {
	$current_user = wp_get_current_user();
	$revision = get_post($rev_id);
	
	if (!empty($revision)) {
		$post = get_post($revision->post_parent);
	}

	if (!empty($post) && $post->post_type == 'article') {
		annowf_save_audit_item($post->ID, $current_user->ID, 1, array($revision->ID));
	}
}
add_action('_wp_put_post_revision', 'anno_put_post_revision');

function annowf_create_user_meta_markup($type) {
?>
<div id="<?php echo esc_attr('anno-invite-'.$type); ?>" class="anno-user-add-wrap hidden">
		<label>
			<span><?php _ex('Email / Username', 'input label', 'anno'); ?></span>
			<input type="text" name="invite_email"/>
		</label>
		<p>
			<?php wp_nonce_field('anno_create_user', '_ajax_nonce-create-user', false); ?>
			<input type="button" class="button anno-create-user" data-type="<?php echo esc_attr($type); ?>" value="<?php _ex('Create User', 'button label', 'anno'); ?>" />
		</p>
		<p>
		 	<?php _ex('or <a href="#" class="'.esc_attr('anno-show-search-'.$type).'">search for an existing user</a>', 'search for user link', 'anno'); ?>
		</p>
</div>
<?php
}

/**
 * Display base markup for user management meta boxes if type param is equal to co_author or reviewer
 * @param string $type The type of user management being displayed
 * @return void
 */ 
function annowf_user_management_meta_box_markup_start($type, $post) {
	if (!in_array($type, array('co_author', 'reviewer'))) {
		return;
	}
	else {
		$type_plural = $type.'s';
	}
?>
	<div id="<?php echo esc_attr($type.'-meta-box'); ?>">
		<div id="<?php echo esc_attr($type.'-add-status'); ?>" class="anno-error hidden"></div>
		<?php annowf_create_user_meta_markup($type); ?>
		<?php if (anno_user_can('manage_'.$type_plural, null, $post->ID)) { ?>
			<div id="<?php echo esc_attr('anno-user-input-'.$type); ?>" class="anno-user-add-wrap">
				<input type="text" id="<?php echo esc_attr($type.'-input'); ?>" class="user-input" name="<?php echo esc_attr($type.'_input'); ?>" /> 
				<input id="<?php echo esc_attr($type.'-add'); ?>" class="user-add button" type="button" value="add" />
				<?php wp_nonce_field('anno_manage_'.$type, '_ajax_nonce-manage-'.$type, false); ?>
				<p>
				 	<?php _ex('or <a href="#" class="'.esc_attr('anno-show-create-'.$type).'">invite a new user</a>', 'invite user link', 'anno'); ?>
				</p>
			</div>			
		<?php } ?>
<?php
}

/**
 * Meta box for reviewer management and display
 */
function annowf_reviewers_meta_box($post) {
	annowf_user_management_meta_box_markup_start('reviewer', $post);
?>
		<ul id="reviewer-list" data-type="reviewer">
<?php
	$reviewers = anno_get_reviewers($post->ID);
	foreach ($reviewers as $user_id) {
		$user = get_userdata($user_id);
		if ($user) {
				annowf_user_li_markup($user, 'reviewer');
		}
		// This user was deleted or no longer exists, remove from post meta
		else if ($user_id !== false) {
			delete_post_meta($post->ID, '_anno_reviewer_'.$user_id);
		}
	}
		
?>
		</ul>
	</div><!-- reviewer-meta-box -->
<?php
}

/**
 * Meta box for author management and display
 */
function annowf_co_authors_meta_box($post) {
	annowf_user_management_meta_box_markup_start('co_author', $post);
?>
		<ul id="co-author-list" data-type="co_author">
<?php
	$co_authors = anno_get_authors($post->ID);
	foreach ($co_authors as $user_id) {
		// Prevent primary author from showing up in this list.
		if ($user_id == $post->post_author) {
			continue;
		}
		$user = get_userdata($user_id);
		if ($user) {
				annowf_user_li_markup($user, 'co_author');
		}
		// This user was deleted or no longer exists, remove from post meta
		else if ($user_id !== false) {
			delete_post_meta($post->ID, '_anno_author_'.$user_id);
		}
	}

?>
		</ul>
	</div><!-- co_author-meta-box -->
<?php
}

/**
 * Markup for user display in meta boxes
 */
function annowf_user_li_markup($user, $type = null) {
	$post_id = anno_get_post_id();
	$extra = '&nbsp;';
	
	// expecting reviewer or co_author
	$type_plural = $type.'s';
	
	if ($type == 'reviewer' && anno_user_can('manage_'.$type_plural, null, $post_id)) {
		// If the type is a user, show what review they left, if any. Loaded in $extra
		global $anno_review_options;
		$round = annowf_get_round($post_id);
		// Review is stored in DB as an integer corresponding to a given review set in the $anno_review_options global for translation purposes.
		$review = annowf_get_user_review($post_id, $user->ID);
		if (isset($anno_review_options[$review])) {
			$extra = $anno_review_options[$review].'&nbsp;';
		}
	}
	$remove = '&nbsp;';
	
	// If a user can manage this type of user, show the remove link.
	if (anno_user_can('manage_'.$type_plural, null, $post_id)) {
		$remove = '&nbsp;&nbsp;<a href="#" class="anno-user-remove">'._x('remove', 'remove user link for admin screens', 'anno').'</a>';
	}
?>
	<li id="<?php echo esc_attr('user-'.$user->ID); ?>">
		<?php echo get_avatar($user->ID, '36'); ?>
		<div class="anno-user-info">
			<a href="<?php echo get_author_posts_url($user->ID); ?>"><?php echo esc_html($user->user_login); ?></a><?php echo $remove; ?><br /><?php echo $extra; ?>
		</div>
	</li>
<?php
}

/**
 * Invite a user as a contributor via POST vars.
 * AJAX handler for adding users.
 */ 
function annowf_invite_user() {
	check_ajax_referer('anno_create_user', '_ajax_nonce-create-user');
	
	// Array for json response
	$data_array = array(
		'code' => '',
		'message' => '',
		'user' => '',
	);
	
	// Only allow entering of email addresses, so a user can't create 'bobisdumb' as the username
	$user_id = anno_invite_contributor($_POST['user_email'], $_POST['user_email']);

	// Error creating user
	if (is_wp_error($user_id)) {
		$data_array['code'] = 'error';
		$data_array['message'] = $user_id->get_error_message();
	}
	else {
		//Pass the username back so it can be added as whatever role (co_author, reviewer) via JS.
		$data_array['code'] = 'success';
		$data_array['message'] = _x('User has been created and added', 'status message for user creation', 'anno');
		$users = get_users(array(
			'include' => array($user_id),
			'fields' => array('user_login'),
		));
		if (!empty($users) && is_array($users)) {
			$data_array['user'] = $users[0]->user_login;
		}
	}
	echo json_encode($data_array);
	die();
	
}
add_action('wp_ajax_anno-invite-user', 'annowf_invite_user');

/**
 * Handles AJAX request for adding a reviewer to a post. As well as transitioning states.
 */ 
function annowf_add_reviewer() {
	$response = annowf_add_user('reviewer');
	if ($response['message'] == 'success') {
		$post_id = absint($_POST['post_id']);
		$post_state = annowf_get_post_state($post_id);
	
		//Send email
		if (anno_workflow_enabled('notifications')) {
			$post = get_post($post_id);
			annowf_send_notification('reviewer_added', $post, '', array($response['user']->user_email), $response['user']);
		}
		
		if ($post_state == 'submitted') {
			update_post_meta($post_id, '_post_state', 'in_review');
			if (anno_workflow_enabled('notifications')) {
				$post = get_post($post_id);
				annowf_send_notification('in_review', $post);
			}
		}
		
		// If the reviewer is being re-added and has already left a review for this round
		$round = annowf_get_round($post_id);

		$review = get_user_meta($response['user']->ID, '_'.$post_id.'_review_'.$round, true);

		if (!empty($review)) {
			$reviewed = get_post_meta($post_id, '_round_'.$round.'_reviewed', true);
			$reviewed[] = $response['user']->ID;
			update_post_meta($post_id, '_round_'.$round.'_reviewed', array_unique($reviewed));
			
			// Used for incrementation of x of x reviewed
			$response['increment'] = 1;
		}
		else {
			$response['increment'] = 0;
		}
		
		//Add to the audit log
		$current_user = wp_get_current_user();
		annowf_save_audit_item($post_id, $current_user->ID, 8, array($response['user']->ID));
		
	}
	unset($response['user']);
	echo json_encode($response);
	die();
}
add_action('wp_ajax_anno-add-reviewer', 'annowf_add_reviewer');

/**
 * Handles AJAX request for adding a co-author to a post.
 */
function annowf_add_co_author() {
	$response = annowf_add_user('co_author');
	if ($response['message'] == 'success') {
		// Used for quick access when filtering posts on a post-author page
		$post_id = absint($_POST['post_id']);
		
		// Send email
		if (anno_workflow_enabled('notifications')) {
			$post = get_post($post_id);
			annowf_send_notification('co_author_added', $post, '', array($response['user']->user_email), $response['user']);
		}

		// Add author to JSON for appending to author dropdown
		$response['author'] = '<option value="'.$response['user']->ID.'">'.$response['user']->user_login.'</option>';
		
		//Add to the audit log
		$current_user = wp_get_current_user();
		annowf_save_audit_item($post_id, $current_user->ID, 6, array($response['user']->ID));
	}
	unset($response['user']);
	echo json_encode($response);
	die();
}
add_action('wp_ajax_anno-add-co-author', 'annowf_add_co_author');

/**
 * AJAX handler for adding a user to a post with a given type
 * 
 * @param string $type Type of user to add to the post (co_author, reviewer)
 * @return array Array of data pertaining to user added and JSON data.
 */
function annowf_add_user($type) {

	check_ajax_referer('anno_manage_'.$type, '_ajax_nonce-manage-'.$type);

	$message = 'error';
	$html = '';
	
	if (isset($_POST['post_id']) && isset($_POST['user'])) {
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
			$post = get_post($_POST['post_id']);
			$co_authors = anno_get_authors(absint($_POST['post_id']));
			$reviewers = anno_get_reviewers(absint($_POST['post_id']));
			
			if ($type == 'reviewer') {
				$type_string = _x('reviewer', 'noun describing user', 'anno');
			}
			else {
				$type_string = _x('co-author', 'noun describing user', 'anno');
			}
			
			if ($post->post_author == $user->ID) {
				$html = sprintf(_x('Cannot add author as a %s', 'Adding user error message for article meta box', 'anno'), $type_string);
			}
			else if (in_array($user->ID, $co_authors) ) {
				$html = sprintf(_x('Cannot add %s as %s. User is already a co-author', 'Adding user error message for article meta box', 'anno'), $user->user_login, $type_string);
			}
			else if (in_array($user->ID, $reviewers)) {
				$html = sprintf(_x('Cannot add %s as %s. User is already a reviewer', 'Adding user error message for article meta box', 'anno'), $user->user_login, $type_string);
			}
			else if (annowf_add_user_to_post($type, $user->ID, absint($_POST['post_id']))) {
				$message = 'success';
				ob_start();
					annowf_user_li_markup($user, $type);
		  			$html = ob_get_contents();
		  		ob_end_clean();
			}
		}
		else {
			$html = sprintf(_x('User \'%s\' not found', 'Adding user error message for article meta box', 'anno'), $user_login);
		}
	}
	return array('message' => $message, 'html' => $html, 'user' => $user);
}

/**
 * Handles AJAX request for remove a reviewer to a post.
 */ 
function annowf_remove_reviewer() {
	$response = annowf_remove_user('reviewer');
	$response['decrement'] = 0;
	if ($response['message'] == 'success') {
		$post_id = absint($_POST['post_id']);
		$user_id = absint($_POST['user_id']);
		
		// Send back to submitted state if we've removed all the reviewers
		if (count(anno_get_reviewers($post_id)) == 0) {
			update_post_meta($post_id, '_post_state', 'submitted');
		}
		
		// Check if the user had already left a review and send back in response to update dom appropriately
		$round = annowf_get_round($post_id);
		$reviews = get_post_meta($post_id, '_round_'.$round.'_reviewed', true);
		if (!is_array($reviews)) {
			$reviews = array();
		}
		if (in_array($user_id, $reviews)) {
			$key = array_search($user_id, $reviews);
			unset($reviews[$key]);
			update_post_meta($post_id, '_round_'.$round.'_reviewed', $reviews);
			$response['decrement'] = 1;
		}
		
		//Add to the audit log
		$current_user = wp_get_current_user();
		annowf_save_audit_item($post_id, $current_user->ID, 9, array($user_id));
		
	}
	echo json_encode($response);
	die();
}
add_action('wp_ajax_anno-remove-reviewer', 'annowf_remove_reviewer');

/**
 * Handles AJAX request for remove a co-author to a post.
 */
function annowf_remove_co_author() {
	$response = annowf_remove_user('co_author');
	if ($response['message'] == 'success') {
		if (isset($_POST['user_id'])) {	
			//Add to the audit log
			$current_user = wp_get_current_user();
			annowf_save_audit_item(absint($_POST['post_id']), $current_user->ID, 7, array(absint($_POST['user_id'])));
		}
	}
	echo json_encode($response);
	die();
}
add_action('wp_ajax_anno-remove-co_author', 'annowf_remove_co_author');

/**
 * AJAX handler for removing users from a post for a given type
 */ 
function annowf_remove_user($type) {
	check_ajax_referer('anno_manage_'.$type, '_ajax_nonce-manage-'.$type);
	$response['message'] = 'error';
	if (isset($_POST['user_id']) && isset($_POST['post_id'])) {
		if (annowf_remove_user_from_post($type, absint($_POST['user_id']), absint($_POST['post_id']))) {
			$response['message'] = 'success';
		}
	}
	return $response;
}

/**
 * Fetch the post state of a given post. If no post state is found
 * it will be determined by the post status
 * 
 * @param int $post_id The id to fetch the post stat for
 * @return string Post state
 */ 
function annowf_get_post_state($post_id) {
	$post_state = get_post_meta($post_id, '_post_state', true);
	if (!$post_state) {
		$post = get_post($post_id);
		if ($post) {
			switch ($post->post_status) {			
				case 'publish':
					$post_state = 'published';
					break;
				case 'draft':
				case 'pending':
				default:
					$post_state = 'draft';
					break;
			}
			update_post_meta($post_id, '_post_state', $post_state);
		}
	}
	return $post_state;
}

/**
 * Return the round associated with a post. Rounds are determined by the number
 * of times they have been in the draft state. (sent for revisions)
 * 
 * @param int $post_id The id to fetch the post stat for
 * @return int Round
 */ 
function annowf_get_round($post_id) {
	$round = get_post_meta($post_id, '_round', true);
	if (!$round) {
		$round = 0;
	}
	return $round;
}

/**
 * Typeahead user search AJAX handler. Based on code in WP Core 3.1.2
 * note this searches the entire users table - on multisite you can add existing users from other blogs to this one.
 */ 
function annowf_user_search() {
	global $wpdb;
	$s = stripslashes($_GET['q']);

	$s = trim( $s );
	if ( strlen( $s ) < 2 )
		die; // require 2 chars for matching

	$results = $wpdb->get_col($wpdb->prepare("
		SELECT user_login
		FROM $wpdb->users
		WHERE user_login LIKE %s",
		'%'.like_escape($s).'%'
	));

	echo join($results, "\n");
	die;
}
add_action('wp_ajax_anno-user-search', 'annowf_user_search');

/**
 * Custom meta Box For Author select.
 */
function annowf_author_meta_box($post) {
	if (!anno_user_can('select_author')) {
		$author = get_userdata($post->post_author);
		echo esc_html($author->user_login);
	}
	else {
		$authors = anno_get_authors($post->ID);	
?>
<label class="screen-reader-text" for="post_author_override"><?php _ex('Author', 'Author meta box dropdown label', 'anno'); ?></label>
<?php
		wp_dropdown_users(array(
			'include' => implode(',', $authors),
			'name' => 'post_author_override',
			'selected' => empty($post->ID) ? $user_ID : $post->post_author,
			'include_selected' => true
		));
	}
}

/**
 * Admin request handler. Handles backend permission enforcement, cloning.
 */ 
function annowf_admin_request_handler() {
	global $anno_post_save, $post;
	
	// Cloning. This must come before the enforcing of capabilities below.
	if (isset($_POST['publish']) && $_POST['publish'] == $anno_post_save['clone']) {
		$post_id = anno_get_post_id();
		if (!anno_user_can('clone_post') || annowf_has_clone($post_id)) {
			wp_die(_x('You are not allowed to clone this post.', 'Cloned article error message', 'anno'));
		}
		$new_id = annowf_clone_post($post_id);
		if (!empty($new_id)) {
			$url = add_query_arg('message', 11, get_edit_post_link($new_id, 'url'));
		} 
		else {
			$url = add_query_arg('message', 12, get_edit_post_link($post_id, 'url'));
		}

		wp_redirect($url);
		die();
	}
	// Enforce Capabilities on the backend. Determine the action, and its relevant annotum capability
	if (isset($_POST['action'])) {
		$wp_action = $_POST['action'];
	}
	else if (isset($_GET['action'])) {
		$wp_action = $_GET['action'];
	}
	if (isset( $_POST['deletepost'])) {
		$wp_action = 'delete';
	}
	if (isset($_POST['post_type'])) {
		$post_type = $_POST['post_type'];
	}
	else if (isset($_GET['post_type'])) {
		$post_type = $_GET['post_type'];
	}
	else if (isset($_GET['revision'])) {
		// We only get revision when restoring a given revision
		$rev_id = $_GET['revision'];
		$rev = get_post($rev_id);
		if (isset($rev->post_parent)) {
			$post = get_post($rev->post_parent);
			if (isset($post->post_type)) {
				$post_type = $post->post_type;
			}
		}
	}
	else {
		$post = get_post(anno_get_post_id());
		if (isset($post->post_type)) {
			$post_type = $post->post_type;
		}
	}
		
	if (!empty($wp_action) && !empty($post_type) && $post_type == 'article') {
		switch ($wp_action) {
			case 'postajaxpost':
			case 'post':
			case 'post-quickpress-publish':
			case 'post-quickpress-save':
				$anno_cap = 'edit_post';
				break;
			// Creation, editing, restoring from revision
			case 'editpost':
			case 'editattachment':
			case 'autosave':
			case 'restore':
			case 'inline-save':
				$anno_cap = 'edit_post';
				break;
			// For Viewing post-edit screen
			case 'edit':
				$anno_cap = 'view_post';
				break;
			case 'trash':
			case 'untrash':
				$anno_cap = 'trash_post';
				break;
			case 'delete':
				$anno_cap = 'admin';
				break;			
			default:
				break;
		}

		if (!empty($anno_cap) && !anno_user_can($anno_cap)) {
			add_filter('user_has_cap', 'annowf_user_has_cap_filter');
		}
	}
}
add_action('admin_init', 'annowf_admin_request_handler', 0);

/**
 * Prevent preview of posts that a user cannot edit
 */
function annowf_prevent_preview($posts, $query) {
	if ($query->is_single && $query->is_preview() && !empty($posts)) {
		$post = $posts[0];
		if ($post->post_type == 'article') {
		 	if (!anno_user_can('view_post', null, $post->ID)) {
				$posts = array();
				$query->is_404 = 1;
				$query->is_single = 0;
				$query->set('error', '404');
				$query->is_singular = 0;
				$query->is_preview = 0;
				$query->post = null;
			}
		}
	}
	return $posts;
}
// Could run similar at pre_get_posts, but this is more reliable at the cost of a slight performance decrease.
add_filter('the_posts', 'annowf_prevent_preview', 10, 2);

/**
 * Filter to remove WP caps from a user for a given action if they do not have the workflow caps
 */
function annowf_user_has_cap_filter($user_caps) {
	// Remove all capabilities so the user cannot perform the current action.
	return false;
}

/**
 * Adjust the edit display to override default WP implementation
 */ 
function annowf_get_sample_permalink_html($return, $id, $new_title, $new_slug) {
	$post = get_post($id);
	if (!empty($post) && $post->post_type == 'article') {
		if (anno_user_can('edit_slug', null, $id)) {
			return $return;
		} 
		else {
			return '';
		}
	}
	return $return;
}
add_filter('get_sample_permalink_html', 'annowf_get_sample_permalink_html', 10, 4);

/**
 * If this article was imported, show a notice to the user, flag is removed on save.
 * 
 * 
 */
function annowf_imported_admin_notices($empty) {
	global $pagenow;
	if ($pagenow == 'post.php') {
		global $post;
		$imported = get_post_meta($post->ID, '_anno_imported', true);
		if ($imported == '1') {
			// Display notice
			echo '<div id="anno-imported-notice" class="error">'.__('You are editing an imported article. Updating this article will likely change its XML and HTML output.').'</div>';
		}

	}
}
//add_action('admin_notices', 'annowf_imported_admin_notices')

/**
 * Remove actions TD content if user cannot edit this post in the Workflow context
 * 
 * @todo Preferably a non-js way to do this. Currently js is the only option
 */ 
function annowf_revision_remove_restore_link() {
	// This is actually the original post, not the revision
	global $post;	
	if ($post->post_type == 'article' && !anno_user_can('edit_post')) {
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
	$('td.action-links').html('');
});
</script>
<?php
	}
}
add_action('admin_head-revision.php', 'annowf_revision_remove_restore_link');

?>