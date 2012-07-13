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

		h = document.body.innerHTML;
		document.body.innerHTML = ed.translate(h);
		window.setTimeout( function(){annoEqEdit.setup();}, 500 );

	},

	setup : function() {
		var t = this, el, f = document.forms[0], ed = tinyMCEPopup.editor,
			dom = tinyMCEPopup.dom, src, ta, regexS, regex, tex;

		document.dir = tinyMCEPopup.editor.getParam('directionality','');


		tinyMCEPopup.restoreSelection();
		el = ed.selection.getNode();

		if (el.nodeName != 'IMG')
			return;
 
		url = ed.dom.getAttrib(el, 'src');
		
		if (url != null && url.match(/^http(s)?:\/\/chart\.googleapis\.com/)) {
			ta = t.I('equation');

			// Based on code from  http://stackoverflow.com/questions/901115/get-query-string-values-in-javascript
			regexS = "[\\?&]" + 'chl' + "=([^&#]*)";
			regex = new RegExp(regexS);
			tex = regex.exec(url);
			if (tex == null) {
				return;
			}
			else {
				ta.value = decodeURIComponent(tex[1]);
				if (tinyMCE.isIE) {
					ta.focus();
				}
			}
		}


		return;

	}

};

window.onload = function(){annoEqEdit.init();};
annoEqEdit.preInit();

