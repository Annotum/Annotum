<?php 
/*
Plugin Name: Annotum PDF Download
Plugin URI: 
Description: Enables the PDF download of any article
Version: 1.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/


// @TODO turn off the debug 
add_filter('Anno_PDF_Download_debug', '__return_true');


class Anno_PDF_Download {
	
	static $instance;

	private function __construct() {
		/* Define what our "action" is that we'll 
		listen for in our request handlers */
		$this->action = 'anno_pdf_download_action';
		$this->i18n = 'anno';
	}

	public function i() {
		if (!isset(self::$instance)) {
			self::$instance = new Anno_PDF_Download;
		}
		return self::$instance;
	}
	
	public function setup_filterable_props() {
		$this->debug = apply_filters(__CLASS__.'_debug', false);
	}
	
	public function add_actions() {
		add_action('init', array($this, 'setup_filterable_props'));
		add_action('init', array($this, 'request_handler'));
	}
	
	protected function set_pdfmaker_configs() {
		// Define various DOMPDF Settings (typically defined in dompdf_config.custom.inc.php)
		//define("DOMPDF_TEMP_DIR", "/tmp");
		//define("DOMPDF_CHROOT", DOMPDF_DIR);
		//define("DOMPDF_UNICODE_ENABLED", false);
		//define("TTF2AFM", "C:/Program Files (x86)/GnuWin32/bin/ttf2pt1.exe");
		//define("DOMPDF_PDF_BACKEND", "PDFLib");
		//define("DOMPDF_DEFAULT_MEDIA_TYPE", "print");
		//define("DOMPDF_DEFAULT_PAPER_SIZE", "letter");
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
	
	public function request_handler() {
		if (isset($_GET[$this->action])) {
			switch ($_GET[$this->action]) {
				case 'download_pdf':
					// Make sure we have an article first
					if (empty($_GET['article'])) {
						wp_die(__('Required article first.', $this->i18n));
					}
					
					// Sanitize our article ID
					$id = intval($_GET['article']);
					
					// Validate our nonce
					if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'download_pdf')) {
						wp_die(__('Please visit <a href="'.get_the_permalink($id).'">the article</a> and click the download link.', $this->i18n));
					}
					
					// Load whatever class will make the PDF
					if (!$this->load_pdfmaker()) {
						$this->log('Couldn\'t load the $pdfmaker');
						$this->generic_die();
					}
					
					// @TODO - Determine which stylesheets get brought in 
					// Our stylesheets don't get brought in till 'wp', not 'init, so we need to register them anyways
					anno_assets();
					current_assets();
					
					// Get our HTML locally
					if (!$this->get_html($id)) {
						$this->log('Could not find HTML post_meta for post ID: '.$id);
						$this->generic_die();
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
					exit;
					break;
				default:
					break;
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
		// Assign post content
		if (!$this->get_post_content($id)) {
			return false;
		}
		
		ob_start();
		include 'templates/default.php';
		$this->html = ob_get_clean();
		return !empty($this->html);
	}
	
	
	/**
	 * Returns the content of the post...in HTML from the post_meta
	 *
	 * @param int $id 
	 * @return bool - whether there was HTML or not
	 */
	private function get_post_content($id) {
		// @TODO This post_meta KEY is probably going to change...
		global $anno_html_post_meta_key;
		$anno_html_post_meta_key = '_anno_html';
		$this->post_html = get_post_meta($id, $anno_html_post_meta_key, true);
		
		$this->post_html = 'TESTING TILL POST META GETS SAVED WITH HTML';
		return !empty($this->post_html);
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
		return !empty($this->pdf_title);
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
		
		// Build our URL args
		$url_args = array(
			$this->action 	=> 'download_pdf',
			'article' 		=> intval($id),
		);
		
		return wp_nonce_url(add_query_arg($url_args, home_url()), 'download_pdf');
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