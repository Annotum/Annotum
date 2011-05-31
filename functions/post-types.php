<?php 

/**
 * Register article post type
 */
function anno_register_post_types() {
	if (anno_workflow_enabled()) {
		$capability_type = array('article', 'articles');
	} 
	else {
		$capability_type = 'post';
	}
	
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
			'capability_type' => $capability_type,
	);
	register_post_type('article', $args);
}
add_action('after_setup_theme', 'anno_register_post_types');

/**
 * Request handler for post types (article)
 */ 
function anno_post_type_requst_handler() {
	if (isset($_GET['anno_action'])) {
		switch ($_GET['anno_action']) {
			case 'article_css':
				anno_post_type_css();
				die();
				break;
			
			default:
				break;
		}
	}
}
add_action('admin_init', 'anno_post_type_requst_handler', 0);

/**
 * Custom CSS for article post type
 */ 
function anno_post_type_css() {
	header("Content-type: text/css");
?>
select#article-category {
	width: 90%;
}
<?php 
}

/**
 * Enqueue article styles
 */
function anno_post_type_enqueue_css() {
	wp_enqueue_style('anno-article-admin', add_query_arg('anno_action', 'article_css', admin_url()));
}
add_action('admin_print_styles', 'anno_post_type_enqueue_css');

?>