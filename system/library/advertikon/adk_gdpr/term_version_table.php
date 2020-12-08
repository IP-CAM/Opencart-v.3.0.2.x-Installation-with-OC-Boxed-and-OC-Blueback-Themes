<?php
/**
 * Table Class
 * @package Advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon\Adk_Gdpr;

use Advertikon\Exception;
use Advertikon\Table;

class Term_Version_Table extends Table {
	protected $headers = [];
	protected $data = [];
	protected $total = 0;
	protected $id = 'term-version-table';

	protected function get_headers() {
		if ( $this->headers ) {
			return $this->headers;
		}

		$this->headers = [
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
			'title'       => [
				'name'     => 'Term name',
				'sortable' => true,
				'filter'   => [ [
					'type'   => 'select',
					'name'   => 'Term',
					'code'   => 'title',
					'class'  => 'form-control',
					'source' => [ $this, 'filter_source_term_title' ],
				] ],
			],
			'date'        => [
				'name'     => 'Modification date',
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
			'text'        => [
				'name' => 'Action',
				'process_data' => [ $this, 'get_preview' ],
			],
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
		$this->data = $term->get_version( $data );

		return $this->data;
	}

	protected function get_total_data_count() {
		if ( $this->total ) {
			return $this->total;
		}

		$term = new Term();
		$this->total = $term->count_version( [

		] );

		return $this->total;
	}

	protected function get_url() {
		return $this->a->u( 'version_table' );
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
			
			foreach( $term->get_version( $data ) as $line ) {
				$ret[ $line['title'] ] = $line['title'];
			}

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
			'custom_data' => [ 'data-id' => $data['term_version_id'], 'data-type' => Term::TYPE_VERSION, ],
			'class'       => 'term-version-show',
		] );
	}
}
