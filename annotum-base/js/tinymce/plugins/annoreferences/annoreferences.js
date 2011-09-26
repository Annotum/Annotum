var annoReferences;

(function($){
	var inputs = {}, ed;

	annoReferences = {	
		keySensitivity: 100,
		textarea : function() { return edCanvas; },

		init : function() {

			inputs.dialog = $('#anno-popup-references');
			inputs.submit = $('#anno-references-submit');
			
			inputs.checkboxes = $('#anno-popup-references input');
			
			// Bind event handlers
			inputs.dialog.keyup( annoReferences.keyup );
			
			inputs.submit.click( function(e){
				annoReferences.update();
				e.preventDefault();
			});
			$('#anno-references-cancel').click( annoReferences.close);

			inputs.dialog.bind('wpdialogrefresh', annoReferences.refresh);
			inputs.dialog.bind('wpdialogclose', annoReferences.onClose);			
		},

		onClose : function() {
			//Lets collapse the edit screens
			$('.anno-reference-edit').hide();
		},

		open : function() {
			// Initialize the dialog if necessary (html mode).
			if ( ! inputs.dialog.data('wpdialog') ) {
				inputs.dialog.wpdialog({
					title: annoLinkL10n.title,
					width: 480,
					height: 'auto',
					modal: true,
					dialogClass: 'wp-dialog',
					zIndex: 300000
				});
			}

			inputs.dialog.wpdialog('open');
		},
		
		getCheckboxes : function() {
			return $('#anno-popup-references input[type=checkbox]:checked');
		},
		
		isMCE : function() {
			return tinyMCEPopup && ( ed = tinyMCEPopup.editor ) && ! ed.isHidden();
		},

		close : function() {
			tinyMCEPopup.close();
		},
		
		update : function() {
			var ed = tinyMCEPopup.editor
			var xml, checkboxes, id, text, validNodes;
			xml = '';
			validNodes = ['BODY', 'LABEL', 'CAP', 'LICENSE-P', 'PARA', 'TD', 'TH'];
			
			var node = ed.selection.getNode();
			
			// If we're in the middle of a link or something similar, we want to insert the references after the element
			if (!ed.dom.isBlock(node) && $.inArray(node.nodeName, validNodes) == -1 ){
				ed.selection.select(node);
			}
			checkboxes = annoReferences.getCheckboxes();
			checkboxes.each(function(i, checkbox) {
				
				//TODO proper reference (text)
				id = $(checkbox).attr('id').replace('reference-checkbox-', '');
				text = $('label[for="reference-checkbox-' + id + '"]').html();
				id = parseInt(id) + 1;
				xml += '<xref ref-type="bibr" rid="R' + id + '">' + id + '</xref>';
			});
			ed.selection.collapse();
			
			ed.execCommand('mceinsertContent', null, xml);
			
			annoReferences.close();
		},

		keyup: function( event ) {
			var key = $.ui.keyCode;

			switch( event.which ) {
				case key.ESCAPE:
					event.stopImmediatePropagation();
					if ( ! $(document).triggerHandler( 'wp_CloseOnEscape', [{ event: event, what: 'annoreferences', cb: annoReferences.close }] ) )
						annoReferences.close();
					return false;
					break;
				default:
					return;
			}
			event.preventDefault();
		},
	}
	$(document).ready( annoReferences.init );	
})(jQuery);