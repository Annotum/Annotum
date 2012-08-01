<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( '_WP_Editors' ) )
	require( ABSPATH . WPINC . '/class-wp-editor.php' );

function annoequationedit_translation() {

	$annoequationedit = array(
		'title' => __('Equation Editor', 'anno'),
		'alt_text' => __('Alt Text', 'anno'),
		'description' => __('Delete Image', 'anno'),
		'update' => __('Update', 'anno'),
		'cancel' => __('Cancel', 'anno'),
		'fig_meta' => __('Figure Meta', 'anno'),
	);
	$locale = _WP_Editors::$mce_locale;
	$translated .= 'tinyMCE.addI18n("' . $locale . '.annoequationedit", ' . json_encode( $annoequationedit ) . ");\n";

	return $translated;
}

$strings = annoequationedit_translation();

?>
