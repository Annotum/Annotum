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
				$this->view();
			$cache = ob_get_clean();
			
			set_transient($this->key, $cache, $this->timeout);
		}
		echo $cache;
	}
	
	public function view() {
		// Do something...
	}
}

class Anno_Featured_Articles extends Anno_Cacheer {
	public $key = 'anno_featured_posts';
	public $enable_cache = false;
	public static $already_shown = array();
	
	public function view() {
		$q = new WP_Query(array(
			'post_type' => 'article',
			'posts_per_page' => 5,
			'exclude' => self::$already_shown
		));
		if ($q->have_posts()) {
			?>
<div class="featured-posts carousel">
	<ul>
			<?php
			while ($q->have_posts()) {
				$q->the_post();
				$this->render_item();
			}
			 ?>
	</ul>
	<div class="control-panel">
		2 of 4
		<a class="previous imr" href="#">Previous</a>
		<a class="next imr" href="#">Next</a>
	</div>
</div>
			<?php
			wp_reset_postdata();
		}
	}
	
	public function render_item() { ?>
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
	}
}
class Anno_Teaser_Articles extends Anno_Featured_Articles
?>