/**
/**
 * Based on the lists plugin for tinyMCE developed by Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 *
 * Modified By Crowd Favorite 05/30/2014
 */


tinymce.PluginManager.add('annoLists', function(editor) {
	var self = this;

	editor.addButton('annoorderedlist', {
		title : editor.getLang('annolists.orderedlist'),
		cmd : 'AnnoInsertOrderedList'
	});

	editor.addButton('annobulletlist', {
		title : editor.getLang('annolists.bullist'),
		cmd : 'AnnoInsertUnorderedList'
	});

	editor.addButton('annoindentlist', {
		title : editor.getLang('annolists.indent'),
		cmd : 'AnnoIndentList',
		icon : 'indent'
	});

	editor.addButton('annooutdentlist', {
		title : editor.getLang('annolists.outdent'),
		cmd : 'AnnoOutdentList',
		icon : 'outdent'
	});

	function isListNode(node) {
		return node && node.nodeType === 1 && node.getAttribute('data-xmlel') == 'list';
	}

	function isFirstChild(node) {
		return node.parentNode.firstChild == node;
	}

	function isLastChild(node) {
		return node.parentNode.lastChild == node;
	}

	function isTextBlock(node) {
		return node && !!editor.schema.getTextBlockElements()[node.nodeName];
	}

	function isBookmarkNode(node) {
		return node && node.nodeName === 'SPAN' && node.getAttribute('data-mce-type') === 'bookmark';
	}

	editor.on('init', function() {
		var dom = editor.dom, selection = editor.selection;
		var helper = editor.plugins.textorum.helper;
		var textorum = editor.plugins.textorum;

		editor.onNodeChange.add(function(ed, object, e) {
			if (helper.getLocalName(e.parentNode) == 'list-item' && helper.getLocalName(e.parentNode.firstChild) != 'p') {
				var pTags = ed.dom.select('.p', e.parentNode);
				if (pTags.length == 0) {
					var content = e.parentNode.innerHTML;
					var pNode = ed.dom.create(
						ed.plugins.textorum.translateElement('p'),
						{'class': 'p', 'data-xmlel': 'p'},
						content
					);

					e.parentNode.innerHTML = (pNode.outerHTML);
				}
			}
		});

		/**
		 * Returns a range bookmark. This will convert indexed bookmarks into temporary span elements with
		 * index 0 so that they can be restored properly after the DOM has been modified. Text bookmarks will not have spans
		 * added to them since they can be restored after a dom operation.
		 *
		 * So this: <p><b>|</b><b>|</b></p>
		 * becomes: <p><b><span data-mce-type="bookmark">|</span></b><b data-mce-type="bookmark">|</span></b></p>
		 *
		 * @param  {DOMRange} rng DOM Range to get bookmark on.
		 * @return {Object} Bookmark object.
		 */
		function createBookmark(rng) {
			var bookmark = {};

			function setupEndPoint(start) {
				var offsetNode, container, offset;

				container = rng[start ? 'startContainer' : 'endContainer'];
				offset = rng[start ? 'startOffset' : 'endOffset'];

				if (container.nodeType == 1) {
					offsetNode = dom.create('span', {'data-mce-type': 'bookmark'});

					if (container.hasChildNodes()) {
						offset = Math.min(offset, container.childNodes.length - 1);

						if (start) {
							container.insertBefore(offsetNode, container.childNodes[offset]);
						} else {
							dom.insertAfter(offsetNode, container.childNodes[offset]);
						}
					} else {
						container.appendChild(offsetNode);
					}

					container = offsetNode;
					offset = 0;
				}

				bookmark[start ? 'startContainer' : 'endContainer'] = container;
				bookmark[start ? 'startOffset' : 'endOffset'] = offset;
			}

			setupEndPoint(true);

			if (!rng.collapsed) {
				setupEndPoint();
			}

			return bookmark;
		}

		/**
		 * Moves the selection to the current bookmark and removes any selection container wrappers.
		 *
		 * @param {Object} bookmark Bookmark object to move selection to.
		 */
		function moveToBookmark(bookmark) {
			function restoreEndPoint(start) {
				var container, offset, node;

				function nodeIndex(container) {
					var node = container.parentNode.firstChild, idx = 0;

					while (node) {
						if (node == container) {
							return idx;
						}

						// Skip data-mce-type=bookmark nodes
						if (node.nodeType != 1 || node.getAttribute('data-mce-type') != 'bookmark') {
							idx++;
						}

						node = node.nextSibling;
					}

					return -1;
				}

				container = node = bookmark[start ? 'startContainer' : 'endContainer'];
				offset = bookmark[start ? 'startOffset' : 'endOffset'];

				if (!container) {
					return;
				}

				if (container.nodeType == 1) {
					offset = nodeIndex(container);
					container = container.parentNode;
					dom.remove(node);
				}

				bookmark[start ? 'startContainer' : 'endContainer'] = container;
				bookmark[start ? 'startOffset' : 'endOffset'] = offset;
			}

			restoreEndPoint(true);
			restoreEndPoint();

			var rng = dom.createRng();

			rng.setStart(bookmark.startContainer, bookmark.startOffset);

			if (bookmark.endContainer) {
				rng.setEnd(bookmark.endContainer, bookmark.endOffset);
			}

			selection.setRng(rng);
		}

		function createNewTextBlock(contentNode, blockName) {
			var node, textBlock, fragment = dom.createFragment(), hasContentNode;
			var blockElements = editor.schema.getBlockElements();

			// Unwrap the P tags
			if (helper.getLocalName(contentNode) == 'list-item') {
				var child = contentNode.firstChild;
				if (helper.getLocalName(child) == 'p') {
					jQuery(child).contents().unwrap();
				}
			}

			if (editor.settings.forced_root_block) {
				blockName = blockName || editor.settings.forced_root_block;
			}

			if (blockName) {
				textBlock = dom.create(
					textorum.translateElement(blockName),
					{'class': blockName, 'data-xmlel': blockName}
				);

				if (textBlock.tagName === editor.settings.forced_root_block) {
					dom.setAttribs(textBlock, editor.settings.forced_root_block_attrs);
				}

				fragment.appendChild(textBlock);
			}

			if (contentNode) {
				while ((node = contentNode.firstChild)) {
					var nodeName = node.nodeName;
					if (!hasContentNode && (nodeName != 'SPAN' || node.getAttribute('data-mce-type') != 'bookmark')) {
						hasContentNode = true;
					}

					if (blockElements[nodeName]) {
						fragment.appendChild(node);
						textBlock = null;
					}
					else {
						if (blockName) {
							if (!textBlock) {
								textBlock = textBlock = dom.create(
												textorum.translateElement(blockName),
												{'class': blockName, 'data-xmlel': blockName}
											);
								fragment.appendChild(textBlock);
							}

							textBlock.appendChild(node);
						}
						else {
							fragment.appendChild(node);
						}
					}
				}
			}

			if (!editor.settings.forced_root_block) {
				fragment.appendChild(dom.create('br'));
			}
			else {
				// BR is needed in empty blocks on non IE browsers
				if (!hasContentNode && (!tinymce.Env.ie || tinymce.Env.ie > 10)) {
					textBlock.appendChild(dom.create('br', {'data-mce-bogus': '1'}));
				}
			}

			return fragment;
		}

		function getSelectedListItems() {
			var blocks = selection.getSelectedBlocks();
			var lis = [];
			tinymce.each(blocks, function(block) {
				var foundLi = dom.getParent(block, '.list-item');
				if (helper.getLocalName(block) == 'list-item') {
					lis.push(block);
				}
				else if (foundLi != null) {
					lis.push(foundLi);
				}
			});
			return lis;
		}

		function splitList(ul, li, newBlock) {
			var tmpRng, fragment, startRange;
			var parent = dom.getParent(li.parentNode, '.list-item');
			var startRange = li;
			var bookmarks = dom.select('span[data-mce-type="bookmark"]', ul);

			// Want to split on the first lists's LI
			while (parent) {
				if (parent) {
					startRange = parent;
				}
				parent = dom.getParent(parent.parentNode, '.list-item');
			}

			newBlock = newBlock || createNewTextBlock(li);

			tmpRng = dom.createRng();
			tmpRng.setStartAfter(startRange);
			tmpRng.setEndAfter(ul);
			fragment = tmpRng.extractContents();

			if (!dom.isEmpty(fragment)) {
				dom.insertAfter(fragment, ul);
			}

			var el = dom.insertAfter(newBlock, ul);

			if (dom.isEmpty(li.parentNode)) {
				tinymce.each(bookmarks, function(node) {
					li.parentNode.parentNode.insertBefore(node, li.parentNode);
				});

				dom.remove(li.parentNode);
			}

			dom.remove(li);
		}

		function getListType(node) {
			return dom.getAttrib(node, 'list-type');
		}

		function mergeWithAdjacentLists(listBlock) {
			var sibling, node;
			var originalNode = listBlock.firstChild;
			sibling = listBlock.nextSibling;
			if (sibling && jQuery(sibling).hasClass('_mce_tagged_br')) {
				sibling = sibling.nextSibling;
			}
			if (sibling && isListNode(sibling) && getListType(sibling) == getListType(listBlock)) {
				while ((node = sibling.firstChild)) {
					listBlock.appendChild(node);
				}

				dom.remove(sibling);
			}

			sibling = listBlock.previousSibling;
			if (sibling && jQuery(sibling).hasClass('_mce_tagged_br')) {
				sibling = sibling.previousSibling;
			}
			if (sibling && isListNode(sibling) && getListType(sibling) == getListType(listBlock)) {
				while ((node = sibling.firstChild)) {
					// inserting node before listBlockfirstchild
					listBlock.insertBefore(node, originalNode);
				}

				dom.remove(sibling);
			}
		}

		/**
		 * Normalizes the all lists in the specified element.
		 */
		function normalizeList(element) {
			tinymce.each(tinymce.grep(dom.select('list', element)), function(ul) {
				var sibling, parentNode = ul.parentNode;

				// Move UL/OL to previous LI if it's the only child of a LI
				if (parentNode.className == 'list-item' && parentNode.firstChild == ul) {
					sibling = parentNode.previousSibling;
					if (sibling && sibling.className == 'list-item') {
						sibling.appendChild(ul);

						if (dom.isEmpty(parentNode)) {
							dom.remove(parentNode);
						}
					}
				}

				// Append OL/UL to previous LI if it's in a parent OL/UL i.e. old HTML4
				if (isListNode(parentNode)) {
					sibling = parentNode.previousSibling;
					if (sibling && sibling.className == 'list-item') {
						sibling.appendChild(ul);
					}
				}
			});
		}

		function outdent(li) {
			var ul = li.parentNode, ulParent = ul.parentNode, newBlock;

			function removeEmptyLi(li) {
				if (dom.isEmpty(li)) {
					dom.remove(li);
				}
			}

			// Only Single LI in the list item
			if (isFirstChild(li) && isLastChild(li)) {
				if (dom.getAttrib(ulParent, 'data-xmlel') == 'list-item') {
					dom.insertAfter(li, ulParent);
					removeEmptyLi(ulParent);
					dom.remove(ul);
				}
				else if (isListNode(ulParent)) {
					dom.remove(ul, true);
				}
				else {
					ulParent.insertBefore(createNewTextBlock(li), ul);
					dom.remove(ul);
				}

				return true;
			}
			else if (isFirstChild(li)) {
				if (dom.getAttrib(ulParent, 'data-xmlel') == 'list-item') {
					dom.insertAfter(li, ulParent);
					li.appendChild(ul);
					removeEmptyLi(ulParent);
				} else if (isListNode(ulParent)) {
					ulParent.insertBefore(li, ul);
				} else {
					ulParent.insertBefore(createNewTextBlock(li), ul);
					dom.remove(li);
				}

				return true;
			}
			else if (isLastChild(li)) {
				if (dom.getAttrib(ulParent, 'data-xmlel') == 'list-item') {
					dom.insertAfter(li, ulParent);
				} else if (isListNode(ulParent)) {
					dom.insertAfter(li, ul);
				} else {
					dom.insertAfter(createNewTextBlock(li), ul);
					dom.remove(li);
				}

				return true;
			}
			else {
				if (dom.getAttrib(ulParent, 'data-xmlel') == 'list-item') {
					ul = ulParent;
					newBlock = createNewTextBlock(li, 'list-item');
				} else if (isListNode(ulParent)) {
					newBlock = createNewTextBlock(li, 'list-item');
				} else {
					newBlock = createNewTextBlock(li);
				}
				splitList(ul, li, newBlock);
				normalizeList(ul.parentNode);

				return true;
			}

			return false;
		}

		function indent(li) {
			var sibling, newList;

			function mergeLists(from, to) {
				var node;

				if (isListNode(from)) {
					while ((node = li.lastChild.firstChild)) {
						to.appendChild(node);
					}

					dom.remove(from);
				}
			}

			sibling = li.previousSibling;

			if (sibling && isListNode(sibling)) {
				sibling.appendChild(li);
				return true;
			}

			if (sibling && sibling.className == 'list-item' && isListNode(sibling.lastChild)) {
				sibling.lastChild.appendChild(li);
				mergeLists(li.lastChild, sibling.lastChild);
				return true;
			}

			sibling = li.nextSibling;

			if (sibling && isListNode(sibling)) {
				sibling.insertBefore(li, sibling.firstChild);
				return true;
			}

			if (sibling && sibling.className == 'list-item' && isListNode(li.lastChild)) {
				return false;
			}

			sibling = li.previousSibling;
			if (sibling && sibling.className == 'list-item') {
				var parentNodeType = dom.getAttrib(li.parentNode, 'data-xmlel');
				var listType = dom.getAttrib(li.parentNode, 'list-type');

				newList = dom.create(
							editor.plugins.textorum.translateElement(parentNodeType),
							{
								'class': parentNodeType,
								'data-xmlel': parentNodeType,
								'list-type' : listType
							}
						);
				sibling.appendChild(newList);
				newList.appendChild(li);
				mergeLists(li.lastChild, newList);
				return true;
			}

			return false;
		}

		function indentSelection() {
			var listElements = getSelectedListItems();
			if (listElements.length) {
				var bookmark = createBookmark(selection.getRng(true));

				for (var i = 0; i < listElements.length; i++) {
					if (!indent(listElements[i]) && i === 0) {
						break;
					}
				}

				moveToBookmark(bookmark);
				editor.nodeChanged();

				return true;
			}
		}

		function outdentSelection() {
			var listElements = getSelectedListItems();

			if (listElements.length) {
				var bookmark = createBookmark(selection.getRng(true));
				var i, y, root = editor.getBody();

				i = listElements.length;
				while (i--) {
					var node = listElements[i].parentNode;

					while (node && node != root) {
						y = listElements.length;
						while (y--) {
							if (listElements[y] === node) {
								listElements.splice(i, 1);
								break;
							}
						}

						node = node.parentNode;
					}
				}

				for (i = 0; i < listElements.length; i++) {
					if (!outdent(listElements[i]) && i === 0) {
						break;
					}
				}

				moveToBookmark(bookmark);
				editor.nodeChanged();

				return true;
			}
		}

		function applyList(listType) {
			var rng = selection.getRng(true), bookmark = createBookmark(rng);

			function cleanupBr(e) {
				if (e && e.tagName === 'BR') {
					dom.remove(e);
				}
			}

			function doWrapList(start, end, template) {
				var li, n = start, tmp, i, title, content;
				while (!dom.isBlock(start.parentNode) && start.parentNode !== dom.getRoot() && start.previousSibling) {
					start = dom.split(start.parentNode, start.previousSibling);
					start = start.nextSibling;
					n = start;
				}
				li = dom.create(
					tinyMCE.activeEditor.plugins.textorum.translateElement('list-item'),
					{'class': 'list-item', 'data-xmlel': 'list-item'}
				);

				dom.add(li, dom.create(
					editor.plugins.textorum.translateElement('p'),
					{'class': 'p', 'data-xmlel': 'p'},
					''
				));

				// Insert before the start but as a child of the parent.
				start.parentNode.insertBefore(li, start);

				while (n && n != end) {
					tmp = n.nextSibling;
					li.firstChild.appendChild(n);
					n = tmp;
				}
				if (li.firstChild && '' == li.firstChild.textContent) {
					var textBlock = dom.create(
						'br',
						{'_mce_bogus' : 1}
					);
					li.firstChild.appendChild(textBlock);
				}
				makeList(li);
			}

			function makeList(element) {
				var list = dom.create(
						tinyMCE.activeEditor.plugins.textorum.translateElement('list'),
						{
							'list-type': listType,
							'class': 'list',
							'data-xmlel': 'list'
						}
					), li;

				if ('LIST-ITEM' !== element.className.toUpperCase()) {
					// Put the list around the element.
					li = dom.create(
						tinyMCE.activeEditor.plugins.textorum.translateElement('list-item'),
						{'class': 'list-item', 'data-xmlel': 'list-item'}
					);

					dom.add(li, dom.create(
						editor.plugins.textorum.translateElement('p'),
						{'class': 'p', 'data-xmlel': 'p'},
						''
					));
					dom.insertAfter(li, element);
					li.appendChild(element);
					element = li;
				}

				dom.insertAfter(list, element);
				list.appendChild(element);
				mergeWithAdjacentLists(list, true);
			}

			function processBrs(element, callback) {

				var startSection, previousBR, END_TO_START = 3, START_TO_END = 1,
					breakElements = 'br,.list:not(.list-item .list),.p:not(.list-item > .p),.title,table,.disp-quote,.pre,dl';
				var bookmark = createBookmark(selection.getRng(true));

				function isAnyPartSelected(start, end) {
					var r = dom.createRng(), sel;
					bookmark.keep = true;
					editor.selection.moveToBookmark(bookmark);
					bookmark.keep = false;
					sel = editor.selection.getRng(true);
					if (!end) {
						end = start.parentNode.lastChild;
					}
					r.setStartBefore(start);
					r.setEndAfter(end);
					return !(r.compareBoundaryPoints(END_TO_START, sel) > 0 || r.compareBoundaryPoints(START_TO_END, sel) <= 0);
				}

				function nextLeaf(br) {
					if (br && br.nextSibling) {
						return br.nextSibling;
					}
					if (br && !dom.isBlock(br.parentNode) && br.parentNode !== dom.getRoot()) {
						return nextLeaf(br.parentNode);
					}
					return br;
				}

				// Split on BRs within the range and process those.
				startSection = element.firstChild;
				// First mark the BRs that have any part of the previous section selected.
				var trailingContentSelected = false;
				tinymce.each(dom.select(breakElements, element), function(br) {
					var b;
					if (br.hasAttribute && br.hasAttribute('_mce_bogus')) {
						return true; // Skip the bogus Brs that are put in to appease Firefox and Safari.
					}

					if (!!startSection && isAnyPartSelected(startSection, br)) {
						dom.addClass(br, '_mce_tagged_br');
						startSection = nextLeaf(br);
					}
				});
				trailingContentSelected = (startSection && isAnyPartSelected(startSection, undefined));
				startSection = element.firstChild;

				tinymce.each(dom.select(breakElements, element), function(br) {
					// Got a section from start to br.
					var tmp = nextLeaf(br);
					if (br.hasAttribute && br.hasAttribute('_mce_bogus')) {
						return true; // Skip the bogus Brs that are put in to appease Firefox and Safari.
					}
					if (dom.hasClass(br, '_mce_tagged_br')) {
						callback(startSection, br, previousBR);
						previousBR = null;
					} else {
						previousBR = br;
					}
					startSection = tmp;
				});

				if (trailingContentSelected) {
					callback(startSection, undefined, previousBR);
				}
			}

			processBrs(tinymce.activeEditor.selection.getNode(), function(startSection, br, previousBR){
				doWrapList(startSection, br);
				cleanupBr(br);
				cleanupBr(previousBR);
			});

			moveToBookmark(bookmark);
		}

		function liIsEmpty(li) {
			var p = li.firstChild;
			if (!p) {
				return true;
			}
			if (p.innerHTML.replace(/&nbsp;/g, '').trim() == '') {
				return true;
			}

			return false;
		}

		function removeList() {
			var bookmark = createBookmark(selection.getRng(true)), root = editor.getBody();

			tinymce.each(getSelectedListItems(), function(li) {
				var node, rootList;

				if (liIsEmpty(li)) {
					outdent(li);
					return;
				}

				for (node = li; node && node != root; node = node.parentNode) {
					if (isListNode(node)) {
						rootList = node;
					}
				}

				splitList(rootList, li);
			});

			moveToBookmark(bookmark);
		}

		function toggleList(listType) {
			var parentList = dom.getParent(selection.getStart(), '.list');

			if (parentList) {
				if (dom.getAttrib(parentList, 'list-type') == listType) {
					removeList(listType);
				}
				else {
					var bookmark = createBookmark(selection.getRng(true));
					dom.setAttrib(parentList, 'list-type', listType);
					mergeWithAdjacentLists(parentList);
					moveToBookmark(bookmark);
				}
			} else {
				applyList(listType);
			}
		}

		self.backspaceDelete = function(isForward) {
			function findNextCaretContainer(rng, isForward) {
				var node = rng.startContainer, offset = rng.startOffset;

				if (node.nodeType == 3 && (isForward ? offset < node.data.length : offset > 0)) {
					return node;
				}

				var walker = new tinymce.dom.TreeWalker(rng.startContainer);
				while ((node = walker[isForward ? 'next' : 'prev']())) {
					if (node.nodeType == 3 && node.data.length > 0) {
						return node;
					}
				}
			}

			function mergeLiElements(fromElm, toElm) {
				var node, listNode, ul = fromElm.parentNode;

				if (isListNode(toElm.lastChild)) {
					listNode = toElm.lastChild;
				}

				node = toElm.lastChild;
				if (node && node.nodeName == 'BR' && fromElm.hasChildNodes()) {
					dom.remove(node);
				}

				while ((node = fromElm.firstChild)) {
					toElm.appendChild(node);
				}

				if (listNode) {
					toElm.appendChild(listNode);
				}

				dom.remove(fromElm);

				if (dom.isEmpty(ul)) {
					dom.remove(ul);
				}
			}

			if (selection.isCollapsed()) {
				var li = dom.getParent(selection.getStart(), '.list-item');

				if (li) {
					var rng = selection.getRng(true);
					var otherLi = dom.getParent(findNextCaretContainer(rng, isForward), '.list-item');

					if (otherLi && otherLi != li) {
						var bookmark = createBookmark(rng);

						if (isForward) {
							mergeLiElements(otherLi, li);
						} else {
							mergeLiElements(li, otherLi);
						}

						moveToBookmark(bookmark);

						return true;
					} else if (!otherLi) {
						if (!isForward && removeList(li.parentNode.nodeName)) {
							return true;
						}
					}
				}
			}
		};

		editor.addCommand('AnnoInsertOrderedList', function() {
			toggleList('order');
		});

		editor.addCommand('AnnoInsertUnorderedList', function() {
			toggleList('bullet');
		});

		editor.addCommand('AnnoOutdentList', function() {
			if (editor.dom.getParent(editor.selection.getStart(), '.list-item')) {
				outdentSelection();
			}
		});

		editor.addCommand('AnnoIndentList', function() {
			if (editor.dom.getParent(editor.selection.getStart(), '.list-item')) {
				indentSelection();
			}
		});

		editor.on('keydown', function(e) {
			if (e.keyCode == 9 && editor.dom.getParent(editor.selection.getStart(), '.list-item')) {
				e.preventDefault();

				if (e.shiftKey) {
					outdentSelection();
				} else {
					indentSelection();
				}
			}
		});
	});

	editor.addButton('indent', {
		icon: 'indent',
		title: 'Increase indent',
		cmd: 'Indent',
		onPostRender: function() {
			var ctrl = this;

			editor.on('nodechange', function() {
				var blocks = editor.selection.getSelectedBlocks();
				var disable = false;

				for (var i = 0, l = blocks.length; !disable && i < l; i++) {
					var tag = blocks[i].nodeName;

					disable = (tag == 'LI' && isFirstChild(blocks[i]) || tag == 'UL' || tag == 'OL');
				}

				ctrl.disabled(disable);
			});
		}
	});

	editor.on('keydown', function(e) {
		if (e.keyCode == tinymce.util.VK.BACKSPACE) {
			if (self.backspaceDelete()) {
				e.preventDefault();
			}
		} else if (e.keyCode == tinymce.util.VK.DELETE) {
			if (self.backspaceDelete(true)) {
				e.preventDefault();
			}
		}
	});
});
