<?php
/**
 * Remove the standard publish meta box
 */ 
function anno_workflow_meta_boxes() {
	// Remove the WP Publish box
	remove_meta_box('submitdiv', 'article', 'side');

	// Add the Annotum workflow publish box
	add_meta_box('anno-submitdiv', __('Publishing', 'anno'), 'anno_meta_publish', 'article', 'side', 'high');
}
add_action('admin_head-post.php', 'anno_workflow_meta_boxes');
add_action('admin_head-post-new.php', 'anno_workflow_meta_boxes');

/**
 * Callback for publish meta box
 */ 
function anno_meta_publish() {
	anno_publish_markup();
}

/**
 * Display publish meta box markup. Heavily based on code from the WP Core.
 */ 
function anno_publish_markup() {
		$post_type = 'article';
		$post_type_object = get_post_type_object($post_type);
		$can_publish = current_user_can($post_type_object->cap->publish_posts);
	?>
<div class="submitbox" id="submitpost">
	<div id="minor-publishing">

	<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
		<div style="display:none;">
	<?php submit_button( __( 'Save' ), 'button', 'save' ); ?>
		</div>

		<div id="minor-publishing-actions">
			<div id="save-action">
				<input <?php if ( 'private' == $post->post_status ) { ?>style="display:none"<?php } ?> type="submit" name="save" id="save-post" value="<?php esc_attr_e('Save Draft'); ?>" tabindex="4" class="button button-highlighted" />

				<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" id="draft-ajax-loading" alt="" />
			</div>

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
			</div>
		</div>
	</div>
	<div class="clear"></div>
</div>
<?php 
}
?>