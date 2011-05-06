jQuery(document).ready(function($) {
	annocommentsBox = {
		st : 0,

		get : function(total, num, type) {
			var st = this.st, data;
			if ( ! num )
				num = 20;

			this.st += num;
			this.total = total;
			$('#' + type + '-comment img.waiting').show();

			data = {
				'action' : 'get-comments',
				'mode' : 'single',
				'_ajax_nonce' : $('#add_comment_nonce').val(),
				'p' : $('#post_ID').val(),
				'start' : st,
				'number' : num,
				'comment_type' : 'article_' + type,
			};

			$.post(ajaxurl, data,
				function(r) {
					r = wpAjax.parseAjaxResponse(r);
					$('#' + type + '-comment .widefat').show();
					$('#' + type + '-comment img.waiting').hide();

					if ( 'object' == typeof r && r.responses[0] ) {
						$('#the-comment-list-' + type).append( r.responses[0].data );

						theList = theExtraList = null;
						$("a[className*=':']").unbind();

						if ( commentsBox.st > commentsBox.total )
							$('#show-comments-' + type).hide();
						else
							$('#show-comments-' + type).html(postL10n.showcomm);
						return;
					} else if ( 1 == r ) {
						$('#show-comments-' + type).parent().html(postL10n.endcomm);
						return;
					}

					$('#the-comment-list-' + type).append('<tr><td colspan="2">'+wpAjax.broken+'</td></tr>');
				}
			);

			return false;
		}
	};
});