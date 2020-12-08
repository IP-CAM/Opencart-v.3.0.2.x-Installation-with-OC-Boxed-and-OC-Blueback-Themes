<?php
/**
 * PDF Wrapper Class
 * @package Advertikon
 * @author Advertikon
 * @version 1.1.53
 */
namespace Advertikon;

class PDF {
	protected $a;
	protected $pdf;
	protected $header_with_logo = false;
	protected $with_footer = false;

	public function __construct( Advertikon $a ) {
		$this->a = $a;

		require_once( __DIR__ . '/vendor/tcpdf/tcpdf.php' );

		$this->pdf = new \TCPDF();

		if ( $this->a->check_font( 'freeserif') ) {
			$this->pdf->SetFont('freeserif', '', 7 );
		}
	}

	public function set_html( $html ) {
		if ( $this->with_header_logo ) {
			$this->header_logo();
		}

		if ( $this->with_footer ) {
			$this->pdf->setFooterData();
		}

		$this->pdf->addPage();
		$this->pdf->writeHTMLCell( 0, 0, '', '', $html, 0, 1, 0, true, '', true );
	}

	/**
	 * Shows PFD in browser's PDF reader
	 */
	public function show_pdf() {
		$this->pdf->Output();
	}

	/**
	 * Returns PDF as a string
	 */
	public function return_pdf() {
		return $this->pdf->Output( '', 'S' );
	}

	/**
	 * Save to a file
	 * @param $filename string File name
	 */
	public function save_pdf( $filename ) {
		return $this->pdf->Output( $filename, 'F' );
	}

	public function add_store_logo() {
		$this->with_header_logo = true;
	}

	public function addd_footer() {
		$this->with_footer = true;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////

	protected function header_logo() {
        $image_file = $this->a->config->get( 'config_image' );
        $this->pdf->SetHeaderData( $image_file, 40, $ht='', $hs='', $tc=array(0,0,0), $lc=array(0,0,0));
		$this->pdf->SetTopMargin( 40 );
    	$this->pdf->SetHeaderMargin( 10 );
    }
}
