(function(){ 
    tinymce.create('tinymce.plugins.annoTable', {
 
        init : function(ed, url){
			var disabled = true;	
	
            ed.addCommand('annoTable', function(){	
               	ed.windowManager.open({
					id : 'anno-tinymce-table',
					width : 480,
					height : "auto",
					wpDialog : true,
					title : 'Insert/Update', //ed.getLang('advlink.link_desc')
				}, {
					plugin_url : url // Plugin absolute URL
				});
 			//	ilc_sel_content = tinyMCE.activeEditor.selection.getContent();
              //  tinyMCE.activeEditor.selection.setContent('[php]' + ilc_sel_content + '[/php]');
            });
			ed.addButton('annotable', {
				title :'annoTable',// ed.getLang('advanced.link_desc'),
				cmd : 'annoTable'
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

    tinymce.PluginManager.add('annoTable', tinymce.plugins.annoTable);
})();