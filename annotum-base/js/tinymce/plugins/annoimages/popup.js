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
		display_type = $('input[name="display"]:checked', form).val();
		
		if (display_type == 'inline') {
			// Inserting for tinyMCE. is converted to XML on save.
			xml = '<img src="'+ img_url + '" class="_inline_graphic" alt="'+ alt_text + '"/>';
		}
		else {
			caption = $('#img-caption-' + attachment_id).val();
			label = $('#img-label-' + attachment_id).val();

			description = $('#img-description-' + attachment_id, form).val();
			description = description == '' ? '<br />' : description;
			copyright_statement = $('#img-copystatement-' + attachment_id, form).val();
			copyright_holder = $('#img-copyholder-' + attachment_id, form).val();
			license = $('#img-license-' + attachment_id, form).val();
						
			xml = '<fig>'
						+'<img src="' + img_url + '" />'
						+'<lbl>' + label + '</lbl>'
						+'<cap><para>' + caption + '</para></cap>'
						+'<media xlink:href="' + img_url + '">'
							+'<alt-text>' + alt_text + '</alt-text>'
							+'<long-desc>' + description + '</long-desc>'
							+'<permissions>'
								+'<copyright-statement>' + copyright_statement + '</copyright-statement>'
								+'<copyright-holder>' + copyright_holder + '</copyright-holder>'
								+'<license license-type="creative-commons">'
									+'<license-p>'+ license +'</license-p>'
								+'</license>'
							+'</permissions>'
						+'</media>'
						+'<div _mce_bogus="1" class="clearfix"></div>';
					+'</fig>'
		}


		var win = window.dialogArguments || opener || parent || top;
		win.tinyMCEPopup.restoreSelection();
		win.send_to_editor(xml);
		win.tinyMCEPopup.close();
		return false;
	});


});