<?php
class ModelExtensionModuleBulkSpecial extends Model {
	
	public function addBulkSpecial($data) {

		$ProductList = explode(',',$data['ProductList']);

		$action = $data['change_action'];

		if($ProductList && $data['allproducts'] == 0) {
		  foreach ($ProductList as $key => $product_id) {
		  	$old_price = $this->getProductPrice($product_id);
		  	if($data['change_value']){
			  	if($action == 1){
			  		$discount = $old_price*$data['change_value']/100;
			  		$price = $old_price-$discount;
			  		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '".$product_id."'");
			  		$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . $this->db->escape($product_id) . "', customer_group_id = '" . $this->db->escape($data['product_special_customer_group_id']) . "', price = '" . (float)$price . "', date_start = '" . $this->db->escape($data['product_special_date_start']) . "', date_end = '" . $this->db->escape($data['product_special_date_end']) . "'");
			  	 }elseif($action == 0){	
			  		$price = $old_price - $data['change_value'];
			  		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '".$product_id."'");
			  		$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . $this->db->escape($product_id) . "', customer_group_id = '" . $this->db->escape($data['product_special_customer_group_id']) . "', price = '" . (float)$price . "', date_start = '" . $this->db->escape($data['product_special_date_start']) . "', date_end = '" . $this->db->escape($data['product_special_date_end']) . "'");
			  	}else{
			  		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '".$product_id."'");
			  	}
			}else{
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '".$product_id."'");
			}
		  }	
		}

		if($data['allproducts']){
			if($data['change_value']){
				if($data['allproducts'] == 1 && $data['change_value'] > 0){
					$products = $this->getAllProduct();
					foreach ($products as $key => $product) {
						if($action == 1){
					  		$discount = $product['price']*$data['change_value']/100;
					  		$price = $product['price'] - $discount;
					  	}else{
					  		$price = $product['price'] - $data['change_value'];
					  	}
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '".$product['product_id']."'");
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" .$product['product_id']. "', customer_group_id = '" . $this->db->escape($data['product_special_customer_group_id']) . "', price = '" . (float)$price . "', date_start = '" . $this->db->escape($data['product_special_date_start']) . "', date_end = '" . $this->db->escape($data['product_special_date_end']) . "'");
					}
				}
				if($data['allproducts'] == 2 && $data['change_value'] > 0){
					// Not in Special
					$products = $this->getNotSpecialProduct();
					foreach ($products as $key => $product) {
						if($action == 1){
					  		$discount = $product['price']*$data['change_value']/100;
					  		$price = $product['price'] - $discount;
					  	}else{
					  		$price = $product['price'] - $data['change_value'];
					  	}
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '".$product['product_id']."'");
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" .$product['product_id']. "', customer_group_id = '" . $this->db->escape($data['product_special_customer_group_id']) . "', price = '" . (float)$price . "', date_start = '" . $this->db->escape($data['product_special_date_start']) . "', date_end = '" . $this->db->escape($data['product_special_date_end']) . "'");
					}
				}
			}
		}
		return;
	}

	public function getProductPrice($product_id) {
		$query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");

		return $query->row['price'];
	}

	public function getNotSpecialProduct() {
		$query = $this->db->query("SELECT price,product_id FROM " . DB_PREFIX . "product WHERE product_id NOT IN (SELECT product_id FROM " . DB_PREFIX . "product_special)");

		return $query->rows;
	}

	public function getAllProduct() {
		$query = $this->db->query("SELECT price,product_id FROM " . DB_PREFIX . "product WHERE status = '1'");

		return $query->rows;
	}
	public function getCategories($data = array()) {
		$sql = "SELECT cp.category_id AS category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name, c1.parent_id, c1.sort_order FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		$sql .= " GROUP BY cp.category_id";
		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getManufacturers() {
		$query = $this->db->query("SELECT distinct(m.manufacturer_id),m.name,m.manufacturer_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "manufacturer m ON (m.manufacturer_id = p.manufacturer_id) ORDER BY m.name ASC");

		return $query->rows;
	}
	public function getProductsByCategoryId($category_id) {
		$query = $this->db->query("SELECT p.model,p.product_id,pd.name FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2c.category_id = '" . (int)$category_id . "' ORDER BY pd.name ASC");

		return $query->rows;
	}

	public function getProductsByMfg($manufacturer_id) {
		$query = $this->db->query("SELECT p.model,p.product_id,pd.name FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE m.manufacturer_id = '" . (int)$manufacturer_id . "' ORDER BY pd.name ASC");

		return $query->rows;
	}

	public function getExistSpecialProduct($customer_group_id,$start_date,$category_id) {
		$query = $this->db->query("SELECT p.model,p.product_id,pd.name FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_special p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2s.customer_group_id = '" . (int)$customer_group_id . "' AND p2s.date_start = '" .$start_date . "' AND p2c.category_id ='".$category_id."' ORDER BY pd.name ASC");

		return $query->rows;
	}

	
	

	public function getTotalProductSpecials($data= array()) {
		$sql = "SELECT COUNT(DISTINCT ps.product_id) AS total FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'";

		if (!empty($data['filter_date_start'])) {
			$sql .= " AND DATE(ps.date_start) >= '" . $this->db->escape($data['filter_date_start']) . "'";
		}

		if (!empty($data['filter_date_end'])) {
			$sql .= " AND DATE(ps.date_end) <= '" . $this->db->escape($data['filter_date_end']) . "'";
		}

		$query = $this->db->query($sql);
		if (isset($query->row['total'])) {
			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function getProductSpecials($data = array()) {
		$sql = "SELECT DISTINCT ps.product_id FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'";

		if (!empty($data['filter_date_start'])) {
			$sql .= " AND DATE(ps.date_start) >= '" . $this->db->escape($data['filter_date_start']) . "'";
		}

		if (!empty($data['filter_date_end'])) {
			$sql .= " AND DATE(ps.date_end) <= '" . $this->db->escape($data['filter_date_end']) . "'";
		}
		
		$sort_data = array(
			'pd.name',
			'p.model',
			'p.price',
			'ps.price',
			'p.sort_order',
			'p.status',
			'ps.date_end'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
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

		$product_data = array();

		$query = $this->db->query($sql);

		foreach ($query->rows as $result) {
			$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
		}

		return $product_data;
	}
	public function getProduct($product_id) {
		$query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image,(SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special,(SELECT date_end FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS date_end, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return array(
				'product_id'       => $query->row['product_id'],
				'name'             => $query->row['name'],
				'model'            => $query->row['model'],
				'quantity'         => $query->row['quantity'],
				'image'            => $query->row['image'],
				'price'            => ($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
				'special'          => $query->row['special'],
				'status'           => $query->row['status'],
				'date_end'         => $query->row['date_end'],
				'viewed'           => $query->row['viewed']
			);
		} else {
			return false;
		}
	}
}