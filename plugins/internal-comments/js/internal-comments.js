jQuery(document).ready(function($) {
	$('input[type="button"].anno-internal-comment-submit').live('click', function() {
		var type = $(this).attr('data-comment-type');
		var content_area = $('#anno_comment_' + type + '_textarea');
		var content = content_area.val();
		var parent_id = $(this).attr('data-comment-parent');
		$.post(ajaxurl, {action: 'anno-internal-comment', type: type, content: content, parent_id: parent_id}, function(data) {
			content_area.val('');
			$('#the-comment-list-general').prepend(data);
		});
	});
	
	$('table.anno-comments .reply').live('click', function() {
		var row_actions = $(this).closest('.row-actions');
		var comment_id = row_actions.attr('data-comment-id');
		var comment_type = row_actions.attr('data-comment-type');

		$('.anno-internal-comment-' + comment_type + '-form').insertAfter('#comment-' + comment_id);
		$('.anno-internal-comment-' + comment_type + '-form input.parent_id').val(comment_id);
		return false;
	});
});