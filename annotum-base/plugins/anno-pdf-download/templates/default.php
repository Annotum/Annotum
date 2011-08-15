<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8">
		<?php 
		wp_head();
		cfct_misc('custom-colors');
		?>
	</head>
	<body>
		<h1 class="title">My H1</h1>
		<h2 class="title">My H2</h2>
		<h3 class="title">My H3</h3>
		<?php echo $this->post_html; ?>
	</body>
</html>