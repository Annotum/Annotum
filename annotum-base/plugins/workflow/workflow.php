<?php
// Used in generating save buttons and proccess state changes
global $anno_post_save;
$anno_post_save = array(
	'approve' => __('Approve', 'anno'),
	'publish' => __('Publish', 'anno'),	
	'reject' => __('Reject', 'anno'),
	'review' => __('Submit For Review', 'anno'),
	'revisions' => __('Request Revisions', 'anno'),
	'clone' => __('Clone', 'anno'),
);

/**
 * Remove the standard publish meta box
 */ 
function anno_workflow_meta_boxes() {
	global $post;
	$post_state = anno_get_post_state($post->ID);
	
	// Remove the WP Publish box
	remove_meta_box('submitdiv', 'article', 'side');

	// Remove discussion box
	remove_meta_box('commentstatusdiv', 'article', 'core');

	// Remove author box
	remove_meta_box('authordiv', 'article', 'normal', 'core');
	
	// Remove taxonomy boxes when a user is unable to save/edit
	if (!anno_user_can('edit_post', null, $post->ID)) {
		remove_meta_box('tagsdiv-article_tag', 'article', 'side');
		remove_meta_box('article_category_select', 'article', 'side');
	}
	
	// Custom author select box. Only displays co-authors in the dropdown.

	add_meta_box('authordiv', __('Author', 'anno'), 'anno_author_meta_box', 'article', 'side', 'low');
	
	// Add the Annotum workflow publish box.
	add_meta_box('submitdiv', __('Status:', 'anno').' '. $post_state, 'anno_status_meta_box', 'article', 'side', 'high');

	// Clone data meta box. Only display if something has been cloned from this post, or it is a clone itself.
	$posts_cloned = get_post_meta($post->ID, '_anno_posts_cloned', true);
	$cloned_from = get_post_meta($post->ID, '_anno_cloned_from', true);
	if (!empty($posts_cloned) || !empty($cloned_from)) {
		add_meta_box('anno-cloned', __('Versions', 'anno'), 'anno_cloned_meta_box', 'article', 'side', 'low');
	}

	if (anno_user_can('view_reviewers')) {
		add_meta_box('anno-reviewers', __('Reviewers', 'anno'), 'anno_reviewers_meta_box', 'article', 'side', 'low');
	}
	add_meta_box('anno-co-authors', __('Co-Authors', 'anno'), 'anno_co_authors_meta_box', 'article', 'side', 'low');
}
add_action('admin_head-post.php', 'anno_workflow_meta_boxes');
add_action('admin_head-post-new.php', 'anno_workflow_meta_boxes');

/**
 * Enqueue css for workflow
 */
function anno_workflow_css() {
	wp_enqueue_style('anno-workflow', trailingslashit(get_bloginfo('stylesheet_directory')).'plugins/workflow/css/workflow.css');
}
add_action('admin_print_styles', 'anno_workflow_css');

/**
 * Enqueue js for internal comments
 */
function anno_workflow_js() {
	wp_enqueue_script('suggest');
	wp_enqueue_script('anno-workflow', trailingslashit(get_bloginfo('stylesheet_directory')).'plugins/workflow/js/workflow.js', array('jquery', 'suggest'));
	
	// Remove Auto-Save feature if a user cannot edit a post. *Note this prevents previewing inputted markup
	if (!anno_user_can('edit_post')) {
		wp_deregister_script('autosave');
	}
}
add_action('admin_print_scripts', 'anno_workflow_js');

/**
 * Article Save action for correcting WP status updating. Fires before insertion into DB
 */ 
function anno_save_article($post, $postarr) {
	
	if ($post['post_type'] == 'article' && $post['post_status'] != 'auto-draft') {
		global $anno_post_save;
		if (isset($_POST['publish'])) {
			switch ($_POST['publish']) {
				case $anno_post_save['publish']:
					$post['post_status'] = 'publish';
					break;
				case $anno_post_save['reject']:
					$post['post_status'] = 'draft';
					break;
				case $anno_post_save['approve']:
				case $anno_post_save['review']:
					$post['post_status'] = 'pending';
					break;	
				case $anno_post_save['revisions']:
					$post['post_status'] = 'draft';
					break;
				default:
					break;
			}
		}
	}
	return $post;
}
add_filter('wp_insert_post_data', 'anno_save_article', 10, 2);

