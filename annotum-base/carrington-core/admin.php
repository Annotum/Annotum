<?php

// This file is part of the Carrington Core Platform for WordPress
// http://carringtontheme.com
//
// Copyright (c) 2008-2011 Crowd Favorite, Ltd. All rights reserved.
// http://crowdfavorite.com
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

// - add admin page for config settings

function cfct_option_css() {
	
}

/**
 * Add a menu option under the admin themes menu
 * 
**/
function cfct_admin_menu() {
	add_submenu_page(
		'themes.php',
		apply_filters('cfct_admin_settings_title', __('Carrington Theme Settings', 'carrington')),
		apply_filters('cfct_admin_settings_menu', __('Theme Settings', 'carrington')),
		'edit_theme_options',
		'carrington-settings',
		'cfct_settings_form'
	);
}
add_action('admin_menu', 'cfct_admin_menu');

/**
 * Add a menu option under the admin admin bar
 * 
**/
function cfct_admin_bar() {
	global $wp_admin_bar;
	if (current_user_can('edit_theme_options')) {
		$wp_admin_bar->add_menu(array(
			'id' => 'theme-settings',
			'title' => apply_filters('cfct_admin_settings_menu', __('Theme Settings', 'carrington')),
			'href' => admin_url('themes.php?page=carrington-settings'),
			'parent' => 'appearance'
		));
	}
}
add_action('wp_before_admin_bar_render', 'cfct_admin_bar');

/**
 * Deprecated in favor of WP Core Settings API
**/
function cfct_admin_request_handler() {
	_deprecated_function(__FUNCTION__, '3.2');
}

/**
 * Deprecated in favor of WP Core Settings API
**/
function cfct_update_settings() {
	_deprecated_function(__FUNCTION__, '3.2');
}

/**
 * Register Theme Settings screen options using WP Settings API
 */ 
function cfct_register_options() {
	global $cfct_hidden_fields;
	$yn_options = array(
		'yes' => __('Yes', 'carrington'),
		'no' => __('No', 'carrington')
	);
	$cfct_options = array(
		'cfct' => array(
			'label' => '',
			//This is a callback, use cfct_options_blank to display nothing
			'description' => 'cfct_options_blank',
			'fields' => array(
				'about' => array(
					'type' => 'textarea',
					'label' => __('About text (shown in sidebar)', 'carrington'),
					'cols' => 60,
					'rows' => 5,
					'name' => 'about_text',
				),
				'header' => array(
					'type' => 'textarea',
					'label' => __('Header code (analytics, etc.)', 'carrington'),
					'name' => 'wp_header',
				),
				'footer' => array(
					'type' => 'textarea',
					'label' => __('Footer code (analytics, etc.)', 'carrington'),
					'name' => 'wp_footer',
				),
				'copyright' => array(
					'type' => 'text',
					'label' => __('Copyright / legal footer text', 'carrington'),
					'name' => 'copyright',
					'help' => '<br /><span class="cfct-help">'.__('(add %Y to output the current year)', 'carrington').'</span>',
					'class' => 'cfct-text-long',
				),
				'login' => array(
					'type' => 'radio',
					'label' => __('Show log in/out links in footer', 'carrington'),
					'name' => 'login_link_enabled',
					'options' => $yn_options,
				),
				'credit' => array(
					'type' => 'radio',
					'label' => __('Give credit in footer', 'carrington'),
					'name' => 'credit',
					'options' => $yn_options,
				),
				/**
				'radio' => array(
					'type' => 'radio',
					'label' => __('Radio Buttons', 'carrington'),
					'name' => 'radio_test',
					'options' => array(
						'value_one' => 'Radio Bar',
						'value_two' => 'Radio Bar 2',
					),
				),
				'checkbox' => array(
					'type' => 'checkbox',
					'label' => __('Checkboxes', 'carrington'),
					'name' => 'checkbox_test',
					'options' => array(
						'value_one' => 'Check Bar',
						'value_two' => 'Check Bar 2',
					),
				),
				*/
			),
		),
	);
	$cfct_options = apply_filters('cfct_options', $cfct_options);

	foreach ($cfct_options as $section_name => $section) {
		if (empty($section['description'])) {
			$section['description'] = 'cfct_options_blank';
		}
		add_settings_section($section_name, $section['label'], $section['description'], 'cfct');

		foreach ($section['fields'] as $key => $option) {
			
			// Prefix the option name
			$option['name'] = cfct_option_name($option['name']);

			// Support for serialized options
			// First we want to match on the name of the option. (everything up to the first []). Only matchs on alpha-numeric, dashes and underscores
			if (preg_match('/^([a-zA-Z0-9-_]+)\[[a-zA-Z0-9-_]+\]/', $option['name'], $basename_match)) {
				$basename = $basename_match[1];
				register_setting('cfct', $basename, 'cfct_sanitize_options');
				$serialize_option = cfct_get_option($basename);

				// match on any subsequent [] with at least one character to determine the value of the option. Only matchs on alpha-numeric, dashes and underscores.
				if(preg_match_all('/\[([a-zA-Z0-9-_]+)\]/', $option['name'], $key_matches)) {
					$value = $serialize_option;
					foreach ($key_matches[1] as $key_match) {
						if (is_array($value) && array_key_exists($key_match, $value)) {
							$value = $value[$key_match];
						}
					}
					$option['value'] = $value;
				}
			}
			else {
				register_setting('cfct', $option['name'], 'cfct_sanitize_options');
				$option['value'] = cfct_get_option($option['name']);
			}
						
			$option['label_for'] = $section_name.'_'.$key;
			if ($option['type'] != 'hidden') {
				add_settings_field($key, $option['label'], 'cfct_options_input', 'cfct', $section_name, $option);
			}
			else {
				$cfct_hidden_fields[] = $option;
			}
		}
	}

}
add_action('admin_init', 'cfct_register_options', 10);

