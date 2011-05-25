<?php
//TODO Restructure!

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
	
	// Remove the WP Publish box
	remove_meta_box('submitdiv', 'article', 'side');

	// Remove discussion box
	remove_meta_box('commentstatusdiv', 'article', 'core');

	// Remove author box
	remove_meta_box('authordiv', 'article', 'normal', 'core');
	
	// Remove taxonomy boxes when a user is unable to save/edit
	if (!anno_user_can('edit_post', null, $post->ID)) {
		remove_meta_box('article_tagdiv', 'article', 'side');
		remove_meta_box('article_category_select', 'article', 'side');
	}
	
	if (anno_user_can('manage_co_authors', null, $post->ID)) {
		add_meta_box('authordiv', __('Author', 'anno'), 'anno_author_metabox', 'article', 'side', 'low');
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
	
	// Remove Auto-Save feature if a user cannot edit a post. *Note this prevents previewing inputted markup
	if (!anno_user_can('edit_post')) {
		wp_deregister_script('autosave');
	}
}
add_action('admin_print_scripts', 'anno_workflow_js');


/**
 * Display status meta box markup. Heavily based on code from the WP Core 3.1.2
 */ 
function anno_status_markup() {
		global $post;		
		$post_state = anno_get_post_state($post->ID);
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
	<?php 
		if ($post_state == 'approved' && anno_user_can('alter_post_state')) { 
			anno_misc_action_approved_markup();
 		} 
	?>
	
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
			$reviewers = count(anno_get_post_users($post->ID, '_reviewers'));
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
 * Approved state markup for misc actions
 */
function anno_misc_action_approved_markup() {
	global $post;
	$post_type = 'article';
	$post_type_object = get_post_type_object($post_type);
	$can_publish = current_user_can($post_type_object->cap->publish_posts);
?>
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
<?php
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
function anno_save_article($post, $postarr) {
	if (isset($po))
	
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
		
		// Send back for revisions
		if ($new_state == 'draft' && !empty($old_state) && $old_state != 'draft') {
			$round = anno_get_round($post_id);
			update_post_meta($post_id, '_round', intval($round) + 1);
		}
	
		if ($new_state != $old_state) {
			if (empty($new_state)) {
				$new_state = 'draft';
			}
			update_post_meta($post->ID, '_post_state', $new_state);
			do_action('anno_state_change', $new_state, $old_state);
			//TODO hook in email action to anno_state_change
		}
		
		// Author has changed, add original author as co-author, remove new author from co-authors
		if ($post->post_author !== $post_before->post_author) {
			anno_add_user_to_post('_co_authors', $post_before->post_author, $post->ID);
			anno_remove_user_from_post('_co_authors', $post->post_author, $post->ID);
		}
	}
}
add_action('post_updated', 'anno_transistion_state', 10, 3);

//TODO move out of workflow
// Look into apply_filters( 'redirect_post_location', $location, $post_id ) );
function anno_post_updated_messages($messages) {
	global $post;
	// Based on message code in WP Core 3.2
	$messages['article'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf(__('Article updated. <a href="%s">View article</a>', 'anno'), esc_url(get_permalink($post->ID))),
		2 => __('Custom field updated.', 'anno'),
		3 => __('Custom field deleted.', 'anno'),
		4 => __('Article updated.', 'anno'),
	 	5 => isset($_GET['revision']) ? sprintf( __('Article restored to revision from %s', 'anno'), wp_post_revision_title((int) $_GET['revision'], false )) : false,
		6 => sprintf( __('Article published. <a href="%s">View article</a>', 'anno'), esc_url(get_permalink($post->ID))),
		7 => __('Article saved.', 'anno'),
		8 => sprintf( __('Article submitted. <a target="_blank" href="%s">Preview article</a>'), esc_url(add_query_arg('preview', 'true', get_permalink($post->ID)))),
		9 => sprintf( __('Article scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview article</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date )), esc_url( get_permalink($post->ID))),
		10 => sprintf( __('Article draft updated. <a target="_blank" href="%s">Preview article</a>', 'article'), esc_url( add_query_arg('preview', 'true', get_permalink($post->ID)))),
	);

	return $messages;
}
add_filter('post_updated_messages', 'anno_post_updated_messages');

//TODO Abstract the two meta below?
/**
 * Meta box for reviewer management and display
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
	check_ajax_referer('anno_manage_'.$type, '_ajax_nonce-manage-'.$type);
	$message = 'error';
	$html = '';
	if (isset($_POST['user']) && isset($_POST['post_id'])) {
		$user = get_userdatabylogin($_POST['user']);
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
			//TODO Check on/for email
			$html = sprintf(__('User \'%s\' not found', 'anno'), $_POST['user']);
		}
	}
	echo json_encode(array('message' => $message, 'html' => $html));
	if ($message == 'success') {
		return true;
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



/********* Cloning/Revisions *********/


/**
 * Metabox for posts that have been cloned from this post
 */ 
//TODO style
function anno_cloned_metabox() {
	global $post;
	$posts_cloned = get_post_meta($post->ID, '_anno_posts_cloned', true);
	if (!empty($posts_cloned) && is_array($posts_cloned)) {
?>
	<div>
		<ul id="anno-posts-cloned">
<?php
		foreach ($posts_cloned as $cloned_post_id) {
			$cloned_post = get_post($cloned_post_id);
			echo '<li>'.esc_html($cloned_post->post_title).'</li>';
		}
?>
		</ul>
	</div>
<?php
	}
	
	$cloned_from = get_post_meta($post->ID< '_anno_cloned_from', true);
	if (!empty($cloned_from)) {
		$cloned_post = get_post($cloned_from)
?>
	<div>
		<ul id="anno-cloned-from">
			<?php echo '<li>'.esc_html($cloned_post->post_title).'</li>'; ?>
		</ul>
	</div>
	
<?php	
	}
}

/**
 * Clones a post, and inserts it into the DB. Maintains all post properties (no post_meta). Also
 * saves the association on both posts.
 *
 * @param int $orig_id The original ID of the post to clone from
 * @return int|bool The newly created (clone) post ID. false if post failed to insert.
 */
function anno_clone_post($orig_id) {
	global $current_user;
	
	//Get post, convert to Array.
	$post = get_post($orig_id);
	$post = get_object_vars($post);

	unset($post['ID']);
	$post['post_author'] = $current_user->ID;
	
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
 * Meta Box For Author select.
 */
function anno_author_metabox() {
	global $post;
	$authors = anno_get_post_users($post->ID, '_co_authors');
	$authors[] = $post->post_author;	
?>s
<label class="screen-reader-text" for="post_author_override"><?php _e('Author'); ?></label>
<?php
	wp_dropdown_users(array(
		'include' => implode(',', $authors),
		'name' => 'post_author_override',
		'selected' => empty($post->ID) ? $user_ID : $post->post_author,
		'include_selected' => true
	));
}

/**
 * Add Workflow settings page
 */ 
function anno_add_submenu_page() {
	add_submenu_page(
		'themes.php', 
		__('Annotum Workflow Settings', 'anno'), 
		__('Workflow Settings', 'anno'), 
		'manage_options',
		'anno-workflow-settings',
		'anno_settings_page' 
	);
}
add_action('admin_menu', 'anno_add_submenu_page');

/**
 * Add Workflow settings page markup
 */
function anno_settings_page() {
?>
<div class="wrap">
	<h2><?php _e('Annotum Workflow Settings', 'anno'); ?></h2>
	<form action="<?php admin_url('/'); ?>" method="post">
		<p>
			<label for="anno-workflow">Enbable Workflow</label>
			<input id="anno-workflow" type="checkbox" value="1" name="anno_workflow"<?php checked(get_option('annowf_setting'), 1); ?> />
		</p>
		<p class="submit">
			<?php wp_nonce_field('annowf_settings', '_wpnonce', true, true); ?>
			<input type="hidden" name="anno_action" value="annowf_update_settings" />
			<input type="submit" name="submit_button" class="button-primary" value="<?php _e('Save Changes', 'anno'); ?>" />
		</p>
	</form>
</div>
<?php
}

function annowf_admin_request_handler() {
	if (isset($_POST['anno_action'])) {
		switch ($_POST['anno_action']) {
			case 'annowf_update_settings':
				if (!check_admin_referer('annowf_settings')) {
					die();
				}
				if (isset($_POST['anno_workflow']) && !empty($_POST['anno_workflow'])) {
					update_option('annowf_setting', 1);
				}
				else {
					update_option('annowf_setting', 0);
				}
				wp_redirect(admin_url('/themes.php?page=anno-workflow-settings&updated=true'));
				die();
				break;
			default:
				break;
		}
	}
	
	// Cloning
	if (isset($_POST['publish'])) {
		
	}
}
if (is_admin()) {
	add_action('init', 'annowf_admin_request_handler', 0);
}

/**
 * Helper function to determine if the workflow is enabled
 */ 
function anno_workflow_enabled() {
	if (get_option('annowf_setting') == 1) {
		return true;
	}
	return false;
}

?>