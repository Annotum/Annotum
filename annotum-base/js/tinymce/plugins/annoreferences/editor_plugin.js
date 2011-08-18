(function(){ 
    tinymce.create('tinymce.plugins.annoReferences', {
 
        init : function(ed, url){
			var disabled = true;	
	
            ed.addCommand('Anno_References', function(){	
               	ed.windowManager.open({
					id : 'anno-popup-references',
					width : 480,
					height : "auto",
					wpDialog : true,
					title : 'Insert References',
				}, {
					plugin_url : url // Plugin absolute URL
				});
            });
			ed.addButton('annoreferences', {
				title :'Insert Reference',
				//ed.getLang('advanced.references_desc'),
				cmd : 'Anno_References'
			});
    	},

        getInfo : function(){
            return {
                longname: 'Annotum DTD',
                author: '@crowdfavorite',
                authorurl: 'http://crowdfavorite.com/',
                infourl: 'http://annotum.wordpress.com/',
                version: "0.1"
			}; 
        }
    });

    tinymce.PluginManager.add('annoReferences', tinymce.plugins.annoReferences);
})();

jQuery(document).ready( function($) {
		
	$('.reference-actions .delete').live('click', function() {
		var ref_id = $(this).attr('id').replace('reference-action-delete-', '');
		var post_data = {ref_id : ref_id, post_id : ANNO_POST_ID, action : 'anno-reference-delete'};
		$.post(ajaxurl, post_data, function(data) {
			if (data) {
				$('#reference-' + ref_id).fadeOut('400', function(){
					$(this).remove();
				});
				// Update all our reference keys
			}
		}, 'json');
		return false;
	});
	
	$('.reference-actions .edit').live('click', function() {
		var ref_id = $(this).attr('id').replace('reference-action-edit-', '');
		$('#reference-form-' + ref_id).slideToggle();
		return false;
	});
	
	$('.reference-edit-actions .cancel').live('click', function() {
		var ref_id = $(this).attr('id').replace('reference-action-cancel-', '');
		$('#reference-form-' + ref_id).slideToggle();
		return false;
	});	
	
	$('.reference-edit-actions .save').live('click', function() {
		var ref_id = $(this).attr('id').replace('reference-action-save-', '');
		var form = $('#reference-form-' + ref_id);
		form.submit();
		return false;
	});
	
	$('#anno-references-new').click(function() {
		$('#reference-form-new').slideDown();
		return false;
	});
	
	$('#anno-popup-references form').submit(function() {
		var form = $(this)
		$.post(ajaxurl, $(this).serialize(), function(data) {
		//	var message_container = $('#popup-message-' + data.type + '-' + data.ref_id);
			if (data.code == 'success') {
				if ($('#reference-' + data.ref_id).length == 0) {
					$('#reference-edit-new').before(data.ref_markup);
					form.slideUp();
					form[0].reset();
				}
				else {
					$('#reference-' + data.ref_id + ' .reference-text').html(data.text);
					form.slideUp();
				}
			}
			else {
				$(message_container).html('').removeClass('success').addclass('error').html(data.message);
			}			
		}, 'json');
		return false;
	});
	
});