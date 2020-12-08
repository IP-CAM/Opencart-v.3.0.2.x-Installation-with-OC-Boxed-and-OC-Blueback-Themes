<?php

//*******************************************************************************
// Sphinx Search v1.2.3
// Author: Iverest EOOD
// E-mail: sales@iverest.com
// Website: http://www.iverest.com
//*******************************************************************************

class ControllerExtensionModuleSphinxsearch extends Controller {
	
	private $LIMIT = 500;
	
	public function install() {
		$this->load->model('catalog/sphinxsearch');
		$this->model_catalog_sphinxsearch->install();
	}
	
	public function index() {

		$this->load->language('extension/module/sphinxsearch');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			
			$this->model_setting_setting->editSetting('module_sphinxsearch', $this->request->post);
		
			$this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}
		
		$this->load->model('catalog/sphinxsearch');
		
		$data['module_sphinxsearch_match_modes'] = $this->model_catalog_sphinxsearch->sphinxMatchModes();
		$data['module_sphinxsearch_sort_modes'] = $this->model_catalog_sphinxsearch->sphinxSortModes();
		$data['module_sphinxsearch_ranking_modes'] = $this->model_catalog_sphinxsearch->sphinxRankingModes();
		$data['module_sphinxsearch_sort_attrs'] = $this->model_catalog_sphinxsearch->sphinxSortAttrs();
		
 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();
		
		$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_module'),
				'href' => $this->url->link('extension/module', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/sphinxsearch', 'user_token=' . $this->session->data['user_token'], true)
		);
		
