<?php
/**
 * Advertikon Option Class
 * @author Advertikon
 * @package Advertikon
 * @version 1.1.53
 */

namespace Advertikon;
/**
 * Class Option
 * @package Advertikon
 * @method array affiliate( array $data = [])
 * @method array attribute
 * @method array attribute_group
 * @method array category
 * @method array country(array $data = [])
 * @method array currency
 * @method array currency_code
 * @method array customer(array $data = [])
 * @method array customer_group
 * @method array geo_zone
 * @method array information
 * @method array language
 * @method array layout
 * @method array length_class
 * @method array log_verbosity
 * @method array manufacturer
 * @method array option
 * @method array order_status
 * @method array product(array $data = [])
 * @method array return_statuses(bool $active = true)
 * @method array shipping_methods(bool $active = true)
 * @method array shortcode
 * @method array status
 * @method array stock_status
 * @method array store
 * @method array tax_class
 * @method array totals
 * @method array weight_class
 * @method array yes_no
 * @method array zone
 * @method array zone_all
 */
class Option {

	static private $cache = array();
	public $cache_t = 600; // 10 min
	private $a;
	
	public function __construct( Advertikon $a = null ) {
		if ( is_null( $a ) ) {
			$this->a = ADK();

		} else {
			$this->a = $a;
		}
	}

	public function __call( $name, $args = null ) {
		$method = 'get_' . $name;

		if ( !method_exists( $this, $method ) ) {
			$mess = sprintf( 'Method "%s" does not exist', $name );
			$this->a->error( new Exception( $mess ) );
			return null;
		}

		if ( !isset( self::$cache[ $name ] ) || !is_null( $args ) ) {
			self::$cache[ $name ] = call_user_func_array(
				array( $this, $method ),
				$args
			);
		} 

		return self::$cache[ $name ];
	}

	/**
	 * Returns yes/no option
	 * @return array
	 */
	protected function get_yes_no() {
		return array(
			ADK()->__( 'No' ),
			ADK()->__( 'Yes' ),
		);
	}

	/**
	 * Returns enabled/disabled option
	 * @return array
	 */
	protected function get_status() {
		return array(
			ADK()->__( 'Disabled' ),
			ADK()->__( 'Enabled' ),
		);
	}

	/**
	 * Returns currencies list
	 * @return array
	 */
	protected function get_currency() {
		$ret = array();

		ADK()->load->model( 'localisation/currency' );

		foreach( ADK()->model_localisation_currency->getCurrencies() as $c ) {
			$ret[ $c['currency_id'] ] = $c['title'];
		}

		return $ret;
	}
	
	protected function get_manufacturer() {
		$q = $this->a->q( [
			'table' => 'manufacturer',
			'field' => [ '`manufacturer_id`', '`name`'],
		] );
		
		if ( !$q ) {
			return [];
		}
		
		$ret = [];
		
		foreach( $q as $line ) {
			$ret[ $line['manufacturer_id'] ] = $line['name'];
		}
		
		return $ret;
	}

	/**
	 * Returns currencies list
	 * @return array
	 */
	protected function get_currency_code() {
		$ret = array();

		foreach( ADK()->load->model( 'localisation/currency' )->getCurrencies() as $c ) {
			$ret[ $c['code'] ] = $c['title'];
		}

		return $ret;
	}

	/**
	 * Formats shipping methods list to be shown in select element
	 * @return array
	 */
	protected function get_shipping_methods( $active = true ) {
			$ret = array( ADK()->__( 'Select shipping method' ) );

			foreach( ADK()->get_shipping_methods() as $method ) {
				$ret[ $method['code'] ] = $method['name'];
			}
	
		return $ret;
	}

	/**
	 * Formats return statuses to be shown in select element
	 * @return array
	 */
	protected function get_return_statuses( $active = true ) {
			$ret = array( ADK()->__( 'Select status' ) );

			$s = ADK()->q( array(
				'table' => 'return_status',
				'where' => array(
					'operation' => '=',
					'field'     => 'language_id',
					'value'     => ADK()->config->get( 'config_language_id' ),
				),
			) );

			foreach( $s as $status ) {
				$ret[ $status['return_status_id'] ] = $status['name'];
			}
	
		return $ret;
	}

