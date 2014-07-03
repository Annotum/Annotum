(function() {
	tinymce.create('tinymce.plugins.annoQuote', {
		init : function(ed, url) {
			var disabled = true;

			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('Anno_Quote', function() {
				ed.windowManager.open({
					id : 'anno-popup-quote',
					width : 480,
					height : "auto",
					wpDialog : true,
					title : ed.getLang('annoquote.title')
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});

			// Register example button
			ed.addButton('annoquote', {
				title : ed.getLang('annoquote.title'),
				cmd : 'Anno_Quote'
			});

		},
		getInfo : function() {
			return {
				longname : 'Annotum Quote Dialog',
				author : 'Crowd Favorite',
				authorurl : 'http://crowdfavorite.com',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('annoQuote', tinymce.plugins.annoQuote);
})();
