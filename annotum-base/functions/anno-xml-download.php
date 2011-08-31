<?php 

class Anno_XML_Download {
	
	static $instance;
	
	private function __construct() {
		/* Define what our "action" is that we'll 
		listen for in our request handlers */
		$this->action = 'anno_xml_download_action';
		$this->i18n = 'anno';
	}
	
	public function i() {
		if (!isset(self::$instance)) {
			self::$instance = new Anno_XML_Download;
		}
		return self::$instance;
	}
	
	public function setup_filterable_props() {
		$this->debug = apply_filters(__CLASS__.'_debug', false);
	}
	
	public function add_actions() {
		add_action('init', array($this, 'setup_filterable_props'));
		add_action('init', array($this, 'request_handler'));
	}
	
	public function request_handler() {
		if (isset($_GET[$this->action])) {
			switch ($_GET[$this->action]) {
				case 'download_xml':
					if (empty($_GET['article'])) {
						wp_die(__('Required article first.', $this->i18n));
					}
					else {
						$article_id = $_GET['article'];
					}
					
					// If we're not debugging, turn off errors
					if (!$this->debug) {
						$display_errors = ini_get('display_errors');
						ini_set('display_errors', 0);
					}
					
					$article = get_post($article_id);
					
					if (!$article) {
						wp_die(__('Required article first.', $this->i18n));
					}
					
		
					header("content-type:text/xml;charset=utf-8");
					$this->generate_xml($article);
					exit;
					break;				
				default:
					break;
			}
		}
	}
	
	
	private function generate_xml($article) {
		echo $this->xml_front($article)."\n".$this->xml_body($article)."\n".$this->xml_back($article);
	}
	
	private function xml_front($article) {
			return 
'<article xmlns:xlink="http://www.w3.org/1999/xlink" article-type="test-article" xml:lang="en">
	<front>
		<journal-meta>
			<journal-id journal-id-type="test">Journal ID</journal-id>
			<journal-title-group>
				<journal-title>Journal Title</journal-title>
			</journal-title-group>
			<issn pub-type="ppub">1234-567X</issn>
			<publisher>
				<publisher-name>Publisher Name</publisher-name>
				<publisher-loc>Publisher Location</publisher-loc>
			</publisher>
		</journal-meta>
		<article-meta>
			<article-id pub-id-type="doi">10.1999/party.like.its</article-id>
			<article-categories>
				<subj-group>
					<subject><bold>Formatted Text</bold></subject>
				</subj-group>
			</article-categories>
			<title-group>
				<article-title><bold>Formatted Text</bold></article-title>
				<subtitle><bold>Formatted Text</bold></subtitle>
			</title-group>
			<contrib-group>
				<contrib>
					<name>
						<surname>Jones</surname>
						<given-names>Jim</given-names>
						<prefix>Rev.</prefix>
						<suffix>III</suffix>
					</name>
					<degrees>Ph D.</degrees>
					<aff>Northwestern University</aff>
					<bio>Lives down by the river</bio>
					<email>jim@jones.com</email>
					<ext-link ext-link-type="uri" xlink:href="http://www.example.com">My Blog</ext-link>
				</contrib>
			</contrib-group>
			<pub-date pub-type="ppub">
				<day>12</day>
				<month>12</month>
				<year>2010</year>
			</pub-date>
			<history>
				<date date-type="submitted">
					<day>12</day>
					<month>12</month>
					<year>2010</year>
				</date>
				<date date-type="submitted">
					<day>12</day>
					<month>12</month>
					<year>2010</year>
				</date>
			</history>
			<abstract>
				<title>Abstract</title>
				<p><inline-graphic xlink:href="charimage.gif" ><alt-text>alternative text</alt-text></inline-graphic></p>
			</abstract>
			<kwd-group kwd-group-type="simple">
				<kwd><bold>Formatted Text</bold></kwd>
				<kwd><bold>Formatted Text</bold></kwd>
				<kwd><bold>Formatted Text</bold></kwd>
				<kwd><bold>Formatted Text</bold></kwd>
				<kwd><bold>Formatted Text</bold></kwd>
				<kwd><bold>Formatted Text</bold></kwd>
			</kwd-group>
			<funding-group>
				<funding-statement><bold>Formatted Text</bold></funding-statement>
			</funding-group>
		</article-meta>
	</front>';
	}
	
