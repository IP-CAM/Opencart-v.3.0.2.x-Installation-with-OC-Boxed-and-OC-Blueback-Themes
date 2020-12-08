<?php
class ModelCatalogMmstock extends Model {
	
	
	public function getProductInfo($product_id) {
		
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
		
	}
	
	
	public function addMmstock($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "mmstock SET name = '" . $this->db->escape($data['name']) . "', sort_order = '" . (int)$data['sort_order'] . "'");

		$mm_sid = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "mmstock SET image = '" . $this->db->escape($data['image']) . "' WHERE mm_sid = '" . (int)$mm_sid . "'");
		}

		if (isset($data['mmstock_store'])) {
			foreach ($data['mmstock_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "mmstock_to_store SET mm_sid = '" . (int)$mm_sid . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (isset($data['keyword'])) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'mm_sid=" . (int)$mm_sid . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
		}

		$this->cache->delete('mmstock');

		return $mm_sid;
	}

	public function editMmstock($mm_sid, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "mmstock SET name = '" . $this->db->escape($data['name']) . "', sort_order = '" . (int)$data['sort_order'] . "' WHERE mm_sid = '" . (int)$mm_sid . "'");

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "mmstock SET image = '" . $this->db->escape($data['image']) . "' WHERE mm_sid = '" . (int)$mm_sid . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "mmstock_to_store WHERE mm_sid = '" . (int)$mm_sid . "'");

		if (isset($data['mmstock_store'])) {
			foreach ($data['mmstock_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "mmstock_to_store SET mm_sid = '" . (int)$mm_sid . "', store_id = '" . (int)$store_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'mm_sid=" . (int)$mm_sid . "'");

		if ($data['keyword']) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'mm_sid=" . (int)$mm_sid . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
		}

		$this->cache->delete('mmstock');
	}

	public function deleteMmstock($mm_sid) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "mmstock WHERE mm_sid = '" . (int)$mm_sid . "'");
		$this->cache->delete('mmstock');
	}

	public function getMmstock($mm_sid) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "mmstock WHERE mm_sid = '" . (int)$mm_sid . "'");

		return $query->row;
	}

	public function getMmstocks($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "mmstock";

		if (!empty($data['filter_name'])) {
			$sql .= " WHERE name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sort_data = array(
			'name',
			'sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
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

	

	public function getTotalMmstocks() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "mmstock");

		return $query->row['total'];
	}
	
	
	public function install(){
        $this->log->write('CONTACT Module --> Starting install');		
			
							
			$sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."mmstock` (
				  `mm_sid` int(11) NOT NULL AUTO_INCREMENT,
				  `name` varchar(50) NOT NULL,
				  `email` varchar(50) NOT NULL,
				  `phone` varchar(20) NOT NULL,
				  `p_id` varchar(10) NOT NULL,
				  `status` tinyint(4) NOT NULL,
				  `date_added` datetime NOT NULL,
				  `date_modified` datetime NOT NULL,
				  PRIMARY KEY (`mm_sid`)
				) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8";
						
						
			
			$query = $this->db->query($sql);
			$this->log->write('CONTACT Module --> Completed install');			
        
    }
	
	
	
	
}
