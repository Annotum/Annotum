/*
 * Load data from tinyMCE into the equation edit form
 * Requires jQuery and Closure TexPane (js/equation-editor-compiled.js)
 */

var tinymce = null, tinyMCEPopup, tinyMCE, annoEqEdit;
tinyMCEPopup = {
	init: function() {
		var t = this, w, ti;

		// Find window & API
		w = t.getWin();
		tinymce = w.tinymce;
		tinyMCE = w.tinyMCE;
		t.editor = tinymce.EditorManager.activeEditor;
		t.params = t.editor.windowManager.params;
		t.features = t.editor.windowManager.features;

		// Setup local DOM
		t.dom = t.editor.windowManager.createInstance('tinymce.dom.DOMUtils', document);
		t.editor.windowManager.onOpen.dispatch(t.editor.windowManager, window);
	},

	getWin : function() {
		return (!window.frameElement && window.dialogArguments) || opener || parent || top;
	},

	getParam : function(n, dv) {
		return this.editor.getParam(n, dv);
	},

	close : function() {
		var t = this;

		// To avoid domain relaxing issue in Opera
		function close() {
			t.editor.windowManager.close(window);
			tinymce = tinyMCE = t.editor = t.params = t.dom = t.dom.doc = null; // Cleanup
		}

		if (tinymce.isOpera)
			t.getWin().setTimeout(close, 0);
		else
			close();
	},

	execCommand : function(cmd, ui, val, a) {
		a = a || {};
		a.skip_focus = 1;

		this.restoreSelection();
		return this.editor.execCommand(cmd, ui, val, a);
	},

	storeSelection : function() {
		this.editor.windowManager.bookmark = tinyMCEPopup.editor.selection.getBookmark(1);
	},

	restoreSelection : function() {
		var t = tinyMCEPopup;

		if ( tinymce.isIE )
			t.editor.selection.moveToBookmark(t.editor.windowManager.bookmark);
	}
};
tinyMCEPopup.init();

