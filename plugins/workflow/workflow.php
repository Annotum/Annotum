<?php

// Used in generating save buttons and proccess state changes
global $anno_post_save;
$anno_post_save = array(
	'approve' => __('Approve', 'anno'),
	'publish' => __('Publish', 'anno'),	
	'reject' => __('Reject', 'anno'),
	'review' => __('Submit For Review', 'anno'),
	'revisions' => __('Request Revisions', 'anno'),
);

/**
 * Remove the standard publish meta box
 */ 
function anno_workflow_meta_boxes() {
	global $post;
	$post_state = anno_get_post_state($post->ID);
	if (empty($post_state)) {
		$post_state = 'draft';
	}
	
	// Remove the WP Publish box
	remove_meta_box('submitdiv', 'article', 'side');

	// Remove taxonomy boxes when a user is unable to save/edit
	if (!anno_user_can('edit_post', null, $post->ID)) {
		remove_meta_box('article_tagdiv', 'article', 'side');
		remove_meta_box('article_category_select', 'article', 'side');
	}
	
	// Add the Annotum workflow publish box.
	add_meta_box('submitdiv', __('Status', 'anno').': '. $post_state, 'anno_status_meta', 'article', 'side', 'high');

	add_meta_box('anno-reviewers', __('Reviewers', 'anno'), 'anno_reviewers_meta_box', 'article', 'side', 'low');
	add_meta_box('anno-co-authors', __('Co-Authors', 'anno'), 'anno_co_authors_meta_box', 'article', 'side', 'low');
}
add_action('admin_head-post.php', 'anno_workflow_meta_boxes');
add_action('admin_head-post-new.php', 'anno_workflow_meta_boxes');

/**
 * Callback for publish meta box
 */ 
function anno_status_meta() {
	anno_status_markup();
}

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
}
add_action('admin_print_scripts', 'anno_workflow_js');


/**
 * Display status meta box markup. Heavily based on code from the WP Core 3.1.2
 */ 
