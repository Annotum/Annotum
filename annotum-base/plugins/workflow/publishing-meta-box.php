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
 * Callback for publish meta box. Heavily based on code from the WP Core 3.1.2
 */ 
function annowf_status_meta_box($post) {
	$post_state = annowf_get_post_state($post->ID);
?>
<div class="submitbox" id="submitpost">
	<input name="post_state" type="hidden" value="<?php esc_attr_e($post_state); ?>" />
	<div id="minor-publishing">
		<div id="minor-publishing-actions">
			<?php 
				if (function_exists('annowf_minor_action_'.$post_state.'_markup')) {
					call_user_func('annowf_minor_action_'.$post_state.'_markup');
				}
			?>
		</div> <!-- #minor-publishing-actions -->

	<?php 
		if ($post_state == 'approved' && anno_user_can('alter_post_state')) { 
			annowf_misc_action_approved_markup();
 		} 
	?>
	</div> <!-- #minor-publising -->
	<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( ('auto-draft' == $post->post_status ) ? 'draft' : $post->post_status); ?>" />
	<div id="major-publishing-actions">
		<?php
			do_action('post_submitbox_start'); 
			if (function_exists('annowf_major_action_'.$post_state.'_markup')) {
				call_user_func('annowf_major_action_'.$post_state.'_markup');
			}
		?>

	</div> <!-- #major-publishing-actions -->
</div> <!-- .submitbox -->
<?php
}

/**
 * Draft state markup for minor actions.
 */
function annowf_minor_action_draft_markup() {
	if (anno_user_can('edit_post')) {
		annowf_minor_action_save_markup();
	}
	annowf_minor_action_preview_markup();
}

/**
 * Draft state markup for major actions.
 */
function annowf_major_action_draft_markup() {
	global $anno_post_save;
	$post_id = anno_get_post_id();
	if (anno_user_can('trash_post')) {
			$wrap_class = '';
?>
		<div id="delete-action">
			<a class="submitdelete deletion" href="<?php echo get_delete_post_link($post_id); ?>"><?php _ex('Move To Trash', 'Publishing box trash action link text', 'anno'); ?></a>
		</div>
<?php
	}
	else {
		$wrap_class = ' class="center-wrap"';
	}
?>
		<div id="publishing-action"<?php echo $wrap_class; ?>>
			<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" id="ajax-loading" alt="" />
			<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e($anno_post_save['review']); ?>" />

			<?php submit_button($anno_post_save['review'], 'primary', 'publish', false, array( 'tabindex' => '5', 'accesskey' => 'p' )); ?>
		</div>
		<div class="clear"></div>
<?php 
}

/**
 * Submitted state markup for minor actions.
 */
function annowf_minor_action_submitted_markup() {
	if (anno_user_can('edit_post')) {
		annowf_minor_action_save_markup();
		annowf_minor_action_preview_markup();
	}
?>
		<p class="status-text">
			<?php _ex('Submitted - Waiting For Review', 'Publishing box meta text', 'anno'); ?>
		</p>
<?php
}

/**
 * Submitted state markup for major actions.
 */
function annowf_major_action_submitted_markup() {
	if (anno_user_can('alter_post_state')) {
		global $anno_post_save;
?>
	<div id="publishing-action-approve" class="center-wrap">
		<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e($anno_post_save['approve']) ?>" />	
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
		annowf_major_action_preview_markup();
	}
}

/**
 * In Review state markup for minor actions.
 */
