<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2011 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */

/**
 * Registers custom taxonomies for articles, so blog post categories/tags don't mix with these.
 */
function anno_register_taxonomies() {
	// Article Categories
	$labels = array(
				'name' =>_x('Article Category', 'taxonomy single name', 'anno'),
				'singular_name' => _x('Article Category', 'taxonomy single name', 'anno'),
				'search_items' =>  _x( 'Search Article Categories', 'taxonomy label', 'anno'),
				'popular_items' => _x( 'Popular Article Categories', 'taxonomy label', 'anno'),
				'all_items' => _x( 'All Article Categories', 'taxonomy label', 'anno'),
				'edit_item' => _x( 'Edit Article Category', 'taxonomy label', 'anno'),
				'update_item' => _x( 'Update Article Category', 'taxonomy label', 'anno'),
				'add_new_item' => _x( 'Add New Article Category', 'taxonomy label', 'anno'),
				'new_item_name' => _x( 'New Article Category Name', 'taxonomy label', 'anno'),
				'add_or_remove_items' => _x( 'Add or Remove Article Categories', 'taxonomy label', 'anno'),
				'choose_from_most_used' => _x('Choose from the most used article categories', 'taxonomy label', 'anno'),
				'menu_name' => _x('Article Categories', 'taxonomy menu name', 'anno'),
			); 

	register_taxonomy('article_category', 'article', array(
		'hierarchical' => true,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => true,
	));
	
	// Article Tags
	$labels = array(
				'name' =>_x('Article Tag', 'taxonomy single name', 'anno'),
				'singular_name' => _x('Article Tag', 'taxonomy single name', 'anno'),
				'search_items' =>  _x( 'Search Article Tags', 'taxonomy label', 'anno'),
				'popular_items' => _x( 'Popular Article Tags', 'taxonomy label', 'anno'),
				'all_items' => _x( 'All Article Tags', 'taxonomy label', 'anno'),
				'edit_item' => _x( 'Edit Article Tag', 'taxonomy label', 'anno'),
				'update_item' => _x( 'Update Article Tag', 'taxonomy label', 'anno'),
				'add_new_item' => _x( 'Add New Article Tag', 'taxonomy label', 'anno'),
				'new_item_name' => _x( 'New Article Tag Name', 'taxonomy label', 'anno'),
				'add_or_remove_items' => _x( 'Add or Remove Article Tags', 'taxonomy label', 'anno'),
				'choose_from_most_used' => _x('Choose from the most used article tags', 'taxonomy label', 'anno'),
				'menu_name' => _x('Article Tags', 'taxonomy menu name', 'anno'),
			); 

	register_taxonomy('article_tag', 'article', array(
		'hierarchical' => false,
		'labels' => $labels,
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => true,
	));
	
}
add_action('after_setup_theme', 'anno_register_taxonomies', 1);

/**
 * Remove default category meta box, so we can force the selection of only one category
 */ 
function anno_remove_category_box() {
	remove_meta_box('article_categorydiv', 'article', 'side');
}
add_action('admin_head', 'anno_remove_category_box');

/**
 * Make article category selection limited to a single category by adding a new meta box
 */
function anno_add_category_single_selection() {
	add_meta_box('article_category_select', _x('Article Category', 'Meta box title', 'anno'), 'anno_article_category_dropdown', 'article', 'side', 'core');
}
add_action('admin_head', 'anno_add_category_single_selection', 1);

/**
 * Displays markup for article category dropdown
 * @todo improved formatting for child categories
 */
function anno_article_category_dropdown() {
	global $post;
	if (!empty($post)) {
		$cat = wp_get_object_terms($post->ID, 'article_category', array('fields' => 'ids'));
		if (is_array($cat) && !empty($cat)) {
			$cat = current($cat);
		}
	}
	else {
		$cat = 0;
	}

	wp_dropdown_categories(array(
		'orderby' => 'name',
 		'id' => 'article-category',
		'selected' => $cat,
		'taxonomy' => 'article_category',
		'hide_empty' => false,
		'name' => 'tax_input[article_category]',
		'depth' => 999,
	));
}

?>