function anno_status_markup() {
		global $post;
		$post_type = 'article';
		$post_type_object = get_post_type_object($post_type);
		$can_publish = current_user_can($post_type_object->cap->publish_posts);
		$post_state = anno_get_post_state($post->ID);
		if (empty($post_state)) {
			$post_state = 'draft';
		}
		
	?>
<div class="submitbox" id="submitpost">
	<input name="post_state" type="hidden" value="<?php esc_attr_e($post_state); ?>" />
	<div id="minor-publishing">
		<div id="minor-publishing-actions">
			<?php 
				if (function_exists('anno_minor_action_'.$post_state.'_markup')) {
					call_user_func('anno_minor_action_'.$post_state.'_markup');
				}
			?>			
		</div> <!-- #minor-publishing-actions -->
	</div> <!-- #minor-publising -->
	<?php if ($post_state == 'approved' && anno_user_can('alter_post_state')) { ?>
		<div id="misc-publishing-actions">
			<div class="misc-pub-section " id="visibility">
			<?php _e('Visibility:'); ?> <span id="post-visibility-display"><?php

			if ( 'private' == $post->post_status ) {
				$post->post_password = '';
				$visibility = 'private';
				$visibility_trans = __('Private');
			} 
			elseif ( !empty( $post->post_password ) ) {
				$visibility = 'password';
				$visibility_trans = __('Password protected');
			} 
			elseif ( $post_type == 'post' && is_sticky( $post->ID ) ) {
				$visibility = 'public';
				$visibility_trans = __('Public, Sticky');
			}
			else {
				$visibility = 'public';
				$visibility_trans = __('Public');
			}

			echo esc_html( $visibility_trans ); ?></span>
			<?php if ( $can_publish ) { ?>
				<a href="#visibility" class="edit-visibility hide-if-no-js"><?php _e('Edit'); ?></a>

				<div id="post-visibility-select" class="hide-if-js">
					<input type="hidden" name="hidden_post_password" id="hidden-post-password" value="<?php echo esc_attr($post->post_password); ?>" />
				<?php if ($post_type == 'post'): ?>
					<input type="checkbox" style="display:none" name="hidden_post_sticky" id="hidden-post-sticky" value="sticky" <?php checked(is_sticky($post->ID)); ?> />
				<?php endif; ?>
					<input type="hidden" name="hidden_post_visibility" id="hidden-post-visibility" value="<?php echo esc_attr( $visibility ); ?>" />
					<input type="radio" name="visibility" id="visibility-radio-public" value="public" <?php checked( $visibility, 'public' ); ?> /> <label for="visibility-radio-public" class="selectit"><?php _e('Public'); ?></label><br />
				<?php if ($post_type == 'post'): ?>
					<span id="sticky-span"><input id="sticky" name="sticky" type="checkbox" value="sticky" <?php checked(is_sticky($post->ID)); ?> tabindex="4" /> <label for="sticky" class="selectit"><?php _e('Stick this post to the front page') ?></label><br /></span>
				<?php endif; ?>
					<input type="radio" name="visibility" id="visibility-radio-password" value="password" <?php checked( $visibility, 'password' ); ?> /> <label 	for="visibility-radio-password" class="selectit"><?php _e('Password protected'); ?></label><br />
					<span id="password-span"><label for="post_password"><?php _e('Password:'); ?></label> <input type="text" name="post_password" id="post_password" value="<?php echo esc_attr($post->post_password); ?>" /><br /></span>
					<input type="radio" name="visibility" id="visibility-radio-private" value="private" <?php checked( $visibility, 'private' ); ?> /> <label for="visibility-radio-private" class="selectit"><?php _e('Private'); ?></label><br />

					<p>
					 <a href="#visibility" class="save-post-visibility hide-if-no-js button"><?php _e('OK'); ?></a>
					 <a href="#visibility" class="cancel-post-visibility hide-if-no-js"><?php _e('Cancel'); ?></a>
					</p>
				</div>
			<?php } ?>
			</div><?php // /misc-pub-section ?>
			<div class="clear"></div>
			<?php
				// translators: Publish box date formt, see http://php.net/date
				$datef = __( 'M j, Y @ G:i' );
				if ( 0 != $post->ID ) {
					if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
						$stamp = __('Scheduled for: <b>%1$s</b>');
					} else if ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
						$stamp = __('Published on: <b>%1$s</b>');
					} else if ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
						$stamp = __('Publish <b>immediately</b>');
					} else if ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
						$stamp = __('Schedule for: <b>%1$s</b>');
					} else { // draft, 1 or more saves, date specified
						$stamp = __('Publish on: <b>%1$s</b>');
					}
					$date = date_i18n( $datef, strtotime( $post->post_date ) );
				} else { // draft (no saves, and thus no date specified)
					$stamp = __('Publish <b>immediately</b>');
					$date = date_i18n( $datef, strtotime( current_time('mysql') ) );
				}

				if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
				<div class="misc-pub-section curtime misc-pub-section-last">
					<span id="timestamp">
					<?php printf($stamp, $date); ?></span>
					<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" tabindex='4'><?php _e('Edit') ?></a>
					<div id="timestampdiv" class="hide-if-js"><?php touch_time(($action == 'edit'),1,4); ?></div>
				</div><?php // /misc-pub-section ?>
				<?php endif; ?>

			<?php do_action('post_submitbox_misc_actions'); ?>

		</div>
	<?php } ?>
	
	<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( ('auto-draft' == $post->post_status ) ? 'draft' : $post->post_status); ?>" />
	<div id="major-publishing-actions">
		<?php
			do_action('post_submitbox_start'); 
			if (function_exists('anno_major_action_'.$post_state.'_markup')) {
				call_user_func('anno_major_action_'.$post_state.'_markup');
			}
		?>

	</div> <!-- #major-publishing-actions -->
</div> <!-- .submitbox -->
<?php 
}

/**
 * Draft state markup for minor actions.
 */
function anno_minor_action_draft_markup() {
	if (anno_user_can('edit_post')) {
		anno_minor_action_save_markup();
	}
	anno_minor_action_preview_markup();
}

/**
 * Draft state markup for major actions.
 */
