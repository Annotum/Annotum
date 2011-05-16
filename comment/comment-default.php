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

global $comment, $post;
// Extract data passed in from threaded.php for comment reply link
extract($data);
?>
<article <?php comment_class('article'); ?> id="comment-<?php comment_ID(); ?>">
	<header class="header">
		<?php
		echo get_avatar($comment, 54);

		?>
		<p>
			<?php comment_author_link(); ?> on <?php comment_date(); ?>
		</p>
	</header>
	<div class="content">
		<?php
		if ($comment->comment_approved == '0') {
			echo '<p>' . __('Your comment is awaiting moderation.', 'anno') . '</p>';
		}
		comment_text();
		?>
	</div>
	<footer class="footer">
		<?php
		comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth'])), $comment, $post);
		echo ' &bull; ';
		edit_comment_link(__('Edit This', 'anno'), '', '');
		?>
	</footer>
</article>