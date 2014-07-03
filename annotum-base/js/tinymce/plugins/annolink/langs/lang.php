<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annolink_translation() {
	$translated = '';

	$annolink = array(
		'insertLink' => __('Insert Link', 'anno'),
		'removeLink' => __('Remove Link', 'anno'),
		'link_desc' => __('Insert/edit link', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annolink", ' . json_encode( $annolink ) . ");\n";

	return $translated;
}

$strings = annolink_translation();

