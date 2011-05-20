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
	<?php if ($comment->comment_approved == '0') {
		_e('Your comment is awaiting moderation.', 'discovery-education');
	} ?>
	<div class="header">
		<?php echo get_avatar($comment, 40); ?>
		<h3 class="title"><?php comment_author_link(); ?></h3>
		<time><?php comment_date(); ?><time>
	</div>
	<div class="content">
			<?php comment_text(); ?>
	</div><!-- .content -->
	<div class="footer">
		<?php 
			comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth'])), $comment, $post); 
			edit_comment_link(__('Edit This', 'discovery-education'), ' <span class="delimiter">&middot;</span> ', ''); 
		?>
	</div><!-- .footer -->
</article>