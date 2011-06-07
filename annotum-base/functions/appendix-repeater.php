<?php 

function anno_article_admin_print_styles() {
	wp_enqueue_style('article-admin', trailingslashit(get_bloginfo('template_directory')).'/css/article-admin.css');
}
add_action('admin_print_styles-post.php', 'anno_article_admin_print_styles');
add_action('admin_print_styles-post-new.php', 'anno_article_admin_print_styles');

function anno_appendicies_meta_box($post) {
	$html .= '
	<div id="anno_appendicies">';

	$appendicies = get_post_meta($post->ID, '_anno_appendicies', true);
	if (!empty($appendicies) && is_array($appendicies)) {
		foreach ($appendicies as $index => $content) {
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
				if (jQuery(\'#anno_appendicies\').children(\'fieldset\').length > 0) {
					last_element_index = jQuery(\'#anno_appendicies fieldset:last\').attr(\'id\').match(/anno_appendix_([0-9]+)/);
					next_element_index = Number(last_element_index[1])+1;
				} else {
					next_element_index = 1;
				}
				insert_element = \''.str_replace(PHP_EOL,'',trim(anno_appendix_box_content())).'\';
				insert_element = insert_element.replace(/'.'###INDEX###'.'/g, next_element_index);
				insert_element = insert_element.replace(/'.'###INDEX_ALPHA###'.'/g, annoIndexAlpha(next_element_index));
				jQuery(insert_element).appendTo(\'#'.'anno_appendicies'.'\');
			}
			function deleteAnnoAppendix'.'(del_el) {
				if(confirm(\''.__('Are you sure you want to delete this?', 'anno').'\')) {
					jQuery(del_el).parent().remove();
				}
			}
		</script>';
	$html .= '</div><div>';
	$html .= '
			<p class="cf_meta_actions"><a href="#" onclick="addAnotherAnnoAppendix(); return false;" '.
		     'class="add_another button-secondary">'.__('Add Another Appendix', 'anno').'</a></p>'.
		'</div><!-- close anno_appendix wrapper -->';
	echo $html;
}

function anno_appendix_box_content($index = null, $content = null) {

	if (empty($index) && $index !== 0) {
		$index = '###INDEX###';
		$index_alpha = '###INDEX_ALPHA###';
		$content = '';
	}
	else {
		$index_alpha = anno_index_alpha($index);
	}
	$html .='
<fieldset id="'.esc_attr('anno_appendix_'.$index).'" class="appendix-wrapper">
	<h4>
	Appendix '.esc_html($index_alpha).' - <a href="#" onclick="deleteAnnoAppendix(jQuery(this).parent()); return false;" class="delete">'.__('delete', 'anno').'</a>
	</h4>
	<textarea class="anno-meta" name="'.esc_attr('anno_appendix['.$index.']').'">'.esc_html($content).'</textarea>
</fieldset>';
	return $html;
}
/**
 * Create an alpha representation of the appendix number. Note that this only supports up to ZZ
 */ 
function anno_index_alpha($index) {
 	for($r = ''; $index >= 0; $index = intval($index / 26) - 1)
        $r = chr(intval($index % 26) + 65) . $r;
    return $r;
}
