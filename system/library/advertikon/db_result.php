<?php
/**
 * Advertikon DB result Class
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

class DB_Result extends Array_Iterator {

	public function __construct( array $data ) {
		if ( !$data ) {
			return;
		}

		if ( is_array( reset( $data ) ) ) {
			$this->data = $data;

		} else {
			array_push( $this->data, $data );
		}
	}

	public function offsetGet ( $offset ) {
		$current = $this->current();

		return isset( $current[ $offset ] ) ? $current[ $offset ] : null;
	}

	public function offsetSet ( $offset , $value ) {
		if ( is_null( $offset ) ) {
			$this->append( $value );

		} else {
			$current = &$this->data[ key( $this->data ) ];

			if ( ( is_string( $offset ) || is_numeric( $offset ) ) && isset( $current[ $offset ] ) ) {
				$current[ $offset ] = $value;

			} else {
				array_push( $current, $value );
			}

			unset( $current );
		}
	}

	public function current() {
		$ret = parent::current();

		if ( false === $ret ) {
			$ret = array();
		}

		return $ret;
	}

	/**
	 * Compares datasets. Dataset with DB result
	 * @param array $data Dataset to be compared with
	 * @return boolean
	 */
	public function compare( $data ) {
		foreach( $data as $k => $v ) {
			if ( !is_scalar( $v ) ) {
				$v = json_encode( $v );
			}

			if ( $this->offsetGet( $k ) != $v ) {
				ADK()->log( sprintf( 'DB result compare: different values for key "%s" ("%s", "%s")', $k, $this->offsetGet( $k ), $v ) );
				return false;
			}
		}

		return true;
	}

	/**
	 * Compares datasets. DB result with dataset
	 * @param array $data Dataset to be compared with
	 * @param array $ignore Fields to ignore
	 * @return boolean
	 */
	public function compare_to( $data, $ignore = [] ) {
		foreach( $this->current() as $k => $v ) {
			if ( in_array( $k, $ignore ) ) {
				continue;
			}

			if ( isset( $data[ $k ] ) ) {
				$value = $data[ $k ];

				if ( !is_scalar( $value ) ) {
					$value = json_encode( $value );
				}

				if ( $value != $v ) {
					ADK()->log( sprintf( 'DB result compare: different values for key "%s" ("%s", "%s")', $k, $v, $value ) );

					return false;
				}
				
			} else {
				ADK()->log( sprintf( 'DB result compare: compared data has no value for key "%s"', $k ) );

				return false;
			}
		}

		return true;
	}
	
	/**
	 * Returns array representation
	 * @return array
	 */
	public function to_array() {
		return $this->data;
	}
	
	/**
	 * Checks if dataset is empty
	 * @return boolean
	 */
	public function is_empty() {
		return count( $this->data ) === 0;
	}
}