function annowf_minor_action_in_review_markup() {
	if (anno_user_can('edit_post')) {
		global $post;
		$post_round = annowf_get_round($post->ID);
		annowf_minor_action_save_markup();
		annowf_minor_action_preview_markup();
		if ($post_round !== false) {
			// Return array of user ids who have given reviews for this round
			$round_reviewed = get_post_meta($post->ID, '_round_'.$post_round.'_reviewed', true);
			if (!is_array($round_reviewed)) {
				$round_reviewed = array();
			}
			$round_reviewed = count($round_reviewed);		
			$reviewers = count(anno_get_reviewers($post->ID));
?>
			<p class="status-text">
<?php
			printf(_x('%s of %s Reviews Complete', 'Article publishing box meta text', 'anno'), '<span id="anno-reviewed-count">'.$round_reviewed.'</span>', '<span id="anno-reviewers-count">'.$reviewers.'</span>');
		}
	}
	else {
?>
			<p class="status-text">
<?php
 			_ex('Submitted - In Review', 'Publishing box meta text', 'anno'); 
	}	
?>
		</p>
<?php
}

/**
 * In Review state markup for major actions.
 */
function annowf_major_action_in_review_markup() {	
	if (anno_user_can('alter_post_state')) {
		global $anno_post_save;
?>
	<div id="publishing-action-approve" class="center-wrap">
		<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e($anno_post_save['approve']) ?>" />	
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
		annowf_major_action_preview_markup();
	}
}

/**
 * Approved state markup for minor actions.
 */
function annowf_minor_action_approved_markup() {
	// We don't have to check for edit, as alter_post_state is the same set of users in this case
	if (anno_user_can('edit_post')) {
		annowf_minor_action_save_markup();
		annowf_minor_action_preview_markup();
	}
	else {
?>
	<p class="status-text">
		<?php _ex('Article Approved', 'Publishing box meta text', 'anno'); ?>
	</p>
<?php
	}
}

/**
 * Approved state markup for major actions.
 */
function annowf_major_action_approved_markup() {
	if (anno_user_can('alter_post_state')) {
		global $anno_post_save;
		annowf_major_action_revert(); 
?>
	<div id="publishing-action" class="float-right">
		<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" id="ajax-loading" alt="" />	
		<?php submit_button($anno_post_save['publish'], 'primary', 'publish', false, array( 'tabindex' => '5')); ?>
	</div>
	<div class="clear"></div>
<?php	
	}
	else {
		annowf_major_action_preview_markup();
	}
}

/**
 * Approved state markup for misc actions
 */
