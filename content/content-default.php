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
<article <?php post_class('article clearfix'); ?>>
	<?php cfct_template_file('content', 'header'); ?>
	<div class="content">
		<?php
		the_content();
		wp_link_pages();
		?>
	</div><!--/content-->
	<footer class="footer">
		<?php
		the_tags('', ',', '');
		?>
	</footer>
</article>