(function(){
	// This plugin provides various utility to other plugins and aspects of the editor;
	var inputs = {};
	tinymce.create('tinymce.plugins.annoUtility', {
		init : function(ed, url){
			var t = this;
			inputs.$dialogs = jQuery('#anno-popup-link, #anno-popup-equations, #anno-popup-quote, #anno-popup-references, #anno-popup-source, #anno-popup-table');
			inputs.$backdrop = jQuery( '#wp-link-backdrop' );
			this.ed = ed;

			ed.on('FullscreenStateChanged', function(e) {
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

			inputs.$dialogs.on('wpdialogbeforeopen', this.beforeOpen);
			inputs.$dialogs.on('wpdialogclose', this.onClose);

			// On Fullscreen, close windows
			// On Fullscreen remove the toolbar thing
			// Off fullscreen, add the toolbar margin back in
		},
		beforeOpen : function(e) {
			inputs.$backdrop.show();
		},
		onClose : function(e) {
			inputs.$backdrop.hide();
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