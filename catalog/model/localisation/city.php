<?php
	class ModelLocalisationCity extends Model {
		public function getCities($zone_id) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "city WHERE zone_id = '" . (int)$zone_id . "' AND status = '1'");
			
			return $query->rows;
		}
		
		public function getCity($city_id) {
			$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "city WHERE city_id = '" . (int)$city_id . "'");

			return $query->row;
		}

		public function getCityId($zone_id, $city_name) {
			$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "city WHERE zone_id = '" . (int)$zone_id . "' AND name = '" . $city_name . "'");

			return isset($query->row) && isset($query->row['city_id']) ? $query->row['city_id'] : 0;
		}
	}
