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

/**
 * Die with custom error page if it exists
 *
 * @param string $str String to die with if no file found
 * 
**/
function cfct_die($str = '') {
	if (!empty($str)) {
		if (file_exists(CFCT_PATH.'error/exit.php')) {
			include(CFCT_PATH.'error/exit.php');
			die();
		}
		else {
			wp_die($str);
		}
	}
}

/**
 * Display custom banners for alerts
 *
 * @param string $str String to display if no banner file found
 * 
**/
function cfct_banner($str = '') {
	if (!empty($str)) {
		if (file_exists(CFCT_PATH.'misc/banner.php')) {
			include(CFCT_PATH.'misc/banner.php');
		}
		else {
			echo '<p>'.$str.'</p>';
		}
	}
}

/**
 * Get a Carrington Framework option, load the default otherwise
 *
 * @param string $name Name of the option to retrieve
 * @return mixed Value of the option
 * 
**/
function cfct_get_option($name) {
	$defaults = array(
		cfct_option_name('login_link_enabled') => 'yes',
		cfct_option_name('copyright') => sprintf(__('Copyright &copy; %s &nbsp;&middot;&nbsp; %s', 'carrington'), date('Y'), get_bloginfo('name')),
		cfct_option_name('credit') => 'yes',
		cfct_option_name('lightbox') => 'yes',
		cfct_option_name('header_image') => 0,
	);
	$name = cfct_option_name($name);
	
	$defaults = apply_filters('cfct_option_defaults', $defaults);
	$value = get_option($name);
	
	
	// We want to check for defaults registered using the prefixed and unprefixed versions of the option name.
	if ($value === false) {
		$prefix = cfct_get_option_prefix();
		$basname = substr($name, strlen($prefix) + 1, -1);
		
		if (isset($defaults[$name])) {
			$value = $defaults[$name];
		}
		else if (isset($basename) && isset($defaults[$basename])) {
			$value = $defaults[$basename];
		}
	}
	return $value;
}

/**
 * Load theme plugins
 * 
**/
function cfct_load_plugins() {
	$files = cfct_files(CFCT_PATH.'plugins');
	if (count($files)) {
		foreach ($files as $file) {
			if (file_exists(CFCT_PATH.'plugins/'.$file)) {
				include_once(CFCT_PATH.'plugins/'.$file);
			}
// child theme support
			if (file_exists(STYLESHEETPATH.'/plugins/'.$file)) {
				include_once(STYLESHEETPATH.'/plugins/'.$file);
			}
		}
	}
}

/**
 * Return the default file to use for a given directory
 *
 * @param string $dir Directory to get the default file for
 * @return string Filename pertaining to the default file
 * 
**/
function cfct_default_file($dir) {
	$fancy = $dir.'-default.php';
	file_exists(CFCT_PATH.$dir.'/'.$fancy) ? $default = $fancy : $default = 'default.php';
	return $default;
}

/**
 * Return the context of the current page
 *
 * @return string The context of the current page
 * 
**/

function cfct_context() {
	$context = 'home';
	if (is_home()) {
		$context = 'home';
	}
	else if (is_page()) {
		$context = 'page';
	}
	else if (is_single()) {
		$context = 'single';
	}
	else if (is_category()) {
		$context = 'category';
	}
	else if (is_tag()) {
		$context = 'tag';
	}
	else if (is_tax()) {
		$context = 'taxonomy';
	}
	else if (is_author()) {
		$context = 'author';
	}
	else if (is_archive()) {
// possible future abstraction for:
// 	is_month()
// 	is_year()
// 	is_day()
		$context = 'archive';
	}
	else if (is_search()) {
		$context = 'search';
	}
	else if (is_404()) {
		$context = '404';
	}
	return apply_filters('cfct_context', $context);
}

/**
 * Get the filename for a given directory, type and keys
 *
 * @param string $dir Folder name of file
 * @param string $type File name of file
 * @param array $keys Keys that could be used for additional filename params
 * @return mixed Path to the file, false if file does not exist
 *
 */