/**
 * Article state transistioning. Fires after post has been inserted into the database
 */ 
function anno_transistion_state($post_id, $post, $post_before) {
	if ($post->post_type == 'article' && $post->post_status != 'auto-draft') {
		global $anno_post_save;
		
		$old_state = get_post_meta($post->ID, '_post_state', true);
		$new_state = $old_state;
		
		if (empty($old_state)) {
			$old_state = 'draft';
		}
		
		if (isset($_POST['publish'])) {
			switch ($_POST['publish']) {
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
					$reviewers = anno_get_post_users($post->ID, '_reviewers');
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
			$round = anno_get_round($post_id);
			update_post_meta($post_id, '_round', intval($round) + 1);
			$notification_type = 'revisions';
		}
	
		if ($new_state != $old_state) {
			if (empty($new_state)) {
				$new_state = 'draft';
			}
			update_post_meta($post->ID, '_post_state', $new_state);
			do_action('anno_state_change', $new_state, $old_state);
			
			// Send notifications
			annowf_send_notification($notification_type, $post);
		}
		
		// Author has changed, add original author as co-author, remove new author from co-authors
		if ($post->post_author !== $post_before->post_author) {
			anno_add_user_to_post('_co_authors', $post_before->post_author, $post->ID);
			anno_remove_user_from_post('_co_authors', $post->post_author, $post->ID);
		}
	}
}
add_action('post_updated', 'anno_transistion_state', 10, 3);

/**
 * Meta box for reviewer management and display
 * @todo Abstract to pass type to meta box markup for co-authors or reviewers
 */
function anno_reviewers_meta_box() {
	global $post;
?>
	<div id="reviewers-meta-box">
		<div id="reviewer-add-error" class="anno-error"></div>
<?php
	$reviewers = anno_get_post_users($post->ID, '_reviewers');
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
				anno_user_li_markup($user, 'reviewer');
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
function anno_co_authors_meta_box() {
	global $post;
?>
	<div id="co-authors-meta-box">
		<div id="co-author-add-error" class="anno-error"></div>
<?php
	$co_authors = anno_get_post_users($post->ID, '_co_authors');
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
		$user = get_userdata($user_id);
		if ($user) {
				anno_user_li_markup($user, 'co_author');
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
function anno_user_li_markup($user, $type = null) {		
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
		$round = anno_get_round($post_id);
		$extra = $anno_review_options[intval(get_user_meta($user->ID, '_'.$post_id.'_review_'.$round, true))].'&nbsp;';
	}
	$remove = '&nbsp;';
	if (anno_user_can('manage_'.$type.'s', null, $post_id)) {
		$remove = '&nbsp;&middot;&nbsp;<a href="#" class="anno-user-remove">remove</a>';
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
function anno_add_reviewer() {
	$user = anno_add_user('reviewer');
	if (!empty($user)) {
		$post_id = intval($_POST['post_id']);
		$post_state = anno_get_post_state($post_id);
		if ($post_state == 'submitted') {
			update_post_meta($post_id, '_post_state', 'in_review');
			//TODO reload?
		}
		//Send email
		$post = get_post($post_id);
		annowf_send_notification('reviewer_added', $post, '', array($user->user_email));
	}
	die();
}
add_action('wp_ajax_anno-add-reviewer', 'anno_add_reviewer');

/**
 * Handles AJAX request for adding a co-author to a post.
 */
function anno_add_co_author() {
	$user = anno_add_user('co_author');
	if (!empty($user)) {
		$post = get_post(intval($_POST['post_id']));
		annowf_send_notification('co_author_added', $post, '', array($user->user_email));
	}
	die();
}
add_action('wp_ajax_anno-add-co-author', 'anno_add_co_author');

/**
 * AJAX handler for adding user to a post with a given type
 * 
 * @param string $type Type of user to add to the post (co_author, reviewer)
 * @return bool True if successfully added, false otherwise
 */
function anno_add_user($type) {
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
			$co_authors = anno_get_post_users($_POST['post_id'], '_co_authors');
			$reviewers = anno_get_post_users($_POST['post_id'], '_reviewers');

			if ($post->post_author == $user->ID) {
				$html = sprintf(__('Cannot add author as a %s', 'anno'), $type);
			}
			else if (in_array($user->ID, $co_authors) ) {
				$html = sprintf(__('Cannot add %s as %s. User is already a co-author', 'anno'), $user->user_login, $type);
			}
			else if (in_array($user->ID, $reviewers)) {
				$html = sprintf(__('Cannot add %s as %s. User is already a reviewer', 'anno'), $user->user_login, $type);
			}
			else if (anno_add_user_to_post($type.'s', $user->ID, intval($_POST['post_id']))) {
				$message = 'success';
				ob_start();
					anno_user_li_markup($user, $type);
		  			$html = ob_get_contents();
		  		ob_end_clean();
			}
		}
		else {
			$html = sprintf(__('User \'%s\' not found', 'anno'), $user_login);
		}
		
	}
	
	echo json_encode(array('message' => $message, 'html' => $html));
	if ($message == 'success') {
		return $user;
	}
	else {
		return false;
	}
}

/**
 * Handles AJAX request for remove a reviewer to a post.
 */ 
function anno_remove_reviewer() {
	// Send back to submitted state if we've removed all the reviewers
	if (anno_remove_user('reviewer')) {
		$post_id = intval($_POST['post_id']);
		$user_id = intval($_POST['user_id']);

		if (count(anno_get_post_users($post_id, '_reviewers')) == 0) {
			update_post_meta($post_id, '_post_state', 'submitted');
		}
		$round = anno_get_round($post_id);
		$reviews = anno_get_post_users($post_id, '_round_'.$round.'_reviewed');
		if (is_array($reviews) && in_array($user_id, $reviews)) {
			$key = array_search($user_id, $reviews);
			unset($reviews[$key]);
			update_post_meta($post_id, '_round_'.$round.'_reviewed', $reviews);
		}
	}
	die();
}
add_action('wp_ajax_anno-remove-reviewer', 'anno_remove_reviewer');

/**
 * Handles AJAX request for remove a co-author to a post.
 */
function anno_remove_co_author() {
	anno_remove_user('co_author');
	die();
}
add_action('wp_ajax_anno-remove-co-author', 'anno_remove_co_author');

/**
 * AJAX handler for removing users from a post for a given type
 */ 
function anno_remove_user($type) {
	check_ajax_referer('anno_manage_'.$type, '_ajax_nonce-manage-'.$type);
	$message = 'error';
	if (isset($_POST['user_id']) && isset($_POST['post_id'])) {
		if (anno_remove_user_from_post($type.'s', $_POST['user_id'], intval($_POST['post_id']))) {
			$message = 'success';
		}
	}
	echo json_encode(array('message' => $message));
	if ($message == 'success') {
		return true;
	}
	else {
		return false;
	}
}

/**
 * Fetch the post state of a given post. If no post state is found
 * it will be determined by the post status
 * 
 * @param int $post_id The id to fetch the post stat for
 * @return string Post state
 */ 
function anno_get_post_state($post_id) {
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
function anno_get_round($post_id) {
	$round = get_post_meta($post_id, '_round', true);
	if (!$round) {
		$round = 0;
	}
	return $round;
}

/**
 * Typeahead user search AJAX handler. Based on code in WP Core 3.1.2
 */ 
function anno_user_search() {
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
add_action('wp_ajax_anno-user-search', 'anno_user_search');

/**
 * Metabox for posts that have been cloned from this post
 */ 
function anno_cloned_meta_box() {
	global $post;
	
	$cloned_from = get_post_meta($post->ID, '_anno_cloned_from', true);
?>
	<dl class="anno-versions">
<?php
	if (!empty($cloned_from)) {
		$cloned_post = get_post($cloned_from);
?>
		<dt><?php echo __('Cloned From', 'anno'); ?></dt>
		<dd><?php echo '<a href="'.esc_url(get_edit_post_link($cloned_from)).'">'.esc_html($cloned_post->post_title).'</a>'; ?></dd>
<?php	
	}
	
	$posts_cloned = get_post_meta($post->ID, '_anno_posts_cloned', true);
	if (!empty($posts_cloned) && is_array($posts_cloned)) {
?>
		<dt><?php echo __('Clones', 'anno'); ?></dt>
<?php
		foreach ($posts_cloned as $cloned_post_id) {
			$cloned_post = get_post($cloned_post_id);
			echo '<dd><a href="'.esc_url(get_edit_post_link($cloned_post_id)).'">'.esc_html($cloned_post->post_title).'</a></dd>';
		}
	}
?>
	</dl>
<?php
}

/**
 * Clones a post, and inserts it into the DB. Maintains all post properties (no post_meta). Also
 * saves the association on both posts.
 *
 * @param int $orig_id The original ID of the post to clone from
 * @return int|bool The newly created (clone) post ID. false if post failed to insert.
 */
function annowf_clone_post($orig_id) {
	global $current_user;
	
	//Get post, convert to Array.
	$post = get_post($orig_id);
	$post = get_object_vars($post);

	// Trick WP into thinking this is a new post. Adjust fields.
	unset($post['ID']);
	$post['post_author'] = $current_user->ID;
	$post['post_status'] = 'draft';
	$post['post_title'] = __('Cloned:', 'anno').' '.$post['post_title'];
	
	$new_id = wp_insert_post($post);
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
function anno_author_meta_box() {
	global $post;
	if (!anno_user_can('manage_co_authors')) {
		$author = get_userdata($post->post_author);
		echo esc_html($author->user_login);
	}
	else {
	
		$authors = anno_get_post_users($post->ID, '_co_authors');
		$authors[] = $post->post_author;	
?>
<label class="screen-reader-text" for="post_author_override"><?php _e('Author'); ?></label>
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
	else if (isset($_GET['action'])) {
		$post_type = $_GET['action'];
	}

	if (!empty($wp_action) && $post_type == 'article') {
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
	
	// Cloning
	if (isset($_POST['publish']) && $_POST['publish'] == 'Clone') {
		if (!anno_user_can('clone_post')) {
			wp_die(__('You are not allowed to clone this post.'));
		}
		$post_id = annowf_get_post_id();
		$new_id = annowf_clone_post($post_id);
		if (!empty($new_id)) {
			$url = add_query_arg( 'message', 11, get_edit_post_link($new_id, 'url'));
		} 
		else {
			$url = add_query_arg( 'message', 12, get_edit_post_link($post_id, 'url'));
		}

		wp_redirect($url);
		die();
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
 * Returns a post ID if it can find it in any of the common places
 * 
 * @return int Post ID loaded on a given page, 0 otherwise.
 */ 
function annowf_get_post_id() {
	global $post;
	$post_id = $post->ID;
	if (empty($post_id)) {
		if (isset($_POST['post'])) {
			$post_id = $_POST['post'];
		}
		else if (isset($_POST['post_ID'])) {
			$post_id = $_POST['post_ID'];
		}
		else if (isset($_GET['post'])) {
			$post_id = $_GET['post'];
		}
		else {
			$post_id = 0;
		}
	}
	return intval($post_id);
}
function anno_get_sample_permalink_html($return, $id, $new_title, $new_slug) {
	
	$post = &get_post($id);

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
}
if (anno_user_can('edit_post')) {
	add_filter('get_sample_permalink_html', 'anno_get_sample_permalink_html', 10, 4);
}

?>