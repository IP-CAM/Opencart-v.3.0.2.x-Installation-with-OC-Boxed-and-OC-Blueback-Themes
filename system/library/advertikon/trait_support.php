<?php
/**
 * Advertikon Support Trait
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

trait Trait_Support {
	private $LICENSE_ACTIVE       = 1;
	private $LICENSE_EXPIRED      = 2;
	private $LICENSE_MISSED       = 0;
	private $LICENSE_TRANSFERRING = 3;
	private $LICENSE_CANCELED     = 4;

	// Need to by in sync with server's constants
	private $STATUS_ACTIVE       = 1;
	private $STATUS_TRANSFERRING = 2;
	private $STATUS_PENDING      = 3;

	private $status_url = 'http://shop.advertikon.com.ua/support/license.php';

	protected function render_support() {
		$this->document->addScript( HTTPS_SERVER . 'view/javascript/advertikon/support.js' );

		$version = $this->a->version();
		$db_version = $this->a->get_db_version();
		$db_prev = $this->a->get_previous_db_version();

		$ret =
<<<HTML
		<div class="tab-pane top-pane wrapper-with-wait-screen form-horizontal" id="tab-support">
			<div class="form-group">
				<div class="col-sm-2">
					<span style="font-weight: bold;">{$this->a->__( 'Current version')}:</span>
				</div>
				<div class="col-sm-1" style="font-weight: bold;">$version</div>
			</div>
HTML;
		if ( $db_version ) {
			$ret .= 
			"<div class='form-group'>
				<div class='col-sm-2'>
					<span style='font-weight: bold;'>{$this->a->__( 'DB version' )}:</span>
				</div>
				<div class='col-sm-1'>$db_version</div>";

				if ( true ) {
					$text = $db_prev ? $this->a->__( 'Rollback to v%s', $db_prev ) : $this->a->__( 'Rollback' );

					$ret .=
					"<div class='col-sm-2'>" .
					$this->a->r( [
						'type'        => 'button',
						'button_type' => 'primary',
						'text_after'  => $text,
						'icon'        => 'fa-fast-backward',
						'custom_data' => 'data-url="' . $this->a->u()->url( 'rollback_db' ) . '"',
						'id'          => 'rollback-db',
					] ) .
					"</div>";
				}

				$ret .=
			"</div>";
		}

		if ( method_exists( $this, 'render_task_status' ) ) {
			$ret .= $this->render_task_status();
		}

		$ret .= <<<HTML
			<div class="form-group">
				<div class="col-sm-2">
					<span style="font-weight: bold; color: green;">{$this->a->__( 'Clear cache')}:</span>
				</div>
				<div class="col-sm-1">{$this->a->r( [
						'type'        => 'button',
						'button_type' => 'danger',
						'icon'        => 'fa-close',
						'text_before' => $this->a->__( 'Flush' ),
						'custom_data' => 'data-url="' . $this->a->u()->url( 'clear_cache' ) . '"',
						'id'          => 'clear-cache',
					] )}</div>
			</div>

			<div id="ticket-wrapper" data-url="{$this->a->u()->url( 'ticket_button' )}"></div>

			<div class="wait-screen static">
				<div class="spinner-holder">
					<i class="fa fa-spinner fa-pulse wait-spinner"></i>
					<span class="msg"></span>
				</div>
			</div>
		</div>
HTML;
		return $ret;
	}

	/**
	 * Get ticket button action
	 * @API
	 */
	public function ticket_button() {
		$ret = '';
		$license_by_order_id_url = $this->a->u( 'license_by_id' );
		$register_license_url = $this->a->u( 'register_extension' );

		try {
			/* @var string[string] License data */
			$license_data = $this->get_installation_status();
			$license_status = $this->get_licence_status_code( $license_data );
			$get_license_by_order = '';
			$register_license = '';
			$license_name = '';
			$license_email = '';
			$transfer_code = '';
			$finish_transfer_button = '';
			$cancel_transfer_button = '';
			$name = '';
			$email = '';
			$attachments = '';
			$support_id = '';
			$subject = '';
			$button = '';
			$header_text = '';
			$new_version = '';
			
			$license = $this->a->r()->render_form_group( array(
				'label'   => $this->a->__( 'License' ),
				'element' => $this->get_license_string( $license_data ),
				'cols'    => array( 'col-sm-2', 'col-sm-10', ),
			) );

			// License transfer is in progress
			if ( $this->LICENSE_TRANSFERRING === $license_status ) {
				$license_name = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( 'License holder\'s name' ),
					'element' => array(
						'type'         => 'text',
						'class'        => 'form-control',
						'id'           => 'license_name',
						'placeholder'  => $this->a->__( 'Name of new license holder' ),
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
					'tooltip' => $this->a->__( 'Just to know how to address you' ),
				) );

				$license_email = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( 'License holder\'s email' ),
					'element' => array(
						'type'         => 'text',
						'class'        => 'form-control',
						'id'           => 'license_email',
						'placeholder'  => $this->a->__( 'Email address of new license holder' ),
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
					'tooltip' => $this->a->__( 'Email address to send a license transfer notification to in case of a future transfer' ),
				) );

				$transfer_code = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( 'Transfer code' ),
					'element' => array(
						'type'         => 'text',
						'class'        => 'form-control',
						'id'           => 'license_code',
						'placeholder'  => $this->a->__( 'Authentication code' ),
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
					'tooltip' => $this->a->__( 'To authorize a license transfer from one installation to another a code is sent to email address of the current license holder' ),
				) );

				$finish_transfer_button = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( 'Finish transfer' ),
					'element' => array(
						'type'        => 'button',
						'text_before' => $this->a->__( 'Transfer' ),
						'icon'        => 'fa-send-o',
						'button_type' => 'primary',
						'id'          => 'finish_transfer',
						'custom_data' => 'data-url="' . $this->a->u()->url( 'finish_transfer' ) . '"',
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
				) );

				$cancel_transfer_button = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( 'Cancel transfer' ),
					'element' => array(
						'type'        => 'button',
						'text_before' => $this->a->__( 'Cancel' ),
						'icon'        => 'fa-close',
						'button_type' => 'danger',
						'id'          => 'cancel_transfer',
						'custom_data' => 'data-url="' . $this->a->u()->url( 'cancel_transfer' ) . '"',
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
				) );
			}

			if ( $license_status === $this->LICENSE_MISSED ) {
				$get_license_by_order = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( 'Get license by order ID' ),
					'element' => array(
						'type'         => 'inputgroup',
						'element'      => [
							'type' => 'text',
							'class' => 'form-control',
							'id'    => 'license_by_id_input',
						],
						'addon_after'  => [
							'type'        => 'button',
							'button_type' => 'primary',
							'icon'        => 'fa-play-circle',
							'id'          => 'license_by_id_button',
							'custom_data' => 'data-url="' . $license_by_order_id_url . '"',
						],  
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
					'tooltip' => $this->a->__( 'Get license by order ID (The license code will be generated automatically)' ),
				) );
			}

			if ( $license_status === $this->LICENSE_EXPIRED ) {
				$register_license = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( 'Register extension' ),
					'element' => array(
						'type'         => 'inputgroup',
						'element'      => [
							'type' => 'text',
							'class' => 'form-control',
							'id'    => 'register_license',
						],
						'addon_after'  => [
							'type'        => 'button',
							'button_type' => 'primary',
							'icon'        => 'fa-play-circle',
							'id'          => 'register_license_button',
							'custom_data' => 'data-url="' . $register_license_url . '"',
						],  
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
					'tooltip' => $this->a->__( 'Provide license code in order to enable support for this particular installation' ),
				) );
			}

			// Render Support contents
			if ( $this->STATUS_ACTIVE === $license_status ) {
				$header_text = '<h3 style="text-align: center;">' . $this->a->__( 'Open a ticket' ) . '</h3>';
				$name = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( 'Name' ),
					'element' => array(
						'type'         => 'text',
						'class'        => 'form-control form-field',
						'placeholder'  => $this->a->__( 'Tell how to address you' ),
						'custom_data' => 'data-name="support_name"'
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
				) );

				$email = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( 'Email' ),
					'element' => array(
						'type'         => 'text',
						'class'        => 'form-control form-field',
						'placeholder'  => $this->a->__( 'Where to send an answer' ),
						'value'        => $this->config->get( 'config_email' ),
						'custom_data' => 'data-name="support_email"'
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
				) );

				$subject = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( 'Your question' ),
					'element' => array(
						'type'         => 'textarea',
						'class'        => 'form-control form-field',
						'custom_data' => 'data-name="support_subject"',
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
				) );

				$attachments = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( 'Attachments' ),
					'element' => array(
						'type'        => 'file',
						'class'       => 'form-field',
						'custom_data' => 'multiple="multiple" data-name="support_attachment[]"'
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
				) );

				$button = $this->a->r()->render_form_group( array(
					'label' => $this->a->__( ' ' ),
					'element' => array(
						'type'        => 'button',
						'text_before' => $this->a->__( 'Send' ),
						'icon'        => 'fa-send-o',
						'button_type' => 'primary',
						'id'          => 'ask-support-button',
						'custom_data' => 'data-url="' . $this->a->u()->url( 'ask_support' ) . '"',
					),
					'cols'      => array( 'col-sm-2', 'col-sm-10', ),
				) );

				$support_id = $this->a->r( array(
					'type'        => 'hidden',
					'value'       => isset( $res['id'] ) ? $res['id'] : '',
					'class'       => 'form-field',
					'custom_data' => 'multiple="multiple" data-name="support_id"'
				) );

				if ( !empty( $license_data['new_version'] ) && !empty( $license_data['new_version_text'] ) ) {
					$new_version = '<div class="new-version-caption">' . $this->a->__(
						'New version is available: %s (%s)', $license_data['new_version'], $license_data['new_version_text']
					) . '</div>';
				}
			}

			$ret =
