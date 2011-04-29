<?php 

/**
 * Register article post type
 */
function anno_register_post_types() {
	$single = __('Article', 'anno');
	$plural = __('Articles', 'anno');
	$labels = array(
		'name' => $single,
		'singular_name' => $single,
		'add_new_item' => sprintf(__('Add New %s', 'anno'), $single),
		'edit_item' => sprintf(__('Edit %s', 'anno'), $single),
		'new_item' => sprintf(__('New %s', 'anno'), $single),
		'view_item' => sprintf(__('View %s', 'anno'), $single),
		'search_items' => sprintf(__('Search %s', 'anno'), $plural),
		'not_found' => sprintf(__('No %s found', 'anno'), $plural),
		'not_found_in_trash' => sprintf(__('No %s found in Trash', 'anno'), $plural),
		'menu_name' => $plural,
	);
	$args = array(
	        'labels' => $labels,
	        'public' => true,
	        'show_ui' => true,
	        'has_archive' => true,
	        'hierarchical' => false,
	        'rewrite' => true,
	        'query_var' => 'articles',
	        'supports' => array('title', 'editor', 'thumbnail', 'comments', 'excerpt', 'revisions', 'author'),
			'taxonomies' => array(),
			'menu_position' => 5,
			'capability_type' => 'post'
	);
	register_post_type( 'article' , $args );
}
add_action('after_setup_theme', 'anno_register_post_types');

?>