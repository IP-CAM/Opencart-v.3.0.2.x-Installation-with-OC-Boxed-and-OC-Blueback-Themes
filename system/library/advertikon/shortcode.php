<?php
/**
 * Advertikon Shortcode Class
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
  
 */

namespace Advertikon;

use Advertikon\Advertikon;

class Shortcode {

	public $shortcode_set = [];
	public $shortcodes_stack = array();
	protected $a;

	public function __construct( Advertikon $a ) {
		$this->a = $a;
		$this->get_dropins();

		$this->shortcode_set = array_merge( array(
			'store_name' => array(
				'callback'    => array( $this, 'shortcode_store_name' ),
				'hint'        => 'store_name',
				'description' => $this->a->__( 'Shows name of current store' ),
				'context'     => array( $this->a->__( 'Customer' ), $this->a->__( 'Dashboard'), $this->a->__( 'Affiliate' ) ),
			),
			'store_url' => array(
				'callback'    => array( $this, 'shortcode_store_url' ),
				'hint'        => 'store_url(Text)',
				'description' => $this->a->__( 'Shows link to the current store' ),
				'context'     => array( $this->a->__( 'Customer' ), $this->a->__( 'Dashboard'), $this->a->__( 'Affiliate' ) ),
			),
			'ip' => array(
				'callback'    => array( $this, 'shortcode_ip' ),
				'hint'        => 'ip',
				'description' => $this->a->__( 'Shows IP for current session' ),
				'context'     => array( $this->a->__( 'Customer' ), $this->a->__( 'Dashboard'), $this->a->__( 'Affiliate' ) ),
			),
			'customer_first_name' => array(
				'callback'    => array( $this, 'shortcode_customer_first_name' ),
				'hint'        => 'customer_first_name',
				'description' => $this->a->__( 'Shows first name for current customer' ),
				'context'     => array( $this->a->__( 'Customer' ) ),
			),
			'customer_email' => array(
				'callback'    => array( $this, 'shortcode_customer_email' ),
				'hint'        => 'customer_email',
				'description' => $this->a->__( 'Shows customer\'s email' ),
				'context'     => array( $this->a->__( 'Customer' ) ),
			),
			'customer_last_name' => array(
				'callback'    => array( $this, 'shortcode_customer_last_name' ),
				'hint'        => 'customer_last_name',
				'description' => $this->a->__( 'Shows last name for current customer' ),
				'context'     => array( $this->a->__( 'Customer' ) ),
			),
			'customer_full_name' => array(
				'callback'    => array( $this, 'shortcode_customer_full_name' ),
				'hint'        => 'customer_full_name',
				'description' => $this->a->__( 'Shows full name for current customer' ),
				'context'     => array( $this->a->__( 'Customer' ) ),
			),
			'account_login_url' => array(
				'callback'    => array( $this, 'shortcode_account_login_url' ),
				'hint'        => 'account_login_url(Text)',
				'description' => $this->a->__( 'Creates link to a customers\'s account login page' ),
				'context'     => array( $this->a->__( 'Customer' ), $this->a->__( 'Dashboard'), $this->a->__( 'Affiliate' ) ),
			),
			'order_id' => array(
				'callback'    => array( $this, 'shortcode_order_id' ), 
				'hint'        => 'order_id',
				'description' => $this->a->__( 'Shows order ID' ),
				'context'     => array( $this->a->__( 'Dashboard - New order' ), $this->a->__( 'Customer - New order' ), $this->a->__( 'Customer - Order update' ) )
			),
			'url' => array(
				'callback'    => array( $this, 'shortcode_url' ),
				'hint'        => 'url(URL,Text)',
				'description' => $this->a->__( 'Arbitrary URL' ),
				'context'     => array( $this->a->__( 'Customer' ), $this->a->__( 'Dashboard'), $this->a->__( 'Affiliate' ) ),
			),
		), $this->shortcode_set );
	}

