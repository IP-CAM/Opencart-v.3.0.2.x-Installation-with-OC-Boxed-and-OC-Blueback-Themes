<?php
/**
 * Requests log Class
 * @package Advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon\Adk_Gdpr;

use Advertikon\Exception;
use Advertikon\Table;

class Request_Table extends Table {
	protected $headers = [];
	protected $data    = [];
	protected $total   = 0;
	protected $id      = 'request-table';

	protected function get_headers() {
		if ( $this->headers ) {
			return $this->headers;
		}

		$this->headers = [
			'date_added'        => [
				'name'     => 'Added',
				'sortable' => true,
				'filter'   => [
					[
						'type' => 'calendar',
						'name' => 'Added from',
						'code' => 'a_date_from',
					],
					[
						'type' => 'calendar',
						'name' => 'Added to',
						'code' => 'a_date_to'
					],
				],
			],
			'date_done'        => [
				'name'     => 'Fulfilled',
				'sortable' => true,
				'filter'   => [
					[
						'type' => 'calendar',
						'name' => 'Fulfilled from',
						'code' => 'd_date_from',
					],
					[
						'type' => 'calendar',
						'name' => 'Fulfilled to',
						'code' => 'd_date_to'
					],
				],
			],
			'email'       => [
				'name'     => 'Customer email',
				'sortable' => true,
				'filter'   => [ [
					'type'         => 'select',
					'name'         => 'Email',
					'code'         => 'email',
					'class'        => 'form-control',
					'autocomplete' => $this->a->u( 'autocomplete_request_email' ),
				] ],
			],
			'type'   => [
				'name'     => 'Request Type',
				'sortable' => true,
				'filter'   => [ [
					'type'   => 'select',
					'name'   => 'Type',
					'code'   => 'term_name',
					'class'  => 'form-control',
					'source' => [ $this, 'filter_source_request_type' ],
				] ],
				'process_data' => [ $this, 'get_request_type_name' ],
			],
			'status'        => [
				'name'     => 'Status',
				'sortable' => true,
				'filter'   => [ [
					'type'         => 'select',
					'name'         => 'Status',
					'code'         => 'status',
					'class'        => 'form-control',
					'source' => [ $this, 'filter_source_request_status' ],
				] ],
				'process_data' => [ $this, 'get_request_status_name' ],
			],
			
			'ip'        => [
				'name' => 'IP',
			],
			'action' => [
				'name'         => 'Action',
				'process_data' => [ $this, 'add_action' ],
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

		$request = new Request( $this->a );
		$this->data = $request->get_request( $data );

		return $this->data;
	}

	protected function get_total_data_count() {
		if ( $this->total ) {
			return $this->total;
		}

		$request = new Request( $this->a );
		$this->total = $request->count_requests( [

		] );

		return $this->total;
	}

	protected function get_url() {
		return $this->a->u( 'request_table' );
	}

	protected function make_where() {
		$where = [];

		foreach( $this->filters as $code => $value ) {
			if ( 'a_date_from' === $code ) {
				$where[] = [
					'field'     => 'date_added',
					'operation' => '>=',
					'value'     => $value
				];

			} else if ( 'a_date_to' === $code ) {
				$where[] = [
					'field'     => 'date_added',
					'operation' => '<=',
					'value'     => $value
				];

			} else if ( 'd_date_from' === $code ) {
				$where[] = [
					'field'     => 'date_done',
					'operation' => '>=',
					'value'     => $value
				];

			} else if ( 'd_date_to' === $code ) {
				$where[] = [
					'field'     => 'date_done',
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
	
	protected function filter_source_request_type() {
		return [
			Request::TYPE_INFORMATION     => Request::get_type_name( Request::TYPE_INFORMATION, $this->a ),
			Request::TYPE_ERASE           => Request::get_type_name( Request::TYPE_ERASE, $this->a ),
			Request::TYPE_BLOCK_PROCESS   => Request::get_type_name( Request::TYPE_BLOCK_PROCESS, $this->a ),
			Request::TYPE_UNBLOCK_PROCESS => Request::get_type_name( Request::TYPE_UNBLOCK_PROCESS, $this->a ),
			Request::TYPE_WITHDRAW        => Request::get_type_name( Request::TYPE_WITHDRAW, $this->a ),
		];
	}

	protected function filter_source_request_status() {
		return [
			Request::STATUS_PENDING   => Request::get_status_name( Request::STATUS_PENDING, $this->a ),
			Request::STATUS_REJECTED  => Request::get_status_name( Request::STATUS_REJECTED, $this->a ),
			Request::STATUS_FULFILLED => Request::get_status_name( Request::STATUS_FULFILLED, $this->a ),
			Request::STATUS_EXPIRED   => Request::get_status_name( Request::STATUS_EXPIRED, $this->a ),
			Request::STATUS_CONFIRMED => Request::get_status_name( Request::STATUS_CONFIRMED, $this->a ),
		];
	}

	protected function get_request_type_name( $type ) {
		return Request::get_type_name( $type, $this->a );
	}

	protected function get_request_status_name( $status ) {
		$name = Request::get_status_name( $status, $this->a );

		if ( $status == Request::STATUS_REJECTED && isset( $this->line['reject_reason'] ) ) {
			$name .= ': ' . $this->line['reject_reason'];
		}

		if ( $status == Request::STATUS_EXPIRED ) {
			$ret = '<span class="fa-stack fa-lg" title="' . htmlentities( $name ) . '">' .
						'<i class="fa fa-hourglass fa-stack-1x"></i>' .
						'<i class="fa fa-ban fa-stack-2x text-danger"></i>' .
					'</span>';

		} else {
			$icon = $this->get_status_icon( $status );
			$ret = '<i class="fa ' . $icon . '" title="' . $name . '"></i>';
		}

		return $ret;
	}

	protected function get_status_icon( $status ) {
		switch ( $status ) {
			case Request::STATUS_PENDING:
				return 'fa-question';
			case Request::STATUS_CONFIRMED:
				return 'fa-thumbs-o-up';
			case Request::STATUS_FULFILLED:
				return 'fa-check';
			case Request::STATUS_REJECTED:
				return 'fa-exclamation-triangle';
			default:
				return '';
		}
	}

	protected function add_action( $value, array $line ) {
		$buttons = [];

		if ( in_array( $line['status'], [ Request::STATUS_CONFIRMED ] ) ) {
			$buttons[] = [
				'type'        => 'button',
				'button_type' => 'success',
				'icon'        => 'fa-check',
				'title'       => $this->a->__( 'Fulfill' ),
				'class'       => 'request-action-button',
				'custom_data' => [ 'data-url' => $this->a->u( 'fulfill_request', [ 'code' => $line['code'] ] ) ],
			];
		}

		if ( in_array( $line['status'], [ Request::STATUS_CONFIRMED ] ) ) {
			$buttons[] = [
				'type'        => 'button',
				'button_type' => 'danger',
				'icon'        => 'fa-close',
				'class'       => 'request-action-button adk-request-reject',
				'title'       => $this->a->__( 'Reject' ),
				'custom_data' => [ 'data-url' => $this->a->u( 'reject_request', [ 'code' => $line['code'] ] ) ],
			];
		}

		return $this->a->r( [
			'type'    => 'buttongroup',
			'buttons' => $buttons,
			'css'     => 'min-width: 80px;',
		] );
	}

	protected function get_line_style( array $line ) {
		$css = [];

		switch( $line['status'] ) {
			case Request::STATUS_CONFIRMED:
				$css['background-color'] = '#a0daa0';
				break;
			case Request::STATUS_REJECTED:
				$css['background-color'] = '#ffe3e3';
				break;
			case Request::STATUS_FULFILLED:
				//$css['background-color'] = 'green';
				break;
			case Request::STATUS_PENDING:
				$css['background-color'] = '#f1f1f1';
				break;
			case Request::STATUS_EXPIRED:
				$css['background-color'] = '#f2ff93';
				break;
		}

		return \Advertikon\Renderer::build_css_string( $css );
	}
	
	// Autocomplete-------------------------------------------------------------
	
	public function filter_source_email( $query = '' ) {
		$request = new Request( $this->a );
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
			
			foreach( $request->get_request( $data ) as $line ) {
				$ret[] = [ 'id' => $line['email'], 'name' => $line['email'] ];
			}
			
			$ret['total_count'] = count( $ret );

		} catch (Exception $ex) {
			$this->a->error( $ex );
			return $ret;
		}
		
		return $ret;
	}
}
