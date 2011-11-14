jQuery(document).ready(function($) {
	$('input[type="button"].anno-submit').live('click', function() {
		var type = $(this).closest('table.anno-comments').attr('data-comment-type');
		var content_area = $('#anno_comment_' + type + '_textarea');
		var content = content_area.val();
		var parent_id = $('#anno-internal-comment-' + type + '-form input.parent-id').val();
		var nonce_name = '_ajax_nonce-anno-comment-' + type;
		var nonce = $('#' + nonce_name).val();
		
		var data = {action: 'anno-internal-comment', type: type, content: content, parent_id: parent_id, post_id: ANNO_POST_ID};
		data[nonce_name] = nonce;
		$.post(ajaxurl, data, function(data) {
			content_area.val('');
			$('#the-comment-list-' + type).prepend(data);
		});
	});
	
	$('table.anno-comments .reply').live('click', function() {
		var row_actions = $(this).closest('.row-actions');
		var comment_id = row_actions.attr('data-comment-id');
		var type = $(this).closest('table.anno-comments').attr('data-comment-type');

		$('#anno-internal-comment-' + type + '-form').insertAfter('#comment-' + comment_id);
		$('#anno-internal-comment-' + type + '-form input.parent-id').val(comment_id);
		$('#anno-internal-comment-' + type + '-form .anno-cancel').show();
		return false;
	});
	
	$('table.anno-comments .anno-cancel').live('click', function() {
		var type = $('table.anno-comments').attr('data-comment-type');
		$('#anno-internal-comment-' + type + '-form input.parent-id').val('0');
		
		$('#anno-internal-comment-' + type + '-form').insertAfter('#comment-add-pos-' + type);
		$('table.anno-comments .anno-cancel').hide();
	});
	
	$('table.anno-comments .anno-trash-comment').live('click', function() {
		var row_actions = $(this).closest('.row-actions');
		var comment_id = row_actions.attr('data-comment-id');
		
		var url = $(this).attr('href');
		$.get(url, function() {
			$('#comment-' + comment_id).fadeOut();
		});
	
		return false;
	})
	
	$('select#anno-review').change(function() {
		var value = $('select#anno-review option:selected').val();
		var nonce = $('#_ajax_nonce-review').val();
		
		var data = {action: 'anno-review', review: value, post_id: ANNO_POST_ID};
		data['_ajax_nonce-review'] = nonce;
		//@TODO translate
		$('.review-notice').html('saving...');

		$.post(ajaxurl, data, function(review){
			$('.review-notice').html('recommendation saved');
			$('.review-section').removeClass().addClass('review-section status-'+review);
		});
	});	
});