function cfct_filename($dir, $type = 'default', $keys = array()) {
	switch ($type) {
		case 'author':
			if (count($keys)) {
				$file = 'author-'.$keys[0];
			}
			else {
				$file = 'author';
			}
			break;
		case 'category':
			if (count($keys)) {
				$file = 'cat-'.$keys[0];
			}
			else {
				$file = 'category';
			}
			break;
		case 'tag':
			if (count($keys)) {
				$file = 'tag-'.$keys[0];
			}
			else {
				$file = 'tag';
			}
			break;
		case 'meta':
			if (count($keys)) {
				foreach ($keys as $k => $v) {
					if (!empty($v)) {
						$file = 'meta-'.$k.'-'.$v;
					}
					else {
						$file = 'meta-'.$k;
					}
					break;
				}
			}
			break;
		case 'user':
			if (count($keys)) {
				$file = 'user-'.$keys[0];
			}
			break;
		case 'role':
			if (count($keys)) {
				$file = 'role-'.$keys[0];
			}
			break;
		case 'parent':
			if (count($keys)) {
				$file = 'parent-'.$keys[0];
			}
			break;
		case 'taxonomy':
			switch (count($keys)) {
				case 1:
					$file = 'tax-'.$keys[0];
					break;
				case 2:
					$file = 'tax-'.$keys[0].'-'.$keys[1];
					break;
				default:
					break;
			}
			break;
		default:
		// handles page, etc.
			$file = $type;
	}
	// fallback for category, author, tag, etc.
	// child theme path
	$path = STYLESHEETPATH.'/'.$dir.'/'.$file.'.php';
	// check for child theme first
	if (!file_exists($path)) {
		// use parent theme if no child theme file found
		$path = CFCT_PATH.$dir.'/'.$file.'.php';
	}
	if (!file_exists($path)) {
		switch ($type) {
			case 'author':
			case 'category':
			case 'tag':
			case 'taxonomy':
				// child theme path
				$path = STYLESHEETPATH.'/'.$dir.'/archive.php';
				if (!file_exists($path)) {
					// use parent theme if no child theme file found
					$path = CFCT_PATH.$dir.'/archive.php';
				}
		}
	}
	$default = CFCT_PATH.$dir.'/'.cfct_default_file($dir);
	if (file_exists($path)) {
		$path = $path;
	}
	else if (file_exists($default)) {
		$path = $default;
	}
	else {
		$path = false;
	}
	return apply_filters('cfct_filename', $path);
}

/**
 * Include a specific file based on context, directory and keys
 * 
 * @param string $dir 
 * @param array $keys Keys used to help build the filename
 * 
**/
function cfct_template($dir, $keys = array()) {
	$context = cfct_context();
	$file = cfct_filename($dir, $context, $keys);
	if ($file) {
		include($file);
	}
	else {
		cfct_die('Error loading '.$dir.' '.__LINE__);
	}
}

/**
 * Include a specific file based on directory and filename
 * 
 * @param string $dir Directory the file will be in
 * @param string $file Filename
 * @param array $data pass in data to be extracted for use by the template
 * 
**/
function cfct_template_file($dir, $file, $data = array()) {
	$path = '';
	if (!empty($file)) {
		$file = basename($file, '.php');
		/* Check for file in the child theme first
		var name is deliberately funny. Avoids inadvertantly
		overwriting path variable with extract() below. */
		$_cfct_filepath = STYLESHEETPATH.'/'.$dir.'/'.$file.'.php';
		if (!file_exists($_cfct_filepath)) {
			$_cfct_filepath = CFCT_PATH.$dir.'/'.$file.'.php';
		}
	}
	if (file_exists($_cfct_filepath)) {
		/* Extract $data as late as possible, so we don't accidentally overwrite
		local function vars */
		extract($data);
		include($_cfct_filepath);
	}
	else {
		cfct_die('Error loading '.$file.' '.__LINE__);
	}
}

/**
 * Include a specific file based on directory and filename and return the output
 * 
 * @param string $dir Directory the file will be in
 * @param string $file Filename
 * @param array $data pass in data to be extracted for use by the template
 * 
**/
function cfct_template_content($dir, $file, $data = array()) {
	ob_start();
	cfct_template_file($dir, $file, $data);
	return ob_get_clean();
}

/**
 * Gets the proper filename (path) to use in displaying a template
 * 
 * @param string $dir Directory to use/search in
 * @return string Path to the file
 * 
**/
function cfct_choose_general_template($dir) {
	$exec_order = array(
		'author',
		'role',
		'category',
		'taxonomy',
		'tag',
		'type',
		'single',
		'default'
	);
	$exec_order = apply_filters('cfct_general_match_order', $exec_order);
	$files = cfct_files(CFCT_PATH.$dir);
	foreach ($exec_order as $func_name) {
		if (!function_exists($func_name)) {
			$func_name = 'cfct_choose_general_template_'.$func_name;
		}
		if (function_exists($func_name)) {
			$filename = $func_name($dir, $files);
			if ($filename != false) {
				break;
			}
		}
	}
	return apply_filters('cfct_choose_general_template', $filename, $dir);
}

