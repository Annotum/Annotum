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

class Anno_XML_Download {
	
	static $instance;
	
	private function __construct() {
		/* Define what our "action" is that we'll 
		listen for in our request handlers */
		$this->action = 'anno_xml_download_action';
		$this->i18n = 'anno';
	}
	
	public function i() {
		if (!isset(self::$instance)) {
			self::$instance = new Anno_XML_Download;
		}
		return self::$instance;
	}
	
	public function setup_filterable_props() {
		$this->debug = apply_filters(__CLASS__.'_debug', false);
	}
	
	/**
	 * Let WP know how to handle "pretty" requests for PDFs
	 *
	 * @return void
	 */
	public function setup_permalinks() {
		// Returns an array of post types named article
		$article_post_types = get_post_types(array('name' => 'article'), 'object');
		
		// Ensure we have what we're looking for
		if (!is_array($article_post_types) || empty($article_post_types)) {
			return;
		}
		
		// Get the first (and hopefully only) one
		$article_post_type = array_shift($article_post_types);
		
		global $wp;
		$wp->add_query_var('xml'); // Allows us to use get_query_var('xml') later on
		
		// Tells WP that a request like /articles/my-article-name/xml/ is valid
		add_rewrite_rule($article_post_type->rewrite['slug'].'/([^/]+)/xml/?$', 'index.php?articles=$matches[1]&xml=true', 'top');
		
		// Enable preview URLs
		$wp->add_query_var('preview');
		add_rewrite_rule($article_post_type->rewrite['slug'].'/([^/]+)/xml/preview/?$', 'index.php?articles=$matches[1]&xml=true&preview=true', 'top');
	}
	
	public function add_actions() {
		add_action('init', array($this, 'setup_filterable_props'));
		
		// Run late to make sure all custom post types are registered
		add_action('init', array($this, 'setup_permalinks'), 20);
		
		// Run at 'wp' so we can use the cool get_query_var functions
		add_action('wp', array($this, 'request_handler'));
	}
	
