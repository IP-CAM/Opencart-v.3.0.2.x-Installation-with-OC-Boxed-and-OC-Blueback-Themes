<?php
	class ControllerExtensionModuleStockEmagCel extends Controller {
		
		private $error = [];
		
		public function index() {
			$this->load->model('setting/setting');
		}
		
		public function emag() {			
			
			file_put_contents(DIR_LOGS . 'emag.log', print_r($this->request->get, true). PHP_EOL, FILE_APPEND);
			
			if (!isset($this->request->get['order_id'])) return true;
			
			$order_id = $this->request->get['order_id'];
			
			if ($this->config->get('module_stock_emag_cel_emag_one_status')) {
				$username = $this->config->get('module_stock_emag_cel_emag_one_username');
				$password = $this->config->get('module_stock_emag_cel_emag_one_password');
				$column = 'emag_one';
			} elseif ($this->config->get('module_stock_emag_cel_emag_two_status')) {
				$username = $this->config->get('module_stock_emag_cel_emag_two_username');
				$password = $this->config->get('module_stock_emag_cel_emag_two_password');
				$column = 'emag_two';
			} else {
				return true;
			}
			
			$request = [
				'id' => $order_id
			];
			
			$orders = $this->_callEmag($request, $username, $password, '/order/read');

			if (!$orders) error_log('Eroare _callEmagOrder');
			$cel = array(
				'products' => array(),
				'options' =>array()
			);
			foreach ($orders as $order) {
				if ($order['status'] == 1) {
					foreach ($order['products'] as $product) {
						$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$product['quantity'] . ") WHERE `" . $column . "` = '" . (int)$product['product_id'] . "' ");
						if ($this->db->countAffected() == 0) {							
							$query = $this->db->query("SELECT product_option_value_id, product_id FROM " . DB_PREFIX . "product_option_value WHERE `" . $column . "` = '" . (int)$product['product_id'] . "' ");
							$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$product['quantity'] . ") WHERE `" . $column . "` = '" . (int)$product['product_id'] . "' ");
							if ($this->db->countAffected() > 0) {
								$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$product['quantity'] . ") WHERE `product_id` = '" . (int)$query->row['product_id'] . "' ");
							}
							if (!empty($query->row['product_option_value_id'])) {
								$cel['options'][] = $query->row['product_option_value_id'];
							}
						} else {
							$query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE `" . $column . "` = '" . (int)$product['product_id'] . "' ");
							if (!empty($query->row['product_id'])) {
								$cel['products'][] = $query->row['product_id'];
							}
						}
					}
				} elseif ($order['status'] == 0 || $order['status'] == 5) {
					foreach ($order['products'] as $product) {
						$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity + " . (int)$product['quantity'] . ") WHERE `" . $column . "` = '" . (int)$product['product_id'] . "' ");
						if ($this->db->countAffected() == 0) {							
							$query = $this->db->query("SELECT product_option_value_id, product_id FROM " . DB_PREFIX . "product_option_value WHERE `" . $column . "` = '" . (int)$product['product_id'] . "' ");
							$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity + " . (int)$product['quantity'] . ") WHERE `" . $column . "` = '" . (int)$product['product_id'] . "' ");
							if ($this->db->countAffected() > 0) {
								$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity + " . (int)$product['quantity'] . ") WHERE `product_id` = '" . (int)$query->row['product_id'] . "' ");
							}
							if (!empty($query->row['product_option_value_id'])) {
								$cel['options'][] = $query->row['product_option_value_id'];
							}
						} else {
							$query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE `" . $column . "` = '" . (int)$product['product_id'] . "' ");
							if (!empty($query->row['product_id'])) {
								$cel['products'][] = $query->row['product_id'];
							}
						}
					}
				}
			}
			if (count($cel['options']) > 0 || count($cel['products']) > 0) {
				$this->cel_product_stock($cel);
			}
		}
		
		public function emag_order_stock($data = array()) {
			$this->load->model('checkout/order');
			
			if (empty($data['order_id'])) return false;
			
			$order_id = $data['order_id'];
			
			$order_products = $this->model_checkout_order->getOrderProducts($order_id);
			
			if ($this->config->get('module_stock_emag_cel_emag_one_status')) {
				$username = $this->config->get('module_stock_emag_cel_emag_one_username');
				$password = $this->config->get('module_stock_emag_cel_emag_one_password');
				$column = 'emag_one';
			} elseif ($this->config->get('module_stock_emag_cel_emag_two_status')) {
				$username = $this->config->get('module_stock_emag_cel_emag_two_username');
				$password = $this->config->get('module_stock_emag_cel_emag_two_password');
				$column = 'emag_two';
			} else {
				return true;
			}
			
			$emag_products = array();
			
			foreach ($order_products as $order_product) {
				$order_options = $this->model_checkout_order->getOrderOptions($order_id, $order_product['order_product_id']);
				if (count($order_options) > 0) {
					foreach ($order_options as $order_option) {
						$query = $this->db->query("SELECT `" . $column . "`, quantity FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "'");
						if (!empty($query->row[$column])) {
							$emag_products[] = array(
								'id' => $query->row[$column],
								'stock' => array(
									array(
										'warehouse_id' => 1,
										'value'        => $query->row['quantity']
									)
								)
							);
						}
					}
				} else {
					$query = $this->db->query("SELECT `" . $column . "`, quantity FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$order_product['product_id'] . "'");
					if (!empty($query->row[$column])) {
						$emag_products[] = array(
							'id' => $query->row[$column],
							'stock' => array(
								array(
									'warehouse_id' => 1,
									'value'        => $query->row['quantity']
								)
							)
						);
					}
				}
			}

			if (!count($emag_products)) error_log('Eroare update emag product empty, order opencart: ' . $order_id);
			
			$response = $this->_callEmag($emag_products, $username, $password, '/product_offer/save');
			
			if ($response === false) error_log('Eroare update emag product error, order opencart: ' . $order_id);
			
		}
		
		public function emag_product_stock($data = array()) {
			
			if ($this->config->get('module_stock_emag_cel_emag_one_status')) {
				$username = $this->config->get('module_stock_emag_cel_emag_one_username');
				$password = $this->config->get('module_stock_emag_cel_emag_one_password');
				$column = 'emag_one';
			} elseif ($this->config->get('module_stock_emag_cel_emag_two_status')) {
				$username = $this->config->get('module_stock_emag_cel_emag_two_username');
				$password = $this->config->get('module_stock_emag_cel_emag_two_password');
				$column = 'emag_two';
			} else {
				return true;
			}
			
			$emag_products = array();
			
			if (isset($data['products']) && count($data['products']) > 0) {
				$query = $this->db->query("SELECT `" . $column . "`, quantity FROM " . DB_PREFIX . "product WHERE product_id IN (". implode(', ', $data['products']) .")");
				foreach ($query->rows as $row) {
					if (empty($row[$column])) continue;
					$emag_products[] = array(
						'id' => $row[$column],
						'stock' => array(
							array(
								'warehouse_id' => 1,
								'value'        => $row['quantity']
							)
						)
					);
				}
			}
			
			if (isset($data['options']) && count($data['options']) > 0) {
				$query = $this->db->query("SELECT `" . $column . "`, quantity FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id IN (". implode(', ', $data['options']) .")");
				foreach ($query->rows as $row) {
					if (empty($row[$column])) continue;
					$emag_products[] = array(
						'id' => $row[$column],
						'stock' => array(
							array(
								'warehouse_id' => 1,
								'value'        => $row['quantity']
							)
						)
					);
				}
			}

			if (!count($emag_products)) error_log('Eroare update emag product empty, data opencart: ' . print_r($data, true));

			$response = $this->_callEmag($emag_products, $username, $password, '/product_offer/save');
			
			if ($response === false) error_log('Eroare update emag product error, data opencart: ' .  print_r($data, true));
			
		}
		
		public function emag_update_products($data = array()) {
			set_time_limit(0);
			error_reporting(0);
			ini_set('display_errors', 0);

			//if (empty($this->config->get('module_stock_emag_cel_emag_cron'))) return true;
			
			$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '0', serialized = '0'  WHERE `code` = 'module_stock_emag_cel' AND `key` = 'module_stock_emag_cel_emag_cron' AND store_id = '0'");
			
			if ($this->config->get('module_stock_emag_cel_emag_one_status')) {
				$username = $this->config->get('module_stock_emag_cel_emag_one_username');
				$password = $this->config->get('module_stock_emag_cel_emag_one_password');
				$column = 'emag_one';
			} elseif ($this->config->get('module_stock_emag_cel_emag_two_status')) {
				$username = $this->config->get('module_stock_emag_cel_emag_two_username');
				$password = $this->config->get('module_stock_emag_cel_emag_two_password');
				$column = 'emag_two';
			} else {
				return true;
			}
			
			$query = $this->db->query("SELECT `" . $column . "`, quantity FROM " . DB_PREFIX . "product WHERE `" . $column . "` IS NOT NULL AND `" . $column . "` <> 0 AND `" . $column . "` <> ''");
					

			$emag_products = array();
			
			foreach ($query->rows as $row) {
				if (empty($row[$column])) continue;
				
				$emag_products[] = array(
					'id' => $row[$column],
					'stock' => array(
						array(
							'warehouse_id' => 1,
							'value'        => $row['quantity']
						)
					)
				);
				
				// if (count($emag_products) > 50) {
				// 	$response = $this->_callEmag($emag_products, $username, $password, '/product_offer/save');
				// 	if ($response === false) error_log('Eroare update emag all products');
					
				// 	$emag_products = array();
				// }
			}
			
			// if (count($emag_products) > 0) {
			// 	$response = $this->_callEmag($emag_products, $username, $password, '/product_offer/save');
			// 	if ($response === false) error_log('Eroare update emag all products');
				
			// 	$emag_products = array();
			// }
			
			$query_second = $this->db->query("SELECT `" . $column . "`, quantity FROM " . DB_PREFIX . "product_option_value WHERE `" . $column . "` IS NOT NULL AND `" . $column . "` <> 0 AND `" . $column . "` <> ''");
			
			// echo '<pre>'; print_r(count($query_second->rows)); echo '</pre>'; die();

			foreach ($query_second->rows as $row) {
				if (empty($row[$column])) continue;
				
				$emag_products[] = array(
					'id' => $row[$column],
					'stock' => array(
						array(
							'warehouse_id' => 1,
							'value'        => $row['quantity']
						)
					)
				);
				
				// if (count($emag_products) > 90) {
				// 	$response = $this->_callEmag($emag_products, $username, $password, '/product_offer/save');
				// 	if ($response === false) error_log('Eroare update emag all products');
					
				// 	$emag_products = array();
				// }
			}
			
			echo '<pre>'; print_r(count($query->rows)); echo '</pre>';

			if (count($emag_products) > 0) {

				foreach (array_chunk($emag_products, 100) as $request) {
					$response = $this->_callEmag($request, $username, $password, '/product_offer/save');
					if ($response === false) error_log('Eroare update emag all products');
				}

				
				
				$emag_products = array();
			}

			echo '<pre>'; print_r('Gata'); echo '</pre>'; die();

			
		}
		
		protected function _callEmag($request, $username = '', $password = '', $url = '') {
			if (empty($username) || empty($password) || !count($request) || empty($url)) return false;
			
			$url = 'https://marketplace.emag.ro/api-3' . $url;
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['data' => $request]));
			
			$result = curl_exec($ch);
			$result = json_decode($result, true);

			if (empty($result['isError'])) return $result['results'];
			
			return false;
		}
		
		public function cel() {
			
			file_put_contents(DIR_LOGS . 'cel.log', print_r($this->request->get, true). PHP_EOL, FILE_APPEND);
			
			if ($this->config->get('module_stock_emag_cel_cel_status')) {
				$username = $this->config->get('module_stock_emag_cel_cel_username');
				$password = $this->config->get('module_stock_emag_cel_cel_password');
			} else {
				return true;
			}

			$token = $this->_authCel($username, $password);
			
			if (!$token) error_log('Cel error token');
			
			if (!isset($this->request->get['type'])) return true;
			$emag = array(
				'products' => array(),
				'options' =>array()
			);
			
			if ($this->request->get['type'] == 1) {
				$cel_orders = json_decode(urldecode($this->request->get['value']), true);
				
				foreach ($cel_orders['list'] as $cel_order) {
					$request = array('order' => $cel_order);
					
					$order_info = $this->_callCel($request, $token, '/orders/getOrder');

					if (!$order_info) error_log('Eroare get order cel: ' . print_r($request, true));
					
					foreach ($order_info['products'] as $product) {
						if ($product['products_model'] == 'taxa_transport') continue;
						$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - ". (int)$product['products_quantity'] .") WHERE `cel` = '". (int)$product['products_model'] ."' ");
						if ($this->db->countAffected() == 0) {							
							$query = $this->db->query("SELECT product_option_value_id, product_id FROM " . DB_PREFIX . "product_option_value WHERE `cel` = '" . (int)$product['products_model'] . "' ");
							$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - ". (int)$product['products_quantity'] .") WHERE `cel` = '". (int)$product['products_model'] ."' ");
							if ($this->db->countAffected() > 0) {
								$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$product['products_quantity'] . ") WHERE `product_id` = '" . (int)$query->row['product_id'] . "' ");
							}
							if (!empty($query->row['product_option_value_id'])) {
								$emag['options'][] = $query->row['product_option_value_id'];
							}
						} else {
							$query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE `cel` = '" . (int)$product['products_model'] . "' ");
							if (!empty($query->row['product_id'])) {
								$emag['products'][] = $query->row['product_id'];
							}
						}
					}
				}
			}
			
			if ($this->request->get['type'] == 24 || $this->request->get['type'] == 11) {
				$cel_orders = json_decode(urldecode($this->request->get['value']), true);
				
				$order_id = isset($cel_orders['order_id']) ? $cel_orders['order_id'] : $cel_orders['cmd'];
				
				$request = ['order' => $order_id];
				
				$order_info = $this->_callCel($request, $token, '/orders/getOrder');
				
				if (!$order_info) error_log('Eroare get order cel: ' . print_r($request, true));
				
				foreach ($order_info['products'] as $product) {
					if ($product['products_model'] == 'taxa_transport') continue;
					$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity + " . (int)$product['products_quantity'] . ") WHERE `cel` = '" . (int)$product['products_model'] . "' ");
					if ($this->db->countAffected() == 0) {						
						$query = $this->db->query("SELECT product_option_value_id, product_id FROM " . DB_PREFIX . "product_option_value WHERE `cel` = '" . (int)$product['products_model'] . "' ");
						$this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity + " . (int)$product['products_quantity'] . ") WHERE `cel` = '" . (int)$product['products_model'] . "' ");
						if ($this->db->countAffected() > 0) {
								$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$product['products_quantity'] . ") WHERE `product_id` = '" . (int)$query->row['product_id'] . "' ");
							}
						if (!empty($query->row['product_option_value_id'])) {
							$emag['options'][] = $query->row['product_option_value_id'];
						}
					} else {
						$query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE `cel` = '" . (int)$product['products_model'] . "' ");
						if (!empty($query->row['product_id'])) {
							$emag['products'][] = $query->row['product_id'];
						}
					}
				}
			}
			
			if (count($emag['options']) > 0 || count($emag['products']) > 0) {
				$this->emag_product_stock($emag);
			}
			
		}
		
		public function cel_order_stock($data = array()) {
			$this->load->model('checkout/order');
			
			if (empty($data['order_id'])) return false;
			
			$order_id = $data['order_id'];
			
			$order_products = $this->model_checkout_order->getOrderProducts($order_id);
			
			if ($this->config->get('module_stock_emag_cel_cel_status')) {
				$username = $this->config->get('module_stock_emag_cel_cel_username');
				$password = $this->config->get('module_stock_emag_cel_cel_password');
			} else {
				return true;
			}
			
			$token = $this->_authCel($username, $password);
			
			if (!$token) error_log('Cel error token');
			
			$cel_products = array();
			
			foreach ($order_products as $order_product) {
				$order_options = $this->model_checkout_order->getOrderOptions($order_id, $order_product['order_product_id']);
				if (count($order_options) > 0) {
					foreach ($order_options as $order_option) {
						$query = $this->db->query("SELECT cel, quantity FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id = '" . (int)$order_option['product_option_value_id'] . "'");
						if (!empty($query->row['cel'])) {
							$cel_products[] = array(
								'products_model' => $query->row['cel'],
								'stoc' => $query->row['quantity']
							);
						}
					}
				} else {
					$query = $this->db->query("SELECT cel, quantity FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$order_product['product_id'] . "'");
					if (!empty($query->row['cel'])) {
						$cel_products[] = array(
							'products_model' => $query->row['cel'],
							'stoc' => $query->row['quantity']
						);
					}
				}
			}
			
			if (!count($cel_products)) error_log('Eroare update cel product empty, order opencart: ' . $order_id);
			
			$response = $this->_callCel(array('products' => $cel_products), $token, '/products/updateStockAndPrice');
			
			if (!$response) error_log('Eroare update cel product error, order opencart: ' . $order_id);
		}
		
		public function cel_product_stock($data = array()) {
			
			if ($this->config->get('module_stock_emag_cel_cel_status')) {
				$username = $this->config->get('module_stock_emag_cel_cel_username');
				$password = $this->config->get('module_stock_emag_cel_cel_password');
			} else {
				return true;
			}
			
			$token = $this->_authCel($username, $password);
			
			if (!$token) error_log('Cel error token');
			
			$cel_products = array();
			
			if (isset($data['products']) && count($data['products']) > 0) {
				$query = $this->db->query("SELECT cel, quantity FROM " . DB_PREFIX . "product WHERE product_id IN (". implode(', ', $data['products']) .")");
				foreach ($query->rows as $row) {
					if (empty($row['cel'])) continue;
					$cel_products[] = array(
						'products_model' => $row['cel'],
						'stoc' => $row['quantity']
					);
				}
			}
			
			if (isset($data['options']) && count($data['options']) > 0) {
				$query = $this->db->query("SELECT cel, quantity FROM " . DB_PREFIX . "product_option_value WHERE product_option_value_id IN (". implode(', ', $data['options']) .")");
				foreach ($query->rows as $row) {
					if (empty($row['cel'])) continue;
					$cel_products[] = array(
						'products_model' => $row['cel'],
						'stoc' => $row['quantity']
					);
				}
			}

			if (!count($cel_products)) error_log('Eroare update cel product empty, data opencart: ' . print_r($data, true));
			
			$response = $this->_callCel(array('products' => $cel_products), $token, '/products/updateStockAndPrice');

			if ($response === false) error_log('Eroare update cel product error, data opencart: ' .  print_r($data, true));
			
		}
		
		protected function _callCel($request, $token = '', $url = '') {
			if (empty($token) || empty($url) || !count($request)) return false;

			$url = 'https://api-mp.cel.ro/market_api' . $url;
			
			$header =  array(
				'AUTH: Bearer ' . $token
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

			$result = curl_exec($ch);
			$result = json_decode($result, true);

			if (!empty($result) && empty($result['error'])) return $result['message'];
			
			return false;
		}
		
		protected function _authCel($username, $password) {
			if (empty($username) || empty($password)) return false;
			
			$url = 'https://api-mp.cel.ro/market_api/login/actionLogin';
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['username' => $username, 'password' => $password]));
			
			$result = curl_exec($ch);
			$result = json_decode($result, true);

			if (empty($result['error'])) return $result['message'];
			
			return false;
		}
		
	}
