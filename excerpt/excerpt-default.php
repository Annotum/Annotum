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
<article <?php post_class('article article-short clearfix'); ?>>
	<header class="header">
		<h1 class="title"><a rel="bookmark" href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>
		<p><?php the_author(); ?> on <?php the_time('F j, Y'); ?></p>
	</header>
	<div class="content">
		<?php

		the_excerpt();

		?>
	</div><!--/content-->
	<footer class="footer">
		<?php
		echo 'Categories: ';
		the_category(', ');
		echo ' &bull; ';
		comments_popup_link(__('No comments', 'carrington-jam'), __('1 comment', 'carrington-jam'), __('% comments', 'carrington-jam'));

		?>
	</footer>
</article>