<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annotree_translation() {
	$translated = '';

	$annotree = array(
		'title' => __('Annotum Tree View', 'anno'),
		'alt_text' => __('Alt Text', 'anno'),
		'description' => __('Description', 'anno'),
		'update' => __('Update', 'anno'),
		'cancel' => __('Cancel', 'anno'),
		'fig_meta' => __('Figure Meta', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annotree", ' . json_encode( $annotree ) . ");\n";

	return $translated;
}

$strings = annotree_translation();

