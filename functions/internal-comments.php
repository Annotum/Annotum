<?php

/**
 * Register meta boxes
 */
function anno_add_meta_boxes() {
	add_meta_box('general-comment', 'Comments', 'anno_admin_general_comments', 'article', 'normal');
}
add_action('admin_head', 'anno_add_meta_boxes');


/************* GENERAL COMMENTS *************/

/**
 * Get a list of general comments
 */
function anno_get_general_comments() {
	return get_comments( array(
	    'type' => 'article_general'
	));
}

/**
 * Output of comments meta box
 */
function anno_admin_general_comments() {
	$comments = anno_get_general_comments();
	if (is_array($comments)) {
		foreach ($comments as $comment) {
			echo $comment->comment_content;
		}
	}
?>
	<label for="comment">Comment</label>
	<textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea>
	<input type="button" class="button" value="Add Comment">
<?php 
}

?>