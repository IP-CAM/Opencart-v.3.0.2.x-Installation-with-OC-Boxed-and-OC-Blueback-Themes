<?php

/**
 * @package adk_gdpr
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon\Adk_Gdpr;

/**
 * Class term
 *
 * @author Advertikon
 */
class Term {
	/* @var $a \Advertikon\Adk_Gdpr\Advertikon */
	protected $a;
	
	const TERM_ACCOUNT   = 'Term Account';
	const TERM_CHECKOUT  = 'Term Checkout';
	const TERM_AFFILIATE = 'Term Affiliate';
	const TERM_RETURN    = 'Term Return';

	const TYPE_VERSION    = 1;
	const TYPE_ACCEPTANCE = 2;

	const STATUS_ACTIVE    = 1;
	const STATUS_WITHDRAWN = 2;

	static public function get_status_name( $status, Advertikon $a ) {
		switch( $status ) {
			case self::STATUS_ACTIVE:
				return $a->__( 'Active' );
			case self::STATUS_WITHDRAWN:
				return $a->__( 'Withdrawn' );
			default:
				return '';
		}
	}

	public function __construct() {
		$this->a = Advertikon::instance();
	}
	
	/**
	 * Remembers acceptance to account terms
	 * @param int $customer_id
	 * @param array $data
	 * @return null
	 */
	public function add_account_acceptance( $customer_id, array $data ) {
		try {
			if ( !$this->a->config( 'status' ) || empty( $data['agree'] ) ||
				!$this->a->config->get( 'config_account_id' ) ) {
				return;
			}

			$term_id = $this->get_last_version_id( self::TERM_ACCOUNT );
			$customer = $this->customer_data( $customer_id, $data );
			$this->add_acceptance( $customer, $term_id, self::TERM_ACCOUNT );

		} catch (\Advertikon\Exception $ex ) {
			$this->a->error( $ex );
		}

		return;
	}
	
	/**
	 * Remembers acceptance to checkout terms
	 * @param int $customer_id
	 * @param array $data
	 * @return null
	 */
	public function add_checkout_acceptance( $customer_id, array $data ) {
		try {
			if ( !$this->a->config( 'status' ) || empty( $data['agree'] ) ||
				!$this->a->config->get( 'config_checkout_id' ) ) {
				return;
			}

			$term_id = $this->get_last_version_id( self::TERM_CHECKOUT );
			$customer = $this->customer_data( $customer_id, $data );
			$this->add_acceptance( $customer, $term_id, self::TERM_CHECKOUT );

		} catch (\Advertikon\Exception $ex ) {
			$this->a->error( $ex );
		}

		return;
	}

    /**
     * Remembers acceptance to affiliate terms
     * @param $affiliate_id
     * @param array $data
     * @return null
     */
	public function add_affiliate_acceptance( $affiliate_id, array $data ) {
		try {
			if ( !$this->a->config( 'status' ) || empty( $data['agree'] ) ||
				!$this->a->config->get( 'config_affiliate_id' ) ) {
				return;
			}

			$term_id = $this->get_last_version_id( self::TERM_AFFILIATE );
			$customer = $this->customer_data( $affiliate_id, $data );
			$this->add_acceptance( $customer, $term_id, self::TERM_AFFILIATE );

		} catch (\Advertikon\Exception $ex ) {
			$this->a->error( $ex );
		}

		return;
	}

    /**
     * Remembers acceptance to account terms
     * @param $return_id
     * @param array $data
     * @return null
     */
	public function add_return_acceptance( $return_id, array $data ) {
		try {
			if ( !$this->a->config( 'status' ) || !isset( $data['agree'] ) ||
				!$this->a->config->get( 'config_return_id' ) ) {
				return;
			}

			$term_id = $this->get_last_version_id( self::TERM_RETURN );
			$customer = $this->customer_data( $return_id, $data );
			$this->add_acceptance( $customer, $term_id, self::TERM_RETURN );

		} catch (\Advertikon\Exception $ex ) {
			$this->a->error( $ex );
		}

		return;
	}

	/**
	 * Returns list of acceptances optionally filtered
	 * @param array $data
	 * @return \Advertikon\DB_Result
	 * @throws \Advertikon\Exception
	 */
	public function get_acceptance( array $data = [] ) {
		$data['table'] = $this->a->tables['terms_acceptance'];
		$q = $this->a->q()->log( 1 )->run_query( $data );

		if (!$q ) {
			throw new \Advertikon\Exception( 'Failed to fetch a list of acceptances' );
		}

		return $q;
	}

