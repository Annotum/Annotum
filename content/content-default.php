<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2010 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }
if (CFCT_DEBUG) { cfct_banner(__FILE__); }

?>
<?php //cfct_misc('tools-nav'); ?>

<article <?php post_class('article'); ?>>
	<?php 
	cfct_template_file('content', 'header');
	cfct_misc('tools-bar');
	cfct_misc('author-list'); ?>
	<div class="content">
		<section class="abstract sec">
			<h1 class="title"><?php _e('Abstract', 'anno'); ?></h1>
			<div class="entry-summary"><?php the_excerpt(); ?></div>
		</section><!--/.abstract-->
		<div class="entry-content">
			<section class="article-section article-section-introduction">
				<h1 class="section-title"><span>Introduction</span></h1>
				<div class="content">
					<p><strong>Lorem ipsum</strong> dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
				</div>
			</section>
			<section class="article-section article-section-methods">
				<h1 class="section-title"><span>Methods</span></h1>
				<div class="content">
					<p><i>Lorem ipsum</i> dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
				</div>
			</section>
			<div class="to-top">
				<a href="#"><span>Top</span></a>
			</div>
		
			<section class="article-section article-section-results">
				<h1 class="section-title"><span>Results</span></h1>
				<div class="content">
					<p><em>Lorem ipsum</em> dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
					<div class="supplement figure">
						<img src="<?php bloginfo('template_url'); ?>/assets/main/img/example-article-figure.jpg" width="150" height="120" alt="Example Article Figure" />
						<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>			
					</div>
					<ol>
						<li>numbered
							<ol>
								<li>numbered</li>
								<li>numbered</li>
								<li>numbered</li>
							</ol>
						</li>
						<li>numbered</li>
						<li>numbered</li>
					</ol>
					<ul>
						<li>bullets
							<ul>
								<li>bullets</li>
								<li>bullets</li>
								<li>bullets</li>
							</ul>	
						</li>
						<li>bullets</li>
						<li>bullets</li>
					</ul>
					<ol>
						<li>numbered
							<ul>
								<li>bullets</li>
								<li>bullets</li>
								<li>bullets</li>
							</ul>
						</li>
						<li>numbered</li>
						<li>numbered</li>
					</ol>
					<ul>
						<li>bullets
							<ol>
								<li>numbered</li>
								<li>numbered</li>
								<li>numbered</li>
							</ol>	
						</li>
						<li>bullets</li>
						<li>bullets</li>
					</ul>
					<dl>
						<dt>Term</dt><dd>Definition</dd>
						<dt>Term</dt><dd>Definition</dd>
						<dt>Term</dt><dd>Definition</dd>
					</dl>
				</div>
			</section>
			<section class="article-section article-section-supporting-information">
				<h1 class="section-title"><span>Supporting Information</span></h1>
				<div class="content">
					<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
					<p><code>Lorem ipsum</code> dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
					<pre>
					Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. 

					Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. 
					</pre>
					<blockquote>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.<blockquote>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</blockquote></blockquote>
				</div>
			</section>
			<?php
			the_content();
			wp_link_pages();
			?>
		</div><!--/.entry-content-->
	</div><!--/.content-->
	<?php cfct_misc('article-references'); ?>
	<footer class="footer">
		<?php
		the_tags('<strong>Tags:</strong> ', ' <span class="sep">&middot;</span> ', '');
		?>
	</footer><!--/.footer-->
</article>