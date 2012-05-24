<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2011 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */

/**
 * Markup for subscription/notification
 */ 
function anno_subscription_fields($user) {
		$subscribe = get_user_meta($user->ID, '_anno_subscribe', true);
		_e('<h3>Subscriptions</h3>', 'anno'); 
?>
		<table class="form-table">
			<tbody>						
				<tr>
					<th><label for="anno-subscribe"><?php _e('Be notified whenever a new article is published on this blog', 'anno'); ?></label></th>
					<td><input type="checkbox" name="_anno_subscribe" class="regular-text" id="anno-subscribe" value="1"<?php checked($subscribe, 1, true); ?> />
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="anno_subscribe_update" value="1">
<?php	
}
// Hook in before other custom meta fields
add_action('show_user_profile', 'anno_subscription_fields', 9);
add_action('edit_user_profile', 'anno_subscription_fields', 9);

/**
 * Update subscriptions
 */
function anno_subscribe_update($user_id) {
	if (isset($_POST['anno_subscribe_update'])) {
		$value = isset($_POST['_anno_subscribe']) ? $_POST['_anno_subscribe'] : 0;
		update_user_meta($user_id, '_anno_subscribe', $value);
	}
}
add_action('edit_user_profile_update', 'anno_subscribe_update');
add_action('personal_options_update', 'anno_subscribe_update');

?>