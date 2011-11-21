<?php

/**
 * Set acceptable custom tags for wp_kses
 */ 
$allowedposttags = array_merge($allowedposttags, array(
	'alt-text' => array(),
	'attrib' => array(),
	'bold' => array(),
	'cap' => array(),
	'copyright-statement' => array(),
	'copyright-holder' => array(),
	'disp-quote' => array(),	
	'ext-link' => array(
		'type' => array(),
		'xlink:href' => array(),
		'title' => array(),
	),
	'fig' => array(),
	'inline-graphic' => array(
		'xlink:href' => array(),
	),
	'heading' => array(),
	'italic' => array(),
	'lbl' => array(),
	'license' => array(
		'license-type' => array(),
	),
	'license-p' => array(),
	'list' => array(
		array('list-type'),
	),
	'list-item' => array(),
	'long-desc' => array(),
	'media' => array(
		'xlink:href' => array(),
	),
	'monospace' => array(),
	'para' => array(),
	'preformat' => array(),
	'permissions' => array(),
	'sec' => array(),
	'title' => array(),
	'table-wrap' => array(),
	'underline' => array(),
	'xref' => array(
		'ref-type' => array(),
		'rid' => array(),
	),
	'br' => array(),
));


/**
 * Load the editor and corresponding textarea the WP 3.3 way
 */ 
