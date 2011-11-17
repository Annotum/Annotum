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
?>
<article <?php post_class('article-full'); ?>>
	<header class="header">
		<div class="entry-title">
			<h1 class="title"><a rel="bookmark" href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>
		</div>
		<div class="meta">
			<time class="published" pubdate datetime="<?php the_time('c'); ?>"><?php the_time('F j, Y'); ?></time>
			<?php echo anno_post_category_list(', '); ?>
		</div>
		<div class="tools-bar supplement clearfix">
			<div class="cell print">
				<a href="#" onclick="window.print(); return false;"><?php _e('Print Post', 'anno'); ?></a>
			</div>
			<div class="cell share clearfix">
				<div class="social-nav">
					<ul class="nav">
						<li><?php anno_email_link(); ?></li>
						<li><?php anno_twitter_button(); ?></li>
						<li><?php anno_facebook_button(); ?></li>
					</ul>
				</div>
			</div>
		</div><!-- .tools-bar -->
		<div class="sec sec-authors">
			<span class="title"><span><?php _e('Authors', 'anno'); ?></span></span>
			<ul class="authors nav">
				<?php anno_the_authors(); ?>
			</ul>
		</div>
	</header>
	<div class="main">
		<div class="content entry-content">
			<?php
			the_content(__('Continued&hellip;', 'anno'));
			wp_link_pages();
			?>
		</div><!--/.content-->
	</div><!--/.main-->
	<footer class="footer">
		<?php the_tags('<dl class="kv"><dt>'.__('Tags:', 'anno').'</dt> <dd class="tags">', ' <span class="sep">&middot;</span> ', '</dd></dl>'); ?>
	</footer><!-- .footer -->
</article>