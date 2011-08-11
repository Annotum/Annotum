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
	
	$labels = array(
		'name' => _x('Article', 'post type name', 'anno'),
		'singular_name' => _x('Article', 'post type singular name', 'anno'),
		'add_new_item' => _x('Add New Article', 'post type plural name', 'anno'),
		'edit_item' => _x('Edit Article', 'post type label', 'anno'),
		'new_item' => _x('New Article', 'post type label', 'anno'),
		'view_item' => _x('View Article', 'post type label', 'anno'),
		'search_items' => _x('Search Articles', 'post type label', 'anno'),
		'not_found' => _x('No Articles found', 'post type label', 'anno'),
		'not_found_in_trash' => _x('No Articles found in Trash', 'post type label', 'anno'),
		'menu_name' => _x('Articles', 'post type label, noun', 'anno'),
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
			'menu_icon' => get_bloginfo('template_url').'/assets/main/img/admin-menu-icon.png',
	);
	register_post_type('article', $args);
}
add_action('after_setup_theme', 'anno_register_post_types');

/**
 * Request handler for post types (article)
 */ 
function anno_post_type_requst_handler() {
	if (isset($_POST['anno_convert'])) {
		if(!wp_verify_nonce($_POST['anno_convert_nonce'], 'anno_convert')) {
			wp_die(_x('Unable to perform that ability', 'wp_die error message', 'anno'));
		}
		$post_id = absint($_POST['post_ID']);
		anno_article_to_post($post_id);
		wp_redirect(get_edit_post_link($post_id, 'redirect'));
		die();
	}

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
		1 => sprintf(_x('Article updated. <a href="%s">View article</a>', 'Article updated message', 'anno'), esc_url(get_permalink($post->ID))),
		2 => _x('Custom field updated.', 'Article updated message', 'anno'),
		3 => _x('Custom field deleted.', 'Article updated message', 'anno'),
		4 => _x('Article updated.', 'Article updated message', 'anno'),
	 	5 => isset($_GET['revision']) ? sprintf( _x('Article restored to revision from %s', 'Article updated message', 'anno'), wp_post_revision_title((int) $_GET['revision'], false )) : false,
		6 => sprintf(_x('Article published. <a href="%s">View article</a>', 'Article updated message', 'anno'), esc_url(get_permalink($post->ID))),
		7 => _x('Article saved.', 'Article updated message', 'anno'),
		8 => sprintf( _x('Article submitted. <a target="_blank" href="%s">Preview article</a>', 'Article updated message', 'anno'), esc_url(add_query_arg('preview', 'true', get_permalink($post->ID)))),
		9 => sprintf( _x('Article scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview article</a>', 'Article updated message', 'anno'), date_i18n( _x( 'M j, Y @ G:i', 'Article updated future time format', 'anno' ), strtotime( $post->post_date )), esc_url( get_permalink($post->ID))),
		10 => sprintf( _x('Article draft updated. <a target="_blank" href="%s">Preview article</a>', 'Article updated message', 'anno'), esc_url( add_query_arg('preview', 'true', get_permalink($post->ID)))),
		11 => sprintf( _x('Article successfully cloned.', 'Article updated message', 'anno')),
		12 => sprintf( _x('Unable to clone article.', 'Article updated message', 'anno')),
	);

	return $messages;
}
add_filter('post_updated_messages', 'anno_post_updated_messages');

/**
 * Add DTD Meta Boxes
 */ 
