<?php
/**
 * @package adk_gdpr
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon\Adk_Gdpr;

class Request {
	const TYPE_INFORMATION     = 1;
	const TYPE_ERASE           = 2;
	const TYPE_BLOCK_PROCESS   = 3;
	const TYPE_UNBLOCK_PROCESS = 4;
	const TYPE_WITHDRAW        = 5;

	const STATUS_PENDING   = 1;
	const STATUS_REJECTED  = 2;
	const STATUS_FULFILLED = 3; 
	const STATUS_EXPIRED   = 4;
	const STATUS_CONFIRMED = 5;

	const MODE_AUTO   = 0;
	const MODE_MANUAL = 1;
	const MODE_REJECT = 2;

	static public $authorize_code = '';
	protected $language = '';
	protected $store = '';
	
	/**
	 * Request object
	 * @var \Advertikon\DB_Result
	 */
	public $request;
	
	static public $rejection_reason = [];

	/**
	 * Helper class
	 * @var Advertikon
	 */
	protected $a;

	/**
	 * Returns type name
	 * @param int $type
	 * @param Advertikon $a
	 * @return string
	 */
	static public function get_type_name( $type, Advertikon $a ) {
		switch ( $type ) {
			case self::TYPE_WITHDRAW:
				return $a->__( 'Consent withdrawal' );
			case self::TYPE_INFORMATION:
				return $a->__( 'Information access' );
			case self::TYPE_ERASE:
				return $a->__( 'Data erasure' );
			case self::TYPE_BLOCK_PROCESS:
				return $a->__( 'Restrict processing' );
			case self::TYPE_UNBLOCK_PROCESS:
				return $a->__( 'Lift restriction' );
			default:
				return '';
		}
	}

	/**
	 * Returns status name
	 * @param int $status
	 * @param Advertikon $a
	 * @return string
	 */
	static public function get_status_name( $status, Advertikon $a ) {
		switch ( $status ) {
			case self::STATUS_PENDING:
				return $a->__( 'Pending' );
			case self::STATUS_CONFIRMED:
				return $a->__( 'Confirmed' );
			case self::STATUS_EXPIRED:
				return $a->__( 'Expired' );
			case self::STATUS_FULFILLED:
				return $a->__( 'Fulfilled' );
			case self::STATUS_REJECTED:
				return $a->__( 'Rejected' );
			default:
				return '';
		}
	}

    /**
     * Request constructor.
     * @param Advertikon $a
     * @throws \Advertikon\Exception
     */
    public function __construct(Advertikon $a ) {
		$this->a = $a;
		$this->check_expiration();
	}

	/**
	 * Sends request confirmation with authorization code
	 * @param string $email
	 * @param int $type One of TYPE_ constants
	 * @throws \Advertikon\Exception
	 */
	public function send( $email, $type ) {
		$t = (int)$type;
		$this->check_type( $t );

		if ( !$this->a->is_email( $email ) ) {
			throw new \Advertikon\Exception( $this->a->__( 'Email address doesn\'t seem to be correct' ) );
		}

		$this->send_confirmation_email( $email, $t );
	}

    /**
     * Authorizes and fulfills request if applicable
     * @param string $code Authorization code
     * @return string
     * @throws \Advertikon\Exception
     */
	public function authorize( $code ) {
		$request = $this->check_code( $code );
		$this->is_rejected( $request );
		$is_manual = $this->is_manual_mode();

		if ( $is_manual ) {
			return $is_manual;
		}

		return $this->do_fulfill( $request );
	}

    /**
     * Fulfills request
     * @param string $code Authorization code
     * @throws \Advertikon\Exception
     */
	public function fulfill( $code ) {
		$request = $this->check_code( $code, true );
		$this->do_fulfill( $request );
	}

    /**
     * Rejects request
     * @param string $code Authorization code
     * @throws \Advertikon\Exception
     */
	public function reject( $code ) {
		$request = $this->check_code( $code, true );
		$this->do_reject( $request );
	}

	/**
	 * Returns request
	 * @param array $data
	 * @throws \Advertikon\Exception
	 * @return \Advertikon\DB_Result
	 */
	public function get_request( array $data = [] ) {
		$data['table'] = $this->a->tables['request_table'];
		$data['query'] = 'select';

		$q = $this->a->q()->log( 1 )->run_query( $data );

		if ( !$q ) {
			throw new \Advertikon\Exception( $this->a->__( 'Database error' ) );
		}

		return $q;
	}

    /**
     * Returns count of specific requests
     * @return number
     * @throws \Advertikon\Exception
     */
	public function count_requests() {
		$data['field'] = [ 'count' => 'COUNT(*)', ];
		$q = $this->get_request( $data );

		return (int)$q['count'];
	}

	////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Checks request type
	 * @param int $type
	 * @throws \Advertikon\Exception
	 */
	protected function check_type( $type ) {
		if ( $type !== self::TYPE_WITHDRAW &&
			$type !== self::TYPE_ERASE &&
			$type != self::TYPE_INFORMATION &&
			$type !== self::TYPE_UNBLOCK_PROCESS &&
			$type !== self::TYPE_BLOCK_PROCESS ) {
			throw new \Advertikon\Exception( $this->a->__( 'Unsupported request type' ) );
		}
	}

	/**
	 * Sends email containing authentication code
	 * @param string $email
	 * @param string $type
	 * @throws \Advertikon\Exception
	 */
	protected function send_confirmation_email( $email, $type ) {
		$code = uniqid();
		self::$authorize_code = $code;
		
		if ( $this->check_same_request_type( $email, $type ) > 0 ) {
			throw new \Advertikon\Exception( $this->a->__( 'You have already made a request of the same type. Please authorize it so that it can be fulfilled. ' .
					'The email with authorization code has been sent to your email. Check spam folder if the email isn\'t in the inbox folder' ) );
		}

		$action1   = $this->add_record_action( $type, $email, $code );
		$rollback1 = $this->remove_record_action( $code );
		$action2   = $this->send_email_action( $email, $type, $code );
		
		$transaction = new \Advertikon\Transaction( $this->a );
		$transaction->add( \Closure::bind( $action1, $this ), \Closure::bind( $rollback1, $this ) );
		$transaction->add( \Closure::bind( $action2, $this ) );
		$transaction->run();
		self::$authorize_code = '';

	}
	
	/**
	 * Returns transaction action to add request record into DB
	 * @param int $type One of TYPE_ constants
	 * @param string $email
	 * @param string $code Authentication code
     * @return \Closure
	 */
	protected function add_record_action( $type, $email, $code ) {
		return function() use( $type, $email, $code ) {
			$result = $this->a->q()->log( 1 )->run_query( [
				'table' => $this->a->tables['request_table'],
				'query' => 'insert',
				'values' => [
						'type'          => $type,
						'status'        => self::STATUS_PENDING,
						'date_added'    => 'NOW()',
						'email'         => $email,
						'store_id'      => \Advertikon\Store::get_current( $this->a )->get_id(),
						'language_code' => $this->a->language()->get_code(),
						'code'          => $code,
						'expire'        => sprintf( 'DATE_ADD( NOW(), INTERVAL %s DAY)', $this->get_expiration() ),
						'ip'            => $this->get_ip(),
				],
			] );
			
			if ( !$result || !$this->a->db->countAffected() ) {
				throw new \Advertikon\Exception( $this->a->__( 'Database error' ) );
			}
		};
	}
	
	/**
	 * Returns transaction rollback action to remove request record from DB
	 * @param string $code Authentication code
	 * @return \Closure
	 */
	protected function remove_record_action( $code ) {
		return function() use( $code ) {
			$this->a->q()->log( 1 )->run_query( [
				'table' => $this->a->tables['request_table'],
				'query' => 'delete',
				'where' => [
					[
						'field'     => 'code',
						'operation' => "=",
						'value'     => $code,
					]
				],
			] );
		};
	}
	
	/**
	 * Returns transaction action to send email
	 * @param string $email
	 * @param int $type One of TYPE_ constants
	 * @param string $code Authentication code
	 * @return \Closure
	 */
	protected function send_email_action( $email, $type, $code ) {
		return function() use( $email, $type, $code ) {
			$this->email( $email, $type );
		};
	}
	
	/**
	 * Returns request expiration in days, depending on system configurations
	 * @return number
	 */
	protected function get_expiration() {
		$request_expire = \Advertikon\Setting::get( 'request_expire', $this->a );

		if ( (int)$request_expire > 0 ) {
			$expire = (int)$request_expire;
			
		} else {
			$expire = 365000;
		}
		
		return $expire;
	}

	/**
	 * Returns current client's IPv4 address
	 * @return string
	 */
	protected function get_ip() {
		if ( !empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];

		} elseif ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}

    /**
     * Sends email with authentication code to authorize request
     * @param string $email
     * @param int $type One of TYPE_ constants
     * @throws \Advertikon\Exception
     * @throws \Exception
     */
	protected function email( $email, $type ) {
		$mail = $this->a->init_mail();
		$key = $this->get_email_template_code( $type );

		$mail->setTo( $email );
		$mail->setText( $this->a->shortcode()->do_shortcode( $this->a->__( $key ) ) );
		$mail->setSubject( $this->a->__( 'GDPR request' ) );
		$mail->send();
	}

	/**
	 * Returns request aithentification email template depends on request type
	 * @param int $type One of TYPE_ constants
	 * @throws \Advertikon\Exception
	 * @return string
	 */
	protected function get_email_template_code( $type ) {
		switch( $type ) {
			case self::TYPE_INFORMATION:
				$key = 'caption_email_information_confirm';
				break;
			case self::TYPE_WITHDRAW:
				$key = 'caption_email_withdraw_confirm';
				break;
			case self::TYPE_ERASE:
				$key = 'caption_email_erase_confirm';
				break;
			case self::TYPE_BLOCK_PROCESS:
				$key = 'caption_email_stop_confirm';
				break;
			case self::TYPE_UNBLOCK_PROCESS:
				$key = 'caption_email_unstop_confirm';
				break;
			default:
				throw new \Advertikon\Exception( 'Undefined request type: ' . $type );
		}

		return $key;
	}

    /**
     * Returns number of pending request of the same type for specific email
     * @param string $email
     * @param int $type One of TYPE_ constants
     * @return number
     * @throws \Advertikon\Exception
     */
	protected function check_same_request_type( $email, $type ) {
		$data = [
			'field' => [ 'count' => 'COUNT(*)',],
			'where' => [
				[
					'field'     => 'email',
					'operation' => '=',
					'value'     => $email, 
				],
				[
					'field'     => 'type',
					'operation' => '=',
					'value'     => $type, 
				],
				[
					'field'     => 'status',
					'operation' => '=',
					'value'     => self::STATUS_PENDING,
				],
			],
		];

		$q = $this->get_request( $data );

		if ( $q ) {
			return (int)$q['count'];
		}

		return 0;
	}

	/**
	 * Performs some checking upon authorization request code
	 * @param string $code Authorization code
	 * @param boolean $force If true - will skip request status verification
	 * @throws \Advertikon\Exception
	 * @return \Advertikon\DB_Result
	 */
	protected function check_code( $code, $force = false ) {
		if ( !$code ) {
			throw new \Advertikon\Exception( $this->a->__( 'Authorization code is missing' ) );
		}

		$data = [
			'where' => [
				'field'     => '`code`',
				'operation' => '=',
				'value'     => $code,
			],
		];

		$q = $this->get_request( $data );

		if ( in_array( $q['email'], [ \Advertikon\Accounts::BLOCKED_TEXT, \Advertikon\Accounts::ERASED_TEXT ] ) ) {
			$acc = new Request_Account( [ 'code' => $code ], $this->a );
			$q = $acc->get_blocked_data();
		}

		$this->language = $q['language_code'];
		$this->store    = $q['store_id'];
		$this->request  = $q;
		
		if ( $force ) {
			return $q;
		}

		$status = (int)$q['status'];

		if ( $status === self::STATUS_EXPIRED ) {
			throw new \Advertikon\Exception( $this->a->__( 'Request is expired. Please make new request from GDPR tools page' ) );

		} else if ( $status === self::STATUS_FULFILLED || $status === self::STATUS_REJECTED ) {
			throw new \Advertikon\Exception( $this->a->__( 'The request has been already fulfilled' ) );

		} else if ( $status === self::STATUS_CONFIRMED ) {
			throw new \Advertikon\Exception(
				$this->a->__( 'Your request has been already authorized and put into the queue. Please wait when it will be fulfilled' )
			);

		} else if ( $status === self::STATUS_PENDING ) {
			$this->mark_as_authorized( $code );

		} else {
			throw new \Advertikon\Exception( $this->a->__( 'Unsupported request type' ) );
		}

		return $q;
	}

    /**
     * Handles request to get access to personal data
     * @param string $email
     * @return string String status to show to a customer
     * @throws \Advertikon\Exception
     */
	protected function information( $email ) {
		$information = $this->collect_information( $email );
		$this->send_information( $email, $information );

		return $this->a->__( 'All your personal data we store, have been sent to your email (also, in a common machine-readable format)' );
	}

	/**
	 * Returns personal data stored in the system for specific email
	 * @param string $email
	 * @return array
	 */
	protected function collect_information( $email ) {
		$ret = [];
		$addresses = [];

		try {
			$customer = \Advertikon\Customer::get( $email, $this->a );
			$addresses = $customer->get_address();
			$ret['customers'] = [ $customer, ];
			
		} catch ( \Advertikon\Exception $a ) {
			
		}
		
		try {
			$affiliate = \Advertikon\Affiliate::get( $email, $this->a );
			$addresses = array_merge( $addresses, $affiliate->get_address() );
			$ret['affiliate'] = $affiliate;

		} catch ( \Advertikon\Exception $e ) {
			
		}

		if ( !isset( $ret['customers'] ) ) {
			try {
				$customers = \Advertikon\Customer::get_from_order( $email, $this->a );

				foreach( $customers as $customer ) {
					$addresses = array_merge( $addresses, $customer->get_address() );
				}

				$ret['customers'] = $customers;		

			} catch ( \Advertikon\Exception $e ) {

			}
		}
		
		$addresses = array_filter( $addresses, function( \Advertikon\Address $i ){ return !$i->is_empty(); } );
		$addresses = \Advertikon\Address::unique( $addresses );
		$ret['address'] = $addresses;

		return $ret;
	}

    /**
     * Sends personal information
     * @param string $email
     * @param array $information
     * @throws \Advertikon\Exception
     * @throws \Exception
     */
	protected function send_information( $email, array $information ) {
		$mail = $this->get_email_instance( $email );
		$pdf_attachment = '';
		$csv_attachment = '';

		// There is no personal data
		if ( !isset( $information['customers'] ) &&
			 ( !isset( $information['affiliate'] ) || $information['affiliate']->is_empty() ) &&
			 !$information['address'] ) {

			$mail->setText(
				$this->a->shortcode()->do_shortcode(
					$this->a->translator->get_translation( 'caption_email_information_missed', $this->language, true )
				)
			);

		} else {
			$pdf_attachment = $this->information_pdf_file( $information );
			$csv_attachment = $this->information_csv_file( $information );
			$mail->setText(
				$this->a->shortcode()->do_shortcode(
					$this->a->translator->get_translation( 'caption_email_information', $this->language, true )
				)
			);

			$mail->addAttachment( $pdf_attachment );
			$mail->addAttachment( $csv_attachment );
		}

		$mail->send();

		if ( $pdf_attachment ) {
			unlink( $pdf_attachment );
		}

		if ( $csv_attachment ) {
			unlink( $csv_attachment );
		}
	}

	/**
	 * Initializes and returns mailer object
	 * @param string $email
	 * @return \Mail
	 */
	protected function get_email_instance( $email ) {
		$mail = $this->a->init_mail();
		$mail->setTo( $email );
		$mail->setSubject( 'GDPR request' );

		return $mail;
	}

    /**
     * Changes status of expired requests in DB
     * @throws \Advertikon\Exception
     */
	protected function check_expiration() {
		$this->a->q()->log( 1 )->run_query( [
			'table' => $this->a->tables['request_table'],
			'query' => 'update',
			'set'   => [ 'status' => self::STATUS_EXPIRED, ],
			'where' => [
				'field'     => 'expire',
				'operation' => '<',
				'value'     => 'NOW()',
			],
		] );
	}

	/**
	 * Creates PDF file with contains personal data information
	 * @param array $information
	 * @return string File name
	 */
	protected function information_pdf_file( array $information ) {
		$file_name = $this->a->tmp_dir . uniqid() . '.pdf';
		$html = [];

		foreach( $information['customers'] as $customer ) {
		    /** @var \Advertikon\Customer $customer */
			if ( !$customer->is_empty() ) {
				$html[] = $customer->to_html();
			}
		}

		if ( isset( $information['affiliate'] ) && !$information['affiliate']->is_empty() ) {
			$html[] = $information['affiliate']->to_html();
		}
 
		if ( isset( $information['address'] ) ) {
		    /** @var \Advertikon\Address $address */
            foreach( $information['address'] as $address ) {
				$html[] = $address->to_html();
			}
		}

		$pdf = new \Advertikon\Pdf( $this->a );
		$pdf->add_store_logo();
		$pdf->set_html( implode( '<hr><br>', $html ) );
		$pdf->save_pdf( $file_name );

		return $file_name;
	}

	/**
	 * Creates ZIP archive with CVS file for each personal data part
	 * @param array $information
	 * @throws \Advertikon\Exception
	 * @return string File name
	 */
	protected function information_csv_file( array $information ) {
		$files = [];

		foreach( $information['customers'] as $customer ) {
		    /** @var \Advertikon\Customer $customer */
			if ( !$customer->is_empty() ) {
				$csv = $customer->to_csv();
				$file_name = $this->a->tmp_dir . 'customer' . uniqid() . '.csv';
				file_put_contents( $file_name, $csv );
				$files[] = $file_name;
			}
		}

		if ( isset( $information['affiliate'] ) && !$information['affiliate']->is_empty() ) {
			$csv = $information['affiliate']->to_csv();
			$file_name = $this->a->tmp_dir . 'affiliate' . uniqid() . '.csv';
			file_put_contents( $file_name, $csv );
			$files[] = $file_name;
		}
 
		if ( isset( $information['address'] ) ) {
			foreach( $information['address'] as $i => $address ) {
			    /** @var \Advertikon\Address $address */
				$csv = $address->to_csv();
				$file_name = $this->a->tmp_dir . 'address' . uniqid() . '.csv';
				file_put_contents( $file_name, $csv );
				$files[] = $file_name;
			}
		}

		$zip = new \ZipArchive();
		$zip_name = $this->a->tmp_dir . uniqid() . '.zip';
		$res = $zip->open( $zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
		
		if ( true !== $res ) {
			throw new \Advertikon\Exception( 'Failed to create archive ' . $zip_name );
		}

		foreach( $files as $file ) {
			if ( true !== $zip->addFile( $file, basename( $file ) ) ) {
				$this->a->error( 'Failed to add file ' . $file . ' to archive' );
			}
		}
		
		$zip->close();

		foreach( $files as $file ) {
			unlink( $file );
		}

		return $zip_name;
	}

    /**
     * Marks request as been fulfilled
     * @param string $code Authentication code
     * @throws \Advertikon\Exception
     */
	protected function mark_as_fulfilled( $code ) {
		$this->a->q()->log( 1 )->run_query( [
			'table' => $this->a->tables['request_table'],
			'query' => 'update',
			'set'   => [ 'status' => self::STATUS_FULFILLED, 'date_done' => 'NOW()' ],
			'where' => [
				'field'     => 'code',
				'operation' => '=',
				'value'     => $code,
			],
		] );
	}

    /**
     * Marks request as been authorized
     * @param string $code Authentication code
     * @throws \Advertikon\Exception
     */
	protected function mark_as_authorized( $code ) {
		$this->a->q()->log( 1 )->run_query( [
			'table' => $this->a->tables['request_table'],
			'query' => 'update',
			'set'   => [ 'status' => self::STATUS_CONFIRMED ],
			'where' => [
				'field'     => 'code',
				'operation' => '=',
				'value'     => $code,
			],
		] );
	}

    /**
     * Marks request as been rejected
     * @param string $code Authentication code
     * @throws \Advertikon\Exception
     */
	protected function mark_as_rejected( $code ) {
		$this->a->q()->log( 1 )->run_query( [
			'table' => $this->a->tables['request_table'],
			'query' => 'update',
			'set'   => [ 'status' => self::STATUS_REJECTED, 'reject_reason' => implode( "\n", self::$rejection_reason ) ],
			'where' => [
				'field'     => 'code',
				'operation' => '=',
				'value'     => $code,
			],
		] );
	}

    /**
     * Handles request to stop data processing
     * @param string $email
     * @return string String result to show to a customer
     * @throws \Advertikon\Exception
     */
	protected function block( $email ) {
		$e = new Extended( $this->a );

		return $e->block( $email );
	}

	/**
	 * Handles request to lift data processing blocking
	 * @param string $email
	 * @return string
	 * @throws \Advertikon\Exception
	 */
	protected function unblock( $email ) {
		$e = new Extended( $this->a );

		return $e->unblock( $email );
	}

    /**
     * Checks if account information can be affected by arase/blobk/unblock requests
     * @param string $email
     * @return boolean
     * @throws \Advertikon\Exception
     */
	public function can_affect_account( $email ) {
		try {
			$customers = [ \Advertikon\Customer::get( $email, $this->a ), ];

		} catch ( \Advertikon\Exception $e ) {
			try {
				$customers = \Advertikon\Customer::get_from_order( $email, $this->a );

			} catch ( \Advertikon\Exception $e ) {
				return true;
			}
		}
		
		foreach( $customers as $customer ) {
			if( \Advertikon\Setting::get( 'reject_active', $this->a ) && $customer->has_active_order() ) {
				self::$rejection_reason[] = $this->a->__( 'the personal data subject has at least one order that is under processing' );

				return false;
			}
			
			$exp_days = \Advertikon\Setting::get( 'order_expire', $this->a );
			$today = date( 'Y-m-d H:i:s' );

			if ( $exp_days && \Advertikon\Setting::get( 'regard_order_expiration', $this->a ) ) {
				foreach ( $customer->get_orders() as $order ) {
					$expiration = date( 'Y-m-d H:i:s', strtotime( $order->get_date_added() . "+{$exp_days} days" ) );

					if ( $today < $expiration ) {
						self::$rejection_reason[] = $this->a->__( 'the store owner has legal obligations to personal data subject regarding the order #' ) .
							$order->get_id() . $this->a->__( ' , which expire at ' ) . $expiration;

						return false;
					}
				}
			}
		}
		
		return true;
	}

	/**
	 * Checks if order information needs to be anonymized
	 * @return bool
	 */
	public function can_affect_order() {
		return (boolean)\Advertikon\Setting::get( 'anonymize_order', $this->a );
	}

    /**
     * Handles request to erase personal information
     * @param string $email
     * @return string String response to show to a customer
     * @throws \Advertikon\Exception
     */
	protected function erase( $email ) {
		$erase_order = $this->can_affect_order();
		$customers = [];

		if ( $this->can_affect_account( $email ) ) {
			try {
				$customers = [ \Advertikon\Customer::get( $email, $this->a ) ];

			} catch ( \Advertikon\Exception $e ) {
				try {
					$customers = \Advertikon\Customer::get_from_order( $email, $this->a );

				} catch ( \Advertikon\Exception $e ) {
					
				}
			}

			foreach( $customers as $customer ) {
				$customer->erase( $erase_order );
			}

			try {
				$affiliate = \Advertikon\Affiliate::get( $email, $this->a );
				$affiliate->erase();

			} catch ( \Advertikon\Exception $e ) {

			}

		} else {
			$this->do_reject( $this->request );
		}

		try {
			foreach( Request_Account::get( $this->request['code'], $this->a ) as $acc ) {
				$acc->erase();
			}

		} catch ( \Advertikon\Exception $e ) {
			$this->a->error( $e );
		}

		return $this->a->__( 'All your personal data has been deleted' );
	}

	/**
	 * Handles request to withdraw consent
	 * @param string $email
	 * @throws \Advertikon\Exception
	 * @return string String response to show to a customer
	 */
	protected function withdraw( $email ) {
		$e = new Extended( $this->a );

		return $e->withdraw( $email );
	}

	/**
	 * Checks if request needs to handled manually
	 * @return string|null String response to show to a customer
	 */
	protected function is_manual_mode() {
		if ( \Advertikon\Setting::get( 'request_mode', $this->a ) == self::MODE_MANUAL || !class_exists( 'Advertikon\Adk_Gdpr\Extended' ) ) {
			return $this->a->__( 'Your request has been put into the queue. You\'ll be notified of its fulfillment' );
		}

		return null;
	}

    /**
     * Checks if request needs to be rejexted, and if so - rejects it
     * @param \Advertikon\DB_Result $request
     * @throws \Advertikon\Exception
     */
	protected function is_rejected( \Advertikon\DB_Result $request ) {
		if ( \Advertikon\Setting::get( 'request_mode', $this->a ) == self::MODE_REJECT ) {
			$this->do_reject( $request );
		}
	}

    /**
     * Rejects request
     * @param \Advertikon\DB_Result $request
     * @throws \Advertikon\Exception
     * @throws \Exception
     */
	public function do_reject( \Advertikon\DB_Result $request ) {
		$mail = $this->get_email_instance( $request['email'] );
		$mail->setText( $this->a->shortcode()->do_shortcode(
			$this->a->translator->get_translation( 'caption_email_reject', $this->language, true )
		) );

		$mail->send();
		$this->mark_as_rejected( $request['code'] );
		$message = $this->a->__( 'Current request cannot be fulfilled at the moment' );
		
		if ( self::$rejection_reason ) {
			$message .= ' (' . implode( ', ', self::$rejection_reason ) . ')';
		}
		
		throw new \Advertikon\Exception( $message );
	}

	/**
	 * Fulfills request depending on its type
	 * @param \Advertikon\DB_Result $request
	 * @throws \Advertikon\Exception
	 * @return string String response to show to a customer
	 */
	protected function do_fulfill( \Advertikon\DB_Result $request ) {
		$email = $request['email'];
		$notify = true;

		switch ( $request['type'] ) {
			case self::TYPE_INFORMATION:
				$result = $this->information( $email );
				$notify = false;
				break;
			case self::TYPE_BLOCK_PROCESS:
				$result = $this->block( $email );
				break;
			case self::TYPE_UNBLOCK_PROCESS:
				$result = $this->unblock( $email );
				break;
			case self::TYPE_ERASE:
				$result = $this->erase( $email );
				break;
			case self::TYPE_WITHDRAW:
				$result = $this->withdraw( $email );
				break;
			default:
				throw new \Advertikon\Exception( $this->a->__( 'Unsupported request type' ) );
		}

		$this->mark_as_fulfilled( $request['code'] );
		
		if ( $notify ) {
			$this->notify_customer( $email, $request['type'] );
		}

		return $result;
	}

    /**
     * Sends request fulfillment notification email
     * @param string $email
     * @param int $type One of TYPE_ constants
     * @throws \Advertikon\Exception
     * @throws \Exception
     */
	protected function notify_customer( $email, $type ) {
		$mail = $this->get_email_instance( $email );
		$template = $this->get_notification_template( $type );
		
		$mail->setText( $this->a->shortcode()->do_shortcode(
			$this->a->translator->get_translation( $template, $this->language, true )
		) );
		
		$mail->send();
	}
	
	/**
	 * Returns request fulfillment confirmation email template by its type
	 * @param int $type One of TYPE_ constants
	 * @throws \Advertikon\Exception
	 * @return string
	 */
	protected function get_notification_template( $type ) {
		switch( $type ) {
			case self::TYPE_BLOCK_PROCESS:
				return 'caption_email_block_done';
			case self::TYPE_UNBLOCK_PROCESS:
				return 'caption_email_unblock_done';
			case self::TYPE_ERASE:
				return 'caption_email_erasure_done';
			case self::TYPE_WITHDRAW:
				return 'caption_email_withdraw_done';
			default:
				throw new \Advertikon\Exception( 'Unsupported request type' );
		}
	}
}

