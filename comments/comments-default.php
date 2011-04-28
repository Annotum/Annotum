<?php

// This file is part of the Carrington JAM Theme for WordPress
// http://carringtontheme.com
//
// Copyright (c) 2008-2010 Crowd Favorite, Ltd. All rights reserved.
// http://crowdfavorite.com
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }
if (CFCT_DEBUG) { cfct_banner(__FILE__); }

global $post, $wp_query, $comments, $comment;

if (have_comments() || comments_open()) {
?>

<h2 id="comments"><?php comments_number('', __('One Response', 'carrington-jam'), __('% Responses', 'carrington-jam')); ?></h2>

<p><?php printf(__('Stay in touch with the conversation, subscribe to the <a class="feed" rel="alternate" href="%s"><acronym title="Really Simple Syndication">RSS</acronym> feed for comments on this post</a>.', 'carrington-jam'), get_post_comments_feed_link($post->ID, '')); ?></p>

<?php 

	if (!post_password_required()) {
		$comments = $wp_query->comments;
		$comment_count = 0;
		$ping_count = 0;
		foreach ($comments as $comment) {
			if (get_comment_type() == 'comment') {
				$comment_count++;
			}
			else {
				$ping_count++;
			}
		}
		if ($comment_count) {
			echo '<ol>', wp_list_comments('type=comment&callback=cfct_threaded_comment'), '</ol>';
			
			previous_comments_link();
			next_comments_link();
		}
		if ($ping_count) {
?>
<h3 class="pings"><?php _e('Continuing the Discussion', 'carrington-jam'); ?></h3>
<?php
			echo '<ol>', wp_list_comments('type=pings&callback=cfct_threaded_comment'), '</ol>';
		}
	}
	cfct_form('comment');
}

?>