/**
 * Display hidden fields registered in `cfct_register_options()`. WP Forces padding when using `do_settings_sections`, so it cannot be truly hidden using that method.
 */ 
function cfct_hidden_fields() {
	global $cfct_hidden_fields;
	if (is_array($cfct_hidden_fields)) {
		foreach ($cfct_hidden_fields as $field_options) {
			echo cfct_options_input($field_options);
		}
	}
}
add_action('cfct_settings_form', 'cfct_hidden_fields', 10);

/**
 * Empty callback, callback required by WP core function add_settings_section
 */
function cfct_options_blank() {
	
}

/**
 * Prints an input field based on arguments passed. 
 * @param array $args Array of arguments used to generate the markup.
 * 					  'type' => Type of input
 * 					  'value' => Value of input
 * 					  'name' => Name of input sent in post
 * 					  'label_for' => ID attached to the input. Also used when generating the label in `add_settings_field`
 * 					  'class' => CSS classes for the input
 * 					  'cols' => Textarea specific
 * 					  'rows' => Textarea specific
 * 					  'options' => Radio button, Checkbox, Select specific. Used to define options
 * 					  'help' => Help markup for the option.
 * @return void
 */
function cfct_options_input($args) {
	$type = $args['type'];
	$value = $args['value'];
	$name = $args['name'];
	$id = empty($args['label_for']) ? $args['name'] : $args['label_for'];
	$class = empty($args['class']) ? '' : ' class="'.esc_attr($args['class']).'"';
	$html = '';
	
	switch ($type) {
		case 'text':
			$html .= '<input id="'.esc_attr($id).'" name="'.esc_attr($name).'" type="text" value="'.esc_attr($value).'"'.$class.' />';
			break;
		case 'password':
			$html .= '<input id="'.esc_attr($id).'" name="'.esc_attr($name).'" type="password" value="'.esc_attr($value).'"'.$class.' />';
			break;
		case 'textarea':
			empty($args['cols']) ? $cols = 60 : $cols = (int) $args['cols'];
			empty($args['rows']) ? $rows = 5 : $rows = (int) $args['rows'];
			$html .= '<textarea id="'.esc_attr($id).'" name="'.esc_attr($name).'" cols="'.$cols.'" rows="'.$rows.'"'.$class.'>'.esc_textarea($value).'</textarea>';
			break;
		case 'select':
			$html .= '<select id="'.esc_attr($id).'" name="'.esc_attr($name).'"'.$class.'>';
			$options = $args['options'];
			foreach ($options as $opt_value => $opt_label) {
				$html .= '<option value="'.esc_attr($opt_value).'"'.selected($opt_value, $value, false).'>'.esc_html($opt_label).'</option>';
			}
			$html .= '</select>';
			break;
		case 'radio':
			$options = $args['options'];
			if (is_array($options)) {
				$html .= '<ul>';
				foreach ($options as $opt_value => $opt_label) {
					$html .= '
					<li>
						<label for="'.esc_attr($name.'-'.$opt_value).'">
							<input type="radio" name="'.esc_attr($name).'" value="'.esc_attr($opt_value).'" id="'.esc_attr($name.'-'.$opt_value).'"'.checked($opt_value, $value, false).' />
							'.esc_html($opt_label).'
						</label>
					</li>';
				}
				$html .= '</ul>';
			}
			break;
		case 'checkbox':
			$options = $args['options'];
			if (is_array($options)) {
				$html .= '<ul>';
				foreach ($options as $opt_value => $opt_label) {
					$html .= '
					<li>
						<label for="'.esc_attr($name.'-'.$opt_value).'">
							<input type="checkbox" name="'.esc_attr($name.'['.$opt_value.']').'" value="'.esc_attr($opt_value).'" id="'.esc_attr($name.'-'.$opt_value).'"'.checked($opt_value, $value[$opt['id']], false).' />
							'.esc_html($opt_label).'
						</label>
					</li>';
				}
				$html .= '</ul>';
			}
			break;
		case 'hidden':
			$html .= '<input id="'.esc_attr($id).'" type="hidden" name="'.esc_attr($name).'" value="'.esc_attr($value).'" class="'.esc_attr($class).'" />';
			break;
		default:
			$html .= apply_filters('cfct_option_'.$type, $html, $args);
			break;
	}
	if (!empty($args['help'])) {
		$html .= $args['help'];
	}
	
	print($html);
}
/**
 * Sanitizes options
 * @todo Better handling coming in WP 3.3, targetable option names. For now, add a filter to 'sanitize_option_{$option_name}' for additional processing
 */ 
