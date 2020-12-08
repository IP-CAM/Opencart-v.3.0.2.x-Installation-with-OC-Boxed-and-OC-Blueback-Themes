<?php
/**
 * Advertikon Array Iterator
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

use ArrayIterator;

class Array_Iterator extends ArrayIterator {

	protected $data = array();
	public $is_end = false;

	/**
	 * @see ArrayIterator::__construct()
	 */
	public function __construct ( $array = array(), $flags = 0 ) {
		if ( is_array( $array ) ) {
			$this->data = $array;

		} else {
			$mess = sprintf( 'Array is expected, "%s" given instead', gettype( $array ) );
			throw new Exception( $mess );
		}
	}	

	/**
	 * @see ArrayIterator::append()
	 */
	public function append ( $value ) {
		$this->data[] = $value;
	}

	/**
	 * @see ArrayIterator::asort()
	 */
	public function asort () {
		parent::asort();
	}
	
	/**
	 * @see ArrayIterator::count()
	 */
	public function count () {
		return count( $this->data );
	}

	/**
	 * @see ArrayIterator::current()
	 */
	public function current () {
		return current( $this->data );
	}

	/**
	 * @see ArrayIterator::getArrayCopy()
	 */
	public function getArrayCopy () {
		return $this->data;
	}

	/**
	 * @see ArrayIterator::getFlags()
	 */
	public function getFlags () {
		parent::getFlags();
	}


	/**
	 * @see ArrayIterator::key()
	 */	
	public function key () {
		return key( $this->data );
	}

	/**
	 * @see ArrayIterator::ksort()
	 */
	public function ksort () {
		parent::ksort();
	}

	/**
	 * @see ArrayIterator::natcasesort()
	 */
	public function natcasesort () {
		parent::natcasesort();
	}

	/**
	 * @see ArrayIterator::natsort()
	 */
	public function natsort () {
		parent::natsort();
	}

	/**
	 * @see ArrayIterator::next()
	 */
	public function next () {
		if( false === next( $this->data ) ) {
			$this->is_end = true;
		}
	}

	/**
	 * @see ArrayIterator::offsetExists()
	 */
	public function offsetExists ( $index ) {
		return isset( $this->data[ $index ] );
	}

	/**
	 * @see ArrayIterator::offsetGet()
	 */
	public function offsetGet ( $index ) {
		return $this->data[ $index ];
	}

	/**
	 * @see ArrayIterator::offsetSet()
	 */
	public function offsetSet ( $index , $newval ) {
		if ( is_null( $index ) ) {
			$this->data[] = $newval;

		} else {
			$this->data[ $index ] = $newval;
		}
	}

	/**
	 * @see ArrayIterator::offsetUnset()
	 */
	public function offsetUnset ( $index ) {
		unset ( $this->data[ $index ] );
	}

	/**
	 * @see ArrayIterator::rewind()
	 */
	public function rewind () {
		reset( $this->data );
		$this->is_end = false;
	}

	/**
	 * @see ArrayIterator::seek()
	 */
	public function seek ( $position ) {
		reset( $this->data );

		while( false !== current( $this->data ) && $position !== key( $this->data ) ) {
			next( $this->data );
		}
	}

	/**
	 * @see ArrayIterator::serialize()
	 */
	public function serialize () {
		return serialize( $this->data );
	}

	/**
	 * @see ArrayIterator::setFlags()
	 */
	public function setFlags ( $flags ) {
		parent::setFlags( $flags );
	}

	/**
	 * @see ArrayIterator::uasort()
	 */
	public function uasort ( $cmp_function ) {
		parent::uasort( $cmp_function );
	}

	/**
	 * @see ArrayIterator::uksort()
	 */
	public function uksort ( $cmp_function ) {
		parent::uksort( $cmp_function );
	}

	/**
	 * @see ArrayIterator::unserialize()
	 */
	public function unserialize ( $serialized ) {
		$this->data = unserialize( $serialized );
	}

	/**
	 * @see ArrayIterator::valid()
	 */
	public function valid () {
		return false !== current( $this->data );
	}

	/**
	 * Slices data structure
	 * @param int $start Start offset 
	 * @param int $length Length 
	 * @return object Self
	 */
	public function slice( $start, $length ) {
		$this->data = array_slice( $this->data, $start, $length );

		return $this;
	}
}
