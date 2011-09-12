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
		
		$formats = array(
			'bold',
			'italic',
			'sup',
			'sub',
			'monospace',
			'underline',
		);
		
		$extended_valid_elements = array_merge(array(
			'ext-link[ext-link-type:uri|xlink::href|title]',
			'sec',
			'xref[ref-type|rid]',
			'inline-graphic[xlink::href]',
			'alt-text',
			'fig',
			'label',
			'heading',
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
			'cap',
			'disp-quote',
		), $formats);
		
		$custom_elements = array(
			'~bold',
			'~italic',
			'~sup',
			'~sub',
			'~monospace',
			'~underline',
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
		
		$formats_as_children = implode('|', $formats);
		$valid_children = array(
			'body[sec|p|media|list|disp-formula|disp-quote|fig|table-wrap|preformat]',
			'copyright-statement['.$formats_as_children.']',
			'license-p['.$formats_as_children.'|xref]',
			'heading['.$formats_as_children.']',
			'media[alt-text|long-desc|permissions]',
			'permissions[copyright-statement|copyright-holder|license]',
			'license[license-p|xref]',
			'list[title|list-item]',
			'list-item[p|xref|list]',
			'disp-formula[label|tex-math]',
			'disp-quote[p|attrib|permissions]',
			'fig[label|cap|media|img]',
			'cap[title|p|xref]',
			'table-wrap[label|cap|table|table-wrap-foot|permissions]',
			'table-wrap-foor[p]',
			'p[media|img|permissions|license|list|list-item|disp-formula|disp-quote|fig|cap|table-wrap|table-wrap-foot|h2|xref|img]',
			'sec[sec|heading|media|img|permissions|license|list|list-item|disp-formula|disp-quote|fig|cap|table-wrap|table-wrap-foot|p|h2]',
		);

		wp_tiny_mce(false, array(
			'remove_linebreaks' => false,
			'content_css' => trailingslashit(get_bloginfo('template_directory')).'css/tinymce.css',
			'extended_valid_elements' => implode(',', $extended_valid_elements),
			'custom_elements' => implode(',', $custom_elements),
			'valid_children' => implode(',', $valid_children),
			//  Defines wrapper, need to set this up as its own button.
			'formats' => '{
					bold : {\'inline\' : \'bold\'},
					italic : { \'inline\' : \'italic\'},
					underline : { \'inline\' : \'underline\'},
					sec : { \'inline\' : \'sec\', \'wrapper\' : \'false\' },
					title : { \'block\' : \'heading\' },
				}',
			'theme_advanced_blockformats' => 'Paragraph=p,Title=heading,Section=sec',
			'forced_root_block' => '',
			'editor_css' => trailingslashit(get_template_directory_uri()).'css/tinymce-ui.css?v=4',
			'debug' => 'true',
			'verify_html' => true,
			'force_p_newlines' => false,
			'force_br_newlines' => false,
// @TODO Define doctype (IE Compat?)
//			'doctype' => '<!DOCTYPE article SYSTEM \"http://dtd.nlm.nih.gov/ncbi/kipling/kipling-jp3.dtd\">',
			'doctype' => '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">',
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
		if ($this->is_article()) {
			$buttons = array('bold', 'italic', 'underline', '|', 'annoorderedlist', 'annobulletlist', '|', 'annoquote', '|', 'sup', 'sub', '|', 'charmap', '|', 'annolink', 'announlink', '|', 'annoimages', 'equation', '|', 'reference', '|', 'undo', 'redo', '|', 'wp_adv', 'help', 'annotable', );
		}
		return $buttons;
	}
	
	function mce_buttons_2($buttons) {
		if ($this->is_article()) {
			$buttons = array('formatselect', '|', 'table', 'row_before', 'row_after', 'delete_row', 'col_before', 'col_after', 'delete_col', 'split_cells', 'merge_cells', '|', 'pastetext', 'pasteword', 'annolist', '|', 'annoreferences', '|', 'annotips');
		}
		return $buttons;
	}
	
	function plugins($plugins) {
		if ($this->is_article()) {
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
		
			$plugins['annoParagraphs'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoparagraphs/editor_plugin.js';
	
			$plugins['annoTips'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annotips/editor_plugin.js';
		}
		return $plugins;
	}
	
	/**
	 * Returns whether or not the current post (post_type) is an article
	 */ 
	private function is_article() {
		global $post_type;
		return $post_type == 'article';
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
function anno_load_tinymce_plugins() {
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
			<input id="<?php echo esc_attr('reference-checkbox-'.$reference_key); ?>" type="checkbox" />
		</td>
		<td class="reference-text">
			<label for="<?php echo esc_attr('reference-checkbox-'.$reference_key); ?>">
				<?php echo $reference_key + 1; ?>. <?php echo esc_html($reference['text']); ?>
			</label>
		</td>
		<td class="reference-actions">
			<a href="#" id="<?php echo esc_attr('reference-action-edit-'.$reference_key); ?>" class="edit"><?php _ex('Edit', 'reference action', 'anno'); ?></a>
			<a href="#" id="<?php echo esc_attr('reference-action-delete-'.$reference_key); ?>" class="delete"><?php _ex('Delete', 'reference action', 'anno'); ?></a>
			<?php  wp_nonce_field('anno_delete_reference', '_ajax_nonce-delete-reference'); ?>
		</td>
	</tr>
	</table>
<?php 
}

/**
 * Row edit markup for references display in the popup Dialog for tinyMCE.
 * 
 * @param int $reference_key Key of the reference
 * @param array $reference Array of reference data
 * @param int $post_id ID of the post
 * @param bool $doi_enabled Determines if the DOI lookup should be enabled. Passed as parameter to prevent lookup on every row display
 * 
 * @return void
 */
function anno_popup_references_row_edit($reference_key, $reference, $post_id, $doi_enabled = false) {
?>
	<table id="<?php echo esc_attr('reference-edit-'.$reference_key); ?>">
		<tr>
			<td class="anno-references-edit-td" colspan="3">
				<div id="<?php echo esc_attr('#popup-message-reference-'.$reference_key); ?>"></div>
					<form id="<?php echo esc_attr('reference-form-'.$reference_key); ?>" class="anno-reference-edit">
						<div id="<?php echo esc_attr('lookup-error-'.$reference_key); ?>" class="popup-error"></div>
						<label>
						<?php
							$doi_value = $doi_enabled ? esc_attr($reference['doi']) : _x('CrossRef Credentials Required', 'disabled DOI lookup message', 'anno');
						?>
							<span><?php _ex('CrossRef DOI', 'input label for DOI lookup', 'anno'); ?></span>
							<input type="text" class="short" name="doi" id="<?php echo esc_attr('doi-'.$reference_key); ?>" value="<?php echo $doi_value; ?>"<?php disabled($doi_enabled, false, true); ?>/>
							<input type="button" name="import_doi" id="<?php echo esc_attr('doi-import-'.$reference_key); ?>" value="<?php _ex('Import', 'button label', 'anno'); ?>"<?php disabled($doi_enabled, false, true); ?>>
							<img src="<?php echo esc_url(admin_url('images/wpspin_light.gif' )); ?>" class="ajax-loading" />
							<?php wp_nonce_field('anno_import_doi', '_ajax_nonce-import-doi', false); ?>
						</label>
						<label>
							<span><?php _ex('PubMed ID (PMID)', 'input for PubMed ID lookup', 'anno'); ?></span>
							<input type="text" class="short" name="pmcid" id="<?php echo esc_attr('pmcid-'.$reference_key); ?>" value="<?php echo esc_attr($reference['pmcid']) ?>" />
							<input type="button" name="import_pubmed" id="<?php echo esc_attr('pmcid-import-'.$reference_key); ?>" value="<?php _ex('Import', 'button label', 'anno'); ?>">
							<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" />
							<?php wp_nonce_field('anno_import_pubmed', '_ajax_nonce-import-pubmed', false); ?>
						</label>
						<label>
							<span><?php _ex('Figures', 'input label for defining reference', 'anno'); ?></span>
							<select name="figureselect" id="<?php echo esc_attr('reffigures-'.$reference_key); ?>">
								<option value=""><?php _ex('Select Figure', 'select option', 'anno'); ?></option>
							</select>
						</label>
						<p>
							<textarea name="figures"></textarea>
						</p>
						<label>
							<span><?php _ex('Text', 'input label for defining tables', 'anno'); ?></span>
							<textarea type="text" name="text" id="<?php echo esc_attr('text-'.$reference_key); ?>"><?php echo esc_textarea($reference['text']) ?></textarea>
						</label>
						<label>
							<span><?php _ex('URL', 'input label for defining tables', 'anno'); ?></span>
							<input type="text" name="url" id="url" value="<?php echo esc_attr($reference['url']) ?>" />
						</label>

						<div class="reference-edit-actions clearfix">
							<?php //TODO Nonce for save ?>
							<a href="#" id="<?php echo esc_attr('reference-action-save-'.$reference_key); ?>" class="save left">Save</a>
							<a href="#" id="<?php echo esc_attr('reference-action-cancel-'.$reference_key); ?>" class="cancel right">Cancel</a>
							<input type="hidden" name="ref_id" value="<?php echo esc_attr($reference_key); ?>" />
							<input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>" />
							<input type="hidden" name="action" value="anno-reference-save" />
							<?php wp_nonce_field('anno_save_reference', '_ajax_nonce-save-reference'); ?>
						</div>
						<div class="clearfix"></div>
					</form>
				</div>
			</td>
		</tr>
	</table>
<?php 
}

/**
 * Row markup for references display in the popup Dialog for tinyMCE.
 *
 * @param int $reference_key Key of the reference
 * @param array $reference Array of reference data
 * @param int $post_id ID of the post
 * @param bool $doi_enabled Determines if the DOI lookup should be enabled. Passed as parameter to prevent lookup on every row display
 * 
 * @return void
 */
function anno_popup_reference_row($reference_key, $reference, $post_id, $doi_enabled = false) {
?>
	<tr id="<?php echo esc_attr('reference-'.$reference_key); ?>">
		<td colspan="3">
			<?php anno_popup_references_row_display($reference_key, $reference); ?>
			<?php anno_popup_references_row_edit($reference_key, $reference, $post_id, $doi_enabled); ?>
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
	$doi_enabled = anno_doi_lookup_enabled();
	if (!empty($references) && is_array($references)) {
		foreach ($references as $reference_key => $reference) {
			//prevent undefined index errors;
			$reference_keys = array('text', 'doi', 'pmcid', 'url', 'figures');
			foreach ($reference_keys as $key_val) {
				$reference[$key_val] = isset($reference[$key_val]) ? $reference[$key_val] : '';
			}
			
			anno_popup_reference_row($reference_key, $reference, $post->ID, $doi_enabled);
		}
	}
?>
					<tr id="<?php echo esc_attr('reference-new'); ?>">
						<td colspan="3">
							<?php anno_popup_references_row_edit('new', array('text' => '', 'doi' => '', 'pmcid' => '', 'url' => '', 'figures' => ''), $post->ID, $doi_enabled); ?>
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

function anno_popup_tips() {
?>
<div id="anno-popup-tips" class="anno-mce-popup">
	- Tip 1<br />
	- Tip 2
</div>
	
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
	
	<div style="display:none;">
	<?php anno_popup_tips(); ?>
	</div>
<?php 
}
add_action('after_wp_tiny_mce', 'anno_preload_dialogs', 10, 1 );


/**
 * Ajax Handler for creating/updating references
 */ 
function anno_tinymce_reference_save() {
	check_ajax_referer('anno_save_reference', '_ajax_nonce-save-reference');

	$success = true;
	$response = array();
	$messages = array();
	
	if (!isset($_POST['post_id'])) {
		$message = _x('Could not evaluate Article ID please try again.', 'error message', 'anno');
		$success = false;
	}
	else if (empty($_POST['text'])) {
		$message = _x('Text cannot be blank.', 'error message', 'anno');
		$success = false;
	}
	else if (!isset($_POST['ref_id'])) {
		$message = _x('Could not evaluate Reference ID please try again.', 'error message', 'anno');
		$success = false;
	}
	
	if ($success) {
		$reference = anno_insert_reference($_POST);
		if (!$reference) {
			$success = false;
			$message = _x('Could not save reference, please try again.', 'error message', 'anno');
		}
		else {
			$response['ref_id'] = $reference['ref_id'];
			ob_start();
				$doi_enabled = anno_doi_lookup_enabled();
				anno_popup_reference_row($reference['ref_id'], $reference, $_POST['post_id'], $doi_enabled);
				$response['markup'] = ob_get_contents();
			ob_end_clean();
			$response['ref_text'] = ($response['ref_id'] + 1).'. '.$reference['text'];
			$message =  _x('Reference Saved.', 'success message', 'anno');
		}
	}
	
	$response['message'] = $success ? 'success' : 'error';
	$response['text'] = $message;
		
	echo json_encode($response);
	die();
}
add_action('wp_ajax_anno-reference-save', 'anno_tinymce_reference_save');

function anno_tinymce_reference_delete() {
	check_ajax_referer('anno_delete_reference', '_ajax_nonce-delete-reference');
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
		'doi' => isset($ref_array['doi']) ? $ref_array['doi'] : '',
		'pmcid' => isset($ref_array['pmcid']) ? $ref_array['pmcid'] : '',
		'text' => isset($ref_array['text']) ? $ref_array['text'] : '',
		'figures' => isset($ref_array['figures']) ? $ref_array['figures'] : '',
		'url' => isset($ref_array['url']) ? $ref_array['url'] : '',
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
	// @TODO Reset our array keys in case any have become offset account for in post_content
	update_post_meta(absint($ref_array['post_id']), '_anno_references', $references);
	
	return $ref_args;
}

function anno_delete_reference($post_id, $ref_id) {
	$references = get_post_meta($post_id, '_anno_references', true);
	if (array_key_exists($ref_id, $references)) {
		unset($references[$ref_id]);
		// @TODO Reset our array keys in case any have become offset account for in post_content
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
				'size',
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


/**
 * Prior to outputting the value of the textarea, convert a couple 
 * entities that wouldn't be able to be seen, into HTML.  This is 
 * done on the fly, so that the XML stored in the DB is correct.
 *
 * @param string $content 
 * @return void
 */
function anno_process_editor_content($content) {
	phpQuery::newDocument($content);
	
	// Convert inline-graphics to <img> tags so they display
	anno_xml_to_html_replace_inline_graphics($content);
	
	// Convert caption to cap
	$content = anno_replace_caption_tag($content);

	// Convert title to heading
	$content = anno_replace_title_tag($content);
	
	// Remove p tags wrapping list items
	$content = anno_remove_p_from_list_items($content);
	
	// We need a clearfix for floated images.
	$figs = pq('fig');
	foreach ($figs as $fig) {
		$fig = pq($fig);
		
		// Add in img for display in editor
		$img_src = $fig->find('media')->attr('xlink:href');
		$fig->prepend('<img src="'.$img_src.'" />');
		
		// _mce_bogus stripped by tinyMCE on save
		$fig->append('<div _mce_bogus="1" class="clearfix"></div>');
	}
	
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
	$content = anno_to_xml($html_content);
	$content = strip_tags($content, implode('', array_unique(anno_get_dtd_valid_elements())));
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
						'<th>',
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
 * Save our appendices as HTML versions to be used on the front end when updating
 */
function anno_update_appendices_xml_as_html($meta_id, $post_id, $meta_key, $meta_value) {
	anno_save_appendices_xml_as_html($post_id, $meta_key, $meta_value);
}
add_action('update_post_metadata', 'anno_update_appendices_xml_as_html', 10, 4);

/**
 * Save our appendices as HTML versions to be used on the front end when adding for the first time
 * This function is also called when the appendices are updated, not just created
 */
function anno_save_appendices_xml_as_html($post_id, $meta_key, $meta_value) {
	if ($meta_key === '_anno_appendices') {
		if (is_array($meta_value)) {
			$meta_html = array();
			foreach ($meta_value as $appendix) {
				$meta_html[] = anno_xml_to_html($appendix);
			}
			update_post_meta($post_id, '_anno_appendices_html', $meta_html);
		}
	}
}
add_action('add_post_meta', 'anno_save_appendices_xml_as_html', 10, 3);

/**
 * Switcheroo! Raw XML content gets switched with HTML formatted content.
 * Save the raw XML to the post_content_formatted column
 * Save the formatted HTML to the post_content column
 */
function anno_insert_post_data($data, $postarr) {
	if ($data['post_type'] == 'article') {
		$content = stripslashes($data['post_content']);
		
		// Set XML as backup content. Filter markup and strip out tags not on whitelist.
		$data['post_content_filtered'] = addslashes(anno_validate_xml_content_on_save($content));
		// Set formatted HTML as the_content
		$data['post_content'] = addslashes(anno_xml_to_html($content));
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
 * 'anno_to_xml' action to allow various actions to change 
 * small specific portions of the HTML
 *
 * @see anno_xml_to_html_replace_bold() for simple example on usage
 * 
 * @param string $xml_content 
 * @return void
 */
function anno_to_xml($html_content) {
	// Load our phpQuery document up, so filters should be able to use the pq() function to access its elements
	phpQuery::newDocument($html_content);
	
	// Let our various actions alter the document into XML
	do_action('anno_to_xml', $html_content);
	
	// Return the newly formed HTML
	return phpQuery::getDocument()->__toString();
}

function anno_to_xml_replace_bold($orig_html) {
	$bold_nodes = pq('strong');
	foreach ($bold_nodes as $node) {
		$pq_node = pq($node); // Create a phpQuery object from the noe
		$pq_node->replaceWith('<bold>'.$pq_node->html().'</bold>');
	}
}
add_action('anno_to_xml', 'anno_to_xml_replace_bold');

/**
 * Change HTML inline <img> to XML <inline-graphic>
 *
 * @param string $orig_html 
 * @return void
 */
function anno_to_xml_replace_inline_graphics($orig_html) {
	$imgs = pq('img[class="_inline_graphic"]');
	foreach ($imgs as $img) {
		$img = pq($img);
		$img_src = $img->attr('src');
		$img_alt = $img->attr('alt');
		$xml = '<inline-graphic xlink:href="'.$img_src.'" ><alt-text>'.$img_alt.'</alt-text></inline-graphic>';
		$img->replaceWith($xml);
	}
}
add_action('anno_to_xml', 'anno_to_xml_replace_inline_graphics');

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
		'title' => array(
			'tag' => 'h2',
			'class' => 'title',
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
	foreach ($inline_imges as $img) {
		$img = pq($img);
		$img_src = $img->attr('xlink:href');
		if (!empty($img_src)) {
			$img = pq($img);
			$alt_text = $img->children('alt-text:first')->html();
			
			$html = '<img src="'.$img_src.'" class="_inline_graphic" alt="'.$alt_text.'" />';
			$img->replaceWith($html);
		}
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_inline_graphics');


/**
 * Replace <fig> nodes in the XML document with HTML elements
 *
 * @param string $orig_xml - Original XML, prob. shouldn't need
 * @return void
 */
function anno_xml_to_html_replace_figures($orig_xml) {
	$tpl = new Anno_Template_Utils();
	$figs = pq('fig');
	
	$count = 0;
	if (count($figs)) {
		foreach ($figs as $fig) {
		
			// Get a phpQuery obj
			$fig = pq($fig);

			// Grab our media element in the fig
			$media = pq($fig->children('media'));

			// Get some img tag properties
			$img_src = $media->attr('xlink:href');
			$alt = $media->children('alt-text')->html();
			$title = $media->children('long-desc')->html();
		
			// Build our img tag
			$img_tag = $tpl->to_tag('img', null, array(
				'src' => $img_src,
				'title' => $title,
				'alt' => $alt,
				'class' => 'photo'
			));
			
			$label = $fig->children('label')->html();
			$label = ($label ? sprintf(__('Fig. %d', 'anno'), ++$count).': '.strip_tags($label) : '');
			$label_tag = $tpl->to_tag('h2', $label, array('class' => 'label'));
			
			$cap = $fig->children('cap')->html();
			$cap_tag = $tpl->to_tag('div', $cap, array('class' => 'fn'));
			
			$permissions = $fig->find('permissions');
			$permissions_tag = anno_convert_permissions_to_html($permissions);
			$permissions->remove();
			
			$figcaption = $tpl->to_tag('figcaption', $label_tag.$cap_tag.$permissions_tag);
		
			$html = '
				<figure class="figure hmedia clearfix">
					'.$img_tag.'
					'.$figcaption.'
				</figure>';
			
			// Replace our figure with valid HTML
			$fig->replaceWith($html);
		}
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_figures');

/**
 * Change XML <sec> tags to HTML5 <section> tags
 * Run at priority 9 so we change the titles before global title changes happen.
 */
function anno_xml_to_html_replace_sec($orig_xml) {
	$sections = pq('sec');
	if (count($sections)) {
		foreach ($sections as $sec) {
			$sec = pq($sec);
			// Replace Titles
			$title = $sec->find('title,heading');
			$title->replaceWith('<h2 class="title"><span>'.$title->html().'</span></h2>');
			
			// Replace sections
			$sec->replaceWith('<section class="sec">'.$sec->html().'</section>');
		}
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_sec', 9);

/**
 * Change the XML <list> elements to proper HTML
 * 
 * @param string $orig_xml
 * @return void
 */
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
		anno_xml_to_html_iterate_list($pq_list);
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_lists');


/**
 * Do stuff to the list elements
 * 
 * @param pq obj $list
 * @return void
 */
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
	$html = '';
	$html .= '<figure class="list">';
	$html .= empty($figcaption) ? '' : '<figcaption>'.$figcaption.'</figcaption>';
	$html .= '<'.$list_type.'>'.$list->html().'</'.$list_type.'>';
	$html .= '</figure>';
	$list->replaceWith($html);
}


/**
 * Set the list items' HTML wrapper
 *
 * @param pqObj $item 
 * @return void
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


/**
 * Replace the XML tables with proper HTML
 *
 * @param string $orig_xml 
 * @return void
 */
function anno_xml_to_html_replace_tables($orig_xml) {
	/*
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
			
	<figure class="table">
		<figcaption>Lemurs vs Other Things</figcaption>
		<table>
			<caption>An overview of the situation.</caption>
			<thead>
				<tr>
					<th>Animal</th>
			<tbody>
				<tr>
					<th scope="row">Lemurs</th>
		<div class="license">
			License: <a rel="license" href="http://example.come">CC Share-alike.</a>
		</div>
	*/
	$tables = pq('table-wrap');
	foreach ($tables as $table) {
		$pq_table = pq($table);
		anno_xml_to_html_iterate_table($pq_table);
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_tables');


/**
 * Do stuff to the table-wrap elements
 * 
 * @param pq obj $table
 * @return void
 */
function anno_xml_to_html_iterate_table($table) {
	// Get table title & caption'
	$figcaption = $table->children('label:first')->html();
	$table_caption = $table->children('cap:first')->html();
	
	// Now that we have the title and caption, get rid of the elements
	$table->children('label:first')->remove();
	$table->children('cap:first')->remove();
	
	// Loop over our table header
	$theads = $table->children('thead');
	if (count($theads)) {
		foreach ($theads as $thead) {
			anno_xml_to_html_iterate_table_head(pq($thead));
		}
	}
	
	// Loop over our table body
	$tbodies = $table->children('tbody');
	if (count($tbodies)) {
		foreach ($tbodies as $tbody) {
			anno_xml_to_html_iterate_table_body(pq($tbody));
		}
	}
	
	// Replace our table-wrap with our built-out HTML
	$html = '<figure class="table">';
	$html .= empty($figcaption) ? '' : '<figcaption>'.$figcaption.'</figcaption>';
	$html .= '<table>';
	$html .= empty($table_caption) ? '' : '<caption>'.$table_caption.'</caption>';
	$html .= $table->children('table:first')->html();
	$html .= '</table>';
	$html .= '</figure><!-- /table -->';
	$table->replaceWith($html);
}


/**
 * Do stuff to the thead elements
 * 
 * @param pq obj $thead
 * @return void
 */
function anno_xml_to_html_iterate_table_head($thead) {
	// Find our thead rows
	$trs = $thead->children('tr');
	if (count($trs)) {
		foreach ($trs as $tr) {
			anno_xml_to_html_iterate_table_head_row(pq($tr));
		}
	}
}


/**
 * Do stuff to the thead->tr elements
 * 
 * @param pq obj $trow
 * @return void
 */
function anno_xml_to_html_iterate_table_head_row($trow) {
	$ths = $trow->children('th');
	if (count($ths)) {
		foreach ($ths as $th) {
			anno_xml_to_html_iterate_table_head_row_th(pq($th));
		}
	}
}

/**
 * Do stuff to the thead->tr->th elements
 * 
 * Currently just removing unnecessary tinyMCE bogus <br>s and leaving
 * the rest of it's HTML the same
 *
 * @param pq obj $th 
 * @return void
 */
function anno_xml_to_html_iterate_table_head_row_th($th) {
	$th->find('br[attr="data-mce-bogus"]')->remove();
}

/**
 * Iterate of elements of the tbody.  Not doing anything right now, just 
 * here for when we need it.
 *
 * @param pq obj $tbody 
 * @return void
 */
function anno_xml_to_html_iterate_table_body($tbody) {
	// Nothing to do here right now...just a stub function 
}

/**
 * Replace xref's that are references with a hyperlink
 *
 * @param string $orig_xml - Original XML, prob. shouldn't need
 * @return void
 */
function anno_xml_to_html_replace_references($orig_xml) {
	$references = pq('xref[ref-type="bibr"]');
	foreach ($references as $ref) {
		$ref = pq($ref);
		$ref_id = $ref->attr('rid');
		$ref->replaceWith('<sup><a class="reflink" href="#ref'.esc_attr($ref_id).'">'.esc_html($ref_id).'</a></sup>');
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_references');


/**
 * Replace external links with a hyperlink
 *
 * @param string $orig_xml - Original XML, prob. shouldn't need
 * @return void
 */
function anno_xml_to_html_replace_external_links($orig_xml) {
	$links = pq('ext-link');
	foreach ($links as $link) {
		$link = pq($link);
		if ($link->attr('ext-link-type') == 'uri') {
			$url = $link->attr('xlink:href');
			$title = $link->attr('title');
			$link->replaceWith('<a href="'.esc_url($url).'" title="'.esc_attr($title).'">'.esc_html($link->text()).'</a>');
		}
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_external_links');

/**
 * Replace disp-quotes with valid HTMl in <blockquote> style
 *
 * @param string $orig_xml - Original XML, prob. shouldn't need
 * @return void
 */
function anno_xml_to_html_replace_dispquotes($orig_xml) {
	$quotes = pq('disp-quote');
	$tpl = new Anno_Template_Utils();
	
	foreach ($quotes as $quote) {
		$quote = pq($quote);
		
		// Get our attribution
		$attrib = $quote->children('attrib:first')->html();
		$by = $tpl->to_tag('span', '&mdash;', array('class' => 'by'));
		$attrib = $attrib ? $by."\n".$attrib : '';
		$attrib_tag = $tpl->to_tag('span', $attrib, array('class' => 'attribution'));

		/* Clone our element, b/c we'll be removing all child elem's so 
		we can just get the immediate text */
		$clone = $quote->clone();
		$clone->children()->remove();
		$quote_text = $clone->text();
		$blockquote_tag = $tpl->to_tag('blockquote', esc_html($quote_text));
		
		$permissions = $quote->find('permissions');
		$permissions_tag = anno_convert_permissions_to_html($permissions);
		$permissions->remove();
		
		$quote_tag = $tpl->to_tag('div', $blockquote_tag.$attrib_tag.$permissions_tag, array('class' => 'quote'));
		
		// Do the actual HTML replacement
		$quote->replaceWith($quote_tag);
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_dispquotes');

function anno_convert_permissions_to_html($permissions_pq_obj) {
	$permissions = pq($permissions_pq_obj);
	$tpl = new Anno_Template_Utils();
	$clauses = array();
	$clause_tag = '';
	
	foreach ($permissions as $permission) {
		$permission = pq($permission);
		
		$statement = $permission->find('copyright-statement')->text();
		$holder = $permission->find('copyright-holder')->text();
		$license = $permission->find('license > license-p')->text();
		
		$copyright = '';
		if ($statement && $holder) {
			$copyright = sprintf(
				_x('%1$s, %2$s.', 'Copyright statement, plus holder. E.g. "Copyright, Me".', 'anno'),
				$statement,
				$holder
			);
		}
		else if ($statement) {
			$copyright = sprintf(
				_x('%1$s.', 'Copyright statement sans holder. E.g. "Copyright"', 'anno'),
				$statement
			);
		}
		else if ($holder) {
			$copyright = sprintf(
				_x('Copyright, %1$s.', 'Copyright sans statement. We can assume copyright.', 'anno'),
				$holder
			);
		}

		$license = ($license ? $license : '');

		$clauses[] = sprintf('%1$s'."\n".'%2$s', $copyright, $license);
	}
	
	$clauses = implode(' ', $clauses);
	$clause_tag .= $tpl->to_tag('small', $clauses, array('class' => 'license'));
	
	return $clause_tag;
}

/**
 * Convert all tags that have been loaded in the phpQuery Document to a new type
 * Assumes a phpQuery object has been loaded. Modifies this object.
 * 
 * @param string $old_tag Old tag to convert from
 * @param string $new_tag New tag to convert to 
 * @return void.
 */ 
function anno_convert_tag($old_tag, $new_tag) {
	$tags = pq($old_tag);
	foreach ($tags as $tag) {
		$tag = pq($tag);
		$tag_html = $tag->html();
		$tag->replaceWith('<'.$new_tag.'>'.$tag_html.'</'.$new_tag.'>');
	}
}

/**
 * Convert caption tag to cap tag for display in editor
 * Browsers strip caption tags not wrapped in <table> tags.
 * 
 * @param phpQueryObject $xml
 * @return void
 */
function anno_replace_caption_tag($xml) {
	anno_convert_tag('caption', 'cap');
}

/**
 * Convert cap tags to caption to match the DTD when saving editor content.
 * Browsers strip caption tags not wrapped in <table> tags.
 * 
 * @param phpQueryObject $xml
 * @return void
 */
function anno_to_xml_cap_tag($orig_xml) {
	anno_convert_tag('cap', 'caption');
}
add_action('anno_to_xml', 'anno_to_xml_cap_tag');

/**
 * Convert heading tags to title to match the DTD when saving editor content.
 * Browsers convert title content to html entities.
 * 
 * @param phpQueryObject $xml
 * @return void
 */
function anno_to_xml_heading_tag($orig_xml) {
	anno_convert_tag('heading', 'title');
}
add_action('anno_to_xml', 'anno_to_xml_heading_tag');

/**
 * Convert title tag to heading tag for display in editor
 * Browsers convert title content to html entities.
 * 
 * @param phpQueryObject $xml
 * @return void
 */
function anno_replace_title_tag($xml) {
	anno_convert_tag('title', 'heading');
}

/**
 * Remove p tags which wrap list item content so the editor can handle the 
 * unconventional xml structure as html.
 * 
 * @param phpQueryObject $xml
 * @return void
 */ 
function anno_remove_p_from_list_items($xml) {
	$list_items = pq('list-item');
	foreach ($list_items as $list_item) {
		$list_item = pq($list_item);
		$p_tags = $list_item->children('p');
		// Replace p tag with its content
		foreach ($p_tags as $p_tag) {
			$p_tag = pq($p_tag);
			$p_inner = $p_tag->html();
			$p_tag->replaceWith($p_inner);
		}
	}
}

/**
 * Add p tags which wrap list item content for valid XML on post save
 * 
 * @param phpQueryObject $xml
 * @return void
 */
function anno_to_xml_list_item_p($xml) {
	$list_items = pq('list-item');
	foreach ($list_items as $list_item) {
		$list_item = pq($list_item);
		$list_item->wrapInner('<p />');
	}
}
add_action('anno_to_xml', 'anno_to_xml_list_item_p');

/**
 * Determines whether or not a DOI lookup is feasible with the credentials given
 * 
 * @return bool
 */ 
function anno_doi_lookup_enabled() {
	// A login (optional password) is required for DOI lookup.
	$crossref_login = cfct_get_option('crossref_login');
	return !empty($crossref_login);
}

?>