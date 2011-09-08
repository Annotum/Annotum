<?php global $comment; ?>
	<div class="header">
		<?php echo get_avatar($comment, 40); ?>
		<h3 class="title"><?php comment_author_link(); ?></h3>
		<time class="published"><?php comment_date(); ?></time>
	</div>