
(function() {
	tinymce.create('tinymce.plugins.annoEquationEdit', {
		url: '',
		editor: {},

		init: function(ed, url) {
			var t = this, mouse = {};

			t.url = url;
			t.editor = ed;
			t._createButtons();

			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('...');
			ed.addCommand('annoEquationEdit', function() {
				var el = ed.selection.getNode(), vp, H, W, cls = ed.dom.getAttrib(el, 'class'), src = el.getAttribute('src');

				if ( cls.indexOf('mceItem') != -1 || el.nodeName != 'IMG')
					return;

				if (src == null || !src.match(/^http(s)?:\/\/chart\.googleapis\.com/)) {
					return;
				}

				ed.windowManager.open({
					file: url + '/editimage.html',
					width: 480,
					height: 200,
					inline: true,
					title: 'Equation'
				});
			});

			ed.onInit.add(function(ed) {
				ed.dom.events.add(ed.getBody(), 'dragstart', function(e) {
					var parent, src = e.target.getAttribute('src');

					if ( e.target.nodeName == 'IMG' && ( parent = ed.dom.getParent(e.target, 'div.mceTemp') && src != null && src.match(/^http(s)?:\/\/chart\.googleapis\.com/)) ) {
						ed.selection.select(parent);
					}
				});
			});

			// show editimage buttons
			ed.onMouseDown.add(function(ed, e) {
				var target = e.target, src;

				if ( target.nodeName != 'IMG' ) {
					if ( target.firstChild && target.firstChild.nodeName == 'IMG' && target.childNodes.length == 1 )
						target = target.firstChild;
					else
						return;
				}

				src = target.getAttribute('src');

				// Only show edit button on google chart api images
				if (src == null || !src.match(/^http(s)?:\/\/chart\.googleapis\.com/)) {
					ed.plugins.wordpress._hideButtons();
					return;
				}

				if ( ed.dom.getAttrib(target, 'class').indexOf('mceItem') == -1 ) {
					mouse = {
						x: e.clientX,
						y: e.clientY,
						img_w: target.clientWidth,
						img_h: target.clientHeight
					};
					
					// This must remain wp_editbtns in order to utilize default WP js
					ed.plugins.wordpress._showButtons(target, 'wp_editbtns');
				}
			});
		},


		_createButtons : function() {
			var t = this, ed = tinyMCE.activeEditor, DOM = tinymce.DOM, editButton;

			// This must remain wp_editbtns in order to utilize default WP js
			DOM.remove('wp_editbtns');

			DOM.add(document.body, 'div', {
				id : 'wp_editbtns',
				style : 'display:none;'
			});

			editButton = DOM.add('wp_editbtns', 'img', {
				src : t.url+'/img/edit.png',
				id : 'edit-equation-img',
				width : '16',
				height : '16',
				title : ed.getLang('wpeditimage.edit_img')
			});

			tinymce.dom.Event.add(editButton, 'mousedown', function(e) {
				var ed = tinyMCE.activeEditor;
				ed.windowManager.bookmark = ed.selection.getBookmark('simple');
				ed.execCommand('annoEquationEdit');
			});
		},

		getInfo : function() {
			return {
				longname : 'Annotum Equation Editor',
				author : 'Crowd Favorite',
				authorurl : 'http://crowdfavorite.com',
				infourl : 'http://annotum.org',
				version : '1.0'
			};
		}
	});
	tinymce.PluginManager.add('annoequationedit', tinymce.plugins.annoEquationEdit);
})();