<<<HTML
			<div id="support-wrapper">
				$license
				$new_version
				$get_license_by_order
				$register_license
				$license_name
				$license_email
				$transfer_code
				$finish_transfer_button
				$cancel_transfer_button
				$header_text
				$name
				$email
				$subject
				$attachments
				$button
				$support_id
			</div>
HTML;
			
		} catch ( \Exception $e ) {
			$this->a->log( $e );
		}

		$this->response->setOutput( $ret );
	}

	/**
	 * Returns formatted string describing license status
	 * @param string[string] $license License data
	 * @return string License description
	 */
	private function get_license_string( $license ) {
		switch ( $this->get_licence_status_code( $license ) ) {
			case $this->LICENSE_MISSED:
				$ret = sprintf( '<b style="color: gray">%s</b>', $this->a->__( 'Missed. This installation is not covered by technical support' ) );
				break;
			case $this->LICENSE_EXPIRED:
				$ret = sprintf( '<b style="color: red">%s %s</b>', $this->a->__( 'Expired at' ), $license['license_date_expire'] );
				break;
			case $this->LICENSE_ACTIVE:
				$ret = sprintf( '<b style="color: green">%s %s</b>', $this->a->__( 'Valid until' ), $license['license_date_expire'] );
				break;
			case $this->LICENSE_TRANSFERRING:
				$ret = sprintf( '<b style="color: #2b774d">%s</b>', $this->a->__( 'Transferring. You need to finish transfer' ) );
				break;
			default:
				$ret = $this->a->__( 'Undefined' );
		}

		return $ret;
	}

	/**
	 * Returns license code status
	 * @param string[string] $license License data
	 * @return integer One of LICENSE_XXXX codes
	 */
	private function get_licence_status_code( $license ) {
		$status = $this->LICENSE_MISSED;

		if ( $this->STATUS_TRANSFERRING == $license['active'] ) {
			$status = $this->LICENSE_TRANSFERRING;

		} else if ( empty( $license['license_date_expire'] ) || !in_array( $license['active'], [ $this->STATUS_ACTIVE ] ) ) {
			$status = $this->LICENSE_MISSED;

		} else {
			if ( strcmp( $license['license_date_expire'], date( 'Y-m-d H:i:s' ) ) > 0 ) {
				$status = $this->LICENSE_ACTIVE;

			} else {
				$status = $this->LICENSE_EXPIRED;
			}
		}

		return $status;
	}

	/**
	 * Updates account details
	 * @return array
	 * @throws \Advertikon\Exception on error
	 */
	private function get_installation_status() {
		if ( $this->a->do_cache ) {
			$ret = $this->a->cache->get( $this->a->code . '_license' );

			if ( $ret ) {
				return json_decode( $ret, true );
			}
		}

		$this->a->log( 'Getting installation status...' );

		$curl_data = array(
			'oc_version' => VERSION,
			'code'       => $this->a->code,
			'store'      => defined( 'HTTPS_CATALOG' ) ? HTTPS_CATALOG : HTTPS_SERVER,
			'version'    => $this->a->get_version(),
		);

		$ret = $this->a->curl( $this->status_url, $curl_data );
		$this->a->log( $ret );
		
		$license = json_decode( $ret, true );

		if ( !$ret || !$license ) {
			throw new \Advertikon\Exception( 'Installation data missed' );
		}

		$this->a->log( 'support is enabled for this account' );

		if ( $this->a->do_cache ) {
			$this->a->cache->set( $this->a->code . '_license', $ret, 60 * 60 );
		}

		return $license;
	}

	/**
	 * Sends support request
	 * @param array &$errors Error structure
	 * @return boolean
	 * @api
	 */
	public function ask_support() {
		$ret = [];

		$name = $this->a->post( 'support_name', '' );
		$email = $this->a->post( 'support_email' );
		$subject = $this->a->post( 'support_subject' );
		$id = $this->a->post( 'support_id' );

		$this->a->log( 'Start sending support request...' );

		try {
			if ( !$this->a->is_admin() ) {
				throw new \Advertikon\Exception( 'Access is forbidden' );
			}

			if ( !$email ) {
				throw new \Exception( $this->a->__( 'Email address is mandatory' ) );
			}

			if ( !$this->a->is_email( $email ) ) {
				throw new \Exception( $this->a->__( 'Invalid format of email address' ) );
			}

			if ( !$subject ) {
				throw new \Exception( $this->a->__( 'Support subject is mandatory' ) );
			}

			$this->a->log( 'Instantiating Mail class' );

			$mail = new \Mail();
			$this->a->init_mail( $mail );
			$subject .= '<br>Installation ID - ' . $id;

			if ( $name ) {
				$subject .= '<br>Contact person: ' . $name;
			}

			$this->a->log( 'Filling in the details...' );

			$mail->setTo( 'ticket@advertikon.freshdesk.com' );
			$mail->setSubject( 'Support request' );
			$mail->setFrom( $email );
			$mail->setHtml( $subject );
			$mail->setText( $subject );

			if( !empty( $_FILES[ 'support_attachment' ]['tmp_name'] ) ) {
				$this->a->log( 'Adding attachments...' );

				foreach( $_FILES[ 'support_attachment' ]['tmp_name'] as $a ) {
					$mail->addAttachment( $a );
				}
			}

			// Adk_Mail support
			if ( class_exists( '\\Advertikon\\Mail\\Advertikon' ) ) {
				\Advertikon\Mail\Advertikon::set_env_hook( 'admin.support' );
			}

			$this->a->log( 'Sending the email...' );
			$mail->send();

			$ret['success'] = $this->a->__( 'Support request has been successfully sent. We will get back to you shortly' );

		} catch ( \Exception $e ) {
			$ret['error'] = $e->getMessage();
			$this->a->log( $e );
		}

		$this->a->log( 'Sending response...' );

		$this->response->setOutput( json_encode( $ret ) );
	}

	/**
	 * Clear cache endpoint
	 * @api
	 */
	public function clear_cache() {
		$ret = [];
		$this->a->log( 'Start flushing the cache' );

		try {
			if ( !$this->a->is_admin() ) {
				throw new \Advertikon\Exception( $this->a->__( 'Access forbidden' ) );
			}

			if ( $this->a->cache->flush() ) {
				$ret['success'] = $this->a->__( 'Cache has been flushed' );

				$this->a->log( 'The cache has been flushed' );

			} else {
				throw new \Advertikon\Exception( $this->a->__( 'Operation failed' ) );
			}

		} catch ( \Advertikon\Exception $e ) {
			$ret['error'] = $e->getMesssage();
			$this->a->error( $e );

		} catch ( \Exception $e ) {
			$ret['error'] = $this->a->__( 'Script error' );
			$this->a->error( $e );
		}

		$this->response->setOutput( json_encode( $ret ) );
	}
	
	/**
	 * Create license by order ID
	 * @api
	 */
	public function license_by_id() {
		try {
			$resp = [];
			$installation = $this->get_installation_status();
			$data = [
				'order_id'     => $this->a->p( 'id' ),
				'extension'    => $this->a->code,
				'installation' => $installation['id'],
			];
			
			$this->a->log( '>', $data );

			$url = 'https://shop.advertikon.com.ua/support/license_by_id.php';
			$ret = $this->a->curl( $url, $data, ['timeout' => 10 ] );
			
			$this->a->log( '<', $ret );
			
			if ( false === $ret || is_null( $json = json_decode( $ret, 1 ) ) ) {
				$resp['error'] = $this->a->__(
					"Script error"
				);

			} else {
				$resp = $json;

				if ( $this->a->do_cache ) {
					$this->a->cache->delete( $this->a->code . '_license' );
				}
			}

		} catch ( Exception $e ) {
			$resp['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $resp ) );
	}

	/**
	 * Register extension by license key
	 * @api
	 */
	public function register_extension() {
		try {
			$resp = [];
			$installation = $this->get_installation_status();
			$data = [
				'license'      => $this->a->p( 'license' ),
				'installation' => $installation['id'],
			];
			
			$this->a->log( $data );

			$url = 'https://shop.advertikon.com.ua/support/register_license.php';
			$ret = $this->a->curl( $url, $data, [ 'timeout' => 10 ] );
			
			$this->a->log( $ret );
			
			if ( false === $ret || is_null( $json = json_decode( $ret, 1 ) ) ) {
				$resp['error'] = $this->a->__( "Script error" );

			} else {
				$resp = $json;

				if ( $this->a->do_cache ) {
					$this->a->cache->delete( $this->a->code . '_license' );
				}
			}

		} catch (Exception $e) {
			$resp['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $resp ) );
	}

	/**
	 * Finishes a license transfer
	 * @api
	 */
	public function finish_transfer() {
		try {
			$resp = [];
			$installation = $this->get_installation_status();
			$data = [
				'code'         => $this->a->p( 'code' ),
				'name'         => $this->a->p( 'name' ),
				'email'        => $this->a->p( 'email' ),
				'installation' => $installation['id'],
			];
			
			$this->a->log( $data );

			$url = 'https://shop.advertikon.com.ua/support/transfer_license.php';
			$ret = $this->a->curl( $url, $data, [ 'timeout' => 10 ] );
			
			$this->a->log( $ret );
			
			if ( false === $ret || is_null( $json = json_decode( $ret, 1 ) ) ) {
				$resp['error'] = $this->a->__(
					"Script error"
				);

			} else {
				$resp = $json;

				if ( $this->a->do_cache ) {
					$this->a->cache->delete( $this->a->code . '_license' );
				}
			}

		} catch (Exception $e) {
			$resp['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $resp ) );
	}

	/**
	 * Cancels a license transfer
	 * @api
	 */
	public function cancel_transfer() {
		try {
			$resp = [];
			$installation = $this->get_installation_status();
			$data = [
				'installation' => $installation['id'],
			];
			
			$this->a->log( $data );

			$url = 'https://shop.advertikon.com.ua/support/cancel_transfer.php';
			$ret = $this->a->curl( $url, $data, [ 'timeout' => 10 ] );
			
			$this->a->log( $ret );
			
			if ( false === $ret || is_null( $json = json_decode( $ret, 1 ) ) ) {
				$resp['error'] = $this->a->__(
					"Script error"
				);

			} else {
				$resp = $json;
			}

		} catch (Exception $e) {
			$resp['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $resp ) );
	}
}