class Request_Account extends \Advertikon\Accounts {
	protected $name           = '';
	protected $id             = 'request_id'; 
	protected $distinct_field = 'request_id';

	protected $fields = [
		'request_id',
	];

    /**
     * @param $code
     * @param Advertikon $a
     * @return Request_Account[]
     * @throws \Advertikon\Exception
     */
    public static function get($code, Advertikon $a ) {
		$q = $a->q()->log( 1 )->run_query( [
			'table' => $a->tables['request_table'],
			'where' => [
				'field'     => 'code',
				'operation' => '=',
				'value'     => $code,
			],
		] );

		if ( !$q ) {
			throw new \Advertikon\Exception( 'There is no request, associates with code ' . $code );
		}

		$ret = [];

		foreach( $q as $data ) {
			$ret[] = new Request_Account( $data, $a );
		}

		return $ret;
	}

    /**
     * Request_Account constructor.
     * @param array $data
     * @param Advertikon $a
     * @throws \Advertikon\Exception
     */
    public function __construct(array $data, Advertikon $a ) {
		$this->a = $a;
		$this->data = $data;
		$this->name = $a->tables['request_table'];

		if ( isset( $this->data['email'] ) &&
			in_array( $this->data['email'], [ \Advertikon\Accounts::BLOCKED_TEXT, \Advertikon\Accounts::ERASED_TEXT ] ) ) {
			$this->get_blocked_data();
		}
	}

