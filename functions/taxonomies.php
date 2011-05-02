<?php

/**
 * Registers custom taxonomies for articles, so blog post categories/tags don't mix with these.
 */
function anno_register_taxonomies() {
		$taxonomies = array(
			array(
				'single' => __('Article Category', 'anno'),
				'plural' => __('Article Categories', 'anno'),
				'slug' => 'article_category',
			),
			array(
				'single' => __('Article Tag', 'anno'),
				'plural' => __('Article Tags', 'anno'),
				'slug' => 'article_tag',
			),
		);
	
		foreach ($taxonomies as $taxonomy) {
			$plural = $taxonomy['plural'];
			$single = $taxonomy['single'];
			$labels = array(
				'name' => $single,
				'singular_name' => $single,
				'search_items' =>  __( 'Search', 'anno').' '.$plural,
				'popular_items' => __( 'Popular', 'anno').' '.$plural,
				'all_items' => __( 'All', 'anno').' '.$plural,
				'edit_item' => __( 'Edit', 'anno' ).' '.$single,
				'update_item' => __( 'Update', 'anno' ).' '.$single,
				'add_new_item' => __( 'Add New' ).' '.$single,
				'new_item_name' => __( 'New', 'anno' ).' '.$single.' '.__( 'Name', 'anno' ),
				'add_or_remove_items' => __( 'Add or remove', 'anno' ).' '.$plural,
				'choose_from_most_used' => __( 'Choose from the most used ', 'anno' ).' '.strtolower($single),
				'menu_name' => $plural
			); 

			register_taxonomy($taxonomy['slug'], 'article', array(
				'hierarchical' => true,
				'labels' => $labels,
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => true,
			));
		}	
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
	add_meta_box('article_category_select', __('Article Category', 'anno'), 'anno_article_category_dropdown', 'article', 'side', 'core');
}
add_action('admin_head', 'anno_add_category_single_selection');

/**
 * Displays markup for article category dropdown
 */
function anno_article_category_dropdown() {
	global $post;
	if (!empty($post)) {
		$cat = wp_get_object_terms($post->ID, 'article_category', array('fields' => 'ids'));
		$cat = current($cat);
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