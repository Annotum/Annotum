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
<header class="header">
	<div class="entry-title">
		<h1 class="title"><a rel="bookmark" href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>
		<?php if (anno_has_subtitle()): ?>
			<p class="subtitle"><?php anno_the_subtitle(); ?></p>
		<?php endif; ?>
	</div>
	<div class="meta">
		<time class="published" pubdate datetime="<?php the_time('c'); ?>"><?php the_time('F j, Y'); ?></time>
		<span class="sep">&middot;</span>
		<?php the_category(', '); ?>
	</div>
</header>