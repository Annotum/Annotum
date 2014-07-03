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
					title : ed.getLang('annoreferences.title')
				}, {
					plugin_url : url // Plugin absolute URL
				});
            });
			ed.addButton('annoreferences', {
				title : ed.getLang('annoreferences.title'),
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

			// No references in abstract
			if (ed.id == 'content') {
				jQuery(document).on('referenceRemoved', function(e, refID) {
					var nodes, rid;
					refID = parseInt(refID);
					if (refID != NaN) {
						// inserted content is offset by 1
						refID = refID + 1;
						nodes = ed.dom.select('.xref');
						if (nodes) {
							tinymce.each(nodes, function(node, index) {
								rid = parseInt(node.getAttribute('rid').replace('ref', ''));
								if (rid == refID) {
									ed.dom.remove(node);
								}
								else if (rid > refID) {
									node.setAttribute('rid', 'ref' + (rid - 1));
									node.textContent = rid - 1;
									node.innerText = rid - 1;
								}
							});
						}
					}
				});
			}
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

jQuery(document).ready(function($) {

	function update_reference_numbers(deletedID) {
		var refID = 0;
		var $row;

		$.each($('.js-reference-row:not(#reference-new)'), function(index, value) {
			$row = $(value);
			// @TODO cleanup HTML so this only has to update in a single place.
			$(this).attr('id', 'reference-' + refID);
			$('.js-reference-number', $row).text((refID + 1) + '.');
			$('.js-reference-checkbox', $row).attr('id', 'reference-checkbox-' + refID);
			$('.js-reference-checkbox-label', $row).attr('for', 'reference-checkbox-' + refID);
			$('.js-ref-id', $row).val(refID);
			$('.edit-reference, .delete-reference, .js-pmid-import, .save-reference, .cancel-reference', $row).data('id', refID);
			$('.js-reference-edit', $row).attr('id', 'reference-edit-' + refID);
			$('.js-reference-form', $row).attr('id', 'reference-form-' + refID);
			$('.js-lookup-error', $row).attr('id', 'lookup-error-' + refID);
			$('.js-doi', $row).attr('id', 'doi-' + refID);
			$('.js-doi-import', $row).attr('id', 'doi-import-' + refID);
			$('.js-pmid', $row).attr('id', 'pmid-' + refID);
			$('.js-pmid-text', $row).attr('id', 'text-' + refID);

			refID = refID + 1;
		});

		 $(document).trigger('referenceRemoved', [deletedID]);
	}

	$(document).on('click', '.reference-actions .delete-reference', function() {
		var ref_id = $(this).data('id');
		var post_data = {ref_id : ref_id, post_id : ANNO_POST_ID, action : 'anno-reference-delete'};
		post_data['_ajax_nonce-delete-reference'] = $(this).siblings('#_ajax_nonce-delete-reference').val();
		$.post(ajaxurl, post_data, function(data) {
			if (data.result == 'success') {
				$('#reference-' + ref_id).fadeOut('400', function(){
					$(this).remove();
					update_reference_numbers(ref_id);
				});

			}
		}, 'json');
		return false;
	});

	$(document).on('click', '.reference-actions .edit-reference', function() {
		var ref_id = $(this).data('id');
		$('#reference-form-' + ref_id).slideToggle();
		return false;
	});

	$(document).on('click', '.reference-edit-actions .cancel-reference', function() {
		var ref_id = $(this).data('id');
		$('#reference-form-' + ref_id).slideToggle();
		return false;
	});

	$(document).on('click', '.reference-edit-actions .save-reference', function(e) {
		e.preventDefault();
		var ref_id = $(this).data('id');
		var form = $('#reference-form-' + ref_id);
		form.submit();
		return false;
	});

	$('#anno-references-new').click(function() {
		$('#reference-form-new').slideDown();
		return false;
	});

	$(document).on('submit', 'form.js-reference-form', function() {
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
		var ref_id = $(this).data('id');
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
