<?php
/**
 * Admin controller
 * @package adk_gdpr
 * @author Advertikon
 * @version 1.1.53
 * 
 * @source catalog/view/javascript/advertikon/jquery.js
 * @source catalog/view/javascript/advertikon/advertikon.js
 * @source admin/view/javascript/advertikon/support.js
 * @source admin/view/javascript/advertikon/spectrum.js
 * @source admin/view/javascript/advertikon/tinycolor-min.js
 * @source admin/view/javascript/advertikon/select2/*
 * 
 * @source catalog/view/theme/default/stylesheet/advertikon/advertikon.css
 * @source catalog/view/theme/default/stylesheet/advertikon/animate.min.css
 * @source admin/view/stylesheet/advertikon/select2/*
 * @source admin/view/stylesheet/advertikon/spectrum.css
 * 
 * @source image/catalog/advertikon/enable_modification.png
 * @source image/catalog/advertikon/refresh_modification.png
 * @source image/catalog/advertikon/icon.png
 * @source image/catalog/advertikon/edit.png
 * 
 * @source system/library/advertikon/advertikon.php
 * @source system/library/advertikon/exception/*
 * @source system/library/advertikon/array_iterator.php
 * @source system/library/advertikon/cache.php
 * @source system/library/advertikon/db_result.php
 * @source system/library/advertikon/exception.php
 * @source system/library/advertikon/fs.php
 * @source system/library/advertikon/option.php
 * @source system/library/advertikon/query.php
 * @source system/library/advertikon/renderer.php
 * @source system/library/advertikon/resource_wrapper.php
 * @source system/library/advertikon/task.php
 * @source system/library/advertikon/console.php
 * @source system/library/advertikon/url.php
 * @source system/library/advertikon/image.php
 * @source system/library/advertikon/placeholder.php
 * @source system/library/advertikon/update.php
 * @source system/library/advertikon/load.php
 * @source system/library/advertikon/tax.php
 * @source system/library/advertikon/twig.php
 * @source system/library/advertikon/Twig/*
 * @source system/library/advertikon/profiler.php
 * @source system/library/advertikon/compatibility_check.php
 * @source system/library/advertikon/language.php
 * @source system/library/advertikon/store.php
 * @source system/library/advertikon/parser/*
 * @source system/library/advertikon/tables.php
 * @source system/library/advertikon/table.php
 * @source system/library/advertikon/translator.php
 * @source system/library/advertikon/address.php
 * @source system/library/advertikon/account.php
 * @source system/library/advertikon/accounts.php
 * @source system/library/advertikon/customer.php
 * @source system/library/advertikon/affiliate.php
 * @source system/library/advertikon/setting.php
 * @source system/library/advertikon/shortcode.php
 * @source system/library/advertikon/pdf.php
 * @source system/library/advertikon/order.php
 * @source system/library/advertikon/transaction.php
 * 
 * @source system/library/advertikon/trait_task.php
 * @source system/library/advertikon/trait_update.php
 * @source system/library/advertikon/trait_support.php
 * @source system/library/advertikon/trait_translate.php
 * 
 * @source system/library/advertikon/interface_admin_controller.php
 */

