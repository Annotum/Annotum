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


// add_filter('Anno_PDF_Download_debug', '__return_true');

class Anno_PDF_Download {
	
	static $instance;

	private function __construct() {
		$this->i18n = 'anno';
		$this->ver = defined('ANNO_VER') ? ANNO_VER : '1.0';
	}

	public function i() {
		if (!isset(self::$instance)) {
			self::$instance = new Anno_PDF_Download;
		}
		return self::$instance;
	}
	
	public function setup_filterable_props() {
		$this->debug = apply_filters(__CLASS__.'_debug', false);
		$this->memory_limit = apply_filters(__CLASS__.'_memory_limit', '100M'); // only gets set when downloading the PDF
		$this->template_path = apply_filters(__CLASS__.'_template_path', 'templates/default.php');
	}
	
	public function add_actions() {
		// Allow plugins to change various properties of our plugin
		add_action('init', array($this, 'setup_filterable_props'));
		
		// Run late to make sure all custom post types are registered
		add_action('init', array($this, 'setup_permalinks'), 20);
		
		// Handle various requests
		add_action('wp', array($this, 'request_handler'));
	}
	
	protected function set_pdfmaker_configs() {
		// Define various DOMPDF Settings (typically defined in dompdf_config.custom.inc.php)
		//define("DOMPDF_TEMP_DIR", "/tmp");
		//define("DOMPDF_CHROOT", DOMPDF_DIR);
		define("DOMPDF_UNICODE_ENABLED", true);
		//define("TTF2AFM", "C:/Program Files (x86)/GnuWin32/bin/ttf2pt1.exe");
		//define("DOMPDF_PDF_BACKEND", "PDFLib");
		define("DOMPDF_DEFAULT_MEDIA_TYPE", "print");
		define("DOMPDF_DEFAULT_PAPER_SIZE", "letter");
		//define("DOMPDF_DEFAULT_FONT", "serif");
		//define("DOMPDF_DPI", 72);
		define("DOMPDF_ENABLE_PHP", true);
		define("DOMPDF_ENABLE_REMOTE", true);
		//define("DOMPDF_ENABLE_CSS_FLOAT", true);
		//define("DOMPDF_ENABLE_JAVASCRIPT", false);
		//define("DEBUGPNG", true);
		//define("DEBUGKEEPTEMP", true);
		//define("DEBUGCSS", true);
		//define("DEBUG_LAYOUT", true);
		//define("DEBUG_LAYOUT_LINES", false);
		//define("DEBUG_LAYOUT_BLOCKS", false);
		//define("DEBUG_LAYOUT_INLINE", false);
		//define("DOMPDF_FONT_HEIGHT_RATIO", 1.0);
		//define("DEBUG_LAYOUT_PADDINGBOX", false);
		//define("DOMPDF_LOG_OUTPUT_FILE", DOMPDF_FONT_DIR."log.htm");
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
		// Allows us to use get_query_var('pdf') later on
		$wp->add_query_var('pdf');
		
		// Tells WP that a request like /articles/my-article-name/pdf/ is valid
		add_rewrite_rule($article_post_type->rewrite['slug'].'/([^/]+)/pdf/?$', 'index.php?articles=$matches[1]&pdf=true', 'top');
	}
	
	
	/**
	 * Takes action when various query_vars are present
	 * (currently just 'PDF' query var)
	 *
	 * @return void
	 */
	public function request_handler() {
		if (get_query_var('pdf')) {
			// Increase our memory limit for this request
			ini_set('memory_limit', $this->memory_limit);
			
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
			
			// Load whatever class will make the PDF
			if (!$this->load_pdfmaker()) {
				$this->log('Couldn\'t load the $pdfmaker');
				$this->generic_die();
			}
			
			// Get our HTML locally
			if (!$this->get_html($id)) {
				$this->log('Could not find HTML post_meta for post ID: '.$id);
				$this->generic_die();
			}
			
			// If we force it to the screen @TODO remove once styling's OK in PDF
			if (isset($_GET['screen'])) {
				die($this->html);
			}
			
			// Get the PDF title (currently just the Article title)
			if (!$this->generate_pdf_title($id)) {
				$this->log('Couldn\'t generate the PDF title for post ID: '.$id);
				$this->generic_die();
			}
			
			// Generate the PDF
			try {
				$this->generate_pdf();
			}
			catch (Exception $e) {
				$this->log('Error Creating PDF: '.$e->getMessage());
				$this->generic_die();
			}
			
			// Set our error display back to what it was.
			if (!$this->debug) {
				ini_set('display_errors', $display_errors);
			}
		}
	}
	
	
	/**
	 * Loads the Class that will generate the PDF.  This is one place where we
	 * should be able to change the PDF maker class
	 *
	 * @return bool
	 */
	private function load_pdfmaker() {
		$this->set_pdfmaker_configs();
		
		// Requre the DOMPDF class
		require_once 'lib/dompdf/dompdf_config.inc.php';
		
		// Store our class to a property
		$this->pdfmaker = new DOMPDF;
		
		return is_a($this->pdfmaker, 'DOMPDF');
	}
	
	
	/**
	 * Gets the HTML associated with a post, spec'd by $id
	 *
	 * @param int $id - Post ID
	 * @return bool - Whether there was HTML or not
	 */
	private function get_html($id) {
		// Setup our global $post stuff
		global $post;
		$post = get_post($id);
		setup_postdata($post);
		
		// Output the template
		add_filter('anno_author_html', array($this, 'author_html'), 10, 2);
		ob_start();
		remove_filter('the_content','wpautop');
		include $this->template_path;
		$this->html = ob_get_clean();
		remove_filter('anno_author_html', array($this, 'author_html'), 10, 2);
		// Reset our global $post
		wp_reset_postdata();
		
		// Replace the HTML5 tags with HTML4
		$this->html = $this->htmlcleanup($this->html);
		return !empty($this->html);
	}
	

