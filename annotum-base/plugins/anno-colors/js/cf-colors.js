jQuery(function($) {
	var CF = CF || {};
	
	$('#cf-kuler-menu a').click(function(e) {
		$('#cf-kuler-menu a').removeClass('current');
		$(this).addClass('current');
		$swatches = $('#cf-kuler-swatch-selector');
		$swatches.html('<div class="cfcp-loading"><em>' + cf_kuler_settings.loading + '</em></div>');
		$.post(
			ajaxurl,
			{
				'action': 'cf_kuler',
				'request': $(this).attr('data-request'),
				'listType': $(this).attr('data-listtype'),
				'startIndex': $(this).attr('data-start'),
				'itemsPerPage': $(this).attr('data-items')
			},
			function(response) {
				$swatches.html(response);
				// set height to avoid the window jerk
				var wrapHeight = $swatches.height();
				$swatches.css('height', wrapHeight);
			},
			'html'
		);
		e.preventDefault();
	});
	
	$('.cf-kuler-wrap .scheme-item a').click(function() {
		var swatches = $(this).attr('data').split(',');
		var idx = 0;
		$('#cf-kuler-swatch-selected .cf-kuler-theme li').each(function(){
			$(this).attr('style', 'background:'+ swatches[idx++]);
		});
		
	});
	
	$('#cf-kuler-search-form').submit(function(e) {
		$swatches = $('#cf-kuler-swatch-selector');
		$swatches.html('<div class="cfcp-loading"><em>' + cf_kuler_settings.loading + '</em></div>');
		$.post(
			ajaxurl,
			{
				'action': 'cf_kuler',
				'request': 'search',
				'searchQuery': $(this).find('#cf_kuler_search').val(),
				'startIndex': $(this).attr('data-start'),
				'itemsPerPage': $(this).attr('data-items')
			},
			function(response) {
				$swatches.html(response);
			},
			'html'
		);
		e.preventDefault();
	});
	$('a.cf-kuler-paging').live('click', function(e) {
		$swatches = $('#cf-kuler-swatch-selector');
		$swatches.html('<div class="cfcp-loading"><em>' + cf_kuler_settings.loading + '</em></div>');
		$.post(
			ajaxurl,
			{
				'action': 'cf_kuler',
				'request': $(this).attr('data-request'),
				'listType': $(this).attr('data-listtype'),
				'searchQuery': $(this).attr('data-search'),
				'startIndex': $(this).attr('data-start'),
				'itemsPerPage': $(this).attr('data-items')
			},
			function(response) {
				$swatches.html(response);
				$.scrollTo($('#preview-selected'), 'slow');
			},
			'html'
		);
		e.preventDefault();
	});
	
	$('#cf-kuler-swatch-selector .cf-kuler-theme .cf-kuler-apply').live('click', function(e) {
// select swatch
		var $selected = $('#cf-kuler-swatch-selected');
		$selected.find('.cf-kuler-theme').html('');
		var $theme = $(this).closest('.cf-kuler-theme');
		$selected.find('.cf-kuler-theme').append($theme.find('ul').clone()).append($theme.find('p.cf-kuler-theme-description').clone());

// populate hidden field
// show save button
		$('#cf_kuler_settings_form')
			.find('#cf-kuler-theme-info').html($theme.find('.cf-kuler-theme-data').clone()).end()
			.find('#cf_kuler_colors').val($theme.attr('data-swatches')).end()
			.find('input[type=submit]').show().end();
		$('html, body').animate({scrollTop:0}, 'slow'); // scroll to top

		CF.utils.initSelectedSortable();
		e.preventDefault();
	});
	
	$('#cf_kuler_settings_form').live('submit', function() {
		// pull selected theme's swatch state
		$(this).find('#cf_kuler_colors').val(CF.utils.getThemeColors($('#cf-kuler-swatch-selected .cf-kuler-theme')));
	});
	
// Utils

	CF.utils = function($) {
		return {
			getThemeColors: function(theme) {
				var $list = $(theme).find('ul');
				var colors = [];
				$list.find('li').each(function(i) {
					colors[i] = CF.utils.rgbToHex($(this).css('backgroundColor'));
				});				
				return colors;
			},
			
			decodeEntities: function(encoded) {
				return $('<textarea />').html(encoded).val();
			},
			
			// colorpicker.js stomps on jQuery.color's getRGB implementation, so we need to duplicate this
			// Color Conversion functions from highlightFade
			// By Blair Mitchelmore
			// http://jquery.offput.ca/highlightFade/
			// Parse strings looking for color tuples [255,255,255]
			getRGB: function(color) {
				var result;

				// Check if we're already dealing with an array of colors
				if ( color && color.constructor == Array && color.length == 3 )
					return color;

				// Look for rgb(num,num,num)
				if (result = /rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(color))
					return [parseInt(result[1], 10), parseInt(result[2], 10), parseInt(result[3], 10)];

				// Look for rgb(num%,num%,num%)
				if (result = /rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(color))
					return [parseFloat(result[1])*2.55, parseFloat(result[2])*2.55, parseFloat(result[3])*2.55];

				// Look for #a0b1c2
				if (result = /#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(color))
					return [parseInt(result[1],16), parseInt(result[2],16), parseInt(result[3],16)];

				// Look for #fff
				if (result = /#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(color))
					return [parseInt(result[1]+result[1],16), parseInt(result[2]+result[2],16), parseInt(result[3]+result[3],16)];

				// Otherwise, we're most likely dealing with a named color
				return colors[jQuery.trim(color).toLowerCase()];
			},
			
			rgbToHex: function(rgb){
				if (typeof(rgb) == 'string') {
					rgb = this.getRGB(rgb);
				}
				return "#" +
					("0" + parseInt(rgb[0], 10).toString(16)).slice(-2) +
					("0" + parseInt(rgb[1], 10).toString(16)).slice(-2) +
					("0" + parseInt(rgb[2], 10).toString(16)).slice(-2);
			}
		};
	}(jQuery);
		
// Preview
	
	CF.preview = function($) {
		var colors,
			$preview = $('#cf-kuler-preview'),
			cssTemplate = cf_kuler_settings.preview_css_template;
			
		return {
			selected: null,
			
			show: function(colors, referenceObj) {
				var $referenceObj = $(referenceObj);
				var pos = $referenceObj.position();
				
				$referenceObj.closest('.cf-kuler-theme').addClass('hover').siblings('.cf-kuler-theme').removeClass('hover');
				
				this.setCssTemplate(colors);
				$preview.css({
					'left': Math.ceil(pos.left - $preview.outerWidth()) + 'px',
					'top': Math.ceil(pos.top - ($preview.outerHeight() / 2) + ($referenceObj.outerHeight() / 2) + 5 /* plus 5 because the lil' arrow isn't centered */) + 'px',
					'position': 'absolute',
					'z-index': 10
				}).show();
				
				this.selected = $referenceObj.attr('id');
			},
			
			hide: function() {
				$('.cf-kuler-theme').removeClass('hover');
				$('#cf-kuler-preview').hide();
			},
			
			toggle: function(colors, source) {
				if (!this.isVisible() || source.attr('id') != this.selected) {
					this.show(colors, source);
				}
				else {
					this.hide();
				}
			},
			
			isVisible: function() {
				return $('#cf-kuler-preview').is(':visible');
			},
			
			setCssTemplate: function(colors) {
				var _template = CF.utils.decodeEntities(cssTemplate);
				for (i in colors) {
					_template = _template.replace('-' + i + '-', colors[i]);
				}
				jQuery('style[title="kuler-preview-css"]').replaceWith(_template);
			}
		};
	}(jQuery);
	
// Color Picker

	CF.picker = function($) {
		var $swatch,
			$picker = $('#cf-kuler-color-picker'),
			currentIndex = null;
	
		return {
			config: {
				'flat': true,
				'defaultColor': '#00ff00'
			},
		
			setPickerPosition: function() {
				var pos = $swatch.position(),
					pnum = $swatch.index(),
					_left,
					_classname,
					_offset = 25;
				
				switch(true) {
					case pnum < 1: // notch on left
						_left = pos.left + ($swatch.outerWidth() / 2) - _offset - 2 + 'px';
						_classname = 'left';
						break;
// 					case pnum > 2: // notch on right
// 						_left = (pos.left + $swatch.outerWidth()) - $picker.outerWidth() + _offset + 'px';
// 						_classname = 'right';
// 						break;
					default: // notch centered
						_left = (pos.left - ($picker.outerWidth() / 2) + ($swatch.outerWidth() / 2)) + 5 + 'px';
						_classname = 'center';
				}

				
				$picker.css({
					top: (pos.top + ($swatch.outerHeight() * 0.97)) + 'px',
					left: _left
				}).attr('class', 'cfp-popover cfp-popover-top-' + _classname);

				currentIndex = pnum;
			}, 
		
			// set the swatch color - can accept either hex or jQuery rgb() string val
			setSwatchColor: function(color) {
				$swatch.css('backgroundColor', (color.length == 6 ? '#' + color : color));				
			},
			
			changePicker: function(hsb, hex, rgb) {
				this.setSwatchColor(hex);
			},
			
			submitPicker: function(hsb, hex, rgb) {
				this.setSwatchColor(hex);
				$picker.hide();
			},
		
			showPicker: function(clicked) {				
				// make sure that the picker is the first item in the popup
				if (false === $picker.find('.cfp-popover-inner :eq(2)').hasClass('colorpicker')) {
					$('.colorpicker', $picker).insertAfter($('.cfp-popover-inner :first', $picker));
				}
				
				// make sure our original swatches are set
				// @TODO don't fire this every time we open
				this.setOriginalSwatches();
				
				$swatch = $(clicked).closest('li');
				this.setPickerColor($swatch.css('backgroundColor'));
				this.setPickerPosition();
			
				$picker.show();
			},
			
			hide: function() {
				$picker.hide();
			},
			
			isVisible: function() {
				return $picker.is(':visible');
			},
		
			currentIndex: function() {
				return currentIndex;
			},

			setOriginalSwatches: function() {
				var colors = $('form#cf_kuler_settings_form .cf-kuler-theme-data[name="cf_kuler_theme[swatches]"]').val().split(',');
				$('.theme-swatches-container ul li', $picker).each(function(i) {
					$(this).css('backgroundColor', colors[i]).unbind().click(function() {
						var color = $(this).css('backgroundColor');
						CF.picker.setPickerColor(color);
						CF.picker.setSwatchColor(color);
					});
				});
			},
		
			setPickerColor: function(color) {
				// colorpicker.js is picky about what it takes, so we need to
				// translate the color to something that it understands
				color = CF.utils.getRGB(color);
				$picker.ColorPickerSetColor({r: color[0], g: color[1], b: color[2]});
			}
		};
	}(jQuery);

// Init

	// color picker init
	$('#cf-kuler-color-picker').ColorPicker({
		flat: CF.picker.config.flat,
		color: CF.picker.config.defaultColor,
		onSubmit: function(hsb, hex, rgb) {
			CF.picker.submitPicker(hsb, hex, rgb);
		},
		onChange: function(hsb, hex, rgb) {
			CF.picker.changePicker(hsb, hex, rgb);
		}
	}).live('click', function(e) {
		// what happens in Vegas stays in Vegas
		e.stopPropagation();
	});

	$('.cf-kuler-theme-edit-swatch').live('click', function(e) {
		if (CF.picker.isVisible() && $(this).closest('li').index() == CF.picker.currentIndex()) {
			CF.picker.hide();
		}
		else {
			CF.picker.showPicker(this);
		}
		
		if (CF.preview.isVisible()) {
			CF.preview.hide();
		}
		
		e.preventDefault();
		e.stopPropagation();
	});
	
	// init preview triggers
	$('.cf-kuler-apply-preview').live('click', function(e) {
		var $this = $(this);
		var colors = $(this).closest('.cf-kuler-theme').attr('data-swatches').split(',');

		CF.preview.toggle(colors, $this);
		
		if (CF.preview.isVisible()) {
			CF.picker.hide();
		}
		
		e.preventDefault();
		e.stopPropagation();
	});
	
	// omg hax!
	$('#cf-kuler-swatch-selector ul li').live('click', function(e) {
		$(this).closest('.cf-kuler-theme').find('.cf-kuler-apply-preview').trigger('click');
		e.preventDefault();
		e.stopPropagation();
	});

	$('input[name="preview_button"]').live('click', function(e) {
		var $this = $(this);		
		var colors = CF.utils.getThemeColors($('#cf-kuler-swatch-selected'));
		
		CF.preview.toggle(colors, $this);
		
		if (CF.preview.isVisible()) {
			CF.picker.hide();
		}
		
		e.preventDefault();
		e.stopPropagation();
	});
	
	// global popup neutralizer
	$('body').live('click', function() {
		CF.preview.hide();
		CF.picker.hide();
	});
	$(document).keyup(function(e) {
		switch (e.which) {
			case 27: // esc
				$('body').click();
				break;
		}
	});
	
	$('.cf-kuler-message-fade')
		.animate({'opacity': 1.0}, 8000) // faux timeout, animates nothing for 8 seconds
		.slideUp('slow');
});