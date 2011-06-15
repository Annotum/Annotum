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
function anno_dtd_meta_boxes() {
	add_meta_box('subtitle', _x('Subtitle', 'Meta box title', 'anno'), 'anno_subtitle_meta_box', 'article', 'normal', 'high');
	add_meta_box('body', _x('Body', 'Meta box title', 'anno'), 'anno_body_meta_box', 'article', 'normal', 'high');
	add_meta_box('references', _x('References', 'Meta box title', 'anno'), 'anno_references_meta_box', 'article', 'normal', 'high');
	add_meta_box('abstract', _x('Abstract', 'Meta box title', 'anno'), 'anno_abstract_meta_box', 'article', 'normal', 'high');
	add_meta_box('funding', _x('Funding Statement', 'Meta box title', 'anno'), 'anno_funding_meta_box', 'article', 'normal', 'high');
	add_meta_box('acknowledgements', _x('Acknowledgements', 'Meta box title', 'anno'), 'anno_acknowledgements_meta_box', 'article', 'normal', 'high');
	add_meta_box('appendicies', _x('Appendicies', 'Meta box title', 'anno'), 'anno_appendicies_meta_box', 'article', 'normal', 'high');
	add_meta_box('featured', _x('Featured', 'Meta box title', 'anno'), 'anno_featured_meta_box', 'article', 'side', 'default');
}
add_action('add_meta_boxes_article', 'anno_dtd_meta_boxes');

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
/*	TODO html/visual editor switch markup.
	<a id="edButtonHTML" class="active hide-if-no-js" onclick="switchEditors.go('anno-body', 'html');"><?php _e('HTML'); ?></a>
	<a id="edButtonPreview" class="hide-if-no-js" onclick="switchEditors.go('anno-body', 'tinymce');"><?php _e('Visual'); ?></a>
*/	
?>
	<textarea id="anno-body" name="content" class="anno-meta"><?php echo esc_html($post->post_content); ?></textarea>
<?php
}

/**
 * References meta box markup
 */
function anno_references_meta_box($post) {
	echo 'references placeholder';
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
	<textarea class="anno-meta" name="anno_funding"><?php echo esc_html($html); ?></textarea>
<?php
}

/**
 * Acknowledgements meta box markup
 */
function anno_acknowledgements_meta_box($post) {
	$html = get_post_meta($post->ID, '_anno_acknowledgements', true);
?>
	<textarea id="guy" class="anno-meta" name="anno_acknowledgements"><?php echo esc_html($html); ?></textarea>
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

/**
 * Print styles for article post type.
 */ 
function anno_article_admin_print_styles() {
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
 * Load TinyMCE for the body and appendices.
 */
function anno_admin_print_footer_scripts() {
	global $post;
	$appendicies = get_post_meta($post->ID, '_anno_appendicies', true);
	if (empty($appendicies) || !is_array($appendicies)) {
		$appendicies = array(0 => '0');
	}
	wp_tiny_mce(false);

?>
<script type="text/javascript">
	tinyMCE.execCommand('mceAddControl', false, 'anno-body');
<?php
	foreach ($appendicies as $key => $value) {
?>
	tinyMCE.execCommand('mceAddControl', false, 'appendix-<?php echo $key; ?>');
<?php
	}
?>
</script>
<?php
}
add_action('admin_print_footer_scripts', 'anno_admin_print_footer_scripts', 99);

?>