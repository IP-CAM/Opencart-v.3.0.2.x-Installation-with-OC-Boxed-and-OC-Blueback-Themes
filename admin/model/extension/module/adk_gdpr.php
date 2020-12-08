<?php
/**
 * Admin model
 * @package adk_gdpr
 * @author Advertikon
 * @version 1.1.53
 */

/**
 * Class ModelExtensionModuleAdkGdpr
 * @property DB $db
 * @property Request $request
 * @property Response $response
 * @property Registry $registry
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

    /** @var \Advertikon\Adk_Gdpr\Advertikon  */
	protected $a = null;

	/**
	 * Class constructor
	 * @param Object $registry 
	 */
	public function __construct( Registry $registry ) {
		parent::__construct( $registry );
		$this->a = Advertikon\ADK_Gdpr\Advertikon::instance();
	}

	/**
	 * Adds tables
	 */
	public function create_tables() {
		$this->db->query( "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->a->tables['terms_acceptance'] . "`
			(`term_acceptance_id` INT UNSIGNED AUTO_INCREMENT KEY,
			 `term_name` VARCHAR(60) NOT NULL,
			 `term_version_id` INT UNSIGNED NOT NULL,
			 `date` TIMESTAMP,
			 `name` VARCHAR(40) NOT NULL,
			 `email` VARCHAR(40) NOT NULL,
			 `status` TINYINT UNSIGNED DEFAULT " . \Advertikon\ADK_Gdpr\Term::STATUS_ACTIVE . " NOT NULL,
			 `withdrawal` DATETIME NOT NULL,
			 `identifier` INT UNSIGNED NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin" );
		
		$this->a->log( 'Table ' . $this->a->tables['terms_acceptance'] . ' is created' );
		
		$this->db->query( "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->a->tables['terms_version'] . "`
			(`term_version_id` INT UNSIGNED AUTO_INCREMENT KEY,
			 `term_name` VARCHAR(60) NOT NULL,
			 `title` VARCHAR(255) NOT NULL,
			 `text` VARCHAR(60000) NOT NULL,
			 `date` TIMESTAMP
			) ENGINE=InnoBD DEFAULT CHARSET=utf8 COLLATE=utf8_bin" );
		
		$this->a->log( 'Table ' . $this->a->tables['terms_version'] . ' is created' );

		$this->db->query( "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->a->tables['request_table'] . "`
			(`request_id` INT UNSIGNED AUTO_INCREMENT KEY,
			 `type` TINYINT UNSIGNED NOT NULL,
			 `status` TINYINT UNSIGNED NOT NULL,
			 `date_added` TIMESTAMP,
			 `date_done` DATETIME,
			 `email` VARCHAR(40) NOT NULL,
			 `name` VARCHAR(40) NOT NULL,
			 `store_id` TINYINT UNSIGNED NOT NULL,
			 `language_code` VARCHAR(6) NOT NULL,
			 `code` VARCHAR(30) NOT NULL,
			 `expire` DATETIME NOT NULL,
			 `ip` VARCHAR(20) NOT NULL,
			`reject_reason` VARCHAR(200) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin" );
		
		$this->a->log( 'Table ' . $this->a->tables['request_table'] . ' is created' );

		$this->db->query( "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->a->tables['breach_log'] . "`
			(`breach_id` INT UNSIGNED AUTO_INCREMENT KEY,
			 `fact` VARCHAR(5000) NOT NULL,
			 `effect` VARCHAR(5000) NOT NULL,
			 `remedy` VARCHAR(5000) NOT NULL,
			 `date` TIMESTAMP
			) ENGINE=InnoBD DEFAULT CHARSET=utf8 COLLATE=utf8_bin" );
		
		$this->a->log( 'Table ' . $this->a->tables['breach_log'] . ' is created' );
	}

	public function save_config( array $config ) {
		$ret = new Advertikon\Exception\Result();

		if ( !$config ) {
			return $ret;
		}

		try {
			$this->validate_config( $config );
			$count = Advertikon\Setting::save_all( $config, $this->a );
			$ret->set_count( $count );
			
		} catch ( Advertikon\Exception\Result $e ) {
			return $e;
		}

		return $ret;
	}

	protected function validate_config( array &$config ) {
		
	}

    /**
     * Renders controls
     * @param array $data
     * @throws \Advertikon\Exception
     */
	public function get_controls( $w ) {
		$data = &$w['data'];

		$data['is_extended'] = class_exists( 'Advertikon\Adk_Gdpr\Extended' );

		// Status
		$name = 'status';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Status' ),
			'element' => $this->a->r()->render_fancy_checkbox( [
				'value' => Advertikon\Setting::get( $name, $this->a ),
				'id'    => $name,
				'name'  => $name,
				'class' => 'config-control'
			] ),
		] );

		// Inline translation
		$name = 'inline_translate';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Inline Translator' ),
			'element' => $this->a->r()->render_fancy_checkbox( [
				'value' => Advertikon\Setting::get( $name, $this->a ),
				'id'    => $name,
				'name'  => $name,
				'class' => 'config-control'
			] ),
			'tooltip' => $this->a->__( 'The Inline Translator makes it possible to add/edit translatable captions '
				. 'right in a browser window (backend as well las frontend). Reload the page in order to the mode change take effect.'
				. ' Translatable fields will be highlighted with underscore and color. '
				. 'Select field and in the pop-up form add/edit translation and click the Apply button' ),
		] );

		$name = 'track_account_terms';
		$$name = [
			'label' => $this->a->__( 'Account Terms' ),
			'type'  => 'form_group',
				'element' => $this->a->r()->render_fancy_checkbox( [
				'value'   => Advertikon\Setting::get( $name, $this->a ),
				'id'      => $name,
				'class'   => 'config-control track-term',
			] ),
		];

		$name = 'track_checkout_terms';
		$$name = [
			'label' => $this->a->__( 'Checkout Terms' ),
			'type'  => 'form_group',
				'element' => $this->a->r()->render_fancy_checkbox( [
				'value'   => Advertikon\Setting::get( $name, $this->a ),
				'id'      => $name,
				'class'   => 'config-control track-term',
			] ),
		];

		$name = 'track_affiliate_terms';
		$$name = [
			'label' => $this->a->__( 'Affiliate Terms' ),
			'type'  => 'form_group',
				'element' => $this->a->r()->render_fancy_checkbox( [
				'value'   => Advertikon\Setting::get( $name, $this->a ),
				'id'      => $name,
				'class'   => 'config-control track-term',
			] ),
		];

		$name = 'track_return_terms';
		$$name = [
			'label' => $this->a->__( 'Return Terms' ),
			'type'  => 'form_group',
				'element' => $this->a->r()->render_fancy_checkbox( [
				'value'   => Advertikon\Setting::get( $name, $this->a ),
				'id'      => $name,
				'class'   => 'config-control track_term',
			] ),
		];

		$data['track_terms'] = $this->a->r( [
			'type'    => 'fieldset',
			'title'   => $this->a->__( 'Terms acceptance to be tracked' ),
			'class'   => 'form-horizontal',
			'element' => [
				$track_account_terms,
				$track_checkout_terms,
				$track_affiliate_terms,
				$track_return_terms,
			],
		] );

		// Add to the left panel
		$name = 'in_left_panel';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Add to the left panel' ),
			'element' => $this->a->r()->render_fancy_checkbox( [
				'value' => Advertikon\Setting::get( $name, $this->a ),
				'id'    => $name,
				'name'  => $name,
				'class' => 'config-control'
			] ),
			'tooltip' => $this->a->__( 'Add the link to the admin area GDPR tools to the left panel of the admin area' ),
		] );
		
		// Add to the account
		$name = 'in_account';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Add to account' ),
			'element' => $this->a->r()->render_fancy_checkbox( [
				'value' => Advertikon\Setting::get( $name, $this->a ),
				'id'    => $name,
				'name'  => $name,
				'class' => 'config-control'
			] ),
			'tooltip' => $this->a->__( 'Add the link to the GDPR tools page to the customer\'s account page' ),
		] );
		
		// Add to the account
		$name = 'in_footer';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Add to footer' ),
			'element' => $this->a->r()->render_fancy_checkbox( [
				'value' => Advertikon\Setting::get( $name, $this->a ),
				'id'    => $name,
				'name'  => $name,
				'class' => 'config-control'
			] ),
			'tooltip' => $this->a->__( 'Add the link to the GDPR tools page to "My account" top bar section' ),
		] );
		
		// Add to the account
		$name = 'in_header';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Add to header' ),
			'element' => $this->a->r()->render_fancy_checkbox( [
				'value' => Advertikon\Setting::get( $name, $this->a ),
				'id'    => $name,
				'name'  => $name,
				'class' => 'config-control'
			] ),
			'tooltip' => $this->a->__( 'Add the link to the GDPR tools page to "My account" header drop-down' ),
		] );

		$translator = new \Advertikon\Translator( $this->a );

		// Information request confirmation email
		$data['data_request_confirmation_template'] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Information request confirmation' ),
			'element' => $translator->render_translate_control( 'caption_email_information_confirm' ),
			'tooltip' => $this->a->__( 'Email template to send to a customer in response to a request to provide a copy of his/her personal data' ) .
				$this->show_supported_shortcodes(),
		] );

		// Data block processing email
		$data['data_stop_confirmation_template'] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Restrict processing confirmation' ),
			'element' => $translator->render_translate_control( 'caption_email_stop_confirm' ),
			'tooltip' => $this->a->__( 'Email template to send to a customer in response to a request to stop processing his/her personal data' ) .
				$this->show_supported_shortcodes(),
		] );

		// Data unblock processing email
		$data['data_unstop_confirmation_template'] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Unblock processing confirmation' ),
			'element' => $translator->render_translate_control( 'caption_email_unstop_confirm' ),
			'tooltip' => $this->a->__( 'Email template to send to a customer in response to a request to lift data processing restriction personal data' ) .
				$this->show_supported_shortcodes(),
		] );

		// Data erase email
		$data['data_erase_confirmation_template'] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Erase confirmation' ),
			'element' => $translator->render_translate_control( 'caption_email_erase_confirm' ),
			'tooltip' => $this->a->__( 'Email template to send to a customer in response to a request to erase personal data' ) .
				$this->show_supported_shortcodes(),
		] );

		// Consent withdrawal email
		$data['data_withdraw_confirmation_template'] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Consent withdrawal confirmation' ),
			'element' => $translator->render_translate_control( 'caption_email_withdraw_confirm' ),
			'tooltip' => $this->a->__( 'Email template to send to a customer in response to a request to withdraw consent to data personal data processing' ) .
				$this->show_supported_shortcodes(),
		] );
		
		// Data erasure fulfillment email
		$data['data_erasure_done_template'] = $this->a->r()->render_form_group( [
				'label'   => $this->a->__( 'Data erasure notification' ),
				'element' => $translator->render_translate_control( 'caption_email_erasure_done' ),
				'tooltip' => $this->a->__( 'Email template to send to a customer as confirmation of data erasure request fulfillment' ) .
				$this->show_supported_shortcodes(),
		] );
		
		// Data block fulfillment email
		$data['data_block_done_template'] = $this->a->r()->render_form_group( [
				'label'   => $this->a->__( 'Data blocking notification' ),
				'element' => $translator->render_translate_control( 'caption_email_block_done' ),
				'tooltip' => $this->a->__( 'Email template to send to a customer as confirmation of data processing blocking request fulfillment' ) .
				$this->show_supported_shortcodes(),
		] );
		
		// Data unblocking fulfillment email
		$data['data_unblock_done_template'] = $this->a->r()->render_form_group( [
				'label'   => $this->a->__( 'Data unblocking notification' ),
				'element' => $translator->render_translate_control( 'caption_email_unblock_done' ),
				'tooltip' => $this->a->__( 'Email template to send to a customer as confirmation of data processing blocking cancelation request fulfillment' ) .
				$this->show_supported_shortcodes(),
		] );
		
		// Data withdraw fulfillment email
		$data['data_withdraw_done_template'] = $this->a->r()->render_form_group( [
				'label'   => $this->a->__( 'Consent withdrawal notification' ),
				'element' => $translator->render_translate_control( 'caption_email_withdraw_done' ),
				'tooltip' => $this->a->__( 'Email template to send to a customer as confirmation of concent withdrawal request fulfillment' ) .
				$this->show_supported_shortcodes(),
		] );

		// No Information reply email
		$data['data_request_missed_template'] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( '"No data" response' ),
			'element' => $translator->render_translate_control( 'caption_email_information_missed' ),
			'tooltip' => $this->a->__( 'Email template to send as a response on data management request in case if there are no corresponding data entries' ) . 
				$this->show_supported_shortcodes(),
		] );

		// Information reply email
		$data['data_request_reply_template'] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Information reply' ),
			'element' => $translator->render_translate_control( 'caption_email_information' ),
			'tooltip' => $this->a->__( 'The email template to send in reply to a request to access personal data' ) .
				$this->show_supported_shortcodes(),
		] );

		// Reject email
		$data['data_request_reject_template'] = $this->a->r()->render_form_group( [
			'label'   => $this->a->__( 'Request rejection' ),
			'element' => $translator->render_translate_control( 'caption_email_reject' ),
			'tooltip' => $this->a->__( 'The email template to send in reply to a data erasure (processing blocking) request when the request can not be fulfilled at the moment (eg due to the legal obligations to the customer)' ) .
				$this->show_supported_shortcodes(),
		] );

		// Request expiration days
		$name = 'request_expire';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label' => $this->a->__( 'Request expiration' ),
			'tooltip' => $this->a->__( 'Expiration period, in days, after which unauthorized GDPR ' .
				'request will be considered obsolete and will not be fulfilled. Zero value treated ' .
				'as the absence of expiration period' ),
			'element' => [
				'type'  => 'number',
				'id'    => $name,
				'class' => 'form-control config-control',
				'value' => \Advertikon\Setting::get( $name, $this->a, 0 ),
			],
		] );

		// Request mode
		$name = 'request_mode';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label' => $this->a->__( 'Request mode' ),
			'tooltip' => $this->a->__( 'Defines how requests will be processed. Manual mode - you need to handle each request manually, auto - requests are handled by the system depending on current settings, reject - all requests are rejected (use this option if you know what you are doing)' ),
			'element' => [
				'type'  => 'select',
				'id'    => $name,
				'class' => 'form-control config-control',
				'active' => \Advertikon\Setting::get( $name, $this->a, \Advertikon\Adk_gdpr\Request::MODE_AUTO ),
				'value' => [
					\Advertikon\Adk_gdpr\Request::MODE_AUTO   => $this->a->__( 'Auto' ),
					\Advertikon\Adk_gdpr\Request::MODE_MANUAL => $this->a->__( 'Manual' ),
					\Advertikon\Adk_gdpr\Request::MODE_REJECT => $this->a->__( 'Reject' ),
				],
			],
		] );

		// Reject recurring
		$name = 'reject_active';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label' => $this->a->__( 'Reject if active' ),
			'tooltip' => $this->a->__( 'Reject request to data erasure (blocking processing, consent ' .
				'withdrawal) if a customer has active subscription or order that is not has been processed yet' ),
			'element' => $this->a->r()->render_fancy_checkbox( [
				'value' => Advertikon\Setting::get( $name, $this->a ),
				'id'    => $name,
				'name'  => $name,
				'class' => 'config-control'
			] ),
		] );

		// Reject recurring
		$name = 'anonymize_order';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label' => $this->a->__( 'Anonymize orders' ),
			'tooltip' => $this->a->__( 'Whether it is necessary to make anonymous personal data ' .
				'contained in orders during fulfillment of data erasure (blocking processing, ' .
				'consent withdrawal) request' ),
			'element' => $this->a->r()->render_fancy_checkbox( [
				'value' => Advertikon\Setting::get( $name, $this->a ),
				'id'    => $name,
				'name'  => $name,
				'class' => 'config-control'
			] ),
		] );

		// Reject recurring
		$name = 'regard_order_expiration';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label' => $this->a->__( 'Regard order expiration' ),
			'tooltip' => $this->a->__( 'Defines whether to regard order expiration setting when ' .
				'making a decision as to whether to reject GDPR request' ),
			'element' => $this->a->r()->render_fancy_checkbox( [
				'value' => Advertikon\Setting::get( $name, $this->a ),
				'id'    => $name,
				'name'  => $name,
				'class' => 'config-control'
			] ),
		] );

		// Request expiration days
		$name = 'order_expire';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label' => $this->a->__( 'Order expiration' ),
			'description' => $this->a->__( 'GDPR (Recite 39) states: "In order to ensure that the personal data are ' .
				'not kept longer than necessary, time limits should be established by the controller ' .
				'for erasure or for a periodic review". So if you have no contractual obligations ' .
				'to a customer regarding order fulfillment you have no rights to store his/her ' .
				'personal details. This setting determines the period in days, after placing ' .
				'the order, after which you have no obligations to the customer and personal ' .
				'information from the order may be anonymized. The system uses this setting ' .
				'as a hint to determine if order information  can be anonymized or GDPR request ' .
				'may be rejected. Zero value treated as the absence of expiration period' ),
			'element' => [
				'type'  => 'number',
				'id'    => $name,
				'class' => 'form-control config-control',
				'value' => \Advertikon\Setting::get( $name, $this->a, 0 ),
			],
		] );

		$data['report_fact'] = $this->a->r( [
			'label' => $this->a->__( 'Related facts' ),
			'type'  => 'form_group',
			'element' =>  [
				'type'    => 'textarea',
				'id'      => 'breach-report-fact',
				'class'   => 'form-control',
			],
			'tooltip' => $this->a->__( 'Describe the facts relating to the personal data breach' ),
		] );

		$data['report_effect'] = $this->a->r( [
			'label' => $this->a->__( 'Effects' ),
			'type'  => 'form_group',
			'element' => [
				'type'    => 'textarea',
				'id'      => 'breach-report-effect',
				'class'   => 'form-control',
			],
			'tooltip' => $this->a->__( 'Describe the effects of the personal data breach' ),
		] );

		$data['report_remedy'] = $this->a->r( [
			'label' => $this->a->__( 'Remedial actions' ),
			'type'  => 'form_group',
			'element' => [
				'type'    => 'textarea',
				'id'      => 'breach-report-remedy',
				'class'   => 'form-control',
			],
			'tooltip' => $this->a->__( 'Describe what remedial actions was taken' ),
		] );

		$data['report_info'] = $this->a->r()->render_info_box(
			$this->a->__( 'The controller shall document any personal data breaches, comprising the facts ' .
				'relating to the personal data breach, its effects and the remedial action taken. ' .
				'That documentation shall enable the supervisory authority to verify compliance with this Article (GDPR, Article 33)' )
		);

		$data['report_add'] = $this->a->r( [
			'label' => $this->a->__( 'Add to breaches log' ),
			'type'  => 'form_group',
			'element' => [
				'type'        => 'button',
				'id'          => 'breach-report-add-button',
				'button_type' => 'primary',
				'text_before' => $this->a->__( 'Add' ),
				'icon'        => 'fa-plus',
				'custom_data' => [ 'data-url' => $this->a->u( 'add_breach_record' ), ],
			],
			'tooltip' => $this->a->__( 'Describe what remedial actions was taken' ),
		] );

		$data['breach_notification_authority_info'] = $this->a->r()->render_info_box(
			$this->a->__( 'In the case of a personal data breach, the controller shall without undue ' .
				'delay and, where feasible, not later than 72 hours after having become aware of it, ' .
				'notify the personal data breach to the supervisory authority ..., ' .
				'unless the personal data breach is unlikely to result in a risk to ' .
				'the rights and freedoms of natural persons. Where the notification to the supervisory ' .
				'authority is not made within 72 hours, it shall be accompanied by reasons for the delay. (GDPR, Article 33)'
			)
		);

		$data['breach_notification_authority_email'] = '';

		if ( class_exists( 'Advertikon\Adk_Gdpr\Extended' ) ) {
			$breach = new \Advertikon\ADK_Gdpr\Breach( $this->a );
			$sal = $breach->get_sal();
			$data['breach_notification_authority_email'] = $this->a->r( [
				'label' => $this->a->__( 'Authority Email' ),
				'type'  => 'form_group',
				'element' => [
					'type'    => 'select',
					'id'      => 'breach-report-authority-email',
					'class'   => 'form-control select2 breach-report-email',
					'value'   => $sal,
				],
				'description' => $sal ? $this->a->__( 'Select your National Data Protection Authority from ' .
					'the list (auto-updatable) or print in it manually' ) : '',
			] );
		}

		$data['breach_notification_authority_subject'] = $this->a->r( [
			'label' => $this->a->__( 'Email subject' ),
			'type'  => 'form_group',
			'element' => [
				'type'    => 'text',
				'id'      => 'breach-report-authority-subject',
				'class'   => 'form-control select2 breach-report-subject',
				'value'   => 'Personal data breach notification'
			],
		] );


		$data['breach_notification_authority_text'] = $this->a->r( [
			'label' => $this->a->__( 'Text' ),
			'type'  => 'form_group',
			'element' => [
				'type'    => 'textarea',
				'id'      => 'breach-report-authority-text',
				'class'   => 'form-control breach-report-text',
				'css'     => 'min-height: 200px;',
			],
			'description' => $this->a->__(
				"The notification shall at least:<br>" . 
				" (a) describe the nature of the personal data breach including where possible, " .
					"the categories and approximate number of data subjects concerned and the " .
					"categories and approximate number of personal data records concerned;<br>" .
				" (b) communicate the name and contact details of the data protection officer " .
				"or other contact point where more information can be obtained;<br>" .
				" (c) describe the likely consequences of the personal data breach;<br>" .
				" (d) describe the measures taken or proposed to be taken by the " .
				"controller to address the personal data breach, including, where appropriate, " .
				"measures to mitigate its possible adverse effects. (GDPR, Article 33)"
			),
		] );

		$data['breach_notification_authority_button'] = $this->a->r( [
			'label' => $this->a->__( 'Send' ),
			'type'  => 'form_group',
			'element' => [
				'type'        => 'button',
				'class'       => 'send-breach-report-button',
				'button_type' => 'primary',
				'text_before' => $this->a->__( 'Send' ),
				'icon'        => 'fa-paper-plane',
				'custom_data' => [ 'data-url' => $this->a->u( 'send_breach_report' ), ],
			],
		] );

		$data['breach_notification_customer_info'] = $this->a->r()->render_info_box(
			$this->a->__( 'When the personal data breach is likely to result in a high risk to the ' .
				'rights and freedoms of natural persons, the controller shall communicate the ' .
				'personal data breach to the data subject without undue delay. (GDPR, Article 34).<br>' .
				'Such communications to data subjects should be made as soon as reasonably feasible and i<b>n close ' .
				'cooperation</b> with the supervisory authority, respecting guidance provided by it or ' .
				'by other relevant authorities such as law-enforcement authorities. (GDPR, Recite 86)'
			)
		);

		$data['consent_info'] = $this->a->r()->render_info_box(
			$this->a->__( 'Where processing is based on consent, the controller shall be able to ' .
				'demonstrate that the data subject has consented to processing of his or her ' .
				'personal data. (GDPR, Article 7)'
			)
		);

		$data['audit_info'] = $this->a->r()->render_info_box(
			$this->a->__( 'Audit tool will scan your system for general GDPR infringements and ' .
				'provide you with recommendations and decisions to eliminate found noncompliances'
			)
		);

		$data['request_info'] = $this->a->r()->render_info_box(
			$this->a->__( 'Modalities should be provided for facilitating the exercise of the data ' .
				'subject\'s rights under this Regulation, including mechanisms to request and, if ' .
				'applicable, obtain, free of charge, in particular, access to and rectification ' .
				'or erasure of personal data and the exercise of the right to object. The ' .
				'controller should also provide means for requests to be made electronically, ' .
				'especially where personal data are processed by electronic means. The controller ' .
				'should be obliged to respond to requests from the data subject without undue ' .
				'delay and at the latest within one month and to give reasons where the controller ' .
				'does not intend to comply with any such request. (GDPR, Recite 59)'
			)
		);

		$data['breach_notification_customer_subject'] = $this->a->r( [
			'label' => $this->a->__( 'Email subject' ),
			'type'  => 'form_group',
			'element' => [
				'type'    => 'text',
				'id'      => 'breach-report-customer-subject',
				'class'   => 'form-control select2 breach-report-subject',
				'value'   => 'Personal data breach notification'
			],
		] );

		$data['breach_notification_customer_email'] = $this->a->r( [
			'label' => $this->a->__( 'Recipients' ),
			'type'  => 'form_group',
			'element' => [
				'type'        => 'button',
				'id'          => 'get-all-email-button',
				'button_type' => 'primary',
				'text_before' => $this->a->__( 'Download' ),
				'icon'        => 'fa-download',
				'custom_data' => [ 'data-url' => $this->a->u( 'get_all_emails' ), ],
			],
		] );

		$data['breach_notification_customer_text'] = $this->a->r( [
			'label' => $this->a->__( 'Text' ),
			'type'  => 'form_group',
			'element' => [
				'type'    => 'textarea',
				'id'      => 'breach-report-customer-text',
				'class'   => 'form-control breach-report-text',
				'css'     => 'min-height: 200px;',
			],
			'description' => $this->a->__(
				"The communication to the data subject shall describe in clear and plain " .
				"language the nature of the personal data breach and contain at least:<br>" . 
				" (a) communicate the name and contact details of the data protection officer " .
				"or other contact point where more information can be obtained;<br>" .
				" (b) describe the likely consequences of the personal data breach;<br>" .
				" (c) describe the measures taken or proposed to be taken by the " .
				"controller to address the personal data breach, including, where appropriate, " .
				"measures to mitigate its possible adverse effects. (GDPR, Article 34)"
			),
		] );

		$data['breach_notification_customer_button'] = $this->a->r( [
			'label' => $this->a->__( 'Send' ),
			'type'  => 'form_group',
			'element' => [
				'type'        => 'button',
				'class'       => 'send-breach-report-button',
				'button_type' => 'primary',
				'text_before' => $this->a->__( 'Send' ),
				'icon'        => 'fa-paper-plane',
				'custom_data' => [ 'data-url' => $this->a->u( 'send_breach_report' ), ],
			],
		] );

		$data['run_audit'] = $this->a->r( [
			'label' => $this->a->__( 'Run GDPR Audit' ),
			'type'  => 'form_group',
			'element' => [
				'type'        => 'button',
				'id'          => 'run-audit-button',
				'button_type' => 'success',
				'text_before' => $this->a->__( 'Run' ),
				'icon'        => 'fa-search',
				'custom_data' => [ 'data-url' => $this->a->u( 'run_audit' ), ],
			],
			'description' => $this->a->__( 'Since audit may take a while its results are cached. Run audit manually in order to get a fresh resultset' ),
		] );

		$data['remove_missed_orders'] = $this->a->r( [
			'label' => $this->a->__( 'Remove missing orders' ),
			'type'  => 'form_group',
			'element' => [
				'type'        => 'button',
				'id'          => 'remove-missed-orders-button',
				'button_type' => 'danger',
				'text_before' => $this->a->__( 'Run' ),
				'icon'        => 'fa-close',
				'custom_data' => [ 'data-url' => $this->a->u( 'remove_missed_orders' ), ],
			],
			'description' => $this->a->__( 'GDPR states that personal data collected for specified, '. 
				'explicit and legitimate purposes and not further processed in a manner that is incompatible ' .
				'with those purposes and for no longer than is necessary for the purposes for which ' .
				'the personal data are processed. Thus keeping Missing Orders in your system is GDPR ' .
				'infringement. This feature allows removing all missing orders older than 1 hour from the system' ),
		] );

		$data['remove_expired_orders'] = $this->a->r( [
			'label' => $this->a->__( 'Anonymize expired orders' ),
			'type'  => 'form_group',
			'element' => [
				'type'        => 'button',
				'id'          => 'remove-expired-orders-button',
				'button_type' => 'danger',
				'text_before' => $this->a->__( 'Run' ),
				'icon'        => 'fa-user-secret',
				'custom_data' => [ 'data-url' => $this->a->u( 'anonymize_expired_orders' ), ],
			],
			'description' => $this->a->__( 'If you have no contractual obligations to a customer ' .
				'after an order has been processed or such obligations have expired (use "Order ' .
				'expiration" setting to make a hint to the system of expiration period value), ' .
				'according to GDPR you have no rights to store or process personal data related to ' .
				'such an order. This feature allows you to anonymize such personal data in a bulk ' .
				'for all the orders with expired contractual obligations' ),
		] );

		$data['anonymize_unconsented_orders'] = $this->a->r( [
			'label' => $this->a->__( 'Anonymize orders without acceptance' ),
			'type'  => 'form_group',
			'element' => [
				'type'        => 'button',
				'id'          => 'anonymize-unconsented-orders-button',
				'button_type' => 'danger',
				'text_before' => $this->a->__( 'Run' ),
				'icon'        => 'fa-user-secret',
				'custom_data' => [ 'data-url' => $this->a->u( 'anonymize_unconsented_orders' ), ],
			],
			'description' => $this->a->__( 'Anonymize personal data in orders which were placed ' .
				'without a customer acceptance to checkout terms' ),
		] );

		$data['consent_required_cookies'] = '';
		$data['cookie_status'] = '';
		$data['cookie_width'] = '';
		$data['cookie_full_width'] = '';
		$data['cookie_bg_color'] = '';
		$data['cookie_button_color'] = '';
		$data['cookie_border_color'] = '';
		$data['cookie_border_width'] = '';
		$data['cookie_text_color'] = '';
		$data['cookie_button_text_color'] = '';
		$data['cookie_position'] = '';

		if ( class_exists( 'Advertikon\Adk_Gdpr\Cookie' ) ) {
			$cookie = new \Advertikon\ADK_Gdpr\Cookie();

			// Cookies list
			$name = 'consent_required_cookies';
			$data[$name] = $this->a->r()->render_form_group([
				'label' => $this->a->__('Cookies list'),
				'description' => $this->a->__('List of cookies that require informed consent (all cookies ' .
					'except first-party session cookies and first party persistent cookies with an ' .
					'expiry date that does not exceed 3 weeks: eg PHPSESSID, currency, language). ' .
					'You may need to list additional cookies that your site sets to user\'s browser. ' .
					'To identify those cookies you may use the Firefox add-ons' .
					' <a href="https://addons.mozilla.org/en-US/firefox/addon/firecookie/" target="_blank">Firecookie</a>' .
					' ,<a href="https://addons.mozilla.org/en-US/firefox/addon/ghostery/" target="_blank">Ghostery</a>'
				),
				'element' => [
					'type' => 'textarea',
					'value' => $cookie->get($name),
					'id' => $name,
					'name' => $name,
					'class' => 'cookie-control form-control',
					'css' => 'min-height: 200px;'
				],
			]);


			// Status
			$name = 'cookie_status';
			$data[$name] = $this->a->r()->render_form_group([
				'label' => $this->a->__('Status'),
				'element' => $this->a->r()->render_fancy_checkbox([
					'value' => Advertikon\Setting::get($name, $this->a),
					'id' => $name,
					'name' => $name,
					'class' => 'config-control'
				]),
			]);

			// Widget width
			$name = 'cookie_width';
			$data[$name] = $this->a->r()->render_form_group([
				'label' => $this->a->__('Width'),
				'element' => [
					'type' => 'number',
					'value' => Advertikon\Setting::get($name, $this->a, \Advertikon\Adk_Gdpr\Cookie::WIDTH),
					'id' => $name,
					'name' => $name,
					'class' => 'config-control form-control',
				],
			]);

			// Status
			$name = 'cookie_full_width';
			$data[$name] = $this->a->r()->render_form_group([
				'label' => $this->a->__('Full width'),
				'element' => $this->a->r()->render_fancy_checkbox([
					'value' => Advertikon\Setting::get($name, $this->a, \Advertikon\Adk_Gdpr\Cookie::IS_FULL_WIDTH),
					'id' => $name,
					'name' => $name,
					'class' => 'config-control'
				]),
			]);

			// Widget color
			$name = 'cookie_bg_color';
			$data[$name] = $this->a->r()->render_form_group([
				'label' => $this->a->__('Color'),
				'element' => [
					'value' => Advertikon\Setting::get($name, $this->a, \Advertikon\Adk_Gdpr\Cookie::BG_COLOR),
					'id' => $name,
					'name' => $name,
					'class' => 'config-control form-control adk-color',
				],
			]);

			// Button color
			$name = 'cookie_button_color';
			$data[$name] = $this->a->r()->render_form_group([
				'label' => $this->a->__('Button color'),
				'element' => [
					'value' => Advertikon\Setting::get($name, $this->a, \Advertikon\Adk_Gdpr\Cookie::BUTTON_COLOR),
					'id' => $name,
					'name' => $name,
					'class' => 'config-control form-control adk-color',
				],
			]);

			// Widget border color
			$name = 'cookie_border_color';
			$data[$name] = $this->a->r()->render_form_group([
				'label' => $this->a->__('Border color'),
				'element' => [
					'value' => Advertikon\Setting::get($name, $this->a, \Advertikon\Adk_Gdpr\Cookie::BORDER_COLOR),
					'id' => $name,
					'name' => $name,
					'class' => 'config-control form-control adk-color',
				],
			]);

			// Widget height
			$name = 'cookie_border_width';
			$data[$name] = $this->a->r()->render_form_group([
				'label' => $this->a->__('Border Width'),
				'element' => [
					'type' => 'number',
					'value' => Advertikon\Setting::get($name, $this->a, \Advertikon\Adk_Gdpr\Cookie::BORDER_WIDTH),
					'id' => $name,
					'name' => $name,
					'class' => 'config-control form-control',
				],
			]);

			// Text color
			$name = 'cookie_text_color';
			$data[$name] = $this->a->r()->render_form_group([
				'label' => $this->a->__('Text Color'),
				'element' => [
					'value' => Advertikon\Setting::get($name, $this->a, \Advertikon\Adk_Gdpr\Cookie::TEXT_COLOR),
					'id' => $name,
					'name' => $name,
					'class' => 'config-control form-control adk-color',
				],
			]);

			// Button Text color
			$name = 'cookie_button_text_color';
			$data[$name] = $this->a->r()->render_form_group([
				'label' => $this->a->__('Button\'s text Color'),
				'element' => [
					'value' => Advertikon\Setting::get($name, $this->a, \Advertikon\Adk_Gdpr\Cookie::BUTTON_TEXT_COLOR),
					'id' => $name,
					'name' => $name,
					'class' => 'config-control form-control adk-color',
				],
			]);

			// Widget border color
			$name = 'cookie_position';
			$data[$name] = $this->a->r()->render_form_group([
				'label' => $this->a->__('Position'),
				'element' => [
					'type' => 'select',
					'active' => Advertikon\Setting::get($name, $this->a, \Advertikon\ADK_Gdpr\Cookie::POSITION),
					'value' => [
						\Advertikon\ADK_Gdpr\Cookie::POSITION_TOP => $this->a->__('Top'),
						\Advertikon\ADK_Gdpr\Cookie::POSITION_BOTTOM => $this->a->__('Bottom'),
					],
					'id' => $name,
					'name' => $name,
					'class' => 'config-control form-control',
				],
			]);
		}

		$data['cookie_header_text'] = $this->a->r()->render_form_group( [
				'label'   => $this->a->__( 'Header text' ),
				'element' => $translator->render_translate_control( 'caption_cookie_header' ),
		] );

		$data['cookie_body_text'] = $this->a->r()->render_form_group( [
				'label'   => $this->a->__( 'Banner text' ),
				'element' => $translator->render_translate_control( 'caption_cookie_text' ),
				'tooltip' => $this->a->__( 'Text to show in banner' ) .
				$this->show_supported_shortcodes(),
		] );

		// Data block fulfillment email
		$data['cookie_reject_text'] = $this->a->r()->render_form_group( [
				'label'   => $this->a->__( 'Reject button text' ),
				'element' => $translator->render_translate_control( 'caption_cookie_reject' ),
		] );

		// Data block fulfillment email
		$data['cookie_accept_text'] = $this->a->r()->render_form_group( [
				'label'   => $this->a->__( 'Accept button text' ),
				'element' => $translator->render_translate_control( 'caption_cookie_accept' ),
		] );

		$name = 'cookie_policy';
		$data[ $name ] = $this->a->r( [
			'label' => $this->a->__( 'Cookies policy page' ),
			'type'  => 'form_group',
			'element' => [
				'type'   => 'select',
				'id'     => $name,
				'name'   => $name,
				'value'  => $this->a->option()->information(),
				'active' => \Advertikon\Setting::get( $name, $this->a ),
				'class'  => 'form-control config-control',
			],
			'description' => $this->a->__( 'The page which contains store cookies policy information' ),
		] );

		$data['cookie_info'] = $this->a->r()->render_info_box(
			$this->a->__( 'This feature will add cookie consent banner (powered by Cookie Consent ' .
				'Kit backed by European Commission) to all front-end pages of your store'
			)
		);

		$data['download_font'] = '';

		if ( !$this->a->check_font( 'freeserif' ) ) {
			$data['download_font'] = $this->a->r( [
				'label' => $this->a->__( 'Download multilingual font' ),
				'type'  => 'form_group',
				'element' => [
					'type'        => 'button',
					'id'          => 'download-font',
					'button_type' => 'success',
					'icon'        => 'fa-download',
					'text_after'  => $this->a->__( 'Download' ),
					'custom_data' => [ 'data-url' => $this->a->u( 'download_font' ), ],
				],
				'tooltip' => $this->a->__( 'In order to display PDF contents in a language other than English download multilingual font' ),
			] );	
		}

		// Captcha
		$name = 'add_captcha';
		$data[ $name ] = $this->a->r()->render_form_group( [
			'label' => $this->a->__( 'Add captcha' ),
			'tooltip' => $this->a->__( 'Add captcha to GDPR request form' ),
			'element' => $this->a->r()->render_fancy_checkbox( [
				'value' => Advertikon\Setting::get( $name, $this->a ),
				'id'    => $name,
				'name'  => $name,
				'class' => 'config-control'
			] ),
		] );
	}

	public function get_url( $w ) {
		$data = &$w['data'];
	}
	
	protected function show_supported_shortcodes() {
		return htmlentities( '<hr><b>' . $this->a->__( 'Supported shortcodes' ) . ':</b><br>' .
		implode( ',<br>', array_map( function( $i ){ return '{' . $i . '}'; }, $this->a->shortcode()->list_of_supported() ) ) );
	}
	
	/**
	 * Returns LOCALE data
	 * @return string
	 */
	public function get_locale() {
		return json_encode( array(
			'settingUrl'     => $this->a->u( 'set_setting' ),
			'checkTermUrl'   => $this->a->u( 'check_term_version' ),
			'termPreviewUrl' => $this->a->u( 'term_preview' ),
			'translateUrl'   => $this->a->u( 'translate' ),
			'cookieUrl'      => $this->a->u( 'set_cookie' ),
			'languages'      => \Advertikon\Translator::get_languages( $this->a ),

			'imageBase'                 => '',
			'networkError'              => $this->a->__( 'Network error' ),
			'parseError'                => $this->a->__( 'Unable to parse server response string' ),
			'undefServerResp'           => $this->a->__( 'Undefined server response' ),
			'serverError'               => $this->a->__( 'Server error' ),
			'sessionExpired'            => $this->a->__( 'Current session has expired' ),
			'scriptError'               => $this->a->__( 'Script error' ),

			'rejectText'                => $this->a->__( 'Reject the request?' ),
			'fulfillText'               => $this->a->__( 'Fulfill the request' ),
			'modalHeader'               => 'aGDPR',
			'no'                        => $this->a->__( 'No' ),
			'yes'                       => $this->a->__( 'Yes' ),
		), JSON_HEX_QUOT ) . PHP_EOL;
	}

    /**
     * @return string
     * @throws \Advertikon\Exception
     */
    public function get_all_emails() {
		$ret = [];
		$html = [];

		$customers = $this->a->q()->log( 1 )->run_query( [
			'table' => 'customer',
			'field' => [ '`email`', '`firstname`', '`lastname`' ],
		] );

		if ( !$customers ) {
			throw new \Advertikon\Exception( 'Failed to fetch customers' );
		}

		foreach( $customers as $c ) {
			$ret[ $c['email'] ] = $c['firstname'] . ' ' . $c['lastname'];
		}

		if ( version_compare( VERSION, '3', '<' ) ) {
			$affiliates = $this->a->q()->log( 1 )->run_query( [
				'table' => 'affiliate',
				'field' => [ '`email`', '`firstname`', '`lastname`' ],
			] );

			if ( !$affiliates ) {
				throw new \Advertikon\Exception( 'Failed to fetch affiliates' );
			}

			foreach( $affiliates as $c ) {
				$ret[ $c['email'] ] = $c['firstname'] . ' ' . $c['lastname'];
			}
		}

		$html[] = '<div class="adk-email-list">';
		$html[] = "<label><input type='checkbox' class='breach-report-email-all'>" . $this->a->__( 'Select all' ) . "</label>";

		foreach( $ret as $email => $name ) {
			$html[] = "<label><input type='checkbox' value='$email' class='breach-report-email'>$name ($email)</label>";
		}

		$html[] = '</div>';

		return implode( "\n", $html );
	}

    /**
     * @throws \Advertikon\Exception
     */
    public function remove_missed_orders() {
		$audit = new \Advertikon\ADK_Gdpr\Audit( $this->a );
		$q = $audit->get_lost_orders();

		foreach( $q as $order ) {
			$order = new \Advertikon\Order( $order, $this->a );
			$order->delete();
		}
	}
}
