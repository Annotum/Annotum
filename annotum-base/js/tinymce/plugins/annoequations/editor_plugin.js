(function(){ 
    tinymce.create('tinymce.plugins.annoEquations', {
 
        init : function(ed, url) {
            ed.addCommand('Anno_Equations', function() {
				ed.windowManager.open({
					id : 'anno-popup-equations',
					width : 480,
					height : 'auto',
					wpDialog : true,
					title : 'Insert Equation'
				}, {
					plugin_url : url // Plugin absolute URL
				});
            });

			ed.addButton('annoequations', {
				title : 'Equations',
				//ed.getLang('advanced.references_desc'),
				cmd : 'Anno_Equations',
			});
    	},

        getInfo : function() {
            return {
                longname: 'Annotum Equations',
                author: 'Crowd Favorite',
                authorurl: 'http://crowdfavorite.com/',
                infourl: 'http://annotum.wordpress.com/',
                version: '0.1'
			};
        },
    });

    tinymce.PluginManager.add('annoEquations', tinymce.plugins.annoEquations);
})();

(function($){	
	$(document).ready(function() {
		// Reset the form every time the dialog is closed
		$('#anno-popup-equations').bind('wpdialogclose', function() {
			$('form#anno-tinymce-equations-form')[0].reset();
			
			// Reset the preview pane.
			$('.ee-preview-container').html('');			
		});
		
		$('#anno-equations-insert').live('click', function() {
			var caption, label, url, xml;
			var form = 'form#anno-tinymce-equations-form';
			var win = window.dialogArguments || opener || parent || top;

			alt_text = $('#equation-alttext', form).val();
			url = $('.ee-preview-container img', form).attr('src');
			display_type = $('input[name="display"]:checked', form).val();

			// We only want to insert if we have a valid URL
			if (url) {
				if (display_type == 'inline') {
					// Inserting for tinyMCE. is converted to XML on save.
					xml = '<img src="'+ url + '" class="_inline_graphic" alt="'+ alt_text + '"/>';
				}
				else {
					// @TODO Revisit <br /> insertion for IE8 compatability 
					caption = $('#equation-caption').val();
					//caption = caption == '' ? '<br />' : caption;

					label = $('#equation-label').val();
					//label = label == '' ? '<br />' : label;

					description = $('#equation-description', form).val();
					description = description == '' ? '<br />' : description;

					xml = '<fig>'
								+'<img src="' + url + '" />'
								+'<label>' + label + '</label>'
								+'<cap><para>' + caption + '</para></cap>'
								+'<media xlink:href="' + url + '">'
									+'<alt-text>' + alt_text + '</alt-text>'
									+'<long-desc>' + description + '</long-desc>'
								+'</media>'
							+'</fig>'
							+'<div _mce_bogus="1" class="clearfix"></div>';
				}	
				tinyMCEPopup.restoreSelection();
				win.send_to_editor(xml);
			}

			win.tinyMCEPopup.close();		
			return false;
		});
	});
})(jQuery);