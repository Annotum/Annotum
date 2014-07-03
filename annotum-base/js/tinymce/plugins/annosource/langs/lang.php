<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annosource_translation() {
	$translated = '';

	$annosource = array(
		'windowTitle' => __('Source Editor and Validation', 'anno'),
		'title' => __('Anno Source', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annosource", ' . json_encode( $annosource ) . ");\n";

	return $translated;
}

$strings = annosource_translation();

