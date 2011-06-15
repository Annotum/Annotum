<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2010 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

define('CFCT_DEBUG', false);
define('CFCT_PATH', trailingslashit(TEMPLATEPATH));
define('ANNO_VER', '1.0');

include_once(CFCT_PATH.'carrington-core/carrington.php');
include_once(CFCT_PATH.'functions/article-post-type.php');
include_once(CFCT_PATH.'functions/appendix-repeater.php');
include_once(CFCT_PATH.'functions/taxonomies.php');
include_once(CFCT_PATH.'functions/capabilities.php');
include_once(CFCT_PATH.'functions/featured-articles.php');
include_once(CFCT_PATH.'functions/template-tags.php');
include_once(CFCT_PATH.'plugins/load.php');

function anno_setup() {
	$path = trailingslashit(TEMPLATEPATH);

	// i18n support
	load_theme_textdomain('anno', $path . 'languages');
	$locale = get_locale();
	$locale_file = $path . '/languages/' . $locale . '.php';
	if ( is_readable( $locale_file ) ) {
		require_once( $locale_file );
	}
	
	add_theme_support('automatic-feed-links');
	add_theme_support('post-thumbnails', array( 'article', 'post' ) );
	add_image_size( 'post-excerpt', 140, 120, true);
	add_image_size( 'post-teaser', 100, 79, true);
	add_image_size( 'featured', 270, 230, true);
	
	$menus = array(
		'main' => 'Main Menu (Header)',
		'secondary' => 'Secondary Menu (Header)',
		'footer' => 'Footer Menu',
	);
	register_nav_menus($menus);
	
	$sidebar_defaults = array(
		'before_widget' => '<aside id="%1$s" class="widget clearfix %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h1 class="title">',
		'after_title' => '</h1>'
	);
	register_sidebar(array_merge($sidebar_defaults, array(
		'name' => __('Default Sidebar', 'anno'),
		'id' => 'default'
	)));
	register_sidebar(array_merge($sidebar_defaults, array(
		'name' => __('Page Sidebar', 'anno'),
		'id' => 'sidebar-page',
		'description' => __('This sidebar will be shown on Pages.', 'anno')
	)));
	register_sidebar(array_merge($sidebar_defaults, array(
		'name' => __('Article Sidebar', 'anno'),
		'id' => 'sidebar-article',
		'description' => __('This sidebar will be shown single Articles.', 'anno')
	)));
	register_sidebar(array_merge($sidebar_defaults, array(
		'name' => __('Masthead Teasers', 'anno'),
		'id' => 'masthead',
		'description' => __('Display items on the home page masthead.'),
		'before_widget' => '<aside id="%1$s" class="teaser clearfix %2$s">'
	)));

	add_action('wp_head', 'anno_css3_pie', 8);
}
add_action('after_setup_theme', 'anno_setup');

// Adding favicon, each theme has it's own which we get with stylesheet directory
function anno_favicon() {
	echo '<link rel="shortcut icon" href="'.get_bloginfo('stylesheet_directory').'/assets/main/img/favicon.ico" />';
}
add_action('wp_head', 'anno_favicon');

/**
 * Enqueue and add CSS and JS assets.
 * Hook into 'wp' action when conditional checks like is_single() are available.
 */
function anno_css3_pie() {
	$assets_root = get_bloginfo('template_url') . '/assets/main/';
	?>
	<!--[if lte IE 8]>
	<style type="text/css" media="screen">
		.featured-posts .control-panel .previous,
		.featured-posts .control-panel .next,
		.widget-recent-posts .nav .ui-tabs-selected,
		.widget-recent-posts .panel {
			behavior: url(<?php echo $assets_root; ?>js/libs/css3pie/PIE.php);
		}
	</style>
	<![endif]-->
<?php
}

/**
 * Add theme CSS, JS here. Everything should run through the enqueue system so that
 * child themes/plugins have access to override whatever they need to.
 * Run at 'wp' hook so we have access to conditional functions,
 * like is_single(), etc.
 */
