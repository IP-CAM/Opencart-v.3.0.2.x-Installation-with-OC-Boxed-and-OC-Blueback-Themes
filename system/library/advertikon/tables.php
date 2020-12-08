<?php
/**
 * @package adk_import
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

/**
 * Class to maintain information about DB tables
 *
 * @author Advertikon
 */
class Tables {
	static protected $tables = [];

	static public function get_tables( Advertikon $a ) {
		if ( !self::$tables ) {
			self::read_tables( $a );
		}

		return self::$tables;
	}

	static public function get_tables_name( Advertikon $a ) {
		if ( !self::$tables ) {
			self::read_tables( $a );
		}

		return array_keys( self::$tables );
	}

	static public function table_exists( $table, Advertikon $a ) {
		return in_array( $table, self::get_tables_name( $a ) ); 
	}

	static public function get_table( $table_name, Advertikon $a ) {
		if ( !self::table_exists( $table_name, $a ) ) {
			throw new Exception( sprintf( 'Table %s does not exist', $table_name ) );
		}

		return self::$tables[ $table_name ];
	}

	static public function normalize( $table_name, array &$data, Advertikon $a ) {
		$table = self::get_table( $table_name, $a )->normalize( $data );
	}

	////////////////////////////////////////////////////////////////////////////////////////////////

	static protected function read_tables( Advertikon $a ) {
		$q = $a->db->query( "show tables" );

		if ( !isset( $q->rows ) ) {
			throw new Exception( 'Failed to read tables list' );
		}

		self::$tables = [];

		foreach( $q->rows as $t ) {
			$table = reset( $t );
			self::$tables[ $table ] = new TTable( $table, $a );
		}
	}
}

class TTable {
	protected $name;
	protected $fields = [];
	protected $a;

	public function __construct( $table, Advertikon $a ) {
		$this->name = $table;
		$this->a = $a;
		$this->populate_fields();
	}

	public function field_exists( $field_name ) {
		return array_key_exists( $field_name, $this->fields );
	}

	public function normalize( array &$data ) {
		$this->remove_unexistend( $data );
		$this->set_defaults( $data );
	}

	public function get_fields() {
		return $this->fields;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////

	protected function set_defaults( array &$data ) {
		foreach( $this->fields as $field ) {
			if ( !array_key_exists( $field->get_name() , $data ) ) {
				$data[ $field->get_name() ] = $field->get_default();
			}
		}
	}

	protected function remove_unexistend( array &$data ) {
		foreach ( $data as $key => $value ) {
			if ( !$this->field_exists( $key ) ) {
				unset( $data[ $key ] );
			}
		}
	}

	protected function populate_fields() {
		$q = $this->a->db->query( 'show fields in ' . $this->name );

		foreach( $q->rows as $line ) {
			$this->fields[ $line['Field'] ] = new Field( $line, $this->a );
		}
	}
}

class Field {
	protected $name;
	protected $type;
	protected $is_null;
	protected $default;
	protected $a;

	const TYPE_NUMERIC  = 1;
	const TYPE_STRING   = 2;
	const TYPE_DATE     = 3;
	const TYPE_DATETIME = 5;
	const TYPE_ENUM     = 6;

	public function __construct( array $data, Advertikon $a ) {
		$this->name    = $data['Field'];
		$this->type    = $this->get_type( $data['Type'] );
		$this->is_null = 'Null' === $data['Null'];
		$this->default = $data['Default'];

		$this->a       = $a;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_default() {
		if ( !is_null( $this->default ) ) {
			return $this->default;
		}

		if ( $this->is_null ) {
			return 'NULL';
		}

		switch( $this->type ) {
			case self::TYPE_NUMERIC:
				return 0;
			break;
			case self::TYPE_STRING:
				return '';
			break;
			case self::TYPE_DATE:
				return '0000-00-00 00:00:00';
			break;
			case self::TYPE_DATETIME:
				return '0000-00-00';
			break;
			case self::TYPE_ENUM:
				return '';
			break;
			default:
				throw new Exception( 'Undefined data type of field ' . $this->name );
		}
	}

	public function to_string() {
		$ret = "\n";
		$ret .= 'Name   : ' . $this->name . "\n";
		$ret .= 'Type   : ' . $this->type . "\n";
		$ret .= 'Is NULL: ' . $this->is_null . "\n";
		$ret .= 'Default: ' . $this->default . "\n\n";

		return $ret;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////

	protected function get_type( $raw_type ) {
		if ( strpos( $raw_type, 'int') !== false ) {
			return self::TYPE_NUMERIC;
		}

		if ( strpos( $raw_type, 'decimal') !== false ) {
			return self::TYPE_NUMERIC;
		}

		if ( strpos( $raw_type, 'double') !== false ) {
			return self::TYPE_NUMERIC;
		}

		if ( strpos( $raw_type, 'float') !== false ) {
			return self::TYPE_NUMERIC;
		}

		if ( strpos( $raw_type, 'char') !== false ) {
			return self::TYPE_STRING;
		}

		if ( strpos( $raw_type, 'text') !== false ) {
			return self::TYPE_STRING;
		}

		if ( strpos( $raw_type, 'blob') !== false ) {
			return self::TYPE_STRING;
		}

		if ( $raw_type === 'date' ) {
			return self::TYPE_DATE;
		}

		if ( $raw_type === 'datetime' || $raw_type === 'timestamp' ) {
			return self::TYPE_DATETIME;
		}

		if ( strpos( $raw_type, 'enum') !== false ) {
			return self::TYPE_ENUM;
		}

		throw new Exception( sprintf( 'Failed to detect type for field %s', $raw_type ) );
	}
}
