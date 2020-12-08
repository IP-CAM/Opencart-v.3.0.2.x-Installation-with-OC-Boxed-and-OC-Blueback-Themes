<?php
/**
 * Advertikon Class
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon {

    /**
     * Class Advertikon
     * @package Advertikon
     * @property Load $load
     * @property \Cart\Tax $tax
     * @property \Session $session
     * @property \Cart\Currency $currency
     * @property \Cart\Customer $customer
     * @property \DB $db
     * @property \Log $log
     * @property \Request $request
     * @property \Response $response
     * @property \Config $config
     * @property \Document $document
     * @property \Language $language
     * @property \Url $url
     * @property \Cart\Cart $cart
     *
     * @property \ModelToolImage $tool_image
     */
class Advertikon {

    /** @var \Registry */
	public $registry = null;
	public $full_name;

	/** @var Renderer */
	public $renderer = null;
	public $query = null;
	public $log_strictness = -1;
	public static $one_time_cache = array();
	public $tables = array();
	public $type = '';
	public $code = 'adk';
	public $option = null;
	protected $mail_instance = null;
	protected $mail_init = false;
	public $do_cache = true;

	/** @var Advertikon[] */
	public static $instance = array();
	public $adk_url = null;

	/** @var Console */
	public $console = null;
	public $libray_dir = __DIR__;

	/** @var Load */
	public $ld = null;
	public $name = '';
	public $support_email = 'support@advertikon.com.ua';
	public $image_dir = '';
	public $image_url = '';
	public $is_test_env = false;
	public $is_console = false;
	public $message_urgency = null;
	public $version = '';

	/** @var Cache */
	public $cache = null;
	//static protected $konsole = null;
	private $class_cache = [];

	/** @var Translator */
	public $translator = null;

	const LOGS_DISABLE 	= -1;
	const LOGS_ERR 		= 0;
	const LOGS_MSG 		= 50;
	const LOGS_DEBUG 	= 100;

	const CHAR_NUMERIC	= '0123456789';
	const CHAR_ALPHA	= 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm';
	const CHAR_ALPHANUM = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0123456789';
	const CHAR_SYMB		= '_-.';
	const CHAR_SPACE	= ' ';

//	const COMPRESSION_LEVEL_NONE        = 0;
//	const COMPRESSION_LEVEL_COMBINE     = 1;
//	const COMPRESSION_LEVEL_WHITESPACES = 3;
//	const COMPRESSION_LEVEL_FULL        = 15;

	const UPDATE_MANUAL  = 1; // Auto update is impossible and manual update need to be performed
	const UPDATE_SUCCESS = 2;
	const UPDATE_FAILED  = 3;
	
	const PERMISSION_ACCESS = 'access';
	const PERMISSION_MODIFY = 'modify';

	const TICKET_BUTTON_URL = 'http://shop.advertikon.com.ua/support/license.php';

	public $error_message = '';

	public $data_dir = '';
	public $tmp_dir = '';

//	public $compression_level = self::COMPRESSION_LEVEL_FULL;
//	public $compression_cache = true;
//
//	// In order to flush cache "use browser cache" setting need to be "false" as well
//	public $compression_browser_cache = true;

	public $_file = __FILE__;
	public static $file = __FILE__;
	protected $actions = [];
	
	const TMP_TIME = 36000;

        /**
         * Advertikon constructor.
         * @throws Exception
         */
        public function __construct() {
	    /** @var $adk_registry \Registry */
		global $adk_registry;

		if ( !is_null( $adk_registry ) ) {
			$this->registry = $adk_registry;

		} else {
			$this->registry = new Placeholder();
			//throw new Exception( 'Stripe gateway: module modifications need to be applied' );
		}

		$this->full_name = ( $this->type ? $this->type . '/' : '' ) . $this->code;

		// Console
		$this->console = $this->get_from_class_cache( 'Console' );
		$this->is_console = true;
		$this->log_strictness = Console::LEVEL_DEBUG;
		$this->message_urgency = Console::LEVEL_NORMAL;

		$this->data_dir  = ( defined( 'DIR_STORAGE' ) ? DIR_STORAGE : DIR_SYSTEM . 'storage/' ) . 'adk/';
		$this->image_dir = DIR_IMAGE . 'catalog/advertikon/' . $this->code . '/';
		$this->image_url = ( defined( 'HTTPS_CATALOG' ) ? HTTPS_CATALOG : HTTPS_SERVER ) .
			substr( $this->image_dir, strlen( dirname( DIR_IMAGE ) ) + 1 );

		// Set custom cache
		$this->cache = $this->get_from_class_cache( 'Cache' );

		//Translator
		$this->translator = new Translator( $this );

		$this->clear_tmp();

		$this->add_listener( 'locale', [ 'action' => [ $this, 'populate_locale' ] ] );

		$this->autoloader();
	}

	protected function autoloader() {
		$file = DIR_SYSTEM . 'library/advertikon/vendor/autoload.php';

		if ( file_exists( $file ) ) {
			require_once( $file );
		}
	}

	public function is_modification_ok() {
		return is_a( $this->registry, 'Registry' );
	}

	/* @var string Class instance indent */
	static protected $c = 'main';

        /**
         * @param null $code
         * @return Advertikon
         * @throws Exception
         */
        public static function instance($code = null ) {
		if ( 'Advertikon\\Advertikon' === $code || $code === 'Advertikon' ) {
			$code = 'main';
		}

		if ( !is_null( $code ) ) {
			if ( empty( self::$instance[ $code ] ) ) {
				$mess = sprintf( 'Failed to load class "%s", library is not initialized', $code );
				throw new Exception( $mess );
				
			} else {
				return self::$instance[ $code ];
			}

		} else {
			if ( empty( self::$instance[ static::$c ] ) ) {
				self::$instance[ static::$c ] = new static();
			}
			
			return self::$instance[ static::$c ];
		}
	}
	
	static function init( $registry ) {
		if ( !is_a( $registry, 'Registry' ) ) {
			return;
		}

		self::load_translator_assets( $registry );
	}

    /**
     * Gets value of configuration setting regarding extension name
     * @param string $name Configuration name
     * @param null $default
     * @return mixed
     */
	public function config( $name, $default = null ) {

		// Composed name eg one/two/three
		if ( strpos( $name , '/' ) !== false ) {
			$parts = explode( '/', $name );
			$conf = $this->registry->get( 'config' )->get( $this->prefix_name( array_shift( $parts ) ) );

		} else {
			$conf = $this->registry->get( 'config' )->get( $this->prefix_name( $name ) );
		}

		if ( ! empty( $parts ) ) {
			foreach( $parts as $p ) {
				if ( isset( $conf[ $p ] ) ) {
					$conf = $conf[ $p ];

				} else {
					$conf = null;
					break;
				}
			}
		}

		if( is_null( $conf ) ) {
			// if ( $this->is_test_env  && is_null( $default ) ) {
			// 	$this->error( new Exception( sprintf( 'Configuration with name "%s" does not exist', $name ) ) );
			// }

			return $default;
		}

		return $conf;
	}

        /**
         * @param $name
         * @return Load|Tax|mixed
         * @throws Exception
         */
        public function __get($name ) {
		// if ( isset( $this->tables[ $name ] ) ) {
		// 	return $this->tables[ $name ];
		// }

		if ( 'load' === $name ) {
			if ( $this->ld ) {
				return $this->ld;
			}

			$l =  new Load( $this->registry->get( 'load' ), $this );
			$this->ld = $l;

			return $l;
		}

		if( $this->registry->has( $name ) ) {
			return $this->registry->get( $name );
		}

		// Text is missed from admin area on old OC2
		if ( 'tax' === $name ) {
			$tax = new Tax( $this->registry );
		
			if ($this->config->get('config_tax_default') == 'shipping') {
				$tax->setShippingAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
			}

			if ($this->config->get('config_tax_default') == 'payment') {
				$tax->setPaymentAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
			}

			$tax->setStoreAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));

