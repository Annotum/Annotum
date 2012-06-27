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
 * Based on code found in WordPress Importer plugin
 */

/**
 * WordPress Importer class for managing parsing of WXR files.
 */
class Knol_WXR_Parser {
	function parse( $file ) {
		
		if (defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG && isset($_POST['anno_knol_parser'])) {
			switch ($_POST['anno_knol_parser']) {
				case 'simplexml':
					$parser = new Knol_WXR_Parser_SimpleXML;
					$result = $parser->parse( $file );
					// If SimpleXML succeeds or this is an invalid WXR file then return the results
					if ( ! is_wp_error( $result ) || 'SimpleXML_parse_error' != $result->get_error_code() )
						return $result;
					break;
				case 'xml':
					$parser = new Knol_WXR_Parser_XML;
					$result = $parser->parse( $file );
					// If XMLParser succeeds or this is an invalid WXR file then return the results
					if ( ! is_wp_error( $result ) || 'XML_parse_error' != $result->get_error_code() )
						return $result;
					break;
				case 'regex':
					$parser = new Knol_WXR_Parser_Regex;
					return $parser->parse( $file );
					break;
				default:
					printf(__('ANNO_IMPORT_DEBUG: Could not find parser %s.', 'anno'), esc_html($_POST['anno_knol_parser']));
					break;
			}
			return;
		}
		
		
		// Attempt to use proper XML parsers first
		if ( extension_loaded( 'simplexml' ) ) {
			$parser = new Knol_WXR_Parser_SimpleXML;
			$result = $parser->parse( $file );

			// If SimpleXML succeeds or this is an invalid WXR file then return the results
			if ( ! is_wp_error( $result ) || 'SimpleXML_parse_error' != $result->get_error_code() )
				return $result;
		} else if ( extension_loaded( 'xml' ) ) {
			$parser = new Knol_WXR_Parser_XML;
			$result = $parser->parse( $file );

			// If XMLParser succeeds or this is an invalid WXR file then return the results
			if ( ! is_wp_error( $result ) || 'XML_parse_error' != $result->get_error_code() )
				return $result;
		}

		// We have a malformed XML file, so display the error and fallthrough to regex
		if ( isset($result) && defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG ) {
			echo '<pre>';
			if ( 'SimpleXML_parse_error' == $result->get_error_code() ) {
				foreach  ( $result->get_error_data() as $error )
					echo $error->line . ':' . $error->column . ' ' . esc_html( $error->message ) . "\n";
			} else if ( 'XML_parse_error' == $result->get_error_code() ) {
				$error = $result->get_error_data();
				echo $error[0] . ':' . $error[1] . ' ' . esc_html( $error[2] );
			}
			echo '</pre>';
			echo '<p><strong>' . __( 'There was an error when reading this WXR file', 'anno' ) . '</strong><br />';
			echo __( 'Details are shown above. The importer will now try again with a different parser...', 'anno' ) . '</p>';
		}

		// use regular expressions if nothing else available or this is bad XML
		$parser = new Knol_WXR_Parser_Regex;
		return $parser->parse( $file );
	}
}


/**
 * WXR Parser that makes use of the SimpleXML PHP extension.
 */
class Knol_WXR_Parser_SimpleXML {	
	var $img_id_modifier = 0;
	
	function parse( $file ) {
		$authors = $posts = $categories = $tags = $terms = array();

		$internal_errors = libxml_use_internal_errors(true);
		$xml = simplexml_load_file( $file );
		// halt if loading produces an error
		if ( ! $xml )
			return new WP_Error( 'SimpleXML_parse_error', __( 'There was an error when reading this WXR file', 'anno' ), libxml_get_errors() );

		$wxr_version = $xml->xpath('/rss/channel/wp:wxr_version');
		if ( ! $wxr_version )
			return new WP_Error( 'WXR_parse_error', __( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'anno' ) );

		$wxr_version = (string) trim( $wxr_version[0] );
		// confirm that we are dealing with the correct file format
		if ( ! preg_match( '/^\d+\.\d+$/', $wxr_version ) )
			return new WP_Error( 'WXR_parse_error', __( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'anno' ) );

		$base_url = 'http://knol.google.com';

		$namespaces = $xml->getDocNamespaces();
		if ( ! isset( $namespaces['wp'] ) )
			$namespaces['wp'] = 'http://wordpress.org/export/1.1/';
		if ( ! isset( $namespaces['excerpt'] ) )
			$namespaces['excerpt'] = 'http://wordpress.org/export/1.1/excerpt/';
		if ( ! isset( $namespaces['content_filtered'] ) )
			$namespaces['content_filtered'] = 'http://wordpress.org/export/1.1/content-filtered/';

		foreach ($xml->xpath('/rss/channel') as $channel) {
			// grab authors

			foreach ( $channel->xpath('wp:author') as $author_arr ) {
				$a = $author_arr->children( $namespaces['wp'] );
				// Knol WXR has Creator stored by 'Knol ID' (author_id) not author_login. author_login is empty in the WXR.
				$author_key = (string) $a->author_id;
				$authors[$author_key] = array(
					'author_id' => (string) $a->author_id,
					'author_login' => $a->author_login,
					'author_email' => (string) $a->author_email,
					'author_display_name' => (string) $a->author_display_name,
					'author_first_name' => (string) $a->author_first_name,
					'author_last_name' => (string) $a->author_last_name
				);
			}

			// grab cats, tags and terms
			foreach ( $channel->xpath('wp:category') as $term_arr ) {
				$t = $term_arr->children( $namespaces['wp'] );
				$categories[] = array(
					'term_id' => (int) $t->term_id,
					'category_nicename' => (string) $t->category_nicename,
					'category_parent' => (string) $t->category_parent,
					'cat_name' => (string) $t->cat_name,
					'category_description' => (string) $t->category_description
				);
			}

			foreach ( $channel->xpath('wp:tag') as $term_arr ) {
				$t = $term_arr->children( $namespaces['wp'] );
				$tags[] = array(
					'term_id' => (int) $t->term_id,
					'tag_slug' => (string) $t->tag_slug,
					'tag_name' => (string) $t->tag_name,
					'tag_description' => (string) $t->tag_description
				);
			}

			foreach ( $channel->xpath('wp:term') as $term_arr ) {
				$t = $term_arr->children( $namespaces['wp'] );
				$terms[] = array(
					'term_id' => (int) $t->term_id,
					'term_taxonomy' => (string) $t->term_taxonomy,
					'slug' => (string) $t->term_slug,
					'term_parent' => (string) $t->term_parent,
					'term_name' => (string) $t->term_name,
					'term_description' => (string) $t->term_description
				);
			}

			// grab posts
			foreach ( $channel->item as $item ) {
				$post = array(
					'post_title' => (string) $item->title,
					'guid' => (string) $item->guid,
				);

				$dc = $item->children( 'http://purl.org/dc/elements/1.1/' );
				$post['post_author'] = (string) $dc->creator;

				$content = $item->children( 'http://purl.org/rss/1.0/modules/content/' );
				$excerpt = $item->children( $namespaces['excerpt'] );
			
				$content_filtered = $item->children( $namespaces['content_filtered'] );
			
				$post['post_content'] = (string) $content->encoded;
				$post['post_excerpt'] = (string) $excerpt->encoded;
				$post['post_content_filtered'] = (string) $content_filtered->encoded;			
			
				$wp = $item->children( $namespaces['wp'] );
				$post['post_id'] = (string) $wp->post_id;
				$post['post_date'] = (string) $wp->post_date;
				$post['post_date_gmt'] = (string) $wp->post_date_gmt;
				$post['comment_status'] = (string) $wp->comment_status;
				$post['ping_status'] = (string) $wp->ping_status;
				$post['post_name'] = (string) $wp->post_name;
				$post['status'] = (string) $wp->status;
				$post['post_parent'] = (int) $wp->post_parent;
				$post['menu_order'] = (int) $wp->menu_order;
				$post['post_type'] = (string) $wp->post_type;
				$post['post_password'] = (string) $wp->post_password;
				$post['is_sticky'] = (int) $wp->is_sticky;

				if ( isset($wp->attachment_url) )
					$post['attachment_url'] = (string) $wp->attachment_url;

				foreach ( $item->category as $c ) {
					$att = $c->attributes();
					if ( isset( $att['nicename'] ) )
						$post['terms'][] = array(
							'name' => (string) $c,
							'slug' => (string) $att['nicename'],
							'domain' => (string) $att['domain']
						);
				}

				foreach ( $wp->postmeta as $meta ) {
					$post['postmeta'][] = array(
						'key' => (string) $meta->meta_key,
						'value' => (string) $meta->meta_value
					);
				}
				
				foreach ( $wp->comment as $comment ) {
					$meta = array();
					if ( isset( $comment->commentmeta ) ) {
						foreach ( $comment->commentmeta as $m ) {
							$meta[] = array(
								'key' => (string) $m->meta_key,
								'value' => (string) $m->meta_value
							);
						}
					}
			
					$post['comments'][] = array(
						'comment_id' => (int) $comment->comment_id,
						'comment_author' => (string) $comment->comment_author,
						'comment_author_email' => (string) $comment->comment_author_email,
						'comment_author_IP' => (string) $comment->comment_author_IP,
						'comment_author_url' => (string) $comment->comment_author_url,
						'comment_date' => ( isset( $comment->comment_gmt ) ) ? (string) $comment->comment_gmt : (string) $comment->comment_date,
                        'comment_date_gmt' => ( isset( $comment->comment_gmt ) ) ? (string) $comment->comment_gmt : (string) $comment->comment_date_gmt,
						'comment_content' => (string) $comment->comment_content,
						'comment_approved' => (string) $comment->comment_approved,
						'comment_type' => (string) $comment->comment_type,
						'comment_parent' => (string) $comment->comment_parent,
						'comment_user_id' => (int) $comment->comment_user_id,
						'commentmeta' => $meta,
					);
				}
				
				$attachment_template = array(
					'upload_date' => $post['post_date_gmt'],
					'post_date' => $post['post_date_gmt'],
					'post_date_gmt' => $post['post_date_gmt'],
					'post_author' => $post['post_author'],
					'post_type' => 'attachment',
					'post_parent' => $post['post_id'],
					'post_id' => '',
					'post_content' => '',
					'post_content_filtered' => '',
					'postmeta' => '',
					'guid' => '',
					'attachment_url' => '',
					'status' => 'inherit',
					'post_title' => '',
					'ping_status' => '',
					'menu_order' => '',
					'post_password' => '',
					'terms' => '',
					'comment_status' => '',
					'is_sticky' => '',
					'post_excerpt' => '',
					'post_name' => '',
				);
				
				$post_attachments = $this->parse_images($content_filtered->encoded, $item->title, $attachment_template);

				$posts[] = $post;
				
				$posts = array_merge($post_attachments, $posts);
			}
		}

		return array(
			'authors' => $authors,
			'posts' => $posts,
			'categories' => $categories,
			'tags' => $tags,
			'terms' => $terms,
			'base_url' => $base_url,
			'version' => $wxr_version,
		);
	}
	
