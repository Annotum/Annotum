(function() {
	tinymce.create('tinymce.plugins.annoImages', {
		init : function(ed, url) {

			ed.addButton('annoimages', {
				//removing for temp fix-- title : ed.getLang('advanced.link_desc'),
				title : 'Insert Image',
				cmd : 'WP_Medialib'
			});

			ed.addShortcut('alt+shift+a', ed.getLang('advanced.link_desc'), 'WP_Medialib');
		},
		getInfo : function() {
			return {
				longname : 'Annotum Image Dialog',
				author : 'Crowd Favorite',
				authorurl : 'http://crowdfavorite.com',
				infourl : '',
				version : "1.1"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('annoImages', tinymce.plugins.annoImages);
})();


