(function($) {
	$('#snapshot-wrapper').sortable({
		axis:'y',
		items:'fieldset',
		containment : 'parent'
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

	$(document).on('click', '.snapshot-remove', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$(this).closest('.snapshot-item').fadeOut(function() {
			$(this).remove();
		});
	});

	// Update the handle name when the name changes in the snapshot data
	$(document).on('blur', '.snapshot-surname,.snapshot-given_names,.snapshot-prefix,.snapshot-suffix', function() {
		var id = $(this).data('id');
		var title = $('#prefix-' + id).val() + ' ' + $('#given_names-' + id).val() + ' ' + $('#surname-' + id).val() + ' ' + $('#suffix-' + id).val();

		title = $.trim(title);
		if (title == '') {
			title = id;
		}
		$('#snapshot-handle-' + id).html(title);
	});

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
	$(document).on('keydown', 'input[type="text"]#snapshot-user-input', function(e) {	
		if (e.keyCode && e.keyCode == 13) {
			var user = $.trim($('#snapshot-user-input').val());
			anno_snapshot_add(user);
			return false;
		}
	});

	$(document).on('click', '#snapshot-add-another', function(e) {
		e.preventDefault();
		anno_snapshot_add($.trim($('#snapshot-user-input').val()));
	});
})(jQuery);