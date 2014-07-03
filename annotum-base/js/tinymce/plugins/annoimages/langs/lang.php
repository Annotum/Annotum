<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annoimages_translation() {
	$translated = '';

	$annoimages = array(
		'title' => __('Insert Image', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annoimages", ' . json_encode( $annoimages ) . ");\n";

	return $translated;
}

$strings = annoimages_translation();

