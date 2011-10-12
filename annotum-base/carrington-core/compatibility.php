<?php

// This file is part of the Carrington Core Platform for WordPress
// http://carringtontheme.com
//
// Copyright (c) 2008-2011 Crowd Favorite, Ltd. All rights reserved.
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

/**
 * Outputs hidden fields for comment form with unique IDs, based on post ID, making it safe for AJAX pull.
 *
 */
function cfct_comment_id_fields() {
	global $id;
	$replytoid = isset($_GET['replytocom']) ? (int) $_GET['replytocom'] : 0;
	
	echo cfct_get_comment_id_fields($id, $replytoid);
}

function cfct_get_comment_id_fields($id, $replytoid) {
	$out = "<input type='hidden' name='comment_post_ID' value='$id' id='comment_post_ID_p$id' />\n";
	$out .= "<input type='hidden' name='comment_parent' id='comment_parent_p$id' value='$replytoid' />\n";
	
	return $out;
}

/**
 * Filter the comment reply link to add a unique unique ID, based on post ID, making it safe for AJAX pull.
 * 
 **/
function cfct_get_cancel_comment_reply_link($reply_link, $link, $text) {
	global $post;
	
	if ( !empty($text) ) { $text = __('Cancel', 'carrington'); }
	
	$style = '';
	if (!isset($_GET['replytocom'])) {
		$style = ' style="display:none;"';
	}
	
	$reply_link = '<a rel="nofollow" id="cancel-comment-reply-link-p' . $post->ID . '" href="' . $link . '-p' . $post->ID . '"' . $style . '>' . $text . '</a>';
	return $reply_link;
}

// For meeting wordpress.org requirements

/*
get_avatar();
the_tags();
register_sidebar('none');
bloginfo('description');
wp_head();
wp_footer();
*/

?>