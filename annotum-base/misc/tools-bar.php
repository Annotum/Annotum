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

// Set a default PDF Link
$pdf_link = '';
if (function_exists('anno_pdf_download_url')) { 
	$pdf_url = anno_pdf_download_url(get_the_ID());
	if (!empty($pdf_url)) {
		$pdf_link = '<a href="'.esc_url($pdf_url).'">'.__('PDF', 'anno').'</a>';
	}
}

// Set a default XML Link
$xml_link = '';
if (function_exists('anno_xml_download_url')) {
	$xml_url = anno_xml_download_url(get_the_ID());
	if (!empty($xml_url)) {
		$xml_link = '<a href="'.esc_url($xml_url).'">'.__('XML', 'anno').'</a>';
	}
}

?>
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
		<?php echo implode(', ', array($pdf_link, $xml_link)); ?> 
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
