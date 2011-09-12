(function(){ 
    tinymce.create('tinymce.plugins.annoTips', {
 
        init : function(ed, url){	
            ed.addCommand('Anno_Tips', function(){	
               	ed.windowManager.open({
					id : 'anno-popup-tips',
					width : 500,
					height : "auto",
					wpDialog : true,
					title : 'Editor Tips',
				}, {
					plugin_url : url // Plugin absolute URL
				});
            });
			ed.addButton('annotips', {
				title : 'Editor Tips',
				//ed.getLang('advanced.references_desc'),
				cmd : 'Anno_Tips'
			});
    	},

        getInfo : function(){
            return {
                longname: 'Annotum Tips',
                author: 'Crowd Favorite',
                authorurl: 'http://crowdfavorite.com/',
                infourl: 'http://annotum.wordpress.com/',
                version: "0.1"
			}; 
        }
    });

    tinymce.PluginManager.add('annoTips', tinymce.plugins.annoTips);
})();