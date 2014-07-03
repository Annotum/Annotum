<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annoquote_translation() {
	$translated = '';

	$annoquote = array(
		'title' => __('Insert Quote', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annoquote", ' . json_encode( $annoquote ) . ");\n";

	return $translated;
}

$strings = annoquote_translation();

