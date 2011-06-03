<?php 

function anno_appendicies_print_styles() {
	wp_enqueue_style('anno-appendix-meta', trailingslashit(get_bloginfo('template_directory')).'/css/appendix-meta.css');
}
add_action('admin_print_styles-post.php', 'anno_appendicies_print_styles');
add_action('admin_print_styles-post-new.php', 'anno_appendicies_print_styles');

function anno_appendicies_meta_box($post) {
	$html .= '
	<div id="anno_appendicies">';
	$html .= '
		<script type="text/javascript" charset="utf-8">
			function addAnotherAnnoAppendix'.'() {
				if (jQuery(\'#'.'anno_appendix'.'\').children().length > 0) {
					last_element_index = jQuery(\'#'.'anno_appendix'.' fieldset:last\').attr(\'id\').match(/'.'anno_appendix'.'_([0-9])/);
					next_element_index = Number(last_element_index[1])+1;
				} else {
					next_element_index = 1;
				}
				insert_element = \''.str_replace(PHP_EOL,'',trim(anno_appendix_box_content())).'\';
				insert_element = insert_element.replace(/'.'test'.'/g, next_element_index);
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
<div class="appendix-wrapper">
	<h4>
	Appendix A - <a href="#" onclick="deleteAnnoAppendix(jQuery(this).parent()); return false;" class="delete">'.__('delete', 'anno').'</a>
	</h4>
	<textarea style="width:100%; height:250px"></textarea>
</div>';
	return $html;
}

