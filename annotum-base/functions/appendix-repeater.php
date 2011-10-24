<?php 

/**
 * Meta box for Article Appendices
 */ 
function anno_appendices_meta_box($post) {
	if (function_exists('wp_editor')) {
		$html = '';
		$html .= '
		<div id="anno_appendices">';

		$appendices = get_post_meta($post->ID, '_anno_appendices', true);
		if (!empty($appendices) && is_array($appendices)) {
			foreach ($appendices as $index => $content) {
				$html .= anno_appendix_box_content($index, $content);
			}
		}
		else {
			$html .= anno_appendix_box_content(0);
		}
	
		$html .= '
			<script type="text/javascript" charset="utf-8">
				function annoIndexAlpha(index) {
					for(var r = \'\'; index >= 0; index = Number(index) / 26 - 1) {
				       	r = String.fromCharCode(Number(index) % 26 + 65) + r;
					}
				   	return r;
				}
				function addAnotherAnnoAppendix() {
					if (jQuery(\'#anno_appendices\').children(\'fieldset\').length > 0) {
						last_element_index = jQuery(\'#anno_appendices fieldset:last\').attr(\'id\').match(/anno_appendix_([0-9]+)/);
						next_element_index = Number(last_element_index[1])+1;
					} else {
						next_element_index = 1;
					}
					insert_element = \''.str_replace(PHP_EOL,'',trim(anno_appendix_box_content())).'\';
					insert_element = insert_element.replace(/'.'###INDEX###'.'/g, next_element_index);
					insert_element = insert_element.replace(/'.'###INDEX_ALPHA###'.'/g, annoIndexAlpha(next_element_index));
					jQuery(insert_element).appendTo(\'#'.'anno_appendices'.'\');
					tinyMCE.execCommand(\'mceAddControl\', false, \'appendix-\' + next_element_index );
					jQuery(\'.wp-editor-tools\').remove();
				}
				function deleteAnnoAppendix'.'(del_el) {
					if(confirm(\''._x('Are you sure you want to delete this?', 'JS popup confirmation', 'anno').'\')) {
						jQuery(del_el).parent().remove();
					}
				}
			</script>';
		$html .= '</div><div>';
		$html .= '
				<p class="cf_meta_actions"><a href="#" onclick="addAnotherAnnoAppendix(); return false;" '.
			     'class="add_another button-secondary">'._x('Add Another Appendix', 'Meta box repeater link', 'anno').'</a></p>'.
			'</div><!-- close anno_appendix wrapper -->';
		echo $html;
	}
	else {
		echo '<p style="padding:0 10px;">'.sprintf(_x('The Annotum editor requires at least WordPress 3.3. It appears you are using WordPress %s. ', 'WordPress version error message', 'anno'), get_bloginfo('version')).'</p>';
	}
}

/**
 * Output for Appendix edit input.
 */ 
function anno_appendix_box_content($index = null, $content = null) {
$html = '';
	if (empty($index) && $index !== 0) {
		$index = '###INDEX###';
		$index_alpha = '###INDEX_ALPHA###';
	}
	else {
		$index_alpha = anno_index_alpha($index);
	}
	if (empty($content)) {
		$content = '<sec>
			<heading></heading>
			<para>&nbsp;</para>
		</sec>';
	}
	
	ob_start();
	anno_load_editor(anno_process_editor_content($content), esc_attr('appendix-').$index, array('textarea_name' => esc_attr('anno_appendix['.$index.']')));
	$editor_markup = ob_get_contents();
	ob_end_clean();
		
	$html .='
<fieldset id="'.esc_attr('anno_appendix_'.$index).'" class="appendix-wrapper">
	<h4>
	'._x('Appendix', 'meta box title', 'anno').' '.esc_html($index_alpha).' - <a href="#" onclick="deleteAnnoAppendix(jQuery(this).parent()); return false;" class="delete">'._x('delete', 'Meta box delete repeater link', 'anno').'</a>
	</h4>
	'.$editor_markup.'
</fieldset>';

	return $html;
}

/**
 * Create an alpha representation of the appendix number.
 * 
 * @param int $index Integer index to be converted to an equivalent base 26 (alphabet) sequence
 * @return string String of capital letters representing the index integer
 */ 
function anno_index_alpha($index) {
 	for($return = ''; $index >= 0; $index = intval($index / 26) - 1)
        $return = chr(intval($index % 26) + 65) . $return;
    return $return;
}