function anno_article_meta_boxes() {
	add_meta_box('subtitle', _x('Subtitle', 'Meta box title', 'anno'), 'anno_subtitle_meta_box', 'article', 'normal', 'high');
	add_meta_box('body', _x('Body', 'Meta box title', 'anno'), 'anno_body_meta_box', 'article', 'normal', 'high');
	add_meta_box('references', _x('References', 'Meta box title', 'anno'), 'anno_references_meta_box', 'article', 'normal', 'high');
	add_meta_box('abstract', _x('Abstract', 'Meta box title', 'anno'), 'anno_abstract_meta_box', 'article', 'normal', 'high');
	add_meta_box('funding', _x('Funding Statement', 'Meta box title', 'anno'), 'anno_funding_meta_box', 'article', 'normal', 'high');
	add_meta_box('acknowledgements', _x('Acknowledgements', 'Meta box title', 'anno'), 'anno_acknowledgements_meta_box', 'article', 'normal', 'high');
	add_meta_box('appendicies', _x('Appendicies', 'Meta box title', 'anno'), 'anno_appendicies_meta_box', 'article', 'normal', 'high');
	add_meta_box('featured', _x('Featured', 'Meta box title', 'anno'), 'anno_featured_meta_box', 'article', 'side', 'default');
	
	if (current_user_can('administrator')) {
		add_meta_box('convert', _x('Convert To Post', 'Meta box title', 'anno'), 'anno_convert_meta_box', 'article', 'side', 'low');
	}
}
add_action('add_meta_boxes_article', 'anno_article_meta_boxes');

function anno_subtitle_meta_box($post) {
	$html = get_post_meta($post->ID, '_anno_subtitle', true);
?>
	<input type="text" name="anno_subtitle" value="<?php echo esc_attr($html); ?>" style="width:100%;" />
<?php
}

/**
 * Body meta box markup (stored in content)
 */
function anno_body_meta_box($post) {
?>
	<textarea id="anno-body" name="content" class="anno-meta"><?php echo esc_textarea($post->post_content); ?></textarea>
<?php
}

/**
 * References meta box markup
 */
function anno_references_meta_box($post) {
	$references = get_post_meta($post->ID, '_anno_references', true);
	if (!empty($references) && is_array($references)) {
		foreach ($references as $ref_key => $reference) {
?>
	<div><?php echo esc_html($ref_key . '. '. $reference['text']); ?></div>
<?php
		}
	}
}

/**
 * Abstract meta box markup (stored in excerpt)
 */
function anno_abstract_meta_box($post) {
?>
	<textarea class="anno-meta" name="excerpt"><?php echo esc_html($post->post_excerpt); ?></textarea>
<?php
}

/**
 * Funding meta box markup
 */
function anno_funding_meta_box($post) {
	$html = get_post_meta($post->ID, '_anno_funding', true);
?>
	<textarea class="anno-meta" name="anno_funding"><?php echo esc_textarea($html); ?></textarea>
<?php
}

/**
 * Acknowledgements meta box markup
 */
function anno_acknowledgements_meta_box($post) {
	$html = get_post_meta($post->ID, '_anno_acknowledgements', true);
?>
	<textarea id="guy" class="anno-meta" name="anno_acknowledgements"><?php echo esc_textarea($html); ?></textarea>
<?php
}

/**
 * Meta box markup for featuring an article in the featured carousel
 */ 