function cfct_sanitize_options($new_value) {
 	return stripslashes_deep($new_value);
}

/**
 * Display a settings for for Carrington Framework
 * 
**/
function cfct_settings_form() {
	settings_errors();
	print('
<div class="wrap">
	'.get_screen_icon().'<h2>'.apply_filters('cfct_admin_settings_form_title', __('Carrington Theme Settings', 'carrington')).'</h2>
	<form action="'.admin_url('options.php').'" method="post">
	');
	do_action('cfct_settings_form_top');
	do_settings_sections('cfct');
	do_action('cfct_settings_form_bottom');
	do_action('cfct_settings_form');
	settings_fields('cfct');
	print('
		<p class="submit" style="padding-left: 230px;">
			<input type="submit" name="submit_button" class="button-primary" value="'.__('Save', 'carrington').'" />
		</p>
	</form>
</div>
	');
	do_action('cfct_settings_form_after');
}

/**
 * Deprecated in favor of WP Core Settings API
**/
function cfct_options_misc() {
	_deprecated_function(__FUNCTION__, '3.2');
}

/**
 * Display a form for image header customization
 * 
 * @return string Markup displaying the form
 * 
**/
function cfct_header_image_form() {
	global $wpdb;

	$images = $wpdb->get_results("
		SELECT * FROM $wpdb->posts 
		WHERE post_type = 'attachment' 
		AND post_mime_type LIKE 'image%' 
		AND post_parent = 0
		ORDER BY post_date_gmt DESC
		LIMIT 50
	");
	$upload_url = admin_url('media-new.php');
	$header_image = cfct_get_option('header_image');
	if (empty($header_image)) {
		$header_image = 0;
	}
	
	$output = '
<ul style="width: '.((count($images) + 1) * 152).'px">
	<li style="background: #666;">
		<label for="cfct_header_image_0">
			<input type="radio" name="'.esc_attr(cfct_option_name('header_image')).'" value="0" id="'.esc_attr(cfct_option_name('header_image_0')).'" '.checked($header_image, 0, false).'/>'.__('No Image', 'carrington-core').'
		</label>
	</li>
	';
	if (count($images)) {
		foreach ($images as $image) {
			$id = cfct_option_name('header_image_'.$image->ID);
			$thumbnail = wp_get_attachment_image_src($image->ID);
			$output .= '
	<li style="background-image: url('.$thumbnail[0].')">
		<label for="'.$id.'">
			<input type="radio" name="'.esc_attr(cfct_option_name('header_image')).'" value="'.$image->ID.'" id="'.$id.'"'.checked($header_image, $image->ID, false).' />'.esc_html($image->post_title).'
		</label>
	</li>';
		}
	}
	$output .= '</ul>';
	return '<p>'.sprintf(__('Header Image &mdash; <a href="%s">Upload Images</a>', 'carrington-core'), $upload_url).'</p><div class="cfct_header_image_carousel">'.$output.'</div>';
}

/**
 * Add assets to the admin side for our control panel
 */
function cfct_admin_enqueue() {
	if (!empty($_GET['page']) && $_GET['page'] == 'carrington-settings') {
		$core_url = get_bloginfo('template_directory').'/carrington-core/';
		
		wp_enqueue_script(
			'jquery-colorpicker',
			$core_url.'js/colorpicker.js',
			array('jquery'),
			'1.0'
		);
		
		wp_enqueue_style(
			'jquery-colorpicker',
			$core_url.'css/colorpicker.css',
			array(),
			'1.0',
			'screen'
		);
		
		add_action('admin_head', 'cfct_admin_css', 7);
		//add_action('admin_head', 'cfct_admin_js', 8);
	}
}
add_action('admin_enqueue_scripts', 'cfct_admin_enqueue');

/**
 * Admin CSS
 * 
**/
function cfct_admin_css() {
?>
<style type="text/css">
div.cfct_header_image_carousel {
	height: 170px;
	overflow: auto;
	width: 600px;
}
div.cfct_header_image_carousel ul {
	height: 150px;
}
div.cfct_header_image_carousel li {
	background: #fff url() center center no-repeat;
	float: left;
	height: 150px;
	margin-right: 2px;
	overflow: hidden;
	position: relative;
	width: 150px;
}
div.cfct_header_image_carousel li label {
	background: #000;
	color: #fff;
	display: block;
	height: 50px;
	line-height: 25px;
	overflow: hidden;
	position: absolute;
	top: 110px;
	width: 150px;
	filter:alpha(opacity=75);
	-moz-opacity:.75;
	opacity:.75;
}
div.cfct_header_image_carousel li label input {
	margin: 0 5px;
}

.cfct-text-long {
	width: 383px;
}

.cfct-help {
	color: #777777;
	font-size: 11px;
}

</style>
<?php
}

/**
 * Admin JS
 * 
**/
function cfct_admin_js() {
?>
<script type="text/javascript">
jQuery(function() {
	jQuery('select.home_column_select').each(function() {
		cfct_home_columns(jQuery(this), false);
	}).change(function() {
		cfct_home_columns(jQuery(this), true);
	});
});

function cfct_home_columns(elem, slide) {
	var id = elem.attr('id').replace('cfct_home_column_', '').replace('_content', '');
	var val = elem.val();
	var option_show = '#cfct_latest_limit_' + id + '_option';
	var option_hide = '#cfct_list_limit_' + id + '_option';
	if (val == 'list') {
		option_show = '#cfct_list_limit_' + id + '_option';
		option_hide = '#cfct_latest_limit_' + id + '_option';
	}
	if (slide) {
		jQuery(option_hide).slideUp(function() {
			jQuery(option_show).slideDown();
		});
	}
	else {
		jQuery(option_show).show();
		jQuery(option_hide).hide();
	}
}
</script>
<?php
}

?>