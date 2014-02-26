(function() {
	tinymce.create('tinymce.plugins.annoSource', {
		init : function(ed, url) {
			var t = this;
			var disabled = true;
			t.url = url;

			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('Anno_Source', function() {
				console.log(t.url);
				ed.windowManager.open({
					url : t.url + '/source_editor.html',
					width : parseInt(ed.getParam("theme_advanced_source_editor_width", 720)),
					height : parseInt(ed.getParam("theme_advanced_source_editor_height", 580)),
					inline : true,
					resizable : true,
					maximizable : true
				}, {
					theme_url : t.url
				});
			});

			// Register example button
			ed.addButton('annosource', {
				//removing for temp fix-- title : ed.getLang('advanced.link_desc'),
				title : 'Anno Source',
				cmd : 'Anno_Source'
			});

		},
		getInfo : function() {
			return {
				longname : 'Annotum Source Editor',
				author : 'Crowd Favorite',
				authorurl : 'http://crowdfavorite.com',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('annoSource', tinymce.plugins.annoSource);
})();
