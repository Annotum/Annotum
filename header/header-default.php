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
<!DOCTYPE html>
<html <?php language_attributes() ?>>
<head>
	<meta charset="<?php bloginfo('charset') ?>" />

	<title><?php wp_title( '-', true, 'right' ); echo esc_html( get_bloginfo('name') ); ?></title>

	<?php wp_head(); ?>
	<?php cfct_misc('custom-colors'); ?>
</head>

<body <?php body_class(); ?>>
<header id="header" class="act">
	<div class="in">
		<div class="header-body">
			<h1 id="site-name"><a href="<?php bloginfo('url') ?>/" title="Home" rel="home"><?php bloginfo('name') ?></a></h1>
			<nav id="secondary-nav" class="clearfix">
			<?php
			$args = array(
				'theme_location' => 'secondary',
				'container' => false,
			);
			wp_nav_menu($args);
			?>
			</nav>			
		</div>

		<nav id="site-nav" class="clearfix">
		<?php
		$args = array(
			'theme_location' => 'main',
			'container' => false,
			'menu_class' => 'nav'
		);
		wp_nav_menu($args);
		cfct_form('search');
		?>
		</nav>
		<?php
			if (is_home()) {
				cfct_template_file('header', 'masthead');
			}
		?>
	</div>
</header>
<div id="main" class="act">
	<div class="in">