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

$s = get_query_var('s');

?>

<form method="get" action="<?php echo home_url('/'); ?>">
	<input type="text" name="s" value="<?php echo esc_html($s); ?>" placeholder="<?php _e('Search', 'anno'); ?>" />
	<input type="submit" value="<?php _e('Search', 'anno'); ?>" />
</form>