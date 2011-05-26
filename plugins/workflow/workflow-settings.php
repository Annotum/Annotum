<?php

/**
 * Add Workflow settings page to the WP Admin sidebar
 */ 
function anno_add_submenu_page() {
	add_submenu_page(
		'themes.php', 
		__('Annotum Workflow Settings', 'anno'), 
		__('Workflow Settings', 'anno'), 
		'manage_options',
		'anno-workflow-settings',
		'anno_settings_page' 
	);
}
add_action('admin_menu', 'anno_add_submenu_page');

/**
 * Workflow settings page markup
 */
function anno_settings_page() {
?>
<div class="wrap">
	<h2><?php _e('Annotum Workflow Settings', 'anno'); ?></h2>
	<form action="<?php admin_url('/'); ?>" method="post">
		<p>
			<label for="anno-workflow">Enbable Workflow</label>
			<input id="anno-workflow" type="checkbox" value="1" name="anno_workflow"<?php checked(get_option('annowf_setting'), 1); ?> />
		</p>
		<p class="submit">
			<?php wp_nonce_field('annowf_settings', '_wpnonce', true, true); ?>
			<input type="hidden" name="anno_action" value="annowf_update_settings" />
			<input type="submit" name="submit_button" class="button-primary" value="<?php _e('Save Changes', 'anno'); ?>" />
		</p>
	</form>
</div>
<?php
}

/**
 * Request handler for setting page options
 */ 
function annowf_settings_request_handler() {
	if (isset($_POST['anno_action'])) {
		switch ($_POST['anno_action']) {
			case 'annowf_update_settings':
				if (!check_admin_referer('annowf_settings')) {
					die();
				}
				if (isset($_POST['anno_workflow']) && !empty($_POST['anno_workflow'])) {
					update_option('annowf_setting', 1);
				}
				else {
					update_option('annowf_setting', 0);
				}
				wp_redirect(admin_url('/themes.php?page=anno-workflow-settings&updated=true'));
				die();
				break;
			default:
				break;
		}
	}	
}
add_action('admin_init', 'annowf_settings_request_handler', 0);

/**
 * Helper function to determine if the workflow is enabled
 * 
 * @return bool True if the workflow is enabled, false otherwise
 */ 
function anno_workflow_enabled() {
	if (get_option('annowf_setting') == 1) {
		return true;
	}
	return false;
}

?>