			return $tax;
		}

		if ( strpos( $name, 'model_' ) === 0 && $this->ld ) {
			$ret = $this->ld->get_model( $name );

			if ( $ret ) {
				return $ret;
			}
		}

		if ( 'log' !== $name && $this->is_test_env ) {
			$this->error( new Exception( sprintf( 'Invalid property name: "%s"', $name ) ) );
		}

		$this->error( new Exception( 'Undefined property ' . $name ) );
		throw new Exception( 'Undefined property "' . $name . '"' );
		//return new Placeholder();
	}

	public function __set( $name, $value ) {
		$this->registry->set( $name, $value );
	}

        /**
         * Renderer lazy loader
         * @return Renderer
         * @throws Exception
         */
	public function renderer() {
		return $this->get_from_class_cache( 'Renderer' );
	}

    /**
     * Shorthand for Advertikon\Advertikon::renderer()
     * @param null $element
     * @return string|Renderer
     * @throws Exception
     */
	public function r( $element = null ) {
		if ( is_array( $element ) ) {
			return $this->renderer()->render_element( $element );
		}

		return  $this->renderer();
	}

    /**
     * Url lazy loader
     * @return Url
     * @throws Exception
     */
	public function url() {
		return $this->get_from_class_cache( 'Url' );
	}

    /**
     * Shortcode lazy loader
     * @return object
     * @throws Exception
     */
	public function shortcode() {
		return $this->get_from_class_cache( 'Shortcode' );
	}

    /**
     * Shorthand for Advertikon\Advertikon::renderer()
     * @param null $route
     * @param array $query
     * @return string|Url
     * @throws Exception
     */
	public function u( $route = null, $query = array() ) {
		if ( ! is_null( $route ) ) {
			return $this->url()->url( $route, $query );
		}

		return  $this->url();
	}

    /**
     * Query lazy loader
     * @return Query
     * @throws Exception
     */
	public function query() {
		return $this->get_from_class_cache( 'Query' );
	}

    /**
     * Store lazy loader
     * @return object
     * @throws Exception
     */
	public function store() {
		return $this->get_from_class_cache( 'Store' );
	}

    /**
     * Shorthand for Advertikon\Advertikon::query
     * @param array $query Query
     * @return DB_Result|Query
     * @throws Exception
     */
	public function q( $query = null ) {
		if ( !is_null( $query ) ) {
			return $this->query()->run_query( $query );
		}

		return $this->query();
	}

    /**
     * Option lazy loader
     * @return Option
     * @throws Exception
     */
	public function option() {
		return $this->get_from_class_cache( 'Option' );
	}

    /**
     * Option lazy loader
     * @return Language
     * @throws Exception
     */
	public function language() {
		return $this->get_from_class_cache( 'Language' );
	}

    /**
     * Wrapper to option
     * @return Option
     * @throws Exception
     */
	public function o(){
		return $this->option();
	}

    /**
     * Returns class singleton (for each package)
     * @param string $class_name
     * @return Renderer|Url|Shortcode|Query|Store|Option|Language
     * @throws Exception
     */
	private function get_from_class_cache( $class_name ) {
		$class = $this->get_class_by_namespace( $class_name );

		if ( !isset( $this->class_cache[ $class ] ) ) {
			Profiler::record( $class );
			$this->class_cache[ $class ] = new $class( $this );
			Profiler::record( $class );
		}

		return $this->class_cache[ $class ];
	}
	
	/**
	 * Returns full class name for specific package or fall back to library class
	 * @param string $class_name Class name
	 * @return string Full class name
	 * @throws Exception if class can not be found
	 */
	protected function get_class_by_namespace( $class_name ) {
		$parts = explode( '\\', get_class( $this ) );
		array_pop( $parts ); // Remove current class name
		
		while( $parts ) {
			$new_class_name = implode( '\\', $parts ) . '\\' . $class_name;

			if (class_exists( $new_class_name ) ) {
				return $new_class_name;
			}
			
			array_pop( $parts );
		}
		
		throw new Exception( sprintf( 'Class %s can not be be found in namespace hierarchy', $class_name ) );
	}

	/**
	 * Translator helper
	 * Supports 'sprintf' text substitutions
	 * @param String $text String to be translated
	 * @return String
	 */
	public function __( $text ) {
		return call_user_func_array( [ $this->translator, 'get' ], func_get_args() );
	}

	/**
	 * Get value for administrative area input control element
	 * Checks firstly POST, then tries get configuration value
	 * @param String $name 
	 * @param Mixed $default Default value
	 * @return Mixed
	 */
	public function get_value_from_post( $name , $default = '' ) {
		$name = $this->prefix_name( $name );
		$data = null;
		$parts = null;

		if ( strpos( $name, '[' ) ) {
			$parts = explode( '[', str_replace( ']', '', $name ) );
			$name = array_shift( $parts );
		}

		if ( isset( $this->request->post[ $name ] ) ) {
			$data = $this->request->post[ $name ];

		} else {
			$data = $this->config->get( $name );
		}

		$ret = $this->get_from_array( $data, $parts );

		if ( ! is_null( $ret ) ) {
			return $ret;
		}

		return $default;
	}

	/**
	 * Returns value from array
	 * @param array $array Target array
	 * @param string|array $path Value name to be retrieved
	 * @return mixed
	 */
	public function &get_from_array( &$array, $path = null ) {
		$ret = null;
		$n = null;

		if ( !is_array( $array ) ) {
			if ( $path ) {
				return $ret;
			}

			return $array;
		}

		if ( !$path ) {
			return $array;
		}

		$ret = &$array;

		if ( !is_array( $path ) ) {
			$path = explode( '/', $path );
		}

		foreach( $path as $p ) {
			if( isset( $ret[ $p ] ) ) {
				$ret = &$ret[ $p ];

			} else {
				return $n; // So it can be returned by reference
			}
		}

		return $ret;
	}

	/**
	 * Fetch variable from POST regarding module-specific prefix
	 * @param String $name Variable name
	 * @param Mixed|null $default Default variable
	 * @return Mixed
	 */
	public function post( $name, $default = null ) {
		if( ! empty( $this->request->post[ $this->prefix_name( $name ) ] ) ) {
			return $this->request->post[ $this->prefix_name( $name ) ];
		}

		return $this->p( $name, $default );
	}

	/**
	 * Fetch non-prefixed variable from POST
	 * @param String $name Variable name
	 * @param Mixed|null $default Default variable
	 * @return Mixed
	 */
	public function p( $name, $default = null ) {
		if( isset ( $this->request->post[ $name ] ) ) {
			return $this->request->post[ $name ];
		}

		return $default;
	}

	public function g( $name, $default = null ) {
		if( isset ( $this->request->get[ $name ] ) ) {
			return $this->request->get[ $name ];
		}

		return $default;
	}

	/**
	 * Fetch non-prefixed variable from REQUEST
	 * @param String $name Variable name
	 * @param Mixed|null $default Default variable
	 * @return Mixed
	 */
	public function request( $name, $default = null ) {
		if( isset ( $this->request->request[ $name ] ) ) {
			return $this->request->request[ $name ];
		}

		return $default;
	}

	public function session_get( $name, $default = null ) {
		$session = $this->session;

		if ( is_a( $session, 'Session' ) ) {
			if( isset( $session->data[ $name ] ) ) {
				return $session->data[ $name ];
			}

		} else {
			$this->error( 'Session is not an instance of Session class' );
		}

		return $default;
	}

	/**
	 * Makes extension specific (prefixed) names
	 * @param String $name 
	 * @return String
	 */
	public function prefix_name( $name = null ) {
		return Setting::prefix_name( $name, $this );
	}

	/**
	 * Strips extension specific prefix from name
	 * @param string $name 
	 * @return string
	 */
	public function strip_prefix( $name = null ) {
		return Setting::strip_prefix( $name, $this );
	}

	/**
	 * Recursively converts object to array
	 * @param object|array $object Target object
	 * @return array
	 */
	public function object_to_array( $object ) {
		if( gettype( $object ) === 'array' ) {
			foreach( $object as &$o ) {
				$o = $this->object_to_array( $o );
			}

		} elseif( gettype( $object ) === 'object' ) {
			$object = $this->object_to_array( (array)$object );
		}

		return $object;
	}

	/**
	 * Fixes JSON/MySQL issue with unicode sequences - adds backslashes, removed by MyQSL parser
	 * @param string $string JSON sting 
	 * @return string Fixed JSON string
	 */
	public function fix_json_string( $string ) {
		$this->console->profiler( 'fix json string' );

		$string = preg_replace( '/(?<!\\\)(u[0-9a-f]{4})/', '\\\$1', $string );
		$string = html_entity_decode( $string );

		$this->console->profiler( 'fix json string' );

		return $string;
	}

	/**
	 * Creates specified array structure
	 * @param array &$array Target array
	 * @param string $path Slash separated path of structure to be created
	 * @return array
	 */
	public function &create_array_structure( &$array, $path ) {
		$parts = explode( '/', $path );

		if( $parts ) {
			foreach( $parts as $part ) {
				if( ! isset( $array[ $part ] ) || ! is_array( $array[ $part ] ) ) {
					$array[ $part ] = array();
				}

				$array = &$array[ $part ];
			}
		}

		return $array;
	}

    /**
     * Create element's name by given path
     * @param string $name Path in a form of one/two/three
     * @param string $delimiter
     * @return string
     */
	public function build_name( $name, $delimiter = '-_' ) {
		$parts = preg_split( '/(?<!\\\\)[' . preg_quote( $delimiter ) . ']/', $name );

		$name = array_shift( $parts );
		if( $parts ) {
			$name .= '[' . implode( '][', $parts ) . ']';

			if ( ! count( $parts ) === 1 || '' === current( $parts ) ) {
				$name .= '[]';
			}
		}

		return $name;
	}

	/**
	 * Escapes string to be used with build_name method
	 * @param string $name Name
	 * @return string
	 */
//	public function escape_name( $name ) {
//		return preg_replace( '/(-)/', '\\\$1', $name );
//	}

	/**
	 * Checks whether haystack is ending with specific needle 
	 * @param string $haystack String to be checked
	 * @param string $needle Searched ending 
	 * @return boolean
	 */
	public function is_ended_with( $haystack, $needle ) {
		$needle_length = strlen( $needle );

		if( strlen( $haystack ) < $needle_length ) {
			return false;
		}

		return strrpos( $haystack, $needle, -1 * $needle_length ) !== false;
	}

	/**
	 * Returns URL to language flag image from language data set
	 * @param array $lang Language data set
	 * @return string
	 */
	public function get_lang_flag_url( $lang ) {
		$url = '';

		if ( version_compare( VERSION, '2.3.0.0', '>=' ) ) {
			if ( ! isset( $lang['code'] ) ) {
				$this->error( 'Language data set is missing language code' );
				return '';
			}

			$path = DIR_LANGUAGE . $lang['code'] . '/'. $lang['code'] . '.png';

		} else { 
			if ( !isset( $lang['image'] ) ) {
				$this->error( 'Language data set is missing language image' );
				return '';
			}

			if( version_compare( VERSION, '2.2.0.0', '=' ) && 'gb.png' === $lang['image'] ) {
				$lang['image'] = 'england.png';
			}

			$path = DIR_IMAGE . 'flags/' . $lang['image'];

			if ( !is_file( $path ) ) {
				$path = DIR_LANGUAGE . $lang['code'] . '/'. $lang['code'] . '.png';
			}
		}

		if ( is_file( $path ) ) {
			$url = $this->get_store_url() . substr( $path , strlen( dirname( DIR_SYSTEM ) ) + 1 );
		}

		return $url;
	}

	/**
	 * Returns current language ID
	 * @return int
	 */
	public function get_lang_id() {
		return $this->config->get( 'config_language_id' );
	}

    /**
     * Returns URL for image from DIR_IMAGE folder
     * @param string $path Absolute path to image
     * @return string
     * @throws Exception
     */
	public function get_img_url( $path ) {
		$ret = '';

		// Confine to IMAGE_DIR only
		if ( strpos( $path, DIR_IMAGE ) === 0 ) {
			$ret = $this->u()->catalog_url() . substr( $path, strlen( dirname( DIR_IMAGE ) ) + 1 );
		}

		return $ret;
	}

	/**
	 * Returns route to a template file
	 * @param string $route Route
	 * @return string
	 */
	public function get_view_route( $route ) {
		if ( version_compare( VERSION, '2.2.0.0', '>=' ) ) {
			$template_file = $route;

		} else {
			if ( $this->is_admin() ) {
				return $route;
			}

			if ( file_exists( DIR_TEMPLATE . $this->config->get( 'config_template' ) . '/template/' . $route ) ) {
				$template_file = $this->config->get( 'config_template' ) . '/template/' . $route;

			} else {
				$template_file = 'default/template/' . $route;
			}
		}

		return $template_file;
	}

	/**
	 * Returns absolute path of catalog view file
	 * @param string $route 
	 * @return string
	 */
	public function get_view_catalog( $route ) {
		if ( !$this->is_admin() ) {
			$DIR_TEMPLATE = DIR_TEMPLATE;

		} else {
			$DIR_TEMPLATE = dirname( DIR_SYSTEM ) . '/catalog/view/theme/';
		}

		if ( file_exists( $DIR_TEMPLATE . $this->config->get( 'config_template' ) . '/template/' . $route ) ) {
			$template_file = $this->config->get( 'config_template' ) . '/template/' . $route;

		} else {
			$template_file = 'default/template/' . $route;
		}

		return $DIR_TEMPLATE . $template_file;
 	}

	/**
	 * get_view_route alias
	 * @param string $route Route
	 * @return string
	 */
	public function get_template( $route ) {
		return $this->get_view_route( $route );
	}

    /**
     * Sorts array by some value
     * @param array $array Target array
     * @param string $name Value name
     * @param string $sort
     * @param string $order
     * @return void Output array
     */
//	public function sort_by( &$array, $name, $sort = 'numeric', $order = 'asc' ) {
//		$self = $this;
//
//		usort( $array, function ( $a, $b ) use ( $name, $sort, $order, $self ) {
//			$ret = 0;
//
//			if ( 'numeric' === $sort ) {
//				$ret = $self->sort_num( $a, $b, $name, $order );
//
//			} elseif ( 'alpha' === $sort ) {
//				$ret = $self->sort_alpha( $a, $b, $name, $order );
//			}
//
//			return $ret;
//		} );
//	}

	/**
	 * Implements sort algorithm for numeric sort
	 * @param array $a First item
	 * @param array $b Second item
	 * @param string $name Field to sort with
	 * @param string $order Sort order
	 * @return int
	 */
//	public function sort_num( $a, $b, $name, $order = 'asc' ) {
//		$res = 0;
//
//		if ( isset( $a['name'] ) && isset( $b['name'] ) ) {
//			$res = $a['name'] - $b['name'];
//
//			if ( 'asc' !== $order ) {
//				$res *= -1;
//			}
//		}
//
//		return $res;
//	}

	/**
	 * Implements sort algorithm for numeric sort
	 * @param array $a First item
	 * @param array $b Second item
	 * @param string $name Field to sort with
	 * @param string $order Sort order
	 * @return int
	 */
