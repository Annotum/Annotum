(function() {
	tinymce.create('tinymce.plugins.annoImages', {
		init : function(ed, url) {
			var disabled = true;

			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('Anno_Images', function() {
				ed.windowManager.open({
					id : 'anno-popup-images',
					width : 480,
					height : "auto",
					wpDialog : true,
					title : 'Insert Images',
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});

			// Register example button
			ed.addButton('annoimages', {
				//removing for temp fix-- title : ed.getLang('advanced.link_desc'),
				title : 'Insert Image',
				cmd : 'Anno_Images'
			});
			
			ed.addShortcut('alt+shift+a', ed.getLang('advanced.link_desc'), 'Anno_Images');
		},
		getInfo : function() {
			return {
				longname : 'Annotum Image Dialog',
				author : 'Crowd Favorite',
				authorurl : 'http://crowdfavorite.com',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('annoImages', tinymce.plugins.annoImages);
})();


