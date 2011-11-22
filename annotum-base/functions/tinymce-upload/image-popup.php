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
function anno_image_popup_head_js() {
	if (isset($_GET['anno_action']) && $_GET['anno_action'] == 'image_popup') {
?>
	<script type="text/javascript">
	var post_id = <?php echo esc_js(anno_get_post_id()); ?>
	</script>
<?php
	}
}
add_action('admin_print_scripts', 'anno_image_popup_head_js');

function anno_image_popup_request_handler() {
	if (isset($_GET['anno_action']) && $_GET['anno_action'] == 'image_popup') {
		anno_popup_images_iframe_html();
		die();
	}
}
add_action('admin_head', 'anno_image_popup_request_handler');


function anno_image_popup_enqueue_scripts() {
	wp_enqueue_script('img-popup', get_bloginfo('template_directory').'/js/tinymce/plugins/annoimages/popup.js', array('jquery'));	
}
if (isset($_GET['anno_action']) && $_GET['anno_action'] == 'image_popup') {
	add_action('admin_enqueue_scripts', 'anno_image_popup_enqueue_scripts');
}

function anno_popup_images_iframe_html() {
	$errors = array();
	if ( isset($_POST['html-upload']) && !empty($_FILES) ) {
		check_admin_referer('media-form');
		// Upload File button was clicked
		$id = media_handle_upload('async-upload', $_REQUEST['post_id']);
		unset($_FILES);
		if ( is_wp_error($id) ) {
			$errors['upload_error'] = $id;
			$id = false;
		}
	}	
	
	global $tab;

	$post_id = anno_get_post_id();

	$attachments = get_posts(array(
		'post_type' => 'attachment',
		'posts_per_page' => -1,
		'post_parent' => $post_id,
		'post_mime_type' => 'image',
		'order' => 'ASC'
	));	
?>
<body id="anno-popup-images">
<div id="anno-popup-images-inside" class="anno-mce-popup">
	<div class="anno-mce-popup-fields">
<?php 
		if ( !empty($id) ) {
			if ( is_wp_error($id) ) {
				echo '<div id="media-upload-error">'.esc_html($id->get_error_message()).'</div>';
				exit;
			}
		}
?>
		<table class="anno-images">
			<thead>
				<tr>
					<th scope="col" class="img-list-img"></th>
					<th scope="col" class="img-list-title"></th>
					<th scope="col" class="img-list-actions"></th>
				</tr>
			</thead>
			<tbody id="media-items">
<?php
	foreach ($attachments as $attachment_key => $attachment) {
		anno_popup_images_row_display($attachment);
		anno_popup_images_row_edit($attachment);
	}
?>		
			</tbody>
		</table>

		<?php anno_upload_form(); ?>
	</div>
</body>
<?php
}

/**
 * Loads the iframe for tinyMCE popup
 */ 
function anno_popup_images() {
	global $post;
	$query_args = array('anno_action' => 'image_popup', 'post' => $post->ID);
	$url = add_query_arg($query_args, admin_url());
?>
	<div id="anno-popup-images">
		<iframe class="" src="<?php echo $url; ?>" width="480px" height="600px"></iframe>
	</div>
<?php
}

