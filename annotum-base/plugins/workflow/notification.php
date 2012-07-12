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
 * Sends a notification email.
 *
 * @param string $type The type of notification to send
 * @param stdObj $post WP Post object
 * @param stdObj $comment WP Comment object
 * @param array $recipients Array of email addresses to send the notification to. This will merge with predetermined values.
 * @param mixed $single_user (id or WP User Object) User in which the notification directly refers to such as the User which was added as a reviewer. 
 * @param $data array of supporting data for notifications
 * @return bool true if mail sent successfully, false otherwise.
 */
function annowf_send_notification($type, $post = null, $comment = null, $recipients = null, $single_user = null, $data = false) {
	// Ensure that workflow notifications are enabled. This is also enforced prior to calls to annowf_send_notification
	if (!anno_workflow_enabled('notifications')) {
		return false;
	}
	else if (empty($type)) {
		return false;
	}
	
	$notification = annowf_notification_message($type, $post, $comment, $single_user, $data);
	if (is_null($recipients) || !is_array($recipients)) {
		$recipients = annowf_notification_recipients($type, $post);
	}
	else if (is_array($recipients)) {
		$recipients = array_merge($recipients, annowf_notification_recipients($type, $post));
	}
	$recipients = apply_filters('annowf_notification_recipients', array_unique($recipients), $type, $post);
	// Sitewide admin should never recieve any workflow notifications.
	$admin_email = get_option('admin_email');
	if ($key = array_search($admin_email, $recipients)) {
		unset($recipients[$key]);
	}
	$headers = array('BCC: '.implode(', ', array_unique($recipients)));

 	return @wp_mail(null, $notification['subject'], $notification['body'], $headers);
}

/**
 * Calculates a list of recipients based on the type of notification to be sent
 *
 * @param string $type The type of notification to send
 * @param stdObj $post WP Post object
 * @return array Array of emails (or empty).
 */
function annowf_notification_recipients($type, $post) {
	$recipients = array();
	switch ($type) {
		case 'submitted':
		case 're_review':
			$recipients = array_merge($recipients, annowf_get_role_emails('reviewer', $post));
		case 'in_review':
			$recipients = array_merge($recipients, annowf_get_role_emails('editor', $post));		
			$recipients = array_merge($recipients, annowf_get_role_emails('author', $post));	
			$recipients = array_merge($recipients, annowf_get_role_emails('administrator', $post));
			break;
		case 'published':
		case 'revisions':
		case 'rejected':
		case 'approved':
			$recipients = array_merge($recipients, annowf_get_role_emails('editor', $post));
			$recipients = array_merge($recipients, annowf_get_role_emails('author', $post));
			$recipients = array_merge($recipients, annowf_get_role_emails('administrator', $post));
			break;
		case 'general_comment':
			$recipients = array_merge($recipients, annowf_get_role_emails('author', $post));
			$recipients = array_merge($recipients, annowf_get_role_emails('administrator', $post));	
			$recipients = array_merge($recipients, annowf_get_role_emails('reviewer', $post));
			break;
		case 'review_comment':
		case 'review_recommendation':
			$recipients = array_merge($recipients, annowf_get_role_emails('editor', $post));
			$recipients = array_merge($recipients, annowf_get_role_emails('administrator', $post));
			break;
		//Some cases intentionally left blank, should be passed to annowf_send_notification in recipients param.
		default:
			break;
	}
	return $recipients;
}

/**
 * Calculates a email subject and body for a given notification type
 *
 * @param string $type The type of notification to send
 * @param stdObj $post WP Post object
 * @param stdObj $comment WP Comment object
 * @param mixed $single_user (id or object) User in which the notification directly refers to such as the User in which was added as a reviewer. 
 * @return array Array consisting of email title and body
 */
