<?php
/**
 * @package advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

class Order extends Accounts {
	protected $name = 'order';
	protected $id = 'order_id';
	protected $recurring;

	const RECURRING_INACTIVE  = 1;
	const RECURRING_ACTIVE    = 1;
	const RECURRING_SUSPENDED = 3;
	const RECURRING_CANCELED  = 4;
	const RECURRING_EXPIRED   = 5;
	const RECURRING_PENDING   = 6;

    /**
     * @param $email
     * @param Advertikon $a
     * @return Order[]
     * @throws Exception
     */
    static public function get($email, Advertikon $a ) {
		$ret = [];

		$q = $a->q()->log( 1 )->run_query( [
			'table' => 'order',
			'where' => [
				'field'     => '`email`',
				'operation' => '=',
				'value'     => $email,
			]
		] );

		if ( !$q ) {
			throw new Exception( 'Failed to fetch orders list' );
		}

		foreach( $q as $o ) {
			$ret[] = new Order( $o, $a );
		}

		return $ret;
	}

    /**
     * @param $id
     * @param Advertikon $a
     * @return Order
     * @throws Exception
     */
    static public function get_by_id($id, Advertikon $a ) {
		$ret = [];

		$q = $a->q()->log( 1 )->run_query( [
			'table' => 'order',
			'where' => [
				'field'     => 'order_id',
				'operation' => '=',
				'value'     => $id,
			]
		] );

		if ( !$q ) {
			throw new Exception( 'Failed to fetch orders with id ' . $id );
		}

		return new Order( $q->current(), $a );
	}

	static public function get_recurrig_status( $status ) {
		if ( 1 == $status ) {
			return version_compare( VERSION, '2.2', '<' ) ? self::RECURRING_INACTIVE : self::RECURRING_ACTIVE;

		} else if ( 2 == $status ) {
			return version_compare( VERSION, '2.2', '<' ) ? self::RECURRING_ACTIVE : self::RECURRING_INACTIVE;

		} else if ( 3 == $status ) {
			return version_compare( VERSION, '2.2', '<' ) ? self::RECURRING_SUSPENDED : self::RECURRING_CANCELED;

		} else if ( 4 == $status ) {
			return version_compare( VERSION, '2.2', '<' ) ? self::RECURRING_CANCELED : self::RECURRING_SUSPENDED;

		} else if ( 5 == $status ) {
			return self::RECURRING_EXPIRED;

		} else if ( 6 == $status ) {
			return self::RECURRING_PENDING;
		}

		throw new Exception( 'Undefined status of recurring order: ' . $status );
	}

	public function __construct( array $data, Advertikon $a ) {
		$this->data = $data;
		$this->a = $a;
	}

	public function get_id() {
		return $this->data['order_id'];
	}

	public function get_email() {
		return $this->data['email'];
	}

	public function get_status() {
		return $this->data['order_status_id'];
	}
	
	public function get_order_status_id() {
		return $this->data['order_status_id'];
	}

	public function get_date_added() {
		return $this->data['date_added'];
	}

	public function block() {
		return $this->block_account();
	}

	public function unblock() {
		return $this->unblock_account();
	}

	public function erase() {
		return $this->anonymize_account();
	}

	public function is_complete() {
		return in_array( $this->get_status(), $this->a->config->get( 'config_complete_status' ) );
	}

	public function is_processing() {
		return in_array( $this->get_status(), $this->a->config->get( 'config_processing_status' ) );
	}
	
	public function is_missed() {
		return !$this->get_status();
	}

	public function get_recurrig() {
		if ( $this->recurring ) {
			return $this->recurring_order;
		}

		$q = $this->a->q()->log( 1 )->run_query( [
			'table' => 'order_recurring',
			'where' => [
				'field'     => '`order_id`',
				'operation' => '=',
				'value'     => $this->get_id(),
			],
		] );

		if ( !$q ) {
			throw new Exception( 'Failed to fetch recurring order' );
		}

		$this->recurring = $q->current();

		return $this->recurring;
	}

	public function has_active_recurring() {
		$recurring = $this->get_recurrig();

		return $recurring &&
			in_array(
				self::get_recurrig_status( $recurring['status'] ),
				[ self::RECURRING_ACTIVE, self::RECURRING_SUSPENDED, self::RECURRING_PENDING ]
			);
	}

	public function delete() {
		$order_id = $this->get_id();

		$this->a->db->query("DELETE FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "'");
		$this->a->db->query("DELETE FROM `" . DB_PREFIX . "order_product` WHERE order_id = '" . (int)$order_id . "'");
		$this->a->db->query("DELETE FROM `" . DB_PREFIX . "order_option` WHERE order_id = '" . (int)$order_id . "'");
		$this->a->db->query("DELETE FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");
		$this->a->db->query("DELETE FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "'");
		$this->a->db->query("DELETE FROM `" . DB_PREFIX . "order_history` WHERE order_id = '" . (int)$order_id . "'");
		$this->a->db->query("DELETE `or`, ort FROM `" . DB_PREFIX . "order_recurring` `or`, `" . DB_PREFIX . "order_recurring_transaction` `ort` WHERE order_id = '" . (int)$order_id . "' AND ort.order_recurring_id = `or`.order_recurring_id");

		// Delete voucher data as well
		$this->a->db->query("DELETE FROM `" . DB_PREFIX . "voucher` WHERE order_id = '" . (int)$order_id . "'");
		$this->a->db->query("DELETE FROM `" . DB_PREFIX . "voucher_history` WHERE order_id = '" . (int)$order_id . "'");

		if ( version_compare( VERSION, '3', '<' ) ) {
			$this->a->db->query("DELETE FROM `" . DB_PREFIX . "affiliate_transaction` WHERE order_id = '" . (int)$order_id . "'");
		}
	}

	////////////////////////////////////////////////////////////////////////////////////////////////

	protected function get_blocked_fields( $placeholder ) {
		return [
			'firstname'               => $placeholder,
			'lastname'                => $placeholder,
			'email'                   => $placeholder,
			'telephone'               => $placeholder,
			'fax'                     => $placeholder,
			'custom_field'            => $placeholder,
			'payment_firstname'       => $placeholder,
			'payment_lastname'        => $placeholder,
			'payment_company'         => $placeholder,
			'payment_address_1'       => $placeholder,
			'payment_address_2'       => $placeholder,
			'payment_city'            => $placeholder,
			'payment_postcode'        => $placeholder,
			'payment_country'         => $placeholder,
			'payment_country_id'      => 0,
			'payment_zone'            => $placeholder,
			'payment_zone_id'         => 0,
			'payment_custom_field'    => $placeholder,
			'shipping_firstname'      => $placeholder,
			'shipping_lastname'       => $placeholder,
			'shipping_company'        => $placeholder,
			'shipping_address_1'      => $placeholder,
			'shipping_address_2'      => $placeholder,
			'shipping_city'           => $placeholder,
			'shipping_postcode'       => $placeholder,
			'shipping_country'        => $placeholder,
			'shipping_country_id'     => 0,
			'shipping_zone'           => $placeholder,
			'shipping_zone_id'        => 0,
			'shipping_custom_field'   => $placeholder,
			'ip'                      => $placeholder,
			'forwarded_ip'            => $placeholder,
			'user_agent'              => $placeholder,
		];
	}
}
