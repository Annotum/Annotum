(function(){ 
    tinymce.create('tinymce.plugins.annoEquations', {
 
        init : function(ed, url){	
            ed.addCommand('Anno_Equations', function() {
				ed.windowManager.open({
					id : 'anno-popup-equations',
					width : 480,
					height : "auto",
					wpDialog : true,
					title : "Insert Equation"
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
                version: "0.1"
			};
        }
    });

    tinymce.PluginManager.add('annoEquations', tinymce.plugins.annoEquations);
})();