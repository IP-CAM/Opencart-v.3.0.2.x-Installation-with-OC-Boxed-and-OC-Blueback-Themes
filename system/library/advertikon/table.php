<?php
/**
 * Table Class
 * Handles table functionality
 * @package Advertikon
 * @author Advertikon
 * @version 1.1.53
 */

namespace Advertikon;

abstract class Table {
	abstract protected function get_headers();
	abstract protected function get_data();
	abstract protected function get_total_data_count();
	abstract protected function get_url();

	const ORDER_ASC  = 0;
	const ORDER_DESC = -1;

	protected $content     = [];
	protected $page        = 1;
	protected $page_size   = 10;
	protected $max_buttons = 14;
	protected $total       = 0;
	protected $sort        = '';
	protected $order       = self::ORDER_ASC;
	protected $a           = null;
	protected $sorted      = false;  // if table have been sorted
	protected $filters     = [];
	protected $id          = '';
	protected $line;
	protected $where       = [];
	protected $field_map   = [];

	public function __construct( Advertikon $a, array $data = [] ) {
		if ( isset( $data['page'] ) ) {
			$this->page = $data['page'];
		}

		if ( isset( $data['sort'] ) ) {
			$this->sort = $data['sort'];
		}

		if ( isset( $data['order'] ) ) {
			$this->order = $data['order'] == self::ORDER_DESC ? self::ORDER_DESC : self::ORDER_ASC;

			if ( isset( $data['sorted'] ) ) {
				$this->sorted = true;
				$this->order =~ $this->order;
			}
		}

		if ( isset( $data['filters'] ) ) {
			$this->filters = $data['filters'];
		}

		$this->a = $a;

		$this->init();
	}

	public function get() {
		$this->combine();
		return implode( "\n", $this->content );
	}

	public function get_body() {
		$this->get_table();
		return implode( "\n", $this->content );
	}

	public function data() {
	    return $this->get_data();
    }

    public function set_where( array $where ) {
	    $this->where  = $where;
    }

    /**
     * @param $id string ID field
     * @param $text string Text field (can be mapped)
     * @param $val string Text field value
     * @param bool $unique
     * @return array
     */
    public function get_autocomplete_data( $id, $text, $val, $unique = true ) {
        $this->set_where( [
            'field'     => $this->un_map( $text ),
            'operation' => 'like',
            'value'     => '%' . $val . '%',
        ] );

        $ret = [];
        $names = [];

        foreach( $this->get_data() as $i ) {
            if ( $unique ) {
                if ( !in_array( $i[ $id ], $names  ) ) {
                    $ret[] = [ 'id' => $i[ $id ], 'name' => $i[ $text ] ];
                    $names[] = $i[ $id ];
                }

            } else {
                $ret[] = [ 'id' => $i[ $id ], 'name' => $i[ $text ] ];
            }
        }

        $ret['total_count'] = count( $ret );

        return $ret;
    }

	////////////////////////////////////////////////////////////////////////////////////////////////

	protected function init() {
		
	}

    /**
     * @return array
     * @throws Exception
     */
    protected function make_where() {
        $ret = [];

        foreach( $this->filters as $k => $v  ) {
            $ret[] = [
                'field'     => $this->un_map( $k ),
                'operation' => '=',
                'value'     => $v,
            ];
        }

        if ( $this->where ) {
            $ret = $this->a->q()->merge_where( $ret, $this->where );
        }

        return $ret;
    }

	protected function un_map( $name ) {
	    return isset( $this->field_map[ $name ] ) ? $this->field_map[ $name ] : $name;
    }

	protected function combine() {
		$this->content[] = '<div class="adk-table-wrapper">';
		$this->get_filters();
		$this->content[] = '<div class="adk-table table-responsive" data-url="' . $this->get_url() .'" id="' . $this->get_id() . '">';
		$this->get_table();
		$this->content[] = '</div>';
		$this->content[] = '</div>';
	}

	protected function get_table() {
		$this->get_overlay();
		$this->content[] = '<table class="table">';
		$this->get_table_header();
		$this->get_table_body();
		$this->get_table_footer();
		$this->content[] = '</table>';
	}

	protected function get_table_header() {
		$this->content[] = '<thead><tr>';

		foreach( $this->get_headers() as $code => $header ) {
			$this->get_table_header_item( $code, $header );
		}

		$this->content[] = '</tr></thead>';
	}

	protected function get_table_header_item( $code, array $header ) {
		if ( !empty( $header['sortable'] ) ) {
			$this->get_table_header_item_sortable( $code, $header );

		} else {
			$this->content[] = sprintf( '<th>%s</th>', $header['name'] );
		}
	}

	protected function get_table_header_item_sortable( $code, array $header ) {
		$is_active = $this->sort == $code;

		$this->content[] = sprintf(
			'<th class="sortable %s" data-sort="%s" data-order="%s">%s&nbsp;<i class="fa %s"></i></th>',
			$is_active ? 'active' : '',
			$code,
			$is_active ? $this->order : '',
			$header['name'],
			$is_active ? ( $this->order === self::ORDER_ASC ? 'fa-caret-up' : 'fa-caret-down' ) : ''
		);
	}

	protected function get_table_body() {
		$this->content[] = '<tbody>';
		$this->get_body_content();
		$this->content[] = '</tbody>';
	}

	protected function get_body_content() {
		foreach( $this->get_data() as $line ) {
			$this->get_body_line( $line );
		}
	}

	protected function get_body_line( array $line ) {
		$this->line = $line;

		$ret = '<tr ' . $this->get_line_style( $line )  . '>';

		foreach( $this->get_headers() as $k => $d ) {
			if ( array_key_exists( $k, $line ) ) {
				$value = $line[ $k ];

			} else {
				$value = '';
			}

			if ( isset( $d['process_data'] ) ) {
				$data = $d['process_data']( $value, $line );

			} else {
				$data = $line[ $k ];
			}

			$ret .= '<td>' . $data . '</td>';
		}

		$this->content[] = $ret . '</tr>';
		$this->line = '';
	}

