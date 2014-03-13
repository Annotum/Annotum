(function() {
	tinymce.create('tinymce.plugins.annoImages', {
		init : function(ed, url) {
			// Register example button
			ed.addButton('annoimages', {
				//removing for temp fix-- title : ed.getLang('advanced.link_desc'),
				title : 'Insert Image',
				cmd : 'WP_Medialib'
			});

			ed.addShortcut('alt+shift+a', ed.getLang('advanced.link_desc'), 'WP_Medialib');

			ed.plugins.textorum.addFilter('after_loadFromText', function(value) {
				var $ = jQuery,
					$value = $('<div/>').append($(value));

					// media element in a fig
					$value.find('.media').each(function() {
						var $media = $(this),
							img_url = $media.attr('xlink:href'),
							img = new Image();

						img.src = img_url;
						img.setAttribute('data-mce-src', img_url);
						img.setAttribute('alt', $media.find('alt-text').text());
						$media.closest('.fig').prepend(img);
					});

					// inline-graphics
					$value.find('.inline-graphic').each(function() {
						var $inline = $(this),
							img_url = $inline.attr('xlink:href');
							img = new Image();

						img.src = img_url;
						img.className = '_inline_graphic';
						img.setAttribute('alt', $inline.attr('alt-text'));
						$inline.append(img);
					});

				return $value.html();
			});
		},
		getInfo : function() {
			return {
				longname : 'Annotum Image Dialog',
				author : 'Crowd Favorite',
				authorurl : 'http://crowdfavorite.com',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('annoImages', tinymce.plugins.annoImages);
})();