/**
 * Gets the proper filename (path) to use for displaying a page based on an author's name
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to loop through
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_general_template_author($dir, $files) {
	$files = cfct_author_templates($dir, $files);
	if (count($files)) {
		$username = get_query_var('author_name');
		if (empty($username)) {
			$user = new WP_User(get_query_var('author'));
			$username = $user->user_login;
		}
		$filename = 'author-'.$username.'.php';
		if (in_array($filename, $files)) {
			$keys = array($username);
			return cfct_filename($dir, 'author', $keys);
		}
 	}
	return false;
}

/**
 * Gets the proper filename (path) to use for displaying a page based on a category's slug
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to loop through
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_general_template_category($dir, $files) {
	$files = cfct_cat_templates($dir, $files);	
	if (count($files)) {
		global $cat;
		$slug = cfct_cat_id_to_slug($cat);
		if (in_array('cat-'.$slug.'.php', $files)) {
			$keys = array($slug);
			return cfct_filename($dir, 'category', $keys);
		}
 	}
	return false;
}

/**
 * Gets the proper filename (path) to use for displaying a page based on a custom taxonomy and a slug within that taxonomy
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to loop through
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_general_template_taxonomy($dir, $files) {
	$files = cfct_tax_templates($dir, $files);
	if (count($files)) {
		$tax = get_query_var('taxonomy');
		$term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
		if (!empty($term) && in_array('tax-'.$tax.'-'.$term->slug.'.php', $files)) {
			$keys = array($tax, $term->slug);
			return cfct_filename($dir, 'taxonomy', $keys);
		}
	}
	return false;
}

/**
 * Gets the proper filename (path) to use for displaying a page based on a tag slug
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to loop through
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_general_template_tag($dir, $files) {
	$files = cfct_tag_templates($dir, $files);
	if (count($files)) {
		$tag = get_query_var('tag');
		if (in_array('tag-'.$tag.'.php', $files)) {
			$keys = array($tag);
			return cfct_filename($dir, 'tag', $keys);
		}
 	}
	return false;
}

/**
 * Gets the proper filename (path) to use for displaying a page based on custom post type
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to loop through
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_general_template_type($dir, $files) {
	$files = cfct_type_templates($dir, $files);
	if (count($files)) {
		$type = get_query_var('post_type');
		$file = 'type-'.$type.'.php';
		if (in_array($file, $files)) {
			return $file;
		}
 	}
	return false;
}

/**
 * Gets the proper filename (path) to use for displaying a page based on a user's role
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to loop through
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_general_template_role($dir, $files) {
	$files = cfct_role_templates($dir, $files);
	if (count($files)) {
		$username = get_query_var('author_name');
		$user = new WP_User(cfct_username_to_id($username));
		if (!empty($user->user_login)) {
			if (count($user->roles)) {
				foreach ($user->roles as $role) {
					$role_file = 'role-'.$role.'.php';
					if (in_array($role_file, $files)) {
						return $role_file;
					}
				}
			}
		}
 	}
	return false;
}

/**
 * Gets the proper filename (path) to use for displaying a page based on whether or not it is a single page and its general context
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to loop through
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_general_template_single($dir, $files) {
	if (cfct_context() == 'single') {
		$files = cfct_single_templates($dir, $files);
		if (count($files)) {
// check to see if we're in the loop.
			global $post;
			$orig_post = $post;
			while (have_posts()) {
				the_post();
				$filename = cfct_choose_single_template($files, 'single-*');
				if (!$filename) {
					if (file_exists(CFCT_PATH.$dir.'/single.php')) {
						$filename = 'single.php';
					}
				}
			}
			rewind_posts();
			$post = $orig_post;
			return $filename;
		}
	}
	return false;
}

/**
 * Gets the proper filename (path) to use for displaying a default page based on context
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to loop through
 * @return mixed path to the file, false if no file exists
 * 
**/
function cfct_choose_general_template_default($dir, $files) {
	$context = cfct_context();
	$keys = array();
	if ($context == 'taxonomy') {
		$keys = array(get_query_var('taxonomy'));
	}
	return cfct_filename($dir, $context, $keys);
}

/**
 * Chooses which template to display for the single context
 * 
 * @param array $files A list of files to search through to find the correct template
 * @param string $filter Used in filtering the filename
 * @param string $dir The directory to search for matching files in
 * @return mixed path to the file, false if no file exists
 * 
**/
function cfct_choose_single_template($files = array(), $filter = '*', $dir = '') {
// must be called within the_loop - cfct_choose_general_template_single() approximates a loop for this reason.
	$exec_order = array(
		'author',
		'meta',
		'format',
		'category',
		'taxonomy',
		'type',
		'role',
		'tag',
		'parent', // for pages
		'default',
	);
	$exec_order = apply_filters('cfct_single_match_order', $exec_order);
	$filename = false;
	foreach ($exec_order as $func_name) {
		if (!function_exists($func_name)) {
			$func_name = 'cfct_choose_single_template_'.$func_name;
		}
		if (function_exists($func_name)) {
			$filename = $func_name($dir, $files, $filter);
			if ($filename !== false) {
				break;
			}
		}
	}
	return apply_filters('cfct_choose_single_template', $filename);
}

 /**
 * Chooses which template to display for the single context based on custom post type
 * 
 * @param string $dir Directory to search through for files
 * @param array $files A list of files to search through to find the correct template
 * @param string $filter Used in filtering the filename
 * @return mixed path to the file, false if no file exists
 * 
**/
function cfct_choose_single_template_type($dir, $files, $filter) {
	$type_files = cfct_type_templates($dir, $files, $filter);
	if (count($type_files)) {
		global $post;
		$file = cfct_filename_filter('type-'.$post->post_type.'.php', $filter);
		if (in_array($file, $type_files)) {
			return $file;
		}
	}
	return false;
}

