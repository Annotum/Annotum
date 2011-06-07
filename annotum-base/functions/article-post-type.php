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
	        'supports' => array('title', 'thumbnail', 'comments', 'revisions', 'author'),
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

/**
 * Display custom messages for articles. Based on WP high 3.1.2
 */ 
function anno_post_updated_messages($messages) {
	global $post;
	// Based on message code in WP high 3.2
	$messages['article'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf(__('Article updated. <a href="%s">View article</a>', 'anno'), esc_url(get_permalink($post->ID))),
		2 => __('Custom field updated.', 'anno'),
		3 => __('Custom field deleted.', 'anno'),
		4 => __('Article updated.', 'anno'),
	 	5 => isset($_GET['revision']) ? sprintf( __('Article restored to revision from %s', 'anno'), wp_post_revision_title((int) $_GET['revision'], false )) : false,
		6 => sprintf( __('Article published. <a href="%s">View article</a>', 'anno'), esc_url(get_permalink($post->ID))),
		7 => __('Article saved.', 'anno'),
		8 => sprintf( __('Article submitted. <a target="_blank" href="%s">Preview article</a>', 'anno'), esc_url(add_query_arg('preview', 'true', get_permalink($post->ID)))),
		9 => sprintf( __('Article scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview article</a>', 'anno'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date )), esc_url( get_permalink($post->ID))),
		10 => sprintf( __('Article draft updated. <a target="_blank" href="%s">Preview article</a>', 'anno'), esc_url( add_query_arg('preview', 'true', get_permalink($post->ID)))),
		11 => sprintf( __('Article successfully cloned.', 'anno')),
		12 => sprintf( __('Unable to clone article.', 'anno')),
	);

	return $messages;
}
add_filter('post_updated_messages', 'anno_post_updated_messages');

/**
 * Add DTD Meta Boxes
 */ 
function anno_dtd_meta_boxes() {
	global $post;
	add_meta_box('subtitle', __('Subtitle', 'anno'), 'anno_subtitle_meta_box', 'article', 'normal', 'high', $post);	
	add_meta_box('body', __('Body', 'anno'), 'anno_body_meta_box', 'article', 'normal', 'high', $post);
	add_meta_box('references', __('References', 'anno'), 'anno_references_meta_box', 'article', 'normal', 'high', $post);
	add_meta_box('abstract', __('Abstract', 'anno'), 'anno_abstract_meta_box', 'article', 'normal', 'high', $post);
	add_meta_box('funding', __('Funding Statement', 'anno'), 'anno_funding_meta_box', 'article', 'normal', 'high', $post);
	add_meta_box('acknowledgements', __('Acknowledgements', 'anno'), 'anno_acknowledgements_meta_box', 'article', 'normal', 'high', $post);
	add_meta_box('appendicies', __('Appendicies', 'anno'), 'anno_appendicies_meta_box', 'article', 'normal', 'high', $post);
	add_meta_box('featured', __('Featured', 'anno'), 'anno_featured_meta_box', 'article', 'side', 'default', $post);
}
add_action('add_meta_boxes_article', 'anno_dtd_meta_boxes');

function anno_subtitle_meta_box($post) {
	$html = get_post_meta($post->ID, '_anno_subtitle', true);
?>
	<input type="text" name="anno_subtitle" value="<?php echo esc_attr($html); ?>" style="width:100%;" />
<?php
}

function anno_body_meta_box($post) {
echo 'body';
?>
	<textarea name="content" class="anno-meta"><?php echo esc_html($post->post_content); ?></textarea>
<?php
}

function anno_references_meta_box($post) {
	echo 'references placeholder';
}

function anno_abstract_meta_box($post) {
?>
	<textarea class="anno-meta" name="excerpt"><?php echo esc_html($post->post_excerpt); ?></textarea>
<?php
}

function anno_funding_meta_box($post) {
	$html = get_post_meta($post->ID, '_anno_funding', true);
?>
	<textarea class="anno-meta" name="anno_funding"><?php echo esc_html($html); ?></textarea>
<?php
}

function anno_acknowledgements_meta_box($post) {
	$html = get_post_meta($post->ID, '_anno_acknowledgements', true);
?>
	<textarea class="anno-meta" name="anno_acknowledgements"><?php echo esc_html($html); ?></textarea>
<?php
}

/**
 * Meta box markup for featuring an article in the featured carousel
 */ 
function anno_featured_meta_box($post) {
	$checked = get_post_meta($post->ID, '_featured', true);
?>
	<label for="anno-featured"><?php _e('Featured ', 'anno'); ?></label>
	<input id="anno-featured" type="checkbox" value="yes" name="featured"<?php checked($checked, 'yes', true); ?> />
<?php	
}

/**
 * Save post meta related to an article 
 */ 
function anno_article_insert_post($post_id, $post) {
	if ($post->post_type == 'article') {
//TODO nonce?
//		check_admin_referer('update-article_'.$post_id);
		
		$anno_meta = array(
			'anno_subtitle',
			'anno_funding',
			'anno_acknowledgements',
			'featured'
		);
		foreach ($anno_meta as $key) {
			// We don't care if its set, an unset post var just means it's empty
			update_post_meta($post_id, '_'.$key, $_POST[$key]);
		}
		
		$appendicies = array();
		if (is_array($_POST['anno_appendix'])) {
			foreach ($_POST['anno_appendix'] as $appendix) {
				if (!empty($appendix)) {
					$appendicies[] = $appendix;
				}
			}
		}
		update_post_meta($post_id, '_anno_appendicies', $appendicies);
	}	
}
add_action('wp_insert_post', 'anno_article_insert_post', 10, 2);


?>