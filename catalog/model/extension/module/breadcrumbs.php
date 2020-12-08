<?php

/*
This file is part of "Breadcrumbs+" project and subject to the terms
and conditions defined in file "EULA.txt", which is part of this source
code package and also available on the project page: https://git.io/JvWAu.
*/

class ModelExtensionModuleBreadcrumbs extends Model {
	public function getProductCategories($product_id = 0) {
		// Returns array with product categories ids and its parents
		$query = $this->db->query(
			'SELECT c.category_id, c.parent_id FROM ' . DB_PREFIX . 'product_to_category p2c LEFT JOIN ' .
			DB_PREFIX . 'category c ON (p2c.category_id = c.category_id) WHERE product_id = "' .
			(int)$product_id . '"'
		);

		return $query->rows;
	}

	public function getCategoryParent($category_id) {
		// Returns id of category parent
		$query = $this->db->query(
			'SELECT category_id, parent_id FROM ' . DB_PREFIX . 'category WHERE category_id = "' . $category_id . '"'
		);

		return $query->row['parent_id'];
	}

	// returns string with category full path, e.g 2_3_4
	public function getPathStr($path_arr = array()) {
		$path_str = '';

		foreach ($path_arr as $path) {
			if (!$path_str) {
				$path_str = $path['path_id'];
			} else {
				$path_str .= '_' . $path['path_id'];
			}
		}

		return $path_str;
	}

	public function getCategoryPath($category_id) {
		$query = $this->db->query('SELECT category_id, path_id, level FROM ' . DB_PREFIX . "category_path WHERE category_id = '" . (int)$category_id . "'");

		return $query->rows;
	}

	public function getProductPath($product_id = 0, $mode = 'default', $max_level = 0) {
		// Returns a string with product categories path (9_11_6 etc.)

		$path = '';

		if ($product_id && in_array($mode, array('short', 'long', 'last'))) {
			$categories = $this->getProductCategories($product_id);

			$path_ids = array();

			foreach ($categories as $key => $category) {
				$path_ids[$key][] = $category['category_id'];

				while ($category['parent_id']) {
					array_unshift($path_ids[$key], $category['parent_id']);

					$parent_id = $this->getCategoryParent($category['parent_id']);

					if ($parent_id == $category['parent_id']) {
						$this->log->write('[Breadcrumb+]: Recursive Category - id(' . $category['parent_id'] . ')');

						continue 2;
					}

					$category['parent_id'] = $parent_id;
				}
			}

			asort($path_ids);

			if ($mode === 'last') {
				$path_ids = end($path_ids);
			} elseif ($mode === 'short') {
				$path_ids = reset($path_ids);
			} elseif ($mode === 'long') {
				$path_ids = end($path_ids);
			}

			if ($path_ids) {
				if ($mode === 'short' || $mode === 'long') {
					$l = 0;

					foreach ($path_ids as $id) {
						if (0 == $id) {
							break;
						}

						$l++;

						if ($max_level && $l > $max_level) {
							break;
						}

						if (!$path) {
							$path = $id;
						} else {
							$path .= '_' . $id;
						}
					}
				} elseif ('last' == $mode) {
					$path = end($path_ids);
				}
			}
		}

		return $path;
	}
}