/**
 * Chooses which template to display for the single context based on author login
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to search through to find the correct template
 * @param string $filter Used in filtering the filename
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_single_template_author($dir, $files, $filter) {
	$author_files = cfct_author_templates($dir, $files, $filter);
	if (count($author_files)) {
		$author = get_the_author_meta('login');
		$file = cfct_filename_filter('author-'.$author.'.php', $filter);
		if (in_array($file, $author_files)) {
			return $file;
		}
	}
	return false;
}

/**
 * Chooses which template to display for the single context based on meta information
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to search through to find the correct template
 * @param string $filter Used in filtering the filename
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_single_template_meta($dir, $files, $filter) {
	global $post;
	$meta_files = cfct_meta_templates('', $files, $filter);
	if (count($meta_files)) {
		$meta = get_post_custom($post->ID);
		if (count($meta)) {
// check key, value matches first
			foreach ($meta as $k => $v) {
				$val = $v[0];
				$file = cfct_filename_filter('meta-'.$k.'-'.$val.'.php', $filter);
				if (in_array($file, $meta_files)) {
					return $file;
				}
			}
// check key matches only
			if (!$filename) {
				foreach ($meta as $k => $v) {
					$file = cfct_filename_filter('meta-'.$k.'.php', $filter);
					if (in_array($file, $meta_files)) {
						return $file;
					}
				}
			}
		}
	}
	return false;
}

/**
 * Chooses which template to display for the single context based on post format
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to search through to find the correct template
 * @param string $filter Used in filtering the filename
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_single_template_format($dir, $files, $filter) {
	global $post;
	$format_files = cfct_format_templates($dir, $files, $filter);	
	if (count($format_files)) {
		$post_format = get_post_format($post->ID);
		foreach ($format_files as $file) {
			if (cfct_format_filename_to_format($file) == $post_format) {
				return cfct_filename_filter($file, $filter);
			}
		}
	}
	return false;
}

/**
 * Chooses which template to display for the single context based on category slug
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to search through to find the correct template
 * @param string $filter Used in filtering the filename
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_single_template_category($dir, $files, $filter) {
	$cat_files = cfct_cat_templates($dir, $files, $filter);
	if (count($cat_files)) {
		foreach ($cat_files as $file) {
			$cat_id = cfct_cat_filename_to_id($file);
			if (in_category($cat_id)) {
				return $file;
			}
		}
	}
	return false;
}

/**
 * Chooses which template to display for the single context based on user role
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to search through to find the correct template
 * @param string $filter Used in filtering the filename
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_single_template_role($dir, $files, $filter) {
	$role_files = cfct_role_templates($dir, $files, $filter);
	if (count($role_files)) {
		$user = new WP_User(get_the_author_meta('ID'));
		if (count($user->roles)) {
			foreach ($role_files as $file) {
				$file = cfct_filename_filter($file, $filter);
				foreach ($user->roles as $role) {
					if (cfct_role_filename_to_name($file) == $role) {
						return $file;
					}
				}
			}
		}
	}
	return false;
}

/**
 * Chooses which template to display for the single context based on taxonomy name and slug within that taxonomy
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to search through to find the correct template
 * @param string $filter used in filtering the filename
 * @return mixed path to the file, false if no file exists
 * 
**/
function cfct_choose_single_template_taxonomy($dir, $files, $filter) {
	global $post;

	$tax_files = cfct_tax_templates($dir, $files, $filter);
	if (count($tax_files)) {
		foreach ($tax_files as $file) {
			$file = cfct_filename_filter($file, $filter);
			$tax = cfct_tax_filename_to_tax_name($file);
			$file_slug = cfct_tax_filename_to_slug($file);
			$terms = wp_get_post_terms($post->ID, $tax);
			if (is_array($terms) && count($terms)) {
				foreach ($terms as $term) {
					if ($term->taxonomy == $tax && $term->slug == $file_slug) {
						return $file;
					}
				}
			}
		}
	}
	return false;
}

