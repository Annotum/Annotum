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

get_header();

$s = get_query_var('s');

if (get_option('permalink_structure') != '') {
	$search_title = '<a href="'.trailingslashit(get_bloginfo('url')).'search/'.urlencode($s).'">'.esc_html($s).'</a>';
}
else {
	$search_title = '<a href="'.trailingslashit(get_bloginfo('url')).'?s='.urlencode($s).'">'.esc_html($s).'</a>';
}

?>

<div id="main-body">
	<h1 class="section-title"><?php printf(__('Search Results for: %s', 'anno'), $search_title); ?></h1>
	<?php
	cfct_loop();
	cfct_misc('nav-posts');
	?>
</div>
<div id="main-sidebar">
<?php get_sidebar();
?>
</div>
<?php get_footer(); ?>