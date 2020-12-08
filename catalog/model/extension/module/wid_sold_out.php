<?php
class ModelExtensionModuleWidSoldOut extends Model {
		
	public function getStockStatusOutOfStock($en_out_of_stock = 'Out Of Stock') {
		//. (int)$this->config->get('config_language_id')
		$sql = "SELECT `name` FROM `" . DB_PREFIX . "stock_status` WHERE stock_status_id = (SELECT DISTINCT `stock_status_id` FROM `" . DB_PREFIX . "stock_status` WHERE `name` = '" . $en_out_of_stock . "')" ;
				
		$stock_status_out_of_stock = array();

		$query = $this->db->query($sql);

		foreach ($query->rows as $row) {
			$stock_status_out_of_stock[] = $row['name'];
		}

		return $stock_status_out_of_stock;

	}
}