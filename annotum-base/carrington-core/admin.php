<?php

// This file is part of the Carrington Core Framework for WordPress
// http://carringtontheme.com
//
// Copyright (c) 2008-2010 Crowd Favorite, Ltd. All rights reserved.
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
	if (current_user_can('manage_options')) {
		$wp_admin_bar->add_menu(array(
			'id' => 'theme-settings',
			'title' => __('Theme Settings', 'carrington'),
			'href' => admin_url('themes.php?page=cfct_settings_form'),
			'parent' => 'appearance'
		));
	}
}
add_action('wp_before_admin_bar_render', 'cfct_admin_bar');

/**
 * Request handler for admin POSTs and GETs
 * 
**/
function cfct_admin_request_handler() {
	if (isset($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {
			case 'cfct_update_settings':
				call_user_func($_POST['cf_action']);
				wp_redirect(admin_url('/themes.php?page=carrington-settings&updated=true'));
		}
	}
}

/**
 * Update Carrington Framework settings
 * 
**/
function cfct_update_settings() {
	if (!current_user_can('manage_options')) {
		return;
	}
	check_admin_referer('cfct_admin_settings');
	
	global $cfct_options;
	foreach ($cfct_options as $option) {
		if (isset($_POST[$option])) {
			update_option($option, stripslashes($_POST[$option]));
		}
	}
	do_action('cfct_update_settings');
}

/**
 * Display a settings for for Carrington Framework
 * 
**/
function cfct_settings_form() {
	if (isset($_GET['updated'])) {
		print('
<div id="message" class="updated fade">
	<p>'.__('Settings updated.', 'carrington').'</p>
</div>
		');
	}
	print('
<div class="wrap">
	<h2>'.apply_filters('cfct_admin_settings_form_title', __('Carrington Theme Settings', 'carrington')).'</h2>
	<form action="'.admin_url('/').'" method="post">
	');
	do_action('cfct_settings_form_top');
	print('
		<table class="form-table">
			<tbody>'
//			.cfct_options_home_column('1')
//			.cfct_options_home_column('2')
//			.cfct_options_home_column('3')
			.cfct_options_misc()
			.'</tbody>
		</table>
	');
	do_action('cfct_settings_form_bottom');
	do_action('cfct_settings_form');
	print('
		<p class="submit" style="padding-left: 230px;">
			'.wp_nonce_field('cfct_admin_settings', '_wpnonce', true, false).'
			<input type="hidden" name="cf_action" value="cfct_update_settings" />
			<input type="submit" name="submit_button" class="button-primary" value="'.__('Save Changes', 'carrington').'" />
		</p>
	</form>
</div>
	');
	do_action('cfct_settings_form_after');
}

/**
 * Display option for home columns
 * 
**/
function cfct_options_home_column($key) {
	$categories = get_categories('hide_empty=0');
	$cat_options = '';
	$home_col_cat = get_option('cfct_home_col_'.$key.'_cat');
	foreach ($categories as $category) {
		$cat_options .= "\n\t".'<option value="'.$category->term_id.'" '.selected($category->term_id, $home_col_cat, false ).'>'.$category->name.'</option>';
	}
	
	$col_content_array = array('latest' => __('Latest Post Preview', 'carrington'), 'list' => __('List of Recent Post Titles', 'carrington'));
	$show_options = '';	
	$show_option = cfct_get_option('cfct_home_column_'.$key.'_content');
	foreach ($col_content_array as $content_key => $content_value) {
		$content_options .= "\n\t".'<option value="'.$content_key.'" '.selected($content_key, $show_option, false ).'>'.$content_value.'</option>';
	}
	
	$html = '
				<tr valign="top">
					<th scope="row"><b>'.sprintf(__('Home Column %s', 'carrington'), $key).'</b></td>
					<td>
						<fieldset>
							<p>
								<label for="cfct_home_column_'.$key.'_cat">'.__('Category:', 'carrington').'</label>
								<select name="cfct_home_column_'.$key.'_cat" id="cfct_home_column_'.$key.'_cat">'.$cat_options.'</select>
							</p>
							<p>
								<label for="cfct_home_column_'.$key.'_content">'.__('Show:', 'carrington').'</label>
								<select name="cfct_home_column_'.$key.'_content" id="cfct_home_column_'.$key.'_content" class="home_column_select">'.$content_options.'</select>
							</p>
							<p id="cfct_latest_limit_'.$key.'_option" class="hidden">
								<label for="cfct_latest_limit_'.$key.'">'.__('Length of preview, in characters (250 recommended):', 'carrington').'</label>
								<input type="text" name="cfct_latest_limit_'.$key.'" id="cfct_latest_limit_'.$key.'" value="'.cfct_get_option('cfct_latest_limit_'.$key).'" />
							</p>
							<p id="cfct_list_limit_'.$key.'_option" class="hidden">
								<label for="cfct_list_limit_'.$key.'">'.__('Number of titles to show in list (5 recommended):', 'carrington').'</label>
								<input type="text" name="cfct_list_limit_'.$key.'" id="cfct_list_limit_'.$key.'" value="'.cfct_get_option('cfct_list_limit_'.$key).'" />
							</p>
						</fieldset>
					</td>
				</tr>
	';
	return $html;
}

/**
 * Display misc options form
 * 
**/
function cfct_options_misc() {
	$options = array(
		'yes' => __('Yes', 'carrington'),
		'no' => __('No', 'carrington')
	);
	$credit_options = '';
	foreach ($options as $k => $v) {
		$credit_options .= "\n\t".'<option value="'.$k.'" '.selected($k, get_option('cfct_credit'), false).'>'.$v.'</option>';
	}
	$html = '
				<tr valign="top">
					<th scope="row">'.__('Misc.', 'carrington').'</td>
					<td>
						<fieldset>
							<p>
								<label for="cfct_about_text">'.__('About text (shown in sidebar):', 'carrington').'</label>
								<br />
								<textarea name="cfct_about_text" id="cfct_about_text" cols="40" rows="8">'.htmlspecialchars(get_option('cfct_about_text')).'</textarea>
							</p>
							<p>
								<label for="cfct_wp_footer">'.__('Footer code (for analytics, etc.):', 'carrington').'</label>
								<br />
								<textarea name="cfct_wp_footer" id="cfct_wp_footer" cols="40" rows="5">'.htmlspecialchars(get_option('cfct_wp_footer')).'</textarea>
							</p>
							<p>
								<label for="cfct_credit">'.__('Give <a href="http://crowdfavorite.com">Crowd Favorite</a> credit in footer:', 'carrington').'</label>
								<select name="cfct_credit" id="cfct_credit">'.$credit_options.'</select>
							</p>
						</fieldset>
					</td>
				</tr>
	';
	return $html;
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
	$header_image = get_option('cfct_header_image');
	if (empty($header_image)) {
		$header_image = 0;
	}
	
	$output = '
<ul style="width: '.((count($images) + 1) * 152).'px">
	<li style="background: #666;">
		<label for="cfct_header_image_0">
			<input type="radio" name="cfct_header_image" value="0" id="cfct_header_image_0" '.checked($header_image, 0, false).'/>'.__('No Image', 'carrington-core').'
		</label>
	</li>
	';
	if (count($images)) {
		foreach ($images as $image) {
			$id = 'cfct_header_image_'.$image->ID;
			$thumbnail = wp_get_attachment_image_src($image->ID);
			$output .= '
	<li style="background-image: url('.$thumbnail[0].')">
		<label for="'.$id.'">
			<input type="radio" name="cfct_header_image" value="'.$image->ID.'" id="'.$id.'"'.checked($header_image, $image->ID, false).' />'.esc_html($image->post_title).'
		</label>
	</li>';
		}
	}
	$output .= '</ul>';
	return '<p>'.sprintf(__('Header Image &mdash; <a href="%s">Upload Images</a>', 'carrington-core'), $upload_url).'</p><div class="cfct_header_image_carousel">'.$output.'</div>';
}

if (is_admin()) {
	wp_enqueue_script('jquery-colorpicker', get_bloginfo('template_directory').'/carrington-core/js/colorpicker.js', array('jquery'), '1.0');
// removing until we drop 2.5 compatibility
//	wp_enqueue_style('jquery-colorpicker', get_bloginfo('template_directory').'/carrington-core/css/colorpicker.css');
}

/**
 * Load in styles and javascript
 * 
**/
// move to enqueue_style
function cfct_admin_head() {
// see enqueued style above, we'll activate that in the future
	if (!empty($_GET['page']) && $_GET['page'] == 'carrington-settings') {
		echo '
<link rel="stylesheet" type="text/css" media="screen" href="'.get_bloginfo('template_directory').'/carrington-core/css/colorpicker.css" />
		';
		cfct_admin_css();
	}
//	cfct_admin_js();
}
add_action('admin_head', 'cfct_admin_head');

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