	/**
	 * Parse images from the post content
	 * 
	 * @param string $content The content to parse from
	 * @param string $post_title Title of the post to process
	 * @param array $attachment_template Base for attachment
	 * 
	 * @return array Array of attachments created
	 */ 
	function parse_images($content, $post_title, $attachment_template) {
		$attachments = array();
		
		// Lets make sure we have a wrapper
		$content = '<div>'.$content.'</div>';
		
		$xml = simplexml_load_string($content);
		if (!$xml) {
			// We've encountered ill formed markup (no closing tag on a div for example)
			// Make note, move along, no need to break the entire import process
			if (defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG) {
				error_log(sprintf(_x('There was an error processing %s\'s content for attachments.', 'importer error message', 'anno'), $post_title));
			}			
		}
		else {		
			// img tags, Knols do not export images with anything but img.
			$images = $xml->xpath('//img');
			foreach ($images as $image) {
				$attachment = $attachment_template;
				$attrs = $image->attributes();

				if (!empty($attrs['src']) && $attrs['src'] !== false) {
					if (
						(strpos($attrs['src'], 'http://') === false &&
						 strpos($attrs['src'], 'https://') === false ) 
						|| strpos($attrs['src'], '://knol.google.com') !== false
					 ) {
						$url_explode = explode('/', (string) $attrs['src']);
						$attachment['post_title'] = end($url_explode);

						$attachment['attachment_url'] = $attachment['guid'] = (string) $attrs['src'];
					}
				}
				
				if (!empty($attrs['alt'])) {
					$attachment['post_title'] = $attrs['title'];
					$attachment['_wp_attachment_image_alt'] = array(
						'key' => '_wp_attachment_image_alt',
						'value' => (string) $attrs['alt'],
					);
				}
				
				if (!empty($attrs['title'])) {
					$attachment['post_title'] = (string) $attrs['title'];
				}
				
				if (!empty($attachment['attachment_url'])) {
					$attachment['post_id'] = $attachment['post_parent'].'_img_'.$this->img_id_modifier;
					$this->img_id_modifier++;
					$attachments[] = $attachment;
				}		
			}		
		}
		
		return $attachments;
	}
}

/**
 * WXR Parser that makes use of the XML Parser PHP extension.
 */
class Knol_WXR_Parser_XML {
	var $wp_tags = array(
		'wp:post_id', 'wp:post_date', 'wp:post_date_gmt', 'wp:comment_status', 'wp:ping_status', 'wp:attachment_url',
		'wp:status', 'wp:post_name', 'wp:post_parent', 'wp:menu_order', 'wp:post_type', 'wp:post_password',
		'wp:is_sticky', 'wp:term_id', 'wp:category_nicename', 'wp:category_parent', 'wp:cat_name', 'wp:category_description',
		'wp:tag_slug', 'wp:tag_name', 'wp:tag_description', 'wp:term_taxonomy', 'wp:term_parent',
		'wp:term_name', 'wp:term_description', 'wp:author_id', 'wp:author_login', 'wp:author_email', 'wp:author_display_name',
		'wp:author_first_name', 'wp:author_last_name',
	);
	var $wp_sub_tags = array(
		'wp:comment_id', 'wp:comment_author', 'wp:comment_author_email', 'wp:comment_author_url',
		'wp:comment_author_IP',	'wp:comment_date', 'wp:comment_date_gmt', 'wp:comment_content',
		'wp:comment_approved', 'wp:comment_type', 'wp:comment_parent', 'wp:comment_user_id',
	);