	/**
	 * Returns system totals
	 * @return array
	 */
	protected function get_totals() {
		$ret = array();

		if ( defined( 'DIR_CATALOG' ) ) {
			$query = ADK()->q( array(
				'table' => 'extension',
				'query' => 'select',
				'where' => array(
					'field'     => '`type`',
					'operation' => '=',
					'value'     => 'total',
				),
				'order_by' => 'code',
			) );


			ADK()->load->model( 'localisation/tax_rate' );

			foreach ($query as $result) {
				$status_name = version_compare( VERSION, '3', '>=' ) ?
				'total_' . $result[ 'code' ] . '_status' :
				$result[ 'code' ] . '_status';

				if( ! ADK()->config->get( $status_name ) ) {
					continue;
				}

				$lang = 'total/' . $result['code']; 

				if ( version_compare( VERSION, '2.3.0.0', '>=' ) ) {
					$lang = 'extension/' . $lang; 
				}

				ADK()->load->language( $lang );

				if( $result ['code'] === 'tax' ) {
					foreach( ADK()->model_localisation_tax_rate->getTaxRates() as $tax ) {
						$ret[ $result['code'] . '_' . $tax['tax_rate_id'] ] = $tax['name'];

					}
				}

				else {
					$ret[ $result['code'] ] = ADK()->language->get( 'heading_title' );
				}
			}
		}

		return $ret;
	}

	/**
	 * Returns short codes list
	 * @return array
	 */
	protected function get_shortcode() {
		$shortcode = $this->a->shortcode();
		$ret = array();

		foreach( $shortcode->get_shortcode_data() as $name => $sh ) {
			$ret[ $name ] = '{' . $sh['hint'] . '}';
		} 

		return $ret;
	}

	/**
	 * Returns system's Geo Zones
	 * @return array
	 */
	protected function get_geo_zone() {
		$ret = array();
		$q = ADK()->q( array(
			'table' => 'geo_zone',
			'query' => 'select',
		) );

		foreach( $q as $geo_zone ) {
			$ret[ $geo_zone['geo_zone_id'] ] = $geo_zone['name'];
		}

		return $ret;
	}

	/**
	 * Returns system's Zones
	 * @return array
	 */
	protected function get_zone() {
		$ret = array();
		$q = ADK()->q( array(
			'table' => 'zone',
			'query' => 'select',
		) );

		foreach( $q as $zone ) {
			$ret[ $zone['zone_id'] ] = $zone['name'];
		}

		return $ret;
	}

	/**
	 * Returns system's Zones
	 * @return array
	 */
	protected function get_zone_all() {
		$ret = [];

		$q = ADK()->q( array(
			'table' => 'zone',
			'query' => 'select',
		) );

		foreach( $q as $zone ) {
			if ( !isset( $ret[ $zone['country_id'] ] ) ) {
				$ret[ $zone['country_id'] ] = [];
			}

			$ret[ $zone['country_id'] ][ $zone['zone_id'] ] = $zone['name'];
		}

		return $ret;
	}


	/**
	 * Returns system's stores
	 * @return array
	 */
	protected function get_store() {
		$ret = array( 0 => ADK()->config->get( 'config_name' ) );

		$q = ADK()->q( array(
			'table' => 'store',
			'query' => 'select'
		) );

		foreach( $q as $store ) {
			$ret[ $store['store_id'] ] = $store['name'];
		}

		return $ret;
	}

	/**
	 * Returns countries
	 * @return array
	 */
	protected function get_country( array $data = null ) {
		$ret = array();
		$query =[
			'table' => 'country',
			'query' => 'select',
		];

		if ( $data ) {
			$where = [];

			foreach( $data as $line ) {
				foreach( $line as $k => $v ) {
					$where[] = [
						'field'     => $k,
						'operation' => '=',
						'value'     => $v,
					];
				}
			}

			if ( $where ) {
				$query['where'] = $where;
			}
		}

		$q = ADK()->q()->log( 1 )->run_query( $query );

		foreach( $q as $country ) {
			$ret[ $country['country_id'] ] = $country['name'];
		}

		return $ret;
	}