	/**
	 * Returns count of acceptances
	 * @param array $data
	 * @return integer
	 * @throws \Advertikon\Exception
	 */
	public function count_acceptance( array $data = [] ) {
		$data['table'] = $this->a->tables['terms_acceptance'];
		$data['field'] = [ 'count' => 'COUNT(*)', ];
		$q = $this->a->q()->log( 0 )->run_query( $data );

		if (!$q ) {
			throw new \Advertikon\Exception( 'Failed to fetch a count of acceptances' );
		}

		return $q['count'];
	}

    /**
     * Returns list of acceptances to account terms, optionally filtered
     * @param array $data
     * @return \Advertikon\DB_Result
     * @throws \Advertikon\Exception
     */
	public function get_account_acceptance( array $data = [] ) {
		$where = $this->a->q()->merge_where(
			isset( $data['where'] ) ? $data['where'] : [],
			[ 'term_name' => self::TERM_ACCOUNT ] 
		);

		return self::get_acceptance( $where );
	}

    /**
     * Returns count of acceptances to account terms, optionally filtered
     * @param array $where
     * @return integer
     * @throws \Advertikon\Exception
     */
	public function count_account_acceptance( array $where = [] ) {
		$w = $this->a->q()->merge_where( $where, [ 'term_name' => self::TERM_ACCOUNT ] );

		return $this->count_acceptance( $w );
	}
	
	public function check_term_version_all() {
		if ( !\Advertikon\Setting::get( 'status', $this->a ) ) {
			$this->a->log('module is disabled' );
			return;
		}
		
		foreach( [
			'track_account_terms'   => self::TERM_ACCOUNT,
			'track_checkout_terms'  => self::TERM_CHECKOUT,
			'track_affiliate_terms' => self::TERM_AFFILIATE,
			'track_return_terms'    => self::TERM_RETURN, ] as $k => $v ) {
			if ( !\Advertikon\Setting::get( $k, $this->a ) ) {
				$this->a->log('track disabled for ' . $v );
				continue;
			}

			try {
				$res = $this->check_term_version( $v );
				$this->a->log( sprintf( '%s is %s ', $v, $res ? 'updated' : 'skipped' ) );

			} catch (\Exception $ex) {
				$this->a->error( $ex );
			}
		}
	}

    /**
     * Update latest terms version if it was updated
     * @param string $term_type one of self::TERM_XXX
     * @return bool
     * @throws \Advertikon\Exception
     */
	public function check_term_version( $term_type ) {
		$last_remembered_version = $this->get_last_version( $term_type );
		$term_id = $this->get_term_id_by_type( $term_type );

		if ( !$term_id ) {
			$this->a->error( 'Failed to check if terms need to be updated: terns ID is missing' );
			return false;
		}
		
		$current_version = $this->get_term_by_id( $term_id );

		if ( !$last_remembered_version->is_empty() &&
			$this->compare_terms( $last_remembered_version, $current_version ) ) {
			return false;
		}
		
		return $this->remember_new_terms_version( $term_type, $current_version );
	}

    /**
     * Update latest terms version if it was updated
     * @param int $term_id integer
     * @return bool
     */
	public function check_term_version_id( $term_id ) {
		try {
			$term_type = $this->get_term_type_by_id( $term_id );

			if ( !$term_type ) {
				return false;
			}

			$last_remembered_version = $this->get_last_version( $term_type );
			$current_version = $this->get_term_by_id( $term_id );

			if ( !$last_remembered_version->is_empty() &&
				$this->compare_terms( $last_remembered_version, $current_version ) ) {
				return false;
			}

			return $this->remember_new_terms_version( $term_type, $current_version );

		} catch (\Exception $ex) {
			$this->a->error( $ex );
			return false;
		}
	}

    /**
     * Returns latest version of specific terms
     * @param string $term_type one of self::TERM_XXX
     * @return \Advertikon\DB_Result
     * @throws \Advertikon\Exception
     */
	public function get_last_version( $term_type ) {
		return $this->a->q()->log()->run_query( [
			'table' => $this->a->tables['terms_version'],
			'where' => [
				'field'     => 'term_name',
				'operation' => '=',
				'value'     => $term_type,
			],
			'order_by' => [ 'date' => 'desc', ],
			'limit'    => '1',
		] );
	}

