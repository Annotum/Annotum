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
