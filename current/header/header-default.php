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

?>
<!doctype html>
<?php anno_open_html(); ?>
<head>
	<meta charset="<?php bloginfo('charset') ?>" />

	<title><?php wp_title( '-', true, 'right' ); echo esc_html( get_bloginfo('name') ); ?></title>

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<header id="header" class="act">
	<div class="header-body">
		<div class="in">
			<?php if (anno_has_header_image()): ?>
				<h1 id="site-name"><a href="<?php bloginfo('url') ?>/" title="Home" rel="home"><?php anno_header_image(); ?></a></h1>
			<?php else: ?>
				<h1 id="site-name"><a href="<?php bloginfo('url') ?>/" title="Home" rel="home"><?php bloginfo('name') ?></a></h1>
			<?php endif; ?>
			<nav id="secondary-nav" class="clearfix">
			<?php
			$args = array(
				'theme_location' => 'secondary',
				'container' => false,
			);
			anno_nav_menu($args);
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
			anno_nav_menu($args);
			cfct_form('search');
			?>
			</nav>
		</div>
	</div>
	<?php if (is_home()) { ?>
	<div id="masthead" class="clearfix">
		<div class="in">
			<div id="masthead-body">
				<?php
				$featured = new Anno_Featured_Articles('anno_featured');
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