	private function xml_body($article) {
		$body = $article->post_content_filtered;		
		return 
'	<body>
		'.$body.'	
	</body>';
	}
	
	private function xml_acknoledgements($article) {
		$ack = get_post_meta($article->ID, '_anno_acknowledgements', true);
		$xml = '';
		if (!empty($ack)) {
			$xml =
'		<ack>
			<title>'._x('Acknowledgments', 'xml acknowledgments title', 'anno').'</title>
			<p>'.esc_html($ack).'</p>
		</ack>';
		}
		
		return $xml;
	}

	private function xml_appendices($article) {
		$appendices = get_post_meta($article->ID, '_anno_appendices', true);
		$xml = '';
		if (!empty($appendices) && is_array($appendices)) {
			$xml = 
'			<app-group>';

			foreach ($appendices as $appendix_key => $appendix) {
				if (!empty($appendix)) {
					$xml .=
	'			<app id="app'.($appendix_key + 1).'">
					<title>'.sprintf(_x('Appendix %s', 'xml appendix title', 'anno'), anno_index_alpha($appendix_key)).'</title>'
					.$appendix.'
				</app>';
				}
			}
			
			$xml .=
'			</app-group>';
		}
			
		return $xml;
	}
	
	private function xml_references($article) {
		$references = get_post_meta($article->ID, '_anno_references', true);
		$xml = '';
		if (!empty($references) && is_array($references)) {
			$xml = 
'			<ref-list>
				<title>'._x('References', 'xml reference title', 'anno').'</title>';
		
			foreach ($references as $ref_key => $reference) {
				if (isset($reference['doi']) && !empty($reference['doi'])) {
					$doi = '
						<pub-id pub-id-type="doi">'.esc_html($reference['doi']).'</pub-id>';
				}
				else {
					$doi = '';
				}
				
				if (isset($reference['pcmid']) && !empty($reference['pcmid'])) {
					$pcmid = '
						<pub-id pub-id-type="pmid">'.esc_html($reference['pcmid']).'</pub-id>';
				}
				else {
					$pcmid = '';
				}
				
				if (isset($reference['text']) && !empty($reference['text'])) {
					$text = esc_html($reference['text']);
				}
				else {
					$text = '';
				}
				
				if (isset($reference['text']) && !empty($reference['text'])) {
					$xml .=
'				<ref id="R'.$ref_key.'">
					<label>'.$ref_key.'</label>
					<mixed-citation>'.$text.'
						'.$doi.$pcmid.'
					</mixed-citation>
				</ref>';
				}
			}
		
			$xml .=
'			</ref-list>';
		}
		
		return $xml;
		
	}
	
	private function xml_back($article) {
		return 
'	<back>
'.$this->xml_acknoledgements($article).'
'.$this->xml_appendices($article).'
'.$this->xml_references($article).'
	</back>'."\n".
//	<response response-type="sample">
//		[TBD]
//	</response>
'</article>';	
	}
	
	/**
	 * Generates the XML download URL for a post
	 *
	 * @param int $id 
	 * @return string
	 */
	public function get_download_url($id = null) {
		// Default to the global $post
		if (is_null($id)) {
			global $post;
			if (empty($post)) {
				$this->log('There is no global $post in scope.');
				return false;
			}
			$id = $post->ID;
		}
		
		// Build our URL args
		$url_args = array(
			$this->action 	=> 'download_xml',
			'article' 		=> intval($id),
		);
		
		return add_query_arg($url_args, home_url());
	}
	
	/**
	 * Conditionally logs messages to the error log
	 *
	 * @param string $msg 
	 * @return void
	 */
	private function log($msg) {
		if ($this->debug) {
			error_log($msg);
		}
	}	
}
Anno_XML_Download::i()->add_actions();
	

/**
 * Get the XML download link for a post
 *
 * @param int $id 
 * @return string
 */
function anno_xml_download_url($id = null) {
	return Anno_XML_Download::i()->get_download_url($id);
}

?>