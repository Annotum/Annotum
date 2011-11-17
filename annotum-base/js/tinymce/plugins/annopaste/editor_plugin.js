/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */
(function() {
	var each = tinymce.each,
		defs = {
			paste_auto_cleanup_on_paste : true,
			paste_enable_default_filters : true,
			paste_block_drop : false,
			paste_retain_style_properties : 'none',
			paste_strip_class_attributes : 'all',
			paste_remove_spans : false,
			paste_remove_styles : false,
			paste_remove_styles_if_webkit : true,
			paste_convert_middot_lists : true,
			paste_text_use_dialog : false,
			paste_text_sticky : false,
			paste_text_sticky_default : false,
			paste_text_notifyalways : false,
			paste_text_linebreaktype : 'p',
			paste_text_replacements : [
				[/\u2026/g, '...'],
				[/[\x93\x94\u201c\u201d]/g, '"'],
				[/[\x60\x91\x92\u2018\u2019]/g, "'"]
			]
		};

	function getParam(ed, name) {
		return ed.getParam(name, defs[name]);
	}

	tinymce.create('tinymce.plugins.AnnoPaste', {
		init : function(ed, url) {
			var t = this;

			t.editor = ed;
			t.url = url;

			// Setup plugin events
			t.onPreProcess = new tinymce.util.Dispatcher(t);
			t.onPostProcess = new tinymce.util.Dispatcher(t);

			// Register default handlers
			t.onPreProcess.add(t._preProcess);
			t.onPostProcess.add(t._postProcess);

			// Register optional preprocess handler
			t.onPreProcess.add(function(pl, o) {
				ed.execCallback('paste_preprocess', pl, o);
			}); 

			// Register optional postprocess
			t.onPostProcess.add(function(pl, o) {
				ed.execCallback('paste_postprocess', pl, o);
			});

			ed.onKeyDown.addToTop(function(ed, e) {
				// Block ctrl+v from adding an undo level since the default logic in tinymce.Editor will add that
				if (((tinymce.isMac ? e.metaKey : e.ctrlKey) && e.keyCode == 86) || (e.shiftKey && e.keyCode == 45))
					return false; // Stop other listeners
			});

			// Initialize plain text flag
			ed.pasteAsPlainText = getParam(ed, 'paste_text_sticky_default');

			// This function executes the process handlers and inserts the contents
			// force_rich overrides plain text mode set by user, important for pasting with execCommand
			function process(o, force_rich) {
				var dom = ed.dom, rng;
				// Execute pre process handlers
				t.onPreProcess.dispatch(t, o);

				// Create DOM structure
				o.node = dom.create('div', 0, o.content);

				// If pasting inside the same element and the contents is only one block
				// remove the block and keep the text since Firefox will copy parts of pre and h1-h6 as a pre element
				if (tinymce.isGecko) {
					rng = ed.selection.getRng(true);
					if (rng.startContainer == rng.endContainer && rng.startContainer.nodeType == 3) {
						// Is only one block node and it doesn't contain word stuff
						if (o.node.childNodes.length === 1 && /^(p|h[1-6]|pre)$/i.test(o.node.firstChild.nodeName) && o.content.indexOf('__MCE_ITEM__') === -1)
							dom.remove(o.node.firstChild, true);
					}
				}

				// Execute post process handlers
				t.onPostProcess.dispatch(t, o);

				// Serialize content
				o.content = ed.serializer.serialize(o.node, {getInner : 1, forced_root_block : ''});

				// Plain text option active?
				if ((!force_rich) && (ed.pasteAsPlainText)) {
					t._insertPlainText(ed, dom, o.content);

					if (!getParam(ed, 'paste_text_sticky')) {
						ed.pasteAsPlainText = false;
						ed.controlManager.setActive('pastetext', false);
					}
				} else {
					t._insert(o.content);
				}
			}

			// Add command for external usage
			ed.addCommand('AnnomceInsertClipboardContent', function(u, o) {
				process(o, true);
			});

			if (!getParam(ed, 'paste_text_use_dialog')) {
				ed.addCommand('AnnomcePasteText', function(u, v) {
					var cookie = tinymce.util.Cookie;

					ed.pasteAsPlainText = !ed.pasteAsPlainText;
					ed.controlManager.setActive('pastetext', ed.pasteAsPlainText);

					if ((ed.pasteAsPlainText) && (!cookie.get('tinymcePasteText'))) {
						if (getParam(ed, 'paste_text_sticky')) {
							ed.windowManager.alert(ed.translate('paste.plaintext_mode_sticky'));
						} else {
							ed.windowManager.alert(ed.translate('paste.plaintext_mode_sticky'));
						}

						if (!getParam(ed, 'paste_text_notifyalways')) {
							cookie.set('tinymcePasteText', '1', new Date(new Date().getFullYear() + 1, 12, 31))
						}
					}
				});
			}

			ed.addButton('annopastetext', {title: 'paste.paste_text_desc', cmd: 'AnnomcePasteText'});
			ed.addButton('annoselectall', {title: 'paste.selectall_desc', cmd: 'Annoselectall'});

			// This function grabs the contents from the clipboard by adding a
			// hidden div and placing the caret inside it and after the browser paste
			// is done it grabs that contents and processes that
			function grabContent(e) {
				var n, or, rng, oldRng, sel = ed.selection, dom = ed.dom, body = ed.getBody(), posY, textContent;
				
				// Check if browser supports direct plaintext access
				if (e.clipboardData || dom.doc.dataTransfer) {
					textContent = (e.clipboardData || dom.doc.dataTransfer).getData('Text');

					if (ed.pasteAsPlainText) {
						e.preventDefault();
						process({content : textContent.replace(/\r?\n/g, '<br />')});
						return;
					}
				}

				if (dom.get('_mcePaste'))
					return;

				// Create container to paste into
				n = dom.add(body, 'div', {id : '_mcePaste', 'class' : 'mcePaste', 'data-mce-bogus' : '1'}, '\uFEFF\uFEFF');

				// If contentEditable mode we need to find out the position of the closest element
				if (body != ed.getDoc().body)
					posY = dom.getPos(ed.selection.getStart(), body).y;
				else
					posY = body.scrollTop + dom.getViewPort(ed.getWin()).y;

				// Styles needs to be applied after the element is added to the document since WebKit will otherwise remove all styles
				// If also needs to be in view on IE or the paste would fail
				dom.setStyles(n, {
					position : 'absolute',
					left : tinymce.isGecko ? -40 : 0, // Need to move it out of site on Gecko since it will othewise display a ghost resize rect for the div
					top : posY - 25,
					width : 1,
					height : 1,
					overflow : 'hidden'
				});

				if (tinymce.isIE) {
					// Store away the old range
					oldRng = sel.getRng();

					// Select the container
					rng = dom.doc.body.createTextRange();
					rng.moveToElementText(n);
					rng.execCommand('Paste');

					// Remove container
					dom.remove(n);

					// Check if the contents was changed, if it wasn't then clipboard extraction failed probably due
					// to IE security settings so we pass the junk though better than nothing right
					if (n.innerHTML === '\uFEFF\uFEFF') {
						ed.execCommand('mcePasteWord');
						e.preventDefault();
						return;
					}

					// Restore the old range and clear the contents before pasting
					sel.setRng(oldRng);
					sel.setContent('');

					// For some odd reason we need to detach the the mceInsertContent call from the paste event
					// It's like IE has a reference to the parent element that you paste in and the selection gets messed up
					// when it tries to restore the selection
					setTimeout(function() {
						// Process contents
						process({content : n.innerHTML});
					}, 0);

					// Block the real paste event
					return tinymce.dom.Event.cancel(e);
				} else {

					function block(e) {
						e.preventDefault();
					};

					// Block mousedown and click to prevent selection change
					dom.bind(ed.getDoc(), 'mousedown', block);
					dom.bind(ed.getDoc(), 'keydown', block);

					or = ed.selection.getRng();

					// Move select contents inside DIV
					n = n.firstChild;
					rng = ed.getDoc().createRange();
					rng.setStart(n, 0);
					rng.setEnd(n, 2);
					sel.setRng(rng);

					// Wait a while and grab the pasted contents
					window.setTimeout(function() {
						var h = '', nl;

						// Paste divs duplicated in paste divs seems to happen when you paste plain text so lets first look for that broken behavior in WebKit
						if (!dom.select('div.mcePaste > div.mcePaste').length) {
							nl = dom.select('div.mcePaste');

							// WebKit will split the div into multiple ones so this will loop through then all and join them to get the whole HTML string
							each(nl, function(n) {
								var child = n.firstChild;

								// WebKit inserts a DIV container with lots of odd styles
								if (child && child.nodeName == 'DIV' && child.style.marginTop && child.style.backgroundColor) {
									dom.remove(child, 1);
								}

								// Remove apply style spans
								each(dom.select('span.Apple-style-span', n), function(n) {
									dom.remove(n, 1);
								});

								// Remove bogus br elements
								each(dom.select('br[data-mce-bogus]', n), function(n) {
									dom.remove(n);
								});

								// WebKit will make a copy of the DIV for each line of plain text pasted and insert them into the DIV
								if (n.parentNode.className != 'mcePaste')
									h += n.innerHTML;
							});
						} else {
							// Found WebKit weirdness so force the content into plain text mode
							h = '<preformat>' + dom.encode(textContent).replace(/\r?\n/g, '<br />') + '</preformat>';
						}

						// Remove the nodes
						each(dom.select('div.mcePaste'), function(n) {
							dom.remove(n);
						});

						// Restore the old selection
						if (or)
							sel.setRng(or);

						process({content : h});

						// Unblock events ones we got the contents
						dom.unbind(ed.getDoc(), 'mousedown', block);
						dom.unbind(ed.getDoc(), 'keydown', block);
					}, 0);
				}
			}

			// Check if we should use the new auto process method			
			if (getParam(ed, 'paste_auto_cleanup_on_paste')) {
				// Is it's Opera or older FF use key handler
				if (tinymce.isOpera || /Firefox\/2/.test(navigator.userAgent)) {
					ed.onKeyDown.addToTop(function(ed, e) {
						if (((tinymce.isMac ? e.metaKey : e.ctrlKey) && e.keyCode == 86) || (e.shiftKey && e.keyCode == 45))
							grabContent(e);
					});
				} else {
					// Grab contents on paste event on Gecko and WebKit
					ed.onPaste.addToTop(function(ed, e) {
						return grabContent(e);
					});
				}
			}

			ed.onInit.add(function() {
				ed.controlManager.setActive('pastetext', ed.pasteAsPlainText);

				// Block all drag/drop events
				if (getParam(ed, 'paste_block_drop')) {
					ed.dom.bind(ed.getBody(), ['dragend', 'dragover', 'draggesture', 'dragdrop', 'drop', 'drag'], function(e) {
						e.preventDefault();
						e.stopPropagation();

						return false;
					});
				}
			});

			// Add legacy support
			t._legacySupport();
		},

		getInfo : function() {
			return {
				longname : 'Annotum Paste text/word',
				author : 'Crowd Favorite',
				authorurl : 'http://crowdfavorite.com',
				infourl : '',
				version : '1.0'
			};
		},

		_preProcess : function(pl, o) {
			var ed = this.editor,
				h = o.content,
				grep = tinymce.grep,
				explode = tinymce.explode,
				trim = tinymce.trim,
				len, stripClass,
				dom = ed.dom;

			function process(items) {
				each(items, function(v) {
					// Remove or replace
					if (v.constructor == RegExp)
						h = h.replace(v, '');
					else 
						h = h.replace(v[0], v[1]);						
				});
			}
			
			if (ed.settings.paste_enable_default_filters == false) {
				return;
			}

			// IE9 adds BRs before/after block elements when contents is pasted from word or for example another browser
			if (tinymce.isIE && document.documentMode >= 9) {
				// IE9 adds BRs before/after block elements when contents is pasted from word or for example another browser
				process([[/(?:<br>&nbsp;[\s\r\n]+|<br>)*(<\/?(h[1-6r]|p|div|address|pre|form|table|tbody|thead|tfoot|th|tr|td|li|ol|ul|caption|blockquote|center|dl|dt|dd|dir|fieldset)[^>]*>)(?:<br>&nbsp;[\s\r\n]+|<br>)*/g, '$1']]);

				// IE9 also adds an extra BR element for each soft-linefeed and it also adds a BR for each word wrap break
				process([
					[/<br><br>/g, '<BR><BR>'], // Replace multiple BR elements with uppercase BR to keep them intact
					[/<br>/g, ' '], // Replace single br elements with space since they are word wrap BR:s
					[/<BR><BR>/g, '<br>'], // Replace back the double brs but into a single BR
				]);
			}

			// Detect Word content and process it more aggressive
			if (/class="?Mso|style="[^"]*\bmso-|w:WordDocument/i.test(h) || o.wordContent) {
				o.wordContent = true;			// Mark the pasted contents as word specific content

				// Process away some basic content
				process([
					/^\s*(&nbsp;)+/gi,				// &nbsp; entities at the start of contents
					/(&nbsp;|<br[^>]*>)+\s*$/gi		// &nbsp; entities at the end of contents
				]);

//				if (getParam(ed, "paste_convert_headers_to_strong")) {
					h = h.replace(/<p [^>]*class="?MsoHeading"?[^>]*>(.*?)<\/p>/gi, '<heading>$1</heading>');
//				}

				if (getParam(ed, 'paste_convert_middot_lists')) {
					process([
						[/<!--\[if !supportLists\]-->/gi, '$&__MCE_ITEM__'],					// Convert supportLists to a list item marker
						[/(<span[^>]+(?:mso-list:|:\s*symbol)[^>]+>)/gi, '$1__MCE_ITEM__'],		// Convert mso-list and symbol spans to item markers
						[/(<p[^>]+(?:MsoListParagraph)[^>]+>)/gi, '$1__MCE_ITEM__']				// Convert mso-list and symbol paragraphs to item markers (FF)
					]);
				}

				process([
					// Word comments like conditional comments etc
					/<!--[\s\S]+?-->/gi,

					// Remove comments, scripts (e.g., msoShowComment), XML tag, VML content, MS Office namespaced tags, and a few other tags
					/<(!|script[^>]*>.*?<\/script(?=[>\s])|\/?(\?xml(:\w+)?|img|meta|link|style|\w:\w+)(?=[\s\/>]))[^>]*>/gi,

					// Convert <s> into <strike> for line-though
					[/<(\/?)s>/gi, '<$1strike>'],

					// Replace nsbp entites to char since it's easier to handle
					[/&nbsp;/gi, '\u00a0']
				]);

				// Remove bad attributes, with or without quotes, ensuring that attribute text is really inside a tag.
				// If JavaScript had a RegExp look-behind, we could have integrated this with the last process() array and got rid of the loop. But alas, it does not, so we cannot.
				do {
					len = h.length;
					h = h.replace(/(<[a-z][^>]*\s)(?:id|name|language|type|on\w+|\w+:\w+)=(?:"[^"]*"|\w+)\s?/gi, '$1');
				} while (len != h.length);

				// Remove all spans if no styles is to be retained
				if (getParam(ed, 'paste_retain_style_properties').replace(/^none$/i, '').length == 0) {
					h = h.replace(/<\/?span[^>]*>/gi, '');
				} else {
					// We're keeping styles, so at least clean them up.
					// CSS Reference: http://msdn.microsoft.com/en-us/library/aa155477.aspx

					process([
						// Convert <span style="mso-spacerun:yes">___</span> to string of alternating breaking/non-breaking spaces of same length
						[/<span\s+style\s*=\s*"\s*mso-spacerun\s*:\s*yes\s*;?\s*"\s*>([\s\u00a0]*)<\/span>/gi,
							function(str, spaces) {
								return (spaces.length > 0)? spaces.replace(/./, ' ').slice(Math.floor(spaces.length/2)).split('').join('\u00a0') : '';
							}
						],

						// Examine all styles: delete junk, transform some, and keep the rest
						[/(<[a-z][^>]*)\sstyle="([^"]*)"/gi,
							function(str, tag, style) {
								var n = [],
									i = 0,
									s = explode(trim(style).replace(/&quot;/gi, "'"), ";");

								// Examine each style definition within the tag's style attribute
								each(s, function(v) {
									var name, value,
										parts = explode(v, ":");

									function ensureUnits(v) {
										return v + ((v !== "0") && (/\d$/.test(v)))? "px" : "";
									}

									if (parts.length == 2) {
										name = parts[0].toLowerCase();
										value = parts[1].toLowerCase();

										// Translate certain MS Office styles into their CSS equivalents
										switch (name) {
											case "mso-padding-alt":
											case "mso-padding-top-alt":
											case "mso-padding-right-alt":
											case "mso-padding-bottom-alt":
											case "mso-padding-left-alt":
											case "mso-margin-alt":
											case "mso-margin-top-alt":
											case "mso-margin-right-alt":
											case "mso-margin-bottom-alt":
											case "mso-margin-left-alt":
											case "mso-table-layout-alt":
											case "mso-height":
											case "mso-width":
											case "mso-vertical-align-alt":
												n[i++] = name.replace(/^mso-|-alt$/g, "") + ":" + ensureUnits(value);
												return;

											case "horiz-align":
												n[i++] = "text-align:" + value;
												return;

											case "vert-align":
												n[i++] = "vertical-align:" + value;
												return;

											case "font-color":
											case "mso-foreground":
												n[i++] = "color:" + value;
												return;

											case "mso-background":
											case "mso-highlight":
												n[i++] = "background:" + value;
												return;

											case "mso-default-height":
												n[i++] = "min-height:" + ensureUnits(value);
												return;

											case "mso-default-width":
												n[i++] = "min-width:" + ensureUnits(value);
												return;

											case "mso-padding-between-alt":
												n[i++] = "border-collapse:separate;border-spacing:" + ensureUnits(value);
												return;

											case "text-line-through":
												if ((value == "single") || (value == "double")) {
													n[i++] = "text-decoration:line-through";
												}
												return;

											case "mso-zero-height":
												if (value == "yes") {
													n[i++] = "display:none";
												}
												return;
										}

										// Eliminate all MS Office style definitions that have no CSS equivalent by examining the first characters in the name
										if (/^(mso|column|font-emph|lang|layout|line-break|list-image|nav|panose|punct|row|ruby|sep|size|src|tab-|table-border|text-(?!align|decor|indent|trans)|top-bar|version|vnd|word-break)/.test(name)) {
											return;
										}

										// If it reached this point, it must be a valid CSS style
										n[i++] = name + ":" + parts[1];		// Lower-case name, but keep value case
									}
								});

								// If style attribute contained any valid styles the re-write it; otherwise delete style attribute.
								if (i > 0) {
									return tag + ' style="' + n.join(';') + '"';
								} else {
									return tag;
								}
							}
						]
					]);
				}
			}
			
			// Replace html lists with list tags defined by the DTD.
			
			process([
				[/<ul>|<ul .*?>/gi, "<list list-type=\"bullet\">"],
				[/<\/ul>|<\/ul .*?>/gi, "</list>"]
			]);
			
			process([
				[/<h[1-9]>|<h[1-9] .*?>/gi, "<heading>"],
				[/<\/h[1-9]>|<\/h[1-9] .*?>/gi, "</heading>"]
			]);
		
			process([
				[/<ol>|<ol .*?>/gi, "<list list-type=\"order\">"],
				[/<\/ol>|<\/ol .*?>/gi, "</list>"]
			]);

			process([
				[/<li>|<li .*?>/gi, "<list-item>"],
				[/<\/li>|<\/li .*?>/gi, "</list-item>"]
			]);
			
			// Replace formatting with formatting tags defined by the DTD.
			process([
				[/<(b|strong)>/gi, "<bold>"],
				[/<(\/strong|\/b)>/gi, "</bold>"]
			]);

			process([
				[/<pre>/gi, "<preformat>"],
				[/<\/pre>/gi, "</preformat>"]
			]);

			process([
				[/<i>/gi, "<italic>"],
				[/<\/i>/gi, "</italic>"]
			]);
		
			process([
				[/<u>/gi, "<underline>"],
				[/<\/u>/gi, "</underline>"]
			]);

			process([
				// Copy paste from Java like Open Office will produce this junk on FF
				[/Version:[\d.]+\nStartHTML:\d+\nEndHTML:\d+\nStartFragment:\d+\nEndFragment:\d+/gi, '']
			]);	
			
			// Class attribute options are: leave all as-is ("none"), remove all ("all"), or remove only those starting with mso ("mso").
			// Note:-  paste_strip_class_attributes: "none", verify_css_classes: true is also a good variation.
			stripClass = getParam(ed, "paste_strip_class_attributes");

			if (stripClass !== "none") {
				function removeClasses(match, g1) {
						if (stripClass === "all")
							return '';

						var cls = grep(explode(g1.replace(/^(["'])(.*)\1$/, "$2"), " "),
							function(v) {
								return (/^(?!mso)/i.test(v));
							}
						);

						return cls.length ? ' class="' + cls.join(" ") + '"' : '';
				};

				h = h.replace(/ class="([^"]+)"/gi, removeClasses);
				h = h.replace(/ class=([\-\w]+)/gi, removeClasses);
			}

			h = h.replace(/<\s*(\w+).*?>/gi, '<$1>');

			// Remove spans option
			if (getParam(ed, "paste_remove_spans")) {
				h = h.replace(/<\/?span[^>]*>/gi, "");
			}
			
			o.content = h;
		},

		/**
		 * Various post process items.
		 */
		_postProcess : function(pl, o) {
			var t = this, ed = t.editor, dom = ed.dom, styleProps;
			if (ed.settings.paste_enable_default_filters == false) {
				return;
			}	
			
			function removeAttributes(el) {
				if (!!el) {
			    	var curIndex = 0;
					var whitelist = ['colspan', 'list-type']; 
					var initialLength = el.attributes.length;
					var whiteListCheck = false; 

					for (var i = 0; i < initialLength; i++) {
						var attr = el.attributes.item(curIndex);
						 for(var j = 0; j < whitelist.length; j++) {
							if(attr.nodeName === whitelist[j]) {
								whiteListCheck = true;
								// We know that there is an item at curIndex we want to keep, proceed to the next
								curIndex++;
								break;
							}   
						}
						if(!whiteListCheck) {
							el.removeAttribute(attr.nodeName);
						}
					}
				}
			}
			
			if (o.wordContent) {
				if (getParam(ed, "paste_convert_middot_lists")) {
					t._convertLists(pl, o);
				}
			}
			
			// Replace p tags with para tags. 
			each(dom.select('p', o.node), function(el) {
				dom.rename(el, 'para');
			});
			
			each(dom.select('a', o.node), function(a) {
				if (!a.href || a.href.indexOf('#_Toc') != -1)
					dom.remove(a, 1);
			});

			// Process styles
			styleProps = getParam(ed, "paste_retain_style_properties"); // retained properties

			// Process only if a string was specified and not equal to "all" or "*"
			if ((tinymce.is(styleProps, "string")) && (styleProps !== "all") && (styleProps !== "*")) {
				styleProps = tinymce.explode(styleProps.replace(/^none$/i, ""));

				// Retains some style properties
				each(dom.select('*', o.node), function(el) {
					var newStyle = {}, npc = 0, i, sp, sv;

					// Store a subset of the existing styles
					if (styleProps) {
						for (i = 0; i < styleProps.length; i++) {
							sp = styleProps[i];
							sv = dom.getStyle(el, sp);

							if (sv) {
								newStyle[sp] = sv;
								npc++;
							}
						}
					}

					// Remove all of the existing styles
					dom.setAttrib(el, 'style', '');

					if (styleProps && npc > 0)
						dom.setStyles(el, newStyle); // Add back the stored subset of styles
					else // Remove empty span tags that do not have class attributes
						if (el.nodeName == 'SPAN' && !el.className)
							dom.remove(el, true);
				});
			}

			t._wrapTables(pl, o);

			// Remove unwanted attributes. Colspan, list-type are the only one we care about.
			// @todo Allow paste from other articles
			each(dom.select('*', o.node), removeAttributes);
			
		},
		
		// Tables are expected to be wrapped with specific elements according to the DTD.
		_wrapTables : function(pl, o) {
			var dom = pl.editor.dom, listElm, li, lastMargin = -1, margin, levels = [], lastType, html;
			function hasTableWrap(el) {
			    var parEl = el.parentNode;
			    var count = 1;
			    while(!!parEl && parEl.nodeName != 'TABLE-WRAP') {
			        parEl = parEl.parentNode;
			        count++;
			    }
				return !!parEl;
			}
					
			each(dom.select('table', o.node), function(table) {
				if (!hasTableWrap(table)) {
					//Wrap it!
					tableWrap = dom.create('table-wrap');
					dom.add(tableWrap, 'label');
					dom.add(tableWrap, 'cap', null, '<para>&nbsp</para>');
					dom.add(tableWrap, 'table', null,  table.innerHTML);
					dom.replace(tableWrap, table);
				}
			});
		},

		/**
		 * Converts the most common bullet and number formats in Office into a real semantic UL/LI list.
		 */
		_convertLists : function(pl, o) {
			var dom = pl.editor.dom, listElm, li, lastMargin = -1, margin, levels = [], lastType, html;

			// Convert middot lists into real semantic lists
			each(dom.select('p', o.node), function(p) {
				var sib, val = '', type, html, idx, parents, listAttr = {};

				// Get text node value at beginning of paragraph
				for (sib = p.firstChild; sib && sib.nodeType == 3; sib = sib.nextSibling)
					val += sib.nodeValue;

				val = p.innerHTML.replace(/<\/?\w+[^>]*>/gi, '').replace(/&nbsp;/g, '\u00a0');

				// Detect unordered lists look for bullets
				if (/^(__MCE_ITEM__)+[\u2022\u00b7\u00a7\u00d8o\u25CF]\s*\u00a0*/.test(val))
					type = 'bullet';

				// Detect ordered lists 1., a. or ixv.
				if (/^__MCE_ITEM__\s*\w+\.\s*\u00a0+/.test(val))
					type = 'order';
					
				// Check if node value matches the list pattern: o&nbsp;&nbsp;
				if (type) {
					margin = parseFloat(p.style.marginLeft || 0);

					if (margin > lastMargin)
						levels.push(margin);


					if (!listElm || type != lastType) {
						listAttr['list-type'] = type;
						listElm = dom.create('list', listAttr);
						dom.insertAfter(listElm, p);
					} else {
						// Nested list element
						if (margin > lastMargin) {
							listAttr['list-type'] = type;
							listElm = li.appendChild(dom.create('list', listAttr));
						} else if (margin < lastMargin) {
							// Find parent level based on margin value
							idx = tinymce.inArray(levels, margin);
							parents = dom.getParents(listElm.parentNode, 'list');
							listElm = parents[parents.length - 1 - idx] || listElm;
						}
					}

					// Remove middot or number spans if they exists
					each(dom.select('span', p), function(span) {
						var html = span.innerHTML.replace(/<\/?\w+[^>]*>/gi, '');

						// Remove span with the middot or the number
						if (type == 'bullet' && /^__MCE_ITEM__[\u2022\u00b7\u00a7\u00d8o\u25CF]/.test(html))
							dom.remove(span);
						else if (/^__MCE_ITEM__[\s\S]*\w+\.(&nbsp;|\u00a0)*\s*/.test(html))
							dom.remove(span);
					});

					html = p.innerHTML;

					// Remove middot/list items
					if (type == 'bullet')
						html = p.innerHTML.replace(/__MCE_ITEM__/g, '').replace(/^[\u2022\u00b7\u00a7\u00d8o\u25CF]\s*(&nbsp;|\u00a0)+\s*/, '');
					else
						html = p.innerHTML.replace(/__MCE_ITEM__/g, '').replace(/^\s*\w+\.(&nbsp;|\u00a0)+\s*/, '');

					// Create list-item and add paragraph data into the new list-item
					li = listElm.appendChild(dom.create('list-item', 0, html));
					dom.remove(p);

					lastMargin = margin;
					lastType = type;
				} else
					listElm = lastMargin = 0; // End list element
			});
		
			// Remove any left over makers
			html = o.node.innerHTML;
			if (html.indexOf('__MCE_ITEM__') != -1)
				o.node.innerHTML = html.replace(/__MCE_ITEM__/g, '');
		},

		/**
		 * Inserts the specified contents at the caret position.
		 */
		_insert : function(h, skip_undo) {
			var ed = this.editor, r = ed.selection.getRng();
			// First delete the contents seems to work better on WebKit when the selection spans multiple list items or multiple table cells.
			if (!ed.selection.isCollapsed() && r.startContainer != r.endContainer)
				ed.getDoc().execCommand('Delete', false, null);

			ed.execCommand('mceInsertContent', false, h, {skip_undo : skip_undo});
			
			// Sometimes, pasted content will come wrapped in a div - in tinyMCE core. 
			var rng = ed.selection.getRng();
			ed.dom.remove(ed.dom.select('div'), true);
		
			// Remove bookmark spans
			ed.dom.remove(ed.dom.select('span'), true);
			
			ed.selection.setRng(rng);
		},

		/**
		 * Instead of the old plain text method which tried to re-create a paste operation, the
		 * new approach adds a plain text mode toggle switch that changes the behavior of paste.
		 * This function is passed the same input that the regular paste plugin produces.
		 * It performs additional scrubbing and produces (and inserts) the plain text.
		 * This approach leverages all of the great existing functionality in the paste
		 * plugin, and requires minimal changes to add the new functionality.
		 * Speednet - June 2009
		 */
		_insertPlainText : function(ed, dom, h) {
			var i, len, pos, rpos, node, breakElms, before, after,
				w = ed.getWin(),
				d = ed.getDoc(),
				sel = ed.selection,
				is = tinymce.is,
				inArray = tinymce.inArray,
				linebr = getParam(ed, "paste_text_linebreaktype"),
				rl = getParam(ed, "paste_text_replacements");

			function process(items) {
				each(items, function(v) {
					if (v.constructor == RegExp)
						h = h.replace(v, "");
					else
						h = h.replace(v[0], v[1]);
				});
			};

			if ((typeof(h) === "string") && (h.length > 0)) {
				// If HTML content with line-breaking tags, then remove all cr/lf chars because only tags will break a line
				if (/<(?:p|br|h[1-6]|ul|ol|dl|table|t[rdh]|div|blockquote|fieldset|pre|address|center|para|list|list-item|sec|heading)[^>]*>/i.test(h)) {
					process([
						/[\n\r]+/g
					]);
				} else {
					// Otherwise just get rid of carriage returns (only need linefeeds)
					process([
						/\r+/g
					]);
				}

				process([
					[/<\/(?:p|h[1-6]|ul|ol|dl|table|div|blockquote|fieldset|pre|address|center|para|list|list-item|sec|heading)>/gi, "\n\n"],		// Block tags get a blank line after them
					[/<br[^>]*>|<\/tr>/gi, "\n"],				// Single linebreak for <br /> tags and table rows
					[/<\/t[dh]>\s*<t[dh][^>]*>/gi, "\t"],		// Table cells get tabs betweem them
					/<[a-z!\/?][^>]*>/gi,						// Delete all remaining tags
					[/&nbsp;/gi, " "],							// Convert non-break spaces to regular spaces (remember, *plain text*)
					[/(?:(?!\n)\s)*(\n+)(?:(?!\n)\s)*/gi, "$1"],	// Cool little RegExp deletes whitespace around linebreak chars.
					[/\n{3,}/g, "\n\n"],							// Max. 2 consecutive linebreaks
					/^\s+|\s+$/g									// Trim the front & back
				]);

				h = dom.decode(tinymce.html.Entities.encodeRaw(h));

				// Delete any highlighted text before pasting
				if (!sel.isCollapsed()) {
					d.execCommand("Delete", false, null);
				}

				// Perform default or custom replacements
				if (is(rl, "array") || (is(rl, "array"))) {
					process(rl);
				}
				else if (is(rl, "string")) {
					process(new RegExp(rl, "gi"));
				}

				// Treat paragraphs as specified in the config
				if (linebr == "none") {
					process([
						[/\n+/g, " "]
					]);
				}
				else if (linebr == "br") {
					process([
						[/\n/g, "<br />"]
					]);
				}
				else {
					process([
						/^\s+|\s+$/g,
						[/\n\n/g, "</p><p>"],
						[/\n/g, "<br />"]
					]);
				}

				// This next piece of code handles the situation where we're pasting more than one paragraph of plain
				// text, and we are pasting the content into the middle of a block node in the editor.  The block
				// node gets split at the selection point into "Para A" and "Para B" (for the purposes of explaining).
				// The first paragraph of the pasted text is appended to "Para A", and the last paragraph of the
				// pasted text is prepended to "Para B".  Any other paragraphs of pasted text are placed between
				// "Para A" and "Para B".  This code solves a host of problems with the original plain text plugin and
				// now handles styles correctly.  (Pasting plain text into a styled paragraph is supposed to make the
				// plain text take the same style as the existing paragraph.)
				if ((pos = h.indexOf("</p><p>")) != -1) {
					rpos = h.lastIndexOf("</p><p>");
					node = sel.getNode(); 
					breakElms = [];		// Get list of elements to break 

					do {
						if (node.nodeType == 1) {
							// Don't break tables and break at body
							if (node.nodeName == "TD" || node.nodeName == "BODY") {
								break;
							}

							breakElms[breakElms.length] = node;
						}
					} while (node = node.parentNode);

					// Are we in the middle of a block node?
					if (breakElms.length > 0) {
						before = h.substring(0, pos);
						after = "";

						for (i=0, len=breakElms.length; i<len; i++) {
							before += "</" + breakElms[i].nodeName.toLowerCase() + ">";
							after += "<" + breakElms[breakElms.length-i-1].nodeName.toLowerCase() + ">";
						}

						if (pos == rpos) {
							h = before + after + h.substring(pos+7);
						}
						else {
							h = before + h.substring(pos+4, rpos+4) + after + h.substring(rpos+7);
						}
					}
				}

				// Insert content at the caret, plus add a marker for repositioning the caret
				ed.execCommand("mceInsertRawHTML", false, h + '<span id="_plain_text_marker">&nbsp;</span>');

				// Reposition the caret to the marker, which was placed immediately after the inserted content.
				// Needs to be done asynchronously (in window.setTimeout) or else it doesn't work in all browsers.
				// The second part of the code scrolls the content up if the caret is positioned off-screen.
				// This is only necessary for WebKit browsers, but it doesn't hurt to use for all.
				window.setTimeout(function() {
					var marker = dom.get('_plain_text_marker'),
						elm, vp, y, elmHeight;

					sel.select(marker, false);
					d.execCommand("Delete", false, null);
					marker = null;

					// Get element, position and height
					elm = sel.getStart();
					vp = dom.getViewPort(w);
					y = dom.getPos(elm).y;
					elmHeight = elm.clientHeight;

					// Is element within viewport if not then scroll it into view
					if ((y < vp.y) || (y + elmHeight > vp.y + vp.h)) {
						d.body.scrollTop = y < vp.y ? y : y - vp.h + 25;
					}
				}, 0);
			}
		},

		/**
		 * This method will open the old style paste dialogs. Some users might want the old behavior but still use the new cleanup engine.
		 */
		_legacySupport : function() {
			var t = this, ed = t.editor;
			// Register command(s) for backwards compatibility
			ed.addCommand('AnnomcePasteWord', function() {
				ed.windowManager.open({
					file: t.url + '/pasteword.htm',
					width: 483,
					height: 450,
					inline: 1,
				});
			});
			
			if (getParam(ed, 'paste_text_use_dialog')) {
				ed.addCommand('AnnomcePasteText', function() {
					ed.windowManager.open({
						file : t.url + '/pastetext.htm',
						width: 483,
						height: 450,
						inline: 1,
					});
				});
			}

			// Register button for backwards compatibility
			ed.addButton('annopasteword', {title : 'paste.paste_word_desc', cmd : 'AnnomcePasteWord'});
			ed.addButton('annopastetext', {title : 'paste.paste_text_desc', cmd : 'AnnomcePasteText'});
		}
	});

	// Register plugin
	tinymce.PluginManager.add('annoPaste', tinymce.plugins.AnnoPaste);
})();
