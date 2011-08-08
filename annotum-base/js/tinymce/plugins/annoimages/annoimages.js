var annoImages;

(function($){
	var inputs = {}, ed;

	annoImages = {	
		keySensitivity: 100,
		textarea: function() { return edCanvas; },

		init : function() {
			inputs.dialog = $('#anno-popup-images');
			inputs.submit = $('.anno-image-insert');
		
			// Bind event handlers
			inputs.dialog.keydown( annoImages.keydown );
			inputs.dialog.keyup( annoImages.keyup );
			inputs.submit.click( function(e){
				var attachment_id = $(this).attr('id').replace('anno-image-insert-', '');
				annoImages.update(attachment_id);
				e.preventDefault();
			});
			
			$('#anno-images-cancel').click(annoImages.close);

		//	inputs.dialog.bind('wpdialogrefresh', annoLink.refresh);
		//	inputs.dialog.bind('wpdialogbeforeopen', annoLink.beforeOpen);
		//	inputs.dialog.bind('wpdialogclose', annoLink.onClose);
		},


		update : function(attachment_id) {
			var display_type, caption, label, copyright_statement, copyright_holder, license, url, xml;
			var ed = tinyMCEPopup.editor;
			ed.selection.collapse(0);
			//TODO Validation
			
			//<inline-graphic xlink:href="charimage.gif" ><alt-text>alternative text</alt-text></inline-graphic>
			/*
			<fig>
				<label>fig X</label>
				<caption><title>&formats;</title><p>&inlines; <xref ref-type="bibr" rid="B1">xref text</xref></p><p>&inlines; <xref ref-type="bibr" rid="B1">xref text</xref></p></caption>
				<media xlink:href="graphic.jpg">
					<alt-text>alt-text</alt-text>
					<long-desc>long-desc</long-desc>
					<permissions>
						<copyright-statement>&formats;</copyright-statement>
						<copyright-holder>holder</copyright-holder>
						<license license-type="creative-commons">
							<license-p>&inlines; <xref ref-type="bibr" rid="B1">xref text</xref></license-p>
						</license>
					</permissions>
				</media>
			</fig>
			*/

			alt_text = $('#img-alttext-' + attachment_id).val();
			url = $('#img-url-' + attachment_id).val();
						
			if (display_type == 'inline') {
				xml = '<inline-graphic xlink:href="' + url +'" ><alt-text>' + alt_text + '</alt-text></inline-graphic>';
			}
			else {
				display_type = $('#img-edit-' + attachment_id + ' input[name$="display"]:checked').val();
				caption = $('#img-caption-' + attachment_id).val();
				label = $('#img-label-' + attachment_id).val();
				description = $('#img-description-' + attachment_id).val();
				copyright_statement = $('#img-caption-' + attachment_id).val();
				copyright_holder = $('#img-caption-' + attachment_id).val();
				license = $('#img-caption-' + attachment_id).val();
				xml = '<fig><label>' + label + '</label><caption><title>' + caption +'</title></caption><media xlink:href="' + url + '"><alt-text>' + alt_text + '</alt-text><long-desc>' + description + '</long-desc><permissions><copyright-statement>' + copyright_statement + '</copyright-statement><copyright-holder>' + copyright_holder + '</copyright-holder><license license-type="creative-commons"><license-p>'+ license +'</license-p></license></permissions></media></fig>';
			}
			
			tinyMCEPopup.execCommand('mceInsertContent', false, xml);
			tinyMCEPopup.close();
		},


		keyup : function( event ) {
			var key = $.ui.keyCode;

			switch( event.which ) {
				case key.ESCAPE:
					event.stopImmediatePropagation();
					if ( ! $(document).triggerHandler( 'wp_CloseOnEscape', [{ event: event, what: 'annoimages', cb: annoImages.close }] ) )
						annoImages.close();

					return false;
					break;
				default:
					return;
			}
			event.preventDefault();
		},
	}
	$(document).ready( annoImages.init );
})(jQuery);

