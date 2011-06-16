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

<article <?php post_class('article-full'); ?>>
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
		<?php cfct_misc('tools-bar'); ?>
		<div class="sec">
			<span class="title"><span><?php _e('Authors', 'anno'); ?></span></span>
			<ul class="authors nav">
				<?php anno_the_authors(); ?>
			</ul>
		</div>
	</header>
	<div class="main">
		<div class="content entry-content">
			<?php if (anno_has_funding_statement()): ?>
				<section class="sec" id="funding-statement">
					<h1><?php _e('Funding Statement', 'anno'); ?></h1>
					<?php anno_the_funding_statement(); ?>
				</section>
			<?php endif; ?>
			<?php
			the_content(__('Continued&hellip;', 'anno'));
			wp_link_pages();
			?>
			<?php if (anno_has_acknowledgements()): ?>
				<section class="sec" id="acknowledgements">
					<h1><?php _e('Acknowledgements', 'anno'); ?></h1>
					<?php anno_the_acknowledgements(); ?>
				</section>
			<?php endif; ?>
		</div><!--/.content-->
	</div><!--/.main-->
	<footer class="footer">
		<?php anno_the_appendices(); ?>
		<dl class="kv">
			<dt><?php _e('Citation', 'anno'); ?>:</dt>
			<dd><textarea class="entry-summary" readonly><?php anno_the_citation(); ?></textarea></dd>
			
			<?php the_tags('<dt>'.__('Tags:', 'anno').'</dt> <dd class="tags">', ' <span class="sep">&middot;</span> ', '</dd>'); ?>
		</dl>
	</footer><!--/.footer-->
</article>