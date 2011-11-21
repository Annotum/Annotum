<?php

/*
Plugin Name: CF Colors 
Description: Selection of color swatches from Adobe Kuler.
Version: dev 
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/


/* TODO

- On selected color, if it's been customized it will "Custom Theme by..." wordpress username

*/

define('CF_ANNO_COLORS_VERSION', '0.8');
define('CF_ANNO_COLORS', 'anno_colors');

function cfcp_admin_init() {
	if (!empty($_GET['page']) && $_GET['page'] == basename(__FILE__)) {
		$plugin_dir = trailingslashit(get_template_directory_uri()).'plugins/'.basename(__FILE__, '.php');
		
		wp_enqueue_style('anno-colors-admin-css', $plugin_dir.'/css/admin.css', array(), '20090523', 'screen');
		
		// colorpicker version is the last entry date from the changelog since it doesn't appear to have a version defined
		wp_enqueue_script('jquery-colorpicker', $plugin_dir.'/js/colorpicker/js/colorpicker.js', array('jquery'), '20090523');
		wp_enqueue_style('jquery-colorpicker', $plugin_dir.'/js/colorpicker/css/colorpicker.css', array(), '20090523', 'screen');
		
		// our js
		wp_enqueue_script('cf-colors', $plugin_dir.'/js/cf-colors.js', array('jquery', 'colorpicker', 'jquery-ui-sortable'), CF_ANNO_COLORS_VERSION);
		wp_localize_script('cf-colors', 'cf_kuler_settings', array(
			'loading' => 'Loading...'
		));	
	}
}
add_action('admin_init', 'cfcp_admin_init');

// /* Let's load some styles that will be used on all theme setting pages */
// function cfcp_admin_css() {
//     $cfcp_admin_styles = get_bloginfo('template_url').'/plugins/cf-colors/css/admin.css';
//     echo '<link rel="stylesheet" type="text/css" href="' . $cfcp_admin_styles . '" />';
// }

function cf_kuler_color($key = 'text', $context = null) {
	$color = '';
	if (!empty($context)) {
		$key = apply_filters('cf_kuler_'.$context, $key);
	}
	if ($colors = cf_kuler_get_colors()) {
		switch (strToLower($key)) {
			case 'links':
				$color = $colors[0];
				break;
			case 'header':
				$color = $colors[1];
				break;
			case 'navbar':
				$color = $colors[2];
				break;
		}
	}
	return $color;
}
function cf_kuler_preset_panel() {
	$anno_presets = array();
	$anno_presets['Default'] = array(
		'Text' => '#0867ab',
		'Header' => '#006b94',
		'Navbar' => '#66a6bf'
	);
	$anno_presets['Rust'] = array(
		'Text' => '#b87546',
		'Header' => '#753a2b',
		'Navbar' => '#9e5a3b'
	);
	
	$anno_presets['Grape'] = array(
		'Text' => '#b8a3a7',
		'Header' => '#574f7d',
		'Navbar' => '#8b799e'
	);
	$anno_presets['Forest'] = array(
		'Text' => '#a3bf59',
		'Header' => '#224732',
		'Navbar' => '#4f8749' 
	);

	$html = '';
	foreach($anno_presets as $scheme_label => $color_list) { 
		$html.='<div class="scheme-item">';
		$html.='<a href="#" data="'.implode(',', $color_list).'">'.$scheme_label.'</a>';
		$html.='<ul class="cf-clearfix">';
		foreach($color_list as $color_label => $color) {
			$html.='<li style="background:'.$color.'"></li>';
		}
		$html.='</ul>';
		$html.='</div>';
	}
	
	return $html;
}


function cf_kuler_get_colors() {
	$settings = anno_colors_get_settings();
	return apply_filters('cf-kuler-colors', $settings['colors']);
}

function anno_colors_get_settings() {
	return get_option(CF_ANNO_COLORS, array(
		'colors' => array(
			'#0867ab',
            '#006b94',
            '#66a6bf'
		),
		'theme' => array(
            'swatches' => '#0b1a0e,#3b3d35,#05ab4a,#65c752,#d0dec7'
		)
	));
}



function cf_kuler_colors_html($settings) {
	extract($settings); // extracts $colors & $theme
	
	$html = '
		<div class="cf-kuler-theme" data-swatches="'.implode(',', $colors).'">
			'.cf_kuler_colors_list($colors).'
		</div>
		<ul class="labels clearfix">
			<li><span>Text Link Color</span></li>
			<li><span>Header Background</span></li>
			<li><span>Navigation Bar &amp; Featured Posts</span></li>
		</ul>
	';
	return $html;
}

function cf_kuler_colors_list($colors) {
	$html = '
		<ul>';
	foreach ($colors as $color) {
		$html .= '
			<li style="background-color: '.$color.';"><a class="cf-kuler-theme-edit-swatch" href="#">'.__('edit', 'cf-kuler').'</a></li>';
	}
	$html .= '
		</ul>';
	return $html;
}

function cf_kuler_theme_desc($theme, $modified = false) {
	return ($modified ? __('Based on', 'cf-kuler').' ' : '').'<a href="'.$theme['link'].'">'.$theme['title'].'</a> <em>'.__('by', 'cf-kuler').' '.$theme['author'].'</em>';
}

