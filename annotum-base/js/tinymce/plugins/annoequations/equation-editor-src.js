/**
 * Display an equation editor form. 
 * Must be used or compiled with the Closure base
 * http://code.google.com/closure/
 *
 * Closure builder command (or something similar):
 * `closure-library/closure/bin/build/closurebuilder.py \
 * --root=closure-library/ \
 * --root=equation-editor/ \
 * --output_mode=compiled \
 * --compiler_jar=compiler.jar \
 * --namespace=goog.ui.annotum.equation.TexPane > equation-editor-compiled.js`
 */
goog.provide('goog.ui.annotum.equation.TexPane');

goog.require('goog.ui.equation.EquationEditorDialog');

goog.ui.annotum.equation.TexPane = function() {}

goog.ui.annotum.equation.TexPane.prototype.render = function() {
	this.context_ = {};
  	this.context_.paletteManager = new goog.ui.equation.PaletteManager();

	this.texEditor = new goog.ui.equation.TexPane(this.context_, '');

    this.texEditor.render();

	goog.dom.classes.add(this.texEditor.element_, 'annotum-eq-wrapper');
};

(function($) {
	$(document).ready(function() {
		var eqPane = new goog.ui.annotum.equation.TexPane();
		eqPane.render();
		$('.annotum-eq-wrapper').prependTo('#anno-popup-equations .anno-mce-popup-fields');
		// Show the div, #anno-popup-equations should be hidden as well
		eqPane.texEditor.setVisible(true);
	});
})(jQuery);