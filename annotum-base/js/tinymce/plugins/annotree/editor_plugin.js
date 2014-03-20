(function(){
	tinymce.create('tinymce.plugins.annoTree', {
		init : function(ed, url){
			var t = this;
			t.ed = ed;
			// Which tags to put into the tree
			t.treeViewTags = ['body', 'sec', 'p', 'fig', 'table-wrap'];
			// Which tags should be processed. Non treeViewTags are used to generate titles
			t.processesableTags = ['body', 'sec', 'p', 'fig', 'table-wrap', 'title', 'label'];
			// Where to grab the title for each block element (p is special case)
			t.titleEls = {sec: 'title', 'table-wrap': 'label', fig: 'label'};

			ed.addCommand('Anno_Tree', function(){
				jQuery('.js-' + t.ed.id + '-tree-pop-up').toggle();
				if (t.ed.id == 'mce_fullscreen') {
					t.toggleFullscreen();
				}
				t.mapNodes();
			});

			ed.addButton('annotree', {
				title : 'Annotum Tree View',
				cmd : 'Anno_Tree'
			});


			// Initializing the fancytree for this editor specifically
			jQuery('#anno-tree-' + this.ed.id).fancytree({
				keyboard : false,
				minExpandLevel : 10,
				source: [''],
				click: function(event, data) {
					var nodeID = t.getNodeIDFromKey(data.node.key);
					var edID = t.getEdIDFromKey(data.node.key);
					var selectedEd = tinymce.get(edID);
					var node = selectedEd.selection.select(selectedEd.dom.select('#' + nodeID)[0]);
					var $metaBox = jQuery('#' + edID + '-meta-box');

					tinymce.execCommand('mceFocus', false, edID);
					if ($metaBox.length) {
						jQuery('body, html').scrollTop($metaBox.position().top);
					}
					jQuery('#' + edID + '_ifr').contents().scrollTop(jQuery(node).position().top);
				}
			});
			t.tree = jQuery('#anno-tree-' + this.ed.id).fancytree('getTree');

			// Regenerate the tree on node change if this editor has a tree view
			// This  fires on initial load as well as nodes change with content loading
			ed.onNodeChange.add(function(ed, object) {
				if (!(t.tree instanceof jQuery)) {
					t.mapNodes();
				}
			});

		},
		// Generats a tree and updates the Dom with the new tree
		mapNodes : function() {
			var root = this.ed.dom.getRoot();
			var t = this;
			if (this.isValidNode(root.firstChild)) {
				//var test = this.visitDFs(root);
				var json = {};
				this.generateTree(root, json);
				this.tree.reload([json]);
			}
		},
		// Checks if a node can be processed
		isValidNode : function(node) {
			var isValid = false;
			if (this.getValidClass(node) != false) {
				isValid = true;
			}
			return isValid;
		},
		// Gets a class from valid tags
		getValidClass : function(node) {
			return this._getValidClass(node, this.processesableTags);
		},
		// Checks if a node is in the list of nodes to displa in a tree
		isTreeViewNode : function(node) {
			return this._getValidClass(node, this.treeViewTags);
		},
		// Checks if a node has a processeable element class
		_getValidClass : function(node, classes) {
			var className = false
			for (var i in classes) {
				if (this.ed.dom.hasClass(node, classes[i])) {
					className = classes[i];
				}
			}
			return className;
		},
		getNodeIDFromKey : function(key) {
			return this._patternIDMatch(key, new RegExp(/node-id-([^ ]+)/));
		},
		getEdIDFromKey : function(key) {
			return this._patternIDMatch(key, new RegExp(/ed-id-([^ ]+)/));
		},
		_patternIDMatch : function (str, pattern) {
			var match = str.match(pattern);
			if (match !== null) {
				return match[1];
			}
			return '';
		},
		// Special things need to happen for the full screen editor
		toggleFullscreen : function () {
			var $wrapper = jQuery('.js-' + this.ed.id + '-tree-pop-up');
			if ($wrapper.is(":hidden")) {
				$wrapper.hide();
				jQuery('#mce_fullscreen_container, #mce_fullscreen_tbl').css({
					'padding-right' :  '0px',
					'min-width' : '600px',
					'width' : '100%'
				});
			}
			else {
				$wrapper.show();
				jQuery('#mce_fullscreen_container, #mce_fullscreen_tbl').css({
					'padding-right' :  '235px',
					'min-width' : '600px',
					'width' : '100%'
				});
			}
		},
		/*
		 * Walks through the staring at a node and generates a tree with JSON
		 *
		 * @return JSON tree representation
		 */
		generateTree : function(node, object) {
			//var curTreeObj = null;
			var childNodes = node.childNodes;
			var textorumType = this.getValidClass(node);
			// Generate title
			object.title = this.generateTitle(node);

			// Set the key so we can access the editor element
			object.key = 'node-id-' + node.id  + ' ed-id-' + this.ed.id;
			//curTreeObj = this.tree.getNodeByKey(object.key)
			object.expanded = true;

			switch (textorumType) {
				case 'p':
					object.extraClasses = 'icon para';
					break;
				case 'sec':
					object.extraClasses = 'icon folder';
					break;
				case 'fig':
					object.extraClasses = 'icon fig';
					break;
				case 'table-wrap':
					object.extraClasses = 'icon table';
					break;
				default:
					object.extraClasses = 'icon sec';
					break;
			}

			// Check if child nodes exist
			if (!!childNodes && childNodes.length) {
				// If child nodes exists create array of them
				object.children = [];
				for (var i = 0; i < childNodes.length; i++) {
					// Only care about children when this item is a section, body or p tag
					if (textorumType == 'sec' || textorumType == 'p' || node.nodeName == 'BODY') {
						if (this.isTreeViewNode(childNodes[i])) {
							object.children.push({});
							this.generateTree(childNodes[i], object.children[object.children.length -1]);
						}
					}
				}
			}
		},
		// Generate a title for a node
		generateTitle : function(node) {
			if (!node) {
				return false;
			}

			var title = '';
			var childNode;
			var annotumType = this.getValidClass(node);
			if (!!this.titleEls[annotumType]) {
				// If node is a section, grab the first title node then the first text node in that title node
				childNode = jQuery(node).children('.'+this.titleEls[annotumType]);
				if (this.getValidClass(childNode[0]) == this.titleEls[annotumType]) {
					title = childNode[0].innerText || childNode[0].textContent || '';
				}
			}
			// Special case for paragraphs
			else if (annotumType == 'p') {
				// Remove all block elements and get the text of the remainder
				title = jQuery(node).clone().children().remove('div').end().text();
			}
			else if (node.nodeName == 'BODY') {
				if (this.ed.id == 'content' || this.ed.id == 'mce_fullscreen') {
					title = 'Article';
				}
				else if (this.ed.id == 'excerpt') {
					title = 'Abstract';
				}
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
	});

	tinymce.PluginManager.add('annoTree', tinymce.plugins.annoTree);
})();
