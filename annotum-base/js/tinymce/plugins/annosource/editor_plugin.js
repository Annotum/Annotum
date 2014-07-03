(function() {
	tinymce.create('tinymce.plugins.annoSource', {
		init : function(ed, url) {
			var t = this;
			t.url = url;

			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('Anno_Source', function() {
				var height =  Math.max(
					document.body.scrollHeight, document.documentElement.scrollHeight,
					document.body.offsetHeight, document.documentElement.offsetHeight,
					document.body.clientHeight, document.documentElement.clientHeight
				);

				height = (height > 1080) ? 1080 : height;

				ed.windowManager.open({
					id : 'anno-popup-source',
					width : 800,
					height : height - 200,
					wpDialog : true,
					title : ed.getLang('annosource.windowTitle'),
					resizable : true,
					inline : true
				}, {
					plugin_url : url
				});
			});

			// Register example button
			ed.addButton('annosource', {
				title : ed.getLang('annosource.title'),
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
