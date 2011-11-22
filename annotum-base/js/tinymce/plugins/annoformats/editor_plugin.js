(function(){ 	
    tinymce.create('tinymce.plugins.annoFormats', {
 
        init : function(ed, url){
			var t = this;
			t.editor = ed;
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
	
			// Add node change function which updates format dropdown
			ed.onInit.add(function() {
				ed.onNodeChange.add(t._nodeChanged, t);
			});	
		},      
		
		// Update format dropdown on change		
		_nodeChanged : function (ed, cm) {
			if (c = cm.get('annoformatselect')) {
				var parent = ed.dom.getParent(ed.selection.getNode(), 'HEADING, PARA, SEC'), selVal;
				if (parent) {
					selVal = parent.nodeName.toLowerCase();
				}
				else {
					selVal = 'format';
				}
				c.select(selVal);
			}
		},
		
		// @TODO Translation for formats
		createControl : function(n, cm) {
			var t = this, c, ed = t.editor;
			var bm = this.bookmark;
			if (n == 'annoformatselect') {
				
				function applyAnnoFormat(format) {
					var sel = ed.selection, dom = ed.dom, range = sel.getRng(), remove = false;
					// We don't care about the selection, just collapse
					sel.collapse(0);

					// Returns a new node that removes all the unsupported tags of the new format
					function getNewNode(originalNode, newNodeName) {
						var newNode = ed.dom.create(newNodeName, null, '<div>'+originalNode.innerHTML+'</div>');
						// We want to remove the div, which is required above
						dom.remove(newNode.childNodes[0], true);
						for (var i=0; i < newNode.childNodes.length; i++) {
							childNode = newNode.childNodes[i];
							if (childNode.nodeName == 'DIV') {
								dom.remove(childNode, true);
							}
							if (childNode.nodeType != 3 && !ed.schema.isValidChild(newNode.nodeName.toLowerCase(), childNode.nodeName.toLowerCase())) {
								//@TODO maybe keep formats, just strip them
								dom.remove(childNode);
							}
						}

						return newNode;
					};
					
					// Determines whether or not the immediate parent supports the new format type
					function canApplyFormat(node, newFormat) {
						if (!node) {
							return false;
						}
						
						return !!ed.schema.isValidChild(node.parentNode.nodeName.toLowerCase(), newFormat.toLowerCase());
					}

					// Find first parent
					var wrapper = ed.dom.getParent(sel.getNode(), 'HEADING, PARA, SEC');
					
					// Only continue if we can insert the new format into the parent node.
					if (!canApplyFormat(wrapper, format) && wrapper !== null) {
						return false;
					}
					
					if (wrapper !== null) {
						// Remove2
						if (format.toLowerCase() === wrapper.nodeName.toLowerCase()) {
							// Move to the end of the current wrapper, and get the bookmark
							// This prevents us from having a bookmark in the middle of an element that may be removed
							sel.select(wrapper);
							sel.collapse(0);
							var bookmark = sel.getBookmark();

							newNode = getNewNode(wrapper, wrapper.parentNode.nodeName);							
							wrapper.parentNode.replaceChild(newNode, wrapper);
							dom.remove(newNode, true);
						
							sel.moveToBookmark(bookmark);
							
							remove = true;
						}
						else {
							// convert
							newNode = getNewNode(wrapper, format);
							wrapper.parentNode.replaceChild(newNode, wrapper);
						}					
					}
					else {
						// Insert a new node if we don't have a valid wrapper
						var newNode = ed.dom.create(format);
						range.insertNode(newNode);
					}
					if (newNode && !remove) {
						range.selectNodeContents(newNode);
						range.collapse(0);
						sel.setRng(range);
					}
				};
				
				// Create the list box
			    var listbox = cm.createListBox('annoformatselect', {
			         title : 'Format',
			         onselect : function(v) {
						var resetIsIE = false;
						// Trick tinyMCE into thinking we're not in IE, and preform as exptected
						// Range Dom errors occur otherwise.
						if (tinymce.isIE) {
							tinymce.isIE = false;
							resetIsIE = true;
						}
						ed.undoManager.beforeChange();
						applyAnnoFormat(v);
						ed.undoManager.add();
						ed.focus();
						if (resetIsIE) {
							tinymce.isIE = true;
						}
			         }
			    });        

			    // Add some values to the list box
			    listbox.add('Heading', 'heading');
			    listbox.add('Paragraph', 'para');
			    listbox.add('Section', 'sec');

			    // Return the new listbox instance
			    return listbox;
				
			}
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