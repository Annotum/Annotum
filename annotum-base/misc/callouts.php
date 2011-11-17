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
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) { die(); }

$opts = get_option('anno_callouts');
if(!$opts) {
	return;
}
$callouts = array();
// We only want options that have things in them...
$callouts = array();
if (!empty($opts) && is_array($opts)) {
	foreach ($opts as $arr) {
		foreach ($arr as $value) {
			if (!$value) { continue; }
			$callouts[] = $arr;
			break;
		}
	}
}
$num = count($callouts);
if ($num > 0) :
?>
<div class="callouts callouts-<?php echo $num; ?>x">
	<ul class="clearfix">
		<?php
		$i = 0;
		foreach ($callouts as $callout):
			$li_class = ($i == 0 ? ' class="first-child"' : '');
			$url = esc_url($callout['url']);
			$title = esc_attr($callout['title']);
			$content = $callout['content'];
			?>
			<li<?php echo $li_class; ?>>
				<section class="callout-item">
					<?php if ($title): ?>
						<?php if ($url): ?>
							<h1 class="title"><a href="<?php echo $url ?>"><?php echo $title; ?></a></h1>
						<?php else: ?>
							<h1 class="title"><?php echo $title; ?></h1>
						<?php endif ?>
					<?php endif ?>
					<div class="content">
						<?php echo $content; ?>
					</div><!-- .content -->
				</section>
			</li>
		<?php
			$i++;
		endforeach ?>
	</ul>
</div>
<?php
endif; ?>