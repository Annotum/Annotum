<?php

function anno_register_settings() {
	$page = 'workflow';
	$options = array(
		'annowf_settings' => array(
			'callback' => '',
			'label' =>  _x('Workflow Options', 'options heading', 'anno'),
			'description_callback' => 'anno_section_blank',
			'sanitization_callback' => '',
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
					'name' => 'author_reviewer',
					'default' => '1',
					'type' => 'checkbox',
				),
				'notification' => array(
					'label' => _x('Enable workflow notifications', 'options label', 'anno'),
					'label_for' => 'notification',
					'name' => 'notification',
					'default' => '1',
					'type' => 'checkbox',
				),
			),
		),
		
		'anno_ga_id' => array(
			'callback' => '',
			'label' =>  _x('Google Analytics', 'options heading', 'anno'),
			'description_callback' => 'anno_section_blank',
			'sanitization_callback' => '',
			'options' => array(
				'anno_ga_id' => array(
					'label' => _x('Google Analytics ID', 'options label', 'anno'),
					'label_for' => 'anno-ga-id',
					'name' => 'anno_ga_id',
					'default' => '',
					'type' => 'text',
				),
			),
		),
	);
		
	foreach ($options as $section => $fields) {
		register_setting('workflow', $section, $fields['sanitization_callback']);
		$settings = get_option($section);
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
	else {
		$setting = $settings;
	}
	$html = '';
	switch ($type) {
		case 'checkbox':
			$html = '<input id="'.$label_for.'" name="'.esc_attr($section.'['.$name.']').'" type="checkbox" value="'.$default.'"'.checked($default, $setting, false).' />';
			break;
		case 'text':
			$html = '<input id="'.$label_for.'" name="'.esc_attr($section.'['.$name.']').'" type="text" value="'.$setting.'" />';
			break;
		default:
			# code...
			break;
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

/**
 * Sanitize google analytics
 */ 
function anno_sanitize_option_ga_id($value, $option) {
		$value = $value['anno_ga_id'];
		if ($value == '' || (bool)preg_match('/\d{5,}-\d{1,}/', $value)) {
			$value = addslashes($value);
			$value = wp_filter_post_kses($value); // calls stripslashes then addslashes
			$value = stripslashes($value);
			$value = esc_html($value);
		}
		else {
			$value = get_option($option);
			if (function_exists('add_settings_error')) {
				add_settings_error('anno_ga_id', 'invalid_ga_id', _x('Invalid Google Analytics ID', '', 'anno'));
			}
		}

	return $value;
}
add_filter('sanitize_option_anno_ga_id', 'anno_sanitize_option_ga_id', 10, 2);
?>