	/**
	 * Request handler for XML.
	 */
	public function request_handler() {
		if (get_query_var('xml')) {
			
			// Sanitize our article ID
			$id = get_the_ID();
			
			// If we don't have an Article, get out
			if (empty($id)) {
				wp_die(__('No article found.', $this->i18n));
			}
			
			// If we're not debugging, turn off errors
			if (!$this->debug) {
				$display_errors = ini_get('display_errors');
				ini_set('display_errors', 0);
			}
			
			// Default Article
			$article = null;
			
			// If we're published, grab the published article
			if (!is_preview()) {
				$article = get_post($id);
			}
			else if (is_preview() && current_user_can('edit_post', $id)) {
				$article = get_post($id);
				
				/* Drafts and sometimes pending statuses append a preview_id on the 
				end of the preview URL.  While we're building the XML download link
				we do a check for that, and set this query arg if the preview_id is
				present. */
				if (isset($_GET['autosave'])) {
					$article = wp_get_post_autosave($id);
				}
			}
			
			// Ensure we have an article
			if (empty($article)) {
				wp_die(__('Required article first.', $this->i18n));
			}
			
			// Get our XML ready and stored
			$this->generate_xml($article);
			
			// Send our headers
			if (!$_GET['screen']) {
				$this->set_headers($article);
			}
			
			// Send the file
			echo $this->xml;
			exit;
		}
	}
	
	
	/**
	 * Sets the download headers for what will be delivered
	 *
	 * @param obj $article 
	 * @return void
	 */
	private function set_headers($article) {
		// Get the post title, so we can set it as the filename
		$filename = sanitize_title(get_the_title($article->ID));
	    header('Cache-Control: private');
		header('Content-Type:text/xml; charset=utf-8');
		header('Content-Length:' . mb_strlen($this->xml, '8bit'));
		header('Content-Disposition: attachment; filename="'.$filename.'.xml"');
	}
	
	
	/**
	 * Builds the entire XML document 
	 *
	 * @param obj $article - WP Post type object
	 * @return void
	 */
	private function generate_xml($article) {
		$this->xml = $this->xml_front($article)."\n".$this->xml_body($article)."\n".$this->xml_back($article);
	}
	
	
	/**
	 * Generate the Front portion of an article XML
	 * 
	 * @param postObject $article Article to generate the XML for. 
	 * @return string XML generated
	 */
	private function xml_front($article) {
		
		// Journal Title
		$journal_title = cfct_get_option('journal_name');
		if (!empty($journal_title)) {
			$journal_title_xml = '<journal-title-group>
					<journal-title>'.esc_html($journal_title).'</journal-title>
				</journal-title-group>';
		}
		else {
			$journal_title_xml = '';
		}
		
		// Journal ID
		$journal_id = cfct_get_option('journal_id');
		if (!empty($journal_id)) {
			$journal_id_type = cfct_get_option('journal_id_type');
			if (!empty($journal_id_type)) {
				$journal_id_type_xml = ' journal-id-type="'.esc_attr($journal_id_type).'"';
			}
			else {
				$journal_id_type_xml = '';
			}
			
			$journal_id_xml = '<journal-id'.$journal_id_type_xml.'>'.esc_html($journal_id).'</journal-id>';
		}
		else {
			$journal_id_xml = '';
		}
		
		// Publisher ISSN
		$pub_issn = cfct_get_option('publisher_issn');
		if (!empty($pub_issn)) {
			$pub_issn_xml = '<issn pub-type="ppub">'.esc_html($pub_issn).'</issn>';
		}
		else {
			$pub_issn_xml = '';
		}
		
		// Abstract
		$abstract = $article->post_excerpt;
		if (!empty($abstract)) {
			$abstract_xml = '<abstract>
					<title>'._x('Abstract', 'xml abstract title', 'anno').'</title>
					<p>'.esc_html($abstract).'</p>
				</abstract>';
		}
		else {
			$abstract_xml = '';
		}
		
		// Funding Statement
		$funding = get_post_meta($article->ID, '_anno_funding', true);
		if (!empty($funding)) {
			$funding_xml = '<funding-group>
					<funding-statement><bold>'.esc_html($funding).'</bold></funding-statement>
				</funding-group>';
		}
		else {
			$funding_xml = '';
		}
				
		// DOI
		$doi = get_post_meta($article->ID, '_anno_doi', true);
		if (!empty($doi)) {
			$doi_xml = '<article-id pub-id-type="doi">'.esc_html($doi).'</article-id>';
		}
		else {
			$doi_xml = '';
		}

		// Article category. Theoretically, there can only be one!
		$cats = wp_get_object_terms($article->ID, 'article_category');
		if (!empty($cats) && is_array($cats)) {
			$category = get_category($cats[0]); 
			if (!empty($category)) {
				$category_xml = '<article-categories>
				<subj-group>
					<subject><bold>'.esc_html($category->name).'</bold></subject>
				</subj-group>
			</article-categories>';
			}
			else {
				$category_xml = '';	
			}
		}
		else {
			$category_xml = '';
		}
		
		// Article Tags
		$tags = wp_get_object_terms($article->ID, 'article_tag');
		if (!empty($tags) && is_array($tags)) {
			$tag_xml = '<kwd-group kwd-group-type="simple">';
			foreach ($tags as $tag) {
				$tag = get_term($tag, 'article_tag');
				$tag_xml .= '<kwd><bold>'.esc_html($tag->name).'</bold></kwd>';
			}
			$tag_xml .= '
			</kwd-group>';
		}
		else {
			$tag_xml = '';
		}
		
		// Article title/subtitle
		$subtitle =  get_post_meta($article->ID, '_anno_subtitle', true);
		$title_xml = '<title-group>';
		if (!empty($article->post_title) || !empty($subtitle)) {
			$title_xml = '<title-group>';
			if (!empty($article->post_title)) {
				$title_xml .= '
				<article-title><bold>'.esc_html($article->post_title).'</bold></article-title>';
			}
			else {
				$title_xml .= '
				<article-title />';
			}
			if (!empty($subtitle)) {
				$title_xml .= '
				<subtitle><bold>'.esc_html($subtitle).'</bold></subtitle>';
			}
		}
		$title_xml .= '
			</title-group>';
		
		// Publisher info
		$pub_name = cfct_get_option('publisher_name');
		$pub_loc = cfct_get_option('publisher_location');
		if (!empty($pub_name) || !empty($pub_loc)) {
			$publisher_xml = '<publisher>';
			if (!empty($pub_name)) {
				$publisher_xml .= '
				<publisher-name>'.esc_html($pub_name).'</publisher-name>';
			}
			
			if (!empty($pub_loc)) {
				$publisher_xml .= '
				<publisher-loc>'.esc_html($pub_loc).'</publisher-loc>';
			}
			$publisher_xml .= '
					</publisher>';
		}
		else {
			$publisher_xml = '';
		}
		
		$pub_date_xml = $this->xml_pubdate($article->post_date);
		
		// Authors	
		$authors = get_post_meta($article->ID, '_anno_author_snapshot', true);
		$author_xml = '<contrib-group>';
		if (!empty($authors) && is_array($authors)) {
			foreach ($authors as $author) {
				$author_xml .= '
				<contrib>';
				if (
					(isset($author['surname']) && !empty($author['surname'])) ||
					(isset($author['given_names']) && !empty($author['given_names'])) ||
					(isset($author['prefix']) && !empty($author['prefix'])) ||
					(isset($author['suffix']) && !empty($author['suffix']))
					) {
						$author_xml .= '
					<name>';
						if (isset($author['surname']) && !empty($author['surname'])) {
							$author_xml .= '
						<surname>'.esc_html($author['surname']).'</surname>';
						}
						if (isset($author['given_names']) && !empty($author['given_names'])) {
							$author_xml .= '
						<given-names>'.esc_html($author['given_names']).'</given-names>';
						}
						if (isset($author['prefix']) && !empty($author['prefix'])) {
							$author_xml .= '
						<prefix>'.esc_html($author['prefix']).'</prefix>';
						}
						if (isset($author['suffix']) && !empty($author['suffix'])) {
							$author_xml .= '
						<suffix>'.esc_html($author['suffix']).'</suffix>';
						}
						$author_xml .= '
					</name>';
					}
					
					// @TODO TDB whether or not to include email							
//					if (isset($author['email']) && !empty($author['email'])) {
//						$author_xml .= '
//						<email>'.esc_html($author['email']).'</email>';
//					}

					if (isset($author['degrees']) && !empty($author['degrees'])) {
						$author_xml .= '
						<degrees>'.esc_html($author['degrees']).'</degrees>';
					}
					
					if (isset($author['affiliation']) && !empty($author['affitliation'])) {
						$author_xml .= '
						<affiliation>'.esc_html($author['affiliation']).'</affiliation>';
					}
					
					if (isset($author['bio']) && !empty($author['bio'])) {
						$author_xml .= '
						<bio><p>'.esc_html($author['bio']).'</p></bio>';
					}			
						
					if (isset($author['link']) && !empty($author['link'])) {
						$author_xml .= '
						<ext-link ext-link-type="uri" xlink:href="'.esc_url($author['link']).'">'.esc_html($author['link']).'</ext-link>';
					}
				
				$author_xml .= '
				</contrib>';
			}
		
		}
		$author_xml .= '
		</contrib-group>';
		
			return 
'<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE article SYSTEM "http://dtd.nlm.nih.gov/ncbi/kipling/kipling-jp3.dtd">
<article xmlns:xlink="http://www.w3.org/1999/xlink" article-type="research-article" xml:lang="en">
	<front>
		<journal-meta>
			'.$journal_id_xml.'
			'.$journal_title_xml.'
			'.$pub_issn_xml.'
			'.$publisher_xml.'
		</journal-meta>
		<article-meta>
			'.$doi_xml.'
			'.$category_xml.'
			'.$title_xml.'
			'.$author_xml.'
			'.$pub_date_xml.
//			<history>
//				<date date-type="submitted">
//					<day>12</day>
//					<month>12</month>
//					<year>2010</year>
//				</date>
//				<date date-type="submitted">
//					<day>12</day>
//					<month>12</month>
//					<year>2010</year>
//				</date>
//			</history>
'			'.$abstract_xml.'
			'.$tag_xml.'
			'.$funding_xml.'
		</article-meta>
	</front>';
	}

