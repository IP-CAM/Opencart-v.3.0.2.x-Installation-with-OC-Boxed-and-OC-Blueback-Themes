<?php
/**
 * @package advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

class Affiliate extends Account {
	protected $name = 'affiliate';
	protected $id   = 'affiliate_id';

	public static function get( $email, Advertikon $a ) {
		$q = self::get_account( $email, $a );

		if ( $q && !$q->is_empty() ) {
			return new Affiliate( $q->current(), $a );
		}
		
		throw new Exception( 'Affiliate with email ' . $email . ' doesn\'t exist' );
	}

	public function __construct( array $data, Advertikon $a ) {
		$this->fields = array_merge( $this->fields, [
			'company',
			'website',
			'commission',
			'tax',
			'payment',
			'cheque',
			'paypal',
			'bank_name',
			'bank_branch_number',
			'bank_swift_code',
			'bank_account_name',
			'bank_account_number',
			
			'address_1',
			'address_2',
			'city',
			'postcode',
			'country',
			'zone',
		] );

		if ( version_compare( VERSION, '3', '<' ) ) {
			$this->fields[] = 'affiliate_id';
			$this->fields[] = 'code';

		} else {
			$this->fields[] = 'customer_id';
			$this->fields[] = 'tracking';
			$this->id       = 'customer_id';
			$this->name     = 'customer_affiliate';
		}
		
		parent::__construct( $data, $a );
	}

	public function get_id() {
		return $this->get_affilaite_id();
	}

	public function get_company() {
		return $this->data['company'];
	}
	
	public function get_website() {
		return $this->data['website'];
	}
	
	public function get_commission() {
		return $this->data['commission'];
	}
	
	public function get_tax() {
		return $this->data['tax'];
	}
	
	public function get_payment() {
		return $this->data['payment'];
	}
	
	public function get_cheque() {
		return $this->data['cheque'];
	}
	
	public function get_paypal() {
		return $this->data['paypal'];
	}
	
	public function get_bank_name() {
		return $this->data['bank_name'];
	}
	
	public function get_bank_branch_numner() {
		return $this->data['bank_branch_number'];
	}
	
	public function get_bank_swift_code() {
		return $this->data['bank_swift_code'];
	}
	
	public function get_bank_account_name() {
		return $this->data['bank_account_number'];
	}
	
	public function get_affilaite_id() {
		return isset( $this->data['affiliate_id'] ) ? $this->data['affiliate_id'] :
			$this->data['customer_id'];
	}
	
	public function get_code() {
		return isset( $this->data['code'] ) ? $this->data['code'] : $this->data['tracking'];
	}
	
	public function get_address() {
		if ( $this->address ) {
			return $this->address;
		}

		if ( version_compare( VERSION, '3', '>' ) ) {
			$this->address =  Address::customer_address( $this->get_email(), $this->a );

		} else {
			$this->address = [ new Address( $this->fetch_address(), $this->a ) ];
		}
		
		return $this->address;
	}

	public function to_html() {
		$ret = [];

		$ret[] = $this->data['firstname'] . ' ' . $this->data['lastname'];
		$ret[] = $this->data['email'];

		if ( !empty( $this->data['telephone'] ) ) {
			$ret[] = $this->data['telephone'];
		}

		if ( !empty( $this->data['fax'] ) ) {
			$ret[] = $this->data['fax'];
		}

		if ( !empty( $this->data['company'] ) ) {
			$ret[] = $this->data['company'];
		}

		if ( !empty( $this->data['website'] ) ) {
			$ret[] = $this->data['website'];
		}

		if ( !empty( $this->data['bank_name'] ) ) {
			$ret[] = $this->data['bank_name'];
		}

		if ( !empty( $this->data['bank_branch_number'] ) ) {
			$ret[] = $this->data['bank_branch_number'];
		}

		if ( !empty( $this->data['bank_account_number'] ) ) {
			$ret[] = $this->data['bank_account_number'];
		}

		if ( !empty( $this->data['bank_swift_code'] ) ) {
			$ret[] = $this->data['bank_swift_code'];
		}

		$ret[] = $this->data['ip'];

		return implode( '<br>', $ret );
	}

	public function to_csv() {
		$ret = [];

		$ret['Name'] = $this->data['firstname'] . ' ' . $this->data['lastname'];
		$ret['Email'] = $this->data['email'];

		if ( !empty( $this->data['telephone'] ) ) {
			$ret['Telephone'] = $this->data['telephone'];
		}

		if ( !empty( $this->data['fax'] ) ) {
			$ret['Fax'] = $this->data['fax'];
		}

		if ( !empty( $this->data['company'] ) ) {
			$ret['Company'] = $this->data['company'];
		}

		if ( !empty( $this->data['website'] ) ) {
			$ret['Website'] = $this->data['website'];
		}

		if ( !empty( $this->data['bank_name'] ) ) {
			$ret["Bank Name"] = $this->data['bank_name'];
		}

		if ( !empty( $this->data['bank_branch_number'] ) ) {
			$ret['Bank Branch Name'] = $this->data['bank_branch_number'];
		}

		if ( !empty( $this->data['bank_account_number'] ) ) {
			$ret['Bank Account Number'] = $this->data['bank_account_number'];
		}

		if ( !empty( $this->data['bank_swift_code'] ) ) {
			$ret['Bank Swift Code'] = $this->data['bank_swift_code'];
		}

		$ret['Ip'] = $this->data['ip'];

		return implode( ',', array_map( [ $this, 'add_quot' ], array_keys( $ret ) ) ) . "\r\n" .
			implode( ',', array_map( [ $this, 'add_quot' ], $ret ) );
	}

	public function block() {
		$transaction = new Transaction( $this->a );

		$transaction->add(
			\Closure::bind( function() { $this->block_account(); }, $this ),
			\Closure::bind( function() { $this->unblock_account(); }, $this )
		);

		if ( version_compare( VERSION, '3', '>=' ) ) {
			$customer = Customer::get( $this->get_email(), $this->a );
			$transaction->add( function() use( $customer ) { $customer->block(); } );
		}

		$transaction->run();
	}

    /**
     * @return bool
     */
    public function unblock() {
		return $this->unblock_account();
	}

	public function erase() {
		if ( version_compare( VERSION, '3', '>=' ) ) {
			$customer = Customer::get( $this->get_email(), $this->a );
			$customer->erase();
		}

		$this->erase_account();
	}

	////////////////////////////////////////////////////////////////////////////////////////////////
	
	static protected function get_account( $email, Advertikon $a ) {
		if( version_compare( VERSION, '3', '>=' ) ) {
			return self::get_account3( $email, $a );
		}
		
		$q = $a->q()->log( 1 )->run_query( [
			'table' => [ 'a' => 'affiliate', ],
			'query' => 'select',
			'field' => [ '`a`.*', 'country' => '`c`.`name`', 'zone' => '`z`.`name`', ],
			'where' => [
				'field'     => '`email`',
				'operation' => '=',
				'value'     => $email,
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
		
		return $q;
	}
	
	static protected function get_account3( $email, $a ) {
		$q = $a->q()->log( 1 )->run_query( [
			'table' => 'customer_affiliate',
			'query' => 'select',
			'where' => [
				'field'     => 'email',
				'operation' => '=',
				'value'     => $email,
			]
		] );
		
		return $q;
	}
	
	protected function fetch_address() {
		return [
			'address_1' => $this->data['address_1'],
			'address_2' => $this->data['address_2'],
			'city'      => $this->data['city'],
			'postcode'  => $this->data['postcode'],
			'country'   => $this->data['country'],
			'zone'      => $this->data['zone'],
			'firstname' => $this->data['firstname'],
			'lastname'  => $this->data['lastname'],
			'company'   => $this->data['company'],
		];
	}

	protected function get_blocked_fields( $placeholder ) {
		$ret = [
			'company'             => $placeholder,
			'website'             => $placeholder,
			'bank_name'           => $placeholder,
			'bank_branch_number'  => $placeholder,
			'bank_swift_code'     => $placeholder,
			'bank_account_name'   => $placeholder,
			'bank_account_number' => $placeholder,
		];

		if ( version_compare( VERSION, '3', '<' ) ) {
			$ret = array_merge( $ret, [
				'firstname'    => $placeholder,
				'lastname'     => $placeholder,
				'email'        => $placeholder,
				'telephone'    => $placeholder,
				'fax'          => $placeholder,
				'ip'           => $placeholder,
				'address_1'    => $placeholder,
				'address_2'    => $placeholder,
				'city'         => $placeholder,
				'postcode'     => $placeholder,
				'country_id'   => 0,
				'zone_id'      => 0,
			] );

		} else {
			$ret[] = [ 'custom_field' => $placeholder, ];
		}

		return $ret;
	}
}
