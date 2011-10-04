<?php

if (!defined('WP_LOAD_IMPORTERS'))
	return;

if ( !class_exists('Knol_Import')) {
	$class_knol_importer = 	trailingslashit(TEMPLATEPATH).'plugins/knol-importer/knol-importer.php';
	if (file_exists($class_knol_importer)) {
		require $class_knol_importer;
	}
}

if (!class_exists('DTD_Impoter')) {
	class DTD_Import extends Knol_Import {

	var $import_slug = 'kipling_dtd_xml';

	function DTD_Import() { /* Nothing */ }

	/**
	 * If fetching attachments is enabled then attempt to create a new attachment
	 *
	 * @param array $post Attachment post details from WXR
	 * @param string $url URL to fetch attachment from
	 * @return int|WP_Error Post ID on success, WP_Error otherwise
	 */
	function process_attachment( $post, $url ) {
		if (!$this->fetch_attachments)
			return new WP_Error('attachment_processing_error',
				__('Fetching attachments is not enabled', 'anno'));

		// if the URL is absolute, but does not contain address, then upload it assuming base_site_url
		if (preg_match( '|^/[\w\W]+$|', $url))
			$url = rtrim( $this->base_url, '/' ) . $url;

		$upload = $this->fetch_remote_file( $url, $post );
		if ( is_wp_error( $upload ) )
			return $upload;

		if ( $info = wp_check_filetype( $upload['file'] ) )
			$post['post_mime_type'] = $info['type'];
		else
			return new WP_Error( 'attachment_processing_error', __('Invalid file type', 'anno') );

		$post['guid'] = $upload['url'];

		// as per wp-admin/includes/upload.php
		$post_id = wp_insert_attachment( $post, $upload['file'] );
		wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $upload['file'] ) );

		// remap resized image URLs, works by stripping the extension and remapping the URL stub.
		if ( preg_match( '!^image/!', $info['type'] ) ) {
			$parts = pathinfo( $url );
			$name = basename( $parts['basename'], ".{$parts['extension']}" ); // PATHINFO_FILENAME in PHP 5.2

			$parts_new = pathinfo( $upload['url'] );
			$name_new = basename( $parts_new['basename'], ".{$parts_new['extension']}" );

			$this->url_remap[$parts['dirname'] . '/' . $name] = $parts_new['dirname'] . '/' . $name_new;
		}

		return $post_id;
	}

	function import( $file ) {
		add_filter( 'import_post_meta_key', array( $this, 'is_valid_meta_key' ) );
		add_filter( 'http_request_timeout', array( &$this, 'bump_request_timeout' ) );

		$this->import_start( $file );

		wp_suspend_cache_invalidation( true );
	//	$this->process_categories();
	//	$this->process_tags();
	//	$this->process_terms();
		$this->process_posts();
		wp_suspend_cache_invalidation( false );

		// update incorrect/missing information in the DB
		$this->backfill_parents();
		$this->backfill_attachment_urls();
		$this->remap_featured_images();

		$this->import_end(); 
	}

	/**
	 * Parses the WXR file and prepares us for the task of processing parsed data
	 *
	 * @param string $file Path to the WXR file for importing
	 */
	function import_start( $file ) {
		if ( ! is_file($file) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'anno' ) . '</strong><br />';
			echo __( 'The file does not exist, please try again.', 'anno' ) . '</p>';
			$this->footer();
			die();
		}

		$import_data = $this->parse( $file );
		error_log(print_r($import_data,1));
		if ( is_wp_error( $import_data ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'anno' ) . '</strong><br />';
			echo esc_html( $import_data->get_error_message() ) . '</p>';
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
	register_importer('kipling_dtd_xml', 'Kipling DTD XML', __('Import <strong>articles, keywords, subjects and users</strong> from a Google Knol WXR export file.', 'anno'), array( $GLOBALS['dtd_import'], 'dispatch') );
}
add_action('admin_init', 'anno_dtd_importer_init');

?>