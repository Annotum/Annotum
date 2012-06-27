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

if (!defined('WP_LOAD_IMPORTERS'))
	return;
	
/** Display verbose errors */
if (!defined('ANNO_IMPORT_DEBUG')) {
	define( 'ANNO_IMPORT_DEBUG', false );
}

// Load Importer API
require_once ABSPATH.'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require $class_wp_importer;
}

// include WXR file parsers
require dirname( __FILE__ ) . '/parsers.php';

/**
 * Knol importer class for handling Knol WXR import files
 */
if (!class_exists('Knol_Import')) {
class Knol_Import extends WP_Importer {
	var $max_wxr_version = 1.1; // max. supported WXR version

	var $id; // WXR attachment ID

	// information to import from WXR file
	var $version;
	var $authors = array();
	var $posts = array();
	var $terms = array();
	var $categories = array();
	var $tags = array();
	var $base_url = '';

	// mappings from old information to new
	var $processed_authors = array();
	var $author_mapping = array();
	var $processed_terms = array();
	var $processed_posts = array();
	var $post_orphans = array();
	var $processed_menu_items = array();
	var $menu_item_orphans = array();
	var $missing_menu_items = array();
	
	// Errors found in mapping process
	var $author_errors = array();
	
	// Creating new users
	var $new_user_credentials = array();
	var $created_users = array();
	
	var $fetch_attachments = false;
	var $url_remap = array();
	var $featured_images = array();
	
	var $import_slug = 'google_knol_wxr';

	function Knol_Import() {/* Nothing */ }

	/**
	 * Registered callback function for the WordPress Importer
	 *
	 * Manages the three separate stages of the WXR import process
	 */
	function dispatch() {
		$this->header();

		$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
		switch ( $step ) {
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
						$this->import($file);
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
		$this->id = isset($_POST['import_id']) ? (int) $_POST['import_id'] : 0;
		$this->version = isset($_POST['version']) ? (string) $_POST['version'] : '1.1' ;
		$this->authors = isset($_POST['authors']) ? $_POST['authors'] : array();
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
		if (defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG) {
			if (isset($_POST['anno_knol_parser'])) {
				echo sprintf('<p>'.__('ANNO_IMPORT_DEBUG: You are using %s to parse data.', 'anno').'</p>', esc_html($_POST['anno_knol_parser']));
			}
		}
		wp_suspend_cache_invalidation( true );
		$this->process_categories();
		$this->process_tags();
		$this->process_terms();
		$this->process_posts();
		wp_suspend_cache_invalidation( false );

		// update incorrect/missing information in the DB
		$this->backfill_parents();
		$this->backfill_attachment_urls();
		$this->remap_featured_images();

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
				else {
					// Keep track of the created users so we can 
					// import their meta information on import					
					$this->created_users[$old_id] = $user_id;
				}				

				$this->processed_authors[$old_id] = $user_id;
				$this->author_mapping[$old_id] = $user_id;
			}
		}
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

		// This block lists the imported items (including ones that already exist(ed)) 
		// and provides edit and preview links.
		if (!empty($this->processed_posts) && is_array($this->processed_posts)) {
			echo '<p>';
			foreach($this->processed_posts as $the_imported_article) {
			
				$the_imported_post = get_post($the_imported_article);
				
				printf( __('%s &#8220;<strong>%s</strong>&#8221; imported. ', 'anno'), ucfirst(esc_html($the_imported_post->post_type)), esc_html($the_imported_post->post_title));

				if ($the_imported_post->post_type == 'attachment') {
					$preview_url = get_permalink($the_imported_post->ID);
				}
				else {
					$preview_url = get_permalink($the_imported_post->ID);
					if (is_ssl()) {
						$preview_url = str_replace('http://', 'https://', $preview_link);
					}
					$preview_url = add_query_arg('preview', 'true', $preview_url);
				}

				// Only provide preview link for attachments or knol articles 
				// Kipling DTD articles must be edited and saved at least once to preview correctly
				if ($GLOBALS['importer'] == 'google_knol_wxr' or $the_imported_post->post_type == 'attachment') {
					printf( __('[ %sEdit%s | %sPreview%s ]', 'anno'),
						'<a href="'.esc_url(get_edit_post_link($the_imported_post->ID)).'">','</a>',
						'<a href="'.esc_url($preview_url).'">','</a>.');
				}
				else {
					printf( __('[ %sEdit%s ]', 'anno'),
						'<a href="'.esc_url(get_edit_post_link($the_imported_post->ID)).'">','</a>');
				}
				
				echo '<br />';				
			}
			echo '</p>';
		}

		echo '<p>' . __( 'All done.', 'anno' ) . ' <a href="' . admin_url() . '">' . __( 'Have fun!', 'anno' ) . '</a>' . '</p>';
		echo '<p>' . __( 'Remember to update the passwords and roles of imported users.', 'anno' ) . '</p>';

		do_action( 'import_end' );
	}

	/**
	 * Handles the WXR upload and initial parsing of the file to prepare for
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

		$this->version = $import_data['version'];
		if ( $this->version > $this->max_wxr_version ) {
			echo '<div class="error"><p><strong>';
			printf( __( 'This WXR file (version %s) may not be supported by this version of the importer. Please consider updating.', 'anno' ), esc_html($import_data['version']) );
			echo '</strong></p></div>';
		}

		$this->get_authors_from_import($import_data);

		return true;
	}

	/**
	 * Retrieve authors from parsed WXR data
	 *
	 * Uses the provided author information from WXR 1.1 files
	 * or extracts info from each post for WXR 1.0 files
	 *
	 * @param array $import_data Data returned by a WXR parser
	 */
	function get_authors_from_import( $import_data ) {
		if ( ! empty( $import_data['authors'] ) ) {
			$this->authors = $import_data['authors'];
		// no author information, grab it from the posts
		} else {
			if (!empty($import_data['posts']) && !is_array($import_data['posts'])) {
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
	}

	/**
	 * Display pre-import options, author importing/mapping and option to
	 * fetch attachments
	 */
	function import_options() {
		$j = 0;
?>
<form action="<?php echo admin_url( 'admin.php?import='.$this->import_slug.'&amp;step=1' ); ?>" method="post">
	<?php
		if (defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG) {
			if (isset($_POST['anno_knol_parser'])) {
				echo sprintf(__('ANNO_IMPORT_DEBUG: You are using %s to parse data.', 'anno'), esc_html($_POST['anno_knol_parser']));
				echo '<input type="hidden" name="anno_knol_parser" value="'.esc_attr($_POST['anno_knol_parser']).'" />';
			}
		}
	?>
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
	if (!empty($author_key) || $author_key === 0) {
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
?>
	
	<input type="hidden" name="version" value="<?php echo esc_attr($this->version); ?>" />
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
		
		if (!empty($author['author_id'])) {
			$extra = ' ('.esc_html($author['author_id']).')';
		}
		else {
			$extra = '';
		}
		
		
		_e( 'Import ', 'anno' );
		echo ' <strong>' . esc_html( $author['author_display_name'] );
		if ( $this->version != '1.0' ) {
			echo $extra;
		}
		echo '</strong> '._x('as the current user.', 'user import display text', 'anno').'<br />';

		if ( $this->version != '1.0' )
			echo '<div style="margin: 0 0 1em 1em">';
		
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
		
		if ( ! $create_users && $this->version == '1.0' )
			_e( '<p>or assign posts to an existing user: ', 'anno' );
		else
			_e( '<p>or assign posts to an existing user: ', 'anno' );
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
			$dropdown_args['selected'] = $_POST['user_map'][$n];
		}
		wp_dropdown_users($dropdown_args);
		_e( '</p>', 'anno');
		
		$lookup_email = !empty($_POST['lookup_email'][$n]) ? $_POST['lookup_email'][$n] : '';

		echo '<p><label for="'.esc_attr('lookup-email-'.$n).'">'.__('or if you know an existing user&#8217;s <strong>email address</strong>, you can assign posts to that user: ', 'anno').'</label> <input id="'.esc_attr('lookup-email-'.$n).'" type="text" name="'.esc_attr('lookup_email['.$n.']').'"  value="'.esc_attr($lookup_email).'"/></p>';
		
		$lookup_username = !empty($_POST['lookup_username'][$n]) ? $_POST['lookup_username'][$n] : '';
		
		echo '<p><label for="'.esc_attr('lookup-username-'.$n).'">'.__('or if you know an existing user&#8217;s <strong>username</strong>, you can assign posts to that user: ', 'anno').'</label> <input id="'.esc_attr('lookup-username-'.$n).'" type="text" name="'.esc_attr('lookup_username['.$n.']').'" value="'.esc_attr($lookup_username).'" /></p>';
		
		if ( $create_users ) {
			_e( '<p>', 'anno');
			if ( $this->version != '1.0' ) {
				_e( 'or you can create new user:', 'anno' );
				$value = '';
			} else {
				_e( 'as a new user:', 'anno' );
				$value = esc_attr( sanitize_user( $author['author_login'], true ) );
			}

			$new_user_email = !empty($_POST['user_new'][$n]['user_email']) ? $_POST['user_new'][$n]['user_email'] : '';
			
			echo ' <label for="'.esc_attr($n.'-email-new').'">'._x('Email: ', 'input label for importer', 'anno').'</label> <input id="'.esc_attr($n.'-email-new').'" type="text" name="user_new['.$n.'][user_email]" value="'.esc_attr($new_user_email).'" /><br />';
		}
		
		// We store 'creator' data by ID in Knol Export, not login.
		$import_author_value = $author['author_id'];

		
		echo '<input type="hidden" name="imported_authors['.$n.']" value="'.esc_attr($import_author_value).'" />';
		
		_e( '</p>', 'anno');
		
		if ( $this->version != '1.0' )
			echo '</div>';
	}

	/**
	 * Map old author logins to local user IDs based on decisions made
	 * in import options form. Can map to an existing user, create a new user
	 * or falls back to the current user in case of error with either of the previous
	 */
	function get_author_mapping() {
		if ( ! isset( $_POST['imported_authors'] ) )
			return;

		$create_users = $this->allow_create_users();

		$this->author_errors = array();
		$this->new_user_credentials = array();

		foreach ( (array) $_POST['imported_authors'] as $i => $old_id ) {
			// Used to determine whether or not we're creating a new user on import.
			$create_new_user = false;
			$user_id = 0;
						
			$old_id = trim($old_id);

			if (!empty($_POST['user_map'][$i])) {
				$user = get_userdata( intval($_POST['user_map'][$i]) );
				if (isset( $user->ID)) {
					if ($old_id) {
						$this->processed_authors[$old_id] = $user->ID;
					}
					$this->author_mapping[$old_id] = $user->ID;
					$user_id = $user->ID;
				}
			}
			else if (!empty($_POST['lookup_email'][$i]) || !empty($_POST['lookup_username'][$i])) {
				// Validate email
				// Search via email
				if (!empty($_POST['lookup_email'][$i]) && !empty($_POST['lookup_username'][$i])) {
					$this->author_errors[$i][] = _x('Please enter an Email <strong>OR</strong> a Username to search for.', 'importer error message', 'anno');
				}
				// Attempt to find a user via email
				else if (!empty($_POST['lookup_email'][$i])) {
					$lookup_email = $_POST['lookup_email'][$i];
					if (!anno_is_valid_email($lookup_email)) {
						$this->author_errors[$i][] = _x('Please enter a valid email to search for.', 'importer error message', 'anno');
					}
					// Search for email
					else {
						$users = get_users(array(
							'search' => $lookup_email,
						));
						if (empty($users)) {
							$this->author_errors[$i][] = sprintf(_x('Could not find user with email: %s', 'importer error message, %s: email address', 'anno'), $lookup_email);
						}
						else if (is_array($users)) {
							$user_id = $users[0]->ID;
						}
					}
				}
				// Validate username
				// Search via username
				else if (!empty($_POST['lookup_username'][$i])) {
					$lookup_username = $_POST['lookup_username'][$i];
					if (!anno_is_valid_username($lookup_username)) {
						$this->author_errors[$i][] = _x('Please enter a valid username to search for.', 'importer error message', 'anno');
					}
					else {
						//@TODO only search user_login column
						$users = get_users(array(
							'search' => $lookup_username,
						));
						if (empty($users)) {
							$this->author_errors[$i][] = sprintf(_x('Could not find user with username: %s', 'importer error message, %s: username', 'anno'), $lookup_username);
						}
						else if (is_array($users)) {
							$user_id = $users[0]->ID;
						}
					}
				}
			}
			// We can create users, and both lookups fields are not present.
			else if ( 
				$create_users && 
				(empty($_POST['lookup_email'][$i]) && empty($_POST['lookup_username'][$i])) && 
				(!empty($_POST['user_new'][$i]['user_email']))
				) {

				if (!empty($_POST['user_new'][$i]['user_email'])) {
					// Username is email.
					$user_new_email = $user_new_login = $_POST['user_new'][$i]['user_email'];
				}
				// From the else if above, conclude that user_login is not empty
				else {
					$this->author_errors[$i][] = _x('Email cannot be empty when creating a new user.', 'importer error message', 'anno');
					$user_new_email = null;
				}

				// email_exists($user_email) username_exists( $user_login )
				if (email_exists($user_new_email) || username_exists($user_new_login)) {
					$this->author_errors[$i][] = _x('This email address is already registered.', 'importer error message', 'anno');
				}
				
				if (!$this->have_author_errors($i)) {
					if (!anno_is_valid_email($user_new_email) || !anno_is_valid_username($user_new_login)) {
						$this->author_errors[$i][] = _x('Please enter a valid email when creating a new user.', 'importer error message', 'anno');
					}

					if (!$this->have_author_errors($i)) {
						$this->new_user_credentials[$i]['old_id'] = $old_id;
						$this->new_user_credentials[$i]['user_login'] = $user_new_login;
						$this->new_user_credentials[$i]['user_email'] = $user_new_email;
						$create_new_user = true;
					}
				}
			}

			// user_id is empty, so no lookup was attempted, and we're not creating a new user
			if (empty($user_id) && !$create_new_user) {
				// Map to current user.
				$user_id = get_current_user_id();
			}

			// Map users, $user_id is only set when we've found a user to map to.
			if (!$create_new_user && !empty($user_id)) {
				if ($old_id) {
					$this->processed_authors[$old_id] = $user_id;
				}
				$this->author_mapping[$old_id] = $user_id;
			}
		}
	}

	/**
	 * Helper function to determine if a given user mapping has any errors associated with it
	 *
	 * @param int $i Index of a given author. 
	 * @return bool True if errors have been found, false otherwise
	 */ 
	private function have_author_errors($i) {
		return isset($this->author_errors[$i]) && count($this->author_errors[$i]);
	}
	
	/**
	 * Create new categories based on import information
	 *
	 * Doesn't create a new category if its slug already exists
	 */
	function process_categories() {
		if ( empty( $this->categories ) )
			return;

		foreach ( $this->categories as $cat ) {
			// if the category already exists leave it alone
			$term_id = term_exists( $cat['category_nicename'], 'category' );
			if ( $term_id ) {
				if ( is_array($term_id) ) $term_id = $term_id['term_id'];
				if ( isset($cat['term_id']) )
					$this->processed_terms[$cat['term_id']] = (int) $term_id;
				continue;
			}

			$category_parent = empty( $cat['category_parent'] ) ? 0 : category_exists( $cat['category_parent'] );
			$category_description = isset( $cat['category_description'] ) ? $cat['category_description'] : '';
			$catarr = array(
				'category_nicename' => $cat['category_nicename'],
				'category_parent' => $category_parent,
				'cat_name' => $cat['cat_name'],
				'category_description' => $category_description
			);

			$id = wp_insert_category( $catarr );
			if ( ! is_wp_error( $id ) ) {
				if ( isset($cat['term_id']) )
					$this->processed_terms[$cat['term_id']] = $id;
			} else {
				printf( __( 'Failed to import category %s', 'anno' ), esc_html($cat['category_nicename']) );
				if ( defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG )
					echo ': ' . $id->get_error_message();
				echo '<br />';
				continue;
			}
		}

		unset( $this->categories );
	}

	/**
	 * Create new post tags based on import information
	 *
	 * Doesn't create a tag if its slug already exists
	 */
	function process_tags() {
		if ( empty( $this->tags ) )
			return;

		foreach ( $this->tags as $tag ) {
			// if the tag already exists leave it alone
			$term_id = term_exists( $tag['tag_slug'], 'post_tag' );
			if ( $term_id ) {
				if ( is_array($term_id) ) $term_id = $term_id['term_id'];
				if ( isset($tag['term_id']) )
					$this->processed_terms[$tag['term_id']] = (int) $term_id;
				continue;
			}

			$tag_desc = isset( $tag['tag_description'] ) ? $tag['tag_description'] : '';
			$tagarr = array( 'slug' => $tag['tag_slug'], 'description' => $tag_desc );

			$id = wp_insert_term( $tag['tag_name'], 'post_tag', $tagarr );
			if ( ! is_wp_error( $id ) ) {
				if ( isset($tag['term_id']) )
					$this->processed_terms[$tag['term_id']] = $id['term_id'];
			} else {
				printf( __( 'Failed to import post tag %s', 'anno' ), esc_html($tag['tag_name']) );
				if ( defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG )
					echo ': ' . $id->get_error_message();
				echo '<br />';
				continue;
			}
		}

		unset( $this->tags );
	}

	/**
	 * Create new terms based on import information
	 *
	 * Doesn't create a term its slug already exists
	 */
	function process_terms() {
		if ( empty( $this->terms ) )
			return;

		foreach ( $this->terms as $term ) {
			// if the term already exists in the correct taxonomy leave it alone
			$term_id = term_exists( $term['slug'], $term['term_taxonomy'] );
			if ( $term_id ) {
				if ( is_array($term_id) ) $term_id = $term_id['term_id'];
				if ( isset($term['term_id']) )
					$this->processed_terms[$term['term_id']] = (int) $term_id;
				continue;
			}

			if ( empty( $term['term_parent'] ) ) {
				$parent = 0;
			} else {
				$parent = term_exists( $term['term_parent'], $term['term_taxonomy'] );
				if ( is_array( $parent ) ) $parent = $parent['term_id'];
			}
			$description = isset( $term['term_description'] ) ? $term['term_description'] : '';
			$termarr = array( 'slug' => $term['slug'], 'description' => $description, 'parent' => $parent );

			$id = wp_insert_term( $term['term_name'], $term['term_taxonomy'], $termarr );
			if ( ! is_wp_error( $id ) ) {
				if ( isset($term['term_id']) )
					$this->processed_terms[$term['term_id']] = $id['term_id'];
			} else {
				printf( __( 'Failed to import %s %s', 'anno' ), esc_html($term['term_taxonomy']), esc_html($term['term_name']) );
				if ( defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG )
					echo ': ' . $id->get_error_message();
				echo '<br />';
				continue;
			}
		}

		unset( $this->terms );
	}

	/**
	 * Create new posts based on import information
	 *
	 * Posts marked as having a parent which doesn't exist will become top level items.
	 * Doesn't create a new post if: the post type doesn't exist, the given post ID
	 * is already noted as imported or a post with the same title and date already exists.
	 * Note that new/updated terms, comments and meta are imported for the last of the above.
	 */
	function process_posts() {
		$snapshot_template = array(
			'id' => '',
			'surname' => '',
			'given_names' => '',
			'prefix' => '',
			'suffix' => '',
			'degrees' => '',
			'institution' => '',
	    	'bio' => '',
	        'email' => '',
	        'link' => '',
		);

		foreach ( $this->posts as $post ) {
		
			if ( ! post_type_exists( $post['post_type'] ) ) {
				printf( __( 'Failed to import &#8220;%s&#8221;: Invalid post type %s', 'anno' ),
					esc_html($post['post_title']), esc_html($post['post_type']) );
				echo '<br />';
				continue;
			}

			if ( isset( $this->processed_posts[$post['post_id']] ) && ! empty( $post['post_id'] ) )
				continue;

			if ( $post['status'] == 'auto-draft' )
				continue;

			if ( 'nav_menu_item' == $post['post_type'] ) {
				$this->process_menu_item( $post );
				continue;
			}

			$post_type_object = get_post_type_object( $post['post_type'] );

			// Just to be safe, knols come with post_date_gmt.
			if (empty($post['post_date'])) {
				$post['post_date'] = $post['post_date_gmt'];
			}

			$post_exists = post_exists( $post['post_title'], '', $post['post_date'], array('article') );
			if ( $post_exists ) {
				printf( __('%s &#8220;%s&#8221; already exists.', 'anno'), $post_type_object->labels->singular_name, esc_html($post['post_title']) );
				echo '<br />';
				$comment_post_ID = $post_id = $post_exists;
			} else {
				$post_parent = $post['post_parent'];
				if ( $post_parent ) {
					// if we already know the parent, map it to the new local ID
					if ( isset( $this->processed_posts[$post_parent] ) ) {
						$post_parent = $this->processed_posts[$post_parent];
					// otherwise record the parent for later
					} else {
						$this->post_orphans[$post['post_id']] = $post_parent;
						$post_parent = 0;
					}
				}

				// map the post author
				if (isset($post['post_author'])) {
					$author = sanitize_user($post['post_author'], true);
					if ( isset( $this->author_mapping[$author] ) ) {
						$author = $this->author_mapping[$author];
					}
					else {
						$author = (int) get_current_user_id();
					}
				}
				else
					$author = (int) get_current_user_id();
				
				$postdata = array(
					'import_id' => $post['post_id'], 'post_author' => $author, 'post_date' => $post['post_date'],
					'post_date_gmt' => $post['post_date_gmt'], 'post_content' => $post['post_content'],
					'post_excerpt' => $post['post_excerpt'], 'post_title' => $post['post_title'],
					'post_status' => $post['status'], 'post_name' => $post['post_name'],
					'comment_status' => $post['comment_status'], 'ping_status' => $post['ping_status'],
					'guid' => $post['guid'], 'post_parent' => $post_parent, 'menu_order' => $post['menu_order'],
					'post_type' => $post['post_type'], 'post_password' => $post['post_password'], 'post_content_filtered' => $post['post_content_filtered'],
				);
				
				// Get rid of wrapping <body> tag in XML
				$postdata['post_content_filtered'] = preg_replace('#</?body(\s[^>]*)?>#i', '', $postdata['post_content_filtered']); 

				if ( 'attachment' == $postdata['post_type'] ) {
					$remote_url = ! empty($post['attachment_url']) ? $post['attachment_url'] : $post['guid'];

					// try to use _wp_attached file for upload folder placement to ensure the same location as the export site
					// e.g. location is 2003/05/image.jpg but the attachment post_date is 2010/09, see media_handle_upload()
					$postdata['upload_date'] = $post['post_date'];
					if ( isset( $post['postmeta']) && is_array($post['postmeta']) ) {
						foreach( $post['postmeta'] as $meta ) {
							if ( $meta['key'] == '_wp_attached_file' ) {
								if ( preg_match( '%^[0-9]{4}/[0-9]{2}%', $meta['value'], $matches ) )
									$postdata['upload_date'] = $matches[0];
								break;
							}
						}
					}
					
					$comment_post_ID = $post_id = $this->process_attachment( $postdata, $remote_url );
				} else {
					remove_filter('wp_insert_post_data', 'anno_insert_post_data', null, 2);
					remove_filter('edit_post_content', 'anno_edit_post_content', 10, 2 );
					remove_filter('edit_post_content_filtered', 'anno_edit_post_content_filtered', 10, 2 );
					$comment_post_ID = $post_id = wp_insert_post( $postdata, true );
				}

				if ( is_wp_error( $post_id ) ) {
					printf( __( 'Failed to import %s &#8220;%s&#8221;', 'anno' ),
						$post_type_object->labels->singular_name, esc_html($post['post_title']) );
					if ( defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG )
						echo ': ' . $post_id->get_error_message();
					echo '<br />';
					continue;
				}

				if ( $post['is_sticky'] == 1 )
					stick_post( $post_id );

				// map pre-import ID to local ID
				$this->processed_posts[$post['post_id']] = $post_id;

				// add categories, tags and other terms
				if ( ! empty( $post['terms'] ) ) {
					$terms_to_set = array();
					foreach ( $post['terms'] as $term ) {
						// back compat with WXR 1.0 map 'tag' to 'post_tag'
						$taxonomy = ( 'tag' == $term['domain'] ) ? 'post_tag' : $term['domain'];
						$term_exists = term_exists( $term['slug'], $taxonomy );
						$term_id = is_array( $term_exists ) ? $term_exists['term_id'] : $term_exists;
						if ( ! $term_id ) {
							$t = wp_insert_term( $term['name'], $taxonomy, array( 'slug' => $term['slug'] ) );
							if ( ! is_wp_error( $t ) ) {
								$term_id = $t['term_id'];
							} else {
								printf( __( 'Failed to import %s %s', 'anno' ), esc_html($taxonomy), esc_html($term['name']) );
								if ( defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG )
									echo ': ' . $t->get_error_message();
								echo '<br />';
								continue;
							}
						}
						$terms_to_set[$taxonomy][] = $term_id;
					}

					foreach ( $terms_to_set as $tax => $ids ) {
						$tt_ids = wp_set_post_terms( $post_id, $ids, $tax );
					}
					unset( $post['terms'], $terms_to_set );
				}

				// add/update comments
				if ( ! empty( $post['comments'] ) ) {
					$num_comments = 0;
					$inserted_comments = array();
					foreach ( $post['comments'] as $comment ) {
						$comment_id	= $comment['comment_id'];
						$newcomments[$comment_id]['comment_post_ID']      = $comment_post_ID;
						$newcomments[$comment_id]['comment_author']       = $comment['comment_author'];
						$newcomments[$comment_id]['comment_author_email'] = $comment['comment_author_email'];
						$newcomments[$comment_id]['comment_author_IP']    = $comment['comment_author_IP'];
						$newcomments[$comment_id]['comment_author_url']   = $comment['comment_author_url'];
						$newcomments[$comment_id]['comment_date']         = $comment['comment_date'];
						$newcomments[$comment_id]['comment_date_gmt']     = $comment['comment_date_gmt'];
						$newcomments[$comment_id]['comment_content']      = $comment['comment_content'];
						$newcomments[$comment_id]['comment_approved']     = $comment['comment_approved'];
						$newcomments[$comment_id]['comment_type']         = $comment['comment_type'];
						$newcomments[$comment_id]['comment_parent'] 	  = $comment['comment_parent'];
						$newcomments[$comment_id]['commentmeta']          = isset( $comment['commentmeta'] ) ? $comment['commentmeta'] : array();
						// This will be empty in the Knol WXR
						/*
						if ( isset( $this->processed_authors[$comment['comment_user_id']] ) )
							$newcomments[$comment_id]['user_id'] = $this->processed_authors[$comment['comment_user_id']];
						*/
					}
					ksort( $newcomments );

					foreach ( $newcomments as $key => $comment ) {
						// if this is a new post we can skip the comment_exists() check
						if ( ! $post_exists || ! comment_exists( $comment['comment_author'], $comment['comment_date'] ) ) {
							if ( isset( $inserted_comments[$comment['comment_parent']] ) )
								$comment['comment_parent'] = $inserted_comments[$comment['comment_parent']];
							$comment = wp_filter_comment( $comment );
							$inserted_comments[$key] = wp_insert_comment( $comment );

							foreach( $comment['commentmeta'] as $meta ) {
								$value = maybe_unserialize( $meta['value'] );
								add_comment_meta( $inserted_comments[$key], $meta['key'], $value );
							}

							$num_comments++;
						}
					}
					unset( $newcomments, $inserted_comments, $post['comments'] );
				}

				// add/update post meta
				$author_snapshot = array();
				// Save the primary author in the author snapshot first
				$snapshot = $snapshot_template;
				if (isset($post['post_author'])) {
					$snapshot['id'] = $this->authors[$post['post_author']]['author_id'];
					$snapshot['email'] = $this->authors[$post['post_author']]['author_email'];
					$snapshot['surname'] = $this->authors[$post['post_author']]['author_last_name'];
					$snapshot['given_names'] = $this->authors[$post['post_author']]['author_first_name'];
					$author_snapshot[$post['post_author']] = $snapshot;
				}
				
				if ( isset( $post['postmeta'] ) && is_array($post['postmeta']) ) {
					foreach ( $post['postmeta'] as $meta ) {
						$key = apply_filters( 'import_post_meta_key', $meta['key'] );
						$value = false;
						
						// Store both the Knol ID and WP ID, for potential future users/associations.
						if (strpos($key, '_anno_knol_author_') !== false) {
							$knol_author_id = str_replace('_anno_knol_author_', '', $key);
							if (isset($this->author_mapping[$knol_author_id])) {
								$wp_author_id = $this->author_mapping[$knol_author_id];
								$this->add_user_to_post('author', $wp_author_id, $post_id);
							}
							// Generate our author snapshot based on post meta that comes in.
							// We don't need to do this for _anno_author_, those will only exist in DTD imports, which will be in draft
							// Snapshots get taken on publish status transition
							$snapshot = $snapshot_template;
							if (isset($this->authors[$knol_author_id]) && !isset($author_snapshot[$this->authors[$knol_author_id]['author_id']])) {						
								$snapshot = $snapshot_template;
								$snapshot['id'] = $this->authors[$knol_author_id]['author_id'];
								$snapshot['email'] = $this->authors[$knol_author_id]['author_email'];
								$snapshot['surname'] = $this->authors[$knol_author_id]['author_last_name'];
								$snapshot['given_names'] = $this->authors[$knol_author_id]['author_first_name'];
								$author_snapshot[$snapshot['id']] = $snapshot;
							}
						}
						
						if (strpos($key, '_anno_knol_reviewer_') !== false) {
							$knol_author_id = str_replace('_anno_knol_reviewer_', '', $key);
							if (isset($this->author_mapping[$knol_author_id])) {
								$wp_reviewer_id = $this->author_mapping[$knol_author_id];
								$this->add_user_to_post('reviewer', $wp_reviewer_id, $post_id);
							}
						}
						
						if ( '_edit_last' == $key ) {
							if ( isset( $this->processed_authors[$meta['value']] ) )
								$value = $this->processed_authors[$meta['value']];
							else
								$key = false;
						}

						if ( $key ) {
							// export gets meta straight from the DB so could have a serialized string
							if ( ! $value )
								$value = maybe_unserialize( $meta['value'] );

							// Update co-author data to be the wp user not the local file user.
							if (strpos($key, '_anno_author_') !== false) {
								$local_author_id = str_replace('_anno_author_', '', $key);
								// Set the key and value to be our WP user ID, not the local
								if (isset($this->author_mapping[$local_author_id])) {
									$wp_author_id = $this->author_mapping[$local_author_id];
									$this->add_user_to_post('author', $wp_author_id, $post_id);
								}
							}
							else if (strpos($key, '_anno_reviewer_') !== false) {
								$local_author_id = str_replace('_anno_reviewer_', '', $key);
								// Set the key and value to be our WP user ID, not the local
								if (isset($this->author_mapping[$local_author_id])) {
									$wp_author_id = $this->author_mapping[$local_author_id];
									$this->add_user_to_post('reviewer', $wp_author_id, $post_id);
								}
							}
							else {
								update_post_meta( $post_id, $key, $value );								
							}

							do_action( 'import_post_meta', $post_id, $key, $value );

							// if the post has a featured image, take note of this in case of remap
							if ( '_thumbnail_id' == $key )
								$this->featured_images[$post_id] = $value;
						}
					}
					// Save our snapshot
					if (!empty($author_snapshot)) {
						update_post_meta($post_id, '_anno_author_snapshot', $author_snapshot);
					}
				}
				if ($GLOBALS['importer'] == 'google_knol_wxr') {
					// Add a key to the post. Posts from google knol with this key are alerted 
					// that the XML structure may change on save. Meta is deleted on save.
					// Kipling (non-knol xml) imports don't need the extra parsing on save
					// Fixes https://github.com/Annotum/Annotum/issues/40
					add_post_meta($post_id, '_anno_knol_import', 1);
				}
			}
		}

		unset( $this->posts );
	}

	/**
	 * Attempt to create a new menu item from import data
	 *
	 * Fails for draft, orphaned menu items and those without an associated nav_menu
	 * or an invalid nav_menu term. If the post type or term object which the menu item
	 * represents doesn't exist then the menu item will not be imported (waits until the
	 * end of the import to retry again before discarding).
	 *
	 * @param array $item Menu item details from WXR file
	 */
	function process_menu_item( $item ) {
		// skip draft, orphaned menu items
		if ( 'draft' == $item['status'] )
			return;

		$menu_slug = false;
		if ( isset($item['terms']) ) {
			// loop through terms, assume first nav_menu term is correct menu
			foreach ( $item['terms'] as $term ) {
				if ( 'nav_menu' == $term['domain'] ) {
					$menu_slug = $term['slug'];
					break;
				}
			}
		}

		// no nav_menu term associated with this menu item
		if ( ! $menu_slug ) {
			_e( 'Menu item skipped due to missing menu slug', 'anno' );
			echo '<br />';
			return;
		}

		$menu_id = term_exists( $menu_slug, 'nav_menu' );
		if ( ! $menu_id ) {
			printf( __( 'Menu item skipped due to invalid menu slug: %s', 'anno' ), esc_html( $menu_slug ) );
			echo '<br />';
			return;
		} else {
			$menu_id = is_array( $menu_id ) ? $menu_id['term_id'] : $menu_id;
		}

		foreach ( $item['postmeta'] as $meta )
			$$meta['key'] = $meta['value'];

		if ( 'taxonomy' == $_menu_item_type && isset( $this->processed_terms[intval($_menu_item_object_id)] ) ) {
			$_menu_item_object_id = $this->processed_terms[intval($_menu_item_object_id)];
		} else if ( 'post_type' == $_menu_item_type && isset( $this->processed_posts[$_menu_item_object_id] ) ) {
			$_menu_item_object_id = $this->processed_posts[($_menu_item_object_id)];
		} else if ( 'custom' != $_menu_item_type ) {
			// associated object is missing or not imported yet, we'll retry later
			$this->missing_menu_items[] = $item;
			return;
		}

		if ( isset( $this->processed_menu_items[intval($_menu_item_menu_item_parent)] ) ) {
			$_menu_item_menu_item_parent = $this->processed_menu_items[intval($_menu_item_menu_item_parent)];
		} else if ( $_menu_item_menu_item_parent ) {
			$this->menu_item_orphans[$item['post_id']] = (int) $_menu_item_menu_item_parent;
			$_menu_item_menu_item_parent = 0;
		}

		// wp_update_nav_menu_item expects CSS classes as a space separated string
		$_menu_item_classes = maybe_unserialize( $_menu_item_classes );
		if ( is_array( $_menu_item_classes ) )
			$_menu_item_classes = implode( ' ', $_menu_item_classes );

		$args = array(
			'menu-item-object-id' => $_menu_item_object_id,
			'menu-item-object' => $_menu_item_object,
			'menu-item-parent-id' => $_menu_item_menu_item_parent,
			'menu-item-position' => intval( $item['menu_order'] ),
			'menu-item-type' => $_menu_item_type,
			'menu-item-title' => $item['post_title'],
			'menu-item-url' => $_menu_item_url,
			'menu-item-description' => $item['post_content'],
			'menu-item-attr-title' => $item['post_excerpt'],
			'menu-item-target' => $_menu_item_target,
			'menu-item-classes' => $_menu_item_classes,
			'menu-item-xfn' => $_menu_item_xfn,
			'menu-item-status' => $item['status']
		);

		$id = wp_update_nav_menu_item( $menu_id, 0, $args );
		if ( $id && ! is_wp_error( $id ) )
			$this->processed_menu_items[$item['post_id']] = (int) $id;
	}

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

		// Keep track of the original URL for remapping purposes
		$original_url = $url;

		// if the URL is absolute, but does not contain address, then upload it assuming base_site_url
		if ( preg_match( '|^/[\w\W]+$|', $url ) )
			$url = rtrim( $this->base_url, '/' ) . $url;
		$upload = $this->fetch_remote_file( $url, $post, $original_url );
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
	 * Attempt to download a remote file attachment
	 *
	 * @param string $url URL of item to fetch
	 * @param array $post Attachment details
	 * @param string $original_url Original that can in from the content, un processed. Used in remapping
	 * @return array|WP_Error Local file location details on success, WP_Error otherwise
	 */
	function fetch_remote_file( $url, $post, $original_url) {
		// extract the file name and extension from the url
		// Decode and remove spaces, so WP can recognize the filename
		$file_name = str_replace(' ', '', urldecode( basename( $url ) ) );

		// get placeholder file in the upload dir with a unique, sanitized filename
		$upload = wp_upload_bits( $file_name, 0, '', $post['upload_date'] );
		if ( $upload['error'] )
			return new WP_Error( 'upload_dir_error', $upload['error'] );

		// fetch the remote url and write it to the placeholder file
		$headers = wp_get_http( $url, $upload['file'] );

		// request failed
		if ( ! $headers ) {
			@unlink( $upload['file'] );
			return new WP_Error( 'import_file_error', __('Remote server did not respond', 'anno') );
		}

		// make sure the fetch was successful
		if ( $headers['response'] != '200' ) {
			@unlink( $upload['file'] );
			return new WP_Error( 'import_file_error', sprintf( __('Remote server returned error response %1$d %2$s', 'anno'), esc_html($headers['response']), get_status_header_desc($headers['response']) ) );
		}

		$filesize = filesize( $upload['file'] );

		if ( isset( $headers['content-length'] ) && $filesize != $headers['content-length'] ) {
			@unlink( $upload['file'] );
			return new WP_Error( 'import_file_error', __('Remote file is incorrect size', 'anno') );
		}

		if ( 0 == $filesize ) {
			@unlink( $upload['file'] );
			return new WP_Error( 'import_file_error', __('Zero size file downloaded', 'anno') );
		}

		$max_size = (int) $this->max_attachment_size();
		if ( ! empty( $max_size ) && $filesize > $max_size ) {
			@unlink( $upload['file'] );
			return new WP_Error( 'import_file_error', sprintf(__('Remote file is too large, limit is %s', 'anno'), size_format($max_size) ) );
		}

		// keep track of the old and new urls so we can substitute them later
		$this->url_remap[$original_url] = $upload['url'];
		$this->url_remap[$post['guid']] = $upload['url']; // r13735, really needed?

		// keep track of the destination if the remote url is redirected somewhere else
		if ( isset($headers['x-final-location']) && $headers['x-final-location'] != $url )
			$this->url_remap[$headers['x-final-location']] = $upload['url'];
		return $upload;
	}

	/**
	 * Attempt to associate posts and menu items with previously missing parents
	 *
	 * An imported post's parent may not have been imported when it was first created
	 * so try again. Similarly for child menu items and menu items which were missing
	 * the object (e.g. post) they represent in the menu
	 */
	function backfill_parents() {
		global $wpdb;
		// find parents for post orphans
		foreach ( $this->post_orphans as $child_id => $parent_id ) {
			$local_child_id = $local_parent_id = false;
			if ( isset( $this->processed_posts[$child_id] ) )
				$local_child_id = $this->processed_posts[$child_id];
			if ( isset( $this->processed_posts[$parent_id] ) )
				$local_parent_id = $this->processed_posts[$parent_id];

			if ( $local_child_id && $local_parent_id )
				$wpdb->update( $wpdb->posts, array( 'post_parent' => $local_parent_id ), array( 'ID' => $local_child_id ), '%d', '%d' );
		}

		// all other posts/terms are imported, retry menu items with missing associated object
		$missing_menu_items = $this->missing_menu_items;
		foreach ( $missing_menu_items as $item )
			$this->process_menu_item( $item );

		// find parents for menu item orphans
		foreach ( $this->menu_item_orphans as $child_id => $parent_id ) {
			$local_child_id = $local_parent_id = 0;
			if ( isset( $this->processed_menu_items[$child_id] ) )
				$local_child_id = $this->processed_menu_items[$child_id];
			if ( isset( $this->processed_menu_items[$parent_id] ) )
				$local_parent_id = $this->processed_menu_items[$parent_id];

			if ( $local_child_id && $local_parent_id )
				update_post_meta( $local_child_id, '_menu_item_menu_item_parent', $local_parent_id );
		}
	}

	/**
	 * Use stored mapping information to update old attachment URLs
	 */
	function backfill_attachment_urls() {
		global $wpdb;
		// make sure we do the longest urls first, in case one is a substring of another
		uksort( $this->url_remap, array(&$this, 'cmpr_strlen') );

		foreach ( $this->url_remap as $from_url => $to_url ) {
			// remap urls in post_content and post_content_filtered		
			$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s), post_content_filtered = REPLACE(post_content_filtered, %s, %s)", $from_url, $to_url, $from_url, $to_url) );

			// remap enclosure urls
			$result = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE meta_key='enclosure'", $from_url, $to_url));
		}
	}

	/**
	 * Update _thumbnail_id meta to new, imported attachment IDs
	 */
	function remap_featured_images() {
		// cycle through posts that have a featured image
		foreach ( $this->featured_images as $post_id => $value ) {
			if ( isset( $this->processed_posts[$value] ) ) {
				$new_id = $this->processed_posts[$value];
				// only update if there's a difference
				if ( $new_id != $value )
					update_post_meta( $post_id, '_thumbnail_id', $new_id );
			}
		}
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
		echo '<h2>' . __( 'Google Knol WXR Import', 'anno' ) . '</h2>';

		$updates = get_plugin_updates();
		$basename = plugin_basename(__FILE__);
		if ( isset( $updates[$basename] ) ) {
			$update = $updates[$basename];
			echo '<div class="error"><p><strong>';
			printf( __( 'A new version of this importer is available. Please update to version %s to ensure compatibility with newer export files.', 'anno' ), $update->update->new_version );
			echo '</strong></p></div>';
		}
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
		echo '<p>'.__( 'Howdy! Upload your Google Knol eXtended RSS (WXR) file and we&#8217;ll import the posts, pages, comments, custom fields, categories, and tags into this site.', 'anno' ).'</p>';
		echo '<p>'.__( 'Choose a Google Knol WXR (.xml) file to upload, then click Upload file and import.', 'anno' ).'</p>';
		$this->import_upload_form( 'admin.php?import='.$this->import_slug.'&amp;step=1' );
		echo '</div>';
	}

	function import_upload_form( $action ) {
		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = wp_convert_bytes_to_hr( $bytes );
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) :
			?><div class="error"><p><?php _e('Before you can upload your import file, you will need to fix the following error:', 'anno'); ?></p>
			<p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
		else :
	?>
	<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr(wp_nonce_url($action, 'import-upload')); ?>">
	<?php
		if (defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG) {
			echo '<p>'.__('ANNO_IMPORT_DEBUG: Select a parser:', 'anno').' <select name="anno_knol_parser">
						<option value="simplexml">SimpleXML</option>
						<option value="xml">XML</option>
						<option value="regex">RegEx</option>
					</select>
				</p>';
		}
	?>
	<p>
	<label for="upload"><?php _e( 'Choose a file from your computer:', 'anno' ); ?></label> (<?php printf( __('Maximum size: %s', 'anno' ), $size ); ?>)
	<input type="file" id="upload" name="import" size="25" />
	<input type="hidden" name="action" value="save" />
	<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
	</p>
	<?php submit_button( __('Upload file and import','anno'), 'button' ); ?>
	</form>
	<?php
		endif;
	}

	/**
	 * Decide if the given meta key maps to information we will want to import
	 *
	 * @param string $key The meta key to check
	 * @return string|bool The key if we do want to import, false if not
	 */
	function is_valid_meta_key( $key ) {
		// skip attachment metadata since we'll regenerate it from scratch
		// skip _edit_lock as not relevant for import
		if ( in_array( $key, array( '_wp_attached_file', '_wp_attachment_metadata', '_edit_lock' ) ) )
			return false;
		return $key;
	}

	/**
	 * Decide whether or not the importer is allowed to create users.
	 * Default is true, can be filtered via import_allow_create_users
	 *
	 * @return bool True if creating users is allowed
	 */
	function allow_create_users() {
		return apply_filters( 'import_allow_create_users', true );
	}

	/**
	 * Decide whether or not the importer should attempt to download attachment files.
	 * Default is true, can be filtered via import_allow_fetch_attachments. The choice
	 * made at the import options screen must also be true, false here hides that checkbox.
	 *
	 * @return bool True if downloading attachments is allowed
	 */
	function allow_fetch_attachments() {
		return apply_filters( 'import_allow_fetch_attachments', true );
	}

	/**
	 * Decide what the maximum file size for downloaded attachments is.
	 * Default is 0 (unlimited), can be filtered via import_attachment_size_limit
	 *
	 * @return int Maximum attachment file size to import
	 */
	function max_attachment_size() {
		return apply_filters( 'import_attachment_size_limit', 0 );
	}

	/**
	 * Added to http_request_timeout filter to force timeout at 60 seconds during import
	 * @return int 60
	 */
	function bump_request_timeout() {
		return 60;
	}

	// return the difference in length between two strings
	function cmpr_strlen( $a, $b ) {
		return strlen($b) - strlen($a);
	}
	
	/**
	 * Adds a user to a given post with a given role
	 * 
	 * Copied from workflow code that does the same thing, need it here in case the workflow is disabled.
	 * 
	 * @param string $type Type of user to add. Can be the meta_key.
	 * @param int $user_id ID of the user being added to the post
	 * @param int $post_id ID of the post to add the user to. Loads from global if nothing is passed.
	 * @return bool True if successfully added or already a user associated with the post, false otherwise
	 */ 
	function add_user_to_post($type, $user_id, $post_id) {
		$type = str_replace('-', '_', $type);
		if ($type == 'co_author') {
			$type = 'author';
		}

		if ($type == 'reviewer' || $type == 'author') {
			$order = '_anno_'.$type.'_order';
			$type = '_anno_'.$type.'_'.$user_id;
		}
		else {
			return false;
		}

		$users = get_post_meta($post_id, $order, true);
		if (!is_array($users)) {
			update_post_meta($post_id, $order, array($user_id));
			return add_post_meta($post_id, $type, $user_id, true);
		}
		else if (!in_array($user_id, $users)) {
			$users[] = $user_id;
			update_post_meta($post_id, $order, array_unique($users));
			return add_post_meta($post_id, $type, $user_id, true);
		}

		return true;
	}
}

} // class_exists( 'WP_Importer' )

if (!function_exists('anno_knol_importer_init')) {
	function anno_knol_importer_init() {
		/**
		 * Knol Importer object for registering the import callback
		 * @global Knol_Import $knol_import
		 */
		$GLOBALS['knol_import'] = new Knol_Import();
		register_importer( 'google_knol_wxr', 'Google Knol WXR', __('Import <strong>articles, comments, custom fields, categories, and tags</strong> from a Google Knol WXR export file.', 'anno'), array( $GLOBALS['knol_import'], 'dispatch' ) );
	}
	add_action( 'admin_init', 'anno_knol_importer_init' );
}