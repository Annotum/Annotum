(function(){
	// This plugin provides various utility to other plugins and aspects of the editor;
	tinymce.create('tinymce.plugins.annoUtility', {
		init : function(ed, url){
			var t = this;
			this.ed = ed;

			ed.on('FullscreenStateChanged', function(e) {
				console.log(e);
				var edID;
				// Transitioning to Fullscreen
				if (e.state) {
					for (edId in tinyMCE.editors) {
						// Close all editor popups, not just this one
						tinyMCE.editors[edId].windowManager.close();
						jQuery('html').removeClass('wp-toolbar');
					}
				}
				// Transitioning out of fullscreen mode
				else {
					// Only need to close this editor's popups
					t.ed.windowManager.close();
					jQuery('html').addClass('wp-toolbar');
				}
			});

			// On Fullscreen, close windows
			// On Fullscreen remove the toolbar thing
			// Off fullscreen, add the toolbar margin back in
		},
		getInfo : function() {
			return {
				longname: 'Annotum Utility',
				author: 'Crowd Favorite',
				authorurl: 'http://crowdfavorite.com/',
				infourl: 'http://annotum.wordpress.com/',
				version: "0.1"
			};
		},
	});

	tinymce.PluginManager.add('annoUtility', tinymce.plugins.annoUtility);
})();