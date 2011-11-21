(function(){ 
    tinymce.create('tinymce.plugins.annoReferences', {
 
        init : function(ed, url){
			var disabled = true;	
	
            ed.addCommand('Anno_References', function(){	
               	ed.windowManager.open({
					id : 'anno-popup-references',
					width : 500,
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
			
			ed.onKeyUp.add(function(ed, e) {
				// When we delete, check to see if reference is empty
				if (e.keyCode == 8 || e.keyCode == 46) {
					var node = ed.selection.getNode(), parent = node.parentNode;
					if (node.nodeName == 'XREF' && node.innerHTML == '') {
						ed.dom.remove(node);
					}
				}
			});
    	},

        getInfo : function() {
            return {
                longname: 'Annotum DTD',
                author: 'Crowd Favorite',
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
		post_data['_ajax_nonce-delete-reference'] = $(this).siblings('#_ajax_nonce-delete-reference').val();
		$.post(ajaxurl, post_data, function(data) {
			if (data) {
				$('#reference-' + ref_id).fadeOut('400', function(){
					$(this).remove();
				});
				// @TODO Update all our reference keys
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
		var form = $(this);
		var ref_id = $('input[name="ref_id"]', this).val();
		var error_div = $('#lookup-error-' + ref_id);
		error_div.hide().html('');
		$.post(ajaxurl, $(this).serialize(), function(data) {
			if (data.message == 'success') {
				// Insert new reference.
				if ($('#reference-' + data.ref_id).length == 0) {
					$('#reference-edit-new').before(data.markup);
					form.slideUp();
					// Reset the new reference form
					form[0].reset();
				}
				// Otherwise, just slide up, replace old text with new, saved text
				else {
					form.slideUp();
					$('label[for="reference-checkbox-' + data.ref_id + '"]').html(data.ref_text);
				}
			}
			else {
				error_div.show().html(data.text);
			}
		}, 'json');
		return false;
	});
	
	$('input[name="import_pubmed"]').live('click', function(e) {
		e.preventDefault();
		var ref_id = $(this).attr('id').replace('pmid-import-', '');
		var id = $('#pmid-' + ref_id).val();
		var data = {action: 'anno-import-pubmed', pmid: id};
		var error_div = $('#lookup-error-' + ref_id);
		data['_ajax_nonce-import-pubmed'] = $('#_ajax_nonce-import-pubmed').val();
		var siblings = $(this).siblings('.ajax-loading');
		
		siblings.css('visibility', 'visible');
		error_div.hide().html('');
		$.post(ajaxurl, data, function(d) {
			siblings.css('visibility', 'hidden');
			if (d.message == 'success') {
				$('#text-' + ref_id).val(d.text);
			}
			else {
				error_div.html(d.text).show();
			}
		}, 'json');
	});
	
	$('input[name="import_doi"]').live('click', function(e) {
		e.preventDefault();
		var ref_id = $(this).attr('id').replace('doi-import-', '');
		var id = $('#doi-' + ref_id).val();
		var data = {action: 'anno-import-doi', doi: id};
		data['_ajax_nonce-import-doi'] = $('#_ajax_nonce-import-doi').val();
		var error_div = $('#lookup-error-' + ref_id);
		
		var siblings = $(this).siblings('.ajax-loading');
		siblings.css('visibility', 'visible');
		
		error_div.html('').hide();
		$.post(ajaxurl, data, function(d) {
			siblings.css('visibility', 'hidden');
			if (d.message == 'success') {
				$('#text-' + ref_id).val(d.text);
			}
			else {
				error_div.html(d.text).show();
			}
		}, 'json');
	});
	
});