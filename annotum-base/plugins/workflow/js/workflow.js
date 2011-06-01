jQuery(document).ready( function($) {
	// Typeahead
	$('.user-input').suggest( 'admin-ajax.php?action=anno-user-search', { delay: 200, minchars: 2, multiple: false} );
	
	$('input[type="submit"], a.submitdelete, #submitpost').click(function() {
		$(this).siblings('.ajax-loading').css('visibility', 'visible');
	});
	
	$('input[type="button"]#reviewer-add').click(function() {
		var user = $('input[type="text"]#reviewer-input').val();
		var data = {action: 'anno-add-reviewer', user: user, post_id: ANNO_POST_ID};
		data['_ajax_nonce-manage-reviewer'] = $('#_ajax_nonce-manage-reviewer').val();
		
		$.post(ajaxurl, data, function(d) {
			if (d.message == 'success') {
				$('ul#reviewer-list').prepend(d.html);
			}
			else {
				$('#reviewer-add-error').html(d.html).show();
			}
		}, 'json');
	});
	
	$('input[type="button"]#co-author-add').click(function() {
		var user = $('input[type="text"]#co-author-input').val();
		var data = {action: 'anno-add-co-author', user: user, post_id: ANNO_POST_ID};
		data['_ajax_nonce-manage-co_author'] = $('#_ajax_nonce-manage-co_author').val();
		$.post(ajaxurl, data, function(d) {
			if (d.message == 'success') {
				$('ul#co-author-list').prepend(d.html);
				// Append co-author to author dropdown
				$('#post_author_override').append(d.author);
				
			}
			else {
				$('#co-author-add-error').html(d.html).show();
			}
		}, 'json');
	});
	
	$('.anno-user-remove').live('click', function() {
		var click = $(this);
		var type = $(this).closest('ul').attr('data-type');
		var user_id = $(this).closest('li').attr('id').replace('user-', '');
		var data = {action: 'anno-remove-' + type, user_id: user_id, post_id: ANNO_POST_ID};
		data['_ajax_nonce-manage-' + type] = $('#_ajax_nonce-manage-' + type).val();
		
		$.post(ajaxurl, data, function(d) {
			if (d.message == 'success') {
				click.closest('li').fadeOut();
				// Remove from author dropdown menu
				if (type == 'co_author') {
					$('#post_author_override option[value=' + user_id + ']').remove();
				}
			}
		}, 'json');
		return false;
	});
	
});