function cf_kuler_request_handler() {
	if (!empty($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {
			case 'cf_kuler_update_settings':
				check_admin_referer('cf_kuler_update_settings');
				$colors = explode(',', stripslashes($_POST['cf_kuler_colors']));
				$theme = array_map('stripslashes', $_POST['cf_kuler_theme']);
				update_option(CF_ANNO_COLORS, compact('colors','theme'));
				wp_redirect(admin_url('themes.php?page='.basename(__FILE__).'&updated=true'));
				die();
				break;
		}
	}
}
add_action('admin_init', 'cf_kuler_request_handler');

function cf_kuler_admin_menu() {
	add_theme_page(
		__('Color Settings', 'cf-kuler'),
		__('Colors', 'cf-kuler'),
		'manage_options',
		basename(__FILE__),
		'cf_kuler_settings_form'
	);
}
add_action('admin_menu', 'cf_kuler_admin_menu');

function cf_kuler_admin_bar() {
	global $wp_admin_bar;
	if (current_user_can('manage_options')) {
		$wp_admin_bar->add_menu(array(
			'id' => 'cf-kuler',
			'title' => __('Colors', 'cf-kuler'),
			'href' => admin_url('themes.php?page='.basename(__FILE__)),
			'parent' => 'appearance'
		));
	}
}
add_action('wp_before_admin_bar_render', 'cf_kuler_admin_bar');

function cf_kuler_theme_fields($theme) {
	return '
	<input class="cf-kuler-theme-data" type="hidden" name="cf_kuler_theme[swatches]" value="'.(is_array($theme['swatches']) ? implode(',', $theme['swatches']) : $theme['swatches']).'" />';
}

function cf_kuler_color_picker($colors_html) {
	return '
		<div id="cf-kuler-color-picker" class="cfp-popover cfp-popover-top-center" style="display: none;">
			<div class="cfp-popover-notch"></div>
			<div class="cfp-popover-inner">
				<div class="header"></div>
			</div>
		</div>';
}

function cf_kuler_settings_form() {
	if ($settings = anno_colors_get_settings()) {
		$colors = $settings['colors'];
		$colors_html = cf_kuler_colors_html($settings);
	}
	else {
		$colors = '';
		$colors_html = '';
	}

	$message = '';
	if (!empty($_GET['updated']) && $_GET['updated'] == true) {
		$message = '<div class="updated below-h2 fade cf-kuler-message-fade" id="message"><p>'.__('Settings updated.', 'cf-kuler').'</p></div>';
	}
		
	print('
<div class="wrap cf-kuler-wrap cf-clearfix">
	'.screen_icon().'
	<h2>'.__('Color Settings', 'cf-kuler').'</h2>
	'.$message.'
	<div class="cfcp-section">
		<h3 id="selected-theme" class="cfcp-section-title"><span>'.__('My Colors', 'cf-kuler').'</span></h3>
		<div id="cf-kuler-swatch-selected" class="cf-clearfix">
			'.$colors_html.'
		</div>
		'.cf_kuler_color_picker($colors_html).'
		<form id="cf_kuler_settings_form" name="cf_kuler_settings_form" action="'.admin_url('themes.php').'" method="post">
			<input type="hidden" name="cf_action" value="cf_kuler_update_settings" />
			<input type="hidden" name="cf_kuler_colors" id="cf_kuler_colors" value="'.$colors.'" />
			<div id="cf-kuler-theme-info">
				');
	if (!empty($settings['theme'])) {
		echo cf_kuler_theme_fields($settings['theme']);
	}
	print('
			</div>
			<p>
				<span><input type="submit" name="submit_button" value="'.__('Save Settings', 'cf-kuler').'" class="button-primary" /></span>
			</p>
		');
		wp_nonce_field('cf_kuler_update_settings');
		print('
		</form>
	</div><!-- .cfcp-section -->');
	
	print('<div class="cfcp-section">
	<h3 class="cfcp-section-title"><span>Preset Schemes</span></h3>
	'.cf_kuler_preset_panel().'
	</div><!-- .cfcp-section -->');
	
	print('</div><!-- .cf-kuler-wrap -->');
}

function cf_kuler_update_settings() {
	if (!current_user_can('manage_options')) {
		return;
	}
// update options
}

/* API endpoints

rss/get.cfm?listType=[listType]&startIndex=[startIndex]&itemsPerPage=[itemsPerPage]&timeSpan=[timeSpan]&key=[key]

Get highest-rated feeds
http://kuler-api.adobe.com/rss/get.cfm?listtype=rating

Get most popular feeds for the last 30 days
http://kuler-api.adobe.com/rss/get.cfm?listtype=popular&timespan=30

Get most recent feeds
http://kuler-api.adobe.com/rss/get.cfm?listtype=recent


rss/search.cfm?searchQuery=[searchQuery]&startIndex=[startIndex]&itemsPerPage=[itemsPerPage]&key=[key]

Search for themes with the word "blue" in the name, tags, user name, etc.
http://kuler-api.adobe.com/rss/search.cfm?searchQuery=blue

Search for themes tagged as "sunset"
http://kuler-api.adobe.com/rss/search.cfm?searchQuery=tag:sunset

*/

//a:23:{s:11:"plugin_name";N;s:10:"plugin_uri";N;s:18:"plugin_description";N;s:14:"plugin_version";N;s:6:"prefix";s:8:"cf_kuler";s:12:"localization";N;s:14:"settings_title";s:14:"Color Settings";s:13:"settings_link";s:6:"Colors";s:4:"init";b:0;s:7:"install";b:0;s:9:"post_edit";b:0;s:12:"comment_edit";b:0;s:6:"jquery";b:0;s:6:"wp_css";b:0;s:5:"wp_js";b:0;s:9:"admin_css";s:1:"1";s:8:"admin_js";s:1:"1";s:8:"meta_box";b:0;s:15:"request_handler";b:0;s:6:"snoopy";b:0;s:11:"setting_cat";b:0;s:14:"setting_author";b:0;s:11:"custom_urls";b:0;}