function anno_assets() {
	if (!is_admin()) {
		$main =  trailingslashit(get_bloginfo('template_directory')) . 'assets/main/';
		$v = ANNO_VER;
		
		// Styles
		wp_enqueue_style('anno', $main.'css/main.css', array(), $v, 'screen');
		
		// Right-to-left languages
		if (is_rtl()) {
			// Override stylesheet
			wp_enqueue_style('anno-rtl', $main.'css/rtl.css', array('anno'), $v, 'screen');
		}
		
		// Scripts
		wp_enqueue_script('modernizr', $main.'js/libs/modernizr-1.7.min.js', array(), $v);
		wp_enqueue_script('jquery-cf-placeholder', $main.'js/libs/jquery.placeholder.min.js', array('jquery'), $v);
		wp_enqueue_script('jquery-ui-tabs');
		
		wp_enqueue_script('anno-main', $main.'js/main.js', array('jquery-cf-placeholder'), $v);
		wp_localize_script('anno-main', 'ANNO_DICTIONARY', array(
			'previous' => __('Previous', 'anno'),
			'next' => __('Next', 'anno'),
			'xofy' => __('%1$s of %2$s')
		));

		if ( is_singular() ) {
			wp_enqueue_script( 'comment-reply' );
		}
		
		/* Home page featured post cycler */
		if (is_home()) {
			wp_enqueue_script('jquery-cycle-lite', $main.'js/libs/jquery.cycle.lite.1.1.min.js', array('jquery'), $v);
		}
	}
}
add_action('wp', 'anno_assets');

/*
 * Outputs a few extra semantic tags in the <head> area.
 * Hook into 'wp_head' action.
 */
function anno_head_extra() {
	echo '<link rel="pingback" href="'.get_bloginfo('pingback_url').'" />'."\n";
	$args = array(
		'type' => 'monthly',
		'format' => 'link'
	);
	wp_get_archives($args);
}
add_action('wp_head', 'anno_head_extra');

/**
 * Register custom widgets extended from WP_Widget
 */
function anno_widgets_init() {
	include_once(CFCT_PATH . 'functions/Anno_Widget_Recently.php');
	register_widget('Anno_Widget_Recently');
}
add_action('widgets_init', 'anno_widgets_init');

/**
 * Filter the default menu arguments
 */
function anno_wp_nav_menu_args($args) {
	$args['fallback_cb'] = null;
	if ($args['container'] == 'div') {
		$args['container'] = 'nav';
	}
	if ($args['depth'] == 0) {
		$args['depth'] = 2;
	}
	if ($args['menu_class'] == 'menu') {
		$args['menu_class'] = 'nav';
	}
	
	return $args;
}
add_filter('wp_nav_menu_args', 'anno_wp_nav_menu_args');

/**
 * Filter the post class to add a .has-featured-image class when featured image
 * is present.
 * @return array $classes array of post classes
 */
function anno_post_class($classes, $class) {
	$has_img = 'has-featured-image';
	
	/* An array of classes that we want to create an additional faux compound class for.
	This lets us avoid having to do something like
	.article-excerpt.has-featured-image, which doesn't work in IE6.
	Instead, we can do .article-excerpt-has-featured-image. While a bit verbose,
	it will nonetheless do the trick. */
	$compoundify = array(
		'article-excerpt'
	);
	
	if (has_post_thumbnail()) {
		$classes[] = $has_img;
		
		foreach ($compoundify as $compound_plz) {
			if (in_array($compound_plz, $classes)) {
				$classes[] = $compound_plz . '-' . $has_img;
			}
		}
	}
	
	return $classes;
}
add_filter('post_class', 'anno_post_class', 10, 2);

/**
 * Customize comment form defaults
 */
function anno_comment_form_defaults($defaults) {
	$req = get_option( 'require_name_email' );
	$req_attr = ( $req ? ' required' : '' );
	$req_label = ( $req ? '<abbr class="required" title="'.__('Required', 'anno').'">*</abbr>' : '');
	$commenter = wp_get_current_commenter();
	
	$fields = apply_filters('comment_form_default_fields', array(
		'author' => '<p class="row author">' . '<label for="author">' . __('Your Name', 'anno') . $req_label . '</label> <input id="author" class="type-text" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '"' . $req_attr . '></p>',
		'email' => '<p class="row email"><label for="email">' . __('Email Address', 'anno') . $req_label . '</label> <input id="email" class="type-text" name="email" type="email" value="' . esc_attr(  $commenter['comment_author_email'] ) . '"' . $req_attr . '></p>',
		'url' => '<p class="row url"><label for="url">' . __( 'Website' ) . '</label> <input id="url" class="type-text" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '"></p>'
	));
	
	$new = array(
		'comment_field' => '<p class="row"><label for="comment">' . _x('Comment', 'noun', 'anno') . '</label> <textarea id="comment" name="comment" required></textarea></p>',
		'fields' => $fields,
		'cancel_reply_link' => __('(cancel)', 'anno'),
		'title_reply' => __('Leave a Comment', 'anno'),
		'title_reply_to' => __('Leave a Reply to %s', 'anno'),
		'label_submit' => __('Submit', 'anno'),
		'comment_notes_after' => '',
		'comment_notes_before' => ''
	);
	
	return array_merge($defaults, $new);
}
add_filter('comment_form_defaults', 'anno_comment_form_defaults');