	public function get_id() {
		return $this->data['request_id'];
	}

	public function get_request_id() {
		return $this->data['request_id'];
	}

	public function get_email() {
		return $this->data['email'];
	}
	
	protected function get_blocked_fields( $placeholder ) {
		return [
			'name'  => $placeholder,
			'email' => $placeholder,
			'ip'    => $placeholder,
		];
	}


    /**
     * @throws \Advertikon\Exception
     */
    public function block() {
		$q = $this->a->q()->log( 1 )->run_query( [
			'table' => $this->a->tables['request_table'],
			'where' => [
				'field'     => 'email',
				'operation' => '=',
				'value'     => $this->get_email()
			],
		] );

		if ( !$q ) {
			throw new \Advertikon\Exception( 'Customer has not placed any GDPR requests' );
		}

		foreach( $q as $data ) {
			$r = new Request_Account( $data, $this->a );
			$r->block_account();
		}
	}

    /**
     * @throws \Advertikon\Exception
     */
    public function unblock() {
		$q = $this->a->q()->log( 1 )->run_query( [
			'table' => $this->get_blocked_name(),
			'where' => [
				'field'     => 'email',
				'operation' => '=',
				'value'     => $this->get_email()
			],
		] );

		if ( !$q ) {
			throw new \Advertikon\Exception( 'Customer has not placed any GDPR requests' );
		}

		foreach( $q as $data ) {
			$r = new Request_Account( $data, $this->a );
			$r->unblock_account();
		}
	}

	public function erase() {
		$this->erase_account();
	}

    /**
     * @return \Advertikon\DB_Result|bool|int
     * @throws \Advertikon\Exception
     */
    public function get_blocked_data() {
		if ( !isset( $this->data['code'] ) ) {
			throw new \Advertikon\Exception( 'Code is missing' );
		}

		$q = $this->a->q()->log( 1 )->run_query( [
			'table' => $this->get_blocked_name(),
			'where' => [
				'field'     => 'code',
				'operation' => '=',
				'value'     => $this->data['code'],
			],
		] );

		if ( !$q ) {
			throw new \Advertikon\Exception( 'Request with code ' . $this->data['code'] . ' is missing' );
		}

		$this->data = $q->current();

		return $q;
	}
}
