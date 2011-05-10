<?php
/**
 * Remove the standard publish meta box
 */ 
function anno_workflow_meta_boxes() {
	// Remove the WP Publish box
	remove_meta_box('submitdiv', 'article', 'side');

	// Add the Annotum workflow publish box.
		add_meta_box('anno-submitdiv', __('Status', 'anno'), 'anno_status_meta', 'article', 'side', 'high');
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
 * Display status meta box markup. Heavily based on code from the WP Core 3.1.2
 */ 
function anno_status_markup() {
		global $post;
		$post_type = 'article';
		$post_type_object = get_post_type_object($post_type);
		$can_publish = current_user_can($post_type_object->cap->publish_posts);
		$post_state = get_post_meta($post->ID, '_post_state', true);
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
		// Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key
?>
			<div style="display:none;">
				<?php submit_button( __( 'Save' ), 'button', 'save' ); ?>
			</div>
			<div id="save-action">
				<input <?php if ( 'private' == $post->post_status ) { ?>style="display:none"<?php } ?> type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save Draft'); ?>" tabindex="4" class="button button-highlighted" />

				<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" id="draft-ajax-loading" alt="" />
			</div>
<?php } ?>
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
 * Draft state markup for major actions.
 */
function anno_major_action_draft_markup() {
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

			<?php submit_button(__('Submit for Review', 'anno'), 'primary', 'publish', false, array( 'tabindex' => '5', 'accesskey' => 'p' )); ?>
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
?>
		<p class="status-text">
<?php
	if (anno_user_can('alter_post_state')) {
		global $post;
		$post_round = get_post_meta($post->ID, '_round', true);
		if ($post_round !== false) {
			// Return array of user ids who have given reviews for this round
			$round_reviewed = get_post_meta($post->ID, '_round_'.$post_round.'_reviewed');
			$reviewers = anno_get_reviewers($post->ID);
			if (is_array($round_reviewed)) {
				$round_reviewed = count($round_reviewed);
			}
			else {
				$round_reviewed = 0;
			}
			
			if (is_array($reviewers)) {
				$reviewers = count($reviewers);
			}
			else {
				$reviewers = 0;
			}
			
			printf(__('%d of %d Reviews Complete', 'anno'), $round_reviewed, $reviewers);
		}
	}
	else {
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
		//TODO implement state transitions
		anno_major_action_preview_markup();
	}
	else {
		anno_major_action_preview_markup();
	}
}

/**
 * Approved state markup for minor actions.
 */

/**
 * Approved state markup for major actions.
 */

/**
 * Rejected state markup for minor actions.
 */

/**
 * Rejected state markup for major actions.
 */

/**
 * Published state markup for minor actions.
 */

/**
 * Published state markup for major actions.
 */


/**
 * Preview button markup used in many major actions for various states
 */ 
function anno_major_action_preview_markup() {
?>
	<div id="preview-action" class="major-preview center-wrap">
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
		$preview_button = __( 'Preview' );
	}
?>
		<a class="button-primary" href="<?php echo $preview_link; ?>" target="wp-preview" id="post-preview" tabindex="4"><?php echo $preview_button; ?></a>
		<input type="hidden" name="wp-preview" id="wp-preview" value="" />
	</div> <!-- #preview-action -->
	<div class="clear"></div>
<?php 	
}

/**
 * Article Save action for correcting default WP status updating.
 */ 
function anno_save_article($post) {
	if ($post['post_type'] == 'article' && $post['post_status'] != 'auto-draft') {
		// We hit save but are sending pending status (for now)
		// TODO Update comment/logic for other states
		if (isset($_POST['save']) && $_POST['save'] != '') {
			$post['post_status'] = 'draft';
		}
	}
	return $post;
}
add_filter('wp_insert_post_data', 'anno_save_article', 10, 2);

/**
 * Article state transistioning
 */ 
function anno_transistion_state($post_id, $post) {
	if ($post->post_type == 'article' && $post->post_status != 'auto-draft') {
		$post_state = get_post_meta($post->ID, '_post_state', true);
		$new_state = $post_state;
		if (empty($post_state)) {
			$new_state = 'draft';
		}
		// Draft to submitted or In Review
		$reviewers = anno_get_reviewers($post_id);
		if ($post_state == 'draft' && !$reviewers && $post->post_status == 'pending') {
			$new_state = 'submitted';
			// TODO Update draft rounds
		}
		else if ($post_state == 'draft' && is_array($reviewers) && count($reviewers) > 0 && $post->post_status == 'pending') {
			$new_state = 'in_review';
			// TODO Update draft rounds
		}
	
		if ($new_state != $post_state) {
			update_post_meta($post->ID, '_post_state', $new_state);
		}
	}
}
add_action('save_post', 'anno_transistion_state', 10, 2);

?>