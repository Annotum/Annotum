var annoReferences;

(function($){
	var inputs = {}, ed;

	annoReferences = {
		keySensitivity: 100,
		textarea : function() { return edCanvas; },

		init : function(e) {
			inputs.dialog = $('#anno-popup-references');
			inputs.submit = $('#anno-references-submit');
			inputs.wrap = $('#wp-link-wrap');
			inputs.dialog = $( '#wp-link' );
			inputs.backdrop = $( '#wp-link-backdrop' );

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
			inputs.dialog.bind('wpdialogbeforeopen', annoReferences.beforeOpen);
		},

		onClose : function() {
			//Lets collapse the edit screens
			if ( ! annoReferences.isMCE() ) {
				annoReferences.textarea().focus();
				if ( annoReferences.range ) {
					annoReferences.range.moveToBookmark( annoReferences.range.getBookmark() );
					annoReferences.range.select();
				}
			}

			$('.anno-reference-edit').hide();
		},

		beforeOpen : function() {
			annoReferences.range = null;

			if ( ! annoReferences.isMCE() && document.selection ) {
				annoReferences.textarea().focus();
				annoReferences.range = document.selection.createRange();
			}
		},

		getCheckboxes : function() {
			return $('#anno-popup-references input[type=checkbox]:checked');
		},

		isMCE : function() {
			//return tinyMCEPopup && ( ed = tinyMCEPopup.editor ) && ! ed.isHidden();
			// This is unlikely to be used anywhere that tinyMCE is not enabled, hence returning tru
			// tinymce 4 removed tinyMCEPopup as well.
			return true;
		},

		close : function() {
			var ed = top.tinymce.activeEditor;
			ed.windowManager.close();
			ed.focus();
		},

		update : function() {
			var ed = tinymce.activeEditor;
			var xml = '', checkboxes, id, text, validNodes, node;

			validNodes = ['BODY', 'LABEL', 'CAP', 'LICENSE-P', 'PARA', 'TD', 'TH'];

			node = ed.selection.getNode();

			// If we're in the middle of a link or something similar, we want to insert the references after the element

			if (!ed.dom.isBlock(node) && $.inArray(node.nodeName, validNodes) == -1 ) {
				ed.selection.select(node);
			}
			checkboxes = annoReferences.getCheckboxes();
			checkboxes.each(function(i, checkbox) {
				id = $(checkbox).attr('id').replace('reference-checkbox-', '');
				text = $('label[for="reference-checkbox-' + id + '"]').html();
				id = parseInt(id) + 1;
				xml += '<span class="xref" data-xmlel="xref" ref-type="bibr" rid="ref' + id + '">' + id + '</span>&nbsp;';
			});

			ed.selection.collapse(false);
			ed.insertContent(xml);
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