/**
 * Chooses which template to display for the single context based
 * on post_tag
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to search through to find the correct template
 * @param string $filter Used in filtering the filename
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_single_template_tag($dir, $files, $filter) {
	global $post;
	$tag_files = cfct_tag_templates($dir, $files, $filter);
	if (count($tag_files)) {
		$tags = get_the_tags($post->ID);
		if (is_array($tags) && count($tags)) {
			foreach ($tag_files as $file) {
				$file = cfct_filename_filter($file, $filter);
				foreach ($tags as $tag) {
					if ($tag->slug == cfct_tag_filename_to_name($file)) {
						return $file;
					}
				}
			}
		}
	}
	return false;
}

/**
 * Chooses which template to display for the single context based on a post's parent
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to search through to find the correct template
 * @param string $filter Used in filtering the filename
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_single_template_parent($dir, $files, $filter) {
	global $post;
	$parent_files = cfct_parent_templates($dir, $files, $filter);
	if (count($parent_files) && $post->post_parent > 0) {
		$parent = cfct_post_id_to_slug($post->post_parent);
		$file = cfct_filename_filter('parent-'.$parent.'.php', $filter);
		if (in_array($file, $parent_files)) {
			return $file;
		}
	}
	return false;
}

/**
 * Chooses which template to display for the content context
 * 
 * @param string $content Used in filtering and default if no template file can be found
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_content_template($type = 'content') {
	$files = cfct_files(CFCT_PATH.$type);
	$filename = cfct_choose_single_template($files);
	if (!$filename && cfct_context() == 'page' && file_exists(CFCT_PATH.$type.'/page.php')) {
		$filename = 'page.php';
	}
	if (!$filename) {
		$filename = cfct_default_file($type);
	}
	return apply_filters('cfct_choose_content_template', $filename, $type);
}

/**
 * Handle content template selection for feed requests. Leverages single context with a feed- prefix.
 * 
 * @param string $dir Directory to use for selecting the template file
 * @param array $files A list of files to loop through
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_content_template_feed($type = 'content') {
	$files = cfct_files(CFCT_PATH.$type);
	$files = cfct_filter_files($files, 'feed-');
	if (count($files)) {
		$filename = cfct_choose_single_template($files, 'feed-*');
		return $filename;
	}
	return false;
}


/**
 * Chooses which template to display for the comment context
 * 
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_comment_template() {
	$exec_order = array(
		'ping',
		'author',
		'user',
		'meta',
		'role',
		'default',
	);
	$exec_order = apply_filters('cfct_comment_match_order', $exec_order);
	$files = cfct_files(CFCT_PATH.'comment');
	foreach ($exec_order as $func_name) {
		if (!function_exists($func_name)) {
			$func_name = 'cfct_choose_comment_template_'.$func_name;
		}
		if (function_exists($func_name)) {
			$filename = $func_name($files);
			if ($filename != false) {
				break;
			}
		}
	}
	return apply_filters('cfct_choose_comment_template', $filename);
}

/**
 * Chooses which template to display for the comment context based on whether or not the comment is a ping or trackback
 * 
 * @param array $files A list of files to search through to find the correct template
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_comment_template_ping($files) {
	global $comment;
	if (in_array('ping.php', $files)) {
		switch ($comment->comment_type) {
			case 'pingback':
			case 'trackback':
				return 'ping';
				break;
		}
	}
	return false;
}

/**
 * Chooses which template to display for the comment context based on meta data
 * 
 * @param array $files A list of files to search through to find the correct template
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_comment_template_meta($files) {
	global $comment;
	$meta_files = cfct_meta_templates('', $files);
	if (count($meta_files)) {
		$meta = get_metadata('comment', $comment->comment_ID);
		if (count($meta)) {
// check key, value matches first
			foreach ($meta as $k => $v) {
				$val = $v[0];
				$file = 'meta-'.$k.'-'.$val.'.php';
				if (in_array($file, $meta_files)) {
					return $file;
				}
			}
// check key matches only
			if (!$filename) {
				foreach ($meta as $k => $v) {
					$file = 'meta-'.$k.'.php';
					if (in_array($file, $meta_files)) {
						return $file;
					}
				}
			}
		}
	}
	return false;
}

/**
 * Chooses which template to display for the comment context based on the post author
 * 
 * @param array $files A list of files to search through to find the correct template
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_comment_template_author($files) {
	global $post, $comment;
	if (!empty($comment->user_id) && $comment->user_id == $post->post_author && in_array('author.php', $files)) {
		return 'author';
 	}
	return false;
}

/**
 * Chooses which template to display for the comment context based on the comment author
 * 
 * @param array $files A list of files to search through to find the correct template
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_comment_template_user($files) {
	global $comment;
	$files = cfct_comment_templates('user', $files);
	if (count($files) && !empty($comment->user_id)) {
		$user = new WP_User($comment->user_id);
		if (!empty($user->user_login)) {
			$user_file = 'user-'.$user->user_login.'.php';
			if (in_array($user_file, $files)) {
				return $user_file;
			}
		}
 	}
	return false;
}

/**
 * Chooses which template to display for the comment context based on comment author's role
 * 
 * @param array $files A list of files to search through to find the correct template
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_comment_template_role($files) {
	global $comment;
	$files = cfct_comment_templates('role', $files);
	if (count($files) && !empty($comment->user_id)) {
		$user = new WP_User($comment->user_id);
		if (!empty($user->user_login)) {
			if (count($user->roles)) {
				foreach ($user->roles as $role) {
					$role_file = 'role-'.$role.'.php';
					if (in_array($role_file, $files)) {
						return $role_file;
					}
				}
			}
		}
 	}
	return false;
}

/**
 * Chooses the default template to be used in the comment context
 * 
 * @param array $files A list of files to search through to find the correct template
 * @return mixed Path to the file, false if no file exists
 * 
**/
function cfct_choose_comment_template_default($files) {
	return cfct_default_file('comment');
}

/**
 * Adds to a filename based on a filter string
 * 
 * @param string $filename Filename to filter
 * @param string $filter What to add
 * @return string The filtered filename
 * 
**/
function cfct_filename_filter($filename, $filter) {
	// check for filter already appended
	if (substr($filename, 0, strlen($filter) - 1) == str_replace('*', '', $filter)) {
		return $filename;
	}
	return str_replace('*', $filename, $filter);
}

