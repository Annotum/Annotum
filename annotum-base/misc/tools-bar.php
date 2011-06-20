<div class="tools-bar supplement clearfix">
	<div class="cell print">
		<a href="#" onclick="window.print(); return false;"><?php _e('Print Article', 'anno'); ?></a>
	</div>
	<div class="cell citation">
		<a><?php _e('Citation', 'anno'); ?></a>
		<div class="citation-container">
			<textarea class="entry-summary" readonly><?php anno_the_citation(); ?></textarea>
		</div><!--/.citation-container -->
	</div>
	<div class="cell download">
		<label>Download:</label> <a href="#">PDF</a>, <a href="#">XML</a>
	</div>
	<div class="cell share clearfix">
		<div class="social-nav">
			<ul class="nav">
				<li><?php anno_email_link(); ?></li>
				<li><?php anno_twitter_button(); ?></li>
				<li><?php anno_facebook_button(); ?></li>
			</ul>
		</div>
	</div>
</div><!-- .action-bar -->