//	protected function sort_alpha( $a, $b, $name, $order = 'asc' ) {
//		$res = 0;
//
//		if ( isset( $a['name'] ) && isset( $b['name'] ) ) {
//			$res = strcmp( $a['name'], $b['name'] );
//
//			if ( 'asc' !== $order ) {
//				$res *= -1;
//			}
//		}
//
//		return $res;
//	}

    /**
     * Returns current store URL
     * @return string
     * @throws Exception
     */
	public function get_store_href( $ssl = null ) {
		return $this->u()->catalog_url( $ssl );
	}

    /**
     * Returns URL for current store
     * @param null $ssl
     * @return string
     * @throws Exception
     */
	public function get_store_url( $ssl = null ) {
		return $this->get_store_href( $ssl );
	}

	public function get_store_by_id( $id ) {
		$store = null;
		$q = $this->db->query( "SELECT * FROM " . DB_PREFIX . "store WHERE store_id = " . (int)$id );

		if ( $q && $q->num_rows ) {
			$store = $q->row;
		}

		return $store;
	}

	/**
	 * Returns target customer, if possible
	 * Logic is following
	 *   1. If front-end and customer is logged in - use it
	 *   2. If guest session in opened - use guest customer
	 * @return array|null
	 */
	public function get_customer() {
		$customer = null;
		$is_admin = $this->is_admin();

		if( !$is_admin && $this->customer && $this->customer->isLogged() ) {
			$customer = $this->get_customer_by_email( $this->customer->getEmail() );

		} elseif ( !$is_admin && isset( $this->session->data['guest'] ) ) {
			$customer = $this->session->data['guest'];
		}

		return $customer;
	}

	/**
	 * Returns order information
	 * @param int $order_id Order ID
	 * @return array
	 * 	'order_id'
		'invoice_no'
		'invoice_prefix'
		'store_id'
		'store_name'
		'store_url'
		'customer_id'
		'firstname'
		'lastname'
		'email'
		'telephone'
		'fax'
		'custom_field'
		'payment_firstname'
		'payment_lastname'
		'payment_company'
		'payment_address_1'
		'payment_address_2'
		'payment_postcode'
		'payment_city'
		'payment_zone_id'
		'payment_zone'
		'payment_zone_code'
		'payment_country_id'
		'payment_country'
		'payment_iso_code_2'
		'payment_iso_code_3'
		'payment_address_format'
		'payment_custom_field'
		'payment_method'
		'payment_code'
		'shipping_firstname'
		'shipping_lastname'
		'shipping_company'
		'shipping_address_1'
		'shipping_address_2'
		'shipping_postcode'
		'shipping_city'
		'shipping_zone_id'
		'shipping_zone'
		'shipping_zone_code'
		'shipping_country_id'
		'shipping_country'
		'shipping_iso_code_2'
		'shipping_iso_code_3'
		'shipping_address_format'
		'shipping_custom_field'
		'shipping_method'
		'shipping_code'
		'comment'
		'total'
		'order_status_id'
		'order_status'
		'affiliate_id'
		'commission'
		'language_id'
		'language_code'
		'currency_id'
		'currency_code'
		'currency_value'
		'ip'
		'forwarded_ip'
		'user_agent'
		'accept_language'
		'date_added'
		'date_modified',
		'order_status'.,
		'downloaded_products',
		'status_name',
		'products',
		'vouchers',
		'totals',
		'payment_address',
		'shipping_address'
	 */
	public function get_order( $order_id ) {
		$order = $this->get_from_cache( 'order/' . $order_id );

		if ($order) {
			return $order;
		}

		if ( $this->do_cache ) {
			$order = $this->cache->get( 'order_' . $order_id );

			if ($order) {
					return $order;
				}
			}

		$this->console->profiler( 'order' );
		$this->console->profiler( 'OC order' );

		if( $this->is_admin() ) {
			$this->load->model( 'sale/order' );
			$order = $this->model_sale_order->getOrder( $order_id );

		} else {
			$this->load->model( 'checkout/order' );
			$order = $this->model_checkout_order->getOrder( $order_id );
		}

		$this->console->profiler( 'OC order' );

		if ( $order ) {
			$order['order_status']        = $this->get_order_status_name( $order['order_status_id'] );
			$order['downloaded_products'] = $this->get_order_downloaded_products( $order_id );
			$order['status_name']         = $this->get_order_status_name( $order['order_status_id'], $order['language_id'] );
			$order['products']            = $this->get_order_products( $order );
			$order['vouchers']            = $this->get_order_vouchers( $order );
			$order['totals']              = $this->get_order_totals( $order );
			$order['payment_address']     = $this->format_address( $order, 'payment' );
			$order['shipping_address']    = $this->format_address( $order, 'shipping' );

			$this->add_to_cache( 'order/' . $order_id, $order );

			if ( $this->do_cache ) {
				$this->cache->set( 'order_' . $order_id, $order, 60 * 2 );
			}
		}
		
		$this->console->profiler( 'order' );

		return $order;
	}

    /**
     * @param $id
     * @param $value
     * @return array
     * @throws Exception
     */
    public function fetch_custom_field( $id, $value ) {
		$language_id = $this->language()->get_id();

		$q1 = $this->q()->log( 1 )->run_query( [
			'table' => [ 'cf' => 'custom_field' ],
			'field' => [ 'name' => '`cfd`.`name`'],
			'join' => [
				'table' => [ 'cfd' => 'custom_field_description'],
				'on'    => [
					[
						'left'      => '`cf`.`custom_field_id`',
						'operation' => '=',
						'right'     => '`cfd`.`custom_field_id`',
					],
					[
						'left'      => '`cfd`.`language_id`',
						'operation' => '=',
						'right'     => $language_id
					],
				],
			],
			'where' => [
				'field'     => '`cf`.`custom_field_id`',
				'operation' => '=',
				'value'     => $id,
			],
		] );

		if ( !$q1 ) {
			$this->a->error( 'Failed to fetch custom field #' . $id );
			return [];
		}

		$q2 = $this->q()->log( 1 )->run_query( [
			'table' => [ 'cfv' => 'custom_field_value' ],
			'field' => [ 'name' => '`cfvd`.`name`'],
			'join' => [
				'table' => [ 'cfvd' => 'custom_field_value_description'],
				'on'    => [
					[
						'left'      => '`cfv`.`custom_field_value_id`',
						'operation' => '=',
						'right'     => '`cfvd`.`custom_field_value_id`',
					],
					[
						'left'      => '`cfvd`.`language_id`',
						'operation' => '=',
						'right'     => $language_id
					],
				],
			],
			'where' => [
				 [
					'field'     => '`cfv`.`custom_field_value_id`',
					'operation' => '=',
					'value'     => $value,
				],
			],
		] );

		if ( !$q2 ) {
			$this->a->error( 'Failed to fetch value for custom field #' . $id );
            return [];
		}

		return [
			'name'  => $q1['name'],
			'value' => $q2->is_empty() ? $value : $q2['name'],
		];
	}

	/**
	 * Returns products list for specific order
	 * @param int|object $order Order/ID
	 * @param int|null $language_id Language ID
	 * @return array
	 */
	public function get_order_products( $order ) {
		$order_id = null;
		
		if ( is_scalar( $order ) ) {
			$order_id = (int)$order;

		} else {
			$order_id = (int)$order['order_id'];
		}

		if( $this->has_in_cache( 'order_products/' . $order_id ) ) {
			return $this->get_from_cache( 'order_product/' . $order_id );
		}

		$this->console->profiler( 'order products' );

		if ( $order_id ) {
			extract( $this->get_order_details( $order_id ) );	
		}

		$query = $this->db->query(
			"SELECT
				*,
				op.quantity as quantity,
				op.name as name,
				op.model as model,
				op.price as price
			FROM `" . DB_PREFIX . "order_product` `op`
			JOIN `" . DB_PREFIX . "product` `p`
					USING(`product_id`)
			LEFT JOIN `" . DB_PREFIX . "product_description` `pd`
					ON(`op`.`product_id` = `pd`.`product_id` AND `pd`.`language_id` = ${order['language_id']} )
			WHERE `op`.`order_id` = " . (int)$order_id
		);

		$products = isset( $query->rows ) ? $query->rows : [];

		foreach( $products as &$product ) {
			$product['formatted_price'] = $this->currency->format( $product['price'], $order['currency_code'], $order['currency_value'] );
			$product['formatted_total'] = $this->currency->format( $product['total'], $order['currency_code'], $order['currency_value'] );

			$opt = $this->db->query(
				"SELECT
					oo.order_option_id,
					oo.product_option_id,
					oo.value as option_value,
					od.name as option_name,
					oo.type as option_type,
					po.option_id,
					po.required,
					pov.product_option_value_id,
					pov.option_value_id,
					pov.quantity,
					pov.subtract,
					pov.price,
					pov.price_prefix,
					pov.points,
					pov.points_prefix,
					pov.weight,
					pov.weight_prefix,
					ovd.name as option_value_name
				FROM " . DB_PREFIX . "order_option oo
				JOIN " . DB_PREFIX . "product_option po
					USING(product_option_id)
				LEFT JOIN " . DB_PREFIX . "option_description od
					ON(od.option_id = po.option_id AND od.language_id = ${order['language_id']} )
				LEFT JOIN " . DB_PREFIX . "product_option_value pov
					ON(oo.product_option_value_id = pov.product_option_value_id)
				LEFT JOIN " . DB_PREFIX . "option_value_description ovd
					ON(pov.option_value_id = ovd.option_value_id AND ovd.language_id = ${order['language_id']} )
				WHERE " .
					"oo.order_product_id = " . $product['order_product_id'] 
			);

			$options = [];

			if ( isset( $opt->rows ) ) {
				foreach( $opt->rows as $row ) {
					if ( array_key_exists( $row['option_id'], $options ) ) {
						$options[ $row['option_id'] ]['values'][] = [
							'order_option_id'         => $row['order_option_id'],
							'option_value'            => $row['option_value'],
							'required'                => $row['required'],
							'product_option_value_id' => $row['product_option_value_id'],
							'option_value_id'         => $row['option_value_id'],
							'quantity'                => $row['quantity'],
							'subtract'                => $row['subtract'],
							'price'                   => $row['price'],
							'price_prefix'            => $row['price_prefix'],
							'points'                  => $row['points'],
							'points_prefix'           => $row['points_prefix'],
							'weight'                  => $row['weight'],
							'weight_prefix'           => $row['weight_prefix'],
							'option_value_name'       => $row['option_value_name'],
						];

					} else {
						$options[ $row['option_id'] ] = [
							'option_id'         => $row['option_id'],
							'product_option_id' => $row['product_option_id'],
							'option_name'        => $row['option_name'],
							'option_type'       => $row['option_type'],
							'values'            => [ [
								'order_option_id'         => $row['order_option_id'],
								'option_value'            => $row['option_value'],
								'required'                => $row['required'],
								'product_option_value_id' => $row['product_option_value_id'],
								'option_value_id'         => $row['option_value_id'],
								'quantity'                => $row['quantity'],
								'subtract'                => $row['subtract'],
								'price'                   => $row['price'],
								'price_prefix'            => $row['price_prefix'],
								'points'                  => $row['points'],
								'points_prefix'           => $row['points_prefix'],
								'weight'                  => $row['weight'],
								'weight_prefix'           => $row['weight_prefix'],
								'option_value_name'       => $row['option_value_name'],
							] ],
						];
					}
				}
			}

			$product['options'] = $options;
		}

		$this->add_to_cache( 'order_products/' . $order_id, $products );
		$this->console->profiler( 'order products' );

		return $products;
	}

	/**
	 * Return order details eg language_id, currencty_code etc
	 * @param int $order_id Order ID 
	 * @return array
	 */
	protected function get_order_details( $order_id ) {
		if ( $this->has_in_cache( 'order_details/' . $order_id ) ) {
			return $this->get_from_cache( 'order_details/' . $order_id );
		}

		$language_id = null;
		$currency_code = null;
		$currency_value = null;
		$order = $this->get_order_info( $order_id );

		if( isset( $order['language_id'] ) ) {
			$language_id = (int)$order['language_id'];

		} else {
			$language_id = $this->config->get( 'config_language_id' );
		}

		if( isset( $order['currency_code'] ) ) {
			$currency_code = $order['currency_code'];

		} else {
			$currency_code = $this->config->get( 'config_currency' );
		}

		if( isset( $order['currency_value'] ) ) {
			$currency_value = (float)$order['currency_value'];

		} else {
			$currency_value = 1;
		}

		$details = [
			'language_id'    => $language_id,
			'currency_code'  => $currency_code,
			'currency_value' => $currency_value,
		];

		$this->add_to_cache( 'order_details/' . $order_id , $details );

		return $details;
	}

		/**
	 * Formats address pertain to an order
	 * @param array $data Order detail
	 * @param string $type Address type
	 * @return string
	 */
	public function format_address( $data, $type ) {
		if ( !empty( $data[ $type . '_address_format' ] ) ) {
				$format = $data[ $type . '_address_format' ];

		} else {
			$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
		}

		$find = array(
			'{firstname}',
			'{lastname}',
			'{company}',
			'{address_1}',
			'{address_2}',
			'{city}',
			'{postcode}',
			'{zone}',
			'{zone_code}',
			'{country}'
		);

		$replace = array(
			'firstname' => isset( $data[ $type . '_firstname' ] ) ?
				$data[ $type . '_firstname' ]: '',
			'lastname'  => isset( $data[ $type . '_lastname' ] ) ?
				$data[ $type . '_lastname' ] : '',
			'company'   => isset( $data[ $type . '_company' ] ) ?
				$data[ $type . '_company' ] : '',
			'address_1' => isset( $data[ $type . '_address_1' ] ) ?
				$data[ $type . '_address_1' ] : '',
			'address_2' => isset( $data[ $type . '_address_2' ] ) ?
				$data[ $type . '_address_2' ] : '',
			'city'      => isset( $data[ $type . '_city' ] ) ?
				$data[ $type . '_city' ] : '',
			'postcode'  => isset( $data[ $type . '_postcode' ] ) ?
				$data[ $type . '_postcode' ] : '',
			'zone'      => isset( $data[ $type . '_zone' ] ) ?
				$data[ $type . '_zone' ] : '',
			'zone_code' => isset( $data[ $type . '_zone_code' ] ) ? 
				$data[ $type . '_zone_code' ] : '',
			'country'   => isset( $data[ $type . '_country' ] ) ?
				$data[ $type . '_country' ] : '',
		);

		$ret = preg_split( '/[\r\n]{1,}/', trim( str_replace( $find, $replace, $format ) ) );
		array_walk( $ret, function( &$e ) { $e = '<p>' . $e . '</p>'; } );

		return implode( '', $ret );
	}

	/**
	 * Returns products list. Result of this function is not cached
	 * @param int $order_id Product ID
	 * @return array
	 */
	public function get_products_by_id( $product_id ) {
		if ( ! $product_id ) {
			return array();
		}

		$query = $this->db->query(
			"SELECT * FROM `" . DB_PREFIX . "product` `p`
			WHERE `p`.`product_id` IN (" . implode( ')(', (array)$product_id ) . ")"
		);

		return $query->rows;
	}

        /**
         * Returns vouchers pertain to an order
         * @param $order
         * @return array
         */
	public function get_order_vouchers( $order ) {
		$order_id = null;
		
		if ( is_scalar( $order ) ) {
			$order_id = (int)$order;

		} else {
			$order_id = (int)$order['order_id'];
		}

		if( $this->has_in_cache( 'order_vouchers/' . $order_id ) ) {
			return $this->get_from_cache( 'order_vouchers/' . $order_id );
		}

		$this->console->profiler( 'order products' );

		if ( $order_id ) {
			extract( $this->get_order_details( $order_id ) );	
		}

		$vouchers = [];

		$query = $this->db->query(
			"SELECT * FROM `" . DB_PREFIX . "order_voucher`
			WHERE `order_id` = $order_id" 
		);

		if ( isset( $query->rows ) ) {
			foreach( $query->rows as $row ) {
				$row['formatted_amount'] = $this->currency->format( $row['amount'], $order['currency_code'], $order['currency_value'] );
				$vouchers[] = $row;
			}
		}
		
		$this->add_to_cache( 'order_vouchers/' . $order_id, $vouchers );

		return $vouchers;
	}

	/**
	 * Returns an order totals list
	 * @param int $order_id Order ID
	 * @return array
	 */
	public function get_order_totals( $order ) {
		$order_id = null;
		
		if ( is_scalar( $order ) ) {
			$order_id = (int)$order;

		} else {
			$order_id = (int)$order['order_id'];
		}

		if( $this->has_in_cache( 'order_totals/' . $order_id ) ) {
			return $this->get_from_cache( 'order_totals/' . $order_id );
		}

		if ( $order_id ) {
			extract( $this->get_order_details( $order_id ) );	
		}

		$totals = array();

		$query = $this->db->query(
			"SELECT * FROM `" . DB_PREFIX . "order_total`
			WHERE `order_id` = " . (int)$order_id . "
			ORDER BY `sort_order` ASC"
		);

		if( $query->num_rows > 0 ) {
			foreach( $query->rows as $row ) {
				$row['text'] = $this->currency->format( $row['value'], $order['currency_code'], $order['currency_value'] );
				$totals[ $row['code'] ] = $row;
			}
		}
		
		$this->add_to_cache( 'order_totals/' . $order_id, $totals );

		return $totals;
	}

	/**
	 * Returns order status name by its ID
	 * @param int $order_status_id Order status ID
	 * @param string|null $language_id Language code, optional
	 * @return string
	 */
	public function get_order_status_name( $order_status_id, $language_id = null ) {

		if( is_null( $language_id ) ) {
			$language_id = $this->config->get( 'config_language_id' );
		}

		if( ! $this->has_in_cache( 'order_statuses/' . $language_id . '/' . $order_status_id ) ) {
			$query = $this->db->query(
				"SELECT * FROM `" . DB_PREFIX . "order_status`
				WHERE `language_id` = " . (int)$language_id
			);

			$ret = array();

			foreach( $query->rows as $row ) {
				$ret[ $row['order_status_id'] ] = $row['name'];
			}

			$this->add_to_cache( 'order_statuses/' . $language_id, $ret );
			
			if( isset( $ret[ $order_status_id ] ) ) {
				return $ret[ $order_status_id ];
			}

			return '';
		}

		return $this->get_from_cache( 'order_statuses/' . $language_id . '/' . $order_status_id );
	}

	/**
	 * Returns download-able products for specific order, if present
	 * @param int $order_id Order ID
	 * @return array
	 */
	public function get_order_downloaded_products( $order_id ) {
		if( ! $this->has_in_cache( 'downloaded_products/' . $order_id ) ) {

			$this->console->profiler( 'Downloaded products' );

			$query = $this->db->query(
				"SELECT * FROM `" . DB_PREFIX . "order_product` `op`
					LEFT JOIN `" . DB_PREFIX . "product_to_download` `pd`
						USING(`product_id`)
					WHERE `op`.`order_id` = " . (int)$order_id . "
						AND `pd`.`download_id` IS NOT NULL"
			);

			$this->console->profiler( 'Downloaded products' );
			
			$this->add_to_cache( 'downloaded_products/' . $order_id, $query->rows );

			return $query->rows;
		}

		return $this->get_from_cache( 'downloaded_products/' . $order_id );
	}

    /**
     * Returns order information by its ID
     * @param int $order_id Order ID
     * @return object|null Advertikon\Db_Result
     * @throws Exception
     */
	public function get_order_info( $order_id ) {
		if( ! $this->has_in_cache( 'orders/' . $order_id ) ) {
			$query = $this->q( array(
				'table' => 'order',
				'query' => 'select',
				'where' => array(
					'operation' => '=',
					'field'     => 'order_id',
					'value'     => $order_id,
				),
			) );
			
			$this->add_to_cache( 'orders/' . $order_id, $query );

			return $query;
		}

		return $this->get_from_cache( 'orders/' . $order_id );
	}

	/**
	 * Returns customer group info by its ID
	 * @param int $group_id Customer group ID
	 * @return array
	 */
	public function get_customer_group_info( $group_id ) {
		if( ! $this->has_in_cache( 'customer_groups/' . $group_id ) ) {
			$query = $this->db->query(
				"SELECT * FROM `" . DB_PREFIX . "customer_group` `cg`
					LEFT JOIN `" . DB_PREFIX . "customer_group_description` `cgd`
						USING(`customer_group_id`)
				WHERE `cg`.`customer_group_id` = " . (int)$group_id . "
					AND `cgd`.`language_id` = " . (int)$this->config->get( 'config_language_id' )
			);
			
			$this->add_to_cache( 'customer_groups/' . $group_id, $query->row );

			return $query->row;
		}

		return $this->get_from_cache( 'customer_groups/' . $group_id );
	}

    /**
     * Returns product details
     * @param int $product_id Product iD
     * @return array
     * @throws Exception
     */
	public function get_product_info( $product_id ) {
		$ret = [];

		if( !$this->has_in_cache( 'products/' . $product_id ) ) {

			if ( $this->do_cache ) {
				$ret = $this->cache->get( 'product_info_' . $product_id );
			}

			if ( !$ret ) {
				$ret = ADK()->q( [
					'table' => [ 'p' => 'product', ],
					'query' => 'select',
					'join'  => [
						'table' => [ 'pd' => 'product_description' ],
						'type'  => 'left',
						'using' => 'product_id',
					],
					'where' => [
						[
							'field'     => '`p`.`product_id`',
							'operation' => '=',
							'value'     => (int)$product_id,
						],
						[
							'field'     => '`pd`.`language_id`',
							'operation' => '=',
							'value'     => (int)$this->config->get( 'config_language_id' ),
						]
					],
				] );
			}

			$this->add_to_cache( 'products/' . $product_id, $ret );

			if ( $this->do_cache ) {
				$this->cache->set( 'product_info_' . $product_id, $ret, 600 );
			}

		} else {
			$ret = $this->get_from_cache( 'products/' . $product_id );
		}

		return $ret;
	}

	/**
	 * Returns region information
	 * @param int $region_id Region ID
	 * @return array
	 */
	public function get_region_info( $region_id ) {
		
		if( ! $this->has_in_cache( 'regions/' . $region_id ) ) {
			$query = $this->db->query(
				"SELECT
					`c`.`country_id`,
					`c`.`name` as `country_name`,
					`c`.`iso_code_2` as `country_iso`,
					`z`.`name` as `zone_name`,
					`z`.`code` as `zone_code`
				FROM `" .DB_PREFIX . "zone` `z`
					LEFT JOIN `" . DB_PREFIX . "country` `c`
						USING(`country_id`)
				WHERE `z`.`zone_id` = " . (int)$region_id
			);

			$this->add_to_cache( 'regions/' . $region_id, $query->row );

			return $query->row;
		}

		return $this->get_from_cache( 'regions/' . $region_id );
	}

	/**
	 * Returns voucher information
	 * @param int $voucher_id Voucher ID
	 * @return array
	 */
	public function get_voucher( $voucher_id ) {
		if( !$this->has_in_cache( 'vouchers/' . $voucher_id ) ) {
			$query = $this->db->query(
				"SELECT * FROM `" . DB_PREFIX . "voucher` `v`
					LEFT JOIN `" . DB_PREFIX . "voucher_theme`
						USING(`voucher_theme_id`)
				WHERE `voucher_id` = " . (int)$voucher_id
			);

			$this->add_to_cache( 'vouchers/' . $voucher_id, $query->row );

			return $query->row;
		}

		return $this->get_from_cache( 'vouchers/' . $voucher_id );
	}

	/**
	 * Returns customer by its email. Result of this function is not cached
	 * @param string $email Email address
	 * @return array
	 */
	public function get_customer_by_email( $email ) {
		if ( $this->has_in_cache( 'customer/' . $email ) ) {
			return $this->get_from_cache( 'customer/' . $email );
		}

		$ret = $this->_get_customer_info( [
			'field'     => 'LCASE(`c`.`email`)',
			'operation' => '=',
			'value'     => mb_strtolower( $email ),
		] );

		$this->add_to_cache( 'customer/' . $email, $ret );

		return $ret;
	}

	/**
	 * Returns customer by its email. Result of this function is not cached
	 * @param string $email Email address
	 * @return array
	 */
	public function get_customer_by_id( $id ) {
		if ( $this->has_in_cache( 'customer/' . $id ) ) {
			return $this->get_from_cache( 'customer/' . $id );
		}

		$ret = $this->_get_customer_info( [
			'field'     => '`c`.`customer_id`',
			'operation' => '=',
			'value'     => (int)$id,
		] );

		$this->add_to_cache( 'customer/' . $id, $ret );

		return $ret;
	}

    /**
     * Returns customer information
     * @param array $where Where clause
     * @return array
     * @throws Exception
     */
	protected function _get_customer_info( $where ) {
		$ret = [];

		$query = $this->q( [
			'query' => 'select',
			'table' => ['c' => 'customer', ],
			'field' => [ '`a`.*', '`c`.*', ],
			'join'  => [
				'type'  => 'left',
				'table' => [ 'a' => 'address' ],
				'using' => 'address_id',
			],
			'where' => $where,
		] );

		if ( $query ) {
			$ret = $this->get_country_zone_details( $query->current() );
		}

		return $ret;
	}

    /**
     * Populates customer details with country and zone details
     * @param array $customer Customer details
     * @return array
     * @throws Exception
     */
	public function get_country_zone_details( $customer ) {
		$ret = $customer;

		if ( isset( $customer['country_id'], $customer['zone_id'] ) ) {
			$query = $this->q( [
				'query' => 'select',
				'table' => [ 'c' => 'country' ],
				'field' => [ 'country_name' => '`c`.`name`', 'zone_name' => '`z`.`name`', ],
				'join' => [
					'type'  => 'left',
					'table' => [ 'z' => 'zone' ],
					'using' => 'country_id',
				],
				'where' => [
					[
						'field'     => '`c`.`country_id`',
						'operation' => '=',
						'value'     => $customer['country_id'],
					],
					[
						'field'     => '`z`.`zone_id`',
						'operation' => '=',
						'value'     => $customer['zone_id'],
					]
				],
 			] );

 			if ( $query && count( $query ) ) {
 				$ret = array_merge( $ret, $query->current() );
 			}
		}

		return $ret;
	}

	/**
	 * Slices the string eg slice( 1,3 ): |0|1|2|3|4|5|6| => |0|4|5|6|
	 * @param string $str Sting to be sliced 
	 * @param int $start Start position (offset) (excluded from the resulting string)
	 * @param int $end End position (offset) (excluded from the resulting string)
	 * @return string
	 */
	public function str_slice( $str, $start = null, $end = null ) {
		$len = strlen( $str );

		// Fix start position
		if ( is_null( $start ) ) {
			$start = 0;

		} elseif ( $start < 0 ) {
			$start = $len + $start;
		}

		if ( $start < 0 ) {
			$start = 0;

		} elseif ( $start >= $len ) {
			$start = $len - 1;
		}

		// Fix end position
		if ( is_null( $end ) ) {
			$end = $len - 1;

		} elseif ( $end < 0 ) {
			$end = abs( $end );
		}

		if ( $end < 0 ) {
			$end = 0;

		} elseif ( $end >= $len ) {
			$end = $len - 1;
		}

		if ( $start > $end ) {
			$start = $end;
		}
		$before = substr( $str, 0, $start );
		$after = substr( $str, $end + 1 );

		return $before . $after;
	}

	/**
	 * Returns file upload errors by code
	 * @param int $code Error code
	 * @return string
	 */
	public function get_file_upload_error( $code ) {
		$ret = '';

		switch( $code ) {
			case UPLOAD_ERR_INI_SIZE:
				$ret = $this->__( 'Exceeded file size limit of %s bytes', ini_get( 'upload_max_filesize' ) );
				break;
			case UPLOAD_ERR_FORM_SIZE :
				$ret = $this->__( 'Exceeded file size limit (HTML form restriction)' );
				break;
			case UPLOAD_ERR_PARTIAL :
				$ret = $this->__( 'File has been uploaded partially' );
				break;
			case UPLOAD_ERR_NO_FILE :
				$ret = $this->__( 'File has not been uploaded' );
				break;
			case UPLOAD_ERR_NO_TMP_DIR :
				$ret = $this->__( 'Upload temporary folder is missing' );
				break;
			case UPLOAD_ERR_CANT_WRITE :
				$ret = $this->__( 'Unable to write file into disk' );
				break;
			case UPLOAD_ERR_EXTENSION :
				$ret = $this->__( 'Stopped by PHP extension' );
				break;
		}

		return $ret;
	}

	/**
	 * Formats bytes into kB. MB etc
	 * @param int $size Bytes
	 * @return string
	 */
	public function format_bytes( $size ) {
		$points = 2;
		$pow = null;
		$value = (float)$size;
		$units = array( "B", "kB", "MB", "GB" );

		if ( $value <= 0 ) {
			$value = 0;
			$pow = 0;

		} else {
			$pow = floor( log10( $value ) / 3 );
		}

		if( $pow > 0 ) {
			$value /= pow( 10, $pow * 3 );
		}

		return round( $value, $points ) . " " . $units[ $pow ];
	}

	public function unformat_bytes( $string ) {
		if ( stripos( $string, 'b' ) > 0 ) {
			return (int)$string;
		}

		if ( stripos( $string, 'k' ) > 0 ) {
			return (int)$string * 1000;
		}

		if ( stripos( $string, 'm' ) > 0 ) {
			return (int)$string * 1000 * 1000;
		}

		if ( stripos( $string, 'g' ) > 0 ) {
			return (int)$string * 1000 * 1000 * 1000;
		}

		return $string;
	}

	/**
	 * Prepare JSON response
	 * @param Mixed $data Data to be sent
	 */
	public function json_response( $data = null ) {
		$json = json_encode( $data );

		if ( json_last_error() ) {
			$this->response->addHeader( 'HTTP/1.0 500 Internal Server Error' );
			$this->error( 'Error while encoding JSON data for response:', $data );

		} else {
			$this->response->addHeader( 'Content-Type: application/json' );
			$this->response->setOutput( $json );
		}
	}

	/**
	 * Write an critical error to a log
	 * @return void
	 */
	public function critical() {
		$this->message_urgency = Console::LEVEL_CRITICAL;
		call_user_func_array( [ $this, '_log' ], func_get_args() );
		$this->message_urgency = Console::LEVEL_NORMAL;
	}

	/**
	 * Write an error to a log
	 * @return void
	 */
	public function error() {
		$this->message_urgency = Console::LEVEL_ERROR;
		call_user_func_array( [ $this, '_log' ], func_get_args() );
		$this->message_urgency = Console::LEVEL_NORMAL;
	}

	/**
	 * Write an error to a log
	 * @return void
	 */
	public function debug() {
		$this->message_urgency = Console::LEVEL_DEBUG;
		call_user_func_array( [ $this, '_log' ], func_get_args() );
		$this->message_urgency = Console::LEVEL_NORMAL;
	}

    /**
     * Shows call stack
     * @param int $depth
     */
    public function stack( $depth = 20 ) {
	    $this->console->stack( $depth );
    }

	/**
	 * Write an error to a log
	 * @return void
	 */
	public function log() {
		call_user_func_array( [ $this, '_log' ], func_get_args() );
	}

	/**
	 * Wrapper for OpnCart logger function
	 * @return void
	 */
	protected function _log() {
		if ( $this->message_urgency < $this->log_strictness ) {
			return;
		}

		$args = func_get_args();
		$line = '';

		foreach( $args as $a ) {
			if ( $a instanceof \Closure ) {
				$a = $a();
			}

			if ( is_a( $a, '\\Exception' ) || is_a( $a, '\\Error' ) ) {

				// Error message
				if ( $this->message_urgency >= Console::LEVEL_ERROR ) {
					$a = $a->__toString();
					
				} else {
					$a = $a->getMessage() . ' in ' . $a->getFile() . ':' . $a->getLine(); 
				}

				// Stack trace already been handled
				$line = null;
			}

			if( $this->console ) {
				if ( !$line && !is_null( $line ) ) {
					if ( ( $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) ) && isset( $trace[ 2 ] ) ) {
						$line = sprintf( ". In file: %s, line: %s", @$trace[ 2 ][ 'file'], @$trace[ 2 ]['line'] );
					}
				}

				$this->console->log( $a, $line, $this->message_urgency );
			}
		}
	}

	/**
	 * Adds value to one-time cache
	 * @param string $name Value name
	 * @param mixed $value Value 
	 * @return void
	 */
	public function add_to_cache( $name, $value ) {
		self::$one_time_cache[ $name ] = $value;
	}

	/**
	 * Checks if value exists in one-time cache
	 * @param string $name Value name
	 * @return boolean
	 */
	public function has_in_cache( $name ) {
		return isset( self::$one_time_cache[ $name ] );
	}

	/**
	 * Resets one-time cache
	 * @param {string|null} $name Cache entry
	 */
	public function reset_cache( $name = null ) {
		if ( is_null( $name ) ) {
			self::$one_time_cache = [];

		} else if ( isset( self::$one_time_cache[ $name ] ) ) {
			unset( self::$one_time_cache[ $name ] );
		}
	}

	/**
	 * Fetches value form one-time cache
	 * @param string $name Value name
	 * @return mixed
	 */
	public function get_from_cache( $name ) {
		return isset( self::$one_time_cache[ $name ] ) ? self::$one_time_cache[ $name ] : null;
	}

	/**
	 * Convert string from camelCase to underscore_notation
	 * @param string $name
	 * @return string
	 */
