jQuery(document).ready( function($) {
	// Typeahead
	$('.user-input').suggest( 'admin-ajax.php?action=anno-user-search', { delay: 200, minchars: 2, multiple: false} );
	
	$('input[type="submit"], a.submitdelete, #submitpost').click(function() {
		$(this).siblings('.ajax-loading').css('visibility', 'visible');
	});
	
	$('input[type="button"]#reviewer-add').click(function() {
		var user = $('input[type="text"]#reviewer-input').val();
		var data = {action: 'anno-add-reviewer', user: user, post_id: ANNO_POST_ID};
		data['_ajax_nonce-add-reviewer'] = $('#_ajax_nonce-add-reviewer').val();
		
		$.post(ajaxurl, data, function(data) {
			if (data.message == 'success') {
				$('ul#reviewer-list').prepend(data.html);
			}
			else {
				$('#reviewer-add-error').html(data.html).show();
			}
		}, 'json');
	});
	
	$('input[type="button"]#co-author-add').click(function() {
		var user = $('input[type="text"]#co-author-input').val();
		var data = {action: 'anno-add-co-author', user: user, post_id: ANNO_POST_ID};
		data['_ajax_nonce-add-co_author'] = $('#_ajax_nonce-add-co_author').val();
		$.post(ajaxurl, data, function(data) {
			if (data.message == 'success') {
				$('ul#co-author-list').prepend(data.html);
			}
			else {
				$('#co-author-add-error').html(data.html).show();
			}
		}, 'json');
	});
});