/**
 * Class ControllerExtensionModuleAdkGDPR
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
class ControllerExtensionModuleAdkGdpr extends Controller implements Advertikon\Interface_Admin_Controller {
	use Advertikon\Trait_Update;
	use Advertikon\Trait_Task;
	use Advertikon\Trait_Support;
	use Advertikon\Trait_Translate;

	/** @var ModelExtensionModuleAdkGdpr */
	public $model = null;

	/** @var \Advertikon\Adk_Gdpr\Advertikon  */
	public $a = null;

	/**
	 * Class constructor
	 * @param Object $registry 
	 */
	public function __construct( Registry $registry ) {
		\Advertikon\Profiler::init();
		\Advertikon\Profiler::record( 'OC admin Controller __construct' );
		parent::__construct( $registry );
		\Advertikon\Profiler::record( 'OC admin Controller __construct' );

		\Advertikon\Profiler::record( 'Advertikon helper load' );
		$this->a = \Advertikon\Adk_Gdpr\Advertikon::instance();
		\Advertikon\Profiler::record( 'Advertikon helper load' );

		\Advertikon\Profiler::record( 'Advertikon admin model load' );
		$this->model = $this->a->load->model( $this->a->full_name );
		\Advertikon\Profiler::record( 'Advertikon admin model load' );

		\Advertikon\Profiler::set_logger( $this->a->console );
	}

    /**
     * Index action
     * @return void
     * @throws \Advertikon\Exception
     */
	public function index() {
		if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
			return;
		}
		
		if( true !== ( $modification_error = $this->check_modifications() ) ) {
			echo $modification_error;
			die;
		}

		$this->document->addScript( $this->a->u()->admin_script( 'advertikon/select2/select2.min.js' ) );
		$this->document->addScript( $this->a->u()->admin_script( 'advertikon/tinycolor-min.js' ) );
		$this->document->addScript( $this->a->u()->admin_script( 'advertikon/spectrum.js' ) );
		$this->document->addScript( $this->a->u()->catalog_script( 'advertikon/advertikon.js' ) );
		$this->document->addScript( $this->a->u()->admin_script( 'advertikon/adk_gdpr/adk_gdpr.js' ) );

		$this->document->addStyle( $this->a->u()->catalog_css( 'advertikon/animate.min.css' ) );
		$this->document->addStyle( $this->a->u()->admin_css( 'advertikon/spectrum.css' ) );
		$this->document->addStyle( $this->a->u()->admin_css( 'advertikon/select2/select2.min.css' ) );
		$this->document->addStyle( $this->a->u()->catalog_css( 'advertikon/advertikon.css' ) );
		$this->document->addStyle( $this->a->u()->admin_css( 'advertikon/adk_gdpr/adk_gdpr.css' ) );

		$data = [];

		$save_result = $this->model->save_config( $this->request->post );

		if ( $save_result->has_error() ) {
			$data['error_warning'] = $save_result->to_array();

		} else {
			$data['error_warning'] = '';

			if ( $save_result->get_count() ) {
				$data['success'] = $this->a->__( 'Settings have been successfully changed' );
			}
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->a->__( 'Home' ),
			'href' => $this->a->u( 'common/dashboard' ),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->a->__( 'Modules' ),
			'href' => version_compare( VERSION, '2.3', '>=' ) ?
				$this->a->u( 'extension/extension' ) : $this->a->u( 'extension/module' ),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->a->__( 'aGDPR' ),
			'href' => $this->a->u( $this->a->full_name ),
		);

		if ( version_compare( VERSION, '3', '>=' ) ) {
			$data['cancel'] = $this->a->u( 'marketplace/extension' );

		} elseif ( version_compare( VERSION, '2.3', '>=' ) ) {
			$data['cancel'] = $this->a->u( 'extension/extension' );

		} else {
			$data['cancel'] = $this->a->u( 'extension/module' );
		}

		$data['action']        = $this->a->u( $this->a->full_name );
		$data['locale']        = $this->model->get_locale();
		$data['version']       = $this->a->version();
		$data['name']          = $this->a->__( 'heading_title' );
		$data['header']        = $this->load->controller( 'common/header' );
		$data['column_left']   = $this->load->controller( 'common/column_left' );
		$data['footer']        = $this->load->controller( 'common/footer' );
		$data['compatibility'] = $this->a->check_compatibility();

		$data['consent_table'] = ( new Advertikon\Adk_Gdpr\Consent_Table( $this->a ) )->get();
		$data['version_table'] = ( new Advertikon\Adk_Gdpr\Term_Version_Table( $this->a ) )->get();
		$data['request_table'] = ( new Advertikon\Adk_Gdpr\Request_Table( $this->a ) )->get();

		$w = [ 'data' => &$data ];
		
		$this->model->get_controls( $w );
		$this->model->get_url( $w );

        if ( class_exists( 'Advertikon\ADK_Gdpr\Extended' ) ) {
            $data['update_button'] = $this->get_update_button();
            $data['support']       = $this->render_support();
            $data['breach_table']  = ( new Advertikon\Adk_Gdpr\Breach_Table( $this->a ) )->get();
            $audit_table = new Advertikon\Adk_Gdpr\Audit_Table( $this->a );
            $data['audit_table']   = $audit_table->get();
            $data['high_severity'] = $audit_table->get_notification_high_severity_count();
        }

		$this->response->setOutput( $this->a->load->view( $this->a->full_name, $data ) );
	}

	/**
	 * Install action
	 */
	public function install() {
		if ( trait_exists( '\\Advertikon\\Trait_Update' ) ) {
			$this->_install();
		}

		if ( method_exists( $this->model, 'create_tables' ) || property_exists( $this->model, 'create_tables' ) ) {
			$this->model->create_tables();
		}

		if ( @mkdir($this->a->tmp_dir, 0777, true)) {
			$this->a->log(sprintf("Create folder '%s'", $this->a->tmp_dir));
		}

		\Advertikon\Setting::set( 'start_date', date( 'Y-m-d H:i:s' ), $this->a );
	}

	/**
	 * Uninstall action
	 */
	public function uninstall() {
		if ( trait_exists( '\\Advertikon\\Trait_Update' ) ) {
			$this->_uninstall();
		}

		if ( is_dir( $this->a->data_dir ) ) {
		    /** @var \Advertikon\Fs $fs */
			$fs = new \Advertikon\Fs();

            $this->a->log(sprintf("Deleting folder '%s'", $this->a->data_dir));
            $fs->rmdir( $this->a->data_dir );
		}

		if ( trait_exists( 'Advertikon\Trait_Task' ) ) {
			$this->uninstall_task();
		}
	}

	public function consent_table() {
		if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
			return;
		}

		$data = [
			'page'    => $this->a->post( 'page', 1 ),
			'sort'    => $this->a->post( 'sort' ),
			'order'   => $this->a->post( 'order' ),
			'sorted'  => $this->a->post( 'sorted' ),
			'filters' => $this->a->post( 'filters' ),
		];

		$this->response->setOutput( ( new Advertikon\Adk_Gdpr\Consent_Table( $this->a, $data ) )->get_body() );
	}
	
	public function version_table() {
		if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
			return;
		}

		$data = [
			'page'    => $this->a->post( 'page', 1 ),
			'sort'    => $this->a->post( 'sort' ),
			'order'   => $this->a->post( 'order' ),
			'sorted'  => $this->a->post( 'sorted' ),
			'filters' => $this->a->post( 'filters' ),
		];

		$this->response->setOutput( ( new Advertikon\Adk_Gdpr\Term_Version_Table( $this->a, $data ) )->get_body() );
	}

	public function request_table() {
		if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
			return;
		}

		$data = [
			'page'    => $this->a->post( 'page', 1 ),
			'sort'    => $this->a->post( 'sort' ),
			'order'   => $this->a->post( 'order' ),
			'sorted'  => $this->a->post( 'sorted' ),
			'filters' => $this->a->post( 'filters' ),
		];

		$this->response->setOutput( ( new Advertikon\Adk_Gdpr\Request_Table( $this->a, $data ) )->get_body() );
	}

	public function breach_table() {
		if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
			return;
		}

		$data = [
			'page'    => $this->a->post( 'page', 1 ),
			'sort'    => $this->a->post( 'sort' ),
			'order'   => $this->a->post( 'order' ),
			'sorted'  => $this->a->post( 'sorted' ),
			'filters' => $this->a->post( 'filters' ),
		];

		$this->response->setOutput( ( new Advertikon\Adk_Gdpr\Breach_Table( $this->a, $data ) )->get_body() );
	}

	public function audit_table() {
		if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
			return;
		}

		$data = [
			'page'    => $this->a->post( 'page', 1 ),
			'sort'    => $this->a->post( 'sort' ),
			'order'   => $this->a->post( 'order' ),
			'sorted'  => $this->a->post( 'sorted' ),
			'filters' => $this->a->post( 'filters' ),
		];

		$this->response->setOutput( ( new Advertikon\Adk_Gdpr\Audit_Table( $this->a, $data ) )->get_body() );
	}

	public function autocomplete_concent_name() {
		$ret = [];
		if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
			return;
		}
		
		if ( isset( $this->request->get['filter_name'] ) ) {
			$table = new Advertikon\Adk_Gdpr\Consent_Table( $this->a );
			$ret = $table->filter_source_name( $this->request->get['filter_name'] );
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}
	
	public function autocomplete_concent_email() {
		$ret = [];
		if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
			return;
		}
		
		if ( isset( $this->request->get['filter_name'] ) ) {
			$table = new Advertikon\Adk_Gdpr\Consent_Table( $this->a );
			$ret = $table->filter_source_email( $this->request->get['filter_name'] );
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

	public function autocomplete_request_email() {
		$ret = [];
		if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
			return;
		}
		
		if ( isset( $this->request->get['filter_name'] ) ) {
			$table = new Advertikon\Adk_Gdpr\Request_Table( $this->a );
			$ret = $table->filter_source_email( $this->request->get['filter_name'] );
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}
	
	public function check_term_version() {
		$ret = [];
		if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
			return;
		}

		try {
			$id = isset( $this->request->get['type'] ) ? $this->request->get['type'] : false;
			
			if ( !$id ) {
				throw new Exception( 'Failed to check terms version freshness - terms ID is missing' );
			}
			
			switch( $id ) {
				case 'track_account_terms':
					$type = Advertikon\Adk_Gdpr\Term::TERM_ACCOUNT;
					break;
				case 'track_checkout_terms':
					$type = Advertikon\Adk_Gdpr\Term::TERM_CHECKOUT;
					break;
				case 'track_affiliate_terms':
					$type = Advertikon\Adk_Gdpr\Term::TERM_AFFILIATE;
					break;
				case 'track_return_terms':
					$type = Advertikon\Adk_Gdpr\Term::TERM_RETURN;
					break;
				default:
					throw new Exception( 'Failed to check terms version freshness - undefined terms type: ' . $id );
			}
			
			$term = new Advertikon\ADK_Gdpr\Term();
			
			if( $term->check_term_version( $type ) ) {
				$ret['success'] = 'Terms version has been updated';
			}
			
		} catch (Exception $ex) {
			$ret['error'] = $ex->getMessage();
			$this->a->error( $ex );
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}
	
	public function term_preview() {
		$id   = isset( $this->request->get['id'] )   ? $this->request->get['id']   : 0;
		$type = isset( $this->request->get['type'] ) ? $this->request->get['type'] : 0;

		try {
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			$term = new \Advertikon\Adk_Gdpr\Term();

			if ( $type == \Advertikon\Adk_Gdpr\Term::TYPE_VERSION ) {
				$term->show_term( $id );
				
			} else if ( $type == \Advertikon\Adk_Gdpr\Term::TYPE_ACCEPTANCE ) {
				$term->show_acceptance( $id );

			} else {
				throw new Exception( 'Undefined operation type' );
			}

		} catch (Exception $ex) {
			$this->a->error( $ex );
			echo $ex->getMessage();
			die;
		}
	}

	public function fulfill_request() {
		$ret = [];

		try {
			$code = isset( $this->request->get['code'] ) ? $this->request->get['code'] : '';

			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			if ( !$code ) {
				throw new Exception( 'Code is missing' );
			}

			$request = new \Advertikon\ADK_Gdpr\Request( $this->a );
			$request->fulfill( $code );
			$ret['success'] = $this->a->__( 'Request has been fulfilled' );

		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}

		$this->response->setOutput( json_encode( $ret ) );
	}

	public function reject_request() {
		$ret = [];
		
		try {
			$code = isset( $this->request->get['code'] ) ? $this->request->get['code'] : '';

			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}
			
			if ( !$code ) {
				throw new Exception( 'Code is missing' );
			}
			
			$request = new \Advertikon\ADK_Gdpr\Request( $this->a );
			$request->reject( $code );
			$ret['success'] = $this->a->__( 'Request has been rejected' );
			
		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

	public function add_breach_record() {
		$ret = [];

		try {
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			$fact   = $this->a->post( 'fact' );
			$effect = $this->a->post( 'effect' );
			$remedy = $this->a->post(  'remedy' );
			$breach = new \Advertikon\ADK_Gdpr\Breach( $this->a );
			$breach->add_report_to_log( [
				'fact'   => $fact,
				'effect' => $effect,
				'remedy' => $remedy,
			] );
			
			$ret['success'] = $this->a->__( 'Breach report has been added to the breach log' );
			
		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

	public function send_breach_report() {
		$ret = [];

		try {
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_MODIFY ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			$text   = $this->a->post( 'text' );
			$subject = $this->a->post( 'subject' );
			$emails = $this->a->post( 'emails' );
			$breach = new \Advertikon\ADK_Gdpr\Breach( $this->a );
			$breach->send_report( [
				'text'    => $text,
				'subject' => $subject,
				'emails'  => $emails ,
			] );
			
			$ret['success'] = $this->a->__( 'Breach report has been sent' );
			
		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

    /**
     * @throws \Advertikon\Exception
     * @throws Exception
     */
    public function get_all_emails() {
		if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
			throw new Exception( 'You have no sufficient permissions' );
		}

		$this->response->setOutput( $this->model->get_all_emails() );
	}

	public function run_audit() {
		$ret = [];

		try {
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			$audit = new \Advertikon\ADK_Gdpr\Audit( $this->a );
			$audit->new_data();
			
			$ret['success'] = $this->a->__( 'Information was refreshed' );
			
		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

	public function ignore_audit() {
		$ret = [];

		try {
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_MODIFY ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			$hash = isset( $this->request->get['id'] ) ? $this->request->get['id'] : '';

			if ( !$hash ) {
				throw new Exception( 'Identifier is missing' );
			}

			$audit = new \Advertikon\ADK_Gdpr\Audit( $this->a );
			$audit->ignore( $hash );
			
			$ret['success'] = $this->a->__( 'Audit record will be ignored' );
			
		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

	public function anonymize_order() {
		$ret = [];

		try {
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_MODIFY ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			$id = isset( $this->request->get['id'] ) ? $this->request->get['id'] : '';

			if ( !$id ) {
				throw new Exception( 'Identifier is missing' );
			}

			$order = \Advertikon\Order::get_by_id( $id, $this->a );
			$order->erase();
			$audit = new \Advertikon\Adk_Gdpr\Audit( $this->a );
			$audit->new_data();
			
			$ret['success'] = $this->a->__( 'Personal data has been anonymized' );
			
		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

	public function remove_missed_orders() {
		$ret = [];

		try {
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_MODIFY ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			$this->model->remove_missed_orders();
			$audit = new \Advertikon\Adk_Gdpr\Audit( $this->a );
			$audit->new_data();
			$ret['success'] = $this->a->__( 'Missed orders has been removed' );
			
		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

	public function delete_order() {
		$ret = [];

		try {
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_MODIFY ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			$id = isset( $this->request->get['id'] ) ? $this->request->get['id'] : '';

			if ( !$id ) {
				throw new Exception( 'Identifier is missing' );
			}

			$order = new \Advertikon\Order( [ 'order_id' => $id ], $this->a );
			$order->delete();
			$audit = new \Advertikon\Adk_Gdpr\Audit( $this->a );
			$audit->new_data();
			
			$ret['success'] = $this->a->__( 'Order has been deleted' );
			
		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

	public function anonymize_expired_orders() {
		$ret = [];

		try {
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_MODIFY ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			$audit = new \Advertikon\Adk_Gdpr\Audit( $this->a );
			
			foreach( $audit->get_expired_orders() as $data ) {
				$order = new \Advertikon\Order( $data, $this->a );
				$order->erase();
			}

			$audit->new_data();
			
			$ret['success'] = $this->a->__( 'Personal data has been anonymized' );
			
		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

	public function anonymize_unconsented_orders() {
		$ret = [];

		try {
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_MODIFY ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			$audit = new \Advertikon\Adk_Gdpr\Audit( $this->a );
			
			foreach( $audit->get_unconsented_orders() as $data ) {
				$order = new \Advertikon\Order( $data, $this->a );
				$order->erase();
			}

			$audit->new_data();
			
			$ret['success'] = $this->a->__( 'Personal data has been anonymized' );
			
		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}
		
		$this->response->setOutput( json_encode( $ret ) );
	}

    /**
     * @throws Exception
     */
    public function set_cookie() {
		$name = $this->a->post( 'name' );
		$value = $this->a->post( 'value' );
		$ret = [];

		try {
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_MODIFY ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			if ( !$name ) {
				throw new Advertikon\Exception( 'Name is missing' );
			}

			if ( is_null( $value ) ) {
				throw new Advertikon\Exception( 'Value is missing' );
			}

			$cookie = new Advertikon\ADK_Gdpr\Cookie();
			$cookie->set( $name, $value );

			$ret['success'] = $this->a->__( 'Configuration have been saved' );

		} catch ( Advertikon\Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();
		}

		$this->response->setOutput( json_encode( $ret ) );
	}

	public function download_font() {
		$ret = [];
		$name = 'freeserif';

		try{
			if ( !$this->a->has_permissions( Advertikon\Advertikon::PERMISSION_ACCESS ) ) {
				throw new Exception( 'You have no sufficient permissions' );
			}

			$font_url = 'http://advertikon.com.ua/assets/font/tcpdf/';
			$file_name = $name . '.zip';
			$file_path = \Advertikon\Adk_Gdpr\Advertikon::DIR_FONT . $file_name;

			$this->a->log( 'Downloading ' . $file_name . '...' );
			$data = $this->a->curl( $font_url . $file_name );
			file_put_contents( $file_path, $data );
			$zip = new \ZipArchive();
			
			if( false === $zip->open( $file_path ) ) {
				throw new Exception( 'Failed to open archive' );
			}

			if( false === $zip->extractTo( dirname( $file_path ) ) ) {
				throw new Exception( 'Failed to extract files from archive' );
			}

			$zip->close();
			unlink( $file_path );
			$ret['success'] = $this->a->__( 'Font has been downloaded');
			
		} catch ( Exception $e ) {
			$this->a->error( $e );
			$ret['error'] = $e->getMessage();

		}

		$this->response->setOutput( json_encode( $ret ) );
	}
}