    /**
     * Returns latest version's id of specific terms
     * @param string $term_type one of self::TERM_XXX
     * @return int
     * @throws \Advertikon\Exception
     */
	public function get_last_version_id( $term_type ) {
		$q = $this->a->q()->log()->run_query( [
			'table' => $this->a->tables['terms_version'],
			'field' => [ 'id' => '`term_version_id`'],
			'where' => [
				'field'     => 'term_name',
				'operation' => '=',
				'value'     => $term_type,
			],
			'order_by' => [ 'date' => 'desc', ],
			'limit'    => '1',
		] );
		
		if ( !$q ) {
			$this->a->error( 'Failed to fetch ID for latest term: ' . $term_type );
			return 0;
		}
		
		return $q['id'];
	}
	
	/**
	 * Returns list of terms versions optionally filtered
	 * @param array $data
	 * @return \Advertikon\DB_Result
	 * @throws \Advertikon\Exception
	 */
	public function get_version( array $data = [] ) {
		$data['table'] = $this->a->tables['terms_version'];
		$q = $this->a->q()->log( 1 )->run_query( $data );

		if ( !$q ) {
			throw new \Advertikon\Exception( 'Failed to fetch a list of terms versions' );
		}

		return $q;
	}

	/**
	 * Returns count of acceptances
	 * @param array $data
	 * @return integer
	 * @throws \Advertikon\Exception
	 */
	public function count_version( array $data = [] ) {
		$data['table'] = $this->a->tables['terms_version'];
		$data['field'] = [ 'count' => 'COUNT(*)', ];
		$q = $this->a->q()->log( 0 )->run_query( $data );

		if ( !$q ) {
			throw new \Advertikon\Exception( 'Failed to fetch a count of terms versions' );
		}

		return $q['count'];
	}

    /**
     * @param array $data
     * @return \Advertikon\DB_Result|bool|int
     * @throws \Advertikon\Exception
     */
    public function get_acceptance_term(array $data = [] ) {
		$data['table'] = [ 'a' => $this->a->tables['terms_acceptance'], ];
		$data['field'] = [
			'date_acceptance' => '`a`.`date`',
			'`a`.`email`',
			'`v`.*',
		];

		$data['join'] = [
			'type'  => 'left',
			'table' => [ 'v' => $this->a->tables['terms_version'] ],
			'using' => 'term_version_id',
		];

		$q = $this->a->q()->log( 1 )->run_query( $data );

		if ( !$q ) {
			throw new \Advertikon\Exception( 'Failed to fetch acceptance' );
		}

		return $q;
	}

    /**
     * @param $id
     * @throws \Advertikon\Exception
     */
    public function show_term($id ) {
		$data['where'] = [
			'field'     => 'term_version_id',
			'operation' => '=',
			'value'     => $id,
		];

		$query = $this->get_version( $data );
		$this->pdf_to_browser( $query );
	}

    /**
     * @param $id
     * @throws \Advertikon\Exception
     */
    public function show_acceptance($id ) {
		$data['where'] = [
			'field'     => 'term_acceptance_id',
			'operation' => '=',
			'value'     => $id,
		];

		$query = $this->get_acceptance_term( $data );
		$this->pdf_to_browser( $query );
	}

    /**
     * @param $email
     * @throws \Advertikon\Exception
     */
    public function withdraw($email ) {
		$this->a->q()->log( 1 )->run_query( [
			'table' => $this->a->tables['terms_acceptance'],
			'query' => 'update',
			'set'   => [
				'status'     => self::STATUS_WITHDRAWN,
				'withdrawal' => 'NOW()',
			],
			'where' => [
				'field'     => '`email`',
				'operation' => '=',
				'value'     => $email,
			],
		] );

		if ( $this->a->db->countAffected() < 1 ) {
			throw new \Advertikon\Exception( $this->a->__( 'Failed to mark consent as withdrawn' ) );
		}
	}

    /**
     * @param $email
     * @return bool
     * @throws \Advertikon\Exception
     */
    public function has_consent($email ) {
		$q = $this->a->q()->log( 1 )->run_query( [
			'table' => $this->a->tables['terms_acceptance'],
			'field' => [ 'count' => 'COUNT(*)', ],
			'where' => [
				'field'     => '`email`',
				'operation' => '=',
				'value'     => $email,
			],
		] );

		if ( $q && $q['count'] > 0 ) {
			return true;
		}

		return false;
	}

