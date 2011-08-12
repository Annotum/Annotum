var annoTable;

(function($){
	var inputs = {}, ed;

	annoTable = {	
		keySensitivity: 100,
		textarea: function() { return edCanvas; },

		init : function() {
			inputs.dialog = $('#anno-popup-table');
			inputs.submit = $('#anno-table-submit');


			inputs.dialog.keydown( annoTable.keydown );
			inputs.dialog.keyup( annoTable.keyup );
			inputs.submit.click( function(e){
				annoTable.update();
				e.preventDefault();
			});
			
			$('#anno-table-cancel').click(annoTable.close);

			inputs.dialog.bind('wpdialogrefresh', annoTable.refresh);
			inputs.dialog.bind('wpdialogbeforeopen', annoTable.beforeOpen);
			inputs.dialog.bind('wpdialogclose', annoTable.onClose);
		},

		beforeOpen : function() {
			annoTable.range = null;

			if ( ! annoTable.isMCE() && document.selection ) {
				annoTable.textarea().focus();
				annoTable.range = document.selection.createRange();
			}
		},

		isMCE : function() {
			return tinyMCEPopup && ( ed = tinyMCEPopup.editor ) && ! ed.isHidden();
		},

		refresh : function() {
			var e;
			ed = tinyMCEPopup.editor;

			tinyMCEPopup.restoreSelection();
			
			// If link exists, select proper values.
			if ( e = ed.dom.getParent(ed.selection.getNode(), 'EXT-LINK') ) {

				inputs.url.val( ed.dom.getAttrib(e, 'xlink:href'));
				inputs.title.val( ed.dom.getAttrib(e, 'title') );
				// Update save prompt.
				inputs.submit.val( annoTableL10n.update );

			// If there's no link, set the default values.
			} else {
				annoTable.setDefaultValues();
			}

			tinyMCEPopup.storeSelection();
		},

		close : function() {
			tinyMCEPopup.close();
		},

		onClose: function() {
			if ( ! annoTable.isMCE() ) {
				annoTable.textarea().focus();
				if ( annoTable.range ) {
					annoTable.range.moveToBookmark( annoTable.range.getBookmark() );
					annoTable.range.select();
				}
			}
		},

		update : function() {
			var ed = tinyMCEPopup.editor,
				e, b, html = '';
				
			var formObj = $('#anno-tinymce-table-form');
			
			tinyMCEPopup.restoreSelection();
			
			//TODO validation
			
			cols = $('input[name$="cols"]', formObj).val();
			rows = $('input[name$="rows"]', formObj).val();
			label = $('input[name$="label"]', formObj).val();
			caption = $('textarea[name$="caption"]', formObj).val();
			
			html += '<table-wrap><label>' + label + '</label><caption><title>' + caption + '</title></caption>';
			html += '<table';
//			html += makeAttrib('data-mce-new', '1');
			html += '>';

				
			html += '<thead>';
			html += '<tr>';
			for (var x=0; x<cols; x++) {
				if (!tinymce.isIE)
					html += '<th><br data-mce-bogus="1"/></th>';
				else
					html += '<th></th>';
			}
				
			for (var y=1; y<rows; y++) {
				html += "<tr>";
				for (var x=0; x<cols; x++) {
					if (!tinymce.isIE)
						html += '<td><br data-mce-bogus="1"/></td>';
					else
						html += '<td></td>';
				}
				html += "</tr>";
			}
		

			

			html += "</table>";//"</table-wrap>";

			// Move table
			if (ed.settings.fix_table_elements) {
				var patt = '';

				ed.focus();
				ed.selection.setContent('<br class="_mce_marker" />');

				tinymce.each('h1,h2,h3,h4,h5,h6'.split(','), function(n) {
					if (patt)
						patt += ',';

					patt += n + ' ._mce_marker';
				});

				tinymce.each(ed.dom.select(patt), function(n) {
					ed.dom.split(ed.dom.getParent(n, 'h1,h2,h3,h4,h5,h6,p'), n);
				});

				ed.dom.setOuterHTML(ed.dom.select('br._mce_marker')[0], html);
			} else
				ed.execCommand('mceInsertContent', false, html);

			tinymce.each(ed.dom.select('table[data-mce-new]'), function(node) {
				var td = ed.dom.select('td', node);

				try {
					// IE9 might fail to do this selection
					ed.selection.select(td[0], true);
					ed.selection.collapse();
				} catch (ex) {
					// Ignore
				}

				ed.dom.setAttrib(node, 'data-mce-new', '');
			});

			ed.addVisual();
			ed.execCommand('mceEndUndoLevel');

			tinyMCEPopup.close();
		},

		keyup: function( event ) {
			var key = $.ui.keyCode;

			switch( event.which ) {
				case key.ESCAPE:
					event.stopImmediatePropagation();
					if ( ! $(document).triggerHandler( 'wp_CloseOnEscape', [{ event: event, what: 'annotable', cb: annoTable.close }] ) )
						annoTable.close();

					return false;
					break;
				default:
					return;
			}
			event.preventDefault();
		},
	}
	$(document).ready( annoTable.init );
})(jQuery);