	/**
	 * Cleans up tags for better domPDF rendering
	 *
	 * @param string $html 
	 * @return string - replaced HTML
	 */
	private function htmlcleanup($html) {
		$html = $this->html4ify($html);
		$html = $this->fixtags($html);

		return $html;
	}
	
	/**
	 * Replaces tags with others using regex for improved rendering in domPDF
	 *
	 * @param string $html 
	 * @return string - replaced HTML
	 */
	private function fixtags($html) {
		// Pattern => replacemant
		$replacements = array(
			'/(<td.*?>)/i' => '${1}<p>', // Addresses http://code.google.com/p/dompdf/issues/detail?id=238
			'/(<\/td>)/i' => '</p>${1}',
		);

		foreach ($replacements as $pattern => $replacement) {
			$html = preg_replace($pattern, $replacement, $html);
		}

		return $html;
	}

	/**
	 * Replaces the HTML5 elements defined in the $replacements array
	 * with an HTML4 element.
	 *
	 * @param string $html 
	 * @return string - replaced HTML
	 */
	private function html4ify($html) {
		$replacements = array(
			'<article' 		=> '<div',
			'</article'		=> '</div',

			'<header' 		=> '<div',
			'</header' 		=> '</div',

			'<footer' 		=> '<div',
			'</footer' 		=> '</div',

			//'<sup'			=> '<span class="sup"',
			//'</sup'			=> '</span',

			//'<sub'			=> '<span class="sub"',
			//'</sub'			=> '</span',

			'<figure'		=> '<div',
			'</figure' 		=> '</div',

			'<figcaption' 	=> '<div class="figcaption"',
			'</figcaption' 	=> '</div',
			
			//'<caption' 		=> '<div class="caption"',
			//'</caption' 	=> '</div',

			'<section'		=> '<div',
			'</section'		=> '</div',
			
			'<mark'			=> '<span',
			'</mark'		=> '</span',
			
			'<time'			=> '<span',
			'</time'		=> '</span',
		);
		return str_replace(array_keys($replacements), array_values($replacements), $html);
	}
	
	
	/**
	 * Creates the PDF for download.  This is another place that will need 
	 * modification to change which underlying class that creates the PDF.
	 *
	 * @return void
	 */
	private function generate_pdf() {
		$this->pdfmaker->load_html($this->html);
		$this->pdfmaker->render();
		$this->pdfmaker->stream($this->pdf_title, array(
			'compress' 		=> 1,
			'Attachment' 	=> 1,
		));
	}
	
	
	/**
	 * Gets the post IDs 
	 *
	 * @param string $id 
	 * @return void
	 */
	private function generate_pdf_title($id) {
		$this->pdf_title = get_the_title($id);
		
		if (empty($this->pdf_title)) {
			return false;
		}

		$this->pdf_title.= '.pdf';
		return true;
	}
	
	
	/**
	 * Generates the PDF download URL for a post
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
			$link = add_query_arg(array('pdf' => 'true'), $permalink);
		}
		else {
			// Pretty permalinks
			$link = trailingslashit($permalink).'pdf/';
		}
		return $link;
	}
	
	
	/**
	 * Provide a nice die message for failed PDF downloads
	 *
	 * @return void
	 */
	private function generic_die() {
		wp_die(__('There was an issue creating the PDF for your article, please try again later', $this->i18n));
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

	/**
	 * Filter author html to be appropriate for PDF output
	 *
	 * @param string $html Default HTML
	 * @param array $authors Authors data array
	 **/
	public function author_html($html, $authors) {
		$out = '';
		$author_markup_arr = array();
		$institutions = array();
		foreach ($authors as $author_data) {
			// Use a user's website if there isn't a user object with associated id (imported user snapshots)
			// Also check to see if this is a string ID or int val id, knol_id vs wp_id
			if ($author_data['id'] == intval($author_data['id'])) {
				$posts_url = get_author_posts_url($author_data['id']);
				$posts_url = $posts_url == home_url('/author/') ? $author_data['link'] : $posts_url;
			}
			else {
				$posts_url = '';
			}
			$prefix_markup = empty($author_data['prefix']) ? '' : esc_html($author_data['prefix'].' ');
			$suffix_markup = empty($author_data['suffix']) ? '' : esc_html(' '.$author_data['suffix']);

			if ($author_data['first_name'] && $author_data['last_name']) {
				$fn = empty($posts_url) ? '<span class="name">' : '<a href="'.esc_url($posts_url).'" class="name">';				

				$fn .= $prefix_markup.esc_html($author_data['first_name']).' '.esc_html($author_data['last_name']).$suffix_markup;

				$fn .= $posts_url ? '</a>' : '</span>';
			}
			else {
				$fn = $posts_url ? '<a href="'.esc_url($posts_url).'" class="name">' : '<span class="name">';

				$fn .= $prefix_markup.esc_html($author_data['display_name']).$suffix_markup;

				$fn .= $posts_url ? '</a>' : '</span>';
			}
			if (!empty($author_data['institution'])) {
				$new = true;
				// Loop instead of foreach for case comparison
				foreach ($institutions as $key => $institution) {
					if (strcasecmp($author_data['institution'], $institution) === 0) {
						$new = false;
						break;
					}
				}

				if ($new) {
					$institutions[] = $author_data['institution'];
					$fn .= '<sup>'.count($institutions).'</sup>';
				}
				else {
					// Already in there
					$fn .= '<sup>'.esc_html($key + 1).'</sup>';					
				}
			}

			$author_markup_arr[] = $fn;
		}
		
		$institutions_out = '';
		if (!empty($institutions)) {
			$institutions_arr = array();
			foreach ($institutions as $key => $value) {
				$institutions_arr[] = '<strong>'.($key + 1).'</strong> '.esc_html($value);
			}
			$institutions_out = '<div id="institutions">'.implode(', ', $institutions_arr).'</div>';
		}

		$authors_out = implode(', ', $author_markup_arr);
		return $authors_out.$institutions_out;

		 
	}

	/**
	 * Header markup for pdfs
	**/
	public static function header_markup() {
		$header_img = get_header_image();
		if (empty($header_img)) {
			$journal_name = cfct_get_option('journal_name');
			$out = empty($journal_name) ? '' : $journal_name.'. ';
			$section_name = get_bloginfo('name');
			$out .= empty($section_name) ? '' : $section_name.'.';
		}
		else {
			$out = '<img src="'.esc_url($header_img).'" />';
		}
		return $out;
	}

	/**
	 * Footer markup for pdfs
	**/ 
	public static function footer_markup() {
		// DOMpdf markup 
		// @see http://code.google.com/p/dompdf/wiki/Usage
		$out = '
		<script type="text/php"> 
			if (isset($pdf)) {
				$font = Font_Metrics::get_font("liberation", "normal");
			    $size = 11;
			    $y = $pdf->get_height() - 30;
			    $x = $pdf->get_width() - 305 - Font_Metrics::get_text_width("1/1", $font, $size);
			    $pdf->page_text($x, $y, "{PAGE_NUM}", $font, $size);
			    $pdf->page_text(25, $y, get_bloginfo(\'name\'), $font, $size);
			}
		</script>';
		return $out;
	}
}
Anno_PDF_Download::i()->add_actions();


/**
 * Get the PDF download link for a post
 *
 * @param int $id 
 * @return string
 */
function anno_pdf_download_url($id = null) {
	return Anno_PDF_Download::i()->get_download_url($id);
}
?>