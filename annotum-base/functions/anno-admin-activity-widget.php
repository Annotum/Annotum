<?php

/**
 * Replace admin dashboard activity widget with one that shows posts and arfticles.
 * Based on code here: http://wordpress.org/support/topic/show-custom-post-types-in-dashboard-activity-widget
 */

// unregister the default activity widget
add_action('wp_dashboard_setup', 'anno_remove_dashboard_widgets' );
function anno_remove_dashboard_widgets() {
	global $wp_meta_boxes;
	unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
}

// register your custom activity widget
add_action('wp_dashboard_setup', 'anno_add_custom_dashboard_activity' );
function anno_add_custom_dashboard_activity() {
	wp_add_dashboard_widget('anno_custom_dashboard_activity', 'Activities', 'anno_custom_wp_dashboard_site_activity');
}

// the new function based on wp_dashboard_recent_posts (in wp-admin/includes/dashboard.php)
function anno_wp_dashboard_recent_post_types( $args ) {

/* Chenged from here */

	if ( ! $args['post_type'] ) {
		$args['post_type'] = 'any';
	}

	$query_args = array(
		'post_type'	  => $args['post_type'],

/* to here */

		'post_status'	=> $args['status'],
		'orderby'		=> 'date',
		'order'		  => $args['order'],
		'posts_per_page' => intval( $args['max'] ),
		'no_found_rows'  => true,
		'cache_results'  => false
	);
	$posts = new WP_Query( $query_args );

	if ( $posts->have_posts() ) {

		echo '<div id="' . $args['id'] . '" class="activity-block">';
		echo '<h4>' . $args['title'] . '</h4>';
		echo '<ul>';

		$i = 0;
		$today	= date( 'Y-m-d', current_time( 'timestamp' ) );
		$tomorrow = date( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );

		while ( $posts->have_posts() ) {
			$posts->the_post();

			$time = get_the_time( 'U' );
			if ( date( 'Y-m-d', $time ) == $today ) {
				$relative = __('Today', 'annot');
			}
			elseif ( date( 'Y-m-d', $time ) == $tomorrow ) {
				$relative = __('Tomorrow', 'anno');
			}
			else {
				/* translators: date and time format for recent posts on the dashboard, see http://php.net/date */
				$relative = date_i18n( __('M jS', 'anno'), $time );
			}

 			$text = sprintf(
				/* translators: 1: relative date, 2: time, 4: post title */
 				_x('<span>%1$s, %2$s</span> <a href="%3$s">%4$s</a>', '1: relative date, 2: time, 4: post title', 'anno'),
  				$relative,
  				get_the_time(),
  				get_edit_post_link(),
  				_draft_or_post_title()
  			);

 			$hidden = $i >= $args['display'] ? ' class="hidden"' : '';
 			echo "<li{$hidden}>$text</li>";
			$i++;
		}

		echo '</ul>';
		echo '</div>';

	} else {
		return false;
	}

	wp_reset_postdata();

	return true;
}

// The replacement widget
function anno_custom_wp_dashboard_site_activity() {

	echo '<div id="activity-widget">';

	$future_posts = anno_wp_dashboard_recent_post_types( array(
		'post_type'  => 'post',
		'display' => 5,
		'max' => 5,
		'status' => 'future',
		'order' => 'ASC',
		'title' => __('Future Posts', 'anno'),
		'id' => 'future-posts',
	) );

	$recent_posts = anno_wp_dashboard_recent_post_types( array(
		'post_type'  => 'post',
		'display' => 5,
		'max'	 => 5,
		'status'  => 'publish',
		'order'   => 'DESC',
		'title'   => __('Recent Posts', 'anno'),
		'id'	  => 'published-posts',
	) );

	$recent_posts .= anno_wp_dashboard_recent_post_types( array(
		'post_type'  => 'article',
		'display' => 5,
		'max'	 => 5,
		'status'  => 'publish',
		'order'   => 'DESC',
		'title'   => __('Recent Articles', 'anno'),
		'id'	  => 'published-posts',
	) );

	$recent_comments = wp_dashboard_recent_comments( 10 );

	if ( !$future_posts && !$recent_posts && !$recent_comments ) {
		echo '<div class="no-activity">';
		echo '<p class="smiley"></p>';
		echo '<p>' . __('No activity yet!', 'anno') . '</p>';
		echo '</div>';
	}

	echo '</div>';
}
