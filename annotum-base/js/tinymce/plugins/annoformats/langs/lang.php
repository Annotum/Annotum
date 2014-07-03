<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annoformats_translation() {
	$translated = '';

	$annoformats = array(
		'preformat' => __('Preformat', 'anno'),
		'monospace' => __('Monospace', 'anno'),
		'newSection' => __('New Section', 'anno'),
		'newSubsection' => __('New Subsection', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annoformats", ' . json_encode( $annoformats ) . ");\n";

	return $translated;
}

$strings = annoformats_translation();

