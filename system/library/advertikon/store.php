<?php

/**
 * @package Advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

/**
 * Store class
 *
 * @author Advertikon
 */
class Store {
    /** @var Store[]  */
	static protected $stores  = [];

	/** @var Store */
	static protected $current;
	static protected $names   = [];

	protected $a;
	protected $name;
	protected $http;
	protected $https;
	protected $id;
	protected $url;

	public function __construct( Advertikon $a, array $data = null ) {
		$this->a = $a;

		if ( is_null( $data ) ) {
			$current = self::get_current( $a );
			$this->name  = $current->get_name();
			$this->id    = $current->get_id();
			$this->http  = $current->get_http();
			$this->https = $current->get_https();
			$this->url   = $current->get_url();
			return;
		}

		$this->name  = $data['name'];
		$this->id    = $data['id'];
		$this->http  = $data['http'];
		$this->https = $data['https'];
		$this->url   = $data['url'];
	}

	static public function get_stores( Advertikon $a ) {
		if ( !self::$stores ) {
			self::init_stores( $a );
			self::set_current( $a );
		} 

		return self::$stores;
	}

    /**
     * @param Advertikon $a
     * @return Store
     */
    static public function get_current(Advertikon $a ) {
		if ( !self::$stores ) {
			self::init_stores( $a );
			self::set_current( $a );
		} 

		return self::$current;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_http() {
		return $this->http;
	}

	public function get_https() {
		return $this->https;
	}

	public function get_url() {
		return $this->url;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////

	static protected function init_stores( Advertikon $a ) {
		self::query_names( $a );

		// Default store
		$data = self::normalize( [
			'store_id'  => 0,
			'url'       => $a->url()->parse( $a->url()->catalog_url() )->get_host(),
		], $a );

		self::$stores[ 0 ] = new Store( $a, $data );

		// Additional stores
		$q = $a->q( [
			'table' => 'store',
			'query' => 'select',
		] );

		if ( $q ) {
			foreach( $q as $line ) {
				$data = self::normalize( $line, $a );
				self::$stores[ $line['store_id'] ]  = new Store( $a, $data );
			}
		}
	}

	static protected function normalize( array $line, Advertikon $a ) {
		if ( empty( $line['url'] ) ) {
			$a->error( new Exception( 'Store has no url part' ) );
		}

		return [
			'name'  => isset( $line['name'] ) ? $line['name'] : self::set_name( $line['store_id'], $a ),
			'url'   => $line['url'],
			'http'  => 'http://' . $line['url'],
			'https' => 'https://' . $line['url'],
			'id'    => $line['store_id'],
		];
	}

	static protected function set_name( $store_id, Advertikon $a ) {
		foreach( self::$names as $name ) {
			if ( $name['store_id'] == $store_id ) {
				return $name['value'];
			}
		}

		$a->error( 'Store name is missing for store #' . $store_id );
	}

	static protected function query_names( Advertikon $a ) {
		$nq = $a->q( [
			'table' => 'setting',
			'query' => 'select',
			'where' => [
				'field'     => 'key',
				'operation' => '=',
				'value'     => 'config_name',
			],
		] );

		if ( !$nq ) {
			$a->error( new Exception( 'Failed to query stores names' ) );
			return;
		}

		self::$names = $nq->to_array();
	}

	static protected function set_current( Advertikon $a ) {
		$host = $a->url()->parse( $_SERVER['HTTP_HOST'] )->get_host();

		foreach( self::$stores as $store ) {
			if ( $store->get_url() === $host ) {
				self::$current = $store;
				return;
			}
		}

		$a->error( new Exception( 'Failed to detect current store' ) );
	}
}
