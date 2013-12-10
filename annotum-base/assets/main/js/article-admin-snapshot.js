(function($) {
	$(document).ready(function() {
		$('#snapshot-wrapper').sortable({
			axis:'y',
			items:'fieldset',
			containment : 'parent'
		});
	});

	$(document).on('click', '.snapshot-item h4, .snapshot-handlediv', function() {
		var $t = $(this);
		$t.siblings('.inside').toggle();
		if ($t.hasClass('snapshot-handlediv')) {
			swap_indicator($t);
		}
		else {
			swap_indicator($t.siblings('.snapshot-handlediv'));
		}
	})
	// Remove row
	.on('click', '.snapshot-remove', function(e) {
		e.preventDefault();
		e.stopPropagation();
		if (confirm(annoAAS.removeConfirmation)) {
			$(this).parents('fieldset.snapshot-item').remove();
		}
	})
	// Add another
	.on('click', '#snapshot-add-another', function(e) {
		e.preventDefault();
		anno_snapshot_add($.trim($('#snapshot-user-input').val()));
	})
	// Update handle name
	.on('blur', '.snapshot-surname,.snapshot-given_names,.snapshot-prefix,.snapshot-suffix', function() {
		var id = $(this).data('id');
		var title = $('#prefix-' + id).val() + ' ' + $('#given_names-' + id).val() + ' ' + $('#surname-' + id).val() + ' ' + $('#suffix-' + id).val();

		title = $.trim(title);
		if (title == '') {
			title = id;
		}
		$('#snapshot-handle-' + id +' .snapshot-title').html(title);
	})
	.on('keydown', 'input[type="text"]#snapshot-user-input', function(e) {
		if (e.keyCode && e.keyCode == 13) {
			var user = $.trim($('#snapshot-user-input').val());
			anno_snapshot_add(user);
			return false;
		}
	});

	// Swap between + and - for snapshot handle
	function swap_indicator($handle) {
		if ($handle.siblings('.inside').is(':visible')) {
			$handle.html('&ndash;');
		}
		else {
			$handle.html('+');
		}
	}

	function anno_snapshot_add(user) {
		if (!user) {
			return false;
		}
		var status_div = $('#snapshot-status');
		var data = {action: 'anno-add-user-snapshot', user: user};

		// Clear status div and hide.
		status_div.html('').hide();

		// ajaxurl defined by WP
		$.post(ajaxurl, data, function(response) {
			if (response.result == 'success') {
				$('#snapshot-wrapper').append(response.html);
				status_div.html(response.status).removeClass('anno-error').addClass('anno-success').fadeIn();
			}
			else {
				status_div.html(response.status).removeClass('anno-success').addClass('anno-error').fadeIn();
			}
			// Clear input box
			$('#snapshot-user-input').val('');
		}, 'json');

	}
})(jQuery);
