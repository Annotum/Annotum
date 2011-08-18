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
});