function annowf_misc_action_approved_markup() {
	global $post;
	$post_type = 'article';
	$post_type_object = get_post_type_object($post_type);
	$can_publish = current_user_can($post_type_object->cap->publish_posts);
?>
<div id="misc-publishing-actions">
	<div class="misc-pub-section " id="visibility">
	<?php _ex('Visibility:', 'Publishing box visibility text', 'anno'); ?> <span id="post-visibility-display"><?php

	if ( 'private' == $post->post_status ) {
		$post->post_password = '';
		$visibility = 'private';
		$visibility_trans = _x('Private', 'Publishing box visibility text', 'anno');
	} 
	elseif ( !empty( $post->post_password ) ) {
		$visibility = 'password';
		$visibility_trans = _x('Password protected', 'Publishing box visibility text', 'anno');
	} 
	elseif ( $post_type == 'post' && is_sticky( $post->ID ) ) {
		$visibility = 'public';
		$visibility_trans = _x('Public, Sticky', 'Publishing box visibility text', 'anno' );
	}
	else {
		$visibility = 'public';
		$visibility_trans = _x('Public', 'Publishing box visibility text', 'anno');
	}

	echo esc_html( $visibility_trans ); ?></span>
	<?php if ( $can_publish ) { ?>
		<a href="#visibility" class="edit-visibility hide-if-no-js"><?php _ex('Edit', 'verb, publising box edit visibility text', 'anno'); ?></a>

		<div id="post-visibility-select" class="hide-if-js">
			<input type="hidden" name="hidden_post_password" id="hidden-post-password" value="<?php echo esc_attr($post->post_password); ?>" />
		<?php if ($post_type == 'post'): ?>
			<input type="checkbox" style="display:none" name="hidden_post_sticky" id="hidden-post-sticky" value="sticky" <?php checked(is_sticky($post->ID)); ?> />
		<?php endif; ?>
			<input type="hidden" name="hidden_post_visibility" id="hidden-post-visibility" value="<?php echo esc_attr( $visibility ); ?>" />
			<input type="radio" name="visibility" id="visibility-radio-public" value="public" <?php checked( $visibility, 'public' ); ?> /> <label for="visibility-radio-public" class="selectit"><?php _ex('Public', 'adjective, publishing box visibility label', 'anno'); ?></label><br />
		<?php if ($post_type == 'post'): ?>
			<span id="sticky-span"><input id="sticky" name="sticky" type="checkbox" value="sticky" <?php checked(is_sticky($post->ID)); ?> tabindex="4" /> <label for="sticky" class="selectit"><?php _ex('Stick this post to the front page', 'Publishing box visibility label', 'anno'); ?></label><br /></span>
		<?php endif; ?>
			<input type="radio" name="visibility" id="visibility-radio-password" value="password" <?php checked( $visibility, 'password' ); ?> /> <label 	for="visibility-radio-password" class="selectit"><?php _ex('Password protected', 'Publishing box visibility label', 'anno'); ?></label><br />
			<span id="password-span"><label for="post_password"><?php _ex('Password:', 'Publishing box visibility label', 'anno'); ?></label> <input type="text" name="post_password" id="post_password" value="<?php echo esc_attr($post->post_password); ?>" /><br /></span>
			<input type="radio" name="visibility" id="visibility-radio-private" value="private" <?php checked( $visibility, 'private' ); ?> /> <label for="visibility-radio-private" class="selectit"><?php _ex('Private', 'adjective, publishing box visibility label', 'anno'); ?></label><br />

			<p>
			 <a href="#visibility" class="save-post-visibility hide-if-no-js button"><?php _ex('OK', 'Publishing box visibility button text', 'anno'); ?></a>
			 <a href="#visibility" class="cancel-post-visibility hide-if-no-js"><?php _ex('Cancel', 'Publishing box visibility button text', 'anno'); ?></a>
			</p>
		</div>
	<?php } ?>
	</div><?php // /misc-pub-section ?>
	<div class="clear"></div>
	<?php
		// translators: Publish box date formt, see http://php.net/date
		$datef = _x( 'M j, Y @ G:i', 'Publishing box date format', 'anno' );
		if ( 0 != $post->ID ) {
			if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
				$stamp = _x('Scheduled for: <b>%1$s</b>', 'Publishing box future date', 'anno');
			} else if ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
				$stamp = _x('Published on: <b>%1$s</b>', 'Publishing box publish date', 'anno');
			} else if ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
				$stamp = _x('Publish <b>immediately</b>', 'Publishing box publish date', 'anno');
			} else if ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
				$stamp = _x('Schedule for: <b>%1$s</b>', 'Publishing box schedule date', 'anno');
			} else { // draft, 1 or more saves, date specified
				$stamp = _x('Publish on: <b>%1$s</b>', 'Publishing box schedule date', 'anno');
			}
			$date = date_i18n( $datef, strtotime( $post->post_date ) );
		} else { // draft (no saves, and thus no date specified)
			$stamp = _x('Publish <b>immediately</b>', 'Publishing box publish date', 'anno');
			$date = date_i18n( $datef, strtotime( current_time('mysql') ) );
		}

		if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
		<div class="misc-pub-section curtime misc-pub-section-last">
			<span id="timestamp">
			<?php printf($stamp, $date); ?></span>
			<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" tabindex='4'><?php _ex('Edit', 'Publishing box edit post date text', 'anno') ?></a>
			<div id="timestampdiv" class="hide-if-js"><?php touch_time(true, 1, 4); ?></div>
		</div><?php // /misc-pub-section ?>
		<?php endif; ?>

	<?php do_action('post_submitbox_misc_actions'); ?>

</div>	
<?php
}

/**
 * Rejected state markup for minor actions.
 */