function anno_major_action_draft_markup() {
	global $anno_post_save;
	if (anno_user_can('trash_post')) {
			$wrap_class = '';
?>
		<div id="delete-action">
			<a class="submitdelete deletion" href="<?php echo get_delete_post_link($post->ID); ?>"><?php _e('Move To Trash', 'anno'); ?></a>
		</div>
<?php
	}
	else {
		$wrap_class = ' class="center-wrap"';
	}
?>
		<div id="publishing-action"<?php echo $wrap_class; ?>>
			<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" id="ajax-loading" alt="" />
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Submit for Review') ?>" />

			<?php submit_button($anno_post_save['review'], 'primary', 'publish', false, array( 'tabindex' => '5', 'accesskey' => 'p' )); ?>
		</div>
		<div class="clear"></div>
<?php 
}

/**
 * Submitted state markup for minor actions.
 */
function anno_minor_action_submitted_markup() {
?>
		<p class="status-text">
			<?php _e('Submitted - Waiting For Review'); ?>
		</p>
<?php
}

/**
 * Submitted state markup for major actions.
 */
function anno_major_action_submitted_markup() {
	anno_major_action_preview_markup();
}

/**
 * In Review state markup for minor actions.
 */
function anno_minor_action_in_review_markup() {
	// No need to check for edit_post for save button, same set of users can edit and alter state here.
	if (anno_user_can('alter_post_state')) {
		global $post;
		$post_round = anno_get_round($post->ID);
		anno_minor_action_save_markup();
		anno_minor_action_preview_markup();
		if ($post_round !== false) {
			// Return array of user ids who have given reviews for this round
			$round_reviewed = count(anno_get_post_users($post->ID, '_round_'.$post_round.'_reviewed'));		
			$reviewers = count(anno_get_reviewers($post->ID));
?>
			<p class="status-text">
<?php
			printf(__('%d of %d Reviews Complete', 'anno'), $round_reviewed, $reviewers);
		}
	}
	else {
?>
			<p class="status-text">
<?php
 			_e('Submitted - In Review', 'anno'); 
	}	
?>
		</p>
<?php
}

/**
 * In Review state markup for major actions.
 */
function anno_major_action_in_review_markup() {	
	if (anno_user_can('alter_post_state')) {
		global $anno_post_save;
?>
	<div id="publishing-action-approve" class="center-wrap">
		<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Approve', 'anno') ?>" />	
		<?php submit_button($anno_post_save['approve'], 'primary', 'publish', false, array( 'tabindex' => '5')); ?>
		<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" alt="" />
	</div>
	<div id="publishing-action-revision" class="center-wrap">
		<?php submit_button($anno_post_save['revisions'], 'primary', 'publish', false, array( 'tabindex' => '6' )); ?>
		<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" />
	</div>
	<div id="publishing-action-reject" class="center-wrap">
		<?php submit_button($anno_post_save['reject'], 'primary', 'publish', false, array( 'tabindex' => '7' )); ?>
		<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" />
	</div>
	
<?php
	}
	else {
		anno_major_action_preview_markup();
	}
}

/**
 * Approved state markup for minor actions.
 */
function anno_minor_action_approved_markup() {
	// We don't have to check for edit, as alter_post_state is the same set of users in this case
	if (anno_user_can('alter_post_state')) {
		anno_minor_action_save_markup();
		anno_minor_action_preview_markup();
	}
	else {
?>
	<p class="status-text">
		<?php _e('Article Approved', 'anno'); ?>
	</p>
<?php

	}
}

/**
 * Approved state markup for major actions.
 */
function anno_major_action_approved_markup() {
	if (anno_user_can('alter_post_state')) {
		global $anno_post_save;
?>
	<div id="publishing-action" class="center-wrap">
		<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" id="ajax-loading" alt="" />	
		<?php submit_button($anno_post_save['publish'], 'primary', 'publish', false, array( 'tabindex' => '5')); ?>
	</div>
	<div class="clear"></div>
<?php
		
	}
	else {
		anno_major_action_preview_markup();
	}
}

