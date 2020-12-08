<?php
/**
 * Settings Class
 * @package Advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon\Adk_Gdpr;

use Advertikon\Exception;
use Advertikon\Table;

class Consent_Table extends Table {
	protected $headers = [];
	protected $data = [];
	protected $total = 0;
	protected $id = 'consent-table';

	protected function get_headers() {
		if ( $this->headers ) {
			return $this->headers;
		}

		$this->headers = [
			'date'        => [
				'name'     => 'Date of acceptance',
				'sortable' => true,
				'filter'   => [
					[
						'type' => 'calendar',
						'name' => 'Date start',
						'code' => 'date_from',
					],
					[
						'type' => 'calendar',
						'name' => 'Date end',
						'code' => 'date_to'
					],
				],
			],
			'term_name'   => [
				'name'     => 'Term type',
				'sortable' => true,
				'filter'   => [ [
					'type'   => 'select',
					'name'   => 'Type',
					'code'   => 'term_name',
					'class'  => 'form-control',
					'source' => [ $this, 'filter_source_term_name' ],
				] ],
			],
			'status'        => [
				'name'     => 'Status',
				'sortable' => true,
				'filter'   => [ [
					'type'         => 'select',
					'name'         => 'Status',
					'code'         => 'status',
					'class'        => 'form-control',
					'source' => [ $this, 'filter_source_status' ],
				] ],
				'process_data' => [ $this, 'get_status_name' ],
			],
			'name'        => [
				'name'     => 'Customer name',
				'sortable' => true,
				'filter'   => [ [
					'type'         => 'select',
					'name'         => 'Name',
					'code'         => 'name',
					'class'        => 'form-control',
					'autocomplete' => $this->a->u( 'autocomplete_concent_name' ),
				] ],
			],
			'email'       => [
				'name'     => 'Customer email',
				'sortable' => true,
				'filter'   => [ [
					'type'         => 'select',
					'name'         => 'Email',
					'code'         => 'email',
					'class'        => 'form-control',
					'autocomplete' => $this->a->u( 'autocomplete_concent_email' ),
				] ],
			],
			'term_version_id'        => [
				'name' => 'Show',
				'process_data' => [ $this, 'get_preview' ],
			],
			// 'customer_id' => [
			// 	'name'     => 'Customer ID',
			// 	'sortable' => true,
			// ], 
		];

		return $this->headers;
	}

	protected function get_data() {
		if ( $this->data ) {
			return $this->data;
		}

		$data = [
			'start' => ( $this->page - 1 ) * $this->page_size,
			'limit' => $this->page_size,
		];

		if ( $this->sort ) {
			$data['order_by'] = [ $this->sort => $this->order === self::ORDER_ASC ? 'ASC' : 'DESC' ];
		}

		if ( $this->filters ) {
			$data['where'] = $this->make_where();
		}

		$term = new Term();
		$this->data = $term->get_acceptance( $data );

		return $this->data;
	}

	protected function get_total_data_count() {
		if ( $this->total ) {
			return $this->total;
		}

		$term = new Term();
		$this->total = $term->count_acceptance( [

		] );

		return $this->total;
	}

	protected function get_url() {
		return $this->a->u( 'consent_table' );
	}

	protected function filter_source_term_name() {
		return [
			Term::TERM_ACCOUNT   => 'Term Account',
			Term::TERM_CHECKOUT  => 'Term Checkout',
			Term::TERM_AFFILIATE => 'Term Affiliate',
			Term::TERM_RETURN    => 'Term Return',
		];
	}

	protected function make_where() {
		$where = [];

		foreach( $this->filters as $code => $value ) {
			if ( 'date_from' === $code ) {
				$where[] = [
					'field'     => 'date',
					'operation' => '>=',
					'value'     => $value
				];

			} else if ( 'date_to' === $code ) {
				$where[] = [
					'field'     => 'date',
					'operation' => '<=',
					'value'     => $value
				];

			} else {
				if ( is_array( $value ) ) {
					$operation = 'IN';

				} else {
					$operation = '=';
				}

				$where[] = [
					'field'     => $code,
					'operation' => $operation,
					'value'     => $value
				];
			} 
		}

		return $where;
	}
	
	protected function filter_source_term_title() {
		$term = new Term();
		$ret = [];
		
		try {
			$data = [
				'field'    => '`title`',
				'distinct' => true,
			];
			
			foreach( $term->get_acceptance( $data ) as $line ) {
				$ret[ $line['title'] ] = $line['title'];
			}

		} catch (Exception $ex) {
			$this->a->error( $ex );
			return $ret;
		}
		
		return $ret;
	}
	
	// Autocomplete-------------------------------------------------------------

	public function filter_source_name( $query = '' ) {
		$term = new Term();
		$ret = [];
		
		try {
			$data = [
				'field'    => [ '`name`' ],
				'distinct' => true,
				'where' => [
					'operation' => 'like',
					'field'     => 'name',
					'value'     => '%' . $query . '%',
				], 
			];
			
			foreach( $term->get_acceptance( $data ) as $line ) {
				$ret[] = [ 'id' => $line['name'], 'name' => $line['name'] ];
			}
			
			$ret['total_count'] = count( $ret );

		} catch (Exception $ex) {
			$this->a->error( $ex );
			return $ret;
		}
		
		return $ret;
	}
	
	public function filter_source_email( $query = '' ) {
		$term = new Term();
		$ret = [];
		
		try {
			$data = [
				'field'    => [ '`email`' ],
				'distinct' => true,
				'where' => [
					'operation' => 'like',
					'field'     => 'email',
					'value'     => '%' . $query . '%',
				], 
			];
			
			foreach( $term->get_acceptance( $data ) as $line ) {
				$ret[] = [ 'id' => $line['email'], 'name' => $line['email'] ];
			}
			
			$ret['total_count'] = count( $ret );

		} catch (Exception $ex) {
			$this->a->error( $ex );
			return $ret;
		}
		
		return $ret;
	}
	
	protected function get_preview( $text, array $data ) {
		return $this->a->r( [
			'type' => 'button',
			'button_type' => 'success',
			'text_after'  => $this->a->__( 'Show' ),
			'icon'        => 'fa-file-pdf-o',
			'custom_data' => [ 'data-id' => $data['term_acceptance_id'], 'data-type' => Term::TYPE_ACCEPTANCE ],
			'class'       => 'term-version-show',
		] );
	}

	protected function get_status_name( $status ) {
		return Term::get_status_name( $status, $this->a );
	}

	protected function filter_source_status() {
		return [
			Term::STATUS_ACTIVE    => TERM::get_status_name( Term::STATUS_ACTIVE, $this->a ),
			Term::STATUS_WITHDRAWN => TERM::get_status_name( Term::STATUS_WITHDRAWN, $this->a ),
		];
	}

	protected function get_line_style( array $line ) {
		$css = [];

		switch( $line['status'] ) {
			case Term::STATUS_WITHDRAWN:
				$css['background-color'] = '#ffe3e3';
				break;
		}

		return \Advertikon\Renderer::build_css_string( $css );
	}
}
