<?php

load_plugin_textdomain('cfpt');

function cfpt_get_page_title() {
	global $wp_locale, $wp_query;
	
	$messages = apply_filters('cfpt_messages', array(
		'home_paged' => __('Latest / <b>page %s</b>', 'cfpt'),
		'search' => __('Search results for <b>%s</b>', 'cfpt'),
		'tag' => __('Tag archives for <b>%s</b>', 'cfpt'),
		'category' => __('Category archives for <b>%s</b>', 'cfpt'),
		'author' => __('Author archives for <b>%s</b>'),
		'date' => __('Archives for <b>%s</b>', 'cfpt')
	));

	$vars = array(
		'paged' => get_query_var('paged'),
		'cat' => get_query_var('cat'),
		'tag' => get_query_var('tag_id'),
		's' => get_query_var('s'),
		'year' => get_query_var('year'),
		'm' => get_query_var('m'),
		'monthnum' => get_query_var('monthnum'),
		'day' => get_query_var('day'),
		'author' => get_query_var('author_name')
	);
	// Keep things kosher
	$vars = array_map('esc_html', $vars);

	extract($vars);

	$output = '';

	if (is_front_page() && is_paged()) {
		$output = sprintf($messages['home_paged'], $paged);
	}
	else if(!empty($s)) {
		$output = sprintf($messages['search'], $s);
	} else if(!empty($tag)) {
		$output = sprintf($messages['tag'], single_tag_title('', false));
	} else if(!empty($cat)) {
		$output = sprintf($messages['category'], single_cat_title('', false));
	} else if(!empty($author)) {
		$user = get_user_by('login', $author);
		if (is_object($user)) {
			$output = sprintf($messages['author'], esc_html($user->display_name));
		}
	} else if(is_archive() && !empty($year)) {
		$date = '';
		if(!empty($monthnum)) {
			$date .= $wp_locale->get_month($monthnum);
			if(!empty($day)) {
				$date .= ' ' . $day;
			}
			$date .= ', ';
		}
		$date .= $year;
		$output = sprintf($messages['date'], $date);
	}

	// If we've hit a page that has a title, output it.
	if($output) {
		return $output;
	}
}

function cfpt_page_title($before = '', $after = '') {
	if ($title = cfpt_get_page_title()) {
		echo $before . $title . $after;
	}
}
?>