/**
 * Get a list of php files within a given path as well as files in corresponding child themes
 * 
 * @param sting $path Path to the directory to search
 * @return array Files within the path directory
 * 
**/
function cfct_files($path) {
	$files = apply_filters('cfct_files_'.$path, false);
	if ($files) {
		return $files;
	}
	$files = wp_cache_get('cfct_files_'.$path, 'cfct');
	if ($files) {
		return $files;
	}
	$files = array();
	$paths = array($path);
	if (STYLESHEETPATH.'/' != CFCT_PATH) {
		// load child theme files
		$paths[] = STYLESHEETPATH.'/'.str_replace(CFCT_PATH, '', $path);
	}
	foreach ($paths as $path) {
		if (is_dir($path) && $handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				$path = trailingslashit($path);
				if (is_file($path.$file) && strtolower(substr($file, -4, 4)) == ".php") {
					$files[] = $file;
				}
			}
			closedir($handle);
		}
	}
	$files = array_unique($files);
	wp_cache_set('cfct_files_'.$path, $files, 'cfct', 3600);
	return $files;
}

/**
 * Filters a list of files based on a prefix
 * 
 * @param array $files A list of files to be filtered
 * @param string $prefix A string to search for and filter with in the filenames
 * @return array A list of files that contain the prefix
 * 
**/
function cfct_filter_files($files = array(), $prefix = '') {
	$matches = array();
	if (count($files)) {
		foreach ($files as $file) {
			if (strpos($file, $prefix) === 0) {
				$matches[] = $file;
			}
		}
	}
	return $matches;
}

/**
 * Get a list of files that match the meta template structure
 * 
 * @param string $dir Directory to search through for files if none are given
 * @param array $files A list of files to search through
 * @return array List of files that match the meta template structure
 * 
**/
function cfct_meta_templates($dir, $files = null, $filter = '*') {
	if (is_null($files)) {
		$files = cfct_files(CFCT_PATH.$dir);
	}
	$prefix = str_replace('*', '', $filter).'meta-';
	$matches = cfct_filter_files($files, $prefix);
	return apply_filters('cfct_meta_templates', $matches);
}

/**
 * Get a list of files that match the category template structure
 * 
 * @param string $dir Directory to search through for files if none are given
 * @param array $files A list of files to search through
 * @return array List of files that match the category template structure
 * 
**/
function cfct_cat_templates($dir, $files = null, $filter = '*') {
	if (is_null($files)) {
		$files = cfct_files(CFCT_PATH.$dir);
	}
	$prefix = str_replace('*', '', $filter).'cat-';
	$matches = cfct_filter_files($files, $prefix);
	return apply_filters('cfct_cat_templates', $matches);
}

/**
 * Get a list of files that match the tag template structure
 * 
 * @param string $dir Directory to search through for files if none are given
 * @param array $files A list of files to search through
 * @return array List of files that match the tag template structure
 * 
**/
function cfct_tag_templates($dir, $files = null, $filter = '*') {
	if (is_null($files)) {
		$files = cfct_files(CFCT_PATH.$dir);
	}
	$prefix = str_replace('*', '', $filter).'tag-';
	$matches = cfct_filter_files($files, $prefix);
	return apply_filters('cfct_tag_templates', $matches);
}

/**
 * Get a list of files that match the custom taxonomy template structure
 * 
 * @param string $dir Directory to search through for files if none are given
 * @param array $files A list of files to search through
 * @return array List of files that match the custom taxonomy template structure
 * 
**/
function cfct_tax_templates($dir, $files = null, $filter = '*') {
	if (is_null($files)) {
		$files = cfct_files(CFCT_PATH.$dir);
	}
	$prefix = str_replace('*', '', $filter).'tax-';
	$matches = cfct_filter_files($files, $prefix);
	return apply_filters('cfct_tax_templates', $matches);
}

/**
 * Get a list of files that match the post format structure
 * 
 * @param string $dir Directory to search through for files if none are given
 * @param array $files A list of files to search through
 * @return array List of files that match the post format template structure
 * 
**/
function cfct_format_templates($dir, $files = null, $filter = '*') {
	if (is_null($files)) {
		$files = cfct_files(CFCT_PATH.$dir);
	}
	$prefix = str_replace('*', '', $filter).'format-';
	$matches = cfct_filter_files($files, $prefix);
	return apply_filters('cfct_format_templates', $matches);
}

/**
 * Get a list of files that match the author template structure
 * 
 * @param string $dir Directory to search through for files if none are given
 * @param array $files A list of files to search through
 * @return array list of files that match the author template structure
 * 
**/
function cfct_author_templates($dir, $files = null, $filter = '*') {
	if (is_null($files)) {
		$files = cfct_files(CFCT_PATH.$dir);
	}
	$prefix = str_replace('*', '', $filter).'author-';
	$matches = cfct_filter_files($files, $prefix);
	return apply_filters('cfct_author_templates', $matches);
}

/**
 * Get a list of files that match the custom post type template structure
 * 
 * @param string $dir Directory to search through for files if none are given
 * @param array $files A list of files to search through
 * @return array List of files that match the custom post type template structure
 * 
**/
function cfct_type_templates($dir, $files = null, $filter = '*') {
	if (is_null($files)) {
		$files = cfct_files(CFCT_PATH.$dir);
	}
	$prefix = str_replace('*', '', $filter).'type-';
	$matches = cfct_filter_files($files, $prefix);
	return apply_filters('cfct_type_templates', $matches);
}

