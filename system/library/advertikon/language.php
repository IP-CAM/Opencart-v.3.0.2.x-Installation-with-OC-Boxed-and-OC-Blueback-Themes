<?php

/**
 * @package Advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

/**
 * Language class
 *
 * @author Advertikon
 */
class Language {
	protected $a;
	protected $id        = '';
	protected $code      = '';
	protected $name      = '';
	protected $locale    = '';
	protected $image     = '';
	protected $directory = '';
	protected $flag      = '';

	private static $languages = [];
	private static $admin;

	/**
	 * Returns system languages
	 * @return Language[]
	 */
	static public function get_languages( Advertikon $a ) {
		if ( !self::$languages ) {
			$data = $a->q( [
				'query' => 'select',
				'table' => 'language'
			] );

			if ( !$data ) {
				return [];
			}

			foreach( $data as $l ) {
				self::$languages[ $l['code'] ] = new Language( $a, null, $l );
			}
		}
		
		return self::$languages;
	}

    /**
     * @param Advertikon $a
     * @return Language
     */
    static public function get_admin(Advertikon $a ) {
		if ( self::$admin ) {
			return self::$admin;
		}

		$code = $a->config->get( 'config_admin_language' );
		self::$admin = new self( $a, $code );

		return self::$admin;
	}

	public function __construct( Advertikon $a, $code = null, array $data = null ) {
		$this->a = $a;

		if ( !is_null( $data ) ) {
			$this->set_language( $data );
			return; 
		}

		if ( is_null( $code ) ) {
			$code = $this->detect_language_code();
		}
		
		$this->set_language_by_code( $code );
	}
	
	/**
	 * Returns language code
	 * @return string
	 */
	public function get_code() {
		return $this->code;
	}
	
	/**
	 * Returns language ID
	 * @return integer
	 */
	public function get_id() {
		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_locale() {
		return $this->locale;
	}

	public function get_image() {
		return $this->image;
	}

	public function get_directory() {
		return $this->directory;
	}

	public function get_flag() {
		return $this->flag;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Detects code of current language
	 * @return string
	 */
	protected function detect_language_code() {
		$code = '';

		if ( $this->a->is_admin() ) {
			$code = $this->a->config->get( 'config_language' );

		} else {
			if ( isset( $this->a->session->data['language'] ) ) {
				$code = $this->a->session->data['language'];
			}
		}
		
		return $code;
	}
	
	/**
	 * Initializes object by language code
	 * @param string $code Language code
	 */
	protected function set_language_by_code( $code ) {
		if ( !$code ) {
			return;
		}
		
		foreach( self::get_languages( $this->a ) as $l ) {
			if ( $l->get_code() === $code ) {
				$this->set_by_clone( $l );
			}
		}
	}

	protected function get_flag_url() {
		$url = '';

		if ( version_compare( VERSION, '2.3.0.0', '>=' ) ) {
			if ( !$this->code ) {
				$this->a->error( 'Language data set is missing language code' );
				return '';
			}

			$path = DIR_LANGUAGE . $this->code . '/'. $this->code . '.png';

		} else { 
			if ( !$this->image ) {
				$this->a->error( 'Language data set is missing language image' );
				return '';
			}

			if( version_compare( VERSION, '2.2.0.0', '=' ) && 'gb.png' === $this->image ) {
				$this->image = 'england.png';
			}

			$path = DIR_IMAGE . 'flags/' . $this->image;

			if ( !is_file( $path ) ) {
				$path = DIR_LANGUAGE . $this->code . '/'. $this->code . '.png';
			}
		}

		if ( is_file( $path ) ) {
			$url = $this->a->get_store_url() . substr( $path , strlen( dirname( DIR_SYSTEM ) ) + 1 );
		}

		return $url;
	}
	
	/**
	 * Initializes object with data
	 * @param array $data Language data as it get from DB
	 */
	protected function set_language( array $data ) {
		$this->id        = $data['language_id'];
		$this->code      = $data['code'];
		$this->name      = $data['name'];
		$this->locale    = $data['locale'];
		$this->image     = $data['image'];
		$this->directory = $data['directory'];
		$this->flag      = $this->get_flag_url();
	}

	protected function set_by_clone( Language $sample ) {
		$this->id        = $sample->get_id();
		$this->code      = $sample->get_code();
		$this->name      = $sample->get_name();
		$this->locale    = $sample->get_locale();
		$this->image     = $sample->get_image();
		$this->directory = $sample->get_directory();
		$this->flag      = $sample->get_flag();
	}
}
