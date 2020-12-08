<?php
/**
 * Advertikon Translate Trait
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

trait Trait_Translate {
	public function trait_translate_init() {
		$this->a->add_listener( 'locale', [ 'action' => [ $this, 'populate_locale_translate' ] ] );
	}

	public function translate() {
		$text     = $this->a->post( 'text' );
		$language = $this->a->post( 'language' );
		$code     = $this->a->post( 'code' );
		$catalog  = $this->a->post( 'catalog', 0 );
		$ret = [];

		try {
			if ( !$text ) {
				throw new Exception( 'Translation text is missing' );
			}

			if ( !$language ) {
				throw new Exception( 'Language code is missing' );
			}

			if ( !$code ) {
				throw new Exception( 'Text code is missing' );
			}

			$this->a->translator->add_translaton( html_entity_decode( $text ), html_entity_decode( $code ), $language, $catalog );
			$ret['success'] = 'ok';

		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}

		$this->response->setOutput( json_encode( $ret ) );
	}

	public function populate_locale_translate( &$locale ) {
		$locale = array_merge( [
			'languages'    => \Advertikon\Translator::get_languages( $this->a ),
			'translateUrl' => $this->a->u( 'translate' ),
		], $locale );
	}
}
