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
<!doctype html>
<!--[if lt IE 7 ]> <html class="ie6" <?php language_attributes() ?>> <![endif]-->
<!--[if IE 7 ]>    <html class="ie7" <?php language_attributes() ?>> <![endif]-->
<!--[if IE 8 ]>    <html class="ie8" <?php language_attributes() ?>> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--> <html <?php language_attributes() ?>> <!--<![endif]-->
<head>
	<meta charset="<?php bloginfo('charset') ?>" />

	<title><?php wp_title( '-', true, 'right' ); echo esc_html( get_bloginfo('name') ); ?></title>

	<?php wp_head(); ?>
	<?php cfct_misc('custom-colors'); ?>
</head>

<body <?php body_class(); ?>>
	<header id="header" class="act">
		<div class="header-body">
			<div class="in">
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
		</div>
		<div class="navigation">
			<div class="in">
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
			</div>
		</div>
		<?php
		if (is_home()) { ?>
		<div id="masthead" class="clearfix">
			<div class="in">
				<div id="masthead-body">
					<?php
					$featured = new Anno_Featured_Articles();
					$featured->render();
					?>
				</div><!-- #masthead-body -->
				<div class="teasers">
					<?php dynamic_sidebar('masthead'); ?>
				</div><!-- #masthead-sidebar -->
			</div>
		</div><!-- #masthead -->
		<?php
		}
		?>
	</header>
<div id="main" class="act">
	<div class="in">