function anno_load_editor($content, $editor_id, $settings = array()) {
	$formats = array(
		'bold',
		'italic',
		'monospace',
		'preformat',
		'sup',
		'sub',
		'underline',
	);
	
	$extended_valid_elements = array_merge(array(
		'alt-text',
		'attrib',
		'cap',
		'copyright-statement',
		'copyright-holder',
		'disp-quote',
		'ext-link[ext-link-type:uri|xlink::href|title]',
		'fig',
		'heading',
		'inline-graphic[xlink::href]',
		'lbl',
		'license[license-type:creative-commons]',
		'license-p',
		'list[list-type]',
		'list-item',
		'long-desc',
		'media[xlink::href]',
		'monospace',
		'permissions',
		'para',
		'preformat',
		'sec',
		'table-wrap',
		'xref[ref-type|rid]',
		'paste',
	), $formats);
	
	$custom_elements = array(
		'~bold',
		'~italic',
		'~sup',
		'~sub',
		'~monospace',
		'preformat',
		'~underline',
		'~ext-link',
		'~xref',
		'~inline-graphic',
		'~alt-text',
		'~lbl',
		'~long-desc',
		'~copyright-statement',
		'~copyright-holder',
		'~license',
		'~license-p',
		'~disp-quote',
		'~attrib',
		'heading',
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
		'para',
		'paste',
	);
	
	$formats_as_children = implode('|', $formats);

	// Note the various html elements not defined by the DTD
	// This takes into account imported content and pasted content which gets inserted natively in divs and spans
	// BR accounts for being able to actually insert a line break, we handle removing or keep these later.
	$valid_children = array(
		'preformat[]',
		'body[sec|para|media|list|disp-formula|disp-quote|fig|table-wrap|preformat|div|span|heading|br]',
		'copyright-statement['.$formats_as_children.'|br]',
		'license-p['.$formats_as_children.'|xref|ext-link|br]',
		'heading['.$formats_as_children.'|div|span|br]',
		'media[alt-text|long-desc|permissions|div|span|br]',
		'permissions[copyright-statement|copyright-holder|license|div|span|br]',
		'license[license-p|xref|div|span|br]',
		'license-p[preformat|br|'.$formats_as_children.']',
	'list[title|list-item|div|span|br]',
		'list-item[para|xref|list|div|span|br]',
		'disp-formula[lbl|tex-math|div|span|preformat|br]',
		'disp-quote[para|attrib|permissions|div|span|preformat|br]',
		'fig[lbl|cap|media|img|div|span|preformat|br]',
		'cap[title|para|xref|div|span|br]',
		'table-wrap[lbl|cap|table|table-wrap-foot|permissions|div|span|preformat|br]',
		'table-wrap-foot[para|div|span|br]',
		'td['.$formats_as_children.'|preformat|ext-link|break|list|media|inline-graphic|xref|br]',
		'th['.$formats_as_children.'|preformat|ext-link|break|list|media|inline-graphic|xref|br]',
		'para['.$formats_as_children.'|media|img|permissions|license|list|list-item|disp-formula|disp-quote|fig|cap|table-wrap|table-wrap-foot|table|h2|xref|img|table|ext-link|paste|div|span|div|span|a|br]',
		'sec[sec|heading|media|img|permissions|license|list|list-item|disp-formula|disp-quote|fig|cap|table-wrap|table-wrap-foot|para|h2|div|span|preformat|br]',
	);
	
	$default_settings = array(
		'wpautop' => false,
		'media_buttons' => false,
		'tinymce' => array(
			'remove_linebreaks' => false,
			'content_css' => trailingslashit(get_bloginfo('template_directory')).'css/tinymce.css',
			'extended_valid_elements' => implode(',', $extended_valid_elements),
			'custom_elements' => implode(',', $custom_elements),
			'valid_children' => implode(',', $valid_children),
			//  Defines formats.
			'formats' => '{
					bold : {\'inline\' : \'bold\'},
					italic : { \'inline\' : \'italic\'},
					monospace : { \'inline\' : \'monospace\'},
					preformat : {\'inline\' : \'preformat\'},
					underline : { \'inline\' : \'underline\'},	
				}',
			'theme_advanced_blockformats' => 'Paragraph=para,Heading=heading,Section=sec',
			'forced_root_block' => '',
			'debug' => 'true',
			'verify_html' => true,
			'force_p_newlines' => true,
			'force_br_newlines' => false,
			'content_css' => trailingslashit(get_bloginfo('template_directory')).'css/tinymce.css',
		),
	);
	// Remove WP specific tinyMCE edit image plugin.
	add_filter('tiny_mce_before_init', 'anno_tiny_mce_before_init');
	// Load the editor
	$content = esc_textarea($content);
	wp_editor($content, $editor_id, array_merge($default_settings, $settings));
	remove_filter('tiny_mce_before_init', 'anno_tiny_mce_before_init');
}

/**
 * Remove filters on editor content 
 * 
 */
function anno_remove_editor_content_filters($editor_markup) {
	global $post_type;
	if ($post_type == 'article') {
		remove_filter('the_editor_content', 'wp_richedit_pre');
		// Shouldnt be needed as richedit is enforced, but just in case
		remove_filter('the_editor_content', 'wp_htmledit_pre');
	}
	return $editor_markup;
}
// This is the only place we can hook into to remove 'the_editor_content' filters
add_filter('the_editor', 'anno_remove_editor_content_filters');

/**
 * Force rich editor for article post type. 
 * This overrides user settings and preferences based on other post types
 */ 
function anno_force_richedit($editor_type) {
	global $post_type;
	if ($post_type == 'article') {
		$editor_type = 'tinymce';
	}
	return $editor_type;
}
add_filter('wp_default_editor', 'anno_force_richedit');

class Anno_tinyMCE {
	function Anno_tinyMCE() {	
		add_filter("mce_external_plugins", array(&$this, 'plugins'));
		add_filter('mce_buttons', array(&$this, 'mce_buttons'));
		add_filter('mce_buttons_2', array(&$this, 'mce_buttons_2'));
	}
	
	function mce_buttons($buttons) {
		if ($this->is_article()) {
			$buttons = array('bold', 'italic', 'underline', '|', 'annoorderedlist', 'annobulletlist', '|', 'annoquote', '|', 'sup', 'sub', '|', 'charmap', '|', 'annolink', 'announlink', '|', 'annoimages', 'equation', '|', 'reference', '|', 'undo', 'redo', '|', 'wp_adv', '|', 'help', 'annotips', 'annotable', '|', 'fullscreen' );
		}
		return $buttons;
	}
	
	function mce_buttons_2($buttons) {
		if ($this->is_article()) {
			$buttons = array('annoformatselect', '|', 'table', 'row_before', 'row_after', 'delete_row', 'col_before', 'col_after', 'delete_col', 'split_cells', 'merge_cells', '|', 'annopastetext', 'annopasteword', 'annolist', '|', 'annoreferences', '|', 'annomonospace', 'annopreformat', '|', 'annoequations');
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
			
			$plugins['annoFormats'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoformats/editor_plugin.js';
			
			$plugins['annoEquations'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annoequations/editor_plugin.js';
			
			$plugins['fullscreen'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/fullscreen/editor_plugin.js';

			$plugins['annoPaste'] = trailingslashit(get_bloginfo('template_directory')).'js/tinymce/plugins/annopaste/editor_plugin.js';		
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

function anno_popup_paste() {
?>
<div id="anno-popup-paste" class="anno-mce-popup">
	<form id="anno-tinymce-paste-form" class="" tabindex="-1" name="source" onsubmit="return AnnoPasteWordDialog.insert();" action="#">
		<div class="anno-mce-popup-fields">
			<textarea id="annotest"></textarea>
		</div>
		<input type="submit" value="submit" />
	</form>
</div>
<?php
}

/**
 * Popup Dialog for linking in the tinyMCE
 */
function anno_popup_link() {
?>
<div id="anno-popup-link" class="anno-mce-popup">
	<form id="anno-tinymce-link-form" class="" tabindex="-1">
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
<?php
		if (anno_current_user_can_edit()) {
?>
			<a href="#" id="<?php echo esc_attr('reference-action-edit-'.$reference_key); ?>" class="edit"><?php _ex('Edit', 'reference action', 'anno'); ?></a>
			<a href="#" id="<?php echo esc_attr('reference-action-delete-'.$reference_key); ?>" class="delete"><?php _ex('Delete', 'reference action', 'anno'); ?></a>
			<?php  wp_nonce_field('anno_delete_reference', '_ajax_nonce-delete-reference'); ?>
<?php
		}
		else {
?>
			<a href="#" id="<?php echo esc_attr('reference-action-edit-'.$reference_key); ?>" class="edit"><?php _ex('Show', 'reference action', 'anno'); ?></a>
<?php
		}
?>
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
<?php if (anno_current_user_can_edit()): ?>
							<input type="button" class="blue" name="import_doi" id="<?php echo esc_attr('doi-import-'.$reference_key); ?>" value="<?php _ex('Import', 'button label', 'anno'); ?>"<?php disabled($doi_enabled, false, true); ?>>
							<img src="<?php echo esc_url(admin_url('images/wpspin_light.gif' )); ?>" class="ajax-loading" />
							<?php wp_nonce_field('anno_import_doi', '_ajax_nonce-import-doi', false); ?>
<?php endif; ?>
						</label>
						<label>
							<span><?php _ex('PubMed ID (PMID)', 'input for PubMed ID lookup', 'anno'); ?></span>
							<input type="text" class="short" name="pmid" id="<?php echo esc_attr('pmid-'.$reference_key); ?>" value="<?php echo esc_attr($reference['pmid']) ?>" />
<?php if (anno_current_user_can_edit()): ?>
							<input type="button" class="blue" name="import_pubmed" id="<?php echo esc_attr('pmid-import-'.$reference_key); ?>" value="<?php _ex('Import', 'button label', 'anno'); ?>">
							<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="ajax-loading" />
							<?php wp_nonce_field('anno_import_pubmed', '_ajax_nonce-import-pubmed', false); ?>
<?php endif; ?>
						</label>
						<label>
							<span><?php _ex('Text', 'input label for defining tables', 'anno'); ?></span>
							<textarea type="text" name="text" id="<?php echo esc_attr('text-'.$reference_key); ?>"><?php echo esc_textarea($reference['text']) ?></textarea>
						</label>
						<label>
							<span><?php _ex('URL', 'input label for defining tables', 'anno'); ?></span>
							<input type="text" name="url" id="url" value="<?php echo esc_attr($reference['url']) ?>" />
						</label>
<?php if (anno_current_user_can_edit()): ?>
						<div class="reference-edit-actions clearfix">
							<a href="#" id="<?php echo esc_attr('reference-action-save-'.$reference_key); ?>" class="save left">Save</a>
							<a href="#" id="<?php echo esc_attr('reference-action-cancel-'.$reference_key); ?>" class="cancel right">Cancel</a>
							<input type="hidden" name="ref_id" value="<?php echo esc_attr($reference_key); ?>" />
							<input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>" />
							<input type="hidden" name="action" value="anno-reference-save" />
							<?php wp_nonce_field('anno_save_reference', '_ajax_nonce-save-reference'); ?>
						</div>
<?php endif; ?>						
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
			$reference_keys = array('text', 'doi', 'pmid', 'url', 'figures');
			foreach ($reference_keys as $key_val) {
				$reference[$key_val] = isset($reference[$key_val]) ? $reference[$key_val] : '';
			}
			
			anno_popup_reference_row($reference_key, $reference, $post->ID, $doi_enabled);
		}
	}
?>
<?php if (anno_current_user_can_edit()): ?>
					<tr id="<?php echo esc_attr('reference-new'); ?>">
						<td colspan="3">
							<?php anno_popup_references_row_edit('new', array('text' => '', 'doi' => '', 'pmid' => '', 'url' => '', 'figures' => ''), $post->ID, $doi_enabled); ?>
						</td>
					<tr>
						<td colspan="3" class="anno-mce-popup-footer">
							<?php _anno_popup_submit_button('anno-references-new', _x('New Reference', 'button value', 'anno')); ?>
						</td>
					</tr>
<?php endif; ?>					
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
 * Popup Dialog for equation editor in the tinyMCE
 */
function anno_popup_equations() {
?>
<div id="anno-popup-equations" class="anno-mce-popup">
	<form id="anno-tinymce-equations-form" class="" tabindex="-1">
		<div class="anno-mce-popup-fields">			
			<div class="equation-edit-details">
				<label for="equation-alttext">
					<div><?php _ex('Alt Text', 'input label', 'anno'); ?></div>
					<input name="alt_text" type="text" id="equation-alttext" />
				</label>
				<label for="equation-description">
					<div><?php _ex('Description', 'input label', 'anno'); ?></div>
					<textarea name="description" id="equation-description"></textarea>
				</label>
			</div>
			<fieldset class="equation-display">
				<legend><?php _ex('Display', 'legend', 'anno'); ?></legend>
				<label for="equation-display-figure" class="radio">
					<input type="radio" value="figure" name="display" class="equation-display-selection equation-display-figure" id="equation-display-figure"<?php checked(true, true, true); ?> />
					<span><?php _ex('Display as Figure', 'input label', 'anno'); ?></span>
				</label>
				<label for="equation-display-inline" class="radio">
					<input type="radio" value="inline" name="display" class="equation-display-selection equation-display-inline" id="equation-display-inline" />
					<span><?php _ex('Display Inline', 'input label', 'anno'); ?></span>
				</label>
				<div id="equation-figure-details">
					<label for="equation-label">
						<span><?php _ex('Label', 'input label', 'anno'); ?></span>
						<input type="text" name="label" id="equation-label" />
					</label>
					<label for="equation-caption">
						<span><?php _ex('Caption', 'input label', 'anno'); ?></span>
						<textarea id="equation-caption" name="caption"></textarea>
					</label>
				</div>
			</fieldset>
		</div>
		<div class="anno-mce-popup-footer">
			<?php _anno_popup_submit_button('anno-equations-insert', _x('Insert', 'button value', 'anno')) ?>
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
		<dl>
			<dt><?php _ex('Use Ctrl+Enter', 'tinyMCE tip dt', 'anno'); ?></dt>
			<dd><?php _ex('To insert new sections (when within a section) or add new paragraphs when inside elements like a table, or figure caption.', 'tinyMCE tip dd', 'anno'); ?></dd>
		</dl>
</div>
<?php
}

/**
 * Markup for the tinyMCE dialog popups
 */ 
function anno_preload_dialogs($init) {
	global $pagenow, $post_type;
	if (($pagenow == 'post-new.php' || $pagenow == 'post.php') && $post_type == 'article') {
?>
	<div style="display:none;">
	<?php anno_popup_link(); ?>
	</div>
	
	<div style="display:none;">
	<?php anno_popup_paste(); ?>
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
	<?php anno_popup_equations(); ?>
	</div>
	
	<div style="display:none;">
	<?php anno_popup_tips(); ?>
	</div>
	
<?php
	}
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
		'pmid' => isset($ref_array['pmid']) ? $ref_array['pmid'] : '',
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

//@todo Update success response
function anno_tinymce_image_save() {
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
				'url',
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
	// Break to BR
	$content = str_replace('<break />', '<br />', $content);
	
	phpQuery::newDocument($content);

	// Convert inline-graphics to <img> tags so they display
	anno_xml_to_html_replace_inline_graphics($content);
	
	// Convert caption to cap
	anno_replace_caption_tag($content);
	
	// Convert p to para
	anno_replace_p_tag($content);
	
	// Convert label to lbl
	anno_replace_label_tag($content);
	
	// Convert title to heading
	anno_replace_title_tag($content);
	
	// Remove p tags wrapping list items
	anno_remove_p_from_list_items($content);
	
	// Remove p tags from disp-quotes
	anno_remove_p_from_disp_quote_items($content);
		
	// We need a clearfix for floated images.
	$figs = pq('fig');
	foreach ($figs as $fig) {
		$fig = pq($fig);

		$img_src = '';

		// Check if we're using bugged version of libxml
		if (version_compare(LIBXML_DOTTED_VERSION, '2.6.29', '<')) {
				$img_src = anno_get_attribute_value_regex($fig, 'media', 'xlink:href');
			}
		else {
			$img_src = $fig->find('media')->attr('xlink:href');
		}
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
	
	// Convert remaining br to break tags. Unable to do this with phpQuery, it thinks break tag should be opened and closed
	$content = str_replace(array('<br />', '<br>'), '<break />', $content);

	$content = strip_tags($content, implode('', array_unique(anno_get_dtd_valid_elements())));
	return $content;
}

function anno_get_dtd_valid_elements() {
	// Build big list of valid XML elements (listed in DTD)
	// This is after the editor content is processed and converted to XML defined by DTD
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
								'<break>',
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
		'<article>',
			'<sec>',
			'<para>',
			'<br>',
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
	// This is the quick edit action, we don't want to mess around with the content.
	if (isset($_POST['action']) && $_POST['action']  == 'inline-save') {
		return $data;
	}
	
	$is_article_type = false;
	// Both published and drafts (before article ever saved) get caught here
	if ($postarr['post_type'] == 'article') {
		$is_article_type = true;
	}
	// If we're a revision, we need to do one more check to ensure our parent is an article
	if ($postarr['post_type'] == 'revision') {
		if (!empty($data['post_parent']) && get_post_type($data['post_parent']) == 'article') {
			$is_article_type = true;
		}
	}
	
	if ($is_article_type) {
		// Get our XML content for the revision
		$content = stripslashes($data['post_content']);
		
		// Remove non-ascii gremlins
		$content = preg_replace('/(\xa0|\xc2)/','', $content);
		$content = str_replace(array("\r", "\r\n", "\n"), '', $content);
		// Set XML as backup content. Filter markup and strip out tags not on whitelist.
		$xml = anno_validate_xml_content_on_save($content);
		$data['post_content_filtered'] = addslashes($xml);
		// Set formatted HTML as the_content
		$data['post_content'] = addslashes(anno_xml_to_html($xml));
	}
	
	return $data;
}
add_filter('wp_insert_post_data', 'anno_insert_post_data', null, 2);

/**
 * Only maintain line breaks on certain tags (title, td, th)
 */ 
function anno_handle_br($xml) {
	$tags = pq('br');
	$save = pq('title > br, td > br, th > br');
	//  ->not() with selector pattern is bugged
	$tags = $tags->not($save);
	$tags->remove();
}
add_action('anno_to_xml', 'anno_handle_br', 99);

function anno_wp_insert_post_update_import($post_id) {
	// If we've saved an imported post (Knol), we've likely changed the structure so we don't need to run
	// the post import filters on it next save
	$imported = get_post_meta($post_id ,'_anno_knol_import', true);
	if ($imported) {
		update_post_meta($post_id ,'_anno_knol_import', 0);
	}
}
add_action('wp_insert_post', 'anno_wp_insert_post_update_import', 10, 1);
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
	$post_id = anno_get_post_id();
	
	// Load our phpQuery document up, so filters should be able to use the pq() function to access its elements
	phpQuery::newDocument($html_content);
	
	// Let our various actions alter the document into XML
	do_action('anno_to_xml', $html_content);
	
	$imported = get_post_meta($post_id, '_anno_knol_import', true);
	if ($imported) {
		do_action('anno_to_xml_imported', $html_content);
	}
	
	// Return the newly formed HTML
	return phpQuery::getDocument()->__toString();
}

/**
 * Change HTML formatting to XML defined by the DTD. 
 * Knol imported content comes with b, em etc... tags
 * 
 * @param string $orig_html 
 * @return void
 */
function anno_to_xml_replace_formatting($orig_markup) {
	$formats = array(
		'b' => 'bold',
		'strong' => 'bold',
		'em' => 'italic',
		'i' => 'italic',
		'u' => 'underline',
	);
	
	foreach ($formats as $html => $kipling_xml) {
		anno_convert_tag($html, $kipling_xml);
	}	
}
add_action('anno_to_xml', 'anno_to_xml_replace_formatting');

/**
 * Change HTML inline <img> to XML <inline-graphic>
 *
 * @param string $orig_markup
 * @return void
 */
function anno_to_xml_import_replace_images($orig_markup) {
	$imgs = pq('img');
	foreach ($imgs as $img) {
		$img = pq($img);
		$img_src = $img->attr('src');
		$img_alt = $img->attr('alt');
		if (!empty($img_alt)) {
			$img_alt == '<alt-text>'.$img_alt.'</alt-text>';
		}
		$xml = '<inline-graphic xlink:href="'.$img_src.'" >'.$img_alt.'</inline-graphic>';
		$img->replaceWith($xml);
	}
}
add_action('anno_to_xml_imported', 'anno_to_xml_import_replace_images');

/**
 * Change HTML inline <a> to XML <ext-link>
 *
 * @param string $orig_markup
 * @return void
 */
function anno_to_xml_import_replace_links($orig_markup) {
	$a_tags = pq('a');
	foreach ($a_tags as $a_tag) {
		$a_tag = pq($a_tag);
		$link_content = $a_tag->html();
		$link_url = $a_tag->attr('href');
			
		$xml = '<ext-link ext-link-type="uri" xlink:href="'.$link_url.'" >'.$link_content.'</ext-link>';
		$a_tag->replaceWith($xml);
	}
}
add_action('anno_to_xml_imported', 'anno_to_xml_import_replace_links');

/**
 * Change HTML inline <img> to XML <inline-graphic>
 *
 * @param string $orig_markup
 * @return void
 */
function anno_to_xml_replace_inline_graphics($orig_markup) {
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
	// Unable to do this with phpQuery, break tag doesn't register as self closing element
	$xml_content = str_replace('<break />', '<br />', $xml_content);

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
		if (version_compare(LIBXML_DOTTED_VERSION, '2.6.29', '<')) {
			$img_src = anno_get_attribute_value_regex($img, 'inline-graphic', 'xlink:href');
		}
		else {
			$img_src = $img->attr('xlink:href');
		}
		
		if (!empty($img_src)) {
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

			// Get some img tag properties. Check for lower versions of libxml which do not support : in attributes
			if (version_compare(LIBXML_DOTTED_VERSION, '2.6.29', '<')) {
				$img_src = anno_get_attribute_value_regex($media, 'media', 'xlink:href');
			}
			else {
				$img_src = $media->attr('xlink:href');
			}
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
			
			$cap = $fig->children('caption')->html();
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
 */
function anno_xml_to_html_replace_sec($orig_xml) {
	$sections = pq('sec');
	if (count($sections)) {
		foreach ($sections as $sec) {
			$sec = pq($sec);			
			// Replace sections
			$sec->replaceWith('<section class="sec">'.$sec->html().'</section>');
		}
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_sec');

/**
 * Change XML <title> tags to HTML5 <h2> tags
 * Run at priority 9 so we change the titles before global title changes happen.
 */
function anno_xml_to_html_replace_title($orig_xml) {
	// Replace Titles
	$titles = pq('title');
	if (count($titles)) {
		foreach ($titles as $title) {
			$title = pq($title);
			$title->replaceWith('<h2 class="title"><span>'.$title->html().'</span></h2>');
		}
	}
}
add_action('anno_xml_to_html', 'anno_xml_to_html_replace_title', 9);

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
		'<lbl>',
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
	$figcaption = $table->children('lbl:first')->html();
	$table_caption = $table->children('caption:first')->html();
	
	// Now that we have the title and caption, get rid of the elements
	$table->children('lbl:first')->remove();
	$table->children('caption:first')->remove();
	
	$inner_table = $table->children('table');
	
	// Loop over our table header
	$theads = $inner_table->children('thead');

	if (count($theads)) {
		foreach ($theads as $thead) {
			anno_xml_to_html_iterate_table_head(pq($thead));
		}
	}
	
	// Loop over our table body
	$tbodies = $inner_table->children('tbody');
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
 * Replace <br>s with <break>
 * the rest of it's HTML the same
 *
 * @param pq obj $th 
 * @return void
 */
function anno_xml_to_html_iterate_table_head_row_th($th) {
	// Nothing to do here right now...just a stub function 
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
			if (version_compare(LIBXML_DOTTED_VERSION, '2.6.29', '<')) {
				$url = anno_get_attribute_value_regex($link, 'ext-link', 'xlink:href');
			}
			else {
				$url = $link->attr('xlink:href');
			}
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
		$attrib = $attrib ? $by.' '.$attrib : '';
		$attrib_tag = $tpl->to_tag('span', $attrib, array('class' => 'attribution'));

		$quote_text = pq('p', $quote)->text();

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

/**
 * Swap preformat tags with pre tag.
 * 
 * @param phpQueryObject $xml (unused, required by add_action)
 * @return void
 */
function anno_xml_to_html_preformat_tag($xml) {
	anno_convert_tag('preformat', 'pre');
}
add_action('anno_xml_to_html', 'anno_xml_to_html_preformat_tag');

/**
 * Convert permissions block to html
 *
 * @param phpQueryObject $permissions_pq_obj
 * @return void 
 */
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
 * Utilize RegEx to find the values of attributes given a specific element.
 * Bug libxml2 2.6.28 and previous with regards to selecting attributes with colons in their name
 * 
 * @param phpQueryObject $element Element to preform the search on
 * @param string $element_name Name of the element to search for
 * @param string $attribute_name Name of the attribute to search for
 * @return string Value of the attribute for a given element, empty string otherwise
 * 
 */ 
function anno_get_attribute_value_regex($element, $element_name, $attribute_name) {
	$outer_html = $element->markupOuter();

	// We only want to match everything in the media tag. Non greedy RegEx.
	if (preg_match('/<'.$element_name.' .*?>/', $outer_html, $element_match)) {
		// $media_match[0] should now just contain the opening media tag and its attribute
		// Match on attribute name where wrapping quotes can be any combination of ', ", or lack there of 
		if (preg_match('/ '.$attribute_name.'=["\']?((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']?/', $element_match[0], $attribute_match)) {
			// $matches[1] should match data contained in parenthesis above
			if (isset($attribute_match[1])) {
				return $attribute_match[1];
			}
		}
	}	
	return '';
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
 * Convert p tag to para tag for display in editor
 * Browsers auto close <p> tag when it encounters another block level element.
 * 
 * @param phpQueryObject $xml
 * @return void
 */
function anno_replace_p_tag($xml) {
	anno_convert_tag('p', 'para');
}

/**
 * Convert label tag to lbl tag for display in editor
 * Firefox has issues selecting inside a label tag when it is in a textarea
 * 
 * @param phpQueryObject $xml
 * @return void
 */
function anno_replace_label_tag($xml) {
	anno_convert_tag('label', 'lbl');
}


/**
 * Swap para tags with p tag. P tag has issues with embedded block level elements 
 * 
 * @param phpQueryObject $xml (unused, required by add_action)
 * @return void
 */
function anno_to_xml_para_tag($xml) {
	anno_convert_tag('para', 'p');
}
add_action('anno_to_xml', 'anno_to_xml_para_tag');

/**
 * Swap lbl tags with label tag.
 * 
 * @param phpQueryObject $xml (unused, required by add_action)
 * @return void
 */
function anno_to_xml_lbl_tag($xml) {
	anno_convert_tag('lbl', 'label');
}
add_action('anno_to_xml', 'anno_to_xml_lbl_tag');

/**
 * Format caption content and converts cap tags to caption to 
 * match the DTD when saving editor content.
 * Browsers strip caption tags not wrapped in <table> tags. 
 * 
 * @param phpQueryObject $xml (unused, required by add_action)
 * @return void
 */
function anno_to_xml_cap_tag($xml) {
	$cap_tags = pq('cap');
	foreach ($cap_tags as $cap_tag) {
		// wpautop the Caption tags so there is no straggling text not wrapped in p
		$cap_tag = pq($cap_tag);
		
		$tag_html = wpautop($cap_tag->html());
		// Also need to convert cap to caption tags
		$cap_tag->replaceWith('<caption>'.$tag_html.'</caption>');
	}
}
add_action('anno_to_xml', 'anno_to_xml_cap_tag');

/**
 * Convert heading tags to title to match the DTD when saving editor content.
 * Browsers convert title content to html entities.
 * 
 * @param phpQueryObject $xml
 * @return void
 */
function anno_to_xml_heading_tag($xml) {
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
	anno_remove_p_from_items('list-item');
}

/**
 * Remove p tags which wrap disp-quote item content so the editor can handle the 
 * unconventional xml structure as html.
 * 
 * @param phpQueryObject $xml
 * @return void
 */
function anno_remove_p_from_disp_quote_items($xml) {
	anno_remove_p_from_items('disp-quote');
}

/**
 * Remove p tags from items stored in the phpQuery document based on name.
 * 
 * @param string $tag_name Tag name to remove the p tags from
 * @return void 
 */ 
function anno_remove_p_from_items($tag_name) {
	$tags = pq($tag_name);
	foreach ($tags as $tag) {
		$tag = pq($tag);
		$p_tags = $tag->children('p');
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
 * Add p tags to disp-quote content
 * 
 * @param phpQueryObject $xml not used
 * @return void
 */
function anno_to_xml_disp_quote_p($xml) {
	$quotes = pq('disp-quote');
	foreach ($quotes as $quote) {
		$quote = pq($quote);
		
		$attribution = $quote->find('attrib');
		$permissions = $quote->find('permissions');
		
		$attribution_markup = $attribution->htmlOuter();
		$permissions_markup = $permissions->htmlOuter();
		
		// Remove attribution and permissions so they don't get included in wpautop
		$attribution->remove();
		$permissions->remove();
				
		// wpautop the content
		$quote_content = wpautop($quote->html());
		$quote->html($quote_content);
		
		// "We can rebuild him, we have the technology"
		$quote->append($attribution_markup);
		$quote->append($permissions_markup);
	}
}
add_action('anno_to_xml', 'anno_to_xml_disp_quote_p');

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

function anno_tinymce_css($hook) {
	global $post_type;
	if ($post_type == 'article') {
		$main = trailingslashit(get_bloginfo('template_directory'));
		wp_enqueue_style('eqeditor', $main.'js/tinymce/plugins/annoequations/equationeditor.css');
	}
}
add_action('admin_print_styles-post.php', 'anno_tinymce_css');
add_action('admin_print_styles-post-new.php', 'anno_tinymce_css');

function anno_tinymce_js() {
	global $post_type;
	if ($post_type == 'article') {
		$main = trailingslashit(get_bloginfo('template_directory'));
		
		wp_enqueue_script('closure-goog', $main.'js/tinymce/plugins/annoequations/equation-editor-compiled.js');
	}
}
add_action('admin_print_scripts-post.php', 'anno_tinymce_js');
add_action('admin_print_scripts-post-new.php', 'anno_tinymce_js');

/**
 * Remove specific filters for non-admins for saving content properly
 */ 
function anno_remove_kses_from_content() {
	// If we don't remove these, WP treats <list-item> as <list> for non-admin users
	remove_filter('content_save_pre', 'wp_filter_post_kses');
	remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
}
add_action('init', 'anno_remove_kses_from_content');

?>