function anno_popup_images_row_edit($attachment) {
		$img_url = wp_get_attachment_image_src($attachment->ID, 'anno_img_edit');
		
		$description = $attachment->post_content;
		$caption = $attachment->post_excerpt;
		
		$alt_text = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
		$display = get_post_meta($attachment->ID, '_anno_attachment_image_display', true);
		if (empty($display)) {
			$display = 'figure';
		}

		//@TODO worth storing all this data in a single meta row as a serialized array
		$label = get_post_meta($attachment->ID, '_anno_attachment_image_label', true);
		$copyright_statement = get_post_meta($attachment->ID, '_anno_attachment_image_copyright_statement', true);
		$copyright_holder = get_post_meta($attachment->ID, '_anno_attachment_image_copyright_holder', true);
		$license = get_post_meta($attachment->ID, '_anno_attachment_image_license', true);
		
		$url = get_post_meta($attachment->ID, '_anno_attachment_image_url', true);
		$file_url = wp_get_attachment_url($attachment->ID); 
		$link = get_attachment_link($attachment->ID);
		
		$img_size = get_post_meta($attachment->ID, '_anno_attachment_image_size', true);
		if (!$img_size) {
			$img_size = 'thumbnail';
		}
?>
			<tr>
				<td class="img-edit-td" colspan="3">
					<form id="<?php echo esc_attr('img-edit-'.$attachment->ID); ?>" class="anno-img-edit">
						<div class="img-edit-details">
							<img src="<?php echo esc_url($img_url[0]); ?>" alt="<?php echo esc_attr($attachment->post_title); ?>" class="img-list-img" />
							<label for="<?php echo esc_attr('img-alttext-'.$attachment->ID); ?>">
								<div><?php _ex('Alt Text', 'input label', 'anno'); ?></div>
								<input name="alt_text" type="text" id="<?php echo esc_attr('img-alttext-'.$attachment->ID); ?>" value="<?php echo esc_attr($alt_text); ?>" />
							</label>
							<label for="<?php echo esc_attr('img-description-'.$attachment->ID); ?>">
								<div><?php _ex('Description', 'input label', 'anno'); ?></div>
								<textarea name="description" id="<?php echo esc_attr('img-description-'.$attachment->ID); ?>"><?php echo esc_textarea($description); ?></textarea>
							</label>
						</div>
<?php 
/*						@TODO Find a way to maintain wrapping URL data while adhering to DTD
						<div class="img-url-input">
							<label for="<?php echo esc_attr('img-url-'.$attachment->ID); ?>">
								<input id="<?php echo esc_attr('img-url-'.$attachment->ID); ?>" type="text" name="url" value="<?php echo esc_attr($url); ?>" /><span><?php _e('URL', 'anno'); ?></span>
							</label>
							<div id="<?php echo esc_attr('img-url-buttons-'.$attachment->ID); ?>" class="img-url-buttons">
								<button type="button" class="button" title=""><?php _e('None', 'anno'); ?></button>
								<button type="button" class="button" title="<?php echo esc_attr($file_url); ?>"> <?php _e('File URL', 'anno'); ?></button>
								<button type="button" class="button" title="<?php echo esc_attr($link); ?>"><?php _e('Attachment Post URL', 'anno'); ?></button>
							</div>
						</div>
*/
?>
						<fieldset class="img-display">
							<legend><?php _ex('Display', 'legend', 'anno'); ?></legend>
							<label for="<?php echo esc_attr('img-display-figure-'.$attachment->ID); ?>" class="radio">
								<input type="radio" value="figure" name="display" class="img-display-selection img-display-figure" id="<?php echo esc_attr('img-display-figure-'.$attachment->ID); ?>"<?php checked($display, 'figure', true); ?> />
								<span><?php _ex('Display as Figure', 'input label', 'anno'); ?></span>
							</label>
							<label for="<?php echo esc_attr('img-display-inline-'.$attachment->ID); ?>" class="radio">
								<input type="radio" value="inline" name="display" class="img-display-selection img-display-inline" id="<?php echo esc_attr('img-display-inline-'.$attachment->ID); ?>"<?php checked($display, 'inline', true); ?> />
								<span><?php _ex('Display Inline', 'input label', 'anno'); ?></span>
							</label>
							<div id="<?php echo esc_attr('img-figure-details-'.$attachment->ID); ?>">
								<label for="<?php echo esc_attr('img-label-'.$attachment->ID); ?>">
									<span><?php _ex('Label', 'input label', 'anno'); ?></span>
									<input type="text" name="label" id="<?php echo esc_attr('img-label-'.$attachment->ID); ?>" value="<?php echo esc_attr($label); ?>" />
								</label>
								<label for="<?php echo  esc_attr('img-caption-'.$attachment->ID); ?>">
									<span><?php _ex('Caption', 'input label', 'anno'); ?></span>
									<textarea id="<?php echo esc_attr('img-caption-'.$attachment->ID); ?>" name="caption"><?php echo esc_textarea($caption); ?></textarea>
								</label>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php _ex('Permissions', 'legend', 'anno'); ?></legend>
							<label for="<?php echo esc_attr('img-copystatement-'.$attachment->ID); ?>">
								<span><?php _ex('Copyright Statement', 'input label', 'anno'); ?></span>
								<input type="text" name="copyright_statement" id="<?php echo esc_attr('img-copystatement-'.$attachment->ID); ?>" value="<?php echo esc_attr($copyright_statement); ?>" />
							</label>
							<label for="<?php echo esc_attr('img-copyholder-'.$attachment->ID); ?>">
								<span><?php _ex('Copyright Holder', 'input label', 'anno'); ?></span>
								<input type="text" name="copyright_holder" id="<?php echo esc_attr('img-copyholder-'.$attachment->ID); ?>" value="<?php echo esc_attr($copyright_holder); ?>" />
							</label>
							<label for="<?php echo esc_attr('img-license-'.$attachment->ID); ?>">
								<span><?php _ex('License', 'input label', 'anno'); ?></span>
								<input type="text" name="license" id="<?php echo esc_attr('img-license-'.$attachment->ID); ?>" value="<?php echo esc_attr($license); ?>" />
							</label>
						</fieldset>
						<fieldset class="img-sizes">
							<legend><?php _ex('Size', 'legend for image size', 'anno'); ?></legend>
							<?php
								$sizes = array(
									'thumbnail' => _x('Thumbnail', 'size label for images', 'anno'),
									'medium' => _x('Medium', 'size label for images', 'anno'),
									'large' => _x('Large', 'size label for images', 'anno'),
								);
							
								// For small images, we won't have any image sizes displayed.
								$size_displayed = false;
								foreach ($sizes as $size_key => $size_label) {
									$downsize = image_downsize($attachment->ID, $size_key);
									$enabled = $downsize[3];
									if ($enabled) {
										$img_size_url = wp_get_attachment_image_src($attachment->ID, $size_key);
										$img_size_url = $img_size_url[0];
										$size_displayed = true;
									}
									else {
										$img_size_url = '';
									}
									
									if (!empty($img_size_url)) {
?>
									<label for="<?php echo esc_attr('img-size-'.$size_key.'-'.$attachment->ID); ?>">
										<input type="radio" name="size" id="<?php echo esc_attr('img-size-'.$size_key.'-'.$attachment->ID); ?>" value="<?php echo esc_attr($size_key); ?>" data-url="<?php echo esc_attr($img_size_url); ?>"<?php checked($size_key, $img_size, true); ?>/> <?php echo esc_html($size_label); ?>
									</label>
<?php
									}
								}
								// No size data displayed because the original image was too small
								if (!$size_displayed) {
									$img_size_url = wp_get_attachment_image_src($attachment->ID, 'full');
									$img_size_url = $img_size_url[0];

									if ($img_size_url) {
?>										
										<label for="<?php echo esc_attr('img-size-full-'.$attachment->ID); ?>">
											<input type="radio" name="size" id="<?php echo esc_attr('img-size-full-'.$attachment->ID); ?>" value="full" data-url="<?php echo esc_attr($img_size_url); ?>"<?php checked(true, true, true); ?> /> <?php _ex('Full', 'size label for images', 'anno'); ?>
										</label>
<?php
									}
								}
								
							?>
						</fieldset>
<?php if (anno_current_user_can_edit()): ?>
						<div class="anno-mce-popup-footer">
							<?php _anno_popup_submit_button('anno-image-save', _x('Save', 'button value', 'anno'), 'submit'); ?>
							<input type="button" id="<?php echo esc_attr('anno-image-insert-'.$attachment->ID); ?>" class="anno-image-insert button" value="<?php _ex('Insert', 'button value', 'anno'); ?>" />
							<input type="hidden" name="action" value="anno-img-save" />
							<input type="hidden" name="attachment_id" value="<?php echo esc_attr($attachment->ID); ?>" />
						</div>
<?php endif; ?>						
					</form>
				</td>
			</tr>
<?php
}

function anno_popup_images_row_display($attachment) {
	$img_url_small = wp_get_attachment_image_src($attachment->ID, 'anno_img_list');
?>
			<tr id="<?php echo esc_attr('img-'.$attachment->ID); ?>">
				<td class="img-list-img">
					<img src="<?php echo esc_url($img_url_small[0]); ?>" alt="<?php echo esc_attr($attachment->post_title); ?>" />
				</td>
				<td class="img-list-title">
					<?php echo esc_html($attachment->post_title); ?>
				</td>
				<td class="img-list-actions">
					<a href="#" id="<?php echo esc_attr('toggle-'.$attachment->ID); ?>" class="show-img"><?php _ex('Show ', 'edit image link text', 'anno'); ?></a>
				</td>
			</tr>
<?php 
}

?>