	private function xml_body($article) {
		$body = $article->post_content_filtered;		
		return 
'	<body>
		'.$body.'	
	</body>';
	}
	
	private function xml_acknoledgements($article) {
		$ack = get_post_meta($article->ID, '_anno_acknowledgements', true);
		$xml = '';
		if (!empty($ack)) {
			$xml =
'		<ack>
			<title>'._x('Acknowledgments', 'xml acknowledgments title', 'anno').'</title>
			<p>'.esc_html($ack).'</p>
		</ack>';
		}
		
		return $xml;
	}

	private function xml_appendices($article) {
		$appendices = get_post_meta($article->ID, '_anno_appendices', true);
		$xml = '';
		if (!empty($appendices) && is_array($appendices)) {
			$xml = 
'			<app-group>';

			foreach ($appendices as $appendix_key => $appendix) {
				if (!empty($appendix)) {
					$xml .='
				<app id="app'.($appendix_key + 1).'">
					<title>'.sprintf(_x('Appendix %s', 'xml appendix title', 'anno'), anno_index_alpha($appendix_key)).'</title>'
					.$appendix.'
				</app>';
				}
			}
			
			$xml .='
			</app-group>';
		}
			
		return $xml;
	}
	
	private function xml_references($article) {
		$references = get_post_meta($article->ID, '_anno_references', true);
		$xml = '';
		if (!empty($references) && is_array($references)) {
			$xml = 
'			<ref-list>
				<title>'._x('References', 'xml reference title', 'anno').'</title>';
		
			foreach ($references as $ref_key => $reference) {
				$ref_key_display = $ref_key + 1;
				if (isset($reference['doi']) && !empty($reference['doi'])) {
					$doi = '
						<pub-id pub-id-type="doi">'.esc_html($reference['doi']).'</pub-id>';
				}
				else {
					$doi = '';
				}
				
				if (isset($reference['pmid']) && !empty($reference['pmid'])) {
					$pmid = '
						<pub-id pub-id-type="pmid">'.esc_html($reference['pmid']).'</pub-id>';
				}
				else {
					$pmid = '';
				}
				
				if (isset($reference['text']) && !empty($reference['text'])) {
					$text = esc_html($reference['text']);
				}
				else {
					$text = '';
				}
					
				if (isset($reference['link']) && !empty($reference['link'])) {
					$link = ' xlink:href="'.esc_url($reference['link']).'"';
				}
				else {
					$link = '';
				}
				
				$xml .='
			<ref id="'.$ref_key_display.'">
				<label>'.$ref_key_display.'</label>
				<mixed-citation'.$link.'>'.$text.'
					'.$doi.$pmid.'
				</mixed-citation>
			</ref>';

			}
		
			$xml .='
		</ref-list>';
		}
		
		return $xml;
		
	}
	