	function parse( $file ) {
		$this->wxr_version = $this->in_post = $this->cdata = $this->data = $this->sub_data = $this->in_tag = $this->in_sub_tag = false;
		$this->authors = $this->posts = $this->term = $this->category = $this->tag = array();

		$xml = xml_parser_create( 'UTF-8' );
		xml_parser_set_option( $xml, XML_OPTION_SKIP_WHITE, 1 );
		xml_parser_set_option( $xml, XML_OPTION_CASE_FOLDING, 0 );
		xml_set_object( $xml, $this );
		xml_set_character_data_handler( $xml, 'cdata' );
		xml_set_element_handler( $xml, 'tag_open', 'tag_close' );

		if ( ! xml_parse( $xml, file_get_contents( $file ), true ) ) {
			$current_line = xml_get_current_line_number( $xml );
			$current_column = xml_get_current_column_number( $xml );
			$error_code = xml_get_error_code( $xml );
			$error_string = xml_error_string( $error_code );
			return new WP_Error( 'XML_parse_error', __('There was an error when reading this WXR file', 'anno'), array( $current_line, $current_column, $error_string ) );
		}
		xml_parser_free( $xml );

		if ( ! preg_match( '/^\d+\.\d+$/', $this->wxr_version ) )
			return new WP_Error( 'WXR_parse_error', __( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'anno' ) );

		return array(
			'authors' => $this->authors,
			'posts' => $this->posts,
			'categories' => $this->category,
			'tags' => $this->tag,
			'terms' => $this->term,
			'base_url' => $this->base_url,
			'version' => $this->wxr_version
		);
	}

	function tag_open( $parse, $tag, $attr ) {
		if ( in_array( $tag, $this->wp_tags ) ) {
			$this->in_tag = substr( $tag, 3 );
			return;
		}

		if ( in_array( $tag, $this->wp_sub_tags ) ) {
			$this->in_sub_tag = substr( $tag, 3 );
			return;
		}

		switch ( $tag ) {
			case 'category':
				if ( isset($attr['domain'], $attr['nicename']) ) {
					$this->sub_data['domain'] = $attr['domain'];
					$this->sub_data['slug'] = $attr['nicename'];
				}
				break;
			case 'item': $this->in_post = true;
			case 'title': if ( $this->in_post ) $this->in_tag = 'post_title'; break;
			case 'guid': $this->in_tag = 'guid'; break;
			case 'dc:creator': $this->in_tag = 'post_author'; break;
			case 'content:encoded': $this->in_tag = 'post_content'; break;
			case 'content_filtered:encoded': $this->in_tag = 'post_content_filtered'; break;
			case 'excerpt:encoded': $this->in_tag = 'post_excerpt'; break;

			case 'wp:term_slug': $this->in_tag = 'slug'; break;
			case 'wp:meta_key': $this->in_sub_tag = 'key'; break;
			case 'wp:meta_value': $this->in_sub_tag = 'value'; break;
		}
	}

	function cdata( $parser, $cdata ) {
		if ( ! trim( $cdata ) )
			return;

		$this->cdata .= trim( $cdata );
	}

	function tag_close( $parser, $tag ) {
		switch ( $tag ) {
			case 'wp:comment':
				unset( $this->sub_data['key'], $this->sub_data['value'] ); // remove meta sub_data
				if ( ! empty( $this->sub_data ) ) {
					$comment_template = array(
						'comment_id' => '',
						'comment_post_ID' => '',
						'comment_author' => '',
						'comment_author_email' => '',
						'comment_author_IP' => '',
						'comment_author_url' => '',
						'comment_date' => '',
						'comment_date_gmt' => '',
						'comment_content' => '',
						'comment_approved' => '',
						'comment_type' => '',
						'comment_parent' => '',
					);
					$this->data['comments'][] = array_merge($comment_template, $this->sub_data);
				}
					
				$this->sub_data = false;
				break;
			case 'wp:commentmeta':
				$this->sub_data['commentmeta'][] = array(
					'key' => $this->sub_data['key'],
					'value' => $this->sub_data['value']
				);
				break;
			case 'category':
				if ( ! empty( $this->sub_data ) ) {
					$this->sub_data['name'] = $this->cdata;
					$this->data['terms'][] = $this->sub_data;
				}
				$this->sub_data = false;
				break;
			case 'wp:postmeta':
				if ( ! empty( $this->sub_data ) )
					$this->data['postmeta'][] = $this->sub_data;
				$this->sub_data = false;
				break;
			case 'item':
				$post_template = array(
					'post_date' => '',
					'post_author' => '',
					'post_type' => '',
					'post_parent' => 0,
					'post_id' => '',
					'post_content' => '',
					'post_content_filtered' => '',
					'postmeta' => '',
					'guid' => '',
					'attachment_url' => '',
					'status' => '',
					'post_title' => '',
					'post_date_gmt' => '',
					'ping_status' => '',
					'menu_order' => '',
					'post_password' => '',
					'terms' => '',
					'comment_status' => '',
					'is_sticky' => '',
					'post_excerpt' => '',
					'post_name' => '',
				);
				$this->posts[] = array_merge($post_template, $this->data);
				$this->data = false;
				break;
			case 'wp:category':
			case 'wp:tag':
			case 'wp:term':
				$n = substr( $tag, 3 );
				array_push( $this->$n, $this->data );
				$this->data = false;
				break;
			case 'wp:author':
				// Knol WXR has Creator stored by  'Knol ID' (author_id) not author_login. author_login is empty in the WXR.
				if (!empty($this->data['author_id'])) {
					$this->authors[$this->data['author_id']] = $this->data;
				}
					
				$this->data = false;
				break;
			case 'wp:base_site_url':
				$this->base_url = 'http://knol.google.com';
				break;
			case 'wp:wxr_version':
				$this->wxr_version = $this->cdata;
				break;
			case 'content_filtered:encoded':
				$filtered_content = $this->cdata;
				$this->parse_images($filtered_content);
				
				break;
			default:
				if ( $this->in_sub_tag ) {
					$this->sub_data[$this->in_sub_tag] = ! empty( $this->cdata ) ? $this->cdata : '';
					$this->in_sub_tag = false;
				} else if ( $this->in_tag ) {
					$this->data[$this->in_tag] = ! empty( $this->cdata ) ? $this->cdata : '';
					$this->in_tag = false;
				}
		}

		$this->cdata = false;
	}
	
	function img_open($parser, $tag, $attributes) {
		// Make sure we have an image tag
 		if (strcasecmp($tag, 'img') === 0) {
			$date_now = date('Y-m-d G:i:s');
			
			$attachment = array(
				'upload_date' => $date_now,
				'post_date' => $date_now,
				'post_date_gmt' => $date_now,
				'post_author' => '',
				'post_type' => 'attachment',
				'post_parent' => 0,
				'post_id' => '',
				'post_content' => '',
				'post_content_filtered' => '',
				'postmeta' => '',
				'guid' => '',
				'attachment_url' => '',
				'status' => 'inherit',
				'post_title' => '',
				'ping_status' => '',
				'menu_order' => '',
				'post_password' => '',
				'terms' => '',
				'comment_status' => '',
				'is_sticky' => '',
				'post_excerpt' => '',
				'post_name' => '',
			);
	
	
			// We have a post parent, this will only occur when the post_id tag is before the content_filtered in the WXR
			if (!empty($this->data['post_id'])) {
				$attachment['post_parent'] = $this->data['post_id'];
			}
			
			if (!empty($this->data['post_date_gmt'])) {
				$attachment['post_date'] = $attachment['post_date_gmt'] = $attachment['upload_date'] = $this->data['post_date_gmt'];
			}
			
			if (!empty($this->data['post_author'])) {
				$attachment['post_author'] = $this->data['post_author'];
			}
			
			if (!empty($attributes['src'])) {
					// We want relative URLS which will be converted to knol urls later or Knol urls.
					if (
						(strpos($attributes['src'], 'http://') === false &&
						 strpos($attributes['src'], 'https://') === false ) 
						|| strpos($attributes['src'], '://knol.google.com' !== false)
					) {
						$url_explode = explode('/', $attributes['src']);
						$attachment['post_title'] = end($url_explode);
						$attachment['attachment_url'] = $attachment['guid'] = $attributes['src'];
					}
				
					if (!empty($attributes['alt'])) {
						$attachment['post_title'] = trim($attributes['alt']);
						$attachment['postmeta'][] = array(
							'key' => '_wp_attachment_image_alt',
							'value' => trim($attributes['alt']),
						);
					}

					if (!empty($attributes['title'])) {
						$attachment['post_title'] = trim($attributes['title']);
					}
			}
			
			// If we have a url, then save the attachment in the posts array.
			if (!empty($attachment['attachment_url'])) {
				$this->posts[] = $attachment;
			}
		}
	}
	
