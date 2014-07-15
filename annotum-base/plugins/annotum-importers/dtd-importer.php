<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2011 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * Based on code found in WordPress Importer plugin
 */

function dtd_importer_enqueue() {
	if (isset($_REQUEST['import']) && $_REQUEST['import'] == 'kipling_dtd_xml') {
		wp_enqueue_style('anno-importer', trailingslashit(get_template_directory_uri()) . 'plugins/annotum-importers/importer.css');
	}
}
add_action('admin_print_styles', 'dtd_importer_enqueue');

function add_xml_import_link_to_admin_menu() {
	/**
	 * Add an entry to the Article admin menu to import XML (Kipling) files
	 */
	add_submenu_page( 'edit.php?post_type=article', _x('XML Import', 'Admin menu title', 'anno'), _x('XML Import', 'Admin menu title', 'anno'), 'edit_posts', 'admin.php?import=kipling_dtd_xml');
	}

add_action('admin_menu', 'add_xml_import_link_to_admin_menu');

if (!defined('WP_LOAD_IMPORTERS'))
	return;

if ( !class_exists('Knol_Import')) {
	$class_knol_importer = 	trailingslashit(TEMPLATEPATH).'plugins/annotum-importers/knol-importer.php';
	if (file_exists($class_knol_importer)) {
		require $class_knol_importer;
	}
}

if (!class_exists('DTD_Importer')) {
class DTD_Import extends Knol_Import {

	var $import_slug = 'kipling_dtd_xml';

	// Author meta data is stored here. We don't get this data from Knols
	var $authors_meta = array();

	function DTD_Import() {}

	/**
	 * Parses the XML file and prepares us for the task of processing parsed data
	 *
	 * @param string $file Path to the WXR file for importing
	 */
	function import_start( $file ) {
		if (!is_file($file)) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'anno' ) . '</strong><br />';
			echo __( 'The file does not exist, please try again.', 'anno' ) . '</p>';
			$this->footer();
			die();
		}

		$import_data = $this->parse( $file );

		if ( is_wp_error( $import_data ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'anno' ) . '</strong><br />';
			echo esc_html( $import_data->get_error_message() ) . '</p>';
			$this->footer();
			die();
		}
		// Validation error likely
		if (isset($import_data['status']) && $import_data['status'] == 'error') {
			echo '<h3>'.__('There were the following errors while trying to validate your XML. Please correct them and try again.', 'anno').'</h3>';
			foreach ($import_data['errors'] as $error) {
				$error_lines[] = $error['line'];
				echo '<div class="error">'.$error['fullMessage'].'.</div>';
			}
			$this->output_XML($import_data['content'], $error_lines);
			$this->footer();
			die();
		}

		$this->version = $import_data['version'];
		$this->get_authors_from_import( $import_data );
		$this->posts = $import_data['posts'];
		$this->terms = $import_data['terms'];
		$this->categories = $import_data['categories'];
		$this->tags = $import_data['tags'];
		$this->base_url = esc_url( $import_data['base_url'] );

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );

		do_action( 'import_start' );
	}

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
		$parser = new Kipling_DTD_Parser($file);
		return $parser->parse($file);
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
		$url = 'admin.php?import=kipling_dtd_xml';
		if ($this->_filesystem_init($url)) {
			echo '<div class="narrow">';
			echo '<p>'.__( 'Howdy! Upload your Kipling DTD XML file and we&#8217;ll import the articles, keywords, subjects, and users into this site.', 'anno' ).'</p>';
			echo '<p>'.__( 'Choose a Kipling DTD XML (.xml) file to upload, then click Upload file and import.', 'anno' ).'</p>';
			$this->import_upload_form('admin.php?import=kipling_dtd_xml&amp;step=1');
			echo '</div>';
		}
	}

	function import_upload_form($action) {
		$bytes = apply_filters('import_upload_size_limit', wp_max_upload_size());
		$size = size_format( $bytes );
		$upload_dir = wp_upload_dir();
		if (!empty($upload_dir['error'])) :
			?>
			<div class="error"><p><?php _e('Before you can upload your import file, you will need to fix the following error:', 'anno'); ?></p>
			<p><strong><?php echo $upload_dir['error']; ?></strong></p></div>
			<?php
		else :
			?>
			<form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form" action="<?php echo esc_url( wp_nonce_url( $action, 'import-upload' ) ); ?>">
				<p>
					<label for="upload"><?php _e('Choose a file from your computer:', 'anno'); ?></label> (<?php printf(__('Maximum size: %s', 'anno'), $size); ?>)
					<input type="file" id="upload" name="import" size="25" />
					<input type="hidden" name="action" value="save" />
					<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
				</p>
				<?php echo $this->_filesystem_hidden_fields(); ?>
				<?php submit_button( __('Upload file and import', 'anno'), 'button' ); ?>
			</form>
			<?php
		endif;
	}

	function output_XML($xml, $error_lines = array()) {
		$xml_lines = explode("\n", $xml);
		$line_number = 0;
		foreach ($xml_lines as $xml_line) {
			if ($line_number == 0) {
				$line_number++;
				continue;
			}
			$class = 'dtd-importer-xml-line';
			if (in_array($line_number - 1, $error_lines)) {
				$class .= ' dtdt-importer-line-error';
			}
			echo '<div class="'.$class.'"><span class="dtd-importer-line-number">'.$line_number.'.</span>'.esc_html($xml_line).'</div>';
			$line_number++;
		}
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
