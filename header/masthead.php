<div id="masthead" class="clearfix">
	<div class="in">
		<div id="masthead-body">
			<?php
			$featured = new Anno_Featured_Posts();
			$featured->render();
			?>
		</div><!-- #masthead-body -->
		<div id="masthead-sidebar">
			<?php cfct_misc('post-teasers'); ?>
		</div><!-- #masthead-sidebar -->		
	</div>
</div><!-- #masthead -->
