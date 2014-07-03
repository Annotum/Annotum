<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annopaste_translation() {
	$translated = '';

	$annopaste = array(
		'description' => __('Paste', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annopaste", ' . json_encode( $annopaste ) . ");\n";

	return $translated;
}

$strings = annopaste_translation();

