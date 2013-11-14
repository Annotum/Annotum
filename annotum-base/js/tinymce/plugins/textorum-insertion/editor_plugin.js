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
					'disp-quote',
					'inline-graphic',
					'inline-formula',
					'disp-formula',
					'table-wrap',
					'bold',
					'italic',
					'monospace',
					'underline',
					'sup',
					'sub'
				];

			editor.addCommand('Textorum_Insertion_Menu', function(ui, where) {
				var options, $button;

				where = where || 'inside';
				$button = $('#'+editor.editorId).next('.mceEditor').find('.mceButton.mce_textorum-insertion-' + where);

				if (dropmenuVisible) {
					dropmenu.hideMenu();
					dropmenuVisible = false;
				}
				else {
					options = editor.plugins.textorum.validator.validElementsForNode(editor.selection.getNode(), where, "array");

					// Textorum doesn't like putting things at the top level body, so account for top level section availability
					if (!options.length && editor.selection.getNode().className.toUpperCase() == 'SEC' && where !== 'inside') {
						options.push('sec');
					}

					dropmenu.removeAll();
					if (options.length) {
						options = options.filter(function(a) { return ignoredElements.indexOf(a) === -1; });
						for (var i = 0, len = options.length; i < len; i++) {
							(function(element_name) {
								dropmenu.add(new tinymce.ui.MenuItem('textorum-insertion-item-' + element_name ,{
									title: element_name,
									onclick: function() {
										var newNode = editor.dom.create(
												editor.plugins.textorum.translateElement(element_name),
												{'class': element_name, 'data-xmlel': element_name},
												'&#xA0;'
											),
											currentNode = editor.selection.getNode(),
											range, elYPos;

										dropmenuVisible = false;
										switch(where) {
											case 'before':
												newNode = currentNode.parentNode.insertBefore(newNode, currentNode);
												break;

											case 'inside':
												newNode = editor.selection.getNode().appendChild(newNode);
												break;

											case 'after':
												if (currentNode.nextSibling) {
													newNode = currentNode.parentNode.insertBefore(newNode, currentNode.nextSibling);
												}
												else {
													newNode = currentNode.parentNode.appendChild(newNode);
												}
												break;
										}

										if (document.createRange) {     // all browsers, except IE before version 9
											range = document.createRange();
											range.selectNodeContents(newNode);
										}
										else { // IE < 9
											range = document.selection.createRange();
											range.moveToElementText(newNode);
										}

										range.collapse(1);
										editor.selection.setRng(range);

										elYPos = editor.dom.getPos(newNode).y;
										// Scroll to new section
										if (elYPos > editor.dom.getViewPort(ed.getWin()).h) {
												editor.getWin().scrollTo(0, elYPos);
										}

										editor.nodeChanged();
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
				var options = editor.plugins.textorum.validator.validElementsForNode(editor.selection.getNode(), "inside", "array");

				if (dropmenuVisible && !dropmenuJustClicked) {
					dropmenu.hideMenu();
					dropmenuVisible = false;
				}

				if (options.indexOf('inline-graphic') === -1 || options.indexOf('fig') === -1) {
					cm.get('annoimages').setDisabled(true);
				}
				else {
					cm.get('annoimages').setDisabled(false);
				}

				if (options.indexOf('disp-quote') === -1) {
					cm.get('annoquote').setDisabled(true);
				}
				else {
					cm.get('annoquote').setDisabled(false);
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
