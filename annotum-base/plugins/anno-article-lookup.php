<?php

/**
 * AJAX handler that looks up an article based on PMCID and parses the data for a reference.
 * Echos a json encoded array
 * 
 * @return void
 */ 
function anno_reference_import_pubmed() {
	//TODO Check nonce
	
	if (!check_ajax_referer('anno_import_pubmed', '_ajax_nonce-import-pubmed') || !isset($_POST['pmcid'])) {
		$lookup_response = anno_reference_error_response();
		echo json_encode($lookup_response);
		die();
	}
	else {
		$pubmed_id = $_POST['pmcid'];
	}

	// Only Allow nubmers for our ID, commas are also allowed, but only when looking up multiple articles.
	$pubmed_id = trim($pubmed_id);
	if (preg_match('/[^0-9]/', $pubmed_id)) {
		$lookup_response = anno_reference_error_response(_x('Invalid PMCID', 'pmcid lookup error message', 'anno'));
		echo json_encode($lookup_response);
		die();
	}
		
	// Generate the URL for lookup
	$url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pubmed&id='.$pubmed_id;

	// Use wp.com functions if available for lookup.
	if (function_exists('vip_safe_wp_remote_get')) {
		$response = vip_safe_wp_remote_get($url);
	}
	else {
		$response = wp_remote_get($url);
	}
	
	if (is_wp_error($response) || (isset($response['response']['code']) && $response['response']['code'] != 200) || !isset($response['body'])) {
		$lookup_response = anno_reference_error_response();
	}
	else {
		include_once(CFCT_PATH.'functions/phpquery/phpquery.php');
		phpQuery::newDocumentXML($response['body']);
		phpQuery::getDocument();
		
		$errors = pq('ERROR');
		$body = pq('DocSum');
		if ($errors->length > 0 || $body->length == 0) {
			$lookup_response = anno_reference_error_response();
		}
		else {
			$text = '';
			
			// Authors
			$authors = pq('Item[Name="Author"]');
			$author_arr = array();
			foreach ($authors as $author) {
				$author = pq($author);
				$author_arr[] = $author->text();
			}
			if (!empty($author_arr)) {
				$text .= implode(', ', $author_arr).'. ';
			}
			
			// Title
			$title = pq('Item[Name="Title"]')->text();
			if (!empty($title)) {
				// Titles already have period
				$text .= $title.' ';
			}
			
			// Source
			$source = pq('Item[Name="Source"]')->text();
			if (!empty($source)) {
				$text .= $source.'. ';
			}
			
			// Date, Volume, Issue, Page
			$date_meta = '';
			$date = pq('Item[Name="PubDate"]')->text();
			$volume = pq('Item[Name="Volume"]')->text();
			$issue = pq('Item[Name="Issue"]')->text();
			$page = pq('Item[Name="Pages"]')->text();
						
			if (!empty($date)) {
				$date_meta .= $date;
			}
			
			if (!empty($volume) || !empty($issue) || !empty($page)) {
				$date_meta .= ';';
				if (!empty($volume)) {
					$date_meta .= $volume;
				}
				if (!empty($issue)) {
					$date_meta .= '('.$issue.')';
				}
				if (!empty($page)) {
					$date_meta .= ':'.$page;
				}
			}
			
			if (!empty($date_meta)) {
				$text .= $date_meta.'. ';
			}
			
			$text .= _x('PubMed PMID:', 'Reference text for PubMed lookup', 'anno').$pubmed_id.'.';			

			$lookup_response = array(
				'message' => 'success',
				'text' => esc_textarea($text),
			);
		}	
	}
		
	echo json_encode($lookup_response);
	die();
}
add_action('wp_ajax_anno-import-pubmed', 'anno_reference_import_pubmed');

function anno_reference_error_response($message = null) {
	if (empty($message)) {
		$message = _x('An error has occurred, please try again later', 'pmcid lookup error message', 'anno');
	}
	
	return array(
		'message' => 'error',
		'text' => $message,
	);
}

?>