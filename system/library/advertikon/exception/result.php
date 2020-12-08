<?php
/**
 * Advertikon Save Configuration Result Class
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */
 
namespace Advertikon\Exception;

class Result extends Transport {
	protected $count = 0;

	public function has_error() {
		return count( $this->data ) > 0;
	}

	public function to_array() {
		$ret = [];

		foreach( $this->errors as $e ) {
			if ( isset( $e['code'], $e['message'] ) ) {
				$ret[ $e['code'] ] = $e['message'];
			}
		}

		return $ret;
	}

	public function set_count( $count ) {
		$this->count = $count;
	}

	public function get_count() {
		return $this->count;
	}
}
