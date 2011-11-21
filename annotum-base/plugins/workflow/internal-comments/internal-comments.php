<?php

global $anno_review_options;
$anno_review_options = array(
	0 => '',
	1 => _x('Approve', 'Review left by Reviewer', 'anno'),
	2 => _x('Revisions', 'Review left by Reviewer', 'anno'),
	3 => _x('Reject', 'Review left by Reviewer', 'anno'),
);

/**
 * Register meta boxes
 */
function anno_internal_comments_add_meta_boxes() {
	if (anno_user_can('view_general_comments')) {
		add_meta_box('general-comment', _x('Internal Comments', 'Meta box title', 'anno'), 'anno_internal_comments_general_comments', 'article', 'advanced');
	}
	if (anno_user_can('view_review_comments')) {
		add_meta_box('reviewer-comment', _x('Reviews', 'Meta box title', 'anno'), 'anno_internal_comments_reviewer_comments', 'article', 'advanced');
	}
}
// We don't want to add them for new-posts
add_action('admin_head-post.php', 'anno_internal_comments_add_meta_boxes');

/**
 * Get a list of comments for a given type
 * 
 * @param string $type Type of comments to fetch
 * @return array Array of comment objects
 */
function anno_internal_comments_get_comments($type, $post_id) {
	remove_filter('comments_clauses', 'anno_internal_comments_clauses');
	$comments = get_comments( array(
	    'type' => $type,
		'post_id' => $post_id,
	));
	add_filter('comments_clauses', 'anno_internal_comments_clauses');

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
<table class="widefat comments comments-box anno-comments" data-comment-type="<?php echo esc_attr($type); ?>" cellspacing="0">
	<tbody id="<?php echo esc_attr('the-comment-list-'.$type); ?>" class="list:comment">
<?php
	if (is_array($comments) && !empty($comments)) {
		foreach ($comments as $comment) {
			if (anno_user_can('view_'.$type.'_comment', null, $post->ID, $comment->comment_ID)) {
				anno_internal_comment_table_row($comment);
			}
		}
	}
	if (anno_user_can('add_'.$type.'_comment')) {
		anno_internal_comments_form($type);
	}
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
	global $comment, $current_screen;
	$comment_holder = $comment;
	$comment = $cur_comment;
	// Filter in WP_Comments_List_Table expectes an object in current screen, throws a fit otherwise
	if (empty($current_screen)) {
		set_current_screen('article');
	}
	
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
				printf( _x('Submitted on <a href="%1$s">%2$s at %3$s</a>', 'Internal comment date', 'anno'), esc_url(get_comment_link($comment)), get_comment_date(_x( 'Y/m/d', 'Date format for internal comments', 'anno')), get_comment_date(get_option('time_format')));
				if ( $comment->comment_parent ) {
					$parent = get_comment($comment->comment_parent);
					$parent_link = esc_url(get_comment_link($comment->comment_parent));
					$name = get_comment_author( $parent->comment_ID );
					printf( ' | '._x( 'In reply to <a href="%1$s">%2$s</a>.', 'Internal Comment reply text' , 'anno'), $parent_link, $name );
				}
			echo '
			</div>
			<p class="comment-content">'.
				$comment->comment_content
			.'</p>';
			
			$actions = array();
			if (anno_user_can('add_'.str_replace('article_', '', $comment->comment_type).'_comment')) {
				$actions['reply'] = '<a href="#" class="reply">'._x('Reply', 'Internal comment action link text', 'anno').'</a>';
			}
			
			if (anno_user_can('edit_comment', null, null, $comment->comment_ID )) {
				$actions['edit'] = '<a href="comment.php?action=editcomment&amp;c='.$comment->comment_ID.'" title="'.esc_attr_x( 'Edit comment', 'Internal comment action link title', 'anno').'">'._x('Edit', 'Internal comment action link text',  'anno').'</a>';
				$actions['delete'] = '<a class="anno-trash-comment" href="'.wp_nonce_url('comment.php?action=trashcomment&amp;c='.$comment->comment_ID, 'delete-comment_'.$comment->comment_ID).'">'._x('Trash', 'Internal comment action link text', 'anno').'</a>';
			}
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
			?>
		</td>
	</tr>
<?php
	$comment = $comment_holder;
}

/**
 * Output of comments meta box
 */
function anno_internal_comments_general_comments() {
	anno_internal_comments_display('general');
}


/**
 * Meta box markup for reviewer review and comments
 */
