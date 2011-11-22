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
?>

<?php global $comment; ?>
	<div class="header">
		<?php echo get_avatar($comment, 40); ?>
		<h3 class="title"><?php comment_author_link(); ?></h3>
		<time class="published"><?php comment_date(); ?></time>
	</div>