//	public function underscore( $name ) {
//		return strtolower( preg_replace( '#(.)([A-Z])#', '$1_$2', $name ) );
//	}

	/**
	 * Camel-case name
	 * @param string $name
	 * @return string
	 */
//	public function camelcase( $name ) {
//		$names = explode( '_', $name );
//		$c_name = '';
//
//		foreach( $names as $part ) {
//			$c_name .= ucfirst( $part );
//		}
//
//		return lcfirst( $c_name );
//	}

	/**
	 * Check whether user has specific permission
	 * @param string $permission
	 * @return boolean
	 */
	public function has_permissions( $permission_name ) {
		if ( defined( 'DIR_CATALOG' ) ) {
			return $this->user->hasPermission( $permission_name, $this->full_name );
		}

		return false;
	}

	/**
	 * Check element for empty
	 * @param mixed $elem
	 * @param boolean $rec Flag as to perform recursive check
	 * @return boolean
	 */
	public function is_empty( $elem, $rec = true ) {
		$empty = true;

		try {

			// Object or array
			if ( is_object( $elem ) || is_array( $elem ) ) {
				foreach( $elem as $v ) {
					$empty = false;

					if ( $rec && ( is_object( $v ) || is_array( $v ) ) ) {
						$empty = true;

						if ( ! $this->is_empty( $v, $rec ) ) {
							$empty = false;
						} 
					}

					if ( ! $empty ) {
						throw new Exception( 'not empty' );
					}
				}

			} else {
				if( ! empty( $elem ) ) {
					throw new Exception( 'not empty' );
				}
			}

		} catch ( Exception $e ) {
			$empty = false;
		}

		return $empty;
	}

	static public function isEmpty( $e ) {
		if ( empty( $e ) ) {
			return true;
		}

		if ( is_array( $e ) ) {
			foreach( $e as $ee ) {
				if ( !self::isEmpty( $ee ) ) {
					return false;
				}
			}

			return true;

		} else {
			return false;
		}
	}

	static public function is_false( $value ) {
		if ( is_scalar( $value ) ) {
			return !(bool)$value || strtolower( $value ) === 'false';
		}
	}

	/**
	 * Obscure part of a string
	 * @param string $str
	 * @param integer $part
	 * @param string $obscureChar
	 * @param boolean $obscureSpace
	 * @return string
	 */
	public function obscure_str( $str, $part = 75, $char = '*', $obscure_space = false ) {

		if ( ! is_string( $str ) ) {
			return '';
		}

		if ( is_null( $part ) ) {
			$part = 75;
		}

		if ( is_null( $char ) ) {
			$char = '*';
		}

		$part = is_int( $part ) ? min( (int)$part, 100 ) : 100;
		$len = ceil( strlen( $str ) * ( $part / 100 ) );

		for( $i = 0; $i < $len ; $i++ ) {
			if ( $obscure_space || $str[ $i ] !== self::CHAR_SPACE ) {
				$str[ $i ] = $char;
			}
		}

		return $str;
	}

	/**
	 * Decoding JSON string
	 * @param String $str String to be evaluated
	 * @return array
	 * @throws \Advertikon\Exception on evaluation error
	 */
	public function json_decode( $str ) {
		$str = str_replace( [ "\r\n", "\r", "\n", ], [ '', ], $str );
		$json = json_decode( $str, true );

		if ( json_last_error() ) {
			$mess = $mess = $this->__( 'Failed to decode JSON string: %s', $this->get_json_error() );
			$this->error( $mess );
			throw new Exception( $mess );
		}

		return $json;
	}

	/**
	 * Encodes value to JSON representation
	 * @param mixed $value Value to be encoded
	 * @return string
	 * @throws \Advertikon\Exception on error
	 */
	public function json_encode( $value ) {
		$ret = json_encode( $value, JSON_HEX_QUOT | JSON_HEX_APOS );
		$ret = str_replace( [ "\r\n", "\r", "\n", ], [ '', ], $ret );

		if ( false === $ret ) {
			$mess = $mess = $this->__( 'Failed to encode value to JSON string: %s', $this->get_json_error() );
			$this->error( $mess );
			throw new Exception( $mess );
		}

		return $ret;
	}

	/**
	 * Returns last JSON error
	 * @return string|int
	 */
	public function get_json_error() {
		if ( function_exists( 'json_last_error_msg' ) ) {
			$mess = json_last_error_msg();

		} else {
			 $mess = json_last_error();
		}

		return $mess;
	}

    /**
     * @param $order_id
     * @param array $data
     * @param null $field
     * @throws Exception
     */
