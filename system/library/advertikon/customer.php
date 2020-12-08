<?php
/**
 * @package advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

class Customer extends Account {
	protected $name = 'customer';
	protected $id   = 'customer_id';
	protected $orders = [];

	/**
	 * Returns customer object
	 * @param string $email
	 * @param Advertikon $a
	 * @throws Exception
	 * @return \Advertikon\Customer
	 */
	public static function get( $email, Advertikon $a ) {
		$q = $a->q()->log( 1 )->run_query( [
			'table' => 'customer',
			'query' => 'select',
			'where' => [
				'field'     => '`email`',
				'operation' => '=',
				'value'     => $email,
			]
		] );
		
		if ( $q && !$q->is_empty() ) {
			return new Customer( $q->current(), $a );
		}
		
		throw new Exception( 'Customer with email ' . $email . ' doesn\'t exist' );
	}

    /**
     * @param $email
     * @param Advertikon $a
     * @return Customer[]
     * @throws Exception
     */
    static public function get_from_order($email, Advertikon $a ) {
		$q = $a->q()->log( 1 )->run_query( [
			'table' => 'order',
			'query' => 'select',
			'where' => [
				'field'     => '`email`',
				'operation' => '=',
				'value'     => $email,
			]
		] );

		if ( $q && !$q->is_empty() ) {
			return self::fetch_from_orders( $q, $a );
		}
		
		throw new Exception( 'Customer with email ' . $email . ' has no orders' );
	}

	public function __construct( array $data, Advertikon $a ) {
		$this->fields = array_merge( $this->fields, [
			'customer_id',
			'customer_group_id',
			'store_id',
			'language_id',
			'cart',
			'wishlist',
			'newsletter',
			'address_id',
			'custom_field',
			'safe',
			'token',
			'code',
		] );
		
		parent::__construct( $data, $a );
	}

	public function get_id() {
		return $this->data['customer_id'];
	}
	
	public function get_customer_id() {
		return $this->data['customer_id'];
	}
	
	public function get_customer_group_id() {
		return $this->data['customer_group_id'];
	}
	
	public function get_store_id() {
		return $this->data['store_id'];
	}
	
	public function get_language_id() {
		return $this->data['language_id'];
	}
	
	public function get_cart() {
		return $this->data['cart'];
	}
	
	public function get_wishlist() {
		return $this->data['wishlist'];
	}
	
	public function get_newsletter() {
		return $this->data['newsletter'];
	}
	
	public function get_address_id() {
		return $this->data['address_id'];
	}
	
	public function get_custom_field() {
		return $this->data['custom_field'];
	}
	
	public function get_safe() {
		return $this->data['safe'];
	}
	
	public function get_token() {
		return $this->data['token'];
	}
	
	public function get_code() {
		return $this->data['code'];
	}
	
	public function get_address() {
		if ( $this->address ) {
			return $this->address;
		}

		$this->address = Address::customer_address( $this->get_email(), $this->a );

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

		$ret['Ip'] = $this->data['ip'];

		return implode( ',', array_map( [ $this, 'add_quot' ], array_keys( $ret ) ) ) . "\r\n" .
			implode( ',', array_map( [ $this, 'add_quot' ], $ret ) );
	}

	public function block($block_order = true ) {
		$transaction = new Transaction( $this->a );

		$transaction->add(
			\Closure::bind( function() { $this->block_account(); }, $this ),
			\Closure::bind( function() { $this->unblock_account(); }, $this )
		);

		foreach( $this->get_address() as $address ) {
			$transaction->add(
				function() use( $address ) { $address->block_account(); },
				function() use( $address ) { $address->unblock_account(); }
			);
		}

		if ( $block_order ) {
			$this->block_order( $transaction );
		}

		$transaction->run();
	}

	public function unblock() {
		$this->unblock_account();
		$order = new Order( [ 'email' => $this->get_email(), ], $this->a );
		$order->unblock();
		
		foreach( $this->get_address() as $address ) {
		    /** @var \Advertikon\Address $address */
			$address->unblock();
		}

		return true;
	}

	public function block_order( Transaction $transaction ) {
		foreach( $this->get_orders() as $order ) {
			$transaction->add(
				function() use( $order ) { $order->block_account(); },
				function() use( $order ) { $order->unblock_account(); }
			);
		}
	}

    /**
     * @return Order[]
     * @throws Exception
     */
    public function get_orders() {
		if ( $this->orders ) {
			return $this->orders;
		}

		$this->orders = Order::get( $this->get_email(), $this->a );

		return $this->orders;
	}

	public function erase( $erase_order = true ) {
		if ( $erase_order ) {
			$this->erase_order();
		}

		foreach( $this->get_address() as $address ) {
			$address->erase();
		}

		$this->erase_account();
	}

	public function erase_order() {
		foreach( $this->get_orders() as $order ) {
			$order->erase();
		}
	}

	public function has_active_order() {
		foreach( $this->get_orders() as $order ) {
			if ( !$order->is_missed() && !$order->is_complete() || $order->has_active_recurring() ) {
				return true;
			}
		}

		return false;
	}

	public function has_account_consent() {
		$term = new Adk_Gdpr\Term( $this->a );
		return $term->has_account_consent( $this->get_email() );
	}

	public function has_checkout_consent() {
		$term = new Adk_Gdpr\Term( $this->a );
		return $term->has_checkout_consent( $this->get_email() );
	}

	////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param DB_Result $orders
     * @param Advertikon $a
     * @return Customer[]
     */
    static protected function fetch_from_orders(DB_Result $orders, Advertikon $a ) {
		$addresses = [];

		foreach( $orders as $order ) {
			$customer = new Customer( $order, $a );
			$customers[] = $customer;

			$data = self::get_data_address_from_order( $order, 'payment_' );

			if ( $data ) {
				$address = new Address( $data, $a );

				if ( !$address->is_empty() ) {
					$addresses[] = $address;
				}

			} else {
				$a->error( 'Empty payment address. Oder ID ' . $order['order_id'] );
			}

			$data = self::get_data_address_from_order( $order, 'shipping_' );

			if ( $data ) {
				$address = new Address( $data, $a );

				if ( !$address->is_empty() ) {
					$addresses[] = $address;
				}

			} else {
				$a->error( 'Empty payment shipping. Oder ID ' . $order['order_id'] );
			}
		}

		$customers = Customer::unique( $customers );

		foreach( $customers as $customer ) {
			$customer->set_address( $addresses );
		}

		return $customers;
   }


	static protected function get_data_address_from_order( array $order, $type = 'payment_' ) {
		$ret = [];

		foreach( $order as $k => $v ) {
			if ( substr( $k, 0, strlen( $type ) ) === $type ) {
				$ret[ substr( $k, strlen( $type) ) ] = $v;
			}
		}

		return $ret;
	}

	protected function get_blocked_fields( $placeholder ) {
		return [
			'firstname'    => $placeholder,
			'lastname'     => $placeholder,
			'email'        => $placeholder,
			'telephone'    => $placeholder,
			'fax'          => $placeholder,
			'ip'           => $placeholder,
			'custom_field' => $placeholder,
		];
	}
}
