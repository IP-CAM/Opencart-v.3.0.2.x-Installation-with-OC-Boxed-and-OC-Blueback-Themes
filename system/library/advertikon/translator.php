<?php
/**
 * @package Advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

class Translator {
	protected $a;
	protected $language_code;
	protected $data = [];
	protected $inline_translate = false;
	protected $form_is_rendered = false;
	protected $default_language = 'en-gb';

	public function __construct( Advertikon $a ) {
		$this->a = $a;

		if ( version_compare( VERSION, '2.2.0.0', '<=' ) ) {
			$this->default_language = 'english';
		}

		$this->load();
		$this->inline_translate = Setting::get( 'inline_translate', $a, false );
	}

	/**
	 * Returns translation
	 * @param string $text Translation code
	 * @return string
	 */
	public function get( $text ) {
		$args = func_get_args();
		$translation = $this->translate( $text );

		if ( count( $args ) > 1 ) {
			array_shift( $args );
			array_unshift( $args, $translation );
			$substitution = call_user_func_array( 'sprintf' , $args );

			if ( $substitution ) {
				$translation = $substitution;
			}
		}

		if ( $this->inline_translate ) {
			$this->wrap_inline_translation( $translation, $text );
		}

		return $translation;
	}

	/**
	 * Adds/sets translation to translation file
	 * @param string $text Text
	 * @param string $code Translation code
	 * @param string $language Language code
	 * @param boolean $catalog Forcibly sets catalog as current side
	 */
	public function add_translaton( $text, $code, $language, $catalog ) {
		$file = $this->get_translation_file( $language, $catalog );
		$found = false;
		$this->check_file( $file );

		$lines = explode( "\n", file_get_contents( $file ) );
		$t = str_replace( [ "\r\n", "\r", "\n", ], '<line_break>', $this->escape( $text ) );
		$string = '$' . "_['" . $this->escape( $code ) . "'] = '$t';";

		foreach( $lines as &$line ) {
			if ( preg_match( '/\$_\[\s*(\'|")' . preg_quote( addcslashes( $code, "'" ), '/' ) . '\1\s*\]/', $line ) ) {
				$line = $string;
				$found = true;
				break;
			}
		}

		if ( !$found ) {
			$lines[] = $string;
		}

		$this->a->log( 'Saving translation into ' . $file );

		file_put_contents( $file , implode( "\n", $lines ) );
	}

	protected function escape( $text ) {
		return addcslashes( $text, "\\'" );
	}

	/**
	 * Renders translate control
	 * @param string $code Translation code
	 * @param boolean $catalog Forsibly sets catalog side as current side
	 * @return string
	 */
	public function render_translate_control( $code, $catalog = true ) {
		Profiler::record( 'Render translation control' );
		$translations = $this->get_translations( $code, $catalog );
		$button = $this->a->r( [
			'type'        => 'button',
			'button_type' => 'primary',
			'text_before' => $this->a->__( 'Apply' ),
			'icon'        => 'fa-language',
			'class'       => 'adk-translate-control-button pull-right',
		] );

		$ret = <<<HTML
<div class="adk-translate-control-wrapper">
	<div class="adk-translate-control-view">
		<div class="adk-translate-control-set">
HTML;

		foreach( $translations as $language_code => $translation ) {
			$ret .= '<textarea id="adk-translate-control-input-' . $language_code . 
				'" data-language="' . $language_code . '" data-code="' . $code . '" data-catalog="' . (int)$catalog . '" class="adk-translate-control-input adk-translate-control-active">' .
			$translation . '</textarea>';
		}

		$ret .= <<<HTML
		</div>
	</div>
	<div class="row">
		<div class="col-sm-10 adk-translate-control-select-wrapper">	
HTML;
		if ( count( $translations ) > 1 ) {
			$ret .= <<<HTML
			<select class="select2 form-control adk-translate-control-select col-sm-10" syle="height: 100%"></select>
HTML;
		}

		$ret .= <<<HTML
		</div>
		<div class="adk-translate-control-buttons col-sm-2">
			$button
		</div>
	</div>
</div>
HTML;
		Profiler::record( 'Render translation control' );

		return $ret;
	}

	/**
	 * Returns information for all available system languages
	 * @param Advertikon $a
	 * @return NULL[][]
	 */
	static public function get_languages( Advertikon $a ) {
		$data = [];

		foreach( Language::get_languages( $a ) as $language ) {
			$data[] = [
				'id'    => $language->get_code(),
				'text'  => $language->get_name(),
				'image' => $language->get_flag(),
			];
		}

		return $data;
	}
	
	/**
	 * Fetches translations from all translation files for current side
	 * @param string $key
	 * @param string $language Language code
	 * @param boolean $catalog Forsibly sets catalog as current side
	 * @return string[]
	 */
	public function get_translation( $key, $language, $catalog ) {
		if ( $catalog ) {
			$prefix = dirname( DIR_SYSTEM ) . '/catalog/language/';
			
		} else {
			$prefix = DIR_LANGUAGE;
		}
		
		$code = $this->check_language_code( $language );
		$file = $prefix . $code . '/' . $this->a->full_name . '.php';
		
		if ( file_exists( $file ) ) {
			return $this->get_transation_from_file( $file, $key );
		}

		return '';
	}

	////////////////////////////////////////////////////////////////////////////

	/**
	 * Fetches translations from all translation files for current side
	 * @param string $key
	 * @param boolean $catalog Forsibly sets catalog as current side
	 * @return string[]
	 */
	protected function get_translations( $key, $catalog ) {
		$ret = [];

		if ( $catalog ) {
			$prefix = dirname( DIR_SYSTEM ) . '/catalog/language/';

		} else {
			$prefix = DIR_LANGUAGE;
		}

		foreach( $this->get_languages( $this->a ) as $data ) {
			$real_code = $data['id'];
			$code = $this->check_language_code( $real_code );
			$file = $prefix . $code . '/' . $this->a->full_name . '.php';

			if ( file_exists( $file ) ) {
				$ret[ $real_code ] = $this->get_transation_from_file( $file, $key );

			} else {
				$ret[ $real_code ] = '';
			}
		}

		return $ret;
	}

	/**
	 * Fetches translation from translation file
	 * @param string $file
	 * @param string $key
	 * @return string
	 */
	protected function get_transation_from_file( $file, $key ) {
		$fp = fopen( $file, 'r' );
		$translation = '';

		while( false !== ( $line = fgets( $fp ) ) ) {
			if ( $translation = $this->get_translation_from_line( $line, $key ) ) {
				break;
			}
		}

		fclose( $fp );

		return $translation;
	}

	/**
	 * Fetches translation from line of translation file
	 * @param string $line
	 * @param string $key
	 * @return string
	 */
	protected function get_translation_from_line( $line, $key ) {
		$pattern = '$_[\'' . $key . '\']';

		if( $pattern === substr( $line, 0, strlen( $pattern ) ) ) {
			$start = strpos( $line, '=' );
			$end = strrpos( $line, ';' );

			if ( false === $start || false === $end ) {
				$this->a->error( 'Corrupted translation line: ' . $line );
				return '';
			}

			$translation = substr( $line, $start + 1,  $end - $start - 1 );

			return stripslashes( str_replace( '<line_break>', "\r\n", trim( trim( $translation ), "'" ) ) );
		}

		return '';
	}

	/**
	 * Normilizes language code
	 * @param string $code
	 * @return string
	 */
	protected function check_language_code( $code ) {
		if (  'en' === $code && version_compare( VERSION, '2.2.0.0', '<' ) ) {
			return 'english';
		}

		return $code;
	}

	/**
	 * Returns current translation file
	 * @param string $language Language code
	 * @param boolean $catalog Forcibly sets catalog side as current side
	 * @return string
	 */
	protected function get_translation_file( $language, $catalog ) {
		if ( $catalog ) {
			$prefix = dirname( DIR_SYSTEM ) . '/catalog/language/';

		} else {
			$prefix = DIR_LANGUAGE;
		}

		$this->check_language_code( $language );
		$file = $prefix . $language . '/' . $this->a->full_name . '.php';
		$default_file = $prefix . $this->default_language . '/' . $this->a->full_name . '.php';

		if ( file_exists( $file ) ) {
			return $file;
		}

		if ( !is_dir( dirname( $file ) ) ) {
			if ( false === @mkdir( dirname( $file ), 0775, true ) ) {
				throw new Exception( 'Failed to create directory ' . dirname( $file ) );
			}
		}

		if ( !is_writable( dirname( $file ) ) ) {
			throw new Exception( "Folder " . dirname( $file ) . " is not writable" );
		}

		if ( file_exists( $default_file ) ) {
			if ( !is_readable( $default_file ) ) {
				throw new Exception( "File $default_file is not readable" );
			}

			$this->a->log( 'Translation file ' . $file . ' is missing. Creating it as a copy of ' . $default_file );

			$content = file_get_contents( $default_file );

			if ( false === $content ) {
				throw new Exception( 'Failed to read file ' . $default_file );
			}

			if( false === file_put_contents( $file , $content ) ) {
				throw new Exception( "Failed to write into file $file" );
			}

			return $file;
		}

		$this->a->log( 'Translation file ' . $file . ' is missing. Creating empty file' );
		
		file_put_contents( $file , "<?php\n" );
		
		return $file;
	}

	/**
	 * Translates string
	 * @param string $text
	 * @return string
	 */
	protected function translate( $text ) {
		if ( isset( $this->data[ $text ] ) ) {
			return str_replace( '<line_break>', "\r\n", $this->data[ $text ] );
		}

		return $text;
	}

	/**
	 * Loads translation files
	 */
	protected function load() {
		if ( 'Advertikon\Advertikon' === get_class( $this->a ) ) {
			return;
		}

		$code = $this->a->language()->get_code();
		$this->language_code = 'en' === $code ? 'english' : $code;
		$file = DIR_LANGUAGE . $this->language_code . '/' . $this->a->full_name . '.php';
		$default_file = DIR_LANGUAGE . $this->default_language . '/' . $this->a->full_name . '.php';
		$_ = [];

		if ( file_exists( $default_file ) ) {
			require( $default_file );
		}

		if ( file_exists( $file ) ) {
			require( $file );
		}

		$this->data = $_;
	}

	/**
	 * Wraps translated string so it can be transalted
	 * @param string $text
	 * @param string $code
	 */
	protected function wrap_inline_translation( &$text, $code ) {
		$t = $text;
		$text = '<i class="adk-translate-item" data-language="' . $this->language_code . '" data-code="' . htmlspecialchars( $code ) .
				'" data-url="' . $this->a->u( 'translate' ) .  '">' .
					'<u class="adk-translate-text">' . $t . '</u>' .
					'<i class="adk-translate-check"' .
						' style="background-image: url(' . $this->a->u()->catalog_url( true )  .
						'image/catalog/advertikon/edit.png )"></i>' .
				'</i>';

		if ( !$this->form_is_rendered ) {
			$text .= $this->render_form();
			$this->form_is_rendered = true;
		}
	}

	/**
	 * Renders HTML string for translation form
	 * @return string
	 */
	protected function render_form() {
		return <<<HTML
<div class="adk-form-translate">
	<textarea class="adk-translate-field adk-translate-original" readonly="true"></textarea>
	<div class="adk-translate-buttons">
		<button type="button" class="adk-translate-button-copy">Copy&nbsp;<b>&dHar;</b></button>
		<button type="button" class="adk-translate-button-apply">Apply&nbsp;<b>&check;</b></button>
		<button type="button" class="adk-translate-button-close">Close&nbsp;<b>&cross;</b></button>
	</div>
	<textarea class="adk-translate-field adk-translate-new"></textarea>
</div>
HTML;
	}
	
	/**
	 * Checks if file is readable and writable
	 * @param string $file
	 * @throws Exception
	 */
	protected function check_file( $file ) {
		if ( !is_readable( $file ) ) {
			throw new Exception( sprintf( 'The process has no read permission on file %s', $file ) );
		}
		
		if ( !is_writable( $file ) ) {
			throw new Exception( sprintf( 'The process has no write permission on file %s', $file ) );
		}
	}

}
