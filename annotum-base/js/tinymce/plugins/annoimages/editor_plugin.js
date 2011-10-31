(function() {
	tinymce.create('tinymce.plugins.annoImages', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			var disabled = true;

			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('Anno_Images', function() {
			//	if ( disabled )
			//		return;
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
		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
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


