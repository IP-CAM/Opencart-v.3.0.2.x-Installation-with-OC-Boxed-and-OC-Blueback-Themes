<?php

/**
 * @package advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

/**
 * Profiler
 *
 * @author Advertikon
 */
class Profiler {
	private static $log = [];
	
	private static $profiles = [];
	
	private static $logger;
	
	private static $time_start;
	
	private static $memory_start;
	
	private static $instance;
	
	private static $is_defer_print = true;
	
	private static $lines = [];
	
	private static $time_caption   = 'Time:               ';
	private static $memory_caption = 'Memory:             ';
	private static $print_now = false;
	private static $suppressed = false;
	
	public static function init( $print_now = false ) {
		self::$time_start = microtime( true );
		self::$memory_start = memory_get_usage();
		$count = 37;
		self::$print_now = $print_now;

		self::print_line( sprintf(
			str_repeat( '<', $count ) . " Memory limit: %5s, Initial memory usage: %5s " . str_repeat( '>', $count ),
			ini_get( 'memory_limit' ),
			self::memory_format( self::$memory_start )
		) );
		
		self::$instance = new self;
	}
	
	public static function record( $code ) {
		if ( !self::$profiles || end( self::$profiles )['code'] !== $code ) {
			self::$profiles[] = [
				'time'   => microtime( true ),
				'memory' => memory_get_usage(),
				'depth'  => count( self::$profiles ),
				'code'   => $code,
			];

		} else {
			$memory = memory_get_usage();
			$item = array_pop( self::$profiles );

			self::$lines[] = [
				'time'         => microtime( true ) - $item['time'],
				'memory'       => $memory - $item['memory'],
				'total_memory' => $memory - self::$memory_start,
				'depth'        => $item['depth'],
				'code'         => $code,
			];

			if ( self::$logger && self::$print_now ) {
				self::print_records();
			}
		}
	}
	
	public static function set_logger( $logger ) {
		self::$logger = $logger;
	}

	public static function suppress() {
		self::$suppressed = true;
	}
	
	private static function memory_format( $value ) {
		$power = log( $value, 10 );
		
		if ( $power >= 6 ) {
			$ret = number_format( $value / 1000000, 2 ) . ' MB';

		} else if ( $power >= 3 ) {
			$ret = number_format( $value / 1000, 2 ) . ' KB';

		} else {
			$ret = $value . ' B';
		}
		
		return $ret;
	}
	
	private static function print_line( $line ) {
		self::$log[] = $line . "\n";
	}
	
	private static function format_lines( $total_time, $total_memory ) {
		while( self::$lines ) {
			$item = array_shift( self::$lines );

			self::print_line( sprintf(
				"%60.60s - %s%2.5f, %s%10s, Total memory: %10s",
				$item['code'] . str_repeat( ' < ', $item['depth'] ),
				$item['depth'] === 0 ? self::make_bar( self::$time_caption, $item['time'], $total_time ) : self::$time_caption,
				$item['time'],
				$item['depth'] === 0 ? self::make_bar( self::$memory_caption, $item['memory'], $total_memory ) : self::$memory_caption,
				self::memory_format( $item['memory'] ),
				self::memory_format( $item['total_memory'] )
			) );
		}
	}
	 
	protected static function make_bar( $line, $value, $total_value ) {
		if ( $total_value == 0 || $value < 0 ) {
			$count = 0;

		} else {
			$count = floor( $value / $total_value * strlen( $line ) );
		}

		return "\e[41m" . substr( $line, 0 , $count ) . "\e[49m" . substr( $line, $count );
	}
	
	public function __destruct() {
		if ( self::$suppressed ) {
			return;
		}

		$count = 41;
		$total_time = microtime( true ) - self::$time_start;
		$total_memory = memory_get_usage() - self::$memory_start;

		self::print_records( sprintf(
			str_repeat( '>', $count ) . " Total time: %2.4f, Total memory: %10s " . str_repeat( '<', $count ),
			$total_time,
			self::memory_format( $total_memory )
		) );
	}

	static public function print_records( $last_line = '' ) {
		$total_time = microtime( true ) - self::$time_start;
		$total_memory = memory_get_usage() - self::$memory_start;

		self::format_lines( $total_time, $total_memory );
		self::print_line( $last_line );
		
		if ( self::$logger ) {
			while( self::$log ) {
				self::$logger->print_profile( array_shift( self::$log ) );
			}
		}
	}
}
