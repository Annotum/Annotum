var annosource;

(function($){
	var inputs = {}, ed;

	annoSource = {
		init : function() {
			var t = this;

			inputs.dialog = $('#anno-popup-source');
			//inputs.dialog.bind('wpdialogrefresh', annoSource.refresh);
			//inputs.dialog.bind('wpdialogclose', annoSource.onClose);
			//this.editorVal = tinymce.EditorManager.activeEditor.getContent({source_view : true});

			t.codemirror = CodeMirror.fromTextArea(document.getElementById('htmlSource'), {
				lineNumbers: true,

			});

			// Prevents CodeMirror from bugging out during show/hide/resize process
			$('body').on('click', '.mce_annosource', function(e) {
				t.codemirror.refresh();
			});


			$('body').on('click', '#validation-errors a, .cm-error', function(e) {
				t.codemirror.refresh();
				e.preventDefault();
				t.codemirror.focus();
				t.codemirror.setCursor($(this).data('line'), $(this).data('col'));
				return false;
			});

			inputs.dialog.bind('wpdialogbeforeopen', annoSource.beforeOpen);
		},
		beforeOpen : function () {
			annoSource.ed = tinyMCEPopup.editor;
			annoSource.editorVal = annoSource.ed.getContent({source_view : true});
			annoSource.codemirror.setValue(annoSource.editorVal);
			annoSource._validate('');
		},
		_getEditorVal : function () {
			return  t.ed = tinyMCEPopup.editor;
		},
		_validate : function (content) {
			var t = this;
			content = '<article>'+this.editorVal+'</article>';
			jQuery.post('http://annotum.dev/wp/wp-admin/admin-ajax.php',
				{
					content: content,
					action: 'anno_validate'
				},
				function (data) {
					var errors = data.errors;
					var insertEl, msg;
					var $errorUL = jQuery('#validation-errors');
					for (var i = 0; i <= errors.length - 1; i++) {
						insertEl = document.createElement('a');
						jQuery(insertEl).text(errors[i].fullMessage).data('col', errors[i].column).data('line', errors[i].line).attr('href', '#');
						$errorUL.append($(insertEl).wrap('<li></li>').parent());

						msg = document.createElement("a");
						msg.className = 'cm-error';
						$(msg).data('col', errors[i].column).data('line', errors[i].line);
						msg.appendChild(document.createTextNode(errors[i].fullMessage));
						t.codemirror.addLineWidget(errors[i].line, msg, {coverGutter: false, noHScroll: true, above: false, handleMouseEvents: true});
					};
				},
				'json'
			);
		},
	};


	$(function(){
		annoSource.init();
	});
})(jQuery);
