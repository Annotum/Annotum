jQuery(document).ready( function($) {
	$('.user-input').suggest( 'admin-ajax.php?action=anno-user-search', { delay: 200, minchars: 2, multiple: false} );
	
	$('input[type="submit"], a.submitdelete, #submitpost').click(function() {
		$(this).siblings('.ajax-loading').css('visibility', 'visible');
	});
	
	
	$('input[type="button"]#reviewer-add').click(function() {
		var user = $('input[type="text"]#reviewer-input').val();
		$.post(ajaxurl, {action: 'anno-add-reviewer', user: user, post_id: ANNO_POST_ID}, function(data) {
			$('ul#reviewer-list').prepend(data);
		});
	});
	
	$('input[type="button"]#co-author-add').click(function() {
		var user = $('input[type="text"]#co-author-input').val();
		$.post(ajaxurl, {action: 'anno-add-co-author', user: user, post_id: ANNO_POST_ID}, function(data) {
			$('ul#co-author-list').prepend(data);
		});
	});
});

