<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annotable_translation() {
	$translated = '';

	$annotable = array(
		'desc' => __('Insert Table', 'anno'),
		'del' => __('Delete Table', 'anno'),
		'delete_col_desc' => __('Delete Column', 'anno'),
		'delete_row_desc' => __('Delete Row', 'anno'),
		'col_after_desc' => __('Insert Column After', 'anno'),
		'col_before_desc' => __('Insert Column Before', 'anno'),
		'row_after_desc' => __('Insert Row After', 'anno'),
		'row_before_desc' => __('Insert Row Before', 'anno'),
		'row_desc' => __('Row Properties', 'anno'),
		'cell_desc' => __('Cell Properties', 'anno'),
		'split_cells_desc' => __('Split Cells', 'anno'),
		'merge_cells_desc' => __('Merge Cells', 'anno'),
		'cut_row_desc' => __('Cut Row', 'anno'),
		'insert' => __('Insert Table', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annotable", ' . json_encode( $annotable ) . ");\n";

	return $translated;
}

$strings = annotable_translation();

