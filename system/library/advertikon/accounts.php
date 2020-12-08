<?php
/**
 * @package advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

abstract class Accounts {
	const TMP_SUFFIX   = '_blocked';
	const BLOCKED_TEXT = '--blocked--';
	const ERASED_TEXT  = '--erased--';

	abstract function get_id();
	abstract protected function get_blocked_fields( $placeholder );
	abstract function block();
	abstract function unblock();
	abstract function erase();

	protected $distinct_field = 'email';
	protected $a;
	protected $data = [];
	protected $do_not_compare = [];

	static public function in_array( Accounts $account, array $set ) {
		foreach( $set as $a ) {
			if ( $account->is_equal( $a ) ) {
				return true;
			}
		}

		return false;
	}

	static public function unique( array $set ) {
		$ret = [];
		
		foreach( $set as $account ) {
			if ( !self::in_array( $account, $ret ) ) {
				$ret[] = $account;
			}
		}
		
		return $ret;
	}
	
	public function is_equal( Accounts $a ) {
		foreach( $this->data as $k => $v ) {
			if ( in_array( $k, $this->do_not_compare ) ) {
				continue;
			}

			if ( $v !== $a->{"get_$k"}() ) {
				$this->a->log( 'not equal' );
				return false;
			}
		}

		return true;
	}
	
	public function is_empty() {
		if ( !$this->data ) {
			return true;
		}

		foreach( $this->data as $k => $v ) {
			if ( in_array( $k, $this->do_not_compare ) ) {
				continue;
			}

			if ( !empty( $v ) ) {
				return false;
			}
		}
		
		return true;
	}
	
	public function dump() {
		return $this->data;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////

	protected function create_block_table( $name = null ) {
		if ( is_null( $name ) ) {
			$name = $this->name;
		}

		$this->a->db->query( "CREATE TABLE " . DB_PREFIX . $this->get_blocked_name() . " LIKE " . DB_PREFIX . $name );
	}

	protected function block_account() {
		$this->a->log( 'blocking ' . $this->name );
		$this->transfer_to_block_table();
		$this->anonymize_fields();
	}

	protected function anonymize_account() {
		$this->anonymize_fields( self::ERASED_TEXT );
	}

	protected function anonymize_fields( $placeholder = self::BLOCKED_TEXT ) {
		$this->a->q()->log( 1 )->run_query( [
			'table' => $this->name,
			'query' => 'update',
			'set'   => $this->get_blocked_fields( $placeholder ),
			'where' => [
				'field'     => '`' . $this->id . '`',
				'operation' => '=',
				'value'     => $this->get_id(),
			],
		] );
	}

	protected function transfer_to_block_table() {
		if ( !$this->block_table_exists() ) {
			$this->create_block_table();
		}

		if ( $this->check_if_blocked() ) {
			$this->a->error( 'Account already blocked' );
			return;
		}

		$this->a->q()->log( 1 )->run_query( [
			'table' => $this->get_blocked_name(),
			'query' => 'delete',
			'where' => [
				'field'     => '`' . $this->id . '`',
				'operation' => '=',
				'value'     => $this->get_id(),
			],
		] );

		$q = $this->a->db->query(
			"INSERT INTO " . DB_PREFIX . $this->get_blocked_name() . " SELECT * FROM " .
				DB_PREFIX . $this->name . ' WHERE ' . $this->id . ' = ' . (int)$this->get_id()
		);

		if ( $this->a->db->countAffected() === 0 ) {
			$this->a->log( "INSERT INTO " . DB_PREFIX . $this->get_blocked_name() . " SELECT * FROM " .
				DB_PREFIX . $this->name . ' WHERE ' . $this->id . ' = ' . (int)$this->get_id() );
			throw new \Exception( 'Failed to move account data to blocked table' );
		}
	}

	protected function unblock_account() {
		$ret = false;
		$this->a->log( 'unblocking ' . $this->name );

		if ( !$this->block_table_exists() ) {
			return false;
		}

		$q = $this->a->q()->log( 1 )->run_query( [
			'table' => $this->get_blocked_name(),
			'query' => 'select',
			'where' => [
				'field'     => '`' . $this->distinct_field . '`',
				'operation' => '=',
				'value'     => $this->{'get_' . $this->distinct_field}(),
			],
		] );

		if ( !$q || $q->is_empty() ) {
			$this->a->error( $this->name . ' is empty' );
			return false;
		}

		foreach( $q as $line ) {
			$this->a->q()->log( 1 )->run_query( [
				'table' => $this->name,
				'query' => 'update',
				'set'   => $line,
				'where' => [
					'field'     => '`' . $this->id . '`',
					'operation' => '=',
					'value'     => $line[ $this->id ],
				],
			] );

			$ret |= $this->a->db->countAffected() > 0;
		}

		$this->clear_blocked();

		return $ret;
	}

	protected function clear_blocked() {
		$q = $this->a->q()->log( 1 )->run_query( [
			'table' => $this->get_blocked_name(),
			'query' => 'delete',
			'where' => [
				'field'     => '`' . $this->distinct_field . '`',
				'operation' => '=',
				'value'     => $this->{'get_' . $this->distinct_field}(),
			],
		] );
	}

	protected function check_if_blocked( $name = null ) {
		if ( is_null( $name ) ) {
			$name = $this->name;
		}

		$q = $this->a->q()->log( 1 )->run_query( [
			'table' => $this->get_blocked_name(),
			'where' => [
				[
					'field' => '`' . $this->id . '`',
					'operation' => '=',
					'value'     => $this->get_id(),
				],
				[
					'field'      => '`' . $this->distinct_field . '`',
					'operation' => '=',
					'value'     => self::BLOCKED_TEXT,
				],
			]
		] );

		if ( !$q ) {
			throw new Exception( 'Failed to check if record is blocked' );
		}

		return !$q->is_empty();
	}

	protected function get_blocked_name() {
		return $this->name . self::TMP_SUFFIX;
	}

	protected function block_table_exists() {
		$q = $this->a->db->query( "SHOW TABLES LIKE '" . DB_PREFIX . $this->get_blocked_name() . "'" );

		return $q && $q->num_rows > 0;
	}

	protected function erase_account() {
		if ( !$this->get_id() ) {
			return;
		}

		$this->a->log( 'erasing ' . $this->name );

		$this->a->q()->log( 1 )->run_query( [
			'table' => $this->name,
			'query' => 'delete',
			'where' => [
				'field'     => '`' . $this->id . '`',
				'operation' => '=',
				'value'     => $this->get_id(),
			],
		] );
	}
}