	protected function get_table_footer() {
		$this->content[] = '<tfoot><tr><td colspan="' . count( $this->get_headers() ) . '" class="fwrapper">';
		$this->get_footer();
		$this->content[] = '</td></tr></tfoot>';
	}

	protected function get_footer() {
		$buttons_count = 0;
		$total = ceil( $this->get_total_data_count() / $this->page_size );
		$this->total = $total;

		// Rewind buttons
		if ( $total > $this->max_buttons && $this->page > 1 ) {
			if ( $this->page > 2 ) {
				$this->content[] = $this->button_first();
				$buttons_count++;
			}

			$this->content[] = $this->button_back();
			$buttons_count++;
		}

		$last_buttons = [];

		// Fast forward buttons
		if ( $total > $this->max_buttons - $buttons_count && $this->page < $this->max_buttons ) {
			$last_buttons[] = $this->button_next();
			$buttons_count++;

			if ( $this->page < $this->max_buttons - 1 ) {
				$last_buttons[] = $this->button_last();
				$buttons_count++;
			}
		}

		// Page buttons
		if ( $total + $buttons_count <= $this->max_buttons ) {
			for( $i = 1; $i < $total; $i++ ) {
				$active = $i == $this->page;
				$this->content[] = $this->get_page_button( $i, null, $active );
			}
			
		} else {
			$count = floor( ( $this->max_buttons - $buttons_count - 1 ) / 2 );

			for( $i = 1; $i <= $count; $i++ ) {
				$active = $i == $this->page;
				$this->content[] = $this->get_page_button( $i, null, $active );
			}

			$this->content[] = $this->get_page_button( '...', null, true );

			for( $i = $total - $count + 1; $i <= $total; $i++ ) {
				$active = $i == $this->page;
				$this->content[] = $this->get_page_button( $i, null, $active );
			}
		}

		if ( $last_buttons ) {
			foreach( $last_buttons as $i ) {
				$this->content[] = $i;
			}
		}

	}

	protected function get_page_button( $text, $page = null, $active = false ) {
		if ( is_null( $page ) ) {
			$page = $text;
		}

		return '<div class="adk-table-page-button ' . ( $active ? 'active' : '' ) . '" data-page="' . $page . '">' . $text . '</div>';
	}

	protected function button_first() {
		return $this->get_page_button( '<<', 1 );
	}

	protected function button_last() {
		return $this->get_page_button( '>>', $this->total );
	}

	protected function button_back() {
		return $this->get_page_button( '<', $this->page - 1 );
	}

	protected function button_next() {
		return $this->get_page_button( '>', $this->page + 1 );
	}

	protected function get_overlay() {
		$this->content[] = '<div class="adk-table-overlay">';
		$this->content[] = '<div class="adk-table-spinner">';
		$this->content[] = '<i class="fa fa-spinner fa-pulse"></i>';
		$this->content[] = '</div>';
		$this->content[] = '</div>';
	}

	protected function get_filters() {
		$count = 0;
		$process = true;

		foreach( $this->get_headers() as $header ) {
			if ( !empty( $header['filter'] ) ) {
				$count += count( $header['filter'] );
			}
		}

		if ( 0 === $count ) {
			return;
		}

		$col_type = 'col-sm-' . max( 4, 12 / $count );
		$col_num = min( 3, $count );
		$cc = 0;
		$c1 = 0;

		$this->content[] = '<div class="well">';

		foreach( $this->get_headers() as $d ) {
			if ( empty( $d['filter'] ) ) {
				continue;
			}

			$data = $d['filter'];

			while( $data ) {
				if ( 0 === $cc ) {
					$this->content[] = '<div class="row">';	
				}

				$this->get_filter_cell( array_shift( $data ), $col_type );
				$cc++;
				$cc %= $col_num;
				$c1++;

				if ( $cc === 0 || $c1 === $count ) {
					$this->content[] = '</div>';
				}
			}
		}

		$this->get_filter_button();

		$this->content[] = '</div>';
	}

	protected function get_filter_cell( array $data, $col_type ) {
		$this->content[] = '<div class="' . $col_type . '">';

		$class = $data['type'] === 'select' ? 'select2 ' : '';
		$class .= isset( $data['class'] ) ? $data['class'] : '';
		$class .= ' table-filter';

		$this->content[] = $this->a->r()->render_form_group( [
			'label'   => $data['name'],
			'cols'    => [ 'col-sm-12', 'col-sm-12', ],
			'element' => [
				'type'         => $data['type'],
				'value'        => isset( $data['source'] ) ? call_user_func( $data['source'] ) : '',
				'custom_data'  => [
					'data-code' => $data['code'],
					'multiple' => 'true',
					'data-autocomplete' => isset( $data['autocomplete'] ) ? $data['autocomplete'] : '' ],
				'class'        => $class,
			],
		] );

		$this->content[] = '</div>';
	}

	protected function get_filter_button() {
		$this->content[] = '<div class="row">';
		$this->content[] = '<div class="col-sm-2 col-sm-offset-10">';

		$this->content[] = $this->a->r( [
			'type'        => 'button',
			'class'       => 'filter-button pull-right',
			'button_type' => 'primary',
			'text_after'  => $this->a->__( 'Filter' ),
			'icon'        => 'fa-filter',
		] );

		$this->content[] = '</div>';
		$this->content[] = '</div>';
	}
	
	protected function get_id() {
		return $this->id ?: uniqid();
	}

	protected function get_line_style( array $line ) {
		return '';
	}
}
