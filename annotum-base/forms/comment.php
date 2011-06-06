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

global $post, $user_identity;

$commenter = wp_get_current_commenter();

extract($commenter);

$req = get_option('require_name_email');

// if post is open to new comments
if (comments_open()) {
	// if you need to be regestered to post comments..
	if ( get_option('comment_registration') && !is_user_logged_in() ) { ?>

<p><?php
		printf(__('You must be <a href="%s">logged in</a> to post a comment.', 'anno'), wp_login_url(urlencode(get_permalink())) );
		if (get_option('users_can_register')) {
			printf(' ' . __('Not a member? %s now!', 'anno'), wp_register('','', false) );
		}
		?></p>

<?php
	}
	else { 
?>
<section id="respond">
	<form id="comments-form" action="<?php echo trailingslashit(get_bloginfo('wpurl')); ?>wp-comments-post.php" method="post">
		<h2 class="section-title">Leave A Comment</h2>
		<?php //cancel_comment_reply_link(); ?>
		<?php
				if (is_user_logged_in()) {
		?>
			<p class="logged-in"><?php
					printf(__('Logged in as <a href="%s">%s</a>. ', 'anno'), get_bloginfo('wpurl').'/wp-admin/profile.php', $user_identity);
					wp_loginout();
				?></p>
		<?php
				}
				else { 
		?>
		<div class="input-row">
			<label for="comment-name">Your Name</label><input class="text" type="text" name="name" value="" id="comment-name" />
		</div>
		<div class="input-row">
			<label for="comment-email">Email Address</label><input class="text" type="email" name="email" value="" id="comment-email" />
		</div>
		<div class="input-row">
			<label for="comment-website">Website</label><input class="text" type="text" name="website" value="" id="comment-website" />
		</div>
		<?php
		
		}
		
		?>
		<div class="input-row">
			<label for="comment">Comment</label><textarea name="comment" rows="8" cols="40" id="comment"></textarea>
		</div>
		<div class="input-row input-row-submit">
			<button name="submit" type="submit" id="submit" tabindex="5"><?php _e('Submit', 'anno'); ?></button>
			<?php
				comment_id_fields();
				do_action('comment_form', $post->ID);
			?>
		</div>
	</form>
</section><!-- #reply -->
<?php 
	} // If registration required and not logged in 
} // If you delete this the sky will fall on your head
?>