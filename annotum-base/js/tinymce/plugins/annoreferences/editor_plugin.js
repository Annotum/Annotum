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
					title : 'Insert/Update', 
					//ed.getLang('advlink.link_desc')
				}, {
					plugin_url : url // Plugin absolute URL
				});
            });
			ed.addButton('annoreferences', {
				title :'annoReferences',
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
	$('.reference-actions .delete').click(function() {
		return false;
	});
	
	$('.reference-actions .edit').click(function() {
		var ref_id = $(this).attr('id').replace('reference-action-edit-', '');
		$('#anno-reference-edit-' + ref_id).slideToggle();
		return false;
	});
	
	$('.reference-edit-actions .cancel').click(function() {
		var ref_id = $(this).attr('id').replace('reference-action-cancel-', '');
		$('#anno-reference-edit-' + ref_id).slideToggle();
		return false;
	});	
	
	$('.reference-edit-actions .save').click(function() {
		return false;
	});	
});