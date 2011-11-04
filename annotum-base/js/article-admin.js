jQuery(document).ready(function($){
	/**
	 * Reset the o.data that's been modified (stripped "<p>" tags for example)
	 * back to the o.unfiltered property that was set inside the editor.js 
	 * pre_wpautop() method.
	 * 
	 * This is required for the structure of the content to be maintained 
	 * within the TinyMCE editor after save.
	 * 
	 * @TODO We may want to run our own type of _wpNop (see editor.dev.js) and remove
	 * just the line that's removing the <p> tags.  It depends on how structured and 
	 * safe the content will be going in.
	 */
	
	// Only bind if post_type is article
	if ($("#post_type").val() == 'article') {
		$('body').bind('afterPreWpautop', function(event, o) {
			o.data = o.unfiltered;
		});
	}
	
	anno_reset_doi_status = function() {
		$('#doi-status').hide().removeClass();
	}
	
	/**
	 * Deposit DOI data
	 */
	 $('#doi-deposit-submit').click(function() {
	 	var data = {action: 'anno-doi-deposit', article_id: ANNO_POST_ID};
		// Nonce
	 	data['_ajax_nonce-doi-deposit'] = $('#_ajax_nonce-doi-deposit').val();
		
		anno_reset_doi_status();

	 	$.post(ajaxurl, data, function(d) {
			if (d.regenerate_markup) {
				// Only insert it if there isn't one on the page already
				if ($("#doi-regenerate").length == 0) {
					$(d.regenerate_markup).insertBefore('#doi-deposit-submit');
				}
			}
	 	}, 'json');
	 	return false;
	 });
		
	/**
	 * DOI regeneration
	 */	
	$('#doi-regenerate').live('click', function() {
	 	var data = {action: 'anno-doi-regenerate', article_id: ANNO_POST_ID};
		// Nonce
	 	data['_ajax_nonce-doi-regenerate'] = $('#_ajax_nonce-doi-regenerate').val();
	
		anno_reset_doi_status();
		
	 	$.post(ajaxurl, data, function(d) {
			if (d.doi) {
				$('#doi.meta-doi-input').val(d.doi);
				$('#doi-status').addClass('anno-success').html(d.status).show();
			}
	 	}, 'json');
	 	return false;
	 });
	
	// We already hide with JS, lets remove the html/visual switch buttons
	$('.wp-switch-editor').remove();
	$('.wp-editor-tools').remove();
	
});