var annoEquationEdit;

(function($){
	var inputs = {}, ed;

	annoEquationEdit = {	
		keySensitivity: 100,
		textarea: function() { return edCanvas; },

		init : function() {
			inputs.dialog = $('#anno-popup-quote');
			inputs.submit = $('#anno-quote-submit');
		
			// Bind event handlers
			inputs.dialog.keydown( annoQuote.keydown );
			inputs.dialog.keyup( annoQuote.keyup );
			inputs.submit.click( function(e){
				annoQuote.update();
				e.preventDefault();
			});
			
			inputs.dialog.bind('wpdialogrefresh', annoEquationEdit.refresh);
			//inputs.dialog.bind('wpdialogclose', annoLink.onClose);

			$('#anno-quote-cancel').click(annoQuote.close);
		},

		refresh : function() {
			annoLink.mceRefresh();

			// Focus the textarea and select its contents
			// inputs.url.focus()[0].select();
		},

		beforeOpen : function() {
			annoEquationEdit.range = null;

			if ( ! annoEquationEdit.isMCE() && document.selection ) {
				annoEquationEdit.textarea().focus();
				annoEquationEdit.range = document.selection.createRange();
			}
		}
	};
	$(document).ready(annoEquationEdit.init);
})(jQuery);

