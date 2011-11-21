jQuery(document).ready( function($) {
	// Type-ahead
	$('.user-input').suggest( 'admin-ajax.php?action=anno-user-search', { delay: 200, minchars: 2, multiple: false} );
	
	$('input[type="submit"], a.submitdelete, #submitpost').click(function() {
		$(this).siblings('.ajax-loading').css('visibility', 'visible');
	});

	
    // Register our add functions so we can call them with a generated string.
    var anno_manage_user = {};



 	// @param string user (login or email expected) 
	anno_manage_user.add_co_author = function anno_add_co_author(user) {
		if (user == '') {
			return false;
		}
		
		var status_div = $('#co_author-add-status');
		var data = {action: 'anno-add-co-author', user: user, post_id: ANNO_POST_ID};
		data['_ajax_nonce-manage-co_author'] = $('#_ajax_nonce-manage-co_author').val();
		
		// Clear status div and hide.
		status_div.html('').hide();
		
		$.post(ajaxurl, data, function(d) {
			if (d.message == 'success') {
				$('ul#co-author-list').prepend(d.html);
				// Append co-author to author dropdown
				$('#post_author_override').append(d.author);
				//Clear input box
				$('input[type="text"]#co_author-input').val('');
			}
			else {
				status_div.html(d.html).removeClass('anno-success').addClass('anno-error').show();
			}
		}, 'json');
	}
	
	$('input[type="text"]#co_author-input').keydown(function(e) {
		if (e.keyCode && e.keyCode == 13) {
			var user = $('input[type="text"]#co-author-input').val();
			anno_manage_user.add_co_author(user);
			return false;
		}
	});
	
	$('input[type="button"]#co_author-add').click(function() {
		var user = $('input[type="text"]#co_author-input').val();
		anno_manage_user.add_co_author(user);
		return false;
	});
	
	
	// @param string user (login or email expected) 
	anno_manage_user.add_reviewer = function anno_add_reviewer(user) {
		if (user == '') {
			return false;
		}
		var status_div = $('#reviewer-add-status');
		var data = {action: 'anno-add-reviewer', user: user, post_id: ANNO_POST_ID};
		data['_ajax_nonce-manage-reviewer'] = $('#_ajax_nonce-manage-reviewer').val();

		// Clear status div
		status_div.html('').hide();

		$.post(ajaxurl, data, function(d) {
			if (d.message == 'success') {
				$('ul#reviewer-list').prepend(d.html);
				$('input[type="text"]#reviewer-input').val('');
				
				
				// Increment reviewer counts on in_review state page
				if ($("#anno-reviewers-count").length > 0) {
					var reviewers = $('#anno-reviewers-count').html();
					$('#anno-reviewers-count').html(Number(reviewers) + 1);
				}
				
				if ($("#anno-reviewed-count").length > 0) {
					var reviewed = $('#anno-reviewed-count').html();
					if (d.increment == 1) {
						$('#anno-reviewed-count').html(Number(reviewed) + 1);
					}
				}
			}
			else {
				status_div.html(d.html).removeClass('anno-success').addClass('anno-error').show();
			}
		}, 'json');
	}
	
	$('input[type="text"]#reviewer-input').keydown(function(e) {
		if (e.keyCode && e.keyCode == 13) {
			var user = $('input[type="text"]#reviewer-input').val();
			anno_manage_user.add_reviewer(user);
			return false;
		}
	});
	
	$('input[type="button"]#reviewer-add').click(function() {
		var user = $('input[type="text"]#reviewer-input').val();
		anno_manage_user.add_reviewer(user);
	});	
	
	// Create form and submit to avoid embedding a form within a form in the markup
	$('.anno-user-remove').live('click', function() {
		var clicked = $(this);
		var type = $(this).closest('ul').attr('data-type');
		var user_id = $(this).closest('li').attr('id').replace('user-', '');
		var data = {action: 'anno-remove-' + type, user_id: user_id, post_id: ANNO_POST_ID};
		data['_ajax_nonce-manage-' + type] = $('#_ajax_nonce-manage-' + type).val();
		
		$.post(ajaxurl, data, function(d) {
			if (d.message == 'success') {
				clicked.closest('li').fadeOut();
				// Remove from author dropdown menu
				if (type == 'co_author') {
					$('#post_author_override option[value=' + user_id + ']').remove();
				}
				else if (type == 'reviewer') {
					if ($("#anno-reviewers-count").length > 0) {
						var reviewers = $('#anno-reviewers-count').html();
						$('#anno-reviewers-count').html(Number(reviewers) - 1);
					}

					if ($("#anno-reviewed-count").length > 0) {
						var reviewed = $('#anno-reviewed-count').html();
						if (d.decrement == 1) {
							$('#anno-reviewed-count').html(Number(reviewed) - 1);
						}
					}
				}
			}
		}, 'json');
		return false;
	});
	
	// Create form and submit to avoid embedding a form within a form in the markup
	$('.anno-create-user').live('click', function() {
		// Type, reviewer or co-author
		var type = $(this).attr('data-type');
		var div_selector = 'div#anno-invite-' + type;
		var status_div = $('#' + type +  '-add-status');
		
		var user_email_div = $(div_selector + ' input[name="invite_email"]');
		var user_email = user_email_div.val();
		
		var post_data = {user_email : user_email, action : 'anno-invite-user'};
		post_data['_ajax_nonce-create-user'] = $('#_ajax_nonce-create-user').val();

		status_div.html('').hide();
		
		$.post(ajaxurl, post_data, function(d) {
			if (d.code == 'success') {
				// Determine which function adds the user to the post
				// co_author or reviewer.
				var fn = 'add_' + type;
				if (fn in anno_manage_user) {
				    anno_manage_user[fn](d.user);
				}

				// Reset the fields
				user_email_div.val('');

				// Hide invite interface, show search interface
				$('#anno-invite-' + type).hide();
				$('#anno-user-input-' + type).show();
				
				status_div.html(d.message).removeClass('anno-error').addClass('anno-success').show();
			}
			else {
				status_div.html(d.message).removeClass('anno-success').addClass('anno-error').show();
			}			
			
		}, 'json');
		return false; 	
	});
		
	$('.anno-show-search-co_author').live('click', function() {
		$('#anno-invite-co_author').hide();
		$('#anno-user-input-co_author').show();
		return false;
	});
	
	
	$('.anno-show-create-co_author').live('click', function() {
		$('#anno-user-input-co_author').hide();
		$('#anno-invite-co_author').show();
		return false;
	});
	
	$('.anno-show-search-reviewer').live('click', function() {
		$('#anno-invite-reviewer').hide();
		$('#anno-user-input-reviewer').show();
		return false;
	});
	
	$('.anno-show-create-reviewer').live('click', function() {
		$('#anno-user-input-reviewer').hide();
		$('#anno-invite-reviewer').show();
		return false;
	});
});

