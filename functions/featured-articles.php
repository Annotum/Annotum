<?php
class Anno_Cacheer {
	public $key;
	public $timeout = 3600; // 1hr
	public $enable_cache = true;
	public function __construct($key = '', $timeout = false) {
		if ($key) {
			$this->$key = $key;
		}
		if ($timeout) {
			$this->$timeout = $timeout;
		}
	}
	
	public function render() {
		$cache = get_transient($this->key);
		if ($cache === false || $this->enable_cache === false) {
			ob_start();
				$this->cached();
			$cache = ob_get_clean();
			
			set_transient($this->key, $cache, $this->timeout);
		}
		echo $cache;
	}
	
	public function cached() {
		// Do something...
	}
}

class Anno_Featured_Articles extends Anno_Cacheer {
	public $post_meta_key = '_featured';
	public $enable_cache = false;
	public static $already_shown = array();
	public static $keys = array();
	public $number = 5;
	
	public function __construct($key = '', $timeout = false) {
		self::$keys[] = $this->key;
		parent::__construct($key, $timeout);
	}
	
	/* Clear ALL the caches of descendants of this class */
	public function clear_caches() {
		foreach (self::$keys as $key) {
			delete_transient($key);
		}
	}
	
	public function modify_query() {
		return new WP_Query(array(
			'meta_query' => array(
				'key' => $this->post_meta_key,
				'value' => 'yes'
			),
			'post_type' => 'article',
			'posts_per_page' => $this->number,
			'post__not_in' => self::$already_shown
		));
	}
	
	public function cached() {
		$this->clear_caches();
		$q = $this->modify_query();
		if ($q->have_posts()) { ?>
<div id="home-featured" class="featured-posts carousel">
		<ul>
			<?php
			while ($q->have_posts()) {
				$q->the_post(); ?>
			<li>
				<div <?php post_class('carousel-item'); ?>>
					<?php the_post_thumbnail('featured'); ?>
					<h2 class="title"><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>
					<div class="content">
						<?php the_excerpt(); ?>
					</div>
				</div>
			</li>
			<?php
				// Store ID of shown item. Make sure it doesn't get shown twice!
				self::$already_shown[] = get_the_ID();
			}
			?>
		</ul>
</div>
				<?php
			wp_reset_postdata();
		}
	}
}

class Anno_Teaser_Articles extends Anno_Featured_Articles {
	public $number = 3;
	
	public function cached() {
		$this->clear_caches();
		$q = $this->modify_query();

		if ($q->have_posts()) { ?>
<div id="home-featured" class="post-teasers">
		<ul>
			<?php
			while ($q->have_posts()) {
				$q->the_post(); ?>
			<li>
				<div <?php post_class('post-teaser-item'); ?>>
					<?php the_post_thumbnail('post-teaser'); ?>
					<h2 class="title"><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>
					<div class="content">
						<?php the_title(); ?>
					</div>
				</div>
			</li>
			<?php
				// Store ID of shown item. Make sure it doesn't get shown twice!
				self::$already_shown[] = get_the_ID();
			}
			?>
		</ul>
</div>
			<?php
			wp_reset_postdata();
		}
	}
}
?>