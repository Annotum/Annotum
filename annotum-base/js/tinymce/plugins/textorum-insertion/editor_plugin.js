
(function($) {
	tinymce.create('tinymce.plugins.textorumInsertion', {
		init : function(ed, url) {
			var self = this,
				disabled = true,
				editor = ed,
				//cm = new tinymce.ControlManager(ed),
				//dropmenu = cm.createDropMenu('textorum-insertion-dropmenu'), // Not used in current editor
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

			// Addresses Chrome 33 issue where fonts didnt displace unless repainted.
			jQuery.each(jQuery('.mceIcon'), function(index, el) {
				var $el = jQuery(el);
				var opacity = $el.css('opacity');
				$el.animate({opacity: opacity}, 1);
			})

			// Key Bindings
			//
			ed.onKeyDown.addToTop(function(ed, e) {
				var target, listItemParent, siblingNode, range, bogusNode, dispParent, pParent;
				var content, rangeClone, inTitle, parentNode, node = ed.selection.getNode();

				// Backspace
				// Disable backspace when the cursor is at the first character in a title such that it doesn't
				// remove the title element when its pressed.
				// Titles are required in every section
				if (8 == e.keyCode) {
					// Check if in a title node
					range = ed.selection.getRng(1);
					parentNode = ed.dom.getParent(node, 'div.title');
					if (parentNode || (3 != range.commonAncestorContainer.nodeType && 'title' == range.endContainer.getAttribute('class'))) {
						bogusNode = ed.dom.create(
							'span',
							{'_mce_bogus': '1'},
							''
						);

						range.insertNode(bogusNode);

						rangeClone = range.cloneRange();
						rangeClone.setStartBefore(node);
						rangeClone.setEndBefore(bogusNode);

						ed.selection.setRng(rangeClone);

						var content = ed.selection.getContent({format: 'text'});
						if ( '' == content ) {
							tinymce.dom.Event.cancel(e);
							ed.dom.remove(bogusNode);
							ed.dom.select(range.endContainer);
							ed.selection.collapse(0);
							return false;
						}
						else {
							ed.dom.remove(bogusNode);
							ed.selection.setRng(range);
						}
					}

					return true;
				}

				// Delete
				// Check if this is the last paragraph and last character in a section
				// If so, delete does nothing
				// Titles are required in every section
				if (46 == e.keyCode) {
					var parents = ed.dom.getParents(node, '.p');
					if (!!parents && parents.length > 0) {
						var topP = parents[parents.length - 1];

						if (!topP.nextSibling || topP.nextSibling.getAttribute('class') == 'sec') {
							bogusNode = ed.dom.create(
								'span',
								{'_mce_bogus': '1'},
								''
							);
							range = ed.selection.getRng(1);
							range.insertNode(bogusNode);

							rangeClone = range.cloneRange();
							rangeClone.setEndBefore(bogusNode);
							ed.selection.setRng(rangeClone);

							var content = ed.selection.getContent({format: 'text'});
							if ('' == content) {
								tinymce.dom.Event.cancel(e);
								ed.dom.remove(bogusNode);
								return false;
							}
							else {
								ed.dom.remove(bogusNode);
								ed.selection.setRng(range);
							}
						}
					}

					return true;
				}

				// Enter
				if (!e.shiftKey && e.keyCode == 13) {

					listItemParent = ed.dom.getParent(node, '.list-item');

					// What to do if empty editor
					if (ed.getContent() == '') {

						// Abstract gets a <P> tag
						if (ed.id == 'excerpt') {
							tinymce.dom.Event.cancel(e);
							return ed.setContent('<div class="p" data-xmlel="p">&nbsp;</div>');
						}
						// Presumably article content.
						else {
							tinymce.dom.Event.cancel(e);
							return ed.setContent('<div class="sec" data-xmlel="sec"><div class="title" data-xmlel="title"><br id=""></div><div class="p" data-xmlel="p">&nbsp;</div></div>');
						}
					}

					// Ctrl + Enter
					if (e.ctrlKey) {
						tinymce.dom.Event.cancel(e);
						return ed.plugins.annoFormats.insertSection();
					}

					// List items
					if (listItemParent) {
						tinymce.dom.Event.cancel(e);
						// insert bookmark/bogus
						range = ed.selection.getRng();                  // get range
						bogusNode = ed.dom.create(
							'span',
							{'_mce_bogus': '1'},
							''
						);
						range.insertNode(bogusNode);
						self.split(ed, listItemParent, bogusNode);
						siblingNode = bogusNode.nextSibling;
						ed.dom.remove(bogusNode);

						// Move to the appropriate place (beginning of the split node)
						ed.selection.select(siblingNode, true);
  						ed.selection.collapse(true);

						return false;
					}

					// Get the parent tag and class
					parentTag = ed.dom.getParent(node.parentNode);
					elementClass = jQuery(node).attr('class');
					parentClass = jQuery(parentTag).attr('class');

					// If it's a 'p' tag, we need its parent
					if (parentClass == 'p') {
						parentTag = ed.dom.getParent(parentTag.parentNode);
						parentClass = jQuery(ed.dom.getParent(parentTag)).attr('class');
					}

					// Only allow sections to be extended
					if (
						parentClass == 'caption' ||
						parentClass == 'fig' ||
						parentClass == 'tr'
					) {
						console.log(parentClass + ' cannot be extended');
						tinymce.dom.Event.cancel(e);
					}
					// If it's a title, then add a P tag instead of splitting
					else if (
						elementClass == 'title'
					) {
						tinymce.dom.Event.cancel(e);
						return !self.insertElement('p', 'after', node);
					}

					// Hitting enter in display-quote while not in a p tag should insert a bogus br after it
					dispParent = ed.dom.getParent(node, '.disp-quote');
					if (dispParent) {
						pParent = ed.dom.getParent(node, '.p', dispParent);
						if (!pParent) {
							bogusNode = ed.dom.create(
								'br',
								{'_mce_bogus': '1'},
								''
							);
							// Jump to the bogus element
							ed.dom.insertAfter(bogusNode, dispParent);
							ed.selection.select(bogusNode);
							ed.selection.collapse(0);
							tinymce.dom.Event.cancel(e);
							return false;
						}
					}
				}
				return true;
			});

			editor.addCommand('Textorum_Insertion_Menu', function(ui, where) {
				var options, $button;

				$button = $('#'+editor.editorId).next('.mceEditor').find('.mceButton.mce_textorum-insertion-' + where);

				if (dropmenuVisible) {
					dropmenu.hideMenu();
					dropmenuVisible = false;
				}
				else {

					where = where || 'inside';
					options = self.getValidElements(editor.selection.getNode(), where);

					dropmenu.removeAll();

					if (options.length) {
						options = options.filter(function(a) { return ignoredElements.indexOf(a) === -1; });
						tinymce.each(options, function(element_name) {
							switch (element_name) {
								case 'list':
									dropmenu.add({ title: 'list (bulleted)', onclick: function() {
										self.insertElement(element_name, where, null, {
											'list-type': 'bullet'
										});
									}});

									dropmenu.add({ title: 'list (ordered)', onclick: function() {
										self.insertElement(element_name, where, null, {
											'list-type': 'order'
										});
									}});

									break;

								default:
									dropmenu.add(new tinymce.ui.MenuItem('textorum-insertion-item-' + element_name ,{
										title: element_name,
										onclick: function() {
											if (element_name == 'list') {
												self.insertElement(element_name, where, null, {
													'list-type': 'bullet'
												});
											}
											else {
												self.insertElement(element_name, where);
											}
										}
									}));
									break;
							}
						});
					}
					else {
						dropmenu.add(new tinymce.ui.MenuItem('textorum-insertion-item-none', {
							title: ed.getLang('annotextorum.noElement'),
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

			editor.onNodeChange.add(function(ed, cm, node) {
				var options, inlineNames, buttonElementMap, firstBlock;
				if (node.nodeName == 'BR') {
					node = node.parentNode;
				}

				options = editor.plugins.textorum.validator.validElementsForNode(node, "inside", "array");
				inlineNames = ['SPAN', 'TT', 'EM', 'U', 'STRONG', 'SUP', 'SUB'];
				buttonElementMap = {
					list : [
						'annoorderedlist',
						'annobulletlist'
					],
					xref : [
						'annoreferences'
					],
					'disp-quote' : [
						'annoquote'
					],
					monospace : [
						'annomonospace'
					],
					preformat : [
						'annopreformat'
					],
					'table-wrap' : [
						'table'
					],
					sup : [
						'superscript'
					],
					sub : [
						'subscript'
					]
				};
				firstBlock = node;

				while (inlineNames.indexOf(firstBlock.nodeName) !== -1) {
					firstBlock = firstBlock.parentNode;
				}

				jQuery.each(buttonElementMap, function(elementName, buttons) {
					var tf = false;
					if (options.indexOf(elementName) === -1) {
						tf = true;
					}

					jQuery.each(buttons, function(index, buttonName) {
						buttonDisable(buttonName, tf);
					});
				});

				if (dropmenuVisible && !dropmenuJustClicked) {
					dropmenu.hideMenu();
					dropmenuVisible = false;
				}

				// enable indent when parent list
				if (ed.dom.getParent(node, '.list')) {
					buttonDisable('annooutdentlist', false);
					buttonDisable('annoindentlist', false);
					buttonDisable('annoorderedlist', false);
					buttonDisable('annobulletlist', false);

				}
				else {
					buttonDisable('annooutdentlist', true);
					buttonDisable('annoindentlist', true);
					buttonDisable('annoorderedlist', true);
					buttonDisable('annobulletlist', true);
				}

				if (firstBlock.getAttribute('data-xmlel') == 'p') {
					buttonDisable('annoorderedlist', false);
					buttonDisable('annobulletlist', false);
				}
				else {
					buttonDisable('annoorderedlist', true);
					buttonDisable('annobulletlist', true);
				}

				if (options.indexOf('inline-graphic') === -1 || options.indexOf('fig') === -1) {
					buttonDisable('annoimages', true);
					buttonDisable('annoequations', true);
				}
				else {
					buttonDisable('annoimages', false);
					buttonDisable('annoequations', false);
				}

				if (options.indexOf('disp-quote') === -1) {
					buttonDisable('annoquote', true);
				}
				else {
					buttonDisable('annoquote', false);
				}

				function buttonDisable(buttonName, tf) {
					if (tf) {
						cm.setActive(buttonName, false);
					}
					return cm.setDisabled(buttonName, tf);
				}
			});

			// Register example button
			editor.addButton('textorum-insertion-before', {
				title : ed.getLang('annotextorum.elementBefore'),
				cmd : 'Textorum_Insertion_Menu',
				value: 'before'
			});

			editor.addButton('textorum-insertion-inside', {
				title : ed.getLang('annotextorum.elementInside'),
				cmd : 'Textorum_Insertion_Menu',
				value: 'inside'
			});

			editor.addButton('textorum-insertion-after', {
				title : ed.getLang('annotextorum.elementafter'),
				cmd : 'Textorum_Insertion_Menu',
				value: 'after'
			});

			editor.addShortcut('ctrl+enter', ed.getLang('annotextorum.insertElement'), 'Textorum_Insertion');
		},

		insertElement: function(element_name, where, target, attrs) {
			var editor = tinyMCE.activeEditor,
				newNode = editor.dom.create(
					editor.plugins.textorum.translateElement(element_name),
					tinymce.extend({'class': element_name, 'data-xmlel': element_name}, attrs),
					'&#xA0;'
				),
				range, elYPos, options;

			where = where || 'inside';
			target = target || editor.selection.getNode();

			options = this.getValidElements(target, where);

			if (options.indexOf(element_name) === -1) {
				return false;
			}

			// Add additionally required child elements
			switch (element_name) {

				case 'sec':
					newNode.innerHTML = '';
					newNode.appendChild(
						editor.dom.create(
							editor.plugins.textorum.translateElement('title'),
							{'class': 'title', 'data-xmlel': 'title'},
							'&#xA0;'
						)
					);
					break;

				case 'fig':
					(function() {
						var cap = editor.dom.create(
							editor.plugins.textorum.translateElement('caption'),
							{'class': 'caption', 'data-xmlel': 'caption'}
						);

						newNode.innerHTML = '';
						newNode.appendChild(
							editor.dom.create(
								editor.plugins.textorum.translateElement('label'),
								{'class': 'label', 'data-xmlel': 'label'},
								'&#xA0;'
							)
						);

						cap.appendChild(
							editor.dom.create(
								editor.plugins.textorum.translateElement('p'),
								{'class': 'p', 'data-xmlel': 'p'},
								'&#xA0;'
							)
						);

						newNode.appendChild(cap);
					})();
					break;

				case 'list':
					newNode.innerHTML = "";
					this.insertElement('list-item', 'inside', newNode);
					break;

				case 'list-item':
					newNode.innerHTML = '';
					newNode.appendChild(
						editor.dom.create(
							editor.plugins.textorum.translateElement('p'),
							{'class': 'p', 'data-xmlel': 'p'},
							'&#xA0;'
						)
					);
					break;
			}

			dropmenuVisible = false;

			switch(where) {
				case 'before':
					newNode = target.parentNode.insertBefore(newNode, target);
					break;

				case 'inside':
					newNode = target.appendChild(newNode);
					break;

				case 'after':
					if (target.nextSibling) {
						newNode = target.parentNode.insertBefore(newNode, target.nextSibling);
					}
					else {
						newNode = target.parentNode.appendChild(newNode);
					}
					break;
			}


			if (document.createRange) {     // all browsers, except IE before version 9
				range = document.createRange();
				if (newNode.firstChild) {
					range.selectNodeContents(newNode.firstChild);
				}
				else {
					range.selectNodeContents(newNode);
				}
			}

			range.collapse(1);
			editor.selection.setRng(range);

			elYPos = editor.dom.getPos(newNode).y;
			// Scroll to new section
			if (elYPos > editor.dom.getViewPort(editor.getWin()).h) {
					editor.getWin().scrollTo(0, elYPos);
			}

			editor.nodeChanged();
			return newNode;
		},

		getValidElements: function(target, where) {
			var options, ed = tinyMCE.activeEditor;

			target = target || ed.selection.getNode();
			where = where || 'inside';

			options = ed.plugins.textorum.validator.validElementsForNode(target, where, "array");

			// Textorum doesn't like putting things at the top level body, so account for top level section availability
			if (!options.length && target.className.toUpperCase() == 'SEC' && where !== 'inside') {
				options.push('sec');
			}

			return options;
		},

		getInfo : function() {
			return {
				longname : 'Textorum Context Aware Element Insertion',
				author : 'Crowd Favorite',
				authorurl : 'http://crowdfavorite.com',
				infourl : '',
				version : "1.0"
			};
		},
		// Based on tinymce.editor.dom.split
		// It does not strip empty tags like the tinyMCE version
		// It also inserts an space into empty nodes.
		split: function(ed, parentElm, splitElm, replacementElm) {
			var self = ed.dom, r = self.createRng(), bef, aft, pa;

			if (parentElm && splitElm) {
				// Get before chunk
				r.setStart(parentElm.parentNode, self.nodeIndex(parentElm));
				r.setEnd(splitElm.parentNode, self.nodeIndex(splitElm));
				bef = r.extractContents();

				if ('' == bef.textContent) {
					if (bef.firstElementChild.firstElementChild) {
						bef.firstElementChild.firstElementChild.textContent = '\xA0';
					}
				}

				// Get after chunk
				r = self.createRng();
				r.setStart(splitElm.parentNode, self.nodeIndex(splitElm) + 1);
				r.setEnd(parentElm.parentNode, self.nodeIndex(parentElm) + 1);
				aft = r.extractContents();

				if ('' == aft.textContent) {
					if (aft.firstElementChild.firstElementChild) {
						aft.firstElementChild.firstElementChild.textContent = '\xA0';
					}
				}

				// Insert before chunk
				pa = parentElm.parentNode;
				pa.insertBefore(bef, parentElm);

				// Insert middle chunk
				if (replacementElm) {
					pa.replaceChild(replacementElm, splitElm);
				} else {
					pa.insertBefore(splitElm, parentElm);
				}

				// Insert after chunk
				pa.insertBefore(aft, parentElm);

				self.remove(parentElm);

				return replacementElm || splitElm;
			}
		},
	});

	// Register plugin
	tinymce.PluginManager.add('textorumInsertion', tinymce.plugins.textorumInsertion);
})(jQuery);
