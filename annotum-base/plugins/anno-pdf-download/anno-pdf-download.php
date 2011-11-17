<?php 
/*
Plugin Name: Annotum PDF Download
Plugin URI: 
Description: Enables the PDF download of any article
Version: 1.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
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
		//define("DOMPDF_UNICODE_ENABLED", false);
		//define("TTF2AFM", "C:/Program Files (x86)/GnuWin32/bin/ttf2pt1.exe");
		//define("DOMPDF_PDF_BACKEND", "PDFLib");
		define("DOMPDF_DEFAULT_MEDIA_TYPE", "print");
		define("DOMPDF_DEFAULT_PAPER_SIZE", "letter");
		//define("DOMPDF_DEFAULT_FONT", "serif");
		//define("DOMPDF_DPI", 72);
		//define("DOMPDF_ENABLE_PHP", true);
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
		ob_start();
		include $this->template_path;
		$this->html = ob_get_clean();
		
		// Reset our global $post
		wp_reset_postdata();
		
		// Replace the HTML5 tags with HTML4
		$this->html = $this->html4ify($this->html);
		
		return !empty($this->html);
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

			'<sup'			=> '<span class="sup"',
			'</sup'			=> '</span',

			'<sub'			=> '<span class="sub"',
			'</sub'			=> '</span',

			'<figure'		=> '<div',
			'</figure' 		=> '</div',

			'<figcaption' 	=> '<div class="figcaption"',
			'</figcaption' 	=> '</div',
			
			'<caption' 		=> '<div class="caption"',
			'</caption' 	=> '</div',

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