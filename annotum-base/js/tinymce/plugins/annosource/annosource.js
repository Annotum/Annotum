var annosource;

(function($){
	var inputs = {};

	annoSource = {
		init : function() {
			var t = this;
			t.widgets = [];
			inputs.$dialog = $('#anno-popup-source');
			inputs.$validate = $('#anno-source-validate');
			inputs.$insert = $('#anno-source-insert');
			inputs.$close = $('#anno-source-close');
			inputs.$all = $('.js-source-button');
			t.validationStatusID = '#validation-status';

			t.validator = window.annoValidation;

			//inputs.dialog.bind('wpdialogrefresh', annoSource.refresh);
			inputs.$dialog.bind('wpdialogclose', annoSource.onClose);

			t.codemirror = CodeMirror.fromTextArea(document.getElementById('htmlSource'), {
				lineNumbers: true,
				//theme: 'elegant'
			});

			// Prevents CodeMirror from bugging out during show/hide/resize process
			$('body').on('click', '.mce_annosource', function(e) {
				e.preventDefault();
				t.codemirror.refresh();
			});

			// Re-evaluate the current xml
			inputs.$validate.on('click', function(e) {
				e.preventDefault();
				inputs.$all.prop('disabled', true);
				$(document).on('annoValidation', t.processValidation);
				t.validate().then(function(){
					inputs.$all.prop('disabled', false);
				});
			});

			// Insert the current XML back into the dom
			inputs.$insert.on('click', function(e) {
				e.preventDefault();
				$(document).on('annoValidation', t.processValidation);
				$(document).on('annoValidation', t.insertAlert);
				inputs.$all.prop('disabled', true);
				t.validate().then(function(){
					inputs.$all.prop('disabled', false);
				});
			});

			// Close the xml editor without inserting content
			inputs.$close.on('click', function(e) {
				e.preventDefault();
				tinyMCEPopup.close();
			});

			$('body').on('click', t.validationStatusID + ' a, .cm-error', function(e) {
				e.preventDefault();
				t.codemirror.focus();
				t.codemirror.setCursor($(this).data('line'), $(this).data('col'));
			});

			inputs.$dialog.bind('wpdialogbeforeopen', annoSource.beforeOpen);
		},
		validate : function(content) {
			if (!content) {
				content = this._getContent();
			}
			this._cleanup();
			content = this._prepContent(content);
			// Returns a promise
			return this.validator.validate(content);
		},
		insertContent : function() {
			this.editor.setContent('<!DOCTYPE sec SYSTEM "http://dtd.nlm.nih.gov/publishing/3.0/journalpublishing3.dtd">' + this.codemirror.getValue(), {source_view : true});
		},
		// * Internal Helper Functions ***************
		_prepContent : function (content) {
			if (this.editor.id == 'content') {
				content = '<body>'+content+'</body>';
			}
			else {
				content = '<abstract>'+content+'</abstract>';
			}
			return content;
		},
		_cleanup : function() {
			$(this.validationStatusID).html('');

			// Loop through the widgets, removing them
			for (var i = this.widgets.length - 1; i >= 0; i--) {
				this.widgets[i].clear();
			};
		},
		_getContent : function() {
			return this.codemirror.getValue();
		},
		// * Event Callbacks ***************
		processValidation : function(e, data) {
			var t = annoSource, widget, errors = data.errors, insertEl, msg, $statusUL = $(t.validationStatusID);
			$(document).off('annoValidation', t.processValidation);
			if (data.status == 'error' || data.status == 'fatal') {
				for (var i = 0; i <= errors.length - 1; i++) {
					// Insert error at top of editor
					insertEl = document.createElement('a');
					$(insertEl).text(errors[i].fullMessage).data('col', errors[i].column).data('line', errors[i].line).attr('href', '#');
					$statusUL.append($(insertEl).wrap('<li></li>').parent());

					// Insert error directly into the editor itself
					// msg = document.createElement("a");
					// msg.className = 'cm-error';
					// $(msg).data('col', errors[i].column).data('line', errors[i].line);
					// msg.appendChild(document.createTextNode(errors[i].fullMessage));
					// widget = t.codemirror.addLineWidget(errors[i].line, msg, {coverGutter: false, noHScroll: true, above: false, handleMouseEvents: true});
					// t.widgets.push(widget);

				};
			}
			else if (data.status == 'success') {
				insertEl = document.createElement('li');
				$(insertEl).text(data.message);
				$statusUL.append($(insertEl));
			}
		},
		insertAlert : function(e, data) {
			var t = annoSource, status = data.status;
			$(document).off('annoValidation', t.insertAlert);
			if (status == 'error') {
				if (confirm('There are validation errors still. Are you sure you want to insert this content?')) { //@TODO i18n
					// onClose triggers cleanup
					t.insertContent();
					tinyMCEPopup.close();
				}
			}
			else if (status == 'fatal') {
				alert('The structure of the XML is broken. Please fix this and try again');
			}
			else {
				// onClose triggers cleanup
				t.insertContent();
				tinyMCEPopup.close();
			}
		},
		beforeOpen : function () {
			var t = annoSource;
			t.editor = tinyMCEPopup.editor;
			t.editorVal = t.editor.getContent({source_view : true}).replace(/^<!DOCTYPE[^>]*?>/, '');
			t.codemirror.setValue(t.editorVal);
			$(document).on('annoValidation', t.processValidation);
			t.validate(t.editorVal);
		},
		onClose : function () {
			var t = annoSource;
			t._cleanup();
			// Insert code back into the editor,
			// Add doctype back in
			//t.editor.setContent('<!DOCTYPE sec SYSTEM "http://dtd.nlm.nih.gov/publishing/3.0/journalpublishing3.dtd">' + t.codemirror.getValue(), {source_view : true});
		}
	};

	$(function(){
		annoSource.init();
	});
})(jQuery);
