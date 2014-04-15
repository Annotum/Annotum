<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2014 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */

function anno_validate($content, $schema) {
	$response = array();
	$response['status'] = '';

	define('XML_PARSE_BIG_LINES', 4194304); //@TODO incorporate this
	$doc = new DOMDocument();
	libxml_use_internal_errors(true);
	libxml_clear_errors();
	$doc->loadxml($content);

	if (!$doc->relaxNGValidate($schema)) {
		$response['status'] = 'error';
		$errors = libxml_get_errors();
		if (is_array($errors)) {
			foreach ($errors as $libxml_error) {
				$response['errors'][] = array(
					'fullMessage' => sprintf(__('Error on line %d, column %d. %s', 'Error message for validation. %s referes to the error message', 'anno'), $libxml_error->line, $libxml_error->column, str_replace('\n', '', $libxml_error->message)),
					// -1 here as the index starts at 0 but display starts at 1
					'line' => $libxml_error->line - 1,
					'column' => $libxml_error->column,
					'message' => str_replace('\n', '', $libxml_error->message),
					'level' => $libxml_error->level,
				);
			}
		}
	}
	else {
		$response['status'] = 'success';
	}

	return $response;
}

function anno_ajax_validate() {
	$response = array();

	if (isset($_POST['content'])) {
		$content = wp_unslash($_POST['content']);
		$schema = trailingslashit(get_template_directory()).'functions/schema/kipling-jp3-partial.rng';
		$response = anno_validate($content, $schema);

		if ($response['status'] == 'success') {
			$response['status'] = 'success';
			$response['message'] = __('Validation Successful', 'anno');
		}
	}

	echo json_encode($response);
	die();
}
add_action('wp_ajax_anno_validate', 'anno_ajax_validate');


function anno_ajax_validate_all() {
	$response = array('body' => array(), 'abstract' => array());

	if (isset($_POST['body'])) {
		$body = wp_unslash($_POST['body']);
		$schema = trailingslashit(get_template_directory()).'functions/schema/kipling-jp3-partial.rng';
		$response['body'] = anno_validate($body, $schema);

		if ($response['body']['status'] == 'success') {
			$response['body']['status'] = 'success';
			$response['body']['message'] = __('Validation Successful', 'anno');
		}
		else {
			$response['status'] = 'error';
		}
	}

	if (isset($_POST['abstract'])) {
		$abstract = wp_unslash($_POST['abstract']);
		$schema = trailingslashit(get_template_directory()).'functions/schema/kipling-jp3-partial.rng';
		$response['abstract'] = anno_validate($abstract, $schema);

		if ($response['abstract']['status'] == 'success') {
			$response['abstract']['status'] = 'success';
			$response['abstract']['message'] = __('Validation Successful', 'anno');
		}
		else {
			$response['status'] = 'error';
		}
	}

	echo json_encode($response);
	die();
}
add_action('wp_ajax_anno_validate_all', 'anno_ajax_validate_all');

function anno_validate_on_save($post_id, $post) {
	remove_action('save_post_article', 'anno_validate_on_save', 999, 2);
	$error = false;
	$schema = trailingslashit(get_template_directory()).'functions/schema/kipling-jp3-partial.rng';


	$body_content = '<body>'.$post->post_content_filtered.'</body>';
	$body_validation = anno_validate($body_content, $schema);

	$abstract_content = '<abstract>'.$post->post_excerpt.'</abstract>';
	$abstract_validation = anno_validate($abstract_content, $schema);

	if (isset($body_validation['status']) && $body_validation['status'] == 'error') {
		$error = true;
	}
	if (isset($abstract_validation['status']) && $abstract_validation['status'] == 'error') {
		$error = true;
	}

	if ($error && $post->post_status == 'publish') {
		$post->post_status = 'draft';
		if (anno_workflow_enabled()) {
			$status = 'pending';
			update_post_meta($post_id, '_post_state', 'approved');
		}
		else {
			$status = 'draft';
		}

		remove_action('post_updated', 'annowf_transistion_state', 10, 3);
		remove_action('add_post_meta', 'anno_save_appendices_xml_as_html', 10, 3);
		remove_filter('wp_insert_post_data', 'anno_insert_post_data', null, 2);
		remove_filter('wp_insert_post_data', 'annowf_insert_post_data', 10, 2);
		wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
	}

}
add_action('save_post_article', 'anno_validate_on_save', 999, 2);
