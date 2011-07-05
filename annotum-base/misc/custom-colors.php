<?php
	$color1 = cf_kuler_color('header');
	//$color1 = '#f00';
	
	$color2 = cf_kuler_color('navbar');
	//$color2 = '#00f';
	
	//$color3 = '#006b94';
	$color3 = cf_kuler_color('links');

?>
<style type="text/css" media="screen">

 
	/* Color 1 */
	
		/* Background Color */
		#header .header-body,
		#header .navigation,
		#masthead,
		.featured-posts .control-panel a,
		.widget .tab-active,
		#reply button,
		.tools-nav nav li a,
		.widget-recent-posts .nav .ui-tabs-selected {
			background-color: <?php echo $color1; ?>;
		}

		/* Border Color */
		#site-nav li,
		.callout-item,
		.widget-recent-posts .nav li { 
			border-color: <?php echo $color1; ?>;
		}
	
		/* Text Color */
		.featured-posts .control-panel {
			color: <?php echo $color1; ?>;
		}


	/* Color 2 */
	
		/* Background Color */
		#site-nav,
		#site-nav li,
		.featured-posts .carousel-item,
		.post-teasers .post-teaser-item {
			background-color: <?php echo $color2; ?>;
		}

	/* Link Color */
	a {
		color: <?php echo $color3; ?>;
	}
</style>
