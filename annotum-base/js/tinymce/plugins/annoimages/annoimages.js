var annoImages;

(function($){
	var inputs = {}, ed;

	annoImages = {	
		keySensitivity: 100,
		textarea: function() { return edCanvas; },

		init : function() {
			inputs.dialog = $('#anno-popup-images');
		
			// Bind event handlers
			inputs.dialog.keydown( annoImages.keydown );
			inputs.dialog.keyup( annoImages.keyup );
			
			$('#anno-images-cancel').click(annoImages.close);

			//	inputs.dialog.bind('wpdialogrefresh', annoLink.refresh);
			//	inputs.dialog.bind('wpdialogbeforeopen', annoLink.beforeOpen);
			//	inputs.dialog.bind('wpdialogclose', annoLink.onClose);
		},


		keyup : function( event ) {
			var key = $.ui.keyCode;

			switch( event.which ) {
				case key.ESCAPE:
					event.stopImmediatePropagation();
					if ( ! $(document).triggerHandler( 'wp_CloseOnEscape', [{ event: event, what: 'annoimages', cb: annoImages.close }] ) )
						annoImages.close();

					return false;
					break;
				default:
					return;
			}
			event.preventDefault();
		},
	}
	$(document).ready( annoImages.init );
	
})(jQuery);



