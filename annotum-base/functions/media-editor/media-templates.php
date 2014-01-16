<?php
/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2011 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }
?>

<script type="text/html" id="tmpl-anno-attachment-display-settings">
<div class="setting">
	<h3><?php _e('Attachment Display Settings', 'anno'); ?></h3>
	<# if ( 'undefined' !== typeof data.sizes ) { #>
	<label>
		<span><?php _e('Display Type', 'anno'); ?></span>
		<select name="displaytype" data-setting="displaytype">
			<option value="inline"><?php _e('Inline Image', 'anno'); ?></option>
			<option value="figure"><?php _e('Figure', 'anno'); ?></option>
		</select>
	</label>
	<# } #>
	<label
	<# if ( 'undefined' !== typeof data.sizes ) { #>
		class="anno-link-to js-link-to"
	<# } #>>
		<span><?php _e('Link To', 'anno'); ?></span>
		<select class="link-to" data-setting="link"
		<# if ( data.userSettings && ! data.model.canEmbed ) { #>
			data-user-setting="urlbutton"
		<# } #>>
			<option value="file" selected>
			<# if ( data.model.canEmbed ) { #>
				<?php esc_attr_e('Link to Media File', 'anno'); ?>
			<# } else { #>
				<?php esc_attr_e('Media File', 'anno'); ?>
			<# } #>
			</option>
			<option value="post">
			<# if ( data.model.canEmbed ) { #>
				<?php esc_attr_e('Link to Attachment Page', 'anno'); ?>
			<# } else { #>
				<?php esc_attr_e('Attachment Page', 'anno'); ?>
			<# } #>
			</option>
			<# if ( 'image' === data.type ) { #>
			<option value="custom">
				<?php esc_attr_e('Custom URL', 'anno'); ?>
			</option>
			<option value="none">
				<?php esc_attr_e('None', 'anno'); ?>
			</option>
			<# } #>
		</select>
	</label>
	<input type="text" class="link-to-custom" data-setting="linkUrl" />


<# if ( 'undefined' !== typeof data.sizes ) { #>
	<label class="setting">
		<span><?php _e('Size', 'anno'); ?></span>
		<select class="size" name="size" data-setting="size"
	<# if ( data.userSettings ) { #>
			data-user-setting="imgsize"
	<# } #>>
<?php
	/** This filter is documented in wp-admin/includes/media.php */
	$sizes = apply_filters( 'image_size_names_choose', array(
		'thumbnail' => __('Thumbnail'),
		'medium'    => __('Medium'),
		'large'     => __('Large'),
		'full'      => __('Full Size'),
	) );

	foreach ( $sizes as $value => $name ) : ?>
	<#
		var size = data.sizes['<?php echo esc_js( $value ); ?>'];
	if ( size ) { #>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, 'full' ); ?>>
		<?php echo esc_html( $name ); ?> &ndash; {{ size.width }} &times; {{ size.height }}
		</option>
	<# } #>
	<?php endforeach; ?>
		</select>
	</label>

<# } #>
</div>
</script>
<script type="text/html" id="tmpl-anno-attachment-details">
	<h3>
		<?php _e('Attachment Details', 'anno'); ?>

		<span class="settings-save-status">
			<span class="spinner"></span>
			<span class="saved"><?php esc_html_e('Saved.', 'anno'); ?></span>
		</span>
	</h3>
	<div class="attachment-info">
		<div class="thumbnail">
			<# if ( data.uploading ) { #>
				<div class="media-progress-bar"><div></div></div>
			<# } else if ( 'image' === data.type ) { #>
				<img src="{{ data.size.url }}" draggable="false" />
			<# } else { #>
				<img src="{{ data.icon }}" class="icon" draggable="false" />
			<# } #>
		</div>
		<div class="details">
			<div class="filename">{{ data.filename }}</div>
			<div class="uploaded">{{ data.dateFormatted }}</div>

			<# if ( 'image' === data.type && ! data.uploading ) { #>
				<# if ( data.width && data.height ) { #>
					<div class="dimensions">{{ data.width }} &times; {{ data.height }}</div>
				<# } #>

				<# if ( data.can.save ) { #>
					<a class="edit-attachment" href="{{ data.editLink }}&amp;image-editor" target="_blank"><?php _e('Edit Image', 'anno'); ?></a>
					<a class="refresh-attachment" href="#"><?php _e('Refresh', 'anno'); ?></a>
				<# } #>
			<# } #>

			<# if ( data.fileLength ) { #>
				<div class="file-length"><?php _e('Length:', 'anno'); ?> {{ data.fileLength }}</div>
			<# } #>

			<# if ( ! data.uploading && data.can.remove ) { #>
				<a class="delete-attachment" href="#"><?php _e('Delete Permanently', 'anno'); ?></a>
			<# } #>

			<div class="compat-meta">
				<# if ( data.compat && data.compat.meta ) { #>
					{{{ data.compat.meta }}}
				<# } #>
			</div>
		</div>
	</div>

	<# var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly'; #>
		<label class="setting" data-setting="title">
			<span><?php _e('Title', 'anno'); ?></span>
			<input type="text" value="{{ data.title }}" {{ maybeReadOnly }} />
		</label>
		<label class="setting" data-setting="caption">
			<span><?php _e('Caption', 'anno'); ?></span>
			<textarea {{ maybeReadOnly }}>{{ data.caption }}</textarea>
		</label>
	<# if ( 'image' === data.type ) { #>
		<label class="setting" data-setting="alt">
			<span><?php _e('Alt Text', 'anno'); ?></span>
			<input type="text" value="{{ data.alt }}" {{ maybeReadOnly }} />
		</label>
	<# } #>
		<label class="setting" data-setting="description">
			<span><?php _e('Description', 'anno'); ?></span>
			<textarea {{ maybeReadOnly }}>{{ data.description }}</textarea>
		</label>
<# if ( 'undefined' !== typeof data.sizes ) { #>
	<label class="setting" data-setting="label">
		<span><?php _e('Label', 'anno'); ?></span>
		<input type="text" value="{{ data.annoLabel }}" />
	</label>
<div class="setting">
	<h3><?php _e('Permissions', 'anno'); ?></h3>
	<label class="setting" data-setting="license">
		<span><?php _e('License', 'anno'); ?></span>
		<input type="text" value="{{ data.annoLicense }}" />
	</label>
	<label class="setting" data-setting="cpstatement">
		<span><?php _e('Copyright Statment', 'anno'); ?></span>
		<input type="text" value="{{ data.annoCpyStatement }}" />
	</label>
	<label class="setting" data-setting="cpholder">
		<span><?php _e('Copyright Holder', 'anno'); ?></span>
		<input type="text" value="{{ data.annoCpyHolder }}" />
	</label>
</div>
<# } #>
</script>
