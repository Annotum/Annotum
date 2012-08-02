/** Based on the wp_editimage plugin for WordpPress **/
(function() {
	var bookmarkIE;
	tinymce.create('tinymce.plugins.annoEquationEdit', {
		url: '',
		editor: {},

		init: function(ed, url) {
			var t = this, mouse = {}, height, figure;

			t.url = url;
			t.editor = ed;
			t._createButtons();

			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('...');
			ed.addCommand('annoEquationEdit', function() {

				//Editor loses selection when clicking on an external item
				if (tinyMCE.isIE){
					ed.selection.moveToBookmark(bookmarkIE);
				}

				var el = ed.selection.getNode(), vp, H, W, cls = ed.dom.getAttrib(el, 'class'), src = el.getAttribute('src');
				
				if ( cls.indexOf('mceItem') != -1 || el.nodeName != 'IMG')
					return;

				if (src == null || !src.match(/^http(s)?:\/\/chart\.googleapis\.com/)) {
					return;
				}
				
				// Different sizes based on whether or not editing a figure
				figure = ed.dom.getParent(el, 'fig');
				if (figure === null) {
					height = 330;
				}
				else {
					height = 560;
				}
 
				ed.windowManager.open({
					file: url + '/editimage.html',
					width: 480,
					height: height,
					inline: true,
					title: ed.getLang('annoequationedit.title')
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

			// Handle all cases where we want the buttons to go away e`
			ed.onInit.add(function(ed) {
				tinymce.dom.Event.add(ed.getWin(), 'scroll', function(e) {
					t._hideButtons();
				});
				tinymce.dom.Event.add(ed.getBody(), 'dragstart', function(e) {
					t._hideButtons();
				});
			});

			ed.onBeforeExecCommand.add(function(ed, cmd, ui, val) {
				t._hideButtons();
			});

			ed.onSaveContent.add(function(ed, o) {
				t._hideButtons();
			});

			// show editimage buttons
			ed.onMouseDown.add(function(ed, e) {
				var target = e.target, src;
				if ( target.nodeName != 'IMG' ) {
					if ( target.firstChild && target.firstChild.nodeName == 'IMG' && target.childNodes.length == 1 )
						target = target.firstChild;
					else {
						t._hideButtons();
						return;
					}
				}

				src = target.getAttribute('src');

				// Only show edit button on google chart api images
				if (src == null || !src.match(/^http(s)?:\/\/chart\.googleapis\.com/)) {
					t._hideButtons();
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
					t._showButtons(target, 'wp_editbtns', ed);
				}
			});
		},

		_hideButtons : function() {
			if ( !this.mceTout )
				return;

			if ( document.getElementById('wp_editbtns') )
				tinymce.DOM.hide('wp_editbtns');

			if ( document.getElementById('wp_gallerybtns') )
				tinymce.DOM.hide('wp_gallerybtns');

			clearTimeout(this.mceTout);
			this.mceTout = 0;
		},

		_showButtons : function(n, id, ed) {
			var t = this, p1, p2, vp, DOM = tinymce.DOM, X, Y;

			vp = ed.dom.getViewPort(ed.getWin());
			p1 = DOM.getPos(ed.getContentAreaContainer());
			p2 = ed.dom.getPos(n);

			X = Math.max(p2.x - vp.x, 0) + p1.x;
			Y = Math.max(p2.y - vp.y, 0) + p1.y;

			DOM.setStyles(id, {
				'top' : Y+5+'px',
				'left' : X+5+'px',
				'display' : 'block',
			});

			if ( this.mceTout )
				clearTimeout(this.mceTout);
			
			this.mceTout = setTimeout( function(){t._hideButtons();}, 5000 );
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
				var ed = tinyMCE.activeEditor, el = ed.selection.getNode();
				ed.windowManager.bookmark = ed.selection.getBookmark('simple');
				if (tinyMCE.isIE){
					//Editor loses selection when clicking on an external item in IE
					bookmarkIE = ed.selection.getBookmark(1);
				}
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

