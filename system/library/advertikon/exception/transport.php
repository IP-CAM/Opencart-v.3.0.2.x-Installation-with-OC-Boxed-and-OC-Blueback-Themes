<?php
/**
 * Advertikon Stripe transport Exception
 *
 * @author Advertikon
 * @package Stripe
 * @version 1.1.53
 */

namespace Advertikon\Exception;

class Transport extends \Advertikon\Exception {

	protected $data = [];

	public function __construct( $data = '' ){
		if( is_scalar( $data ) ) {
			parent::__construct( $data );

		} else {
			$this->_data[] = $data;
			parent::__construct( '' );
		}
	}

	/**
	* Get transported object
	*
	* @return mixed
	*/
	public function getData(){
		return $this->data;
	}

	public function add( array $data ) {
 		$this->data[] = $data;
 	}
}
