jQuery(document).ready( function($) {

	$('.img-list-actions .show-img').live('click', function() {
		var img_id = $(this).attr('id').replace('toggle-', '');
		$(this).removeClass('show-img');
		$(this).addClass('hide-img');

		$('#img-edit-' + img_id).slideDown();
		//TODO translate
		$(this).html('Hide');
		return false;
	});
	
	$('.img-list-actions .hide-img').live('click', function() {
		var img_id = $(this).attr('id').replace('toggle-', '');
		$(this).removeClass('hide-img');
		$(this).addClass('show-img');
		
		$('#img-edit-' + img_id).slideUp();
		//TODO translate
		$(this).html('Show');
		return false;
	});
		
	
	$('#anno-popup-images-inside form.anno-img-edit').live('submit', function() {
		$.post(ajaxurl, $(this).serialize(), function(data) {
			//TODO Image saved!!
		});
		return false;
	})
	
	$('.anno-image-insert').live('click', function() {
		var attachment_id = $(this).attr('id').replace('anno-image-insert-', '');
		var display_type, caption, label, copyright_statement, copyright_holder, license, url, xml;

		alt_text = $('#img-alttext-' + attachment_id).val();
		url = $('#img-url-' + attachment_id).val();
		display_type = $('#img-edit-' + attachment_id + ' input[name$="display"]:checked').val();
		

		if (display_type == 'inline') {
			// Inserting for tinyMCE. is converted to XML on save.
			xml = '<img src="'+ url + '" class="_inline_graphic" alt="'+ alt_text + '"/>';
		}
		else {
			caption = $('#img-caption-' + attachment_id).val();
			label = $('#img-label-' + attachment_id).val();
			description = $('#img-description-' + attachment_id).val();
			copyright_statement = $('#img-copystatement-' + attachment_id).val();
			copyright_holder = $('#img-copyholder-' + attachment_id).val();
			license = $('#img-license-' + attachment_id).val();
			//TODO Caption Title Support
			xml = '<fig>'
					+'<img src="' + url + '" />'
					+'<label>' + label + '</label>'
					+'<cap>' + caption + '</cap>'
					+'<media xlink:href="' + url + '">'
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
					+'<div _mce_bogus="1" class="clearfix"></div>'
				+'</fig>';
				
		}

		var win = window.dialogArguments || opener || parent || top;
		
		win.send_to_editor(xml);
		win.tinyMCEPopup.close();
		return false;
	});


});