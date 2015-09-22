<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2015 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }
if (CFCT_DEBUG) { cfct_banner(__FILE__); }

?>
<article <?php post_class('post-excerpt'); ?>>
	<header class="header">
		<div class="entry-title">
			<h1 class="title"><a rel="bookmark" href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>
		</div>
		<div class="meta">
			<time class="published" pubdate datetime="<?php the_time('c'); ?>"><?php the_time('F j, Y'); ?></time>
			<?php the_terms('category', '<span class="categories"> <span class="sep">&middot;</span> ', ',', '</span>'); ?>
		</div>
	</header>
	<div class="content entry-summary">
		<?php if (has_post_thumbnail()): ?>
			<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('post-excerpt'); ?></a>
		<?php endif; ?>
		<?php the_excerpt(); ?>
	</div><!--/content-->
</article>
