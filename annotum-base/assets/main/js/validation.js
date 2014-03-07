(function($) {
	var annoValidation = {
		validate : function (content) {
			$.post(ajaxurl,
				{
					content: content,
					action: 'anno_validate'
				},
				function (data) {
					var errors;
					if (data.status == 'error') {
						errors = data.errors;
						for (var i = 0; i <= errors.length - 1; i++) {
							if (errors[i].level == 3) {
								// Status is fatal, inserting it into the editor
								// will completely break it
								data.status = 'fatal';
							}
						}
					}
					$.event.trigger('annoValidation', [data]);
				},
				'json'
			);
		}
	}
	window.annoValidation = annoValidation;
})(jQuery);