	function img_close() {
		// Do nothing.
	}
	
	function parse_images($content) {
		$xml = xml_parser_create( 'UTF-8' );
		xml_parser_set_option( $xml, XML_OPTION_SKIP_WHITE, 1 );
		xml_parser_set_option( $xml, XML_OPTION_CASE_FOLDING, 0 );
		xml_set_object( $xml, $this );
		xml_set_character_data_handler( $xml, 'cdata' );
		xml_set_element_handler( $xml, 'tag_open', 'tag_close' );

		if ( ! xml_parse( $xml, $content, true ) ) {
			$current_line = xml_get_current_line_number( $xml );
			$current_column = xml_get_current_column_number( $xml );
			$error_code = xml_get_error_code( $xml );
			if (defined('ANNO_IMPORT_DEBUG') && ANNO_IMPORT_DEBUG) {
				error_log(sprintf(_x('XML Parser: There was an error processing the content for attachments. Line %s. Column %s. Code %s.', 'importer error message', 'anno'), $current_line, $current_column, $error_code));
			}
		}
		xml_parser_free( $xml );
	}
}

/**
 * WXR Parser that uses regular expressions. Fallback for installs without an XML parser.
 */
class Knol_WXR_Parser_Regex {
	var $authors = array();
	var $posts = array();
	var $categories = array();
	var $tags = array();
	var $terms = array();
	var $base_url = '';

	function Knol_WXR_Parser_Regex() {
		$this->__construct();
	}

	function __construct() {
		$this->has_gzip = is_callable( 'gzopen' );
	}

	function parse( $file ) {
		$wxr_version = $in_post = false;

		$fp = $this->fopen( $file, 'r' );
		if ( $fp ) {
			while ( ! $this->feof( $fp ) ) {
				$importline = rtrim( $this->fgets( $fp ) );

				if ( ! $wxr_version && preg_match( '|<wp:wxr_version>(\d+\.\d+)</wp:wxr_version>|', $importline, $version ) )
					$wxr_version = $version[1];

				if ( false !== strpos( $importline, '<wp:base_site_url>' ) ) {
					$this->base_url = 'http://knol.google.com';
					continue;
				}
				if ( false !== strpos( $importline, '<wp:category>' ) ) {
					preg_match( '|<wp:category>(.*?)</wp:category>|is', $importline, $category );
					$this->categories[] = $this->process_category( $category[1] );
					continue;
				}
				if ( false !== strpos( $importline, '<wp:tag>' ) ) {
					preg_match( '|<wp:tag>(.*?)</wp:tag>|is', $importline, $tag );
					$this->tags[] = $this->process_tag( $tag[1] );
					continue;
				}
				if ( false !== strpos( $importline, '<wp:term>' ) ) {
					preg_match( '|<wp:term>(.*?)</wp:term>|is', $importline, $term );
					$this->terms[] = $this->process_term( $term[1] );
					continue;
				}
				if ( false !== strpos( $importline, '<wp:author>' ) ) {
					if ( false !== strpos( $importline, '</wp:author>' ) ) {
						$buffer = $importline;
					}
					else {
						$buffer = $next_importline =  $importline;
						
						while(!$this->feof($fp) && (false === strpos( $next_importline, '</wp:author>'))) {
							$next_importline = rtrim( $this->fgets( $fp ) );
							$buffer .= $next_importline;
						}
					}
	
					preg_match( '|<wp:author>(.*?)</wp:author>|is', $buffer, $author );
					$a = $this->process_author( $author[1] );
					// Knol WXR has Creator stored by  'Knol ID' (author_id) not author_login. author_login is empty in the WXR.
					$this->authors[$a['author_id']] = $a;
					continue;
				}
				if ( false !== strpos( $importline, '<item>' ) ) {
					$post = '';
					$in_post = true;
					continue;
				}
				if ( false !== strpos( $importline, '</item>' ) ) {
					$in_post = false;
					$this->posts[] = $this->process_post( $post );
					continue;
				}
				if ( $in_post ) {
					$post .= $importline . "\n";
				}
			}

			$this->fclose($fp);
		}

		if ( ! $wxr_version )
			return new WP_Error( 'WXR_parse_error', __( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'anno' ) );

		return array(
			'authors' => $this->authors,
			'posts' => $this->posts,
			'categories' => $this->categories,
			'tags' => $this->tags,
			'terms' => $this->terms,
			'base_url' => $this->base_url,
			'version' => $wxr_version
		);
	}

	function get_tag( $string, $tag ) {
		global $wpdb;
		preg_match( "|<$tag.*?>(.*?)</$tag>|is", $string, $return );
		if ( isset( $return[1] ) ) {
			$return = preg_replace( '|^<!\[CDATA\[(.*)\]\]>$|s', '$1', $return[1] );
			$return = $wpdb->escape( trim( $return ) );
		} else {
			$return = '';
		}
		return $return;
	}

	function process_category( $c ) {
		return array(
			'term_id' => $this->get_tag( $c, 'wp:term_id' ),
			'cat_name' => $this->get_tag( $c, 'wp:cat_name' ),
			'category_nicename'	=> $this->get_tag( $c, 'wp:category_nicename' ),
			'category_parent' => $this->get_tag( $c, 'wp:category_parent' ),
			'category_description' => $this->get_tag( $c, 'wp:category_description' ),
		);
	}

	function process_tag( $t ) {
		return array(
			'term_id' => $this->get_tag( $t, 'wp:term_id' ),
			'tag_name' => $this->get_tag( $t, 'wp:tag_name' ),
			'tag_slug' => $this->get_tag( $t, 'wp:tag_slug' ),
			'tag_description' => $this->get_tag( $t, 'wp:tag_description' ),
		);
	}

	function process_term( $t ) {
		return array(
			'term_id' => $this->get_tag( $t, 'wp:term_id' ),
			'term_taxonomy' => $this->get_tag( $t, 'wp:term_taxonomy' ),
			'slug' => $this->get_tag( $t, 'wp:term_slug' ),
			'term_parent' => $this->get_tag( $t, 'wp:term_parent' ),
			'term_name' => $this->get_tag( $t, 'wp:term_name' ),
			'term_description' => $this->get_tag( $t, 'wp:term_description' ),
		);
	}

