<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2011 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }
if (CFCT_DEBUG) { cfct_banner(__FILE__); }

if (comments_open()) {
	if (!post_password_required() && have_comments() ) {
		?>
		<section id="replies">
			<div class="section-header clearfix">
				<h1 class="section-title"><?php comments_number('', __('1 Comment', 'anno'), __('% Comments', 'anno')); ?></h1>
				<?php post_comments_feed_link('Comments RSS'); ?>
			</div>

			<?php
			echo '<ol class="reply-list">', wp_list_comments('callback=cfct_threaded_comment'), '</ol>';
			
			previous_comments_link();
			next_comments_link();
			?>
		</section>
			
		<?php 
	}
	
	comment_form();
}

?>