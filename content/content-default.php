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
	<?php 
	cfct_template_file('content', 'header');
	cfct_misc('tools-bar');
	cfct_misc('author-list'); ?>
	<div class="content">
	<?php //if (has_excerpt()): ?>
		<section class="abstract sec">
			<h1 class="title"><?php _e('Abstract', 'anno'); ?></h1>
			<h1 class="title"><?php _e('Abstract', 'anno'); ?></h1>
			<div class="entry-summary"><?php the_excerpt(); ?></div>
		</section><!--/.abstract-->
	<?php //endif; ?>
		<div class="entry-content">
			<?php if (has_post_thumbnail()): ?>
				<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('post-excerpt'); ?></a>
			<?php endif; ?>
			<?php
			the_content();
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