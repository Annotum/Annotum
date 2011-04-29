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

if (!empty($comment)) {
?>
<li class="li-comment" id="li-comment-1">
	<div class="div-comment" id="div-comment-1">
		<div id="comment-1">
			<img alt="" src="http://0.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=54" class="avatar avatar-54 photo avatar-default" height="54" width="54">
			<a href="http://wordpress.org/" rel="external nofollow" class="url">Mr WordPress</a>
			<p>Hi, this is a comment.<br>To delete a comment, just log in and view the post's comments. There you will have the option to edit or delete them.</p>
			<div id="comment-1-date">April 29, 2011 8:38 pm</div>
			<a class="comment-reply-link" href="/2011/04/29/hello-world/?replytocom=1#respond" onclick="return addComment.moveForm(&quot;comment-1&quot;, &quot;1&quot;, &quot;respond&quot;, &quot;1&quot;)">Reply</a>
			<a class="comment-edit-link" href="http://annotum.local/wp-admin/comment.php?action=editcomment&amp;c=1" title="Edit comment">Edit This</a>
		</div>	
	</div>
</li>
<?php
}
?>