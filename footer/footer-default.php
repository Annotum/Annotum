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
	</div>
	<footer id="footer" class="act clearfix">
		<div class="in">
			<label>Footer Menu</label>
			<?php
			$args = array(
				'theme_location' => 'footer',
				'container' => false,
			);
			wp_nav_menu($args);
			?>
		</div>
	</footer>
</div><!--/main-->
<?php
wp_footer();
?>
</body>
</html>