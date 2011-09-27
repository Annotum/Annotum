<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo esc_attr(get_bloginfo('charset')); ?>">
	
	<title><?php wp_title( '-', true, 'right' ); echo esc_html( get_bloginfo('name') ); ?></title>

	<link rel="stylesheet" id="anno-css"  href="<?php echo esc_url(trailingslashit(get_template_directory_uri()).'assets/main/css/main.css?ver=1.1'); ?>" type="text/css" media="all">
	
	<?php cfct_misc('custom-colors'); ?>
	
	<style type="text/css" media="all">
		body {
			margin: 20px;
/*			color: #000 !important;*/
		}
		.sub,
		.sup{
			font-size:75%;
			line-height:0;
			position:relative;
		}
		.sup{
			top:-0.5em;
		}
		.sub{
			bottom:-0.25em;
		}
		.figcaption {
			font-size:13px;
			line-height:1.384615385;
			overflow:hidden;
		}
		div.tools-bar {
			display:none;
		}
		#wpadminbar {
			display:none;
		}
	</style>
</head>

<body <?php body_class(); ?>>
	<div id="main" class="act">
		<div class="in">
		
			<div id="main-body" class="clearfix">
				
				<?php cfct_template_file('content', 'type-article'); ?>
				
			</div><!-- #main-content -->

		</div><!-- .in -->
	</div><!-- #main -->
	<?php wp_footer(); ?>
</body>
</html>