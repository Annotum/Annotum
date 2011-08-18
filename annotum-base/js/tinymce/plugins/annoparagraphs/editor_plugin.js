(function() {
	tinymce.create('tinymce.plugins.annoParagraphs', {		
		init : function(ed, url) {
			var t = this;
			t.editor = ed;
			t.dom = ed.dom;

			ed.onKeyDown.add(function(ed, e) {

				if (e.keyCode == 13 && e.ctrlKey) {
					t.insertPara(e);
				}
			});
		},

		insertPara : function(e) {
			var t = this, ed = t.editor;
			ed.dom.add(ed.dom.getRoot(), 'p', null, '<br mce_bogus="1" />');
			return false;
		},

		getInfo : function() {
			return {
				longname : 'Annotum Paragraphs',
				author : 'Crowd Favorite',
				authorurl : 'http://crowdfavorite.org',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('annoParagraphs', tinymce.plugins.annoParagraphs);
})();