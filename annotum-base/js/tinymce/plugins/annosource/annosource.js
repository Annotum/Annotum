var annosource;

(function($){
	var inputs = {};

	annoSource = {
		init : function() {
			var t = this;
			t.widgets = [];
			inputs.dialog = $('#anno-popup-source');
			inputs.validate = $('#anno-source-validate');
			inputs.insert = $('#anno-source-insert');
			inputs.close = $('#anno-source-close');
			t.validationStatusID = '#validation-status';

			//inputs.dialog.bind('wpdialogrefresh', annoSource.refresh);
			inputs.dialog.bind('wpdialogclose', annoSource.onClose);

			t.codemirror = CodeMirror.fromTextArea(document.getElementById('htmlSource'), {
				lineNumbers: true,
				//theme: 'elegant'
			});

			// Prevents CodeMirror from bugging out during show/hide/resize process
			$('body').on('click', '.mce_annosource', function(e) {
				e.preventDefault();
				t.codemirror.refresh();
			});
			inputs.validate.on('click', function(e) {
				e.preventDefault();
				t.validate();
			});
			inputs.insert.on('click', function(e) {
				e.preventDefault();
				tinyMCEPopup.close();
			});
			inputs.close.on('click', function(e) {
				e.preventDefault();
				inputs.dialog.unbind('wpdialogclose', annoSource.onClose);
				tinyMCEPopup.close();
				t._cleanup();
			});

			$('body').on('click', t.validationStatusID + ' a, .cm-error', function(e) {
				t.codemirror.refresh();
				e.preventDefault();
				t.codemirror.focus();
				t.codemirror.setCursor($(this).data('line'), $(this).data('col'));
				return false;
			});

			inputs.dialog.bind('wpdialogbeforeopen', annoSource.beforeOpen);
		},
		beforeOpen : function () {
			annoSource.editor = tinyMCEPopup.editor;
			annoSource.editorVal = annoSource.editor.getContent({source_view : true});
			annoSource.editorVal = annoSource.editorVal.replace(/^<!DOCTYPE[^>]*?>/, '');
			annoSource.codemirror.setValue(annoSource.editorVal);
			annoSource._validate(annoSource.editorVal);
		},
		onClose : function () {
			var t = annoSource;
			t._cleanup();
			// Insert code back into the editor,
			// Add doctype back in
			t.editor.setContent('<!DOCTYPE sec SYSTEM "http://dtd.nlm.nih.gov/publishing/3.0/journalpublishing3.dtd">' + t.codemirror.getValue(), {source_view : true});

		},
		validate : function() {
			this._cleanup();
			this._validate(this.codemirror.getValue());
		},
		_validate : function (content) {
			var t = this;
			content = '<body>'+content+'</body>';
			$.post(ajaxurl,
				{
					content: content,
					action: 'anno_validate'
				},
				function (data) {
					var widget, errors = data.errors, insertEl, msg, $statusUL = $(t.validationStatusID);
					if (data.status == 'error') {
						for (var i = 0; i <= errors.length - 1; i++) {
							insertEl = document.createElement('a');
							$(insertEl).text(errors[i].fullMessage).data('col', errors[i].column).data('line', errors[i].line).attr('href', '#');
							$statusUL.append($(insertEl).wrap('<li></li>').parent());

							msg = document.createElement("a");
							msg.className = 'cm-error';
							$(msg).data('col', errors[i].column).data('line', errors[i].line);
							msg.appendChild(document.createTextNode(errors[i].fullMessage));
							widget = t.codemirror.addLineWidget(errors[i].line, msg, {coverGutter: false, noHScroll: true, above: false, handleMouseEvents: true});
							t.widgets.push(widget);
						};
					}
					else {
						insertEl = document.createElement('li');
						$(insertEl).text('Validation Successful');
					}
				},
				'json'
			);
		},
		_cleanup : function() {
			$(this.validationStatusID).html('');

			// Loop through the widgets, removing them
			for (var i = this.widgets.length - 1; i >= 0; i--) {
				this.widgets[i].clear();
			};
		}
	};

	$(function(){
		annoSource.init();
	});
})(jQuery);