	function process_author( $a ) {
		return array(
			'author_id' => $this->get_tag( $a, 'wp:author_id' ),
			'author_login' => $this->get_tag( $a, 'wp:author_login' ),
			'author_email' => $this->get_tag( $a, 'wp:author_email' ),
			'author_display_name' => $this->get_tag( $a, 'wp:author_display_name' ),
			'author_first_name' => $this->get_tag( $a, 'wp:author_first_name' ),
			'author_last_name' => $this->get_tag( $a, 'wp:author_last_name' ),
		);
	}

	function process_images($content, $attachment_template) {
		preg_match_all('/<img\b(?:(?=(\s+(?:src=[\'\"]([^"\']*)[\'\"]|alt=[\'\"]([^"\']*)[\'\"]|title=[\'\"]([^"\']*)[\'\"])|[^\s>]+|\s+))\1)*?>/', $content, $matches);
		
		// $matches[0] = img tag match
		// $matches[2] = src
		// $matches[3] = alt
		// $matches[4] = title
		foreach ($matches[0] as $img_key => $tag_string) {
			$attachment = $attachment_template;
			
			
			if (!empty($matches[2][$img_key])) {
				$img_url = $matches[2][$img_key];
				
				if (
					(strpos($img_url, 'http://') === false &&
					 strpos($img_url, 'https://') === false ) 
					|| strpos($img_url, '://knol.google.com' !== false)
				) {
					$explode_url = explode('/', $img_url);
					$attachment['post_title'] = end($explode_url);
					$attachment['attachment_url'] = $attachment['guid'] = $img_url;
				}
				
				if (!empty($matches[3][$img_key])) {
					$attachment['post_title'] = $matches[3][$img_key];
					$attachment['postmeta'][] = array(
						'key' => '_wp_attachment_image_alt',
						'value' => $matches[3][$img_key],
					);
				}
				
				//Title
				if (!empty($matches[4][$img_key])) {
					$attachment['post_title'] = $matches[4][$img_key];
				}
			}

			// Only process this image if we have a URL
			if (!empty($attachment['attachment_url'])) {
				$this->posts[] = $attachment;
			}
		}
	}

	function process_post( $post ) {
		$post_id        = $this->get_tag( $post, 'wp:post_id' );
		$post_title     = $this->get_tag( $post, 'title' );
		$post_date      = $this->get_tag( $post, 'wp:post_date' );
		$post_date_gmt  = $this->get_tag( $post, 'wp:post_date_gmt' );
		$comment_status = $this->get_tag( $post, 'wp:comment_status' );
		$ping_status    = $this->get_tag( $post, 'wp:ping_status' );
		$status         = $this->get_tag( $post, 'wp:status' );
		$post_name      = $this->get_tag( $post, 'wp:post_name' );
		$post_parent    = $this->get_tag( $post, 'wp:post_parent' );
		$menu_order     = $this->get_tag( $post, 'wp:menu_order' );
		$post_type      = $this->get_tag( $post, 'wp:post_type' );
		$post_password  = $this->get_tag( $post, 'wp:post_password' );
		$is_sticky		= $this->get_tag( $post, 'wp:is_sticky' );
		$guid           = $this->get_tag( $post, 'guid' );
		$post_author    = $this->get_tag( $post, 'dc:creator' );

		$post_excerpt = $this->get_tag( $post, 'excerpt:encoded' );
		$post_excerpt = preg_replace_callback( '|<(/?[A-Z]+)|', array( &$this, '_normalize_tag' ), $post_excerpt );
		$post_excerpt = str_replace( '<br>', '<br />', $post_excerpt );
		$post_excerpt = str_replace( '<hr>', '<hr />', $post_excerpt );

		$post_content = $this->get_tag( $post, 'content:encoded' );
		$post_content = preg_replace_callback( '|<(/?[A-Z]+)|', array( &$this, '_normalize_tag' ), $post_content );
		$post_content = str_replace( '<br>', '<br />', $post_content );
		$post_content = str_replace( '<hr>', '<hr />', $post_content );
		
		$post_content_filtered = $this->get_tag( $post, 'content_filtered:encoded' );
		$post_content_filtered = preg_replace_callback( '|<(/?[A-Z]+)|', array( &$this, '_normalize_tag' ), $post_content_filtered );
		$post_content_filtered = str_replace( '<br>', '<br />', $post_content_filtered );
		$post_content_filtered = str_replace( '<hr>', '<hr />', $post_content_filtered );
		
		
		$attachment_template = array(
			'upload_date' => (string) $post_date_gmt,
			'post_date' => (string) $post_date_gmt,
			'post_date_gmt' => (string) $post_date_gmt,
			'post_author' => $post_author,
			'post_type' => 'attachment',
			'post_parent' => $post_id,
			'post_id' => '',
			'post_content' => '',
			'post_content_filtered' => '',
			'postmeta' => '',
			'guid' => '',
			'attachment_url' => '',
			'status' => 'inherit',
			'post_title' => '',
			'ping_status' => '',
			'menu_order' => '',
			'post_password' => '',
			'terms' => '',
			'comment_status' => '',
			'is_sticky' => '',
			'post_excerpt' => '',
			'post_name' => '',
		);

		$this->process_images($post_content_filtered, $attachment_template);

		$postdata = compact( 'post_id', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_excerpt',
			'post_title', 'status', 'post_name', 'comment_status', 'ping_status', 'guid', 'post_parent',
			'menu_order', 'post_type', 'post_password', 'is_sticky', 'post_content_filtered'
		);

		$attachment_url = $this->get_tag( $post, 'wp:attachment_url' );
		if ( $attachment_url )
			$postdata['attachment_url'] = $attachment_url;

		preg_match_all( '|<category domain="([^"]+?)" nicename="([^"]+?)">(.+?)</category>|is', $post, $terms, PREG_SET_ORDER );
		foreach ( $terms as $t ) {
			$post_terms[] = array(
				'slug' => $t[2],
				'domain' => $t[1],
				'name' => str_replace( array( '<![CDATA[', ']]>' ), '', $t[3] ),
			);
		}
		if ( ! empty( $post_terms ) ) $postdata['terms'] = $post_terms;

		preg_match_all( '|<wp:comment>(.+?)</wp:comment>|is', $post, $comments );
		$comments = $comments[1];
		if ( $comments ) {
			foreach ( $comments as $comment ) {
				preg_match_all( '|<wp:commentmeta>(.+?)</wp:commentmeta>|is', $comment, $commentmeta );
				$commentmeta = $commentmeta[1];
				$c_meta = array();
				foreach ( $commentmeta as $m ) {
					$c_meta[] = array(
						'key' => $this->get_tag( $m, 'wp:meta_key' ),
						'value' => $this->get_tag( $m, 'wp:meta_value' ),
					);
				}

				$post_comments[] = array(
					'comment_id' => $this->get_tag( $comment, 'wp:comment_id' ),
					'comment_author' => $this->get_tag( $comment, 'wp:comment_author' ),
					'comment_author_email' => $this->get_tag( $comment, 'wp:comment_author_email' ),
					'comment_author_IP' => $this->get_tag( $comment, 'wp:comment_author_IP' ),
					'comment_author_url' => $this->get_tag( $comment, 'wp:comment_author_url' ),
					'comment_date' => $this->get_tag( $comment, 'wp:comment_date' ),
					'comment_date_gmt' => $this->get_tag( $comment, 'wp:comment_date_gmt' ),
					'comment_content' => $this->get_tag( $comment, 'wp:comment_content' ),
					'comment_approved' => $this->get_tag( $comment, 'wp:comment_approved' ),
					'comment_type' => $this->get_tag( $comment, 'wp:comment_type' ),
					'comment_parent' => $this->get_tag( $comment, 'wp:comment_parent' ),
					'comment_user_id' => $this->get_tag( $comment, 'wp:comment_user_id' ),
					'commentmeta' => $c_meta,
				);
			}
		}
		if ( ! empty( $post_comments ) ) $postdata['comments'] = $post_comments;

		preg_match_all( '|<wp:postmeta>(.+?)</wp:postmeta>|is', $post, $postmeta );
		$postmeta = $postmeta[1];
		if ( $postmeta ) {
			foreach ( $postmeta as $p ) {
				$post_postmeta[] = array(
					'key' => $this->get_tag( $p, 'wp:meta_key' ),
					'value' => $this->get_tag( $p, 'wp:meta_value' ),
				);
			}
		}
		if ( ! empty( $post_postmeta ) ) $postdata['postmeta'] = $post_postmeta;

		return $postdata;
	}

