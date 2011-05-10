<?php

/**
 * Register meta boxes
 */
function anno_add_meta_boxes() {
	if (anno_user_can('view_general_comment')) {
		add_meta_box('general-comment', __('Internal Comments: General', 'anno'), 'anno_admin_general_comments', 'article', 'normal');
	}
}
add_action('admin_head-post.php', 'anno_add_meta_boxes');

/**
 * Get a list of comments for a given type
 * 
 * @param string $type Type of comments to fetch
 * @return array Array of comment objects
 */
function anno_internal_comments_get_comments($type, $post_id) {
	remove_filter('comments_clauses', 'anno_internal_comments_filter');
	$comments = get_comments( array(
	    'type' => $type,
		'post_id' => $post_id,
	));
	add_filter('comments_clauses', 'anno_internal_comments_filter');

	return $comments;
}

/**
 * Display the content for internal comment meta boxes
 * @param $type The comment type base
 * @return void
 */ 
function anno_internal_comments_display($type) {
	global $post;
	$comments = anno_internal_comments_get_comments('article_'.$type, $post->ID);
?>
<table class="widefat fixed comments comments-box anno-comments" data-comment-type="<?php echo esc_attr($type); ?>" cellspacing="0">
	<tbody id="<?php echo esc_attr('the-comment-list-'.$type); ?>" class="list:comment">
<?php
	if (is_array($comments) && !empty($comments)) {
		foreach ($comments as $comment) {
			anno_internal_comment_table_row($comment);
		}
	}
	anno_internal_comments_form($type);
?>
	</tbody>
</table>
<?php
}

/**
 * Displays comment markup for internal comments
 * 
 * @param int $comment_id ID of the comment to be displayed
 * @param string $type Type of comment to display (general, reviewer)
 * @return void
 */ 
function anno_internal_comment_table_row($cur_comment) {
	global $comment;
	$comment_holder = $comment;
	$comment = $cur_comment;
	$comment_list_table = _get_list_table('WP_Comments_List_Table');
?>
	<tr id="comment-<?php echo $comment->comment_ID; ?>" class="approved">
		<td class="author column-author">
			<?php

				$author_url = $comment->comment_author_url;
				if ( 'http://' == $author_url ) {
					$author_url = '';
				}
				$author_url_display = preg_replace( '|http://(www\.)?|i', '', $author_url );
				if ( strlen( $author_url_display ) > 50 ) {
					$author_url_display = substr( $author_url_display, 0, 49 ) . '...';
				}

				echo '<strong>'.get_avatar($comment->comment_author_email, '32').$comment->comment_author.'</strong><br />';
				if ( !empty( $author_url ) ) {
					echo "<a title='$author_url' href='$author_url'>$author_url_display</a><br />";
				}

				if ( !empty( $comment->comment_author_email ) ) {
					echo '<a href="mailto:'.esc_url($comment->comment_author_email).'">'.esc_html($comment->comment_author_email).'</a>';
					echo '<br />';
				}
				echo '<a href="edit-comments.php?s='.$comment->comment_author_IP.'&amp;mode=detail">'.esc_html($comment->comment_author_IP).'</a>';
			?>
		</td>
		<td class="comment column-comment">
			<?php
			echo '
			<div class="submitted-on">';
				printf( __('Submitted on <a href="%1$s">%2$s at %3$s</a>', 'anno'), esc_url(get_comment_link($comment)), get_comment_date(__( 'Y/m/d', 'anno')), get_comment_date(get_option('time_format')));
				if ( $comment->comment_parent ) {
					$parent = get_comment($comment->comment_parent);
					$parent_link = esc_url(get_comment_link($comment->comment_parent));
					$name = get_comment_author( $parent->comment_ID );
					printf( ' | '.__( 'In reply to <a href="%1$s">%2$s</a>.', 'anno'), $parent_link, $name );
				}
			echo '
			</div>
			<p class="comment-content">'.
				$comment->comment_content
			.'</p>';
			
			if (anno_user_can('manage_general_comment')) {
				$actions['reply'] = '<a href="#" class="reply">'.__( 'Reply', 'anno').'</a>';
				$actions['edit'] = '<a href="comment.php?action=editcomment&amp;c='.$comment->comment_ID.'" title="'.esc_attr__( 'Edit comment', 'anno').'">'.__('Edit', 'anno').'</a>';
				$actions['delete'] = '<a class="anno-trash-comment" href="'.wp_nonce_url('comment.php?action=trashcomment&amp;c='.$comment->comment_ID, 'delete-comment_'.$comment->comment_ID).'">'.__('Trash', 'anno').'</a>';
				echo '
				<div class="row-actions" data-comment-id="'.$comment->comment_ID.'">';
				$i = 1;
				foreach ($actions as $action) {
					if ($i == count($actions)) {
						$sep = '';
					}
					else {
						$sep = ' | ';
					}
					echo $action.$sep;
					$i++;
				}
				echo '
				</div>';
			}
			?>
		</td>
	</tr>
<?php
	$comment = $comment_holder;
}

/**
 * Output of comments meta box
 */
function anno_admin_general_comments() {
	anno_internal_comments_display('general');
}

/**
 * Output the form for internal comments
 */
