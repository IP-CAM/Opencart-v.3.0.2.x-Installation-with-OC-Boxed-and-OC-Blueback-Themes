<?php
/**
 * Settings Class
 * @package Advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

class Setting {
	static public function get( $name, Advertikon $a, $default = null ) {
		$value = $a->config->get( self::prefix_name( $name, $a ) );

		if ( is_null( $value ) ) {
			return $default;
		}

		return $value;
	}

	static public function save_all( array $settings, Advertikon $a ) {
		Profiler::record( 'Save settings' );
		$model = $a->load->model( 'setting/setting' );
		$code = self::get_prefix( $a );
		$new_settings = array_merge( $model->getSetting( $code ), $settings	);
		$a->debug( $new_settings );
		$model->editSetting( $code, $new_settings );
		$result = $a->db->countAffected();
		Profiler::record( 'Save settings' );

		return $result;
	}

	static public function set( $name, $value, Advertikon $a ) {
		$serialized = 0;
		$ret = false;
		$name = self::prefix_name( $name, $a );
		$raw_value = $value;
		$code_field = version_compare( VERSION, '2.0.0.0', '=' ) ? 'group' : 'code';
		$store_id = Store::get_current( $a )->get_id();
		$prefix = self::get_prefix( $a );

		if ( !is_scalar( $value ) ) {
			$value = version_compare( VERSION, '2.1.0.1', '>=' ) ? json_encode( $value ) : serialize( $value );
			$serialized = 1;
		}

		if( is_null( $a->config->get( $name ) ) ) {
			$query = $a->db->query(
				"INSERT INTO `" . DB_PREFIX . "setting`
				SET
					`store_id` = " . (int)$store_id . ",
					`$code_field` = '" . $prefix . "',
					`key` = '" . $a->db->escape( $name ) . "',
					`value` = '" . $a->db->escape( $value ) . "',
					`serialized` = $serialized"
			);

			$ret = $a->db->countAffected();

		} else {
			$query = $a->db->query(
				"UPDATE `" . DB_PREFIX . "setting`
				SET
					`value` = '" . $a->db->escape( $value ) . "',
					`serialized` = $serialized
				WHERE `$code_field` = '" . $prefix . "'
					AND `store_id` = " . (int)$store_id . "
					AND `key` = '" . $a->db->escape( $name ) . "'"
			);

			$ret = true;
		}

		if( $ret ) {
			$a->log( "Config: $name = $value" );
			$a->config->set( $name, $raw_value );
		}

		return $ret;
	}

	static public function get_prefix( Advertikon $a ) {
		$ret = $a->code;

		if ( version_compare( VERSION, '3', '>=' ) ) {
			$pos = strpos( $a->type, '/' );

			if ( false !== $pos ) {
				$ret = substr( $a->type, $pos + 1 ) . '_' . $ret;

			} else {
				$ret = $a->type . '_' . $ret;
			}
		}

		return $ret;
	}

	static public function prefix_name( $name = null, Advertikon $a ) {
		$prefix = self::get_prefix( $a );

		if ( $prefix && strpos( $name, $prefix ) !== 0 ) {
			$name = $prefix . '_' . $name;
		}

		return $name;
	}

	static public function strip_prefix( $name = null, Advertikon $a ) {
		$prefix = self::get_prefix( $a );

		if( strpos( $name, $prefix . '_' ) === 0 ) {
			$name = substr( $name , strlen( $prefix ) + 1 );
		}

		return $name;
	}
}
