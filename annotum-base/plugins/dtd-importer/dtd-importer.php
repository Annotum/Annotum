<?php

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require $class_wp_importer;
}

if (!class_exists('DTD_Impoter')) {
	
	class DTD_Import extends WP_Importer {

	var $id; // File attachment ID

	var $authors = array();
	var $posts = array();
	var $terms = array();
	var $categories = array();
	var $tags = array();
	var $base_url = '';

	// mappings from old information to new
	var $processed_authors = array();
	var $author_mapping = array();
//	var $processed_terms = array();
	var $processed_posts = array();
	var $post_orphans = array();

	// Errors found in mapping process
	var $author_errors = array();

	// Creating new users
	var $new_user_credentials = array();

	var $fetch_attachments = false;
	var $url_remap = array();


	function DTD_Import() { /* nothing */ }

	/**
	 * Registered callback function for the DTD Importer
	 *
	 * Manages the separate stages of the WXR import process
	 */
	function dispatch() {
		$this->header();
	
		$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
		switch ($step) {
			case 0:
				$this->greet();
				break;
			case 1:
				if (!empty($_POST['import-wordpress'])) {
					check_admin_referer('import-wordpress');
			
					$this->load_data_from_post();
					// Load mapping, and errors if they exist.
					$this->get_author_mapping();

					if (!empty($this->author_errors)) {
						// If we have errors, display assignment page again with errors.
						$this->import_options();
					}
					else {
						// Create users and import.
						$this->create_users();
						$this->fetch_attachments = ( ! empty( $_POST['fetch_attachments'] ) && $this->allow_fetch_attachments() );
						$this->id = (int) $_POST['import_id'];
						$file = get_attached_file( $this->id );
						set_time_limit(0);
						$this->import( $file );
					}
				}
				else {
					check_admin_referer( 'import-upload' );
					if ( $this->handle_upload() )
						$this->import_options();
				}
				break;
		}
		$this->footer();
	}
	
	
	/**
	 * Loads data from $_POST variable.
	 */ 
	function load_data_from_post() {
		$this->id = (int) $_POST['import_id'];
		$this->authors = $_POST['authors'];
	}
	
	/**
	 * The main controller for the actual import stage.
	 *
	 * @param string $file Path to the WXR file for importing
	 */
	function import( $file ) {
		add_filter( 'import_post_meta_key', array( $this, 'is_valid_meta_key' ) );
		add_filter( 'http_request_timeout', array( &$this, 'bump_request_timeout' ) );

		$this->import_start( $file );

		wp_suspend_cache_invalidation( true );
		$this->process_categories();
		$this->process_tags();
//		$this->process_terms();
		$this->process_posts();
		wp_suspend_cache_invalidation( false );

		// update incorrect/missing information in the DB
//		$this->backfill_parents();
		$this->backfill_attachment_urls();
//		$this->remap_featured_images();

		$this->import_end();
	}
	
	
	/**
	 * Creates users, whose credentials were created in get_author_mapping. Maps newly created users.
	 */
	function create_users() {
		$create_users = $this->allow_create_users();
		if ($create_users) {
			$new_users = $this->new_user_credentials;
			foreach ($new_users as $i => $user_creds) {
				$old_id = $user_creds['old_id'];			
			
				$extra = array(
					'display_name' => $this->authors[$old_id]['author_display_name'],
					'first_name' => $this->authors[$old_id]['author_first_name'],
					'last_name' => $this->authors[$old_id]['author_last_name'],
				);	            
				

				// Create the user, send them an email.
				$user_id = anno_invite_contributor($user_creds['user_login'], $user_creds['user_email'], $extra);
				
				// All checks for validity (username/email existance) have occured in get_author_mapping. 
				// This should not produce an error under any normal circumstance, but just in case.
				if (is_wp_error($user_id) || empty($user_id)) {
					$user_id = get_current_user_id();
				}
				if ($old_id) {
					$this->processed_authors[$old_id] = $user_id;
				}
				$this->author_mapping[$santized_old_login] = $user_id;
			}
		}
	}
	
	/**
	 * Parses the file and prepares us for the task of processing parsed data
	 *
	 * @param string $file Path to the XML file for importing
	 */
	function import_start( $file ) {
		if ( ! is_file($file) ) {
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

//		$this->version = $import_data['version'];
		$this->get_authors_from_import( $import_data );
		$this->posts = $import_data['posts'];
//		$this->terms = $import_data['terms'];
		$this->categories = $import_data['categories'];
		$this->tags = $import_data['tags'];
		$this->base_url = esc_url( $import_data['base_url'] );

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );

		do_action( 'import_start' );
	}
	
	/**
	 * Performs post-import cleanup of files and the cache
	 */
	function import_end() {
		wp_import_cleanup( $this->id );

		wp_cache_flush();
		foreach ( get_taxonomies() as $tax ) {
			delete_option( "{$tax}_children" );
			_get_term_hierarchy( $tax );
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		echo '<p>' . __( 'All done.', 'anno' ) . ' <a href="' . admin_url() . '">' . __( 'Have fun!', 'anno' ) . '</a>' . '</p>';
		echo '<p>' . __( 'Remember to update the passwords and roles of imported users.', 'anno' ) . '</p>';

		do_action( 'import_end' );
	}
	
	/**
	 * Handles the XML upload and initial parsing of the file to prepare for
	 * displaying author import options
	 *
	 * @return bool False if error uploading or invalid file, true otherwise
	 */
	function handle_upload() {
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'anno' ) . '</strong><br />';
			echo esc_html( $file['error'] ) . '</p>';
			return false;
		}

		$this->id = (int) $file['id'];
		$import_data = $this->parse( $file['file'] );
		if ( is_wp_error( $import_data ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'anno' ) . '</strong><br />';
			echo esc_html( $import_data->get_error_message() ) . '</p>';
			return false;
		}

		// $this->version = $import_data['version'];
		// if ( $this->version > $this->max_wxr_version ) {
		// 	echo '<div class="error"><p><strong>';
		// 	printf( __( 'This WXR file (version %s) may not be supported by this version of the importer. Please consider updating.', 'anno' ), esc_html($import_data['version']) );
		// 	echo '</strong></p></div>';
		// }

		$this->get_authors_from_import($import_data);

		return true;
	}
	
	/**
	 * Retrieve contributors from parsed XML data
	 *
	 * @param array $import_data Data returned by a WXR parser
	 */
	function get_authors_from_import( $import_data ) {
		if ( ! empty( $import_data['authors'] ) ) {
			$this->authors = $import_data['authors'];
		// no author information, grab it from the posts
		} else {
			foreach ( $import_data['posts'] as $post ) {
				$login = sanitize_user( $post['post_author'], true );
				if ( empty( $login ) ) {
					printf( __( 'Failed to import author %s. Their posts will be attributed to the current user.', 'anno' ), esc_html( $post['post_author'] ) );
					echo '<br />';
					continue;
				}

				if ( ! isset($this->authors[$login]) )
					$this->authors[$login] = array(
						'author_login' => $login,
						'author_display_name' => $post['post_author']
					);
			}
		}
	}
	
	/**
	 * Display pre-import options, author importing/mapping and option to
	 * fetch attachments
	 */
	function import_options() {
		$j = 0;
?>
<form action="<?php echo admin_url( 'admin.php?import=google_knol_wxr&amp;step=1' ); ?>" method="post">
	<?php wp_nonce_field( 'import-wordpress' ); ?>
	<input type="hidden" name="import_id" value="<?php echo $this->id; ?>" />

<?php if ( ! empty( $this->authors ) ) : ?>
	<h3><?php _e( 'Assign Authors', 'anno' ); ?></h3>
	<p><?php _e( 'To make it easier for you to edit and save the imported content, you may want to reassign the author of the imported item to an existing user of this site. For example, you may want to import all the entries as <code>admin</code>s entries.', 'anno' ); ?></p>
<?php if ( $this->allow_create_users() ) : ?>
	<p><?php _e( 'If a new user is created by WordPress, a new password will be randomly generated and the new user&#8217;s role will be set as contributor. Manually changing the new user&#8217;s details will be necessary.', 'anno' ); ?> </p>
<?php endif; ?>
	<ol id="authors">
<?php foreach ( $this->authors as $author ) : 
		// Output errors.
		if (!empty($this->author_errors[$j]) && is_array($this->author_errors[$j])) {
			echo '<div style="color: red">';
			foreach ($this->author_errors[$j] as $author_error) {
				echo $author_error.'<br />';
			}
			echo '</div>';
		}
?>
		<li><?php $this->author_select( $j++, $author ); ?></li>
<?php endforeach; ?>
	</ol>
<?php endif; ?>

<?php if ( $this->allow_fetch_attachments() ) : ?>
	<h3><?php _e( 'Import Attachments', 'anno' ); ?></h3>
	<p>
		<input type="checkbox" value="1" name="fetch_attachments" id="import-attachments" />
		<label for="import-attachments"><?php _e( 'Download and import file attachments', 'anno' ); ?></label>
	</p>
<?php endif; ?>
<?php

// Populate authors, so we can repopulate $this->authors on chance an error occurs and we're redirected to 'step 1' again.
foreach ($this->authors as $author_key => $author_data) {
	if (!empty($author_key) || $author_key === '0') {
?>
	<input type="hidden" name="authors[<?php echo esc_attr($author_key) ?>][author_id]" value="<?php echo esc_attr($author_data['author_id']) ?>" />
	<input type="hidden" name="authors[<?php echo esc_attr($author_key) ?>][author_login]" value="<?php echo esc_attr($author_data['author_login']) ?>" />
	<input type="hidden" name="authors[<?php echo esc_attr($author_key) ?>][author_email]" value="<?php echo esc_attr($author_data['author_email']) ?>" />
	<input type="hidden" name="authors[<?php echo esc_attr($author_key) ?>][author_display_name]" value="<?php echo esc_attr($author_data['author_display_name']) ?>" />
	<input type="hidden" name="authors[<?php echo esc_attr($author_key) ?>][author_first_name]" value="<?php echo esc_attr($author_data['author_first_name']) ?>" />
	<input type="hidden" name="authors[<?php echo esc_attr($author_key) ?>][author_last_name]" value="<?php echo esc_attr($author_data['author_last_name']) ?>" />
<?php
	}
}
//	<input type="hidden" name="version" value="<?php echo esc_attr($this->version); ?>" />
?>
	<input type="hidden" name="import-wordpress" value="true" />
	<p class="submit"><input type="submit" class="button" value="<?php esc_attr_e( 'Submit', 'anno' ); ?>" /></p>
</form>
<?php
	}
	
	/**
	 * Display import options for an individual author. That is, either create
	 * a new user based on import info or map to an existing user
	 *
	 * @param int $n Index for each author in the form
	 * @param array $author Author information, e.g. login, display name, email
	 */
	function author_select( $n, $author ) {	
		_e( 'Import ', 'anno' );
		echo ' <strong>' . esc_html( $author['author_display_name'] );
//		if ( $this->version != '1.0' ) echo ' (' . esc_html( $author['author_login'] ) . ')';
		echo '</strong> as the current user.<br />';

//		if ( $this->version != '1.0' )
//			echo '<div style="margin-left:18px">';
		
// @TODO reactivate some time in the future, or remove.
// Show information about a matching user with a given Knol ID
/*		//@TODO Search globally, not just this blog. Display all users with this Knol ID, not just the first.
 		$users = get_users(array(
			'meta_key' => '_knol_id',
			'meta_value' => $author['author_id'],
		));		
			
		if (!empty($users) && is_array($users)) {
			echo '<span style="color:#009900;">'.__('A user with the same Knol ID has been found', 'anno') . ': <strong>' . esc_html($users[0]->display_name) . ' (' . esc_html($users[0]->user_login). ')</strong></span><br />';
		}
*/
		$create_users = $this->allow_create_users();
		
		_e( '- OR -<br /> assign posts to an existing user: ', 'anno' );

		$dropdown_args = array( 
			'name' => "user_map[$n]", 
			'multi' => true, 
			'show_option_all' => __( '- Select -', 'anno' ) 
			);
// @TODO reactivate some time in the future, or remove.
// Auto select a user found that has a matching Knol ID.		
/*		@TODO Search locally, this blog only.
 		if (!empty($users) && is_array($users)) {
			$dropdown_args['selected'] = $users[0]->ID;
		}
*/
		// Preselect if we're returning to this page.
		if (!empty($_POST['user_map'][$n])) {
			$dropdown_args['selected'] = (int) $_POST['user_map'][$n];
		}
		wp_dropdown_users($dropdown_args);
		
		$lookup_email = !empty($_POST['lookup_email'][$n]) ? $_POST['lookup_email'][$n] : '';

		echo '<br />'.__('- OR -', 'anno').'<br /><label for="'.esc_attr('lookup-email-'.$n).'">'.__('if you know an existing user&#8217;s <strong>email address</strong>, you can assign posts to that user:', 'anno').'</label> <input id="'.esc_attr('lookup-email-'.$n).'" type="text" name="'.esc_attr('lookup_email['.$n.']').'"  value="'.esc_attr($lookup_email).'"/>';
		
		$lookup_username = !empty($_POST['lookup_username'][$n]) ? $_POST['lookup_username'][$n] : '';
		
		echo '<br />'.__('- OR -', 'anno').'<br /><label for="'.esc_attr('lookup-username-'.$n).'">'.__('if you know an existing user&#8217;s <strong>username</strong>, you can assign posts to that user:', 'anno').'</label> <input id="'.esc_attr('lookup-username-'.$n).'" type="text" name="'.esc_attr('lookup_username['.$n.']').'" value="'.esc_attr($lookup_username).'" />';
		
		if ( $create_users ) {
//			if ( $this->version != '1.0' ) {
				_e( '<br />- OR -<br /> you can create new user:', 'anno' );
				$value = '';
//			} else {
//				_e( '<br />as a new user:', 'anno' );
//				$value = esc_attr( sanitize_user( $author['author_login'], true ) );
//			}
			
			$new_user_login = !empty($_POST['user_new'][$n]['user_login']) ? $_POST['user_new'][$n]['user_login'] : '';

			echo '<br /><label for="'.esc_attr($n.'-login-new').'">'._x('Login: ', 'input label for importer', 'anno').'</label> <input id="'.esc_attr($n.'-login-new').'" type="text" name="user_new['.$n.'][user_login]" value="'.esc_attr($new_user_login).'" /><br />';
			
			$new_user_email = !empty($_POST['user_new'][$n]['user_email']) ? $_POST['user_new'][$n]['user_email'] : '';
			
			echo ' <label for="'.esc_attr($n.'-email-new').'">'._x('Email: ', 'input label for importer', 'anno').'</label> <input id="'.esc_attr($n.'-email-new').'" type="text" name="user_new['.$n.'][user_email]" value="'.esc_attr($new_user_email).'" /><br />';
		}
		
		// We store 'creator' data by ID in Knol Export, not login.
		$import_author_value = $author['author_id'];

		
		echo '<input type="hidden" name="imported_authors['.$n.']" value="'.esc_attr($import_author_value).'" />';
		
//		if ( $this->version != '1.0' )
//			echo '</div>';
	}
}
?>