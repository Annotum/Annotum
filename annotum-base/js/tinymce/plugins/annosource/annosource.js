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

			inputs.$dialog.bind('wpdialogclose', annoSource.onClose);
			t.codemirror = CodeMirror.fromTextArea(document.getElementById('htmlSource'), {
				lineNumbers: true,
			});

			// Prevents CodeMirror from bugging out during show/hide/resize process
			$('body').on('click', '.mce-i-annosource', function(e) {
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
				t.close();
			});

			$('body').on('click', t.validationStatusID + ' a, .cm-error', function(e) {
				e.preventDefault();
				t.codemirror.focus();
				t.codemirror.setCursor($(this).data('line'), $(this).data('col'));
			});

			inputs.$dialog.bind('wpdialogbeforeopen', annoSource.beforeOpen);
		},
		validate : function(content) {
			var contentType;
			if (!content) {
				var content = this._getContent();
			}
			this._cleanup();
			contentType = this._getContentType();
			// Returns a promise
			return this.validator.validate(content, contentType);
		},
		insertContent : function() {
			var xsltPromise;
			var t = this;
			var contents = {};
			contents.content = this.codemirror.getValue().trim();
			xsltPromise = annoValidation.xsltTransform(contents, 'html');
			xsltPromise.then(function(data) {
				if (data.status == 'success') {
					t.editor.setContent('<!DOCTYPE sec SYSTEM "http://dtd.nlm.nih.gov/publishing/3.0/journalpublishing3.dtd"><body>' + data.contents.content  + '</body>', {source_view : true});
				}
			});
		},
		// * Internal Helper Functions ***************
		_getContentType : function () {
			var contentType = '';
			if (!this.editor) {
				this.editor = top.tinymce.activeEditor;
			}

			if (this.editor.id == 'content') {
				contentType = 'body';
			}
			else if (this.editor.id == 'excerpt') {
				contentType = 'abstract';
			}

			return contentType;
		},
		_cleanup : function() {
			$(this.validationStatusID).html('');
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
					t.close();
				}
			}
			else if (status == 'fatal') {
				alert('The structure of the XML is broken. Please fix this and try again');
			}
			else {
				// onClose triggers cleanup
				t.insertContent();
				t.close();
			}
		},
		close : function() {
			this.editor.windowManager.close();
		},
		beforeOpen : function () {
			var t = annoSource;
			var xsltPromise;
			var contentType = t._getContentType();
			var contents = {};


			t.editor = top.tinymce.activeEditor;
			t.editorVal = t.editor.getContent({source_view : true}).replace(/^<!DOCTYPE[^>]*?>/, '');
			contents.content = t.editorVal;
			xsltPromise = annoValidation.xsltTransform(contents, 'xml');
			xsltPromise.then(function(data) {
				if (data.status == 'success') {
					t.codemirror.setValue(data.contents.content);
					$(document).on('annoValidation', t.processValidation);
					t.validate(data.contents.content, contentType);
				}
			});
		},
		onClose : function () {
			var t = annoSource;
			t._cleanup();
		}
	};

	$(function(){
		annoSource.init();
	});
})(jQuery);
