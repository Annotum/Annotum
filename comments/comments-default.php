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

<?php 

	if (!post_password_required()) {
		echo '<ol class="reply-list">', wp_list_comments('callback=cfct_threaded_comment'), '</ol>';
			
			previous_comments_link();
			next_comments_link();
		}
		cfct_form('comment');
}

?>