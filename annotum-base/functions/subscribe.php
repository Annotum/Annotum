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


/**
 * Send new article notification when a post transitions to the publish state from a none published state
 * @todo add filters to readme
 */
function anno_send_subscription_notification($new_status, $old_status, $post) {
	if ($new_status == 'publish' && $old_status != 'publish' && $post->post_type == 'article') {
		$query = new WP_User_Query(array(
			'meta_key' => '_anno_subscribe',
			'meta_value' => '1',
			'meta_compare' => '=',
		));
		if (is_array($query->results) && !empty($query->results)) {
			$recipients = array();
			foreach ($query->results as $user) {
				$recipients[] = $user->user_email;				
			}
			$blogname = get_option('blogname');


			$subscription_subject = apply_filters('anno_subscription_notification_subject', sprintf(__('A new article has been published on %s', 'anno'), $blogname));
			$subscription_body = apply_filters('anno_subscription_notification_body', sprintf(_x('
%s was published on %s

%s
%s

You may view this article in its entirety at the following link: %s

You are receiving these notifications because you opted in. If you would like to no longer receive these notifications, please visit your profile at %s and update your settings.

', 'order of replacement: Post title, blog name, post title, post excerpt, post permalink, profile url', 'anno'), $post->post_title, $blogname, $post->post_title, $post->post_excerpt, get_permalink($post->ID), admin_url('profile.php')));
			$headers = array('BCC: ' .implode(',', $recipients));
			@wp_mail(null, $subscription_subject, $subscription_body, $headers);
		}
	}
}
add_action('transition_post_status', 'anno_send_subscription_notification', 10, 3);
?>