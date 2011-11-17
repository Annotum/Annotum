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
		<h1 class="page-title"><a rel="bookmark" href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>
	</header>
	<div class="content">
		<?php
		the_content(); 
		wp_link_pages();
		?>
	</div><!--/content-->
</article>