	function _normalize_tag( $matches ) {
		return '<' . strtolower( $matches[1] );
	}

	function fopen( $filename, $mode = 'r' ) {
		if ( $this->has_gzip )
			return gzopen( $filename, $mode );
		return fopen( $filename, $mode );
	}

	function feof( $fp ) {
		if ( $this->has_gzip )
			return gzeof( $fp );
		return feof( $fp );
	}

	function fgets( $fp, $len = 8192 ) {
		if ( $this->has_gzip )
			return gzgets( $fp, $len );
		return fgets( $fp, $len );
	}

	function fclose( $fp ) {
		if ( $this->has_gzip )
			return gzclose( $fp );
		return fclose( $fp );
	}
}


/**
 * Parse Kipling DTD Files. Use phpQuery as its already required by the Annotum theme.
 * phpQuery also makes it easier to decode multipart tags such as Abstract
 * 
 */ 
class Kipling_DTD_Parser {	
	function parse($file) {
		$authors = $posts = $attachments = $post = $author_snapshots = $authors_meta = array();

		if (!class_exists('phpQueryObject')) {
			require(trailingslashit(TEMPLATEPATH).'functions/phpQuery/phpQuery.php');
		}
		$file_content = file_get_contents($file);
		if (!$file_content) {
			return new WP_Error('xml_parse_error', __( 'There was an error when reading this Kipling DTD file', 'anno'));
		}
	
		phpQuery::newDocumentXML($file_content);
	
		// Made up post IDs just for sanities sake, and parent relationship
		$post_id = 1;
	
		$articles = pq('article');

		// Lets make sure we have article tags
		$num_articles = $articles->length();
		if (empty($num_articles)) {
			return new WP_Error('xml_parse_article_error', __( 'This does not appear to be a Kipling DTD file, no articles could be found', 'anno'));
		}
		
		// Process articles, this contains all catergory, tag, author, term etc... processing.
		foreach ($articles as $article) {
			$article = pq('article');
			$article_meta = pq('article-meta', $article);
			
			$article_back = pq('back', $article);
						
			$post['post_type'] = 'article';
			$post['post_content_filtered'] = trim(pq('> body', $article)->html());
			$post['post_title'] = trim(pq('article-title', $article_meta)->text());
			
			$post['postmeta'][] = array(
				'key' => '_anno_subtitle',
				'value' => trim(pq('subtitle', $article_meta)->text()),
			);
			
			// Auto generated
	 		$post['guid'] = '';

			$abstract = pq('abstract', $article_meta);
			// We don't want the title of the abstract
			pq('title', $abstract)->remove();

			// Just the text, wpautop is run on it later (excerpt)
			$post['post_excerpt'] = trim($abstract->text());
		
			// Post content gets generated by Annotum theme from the XML on wp_insert_post. We can leave it empty for now. 		
			$post['post_content'] = '';
		
			$post['post_id'] = $post_id;
		
			// Generated from post title on insert
			$post['post_name'] = '';

			$pub_date = pq('pub-date', $article_meta);
			$pub_date = $this->parse_date($pub_date);
				$post['post_date'] = (string) $pub_date;
				$post['post_date_gmt'] = (string) $pub_date;
				
			$post['status'] = 'draft';
			// Reflect in post_state meta as well.
			$post['postmeta'][] = array(
				'key' => '_post_state', 
				'value' => 'draft',
			);
			

			// Not used in Kipling DTD, but set for data structure integrity required by the importer
			$post['post_parent'] = 0;
			$post['menu_order'] = 0;
			$post['post_password'] = '';
			$post['is_sticky'] = 0;
			$post['ping_status'] = '';
			$post['comment_status'] = '';

			// Grab the category(ies). Annotum DTD should contain only one, Kipling DTD does not contain this requirement.
			foreach (pq('subject', $article_meta) as $category) {
				$category = pq($category);
				// We really don't care about the global categories, categories aren't defined outside of an article in the XML
				$cat_name = trim($category->text());
				if (!empty($cat_name)) {
					$post['terms'][] = array(
						'name' => $cat_name,
						'slug' => sanitize_title($cat_name),
						'domain' => 'article_category',
					);
				}
			}

			// Grab the tags.
			foreach (pq('kwd', $article_meta) as $tag) {
				$tag = pq($tag);
				// We really don't care about the global tags, tags aren't defined outside of an article in the XML
				$tag_name = trim($tag->text());
				if (!empty($tag_name)) {
					$post['terms'][] = array(
						'name' => $tag_name,
						'slug' => sanitize_title($tag_name),
						'domain' => 'article_tag',
					);
				}
			}

			// First author is the primary author, possible @todo - look for primary-author contrib type
			$first_author_check = true;
			$default_author_id = $first_author_id = 1;
			
			// Grab the author(s). 
			$authors = array();
			foreach (pq('contrib', $article_meta) as $contributor) {
				$contributor = pq($contributor);
			
				$author_arr = $this->parse_author($contributor);
			
				$author = $author_arr['author'];
				$author_meta = $author_arr['author_meta'];
						
				// Check for author_id existance, if not, assign one. 
				if (empty($author['author_id'])) {
					$author['author_id'] = $default_author_id;
				}
			
				// Save in authors
				$authors[$author['author_id']] = $author;
			
				// Save in authors_meta, consistant with author_id to match on import of user
				$authors_meta[$author['author_id']] = $author_meta;
			
				if ($first_author_check) {
					$post['post_author'] = $author['author_id'];
				}
			
				$author_snapshots[] = $this->author_snapshot($author, $author_meta);
				if ($first_author_check) {
					// Used in attachment assignment
					$first_author_id = $author['author_id'];
				}
				
				// We'll convert this in the import process
				$post['postmeta'][] = array(
					'key' => '_anno_author_'.$author['author_id'],
					'value' => $author['author_id'],
				);
				
				$first_author_check = false;
				$default_author_id++;
			}

			// Acknowledgements 
			$ack = trim(pq('ack p', $article_back)->text());
			if (!empty($ack)) {	
		 		$post['postmeta'][] = array(
					'key' => '_anno_acknowledgements',
					'value' => $ack,
				);
			}

			// Funding
			$funding = trim(pq('funding-statement', $article_meta)->text());
			if (!empty($funding)) {	
		 		$post['postmeta'][] = array(
					'key' => '_anno_funding',
					'value' => $funding,
				);
			}
				
			// Appendices
			$appendices = pq('app', $article_back);
			$appendix_array = array();
			foreach ($appendices as $appendix) {
	 			$appendix = trim(pq($appendix)->html());
				if (!empty($appendix)) {
					$appendix_array[] =	$appendix;
				}
			}
		
			if (!empty($appendix_array)) {
				// Process to HTML on import
				$post['postmeta'][] = array(
					'key' => '_anno_appendices',
					'value' => serialize($appendix_array),
				);
			}
						
			// References
			$references = pq('ref', $article_back);
			$ref_array = array();
			$single_ref = array(
				'doi' => '',
				'text' => '',
				'pmid' => '',
				'figures' => '',
				'url' => '',
			);
			
			foreach ($references as $reference) {
				$reference = pq($reference);
				// For now, just support mixed-citations as text.
				$ref_id = str_replace('ref', '', $reference->attr('id'));
				
				// Only store numeric values
				if (is_numeric($ref_id)) {
					$ref_id = intval($ref_id) - 1;
				}
				else {
					$ref_id = null;
				}
				
				$ref_text = pq('mixed-citation', $reference);
				
				$ref_data['text'] = trim($ref_text->text());
			
				if (empty($ref_id)) {
					$ref_array[] = $ref_data;
				}
				else {
					// Possibility that this key was already set programmatically, replace it and add old ref to end.
					if (isset($ref_array[$ref_id])) {
						$old_ref = $ref_array[$ref_id];
						$ref_array[$ref_id] = $ref_data;
						$ref_array[] = $old_ref;
					}
					else {
						$ref_array[$ref_id] = $ref_data;
					}
				}
			}
			if (!empty($ref_array)) {
				$post['postmeta'][] = array(
					'key' => '_anno_references',
					'value' => serialize($ref_array),
				);
			}
			

			// Attachments
			
			// Modification for post_id
			$attachment_id_mod = 0;			

			// $pub_date is the date gathered from the post data.
			$attachment_template = array(
				'upload_date' => (string) $pub_date,
				'post_date' => (string) $pub_date,
				'post_date_gmt' => (string) $pub_date,
				'post_author' => $first_author_id,
				'post_type' => 'attachment',
				'post_parent' => $post_id,
				'post_id' => '',
				'post_content' => '',
				'post_content_filtered' => '',
				'postmeta' => '',
				'guid' => '',
				'attachment_url' => '',
				'status' => 'inherit',
				'post_title' => '',
				'ping_status' => '',
				'menu_order' => '',
				'post_password' => '',
				'terms' => '',
				'comment_status' => '',
				'is_sticky' => '',
				'post_excerpt' => '',
				'post_name' => '',
			);
			
			$inline_images = pq('> body inline-graphic', $article);
			foreach ($inline_images as $img) {
				$img = pq($img);
							

				$img_url = $img->attr('xlink:href');
				
				// Dont save chart api images (most likely formulas)
				if (!empty($img_url) && strpos($img_url, 'googleapis.com/chart') === false) {
					$post_meta = array();
					
					$alt_text = pq('alt-text', $img)->html();
					if (!empty($alt_text)) {
						$post_meta[] = array(
							'key' => '_wp_attachment_image_alt',
							'value' => $alt_text,
						);
					}
					
					$attachment_title = !empty($alt_text) ? $alt_text : end(explode('/',$img_url));
					
					$attachments[] = array_merge($attachment_template, array(
						'post_id' => $post_id.'.'.$attachment_id_mod,
						'guid' => $img_url,
						'attachment_url' => $img_url,
						'post_parent' => $post_id,
						'title' => trim($attachment_title),
						'postmeta' => $post_meta,
						'post_title' => $img_url,
					));
					
					$attachment_id_mod++;
				}
			}
			
			// Find media and save as attachment
			$media_images = pq('> body media', $article);
			foreach ($media_images as $media_image) {
				$media_image = pq($media_image);
				
				// Parse Media will return an array with:
					// attachment_url
					// guid
					// post_title
					// post_content
					// postmeta
				$media_array = $this->parse_media($media_image);
				
				if (is_array($media_array) && !empty($media_array['attachment_url'])) {
					// Check if this is a figure image
					$figure = $media_image->parent('fig');
					$figure_html = trim($figure->html());
					$caption = '';
					if (!empty($figure_html)) {
						$label = pq('label', $figure)->html();
						$caption = pq('caption', $figure)->html();
						
						$post_meta[] = array(
							'key' => '_anno_attachment_image_label',
							'value' => $label,
						);
					}
					
					$attachment = array_merge($media_array, array(
						'post_id' => $post_id.'.'.$attachment_id_mod,
						'post_parent' => $post_id,
						// Concat
						'postmeta' => array_merge($post_meta, $media_array['postmeta']),
					));
					
					$attachments[] = array_merge($attachment_template, $attachment);
										
					$attachment_id_mod++;
				}
			}

			$comments = pq('response');
			foreach ($comments as $comment) {
				$comment = pq($comment);
				$comment_content = pq('body', $comment)->html();
				$comment_date = $this->parse_date(pq('pub-date', $comment));
				$comment_author_arr = $this->parse_author(pq('contrib', $comment));
				$comment_author = $comment_author_arr['author'];
			
				$post['comments'][] = array(
					'comment_id' => '',
					'comment_author' => (string) $comment_author['author_display_name'],
					'comment_author_email' => (string) $comment_author['author_email'],
					'comment_author_IP' => '',
					'comment_author_url' => (string) $comment_author['author_url'],
					'comment_date' => (string) $comment_date,
					'comment_date_gmt' => '',
					// We only export approved comments
					'comment_content' => $comment_content,
					'comment_approved' => 1,
					'comment_type' => '',
					'comment_parent' => '',
					'comment_user_id' => 0,
					'commentmeta' => array(),
				);
			}
			
			// Save our author snapshots
			$post['postmeta'][] = array(
				'key' => '_anno_author_snapshot',
				'value' => serialize($author_snapshots),
			);
						
			$posts[] = $post;
			// Concat, both indexed
			$posts = array_merge($posts, $attachments);
		}

				
		return array(
			'authors' => $authors,
			'authors_meta' => $authors_meta,
			'posts' => $posts,
			'categories' => array(),
			'tags' => array(),
			'terms' => array(),
			'base_url' => '', //$base_url,
			'version' => 1.1, //$wxr_version
		);
	}
	
