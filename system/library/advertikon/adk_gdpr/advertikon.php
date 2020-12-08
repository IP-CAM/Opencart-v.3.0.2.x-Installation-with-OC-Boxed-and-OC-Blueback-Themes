<?php
/**
 * @package adk_gdpr
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon\Adk_Gdpr;

class Advertikon extends \Advertikon\Advertikon {

	/* @var string Extension type */
	public $type = 'module';

	/* @var string Extension code */
	public $code = 'adk_gdpr';

	/* @var string Class instance indent */
	protected static $c = __NAMESPACE__;

	/* @var \Registry registry object */
	public $registry = null;

	protected static $journal_menu;
	protected static $is_journal_footer_menu_set = false;

	/* @var array Extension's tables */
	public $tables = [
		'terms_acceptance' => 'adk_gdpr_terms_acceptance',
		'terms_version'    => 'adk_gdpr_terms_version',
		'request_table'    => 'adk_gdpr_request',
		'breach_log'       => 'adk_gdpr_breach_log',
	];

	public $tmp_dir = null;
	public $libray_dir = __DIR__;

	static $env = array();

	const DIR_FONT = __DIR__ . '/../vendor/tcpdf/fonts/';

	/**
	 * Returns class' singleton
	 * @return \Advertikon\Adk_Import\Advertikon
	 */
	public static function instance( $code = null ) {
		return parent::instance( $code );
	}

	public $_file = __FILE__;

	/**
	 * Class constructor
	 */
	public function __construct() {
		if ( version_compare( VERSION, '2.3.0.0', '>=' ) ) {
			$this->type = 'extension/' . $this->type;
		}

		parent::__construct();

		$this->data_dir      = $this->data_dir . 'adk_gdpr/';
		$this->tmp_dir       = $this->data_dir . 'tmp/';
	}

	public function add_to_left_panel( &$data ) {
		if ( !$this->has_permissions( \Advertikon\Advertikon::PERMISSION_ACCESS ) ||
			!\Advertikon\Setting::get( 'in_left_panel', $this ) ) {
			return;
		}

		$data['menus'][] = array(
			'id'       => 'adk-gdpr',
			'icon'	   => 'fa-shield', 
			'name'	   => 'GDPR tools',
			'href'     => $this->u( $this->full_name ),
			'children' => '',
		);	
	}

	public function add_to_left_panel2000() {
		if ( !$this->has_permissions( \Advertikon\Advertikon::PERMISSION_ACCESS ) ||
			!\Advertikon\Setting::get( 'in_left_panel', $this ) ) {
			return;
		}

		$href = $this->u( $this->full_name );

		echo <<<HTML
	<li>
		<a class="parent" href="$href"><i class="fa fa-shield fa-fw"></i> <span>GDPR tools</span></a>
	</li>
HTML;
	}

	public function add_gdpr_page_account() { 
		if ( !\Advertikon\Setting::get( 'in_account', $this ) ) {
			return '';
		}

		return $this->add_gdpr_page();
	}

	public function add_gdpr_page_footer() {
		if ( !\Advertikon\Setting::get( 'in_footer', $this ) ) {
			return '';
		}

		return $this->add_gdpr_page();
	}

	public function add_gdpr_page_header() {
		if ( !\Advertikon\Setting::get( 'in_header', $this ) ) {
			return '';
		}

		return $this->add_gdpr_page();
	}
	
	protected function add_gdpr_page() {
		if ( !\Advertikon\Setting::get( 'status', $this ) ) {
			return '';
		}

		$href = $this->u( $this->full_name . '/account' );
		$text = $this->__( 'GDPR tools' );

		return "<li class='gdpr'><a href=\"$href\">$text</a></li>";
	}

	public function check_font( $name ) {
		foreach( $this->get_fonts_list( $name ) as $font ) {
			if ( !file_exists( self::DIR_FONT . $font ) ) {
				return false;
			}
		}

		return true;
	}

	public function get_fonts_list( $name ) {
		$type = [ '', 'b', 'i', 'bi' ];
		$ext = [ '.ctg.z', '.php', '.z', ];

		$ret = [];

		foreach( $type as $t ) {
			foreach( $ext as $e ) {
				$ret[] = $name . $t . $e;
			}
		}

		return $ret;
	}

	public function journal3_add_gdpr_page( &$settings, $type ) {
		if ( !\Advertikon\Setting::get( 'status', $this ) ) {
			return;
		}

		$href = $this->u( $this->full_name . '/account' );
		$text = $this->__( 'GDPR tools' );

		if ( \Advertikon\Setting::get( 'in_footer', $this ) &&
			isset( $settings['items'] ) && self::$journal_menu === 'footer' &&
			!self::$is_journal_footer_menu_set && $type === 'links_menu' ) {

			$i = $settings['items'][ array_keys( $settings['items'])[ 0 ] ];

			if ( empty( $i['link']['href'] ) ) {
				return;
			}

			$i['title'] = $text;
			$i['link']['href'] = $href;
			$settings['items'][] = $i;
			self::$is_journal_footer_menu_set = true;
		}

		if ( \Advertikon\Setting::get( 'in_header', $this ) && isset( $settings['items'] ) && $type === 'top_menu' ) {
			foreach( $settings['items'] as $i ) {
				if ( empty( $i['link']['href'] ) || !empty( $i['items'] ) ) {
					continue;
				}

				$i['title'] = $text;
				$i['link']['href'] = $href;
				$settings['items'][] = $i;
				break;
			}

		}
	}

	public static function journal_menu( $name ) {
		self::$journal_menu = $name;
	}
}
