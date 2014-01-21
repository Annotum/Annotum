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


function anno_media_send_to_editor($html, $id, $attachment) {
	if (!anno_is_article($_REQUEST['post_id'])) {
		return $html;
	}
	$html = '';

	$attachment_object = get_post($id);
	if (!$attachment_object) {
		return '';
	}

	// Attachment is not an image, insert it as a link
	if (!wp_attachment_is_image($id)) {
		$html = '<ext-link ext-link-type="uri" xlink:href="'.$attachment['url'].'">'.$attachment['post_title'].'</ext-link>';
	}
	else {

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
				$fig_uri = '<uri xlink:href="'.$attachment['url'].'"></uri>';
			}
			else {
				$fig_uri = '';
			}

			$html = '
	<fig>
		<img src="'.$img_url.'" />
		<lbl>'.$attachment['label'].'</lbl>
		<cap>
			<para>'.$attachment['post_excerpt'].'<para>
		</cap>
		<media xlink:href="'.$img_url.'">
			'.$fig_uri.'
			<alt-text>'.$attachment['image_alt'].'</alt-text>
			<long-desc>'.$attachment['post_content'].'</long-desc>
			<permissions>
				<copyright-statement>'.$attachment['copyright_statement'].'</copyright-statement>
				<copyright-holder>'.$attachment['copyright_holder'].'</copyright-holder>
				<license license-type="creative-commons">
					<license-p>'.$attachment['license'].'</license-p>
				</license>
			</permissions>
		</media>
		<div _mce_bogus="1" class="clearfix"></div>
	</fig>';

		}
		else {
			$html = '<img src="'.$img_url.'" class="_inline_graphic" alt="'.$attachment['image_alt'].'"/>';
		}
	}


	return $html;
}
add_filter('media_send_to_editor', 'anno_media_send_to_editor', 10, 3);

function anno_media_filter_items($args) {
	$post_id = $_REQUEST['post_id'];
	if (anno_is_article($post_id)) {
		$args['post_parent'] = $post_id;
	}
	return $args;
}
add_filter('ajax_query_attachments_args', 'anno_media_filter_items');

function anno_ajax_save_attachment() {
	if (anno_is_article($_REQUEST['post_id'])) {
		if (!anno_is_article($_REQUEST['post_id'])) {
			return $html;
		}
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
	}
}
add_action('wp_ajax_save-attachment', 'anno_ajax_save_attachment', 0);
