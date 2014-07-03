<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annoreferences_translation() {
	$translated = '';

	$annoreferences = array(
		'title' => __('Insert Reference', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annoreferences", ' . json_encode( $annoreferences ) . ");\n";

	return $translated;
}

$strings = annoreferences_translation();

