<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8">
		<?php 
		wp_head();
		cfct_misc('custom-colors');
		?>
	</head>
	<body>
		<div id="main-body">
		
			<article class="article-full">
				<div class="main">
					<div class="content entry-content">
						<?php echo $this->post_html; ?>
					</div><!--/.content-->
				</div><!--/.main-->
			</article>
		
		</div><!-- /main-body -->
	</body>
</html>



