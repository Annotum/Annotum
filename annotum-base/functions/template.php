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

/**
 * Globally keep around instances without polluting the global namespace.
 * Get and set instances with a keyword.
 * Also allows for easy overrides by re-keeping something with the same key.
 * Replaced keys must be an instance of the original.
 */
class Anno_Keeper {
	protected static $instances = array();
	public static function keep($key, $instance) {
		if (isset(self::$instances[$key]) && !($instance instanceof self::$instances[$key])) {
			throw new Exception('If you\'re going to replace an instance that already exists, the new instance must be an instanceof the original class, so that methods may safely be called.', 1);
		}
		self::$instances[$key] = $instance;
	}
	public static function retrieve($key) {
		return self::$instances[$key];
	}
	public function discard($key) {
		unset(self::$instances[$key]);
	}
}

class Anno_Template {
	/**
	 * An out for transient caches used in this class so you can turn them off for testing.
	 */
	protected $enable_caches = true;
	
	/**
	 * Attach WordPress hooks. This should be a single point of attachment for all hooks in this
	 * class. It should be called once per instance.
	 */
	public function attach_hooks() {
		add_action('save_post', array($this, 'invalidate_citation_cache'), 10, 2);
		add_action('deleted_post', array($this, 'invalidate_citation_cache'));
		add_action('wp', array($this, 'add_assets'));
	}
	
	public function add_assets() {
		wp_enqueue_script('twitter', 'http://platform.twitter.com/widgets.js', array(), null, true);
	}
	
	public function post_id_for_sure($post_id) {
		if (!$post_id) {
			$post_id = get_the_ID();
		}
		return $post_id;
	}
	
	public function strip_newlines($text) {
		return preg_replace("/[\n\r]/", '', $text);
	}
	
	/**
	 * Truncate to a certain number of words
	 */
	public function truncate_words($text, $length, $more_delimiter = '&hellip;') {
		$text =  strip_tags($text);

		$words = explode(' ', $text, $length + 1);
		if (count($words) > $length) {
			array_pop($words);
			$text = implode(' ', $words);
			$text = $text . $more_delimiter;
		}

		return $text;
	}
	
	/**
	 * A getter for the_excerpt(). The excerpt doesn't have a getter that
	 * is run through all the relevant filters. We'll do that here.
	 */
	public function get_excerpt() {
		ob_start();
		the_excerpt();
		return ob_get_clean();
	}
	
	/**
	 * Turn an array or two into HTML attribute string
	 */
	public function to_attr($arr1 = array(), $arr2 = array()) {
		$attrs = array();
		$arr = array_merge($arr1, $arr2);
		foreach ($arr as $key => $value) {
			$attrs[] = esc_attr($key).'="'.esc_attr($value).'"';
		}
		return implode(' ', $attrs);
	}
	
	public function to_tag($tag, $text, $attr1 = array(), $attr2 = array()) {
		$tag = esc_attr($tag);
		return '<'.$tag.' '.$this->to_attr($attr1, $attr2).'>'.$text.'</'.$tag.'>';
	}

	/**
	 * Get an array of ids for contributors to a given post.
	 * @param int $post_id (optional) the ID of the post to get from. Defaults to current post.
	 * @return array
	 */
	public function get_contributor_ids($post_id = null) {
		if ($post_id) {
			$post = get_post($post_id);
			$author_id = $post->post_author;
			unset($post);
		}
		else {
			$post_id = get_the_ID();
			$author_id = get_the_author_meta('id');
		}

		$authors = array();

		/* Get the additional contributors, if the workflow is turned on. */
		if (function_exists('annowf_get_post_users')) {
			$authors = annowf_get_post_users($post->ID, '_co_authors');
		}
		/* Everybody together now! */
		array_unshift($authors, $author_id);

		return $authors;
	}

	/**
	 * Get the subtitle data stored as post meta
	 */
	public function get_subtitle($post_id = false) {
		$post_id = $this->post_id_for_sure($post_id);
		return get_post_meta($post_id, '_anno_subtitle', true);
	}
	
