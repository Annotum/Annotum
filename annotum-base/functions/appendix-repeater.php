<?php 

function anno_appendicies_print_styles() {
	wp_enqueue_style('article-admin', trailingslashit(get_bloginfo('template_directory')).'/css/article-admin.css');
}
add_action('admin_print_styles-post.php', 'anno_appendicies_print_styles');
add_action('admin_print_styles-post-new.php', 'anno_appendicies_print_styles');

function anno_appendicies_meta_box($post) {
	$html .= '
	<div id="anno_appendicies">';
	//TODO wrap for Z, should become AA?
	$html .= '
		<script type="text/javascript" charset="utf-8">
			function addAnotherAnnoAppendix() {
				if (jQuery(\'#'.'anno_appendicies'.'\').children(\'fieldset\').length > 0) {
					last_element_index = jQuery(\'#anno_appendicies fieldset:last\').attr(\'id\').match(/anno_appendix_([A-Z])/);
					next_element_index = Number(last_element_index[1].charCodeAt())+1;
				} else {
					next_element_index = 65;
				}
				insert_element = \''.str_replace(PHP_EOL,'',trim(anno_appendix_box_content())).'\';
				insert_element = insert_element.replace(/'.'###INDEX###'.'/g, String.fromCharCode(next_element_index));
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

function anno_appendix_box_content() {
	$html .='
<fieldset id="anno_appendix_###INDEX###" class="appendix-wrapper">
	<h4>
	Appendix ###INDEX### - <a href="#" onclick="deleteAnnoAppendix(jQuery(this).parent()); return false;" class="delete">'.__('delete', 'anno').'</a>
	</h4>
	<textarea style="width:100%; height:250px"></textarea>
</fieldset>';
	return $html;
}