function annowf_notification_message($type, $post, $comment, $single_user = null, $data = false) {
	$footer = get_option('blogname').' '.home_url();
	
	$author = get_userdata($post->post_author);
	$author = anno_user_display($author);
	
	if (!empty($single_user->ID)) {
		$single_user_id = $single_user->ID;
	}
	else {
		$single_user_id = $single_user;
	}
	if (!empty($single_user)) {
		$single_user = anno_user_display($single_user);
	}
	// Used in determining a user's recommendation
	$authors = anno_get_authors($post->ID);
	$author_names = array_map('anno_user_display', $authors);

	$edit_link = get_edit_post_link($post->ID, null);
	$title = $post->post_title;
	
	
	$excerpt = strip_tags($post->post_excerpt ? $post->post_excerpt : $post->post_content);

	if (strlen($excerpt) > 255) {
		$excerpt = substr($excerpt,0,252) . '...';
	}
	
	$reviewer_instructions = _x('To review this article, please visit the URL above and navigate to the Reviews section. You may leave comments and questions in this section as well as providing a general review of \'Approve\', \'Reject\' or \'Request Revisions\' from the dropdown.', 'Instructions sent to reviewers via email notification', 'anno');
	
	$notification = array('subject' => '', 'body' => '');
	switch ($type) {
		// Status change to: submitted
		case 'submitted':
			$notification = array(
				'subject' => sprintf(_x('New Submission: %s by %s.', 'Email notification subject', 'anno'), $title, $author),
				'body' => sprintf(_x(
'The following article has been submitted for review:
--------------------
Title: %s
Author(s): %s
Excerpt: %s
%s

%s', 'Email notification body', 'anno'), $title, implode(',', $author_names), $excerpt, $edit_link, $footer)	
			);
			break;
		// Status change to: in_review from submitted
		case 'in_review':
			$notification = array(
				'subject' => sprintf(_x('%s now in review.', 'Email notification subject', 'anno'), $title),
				'body' => sprintf(_x(
'Review has begun for: %s

%s

%s', 'Email notification body', 'anno'), $title, $edit_link, $footer)	
			);
			break;
		// Status change to: in_review from draft (revisions have occured)
		case 're_review':
			$notification = array(
				'subject' => sprintf(_x('%s now in review.', 'Email notification subject', 'anno'), $title),
				'body' => sprintf(_x(
'Revisions have been made for %s and we ask you to please re-review the article.

%s

%s', 'Email notification body', 'anno'), $title, $edit_link, $footer),
			);
			break;
		// Status change to: approved
		case 'approved':
			$notification = array(
				'subject' => sprintf(_x('%s review is complete. APPROVED.', 'Email notification subject', 'anno'), $title),
				'body' => sprintf(_x(
'Thank you for your contribution to %s. We are pleased to inform you that the article, %s, has been approved!

You will receive an additional notification when the article is published.

Thank you.

%s', 'Email notification body', 'anno'), $title, $title, $footer),
			);
			break;
		// Status change to: rejected
		case 'rejected':
			$notification = array(
				'subject' => sprintf(_x('%s review is complete.', 'Email notification subject', 'anno'), $title),
				'body' => sprintf(_x(
'Thank you for contributing to %s.  After our review process, we have decided not to accept the article at this time.  
--------------------
Title: %s

%s

%s', 'Email notification body', 'anno'), $title, $title, $edit_link, $footer),
			);
			break;
		// Status change to: draft (from in_review)
		case 'revisions':
			$notification = array(
				'subject' => sprintf(_x('%s review is complete. CHANGES REQUESTED.', 'Email notification subject', 'anno'), $title),
				'body' => sprintf(_x(
'Thank you for contributing to %s.  After reviewing your article, we would like to request some changes.  Please open the article here:

%s

and refer to the comments listed.  Once you have updated your submission in accordance with the comments, please resubmit your article.

Thank you.

%s', 'Email notification body', 'anno'), $title, $edit_link, $footer),
				);
				break;
		// Status change to: published
		case 'published':
			$notification = array(
				'subject' => sprintf(_x('%s has been published.', 'Email notification subject', 'anno'), $title),
				'body' => sprintf(_x(
'The following article has been published: %s

%s

%s', 'Email notification body', 'anno'), $title, $edit_link, $footer)	
			);
			break;
		case 'reviewer_added':
			$notification = array(
				'subject' => sprintf(_x('%s has been invited to review %s by %s', 'Email notification subject', 'anno'), $single_user, $title, $author),
				'body' => sprintf(_x(
'%s has been invited to review the following article:
--------------------
Title: %s
Author(s): %s
Excerpt: %s
%s

%s
%s', 'Email notification body', 'anno'), $single_user, $title, implode(', ', $author_names), $excerpt, $edit_link, $reviewer_instructions, $footer),
			);
			break;
		case 'co_author_added':
			$notification = array(
				'subject' => sprintf(_x('%s has been invited to co-author %s by %s', 'Email notification subject', 'anno'), $single_user, $title, $author),
				'body' => sprintf(_x(
'%s has been invited to co-author %s by %s.
%s

%s', 'Email notification body', 'anno'), $single_user, $title, $author, $edit_link, $footer),
				);
			break;
		case 'primary_author': 
			$notification = array(
				'subject' => sprintf(_x('%s is now the primary author on %s', 'Email notification subject', 'anno'), $author, $single_user),
				'body' => sprintf(_x(
'%s is now the primary author on %s.
%s

%s', 'Email notification body', 'anno'), $author, $title, $edit_link, $footer),
				);
			break;
		case 'review_recommendation':
			global $anno_review_options;
			//Get user review
			$review_key = annowf_get_user_review($post->ID, $single_user_id);
			switch ($review_key) {
				// Translate here so the entire sentence can be a translated string instead of bits and pieces
				case 1:
					$action_text = sprintf(_x('%s has reviewed %s and has accepted the article for publication.', 'Email review action text', 'anno'), $single_user, $title);
					break;
				case 2:
					$action_text = sprintf(_x('%s has reviewed %s and has requested revisions be made to the article prior to publication.', 'Email review action text', 'anno'), $single_user, $title);						
					break;
				case 3:
					$action_text = sprintf(_x('%s has reviewed %s and has rejected the article for publication.', 'Email review action text', 'anno'), $single_user, $title);
					break;
				default:
					$action_text = '';
					break;
			}
			
			$notification = array(
				'subject' => sprintf(_x('%s has reviewed %s', 'Email notification subject', 'anno'), $single_user, $title),
				'body' => sprintf(_x(
'%s
%s

%s', 'Email notification body', 'anno'), $action_text, $edit_link, $footer),
			);
			break;
		case 'reviewer_update':		
				// Get review
				$reviewer = get_user_by('id', $single_user_id);
				
				global $anno_review_options;
				$review_key = annowf_get_user_review($post->ID, $single_user_id);
				if ($data['status'] == 'revisions') {
					$status = _x('requested to make revisions', 'Email notification status', 'anno');
				}
				else {
					global $annowf_states;
					// approved, published, rejected
					$status = $annowf_states[$data['status']];
				}
				$notification = array(
					'subject' => sprintf(_x('An article you reviewed, %s, has been %s', 'Email notification subject', 'anno'), $title, $status),
					'body' => sprintf(_x(
'%s by %s has been %s
%s
--------------------
You left a review of : %s
--------------------

Thank you for your contribution, at this time the review process is completed, you will be notified of any further updates to this article.

%s', 'Email notification body', 'anno'), $title, implode(', ', $author_names), $status, $edit_link, $anno_review_options[(int)$review_key], $footer),
			);
			break;
		default:
			break;
	}
	if (!empty($comment)) {
		$comment_author = anno_user_display($comment->user_id);
		$comment_edit_link =  get_edit_post_link($comment->comment_post_ID, null).'#comment-'.$comment->comment_ID;
		switch ($type) {
			case 'general_comment':
				$notification = array(
					'subject' => sprintf(_x('New internal comment on %s', 'Email notification subject', 'anno'), $title),
					'body' => sprintf(_x(
'The following comment was submitted on %s by %s.
--------------------
%s
--------------------
%s

%s', 'Email notification body', 'anno'), $title, $comment_author, $comment->comment_content, $comment_edit_link, $footer),
				);
				break;
			case 'review_comment':
				$notification = array(
					'subject' => sprintf(_x('New reviewer comment on %s', 'Email notification subject', 'anno'), $title),
					'body' => sprintf(_x(
'The following comment was submitted on %s by %s.
--------------------
%s
--------------------
%s

%s', 'Email notification body', 'anno'), $title, $comment_author, $comment->comment_content, $comment_edit_link, $footer),
				);
				break;
			default:
				break;
		}
	}	
	return apply_filters('annowf_notfication', $notification, $type, $post, $comment);
}
?>