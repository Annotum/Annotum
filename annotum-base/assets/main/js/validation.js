(function($) {
	var annoValidation = {
		validate : function (content) {
			var promise;
			promise = $.post(ajaxurl,
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

			return promise;
		},
		validateAll : function(body, abstract) {
			var promise;
			promise = $.post(ajaxurl,
				{
					body: body,
					abstract : abstract,
					action: 'anno_validate_all'
				},
				function (data) {
					$.event.trigger('annoValidationAll', [data]);
				},
				'json'
			);

			return promise;
		}
	}
	window.annoValidation = annoValidation;
})(jQuery);
