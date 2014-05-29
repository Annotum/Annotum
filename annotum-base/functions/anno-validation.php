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
		$content_type = empty($_POST['type']) ? 0 : intval($_POST['type']);
		$content = wp_unslash($_POST['content']);
		$schema = trailingslashit(get_template_directory()).'functions/schema/kipling-jp3-partial.rng';

		if ($content_type == 'body') {
			$post_id = empty($_POST['postID']) ? 0 : intval($_POST['postID']);
			$content = anno_validation_prep_body($content, $post_id);
		}
		else if ($content_type == 'abstract') {
			$content = anno_validation_prep_abstract($content, $post_id);
		}

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
		$post_id = empty($_POST['postID']) ? 0 : intval($_POST['postID']);

		$body = wp_unslash($_POST['body']);
		$body = anno_validation_prep_body($body, $post_id);

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
		$abstract = anno_validation_prep_abstract($abstract);
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

	$body_content = anno_validation_prep_body($post->post_content_filtered, $post_id);
	$body_validation = anno_validate($body_content, $schema);

	$abstract_content = anno_validation_prep_abstract($post->post_excerpt);
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
		add_filter('redirect_post_location', 'anno_validation_redirect_post_location_message');

		remove_action('post_updated', 'annowf_transistion_state', 10, 3);
		remove_action('add_post_meta', 'anno_save_appendices_xml_as_html', 10, 3);
		remove_filter('wp_insert_post_data', 'anno_insert_post_data', null, 2);
		remove_filter('wp_insert_post_data', 'annowf_insert_post_data', 10, 2);
		wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
	}

}
add_action('save_post_article', 'anno_validate_on_save', 999, 2);

function anno_validation_prep_body($content, $post_id = 0) {
	$references = anno_xml_references($post_id);
	return '<body>'.$content.$references.'</body>';
}

function anno_validation_prep_abstract($content) {
	return '<abstract>'.$content.'</abstract>';
}

function anno_xml_references($article_id) {
	$references = get_post_meta($article_id, '_anno_references', true);
	$xml = '';
	if (!empty($references) && is_array($references)) {
		$xml =
'			<ref-list>
			<title>'._x('References', 'xml reference title', 'anno').'</title>';

		foreach ($references as $ref_key => $reference) {
			$doi = '';
			$pmid = '';
			$text = '';
			$link = '';

			$ref_key_display = esc_attr('ref'.($ref_key + 1));
			if (isset($reference['doi']) && !empty($reference['doi'])) {
				$doi = '
					<pub-id pub-id-type="doi">'.esc_html($reference['doi']).'</pub-id>';
			}

			if (isset($reference['pmid']) && !empty($reference['pmid'])) {
				$pmid = '
					<pub-id pub-id-type="pmid">'.esc_html($reference['pmid']).'</pub-id>';
			}

			if (isset($reference['text']) && !empty($reference['text'])) {
				$text = esc_html($reference['text']);
			}

			if (isset($reference['link']) && !empty($reference['link'])) {
				$link = ' xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="'.esc_url($reference['link']).'"';
			}

			$xml .='
		<ref id="'.$ref_key_display.'">
			<label>'.$ref_key_display.'</label>
			<mixed-citation'.$link.'>'.trim($text).'
				'.$doi.$pmid.'
			</mixed-citation>
		</ref>';
		}

		$xml .='
	</ref-list>';
	}

	return $xml;
}

function anno_xslt_transform($content, $action = 'xml') {
	error_reporting(-1);
	ini_set('error_reporting', E_ALL);
	// HTML->XML
	if ($action == 'xml') {
		$content = trim(anno_to_xml($content));
	}
	// XML->HTML
	else {
		$content = trim(anno_process_editor_content($content));
	}
	error_reporting(0);

	return $content;
}

function anno_ajax_xslt_transform() {
	$response = array();
	$response['status'] = '';
	if (isset($_POST['contents'])) {
		$response['status'] = 'success';
		$action = isset($_POST['xsltAction']) ? $_POST['xsltAction'] : 'xml';
		$contents = $_POST['contents'];
		foreach ($contents as $index => $content) {
			$content = wp_unslash($content);
			$content = anno_xslt_transform($content, $action);
			$response['contents'][$index] = $content;
		}
	}
	echo json_encode($response);
	die();
}
add_action('wp_ajax_anno_xslt_transform', 'anno_ajax_xslt_transform');

function anno_validation_redirect_post_location_message($location) {
	return add_query_arg('message', 13, $location);
}