	/**
	 * Generate a snapshot of the author based on data provided
	 * 
	 * @param array $author Array of standard WP author keys/values
	 * @param array $author_meta Array of non-standard WP author data
	 * @return array Snapshot of data grabbed from the two params
	 */ 
	function author_snapshot($author, $author_meta) {
		return array(
			'id' => $author['author_id'],
			'surname' => $author['author_last_name'],
			'given_names' => $author['author_first_name'],
			'prefix' => isset($author_meta['prefix']) ? $author_meta['prefix'] : '',
			'suffix' => isset($author_meta['suffix']) ? $author_meta['suffix'] : '',
			'degrees' => isset($author_meta['degrees']) ? $author_meta['degrees'] : '',
			'affiliation' => isset($author_meta['affiliation']) ? $author_meta['affiliation'] : '',
			'institution' => isset($author_meta['institution']) ? $author_meta['institution'] : '',
			'bio' => isset($author_meta['bio']) ? $author_meta['bio'] : '',
			'email' => $author['author_email'],
			'link' => $author['author_url'],
		);
	}
		
	/**
	 * Parse contributor data from a <contrib> tag, maintaining as much data as possible
	 * @TODO better handling of contrib-type, potentially do logic on <role> within <contrib>
	 * 
	 * @param phpQueryObj $contributor phpQuery object pertaining to a contrib tag
	 * @return array Author data and author meta data gathered
	 */
	function parse_author($contributor) {
		// Basic data structure
		$author = array(
			'author_id' => '',
			'author_login' => '',
			'author_email' => '',
			'author_display_name' => '',
			'author_first_name' => '',
			'author_last_name' => '',
			'author_url' => '',
		);
		$author_meta = array();
								
		$contributor_type = $contributor->attr('contrib-type');
		
		// Currently, just assume contrib-type="author" and non-existant contrib-types are authors. Note - empty contrib-types (contrib-type="") will not be processed as an author.			
		if (strcasecmp($contributor_type, 'author') === 0 || $contributor_type === null) {

			// Grab supplimentary data first. So we can determine which to use over <collab>
			// Only direct children, we don't want to grab from <collab>
			$email = pq('> email', $contributor)->text();

			$author_meta['affiliation'] = $affiliation = pq('> aff', $contributor)->text();
			$author_meta['institution'] = $institution = pq('> aff > institution', $contributor)->text();
			$author_meta['bio'] = pq('> bio', $contributor)->text();
			$author_meta['ext-link'] = pq('> ext-link', $contributor)->attr('xlink::href');								
			$author_meta['uri'] = pq('> uri', $contributor)->text();
			$author_meta['xref'] = pq('> xref', $contributor)->text();				
			
			// Get address information, there may be an email lurking in there
			$address_array = $this->parse_address(pq('> address', $contributor));
			if (empty($email) && !empty($address_array['email'])) {
				$email = $address_array['email'];
				unset($address_array['email']);
			}
			// Use existing meta if its populated
			$author_meta = array_merge($address_array, $author_meta);
			
		// Top Level identifiers
			// @TODO Support contrib with <name> <collab> and <anonymous> tags...doesn't make much sense, but possible by DTD.
			// Handle names of individuals
			$name = pq('name', $contributor);
			$collab = pq('collab', $contributor);
			$anon = pq('anonymous', $contributor);
			if (!empty($name)) {
				// Always set the author ID first, so we know the key when storing user_meta
				
				$last_name = pq('surname', $name)->text();
				$first_name = pq('given-names', $name)->text();
				$suffix = pq('suffix', $name)->text();
				$prefix = pq('prefix', $name)->text();
				
				// Generate a (semi) unique ID for this user, hopefully this will be consistant across the XML file (other articles)
				$author['author_id'] = sanitize_title($last_name.$first_name.$suffix.$prefix);
				
				$author['author_display_name'] = $first_name.' '.$last_name;		
				$author['author_first_name'] = $first_name;
				$author['author_last_name'] = $last_name;							

				// Suffix and Prefix are user_meta. Import to check for emptiness
				$author_meta['prefix'] = $suffix;
				$author_meta['suffix'] = $prefix;

			}
			// Handle organizations, collaborations etc...
			else if (!empty($collab)) {
				// Use three most likely items to generate ID
				$contrib_group = pq('contrib-group', $collab)->text();
				$affiliation = pq('> aff', $collab)->text();
				$institution = pq('> aff > institution', $collab)->text();
				
				$author['author_id'] = sanitize_title($contrib_group.$affiliation.$institution);
				$author['author_display_name'] = !empty($contrib_group) ? $contrib_group : $institution;
			
				// Meta info - Bio, email etc.. gathered later
				$collab_meta = array();
				$collab_meta['affiliation'] = $affiliation;
				$collab_meta['institution'] = $institution;
				$collab_meta['bio'] = pq('> bio', $collab)->text();
				$collab_meta['ext-link'] = pq('> ext-link', $collab)->text();								
				$collab_meta['uri'] = pq('> uri', $collab)->text();
				$collab_meta['xref'] = pq('> xref', $collab)->text();
				$collab_meta['country'] = pq('country', $collab)->text();
				$collab_meta['fax'] = pq('fax', $collab)->text();
				$collab_meta['phone'] = pq('phone', $collab)->text();
				
				// Meta outside of <collab> takes precendence
				$author_meta = array_merge($collab_meta, $author_meta);
				
				$address_array = parse_address(pq('> address', $contrib));
				if (empty($email) && !empty($address_array['email'])) {
					$email = $address_array['email'];
					unset($address_array['email']);
				}
				// Use existing meta if its populated
				$author_meta = array_merge($address_array, $author_meta);
				
			}
			// Handle anonymous
			else if (!empty($anon)) {
				$author['author_id'] = 'anonymous';
				$author['author_display_name'] = 'anonymous';
			}

			$author['email'] = $email;

			
			// Determine author_url, store other data in author meta.
			if (!empty($author_meta['uri'])) {
				$author['author_url'] = $author_meta['uri'];
			}
			else if (!empty($author_meta['ext-link'])) {
				$author['author_url'] = $author_meta['ext-link'];
			}
			else if (!empty($author_meta['xref'])) {
				$author['author_url'] = $author_meta['xref'];
			}
		}
				
		return array(
			'author' => $author,
			'author_meta' => $author_meta,
		);	
	}
	
