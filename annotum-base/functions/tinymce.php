<?php
/**
 * Load TinyMCE for the body and appendices.
 */
function anno_admin_print_footer_scripts() {
	global $post;
	if ($post->post_type == 'article') {
		$appendicies = get_post_meta($post->ID, '_anno_appendicies', true);
		if (empty($appendicies) || !is_array($appendicies)) {
			$appendicies = array(0 => '0');
		}
		wp_tiny_mce(false, array(
			'content_css' => trailingslashit(get_bloginfo('template_directory')).'/css/tinymce.css',
			'wp_fullscreen_content_css' => trailingslashit(get_bloginfo('template_directory')).'/js/tinymce/css/tinymce.css',
			'extended_valid_elements' => 'italic,underline,monospace,ext-link[ext-link-type:uri|xlink::href|title],sec,list[list-type],list-item,xref[ref-type|rid]',
			'custom_elements' => '~italic,~underline,~monospace,~ext-link,sec,list,~list-item,~xref',
			//  Defines wrapper, need to set this up as its own button.
			'formats' => '{
					bold : {\'block\' : \'sec\', \'wrapper\' : true},
					italic : { \'inline\' : \'italic\'},
					underline : { \'inline\' : \'underline\'}
				}',
			'theme_advanced_blockformats' => 'Paragraph=p,Heading=h2',
			'forced_root_block' => '',
			'editor_css' => trailingslashit(get_bloginfo('template_directory')).'/css/tinymce-ui.css?v=2',
			'debug' => 'true',
		));
?>

<script type="text/javascript">
	tinyMCE.execCommand('mceAddControl', false, 'anno-body');
<?php
		foreach ($appendicies as $key => $value) {
?>
	tinyMCE.execCommand('mceAddControl', false, 'appendix-<?php echo $key; ?>');
<?php
		}
?>
	function annoActiveEditor(selector) {
		tinyMCE.execInstanceCommand(jQuery(selector).next().find('textarea').attr('id'), 'mceFocus');
		alert(tinyMCE.activeEditor.editorId);
	}
</script>

<?php
	}
}
add_action('admin_print_footer_scripts', 'anno_admin_print_footer_scripts', 99);


class Anno_tinyMCE {
	function Anno_tinyMCE() {	
		add_filter("mce_external_plugins", array(&$this, "plugins"));
		add_filter('mce_buttons', array(&$this, 'mce_buttons'));
		add_filter('mce_buttons_2', array(&$this, 'mce_buttons_2'));
	}
	
	function mce_buttons($buttons) {
		global $post;
		if ($post->post_type == 'article') {
			$buttons = array('bold', 'italic', 'underline', '|', 'bullist', 'numlist', '|', 'blockquote', '|', 'sup', 'sub', '|', 'charmap', '|', 'annolink', 'announlink', '|', 'image', 'equation', '|', 'reference', '|', 'undo', 'redo', '|', 'wp_adv', 'help', 'annotable' );
		}
		return $buttons;
	}
	
	function mce_buttons_2($buttons) {
		global $post;
		if ($post->post_type == 'article') {
			$buttons = array('formatselect', '|', 'table', '|', 'pastetext', 'pasteword', 'annolist', '|', 'annoreferences');
		}
		return $buttons;
	}
	
	function plugins($plugins) {

		$plugins['annoLink_base'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annolink/annolink.js';
		$plugins['annoLink']  =  trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annolink/editor_plugin.js';
		
		
		$plugins['annoTable']  =  trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annotable/annotable.js';
		
		$plugins['annoReferences_base'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoreferences/annoreferences.js';
		$plugins['annoReferences']  =  trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoreferences/editor_plugin.js';

		return $plugins;
	}
}
 

function anno_load_tinymce_plugins(){
	$load = new Anno_tinyMCE();
}
if (is_admin()) {
	add_action('init', 'anno_load_tinymce_plugins');
}

function anno_popup_link() {
?>
<div id="anno-popup-link" class="anno-mce-popup">
	<form id="anno-tinymce-link-form" class="" tabindex="-1">
		<?php //TODO NONCE ?>
		<div class="anno-mce-popup-fields">
			<label>
				<span><?php _ex('URL', 'input label', 'anno'); ?></span>
				<input type="text" name="link-href" id="anno-link-url-field" />
			</label>
			<label>
				<span><?php _ex('Title', 'input label', 'anno'); ?></span>
				<input type="text" name="link-title" id="anno-link-title-field" />
			</label>
			<label>
				<span><?php _ex('Alt Text', 'input label', 'anno'); ?></span>
				<input type="text" name="link-alt" id="anno-link-alt-field" />
			</label>
		</div>
		<div class="anno-mce-popup-footer">
			<?php _anno_popup_submit_button('anno-link-submit', _x('Save', 'button value', 'anno')) ?>
		</div>
	</form>
</div>
<?php
}

function anno_popup_table() {
?>
	<div id="anno-tinymce-table" class="anno-mce-popup">
		<form id="anno-tinymce-table-form" class="" tabindex="-1">
			<?php //TODO NONCE ?>
			<div class="anno-mce-popup-fields">
				<label>
					<span><?php _ex('Label', 'input label for defining tables', 'anno'); ?></span>
					<input type="text" name="link-href" id="link-href" />
				</label>
				<label>
					<span><?php _ex('Caption', 'input label for defining tables', 'anno'); ?></span>
					<textarea name="link-title" id="link-title" rows="2"></textarea>
				</label>
				<fieldset>
				<legend><?php _ex('Table Properties', 'legend', 'anno'); ?></legend>
					<label>
						<span><?php _ex('Columns', 'input label for defining tables', 'anno'); ?></span>
						<input type="text" class="short-text" name="link-href" id="link-href" />
					</label>
					<label>
						<span><?php _ex('Rows', 'input label for defining tables', 'anno'); ?></span>
						<input type="text" class="short-text" name="link-title" id="link-title" />
					</label>
				</fieldset>
			</div>
			<div class="anno-mce-popup-footer">
				<?php _anno_popup_submit_button('anno-table-submit', _x('Save', 'button value', 'anno')); ?>
			</div>
		</form>
	</div>
<?php 
}

function anno_popup_references() {
	global $post;
	$references = get_option($post->ID, '_anno_references', true);
	$references = array(
		array(
			'text' => 'Short Text',
		),
		array(
			'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus eu turpis non neque commodo placerat a sodales dui. Praesent et eros et neque aliquam ullamcorper faucibus ut dui. Mauris ultrices tincidunt lacinia. Cras sagittis cursus tincidunt. Aliquam posuere, diam id mattis convallis, sem eros tincidunt dui, ut molestie purus orci at ligula.',
			'doi' => '123a.3'
		),
		array(
			'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
			'url' => 'http://www.google.com',
			'pcmid' => '123465',
		),
	);
?>
	<div id="anno-popup-references" class="anno-mce-popup">
		<form id="anno-tinymce-references-form" class="" tabindex="-1">
			<?php //TODO NONCE ?>
			<div class="anno-mce-popup-fields">
				<table class="anno-references">
					<tbody>
<?php
	foreach ($references as $reference_key => $reference) {
		// Prevent undefined index errors
		$reference_keys = array('text', 'doi', 'pcmid', 'url', 'figures');
		foreach ($reference_keys as $key_val) {
			$reference[$key_val] = isset($reference[$key_val]) ? $reference[$key_val] : '';
		}
								
?>
						<tr id="<?php echo esc_attr('reference-'.$reference_key); ?>">
							<td class="reference-checkbox">
								<?php echo $reference_key + 1; ?>.<input id="<?php echo esc_attr('reference-checkbox-'.$reference_key); ?>" type="checkbox" />
							</td>
							<td class="reference-text">
								<label for="<?php echo esc_attr('reference-checkbox-'.$reference_key); ?>">
									<?php echo esc_html($reference['text']); ?>
								</label>
							</td>
							<td class="reference-actions">
								<a href="#" id="<?php echo esc_attr('reference-action-edit-'.$reference_key); ?>" class="edit">Edit</a>
								<a href="#" id="<?php echo esc_attr('reference-action-delete-'.$reference_key); ?>" class="delete">Delete</a>
							</td>
						</tr>
						
						<tr>
							<td class="anno-references-edit-td" colspan="3">
								<div id="<?php echo esc_attr('anno-reference-edit-'.$reference_key); ?>" class="anno-reference-edit">
									<label>
										<span><?php _ex('DOI', 'input label for defining tables', 'anno'); ?></span>
										<input type="text" name="doi" id="doi" value="<?php echo esc_attr($reference['doi']) ?>" />
									</label>
									<label>
										<span><?php _ex('PCMID', 'input label for defining tables', 'anno'); ?></span>
										<input type="text" name="pcmid" id="pcmid" value="<?php echo esc_attr($reference['pcmid']) ?>" />
									</label>
									<label>
										<span><?php _ex('Figures', 'input label for defining tables', 'anno'); ?></span>
										<select name="figures" id="figures">
											<option value=""><?php _ex('Select Figure', 'select option', 'anno'); ?></option>
										</select>
									</label>
									<p>
										<textarea></textarea>
									</p>
									<label>
										<span><?php _ex('Text', 'input label for defining tables', 'anno'); ?></span>
										<textarea type="text" name="text" id="text"><?php echo esc_textarea($reference['text']) ?></textarea>
									</label>
									<label>
										<span><?php _ex('URL', 'input label for defining tables', 'anno'); ?></span>
										<input type="text" name="url" id="url" value="<?php echo esc_attr($reference['url']) ?>" />
									</label>

									<div class="reference-edit-actions clearfix">
										<a href="#" id="<?php echo esc_attr('reference-action-save-'.$reference_key); ?>" class="save left">Save</a>
										<a href="#" id="<?php echo esc_attr('reference-action-cancel-'.$reference_key); ?>" class="cancel right">Cancel</a>
									</div>
									<div class="clearfix"></div>
								</div>
							</td>
						</tr>
<?php
	}
?>
					</tbody>
				</table>	
			</div>
			<div class="anno-mce-popup-footer">
				<?php _anno_popup_submit_button('anno-references-submit', _x('Insert Reference(s)', 'button value', 'anno')) ?>
			</div>
		</form>
	</div>
<?php
}

function anno_popup_images() {
	global $post;
	$attachments = get_posts(array(
		'post_type' => 'attachment',
		'posts_per_page' => -1,
		'post_parent' => $post->ID,
		'post_mime_type' => 'image',
	));
		
	// Get attachments
	// Do something
?>
<div id="anno-popup-images" class="anno-mce-popup">
	<div class="anno-mce-popup-fields">
		<ul class="anno-images">
<?php
	foreach ($attachments as $attachment_key => $attachment) {
		$img_url_small = wp_get_attachment_image_src($attachment->ID, 'anno_img_list');
		$img_url = wp_get_attachment_image_src($attachment->ID, 'anno_img_edit');
?>
	<li id="<?php echo esc_attr('img-'.$attachment_key); ?>">
		<a href="<?php echo esc_url($img_url_small[0]); ?>" alt="<?php echo esc_attr($attachment->post_title); ?>" />
		<?php echo esc_html($attachment->post_title); ?> <span id="<?php esc_attr('img-action-show-'.$attachment->ID); ?>" class="img-action-show">
	</li>
	
	<li class="<?php esc_attr('img-edit-'.$attachment->ID); ?>">
		<img src="<?php echo esc_url($img_url[0]); ?>" alt="<?php echo esc_attr($attachment->post_title); ?>" />
		<label for="<?php esc_attr('img-alttext-'.$attachment->ID); ?>">
			<input name="<?php esc_attr('img-alttext-'.$attachment->ID); ?>" type="text" id="<?php esc_attr('img-alttext-'.$attachment->ID); ?>" />
		</label>
		<label for="<?php esc_attr('img-description-'.$attachment->ID); ?>">
			<textarea name="<?php esc_attr('img-description-'.$attachment->ID); ?>" id="<?php esc_attr('img-description-'.$attachment->ID); ?>"></textarea>
		</label>
		<fieldset>
			<legend><?php _ex('Display', 'legend', 'anno'); ?></legend>
			<label for="<?php esc_attr('img-displayfigure-'.$attachment->ID); ?>">
				<span><?php _ex('Display as Figure', 'input label', 'anno'); ?></span>
				<input type="radio" name="<?php esc_attr('img-display-'.$attachment->ID); ?>" id="<?php esc_attr('img-displayfigure-'.$attachment->ID); ?>" />
			</label>
			<label for="<?php esc_attr('img-displayinline-'.$attachment->ID); ?>">
				<span><?php _ex('Display Inline', 'input label', 'anno'); ?></span>
				<input type="radio" name="<?php esc_attr('img-display-'.$attachment->ID); ?>" id="<?php esc_attr('img-displayinline-'.$attachment->ID); ?>" />
			</label>
			<label for="<?php esc_attr('img-alttext-'.$attachment->ID); ?>">
				<span><?php _ex('Copyright Statment', 'input label', 'anno'); ?></span>
				<input type="text" name="<?php esc_attr('img-alttext-'.$attachment->ID); ?>" id="<?php esc_attr('img-alttext-'.$attachment->ID); ?>" />
			</label>
			<label for="<?php esc_attr('img-label-'.$attachment->ID); ?>">
				<span><?php _ex('Label', 'input label', 'anno'); ?></span>
				<input type="text" name="<?php esc_attr('img-label-'.$attachment->ID); ?>" id="<?php esc_attr('img-label-'.$attachment->ID); ?>" />
			</label>
			<label for="<?php esc_attr('img-label-'.$attachment->ID); ?>">
				<span><?php _ex('Caption', 'input label', 'anno'); ?></span>
				<textarea id="<?php esc_attr('img-label-'.$attachment->ID); ?>" name="<?php esc_attr('img-label-'.$attachment->ID); ?>"></textarea>
			</label>
		</fieldset>
		<fieldset id="<?php echo esc_attr('img-permissions-'.$post->ID); ?>" class="img-permissions">
			<legend><?php _ex('Permissions', 'legend', 'anno'); ?></legend>
			<label for="<?php esc_attr('img-alttext-'.$attachment->ID); ?>">
				<span><?php _ex('Copyright Statment', 'input label', 'anno'); ?></span>
				<input type="text" name="<?php esc_attr('img-alttext-'.$attachment->ID); ?>" id="<?php esc_attr('img-alttext-'.$attachment->ID); ?>" />
			</label>
			<label for="<?php esc_attr('img-alttext-'.$attachment->ID); ?>">
				<span><?php _ex('Copyright Holder', 'input label', 'anno'); ?></span>
				<input type="text" name="<?php esc_attr('img-alttext-'.$attachment->ID); ?>" id="<?php esc_attr('img-alttext-'.$attachment->ID); ?>" />
			</label>
			<label for="<?php esc_attr('img-alttext-'.$attachment->ID); ?>">
				<span><?php _ex('License', 'input label', 'anno'); ?></span>
				<input type="text" name="<?php esc_attr('img-alttext-'.$attachment->ID); ?>" id="<?php esc_attr('img-alttext-'.$attachment->ID); ?>" />
			</label>
		</fieldset>
		INSERT IMAGE
	</li>
<?php
	}
?>
		</ul>
	</div>
	<div class="anno-mce-popup-footer">
		<?php _anno_popup_submit_button('anno-image-submit', _x('INSERT', 'button value', 'anno')); ?>
	</div>
</div>
<?php
}

function _anno_popup_submit_button($id, $value) {
?>
	<input id="<?php echo esc_attr($id); ?>" type="button" class="button" value="<?php echo esc_attr($value); ?>" />
<?php
}


function anno_preload_dialogs($init) {
?>
	<div style="display:none;">
	<?php anno_popup_link(); ?>
	</div>

	<div style="display:none;">
	<?php anno_popup_references(); ?>
	</div>

	<div style="display:none;">
	<?php anno_popup_table(); ?>
	</div>
	
	<div style="display:none;">
	<?php anno_popup_images(); ?>
	</div>
<?php 
}
add_action('after_wp_tiny_mce', 'anno_preload_dialogs', 10, 1 );

?>