function anno_internal_comments_reviewer_comments() {
	global $anno_review_options, $current_user, $post;
	$user_review = annowf_get_user_review($post->ID, $current_user->ID);
	$reviewers = anno_get_reviewers($post->ID);
	if (anno_user_can('leave_review', $current_user->ID, $post->ID)) {
?>
<div class="review-section <?php echo 'status-'.$user_review; ?>">
	<label for="anno-review"><?php _ex('Recommendation: ', 'Review label for reviewer meta box review dropdown', 'anno'); ?></label>
	<select id="anno-review" name="anno_review">
	<?php 
		foreach ($anno_review_options as $opt_key => $opt_val) {
	?>	
		<option value="<?php echo esc_attr($opt_key); ?>"<?php selected($user_review, $opt_key, true); ?>><?php echo esc_html($opt_val); ?></option>
	<?php 
		} 
	?>
	</select>
	<span class="review-notice"></span>
</div>

<?php 
		wp_nonce_field('anno_review', '_ajax_nonce-review', false);
	}
 	anno_internal_comments_display('review');
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
				<label for="<?php echo esc_attr('anno_comment_'.$type.'_textarea'); ?>"><?php _ex('Comment', 'Internal comment textarea label', 'anno'); ?></label>
				<textarea id="<?php echo esc_attr('anno_comment_'.$type.'_textarea'); ?>"></textarea>
			</p>
			<p>
				<input class="anno-submit button" type="button" value="<?php _ex('Submit', 'Internal comment button value', 'anno'); ?>" />
				<input class="anno-cancel button" type="button" value="<?php _ex('Cancel', 'Internal comment button value', 'anno'); ?>" style="display: none;" onClick="location.href='#<?php echo esc_attr('comment-add-pos-'.$type); ?>'"/>
			</p>
			<?php wp_nonce_field('anno_comment', '_ajax_nonce-anno-comment-'.esc_attr($type), false ); ?>
		</td>
	</tr>
<?php
}

/**
 * Enqueue js for internal comments
 */
function anno_internal_comments_print_scripts() {
global $post;
	wp_enqueue_script('anno-internal-comments', trailingslashit(get_bloginfo('template_directory')).'plugins/workflow/internal-comments/js/internal-comments.js', array('jquery'));
}
add_action('admin_print_scripts-post.php', 'anno_internal_comments_print_scripts');
add_action('admin_print_scripts-post-new.php', 'anno_internal_comments_print_scripts');

/**
 * Enqueue css for internal comments
 */
function anno_internal_comments_print_styles() {
	wp_enqueue_style('anno-internal-comments', trailingslashit(get_bloginfo('template_directory')).'plugins/workflow/internal-comments/css/internal-comments.css');
}
add_action('admin_print_styles-post.php', 'anno_internal_comments_print_styles');
add_action('admin_print_styles-post-new.php', 'anno_internal_comments_print_styles');

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
function anno_internal_comments_clauses($clauses) {
	$clauses['where'] .= " AND comment_type NOT IN ('article_general', 'article_review')";
	return $clauses;
}
// Never display internal comments on the front-end
if (!is_admin()) {
	add_filter('comments_clauses', 'anno_internal_comments_clauses');
}

/**
 * Don't allow non editor/admin to see internal comments on comment listing page. 
 */ 
function anno_filter_edit_comments_page() {
	global $pagenow;
	if ($pagenow == 'edit-comments.php' && !(current_user_can('editor') || current_user_can('administrator'))) {
		add_filter('comments_clauses', 'anno_internal_comments_clauses');
	}
}
add_action('admin_init', 'anno_filter_edit_comments_page');

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
 * 
 * @todo handle comment errors instead of just dying.
 */ 
function anno_internal_comments_ajax() {
	check_ajax_referer('anno_comment', '_ajax_nonce-anno-comment-'.$_POST['type']);
	//Check to make sure user can post comments on this post
	global $wpdb;
	$user = wp_get_current_user();

	if ($user->ID) {
		$comment_base_type = $wpdb->escape(trim($_POST['type']));
		
		$comment_author = $wpdb->escape($user->display_name);
		$comment_author_email = $wpdb->escape($user->user_email);
		$comment_author_url = $wpdb->escape($user->user_url);
		$comment_content = trim($_POST['content']);
		$comment_type = 'article_'.$comment_base_type;
		$comment_post_ID = $wpdb->escape(intval($_POST['post_id']));
		$user_ID = $user->ID;
	}
	else {
		die();
	}
	
	if ( '' == $comment_content ) {
		die();
	}
	
	$comment_parent = absint($_POST['parent_id']);
	
	$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

	// Create the comment and automatically approve it
	add_filter('pre_comment_approved', 'anno_internal_comments_pre_comment_approved');
	add_filter('pre_option_comments_notify', 'anno_internal_comments_surpress_notification');
	$comment_id = wp_new_comment($commentdata);
	remove_filter('pre_option_comments_notify', 'anno_internal_comments_surpress_notification');
	remove_filter('pre_comment_approved', 'anno_internal_comments_pre_comment_approved');
	
	$comment = get_comment($comment_id);
	if (!$comment) {
		 die();
	}

	// Save in the audit log
	if ($comment->comment_type == 'article_general') {
		annowf_save_audit_item($comment->comment_post_ID, $user->ID, 5, array($comment->comment_ID));
	}
	else if ($comment->comment_type = 'article_review') {
		annowf_save_audit_item($comment->comment_post_ID, $user->ID, 3, array($comment->comment_ID));
	}


	// Send email notifications of new commment
	if (anno_workflow_enabled('notifications')) {
		$post = get_post($comment_post_ID);
		$recipients = array();
		if ($comment_base_type == 'review') {
			$recipients[] = $user->user_email;
		}
		annowf_send_notification($comment_base_type.'_comment', $post, $comment, $recipients);
	}
	
	// Attach a 'round' to a comment, marking which revision number this is
	$round = annowf_get_round($comment_post_ID);
	update_comment_meta($comment->comment_ID, '_round', $round);  

	//Display markup for AJAX
	anno_internal_comment_table_row($comment);

}
add_action('wp_ajax_anno-internal-comment', 'anno_internal_comments_ajax');

