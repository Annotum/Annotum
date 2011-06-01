<?php
/**
 * Sends a notification email.
 *
 * @param string $type The type of notification to send
 * @param stdObj $post WP Post object
 * @param stdObj $comment WP Comment object
 * @param array $recipients Array of email addresses to send the notification to. This will merge with predetermined values.
 * @return bool true if mail sent successfully, false otherwise.
 */
function annowf_send_notification($type, $post = null, $comment = null, $recipients = null) {
	if (empty($type)) {
		return false;
	}
	$notification = annowf_notification_message($type, $post, $comment);
	if (is_null($recipients) || !is_array($recipients)) {
		$recipients = annowf_notification_recipients($type, $post);
	}
	else if (is_array($recipients)) {
		$recipients = array_merge($recipients, annowf_notification_recipients($type, $post));
	}
	$recipients = apply_filters('annowf_notification_recipients', array_unique($recipients), $type, $post);

 	return wp_mail($recipients, $notification['subject'], $notification['body']);
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
			$recipients = array_merge($recipients, annowf_get_role_emails('editor'));
		case 'in_review':
			$recipients = array_merge($recipients, annowf_get_role_emails('author'));
			$recipients = array_merge($recipients, annowf_get_role_emails('administrator'));
			break;
		case 'revisions':
		case 'rejected':
		case 'approved':
		case 'general_comment':
			$recipients = array_merge($recipients, annowf_get_role_emails('author'));
			$recipients = array_merge($recipients, annowf_get_role_emails('administrator'));
			$recipients = array_merge($recipients, annowf_get_role_emails('reviewer'));
		case 'published':
			$recipients = array_merge($recipients, annowf_get_role_emails('editor'));
			break;
		case 'reviewer_comment':
			$recipients = array($recipients, annowf_get_role_emails('editor'));
			$recipients = array_merge($recipients, annowf_get_role_emails('administrator'));
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
 * @return array Array consisting of email title and body
 */
function annowf_notification_message($type, $post, $comment) {
	// TODO, actual footer
	$footer = 'FOOTER';
	
	$author = get_userdata($post->post_author);
	$author = annowf_user_display($author);
	

	$authors = anno_get_post_users($post_id, '_co_authors');
	$authors = array_merge(array($post->post_author), $authors);	
	$author_names = array_map('annowf_user_display', $authors);

	$edit_link = get_edit_post_link($post->ID, null);
	$edit_link = sprintf(__('Edit This Article: %s', 'anno'), $edit_link);
	$title = $post->post_title;
	
	//TODO URL, wording
	$reviewer_instructions = __('Reviewer Instructions', 'anno');
	
	$notification = array('subject' => '', 'body' => '');
	switch ($type) {
		// Status change to: submitted
		case 'submitted':
			$notification = array(
				'subject' => sprintf(__('New Submission: %s by %s.', 'anno'), $title, $author),
				'body' => sprintf(__(
'The following article has been submitted for review:
--------------------
Title: %s
Author(s): %s
Excerpt: %s
%s

%s', 'anno'), $title, implode(',', $author_names), $post->post_excerpt, $edit_link, $footer)	
			);
			break;
		// Status change to: in_review from submitted
		case 'in_review':
			$notification = array(
				'subject' => sprintf(__('%s now in review.', 'anno'), $title),
				'body' => sprintf(__(
'Review has begun for: %s

%s

%s', 'anno'), $title, $edit_link, $footer)	
			);
			break;
		// Status change to: in_review from draft (revisions have occured)
		case 're_review':
			$notification = array(
				'subject' => sprintf(__('%s now in review.', 'anno'), $title),
				'body' => sprintf(__(
'Revisions have been made for %s and we ask you to please re-review the article.

%s

%s', 'anno'), $title, $edit_link, $footer),
			);
			break;
		// Status change to: approved
		case 'approved':
			$notification = array(
				'subject' => sprintf(__('%s review is complete. APPROVED.', 'anno'), $title),
				'body' => sprintf(__(
'Thank you for your submission. We are pleased to inform you that your article, %s, has been approved!

You will receive an additional notification when the article is published.

Thank you.

%s', 'anno'), $title, $footer),
			);
			break;
		// Status change to: rejected
		case 'rejected':
			$notification = array(
				'subject' => sprintf(__('%s review is complete.', 'anno'), $title),
				'body' => sprintf(__(
'Thank you for your submission, %s.  After our review process, we have decided not to accept your article at this time.  
--------------------
Title: %s
%s

%s', 'anno'), $title, $title, $edit_link, $footer),
			);
			break;
		// Status change to: draft (from in_review)
		case 'revisions':
			$notification = array(
				'subject' => sprintf(__('%s review is complete.', 'anno'), $title),
				'body' => sprintf(__(
'Thank you for your submission, %s.  After our review process, we have decided not to accept your article at this time.  
--------------------
Title: %s
%s

%s', 'anno'), $title, $title, $edit_link, $footer),
				);
				break;
		// Status change to: published
		case 'published':
			$notification = array(
				'subject' => sprintf(__('%s has been published.', 'anno'), $title),
				'body' => sprintf(__(
'The following article has been published: %s

%s

%s', 'anno'), $title, $edit_link, $footer)	
			);
			break;
		case 'reviewer_added':
			$notification = array(
				'subject' => sprintf(__('You have been invited to review %s by %s', 'anno'), $title, $author),
				'body' => sprintf(__(
'Please review the following article:
--------------------
Title: %s
Author(s): %s
Excerpt: %s
%s

%s
%s', 'anno'), $title, implode($author_names), $post->post_excerpt, $edit_link, $reviewer_instructions, $footer),
			);
			break;
			case 'co_author_added':
				$notification = array(
					'subject' => sprintf(__('You have been invited to co-author %s by %s', 'anno'), $title, $author),
					'body' => sprintf(__(
'You are have been invited to co-author %s by %s.
%s

%s', 'anno'), $title, $author, $foorer),
				);
				break;
		default:
			break;
	}
	if (!empty($comment)) {
		$comment_author = annowf_user_display($comment->user_id);
		$comment_edit_link =  get_edit_post_link($comment->comment_post_ID, null).'#comment-'.$comment->comment_ID;
		switch ($type) {
			case 'general_comment':
				$notification = array(
					'subject' => sprintf(__('New internal comment on %s.', 'anno'), $title),
					'body' => sprintf(__(
'The following comment was submitted on %s by %s.
--------------------
%s
--------------------
%s

%s', 'anno'), $title, $comment_author, $comment->comment_content, $comment_edit_link, $footer),
				);
				break;
			case 'general_comment_reply':
				$notification = array(
					'subject' => sprintf(__('Reply to internal comment on %s', 'anno'), $title),
					'body' => sprintf(__(
'%s has replied to your internal comment on %s.
--------------------
%s
--------------------
%s

%s', 'anno'), $comment_author, $title, $comment->comment_content, $comment_edit_link, $footer),
				);
				break;
			case 'review_comment':
				$notification = array(
					'subject' => sprintf(__('New reviewer comment on %s', 'anno'), $title),
					'body' => sprintf(__(
'The following comment was submitted on %s by %s.
--------------------
%s
--------------------
%s

%s', 'anno'), $title, $comment_author, $comment->comment_content, $comment_edit_link, $footer),
				);
				break;
			case 'review_comment_reply':
				$notification = array(
					'subject' => sprintf(__('Reply to reviewer comment on %s', 'anno'), $title),
					'body' => sprintf(__(
'%s has replied to your reviewer comment on %s.
--------------------
%s
--------------------
%s

%s', 'anno'), $comment_author, $title, $comment->comment_content, $comment_edit_link, $footer),
				);
				break;
			default:
				break;
		}
	}
	
	return apply_filters('annowf_notfication', $notification, $type, $post, $comment);
}
?>