/**
 * Register our custom theme settings forms with Carrington so that they are saved to options
 * table. Must run before init:10.
 */
function anno_register_settings_forms_to_save() {
	global $cfct_options;
	$cfct_options[] = 'anno_callouts';
}
add_action('init', 'anno_register_settings_forms_to_save', 9);

/**
 * Form fragment for the Carrington theme settings page
 */
function anno_settings_form_top() {
	$key = 'anno_callouts';
	$opt = get_option($key);
	?>
<table class="form-table">
	<tbody>
		<tr>
			<th><?php _ex('Home Page Callout Left', 'Label text for settings screen', 'annotum'); ?></th>
			<td>
				<p>
					<label for="<?php echo $key ?>-0-title"><?php _ex('Title', 'Label text for admin setting', 'annotum'); ?></label><br />
					<input id="<?php echo $key ?>-0-title" class="widefat" name="<?php echo $key; ?>[0][title]" value="<?php echo esc_attr($opt[0]['title']); ?>" />
				</p>
				<p>
					<label for="<?php echo $key ?>-0-url"><?php _ex('URL', 'Label text for admin setting', 'annotum'); ?></label><br />
					<input id="<?php echo $key ?>-0-url" class="widefat" name="<?php echo $key; ?>[0][url]" value="<?php echo esc_url($opt[0]['url']); ?>" />
				</p>
				<p>
					<label for="<?php echo $key ?>-0-content"><?php _ex('Content', 'Label text for admin setting', 'annotum'); ?></label><br />
					<textarea id="<?php echo $key ?>-0-content" class="widefat" name="<?php echo $key; ?>[0][content]" rows="4"><?php echo esc_textarea($opt[0]['content']); ?></textarea>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php _ex('Home Page Callout Right', 'Label text for settings screen', 'annotum'); ?></th>
			<td>
				<p>
					<label for="<?php echo $key ?>-1-title"><?php _ex('Title', 'Label text for admin setting', 'annotum'); ?></label><br />
					<input id="<?php echo $key ?>-1-title" class="widefat" name="<?php echo $key; ?>[1][title]" value="<?php echo esc_attr($opt[1]['title']); ?>" />
				</p>
				<p>
					<label for="<?php echo $key ?>-1-url"><?php _ex('URL', 'Label text for admin setting', 'annotum'); ?></label><br />
					<input id="<?php echo $key ?>-1-url" class="widefat" name="<?php echo $key; ?>[1][url]" value="<?php echo esc_url($opt[1]['url']); ?>" />
				</p>
				<p>
					<label for="<?php echo $key ?>-1-content"><?php _ex('Content', 'Label text for admin setting', 'annotum'); ?></label><br />
					<textarea id="<?php echo $key ?>-1-content" class="widefat" name="<?php echo $key; ?>[1][content]" rows="4"><?php echo esc_textarea($opt[1]['content']); ?></textarea>
				</p>
			</td>
		</tr>
	</tbody>
</table>
<?php
}
add_action('cfct_settings_form_top', 'anno_settings_form_top');

/**
 * Determines whether or not an email address is valid
 * 
 * @param string email to check
 * @return bool true if it is a valid email, false otherwise
 */ 
function anno_is_valid_email($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Returns an appropriate link for editing a given user.
 * Based on code found in WP Core 3.2
 * 
 * @param int $user_id The id of the user to get the url for
 * @return string edit user url
 */ 
function anno_edit_user_url($user_id) {
	if ( get_current_user_id() == $user_id ) {
		$edit_url = 'profile.php';
	}
	else {
		$edit_url = admin_url(esc_url( add_query_arg( 'wp_http_referer', urlencode(stripslashes($_SERVER['REQUEST_URI'])), "user-edit.php?user_id=$user_id" )));
	}
	return $edit_url;
	
}

/**
 * Function to limit front-end display of comments. 
 * Wrap this filter around comments_template();
 * 
 * @todo Update to WP_Comment_Query filter when WP updates core to use non-hardcoded queries.
 */
function anno_internal_comments_query($query) {
	$query = str_replace('WHERE', 'WHERE comment_type NOT IN (\'article_general\', \'article_review\') AND', $query);
	return $query;
}
add_filter('comment_feed_where', 'anno_internal_comments_query');

?>