function annowf_minor_action_rejected_markup() {
?>
	<p class="status-text">
		<?php _ex('Article Rejected', 'Publishing box meta text', 'anno'); ?>
	</p>
<?php
}

/**
 * Rejected state markup for major actions.
 */
function annowf_major_action_rejected_markup() {
	if (anno_user_can('alter_post_state')) {
		annowf_major_action_revert('left');
		annowf_major_action_clone_markup('right');
	}
	else {
		annowf_major_action_clone_markup();
	}
}

/**
 * Published state markup for minor actions.
 */
function annowf_minor_action_published_markup() {
	// No state alteration should occur in published
	if (anno_user_can('edit_post')) {
		annowf_minor_action_save_markup();
		annowf_minor_action_preview_markup();
	}
	else {
?>
	<p class="status-text">
		<?php _ex('Article Published', 'Publishing box meta text', 'anno'); ?>
	</p>
<?php
	}
}

/**
 * Published state markup for major actions.
 */
function annowf_major_action_published_markup() {
	if (anno_user_can('edit_post')) {
		annowf_major_action_revert('left');
		annowf_major_action_clone_markup('right');
	}
	else {
		annowf_major_action_clone_markup();
	}
}

/**
 * Preview button markup used in many minor actions for various states
 */
function annowf_minor_action_preview_markup() {
	global $post;
?>
	<div id="preview-action">
<?php
	if ( 'publish' == $post->post_status ) {
		$preview_link = esc_url( get_permalink( $post->ID ) );
		$preview_button = _x('Preview Changes', 'Publising box preview button text', 'anno');
	} else {
		$preview_link = get_permalink( $post->ID );
		if ( is_ssl() )
			$preview_link = str_replace( 'http://', 'https://', $preview_link );
		$preview_link = esc_url( apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $preview_link ) ) );
		$preview_button = _x('Preview', 'Publising box preview button text', 'anno');
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
function annowf_major_action_preview_markup() {
?>
	<div id="preview-action" class="major center-wrap">
<?php
	global $post;
	if ( 'publish' == $post->post_status ) {
		$preview_link = esc_url( get_permalink( $post->ID ) );
		$preview_button = _x('Preview Changes', 'Publising box preview button text', 'anno');
	} 
	else {
		$preview_link = get_permalink( $post->ID );
		if ( is_ssl() )
			$preview_link = str_replace( 'http://', 'https://', $preview_link );
		$preview_link = esc_url( apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true', $preview_link ) ) );
		$preview_button = _x('Preview', 'Publising box preview button text', 'anno');
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
function annowf_minor_action_save_markup() {
	// Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key
	global $post;
?>
		<div style="display:none;">
			<?php submit_button( _x('Save', 'Publishing box save button text', 'anno'), 'button', 'save' ); ?>
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
function annowf_major_action_clone_markup($position = 'center') {
	global $anno_post_save, $post;
	if (!annowf_has_clone($post->ID)) {
		if ($position == 'center') {
			$class = 'center-wrap';
		}
		else {
			$class = 'float-right';
		}
?>
		<div id="clone-action" class="major <?php echo $class ?>">
			<?php submit_button($anno_post_save['clone'], 'primary', 'publish', false, array( 'tabindex' => '5', 'accesskey' => 'p' )); ?>
		</div>
<?php 
	}
	if ($position != 'center') {		
?>	
			<div class="clear"></div>
<?php 
	}
}

/**
 * Revert to draft markup, used for accidental state transitions
 */ 
function annowf_major_action_revert($position = 'left') {
	global $anno_post_save;
	if ($position == 'center') {
		$class = 'center-wrap';
	}
	else {
		$class = 'float-left';
	}
?>
	<div id="revert-action" class="major <?php echo $class ?>">
		<?php submit_button($anno_post_save['revert'], 'primary', 'revert', false, array( 'tabindex' => '5', 'accesskey' => 'p' )); ?>
	</div>
<?php 
	if ($position == 'center') {		
?>	
		<div class="clear"></div>
<?php 
	}
}

?>