/**
 * Rejected state markup for minor actions.
 */
function anno_minor_action_rejected_markup() {
?>
	<p class="status-text">
		<?php _e('Article Rejected', 'anno'); ?>
	</p>
<?php
}

/**
 * Rejected state markup for major actions.
 */
function anno_major_action_rejected_markup() {
	anno_major_action_clone_markup();
}

/**
 * Published state markup for minor actions.
 */
function anno_minor_action_published_markup() {
	// No state alteration should occur in published
	if (anno_user_can('edit_post')) {
		anno_minor_action_save_markup();
		anno_minor_action_preview_markup();
	}
	else {
?>
	<p class="status-text">
		<?php _e('Article Published', 'anno'); ?>
	</p>
<?php
	}
}

/**
 * Published state markup for major actions.
 */
function anno_major_action_published_markup() {
	anno_major_action_clone_markup();
}

/**
 * Preview button markup used in many minor actions for various states
 */
function anno_minor_action_preview_markup() {
	global $post;
?>
	<div id="preview-action">
<?php
	if ( 'publish' == $post->post_status ) {
		$preview_link = esc_url( get_permalink( $post->ID ) );
		$preview_button = __( 'Preview Changes' );
	} else {
		$preview_link = get_permalink( $post->ID );
		if ( is_ssl() )
			$preview_link = str_replace( 'http://', 'https://', $preview_link );
		$preview_link = esc_url( apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $preview_link ) ) );
		$preview_button = __( 'Preview' );
	}
?>
		<a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview" id="post-preview" tabindex="4"><?php echo $preview_button; ?></a>
		<input type="hidden" name="wp-preview" id="wp-preview" value="" />
	</div> <!-- #preview-action -->
	<div class="clear"></div>
<?php 
}

/**
 * Preview button markup used in many major actions for various states
 */ 
function anno_major_action_preview_markup() {
?>
	<div id="preview-action" class="major center-wrap">
<?php
	global $post;
	if ( 'publish' == $post->post_status ) {
		$preview_link = esc_url( get_permalink( $post->ID ) );
		$preview_button = __( 'Preview Changes' );
	} 
	else {
		$preview_link = get_permalink( $post->ID );
		if ( is_ssl() )
			$preview_link = str_replace( 'http://', 'https://', $preview_link );
		$preview_link = esc_url( apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $preview_link ) ) );
		$preview_button = __('Preview', 'anno');
	}
?>
		<a class="button-primary" href="<?php echo $preview_link; ?>" target="wp-preview" id="post-preview" tabindex="4"><?php echo $preview_button; ?></a>
		<input type="hidden" name="wp-preview" id="wp-preview" value="" />
	</div> <!-- #preview-action -->
	<div class="clear"></div>
<?php 	
}

/**
 * Save button markup used in many minor actions for various states
 */
function anno_minor_action_save_markup() {
	// Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key
?>
		<div style="display:none;">
			<?php submit_button( __( 'Save' ), 'button', 'save' ); ?>
		</div>
		<div id="save-action">			
			<input <?php if ( 'private' == $post->post_status ) { ?>style="display:none"<?php } ?> type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save', 'anno'); ?>" tabindex="4" class="button button-highlighted" />
			<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" id="draft-ajax-loading" alt="" />
		</div>
<?php 
}

/**
 * Clone button markup used in many major actions for various states
 */
function anno_major_action_clone_markup() {
?>
	<div id="preview-action" class="major center-wrap">
		<a class="button-primary" href="#" id="post-clone" tabindex="5"><?php _e('Clone', 'anno') ?></a>
	</div>
	<div class="clear"></div>
		
<?php
}


/**
 * Article Save action for correcting WP status updating. Fires before insertion into DB
 */ 
