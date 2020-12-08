<?php
/**
 * Shortcode class
 * @author Advertikon
 * @package adk-gdpr
 * @version 1.1.53
 */

namespace Advertikon\Adk_Gdpr;

class Shortcode extends \Advertikon\Shortcode {
	
	public function __construct(\Advertikon\Advertikon $a) {
		parent::__construct($a);
		
		$this->shortcode_set = array_merge( array(
			'authorize_url' => [
				'callback'    => array( $this, 'shortcode_authorize_url' ),
				'hint'        => 'authorize_url',
				'description' => $this->a->__( 'Link to ap page to authorize GDPR request' ),
				'context'     => [ $this->a->__( 'Customer' ), $this->a->__( 'Dashboard'), $this->a->__( 'Affiliate' ) ],
			],
			'reject_reason' => [
					'callback'    => array( $this, 'shortcode_reject_reason' ),
					'hint'        => 'reject_reason',
					'description' => $this->a->__( 'Describes the reason of the data management request rejection' ),
					'context'     => [ $this->a->__( 'Customer' ), $this->a->__( 'Dashboard'), $this->a->__( 'Affiliate' ) ],
			],
			'cookie_policy_url' => [
					'callback'    => array( $this, 'shortcode_cookie_policy_url' ),
					'hint'        => 'cookie_policy_url(Text)',
					'description' => $this->a->__( 'Link to the cookie policy page' ),
					'context'     => [ $this->a->__( 'Customer' ), $this->a->__( 'Dashboard'), $this->a->__( 'Affiliate' ) ],
			],
		), $this->shortcode_set );
	}

	protected function shortcode_authorize_url() {
		return $this->a->u( 'authorize', [ 'code' => Request::$authorize_code ] );
	}
	
	protected function shortcode_reject_reason() {
		return '(' . implode( ', ', Request::$rejection_reason ) . ')';
	}

	protected function shortcode_cookie_policy_url() {
		$ret = '';
		$args = func_get_args();
		array_shift( $args );

		$text = isset( $args[ 0 ] ) ? $args[ 0 ] : $this->a->__( 'Cookie Policy');
		$info_id = \Advertikon\Setting::get( 'cookie_policy', $this->a );

		if ( $info_id ) {
			$ret = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				$this->a->u()->catalog_url() . 'index.php?route=information/information&information_id=' . $info_id,
				$text
			);
		}

		return $ret;
	}
}
