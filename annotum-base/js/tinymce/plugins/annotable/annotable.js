/**
 * Based on the tables plugin for tinyMCE developed by Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

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
			return true; // Unlikely a non mce context
		},

		close : function() {
			var ed = tinymce.activeEditor;
			ed.windowManager.close();
			inputs.dialog.find('input:not(.button),textarea').val('');
			ed.focus();
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
			var ed = tinymce.activeEditor,
				e, b, html = '';

			var formObj = $('#anno-tinymce-table-form');

			cols = $('input[name$="cols"]', formObj).val();
			rows = $('input[name$="rows"]', formObj).val();
			label = $('input[name$="label"]', formObj).val();
			caption = $('textarea[name$="caption"]', formObj).val();

			html += '<div class="table-wrap" data-xmlel="table-wrap"><div class="label" data-xmlel="label">' + label + '</div><div class="caption" data-xmlel="caption"><div class="p" data-xmlel="p">' + caption + '</div></div><table class="table" data-xmlel="table">';
			html += '<thead class="thead" data-xmlel="thead">';
			html += '<tr class="tr" data-xmlel="tr">';
			for (var x=0; x<cols; x++) {
				if (!tinymce.isIE)
					html += '<th class="th" data-xmlel="th"><br data-mce-bogus="1"/></th>';
				else
					html += '<th class="th" data-xmlel="th"><br data-mce-bogus="1"/></th>';
			}
			html += '</tr>';
			html += '</thead><tbody class="tbody" data-xmlel="tbody">';

			for (var y=1; y<rows; y++) {
				html += '<tr class="tr" data-xmlel="tr">';
				for (var x=0; x<cols; x++) {
					if (!tinymce.isIE)
						html += '<td class="td" data-xmlel="td"><br data-mce-bogus="1"/></td>';
					else
						html += '<td class="td" data-xmlel="td"><br data-mce-bogus="1"/></td>';
				}
				html += '</tr>';
			}

			html += "</tbody></table></div>";

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
			this.close();
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
