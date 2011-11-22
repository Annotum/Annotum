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

class Anno_Cacheer {
	public $key;
	public $timeout = 3600; // 1hr
	public $enable_cache = true;
	public function __construct($key = '', $timeout = false) {
		if ($key) {
			$this->key = $key;
		}
		if ($timeout) {
			$this->timeout = $timeout;
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
	public $enable_cache = true;

	public function cached() {
		$q = new WP_Query(array(
			'meta_query' => array(array(
					'key' => '_anno_featured',
					'value' => 'on'
			)),
			'post_type' => 'article',
			'posts_per_page' => 5
		));
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