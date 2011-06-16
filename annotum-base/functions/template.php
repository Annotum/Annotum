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
	}
	
	/**
	 * Get the subtitle data stored as post meta
	 */
	public function get_subtitle($post_id = false) {
		if (!$post_id) {
			$post_id = get_the_ID();
		}
		return get_post_meta($post_id, '_anno_subtitle', true);
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
	 * Get the HTML list for authors.
	 * @param int $post_id (optional)
	 */
	public function get_contributors_list($post_id = null) {
		$out = '';
		if (!$post_id) {
			$post_id = get_the_ID();
		}
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
		if (!$post_id) {
			$post_id = get_the_ID();
		}
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
	return $template->get_subtitle($post_id) ? true : false;
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
function anno_citation() {
	$template = Anno_Keeper::retrieve('template');
	echo $template->get_citation();
}
?>