	/**
	 * Get shortcodes for third parties
	 * @param type &$array 
	 * @return void
	 */
	private function get_dropins() {
		if ( is_dir( __DIR__ . '/shortcode' ) ) {
			foreach( glob( __DIR__ . '/shortcode/*' ) as $file ) { 
				if ( $this->a->config->get( basename( $file ) . '_status' ) ) {
					$s = include( $file );
					$this->shortcode_set = array_merge( $this->shortcode_set, $s );
				}
			}
		}
	}

	/**
	 * Recursively evaluates short-codes
	 * @since 1.1.0 - rebuild
	 * @param string $text 
	 * @return string
	 */
	public function do_shortcode( $text ) {
		$ret = $text;
		
		$ret = $this->conditional_print( $ret );
		$ret = $this->evaluate_shortcode( $ret, $count );
		$ret = $this->fix_content( $ret );

		return $ret;
	}

	/**
	 * Preforms short-code evaluation over text
	 * @since 1.1.0
	 * @param string $text Text with short-codes 
	 * @param int $count Number of evaluations
	 * @return string
	 */
	public function evaluate_shortcode( $text, &$counts = 0 ) {
		$self = $this;

		// Search for {short-code(param)} and pass into callback function
		$ret = preg_replace_callback( '/\{ (\w+) (?:\( ( [^\)]* ) \) )?}/x', function( $matches ) use( $self, &$counts ) {
			$replace = $matches[0];

			// Ignore conditional tags
			if( strpos( $matches[0], '{if_' ) === 0 || strpos( $matches[0], '{/if_' ) ) {
				return $replace;
			} 

			// $matches[1] - short-code name, $matches[2] - arguments 
			$recursion = in_array( $matches[0], $self->shortcodes_stack );

			if( $recursion ) {
				$this->a->error( sprintf( 'Recursion detected for short-code "%s"', $matches[0] ) );
			}

			// Short-code exists
			if( !$recursion ) {

				// Short-code has callback
				if ( method_exists( $self, 'shortcode_' . $matches[ 1 ] ) ) {

					// Short-code name is the first argument
					$args = array( $matches[1] );

					// Define arguments
					if( isset( $matches[2] ) ) {
						$f_args = explode( ',', $matches[2] );

						foreach( $f_args as $arg ) {
							$args[] = trim( $arg );
						}
					}

					$this->a->console->profiler( 'shortcode {' . $matches[ 1 ] . '}' );
					$replace = call_user_func_array( [ $self, 'shortcode_' . $matches[ 1 ] ], $args );
					$this->a->console->profiler( 'shortcode {' . $matches[ 1 ] . '}' );

					// Active short-codes stack
					array_push( $self->shortcodes_stack, $matches[0] );

					// Enter recursion
					$replace = $self->do_shortcode( $replace );

					array_pop( $self->shortcodes_stack );

				} else {
					$this->a->error( sprintf( 'Callback is missing for shortcode "%s"', $matches[0] ) );
				}
			}

			return $replace;
		}, $text );

		return $ret;
	}

