<?php
/**
 * Transaction class
 *
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

use Closure;

class Transaction {
	protected $actions = [];
	protected $rollbacks = [];
	protected $index = 0;
	protected $a = null;
	protected $length = 100;

	public function __construct( $a ) {
		$this->a = $a;

		for( $i = 0; $i < $this->length; $i++ ) {
			$this->actions[ $i ] = null;
			$this->rollbacks[ $i ] = null;
		}
	}

	/**
	 * Adds transaction
	 * @param Closure $action Action 
	 * @param Closure|null $rollback Rollback action
	 * @param int|null $index Position of the transaction in the transactions list 
	 * @return void
	 */
	public function add( Closure $action, Closure $rollback = null, $index = null ) {
		if ( !is_null( $index ) && isset( $this->actions[ (int)$index ] ) && !is_null( $this->actions[ (int)$index ] ) ) {
			throw new Exception( sprintf( 'Transaction with index %s already exists', $index ) );
		}

		// if ( !is_a( $action, 'Closure' ) ) {
		// 	throw new Exception( 'Transaction action needs to be a Closure' );
		// }

		// if ( !is_null( $rollback ) && !is_a( $rollback, 'Closure' ) ) {
		// 	throw new Exception( 'Transaction rollback needs to be a closure' );
		// }

		if ( !is_null( $index ) ) {
			$index =  (int)$index;

		} else {
			$index = $this->index;
		}

		$this->actions[ $index ] = $action;
		$this->rollbacks[ $index ] = $rollback;

		if ( $this->index < $index ) {
			$this->index = $index;
		}

		if( $this->index === (int)$index ) {
			$this->index++;
		}
	}

	/**
	 * Runs the list of transactions
	 * @param int|null $index Optional index to start from
	 * @return void
	 */
	public function run( $index = null ) {
		$this->index = (int)$index;

		for( ;$this->index < count( $this->actions ); $this->index++ ) {
			if ( is_null( $this->actions[ $this->index ] ) ) {
				continue;
			}

			$this->a->log( sprintf( 'Run transaction #%s', $this->index ) );

			try {
				$this->actions[ $this->index ]();

			} catch ( \Exception $e ) {
				$this->a->log( 'Exception thrown during transaction run' );
				$this->rollback();
				throw $e;

			} catch ( \Error $e ) {
				$this->a->log( 'Error thrown during transaction run' );
				$this->rollback();
				throw new Exception( $e->getMessage() . ' in file ' . $e->getFile() . ':' . $e->getLine() );
			}
		}
	}

	/**
	 * Rolls back the actions
	 */
	public function rollback() {
		if ( count( $this->rollbacks ) === 0 ) {
			return;
		}

		for( ;$this->index >= 0; $this->index-- ) {
			if ( !is_null( $this->rollbacks[ $this->index ] ) ) {
				$this->a->log( sprintf( 'Run rollback #%s', $this->index ) );

				try {
					$result = $this->rollbacks[ $this->index ]();

					// If rollback action returns FALSE - skip the rest
					if (false === $result) {
						$this->a->log( 'Stop farther rollback' );
						return;
					}
				} catch ( \Exception $e ) {
					$this->a->error( $e );

				} catch ( \Error $e ) {
					$this->a->error( $e );
				}
			}
		}
	}
}