	private function xml_back($article) {
		return 
'	<back>
'.$this->xml_acknoledgements($article).'
'.$this->xml_appendices($article).'
'.$this->xml_references($article).'
	</back>
'.$this->xml_responses($article).'
</article>';	
	}
	
	private function xml_responses($article) {
		$comments = get_comments(array('post_id' => $article->ID));
		$comment_xml = '';
		if (!empty($comments) && is_array($comments)) {
			foreach ($comments as $comment) {
				$comment_xml .= '
	<response response-type="reply">
		<front-stub>'
			.$this->xml_comment_author($comment)
			.$this->xml_pubdate($comment->comment_date).'	
		</front-stub>
		<body>
			<p>'
				.esc_html($comment->comment_content).
'			</p>
		</body>
	</response>';
			}
		}
		return $comment_xml;
	}
	
	private function xml_comment_author($comment) {
		$author_xml = '<contrib-group>
				<contrib>';
		if (!empty($comment->user_id)) {
			$user = get_userdata($comment->user_id);
			$author_xml .= '
					<name>';
					
			if (!empty($user->last_name)) {
				$author_xml .= '
						<surname>'.esc_html($user->last_name).'</surname>';
			}
			else {
				$author_xml .= '
						<surname>'.esc_html($user->display_name).'</surname>';
			}
			if (!empty($user->first_name)) {
				$author_xml .= '
						<given-names>'.esc_html($user->first_name).'</given-names>';
			}
			
			$prefix = get_user_meta($user->ID, '_anno_prefix', true);
			if (!empty($prefix)) {
				$author_xml .= '
						<prefix>'.esc_html($prefix).'</prefix>';
			}
			
			$suffix = get_user_meta($user->ID, '_anno_suffix', true);
			if (!empty($suffix)) {
				$author_xml .= '
						<suffix>'.esc_html($suffix).'</suffix>';
			}
			$author_xml .= '
					</name>';
			
			// @TODO TDB whether or not to include email							
//			if (!empty($user->user_email)) {
//				$author_xml .= '
//				<email>'.esc_html($user->user_email).'</email>';
//			}
			
			$degrees = get_user_meta($user->ID, '_anno_degrees', true);
			if (!empty($degrees)) {
				$author_xml .= '
					<degrees>'.esc_html($degrees).'</degrees>';
			}

			$affiliation = get_user_meta($user->ID, '_anno_affiliation', true);
			if (!empty($affilitation)) {
				$author_xml .= '
					<aff>'.esc_html($user->last_name).'</aff>';
			}

			$bio = $user->user_description;
			if (!empty($bio)) {
				$author_xml .= '
					<bio>'.wpautop(esc_html($bio)).'</bio>';
			}

			$link = $user->user_url;
			if (!empty($link)) {
				$author_xml .= '
					<ext-link ext-link-type="uri" xlink:href="'.esc_url($link).'">'.esc_html($link).'</ext-link>';
			}
		}
		else {
			if (!empty($comment->commment_author)) {
				$author_xml .= '
					<name>
						<surname>'.esc_html($comment->comment_author).'</surname>';
				$link = $comment->comment_author_url;
				if (!empty($link)) {
					$author_xml .= '
						<ext-link ext-link-type="uri" xlink:href="'.esc_url($link).'">'.esc_html($link).'</ext-link>';
				}
				$author_xml .= '
					</name>';
			}
		}
		$author_xml .= '
				</contrib>
			</contrib-group>';
		
		return $author_xml;
	}
	
