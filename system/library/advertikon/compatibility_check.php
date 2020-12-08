<?php

/**
 * @package Mail template manager
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

/**
 * Compatibility checker
 *
 * @author Advertikon
 */
class Compatibility_Check {
	protected $errors = [];
	private static $name = 'Advertikon Library';
	protected $test_mode = false;
	
	public function __construct( $test = false ) {
		$this->test_mode = $test;
	}
	
	protected function check() {
		$this->errors[ self::$name ] = [];

		// PHP v5.6+
		if ( $this->test_mode || version_compare( PHP_VERSION, '5.6', '<' ) ) {
			$this->errors[ self::$name ][] =  'PHP version 5.6 or higher required';
		}
	}
	
	public function get_errors() {
		$this->check();
		$errors = [];
		$ret = '';
		
		foreach( $this->errors as $module => $e ) {
			foreach( $e as $l ) {
				$errors[] = sprintf( "<li><b>%s - %s</b></li>", $module, $l );
			}
		}
		
		if ( count( $errors ) > 0 ) {
			$ret .= '<div style="background-color: #ffc4c4; padding: 5px;">' .
					'<b style="color: red;"> Environment incompatibility detected:</b><ul>';
			
			foreach( $errors as $e ) {
				$ret .= $e;
			}
			
			$ret .= '</ul></div>';
		}
		
		return $ret;
	}
}
