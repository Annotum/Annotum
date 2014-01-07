/**
 * CF Image Library Filter
 */
(function($) {
	$(function() {
		var media = wp.media;
		if (!media || !media.view || !media.view.Settings.AttachmentDisplay) {
			return;
		}
		console.log(media.model);
		// Supercede the default AttachmentsBrowser view
		var AttachmentDisplay = media.view.Settings.AttachmentDisplay;
		media.view.Settings.AttachmentDisplay = AttachmentDisplay.extend({
			className: 'attachment-display-settings',
			template:  media.template('anno-attachment-display-settings')
		});



		var AttachmentDetails = media.view.Attachment.Details;
		media.view.Attachment.Details = AttachmentDetails.extend({
			template:  media.template('anno-attachment-details')
		});
	});
})(jQuery);
