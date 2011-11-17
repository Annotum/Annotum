(function() {
	tinymce.create('tinymce.plugins.annoLink', {		
		init : function(ed, url) {
			this.editor = ed;
			
		/*	removeLink : function(element) {
				if (node.nodeName == 'EXT-Link') {
					ed.dom.remove(element, true);
				}
			} */
			
			ed.addCommand('annoUnlink', function() {
				var se = ed.selection;
				var sn = se.getStart(),
					snp = ed.dom.getParent(sn, 'EXT-LINK');
				var en = se.getEnd(),
					enp = ed.dom.getParent(en, 'EXT-LINK');
				var n = se.getNode(),
					bookmark = se.getBookmark();
					
				if (n.nodeName != 'EXT-LINK') {
					n = ed.dom.getParent(n, 'EXT-LINK');
				}
				if (se.isCollapsed() && n.nodeName != 'EXT-LINK')
					return;

				
				ed.dom.remove(n, true);
				se.moveToBookmark(bookmark);
				//TODO remove partial selections.
			});
			
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('Anno_Link', function() {
				var se = ed.selection;			
				
				if (se.isCollapsed() && !ed.dom.getParent(se.getNode(), 'EXT-LINK'))
					return;
									
				ed.windowManager.open({
					id : 'anno-popup-link',
					width : 480,
					height : 'auto',
					wpDialog : true,
					title : ed.getLang('advlink.link_desc'),
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});
				
			// Register example button
			ed.addButton('annolink', {
				//removing for temp fix-- title : ed.getLang('advanced.link_desc'),
				title : 'Insert Link',
				cmd : 'Anno_Link'
			});
			ed.addButton('announlink', {
				//removing for temp fix-- title : ed.getLang('advanced.link_desc'),
				title : 'Remove Link',
				cmd : 'annoUnlink'
			});

			ed.addShortcut('alt+shift+a', ed.getLang('advanced.link_desc'), 'Anno_Link');

			ed.onNodeChange.add(function(ed, cm, n, co) {			
				cm.setDisabled('annolink', co && n.nodeName != 'EXT-LINK' && ed.dom.getParent(n, 'EXT-LINK') == null );
				cm.setActive('annolink', (n.nodeName == 'EXT-LINK' || ed.dom.getParent(n, 'EXT-LINK') != null ) && !n.name);
				
				cm.setDisabled('announlink', co && n.nodeName != 'EXT-LINK' && ed.dom.getParent(n, 'EXT-LINK') == null );
				cm.setActive('announlink', (n.nodeName == 'EXT-LINK' || ed.dom.getParent(n, 'EXT-LINK') != null ) && !n.name);
			});
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