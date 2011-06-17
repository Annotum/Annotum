<?php

function anno_register_settings() {
	$page = 'workflow';
	$options = array(
		'annowf_settings' => array(
			'callback' => '',
			'label' =>  _x('Workflow Options', 'options heading', 'anno'),
			'description_callback' => 'anno_section_blank',
			'options' => array(
				'workflow' => array(
					'label' => _x('Enable Workflow', 'options label', 'anno'),
					'label_for' => 'workflow',
					'name' => 'workflow',
					'default' => '1',
					'type' => 'checkbox',
				),
				'author_reviewer' => array(
					'label' => _x('Allow article authors to see reviewers', 'options label', 'anno'),
					'label_for' => 'author-reviewer',
					'name' => 'workflow_author_reviewer',
					'default' => '1',
					'type' => 'checkbox',
				),
				'notification' => array(
					'label' => _x('Enable workflow notifications', 'options label', 'anno'),
					'label_for' => 'notification',
					'name' => 'workflow_notifications',
					'default' => '1',
					'type' => 'checkbox',
				),
			),
		),
		
		'anno_ga_id' => array(
			'callback' => '',
			'label' =>  _x('Google Analytics', 'options heading', 'anno'),
			'description_callback' => 'anno_section_blank',
			'options' => array(
				'anno_ga_id' => array(
					'label' => _x('Google Analytics ID', 'options label', 'anno'),
					'label_for' => 'anno-ga-id',
					'name' => 'ga_id',
					'default' => '',
					'type' => 'text',
					'help' => _x('ex: UA-123456-12', 'help text for option input', 'anno'),
				),
			),
		),
	);
		
	register_setting('workflow', 'anontum_settings', 'anno_sanitize_options');
	$settings = get_option('anontum_settings');
	foreach ($options as $section => $fields) {
		add_settings_section($section,  $fields['label'], $fields['description_callback'], $page);
		foreach ($fields['options'] as $option) {
			$option['settings'] = $settings;
			$option['section'] = $section;
			add_settings_field($option['label_for'], $option['label'], 'anno_option_display', $page, $section, $option);
		}
	}
}
add_action('admin_init', 'anno_register_settings');

/**
 * Callback that does nothing for functions which require callbacks.
 */ 
function anno_section_blank() {
}

/**
 * Callback for displaying options on the settings screen.
 */ 
function anno_option_display($args) {	
	extract($args);
	
	if (is_array($settings)) {
		$setting = $settings[$name];
	}

	$html = '';
	switch ($type) {
		case 'checkbox':
			$html = '<input id="'.$label_for.'" name="'.esc_attr('anontum_settings['.$name.']').'" type="checkbox" value="'.$default.'"'.checked($default, $setting, false).' />';
			break;
		case 'text':
			$html = '<input id="'.$label_for.'" name="'.esc_attr('anontum_settings['.$name.']').'" type="text" value="'.$setting.'" />';
			break;
		default:
			# code...
			break;
	}
	if (!empty($help)) {
		$html .= '<span class="anno-help">'.esc_html($help).'</span>';
	}
	echo $html;
}

/**
 * Add Workflow settings page to the WP Admin sidebar
 */ 
function anno_add_submenu_page() {
	add_submenu_page(
		'themes.php', 
		_x('Annotum Settings', 'Setting page title', 'anno'), 
		_x('Annotum Settings', 'Admin menu option link text', 'anno'), 
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
	'workflow' => _x('Enable workflow', 'Workflow setting label', 'anno'),
	'author_reviewer' => _x('Allow article authors to see reviewers', 'Workflow setting label', 'anno'),
	'notification' => _x('Enable workflow notifications', 'Workflow setting label', 'anno'),
);

/**
 * Workflow settings page markup
 */
function anno_settings_page() {
	settings_errors();
?>
<div class="wrap">
	<h2><?php _ex('Annotum Settings', 'Setting page header', 'anno'); ?></h2>
	<form method="post" action="options.php">
		<?php do_settings_sections('workflow'); ?>

		<p class="submit">
			<?php settings_fields('workflow') ?>
			<input type="submit" name="submit_button" class="button-primary" value="<?php _ex('Save Changes', 'Setting page save button value', 'anno'); ?>" />
		</p>
	</form>
</div>
<?php
}

/**
 * 
 * @param string $key option name to get
 * @param mixed $default What to return if the given option is not set
 * @return mixed
 */ 
function anno_get_option($key, $default = false) {
	$option = get_option('anontum_settings');
	if (is_null($option[$key])) {
		return $default;
	}
	else {
		return $option[$key];
	}
}

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

	$settings = anno_get_option($option);
	return $settings[$option];
}

/**
 * Sanitize google analytics
 */ 
//TODO default
function anno_sanitize_options($value, $option) {
	
	$original_options = get_option($option);
	
	foreach ($value as $option_name => $option_value) {
		switch ($option_name) {
			case 'ga_id':
				if ($option_value == '' || (bool)preg_match('/[a-zA-Z]{2,}-[a-zA-Z0-9]{2,}-[a-zA-Z0-9]{1,}/', $option_value)) {
					$value[$option_name] = anno_sanitize_string($option_value);
				}
				else {
					$value[$option_name] = $original_option[$option_name];
					if (function_exists('add_settings_error')) {
						add_settings_error('anontum_settings', 'invalid_ga_id', _x('Invalid Google Analytics ID', '', 'anno'));
					}
				}
				break;
			default:
				break;
		}
	}

	return $value;
}
add_filter('sanitize_option_anno_ga_id', 'anno_sanitize_option_ga_id');

/**
 * Sanitizes a string for insertion into DB
 * @param string $option The string to be sanitized
 */ 
function anno_sanitize_string($option) {
	$option = addslashes($option);
	$option = wp_filter_post_kses($option); // calls stripslashes then addslashes
	$option = stripslashes($option);
	$option = esc_html($option);
	
	return $option;
}

/**
 * Style settings input
 */ 
function anno_settings_css() {
?>
<style type="text/css">
.anno-help {
	margin-left: 10px;
	color: #AAAAAA;
}
</style>
<?php
}
add_action('admin_print_scripts-appearance_page_anno-workflow-settings', 'anno_settings_css');


?>


