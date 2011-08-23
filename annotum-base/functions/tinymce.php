<?php
/**
 * Load TinyMCE for the body and appendices.
 */
function anno_admin_print_footer_scripts() {
	global $post;
	if (isset($post->post_type) && $post->post_type == 'article') {
		// Remove the WP image edit plugin
		add_filter('tiny_mce_before_init', 'anno_tiny_mce_before_init');
		$appendices = get_post_meta($post->ID, '_anno_appendices', true);
		if (empty($appendices) || !is_array($appendices)) {
			$appendices = array(0 => '0');
		}
		
		$extended_valid_elements = array(
			'italic',
			'underline',
			'monospace',
			'bold',
			'ext-link[ext-link-type:uri|xlink::href|title]',
			'sec',
			'xref[ref-type|rid]',
			'inline-graphic[xlink::href]',
			'alt-text',
			'fig',
			'label',
			'title',
			'media[xlink::href]',
			'long-desc',
			'permissions',
			'copyright-statement',
			'copyright-holder',
			'license[license-type:creative-commons]',
			'license-p',
			'table-wrap',
			'disp-quote',
			'attrib',
			'list[list-type]',
			'list-item',
			//TODO caption
			'cap',
			'disp-quote',
		);
		
		$custom_elements = array(
			'~bold',
			'~italic',
			'~underline',
			'~monospace',
			'~ext-link',
			'~xref',
			'~inline-graphic',
			'~alt-text',
			'~label',
			'~long-desc',
			'~copyright-statement',
			'~copyright-holder',
			'~license',
			'~license-p',
			'~disp-quote',
			'~attrib',
			'sec',
			'list',
			'list-item',
			'fig',
			'title',
			'media',
			'permissions',
			'table-wrap',
			'cap',
			'disp-quote',
		);
				
		$valid_child_elements = array(			
			'media[alt-text|long-desc|permissions]',
			'permissions[copyright-statement|copyright-holder|license]',
			'license[license-p|xref]',
			'list[title|list-item]',
			'list-item[p|xref|list]',
			'disp-formula[label|tex-math]',
			'disp-quote[p|attrib|permissions]',
			'p[xref]',
			'fig[label|caption|media]',
			'caption[title|p]',
			'table-wrap[label|caption|table|table-wrap-foot|permissions]',
			'table-wrap-foor[p]',
			'p[media|img|permissions|license|list|list-item|disp-formula|disp-quote|fig|caption|table-wrap|table-wrap-foot|h2]',
			'sec[media|img|permissions|license|list|list-item|disp-formula|disp-quote|fig|caption|table-wrap|table-wrap-foot|p|h2]',
		);

		wp_tiny_mce(false, array(
			'remove_linebreaks' => false,
			'content_css' => trailingslashit(get_bloginfo('template_directory')).'css/tinymce.css',
			'extended_valid_elements' => implode(',', $extended_valid_elements),
			'custom_elements' => implode(',', $custom_elements),
			'valid_child_elements' => implode(',', $valid_child_elements),
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
			'verify_html' => false,
			'force_p_newlines' => true,
			'force_br_newlines' => false,
		));
?>

<script type="text/javascript">
	<?php 
	// Initialize tinyMCE on the anno-body element
	?>
	tinyMCE.execCommand('mceAddControl', false, 'anno-body');
	
	<?php
	// Loop over each appendix and initialize tinyMCE on each one as well
	foreach ($appendices as $key => $value) {
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
		add_filter("mce_external_plugins", array(&$this, 'plugins'));
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
		
		//$plugins['annoParagraphs'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoparagraphs/editor_plugin.js';

		return $plugins;
	}
}
/**
 * Remove the wpeditimage plugin from tinyMCE plugins. This prevents edit image buttons popping up on hover
 */
function anno_tiny_mce_before_init($init_array) {
	if (isset($init_array['plugins'])) {
		$init_array['plugins'] = str_replace('wpeditimage,', '', $init_array['plugins']);
		$init_array['plugins'] = str_replace('wpeditimage', '', $init_array['plugins']);
	}
	
	return $init_array;
}

/**
 * Load Annotum tinyMCE plugins
 */
function anno_load_tinymce_plugins(){
	$load = new Anno_tinyMCE();
}
if (is_admin()) {
	add_action('init', 'anno_load_tinymce_plugins');
}

/**
 * Popup Dialog for linking in the tinyMCE
 */
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

/**
 * Popup Dialog for table insertion in the tinyMCE
 */
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


/**
 * Row brief markup for references display in the popup Dialog for tinyMCE.
 */
function anno_popup_references_row_display($reference_key, $reference) {
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

/**
 * Row edit markup for references display in the popup Dialog for tinyMCE.
 */
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


/**
 * Row markup for references display in the popup Dialog for tinyMCE.
 */
function anno_popup_reference_row($reference_key, $reference, $post_id) {
?>
	<tr id="<?php echo esc_attr('reference-'.$reference_key); ?>">
		<td colspan="3">
			<?php anno_popup_references_row_display($reference_key, $reference); ?>
			<?php anno_popup_references_row_edit($reference_key, $reference, $post_id); ?>
		</td>
	</tr>
<?php
}

/**
 * Popup dialog for references in the tinyMCE
 */
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

/**
 * Popup dialog for quote insertion in the tinyMCE
 */
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
			<?php _anno_popup_submit_button('anno-quote-submit', _x('Insert', 'button value', 'anno')); ?>
		</div>
	</form>
</div>
	
<?php
}

/**
 * Markup for insertion/save buttons in popup dialogs for the tinyMCE
 */
function _anno_popup_submit_button($id, $value, $type = 'button') {
?>
	<input id="<?php echo esc_attr($id); ?>" type="<?php echo esc_attr($type); ?>" class="button" value="<?php echo esc_attr($value); ?>" />
<?php
}

/**
 * Markup for the tinyMCE dialog popups
 */ 
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
				'copyright_statement',
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


function anno_process_editor_content($content) {

	$doc = phpQuery::newDocument($content);
	phpQuery::selectDocument($doc); 
	
	$inline_imges = pq('inline-graphic');
	$inline_imges->each(function($img) {	
		$img = pq($img);
		$img_src = $img->attr('xlink:href');

		if (!empty($img_src)) {
			$img = pq($img);
			$alt_text = $img->children('alt-text:first');
			$html = '<img src="'.$img_src.'" class="_inline_graphic" alt="'.$alt_text.'" />';
			$img->replaceWith($html);
		}
	});
	
	// We need a clearfix for floated images.
	$figs = pq('fig');
	$figs->each(function($fig) {
		$fig = pq($fig);
		
		// Add in img for display in editor
		$img_src = $fig->find('media')->attr('xlink:href');
		$fig->prepend('<img src="'.$img_src.'"');
		
		// _mce_bogus stripped by tinyMCE on save
		$fig->append('<div _mce_bogus="1" class="clearfix"></div></fig>');
	});
	
	return phpQuery::getDocument();
}


/**
 * Validates the XML to only allow tags defined in the DTD
 *
 * @param string $html_content 
 * @return string - DTD valid XML
 */
function anno_validate_xml_content_on_save($html_content) {
	// Strip all tags not defined by DTD
	$content = strip_tags(anno_html_to_xml($html_content), implode('', array_unique(anno_get_dtd_valid_elements())));
	return $content;
}

function anno_get_dtd_valid_elements() {
	// Build big list of valid XML elements (listed in DTD)
	// @TODO remove reference to CF SVN
	// @see https://svn.crowdfave.org/svn/crowdfavorite/active/solvitor/notes/Annotum%20DTD.xml
	$tags = array(
		// Formats
		'<bold>',
		'<italic>',
		'<sup>',
		'<sub>',
		'<monospace>',
		'<underline>',
		
		// Inlines
		'<named-content>',
		'<ext-link>',
		'<inline-graphic>',
			'<alt-text>',
		
		// Paragraph-level
		'<media>',
			'<alt-text>',
			'<long-desc>',
			'<permissions>',
				'<copyright-statement>',
				'<copyright-holder>',
				'<license>',
					'<license-p>',
						'<xref>',
		'<list>',
			'<title>',
			'<list-item>',
				'<p>',
					'<xref>',
				'<list>', // allow nested lists
		'<disp-formula>',
			'<label>',
			'<tex-math>',
		'<disp-quote>',
			'<p>',
				'<xref>',
			'<attrib>',
			'<permissions>',
				'<copyright-statement>',
				'<copyright-holder>',
				'<license>',
					'<license-p>',
						'<xref>',
		'<fig>',
			'<label>',
			'<caption>',
				'<title>',
				'<p>',
					'<xref>',
			'<media>',
				'<alt-text>',
				'<long-desc>',
				'<permissions>',
					'<copyright-statement>',
					'<copyright-holder>',
					'<license>',
						'<license-p>',
							'<xref>',
		'<table-wrap>',
			'<label>',
			'<caption>',
				'<title>',
				'<p>',
					'<xref>',
				'<media>',
					'<alt-text>',
					'<long-desc>',
				'<table>',
					'<thead>',
						'<tr>',
							'<td>',
								'<xref>',
					'<tbody>',
						'<tr>',
							'<td>',
								'<xref>',
				'<table-wrap-foot>',
					'<p>',
						'<xref>',
				'<permissions>',
					'<copyright-statement>',
					'<copyright-holder>',
					'<license>',
						'<license-p>',
							'<xref>',
					
		'<preformat>',
		
		// @TODO the <article> XML elements
		'<article>',
			'<p>',
			'<sec>',
			'<title>',
	);
	return apply_filters('anno_valid_dtd_elements', $tags);
}

/**
 * Take the XML in the post_content, and convert to HTML which
 * is then stored as post_meta
 *
 * @param int $post_id
 * @param obj $post - The actual $post object
 */
function anno_save_xml_as_html_post_meta($post_id, $post) {
	if ($post->post_type == 'article' && isset($post->post_content)) {
		// in goes the XML, out comes the HTML
		update_post_meta($post_id, '_anno_article_html', anno_xml_to_html($post->post_content));
	}
	return $data;
}
//add_action('save_post', 'anno_save_xml_as_html_post_meta', null, 2);

/**
 * Switcheroo! Raw XML content gets switched with HTML formatted content.
 * Save the raw XML to the post_content_formatted column
 * Save the formatted HTML to the post_content column
 */
function anno_insert_post_data($data, $postarr) {
	if ($data['post_type'] == 'article') {
		// Set XML as backup content. Filter markup and strip out tags not on whitelist.
		$data['post_content_filtered'] = addslashes(anno_validate_xml_content_on_save(stripslashes($data['post_content'])));
		// Set formatted HTML as the_content
		$data['post_content'] = anno_xml_to_html($data['post_content']);
	}
	return $data;
}
add_filter('wp_insert_post_data', 'anno_insert_post_data', null, 2);

/**
 * Swap real HTML post content with XML formatted post content for editing
 */
function anno_edit_post_content($content, $id) {
	$post = get_post( $id );
	if ( $post && $post->post_type == 'article' ) {
		$content = $post->post_content_filtered;
	}
	return $content;
}
add_filter( 'edit_post_content', 'anno_edit_post_content', 10, 2 );

function anno_edit_post_content_filtered( $content, $id ) {
	return $content;
}
add_filter( 'edit_post_content_filtered', 'anno_edit_post_content_filtered', 10, 2 );

/**
 * Utility function to convert our HTML into XML
 * By default, this doesn't do anything by itself, but it runs the 
 * 'anno_html_to_xml' action to allow various actions to change 
 * small specific portions of the HTML
 *
 * @see anno_xml_to_html_replace_bold() for simple example on usage
 * 
 * @param string $xml_content 
 * @return void
 */
function anno_html_to_xml($html_content) {
	// Load our phpQuery document up, so filters should be able to use the pq() function to access its elements
	phpQuery::newDocument($html_content);
	
	// Let our various actions alter the document into XML
	do_action('anno_html_to_xml', $html_content);
	
	// Return the newly formed HTML
	return phpQuery::getDocument()->__toString();
}

function anno_html_to_xml_replace_bold($orig_html) {
	$bold_nodes = pq('strong');
	foreach ($bold_nodes as $node) {
		$pq_node = pq($node); // Create a phpQuery object from the noe
		$pq_node->replaceWith('<bold>'.$pq_node->html().'</bold>');
	}
}
add_action('anno_html_to_xml', 'anno_html_to_xml_replace_bold');


/**
 * Change HTML <img> to XML <inline-graphic>
 *
 * @param string $orig_html 
 * @return void
 */
function anno_html_to_xml_replace_img($orig_html) {
	$imgs = pq('img');
	$imgs->each(function($img) {
		$img = pq($img);
	 	$img_class = pq($img)->attr('class');
		if (!empty($img_class) && $img_class == '_inline_graphic') {
			$img_src = $img->attr('src');
			$img_alt = $img->attr('alt');
			$xml = '<inline-graphic xlink:href="'.$img_src.'" ><alt-text>'.$img_alt.'</alt-text></inline-graphic>';
			$img->replaceWith($xml);
		}
	});
}
add_action('anno_html_to_xml', 'anno_html_to_xml_replace_img');


/**
 * Utility function to convert our XML into HTML
 * By default, this doesn't do anything by itself, but it runs the 
 * 'anno_xml_to_html' action to allow various actions to change 
 * small specific portions of the XML
 *
 * @see anno_xml_to_html_replace_bold() for simple example on usage
 * 
 * @param string $xml_content 
 * @return void
 */
function anno_xml_to_html($xml_content) {
	// Load our phpQuery document up, so filters should be able to use the pq() function to access its elements
	phpQuery::newDocument($xml_content);
	
	// Let our various actions alter the document into HTML
	do_action('anno_xml_to_html', $xml_content);
	
	// Return the newly formed HTML
	return phpQuery::getDocument()->__toString();
}


/**
 * Loop over each formatting tag and do the proper HTML replacement
 *
 * @param string $orig_xml 
 * @return void
 */
function anno_xml_to_html_replace_formatting($orig_xml) {
	$mapping = array(
		'bold' => array(
			'tag' => 'strong',
			'class' => '',
		),
		'italic' => array(
			'tag' => 'em',
			'class' => '',
		),
		'underline' => array(
			'tag' => 'mark',
			'class' => 'underline',
		),
		'monospace' => array(
			'tag' => 'code',
			'class' => '',
		),
		'sup' => array(
			'tag' => 'sup',
			'class' => '',
		),
		'sub' => array(
			'tag' => 'sub',
			'class' => '',
		),
	);
	foreach ($mapping as $format => $html_info) {
		$nodes = pq($format);
		foreach ($nodes as $node) {
			// Get our HTML information from the mapping array
			extract($html_info);
			
			// Build our class string if we need to
			$class = empty($class) ? '' : ' class="'.$class.'"';
			
			$pq_node = pq($node); // Create a phpQuery object from the node
			$pq_node->replaceWith('<'.$tag.$class.'>'.$pq_node->html().'</'.$tag.'>');
		}
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_formatting');

/**
 * Replace inline graphics in the XML document with HTML elements
 *
 * @param string $orig_xml - Original XML, prob. shouldn't need
 * @return void
 */
function anno_xml_to_html_replace_inline_graphics($orig_xml) {
	$inline_imges = pq('inline-graphic');
	$inline_imges->each(function($img) {	
		$img = pq($img);
		$img_src = $img->attr('xlink:href');

		if (!empty($img_src)) {
			$img = pq($img);
			$alt_text = $img->children('alt-text:first');
			$html = '<img src="'.$img_src.'" class="_inline_graphic" alt="'.$alt_text.'" />';
			$img->replaceWith($html);
		}
	});
}
// add_action('anno_xml_to_html', 'anno_xml_to_html_replace_inline_graphics');


/**
 * Replace <fig> nodes in the XML document with HTML elements
 *
 * @param string $orig_xml - Original XML, prob. shouldn't need
 * @return void
 */
function anno_xml_to_html_replace_figures($orig_xml) {
	// We need a clearfix for floated images.
	$figs = pq('fig');
	$figs->each(function($fig) {
		$fig = pq($fig);
		
		// Add in img for display in editor
		$img_src = $fig->find('media')->attr('xlink:href');
		$fig->prepend('<img src="'.$img_src.'"');
		
		// _mce_bogus stripped by tinyMCE on save
		$fig->append('<div _mce_bogus="1" class="clearfix"></div></fig>');
	});
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_figures');

function anno_xml_to_html_replace_lists($orig_xml) {
	/*
	'<list>',
		'<title>',
		'<list-item>',
			'<p>',
				'<xref>',
			'<list>', // allow nested lists
			
	<figure>
		<figcaption>
		<ul/ol>
			<li>
	
	*/
	$lists = pq('list');
	foreach ($lists as $list) {
		$pq_list = pq($list);
		$pq_list->replaceWith(anno_xml_to_html_iterate_list($pq_list));
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_lists');

function anno_xml_to_html_iterate_list($list) {
	// Get list type
	$list_type = $list->attr('list-type');
	$list_type = ($list_type == 'order') ? 'ol' : 'ul';

	// Get list title if there is one
	$figcaption = $list->find('title:first')->html();
	
	// Now that we have the title, get rid of the element
	$list->find('title:first')->remove();

	// Loop over our items
	$items = $list->children('list-item');
	if (count($items)) {
		foreach ($items as $item) {
			$pq_item = pq($item);
			anno_xml_to_html_iterate_list_item($pq_item);
		}
	}
	
	// Replace our list with our built-out HTML
	$html = '<figure>';
	$html .= empty($figcaption) ? '' : '<figcaption>'.$figcaption.'</figcaption>';
	$html .= '<'.$list_type.'>'.$list->html().'</'.$list_type.'>';
	$list->replaceWith($html);
}

/**
 * phpQuery set the 
 *
 * @param string $item 
 * @return void
 * @author Crowd Favorite
 */
function anno_xml_to_html_iterate_list_item($item) {
	$child_list = $item->find('list');
	if (!empty($child_list->elements)) {
		foreach ($child_list->elements as $list) {
			anno_xml_to_html_iterate_list(pq($list));
		}
	}
	else {
		$item->replaceWith('<li>'.$item->html().'</li>');
	}
}
?>