//    public function add_custom_field_array( $order_id, array $data, $field = null ) {
//		if ( is_null( $field ) ) {
//			$field = 'payment';
//		}
//
//		if ( false === strpos( $field, '_' ) ) {
//			$field = ( $field ? $field . '_' : '' ) . 'custom_field';
//		}
//
//		$field_val = $this->get_custom_field( $order_id, $field );
//
//		foreach( $data as $k => $v ) {
//			$field_val[ $k ] = $v;
//		}
//
//		if ( version_compare( VERSION, '2.1.0.1', '>=' ) ) {
//			$serialized = $this->json_encode( $field_val );
//
//		} else {
//			$serialized = serialize( $field_val );
//
//			if ( !$serialized ) {
//				throw new Exception( $this->__( 'Failed to add data to custom field' ) );
//			}
//		}
//
//		$this->q()->log( 0 )->run_query( array(
//			'table' => 'order',
//			'query' => 'update',
//			'set'   => array(
//				$field => $serialized,
//			),
//			'where' => array(
//				'field'     => 'order_id',
//				'operation' => '=',
//				'value'     => $order_id,
//			)
//		) );
//	}

	/**
	 * Adds value to custom field of OpenCarts' DB table
	 * @param int|array $order Order ID or order itself
	 * @param string $name Value name, not used when field_val is supplied
	 * @param mixed $value Value, not used when field_val is supplied
	 * @param string $field Custom field name
	 * @param mixed $field_val Optional field value
	 * @throws \Advertikon\Exception on operation failure
	 * @return void
	 */
