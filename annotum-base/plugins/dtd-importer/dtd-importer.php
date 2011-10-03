<?php

if ( ! class_exists( 'Knol_Import' ) ) {
	$class_knol_importer = 	trailingslashit(TEMPLATEPATH).'plugins/knol-importer/knol-importer.php';
	if ( file_exists( $class_knol_importer ) )
		require $class_knol_importer;
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
		if ( ! $this->fetch_attachments )
			return new WP_Error( 'attachment_processing_error',
				__( 'Fetching attachments is not enabled', 'anno' ) );

		// if the URL is absolute, but does not contain address, then upload it assuming base_site_url
		if ( preg_match( '|^/[\w\W]+$|', $url ) )
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
	
	/**
	 * Parse a WXR file
	 *
	 * @param string $file Path to WXR file for parsing
	 * @return array Information gathered from the WXR file
	 */
	function parse( $file ) {
		$parser = new Knol_WXR_Parser();
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

function anno_dtd_importer_init() {
	/**
	 * Knol Importer object for registering the import callback
	 * @global DTD_Import $dtd_import
	 */
	$GLOBALS['dtd_import'] = new DTD_Import();
	register_importer( 'kipling_dtd_xml', 'Kipling DTD XML', __('Import <strong>articles, keywords, subjects and users</strong> from a Google Knol WXR export file.', 'anno'), array( $GLOBALS['knol_import'], 'dispatch' ) );
}
add_action( 'admin_init', 'anno_knol_importer_init' );

?>