	private function xml_pubdate($pub_date) {
		if (!empty($pub_date)) {
			$pub_date = strtotime($pub_date);
			$pub_date_xml = '
				<pub-date pub-type="ppub">
					<day>'.date('j', $pub_date).'</day>
					<month>'.date('n', $pub_date).'</month>
					<year>'.date('Y', $pub_date).'</year>
				</pub-date>';
		}
		else {
			$pub_date_xml = '';
		}
		
		return $pub_date_xml;
	}
	
	/**
	 * Generates the XML download URL for a post
	 *
	 * @param int $id 
	 * @return string
	 */
	public function get_download_url($id = null) {
		// Default to the global $post
		if (is_null($id)) {
			global $post;
			if (empty($post)) {
				$this->log('There is no global $post in scope.');
				return false;
			}
			$id = $post->ID;
		}
		
		// Build our download link
		$permalink = get_permalink($id);
		
		/* Handle pretty and ugly permalinks. Need at least this stripos 
		function, b/c initial drafts don't get a pretty permalink till 
		published, so even if the setting is pretty permalinks the draft's 
		permalink is going to be ugly. */
		if (stripos($permalink, 'post_type=article') || get_option('permalink_structure') == '') {
			// Ugly permalinks
			$link = add_query_arg(array('xml' => 'true'), $permalink);
			if (is_preview()) {
				$link = add_query_arg(array('preview' => 'true'), $link);
				if (isset($_GET['preview_id'])) {
					$link = add_query_arg(array('autosave' => 'true'), $link);
				}
			}
		}
		else {
			// Pretty permalinks
			$link = trailingslashit($permalink).'xml/';
			if (is_preview()) {
				$link = trailingslashit($link).'preview/';
				if (isset($_GET['preview_id'])) {
					$link = add_query_arg(array('autosave' => 'true'), $link);
				}
			}
		}
		return $link;
	}
	
	/**
	 * Conditionally logs messages to the error log
	 *
	 * @param string $msg 
	 * @return void
	 */
	private function log($msg) {
		if ($this->debug) {
			error_log($msg);
		}
	}	
}
Anno_XML_Download::i()->add_actions();
	

/**
 * Get the XML download link for a post
 *
 * @param int $id 
 * @return string
 */
function anno_xml_download_url($id = null) {
	return Anno_XML_Download::i()->get_download_url($id);
}

?>