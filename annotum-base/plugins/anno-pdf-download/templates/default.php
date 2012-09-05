<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo esc_attr(get_bloginfo('charset')); ?>">
	
	<title><?php wp_title( '-', true, 'right' ); echo esc_html( get_bloginfo('name') ); ?></title>

	<link rel="stylesheet" id="anno-pdf-css"  href="<?php echo esc_url(trailingslashit(get_template_directory_uri()).'assets/main/css/pdf.css?ver=1.1'); ?>" type="text/css" media="all">
</head>

<body <?php body_class(); ?>>
	<div id="header">
		<?php echo Anno_PDF_Download::header_markup(); ?>
	</div>
	<div id="main" class="act">
		<div class="in">
		
			<div id="main-body" class="clearfix">
				
				<?php cfct_template_file('content', 'article-pdf'); ?>
				
			</div><!-- #main-content -->

		</div><!-- .in -->
	</div><!-- #main -->
	<?php echo Anno_PDF_Download::footer_markup(); ?>
</body>
</html>