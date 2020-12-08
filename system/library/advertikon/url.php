<?php
/**
 * Advertikon Url Class
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

class Url {

	private $scheme = '';
	private $host = '';
	private $port = '';
	private $path = '';
	private $query = array();
	private $fragment = '';
	public static $extensions = array(
		'analytics',
		'captcha',
		'dashboard',
		'feed',
		'fraud',
		'module',
		'payment',
		'shipping',
		'theme',
		'total',
	);
	public static $spoof_23 = false;
	private $a;

	public function __construct( Advertikon $a ) {
		$this->a = $a;
	}

	protected function is_empty() {
		return ! ( $this->scheme or $this->host or $this->port or $this->path or $this->query or $this->fragment );
	}

	public function reset() {
		$this->scheme = '';
		$this->host = '';
		$this->port = '';
		$this->path = '';
		$this->query = array();
		$this->fragment = '';

		return $this;
	}

	/**
	 * Returns URL's scheme part
	 * @return string
	 */
	public function get_scheme() {
		return $this->scheme;
	}

	/**
	 * Returns URL's host part
	 * @return string
	 */
	public function get_host() {
		return $this->host;
	}

	/**
	 * Returns URL's port part
	 * @return string
	 */
	public function get_port() {
		return $this->port;
	}

	/**
	 * Returns URL's path part
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Returns URL's fragment part
	 * @return string
	 */
	public function get_fragment() {
		return $this->fragment;
	}

	/**
	 * Parses URL
	 * @param String $url 
	 * @return Array
	 */
	public function parse( $url ) {
		if ( !$this->is_empty() ) {
			$this->reset();
		}

		if( gettype( $url ) !== 'string' ) {
			$this->a->error( new Exception( sprintf( '%s: URL need to be a string, %s given instead', __CLASS__, gettype( $url ) ) ) );
			return;
		}

		if( !$url ) {
			$this->a->error( new Exception( sprintf( '%s: URL may not be an empty string', __CLASS__ ) ) );
			return;
		}

		$preg_str = '%' .
					'(?:(^[^/:]*?)(?=://))?' . // Scheme
					'(?::?/{2})?' .
					'([^/:?#]+?(?=\/|$|:|\?|#))?' . // Host
					':?' .
					'(?:(?<=:)(\d+))?' . // Port
					'([^:?#]+)?' . // Path
					'\??' .
					'(?:(?<=\?)([^#]+))?' . // Query
					'#?' .
					'(?:(?<=#)(.*))?' . // Fragment
					'%';

		if( preg_match( $preg_str, $url, $m ) ) {

			if( isset( $m[1] ) ) {
				$this->scheme = $m[1];
			}

			if( isset( $m[2] ) ) {
				$this->host = $m[2];
			}

			if( isset( $m[3] ) ) {
				$this->port = $m[3];
			}

			if( ! empty( $m[4] ) ) {
				$this->path = $m[4];

			} else {
				$this->path = '/';
			}
			if( isset( $m[5] ) && $m[5] ) {
				foreach( explode( '&', str_replace( '&amp;', '&', $m[5] ) ) as $part ) {
					$parts = explode( '=', $part );

					if ( empty( $parts[0] ) || ! isset( $parts[1] ) ) {
						$this->a->error( new Exception( sprintf( 'URL query part "%s" is invalid', $part ) ) );
						continue;
					}

					$this->query[ $parts[0] ] = $parts[1];	
				}
			}

			if( isset( $m[6] ) ) {
				$this->fragment = $m[6];
			}
		}

		return $this;
	}

	/**
	 * Normalizes URL
	 * @param String|Array $url URL to be normalized
	 * @return String
	 */
	public function to_string() {
		if ( ! $this->host ) {
			$this->gues_host();
		}

		$ret = 
		( $this->scheme ? $this->scheme . ':' : '' ) . '//' .
		( ! $this->host ? $_SERVER['SERVER_NAME'] : $this->host ) .
		( ! $this->port ? '' : ':' . $this->port ) .
		$this->path .
		( ! $this->query ? '' : '?' . $this->query_to_string() ) .
		( ! $this->fragment ? '' : '#' . $this->fragment );

		return $ret;
	}

	/**
	 * Define host name depending on position
	 * @return void
	 */
	protected function gues_host() {
		$host = '';

		if ( defined( 'HTTP_CATALOG' ) ) {
			$host = new self( self::admin_url() );

		} else {
			$host = new self( self::catalog_url() );
		}

		$this->scheme = $host->scheme;
		$this->host = $host->host;
	}

	/**
	 * Returns query part as a string 
	 * @return string
	 */
	public function query_to_string() {
		return http_build_query( $this->query );
	}

	/**
	 * Adds query parameter
	 * @param string $name Parameter name
	 * @param string $value Parameter value
	 * @return object
	 */
	public function add_query( $name, $value ) {
		if ( ! is_string( $name ) ) {
			$this->a->error(
				new Exception( sprintf(
					'%s: name of query parameter need to be a string, %s given instead',
					__CLASS__,
					gettype( $name )
				) )
			);

			return $this;
		}

		if ( ! is_string( $value ) ) {
			$this->a->error(
				new Exception( sprintf(
					'%s: value of query parameter need to be a string, %s given instead',
					__CLASS__,
					gettype( $value )
				) )
			);

			return $this;
		}

		$this->query[ $name ] = $value;

		return $this;
	}

	/**
	 * Returns query part by name
	 * @param string $name Query's part name 
	 * @return string
	 */
	public function get_query( $name = null ) {
		if ( is_null( $name ) ) {
			return $this->query;
		}

		if ( isset( $this->query[ $name ] ) ) {
			return $this->query[ $name ];
		}

		return null;
	}

	/**
	 * Returns URL to extension's action
	 * @param string $route Action name 
	 * @return string
	 */
	public function url( $route = '', $query = array() ) {
		if ( '' === $route ) {
			$parts = array();

		} else {
			$parts = explode( '/', $route );
		}

		if ( count( $parts ) <= 1 ) {
			array_unshift( $parts, $this->a->type, $this->a->code );
		}

		if ( version_compare( VERSION, '2.3.0.0', '>=' ) || self::$spoof_23 ) {
			if ( in_array( $parts[0], self::$extensions ) ) {
				array_unshift( $parts, 'extension' );
			}
		}
			
		$route = implode( '/', $parts );

		if ( isset( $this->a->session->data['user_token'] ) ) {
			$query = array_merge( (array)$query, array( 'user_token' => $this->a->session->data['user_token'] ) );

		} else if( isset( $this->a->session->data['token'] ) ) {
			$query = array_merge( (array)$query, array( 'token' => $this->a->session->data['token'] ) );

		}
		// else if ( isset( $this->a->session->data['user_token'] ) ) {
		// 	$query = array_merge( (array)$query, array( 'user_token' => $this->a->session->data['user_token'] ) );
		// }

		return str_replace( '&amp;', '&', $this->a->url->link( $route, http_build_query( $query ), 'SSL' ) );
	}

	/**
	 * Returns base URL for catalog area
	 * @param boolean|null|string $ssl SSL setting: true, false, auto or null 
	 * @return string
	 */
	public function catalog_url( $ssl = null ) {
		// Get URL depending on current position
		if ( defined( 'HTTP_CATALOG' ) ) {
			$url = HTTP_CATALOG;
			$ssl_url = HTTPS_CATALOG;

		} else {
			$url = HTTP_SERVER;
			$ssl_url = HTTPS_SERVER;
		}

		if ( 0 !== ( $store_id = (int)$this->a->config->get( 'config_store_id' ) ) ) {
			$q = $this->a->q( array(
				'table' => 'store',
				'query' => 'select',
				'fields' => array( 'url', 'ssl' ),
				'where' => array(
					'field'     => 'store_id',
					'operation' => '=',
					'value'     => $store_id,
				),
			) );

			if ( $q && count( $q ) ) {
				$url = $q['url'];
				$ssl_url = $q['ssl'];

			} else {
				$this->a->error( 'Failed to detect store URL' );
			}
		}

		$ssl_config = $this->a->config->get( 'config_secure' ); 

		// Explicit HTTPS
		if ( true === $ssl || ( 'auto' === $ssl && $ssl_config ) ) {
			return preg_match( '~^http(s)?://~', $ssl_url ) ? $ssl_url : "https://$ssl_url";

		// Explicit HTTP
		} elseif ( false === $ssl || ( 'auto' === $ssl && !$ssl_config ) ) {
			return preg_match( '~^http(s)?://~', $url ) ? $url : "http://$ssl_url";

		// Protocol-less scheme
		} else {
			return preg_replace( '~^http(s)?://~', '//', $url );
		}
	}

	/**
	 * Returns base URL for administrative area
	 * @param boolean|null|string $ssl SSL setting: true, false, auto or null 
	 * @return string
	 */
	public function admin_url( $ssl = null ) {
		$ret = '';

		// Return URL if only at admin area
		if ( defined( 'HTTP_CATALOG' ) ) {
			$ssl_config = $this->a->config->get( 'config_secure' );

			// Explicit HTTPS
			if ( true === $ssl || ( 'auto' === $ssl && $ssl_config ) ) {
				return preg_match( '~^http(s)?://~', HTTPS_SERVER ) ? HTTPS_SERVER : 'https://' . HTTPS_SERVER;

			// Explicit HTTP
			} elseif ( false === $ssl || ( 'auto' === $ssl && ! $ssl_config ) ) {
				return preg_match( '~^http(s)?://~', HTTP_SERVER ) ? HTTP_SERVER : 'http://' . HTTP_SERVER;

			// Protocol-less scheme
			} else {
				return preg_replace( '~^http(s)?://~', '//', HTTP_SERVER );
			}
		}

		return $ret;
	}

	/**
	 * Returns v2.3+ aware route
	 * @param string $route 
	 * @return string
	 */
	public function get_route( $route ) {
		if ( version_compare( VERSION, '2.3.0.0', '>=' ) || self::$spoof_23 ) {
			$parts = explode( '/', $route );

			if ( in_array( $parts[0], self::$extensions ) ) {
				array_unshift( $parts, 'extension' );

				$route = implode( '/', $parts );
			}
		}

		return $route;
	}

	/**
	 * Converts absolute path into absolute URL
	 * @param string $path 
	 * @return string
	 */
	public function path_to_url( $path ) {
		$base = dirname( DIR_SYSTEM );

		if ( strpos( $path, $base ) === 0 ) {
			return $this->catalog_url() . substr( $path, strlen( $base ) + 1 );
		}

		$this->a->error( sprintf( "Failed to convert path '%s' into URL", $path ) );

		return $path;
	}

	/**
	 * Return path to catalog side script
	 * @param string $script 
	 * @return string
	 */
	public function admin_script( $script ) {
		if ( defined( 'JOOCART_COMPONENT_URL' ) ) {
			$url = JOOCART_COMPONENT_URL . 'admin/';

		} else {
			$url = $this->admin_url( true );
		}

		return $url . 'view/javascript/' . $script;
	}

	/**
	 * Return path to catalog side script
	 * @param string $script 
	 * @return string
	 */
	public function catalog_script( $script ) {
		$url = '';

		if ( defined( 'JOOCART_COMPONENT_URL' ) ) {
			$url = JOOCART_COMPONENT_URL;

		} else {
			$url = $this->catalog_url( true );
		}

		return $url . 'catalog/view/javascript/' . $script;
	}

	/**
	 * Return path to admin side CSS
	 * @param string $css 
	 * @return string
	 */
	public function admin_css( $css ) {
		if ( defined( 'JOOCART_COMPONENT_URL' ) ) {
			$url = JOOCART_COMPONENT_URL . 'admin/';

		} else {
			$url = $this->admin_url( true );
		}

		return $url . 'view/stylesheet/' . $css;
	}

	/**
	 * Return path to catalog side CSS
	 * @param string $css 
	 * @return string
	 */
	public function catalog_css( $css ) {
		$url = '';

		if ( defined( 'JOOCART_COMPONENT_URL' ) ) {
			$url = JOOCART_COMPONENT_URL;

		} else {
			$url = $this->catalog_url( true );
		}

		return $url . 'catalog/view/theme/default/stylesheet/' . $css;
	}
}