    /**
     * @param $email
     * @return bool
     * @throws \Advertikon\Exception
     */
    public function has_account_consent($email ) {
		$q = $this->a->q()->log( 1 )->run_query( [
			'table' => $this->a->tables['terms_acceptance'],
			'field' => [ 'count' => 'COUNT(*)', ],
			'where' => [
				[
					'field'     => '`email`',
					'operation' => '=',
					'value'     => $email,
				],
				[
					'field'     => 'term_name',
					'operation' => '=',
					'value'     => self::TERM_ACCOUNT,
				],
				[
					'field'     => 'status',
					'operation' => '=',
					'value'     => self::STATUS_ACTIVE,
				],
			]
		] );

		if ( $q && $q['count'] > 0 ) {
			return true;
		}

		return false;
	}

    /**
     * @param $email
     * @return bool
     * @throws \Advertikon\Exception
     */
    public function has_checkout_consent($email ) {
		$q = $this->a->q()->log( 1 )->run_query( [
			'table' => $this->a->tables['terms_acceptance'],
			'field' => [ 'count' => 'COUNT(*)', ],
			'where' => [
				[
					'field'     => '`email`',
					'operation' => '=',
					'value'     => $email,
				],
				[
					'field'     => 'term_name',
					'operation' => '=',
					'value'     => self::TERM_CHECKOUT,
				],
				[
					'field'     => 'status',
					'operation' => '=',
					'value'     => self::STATUS_ACTIVE,
				],
			]
		] );

		if ( $q && $q['count'] > 0 ) {
			return true;
		}

		return false;
	}

	////////////////////////////////////////////////////////////////////////////
	protected function pdf_to_browser( \Advertikon\DB_Result $query ) {
		$description     = $this->filter_font( html_entity_decode( $query['text'], ENT_QUOTES, 'UTF-8' ) );
		$title           = $query['title'];
		$modified        = $query['date'];
		$acceptance_date = $query['date_acceptance'];
		$modifed_text    = $this->a->__( 'Last modified:' );
		$acceptance_text = '';

		if ( $acceptance_date ) {
			$acceptance_text = $this->a->__( 'Acceptance date:' );
		}

		$html = <<<HTML
<html lang="">
	<head><title></title></head>
	<body>
		<div>
			<h1 style="text-align: center;">$title</h1>
			<h1><div style="padding: 10px;">$description</div></h1>
		</div>
		<div><b>$modifed_text</b> <i>$modified</i></div>
		<div><b>$acceptance_text</b> <i>$acceptance_date</i>
	</body>
</html>
HTML;

		$pdf = new \Advertikon\PDF( $this->a );
		$pdf->add_store_logo();
		$pdf->addd_footer();
		$pdf->set_html( $html );
		$pdf->show_pdf();
	}

    /**
     * Remove fonts since PDF has limited set of fonts
     * @param $text
     * @return string|string[]|null
     */
	protected function filter_font( $text ) {
		$t1 = preg_replace( '/font-family:.*?;/', '', $text );
		$t2 = preg_replace( '/(<font) face=".*?"/', '\1', $t1 );

		return $t2;
	}

    /**
     * Returns current account terms
     * @return array
     * @throws \Advertikon\Exception
     */
	protected function get_account_term() {
		return $this->get_term_by_id( $this->a->config->get( 'config_account_id' ) );
	}

    /**
     * Returns current checkout terms
     * @return array
     * @throws \Advertikon\Exception
     */
	protected function get_checkout_term() {
		return $this->get_term_by_id( $this->a->config->get( 'config_checkout_id' ) );
	}
	
	/**
	 * Returns terms by ID
	 * @param int $term_id
	 * @return array
	 * @throws \Advertikon\Exception
	 */
	protected function get_term_by_id( $term_id ) {
		$query = $this->a->db->query(
			"SELECT DISTINCT *"
			. " FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id "
			. " ON (i.information_id = id.information_id) LEFT JOIN " . DB_PREFIX . "information_to_store i2s "
			. " ON (i.information_id = i2s.information_id) WHERE i.information_id = '" . (int)$term_id 
			. "' AND id.language_id = '" . (int)$this->a->config->get('config_language_id') 
			. "' AND i2s.store_id = '" . (int)$this->a->config->get('config_store_id') . "' AND i.status = '1'"
		);

		if ( !$query->row ) {
			throw new \Advertikon\Exception( sprintf( 'Term with TD %s is missing', $term_id ) );
		}
		
		return $query->row;
	}

