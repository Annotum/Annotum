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
 */
var Anno = function($, Exe) {
	/* A dictionary for localizing strings in PHP.
	ANNO_DICTIONARY is populated using wp_localize_script in functions.php.*/
	var lang = ANNO_DICTIONARY;
	return {
		/* Run this method first */
		init: function() {
			this.everywhere();
			
			Exe('body')
				.given('home', this.home);
		},
		
		everywhere: function() {
			$('input[placeholder],textarea[placeholder]').placeholder();
			$('.widget-recent-posts .tabs').tabs();
		},
		
		home: function () {
			var $cycler = $('#home-featured'),
				$pagination = $cycler.find('.control-panel'),
				$controls = $('<div class="control-panel" />'),
				$prev = $('<a class="previous imr">'+lang.previous+'</a>'),
				$next = $('<a class="next imr">'+lang.next+'</a>');
				
			$controls
				.append($next)
				.append($prev);
			
			$cycler.append($controls);
			
			$cycler.find('ul').cycle({
				'next': $next,
				'prev': $prev
			});
		}
	};
}(jQuery, Contexe);

// Run on DOMReady
jQuery(function() {
	Anno.init();
});