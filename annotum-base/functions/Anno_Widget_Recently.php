<?php
/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2010 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
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
	public $enable_cache = false;
	public $uid;
	
	protected static $script_initiated = false;
	
	public function __construct() {
		$args = array(
			'description' => __('Display the most recent posts and comments in a tabbed box.', 'anno'),
			'classname' => 'widget-recent-posts'
		);
		parent::__construct('anno_recently', __('Recently&hellip;'), $args);
		
		$this->uid = uniqid('anno-recently');
		
		// Clear widget cache on one of these important events
		add_action( 'save_post', array($this, 'flush_widget_cache') );
		add_action( 'deleted_post', array($this, 'flush_widget_cache') );
		add_action( 'switch_theme', array($this, 'flush_widget_cache') );
		add_action( 'comment_post', array($this, 'flush_widget_cache') );
		add_action( 'transition_comment_status', array($this, 'flush_widget_cache') );
	}
	
	public function widget($args, $instance) {
		$cache = get_transient($this->key);
		if ($cache === false || $this->enable_cache === false) {
			ob_start();
				$this->cached($args, $instance);
			$cache = ob_get_clean();
			set_transient($this->key, $cache, $this->timeout);
		}
		echo $cache;
	}

	public function cached($args, $instance) { ?>
<aside class="widget widget-recent-posts">
	<div class="tabs">
		<ul class="nav">
			<li><a href="#p1-<?php echo $this->uid; ?>">Recent Posts</a></li>
			<li><a href="#p2-<?php echo $this->uid; ?>">Comments</a></li>
		</ul>
		<div class="panel" id="p1-<?php echo $this->uid; ?>">
			<ol>
				<li><a href="#">Lorem ipsum dolor sit amet, consectetuer adi piscing tristiqut elit</a></li>
				<li><a href="#">Integer vitae libero ac risus egestas placerat vestibulum commodo felis quis tortor</a></li>
				<li><a href="#">Ut aliquam sollicitudin leo cras ornare Cras iaculis ultricies nulla auctor dapibus neque</a></li>
				<li><a href="#">Nunc dignissim risus id metus fusce lobortis lorem at ipsum semper sagittis</a></li>
				<li><a href="#">Vivamus vestibulum nulla nec ante</a></li>
			</ol>
		</div>
		<div class="panel" id="p2-<?php echo $this->uid; ?>">
			<ul>
				<li class="recentcomments"><a class="url" rel="external nofollow" href="#">Mr WordPress</a> on <a href="#">Hello world!</a></li>
				<li class="recentcomments">admin on <a href="#">Comment Test</a></li>
				<li class="recentcomments">Test Contributor on <a href="#">Comment Test</a></li>
				<li class="recentcomments">tellyworthtest2 on <a href="#">Comment Test</a></li>
				<li class="recentcomments">Test Author on <a href="#">Comment Test</a></li>
			</ul>
		</div>
	</div>
</aside>
	<?php
	}
	
	public function flush_widget_cache() {
		delete_transient($this->key);
	}
}
?>