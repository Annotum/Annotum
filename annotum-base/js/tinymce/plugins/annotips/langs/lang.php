<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annotips_translation() {
	$translated = '';

	$annotips = array(
		'title' => __('Equation Tips', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annotips", ' . json_encode( $annotips ) . ");\n";

	return $translated;
}

$strings = annotips_translation();

