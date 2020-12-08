<?php
class ControllerExtensionModuleBulkSpecial extends Controller {
	private $error = array();

	public function index() {
			
		$this->load->language('extension/module/bulk_special');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module/bulk_special');

		$this->getForm();
	}

	public function bulksave() {
		$this->load->language('extension/module/bulk_special');
		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module/bulk_special');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_module_bulk_special->addBulkSpecial($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			$this->response->redirect($this->url->link('extension/module/bulk_special', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}
		$this->getForm();
	}

	public function getProductsByCategory(){

		$json = array();

		$this->load->model('extension/module/bulk_special');
		if($this->request->get['category_id']){

			$category_id = $this->request->get['category_id'];
		
			$results = $this->model_extension_module_bulk_special->getProductsByCategoryId($category_id);
			
			foreach ($results as $result) {
				$json[] = array(
					'product_id'  => $result['product_id'],
					'name'        => $result['name'],
					'model'       => $result['model']
				);
			}
		}else{
			$json = 0;
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getProductsByMfg(){

		$json = array();

		$this->load->model('extension/module/bulk_special');
		if($this->request->get['manufacturer_id']){

			$manufacturer_id = $this->request->get['manufacturer_id'];
		
			$results = $this->model_extension_module_bulk_special->getProductsByMfg($manufacturer_id);
			
			foreach ($results as $result) {
				$json[] = array(
					'product_id'  => $result['product_id'],
					'name'        => $result['name'],
					'model'       => $result['model']
				);
			}
		}else{
			$json = 0;
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function product_nospl(){
		$json = array();

		$this->load->model('extension/module/bulk_special');

		if($this->request->get['customer_group_id']){
			
			$customer_group_id = $this->request->get['customer_group_id'];
			$date_start = $this->request->get['date_start'];
			$category_id = $this->request->get['category_id'];
		
			$results = $this->model_extension_module_bulk_special->getExistSpecialProduct($customer_group_id,$date_start,$category_id);
			
			foreach ($results as $result) {
				$json[] = array(
					'product_id'  => $result['product_id'],
					'name'        => $result['name'],
					'model'       => $result['model']
				);
			}
		}else{
			$json = 0;
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	protected function getForm() {
		if (isset($this->error['date_start'])) {
			$data['error_date_start'] = $this->error['date_start'];
		} else {
			$data['error_date_start'] = '';
		}

		if (isset($this->error['date_end'])) {
			$data['error_date_end'] = $this->error['date_end'];
		} else {
			$data['error_date_end'] = '';
		}
		
		$url = '';

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/bulk_special', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['user_token'] = $this->session->data['user_token'];

		$data['all_products'] = array(
		 	array('value' => 0,'type' => 'No'),
		 	array('value' => 1,'type' => 'Yes'),
		 	array('value' => 2,'type' => 'Not Special')
		);

		$this->load->model('customer/customer_group');
		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
	
		$data['categories'] = $this->model_extension_module_bulk_special->getCategories();

		$data['manufacturers'] = $this->model_extension_module_bulk_special->getManufacturers();

		$data['action'] = $this->url->link('extension/module/bulk_special/bulksave', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['display_all_special'] = $this->url->link('extension/module/bulk_special/display_all_special', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/bulk_special', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/module/bulk_special')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$this->load->language('extension/module/bulk_special');

		if ((utf8_strlen($this->request->post['product_special_date_start']) < 8)) {
			$this->error['date_start'] = $this->language->get('error_date_start');
		}

		if ((utf8_strlen($this->request->post['product_special_date_end']) < 8)) {
			$this->error['date_end'] = $this->language->get('error_date_end');
		}

		/*if (!$this->request->post['ProductList']) {
			$this->error['product'] = $this->language->get('error_product');
		}*/
		return !$this->error;
	}

	public function display_all_special() {

		if (isset($this->request->get['filter_date_start'])) {
			$filter_date_start = $this->request->get['filter_date_start'];
		} else {
			$filter_date_start = '';
		}

		if (isset($this->request->get['filter_date_end'])) {
			$filter_date_end = $this->request->get['filter_date_end'];
		} else {
			$filter_date_end = '';
		}

		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}
		
		$this->load->language('extension/module/bulk_special');
		$this->document->setTitle($this->language->get('heading_title'));	

		$this->load->model('extension/module/bulk_special');

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_product'])) {
			$url .= '&filter_product=' . urlencode(html_entity_decode($this->request->get['filter_product'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date_start'])) {
			$url .= '&filter_date_start=' . urlencode(html_entity_decode($this->request->get['filter_date_start'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date_end'])) {
			$url .= '&filter_date_end=' . urlencode(html_entity_decode($this->request->get['filter_date_end'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/bulk_special/display_all_special', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['bulk_specials'] = array();

		$filter_data = array(
			'filter_name'	=> $filter_name,
			'filter_date_start' => $filter_date_start,
			'filter_date_end' => $filter_date_end,
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$product_total = $this->model_extension_module_bulk_special->getTotalProductSpecials($filter_data);
		
		$results = $this->model_extension_module_bulk_special->getProductSpecials($filter_data);
      	
		foreach ($results as $result) {
		 if($result['status']){
      		$status = "<label class='label label-success'>Enabled</label>";
      			}else{
      		$status = "<label class='label label-danger'>Disabled</label>";
      		}

			$data['bulk_specials'][] = array(
				'product_id'  => $result['product_id'],
				'name'  	  => $result['name'],
				'model'       => $result['model'],
				'quantity'    => $result['quantity'],
				'image'  	  => $result['image'],
				'price'   	  => $result['price'],
				'special'  	  => $result['special'],
				'status'  	  => $status,
				'date_end'	  => $result['date_end']
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date_start'])) {
			$url .= '&filter_date_start=' . urlencode(html_entity_decode($this->request->get['filter_date_start'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date_end'])) {
			$url .= '&filter_date_end=' . urlencode(html_entity_decode($this->request->get['filter_date_end'], ENT_QUOTES, 'UTF-8'));
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['add'] = $this->url->link('extension/module/bulk_special', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['sort_name'] = $this->url->link('extension/module/bulk_special/display_all_special', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, true);
		$data['sort_model'] = $this->url->link('extension/module/bulk_special/display_all_special', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url, true);
		$data['sort_price'] = $this->url->link('extension/module/bulk_special/display_all_special', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price' . $url, true);
		$data['sort_status'] = $this->url->link('extension/module/bulk_special/display_all_special', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url, true);
		$data['sort_special'] = $this->url->link('extension/module/bulk_special/display_all_special', 'user_token=' . $this->session->data['user_token'] . '&sort=ps.price' . $url, true);
		$data['sort_date_end'] = $this->url->link('extension/module/bulk_special/display_all_special', 'user_token=' . $this->session->data['user_token'] . '&sort=ps.date_end' . $url, true);
		
		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date_start'])) {
			$url .= '&filter_date_start=' . urlencode(html_entity_decode($this->request->get['filter_date_start'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_date_end'])) {
			$url .= '&filter_date_end=' . urlencode(html_entity_decode($this->request->get['filter_date_end'], ENT_QUOTES, 'UTF-8'));
		}


		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/bulk_special/display_all_special', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

		$data['filter_name'] = $filter_name;
		$data['filter_date_start'] = $filter_date_start;
		$data['filter_date_end'] = $filter_date_end;
		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/bulk_special_list', $data));
	}
}