/**
 * Get a list of files that match the user role template structure
 * 
 * @param string $dir Directory to search through for files if none are given
 * @param array $files A list of files to search through
 * @return array List of files that match the user role template structure
 * 
**/
function cfct_role_templates($dir, $files = null, $filter = '*') {
	if (is_null($files)) {
		$files = cfct_files(CFCT_PATH.$dir);
	}
	$prefix = str_replace('*', '', $filter).'role-';
	$matches = cfct_filter_files($files, $prefix);
	return apply_filters('cfct_role_templates', $matches);
}

/**
 * Get a list of files that match the post parent template structure
 * 
 * @param string $dir Directory to search through for files if none are given
 * @param array $files A list of files to search through
 * @return array List of files that match the post parent template structure
 * 
**/
function cfct_parent_templates($dir, $files = null, $filter = '*') {
	if (is_null($files)) {
		$files = cfct_files(CFCT_PATH.$dir);
	}
	$prefix = str_replace('*', '', $filter).'parent-';
	$matches = cfct_filter_files($files, $prefix);
	return apply_filters('cfct_parent_templates', $matches);
}

/**
 * Get a list of files that match the single template structure
 * 
 * @param string $dir Directory to search through for files if none are given
 * @param array $files A list of files to search through
 * @return array List of files that match the single template structure
 * 
**/
function cfct_single_templates($dir, $files = null) {
	if (is_null($files)) {
		$files = cfct_files(CFCT_PATH.$dir);
	}
	$matches = cfct_filter_files($files, 'single');
	return apply_filters('cfct_single_templates', $matches);
}

/**
 * Get a list of files from list that should be used in feed consideration 
 * 
 * @param string $dir Directory to search through for files if none are given
 * @param array $files A list of files to search through
 * @return array List of files that match the single template structure
 * 
**/
function cfct_feed_templates($dir, $files = null) {
	if (is_null($files)) {
		$files = cfct_files(CFCT_PATH.$dir);
	}
	$matches = cfct_filter_files($files, 'feed');
	return apply_filters('cfct_feed_templates', $matches);
}

/**
 * Get a list of files that match the comment template structure for a given type
 * 
 * @param string $type The type of template to search for
 * @param array $files A list of files to search through
 * @return array List of files that match the comment template structure for a given type
 * 
**/
function cfct_comment_templates($type, $files = false) {
	if (!$files) {
		$files = cfct_files(CFCT_PATH.'comment');
	}
	$matches = array();
	switch ($type) {
		case 'user':
			$matches = cfct_filter_files($files, 'user-');
			break;
		case 'role':
			$matches = cfct_filter_files($files, 'role-');
			break;
	}
	return apply_filters('cfct_comment_templates', $matches);
}

/**
 * Get the id of a category from the category slug of a filename
 * 
 * @param string $file Filename 
 * @return int Category id matching the category slug of the filename
 * 
**/
function cfct_cat_filename_to_id($file) {
	$cat = cfct_cat_filename_to_slug($file);
	$cat = get_category_by_slug($cat);
	return $cat->cat_ID;
}

/**
 * Get the name of a category from the category slug of a filename
 * 
 * @param string $file Filename 
 * @return string Category name matching the category slug of the filename
 * 
**/
function cfct_cat_filename_to_name($file) {
	$cat = cfct_cat_filename_to_slug($file);
	$cat = get_category_by_slug($cat);
	return $cat->name;
}

/**
 * Get the slug of a category from a filename
 * 
 * @param string $file Filename 
 * @return string Category slug
 * 
**/
function cfct_cat_filename_to_slug($file) {
	$prefixes = apply_filters('cfct_cat_filename_prefixes', array('feed-cat-', 'single-cat-', 'cat-'));
	$suffixes = apply_filters('cfct_cat_filename_suffixes', array('.php'));
	return str_replace(array_merge($prefixes, $suffixes), '', $file);
}

/**
 * Get the slug of a category from its id
 * 
 * @param int $id Category id 
 * @return string Category slug
 * 
**/
function cfct_cat_id_to_slug($id) {
	$cat = &get_category($id);
	return $cat->slug;
}

/**
 * Get the id of a user from their username
 * 
 * @param string $username A user's username
 * @return int The id of the user
 * 
**/
function cfct_username_to_id($username) {
	$user = get_user_by('ID', $username);
	return (isset($user->ID) ? $user->ID : 0);
}

/**
 * Get the slug of a tag from a filename
 * 
 * @param string $file Filename 
 * @return string Tag slug
 *  
**/
function cfct_tag_filename_to_name($file) {
	$prefixes = apply_filters('cfct_tag_filename_prefixes', array('feed-tag-', 'single-tag-', 'tag-'));
	$suffixes = apply_filters('cfct_tag_filename_suffixes', array('.php'));
	return str_replace(array_merge($prefixes, $suffixes), '', $file);
}

/**
 * Get the author from a filename
 * 
 * @param string $file Filename 
 * @return string Author
 *  
**/
function cfct_author_filename_to_name($file) {
	$prefixes = apply_filters('cfct_author_filename_prefixes', array('feed-author-', 'single-author-', 'author-'));
	$suffixes = apply_filters('cfct_author_filename_suffixes', array('.php'));
	return str_replace(array_merge($prefixes, $suffixes), '', $file);
}

