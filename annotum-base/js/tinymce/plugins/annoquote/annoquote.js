var annoQuote;

(function($){
	var inputs = {}, ed;

	annoQuote = {
		keySensitivity: 100,
		textarea: function() { return edCanvas; },

		init : function() {
			inputs.dialog = $('#anno-popup-quote');
			inputs.submit = $('#anno-quote-submit');

			// Bind event handlers
			inputs.dialog.keydown( annoQuote.keydown );
			inputs.dialog.keyup( annoQuote.keyup );
			inputs.submit.click( function(e){
				annoQuote.update();
				e.preventDefault();
			});

			$('#anno-quote-cancel').click(annoQuote.close);
		},

		update : function(attachment_id) {
			var display_type, caption, label, copyright_statement, copyright_holder, license, url, xml;
			var ed = tinyMCEPopup.editor;
			ed.selection.collapse(0);

			form = $('#anno-popup-quote-form');
			quote = $('input[name$="text"]', form).val();
			attribution = $('input[name$="attribution"]', form).val();
			statement = $('input[name$="statement"]', form).val();
			holder = $('input[name$="holder"]', form).val();
			license = $('input[name$="license"]', form).val();

			xml = '<div class="disp-quote" data-xmlel="disp-quote">' + quote
					+'<div class="attrib" data-xmlel="attrib">' + attribution +  '</div>'
					+'<div class="permissions" data-xmlel="permissions">'
						+'<span class="copyright-statement" data-xmlel="copyright-statement">' + statement + '</span>'
						+'<span class="copyright-holder" data-xmlel="copyright-holder">' + holder + '</span>'
						+'<div class="license" data-xmlel="license" license-type="creative-commons">'
							+'<span class="license-p" data-xmlel="license-p">'+ license +'</span>'
						+'</div>'
					+'</div>'
				+'</div>';

			tinyMCEPopup.execCommand('mceInsertContent', false, xml);
			form[0].reset();


			tinyMCEPopup.close();
		},

		keyup : function( event ) {
			var key = $.ui.keyCode;

			switch( event.which ) {
				case key.ESCAPE:
					event.stopImmediatePropagation();
					if ( ! $(document).triggerHandler( 'wp_CloseOnEscape', [{ event: event, what: 'annoquote', cb: annoQuote.close }] ) )
						annoQuote.close();

					return false;
					break;
				default:
					return;
			}
			event.preventDefault();
		},
	}
	$(document).ready( annoQuote.init );
})(jQuery);