function anno_featured_meta_box($post) {
	$checked = get_post_meta($post->ID, '_featured', true);
?>
	<input id="anno-featured" type="checkbox" value="yes" name="featured"<?php checked($checked, 'yes', true); ?> />
	<label for="anno-featured"><?php _ex('Feature this article to appear in the home page carousel', 'Featured post meta box label', 'anno'); ?></label>
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
			if (isset($_POST[$key])) {
				update_post_meta($post_id, '_'.$key, $_POST[$key]);
			}
			else {
				update_post_meta($post_id, '_'.$key, '');
			}
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
remove_filter ('the_content',  'wpautop');
/**
 * Print styles for article post type.
 */ 
function anno_article_admin_print_styles() {
	// TODO, only enqueue on article post type
	wp_enqueue_style('article-admin', trailingslashit(get_bloginfo('template_directory')).'/css/article-admin.css');
}
add_action('admin_print_styles-post.php', 'anno_article_admin_print_styles');
add_action('admin_print_styles-post-new.php', 'anno_article_admin_print_styles');

/**
 * Print styles for article post type.
 */ 
function anno_article_admin_print_scripts() {
//	wp_enqueue_script('article-admin', trailingslashit(get_bloginfo('template_directory')).'/js/article-admin.js', array('tinymce'), '', true);
}
add_action('init', 'anno_article_admin_print_scripts', 99);

/**
 * Converts a post with the article post-type to the post post-type
 * 
 * @param int $post_id The ID of the post to convert
 * @return void
 */ 
function anno_article_to_post($post_id) {
	$post = wp_get_single_post(absint($post_id), ARRAY_A);
	if ($post['post_type'] != 'article') {
		return;
	}

	// Convert the taxonomies before inserting so we don't get default categories assigned.
	$taxonomy_conversion = array(
		'article_tag' => 'post_tag',
		'article_category' => 'category',
	);
	foreach ($taxonomy_conversion as $from_tax => $to_tax) {
		anno_convert_taxonomies($post['ID'], $from_tax, $to_tax);
	}


	$post['post_type'] = 'post';
	$post['post_category'] = wp_get_post_categories($post['ID']);
	$post['tags_input'] = wp_get_post_tags($post['ID'], array('fields' => 'names'));	

	$post_id = wp_insert_post($post);
}

/**
 * Converts a post's terms from one taxonomy to another.
 * 
 * @param int $post_id The id of the post to convert the terms for
 * @param String $from_tax The original taxonomy of the term
 * @param String $to_tax The taxonomy to convert the term to
 */ 
function anno_convert_taxonomies($post_id, $from_tax, $to_tax) {
	$post_terms = wp_get_object_terms($post_id, $from_tax);
	if (is_array($post_terms)) {
		$new_terms = array();
		foreach ($post_terms as $term) {
			$term_id = anno_convert_term($term, $from_tax, $to_tax);
			$new_terms[] = (int) $term_id;
		}
		wp_set_object_terms($post_id, $new_terms, $to_tax, true);
	}
 	wp_set_object_terms($post_id, array(), $from_tax, false);
}

/**
 * Converts a term and all its ancestors from one taxonomy to another
 * 
 * @param Term Object $term The original term to convert
 * @param String $from_tax The original taxonomy of the term
 * @param String $to_tax The taxonomy to convert the term to
 * @return int The ID of the newly converted term. 
 */ 
function anno_convert_term($term, $from_tax, $to_tax) {
	if (!empty($term->parent)) {
		$parent_term = get_term($term->parent, $from_tax);
		$new_parent_id = anno_convert_term($parent_term, $from_tax, $to_tax);
		if (!term_exists($term->name, $to_tax)) {
			$term_data = wp_insert_term($term->name, $to_tax, array('parent' => $new_parent_id));
			$term_id = $term_data['term_id'];
		}
		else {
			$term = get_term_by('slug', $term->slug, $to_tax);
			$term_id = $term->term_id;
		}
	}
	else {
		if (!term_exists($term->name, $to_tax)) {
			$term_data = wp_insert_term($term->name, $to_tax);
			$term_id = $term_data['term_id'];
		}
		else {
			$term = get_term_by('slug', $term->slug, $to_tax);
			$term_id = $term->term_id;
		}
	}
	return $term_id;
} 

/**
 * Markup for the convert mechanism meta box
 */ 
function anno_convert_meta_box($post) {
?>
	<p>
	<?php _ex('Clicking the button below will convert the current <strong>Article</strong> to a <strong>Post</strong>. This will also convert any terms in article taxonomies to post taxonomies. You will not be able to revert this Article back once it has been converted to a Post.', 'conversion instructions', 'anno'); ?>
	</p>
	<p style="text-align: center;">
		<?php wp_nonce_field('anno_convert', 'anno_convert_nonce', true, true); ?>
		<input type="submit" name="anno_convert" class="button-primary" value="Convert To Post" />
	</p>
	
<?php
}

?>