/**
 * Get the role from a filename
 * 
 * @param string $file Filename 
 * @return string Role
 *  
**/
function cfct_role_filename_to_name($file) {
	$prefixes = apply_filters('cfct_role_filename_prefixes', array('feed-role-', 'single-role-', 'role-'));
	$suffixes = apply_filters('cfct_role_filename_suffixes', array('.php'));
	return str_replace(array_merge($prefixes, $suffixes), '', $file);
}

/**
 * Get the post format from a filename
 * 
 * @param string $file Filename 
 * @return string Post format
 *  
**/
function cfct_format_filename_to_format($file) {
	$prefixes = apply_filters('cfct_format_filename_prefixes', array('feed-format-', 'single-format-', 'format-'));
	$suffixes = apply_filters('cfct_format_filename_suffixes', array('.php'));
	return str_replace(array_merge($prefixes, $suffixes), '', $file);
}

/**
 * Get the taxonomy name from a filename
 * 
 * @param string $file Filename 
 * @return string Taxonomy name
 *  
**/
function cfct_tax_filename_to_tax_name($file) {
	$prefixes = apply_filters('cfct_tax_filename_prefixes', array('feed-tax-', 'single-tax-', 'tax-'));
	$suffixes = apply_filters('cfct_tax_filename_suffixes', array('.php'));
	$tax = str_replace(array_merge($prefixes, $suffixes), '', $file);
	$tax = explode('-', $tax);
	return $tax[0];
}

/**
 * Get the slug of a taxonomy from a filename
 * 
 * @param string $file Filename 
 * @return string Taxonomy slug
 *  
**/
function cfct_tax_filename_to_slug($file) {
	$prefixes = apply_filters('cfct_tax_filename_prefixes', array('feed-tax-', 'single-tax-', 'tax-'));
	$suffixes = apply_filters('cfct_tax_filename_suffixes', array('.php'));
	$slug = str_replace(array_merge($prefixes, $suffixes), '', $file);
	$slug = explode('-', $slug);
	unset($slug[0]);
	if (count($slug)) {
		return implode('-', $slug);
	}
	return '';
}

/**
 * Get the post name from its id
 * 
 * @param int $id A post id
 * @return string Post name
 *  
**/
function cfct_post_id_to_slug($id) {
	$post = get_post($id);
	return $post->post_name;
}

/**
 * Custom formatting for strings
 * 
 * @param string $str A string to be formatted
 * @return string Formatted string
 *  
**/
function cfct_basic_content_formatting($str) {
	$str = wptexturize($str);
	$str = convert_smilies($str);
	$str = convert_chars($str);
	$str = wpautop($str);
	return $str;
}

/**
 * Get an array with the path to the director of the file as well as the filename
 * 
 * @param string $path A path to a file
 * @return array Contains the directory the file is in as well as the filename
 *  
**/
function cfct_leading_dir($path) {
	$val = array(
		'dir' => '',
		'file' => ''
	);
	if (strpos($path, '/') !== false) {
		$parts = explode('/', $path);
		$val['file'] = $parts[count($parts) - 1];
		$val['dir'] = implode('/', array_slice($parts, 0, count($parts) - 1));
	}
	else {
		$val['file'] = $path;
	}
	return $val;
}

/**
 * Prevent code from breaking in WP versions < 3.1
 * 
 * 
**/
if (!function_exists('get_post_format')) {
	function get_post_format($post_id) {
		return false;
	}
}

/**
 * Generate markup for login/logout links
 * 
 * @param string $redirect URL to redirect after the login or logout
 * @param string $before Markup to display before
 * @param string $after Markup to display after
 * @return string Generated login/logout Markup
 */ 
function cfct_get_loginout($redirect = '', $before = '', $after = '') {
	if (cfct_get_option('login_link_enabled') != 'no') {
		return $before . wp_loginout($redirect, false) . $after;
	}
} 

/**
 * Recursively merges two arrays down overwriting values if keys match.
 * 
 * @param array $array_1 Array to merge into
 * @param array $array_2 Array in which values are merged from
 * 
 * @return array Merged array
 */ 
function cfct_array_merge_recursive($array_1, $array_2) {
	foreach ($array_2 as $key => $value) {
		if (isset($array_1[$key]) && is_array($array_1[$key]) && is_array($value)) {
			$array_1[$key] = cfct_array_merge_recursive($array_1[$key], $value);
		}
		else {
			$array_1[$key] = $value;
		}
	}
	
	return $array_1;
}

/**
 * Returns the options prefix
 */ 
function cfct_get_option_prefix() {
	return apply_filters('cfct_option_prefix', 'cfct');
}

/**
 * Prefix options names
 */ 
function cfct_option_name($name) {
	$prefix = cfct_get_option_prefix();
	// If its already prefixed, we don't need to do it again.
	if (strpos($name, $prefix.'_') !== 0) {
		return $prefix.'_'.$name;
	}
	else {
		return $name;
	}
}

?>