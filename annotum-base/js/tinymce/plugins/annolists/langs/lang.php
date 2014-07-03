<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annolists_translation() {
	$translated = '';

	$annolists = array(
		'bullist' => __('Insert Bullet List', 'anno'),
		'orderedlist' => __('Insert Ordered List', 'anno'),
		'indent' => __('Indent List', 'anno'),
		'outdent' => __('Outdent List', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annolists", ' . json_encode( $annolists ) . ");\n";

	return $translated;
}

$strings = annolists_translation();

