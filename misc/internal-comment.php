<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2010 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }
if (CFCT_DEBUG) { cfct_banner(__FILE__); }

global $comment;

// Use properties of object instead of functions to avoid filters since this is the backend display.
if (!empty($comment)) {
?>
<li class="li-comment" id="li-comment-<?php echo $comment->comment_ID; ?>">
	<div class="div-comment" id="div-comment-<?php echo $comment->comment_ID; ?>">
		<div id="comment-<?php echo $comment->comment_ID; ?>">
			<?php echo get_avatar($comment->user_id, '36'); ?>
			<?php echo $comment->comment_author; ?>
			<p><?php esc_html($comment->comment_content); ?></p>
			<div id="comment-<?php echo $comment->comment_ID ?>-date"><?php echo date('F j, Y g:i a' , $comment->comment_date); ?></div>
			<a class="comment-reply-link" href="/2011/04/29/hello-world/?replytocom=1#respond" onclick="return addComment.moveForm(&quot;comment-1&quot;, &quot;1&quot;, &quot;respond&quot;, &quot;1&quot;)">Reply</a>
			<a class="comment-edit-link" href="http://annotum.local/wp-admin/comment.php?action=editcomment&amp;c=1" title="Edit comment">Edit This</a>
		</div>	
	</div>
</li>
<?php
}
?>