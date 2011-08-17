<?php
/**
 * Load TinyMCE for the body and appendices.
 */
function anno_admin_print_footer_scripts() {
	global $post;
	if (isset($post->post_type) && $post->post_type == 'article') {
		// Remove the WP image edit plugin
		add_filter('tiny_mce_before_init', 'anno_tiny_mce_before_init');
		$appendicies = get_post_meta($post->ID, '_anno_appendicies', true);
		if (empty($appendicies) || !is_array($appendicies)) {
			$appendicies = array(0 => '0');
		}
		
		$extendec_valid_elements = 
'italic,underline,monospace,bold,ext-link[ext-link-type:uri|xlink::href|title],sec,xref[ref-type|rid],inline-graphic[xlink::href],alt-text,fig,label,title,media[xlink::href],long-desc,permissions,copyright-statement,copyright-holder,license[license-type:creative-commons],license-p,table-wrap,disp-quote,attrib,list[list-type],list-item,cap';
		
		$custom_elements =  '~bold~italic,~underline,~monospace,~ext-link,~xref,~inline-graphic,~alt-text,~label,~long-desc,~copyright-statement,~copyright-holder,~license,~license-p,~disp-quote,~attrib'.'sec,list,list-item,fig,title,media,permissions,table-wrap,~caption';
		
		$valid_child_elements = '';
		
		$valid_elements = 
		
		wp_tiny_mce(false, array(
			'content_css' => trailingslashit(get_bloginfo('template_directory')).'css/tinymce.css',
			'extended_valid_elements' => $extendec_valid_elements,
			'custom_elements' => $custom_elements,
			//  Defines wrapper, need to set this up as its own button.
			'formats' => '{
					bold : {\'inline\' : \'bold\'},
					italic : { \'inline\' : \'italic\'},
					underline : { \'inline\' : \'underline\'},
					sec : { \'block\' : \'sec\', \'wrapper\' : \'true\' },
				}',
			'theme_advanced_blockformats' => 'Paragraph=p,Heading=h2,Section=sec',
			'forced_root_block' => '',
			'editor_css' => trailingslashit(get_bloginfo('template_directory')).'/css/tinymce-ui.css?v=2',
			'debug' => 'true',
			'valid_child_elements' => 'p[table],ul[list-item],ol[list-item],list[list-item],list[title]'.
			'fig[img|media|caption],caption[title]',
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
		remove_filter('tiny_mce_before_init', 'anno_tiny_mce_before_init');
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
			$buttons = array('bold', 'italic', 'underline', '|', 'annoorderedlist', 'annobulletlist', '|', 'annoquote', '|', 'sup', 'sub', '|', 'charmap', '|', 'annolink', 'announlink', '|', 'annoimages', 'equation', '|', 'reference', '|', 'undo', 'redo', '|', 'wp_adv', 'help', 'annotable', );
		}
		return $buttons;
	}
	
	function mce_buttons_2($buttons) {
		global $post;
		if ($post->post_type == 'article') {
			$buttons = array('formatselect', '|', 'table', 'row_before', 'row_after', 'delete_row', 'col_before', 'col_after', 'delete_col', 'split_cells', 'merge_cells', '|', 'pastetext', 'pasteword', 'annolist', '|', 'annoreferences', '|');
		}
		return $buttons;
	}
	
	function plugins($plugins) {

		$plugins['annoLink_base'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annolink/annolink.js';
		$plugins['annoLink']  =  trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annolink/editor_plugin.js';
				
		$plugins['annoReferences_base'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoreferences/annoreferences.js';
		$plugins['annoReferences']  =  trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoreferences/editor_plugin.js';

		$plugins['annoImages_base'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoimages/annoimages.js';
		$plugins['annoImages']  =  trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoimages/editor_plugin.js';
		
		$plugins['annoTable'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annotable/editor_plugin.js';
		$plugins['annoTable_base'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annotable/annotable.js';
		
		$plugins['annoQuote'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoquote/editor_plugin.js';
		$plugins['annoQuote_base'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoquote/annoquote.js';
		
		$plugins['annoLists'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annolists/editor_plugin.js';
		
//		$plugins['annoSection'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annosection/editor_plugin.js';

		return $plugins;
	}
}
 
function anno_tiny_mce_before_init($init_array) {
	if (isset($init_array['plugins'])) {
		$init_array['plugins'] = str_replace('wpeditimage,', '', $init_array['plugins']);
		$init_array['plugins'] = str_replace('wpeditimage', '', $init_array['plugins']);
	}
	
	return $init_array;
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
	<div id="anno-popup-table" class="anno-mce-popup">
		<form id="anno-tinymce-table-form" class="" tabindex="-1">
			<?php //TODO NONCE ?>
			<div class="anno-mce-popup-fields">
				<form>
					<label for="table-label">
						<span><?php _ex('Label', 'input label for defining tables', 'anno'); ?></span>
						<input type="text" name="label" id="table-label" />
					</label>
					<label for="table-caption">
						<span><?php _ex('Caption', 'input label for defining tables', 'anno'); ?></span>
						<textarea name="caption" id="table-caption" rows="2"></textarea>
					</label>
					<fieldset>
					<legend><?php _ex('Table Properties', 'legend', 'anno'); ?></legend>
						<label for="table-cols">
							<span><?php _ex('Columns', 'input label for defining tables', 'anno'); ?></span>
							<input type="text" class="short-text" name="cols" id="table-cols" />
						</label>
						<label for="table-rows">
							<span><?php _ex('Rows', 'input label for defining tables', 'anno'); ?></span>
							<input type="text" class="short-text" name="rows" id="table-rows" />
						</label>
					</fieldset>
				</form>
			</div>
			<div class="anno-mce-popup-footer">
				<?php _anno_popup_submit_button('anno-table-submit', _x('Insert', 'button value', 'anno')); ?>
			</div>
		</form>
	</div>
<?php 
}

function anno_popup_references_row_top($reference_key, $reference) {
?>
	<table>
	<tr>
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
	</table>
<?php 
}

function anno_popup_references_row_edit($reference_key, $reference, $post_id) {
?>
	<table id="<?php echo esc_attr('reference-edit-'.$reference_key); ?>">
		<tr>
			<td class="anno-references-edit-td" colspan="3">
				<div id="<?php echo esc_attr('#popup-message-reference-'.$reference_key); ?>"></div>
					<form id="<?php echo esc_attr('reference-form-'.$reference_key); ?>" class="anno-reference-edit">
						<label>
							<span><?php _ex('DOI', 'input label for defining tables', 'anno'); ?></span>
							<input type="text" name="doi" id="<?php echo esc_attr('doi-'.$reference_key); ?>" value="<?php echo esc_attr($reference['doi']) ?>" />
							</label>
						<label>
							<span><?php _ex('PCMID', 'input label for defining tables', 'anno'); ?></span>
							<input type="text" name="pcmid" id="<?php echo esc_attr('pcmid-'.$reference_key); ?>" value="<?php echo esc_attr($reference['pcmid']) ?>" />
						</label>
						<label>
							<span><?php _ex('Figures', 'input label for defining tables', 'anno'); ?></span>
							<select name="figureselect" id="<?php echo esc_attr('reffigures-'.$reference_key); ?>">
								<option value=""><?php _ex('Select Figure', 'select option', 'anno'); ?></option>
							</select>
						</label>
						<p>
							<textarea name="figures"></textarea>
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
						<input type="hidden" name="ref_id" value="<?php echo esc_attr($reference_key); ?>" />
						<input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>" />
						<input type="hidden" name="action" value="anno-reference-save" />
					</form>
				</div>
			</td>
		</tr>
	</table>
<?php 
}

function anno_popup_reference_row($reference_key, $reference, $post_id) {
?>
	<tr id="<?php echo esc_attr('reference-'.$reference_key); ?>">
		<td colspan="3">
			<?php anno_popup_references_row_top($reference_key, $reference); ?>
			<?php anno_popup_references_row_edit($reference_key, $reference, $post_id); ?>
		</td>
	</tr>
<?php
}

function anno_popup_references() {
	global $post;
	$references = get_post_meta($post->ID, '_anno_references', true);
	
?>
	<div id="anno-popup-references" class="anno-mce-popup">
		<?php //TODO NONCE ?>
		<div class="anno-mce-popup-fields">
			<table id="anno-references">
				<thead>
					<tr>
						<th class="reference-checkbox"></th>
						<th class="reference-text"></th>
						<th class="reference-actions"></th>
					</tr>
				</thead>
				<tbody>
<?php
	if (!empty($references) && is_array($references)) {
		foreach ($references as $reference_key => $reference) {
			//prevent undefined index errors;
			$reference_keys = array('text', 'doi', 'pcmid', 'url', 'figures');
			foreach ($reference_keys as $key_val) {
				$reference[$key_val] = isset($reference[$key_val]) ? $reference[$key_val] : '';
			}
			
			anno_popup_reference_row($reference_key, $reference, $post->ID);
		}
	}
?>
					<tr id="<?php echo esc_attr('reference-new'); ?>">
						<td colspan="3">
							<?php anno_popup_references_row_edit('new', array('text' => '', 'doi' => '', 'pcmid' => '', 'url' => '', 'figures' => ''), $post->ID); ?>
						</td>
					<tr>
						<td colspan="3" class="anno-mce-popup-footer">
							<?php _anno_popup_submit_button('anno-references-new', _x('New Reference', 'button value', 'anno')); ?>
						</td>
					</tr>
				</tbody>
			</table>	
		</div>
		<div class="anno-mce-popup-footer">
			<?php _anno_popup_submit_button('anno-references-submit', _x('Insert Reference(s)', 'button value', 'anno')); ?>
		</div>
	</div>
<?php
}

function anno_popup_quote() {
?>
<div id="anno-popup-quote" class="anno-mce-popup">
	<form id="anno-popup-quote-form" class="" tabindex="-1">
		<?php //TODO NONCE ?>
		<div class="anno-mce-popup-fields">
				<label for="quote-text">
					<span><?php _ex('Text', 'input label for defining quotes', 'anno'); ?></span>
					<input type="text" name="text" id="quote-text" />
				</label>
				<label for="quote-attribution">
					<span><?php _ex('Attribution', 'input label for defining quotes', 'anno'); ?></span>
					<input type="text" name="attribution" id="quote-attribution" />
				</label>
				<fieldset>
					<legend><?php _ex('Permissions', 'legend', 'anno'); ?></legend>
					<label for="quote-copy-statement">
						<span><?php _ex('Copyright Statement', 'input label for defining quotes', 'anno'); ?></span>
						<input type="text" name="copyright-statement" id="quote-copy-statement" />
					</label>
					<label for="quote-copy-holder">
						<span><?php _ex('Copyright Holder', 'input label for defining quotes', 'anno'); ?></span>
						<input type="text" name="copyright-holder" id="quote-copy-holder" />
					</label>
					<label for="quote-license">
						<span><?php _ex('License', 'input label for defining quotes', 'anno'); ?></span>
						<input type="text" name="license" id="quote-license" />
					</label>
				</fieldset>
			</form>
		</div>
		<div class="anno-mce-popup-footer">
			<?php _anno_popup_submit_button('anno-quote-submit', _x('Save', 'button value', 'anno')); ?>
		</div>
	</form>
</div>
	
	
<?php
}

function _anno_popup_submit_button($id, $value, $type = 'button') {
?>
	<input id="<?php echo esc_attr($id); ?>" type="<?php echo esc_attr($type); ?>" class="button" value="<?php echo esc_attr($value); ?>" />
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
	
	<div style="display:none;">
	<?php anno_popup_quote(); ?>
	</div>
	
	
<?php 
}
add_action('after_wp_tiny_mce', 'anno_preload_dialogs', 10, 1 );


/**
 * Ajax Handler for creating/updating references
 */ 
function anno_tinymce_reference_save() {

	$success = true;
	$response = array();
	$messages = array();
	
	if (!isset($_POST['post_id'])) {
		$messages[] = _x('Could not evaluate Article ID please try again.', 'error message', 'anno');
		$success = false;
	}
	
	if (!isset($_POST['text'])) {
		$messages[] = _x('Text cannot be blank.', 'error message', 'anno');
		$success = false;
	}
	
	if (!isset($_POST['ref_id'])) {
		$response['message'][] = _x('Could not evaluate Reference ID please try again.', 'error message', 'anno');
		$success = false;
	}
	
	if ($success) {
		$reference = anno_insert_reference($_POST);
		$response['ref_id'] = $reference['ref_id'];
		ob_start();
			anno_popup_reference_row($reference['ref_id'], $reference, $_POST['post_id']);
			$response['ref_markup'] = ob_get_contents();
		ob_end_clean();
		$response['text'] = $reference['text'];
		$messages[] =  _x('Reference Saved.', 'success message', 'anno');
	}
	
	$response['code'] = $success ? 'success' : 'error';
	$response['message'] = implode($messages, '<br />');

	
	echo json_encode($response);
	die();
}
add_action('wp_ajax_anno-reference-save', 'anno_tinymce_reference_save');

function anno_tinymce_reference_delete() {
	echo json_encode(anno_delete_reference($_POST['post_id'], $_POST['ref_id']));
	die();
}
add_action('wp_ajax_anno-reference-delete', 'anno_tinymce_reference_delete');

/**
 * Updates or creates a new reference for a given post. 
 * New posts should be passed in without a 'ref_id' key, or with 'new' as a value
 * 
 * @param array $ref_array Array of reference data
 * @return mixed bool|Array array of the updated/created reference false otherwise
 */ 
function anno_insert_reference($ref_array) {
	if (!isset($ref_array['post_id']) || !isset($ref_array['text'])) {
		return false;
	}
		
	$ref_args = array(
		'doi' => $ref_array['doi'],
		'pcmid' => $ref_array['pcmid'],
		'text' => $ref_array['text'],
		'figures' => $ref_array['figures'],
		'url' => $ref_array['url'],
	);
	
	$references = get_post_meta(absint($ref_array['post_id']), '_anno_references', true);
	
	// Do new item
	if (!isset($ref_array['ref_id']) || $ref_array['ref_id'] == 'new') {
		$references[] = $ref_args;
		// Grab the key of our newly created reference
		$ref_args['ref_id'] = array_pop(array_keys($references));
	}
	else if (array_key_exists(absint($ref_array['ref_id']), $references)) {
		$references[absint($ref_array['ref_id'])] = $ref_args;
		$ref_args['ref_id'] = absint($ref_array['ref_id']);
	}
	else {
		return false;
	}
	// TODO Reset our array keys in case any have become offset account for in post_content
	update_post_meta(absint($ref_array['post_id']), '_anno_references', $references);
	
	return $ref_args;
}

function anno_delete_reference($post_id, $ref_id) {
	$references = get_post_meta($post_id, '_anno_references', true);
	if (array_key_exists($ref_id, $references)) {
		unset($references[$ref_id]);
		// TODO Reset our array keys in case any have become offset account for in post_content
		update_post_meta($post_id, '_anno_references', $references);
		return true;
	} 
	return false;
}

function anno_tinymce_image_save() {
	//TODO Nonce
	if (isset($_POST['attachment_id'])) {
		$attachment = get_post($_POST['attachment_id']);
		if (!empty($attachment)) {
			$attachment->post_content = isset($_POST['description']) ? $_POST['description'] : '';
			$attachment->post_excerpt = isset($_POST['caption']) ? $_POST['caption'] : '';
			// Pass in as array to prevent double escaping
			wp_update_post((array) $attachment);

			$meta_fields = array(
				'alt_text',
				'display',
				'label',
				'copyright_statment',
				'copyright_holder',
				'license',
			);
			foreach ($meta_fields as $meta_field) {
				$meta_value = isset($_POST[$meta_field]) ? $_POST[$meta_field] : '';
				switch ($meta_field) {
					case 'alt_text':
						update_post_meta($attachment->ID, '_wp_attachment_image_alt', $meta_value);
						break;
					case 'display':
						if (empty($_POST['display'])) {
							$meta_value = 'figure';
						}
						update_post_meta($attachment->ID, '_anno_attachment_image_display', $meta_value);
						break;
					default:
						update_post_meta($attachment->ID, '_anno_attachment_image_'.$meta_field, $meta_value);
						break;
				}
			}
		//todo Update success response
		}
	}
	
	die();
}
add_action('wp_ajax_anno-img-save', 'anno_tinymce_image_save');

?>
