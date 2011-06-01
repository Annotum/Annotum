/**
 * Contexe - Contextual Execution
 * http://github.com/gordonbrander/contexe
 * @author Gordon Brander
 * @version 0.1
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 */
(function(d,e){Contexe=function(b){var a=b||"html",c=Contexe;if(!(this instanceof c))return a=c.ins,typeof a[b]==="undefined"&&(a[b]=new c(b)),a[b];a==="html"?a=e.getElementsByTagName("html")[0]:a==="body"&&(a=e.getElementsByTagName("body")[0]);this.el=a};Contexe.ins=[];Contexe.prototype={classReg:function(b){return RegExp("(^|\\s+)"+b+"(\\s+|$)")},hasClass:function(b,a){return this.classReg(a).test(b.className)},given:function(b,a,c){this.hasClass(this.el,b)&&a.apply(null,c);return this}};d.Contexe=Contexe;return d})(this,document);

/**
 * Annotum module
 * @author Crowd Favorite
 */
var Anno = {};

Anno.init = function() {
	var Exe = Contexe,
		con = Anno.contexts;
		
	con.common();
	Exe('body').given('home', con.home);
};

Anno.util = (function() {
	/* convertEntities is defined in l10n.js and handles JS-safe to HTML
	entity conversion. All translated strings used for HTML should be run through it. */
	var e = convertEntities;
	
	return {
		_ents: e,
		
		/* A dump sprintf-style function that only does positionals and doesn't do
		data types oustide of string. e.g. %1$s %2$s */
		formattedReplace: function (str, replacements) {
			var matches = str.match(/%([0-9])\$s/g);
			if (matches !== null) {
				for (var i = replacements.length - 1; i >= 0; i--){
					str = str.replace(matches[i], replacements[i]);
				};
			};
			return str;
		},
		
		/* Convert entities and translate strings */
		_l: function (str, replacements) {
			str = this._ents(str);
			return this.formattedReplace(str, replacements);
		}
	};
})();

/* Contextual code execution on DOMReady.
Put global code in common() */
Anno.contexts = (function () {
	var $ = jQuery,
		util = Anno.util,
		/* A dictionary for localizing strings in PHP.
		ANNO_DICTIONARY is populated using wp_localize_script in functions.php.*/
		lang = ANNO_DICTIONARY;

	return {
		common: function() {
			$('input[placeholder],textarea[placeholder]').placeholder();
			$('.widget-recent-posts .tabs').tabs();
		},

		home: function () {
			var $cycler = $('#home-featured'),
				$pagination = $cycler.find('.control-panel'),
				$controls = $('<div class="control-panel" />'),
				$numbers = $('<div class="numbers"></div>'),
				$prev = $('<a class="previous imr">'+util._ents(lang.previous)+'</a>'),
				$next = $('<a class="next imr">'+util._ents(lang.next)+'</a>'),
				numbering;

			$controls
				.append($next)
				.append($prev)
				.append($numbers);

			$cycler.append($controls);

			numbering = function (currSlideElement, nextSlideElement, options, forwardFlag) {
				var $items = $cycler.find('li'),
					i = $items.index(nextSlideElement),
					xofy = util._l(lang.xofy, [i + 1, $items.length]);
				$numbers.html(xofy);
			};

			$cycler.find('ul').cycle({
				timeout: 0,
				next: $next,
				prev: $prev, 
				fit: true,
				before: numbering
			});
		}
	};
})();

// Run on DOMReady
jQuery(function() {
	Anno.init();
});