    /**
     * Remembers terms acceptance
     * @param array $customer
     * @param integer $term_id
     * @param string $term_name one of self::TERM_XXX
     * @throws \Advertikon\Exception
     */
	protected function add_acceptance( array $customer, $term_id, $term_name ) {
		$this->a->q()->log( 1 )->run_query( [
			'table' => $this->a->tables['terms_acceptance'],
			'query' => 'insert',
			'values' => [
				'term_name'       => $term_name,
				'term_version_id' => $term_id,
				'name'            => $customer['name'],
				'email'           => $customer['email'],
				'identifier'      => $customer['id'],
			],
		] );
	}

    /**
     * Compaund customer's data for further use
     * @param int $id
     * @param array $data
     * @return array
     * @throws \Advertikon\Exception
     * @throws\Advertikon\Exception
     */
	protected function customer_data( $id, array $data ) {
		$email = isset( $data['email'] ) ? $data['email'] : '';
		$name = ( isset( $data['firstname'] ) ? $data['firstname'] : '' ) . ' ' .
			( isset( $data['lastname'] ) ? $data['lastname'] : '' );

		if ( !trim( $name ) && !$id && !$email ) {
			throw new\Advertikon\Exception( 'Can not add account acceptance - unknown customer' );
		}

		return [
			'name'	=> $name,
			'email' => $email,
			'id'    => $id,
		];
	}
	
	/**
	 * Returns terms ID by it's type
	 * @param string $term_type one of TERM_XXX
	 * @return integer
	 */
	protected function get_term_id_by_type( $term_type ) {
		switch( $term_type ) {
			case self::TERM_ACCOUNT:
				return $this->a->config->get( 'config_account_id' );
			case self::TERM_CHECKOUT:
				return $this->a->config->get( 'config_checkout_id' );
			case self::TERM_AFFILIATE:
				return $this->a->config->get( 'config_affiliate_id' );
			case self::TERM_RETURN:
				return $this->a->config->get( 'config_return_id' );
		}

		return null;
	}
	
	/**
	 * Returns term's type by its ID
	 * @param int $id
	 * @return string of self::TERM_XXX
	 */
	protected function get_term_type_by_id( $id ) {
		if ( $id == $this->a->config->get( 'config_account_id' ) ) {
			return self::TERM_ACCOUNT;

		} else if ( $id == $this->a->config->get( 'config_checkout_id' ) ) {
			return self::TERM_CHECKOUT;

		} else if ( $id == $this->a->config->get( 'config_affiliate_id' ) ) {
			return self::TERM_AFFILIATE;

		} else if ( $id == $this->a->config->get( 'config_return_id' ) ) {
			return self::TERM_RETURN;
		}

		return null;
	}
	
	/**
	 * Compares two terms
	 * @param \Advertikon\DB_Result $a
	 * @param array $b
	 * @return boolean
	 */
	protected function compare_terms( \Advertikon\DB_Result $a,  array $b ) {
		if( $this->clear_text( $a['title'] ) !== $this->clear_text( $b['title'] ) ) {
			return false;
		}

		return $this->clear_text( $a['text'] ) === $this->clear_text( $b['description'] );
	}
	
	/**
	 * Makes term's text to be comparable
	 * @param string $text
	 * @return string
	 */
	protected function clear_text( $text ) {
		return preg_replace( '/\s+/m', ' ', trim( strip_tags( html_entity_decode( $text ) ) ) );
	}

    /**
     * Saves new version of a terms
     * @param string $term_type one of self::TERM_XXX
     * @param array $term
     * @return boolean
     * @throws \Advertikon\Exception
     */
	protected function remember_new_terms_version( $term_type, array $term ) {
		$this->a->q()->log( 1 )->run_query( [
			'table' => $this->a->tables['terms_version'],
			'query' => 'insert',
			'values' => [
				'term_name' => $term_type,
				'title'     => $term['title'],
				'text'      => $term['description'],
			],
		] );
		
		return $this->a->db->countAffected() > 0;
	}
}
