<?php

/**
 * AJAX handler that looks up an article based on PMCID and parses the data for a reference.
 * Echos a json encoded array
 * 
 * @return void
 */ 
function anno_reference_import_pubmed() {	
	if (!check_ajax_referer('anno_import_pubmed', '_ajax_nonce-import-pubmed') || !isset($_POST['pmcid'])) {
		$lookup_response = anno_reference_error_response();
	}
	else {
		$pubmed_id = $_POST['pmcid'];
	}

	$lookup_response = array(
		'message' => 'error',
		'text' => _x('An error has occurred, please try again later', 'pmcid lookup error message', 'anno'),
	);

	// Only Allow nubmers for our ID, commas are also allowed, but only when looking up multiple articles.
	$pubmed_id = trim($pubmed_id);
	if (preg_match('/[^0-9]/', $pubmed_id)) {
		anno_reference_error_response(_x('Invalid PMCID', 'pmcid lookup error message', 'anno'));
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
		anno_reference_error_response();
	}
	else {
		include_once(CFCT_PATH.'functions/phpquery/phpquery.php');
		phpQuery::newDocumentXML($response['body']);
		phpQuery::getDocument();
		
		$errors = pq('ERROR');
		$body = pq('DocSum');
		if ($errors->length > 0 || $body->length == 0) {
			anno_reference_error_response();
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


/**
 * AJAX handler that looks up an article based on PMCID and parses the data for a reference.
 * Echos a JSON encoded array
 * 
 * @return void
 */
function anno_reference_import_doi() {
		if (!check_ajax_referer('anno_import_doi', '_ajax_nonce-import-doi') || !isset($_POST['doi'])) {
			anno_reference_error_response();
		}
		else {
			$doi = $_POST['doi'];
		}
		
		$lookup_response = array(
			'message' => 'error',
			'text' => _x('An error has occurred, please try again later', 'pmcid lookup error message', 'anno'),
		);

		// DOIs cannot contain any control characters. As defined here: http://www.doi.org/handbook_2000/appendix_1.html
		$doi = trim($doi);
		if (preg_match('/[\x00-\x1F\x7F]/', $doi)) {
			anno_reference_error_response(_x('Invalid DOI', 'pmcid lookup error message', 'anno'));
		}

		// Generate the URL for lookup
		$crossref_login = cfct_get_option('crossref_login');
		$crossref_pass = cfct_get_option('crossref_pass');
		
		// Empty login, throw error,
		if (empty($crossref_login)) {
			anno_reference_error_response(_x('Invalid CrossRef Login', 'pmcid lookup error message', 'anno'));
		}
		// Empty pass, just try passing login
		else if (empty($croossref_pass)) {
			$url = 'http://www.crossref.org/openurl/?pid='.$crossref_login.'&id=doi:'.$doi.'&noredirect=true';
		}
		// Password and Login not empty, pass both in query. Caught later if invalid.
		else {
			$url = 'http://www.crossref.org/openurl/?pid='.$crossref_login.':'.$crossref_pass.'&id=doi:'.$doi.'&noredirect=true';
		}

		// Use wp.com functions if available for lookup.
		if (function_exists('vip_safe_wp_remote_get')) {
			$response = vip_safe_wp_remote_get($url);
		}
		else {
			$response = wp_remote_get($url);
		}

		if (is_wp_error($response) || (isset($response['response']['code']) && $response['response']['code'] != 200) || !isset($response['body'])) {
			anno_reference_error_response();
		}
		else {
			include_once(CFCT_PATH.'functions/phpquery/phpquery.php');
			phpQuery::newDocumentXML($response['body']);
			phpQuery::getDocument();

			$html = pq('html');

			// If we find an HTML tag, echo error.
			if ($html->length > 0) {
				// We should only hit an HTML page for malformed URLs or invalid logins
				// @TODO error for invalid login.
				anno_reference_error_response(_x('Invalid DOI', 'pmcid lookup error message', 'anno'));
			}

			$query_status = pq('query')->attr('status');
			// Error if unresolved
			if ($query_status == 'unresolved') {
				$lookup_response = anno_reference_error_response(pq('msg')->text());
			}
			// Process resolved queries
			else if ($query_status == 'resolved') {
				$text = '';
				
				// There should only be a single 'first' author.
				$prime_author = pq('contributor[sequence="first"][contributor_role="author"]');
				$author_text = anno_reference_doi_process_author($prime_author);
				if (!empty($author_text)) {
					$author_arr[] = $author_text;
				}
				
				$additional_authors = pq('contributor[sequence="additional"][contributor_role="author"]');
				
				foreach ($additional_authors as $additional_author) {
					$additional_author = pq($additional_author);
					$author_text = anno_reference_doi_process_author($additional_author);
					if (!empty($author_text)) {
						$author_arr[] = $author_text;
					}
				}
				$text .= implode(', ', $author_arr).'. ';			
				
				// Title
				$title = pq('article_title')->text();
				if (!empty($title)) {
					// Titles do not have periods
					$text .= $title.'. ';
				}

				// Source
				$source = pq('journal_title')->text();
				if (!empty($source)) {
					$text .= $source.'. ';
				}

				// Date, Volume, Issue, Page
				$date_meta = '';
				$date = pq('year')->text();
				$volume = pq('volume')->text();
				$issue = pq('issue')->text();
				$first_page = pq('first_page')->text();
				$last_page = pq('last_page')->text();

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
					if (!empty($first_page)) {
						$date_meta .= ':'.$first_page;
					}
					if (!empty($last_page)) {
						$date_meta .= '-'.$last_page;
					}
				}

				if (!empty($date_meta)) {
					$text .= $date_meta.'. ';
				}

				$text .= _x('DOI:', 'Reference text for doi lookup', 'anno').$doi.'.';			

				$lookup_response = array(
					'message' => 'success',
					'text' => esc_textarea($text),
				);
			}
			// Neither resolved nor unresolved, throw generic error
			else {
			 	anno_reference_error_response();
			
			}
		}

		echo json_encode($lookup_response);
		die();
	}
add_action('wp_ajax_anno-import-doi', 'anno_reference_import_doi');

/**
 * Echos out a JSON encoded array for errors with reference lookup.
 * 
 * @param String $message Error message to be used, otherwise a default will be generated
 * @return void
 */ 
function anno_reference_error_response($message = null) {
	if (empty($message)) {
		$message = _x('An error has occurred, please try again later', 'pmcid lookup error message', 'anno');
	}
	
	echo json_encode(array(
		'message' => 'error',
		'text' => $message,
	));
	// No need to continue if we've encountered an error
	die();
}

/**
 * Generate author text for a DOI XML document
 * @param phpQueryObject $author author wrapping element
 * @return string  
 */ 
function anno_reference_doi_process_author($author) {
	$text = '';
	// @TODO implement prefixes
	$last_name = pq('surname', $author)->text();
	$first_name = pq('given_name', $author)->text();

	if (!empty($last_name)) {
		$text .= $last_name;
		if (!empty($first_name)) {
			$text .= ' ';
		}
	}

	if (!empty($first_name)) {
		$text .= $first_name;
	}

	return $text;
}

?>