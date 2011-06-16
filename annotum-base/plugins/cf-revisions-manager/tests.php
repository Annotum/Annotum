<?php
	function cf_revisions_post_meta_config($config) {
		$config[] = array(
			'title' => 'Block title',	// required, Title of the Meta Box
			'description' => 'Block Description', 		// optional, Description text that appears at the top of the Meta Box
			'type' => array('page', 'post'), 	// required, Which edit screen to add to. Use array('page','post') to add to both at the same time
			'id' => 'cf-revisions-post-meta-test', 		// required, unique id for the Meta Box
			'add_to_sortables' => true,	// optional, this is the default behavior
			'context' => 'normal',		// optional, sets the location of the metabox in the edit page.  Other posibilites are 'advanced' or 'side' (this sets the meta box to apear in the rt sidebar of the edit page)
			'items' => array(
				// text input
				array(
					'name' => '_cf_revisions_text_meta',			// required, this is the meta_key that will be saved by WordPress
					'label' => 'Label Text', 				// optional, label only printed if text is not empty
					'label_position' => 'before',			// optional, label position in relation to the input, default: 'before'
					'type' => 'text',						// required, input type
					'before' => '<div class="special">',	// optional, html to put before the field
					'after' => '</div>',					// optional, html to put after the field
				)
			)	
		);
		return $config;
	}
	
	function cf_revisions_tests_init() {
		if (function_exists('cf_meta_get_type')) {
			add_filter('cf_meta_config', 'cf_revisions_post_meta_config');
			cfr_register_metadata('_cf_revisions_text_meta');
		}
		if (function_exists('cfct_build')) {
			cfr_register_metadata(CFCT_BUILD_POSTMETA, 'cfct_describe_postmeta');
		}
	}
	add_action('init', 'cf_revisions_tests_init');
		
?>