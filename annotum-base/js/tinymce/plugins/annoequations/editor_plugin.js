(function(){
    tinymce.create('tinymce.plugins.annoEquations', {

        init : function(ed, url) {
            ed.addCommand('Anno_Equations', function() {
				ed.windowManager.open({
					id : 'anno-popup-equations',
					width : 480,
					height : 'auto',
					wpDialog : true,
					title: ed.getLang('annoequations.title')
				}, {
					plugin_url : url // Plugin absolute URL
				});
            });

			ed.addButton('annoequations', {
				title: ed.getLang('annoequations.title'),
				cmd : 'Anno_Equations'
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
					xml = '<span class="inline-graphic" data-xmlel="inline-graphic" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="'+ url + '"></span><span> &nbsp;</span>';
				}
				else {
					caption = $('#equation-caption').val();
					label = $('#equation-label').val();

					description = $('#equation-description', form).val();
					description = description == '' ? '<br />' : description;

					xml = '<div class="fig" data-xmlel="fig">'
								+'<div class="label" data-xmlel="label">&#xA0;' + label + '</div>'
								+'<div class="caption" data-xmlel="caption">'
									+'<div class="p" data-xmlel="p">&#xA0;' + caption + '</div>'
								+'</div>'
								+'<div class="media" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="' + url + '" data-xmlel="media">'
									+'<span class="alt-text" data-xmlel="alt-text">' + alt_text + '</span>'
									+'<span class="long-desc" data-xmlel="long-desc">' + description + '</span>'
								+'</div>'
								+'<div _mce_bogus="1" class="clearfix"></div>'
							+'</div>';
				}
				xml = win.tinymce.activeEditor.plugins.textorum.applyFilters('after_loadFromText', xml);
				win.send_to_editor(xml);
			}

			win.tinymce.activeEditor.windowManager.close();
			return false;
		});
	});
})(jQuery);
