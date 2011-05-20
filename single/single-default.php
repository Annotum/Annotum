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

get_header();

echo '<div id="content">';
cfct_loop();
// Remove internal comments from being displayed.
add_filter('query', 'anno_internal_comments_query');
comments_template();
remove_filter('query', 'anno_internal_comments_query');
echo '</div>';

get_sidebar();

get_footer();

?>