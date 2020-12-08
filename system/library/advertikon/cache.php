<?php
/**
 * Advertikon Cache Class
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

class Cache {

	protected $dir = null;
	protected $exp = 3600;
	protected $fs = null;
	protected $is_win = false;
	protected $a = null;
	protected $level = 2;
	protected $level_part = 2;

	public function __construct( $a, $expiration = null, $dir = null ) {
		if ( $this->is_win() ) {
			$this->is_win = true;
			return;
		}

		$this->a = $a;
		$this->fs = new Fs();

		if ( !is_null( $expiration ) ) {
			$this->exp = $expiration;
		}

		if ( !is_null( $dir ) ) {
			$this->dir = rtrim( $dir, '/' ) . '/';

			if ( !$this->dir ) {
				$mess = 'Cache folder is mandatory';
				$this->a->log( new Exception( $mess ) );

				// TODO: add some other flag
				$this->win = true;
				return;
			}

			if ( !$this->fs->above_store_root( $this->dir ) ) {
				$mess = sprintf( 'Cache directory "%s" may not be above the store root folder', $this->dir );
				$this->a->log( new Exception( $mess ) );
				$this->win = true;
				return;
			}

		} else {
			$this->dir = $this->a->data_dir . 'cache/';
		}

		if ( (int)$this->exp <= 0 ) {
			$mess = 'Expiration time should be a number which is greater than 0';
			$this->a->log( new Exception( $mess ) );
			$this->win = true;
			return;
		}

		if ( !is_dir( $this->dir ) ) {
			@mkdir( $this->dir, 0755, true );
			$this->add_htaccess();
			
		} elseif ( rand( 0, 20 ) === 0 ) {
			$this->clear();
		}
	}

	/**
	 * Caches value
	 * @param string $key Value's key 
	 * @param mixed $val Value
	 * @param int|null $exp Expiration period in seconds
	 * @return void
	 */
	public function set( $key, $val, $exp = null ) {
		if ( $this->is_win ) {
			return;
		}

		if ( is_null( $exp ) ) {
			$exp = $this->exp;
		}
		$this->delete( $key );
		$file = $this->dir . $this->key( $key );
		@mkdir( dirname( $file ), 0777, true );

		$entry = $file . '.' . ( time() + $exp );
		file_put_contents( $entry, serialize( $val ) );

		// Grant access only for owner
		chmod( $entry, 0600 );
	}

	/**
	 * Returns cached value
	 * @param string $key Cache key
	 * @return mixed|null
	 */
	public function get( $key ) {
		if ( $this->is_win ) {
			return null;
		}

		if ( $this->a->is_test_env ) {
			$start = microtime( true );
		}

		$cache = glob( $this->dir . $this->key( $key ) . '*.*' );

		if ( is_array( $cache ) ) {
			foreach( $cache as $c ) {
				if ( is_file( $c ) ) {
					if ( time() > @substr( strrchr( $c, '.' ), 1 ) ) {
						@unlink( $c );

						continue;
					}

					$data = unserialize( file_get_contents( $c ) );

					if ( $this->a->is_test_env ) {
						$this->a->debug( sprintf( 'From cache: "%s", [%7.5f sec]', $key, ( microtime( true ) - $start ) ) );
					}

					return $data;
				}
			}
		}

		if ( $this->a->is_test_env ) {
			$this->a->debug( sprintf( 'Missed cache: "%s", [%7.5f sec]', $key, ( microtime( true ) - $start ) ) );
		}

		return null;
	}

	/**
	 * Delete cache entry
	 * @param string $key Cache key
	 * @return void
	 */
	public function delete( $key ) {
		if ( $this->is_win ) {
			return;
		}

		$cache = glob( $this->dir . $this->key( $key ) . '.*' );

		if ( is_array( $cache ) ) {
			foreach( $cache as $c ) {
				@unlink( $c );
			}
		}
	}

	/**
	 * Removes expired cache entries 
	 * @return void
	 */
	public function clear() {
		if ( $this->is_win ) {
			return;
		}

		if ( $this->a->is_test_env ) {
			$start = microtime( 1 );
		}

		$fs = new Fs;
		$t = time();

		$fs->iterate_directory( $this->dir, function( $item ) use ( $t ) {
			if ( is_file( $item ) && @substr( strrchr( $item, '.' ), 1 ) < $t ) {
				@unlink( $item );

			} elseif ( is_dir( $item ) ) {
				@rmdir( $item );
			}
		} );

		if ( $this->a->is_test_env ) {
			$this->a->debug( sprintf( 'Cache clean up: %7.5f sec', ( microtime( 1 ) - $start ) ) );
		}
	}

	/**
	 * Removes all entries 
	 * @return void
	 */
	public function flush() {
		if ( $this->is_win ) {
			return true;
		}

		if ( $this->a->is_test_env ) {
			$start = microtime( 1 );
		}

		$fs = new Fs;

		$fs->iterate_directory( $this->dir, function( $item ) {
			if ( is_file( $item ) ) {
				@unlink( $item );

			} elseif ( is_dir( $item ) ) {
				@rmdir( $item );
			}
		} );

		if ( $this->a->is_test_env ) {
			$this->a->debug( sprintf( 'Cache flushed: %7.5f sec', ( microtime( 1 ) - $start ) ) );
		}

		return true;
	}

	/**
	 * Adds .htaccess file to restrict access to cache from outside
	 * @return void
	 */
	protected function add_htaccess() {
		if ( !is_file( $this->dir . '.htaccess' ) ) {
			$content = '# Automatically generated .htaccess file by Advertikon Cache class
			Order Deny,allow
			Deny from all
			<Files "*">
				Order allow,deny
				Deny from all
			</Files>';

			@file_put_contents( $this->dir . '.htaccess', $content );
			@chmod( $this->dir . '.htaccess', 0644 );
		}
	}

	protected function is_win() {
		return strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN';
	}

	/**
	 * Subfolders $key
	 * @param string $key 
	 * @return string
	 */
	protected function key( $key ) {
		$parts = [];
		$level = $this->level;

		while( --$level >= 0 && strlen( $key ) > $this->level_part ) {
			$parts[] = substr( $key, 0, $this->level_part );
			$key = substr( $key, $this->level_part );
		}

		if ( $key ) {
			$parts[] = $key;
		}

		return implode( '/', $parts );
	}

}
