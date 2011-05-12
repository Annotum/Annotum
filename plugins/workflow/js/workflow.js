jQuery(document).ready( function($) {
	$('input[type="submit"], a.submitdelete, #submitpost').click(function() {
		$(this).siblings('.ajax-loading').css('visibility', 'visible');
	});
	
	
	$('input[type="button"]#reviewer-add').click(function() {
		var user = $('input[type="text"]#reviewer-input').val();
		$.post(ajaxurl, {action: 'anno-add-reviewer', user: user, post_id: ANNO_POST_ID}, function(data) {
			$('ul#reviewer-list').prepend(data);
		});
	});


});

