# CF Revision Manager

The CF Revision Manager will take registered post meta fields and version them when post-revisions are made. The post-meta is duplicated and attached to the specific post-revision that is made. Registered post meta items will appear in the post-revision inspection screen. Post meta items do not show up in the post-comparison feature.

## Registering Your Post Meta

The CFR plugin includes a registration function to easily include your post-meta in the revision scheme.

	function my_registered_post_meta_item() {
		if (function_exists('cfr_register_metadata')) {
			cfr_register_metadata('my_metadata_key');
		}
	}
	add_action('init', 'my_registered_post_meta_item');
	
## Prettifying Your Post Meta

By default the post meta is run through `print_r` (if its an object or array) and then through `htmlspecialchars`. Register a callback function along with your post meta key to override the default display of your post meta in the revision screen.

	function my_registered_post_meta_item() {
		if (function_exists('cfr_register_metadata')) {
			cfr_register_metadata('my_metadata_key', 'prettify_my_postmeta');
		}
	}
	add_action('init', 'my_registered_post_meta_item');
	
	function prettify_my_postmeta($postmeta) {
		// make the post meta data presentable
		
		return $postmeta;
	}