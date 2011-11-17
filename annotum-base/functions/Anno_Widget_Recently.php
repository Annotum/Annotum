<?php
/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2011 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 * 
 * This file contains function wrappers for a few custom additions to the standard WordPress
 * template tag milieu.
 */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

class Anno_Widget_Recently extends WP_Widget {
	public $key = 'anno_recently_html';
	public $timeout = 3600; // 1 hour
	public $enable_cache = true;
	public $html_uid;
	public $number = 5;
	
	protected static $script_initiated = false;
	
	public function __construct() {
		$args = array(
			'description' => __('Display the most recent posts and comments in a tabbed box.', 'anno'),
			'classname' => 'widget-recent-posts'
		);
		parent::__construct('anno_recently', __('Recently&hellip;'), $args);
		
		$this->html_uid = uniqid('anno-recently');
		
		// Clear widget cache on one of these important events
		add_action( 'save_post', array($this, 'flush_widget_cache') );
		add_action( 'deleted_post', array($this, 'flush_widget_cache') );
		add_action( 'switch_theme', array($this, 'flush_widget_cache') );
		add_action( 'comment_post', array($this, 'flush_widget_cache') );
		add_action( 'transition_comment_status', array($this, 'flush_widget_cache') );
	}
	
	public function widget($args, $instance) {
		extract($args);
		$cache = get_transient($this->key);
		if ($cache === false || $this->enable_cache === false) {
			ob_start();
				$this->cached($args, $instance);
			$cache = ob_get_clean();
			set_transient($this->key, $cache, $this->timeout);
		}
		echo $before_widget;
		echo $cache;
		echo $after_widget;
	}

	public function cached($args, $instance) { ?>
	<div class="recently-container">
		<div class="tabs">
			<ul class="nav">
				<li><a href="#p1-<?php echo $this->html_uid; ?>"><?php _e('Recent Articles', 'anno'); ?></a></li>
				<li><a href="#p2-<?php echo $this->html_uid; ?>"><?php _e('Comments', 'anno'); ?></a></li>
			</ul>
			<div class="panel first-child" id="p1-<?php echo $this->html_uid; ?>">
				<?php 
				$articles = new WP_Query(array(
					'post_type' => 'article',
					'posts_per_page' => $this->number
				));
				if ($articles->have_posts()) {
					echo '<ol>';
					while ($articles->have_posts()) {
						$articles->the_post();
				 ?>
					<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
				<?php 
					}
					echo '</ol>';
					wp_reset_postdata();
					unset($articles);
				}
				?>
			</div>
			<div class="panel" id="p2-<?php echo $this->html_uid; ?>">
				<?php
				$comments = get_comments(array(
					'number' => $this->number,
					'status' => 'approve',
					'post_status' => 'publish'
				));
				if (count($comments)) {
					echo '<ul>';
					foreach ((array) $comments as $comment) {
				?>
					<li class="recentcomments"><?php
						/* translators: comments widget: 1: comment author, 2: post link */ 
						printf(_x('%1$s on %2$s', 'widgets'), get_comment_author_link(), '<a href="' . esc_url(get_comment_link($comment->comment_ID)) . '">' . get_the_title($comment->comment_post_ID) . '</a>'); 
				?></li>
				<?php
					}
					echo '</ul>';
				}
				?>
			</div>
		</div>
	</div><!-- .recently-container -->
	
	<?php
	}
	
	public function flush_widget_cache() {
		delete_transient($this->key);
	}
}
?>