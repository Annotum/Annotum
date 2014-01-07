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
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

//@TODO conditionally use on article post type

function anno_media_enqueue() {
	wp_enqueue_script(
		'annotum-media',
		trailingslashit(get_template_directory_uri()).'assets/main/js/media.js',
		array('jquery', 'backbone', 'media-models')
	);
 }
add_action('admin_enqueue_scripts', 'anno_media_enqueue');


function anno_media_templates() {
	include_once('media-templates.php');
}
add_action('print_media_templates', 'anno_media_templates');

// Setup the Backbone object so we can access via
// media templates with data.property
function anno_prepare_attachment_for_js($response, $attachment) {
	$meta_keys = array(
		'annoLabel' => '_anno_attachment_image_label',
		'annoCpyStatement' => '_anno_attachment_image_copyright_statement',
		'annoCpyHolder' => '_anno_attachment_image_copyright_holder',
		'annoLicense' => '_anno_attachment_image_license',
	);

	foreach ($meta_keys as $key => $meta_key) {
		$response[$key] = get_post_meta($attachment->ID, $meta_key, true);
	}
	$response['annoDspType'] = 'inline';
	return $response;
}
add_filter('wp_prepare_attachment_for_js', 'anno_prepare_attachment_for_js', 10 , 2);


function anno_ajax_save_attachment() {
	if (!isset($_REQUEST['id']) || !isset($_REQUEST['changes'])) {
		wp_send_json_error();
	}

	if (!$id = absint( $_REQUEST['id'])) {
		wp_send_json_error();
	}

	check_ajax_referer('update-post_' . $id, 'nonce');

	if (!current_user_can('edit_post', $id)) {
		wp_send_json_error();
	}

	$changes = $_REQUEST['changes'];

	$meta_to_save = array(
		'label' => '_anno_attachment_image_label',
		'license' => '_anno_attachment_image_license',
		'cpstatement' => '_anno_attachment_image_copyright_statement',
		'cpholder' => '_anno_attachment_image_copyright_holder',
	);

	foreach ($meta_to_save as $key => $meta_key) {
		if (isset($changes[$key])) {
			update_post_meta($id, $meta_key, $changes[$key]);
		}
	}

	//wp_send_json_success(); Let other actions also fire like wp_ajax_save_attachment
}
add_action('wp_ajax_save-attachment', 'anno_ajax_save_attachment', 0);

function anno_media_send_to_editor($html, $id, $attachment) {
	$html = '';
	if (!isset($attachment['display'])) {
		$attachment['display'] = 'inline';
	}

	$img_data = wp_get_attachment_image_src($id, $attachment['image-size']);
	$img_url = is_array($img_data) && isset($img_data[0]) ? $img_data[0] : '';

	if (trim($attachment['display']) == 'figure') {
		$meta = array(
			'label' => '_anno_attachment_image_label',
			'copyright_statement' => '_anno_attachment_image_copyright_statement',
			'copyright_holder' => '_anno_attachment_image_copyright_holder',
			'license' => '_anno_attachment_image_license',

		);
		foreach ($meta as $key => $meta_key) {
			$attachment[$key] = get_post_meta($id, $meta_key, true);
		}

		if (!empty($attachment['url'])) {
			$fig_uri = '<div class="uri" data-xmlel="uri" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="'.$attachment['url'].'"></div>';
		}
		else {
			$fig_uri = '';
		}

		$html = '
<div class="fig" data-xmlel="fig">
	<div class="label" data-xmlel="label">'.$attachment['label'].'</div>
	<div class="caption" data-xmlel="caption">
		<span class="p" data-xmlel="p">'.$attachment['post_excerpt'].'</span>
	</div>
	<div class="media" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="'.$img_url.'" data-xmlel="media">
		'.$fig_uri.'
		<span class="alt-text" data-xmlel="alt-text">'.$attachment['image_alt'].'</span>
		<span class="long-desc" data-xmlel="long-desc">'.$attachment['post_content'].'</span>
		<div class="permissions" data-xmlel="permissions">
			<span class="copyright-statement" data-xmlel="copyright-statement">'.$attachment['copyright_statement'].'</span>
			<span class="copyright-holder" data-xmlel="copyright-holder">'.$attachment['copyright_holder'].'</span>
			<div class="license" data-xmlel="license" license-type="creative-commons">
				<span class="license-p" data-xmlel="license-p">'.$attachment['license'].'</span>
			</div>
		</div>
	</div>
	<div _mce_bogus="1" class="clearfix"></div>
</div>';

	}
	else {
		$html = '<span class="inline-graphic" data-xmlel="inline-graphic" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="'.$img_url.'" alt-text="'.$attachment['image_alt'].'"></span><span> &nbsp;</span>';
	}

	return $html;
}
add_filter('media_send_to_editor', 'anno_media_send_to_editor', 10, 3);


