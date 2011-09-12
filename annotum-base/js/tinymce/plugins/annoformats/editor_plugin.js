(function(){ 
    tinymce.create('tinymce.plugins.annoFormats', {
 
        init : function(ed, url){	
            ed.addCommand('Anno_Monospace', function() {
				tinymce.activeEditor.formatter.toggle('monospace');
            });

            ed.addCommand('Anno_Preformat', function() {
				tinymce.activeEditor.formatter.toggle('preformat');
            });


			ed.addButton('annopreformat', {
				title : 'Preformat',
				//ed.getLang('advanced.references_desc'),
				cmd : 'Anno_Preformat',
			});
			
			ed.addButton('annomonospace', {
				title : 'Monospace',
				//ed.getLang('advanced.references_desc'),
				cmd : 'Anno_Monospace',
			});
    	},

        getInfo : function() {
            return {
                longname: 'Annotum Formats',
                author: 'Crowd Favorite',
                authorurl: 'http://crowdfavorite.com/',
                infourl: 'http://annotum.wordpress.com/',
                version: "0.1"
			};
        }
    });

    tinymce.PluginManager.add('annoFormats', tinymce.plugins.annoFormats);
})();