function anno_save_article($post) {
	if ($post['post_type'] == 'article' && $post['post_status'] != 'auto-draft') {
		global $anno_post_save;
		if (isset($_POST['publish'])) {
			switch ($_POST['publish']) {
				case $anno_post_save['publish']:
					$post['post_status'] = 'publish';
					break;
				case $anno_post_save['reject']:
					//TODO, trash??
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
function anno_transistion_state($post_id, $post) {
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
					$reviewers = anno_get_reviewers($post_id);
					if (is_array($reviewers) && count($reviewers) && in_array($old_state, array('submitted', 'draft'))) {
						$new_state = 'in_review';
					}
					else if ($old_state == 'draft') {
						$new_state = 'submitted';
					}
					//TODO Update draft round
					break;
				default:
					break;
			}
		}
		
	
		if ($new_state != $old_state) {
			if (empty($new_state)) {
				$new_state = 'draft';
			}
			update_post_meta($post->ID, '_post_state', $new_state);
			do_action('anno_state_change', $new_state, $old_state);
			//TODO hook in email action to anno_state_change
		}
	}
}
add_action('save_post', 'anno_transistion_state', 10, 2);

// Filters needed for modification of message displayed
//apply_filters( 'post_updated_messages', $messages ); $messages['post']
//apply_filters( 'redirect_post_location', $location, $post_id ) );

//TODO Abstract the two meta below?
/**
 * Meta box for reviewer management and display
 */
function anno_reviewers_meta_box() {
	global $post;
?>
	<div id="reviewers-meta-box">
<?php
	$reviewers = anno_get_reviewers();
	if (anno_user_can('manage_reviewers', null, $post->ID)) {
?>
		<div class="user-input-wrap">
			<input type="text" id="reviewer-input" class="user-input" name="reviewer_input" /> 
			<input id="reviewer-add" class="user-add button" type="button" value="add" />
		</div>
<?php
	}
?>
		<ul id="reviewer-list">
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
<?php
	$co_authors = anno_get_co_authors();
	if (anno_user_can('manage_co_authors', null, $post->ID)) {
?>
		<div class="user-input-wrap">
			<input type="text" id="co-author-input" class="user-input" name="co_author_input" /> 
			<input id="co-author-add" class="user-add button" type="button" value="add" />
		</div>
<?php
	}
?>
		<ul id="co-author-list">
<?php
	foreach ($co_authors as $user_id) {
		$user = get_userdata($user_id);
		if ($user) {
				anno_user_li_markup($user, 'co-author');
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
	$extra = '';
	if ($type == 'reviewer') {
		global $post, $anno_review_options;
		$round = anno_get_round($post->ID);
		$extra = ' '.$anno_review_options[intval(get_user_meta($user->ID, '_'.$post->ID.'_review_'.$round, true))];
	}
?>
	<li>
		<?php echo get_avatar($user->ID, '24'); ?>
		<a href="<?php echo get_author_posts_url($user->ID); ?>"><?php echo esc_html($user->user_login) ?></a><?php echo $extra; ?>
	</li>
<?php
}

/**
 * Handles AJAX request for adding a reviewer to a post. As well as transitioning states.
 */ 
function anno_add_reviewer() {
	if (anno_add_user('reviewer')) {
		$post_id = $_POST['post_id'];
		$post_state = anno_get_post_state($post_id);
		if ($post_state == 'submitted') {
			update_post_meta($post_id, '_post_state', 'in_review');
			//TODO reload?
		}
	}
	die();
}
add_action('wp_ajax_anno-add-reviewer', 'anno_add_reviewer');

/**
 * Handles AJAX request for adding a co-author to a post.
 */
function anno_add_co_author() {
	anno_add_user('co_author');
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
//TODO nonce
	if (isset($_POST['user']) && isset($_POST['post_id'])) {
		$user = get_userdatabylogin($_POST['user']);
		if (!empty($user)) {
			if (function_exists('anno_add_'.$type.'_to_post')) {
				anno_user_li_markup($user, $type);
				return call_user_func_array('anno_add_'.$type.'_to_post', array($user->ID, intval($_POST['post_id'])));
			}
		}
	}
	return false;
}

/******* Meta Retrieval *******/
function anno_get_post_state($post_id) {
	$post_state = get_post_meta($post_id, '_post_state', true);
	if (!$post_state) {
		// TODO determine post state from post_status
		$post_state = 'draft';
	}
	return $post_state;
}

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

?>