//	public function add_custom_field( $order_id, $name, $value, $field = null ) {
//		if ( is_null( $field ) ) {
//			$field = 'payment';
//		}
//
//		if ( false === strpos( $field, '_' ) ) {
//			$field = ( $field ? $field . '_' : '' ) . 'custom_field';
//		}
//
//		$field_val = $this->get_custom_field( $order_id, $field );
//		$field_val[ $name ] = $value;
//
//		if ( version_compare( VERSION, '2.1.0.1', '>=' ) ) {
//			$serialized = $this->json_encode( $field_val );
//
//		} else {
//			$serialized = serialize( $field_val );
//
//			if ( !$serialized ) {
//				throw new Exception( $this->__( 'Failed to add data to custom field' ) );
//			}
//		}
//
//		$this->q()->log( 0 )->run_query( array(
//			'table' => 'order',
//			'query' => 'update',
//			'set'   => array(
//				$field => $serialized,
//			),
//			'where' => array(
//				'field'     => 'order_id',
//				'operation' => '=',
//				'value'     => $order_id,
//			)
//		) );
//	}

	/**
	 * Serializes custom field data
	 * @param int $order_id Order ID
	 * @param string $field Field name, optional
	 * @throws \Advertikon\Exception on operation error
	 * @return array
	 */
	public function get_custom_field( $order_id, $field = null ) {
		if ( is_null( $field ) ) {
			$field = 'payment';
		}

		if ( false === strpos( $field , "_" ) ) {
			$field = ( $field ? $field . '_' : '' ) . 'custom_field';
		}

		$order = $this->q( array(
			'table'  => 'order',
			'query'  => 'select',
			'where'  => array(
				'field'     => 'order_id',
				'operation' => '=',
				'value'     => $order_id,
			)
		) );

		if ( !count( $order ) ) {
			$mess = $this->__(
				'Failed to fetch custom field for order #%s - Order is missing',
				$order_id
			);

			$this->error( $mess );
			throw new Exception( $mess );
		}

		if ( version_compare( VERSION, '2.1.0.1', '>=' ) ) {
			try {
				$ret = $this->json_decode( $order[ $field ] ) ?: [];
				
			} catch ( Exception $e ) {
				$ret = [];
			}

		} else {
			$ret = unserialize( $order[ $field ] );
		}

		return $ret;
	}

    /**
     * Send email message
     * @param String $to Recipient's address
     * @param String $subject Email subject
     * @param String $message Message body
     * @param Boolean $singleton SIngleton flag.
     * Optional, default value - true. If true will reuse existing object.
     * @return bool
     * @throws \Exception
     */
	public function mail( $to, $subject, $message = '', $singleton = true, $text = '' ) {
		if ( !$this->mail_instance || !$singleton ) {
			$this->mail_instance = new \Mail;
			$this->init_mail( $this->mail_instance );
		}

		$mail = $this->mail_instance;

		$mail->setTo( $to );
		$mail->setFrom( $this->config->get('config_email') );
		$mail->setSender( html_entity_decode( $this->config->get( 'config_name' ), ENT_QUOTES, 'UTF-8') );
		$mail->setSubject( html_entity_decode( $subject, ENT_QUOTES, 'UTF-8' ) );
		$mail->setText( $text );
		$mail->send();

		return true;
	}

	/**
	 * Returns total product details
	 * @param int $product_id 
	 * @return array
	 */
	public function get_product( $product_id ) {
		$product_id = (int)$product_id;

		if( !$this->has_in_cache( 'product/' . $product_id ) ) {
			$product = array();

			if( $this->customer && $this->customer->isLogged() ) {
				$customer_group_id = $this->customer->getGroupId();

			} else {
				$customer_group_id = $this->config->get( 'config_customer_group_id' );
			}

			$customer_group_id = (int)$customer_group_id;

			// Min query
			$product_query = $this->db->query(
				"SELECT
					*,
					( SELECT `price`
						FROM `" . DB_PREFIX . "product_special`
						WHERE `product_id` = '$product_id'
							AND `customer_group_id` = '$customer_group_id'
							AND ( `date_start` = '000-00-00' OR `date_start` <= NOW() )
							AND ( `date_end` = '0000-00-00' OR `date_end` > NOW())
						ORDER BY `priority` DESC LIMIT 1 ) as special
				FROM `" . DB_PREFIX . "product` `p`
					LEFT JOIN `" . DB_PREFIX . "product_description` `pd`
						USING(`product_id`)
				WHERE `p`.`product_id` = " . (int)$product_id . "
					AND `pd`.`language_id` = " . (int)$this->config->get( 'config_language_id')
			);

			if( $product_query && $product_query->num_rows > 0 ) {
				$product = $product_query->row;

				// Discount query
				$discount_query = $this->db->query(
					"SELECT `price` FROM `" . DB_PREFIX . "product_discount`
					WHERE `product_id` = '$product_id'
						AND `customer_group_id` = '$customer_group_id'
						AND `quantity` <= " . $product['minimum'] . "
						AND ( `date_start` <= NOW() OR `date_start` = '0000-00-00' )
						AND ( `date_end` > NOW() OR `date_end` = '0000-00-00' )
					ORDER BY `priority` DESC LIMIT 1"
				);

				if( $discount_query && $discount_query->num_rows > 0 ) {
					$product['discount'] = $discount_query->row['price'];
				}

				// Recurring query
				$recurring_query = $this->db->query(
					"SELECT `recurring_id` FROM `" . DB_PREFIX . "product_recurring`
					WHERE `product_id` = '$product_id'
						AND `customer_group_id` = '$customer_group_id' LIMIT 1"
				);

				if( $recurring_query && $recurring_query->num_rows > 0 ) {
					$product['recurring'] = $recurring_query->rows;
				}

				// Options query
				$options = array();
				$option_query = $this->db->query(
					"SELECT
						`po`.`option_id`,
						`po`.`value`,
						`po`.`required`,
						`pod`.`name`
					FROM `" . DB_PREFIX . "product_option` `po`
						LEFT JOIN `" . DB_PREFIX . "option_description` `pod`
							USING(`option_id`)
					WHERE `po`.`product_id` = '$product_id'
						AND `pod`.`language_id` = " . (int)$this->config->get( 'config_language_id' )
				);

				if( $option_query && $option_query->num_rows > 0 ) {
					$o = array();

					foreach( $option_query->rows as $option ) {

						$options[ $option['option_id'] ] = array(
							'value'    => ! empty( $option['value'] ) ? $option['value'] : array(),
							'required' => $option['required'],
							'name'     => $option['name'],
						);

						if( empty( $option['value'] ) ) {
							$o[] = $option['option_id'];
						}

						if( $o ) {
							$option_value_query = $this->db->query(
								"SELECT pov.*, `povd`.`name`
								FROM `" . DB_PREFIX . "product_option_value` `pov`
									LEFT JOIN `" . DB_PREFIX . "option_value_description` `povd`
										USING(`option_value_id`)
								WHERE `pov`.`option_id` IN (" . implode( ',', $o ) . ")
									AND `povd`.`language_id` = " . (int)$this->config->get( 'config_language_id' )
							);

							if( $option_value_query && $option_value_query->num_rows > 0 ) {
								foreach( $option_value_query->rows as $value ) {
									$options[ $value['option_id'] ] = $value;
								}
							}
						}
					}
				}

				$product['options'] = $options;

				// Download query
				$download = array();
				$download_query = $this->db->query(
					"SELECT * FROM `" . DB_PREFIX . "product_to_download` `p2d`
						LEFT JOIN `" . DB_PREFIX . "download` `d`
							USING(`download_id`)
						LEFT JOIN `" . DB_PREFIX . "download_description` `dd`
							USING(`download_id`)
					WHERE `p2d`.`product_id` = '$product_id'
						AND `dd`.`language_id` = " . (int)$this->config->get( 'confog_language_id' )
				);

				if( $download_query && $download_query->num_rows > 0 ) {
					$download = $download_query->rows;
				}

				$product['download'] = $download;

				// Reward query
				$reward = 0;
				$reward_query = $this->db->query(
					"SELECT * FROM `" . DB_PREFIX . "product_reward`
					WHERE `product_id` = '$product_id'
						AND `customer_group_id` = '$customer_group_id'"
				);

				if( $reward_query && $reward_query->num_rows > 0 ) {
					$reward = $reward_query->row['points'];
				}

				$product['reward'] = $reward;
			}

			$this->add_to_cache( 'product/' . $product_id, $product );

			return $product;
		}

		return $this->get_from_cache( 'product/' . $product_id );
	}

	public function get_product_options( $product_option_value_ids ) {
		$l_id  = 1;
		$l_code = isset( $this->session->data['language'] ) ? $this->session->data['language'] : '';

		foreach( $this->get_languages() as $lang ) {
			if ( $lang['code'] === $l_code ) {
				$l_id = $lang['language_id'];
				break;
			}
		}
		$q = $this->db->query(
			"SELECT pov.option_id, pov.product_option_value_id, od.name as option_name, ovd.name as option_value_name
			FROM " . DB_PREFIX . "product_option_value pov
			JOIN " . DB_PREFIX . "option_value_description ovd
				ON(pov.option_value_id = ovd.option_value_id)
			JOIN " . DB_PREFIX . "option_description od
				ON(pov.option_id = od.option_id)
			WHERE
				ovd.language_id = $l_id AND
				od.language_id = $l_id AND
				pov.product_option_value_id IN (" .  implode( ', ', array_map( function( $a ) { return (int)$a; }, $product_option_value_ids ) ) . ")"
		);

		return $q->rows;
	}

	/**
	 * Returns order model
	 * @return object
	 */
	public function get_order_model( $side = null ) {
		global $adk_registry;
		$model = null;

		if ( 'catalog' === $side ) {
			if ( $this->is_admin() ) {
				require_once dirname( DIR_SYSTEM ) . '/catalog/model/checkout/order.php';
				$model = new \ModelCheckoutOrder( $adk_registry );

			} else {
				$model = $this->load->model( 'checkout/order' );
			}

		} else {
			if ( $this->is_admin() ) {
				$model = $this->load->model( 'sale/order' );

			} else {
				$model = $this->load->model( 'checkout/order' );
			}
		}

		return $model;
	}

	/**
	 * Returns recurring order info model
	 * @return object
	 */
	public function get_recurring_info_model() {
		$model = null;

		if ( defined( 'DIR_CATALOG' ) ) {
			$model = $this->load->model( 'sale/recurring' );

		} else {
			$model = $this->load->model( 'account/recurring' );
		}

		return $model;
	}

    /**
     * Returns system shipping methods
     * @param bool $active
     * @return array
     */
	public function get_shipping_methods( $active = true ) {
		// if ( !$this->is_admin() ) {
		// 	return [];
		// }

		$ext = [];

		if ( $this->is_admin() ) {
			$model_name = version_compare( VERSION, '3', '>=' ) ? 'setting/extension' : 'extension/extension';

			/** @var \ModelExtensionExtension|\ModelSettingExtension $extension */
			$extension = $this->load->model( $model_name );
			$extensions = $extension->getInstalled('shipping');

			if( version_compare( VERSION, '2.3.0.0', '>=' ) ) {
				$route = 'extension/shipping';

			} else {
				$route = 'shipping';
			}
				
			$files = glob( DIR_APPLICATION . 'controller/' . $route . '/*.php' );

			if ($files) {
				foreach ($files as $file) {
					$extension = basename($file, '.php');

					$status_name = ( version_compare( VERSION, '3', '>=' ) ? 'shipping_' : '' ) . $extension . '_status';

					if( $active && ( ! $this->config->get( $status_name ) ||
						! in_array( $extension, $extensions ) ) ) {
						continue;
					}

					$this->load->language( $route . '/' . $extension );

					$ext[] = array(
						'name'       => $this->language->get('heading_title'),
						'status'     => $this->config->get($extension . '_status'),
						'sort_order' => $this->config->get($extension . '_sort_order'),
						'installed'  => in_array($extension, $extensions),
						'code'       => $extension,
					);
				}
			}
		}

		return $ext;
	}

    /**
     * Returns localized caption
     * @param string $key Caption key
     * @param string $lang_code Language code
     * @param string $default Optional default value
     * @param array $conf
     * @return string
     * @throws Exception
     */
	public function get_lang_caption( $key, $lang_code = null, $default = '', $conf = [] ) {
		$ret = null;

		if ( '' === $key ) {
			return '';
		}

		// Try POST if there is no custom source
		if ( !$conf )$conf = $this->p( $key );

		// Try configuration
		if ( !$conf )$conf = $this->config( $key );

		if ( is_null( $lang_code ) ) {
			$lang_code = $this->language()->get_code();
		}

		if ( is_array( $conf ) ) {
			if ( isset( $conf[ $lang_code ] ) ) {
				$ret = $conf[ $lang_code ];

			} else {
				$def_lang_code = $this->config->get( 'config_admin_language' );

				if ( isset( $conf[ $def_lang_code ] ) ) {
					$ret = $conf[ $def_lang_code ];
				}
			}

		} else {
			$ret = $conf;
		}

		if ( is_null( $ret ) ) {
			$ret = $default;
		}

		return $ret;
	}

	/**
	 * Returns DB languages
	 * @return array
	 */
	public function get_languages() {
		if( ! $this->has_in_cache( 'languages' ) ) {
			$query = $this->db->query( "SELECT * FROM `" . DB_PREFIX . "language` where status = 1" );

			$this->add_to_cache( 'languages', $query->rows );
			return $query->rows;
		}

		return $this->get_from_cache( 'languages' );
	}

    /**
     * Returns customizable caption to show to user
     * @param string $name Caption code
     * @return string
     * @throws Exception
     */
	public function caption( $name ) {
		return nl2br( $this->get_lang_caption( $name ) );
	}

    /**
     * Check compatibility
     * @return string
     */
	public function check_compatibility() {
		$return = '';
		
		$class = 'Compatibility_Check';
		$namespace = explode( '\\', get_class( $this ) );
		array_pop( $namespace );
		
		while( count( $namespace ) > 0 ) {
			$class_name = implode( '\\', $namespace ) . '\\' . $class;
			
			if ( class_exists( $class_name ) ) {

			    /** @var Compatibility_Check $checker */
				$checker = new $class_name();
				$return = $checker->get_errors();
				break;
			}

			array_pop( $namespace );
		}

		return $return;
	}

	/**
	 * Returns current version
	 * @return string
	 */
	public function version() {
		if ( $this->version ) {
			return $this->version;
		}

		$this->console->profiler( 'Version' );

		$cont = file_get_contents( $this->_file, 1000 );
		preg_match( '/@version\s+([0-9.]+)/m', $cont, $m );

		$this->version = isset( $m[ 1 ] ) ? $m[ 1 ] : '';

		$this->console->profiler( 'Version' );

		return $this->version;
	}

	/**
	 * Returns current version - obsolete
	 * @return string
	 * @obsolete
	 */
	static public function get_version() {
		$cont = file_get_contents( static::$file, 1000 );
		preg_match( '/@version\s+([0-9.]+)/m', $cont, $m );

		return isset( $m[ 1 ] ) ? $m[ 1 ] : '';
	}

	/**
	 * Saves setting value
	 * @param string $name Setting name 
	 * @param mixed $value Setting value 
	 * @param int $store_id Store ID, optional
	 * @return boolean Operation status
	 */
	public function set_setting( $name, $value, $store_id = 0, $code = null ) {
		return Setting::set( $name, $value, $this );
	}

    /**
     * Initializes OC Mail object with store mail configuration
     * @param null $m
     * @param array $data Additional parameters
     * @return \Mail
     */
	public function init_mail( $m = null, $data = array() ) {
		if ( is_null( $m ) ) {
			$mail = $this->get_mail_object( $data );

		} else {
			$mail = $m;
		}

		$mail->setSender( $this->config->get( 'config_email' ) );
		$mail->setFrom( $this->config->get( 'config_email' ) );

		if ( version_compare( VERSION, '2.0.1.1', '<=' ) ) {
			foreach( $this->config->get('config_mail') as $k => $v ) {
				$mail->{$k} = $v;
			}

		} else {
			if( version_compare( VERSION, '3', '>=' ) ) {
				$mail->protocol = $this->config->get( 'config_mail_engine');

			} else {
				$mail->protocol = $this->config->get( 'config_mail_protocol' );
			}

			if( 'smtp' === $mail->protocol ) {
				if ( isset( $mail->adaptor ) ) {
					$adaptor = $mail->adaptor;
					$target = &$adaptor;

				} else {
					$target = &$mail;
				}

				$target->smtp_hostname = $this->config->get( 'config_mail_smtp_hostname' );
				$target->smtp_username = $this->config->get( 'config_mail_smtp_username' );
				$target->smtp_password = html_entity_decode( $this->config->get( 'config_mail_smtp_password' ), ENT_QUOTES, 'UTF-8' );

				if( $this->config->has( 'config_mail_smtp_port' ) ) {
					$target->smtp_port = $this->config->get( 'config_mail_smtp_port' );
				}

				if( $this->config->has( 'config_mail_smtp_timeout' ) ) {
					$target->smtp_timeout = (float)$this->config->get( 'config_mail_smtp_timeout' );
				}

			} else {
				$mail->parameter = $this->config->get( 'config_mail_parameter' );
			}
		}

		if( $data ) {
			foreach( $data as $k => $v ) {
				$method = 'set' . ucfirst( $k );
				if( method_exists( $mail, $method ) ) {
					call_user_func( array( $mail, $method ), $v );
				}
			}
		}

		return $mail;
	}

	public function get_mail_object( $data = [] ) {
		if ( version_compare( VERSION, '3', '>=' ) ) {
			return new \Mail( $this->config->get( 'config_mail_engine') );
		}

		return new \Mail( $data );
	}

	/**
	 * Tests string whether it looks like email address
	 * @param string $email 
	 * @return boolean
	 */
	public function is_email( $email ) {
		return preg_match( '/^[A-Za-z0-9._+-]+@[A-Za-z0-9._-]+\.[A-Za-z]{2,4}$/', $email );
	}

	/**
	 * Parses exception's stack
	 * @param object $e Exception
	 * @return string
	 */
