(function(){
	tinymce.create('tinymce.plugins.annoFormats', {

		init : function(ed, url){
			var t = this;
			t.editor = ed;
			t.helper = ed.plugins.textorum.helper;
			t.textorum = ed.plugins.textorum;

			ed.addCommand('Anno_Monospace', function() {
				tinymce.activeEditor.formatter.toggle('monospace');
			});

			ed.addCommand('Anno_Preformat', function() {
				tinymce.activeEditor.formatter.toggle('preformat');
			});

			ed.addButton('annopreformat', {
				title : ed.getLang('annoformats.preformat'),
				//ed.getLang('advanced.references_desc'),
				cmd : 'Anno_Preformat'
			});

			ed.addButton('annomonospace', {
				title : ed.getLang('annoformats.monospace'),
				//ed.getLang('advanced.references_desc'),
				cmd : 'Anno_Monospace'
			});

			ed.addButton('annosection', {
				title: ed.getLang('annoformats.newSection'),
				type: 'menubutton',
				icon: 'annosection',
				menu: [
					{
						text: ed.getLang('annoformats.newSection'),
						onclick : function() {t.insertSection();}
					},
					{
						text: ed.getLang('annoformats.newSubsection'),
						onclick : function() {t.insertSubsection();}
					}
				]
			});

		},

		_insertSection: function(isSubsection) {
			var ed = this.editor, doc = ed.getDoc(), node = ed.selection.getNode(), dom = ed.dom, range, elYPos, vpHeight = dom.getViewPort(ed.getWin()).h;
			var curNodeName = this.helper.getLocalName(node), target, newElement = newSec(), eleArray;

			isSubsection = typeof isSubsection !== 'undefined' ? isSubsection : false;

			// If In sec and insert sub section or we're in the body, use insert into
			if ((curNodeName == 'sec' && isSubsection) || curNodeName == 'body') {
				dom.add(node, newElement);
			}
			else {
				// Special case, current node is section and not inserting sub section
				if (curNodeName == 'sec') {
					target = node;
				}
				else {
					target = dom.getParent(node, this.helper.testNameIs('sec'));
					if (isSubsection && target !== null) {
						target = target.lastChild;
					}
				}
				if (target === null) {
					target = topLevelNode(node);
				}
				dom.insertAfter(newElement, target);
			}
			eleArray = dom.select(' > title', newElement);

			// Focus the editor since dropdown menus lose focus
			ed.focus();

			if (eleArray.length > 0) {
				newElement = eleArray[0];
			}

			if (doc.createRange) { // all browsers, except IE before version 9
				range = doc.createRange();
				range.selectNodeContents(newElement.firstChild);
			}
			else { // IE < 9
				range = doc.selection.createRange();
				range.moveToElementText(newElement.firstChild);
			}

			range.collapse(1);
			ed.selection.setRng(range);

			elYPos = dom.getPos(newElement).y;
			// Scroll to new section
			if (elYPos > vpHeight) {
				ed.getWin().scrollTo(0, elYPos);
			}

			ed.nodeChanged();

			// Create a new sec element with a title
			function newSec() {
				var sec = dom.create(ed.plugins.textorum.translateElement('sec'), {'class': 'sec', 'data-xmlel': 'sec'});
				dom.add(sec, ed.plugins.textorum.translateElement('title'), {'class': 'title', 'data-xmlel': 'title'}, '&#xA0;');
				dom.add(sec, ed.plugins.textorum.translateElement('p'), {'class': 'p', 'data-xmlel': 'p'}, '&#xA0;');
				return sec;
			}
			function topLevelNode(currentNode) {
				while (currentNode && currentNode.parentNode.nodeName != 'BODY') {
					currentNode = currentNode.parentNode;
				}

				return currentNode;
			}

		},
		insertSection : function () {
			this._insertSection(false);

		},
		insertSubsection : function () {
			this._insertSection(true);
		},
		getInfo : function() {
			return {
				longname: 'Annotum Formats',
				author: 'Crowd Favorite',
				authorurl: 'http://crowdfavorite.com/',
				infourl: 'http://annotum.wordpress.com/',
				version: "0.2"
			};
		}
	});

	tinymce.PluginManager.add('annoFormats', tinymce.plugins.annoFormats);
})();
