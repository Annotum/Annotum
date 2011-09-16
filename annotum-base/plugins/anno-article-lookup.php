<?php

/**
 * AJAX handler that looks up an article based on PMCID and parses the data for a reference.
 * Echos a json encoded array
 * 
 * @return void
 */ 
function anno_reference_import_pubmed() {	
	check_ajax_referer('anno_import_pubmed', '_ajax_nonce-import-pubmed');
	if (!isset($_POST['pmcid'])) {
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
	check_ajax_referer('anno_import_doi', '_ajax_nonce-import-doi');
	if (!isset($_POST['doi'])) {
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
	
	// Empty login, or empty password and login is not an email.
	if (empty($crossref_login) || (empty($crossref_pass) && !anno_is_valid_email($crossref_login))) {
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
	//Last, First, Suffix
	$last_name = pq('surname', $author)->text();
	$first_name = pq('given_name', $author)->text();
	$suffix = pq('suffix', $author)->text();
		
	if (!empty($last_name)) {
		$text .= $last_name;
		if (!empty($first_name) || !empty($suffix)) {
			$text .= ', ';
		}
	}

	if (!empty($first_name)) {
		$text .= $first_name;
		if (!empty($suffix)) {
			$text .= ', ';
		}
	}
	
	if (!empty($suffix)) {
		$text .= $suffix;
	}
	
	return $text;
}



function anno_reference_doi_article_deposit($article, $doi_suffix) {
	
	$journal_title = cfct_get_option('journal_name');
	$jouranl_issn = cfct_get_option('journal_issn');
	$registrant_code = cfct_get_option('registrant_code');
	
	// Journal Required Fields
	if (empty($journal_title) || empty($journal_issn) || empty($registrant_code)) {
		// @TODO Throw error
	}
	
	$article_year = date('Y', strtotime($article->post_date));

	// Article Required Fields	
	if (empty($article->title) || empty($article_year) || empty($doi_suffix)) {
		// @TODO Throw error
	}
	
	// Journal Required Fields:
	// full_title, ISSN
	// (rec) abbrev_title, doi_data, coden
	// 
	// Article
	// titles, publication_date (year), doi_data
	// (rec) contributors, publication_date (day, month), pages (first_page, last_page), citation_list
	// 
	// 
	
	// Journal Title
	$journal_title_xml = '<full_title>'.$journal_title.'</full_title>';
	
	// Journal ISSN 
	$journal_issn_xml = '<issn media_type="online">'.$journal_issn.'</issn>';
	
	// Journal abbr
	if ($journal_title_abbr = cfct_get_option('journal_abbr')) {
		$journal_title_abbr_xml = '<abbrev_title>'.$journal_title_abbr.'</abbrev_title>';
	}
	else {
		$journal_title_abbr_xml = '';
	}
	
	$authors = get_post_meta($article->ID, '_anno_author_snapshot', true);
	if (is_array($authors) && !empty($authors)) {
		$i = 0;
		$author_xml = '';
		foreach ($authors as $author) {
			// First author is always the primary.
			if ($i == 0) {
				$sequence = 'first';
			}
			else {
				$sequence = 'additional';
			}
			
			$author_xml .= '<person_name sequence="'.$sequence.'" contributor_role="author">';
			if (!empty($author['given_names'])) {
				$author_xml .= '<given_name>'.esc_html($author['given_name']).'</given_name>';
			}
			if (!empty($author['surname'])) {
				$author_xml .= '<surname>'.esc_html($author['given_name']).'</surname>';
			}
			if (!empty($author['suffix'])) {
				$author_xml .= '<suffix>'.esc_html($author['suffix']).'</suffix>';
			}
			if (!empty($author['affiliation'])) {
				$author_xml .= '<affiliation>'.esc_html($author['affiliation']).'</affiliation>';
			}
			$author_xml .= '</person_name>';
			$i++;
		}
	}
	else {
		// @TODO throw error
	}
	
	// @TODO DOI
	$doi = '10.'.$registrant_code.'/'.$doi_suffix;
	
	$citation_xml = '';
	if ($citation_xml = get_post_meta($article->ID, '_anno_references', true)) {
		if (!empty($citations) && is_array($citation_xml)) {
			foreach ($citations as $citation_key => $citation) {
				if (isset($citation['text']) && !empty($citation['text'])) {
					$citation_xml .= '<citation key="'.$doi.'-'.esc_html($citation_key).'">
							<unstructured_citation>'.esc_html($citation_xml).'</unstructured_citation>
						</<citation>';
				}
			}
			// @TODO check other Element xml creation in a similar manner.
			if (!empty($citation_xml)) {
				$citation_xml = '<citation_list>'.$citation_xml.'</citation_list>';
			}
		}
	}
	
	// Use old parameter based links, in case permalink structure is changed in the future
	$permalink = home_url('?p=' . $article->ID);
	

	$xml = '
<doi_batch xmlns="http://www.crossref.org/schema/4.3.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" version="4.3.0" xsi:schemaLocation="http://www.crossref.org/schema/4.3.0 http://www.crossref.org/schema/deposit/crossref4.3.0.xsd">
	<head>
		<timestamp>'.time().'</timestamp>'
/*		<depositor>
			<name>'.$TODO.'</name>
			<email_address>'.$TODO.'</email_address>
		</depositor>
*/
	.'</head>
	<body>
		<journal>
			<journal_metadata language="en">
				'.$journal_title_xml.'
				'.$journal_title_abbr_xml.'
				'.$journal_issn_xml.'
			</journal_metadata>
			<journal_article publication_type="full_text">
				<titles>
					<title>
						'.esc_html($article->title).'
					</title>
				</titles>
				<contributors>
					'.$author_xml.'
				</contributors>
				<publication_date media_type="online">
					<year>'.$article_year.'</year>
				</publication_date>
				
				<doi_data>
					<doi>'.$doi.'</doi>
					<timestamp>'.strtotime($article->post_date).'</timestamp>
					<resource>'.$permalink.'</resource>
				</doi_data>
				'.$citation_list.
/*				<component_list>
					<component parent_relation="isPartOf">
						<description>
							<b>Figure 1:</b>
							This is the caption of the first figure...
						</description>
						<format mime_type="image/jpeg">Web resolution image</format>
						<doi_data>
							<doi>10.9876/S0003695199019014/f1</doi>
							<resource>http://ojps.aip.org:18000/link/?apl/74/1/76/f1</resource>
							</doi_data>
					</component>
					<component parent_relation="isReferencedBy">
						<description>
							<b>Video 1:</b>
							This is a description of the video...
						</description>
						<format mime_type="video/mpeg"/>
						<doi_data>
							<doi>10.9876/S0003695199019014/video1</doi>
							<resource>http://ojps.aip.org:18000/link/?apl/74/1/76/video1</resource>
						</doi_data>
					</component>
				</component_list>
*/
		'</journal_article>
	</journal>';

	//@ TODO Deposti

}

?>