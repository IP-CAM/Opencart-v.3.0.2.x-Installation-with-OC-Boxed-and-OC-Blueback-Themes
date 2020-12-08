<?php
/**
 * @package adk_gdpr
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

class Address extends Account {
	protected $fields = [
		'firstname' => '',
		'lastname'  => '',
		'company'   => '',
		'address_1' => '',
		'address_2' => '',
		'city'      => '',
		'zone'      => '',
		'postcode'  => '',
		'country'   => '',
		'address_id' => 0,
		'custom_field' => [],
	];

	protected $name           = 'address';
	protected $id             = 'address_id'; 
	protected $distinct_field = 'address_id';
	protected $do_not_compare = [ 'address_id' ];

	static public function customer_address( $email, Advertikon $a ) {
		$ret = [];

		foreach( self::address( $email, $a ) as $address ) {
			$ret[] = new Address( $address, $a );
		}

		return $ret;
	}

	static public function get( $email, Advertikon $a ) {
		$ret = [];

		foreach( self::address( $email, $a ) as $address ) {
			$ret[] = new Address( $address, $a );
		}

		return $ret;
	}
	
	static public function by_id( $id, Advertikon $a ) {
		$data = self::get_by_id( $id, $a );

		if ( !$data ) {
			throw new Exception( 'Address with id #' . $id . ' is missing' );
		}

		return new Address( $data, $a );
	}

	public function __construct( array $data, Advertikon $a ) {
		foreach( $this->fields as $name => $default ) {
			if ( !empty( $data[ $name ] ) ) {
				$this->data[ $name ] = $data[ $name ];

			} else {
				$this->data[ $name ] = $default;
			}
		}

		$this->a = $a;
	}

	public function get_firstname() {
		return $this->data['firstname'];
	}

	public function get_lastname() {
		return $this->data['lastname'];
	}
	
	public function get_name() {
		$ret = $this->get_firstname() . ' ' . $this->get_lastname();
		
		return trim( $ret );
	}

	public function get_company() {
		return $this->data['company'];
	}

	public function get_address_1() {
		return $this->data['address_1'];
	}
	public function get_address_2() {
		return $this->data['address_2'];
	}

	public function get_city() {
		return $this->data['city'];
	}

	public function get_zone() {
		return $this->data['zone'];
	}

	public function get_postcode() {
		return $this->data['postcode'];
	}

	public function get_country() {
		return $this->data['country'];
	}

	public function get_id() {
		return $this->data['address_id'];
	}
	
	public function get_address_id() {
		return $this->data['address_id'];
	}

	public function get_address() {
		return $this;
	}
	
	public function get_custom_field() {
		if ( empty( $this->data['custom_field'] ) ) {
			return [];
		}
		
		return version_compare( VERSION, '2.1', '<' ) ? unserialize( $this->data['custom_field'] ) :
			json_decode( $this->data['custom_field'], true );
	}
	
	public function to_html() {
		return implode( '<br>', $this->show() );
	}

	public function to_csv() {
		$ret = [];
		$line = [];
		$header = [];
		$data = $this->show();

		if ( !$data ) {
			return '';
		}

		foreach( $data as $k => $v ) {
			$header[] = '"' . $k . '"';
			$line[] = '"' . $v . '"';
		}

		return implode( ',', $header ) . "\r\n" . implode( ',', $line );
	}

	public function block() {
		$this->block_account();
	}

	public function unblock() {
		$this->unblock_account();
	}

	public function erase() {
		$this->erase_account();
	}

	////////////////////////////////////////////////////////////////////////////////////////////////

	static protected function address( $email, Advertikon $a ) {
		$customer_id = self::get_customer_id_by_email( $email, $a );

		$q = $a->q()->log( 1 )->run_query( [
			'table' => [ 'a' => 'address',],
			'query' => 'select',
			'field' => [ '`a`.*', 'country' => '`c`.`name`', 'zone' => '`z`.`name`', ],
			'where' => [
				'field'     => '`customer_id`',
				'operation' => '=',
				'value'     => $customer_id,
			],
			'join' => [
				[
					'type'  => 'left',
					'table' => [ 'c' => 'country', ],
					'using' => 'country_id'
				],
				[
					'type'  => 'left',
					'table' => [ 'z' => 'zone', ],
					'using' => 'zone_id'
				]
			],
		] );

		if ( $q ) {
			return $q->to_array();
		}

		return [];
	}
	
	static protected function get_by_id( $id, Advertikon $a ) {
		$q = $a->q()->log( 1 )->run_query( [
			'table' => [ 'a' => 'address',],
			'query' => 'select',
			'field' => [ '`a`.*', 'country' => '`c`.`name`', 'zone' => '`z`.`name`', ],
			'where' => [
				'field'     => '`address_id`',
				'operation' => '=',
				'value'     => $id,
			],
			'join' => [
				[
					'type'  => 'left',
					'table' => [ 'c' => 'country', ],
					'using' => 'country_id'
				],
				[
					'type'  => 'left',
					'table' => [ 'z' => 'zone', ],
					'using' => 'zone_id'
				]
			],
		] );
		
		if ( $q ) {
			return $q->current();
		}
		
		return [];
	}
	

	static protected function get_customer_id_by_email( $email, Advertikon $a ) {
		$q = $a->q()->log( 1 )->run_query( [
			'table' => 'customer',
			'field' => '`customer_id`',
			'where' => [
				'field'     => '`email`',
				'operation' => '=',
				'value'     => $email,
			],
		] );

		if ( $q && count( $q ) > 0 ) {
			return $q['customer_id'];
		}

		return 0;
	}
	
	protected function show() {
		$ret = [];
		
		if ( $this->get_name() ) {
			$ret['Name'] = $this->get_name();
		}
		
		if ( $this->get_company() ) {
			$ret['Company'] = $this->get_company();
		}
		
		if ( $this->get_address_1() ) {
			$ret['Address Line 1'] = $this->get_address_1();
		}
		
		if ( $this->get_address_2() ) {
			$ret['Address Line 2'] = $this->get_address_2();
		}
		
		if ( $this->get_city() ) {
			$ret['City'] = $this->get_city();
		}
		
		$zone = trim( $this->get_zone() . ' ' . $this->get_postcode() );
		
		if ( $zone ) {
			$ret['Zone'] = $zone;
		}
		
		if ( $this->get_country() ) {
			$ret['Country'] = $this->get_country();
		}

		return $ret;
	}

	protected function get_blocked_fields( $placeholder ) {
		return [
			'firstname'    => $placeholder,
			'lastname'     => $placeholder,
			'company'      => $placeholder,
			'address_1'    => $placeholder,
			'address_2'    => $placeholder,
			'city'         => $placeholder,
			'postcode'     => $placeholder,
			'country_id'   => 0,
			'zone_id'      => 0,
			'custom_field' => $placeholder,
		];
	}
}
