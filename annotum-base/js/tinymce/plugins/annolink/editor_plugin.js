(function() {
	tinymce.create('tinymce.plugins.annoLink', {
		init : function(ed, url) {
			var t = this;
			this.editor = ed;
			this.parentSelector = 'span[data-xmlel="ext-link"]';

			ed.addCommand('annoUnlink', function() {
				var se = ed.selection;
				var sn = se.getStart(),
					snp = ed.dom.getParent(sn, t.parentSelector);
				var en = se.getEnd(),
					enp = ed.dom.getParent(en, t.parentSelector);
				var n = se.getNode(),
					bookmark = se.getBookmark(),
					nodeName = t._getNodeName(n);


				if (nodeName != 'EXT-LINK') {
					n = ed.dom.getParent(n, t.parentSelector);
				}

				if (se.isCollapsed() && t._getNodeName(n) != 'EXT-LINK') {
					return;
				}


				ed.dom.remove(n, true);
				se.moveToBookmark(bookmark);
				//TODO remove partial selections.
			});

			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('Anno_Link', function() {
				var se = ed.selection;

				if (se.isCollapsed() && !ed.dom.getParent(se.getNode(), t.parentSelector)) {
					return;
				}

				ed.windowManager.open({
					id : 'anno-popup-link',
					width : 480,
					height : 'auto',
					wpDialog : true,
					title : ed.getLang('annolink.link_desc')
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});

			// Register example button
			ed.addButton('annolink', {
				//removing for temp fix-- title : ed.getLang('advanced.link_desc'),
				title : ed.getLang('annolink.insertLink'),
				cmd : 'Anno_Link'
			});
			ed.addButton('announlink', {
				//removing for temp fix-- title : ed.getLang('advanced.link_desc'),
				title : ed.getLang('annolink.removeLink'),
				cmd : 'annoUnlink'
			});

			ed.addShortcut('alt+shift+a', ed.getLang('annolink.link_desc'), 'Anno_Link');

			ed.onNodeChange.add(function(ed, cm, n, co) {
				var xmlNodeType = t._getNodeName(n);

				cm.setDisabled('annolink', co && xmlNodeType != 'EXT-LINK' && ed.dom.getParent(n, t.parentSelector) == null );
				cm.setActive('annolink', (xmlNodeType == 'EXT-LINK' || ed.dom.getParent(n, t.parentSelector) != null ));

				cm.setDisabled('announlink', co && xmlNodeType != 'EXT-LINK' && ed.dom.getParent(n, t.parentSelector) == null );
				cm.setActive('announlink', (xmlNodeType == 'EXT-LINK' || ed.dom.getParent(n, t.parentSelector) != null ) && !n.name);
			});
		},
		_getNodeName : function(node) {
			var xmlNodeType = node.getAttribute('data-xmlel');
			if (typeof xmlNodeType == 'string') {
				xmlNodeType = xmlNodeType.toUpperCase();
			}
			return xmlNodeType;
		},
		getInfo : function() {
			return {
				longname : 'Annotum Link Dialog',
				author : 'Crowd Favorite',
				authorurl : 'http://crowdfavorite.com',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('annoLink', tinymce.plugins.annoLink);
})();
