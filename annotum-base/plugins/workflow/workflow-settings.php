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
 * Workflow settings
 */
global $annowf_settings;
$annowf_settings = array(
	'workflow' => __('Enable workflow', 'anno'),
	'author_reviewer' => __('Allow article authors to see reviewers', 'anno'),
	'notification' => __('Enable workflow notifications', 'anno'),
);

/**
 * Workflow settings page markup
 */
function anno_settings_page() {
	global $annowf_settings;
	$settings = get_option('annowf_settings');
?>
<div class="wrap">
	<h2><?php _e('Annotum Workflow Settings', 'anno'); ?></h2>
	<form action="<?php admin_url('/'); ?>" method="post">
<?php
	foreach ($annowf_settings as $slug => $label) {
?>
		<p>
			<label for="annowf-<?php echo $slug ?>"><?php echo $label; ?></label>
			<input id="annowf-<?php echo $slug ?>" type="checkbox" value="1" name="annowf_settings[<?php echo $slug ?>]"<?php checked($settings[$slug], 1); ?> />
		</p>	
<?php
	}
?>
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
 * Request handler for setting page settings
 */ 
function annowf_settings_request_handler() {
	if (isset($_POST['anno_action'])) {
		switch ($_POST['anno_action']) {
			case 'annowf_update_settings':
				if (!check_admin_referer('annowf_settings')) {
					die();
				}
				if (isset($_POST['annowf_settings']) && !empty($_POST['annowf_settings'])) {
					$post_settings = $_POST['annowf_settings'];
					global $annowf_settings;
					foreach ($annowf_settings as $slug => $label) {
						if (!isset($post_settings[$slug])) {
							$post_settings[$slug] = 0;
						}
					}
				}
				else {
					$post_settings = array();
				}
				update_option('annowf_settings', $post_settings);
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
 * @param string $option Name of the workflow option to check. Defaults to workflow (entire workflow enabled/disabled)
 * @return mixed true(1) if the workflow is enabled, false(null) otherwise
 */ 
function anno_workflow_enabled($option = null) {
	if (empty($option)) {
		$option = 'workflow';
	}

	$settings = get_option('annowf_settings');
	return $settings[$option];
}

?>
