jQuery.noConflict();

jQuery(document).ready( function($) {
	$('.img-list-actions .show-img').live('click', function() {
		var img_id = $(this).attr('id').replace('toggle-', '');
		$(this).removeClass('show-img');
		$(this).addClass('hide-img');

		$('#img-edit-' + img_id).slideDown();
		// @TODO translate
		$(this).html('Hide');
		return false;
	});
	
	$('.img-list-actions .hide-img').live('click', function() {
		var img_id = $(this).attr('id').replace('toggle-', '');
		$(this).removeClass('hide-img');
		$(this).addClass('show-img');
		
		$('#img-edit-' + img_id).slideUp();
		// @TODO translate
		$(this).html('Show');
		return false;
	});
		
	
	$('#anno-popup-images-inside form.anno-img-edit').live('submit', function() {
		$.post(ajaxurl, $(this).serialize(), function(data) {
			// @TODO Image saved!!
		});
		return false;
	})
	
	$('.img-url-input button').live('click', function() {
		var attachment_id = $(this).parent('div').attr('id').replace('img-url-buttons-', '');
		var url = $(this).attr('title');
		var form = 'form#img-edit-' + attachment_id;
		$('#img-url-' + attachment_id, form).val(url);		
	});
	
	$('.anno-image-insert').live('click', function() {
		var attachment_id = $(this).attr('id').replace('anno-image-insert-', '');
		var display_type, caption, label, copyright_statement, copyright_holder, license, url, xml;
		var form = 'form#img-edit-' + attachment_id;

		alt_text = $('#img-alttext-' + attachment_id, form).val();
		img_url = $('input[name="size"]:checked', form).attr('data-url');
		file_url = $('#img-url-' + attachment_id, form).val();
		display_type = $('input[name="display"]:checked', form).val();
		
		if (display_type == 'inline') {
			// Inserting for tinyMCE. is converted to XML on save.
			xml = '<div class="inline-graphic" data-xmlel="inline-graphic" xlink:href="'+ img_url + '"  alt-text="'+ alt_text + '"></div>';
		}
		else {
			caption = $('#img-caption-' + attachment_id).val();
			label = $('#img-label-' + attachment_id).val();
			
			description = $('#img-description-' + attachment_id, form).val();
			description = description == '' ? '<br />' : description;
			copyright_statement = $('#img-copystatement-' + attachment_id, form).val();
			copyright_holder = $('#img-copyholder-' + attachment_id, form).val();
			license = $('#img-license-' + attachment_id, form).val();

			// Add the URI element if there's a file_url - 
			// something to link if the image is clicked.
			if (file_url) {
				fig_uri = '<div class="uri" data-xmlel="uri" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="' + file_url + '"></div>';
			}
			else { 
				fig_uri = '';
			}
			
			xml = '<div class="fig" data-xmlel="fig">'
						+'<img src="' + img_url + '" />'
						+'<div class="label" data-xmlel="label">' + label + '</div>'
						+'<div class="caption" data-xmlel="caption"><span class="p" data-xmlel="p">' + caption + '</span>'
						+'</div>'
						+'<div class="media" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="' + img_url + '" data-xmlel="media">'
							+ fig_uri
							+'<span class="alt-text" data-xmlel="alt-text">' + alt_text + '</span>'
							+'<span class="long-desc" data-xmlel="long-desc">' + description + '</span>'
							+'<div class="permissions" data-xmlel="permissions">'
								+'<span class="copyright-statement" data-xmlel="copyright-statement">' + copyright_statement + '</span>'
								+'<span class="copyright-holder" data-xmlel="copyright-holder">' + copyright_holder + '</span>'
								+'<div class="license" data-xmlel="license" license-type="creative-commons">'
									+'<span class="license-p" data-xmlel="license-p">'+ license +'</span>'
								+'</div>'
							+'</div>'
						+'</div>'
						+'<div _mce_bogus="1" class="clearfix"></div>'
					+'</div>';
		}


		var win = window.dialogArguments || opener || parent || top;
		win.tinyMCEPopup.restoreSelection();
		win.send_to_editor(xml);
		win.tinyMCEPopup.close();
		return false;
	});
});
