var tinymce = null, annoSource;
var win = window.dialogArguments || opener || parent || top;
var tinyMCEPopup = win.tinyMCEPopup;
var tinymce = win.tinymce;

annoSource = {
	init : function() {
		var t = this;
		this.editorVal = tinymce.EditorManager.activeEditor.getContent({source_view : true});
		document.getElementById('htmlSource').value = this.editorVal;
		this.codemirror = CodeMirror.fromTextArea(document.getElementById('htmlSource'), {
			lineNumbers: true,
			mode: 'xml'
		});


		$('body').on('click', '#errors a, .cm-error', function(e) {
			t.codemirror.refresh();
			e.preventDefault();
			t.codemirror.focus();
			t.codemirror.setCursor($(this).data('line'), $(this).data('col'));
			return false;
		});

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
				var $errorUL = jQuery('#errors');
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

(function($){
	$(function(){
		annoSource.init();
		annoSource._validate();


		/*$('body').on('click', '#errors a', function(e) {
			e.preventDefault();
			cm.focus();
			alert($(this).data('line')-1);
			cm.setCursor($(this).data('line')-1, $(this).data('col'));
			return false;
		})*/
	});
})(jQuery);



//window.onload = function(){annoEqEdit.init();};
//annoEqEdit.preInit();
