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

global $comment, $post;
// Extract data passed in from threaded.php for comment reply link
extract($data);
?>
<div id="comment-<?php comment_ID(); ?>">
<?php if ($comment->comment_approved == '0') {
	_e('Your comment is awaiting moderation.', 'carrington-jam');
}

echo get_avatar($comment, 54);

comment_author_link();

comment_text();

comment_date();

echo '<a href="'.esc_url(get_comment_link( $comment->comment_ID )).'">', comment_time(), '</a>';

comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth'])), $comment, $post);

edit_comment_link(__('Edit This', 'carrington-jam'), '', '');

?>
</div>