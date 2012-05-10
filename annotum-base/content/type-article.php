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
		
		<?php
		
 			if (function_exists('annowf_get_clone_dropdown')) {
				$markup = annowf_get_clone_dropdown(get_the_ID());
				if (!empty($markup)) {
		?>
			<div class="sec sec-revisions">
				<span class="title"><span><?php _e('Revisions', 'anno'); ?></span></span>
				<?php _e('This article is either a revised version or has previous revisions', 'anno'); ?>
				<?php echo $markup; ?>
			</div>
		<?php
				}
			}
		
		?>
		
		
		<div class="sec sec-authors">
			<span class="title"><span><?php _e('Authors', 'anno'); ?></span></span>
			<ul class="authors nav">
				<?php anno_the_authors(); ?>
			</ul>
		</div>
	</header>
	<div class="main">
		<div class="content entry-content">
			<?php if (has_excerpt()): ?>
				<section class="sec">
					<h1><span><?php _e('Abstract', 'anno'); ?></span></h1>
					<?php the_excerpt(); ?>
				</section>
			<?php endif; ?>
			<?php if (anno_has_funding_statement()): ?>
				<section class="sec" id="funding-statement">
					<h1><span><?php _e('Funding Statement', 'anno'); ?></span></h1>
					<?php anno_the_funding_statement(); ?>
				</section>
			<?php endif; ?>
			<?php
			the_content(__('Continued&hellip;', 'anno'));
			wp_link_pages();
			?>
			<?php if (anno_has_acknowledgements()): ?>
				<section class="sec" id="acknowledgements">
					<h1><span><?php _e('Acknowledgements', 'anno'); ?></span></h1>
					<?php anno_the_acknowledgements(); ?>
				</section>
			<?php endif; ?>
			<?php anno_the_appendices(); ?>
			<?php anno_the_references(); ?>
		</div><!--/.content-->
	</div><!--/.main-->
	<footer class="footer">
		<?php
		anno_the_terms('article_tag', '<dl class="kv"><dt>'.__('Tags:', 'anno').'</dt> <dd class="tags">', ' <span class="sep">&middot;</span> ', '</dd></dl>'); 
		//the_tags('<dt>'.__('Tags:', 'anno').'</dt> <dd class="tags">', ' <span class="sep">&middot;</span> ', '</dd>'); ?>
	</footer><!-- .footer -->
</article>