	/**
	 * Get the HTML list for authors.
	 * @param int $post_id (optional)
	 */
	public function get_contributors_list($post_id = null) {
		$out = '';
		$post_id = $this->post_id_for_sure($post_id);
		$authors = $this->get_contributor_ids($post_id);
		
		foreach ($authors as $id) {
			$author = get_userdata($id);
			$posts_url = get_author_posts_url($id);
			// Name
			$first_name = esc_attr($author->user_firstname);
			$last_name = esc_attr($author->user_lastname);

			if ($first_name && $last_name) {
				$fn = '<a href="'.$posts_url.'" class="url name"><span class="given-name">'.$first_name.'</span> <span class="family-name">'.$last_name.'</span></a>';
			}
			else {
				$fn = '<a href="'.$posts_url.'" class="fn url">' . esc_attr($author->display_name) . '</a>';
			}

			// Website
			$trimmed_url = substr($author->user_url, 0, 20);
			$trimmed_url = ($trimmed_url != $author->user_url ? $trimmed_url . '&hellip;' : $author->user_url);

			$website = $author->user_url ? '<span class="group">' . __('Website:', 'anno') . ' <a class="url" href="' . esc_url($author->user_url) . '">' . $trimmed_url . '</a></span>' : '';

			// Note
			$note = $author->user_description ? '<span class="group note">' . esc_attr($author->user_description) . '</span>' : '';

			// @TODO Honoraries (PHD, etc)
			// @TODO organization (MIT, etc)

			$card = '
	<li>
		<div class="author vcard">
			'.$fn;

		if ($website || $note) {
			$card .= '
			<span class="extra">
				<span class="extra-in">
					'.$website.'
					'.$note.'
				</span>
			</span>';
		}

		$card .= '
		</div>
	</li>';

			$out .= $card;
		}

		return $out;
	}
	
	/**
	 * Text-only citation -- safe for textareas.
	 * Output is cached for 1 hour unless cache is invalidated by updating the post.
	 * @param int $post_id (optional) id of post to cite.
	 */
	public function get_citation($post_id = null) {
		$post_id = $this->post_id_for_sure($post_id);
		$cache_key = 'anno_citation_html_'.$post_id;
		
		/* Do we already have this cached? Let's return that. */
		$cache = get_transient($cache_key);
		if ($cache !== false && $this->enable_caches !== false) {
			return $cache;
		}
		
		/* Otherwise, let's build a cache and return it */

		$site = strip_tags(get_bloginfo('name'));
		$permalink = get_permalink();
		$last_modified = get_the_modified_date('Y M j');

		$title = get_the_title($post_id);
		$subtitle = $this->get_subtitle($post_id);
		if ($title && $subtitle) {
			$title = sprintf(_x('%1$s: %2$s', 'Title and subtitle as a textarea-safe string', 'anno'), $title, $subtitle);
		}

		$contributors = $this->get_contributor_ids($post_id);

		$names = array();
		foreach ($contributors as $id) {
			$first = get_user_meta($id, 'first_name', true);
			$last = get_user_meta($id, 'last_name', true);
			if ($first && $last) {
				$name = sprintf(_x('%1$s %2$s', 'First and last name as a textarea-safe string', 'anno'), $first, $last);
			}
			else {
				$user = get_user_by('id', $id);
				$name = $user->display_name;
				unset($user);
			}
			$names[] = $name;
		}
		$authors = implode(', ', $names);

		$version = count(wp_get_post_revisions($post_id));
		if ($version === 0) {
			$version = 1;
		}

		$citation = sprintf(
			_x('%1$s. %2$s [Internet]. Version %3$s. %4$s. %5$s. Available from: %6$s.', 'Citation format', 'anno'),
			$authors,
			$title,
			$version,
			$site,
			$last_modified,
			$permalink
		);
		
		set_transient($cache_key, $citation, 60*60); // Cache for 1 hour.
		return $citation;
	}
	
	/**
	 * Delete citation caches. Run on post update hook.
	 */
	public function invalidate_citation_cache($post_id) {
		delete_transient('anno_citation_html_'.$post_id);
	}
	
	/**
	 * Get content string from a specified meta key and run it through wptexturize().
	 */
	public function texturized_meta($post_id = null, $key) {
		$post_id = $this->post_id_for_sure($post_id);
		$text = trim(get_post_meta($post_id, $key, true));
		return wptexturize($text);
	}
	
	public function get_funding_statement($post_id = null) {
		return $this->texturized_meta($post_id, '_anno_funding');
	}
	
	public function get_acknowledgements($post_id = null) {
		return $this->texturized_meta($post_id, '_anno_acknowledgements');
	}
	
	public function get_appendices($post_id = null) {
		$out = '';
		$post_id = $this->post_id_for_sure($post_id);
		$appendices = get_post_meta($post_id, '_anno_appendicies', true);
		if (is_array($appendices) && count($appendices)) {
			$title_text = _x('Appendix %s', 'Appendix title displayed in post, auto-incremented for each appendix.', 'anno');

			$out .= '<div class="appendices">';

			for ($i=0, $count = count($appendices); $i < $count; $i++) {
				$title = '<h1><span>'.sprintf($title_text, $i + 1).'</span></h1>';
				$content = $appendices[$i];
				
				$out .= '<section class="appendix sec">'.$title.$content.'</section>';
			}

			$out .= '</div>';
		}
		return $out;
	}
	
