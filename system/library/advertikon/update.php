<?php

/**
 * @package Update manager
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

class Update {
	protected $controller = null;
	protected $version = '';
	private $rollbacks = array();
	private $i = 1;

	public function __construct( $controller ) {
		$this->controller = $controller;
	}

	public function update() {
		$this->controller->a->debug( sprintf( 'Updating extension to version %s...', $this->version ) );

		if ( !$this->check_roll_backs() ) return false;

		if( null === set_error_handler( array( $this, 'error' ), E_ALL ) ) {
			$this->controller->a->error( 'Failed to set error handler to intercept error messages' );
		}

		for ( $i = 1; $i <= $this->i; $i++ ) {
			$update_name = "update$i";

			try {
				if ( false === $this->{"update$i"}() ) {
					$this->controller->a->error(
						sprintf( "Update %s can not be applied: method %s reported error", $this->version, $update_name )
					);

					$this->rollback( --$i );

					return false;

				} else {
					$this->controller->a->debug(
						sprintf( "Method %s of update %s was applied successfully",	$update_name, $this->version )
					);
				}

			} catch ( \Exception $e ) {
				$this->controller->a->error(
					sprintf( "Update %s can not be applied: update method %s thrown an exception: %s", $this->version, $update_name, $e->getMessage() )
				);

				$this->rollback( --$i );

				return false;
			}
		}

		restore_error_handler();

		$this->controller->a->set_db_version( $this->version );
		$this->controller->a->debug(
			sprintf( "Update %s was applied successfully, %s updates were made", $this->version, $this->i )
		);

		return true;
	}

	/**
	 * Checks matching between update and rollback methods
	 * @return boolean
	 */
	protected function check_roll_backs() {
		while ( method_exists( $this, "update{$this->i}" ) ) {
			if ( !method_exists( $this, "rollback{$this->i}" ) ) {
				$this->controller->a->error(
					sprintf( "Update %s can not be applied: missing rollback method %s", $this->version, "rollback{$this->i}" )
				);

				return false;
			}

			$this->i++;
		}

		$this->i--;

		return true;
	}

	/**
	 * Rolls back updates
	 * @param int $step Number of steps to be rolled back
	 * @return boolean
	 */
	public function rollback( $step = null ) {
		$on_failure = !is_null( $step );

		if ( is_null( $step ) ) {
			$this->check_roll_backs();
			$step = $this->i;
		}

		while ( $step > 0 ) {
			try {
				if ( false === $this->{"rollback$step" }() ) {
					$this->controller->a->error(
						sprintf( "Update %s: rollback method %s failed", $this->version, "rollback{$step}" )
					);

					return false;

				} else {
					$this->controller->a->debug(
						sprintf( "Update %s: step %s was rolled back", $this->version, $step )
					);
				}

			} catch ( \Exception $e ) {
				$this->controller->a->error(
					sprintf(
						"Update %s: rollback method %s threw the exception %s",
						$this->version,
						"rollback{$step}",
						$e->getMessage()
					)
				);

				return false;
			}

			$step--;
		}

		// Do not degrade DB version on update failure, since new DB version hasn't been applied yet
		if ( !$on_failure ) {
			$this->controller->a->unset_db_version();
		}

		return true;
	}

	/**
	 * Catches errors of level E_ERROR
	 * @param integer $errno 
	 * @param string $errstr 
	 * @param integer $errfile 
	 * @param integer $errline 
	 * @throws Exception on error of E_ERROR level
	 */
	public function error( $errno , $errstr, $errfile, $errline ) {
		throw new \Exception( sprintf( "%s in %s:%s", $errstr, $errfile, $errline ) );
	}
}
