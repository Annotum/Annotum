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

// Article Enqueue
function anno_media_enqueue() {
	wp_enqueue_script(
		'annotum-media',
		trailingslashit(get_template_directory_uri()).'assets/main/js/media.js',
		array('jquery', 'backbone', 'media-models')
	);
}
add_action('admin_enqueue_scripts', 'anno_media_enqueue');