//	public function trace_exception( $e ) {
//		$stack = array();
//
//		if ( is_subclass_of( $e, '\Exception' ) ) {
//			$stack[] = 'Trace of exception: ' . $e->getMessage();
//
//			foreach( $e->getTrace() as $n => $line ) {
//				$stack[] = sprintf(
//					'%n - %s %s %s',
//					$n,
//					isset( $line['function'] ) ? $line['function'] : '',
//					isset( $line['file'] ) ? $line['file'] : '',
//					isset( $line['line'] ) ? $line['line'] : ''
//				);
//			}
//		}
//
//		return implode( chr( 10 ), $stack );
//	}

	/**
	 * Returns default PHP charset
	 * @return string
	 */
//	public function get_default_charset() {
//		$ini =  ini_get( 'default_charset' );
//
//		return empty( $ini ) ? 'UTF-8' : $ini;
//	}

	/**
	 * Returns header/headers
	 * @param string|null $name Header name, optional
	 * @return string|array
	 */
//	public function get_header( $name = null ) {
//		if ( is_null( $name ) ) {
//			$ret = array();
//
//		} else {
//			$name = strtolower( $name );
//			$ret = null;
//		}
//
//		foreach( $_SERVER as $n => $v ) {
//			if ( strpos( $n, 'HTTP_' ) !== 0 ) continue;
//			$n = str_replace( '_', '-', strtolower( substr( $n, 5 ) ) );
//
//			if ( is_null( $name ) ) {
//				$ret[ $n ] = $v;
//
//			} else {
//				if ( $name === $n ) {
//					$ret = $v;
//					break;
//				}
//			}
//		}
//
//		return $ret;
//	}

	/**
	 * Execute function and catch any sort of errors
	 * @param callable $func 
	 * @param string &$error 
	 * @return string
	 */
	public function do_clean( $func, &$error = '' ) {
		$ret = null;
		$error = '';

		if ( ! is_callable( $func ) ) {
			return null;
		}

		ob_start();

		try {
			$ret = $func();

		} catch ( \Exception $e ) {
			$error = $e->getMessage();
		}

		$error .= ob_get_clean();

		return $ret;
	}

	/**
	 * Checks if we are in ADMIN area
	 * @return boolean
	 */
	public function is_admin() {
		return defined( 'DIR_CATALOG' ) && strpos( DIR_TEMPLATE, DIR_APPLICATION ) === 0;
	}

	/**
	 * Returns current DB version
	 * @return string
	 */
	public function get_db_version() {
		$versions = $this->config( 'db_version', [] );

		// Fix if version is not an array
		if ( gettype( $versions ) !== 'array' ) {
			$this->log( 'DB version is a string. Clean it up' );

			$versions = [];
			$this->set_setting( 'db_version', $versions );
		}

		return $versions ? array_pop( $versions ) : '';
	}

	/**
	 * Returns previous DB version
	 * @return string
	 */
	public function get_previous_db_version() {
		$versions = $this->config( 'db_version', [] );

		return count( $versions ) > 1 ? $versions[ count( $versions ) - 2 ] : '';
	}

	/**
	 * Checks if DB version can be updated to new version
	 * @param string $version 
	 * @return boolean
	 */
	public function can_update_db_version( $version ) {

		// Invalid version number
		if ( version_compare( '0', $version, '>' ) ) {
			return false;
		}

		$versions = $this->config( 'db_version', [] );

		if ( $versions ) {
			$current = $versions[ count( $versions ) - 1 ];

			// Version is older then current one
			if ( version_compare( $current, $version, '>=' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Updates DB version number
	 * @param string $version 
	 * @return boolean
	 */
	public function set_db_version( $version ) {
		if ( !$this->can_update_db_version( $version ) ) {
			$this->error( sprintf( 'Failed to update DB version to %s', $version ) );

			return false;
		}

		$versions = $this->config( 'db_version', [] );

		$this->log( 'Current versions stack', $versions );
		$this->log( sprintf( 'Pushing version %s in the stack', $version ) );

		array_push( $versions, $version );
		$this->set_setting( 'db_version', $versions );

		return true;
	}

	/**
	 * Unsets current DB version number
	 * @return void
	 */
	public function unset_db_version() {
		$versions = $this->config( 'db_version' );

		$this->log( 'Current versions stack', $versions );

		if ( $versions && is_array( $versions ) ) {
			$this->log( 'Popping up DB version' );

			@array_pop( $versions );
			$this->set_setting( 'db_version', $versions );
		}
	}

	/**
	 * Generates simple pseudo-random code
	 * @param int $length Code length 
	 * @return string
	 */
	public function code( $length = 5 ) {
		$out = [];
		$input_length = strlen( self::CHAR_ALPHANUM ) - 1;

		for ( $i = 0; $i < $length; $i++ ) {
			$sec = self::CHAR_ALPHANUM;
			$out[] = $sec[ rand( 0, $input_length ) ];
		}

		return implode( '', $out );
	}

	/**
	 * Checks if two associated arrays are equal 
	 * @param array $a1 
	 * @param array $a2
	 * @param boolean Strict mode
	 * @return boolean
	 */
	public function array_assoc_is_equal( $a1, $a2, $strict = false ) {
		if ( !is_array( $a1 ) || !is_array( $a2 ) ) return false;

		foreach( $a1 as $k => $v ) {
			if ( !array_key_exists( $k, $a2 ) ) {
				$this->log( sprintf( 'Array comparison. Second array has no member "%s". Comparison failed', $k ) );
				return false;
			}

			if ( is_array( $v ) ) {
				$result = $this->array_assoc_is_equal( $v, $a2[ $k ] );

			} else {
				$result = $v == $a2[ $k ];
			}
			
			if ( !$result ) {
				$this->log( sprintf( 'Array comparison. Second arrays have different values for keys "%s". Comparison failed', $k ) );
				return false;
			}
		}

		return $strict ? count( $a1 ) === count( $a2 ) : true;
	}

    /**
     * Returns country flag image URL by country code
     * @param string $code Country code ISO-2
     * @return string URL
     * @throws Exception
     */
	public function get_country_flag_by_code( $code ) {
		$ret = '';
		$file = 'catalog/advertikon/flags/' . strtolower( $code ) . '.png';

		if ( file_exists( DIR_IMAGE . $file ) ) {
			$ret = $this->u()->catalog_url( true ) . 'image/' . $file;

		} else {
			$ret = $this->u()->catalog_url( true ) . 'image/catalog/advertikon/flags/undefined.png';
		}

		return $ret;
	}

    /**
     * Returns weight class details
     * @param int $weight_class_id
     * @return object Db_Result
     * @throws Exception
     */
	public function get_weight( $weight_class_id ) {
		if ( $this->has_in_cache( 'weight/' . $weight_class_id ) ) {
			return $this->get_from_cache( 'weight/' . $weight_class_id );
		}

		$q = $this->q( [
			'table' => 'weight_class',
			'query' => 'select',
			'join'  => [
				'type'  => 'left',
				'table' => [ 'wd' => 'weight_class_description' ],
				'using' => 'weight_class_id',
			],
			'where' => [
				'field'     => 'weight_class_id',
				'operation' => '=',
				'value'     => (int)$weight_class_id,
			],
		] );

		$this->add_to_cache( 'weight/' . $weight_class_id, $q );

		return $q;
	}

    /**
     * Returns length class details
     * @param $length_class_id
     * @return object Db_Result
     * @throws Exception
     */
	public function get_length( $length_class_id ) {
		if ( $this->has_in_cache( 'length/' . $length_class_id ) ) {
			return $this->get_from_cache( 'length/' . $length_class_id );
		}

		$q = $this->q( [
			'table' => 'length_class',
			'query' => 'select',
			'join'  => [
				'table' => [ 'ld' => 'length_class_description', ],
				'using' => 'length_class_id',
			],
			'where' => [
				'field'     => 'length_class_id',
				'operation' => '=',
				'value'     => (int)$length_class_id,
			],
		] );

		$this->add_to_cache( 'length/' . $length_class_id, $q );

		return $q;
	}

	/**
	 * Returns dimension components
	 * @param string $string 
	 * @return array
	 */
	public function parse_dimension( $string ) {
		$ret = [ 'value' => 0, 'units' => 'px' ];

		preg_match( '/(\d+)(px|%)/', $string, $m );
		
		if ( isset( $m[ 1 ] ) ) {
			$ret['value'] = $m[ 1 ];
		}

		if ( isset( $m[ 2 ] ) ) {
			$ret['units'] = $m[ 2 ];
		}

		return $ret;
	}

	public function dim( $val, $d_unit = 'px' ) {
		$ret = [ 'value' => 0, 'units' => $d_unit ];

		if ( isset( $val['value'] ) ) {
			$ret['value'] = $val['value'];
		}

		if ( isset( $val['units'] ) ) {
			$ret['units'] = $val['units'];
		}

		return $ret;
	}

	public function dim_str( $val, $d_unit = 'px' ) {
		$dim = $this->dim( $val, $d_unit );
		return $dim['value'] . $dim['units'];
	}

    /**
     * CURL wrapper
     * @param string $url URL
     * @param array $data POST data. Optional
     * @param array $config Configuration:
     *                        - verbose - default 0;
     *                        - return_transfer - default 1;
     *                        - timeout - default 5
     * @return bool|string {boolean|string} False on failure. True or response depending on 'return_transfer' config value False on failure. True or response depending on 'return_transfer' config value
     * @throws Exception
     */
	public function curl( $url, array $data = null, array $config = [] ) {
		$this->log( sprintf( 'Connecting to "%s"', $url ) );
		
		$verbose         = isset( $config['verbose'] ) ? (int)$config['verbose'] : 0;
		$return_transfer = isset( $config['return_transfer'] ) ? (int)$config['return_transfer'] : 1;
		$is_post         = is_array( $data );
		$timeout         = isset( $config['timeout'] ) ? (int)$config['timeout'] : 5;
		$follow_redirect = isset( $config['follow_redirect'] ) ? $config['follow_redirect'] : 1;

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, $return_transfer );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, $follow_redirect );
		
		if ( $is_post ) {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		}

		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_VERBOSE, $verbose );
		
		if ( $verbose ) {
			$fd = fopen( 'php://temp', 'r+' );
			curl_setopt( $ch, CURLOPT_STDERR, $fd );
		}

		$res = @curl_exec( $ch );
		$this->log( 'Request ended' );

		if( curl_errno( $ch ) ) {
			$this->error( sprintf( 'CURL error: %s', curl_error( $ch ) ) );
			
			if ( $verbose && is_resource( $fd ) ) {
				rewind( $fd );
				$info = fread( $fd, 10240 );
				fclose( $fd );
				$this->log( $info );
			}

			if ( is_resource( $ch ) ) {
				curl_close( $ch );
			}

			throw new Exception( curl_error( $ch ) );
		}

		$code = curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );

		if ( 200 != $code ) {
			$this->error( 'Curl response code: ' . $code );
			curl_close( $ch );
			throw new Exception( 'Network error' );
		}

		if ( is_resource( $ch ) ) {
			curl_close( $ch );
		}

		return $res;
	}

	public static function load_vendors() {
		require_once( __DIR__ . '/vendor/autoload.php' );
	}
	
	public function clear_tmp() {
		if ( rand( 0, 10 ) < 8 ) {
			return;
		}

		if ( $this->tmp_dir && is_dir( $this->tmp_dir ) ) {
			$exp_time = time() - self::TMP_TIME;

			Profiler::record( 'Clear TMP' );
			$i = new \RecursiveDirectoryIterator(
				$this->tmp_dir,
				\FilesystemIterator::KEY_AS_PATHNAME |
				\FilesystemIterator::CURRENT_AS_FILEINFO |
				\FilesystemIterator::SKIP_DOTS
			);

			foreach ( new \RecursiveIteratorIterator( $i, \RecursiveIteratorIterator::CHILD_FIRST ) as $path => $info ) {
				if ( $info->getMTime() < $exp_time ) {
					if ( $info->isDir() ) {
						if ( !$info->hasChildren() ) {
							@rmdir( $path );
						}

					} else {
						@unlink( $path );
					}
				}
			}
			Profiler::record( 'Clear TMP' );
		}
	}
	
	static protected function load_translator_assets( \Registry $r ) {
		$enabled = false;
		$q = $r->get( 'db' )->query( 'select value from ' . DB_PREFIX . 'setting where `key` like "adk_%inline_translate"' );

		if ( $q && isset( $q->rows ) ) {
			foreach( $q->rows as $row ) {
				if ( $row['value'] == 1 ) {
					$enabled = true;
					break;
				}
			}
		}

		if ( $enabled ) {
			$dir =  defined( 'HTTPS_CATALOG' ) ? HTTPS_CATALOG : HTTPS_SERVER;
			$r->get( 'document' )->addScript( $dir . 'catalog/view/javascript/advertikon/advertikon.js' );
			$r->get( 'document' )->addStyle( $dir . 'catalog/view/theme/default/stylesheet/advertikon/advertikon.css' );
		}
	}

        /**
         * @param $event
         * @param array $action
         * @throws Exception
         */
        public function add_listener($event, array $action ) {
		if ( !isset( $action['action'] ) ) {
			throw new Exception( 'Action required' );
		}

		if ( !is_callable( $action['action'] ) ) {
			throw new Exception( 'Action needs to be callable' );
		}

		if ( !isset( $this->actions[ $event ] ) ) {
			$this->actions[ $event ] = [];
		}

		$this->actions[ $event ][] = $action;
	}

	public function trigger( $event, &$data = null ) {
		if ( !isset( $this->actions[ $event ] ) ) {
			return;
		}

		foreach( $this->actions[ $event ] as $e ) {
			$e['action']( $data );
		}
	}

	private static $events = [];

	public static function event( $event, array $action ) {
		if ( !isset( self::$events[ $event ] ) ) {
			self::$events[ $event ] = [];
		}

		self::$events[ $event ][] = $action;
	}

	public static function fire( $event, &$data = null ) {
		if ( isset( self::$events[ $event ] ) ) {
			foreach( self::$events[ $event ] as $e ) {
				if ( isset( $e['action'] ) ) {
					$e['action']( $data );
				}
			}
		}
	}

        /**
         * @param $locale
         * @throws Exception
         */
        protected function populate_locale(&$locale ) {
		$locale = array_merge( $locale, [
			'networkError'              => $this->__( 'Network error' ),
			'parseError'                => $this->__( 'Unable to parse server response string' ),
			'undefServerResp'           => $this->__( 'Undefined server response' ),
			'serverError'               => $this->__( 'Server error' ),
			'sessionExpired'            => $this->__( 'Current session has expired' ),
			'modalHeader'               => 'Module',
			'yes'                       => $this->__( 'Yes' ),
			'no'                        => $this->__( 'No' ),
			'clipboard'                 => '',
			'catalogUrl'                => $this->u()->catalog_url( true ),
			'moduleFullName'            => $this->full_name,
			'imageBase'                 => '',
		] );
	}

	public function init_traits( $class ) {
		foreach( class_uses( $class ) as $interface ) {
			$parts = explode( '\\', $interface );
			$method = strtolower( array_pop( $parts ) ) . '_init';

			if ( method_exists( $class, $method ) ) {
				$class->$method();
			}
		}
	}
}
} //<-- Advertikon namespace end

namespace {
    /**
     * @param null $code
     * @return \Advertikon\Advertikon
     * @throws \Advertikon\Exception
     */
    function ADK( $code = null ) {
		return Advertikon\Advertikon::instance( $code );
	}
}
