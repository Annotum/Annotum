(function(){
	tinymce.create('tinymce.plugins.annoTree', {
		init : function(ed, url){
			var t = this;
			t.ed = ed;
			t.treeViewTags = ['body', 'sec', 'p', 'fig', 'table-wrap'];
			t.processesableTags = ['body', 'sec', 'p', 'fig', 'table-wrap', 'title', 'label'];
			t.titleEls = {sec: 'title', 'table-wrap': 'label', fig: 'label'};
			t.tree = [];

			ed.addCommand('Anno_Tree', function(){
				t.mapNode();
			});
			ed.addButton('annotree', {
				title : 'Annotum Tree View',
				cmd : 'Anno_Tree'
			});
		},

		getInfo : function() {
			return {
				longname: 'Annotum Treeview',
				author: 'Crowd Favorite',
				authorurl: 'http://crowdfavorite.com/',
				infourl: 'http://annotum.wordpress.com/',
				version: "0.1"
			};
		},
		mapNode : function() {
			var root = this.ed.dom.getRoot();
			var t = this;
			if (this.isValidNode(root.firstChild)) {
				//var test = this.visitDFs(root);
				var json = {};
				this.generateTree(root, json);
				jQuery	("#tree").fancytree({
					keyboard : false,
					source: [json],
					activate: function(event, data) {
						// Select the node which matches the key
						// Key is set as the id of the node.
						var node = t.ed.selection.select(t.ed.dom.select('#' + data.node.key)[0])
						tinymce.execCommand('mceFocus', false, 'content');
						console.log(node.offsetTop);
						console.log(tinymce.DOM.getViewPort(tinymce.activeEditor.getWin()).y);
						tinymce.DOM.getViewPort(tinymce.activeEditor.getWin()).y = node.offsetTop;
						//node.scrollIntoView();
					}
				});
			}
		},
		isValidNode : function(node) {
			var isValid = false;
			if (this.getValidClass(node) != false) {
				isValid = true;
			}
			return isValid;
		},
		getValidClass : function(node) {
			return this._getValidClass(node, this.processesableTags);
		},
		isTreeViewNode : function(node) {
			return this._getValidClass(node, this.treeViewTags);
		},
		_getValidClass : function(node, classes) {
			var className = false
			for (var i in classes) {
				if (this.ed.dom.hasClass(node, classes[i])) {
					className = classes[i];
				}
			}
			return className;
		},
		generateTree : function(node, object) {
			var childNodes = node.childNodes;
			var textorumType = this.getValidClass(node);
			// Generate title
			object.title = this.generateTitle(node);

			// Set the key so we can access the editor element
			object.key = node.id;

			// Check if child nodes exist
			if (!!childNodes && childNodes.length) {
				// If child nodes exists create array of them
				object.children = [];
				for (var i = 0; i < childNodes.length; i++) {
					// Only care about children when this item is a section or body
					if (textorumType == 'sec' || textorumType == 'p' || node.nodeName == 'BODY') {
						if (this.isTreeViewNode(childNodes[i])) {
							object.children.push({});
							this.generateTree(childNodes[i], object.children[object.children.length -1]);
						}
					}
				}
			}
		},
		generateTitle : function(node) {
			if (!node) {
				return false;
			}

			var title = '';
			var childNode;
			var annotumType = this.getValidClass(node);
			if (!!this.titleEls[annotumType]) {
				// If node is a section, grab the first title node then the first text node in that title node, truncate by x characters

				childNode = jQuery(node).children('.'+this.titleEls[annotumType]);
				if (this.getValidClass(childNode[0]) == this.titleEls[annotumType]) {
					title = childNode[0].innerText || childNode[0].textContent || '';
				}
			}
			// Special case for paragraphs
			else if (annotumType == 'p') {
				// Remove all block level elements
				title = jQuery(node).clone().children().remove('div').end().text();
			}

			if (annotumType && !title.trim()) {
				title = '&#60;' + annotumType + '&#62;';
			}
			else if (!title.trim()) {
				title = '&#60;' + node.nodeName + '&#62;';
			}

			if (title.length > 25) {
				title = title.substring(0, 22).trim() + '&#8230;';
			}

			return title;
		}
	});

	tinymce.PluginManager.add('annoTree', tinymce.plugins.annoTree);
})();
