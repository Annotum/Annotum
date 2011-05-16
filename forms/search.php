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

if (get_option('permalink_structure') != '') {
	$onsubmit = "location.href=this.action+'search/'+encodeURIComponent(this.s.value).replace(/%20/g, '+'); return false;";
}
else {
	$onsubmit = '';
}

?>

<form method="get" action="<?php echo trailingslashit(get_bloginfo('url')); ?>" onsubmit="<?php echo $onsubmit; ?>">
	<input type="text" id="s" name="s" value="<?php echo esc_html($s); ?>" size="15" />
	<input type="submit" name="submit_button" value="<?php _e('Search', 'carrington-jam'); ?>" />
</form>