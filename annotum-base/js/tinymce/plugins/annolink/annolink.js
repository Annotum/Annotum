var annoLink;

(function($){
	var inputs = {}, ed;

	annoLink = {	
		keySensitivity: 100,
		textarea: function() { return edCanvas; },

		init : function() {
			inputs.dialog = $('#anno-popup-link');
			inputs.submit = $('#anno-link-submit');
			inputs.url = $('#anno-link-url-field');
			inputs.title = $('#anno-link-title-field');
			inputs.alt = $('#anno-link-alt-field');
			
			// Bind event handlers
			inputs.dialog.keydown( annoLink.keydown );
			inputs.dialog.keyup( annoLink.keyup );
			inputs.submit.click( function(e){
				annoLink.update();
				e.preventDefault();
			});
			
			$('#anno-link-cancel').click(annoLink.close);

			inputs.dialog.bind('wpdialogrefresh', annoLink.refresh);
			inputs.dialog.bind('wpdialogbeforeopen', annoLink.beforeOpen);
			inputs.dialog.bind('wpdialogclose', annoLink.onClose);
		},

		beforeOpen : function() {
			annoLink.range = null;

			if ( ! annoLink.isMCE() && document.selection ) {
				annoLink.textarea().focus();
				annoLink.range = document.selection.createRange();
			}
		},

		isMCE : function() {
			return tinyMCEPopup && ( ed = tinyMCEPopup.editor ) && ! ed.isHidden();
		},

		refresh : function() {
			annoLink.mceRefresh();

			// Focus the URL field and highlight its contents.
			//     If this is moved above the selection changes,
			//     IE will show a flashing cursor over the dialog.
			inputs.url.focus()[0].select();
		},

		mceRefresh : function() {
			var e;
			ed = tinyMCEPopup.editor;

			tinyMCEPopup.restoreSelection();
			
			// If link exists, select proper values.
			if ( e = ed.dom.getParent(ed.selection.getNode(), 'EXT-LINK') ) {

				inputs.url.val( ed.dom.getAttrib(e, 'xlink:href'));
				inputs.title.val( ed.dom.getAttrib(e, 'title') );
				// Update save prompt.
				inputs.submit.val( annoLinkL10n.update );

			// If there's no link, set the default values.
			} else {
				annoLink.setDefaultValues();
			}

			tinyMCEPopup.storeSelection();
		},

		close : function() {
			tinyMCEPopup.close();
		},

		onClose: function() {
			if ( ! annoLink.isMCE() ) {
				annoLink.textarea().focus();
				if ( annoLink.range ) {
					annoLink.range.moveToBookmark( annoLink.range.getBookmark() );
					annoLink.range.select();
				}
			}
		},

		getAttrs : function() {
			var tmp_attrs = {};
			tmp_attrs['xlink:href'] = inputs.url.val();
			tmp_attrs['title'] = inputs.title.val();
			tmp_attrs['ext-link-type'] = 'uri';
			return tmp_attrs;
		},

		update : function() {
			var ed = tinyMCEPopup.editor,
				attrs = annoLink.getAttrs(),
				e, b;

			tinyMCEPopup.restoreSelection();
			e = ed.dom.getParent(ed.selection.getNode(), 'EXT-LINK');

			// If the values are empty, unlink and return
			if ( ! attrs['xlink:href'] || attrs['xlink:href'] == 'http://' ) {
				if ( e ) {
					tinyMCEPopup.execCommand("mceBeginUndoLevel");
					b = ed.selection.getBookmark();
					ed.dom.remove(e, 1);
					ed.selection.moveToBookmark(b);
					tinyMCEPopup.execCommand("mceEndUndoLevel");
					annoLink.close();
				}
				return;
			}

			tinyMCEPopup.execCommand("mceBeginUndoLevel");
			// Leverage the logic of CreateLink
			if (e == null) {
				tinyMCEPopup.execCommand("CreateLink", false, "#mce_temp_url#", {skip_undo : 1});
				
				tinymce.each(ed.dom.select("a"), function(n) {
					if (ed.dom.getAttrib(n, 'href') == '#mce_temp_url#') {
						var new_element = document.createElement('ext-link');
					 	var old_innerHTML = n.innerHTML;
					
						n.parentNode.replaceChild(new_element, n);
						e = new_element;
						ed.dom.setAttribs(e, attrs);
						e.innerHTML = old_innerHTML;
					}
				});

				// Sometimes WebKit lets a user create a link where
				// they shouldn't be able to. In this case, CreateLink
				// injects "#mce_temp_url#" into their content. Fix it.
				if ( $(e).text() == '#mce_temp_url#' ) {
					ed.dom.remove(e);
					e = null;
				}

			} 
			else {
				ed.dom.setAttribs(e, attrs);
			}

			// Don't move caret if selection was image
			if ( e && (e.childNodes.length != 1 || e.firstChild.nodeName != 'IMG') ) {
				ed.focus();
				ed.selection.select(e);
				ed.selection.collapse(0);
				tinyMCEPopup.storeSelection();
			}

			tinyMCEPopup.execCommand("mceEndUndoLevel");
			annoLink.close();
		},

		setDefaultValues : function() {
			// Set URL and descriptions to defaults.
			// Leave the new tab setting as-is.
			inputs.url.val('http://');
			inputs.title.val('');
			inputs.alt.val('');
		},

		keyup: function( event ) {
			var key = $.ui.keyCode;

			switch( event.which ) {
				case key.ESCAPE:
					event.stopImmediatePropagation();
					if ( ! $(document).triggerHandler( 'wp_CloseOnEscape', [{ event: event, what: 'annolink', cb: annoLink.close }] ) )
						annoLink.close();

					return false;
					break;
				default:
					return;
			}
			event.preventDefault();
		},
	}
	$(document).ready( annoLink.init );
})(jQuery);
