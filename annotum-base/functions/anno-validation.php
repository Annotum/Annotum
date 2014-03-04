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

function anno_validate($content) {
	$doc = new DOMDocument();
	//$doc->loadxml($post->post_content_filtered);

	$doc->loadxml('<article></article>');

	if (!$doc->relaxNGValidate(get_template_directory().'/js/textorum/schema/kipling-jp3.rng')) {
    	print '<b>DOMDocument::schemaValidate() Generated Errors!</b>';
    	//libxml_display_errors();
	}

}

function anno_ajax_validate() {
	$response = array();
	define('XML_PARSE_BIG_LINES', 4194304);
	if (isset($_POST['content'])) {
		$content = wp_unslash($_POST['content']);
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadxml($content);


		if (!$doc->relaxNGValidate(trailingslashit(get_template_directory()).'functions/schema/kipling-jp3-partial.rng')) {
			$response['status'] = 'error';
			$errors = libxml_get_errors();
			if (is_array($errors)) {
				foreach ($errors as $libxml_error) {
					$response['errors'][] = array(
						'fullMessage' => sprintf(__('Error on line %d, column %d. %s', 'Error message for validation. %s referes to the error message', 'anno'), $libxml_error->line, $libxml_error->column, str_replace('\n', '', $libxml_error->message)),
						// -1 here as the index starts at 0 but display starts at 1
						'line' => $libxml_error->line - 1,
						'column' => $libxml_error->column,
						'message' => str_replace('\n', '', $libxml_error->message)
					);
				}
			}
		}
		else {
			$response['status'] = 'success';
			$response['message'] = __('Validation Successful', 'anno');
		}
	}
	error_log(print_r($response,1));

	echo json_encode($response);
	die();
}
add_action('wp_ajax_anno_validate', 'anno_ajax_validate');
