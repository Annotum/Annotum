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

$s = the_search_query();

?>

<form class="search" method="get" action="<?php echo esc_url(home_url()); ?>">
	<input type="text" name="s" value="<?php echo esc_html($s); ?>" placeholder="<?php _e('Search', 'anno'); ?>" class="type-text" />
	<button class="type-submit imr" type="submit"><?php _e('Search', 'anno'); ?></button>
</form>