	public function get_twitter_button($text = null, $attr = array()) {
		if (!$text) {
			$text = _x('Tweet', 'Text for Twitter button', 'anno');
		}
		$title = $this->truncate_words(get_the_title(), 5);
		$url = urlencode(get_permalink());
		$default_attr = array(
			'href' => 'http://twitter.com/share?url='.$url.'&amp;text='.$title,
			'class' => 'twitter-share-button',
			'data-count' => 'none'
		);
		return $this->to_tag('a', $text, $default_attr, $attr);
	}
	
	public function get_facebook_button($attr = array()) {
		$url = urlencode(get_permalink());
		$default_attr = array(
			'src' => 'http://www.facebook.com/plugins/like.php?href='.$url.'&amp;send=false&amp;layout=button_count&amp;width=90&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font=arial&amp;height=21',
			'class' => 'facebook-like-button',
			'scrolling' => 'no',
			'frameborder' => 0,
			'allowTransparency' => true,
			'style' => 'width:90px;height:21px'
		);
		return $this->to_tag('iframe', '', $default_attr, $attr);
	}
	
	public function get_email_link($text = null, $attr = array()) {
		if (!$text) {
			$text = _x('Email', 'Text for "email this" link', 'anno');
		}
		
		$title = esc_attr($this->truncate_words(get_the_title(), 5));
		$url = urlencode(get_permalink());
		
		$excerpt = strip_tags($this->get_excerpt());
		$excerpt = $this->strip_newlines($excerpt);
		$excerpt = $this->truncate_words($excerpt, 10);
		$excerpt = esc_attr($excerpt);
		
		$default_attr = array(
			'href' => 'mailto:?subject='.$title.'&amp;body='.$excerpt.'%0A%0A '.$url,
			'class' => 'email'
		);
		return $this->to_tag('a', $text, $default_attr, $attr);
	}
}

/**
 * Instantiate Anno_Template. Let theme authors override.
 */
function anno_template_init() {
	$template = new Anno_Template();
	$template->attach_hooks();
	Anno_Keeper::keep('template', $template);
}
add_action('init', 'anno_template_init');

/**
 * Check if an article has a subtitle
 */
function anno_has_subtitle($post_id = false) {
	$template = Anno_Keeper::retrieve('template');
	return (bool) $template->get_subtitle($post_id);
}

/**
 * Output subtitle data stored as post meta
 */
function anno_the_subtitle() {
	$template = Anno_Keeper::retrieve('template');
	echo $template->get_subtitle();
}

/**
 * Article Category is a custom taxonomy for articles
 */
function anno_the_terms($taxonomy = 'article_category', $before = '', $sep = '', $after = '') {
	$post_id = get_the_ID();
	echo get_the_term_list($post_id, $taxonomy, $before, $sep, $after);
}

/**
 * Render an HTML list of all the authors, including meta info like bio and URL.
 * @return string
 */
function anno_the_authors() {
	$template = Anno_Keeper::retrieve('template');
	echo $template->get_contributors_list();
}

/**
 * Get citation for article. Textarea safe
 * @return string text-only (no-tags) citation for an article
 */
function anno_the_citation() {
	$template = Anno_Keeper::retrieve('template');
	echo $template->get_citation();
}

function anno_has_acknowledgements() {
	$template = Anno_Keeper::retrieve('template');
	return (bool) $template->get_acknowledgements();
}

function anno_the_acknowledgements() {
	$template = Anno_Keeper::retrieve('template');
	echo $template->get_acknowledgements();
}

function anno_has_funding_statement() {
	$template = Anno_Keeper::retrieve('template');
	return (bool) $template->get_funding_statement();
}

function anno_the_funding_statement() {
	$template = Anno_Keeper::retrieve('template');
	echo $template->get_funding_statement();
}

function anno_the_appendices() {
	$template = Anno_Keeper::retrieve('template');
	echo $template->get_appendices();
}

function anno_twitter_button($text = null, $attr = array()) {
	$template = Anno_Keeper::retrieve('template');
	echo $template->get_twitter_button($text, $attr);
}

function anno_facebook_button($attr = array()) {
	$template = Anno_Keeper::retrieve('template');
	echo $template->get_facebook_button($attr);
}
function anno_email_link($text = null, $attr = array()) {
	$template = Anno_Keeper::retrieve('template');
	echo $template->get_email_link($attr);
}
?>