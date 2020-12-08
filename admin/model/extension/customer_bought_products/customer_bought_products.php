<?php
class ModelExtensionCustomerBoughtProductsCustomerBoughtProducts extends Model {

	public function getProductPurchasesByCustomerId($customer_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) LEFT JOIN " . DB_PREFIX . "customer c ON (o.customer_id = c.customer_id) LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id) WHERE o.order_status_id > '0' AND c.customer_id = '" . (int)$customer_id . "' GROUP BY op.product_id");
		
		return $query->rows;
	}
	
	public function getTotalProductPurchasesByCustomerId($product_id, $customer_id) {
		$query = $this->db->query("SELECT SUM(op.quantity) AS total FROM " . DB_PREFIX . "order_product op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) LEFT JOIN " . DB_PREFIX . "customer c ON (o.customer_id = c.customer_id) WHERE o.order_status_id > '0' AND op.product_id = '" . (int)$product_id . "' AND c.customer_id = '" . (int)$customer_id . "'");
		
		if ($query->row) {
			return $query->row['total'];
		} else {
			return false;
		}
	}

	public function getTotalProductsPurchasedByCustomerId($customer_id) {
		$query = $this->db->query("SELECT SUM(op.quantity) AS total FROM " . DB_PREFIX . "order_product op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) LEFT JOIN " . DB_PREFIX . "customer c ON (o.customer_id = c.customer_id) WHERE o.order_status_id > '0' AND c.customer_id = '" . (int)$customer_id . "'");

		if ($query->row) {
			return $query->row['total'];
		} else {
			return false;
		}
	}
}