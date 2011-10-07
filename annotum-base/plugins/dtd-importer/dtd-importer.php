<?php

if (!defined('WP_LOAD_IMPORTERS'))
	return;

if ( !class_exists('Knol_Import')) {
	$class_knol_importer = 	trailingslashit(TEMPLATEPATH).'plugins/knol-importer/knol-importer.php';
	if (file_exists($class_knol_importer)) {
		require $class_knol_importer;
	}
}

if (!class_exists('DTD_Importer')) {
	class DTD_Import extends Knol_Import {

	var $import_slug = 'kipling_dtd_xml';
	
	// Author meta data is stored here. We don't get this data from Knols
	var $authors_meta = array();
			
	function DTD_Import() { /* Nothing */ }
	
	/**
	 * Retrieve authors and author meta from parse XML file. Process meta data.
	 *
	 * @param array $import_data Data returned by the parser
	 */
	function get_authors_from_import($import_data) {
		// No fallback options available
		if (!empty($import_data['authors'])) {
			$this->authors = $import_data['authors'];
		}

		if (!empty($import_data['authors_meta'])) {
			$this->authors_meta = $import_data['authors_meta'];
			$this->process_user_meta();
		}	
	}

	/**
	 * Process post meta for created users
	 * 
	 */ 
	function process_user_meta() {
		// Only perform on newly created users
		foreach ($this->created_users as $old_id => $wp_id) {
			if (!empty($this->authors_meta[$old_id]) && is_array($this->authors_meta[$old_id])) {
				foreach ($this->authors_meta[$old_id] as $key => $value) {
					$value = trim($value);
					if (!empty($value)) {
						if ($key == 'bio') {
							$key == 'descriptions';
						}
						else {
							$key = '_anno_'.$key;
						}
						// We only have single rows per key
						update_user_meta($wp_id, $key, $value);
					}
				}
			}
		}
	}

	/**
	 * Parse a WXR file
	 *
	 * @param string $file Path to WXR file for parsing
	 * @return array Information gathered from the WXR file
	 */
	function parse($file) {
		$parser = new Kipling_DTD_Parser();
		return $parser->parse( $file );
	}

	// Display import page title
	function header() {
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>' . __( 'Kipling DTD XML Import', 'anno' ) . '</h2>';
	}

	// Close div.wrap
	function footer() {
		echo '</div>';
	}

	/**
	 * Display introductory text and file upload form
	 */
	function greet() {
		echo '<div class="narrow">';
		echo '<p>'.__( 'Howdy! Upload your Kipling DTD XML file and we&#8217;ll import the articles, keywords, subjects, and users into this site.', 'anno' ).'</p>';
		echo '<p>'.__( 'Choose a Kipling DTD XML (.xml) file to upload, then click Upload file and import.', 'anno' ).'</p>';
		wp_import_upload_form( 'admin.php?import=kipling_dtd_xml&amp;step=1' );
		echo '</div>';
	}
}
}

function anno_dtd_importer_init() {
	/**
	 * Knol Importer object for registering the import callback
	 * @global DTD_Import $dtd_import
	 */
	$GLOBALS['dtd_import'] = new DTD_Import();
	register_importer('kipling_dtd_xml', 'Kipling DTD XML', __('Import <strong>articles, keywords, subjects and users</strong> from a Kipling DTD XML file.', 'anno'), array( $GLOBALS['dtd_import'], 'dispatch') );
}
add_action('admin_init', 'anno_dtd_importer_init');

?>