	/**
	 * Parse address into an array
	 * 
	 * @param phpQueryObj $address Tag to parse
	 * @return array An array of address fields
	 */ 
	function parse_address($address) {
		$address_array = array();
		$elements = array(
			'country',
			'add-line',
			'fax',
			'institution',
			'phone',
			'email',
		);
		
		foreach ($elements as $el_name) {
			$el_text = pq($el_name, $address)->text();
			// Any field that data that is '' or 0 or null
			if (!empty($el_text)) {
				$address_array[$el_name] = $el_text;
			}
		}
		return $address_array;
	}
	
	/**
	 * Parse date into an array
	 * 
	 * @param phpQueryObj $date Tag to parse
	 * @return string Formatted date string
	 */
	function parse_date($date) {
		$day = pq('day', $date)->text();
		$month = pq('month', $date)->text();
		$year = pq('year', $date)->text();
		
		// Note, DTD does not detail time
		return $year.'-'.$month.'-'.$day.' 00:00:00';
	}
	
	/**
	 * Parse media data from a media tag
	 * @param phpQueryObj $media Media object to parse
	 * 
	 * @return false|array Array of data if a relevant url can be found, false otherwise.
	 */
	function parse_media($media) {		
		$img_url = $media->attr('xlink:href');
		
		if (!empty($img_url) && strpos($img_url, 'googleapis.com/chart') === false) {
			$post_meta = array();
			
			$alt_text = pq('alt-text', $media)->text();
			$long_desc = pq('long-desc', $media)->text();

			$post_meta[] = array(
				'key' => '_anno_attachment_image_copyright_statement',
				'value' => pq('copyright-statement', $media)->html(),
			);

			$post_meta[] = array(
				'key' => '_anno_attachment_image_copyright_holder',
				'value' => pq('copyright-holder', $media)->text(),
			);

			$post_meta[] = array(
				'key' => '_anno_attachment_image_license',
				'value' => pq('license-p', $media)->html(),
			);

			$post_meta[] = array(
				'key' => '_wp_attachment_image_alt',
				'value' => $alt_text,
			);

			$attachment_title = !empty($alt_text) ? $alt_text : end(explode('/',$img_url));

			return array(
				'attachment_url' => $img_url,
				'guid' => $img_url,
				'post_title' => $attachment_title,
				'post_content' => trim(pq('long-desc', $media)->html()),
				'postmeta' => $post_meta,
			);
		}
		return false;
	}
}

?>