function anno_internal_comments_form($type) {
?>
	<tr id="<?php echo esc_attr('comment-add-pos-'.$type); ?>">
	</tr>
	<tr id="<?php echo esc_attr('anno-internal-comment-'.$type.'-form'); ?>">
		<td colspan="2">
			<input type="hidden" name="anno_comment_type" value="<?php echo esc_attr($type); ?>" />
			<input type="hidden" class="parent-id" name="parent_id" value="0" />
			<p>
				<label for="<?php echo esc_attr('anno_comment_'.$type.'_textarea'); ?>"><?php _e('Comment', 'anno'); ?></label>
				<textarea id="<?php echo esc_attr('anno_comment_'.$type.'_textarea'); ?>"></textarea>
			</p>
			<p>
				<input class="anno-submit button" type="button" value="<?php _e('Submit', 'anno'); ?>" />
				<input class="anno-cancel button" type="button" value="<?php _e('Cancel', 'anno'); ?>" style="display: none;" onClick="location.href='#<?php echo esc_attr('comment-add-pos-'.$type); ?>'"/>
			</p>
			<?php wp_nonce_field('anno_comment', '_ajax_nonce-anno-comment-'.esc_attr($type), false ); ?>
		</td>
	</tr>
<?php
}

/**
 * Enqueue js for internal comments
 */
function anno_internal_comments_js() {
global $post;
	echo '
<script type="text/javascript">
	var POST_ID = '.$post->ID.';
</script>
	';
	wp_enqueue_script('anno-internal-comments', trailingslashit(get_bloginfo('stylesheet_directory')).'plugins/internal-comments/js/internal-comments.js', array('jquery'));
}
add_action('admin_print_scripts', 'anno_internal_comments_js');

/**
 * Enqueue css for internal comments
 */
function anno_internal_comments_css() {
	wp_enqueue_style('anno-internal-comments', trailingslashit(get_bloginfo('stylesheet_directory')).'plugins/internal-comments/css/internal-comments.css');
}
add_action('admin_print_styles', 'anno_internal_comments_css');

/**
 * Filter the comment link if its an internal comment. Link will take you to the post edit page
 */ 
function anno_internal_comments_get_comment_link($link, $comment) {
	if (in_array($comment->comment_type, array('article_review', 'article_general'))) {
		$link = get_edit_post_link($comment->comment_post_ID).'#comment-'.$comment->comment_ID;
	}
	
	return $link;
}
add_filter('get_comment_link', 'anno_internal_comments_get_comment_link', 10, 2);

/**
 * Filter to prevent front end display of our comments
 */ 
function anno_internal_comments_filter($clauses) {
	$clauses['where'] .= " AND comment_type NOT IN ('article_general', 'article_review')";
	return $clauses;
}
if (!is_admin()) {
	add_filter('comments_clauses', 'anno_internal_comments_filter');
}

/**
 * Modify the comment count stored in the wp_post comment_count column, so internal comments don't show up there.
 * Based on code in the WP Core function wp_update_comment_count
 */
function anno_internal_comments_update_comment_count($post_id) {
	global $wpdb;
	$post_id = (int) $post_id;
	if ( !$post_id )
		return false;
	if ( !$post = get_post($post_id) )
		return false;
		
	$old = (int) $post->comment_count;
	$internal_count = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved = '1' AND comment_type IN ('article_general', 'article_review')", $post_id) );
	$wpdb->update( $wpdb->posts, array('comment_count' => max($old - $internal_count, 0)), array('ID' => $post_id) );

}
add_action('wp_update_comment_count', 'anno_internal_comments_update_comment_count');

/**
 * Processes an AJAX request when submitting an internal comment
 * Based on code in the WP Core
 */ 
function anno_internal_comments_ajax() {
	check_ajax_referer('anno_comment', '_ajax_nonce-anno-comment-'.$_POST['type']);
	//Check to make sure user can post comments on this post
	global $wpdb;
	$user = wp_get_current_user();
	if ($user->ID) {
		$comment_author = $wpdb->escape($user->display_name);
		$comment_author_email = $wpdb->escape($user->user_email);
		$comment_author_url = $wpdb->escape($user->user_url);
		$comment_content = trim($_POST['content']);
		$comment_type = 'article_'.trim($_POST['type']);
		$comment_post_ID = intval($_POST['post_id']);
		$user_ID = $user->ID;
	}
	else {
		die( __('Sorry, you must be logged in to reply to a comment.') );
	}
	
	if ( '' == $comment_content ) {
		die( __('Error: please type a comment.') );
	}
	
	$comment_parent = absint($_POST['parent_id']);
	
	$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

	// Create the comment and automatically approve it
	add_filter('pre_comment_approved', 'anno_internal_comments_approve');
	$comment_id = wp_new_comment($commentdata);
	remove_filter('pre_comment_approved', 'anno_internal_comments_approve');
	
	$comment = get_comment($comment_id);
	if (!$comment) {
		 die('1');
	}
	
	//Display markup for AJAX
	anno_internal_comment_table_row($comment);

}
add_action('wp_ajax_anno-internal-comment', 'anno_internal_comments_ajax');

/**
 * Filter to automatically approve internal comments
 */ 
function anno_internal_comments_approve($approved) {
		return 1;
}

/**
 * Dropdown filter to display only our internal comment types in the admin screen
 */ 
function anno_admin_comment_types_dropdown($comment_types) {
	$comment_types['article_general'] = __('Article General', 'anno');
	$comment_types['article_review'] = __('Article Review', 'anno');
	return $comment_types;
}
add_filter('admin_comment_types_dropdown', 'anno_admin_comment_types_dropdown');

?>
