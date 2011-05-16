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

global $post, $wp_query, $comments, $comment;

if (comments_open()) {
	if (!post_password_required() && have_comments() ) {
		?>
		<h2 id="comments"><?php comments_number('', __('One Response', 'anno'), __('% Responses', 'anno')); ?></h2>
		<?php
		echo '<ol class="reply-list">', wp_list_comments('callback=cfct_threaded_comment'), '</ol>';
			
		previous_comments_link();
		next_comments_link();
	}
	cfct_form('comment');
}

?>