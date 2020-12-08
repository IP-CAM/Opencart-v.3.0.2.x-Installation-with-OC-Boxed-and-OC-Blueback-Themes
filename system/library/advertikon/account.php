<?php
/**
 * @package advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

abstract class Account extends Accounts {
	protected $fields = [
		'firstname',
		'lastname',
		'email',
		'telephone',
		'fax',
		'approved',
		'date_added',   
		'ip',
		'status',
	];

	protected $address = [];

	abstract public function get_address();
	abstract public function to_html();

	public function __construct( array $data, Advertikon $a ) {
		foreach( $this->fields as $k ) {
			$this->data[ $k ] = isset( $data[ $k ] ) ? $data[ $k ] : '';
		}

		$this->a = $a;
	}
	
	public function get_name() {
		return $this->data['firstname'] . ' ' . $this->data['lastname']; 
	}

	public function get_email() {
		return $this->data['email']; 
	}
	
	public function get_status() {
		return $this->data['status'];
	}
	
	public function get_telephone() {
		return $this->data['telephone'];
	}
	
	public function get_fax() {
		return $this->data['fax'];
	}
	
	public function get_approved() {
		return $this->data['approved'];
	}
	
	public function get_date_added() {
		return $this->data['date_added'];
	}
	
	public function get_ip() {
		return $this->data['ip'];
	}

	public function add_address( Address $address ) {
		$this->address[] = $address;
	}

	public function set_address( array $addresses ) {
		$this->address = $addresses;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////

	protected function add_quot( $i ) {
		return '"' . $i . '"';
	}
}
