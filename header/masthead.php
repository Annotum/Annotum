<div id="masthead" class="clearfix">
	<div class="in">
		<div id="masthead-body">
			<?php
			$featured = new Anno_Featured_Articles();
			$featured->render();
			?>
		</div><!-- #masthead-body -->
		<div id="masthead-sidebar">
			<?php
			$teasers = new Anno_Teaser_Articles();
			$teasers->render();
			?>
		</div><!-- #masthead-sidebar -->
	</div>
</div><!-- #masthead -->
