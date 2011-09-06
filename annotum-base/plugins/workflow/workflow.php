<?php

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

	// Remove discussion box
	remove_meta_box('commentstatusdiv', 'article', 'normal');

	// Remove author box
	remove_meta_box('authordiv', 'article', 'normal');
		
	// Remove slug div
	remove_meta_box('slugdiv', 'article', 'normal');
	
	if (!anno_user_can('manage_public_comments', null, $post->ID)) {
		remove_meta_box('commentsdiv', 'article', 'normal');
	}
	
	// Remove Revisions box in favor of audit log box
	remove_meta_box('revisionsdiv', 'article', 'normal');
	if (anno_user_can('view_audit')) {
		add_meta_box('audit_log', _x('Audit Log', 'Meta box title', 'anno'), 'annowf_audit_log', 'article', 'normal', 'low');
	}
	
	// Remove taxonomy/edit boxes when a user is unable to save/edit
	if (!anno_user_can('edit_post', null, $post->ID)) {
		remove_meta_box('tagsdiv-article_tag', 'article', 'side');
		remove_meta_box('article_category_select', 'article', 'side');
		remove_meta_box('postimagediv', 'article', 'side', 'low');
	}

	
	// Custom author select box. Only displays co-authors in the dropdown.

	add_meta_box('authordiv', _x('Author', 'Meta box title', 'anno'), 'annowf_author_meta_box', 'article', 'side', 'low');
	
	// Add the Annotum workflow publish box.
	global $annowf_states;
	add_meta_box('submitdiv', _x('Status:', 'Meta box title', 'anno').' '. $annowf_states[$post_state], 'annowf_status_meta_box', 'article', 'side', 'high');

	// Clone data meta box. Only display if something has been cloned from this post, or it is a clone itself.
	$posts_cloned = get_post_meta($post->ID, '_anno_posts_cloned', true);
	$cloned_from = get_post_meta($post->ID, '_anno_cloned_from', true);
	if (!empty($posts_cloned) || !empty($cloned_from)) {
		add_meta_box('anno-cloned', _x('Versions', 'Meta box title', 'anno'), 'annowf_cloned_meta_box', 'article', 'side', 'low');
	}

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
		wp_deregister_script('autosave');
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
			
			// Send notifications
			if (anno_workflow_enabled('notifications')) {
				annowf_send_notification($notification_type, $post);
			}
		}
		
		// Author has changed, add original author as co-author, remove new author from co-authors
		if ($post->post_author !== $post_before->post_author) {
			annowf_add_user_to_post('author', $post_before->post_author, $post->ID);
			annowf_remove_user_from_post('author', $post->post_author, $post->ID);
			if (anno_workflow_enabled('notifications')) {
				annowf_send_notification('primary_author', $post, null, array(annowf_user_email($post->post_author)));
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
	$post = get_post($post_parent);
	if (!empty($post) && $post->post_type == 'article') {
		annowf_save_audit_item($post->ID, $current_user->ID, 1, array($revision->ID));
	}
}
add_action('_wp_put_post_revision', 'anno_put_post_revision');
/**
 * Meta box for reviewer management and display
 * @todo Abstract to pass type to meta box markup for co-authors or reviewers
 */
function annowf_reviewers_meta_box($post) {
?>
	<div id="reviewers-meta-box">
		<div id="reviewer-add-error" class="anno-error"></div>
<?php
	$reviewers = anno_get_reviewers($post->ID);
	if (anno_user_can('manage_reviewers', null, $post->ID)) {
?>
		<div class="user-input-wrap">
			<input type="text" id="reviewer-input" class="user-input" name="reviewer_input" /> 
			<input id="reviewer-add" class="user-add button" type="button" value="add" />
			<?php wp_nonce_field('anno_manage_reviewer', '_ajax_nonce-manage-reviewer', false); ?>
		</div>
<?php
	}
?>
		<ul id="reviewer-list" data-type="reviewer">
<?php
	foreach ($reviewers as $user_id) {
		$user = get_userdata($user_id);
		if ($user) {
				annowf_user_li_markup($user, 'reviewer');
			}
		}
?>
		</ul>
	</div>
<?php
}

/**
 * Meta box for author management and display
 */
function annowf_co_authors_meta_box($post) {
?>
	<div id="co-authors-meta-box">
		<div id="co-author-add-error" class="anno-error"></div>
<?php
	$co_authors = anno_get_authors($post->ID);
	if (anno_user_can('manage_co_authors', null, $post->ID)) {
?>
		<div class="user-input-wrap">
			<input type="text" id="co-author-input" class="user-input" name="co_author_input" /> 
			<input id="co-author-add" class="user-add button" type="button" value="add" />
			<?php wp_nonce_field('anno_manage_co_author', '_ajax_nonce-manage-co_author', false); ?>
		</div>
<?php 	
	}
?>
		<ul id="co-author-list" data-type="co_author">
<?php
	foreach ($co_authors as $user_id) {
		// Prevent primary author from showing up in this list.
		if ($user_id == $post->post_author) {
			continue;
		}
		$user = get_userdata($user_id);
		if ($user) {
				annowf_user_li_markup($user, 'co_author');
			}
		}
?>
		</ul>
	</div>
<?php
}

/**
 * Markup for user display in meta boxes
 */
function annowf_user_li_markup($user, $type = null) {		
	$extra = '&nbsp;';
	global $post;
	if (empty($post)) {
		$post_id = $_POST['post_id'];
	}
	else {
		$post_id = $post->ID;
	}
	
	if ($type == 'reviewer' && anno_user_can('manage_'.$type.'s', null, $post_id)) {
		global $anno_review_options;
		$round = annowf_get_round($post_id);
		$extra = $anno_review_options[intval(get_user_meta($user->ID, '_'.$post_id.'_review_'.$round, true))].'&nbsp;';
	}
	$remove = '&nbsp;';
	if (anno_user_can('manage_'.$type.'s', null, $post_id)) {
		$remove = '&nbsp;&nbsp;<a href="#" class="anno-user-remove">remove</a>';
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
			//TODO reload?
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
		$user = get_userdatabylogin($user_login);

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
 * Metabox for posts that have been cloned from this post
 */ 
function annowf_cloned_meta_box($post) {
//TODO check for trash/deleted	
	$cloned_from = get_post_meta($post->ID, '_anno_cloned_from', true);
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
	
	$posts_cloned = get_post_meta($post->ID, '_anno_posts_cloned', true);
	if (!empty($posts_cloned) && is_array($posts_cloned)) {
?>
		<dt><?php echo _x('Clones', 'Cloned meta box text', 'anno'); ?></dt>
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
	
	// Form the new cloned post
	$new_post = array(
		'post_author' => $current_user->ID,
		'post_status' => 'draft',
		'post_title' => sprintf(_x('Cloned: %s', 'Cloned article title prepend', 'anno'), $post->post_title),
		'post_content' => $post->post_content,
		'post_excerpt' => $post->post_excerpt,
		'post_type' => $post->post_type,
		'post_parent' => $post->post_parent,
	);
	
	$new_id = wp_insert_post($new_post);

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
	return $new_id;
}

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
	global $anno_post_save;
	
	// Cloning. This must come before the enforcing of capabilities below.
	if (isset($_POST['publish']) && $_POST['publish'] == $anno_post_save['clone']) {
		if (!anno_user_can('clone_post')) {
			wp_die(_x('You are not allowed to clone this post.', 'Cloned article error message', 'anno'));
		}
		$post_id = anno_get_post_id();
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
	
	// Enforce Capabilities on the backend.
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
	else if (isset($_GET['post'])) {
		$post = get_post(absint($_GET['post']));
		$post_type = $post->post_type;
	}
	if (!empty($wp_action) && !empty($post_type) && $post_type == 'article') {
		switch ($wp_action) {
			case 'postajaxpost':
			case 'post':
			case 'post-quickpress-publish':
			case 'post-quickpress-save':
				$anno_cap = 'edit_post';
				break;
			// Creation and editing
			case 'editpost':
			case 'editattachment':
			case 'autosave':
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
// TODO possibly re-implement
/*
	if ( 'publish' == $post->post_status ) {
			$ptype = get_post_type_object($post->post_type);
			$view_post = $ptype->labels->view_item;
			$title = __('Click to edit this part of the permalink');
		} else {
			$title = __('Temporary permalink. Click to edit this part.');
		}

	list($permalink, $post_name) = get_sample_permalink($post->ID, $new_title, $new_slug);

	if ( function_exists('mb_strlen') ) {
		if ( mb_strlen($post_name) > 30 ) {
			$post_name_abridged = mb_substr($post_name, 0, 14). '&hellip;' . mb_substr($post_name, -14);
		} else {
			$post_name_abridged = $post_name;
		}
	} else {
		if ( strlen($post_name) > 30 ) {
			$post_name_abridged = substr($post_name, 0, 14). '&hellip;' . substr($post_name, -14);
		} else {
			$post_name_abridged = $post_name;
		}
	}
	
	$post_name_html = $post_name_abridged;
	$display_link = str_replace(array('%pagename%','%postname%'), $post_name_html, $permalink);
	$view_link = str_replace(array('%pagename%','%postname%'), $post_name, $permalink);
	$return =  '<strong>' . __('Permalink:') . "</strong>\n";
	$return .= '<span id="sample-permalink">' . $display_link . "</span>\n";
	$return .= '&lrm;'; // Fix bi-directional text display defect in RTL languages.
	if ( isset($view_post) )
		$return .= "<span id='view-post-btn'><a href='$view_link' class='button' target='_blank'>$view_post</a></span>\n";

	return $return;
*/
}
add_filter('get_sample_permalink_html', 'annowf_get_sample_permalink_html', 10, 4);

?>