	/**
	 * Fixes email contents issues
	 * @param string $text Text to be fixed 
	 * @return string
	 */
	public function fix_content( $text ) {
		$search = array(
			'http://http://', // 1 double protocol parts, added by summer-note
			'https://https://', // 1 double protocol parts, added by summer-note
		);

		$replace = array(
			'http://', // 1
			'https://', // 1
		);

		// Anchors with empty href attributes
		$text = preg_replace( '#<a\s+[^>]*?href=(\'|\")(?:[^/]*//\s*|\s*)\1[^>]*?>[^<]*?</a>#', '', $text );
		$text1 = rawurldecode( $text );

		// Folded HREF attributes
		$text2 = preg_replace(
			'~href=   # outer HREF
			(["\'])   # opening quotation mark of the outer HREF - #1
			\s*       # maybe whitespace
			(?:https?:/{2})?
			<a[^>]+?  # folded HTML anchor
			href=     # inner HREF
			(["\'])   # opening quotation mark of the inner HREF - #2
			([^\s]+?) # content of the inner HREF                - #3
			\2        # closing quotation mark on the inner HREF
			.+?       # everything up to the closing tag
			</a>      # closing tag on the folded HTML anchor
			\s*       # maybe whitespace
			\1        # closing quotation mark of outer HREF
			~x', 'href=' . rawurlencode( '$1$3$1' ), $text1 );

		if ( strlen( $text1 ) !== strlen( $text2 ) ) {
			$text = $text2;
		}

		$text = str_replace( $search, $replace, $text );

		return $text;
	}

	/**
	 * Parses string with conditional tags and hides contents of tags which evaluate to false
	 * @param string $str Target string 
	 * @return string
	 */
	public function conditional_print( $str ) {
		$offset = 0;
		$debug = false;

		if( $debug ) {
			$str = '{if_no_products_sku_all(32,2,1,3)}text{/if_no_products_sku_all}';
		}

		if( !$this->parse_conditional_string( $str ) ) {
			return $str;
		}

		while( ( $close_tag_start = strpos( $str, '{/if_', $offset ) ) !== false ) {

			$close_tag_end = strpos( $str, '}', $close_tag_start );
			$offset = $close_tag_start;
			$tag = substr( $str, $close_tag_start + 2, $close_tag_end - $close_tag_start - 2 );

			if ( $debug ) {
				console_log( 'Tag: ' . $tag );
				console_log( 'Before:' );
				console_log( 'Offset: ' . $offset );
				console_log( $str );
				console_log( str_repeat( '_', $offset ) . '|' );
			}

			// Short-code has callback
			if( !method_exists( $this, 'shortcode_' . $tag ) ) {
				$this->a->error( sprintf( 'Conditional shortcode %s has no callback function', $tag ) );
				$offset++;

				continue;
			}

			if ( $debug ) {
				console_log( 'Search opening tag in: ' . substr( $str, 0, $close_tag_start ) );
				console_log( 'Searching for: ' . '{' . $tag );
			}

			$open_tag_start = strrpos( substr( $str, 0, $close_tag_start ), '{' . $tag );
			$open_tag_end = strpos( $str, '}', $open_tag_start );

			if ( $debug ) {
				console_log( 'Opening tag start position: ' . $open_tag_start );
				console_log( $str );
				console_log( str_repeat( '_', $open_tag_start ) . '|' );
				console_log( 'Opening tag end position: ' . $open_tag_end );
				console_log( $str );
				console_log( str_repeat( '_', $open_tag_end ) . '|' );
				$open_tag = substr( $str, $open_tag_start + 1, $open_tag_end - $open_tag_start - 1 );
				console_log( 'Opening tag: ' . $open_tag );
			}

			$args_start = $open_tag_start + strlen( '{' . $tag );
			$maybe_args_start = substr( $str, $args_start, 1 );
			$str_args = '';

			if( '(' === $maybe_args_start ) {
				$str_args = substr( $str, $args_start + 1, strpos( $str, ')', $args_start ) - $args_start - 1 );

				if ( $debug ) {
					console_log( 'Arguments: ' . $str_args );
				}
			}

			$tag_args = array();

			if ( $str_args ) {
				foreach( explode( ',', $str_args ) as $arg ) {
					$tag_args[] = trim( $arg );
				}
			}

			// Short-code name is the first argument
			$args = array_merge( array( $tag ), $tag_args );

			$result = (boolean)call_user_func_array( [ $this, 'shortcode_' . $tag ], $args );

			// Conditional tag evaluated to true
			if( $result ) {
				$str = $this->a->str_slice( $str, $close_tag_start, $close_tag_end );
				$str = $this->a->str_slice( $str, $open_tag_start, $open_tag_end );
				$offset -= $open_tag_end - $open_tag_start + 1;

				if ( $debug ) {
					console_log( 'Offset subtraction: ' . ( $open_tag_end - $open_tag_start + 1 ) );
				}

			// Conditional tag evaluated to false
			} else {
				$str = $this->a->str_slice( $str, $open_tag_start, $close_tag_end );
				$offset -= $close_tag_start - $open_tag_start;

				if ( $debug ) {
					console_log( 'Offset subtraction: ' . ( $close_tag_start - $open_tag_start ) );
				}
			}

			if ( $debug ) {
				console_log( 'After:' );
				console_log( 'Offset: ' . $offset );
				console_log( $str );
				console_log( str_repeat( '_', $offset ) . '|' );
			}
		}

		return $str;
	}

	/**
	 * Checks string with conditional tags for correctness or presence of conditional tags
	 * @param string $str Target string 
	 * @return boolean
	 */
	public function parse_conditional_string( $str ) {
		$stack = array();
		$count = 0;
		$r = '@
			 \{                # opening curly brace
			 ( /?if_[^}(]+ )   # opening or closing tag
			 ( \( [^)]* \) )?  # tag\'s arguments. Optional
			 \}                # closing curly brace
			 @x';

		preg_replace_callback( $r, function( $m ) use( &$stack, &$count ) {

			// Error previously had occurred 
			if ( false === $count ) {
				return;
			}

			$tag = $m[1];

			// Closing tag
			if ( '/if_' === substr( $tag, 0, 4 ) ) {
				$open = false;

			} elseif ( 'if_' === substr( $tag, 0, 3 ) ) {
				$open = true;

			// Non-conditional tag
			} else {
				return;
			}

			if ( $open ) {
				array_push( $stack, $tag );

			} else {
				$expect_tag = array_pop( $stack );

				if ( $tag !== '/' . $expect_tag ) {
					if( $expect_tag ) {
						$this->a->error( sprintf( 'Opening tag "%s" is not matching to closing one "%s"', $expect_tag, $tag ) );

					} else {
						$this->a->error( sprintf( 'Closing tag "%s" has no counterpart tag', $tag ) );
					}

					$count = false;

					return;
				}

				$count++;
			}

		}, $str );

		// Error has not been triggered yet and stack is not empty - opening-closing tags mismatch
		if ( false !== $count && $stack ) {
			$this->a->error( sprintf( 'Conditional tag "%s" is not closed', current( $stack ) ) );
		}

		return $count > 0 && ! $stack;
	}

	/**
	 * Brace short-code name into braces
	 * @param string $name Short-code name 
	 * @return string
	 */
	public function brace_shortcode_name( $name ) {
		return '{' . $name . '}';
	}

	/**
	 * Returns short-code/set by its name
	 * @param string|null $shortcode_name Short-code name, if omitted - all lest will be returned 
	 * @return array
	 */
	public function get_shortcode_data( $shortcode_name = null ) {
		$ret = null;
		$shortcode_name = $this->shortcode_name( $shortcode_name );

		if( is_null( $shortcode_name ) ) {
			$ret = $this->shortcode_set;
		}

		if( isset( $this->shortcode_set[ $shortcode_name ] ) ) {
			$ret =  $this->shortcode_set[ $shortcode_name ];
		}

		return $ret;
	}

	/**
	 * MAkes it possible to rewrite shortcode name in children class
	 * @param string $name Short-code's name
	 * @return string
	 */
	public function shortcode_name( $name ) {
		return $name;
	}

	/**
	 * Returns current store name
	 * @return string
	 */
	public function shortcode_store_name() {
		return $this->a->config->get( 'config_name' );
	}

	/**
	 * Returns client IP address
	 * @return string
	 */
	public function shortcode_ip() {
		$ip = '';

		if ( isset( $_SERVER ) ) {

			if( isset( $_SERVER["HTTP_X_FORWARDED_FOR"] ) ) {
				$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];

				if( strpos( $ip, "," ) ){
					$exp_ip = explode( ",", $ip );
					$ip = $exp_ip[0];
				}

			} else if( isset( $_SERVER["HTTP_CLIENT_IP"] ) ) {
				$ip = $_SERVER["HTTP_CLIENT_IP"];

			} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = $_SERVER["REMOTE_ADDR"];

			} else {
				$ip = '';
			}

		} else {
			if( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
				$ip = getenv( 'HTTP_X_FORWARDED_FOR' );

				if( strpos( $ip, "," ) ) {
					$exp_ip = explode( ",", $ip );
					$ip = $exp_ip[0];
				}

			} else if( getenv( 'HTTP_CLIENT_IP' ) ) {
				$ip = getenv( 'HTTP_CLIENT_IP' );

			} else {
				$ip = getenv( 'REMOTE_ADDR' );
			}
		}

		return $ip;
	}

	/**
	 * Returns customer's full name
	 * @return string
	 */
	public function shortcode_customer_full_name() {
		$customer = $this->a->get_customer();
		$ret = '';
		
		if( isset( $customer['firstname'] ) && isset( $customer['lastname'] ) ) {
			$ret = $customer['firstname'] . ' ' . $customer['lastname'];
		}

		if ( ! $ret && defined( 'PREVIEW' ) ) {
			$ret = 'John Smith';
		}

		return $ret;
	}

	/**
	 * Returns customer's first name
	 * @return string
	 */
	public function shortcode_customer_first_name() {
		$customer = $this->a->get_customer();
		$ret = '';
		
		if( isset( $customer['firstname'] ) ) {
			$ret = $customer['firstname'];
		}

		if ( ! $ret && defined( 'PREVIEW' ) ) {
			$ret = 'John';
		}

		return $ret;
	}

	/**
	 * Returns customers last name
	 * @return string
	 */
	public function shortcode_customer_last_name() {
		$customer = $this->a->get_customer();
		$ret = '';
		
		if( isset( $customer['lastname'] ) ) {
			$ret = $customer['lastname'];
		}

		if ( ! $ret && defined( 'PREVIEW' ) ) {
			$ret = 'Smith';
		}

		return $ret;
	}

	/**
	 * Returns customers email
	 * @return string
	 */
	public function shortcode_customer_email() {
		$customer = $this->a->get_customer();
		$ret = '';
		
		if( isset( $customer['email'] ) ) {
			$ret = $customer['email'];
		}

		if ( ! $ret && defined( 'PREVIEW' ) ) {
			$ret = 'smith@gmail.com';
		}

		return $ret;
	}

	/**
	 * Returns link to the log-in to customer account page 
	 * @return string
	 */
	public function shortcode_account_login_url() {
		$args = func_get_args();
		$text = empty( $args[1] ) ? $this->a->__( 'Sign in' ) : $args[1];

		$ret =  $this->a->get_store_href( true ) . 'index.php?route=account/login';

		return sprintf( '<a href="%s" target="_blank">%s</a>', $ret, $text );
	}

	/**
	 * Shows arbirary link
	 * @return string
	 */
	public function shortcode_url() {
		$args = func_get_args();
		$href = empty( $args[1] ) ? '/' : $args[1];
		$text = empty( $args[2] ) ? 'Click me' : $args[2];

		return sprintf( '<a href="%s" target="_blank">%s</a>', $href, $text );
	}

	/**
	 * Returns link to the store
	 * @return string
	 */
	public function shortcode_store_url() {
		$ret = '';

		$args = func_get_args();
		$text = empty( $args[1] ) ? $this->a->__( 'Store' ) : $args[1]; 
		$ret = $this->a->get_store_href( true );

		return sprintf( '<a href="%s" target="_blank">%s</a>', $ret, $text );
	}

	/**
	 * Returns order ID
	 * @return string
	 */
	public function shortcode_order_id() {
		$ret = '';

		if( isset( $this->a->session->data['order_id'] ) ) {
			$ret = $this->a->session->data['order_id'];

		}

		return $ret;
	}

	/**
	 * Returns list of supported shortcodes
	 * @return array
	 */
	public function list_of_supported() {
		$list = array();

		foreach( $this->shortcode_set as $s ) {
			$list[] = $s['hint'];
		}

		return $list;
	}
}