		$data['action'] = $this->url->link('extension/module/sphinxsearch', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('extension/module', 'user_token=' . $this->session->data['user_token'], true);
		$data['user_token'] = $this->session->data['user_token'];
		
		//General tab
        if (isset($this->request->post['module_sphinxsearch_status'])) {
            $data['module_sphinxsearch_status'] = $this->request->post['module_sphinxsearch_status'];
        } else {
            $data['module_sphinxsearch_status'] = $this->config->get('module_sphinxsearch_status');
        }

		if (isset($this->request->post['module_sphinxsearch_server'])) {
			$data['module_sphinxsearch_server'] = $this->request->post['module_sphinxsearch_server'];
		} else {
			$data['module_sphinxsearch_server'] = $this->config->get('module_sphinxsearch_server');
		}
		
		if (isset($this->request->post['module_sphinxsearch_port'])) {
			$data['module_sphinxsearch_port'] = $this->request->post['module_sphinxsearch_port'];
		} else {
			$data['module_sphinxsearch_port'] = $this->config->get('module_sphinxsearch_port');
		}
		
		if (isset($this->request->post['module_sphinxsearch_match_mode'])) {
			$data['module_sphinxsearch_match_mode'] = $this->request->post['module_sphinxsearch_match_mode'];
		} else {
			$data['module_sphinxsearch_match_mode'] = $this->config->get('module_sphinxsearch_match_mode');
		}
		
		if (isset($this->request->post['module_sphinxsearch_sort_mode'])) {
			$data['module_sphinxsearch_sort_mode'] = $this->request->post['module_sphinxsearch_sort_mode'];
		} else {
			$data['module_sphinxsearch_sort_mode'] = $this->config->get('module_sphinxsearch_sort_mode');
		}
		
		if (isset($this->request->post['module_sphinxsearch_ranking_mode'])) {
			$data['module_sphinxsearch_ranking_mode'] = $this->request->post['module_sphinxsearch_ranking_mode'];
		} else {
			$data['module_sphinxsearch_ranking_mode'] = $this->config->get('module_sphinxsearch_ranking_mode');
		}
		
		if (isset($this->request->post['module_sphinxsearch_sort'])) {
			$data['module_sphinxsearch_sort'] = $this->request->post['module_sphinxsearch_sort'];
		} else {
			$data['module_sphinxsearch_sort'] = $this->config->get('module_sphinxsearch_sort');
		}
		
		if (isset($this->request->post['module_sphinxsearch_sort_attr_val'])) {
			$data['module_sphinxsearch_sort_attr_val'] = $this->request->post['module_sphinxsearch_sort_attr_val'];
		} else {
			$data['module_sphinxsearch_sort_attr_val'] = $this->config->get('module_sphinxsearch_sort_attr_val');
		}
		
		if (isset($this->request->post['module_sphinxsearch_weights'])) {
			$data['module_sphinxsearch_weights'] = $this->request->post['module_sphinxsearch_weights'];
		} else {
			$data['module_sphinxsearch_weights'] = $this->config->get('module_sphinxsearch_weights');
		}
		
		//Autocomplete tab
		if (isset($this->request->post['module_sphinxsearch_autocomple'])) {
			$data['module_sphinxsearch_autocomple'] = $this->request->post['module_sphinxsearch_autocomple'];
		} else {
			$data['module_sphinxsearch_autocomple'] = $this->config->get('module_sphinxsearch_autocomple');
		}
		
		if (isset($this->request->post['module_sphinxsearch_autocomple_selector'])) {
			$data['module_sphinxsearch_autocomple_selector'] = $this->request->post['module_sphinxsearch_autocomple_selector'];
		} else {
			$data['module_sphinxsearch_autocomple_selector'] = $this->config->get('module_sphinxsearch_autocomple_selector');
		}
		
		if (isset($this->request->post['module_sphinxsearch_autocomple_limit'])) {
			$data['module_sphinxsearch_autocomple_limit'] = $this->request->post['module_sphinxsearch_autocomple_limit'];
		} else {
			$data['module_sphinxsearch_autocomple_limit'] = $this->config->get('module_sphinxsearch_autocomple_limit');
		}
		
		if (isset($this->request->post['module_sphinxsearch_autocomplete_categories'])) {
			$data['module_sphinxsearch_autocomplete_categories'] = $this->request->post['module_sphinxsearch_autocomplete_categories'];
		} else {
			$data['module_sphinxsearch_autocomplete_categories'] = $this->config->get('module_sphinxsearch_autocomplete_categories');
		}
		
		if (isset($this->request->post['module_sphinxsearch_autocomplete_cat_limit'])) {
			$data['module_sphinxsearch_autocomplete_cat_limit'] = $this->request->post['module_sphinxsearch_autocomplete_cat_limit'];
		} else {
			$data['module_sphinxsearch_autocomplete_cat_limit'] = $this->config->get('module_sphinxsearch_autocomplete_cat_limit');
		}
		
		//Products tab
		if (isset($this->request->post['module_sphinxsearch_product_status'])) {
			$data['module_sphinxsearch_product_status'] = $this->request->post['module_sphinxsearch_product_status'];
		} else {
			$data['module_sphinxsearch_product_status'] = $this->config->get('module_sphinxsearch_product_status');
		}
		
		if (isset($this->request->post['module_sphinxsearch_products_quantity'])) {
			$data['module_sphinxsearch_products_quantity'] = $this->request->post['module_sphinxsearch_products_quantity'];
		} else {
			$data['module_sphinxsearch_products_quantity'] = $this->config->get('module_sphinxsearch_products_quantity');
		}
		
		//Categories tab
		if (isset($this->request->post['module_sphinxsearch_category_status'])) {
			$data['module_sphinxsearch_category_status'] = $this->request->post['module_sphinxsearch_category_status'];
		} else {
			$data['module_sphinxsearch_category_status'] = $this->config->get('module_sphinxsearch_category_status');
		}
		
		if (isset($this->request->post['module_sphinxsearch_category_product_status'])) {
			$data['module_sphinxsearch_category_product_status'] = $this->request->post['module_sphinxsearch_category_product_status'];
		} else {
			$data['module_sphinxsearch_category_product_status'] = $this->config->get('module_sphinxsearch_category_product_status');
		}
		
		if (isset($this->request->post['module_sphinxsearch_category_product_quantity'])) {
			$data['module_sphinxsearch_category_product_quantity'] = $this->request->post['module_sphinxsearch_category_product_quantity'];
		} else {
			$data['module_sphinxsearch_category_product_quantity'] = $this->config->get('module_sphinxsearch_category_product_quantity');
		}
		
		//Config tab
		if (isset($this->request->post['module_sphinxsearch_config_index_type'])) {
			$data['module_sphinxsearch_config_index_type'] = $this->request->post['module_sphinxsearch_config_index_type'];
		} else {
			$data['module_sphinxsearch_config_index_type'] = $this->config->get('module_sphinxsearch_config_index_type');
		}
		
		if (isset($this->request->post['module_sphinxsearch_config_path_to_indexes'])) {
			$data['module_sphinxsearch_config_path_to_indexes'] = $this->request->post['module_sphinxsearch_config_path_to_indexes'];
		} else {
			$data['module_sphinxsearch_config_path_to_indexes'] = $this->config->get('module_sphinxsearch_config_path_to_indexes');
		}
		
		if (isset($this->request->post['module_sphinxsearch_config_path_to_log'])) {
			$data['module_sphinxsearch_config_path_to_log'] = $this->request->post['module_sphinxsearch_config_path_to_log'];
		} else {
			$data['module_sphinxsearch_config_path_to_log'] = $this->config->get('module_sphinxsearch_config_path_to_log');
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
				
		$this->response->setOutput($this->load->view('extension/module/sphinxsearch', $data));
	}

	public function save() {
		
		$postdata = $this->request->post;

		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('sphinx', $postdata);
		
	}
	
	/**
	 * Generate suggestions - gets all products from 'product_description' table, extract the keywords, builds trigrams and freqs
	 */
	public function generateSuggestions() {
		
		if (!$this->validate()) {
			echo 'You have no permissons to do that!';
			return;
		}
		
		$timeLimit = (int)ini_get('max_execution_time')-10;
		$timeStart = microtime(true);
		
		$this->load->model('catalog/sphinxsearch');
		
		$isRtIndex = $this->config->get('module_sphinxsearch_config_index_type');
		
		$sphinxConnection = $this->checkSphinxConnection();

		if($sphinxConnection['error'] != '') {
			$this->response->setOutput(json_encode($sphinxConnection));
			return;
		}
		
		$offset = 0;
		if(isset($this->request->get['offset'])) {
			$offset = (int)$this->request->get['offset'];
		}
		
		$this->load->model('catalog/product');
		
		if($offset == 0) {
			$this->session->data['total'] = (int)$this->model_catalog_product->getTotalProducts();
		}
		
		if($offset == 0 && !$isRtIndex) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "module_sphinxsearch_suggestions`;");
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "module_sphinxsearch_suggestions` AUTO_INCREMENT =1;");
		}
		
		$data = array(
			'start' => $offset,
			'limit' => $this->LIMIT
		);
		
		$products = $this->model_catalog_product->getProducts($data);
		
		if(empty($products) || $products == '') {
			$json = array(
					'error' => 'No products found!',
					'offset' => '',
					'limit' => $this->LIMIT,
					'total' => ''
			);
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		foreach ($products as $idx => $product) {
			
			if (microtime(true) - $timeStart >= $timeLimit) {
				break;
			}
			
			$productDesc = $this->model_catalog_product->getProductDescriptions($product['product_id']);
			
			$result = $this->model_catalog_sphinxsearch->addToSphinxSuggestions($productDesc);

		}
		
		$json = array(
				'error' => '',
				'offset' => $offset+$idx+1,
				'limit' => $this->LIMIT,
				'total' => $this->session->data['total']
		);
		
		if(isset($result['error'])) {
			$json = array(
					'error' => $result['error'],
					'offset' => '',
					'limit' => $this->LIMIT,
					'total' => ''
			);
		}
		
		$this->response->setOutput(json_encode($json));
		
	}
	
	public function generateSphinxConfig() {
		
		$baseFile = DIR_SYSTEM .'library/sphinx/sphinx.conf.in';
		
		if($this->config->get('module_sphinxsearch_config_index_type')) {
			$baseFile = DIR_SYSTEM .'library/sphinx/sphinx.rt.conf.in';
		}
		
		$newConfigFile = DIR_DOWNLOAD .'sphinx.conf';
		
		if(!file_exists($baseFile)) {
			die('Please make sure that '. $baseFile. ' exists!');
		}
		
		$baseFileContent = file_get_contents($baseFile);
		
		$find = array(
			'{{ module_sphinxsearch_config_db_host }}',
			'{{ module_sphinxsearch_config_db_user }}',
			'{{ module_sphinxsearch_config_db_pass }}',
			'{{ module_sphinxsearch_config_db_name }}',
			'{{ module_sphinxsearch_config_db_table_prefix }}',
			'{{ module_sphinxsearch_config_path_to_indexes }}',
			'{{ module_sphinxsearch_config_path_to_log }}'
		);
		
		$replace = array(
			DB_HOSTNAME,
			DB_USERNAME,
			DB_PASSWORD,
			DB_DATABASE,
			DB_PREFIX,
			$this->config->get('module_sphinxsearch_config_path_to_indexes'),
			$this->config->get('module_sphinxsearch_config_path_to_log')
		);
		
		$newContent = str_replace($find, $replace, $baseFileContent);
		 
		file_put_contents($newConfigFile, $newContent);
		
		if (file_exists($newConfigFile)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.basename($newConfigFile));
			readfile($newConfigFile);
		}
		
		unlink($newConfigFile);
		
	}
	
	public function buildProductsRtIndex() {
		
		$timeLimit = (int)ini_get('max_execution_time')-10;
		$timeStart = microtime(true);
		
		$this->load->model('catalog/sphinxsearch');
		$this->load->model('catalog/product');
		
		//Check sphinx connection before continue
		$sphinxConnection = $this->checkSphinxConnection();
		
		if($sphinxConnection['error'] != '') {
			$this->response->setOutput(json_encode($sphinxConnection));
			return;
		}
		
		$offset = 0;
		if(isset($this->request->get['offset'])) {
			$offset = (int)$this->request->get['offset'];
		}
		
		if($offset == 0) {
			$this->session->data['main_total'] = (int)$this->model_catalog_product->getTotalProducts();
		}
		
		$products = $this->model_catalog_product->getProducts(array('start' => $offset, 'limit' => $this->LIMIT));
		
		if(empty($products) || $products == '') {
			$json = array(
					'error' => 'No products found!',
					'offset' => '',
					'limit' => '',
					'total' => ''
			);
			
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		foreach ($products as $idx => $product) {
				
			if (microtime(true) - $timeStart >= $timeLimit) {
				break;
			}
			
			$productDesc = $this->model_catalog_product->getProductDescriptions($product['product_id']);
			$productRating = $this->model_catalog_sphinxsearch->getRatingByProductId($product['product_id']);
			
			if(empty($productDesc)) {
				continue;
			}
			
			foreach ($productDesc as $lang_id => $info) {
				//Prepare the data
				$productInfo = array_merge($info, $product);
				$productInfo['id'] = $this->model_catalog_sphinxsearch->properSphinxId($product['product_id'], $lang_id);
				$productInfo['product_id'] = $this->model_catalog_sphinxsearch->properSphinxId($product['product_id'], $lang_id);
				$productInfo['name'] = $info['name'];
				$productInfo['description'] = $info['description'];
				$productInfo['language_id'] = $lang_id;
				$productInfo['date_available'] = strtotime($product['date_available']);
				$productInfo['rating'] = $productRating;
				$categoriesFilter = (implode(',', $this->model_catalog_product->getProductCategories($product['product_id'])) != 0) ? '('.implode(',', $this->model_catalog_product->getProductCategories($product['product_id'])).')' : '(0)';
				$productInfo['categories_filter'] = $categoriesFilter;
				$storeFilter = (implode(',', $this->model_catalog_product->getProductStores($product['product_id'])) != 0) ? '('.implode(',', $this->model_catalog_product->getProductStores($product['product_id'])).')' : '(0)';
				$productInfo['store_filter'] = $storeFilter;
				
				$productAttributes = $this->model_catalog_product->getProductAttributes($product['product_id']);
				$prAttributes = array();
				if(is_array($productAttributes) && !empty($productAttributes)) {
					foreach ($productAttributes as $attribute) {
						$prAttributes[] = $attribute['attribute_id'];
					}
					
					$productInfo['product_attribute'] = '('. implode(',', $prAttributes) .')';
				}
				
				$this->model_catalog_sphinxsearch->insertOrReplace('products', $productInfo);
			}
		}
		
		$json = array(
				'offset' => $offset+$idx+1,
				'limit' => $this->LIMIT,
				'total' => $this->session->data['main_total']
		);
		
		$this->response->setOutput(json_encode($json));
	}
	
	public function buildCategoriesRtIndex() {
		
		$timeLimit = (int)ini_get('max_execution_time')-10;
		$timeStart = microtime(true);
		
		$this->load->model('catalog/sphinxsearch');
		$this->load->model('catalog/category');
		
		//Check sphinx connection before continue
		$sphinxConnection = $this->checkSphinxConnection();

		if($sphinxConnection['error'] != '') {
			$this->response->setOutput(json_encode($sphinxConnection));
			return;
		}
		
		$offset = 0;
		if(isset($this->request->get['offset'])) {
			$offset = (int)$this->request->get['offset'];
		}
		
		if($offset == 0) {
			$this->session->data['categoryinfo_total'] = $this->model_catalog_sphinxsearch->getTotalRows('category_description');
		}
		
		$categories = $this->model_catalog_sphinxsearch->getCategories($offset, $this->LIMIT);

		if(empty($categories) || $categories == '') {
			$json = array(
					'error' => 'No categories found!',
					'offset' => '',
					'limit' => '',
					'total' => ''
			);
		
			$this->response->setOutput(json_encode($json));
			return;
		}
		
		foreach($categories as $idx => &$res) {
				
			if (microtime(true) - $timeStart >= $timeLimit) {
				break;
			}

			//Prepare the data
			$res['id'] = $this->model_catalog_sphinxsearch->properSphinxId($res['category_id'], $res['language_id']);
			$res['category_id'] = $this->model_catalog_sphinxsearch->properSphinxId($res['category_id'], $res['language_id']);
			
			$store_filter = (implode(',', $this->model_catalog_category->getCategoryStores($res['category_id'])) != 0) ? '('.implode(',', $this->model_catalog_category->getCategoryStores($res['category_id'])).')' : '(0)';
			$res['store_filter'] = $store_filter; 
			
			$this->model_catalog_sphinxsearch->insertOrReplace('categories', $res, 'insert');
		}
		
		$json = array(
				'offset' => $offset+$idx+1,
				'limit' => $this->LIMIT,
				'total' => $this->session->data['categoryinfo_total']
		);
		
		$this->response->setOutput(json_encode($json));
		
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/sphinxsearch')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
	
		return !$this->error;
	}

	protected function checkSphinxConnection() {
		
		$this->load->model('catalog/product');
		
		$sphinxConnection = $this->model_catalog_sphinxsearch->sphinxConnection();
		if($sphinxConnection->connect_error) {
			$json = array(
					'error' => $sphinxConnection->connect_error,
					'offset' => '',
					'limit' => '',
					'total' => ''
			);
		
			return $json;
		}
	}
	
}

?>