annoEqEdit = {
	preInit : function() {
		// import colors stylesheet from parent
		var ed = tinyMCEPopup.editor, win = tinyMCEPopup.getWin(), styles = win.document.styleSheets, url, i;

		for ( i = 0; i < styles.length; i++ ) {
			url = styles.item(i).href;
			if ( url && url.indexOf('colors') != -1 ) {
				document.getElementsByTagName('head')[0].appendChild( ed.dom.create('link', {rel:'stylesheet', href: url}) );
				break;
			}
		}
	},

	I : function(e) {
		return document.getElementById(e);
	},

	current : '',
	link : '',
	link_rel : '',
	target_value : '',
	current_size_sel : 's100',
	width : '',
	height : '',
	align : '',
	img_alt : '',

	init : function() {
		var ed = tinyMCEPopup.editor, h;
		// Check if inline or figure
		this.isFig = this.isFigure();
		h = document.body.innerHTML;
		document.body.innerHTML = ed.translate(h);
		window.setTimeout( function(){annoEqEdit.setup();}, 500 );

	},

	setup : function() {
		var t = this, el, form = document.forms[0], ed = tinyMCEPopup.editor,
			dom = tinyMCEPopup.dom, src, ta, regexS, regex, tex, texPane = new goog.ui.annotum.equation.TexPane(), altEl, descriptionEl, mediaEl;

		tinyMCEPopup.restoreSelection();
		el = ed.selection.getNode();

		if (el.nodeName != 'IMG')
			return;
 
		url = ed.dom.getAttrib(el, 'src');
		
		if (url !== null && url.match(/^http(s)?:\/\/chart\.googleapis\.com/)) {
			// Based on code from  http://stackoverflow.com/questions/901115/get-query-string-values-in-javascript
			regexS = "[\\?&]" + 'chl' + "=([^&#]*)";
			regex = new RegExp(regexS);
			tex = regex.exec(url);
			texPane.render();

			// Could be done with pure javascript, jQuery is avaialable, might as well utilize it
			$('.annotum-eq-wrapper').prependTo('#anno-popup-equations .anno-mce-popup-fields');
			texPane.texEditor.setVisible(!0);
			texPane.texEditor.setEquation(decodeURIComponent(tex[1]));
		}

		// Figure specific
		if (t.isFig) {
			mediaEl = ed.dom.getNext(el, 'media');
			if (mediaEl !== undefined) {
				altEl = mediaEl.getElementsByTagName('alt-text');
				descriptionEl = mediaEl.getElementsByTagName('long-desc');

				if (altEl !== undefined) {
					form.alt.value = altEl[0].textContent;
				}
				if (descriptionEl !== undefined) {
					form.description.value = descriptionEl[0].textContent;
				}
			}

			// Remove the hidden class
			form.figuremeta.className = form.figuremeta.className.replace('hidden', '');
		}
		
		// Show the form now its ready and populated with data
		form.className = form.className.replace('hidden', '');
		return;

	},

	isFigure : function () {
		var t = this;
		var ed = tinyMCEPopup.editor;
		var el = ed.selection.getNode();

		figure = ed.dom.getParent(el, 'fig');
		if (figure === null) {
			return false;
		}

		return true;
	},

	update : function() {
		var t = this;
		var form = document.forms[0]; // form
		var ed = tinyMCEPopup.editor;
		var el; // image in editor
		var src = $('.ee-preview-container img').prop('src'); // New image source - could be implemented without jQuery, cleaner with it
		var b;

		// Grab the image
		tinyMCEPopup.restoreSelection();
		el = ed.selection.getNode();

		if (el.nodeName != 'IMG') {
			return;
		}

		if (src == '' || src === undefined) {
			t.remove();
			return;
		}

		// History for undo/redo
		tinyMCEPopup.execCommand('mceBeginUndoLevel');

		// Update the image itself
		ed.dom.setAttribs(el, {
			src : src,
			'data-mce-src' : src
		});

		// IE Does a poor job at storing the property
		if (t.isFig || (tinymce.isIE && t.isFigure())) {
			// Update alt and description
			mediaEl = ed.dom.getNext(el, 'media');

			if (mediaEl !== undefined) {
				mediaEl.setAttribute('xlink:href', src);

				altEl = mediaEl.getElementsByTagName('alt-text');
				descriptionEl = mediaEl.getElementsByTagName('long-desc');

				// Set innerHTML, though the popup edits textContent - no tags allowed in here
				if (altEl !== undefined) {
					altEl[0].innerHTML = form.alt.value;
				}
				if (descriptionEl !== undefined) {
					descriptionEl[0].textContent = form.description.value;
				}
			}
		}

		tinyMCEPopup.execCommand('mceEndUndoLevel');
		ed.execCommand('mceRepaint');
		tinyMCEPopup.close();

		return;
	},

	// Remove the image from the dom, remove the figure entirely if the image is removed.
	remove : function() {
		var ed = tinyMCEPopup.editor, figureEl, el;

		// History for undo/redo
		tinyMCEPopup.execCommand('mceBeginUndoLevel');

		tinyMCEPopup.restoreSelection();
		
		el = ed.selection.getNode();
		if (el.nodeName != 'IMG') {
			return;
		}
		
		tinyMCEPopup.execCommand('mceBeginUndoLevel');

		if (this.isFigure()) {
			figureEl = ed.dom.getParent(el, 'fig');
			if (figure !== null) {
				ed.dom.remove(figureEl);
			}
		}
		else {
			// Just remove the image
			ed.dom.remove(el);
		}

		tinyMCEPopup.execCommand('mceEndUndoLevel');
		ed.execCommand('mceRepaint');
		tinyMCEPopup.close();
		return;
	}

};

window.onload = function(){annoEqEdit.init();};
annoEqEdit.preInit();