	/**
	 * Returns system's customer groups
	 * @return array
	 */
	protected function get_customer_group() {
		$q = null;
		$ret = array();
		$data = array();

		$sql = "SELECT * FROM " . DB_PREFIX . "customer_group cg LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (cg.customer_group_id = cgd.customer_group_id) WHERE cgd.language_id = '" . (int)ADK()->config->get('config_language_id') . "'";

		$sort_data = array(
			'cgd.name',
			'cg.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY cgd.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = ADK()->db->query($sql);

		foreach( $query->rows as $group ) {
			$ret[ $group['customer_group_id'] ] = $group['name'];
		}

		return $ret;
	}

	/**
	 * Returns system\s order statuses
	 * @return array
	 */
	protected function get_order_status() {
		$ret = array();

		$q = ADK()->q( array(
			'table' => 'order_status',
			'query' => 'select',
			'where' => array(
				'field'     => 'language_id',
				'operation' => '=',
				'value'     => ADK()->config->get( 'config_language_id' ),
			),
		) );

		foreach( $q as $status ) {
			$ret[ $status['order_status_id'] ] = $status['name'];
		}

		return $ret;
	}

	/**
	 * Returns log verbosity options
	 * @return array
	 */
	protected function get_log_verbosity() {
		return array(
			Advertikon::LOGS_DISABLE => ADK()->__( 'Disabled' ),
			Advertikon::LOGS_ERR     => ADK()->__( 'Error' ),
			Advertikon::LOGS_MSG     => ADK()->__( 'Message' ),
			Advertikon::LOGS_DEBUG   => ADK()->__( 'Debug' ),
		);
	}

	protected function get_category() {
		$ret = array();

		$sql = "SELECT cp.category_id AS category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name, c1.parent_id, c1.sort_order FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int)ADK()->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)ADK()->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND cd2.name LIKE '%" . ADK()->db->escape($data['filter_name']) . "%'";
		}

		$sql .= " GROUP BY cp.category_id";

		$sort_data = array(
			'name',
			'sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = ADK()->db->query($sql);

		if ( $query ) {
			foreach( $query->rows as $c ) {
				$ret[ $c['category_id'] ] = $c['name'];
			}
		}

		return $ret;
	}

	protected function get_product( $data = [] ) { 
		$hash = md5( json_encode( $data ) );

		if ( ADK()->do_cache ) {
			$ret = ADK()->cache->get( 'option_product_' . $hash );

			if ( $ret ) return $ret;
		}

		$ret = [];

		$qa = [
			'table' => [ 'p' => 'product', ],
			'field' => [
				'id'   => '`p`.`product_id`',
				'name' =>'`pd`.`name`',
			],
			'join' => [
				'type' => 'LEFT',
				'table' => 'product_description',
				'alias' => 'pd',
				'using' => 'product_id',
			],
		];

		if ( isset( $data['product_id'] ) ) {
			$qa['where'] = [
				'field'     => '`p`.`product_id`',
				'operation' => 'in',
				'value'     => $data['product_id'],
			];
		}

		$q = ADK()->q( $qa );

		foreach( $q as $p ) {
			$ret[ $q['id'] ] = $q['name'];
		}

		if ( ADK()->do_cache ) {
			ADK()->cache->set( 'option_product_' . $hash, $ret, $this->cache_t );
		}

		return $ret;
	}

	protected function option_category( $data = [] ) {
		$hash = md5( json_encode( $data ) );

		if ( ADK()->do_cache ) {
			$ret = ADK()->cache->get( 'option_category_' . $hash );

			if ( $ret ) return $ret;
		}

		$ret = [];
		$results = $this->getCategories( $data ); // filter_id to filter by IDs

		foreach ( $results as $result ) {
			$ret[ $result['category_id'] ] = strip_tags( html_entity_decode( $result['name'], ENT_QUOTES, 'UTF-8' ) );
		}

		if ( ADK()->do_cache ) {
			ADK()->cache->set( 'option_category_' . $hash, $ret, $this->cache_t );
		}

		return $ret;
	}

	protected function get_customer( $data = [] ) {
		$hash = md5( json_encode( $data ) );

		if ( ADK()->do_cache ) {
			$ret = ADK()->cache->get( 'option_customer_' . $hash );

			if ( $ret ) return $ret;
		}

		$ret = [];
		$qa = [
			'table' => 'customer',
			'field' => [
				'id'   => '`customer_id`',
				'name' =>"concat(`firstname`, ' ' , `lastname`)",
			],
		];

		if ( isset( $data['customer_id'] ) ) {
			$qa['where'] = [
				'field'     => '`customer_id`',
				'operation' => 'in',
				'value'     => $data['customer_id'],
			];
		}

		$q = ADK()->q( $qa );

		foreach( $q as $p ) {
			$ret[ $q['id'] ] = $q['name'];
		}

		if ( ADK()->do_cache ) {
			ADK()->cache->set( 'option_customer_' . $hash, $ret, $this->cache_t );
		}

		return $ret;
	}

	protected function get_affilaite( $data = [] ) {
		$hash = md5( json_encode( $data ) );

		if ( ADK()->do_cache ) {
			$ret = ADK()->cache->get( 'option_affilaite_' . $hash );

			if ( $ret ) return $ret;
		}

		$ret = [];
		$qa = [
			'table' => 'affilaite',
			'field' => [
				'id'   => '`affilaite_id`',
				'name' =>"concat(`firstname`, ' ' , `lastname`)",
			],
		];

		if ( isset( $data['affilaite_id'] ) ) {
			$qa['where'] = [
				'field'     => '`affilaite_id`',
				'operation' => 'in',
				'value'     => $data['affilaite_id'],
			];
		}

		$q = ADK()->q( $qa );

		foreach( $q as $p ) {
			$ret[ $q['id'] ] = $q['name'];
		}

		if ( ADK()->do_cache ) {
			ADK()->cache->set( 'option_affilaite_' . $hash, $ret, $this->cache_t );
		}

		return $ret;
	}

	protected function getCategories($data = array()) {
		$sql = "SELECT cp.category_id AS category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name, c1.parent_id, c1.sort_order FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND cd2.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if ( !empty( $data['filter_id'] ) ) {
			$sql .= " AND c1.catagory_id IN (";

			$ids = [];

			foreach ( (array)$data['filter_id'] as $id ) {
				$ids[] = (int)$id;
			}

			$sql .= implode( ', ', $ids ) . ")";
		}

		$sql .= " GROUP BY cp.category_id";

		$sort_data = array(
			'name',
			'sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}
	
	protected function get_stock_status() {
		$ret = [];

		$data = $this->a->q( [
			'table' => 'stock_status',
			'where' => [
				'field'     => '`language_id`',
				'operation' => '=',
				'value'     => $this->a->language()->get_id(),
			],
		] );
		
		if ( count( $data ) ) {
			foreach( $data as $d ) {
				$ret[ $d['stock_status_id'] ] = $d['name'];
			}
		}
		
		return $ret;
	}
	
	protected function get_tax_class() {
		$ret = [];

		$data = $this->a->q( [
			'table' => 'tax_class',
		] );
		
		if ( count( $data ) ) {
			foreach( $data as $d ) {
				$ret[ $d['tax_class_id'] ] = $d['title'];
			}
		}
		
		return $ret;
	}
	
	protected function get_weight_class() {
		$ret = [];

		$data = $this->a->q()->log( 0 )->run_query( [
			'table' => [ 'w' => 'weight_class' ],
			'join' => [
				'type'  => 'join',
				'table' => [ 'd' => 'weight_class_description' ],
				'using' => 'weight_class_id'
			],
			'where' => [
				'operation' => '=',
				'field'     => '`language_id`',
				'value'     => $this->a->language()->get_id(),
			],
		] );

		if ( count( $data ) ) {
			foreach( $data as $d ) {
				$ret[ $d['weight_class_id'] ] = $d['title'];
			}
		}
		
		return $ret;
	}
	
	protected function get_length_class() {
		$ret = [];

		$data = $this->a->q()->log( 0 )->run_query( [
			'table' => [ 'w' => 'length_class' ],
			'join' => [
				'type'  => 'join',
				'table' => [ 'd' => 'length_class_description' ],
				'using' => 'length_class_id'
			],
			'where' => [
				'operation' => '=',
				'field'     => '`language_id`',
				'value'     => $this->a->language()->get_id(),
			],
		] );

		if ( count( $data ) ) {
			foreach( $data as $d ) {
				$ret[ $d['length_class_id'] ] = $d['title'];
			}
		}
		
		return $ret;
	}

	protected function get_option() {
		$ret = [];

		$data = $this->a->q()->log( 0 )->run_query( [
			'table' => [ 'w' => 'option' ],
			'join' => [
				'type'  => 'join',
				'table' => [ 'd' => 'option_description' ],
				'using' => 'option_id'
			],
			'where' => [
				'operation' => '=',
				'field'     => '`language_id`',
				'value'     => $this->a->language()->get_id(),
			],
		] );

		if ( count( $data ) ) {
			foreach( $data as $d ) {
				$ret[ $d['option_id'] ] = $d['name'];
			}
		}
		
		return $ret;
	}
	
	protected function get_language() {
		$ret = [];
		
		foreach( Language::get_languages( $this->a ) as $l ) {
			$ret[ $l->get_id() ] = $l->get_name();
		}
		
		return $ret;
	}

	protected function get_information() {
		$ret = [];

		$q = $this->a->q( [
			'table' => 'information_description',
			'field' => [ '`information_id`', '`title`', ],
			'where' => [
				'field'     => '`language_id`',
				'operation' => '=',
				'value'     => $this->a->language()->get_id(),
			],
		] );

		if ( $q ) {
			$ret[ 0 ] = '';

			foreach( $q as $line ) {
				$ret[ $line['information_id'] ] = $line['title'];
			}
		}

		return $ret;
	}

	protected function get_attribute() {
		$ret = [];

		$data = $this->a->q()->log( 0 )->run_query( [
			'table' => [ 'w' => 'attribute' ],
			'field' => [ '`attribute_id`', '`name`' ],
			'join' => [
				'type'  => 'join',
				'table' => [ 'd' => 'attribute_description' ],
				'using' => 'attribute_id'
			],
			'where' => [
				'operation' => '=',
				'field'     => '`language_id`',
				'value'     => $this->a->language()->get_id(),
			],
		] );

		if ( count( $data ) ) {
			foreach( $data as $d ) {
				$ret[ $d['attribute_id'] ] = $d['name'];
			}
		}
		
		return $ret;
	}

	protected function get_attribute_group() {
		$ret = [];

		$data = $this->a->q()->log( 0 )->run_query( [
			'table' => [ 'w' => 'attribute_group' ],
			'field' => [ '`w`.`attribute_group_id`', '`name`' ],
			'join' => [
				'type'  => 'join',
				'table' => [ 'd' => 'attribute_group_description' ],
				'using' => 'attribute_group_id'
			],
			'where' => [
				'operation' => '=',
				'field'     => '`language_id`',
				'value'     => $this->a->language()->get_id(),
			],
		] );

		if ( count( $data ) ) {
			foreach( $data as $d ) {
				$ret[ $d['attribute_group_id'] ] = $d['name'];
			}
		}

		return $ret;
	}

	protected function get_layout() {
		$q = $this->a->q( [
			'table' => 'layout',
			'field' => [ '`layout_id`', '`name`'],
		] );
		
		if ( !$q ) {
			return [];
		}
		
		$ret = [];
		
		foreach( $q as $line ) {
			$ret[ $line['layout_id'] ] = $line['name'];
		}
		
		return $ret;
	}
}
