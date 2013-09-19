(function($) {
	tinymce.create('tinymce.plugins.textorumInsertion', {
		init : function(ed, url) {
			var disabled = true,
				editor = ed,
				cm = new tinyMCE.ControlManager(ed),
				dropmenu = cm.createDropMenu('textorum-insertion-dropmenu'),
				dropmenuVisible = false,
				dropmenuJustClicked = false,
				ignoredElements = [
					'media',
					'xref',
					'inline-graphic',
					'inline-formula',
					'disp-formula',
					'table-wrap'
				];

			function insertElementBefore(element_name) {
				var node = editor.dom.create(
						editor.plugins.textorum.translateElement(element_name),
						{'class': element_name, 'data-xmlel': element_name},
						'&nbsp;'
					),
					currentNode = editor.selection.getNode();

				node = currentNode.parentNode.insertBefore(node, currentNode);
				editor.selection.select(node);
				editor.selection.collapse();
			}

			function insertElementInside(element_name) {
				var node = editor.dom.create(
						editor.plugins.textorum.translateElement(element_name),
						{'class': element_name, 'data-xmlel': element_name},
						'&nbsp;'
					);

				node = editor.selection.getNode().appendChild(node);
				editor.selection.select(node);
				editor.selection.collapse();
			}

			function insertElementAfter(element_name) {
				var node = editor.dom.create(
						editor.plugins.textorum.translateElement(element_name),
						{'class': element_name, 'data-xmlel': element_name},
						'&nbsp;'
					),
					currentNode = editor.selection.getNode();

				if (currentNode.nextSibling) {
					node = currentNode.parentNode.insertBefore(node, parentGuest.nextSibling);
				}
				else {
					node = currentNode.parentNode.appendChild(node);
				}
				editor.selection.select(node);
				editor.selection.collapse();
			}

			editor.addCommand('Textorum_Insertion_Menu', function(ui, where) {
				var options, $button;

				where = where || 'inside',
				$button = $('#'+editor.editorId).next('.mceEditor').find('.mceButton.mce_textorum-insertion-' + where);

				if (dropmenuVisible) {
					dropmenu.hideMenu();
					dropmenuVisible = false;
				}
				else {
					options = editor.plugins.textorum.validator.validElementsForNode(editor.currentNode, where, "array");
					dropmenu.removeAll();
					if (options.length) {
						options = options.filter(function(a) { return ignoredElements.indexOf(a) === -1; });
						for (var i = 0, len = options.length; i < len; i++) {
							(function(element_name) {
								dropmenu.add(new tinymce.ui.MenuItem('textorum-insertion-item-' + element_name ,{
									title: element_name,
									onclick: function() {
										dropmenuVisible = false;
										switch(where) {
											case 'before':
												insertElementBefore(element_name);
												break;

											case 'inside':
												insertElementInside(element_name);
												break;

											case 'after':
												insertElementAfter(element_name);
												break;

											default:
												insertElementInside(element_name);
										}
									}
								}));
							})(options[i]);
						}
					}
					else {
						dropmenu.add(new tinymce.ui.MenuItem('textorum-insertion-item-none', {
							title: 'No elements available',
							style: 'color:#999',
							onclick: function() {
								dropmenuVisible = false;
								return false;
							}
						}));
					}

					dropmenu.showMenu($button.offset().left, $button.offset().top + 24);
					dropmenuJustClicked = dropmenuVisible = true;
					setTimeout(function() { dropmenuJustClicked = false; }, 250);

				}
			});

			editor.onNodeChange.add(function(ed, cm, e) {
				if (dropmenuVisible && !dropmenuJustClicked) {
					dropmenu.hideMenu();
					dropmenuVisible = false;
				}
			});

			// Register example button
			editor.addButton('textorum-insertion-before', {
				title : 'Insert Element Before',
				cmd : 'Textorum_Insertion_Menu',
				value: 'before'
			});

			editor.addButton('textorum-insertion-inside', {
				title : 'Insert Element Inside',
				cmd : 'Textorum_Insertion_Menu',
				value: 'inside'
			});

			editor.addButton('textorum-insertion-after', {
				title : 'Insert Element After',
				cmd : 'Textorum_Insertion_Menu',
				value: 'after'
			});

			editor.addShortcut('ctrl+enter', 'Insert Element', 'Textorum_Insertion');
		},
		getInfo : function() {
			return {
				longname : 'Textorum Context Aware Element Insertion',
				author : 'Crowd Favorite',
				authorurl : 'http://crowdfavorite.com',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('textorumInsertion', tinymce.plugins.textorumInsertion);
})(jQuery);
