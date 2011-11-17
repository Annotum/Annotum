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
		</div><!-- .in -->
	</div><!-- #main -->
	<footer id="footer" class="act clearfix">
		<div class="in">
			<?php
			$footer_menu_markup = get_transient('anno_footer_menu');
			if ($footer_menu_markup === false) {
				$args = array(
					'theme_location' => 'footer',
					'container' => false,
				);
				ob_start();
					anno_nav_menu($args);
					$footer_menu_markup = ob_get_contents();
				ob_end_clean();
				set_transient('anno_footer_menu', $footer_menu_markup, (60 * 60 * 24));
			}
			echo $footer_menu_markup;
			?>
		</div>
	</footer>
<?php
wp_footer();
?>
</body>
</html>