/**
 * Processes an AJAX request when submitting a review from the dropdown.
 */
function anno_internal_comments_review_ajax() {
	check_ajax_referer('anno_review', '_ajax_nonce-review');
	if (isset($_POST['post_id']) && isset($_POST['review'])) {
		global $current_user;
		$post_id = absint($_POST['post_id']);
		$review = $_POST['review'];
		
		$post_round = annowf_get_round($post_id);

		update_user_meta($current_user->ID, '_'.$post_id.'_review_'.$post_round, $review);
		
		$reviewed = get_post_meta($post_id, '_round_'.$post_round.'_reviewed', true);
		if (!is_array($reviewed)) {
			$reviewed = array();
		}
				
		// If review is set to none, remove the user from reviewed, otherwise update it with the current user.
		if ($review != 0) {
			// Keep track that this user has left a review on the post
			if (!in_array($current_user->ID, $reviewed)) {
				$reviewed[] = $current_user->ID;
				update_post_meta($post_id, '_round_'.$post_round.'_reviewed', array_unique($reviewed));
			}
			// Send notification
			$post = get_post(intval($post_id));
			annowf_send_notification('review_recommendation', $post, null, null, $current_user->ID);
			annowf_save_audit_item($post_id, $current_user->ID, 4, array($review));
		}
		else {
			$key = array_search($current_user->ID, $reviewed);
			if ($key !== false) {
				unset($reviewed[$key]);
				update_post_meta($post_id, '_round_'.$post_round.'_reviewed', array_unique($reviewed));
			}
		}
		echo $review;
	}
	die();
}
add_action('wp_ajax_anno-review', 'anno_internal_comments_review_ajax');

/**
 * Filter to automatically approve internal comments
 */ 
function anno_internal_comments_pre_comment_approved($approved) {
	return 1;
}

/**
 * Dropdown filter to display only our internal comment types in the admin screen
 */ 
function anno_internal_comment_types_dropdown($comment_types) {
	if (current_user_can('editor') || current_user_can('administrator')) {
		$comment_types['article_general'] = _x('Article General', 'Dropdown comment type selector', 'anno');
		$comment_types['article_review'] = _x('Article Review', 'Dropdown comment type selector', 'anno');
	}
	return $comment_types;
}
add_filter('admin_comment_types_dropdown', 'anno_internal_comment_types_dropdown');

/**
 * Get the top level comment of a series of comment replies.
 * 
 * @param int|object ID of the comment or the comment object itself
 * @return bool|obj Comment object, or false if the comment could not be found.
 */ 
function anno_internal_comments_get_comment_root($comment) {
	if (is_numeric($comment)) {
		$comment = get_comment($comment);
	}
	if ($commment !== false) {
		while($comment->comment_parent != 0) {
			$comment = get_comment($comment->comment_parent);
		}
	}
	return $comment;
}

/**
 * Filter for removing WP action that sends emails when comments are created. Added to internal comments.
 */ 
function anno_internal_comments_surpress_notification() {
	return 0;
}

/**
 * Enforce general comment capabilities
 */
function anno_internal_comments_capabilities($allcaps, $caps, $args) {
// $args are an array => 'capability_name' , 'user_id', 'additional args (obj id)'
	if ($args[0] == 'edit_comment') {
		$comment = get_comment($args[2]);
		if (!empty($comment) && ($comment->comment_type == 'article_general' || $comment->comment_type == 'article_review')) {
			if (anno_workflow_enabled()) {
				if (!anno_user_can('edit_comment', $args[1], '', $args[2])) {
					$allcaps = array();
				}
			}
			//No internal comments should be editable if the workflow is disabled
			else {
				$allcaps = array();
			}	
		}
	}
	return $allcaps;
}
add_action('user_has_cap', 'anno_internal_comments_capabilities', 1, 3);
?>