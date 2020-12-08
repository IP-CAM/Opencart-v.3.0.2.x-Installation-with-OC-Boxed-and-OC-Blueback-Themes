<?php
/**
 * Amin model
 * @package adk_gdpr
 * @author Advertikon
 * @version 1.1.53
 */

/**
 * Class ModelExtensionModuleAdkGdpr
 * @property DB $db
 * @property Request $request
 * @property Response $response
 * @property Config $config
 * @property Document $document
 * @property Loader $load
 * @property Language $language
 * @property Session $session
 * @property Url $url
 * @property \Cart\Customer $customer
 * @property \Cart\Cart $cart
 * @property \Cart\Currency $currency
 * @property Log $log
 */
class ModelExtensionModuleAdkGdpr extends Model {
	/**
	 * @var \Advertikon\Adk_Gdpr\Advertikon
	 */
	protected $a = null;

	/**
	 * Class constructor
	 * @param Registry $registry
	 */
	public function __construct( Registry $registry ) {
		parent::__construct( $registry );
		$this->a = Advertikon\ADK_Gdpr\Advertikon::instance();
	}

	/**
	 * Returns LOCALE data
	 * @return string
	 * @throws \Advertikon\Exception
	 */
	public function get_locale() {
		return json_encode( array(
			'translateUrl'   => $this->a->u( 'translate' ),
			'requestUrl'     => $this->a->u( 'gdpr_request' ),
			'networkError'              => $this->a->__( 'Network error' ),
			'parseError'                => $this->a->__( 'Unable to parse server response string' ),
			'undefServerResp'           => $this->a->__( 'Undefined server response' ),
			'serverError'               => $this->a->__( 'Server error' ),
			'sessionExpired'            => $this->a->__( 'Current session has expired' ),
			'scriptError'               => $this->a->__( 'Script error' ),
			'accountUrl'                => $this->url->link( 'account/account' ),
			'modalHeader'               => 'aGDPR',
			'no'                        => $this->a->__( 'No' ),
			'yes'                       => $this->a->__( 'Yes' ),
			'emptyEmail' => $this->a->__( 'Email address may not be empty' ),
		) ) . PHP_EOL;
	}
}
