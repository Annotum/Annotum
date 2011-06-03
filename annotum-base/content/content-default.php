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

?>
<?php //cfct_misc('tools-nav'); ?>

<article <?php post_class('article'); ?>>
	<header class="header">
		<div class="entry-title">
			<h1 class="title"><a rel="bookmark" href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>
			<?php if (anno_has_subtitle()): ?>
				<p class="subtitle"><?php anno_the_subtitle(); ?></p>
			<?php endif; ?>
		</div>
		<div class="meta">
			<time class="published" pubdate datetime="<?php the_time('c'); ?>"><?php the_time('F j, Y'); ?></time>
			<?php anno_the_terms('article_category', '<span class="article-categories"> <span class="sep">&middot;</span> ', ',', '</span>'); ?>
		</div>
		<div class="sec">
			<span class="title"><?php _e('Authors', 'anno'); ?></span>
			<ul class="authors nav">
				<?php anno_the_author(); ?>
			</ul>
		</div>
	</header>
	<?php 
	cfct_misc('tools-bar');
	?>
	<div class="content">
		<section class="abstract sec">
			<h1><?php _e('Abstract', 'anno'); ?></h1>
			<div class="entry-summary"><?php the_excerpt(); ?></div>
		</section><!--/.abstract-->
		<div class="entry-content">
			<?php
			the_content(__('Continued&hellip;', 'anno'));
			wp_link_pages();
			?>
		</div><!--/.entry-content-->
	</div><!--/.content-->
	<?php cfct_misc('article-references'); ?>
	<footer class="footer">
		<?php
		the_tags('<strong>Tags:</strong> ', ' <span class="sep">&middot;</span> ', '');
		?>
	</footer><!--/.footer-->
</article>