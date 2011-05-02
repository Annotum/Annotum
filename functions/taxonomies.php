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
				'search_items' =>  __( 'Search', 'farecompre').' '.$plural,
				'popular_items' => __( 'Popular', 'farecompre').' '.$plural,
				'all_items' => __( 'All', 'farecompre').' '.$plural,
				'edit_item' => __( 'Edit', 'farecompre' ).' '.$single,
				'update_item' => __( 'Update', 'farecompre' ).' '.$single,
				'add_new_item' => __( 'Add New' ).' '.$single,
				'new_item_name' => __( 'New', 'farecompre' ).' '.$single.' '.__( 'Name', 'farecompre' ),
				'add_or_remove_items' => __( 'Add or remove', 'farecompre' ).' '.$plural,
				'choose_from_most_used' => __( 'Choose from the most used ', 'farecompre' ).' '.strtolower($single),
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

?>