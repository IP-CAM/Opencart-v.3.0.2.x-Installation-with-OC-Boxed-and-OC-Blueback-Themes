<?php
class ControllerMmstockMmstock extends Controller {
	private $error = array();

	public function index() {
		
		$this->install();
		$this->load->language('mmstock/mmstock');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/mmstock');

		$this->getList();
	}

	public function add() {
		$this->load->language('mmstock/mmstock');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/mmstock');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_mmstock->addMmstock($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('mmstock/mmstock', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function send() {
		
		$this->load->language('mmstock/mmstock');
		
		// In-Stock Alert email to Client/Customer
		
			if (isset($this->request->get['mm_sid'])) {
				$mm_sid = $this->request->get['mm_sid'];
			} else {
				$mm_sid = 0;
			}

		
			$this->load->model('catalog/mmstock');
			$this->load->model('catalog/product');
			
			
			$mmstock_info = $this->model_catalog_mmstock->getMmstock($mm_sid);
			
			$product_info = $this->model_catalog_product->getProductDescriptions($mmstock_info['p_id']);
			
			$pro_name ="";
			if(!empty($product_info)){
				$pro_name = $product_info[$this->config->get('config_language_id')]['name'];
			}
			
			$module_info = $this->config->get('emoutstock_status'); 
			
			
			if (!empty($module_info) && $module_info['emailtemp_description']) {
				$emailtemp_description = $module_info['emailtemp_description'][$this->config->get('config_language_id')]['user_newticket'];
			} else {
				$emailtemp_description = '';
			}			
			
			
			/* $message  = sprintf($this->language->get('text_ticket_request'),$ticket_id) . "\n\n";
			$message .= $this->language->get('text_staff_review') . "\n\n\n";
			$message .= $this->language->get('text_review_link') . "\n";
			$message .= $this->url->link('emticket/ticketview' . '&ticket_id=' .$ticket_id) . "\n\n\n";
			$message .= $this->language->get('text_ticket_id') . ' ' . $ticket_id . "\n";
			$message .= $this->language->get('text_telephone') . ' ' . $data['telephone'] . "\n\n"; */	
			

			$message = $emailtemp_description;
			
			
			// Email Template Customer Setting Start 30-1-19
			
			
		 	if($message){
				
				$format = html_entity_decode($message);
				
				$find = array(
					'{name}',
					'{email}',
					'{telephone}',
					'{productname}',
					'{productlink}'
				);
				
				$replace = array(
					'name' => $mmstock_info['name'],
					'email'   => $mmstock_info['email'],
					'telephone' => $mmstock_info['phone'],
					'productname' => $pro_name,
					'productlink'   => HTTP_CATALOG . 'index.php?route=product/product&product_id=' . $mmstock_info['p_id']
				);
			
				$message  = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));				
				
			} 
			echo $message;
			
			// Email Template Customer Setting End
			
			//die();
			
			
			$mail = new Mail();
			$mail->protocol = $this->config->get('config_mail_protocol');
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($mmstock_info['email']);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode($this->language->get('text_stock_alert'), ENT_QUOTES, 'UTF-8'));
			$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
			$mail->send();
					
		
		// In-Stock Alert email to Client/Customer 
		$this->session->data['success'] = $this->language->get('text_mail_success');
		
		$this->response->redirect($this->url->link('mmstock/mmstock', 'user_token=' . $this->session->data['user_token'] . $url, true));
		
		
	}
	
	public function edit() {
		$this->load->language('mmstock/mmstock');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/mmstock');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_mmstock->editMmstock($this->request->get['mm_sid'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('mmstock/mmstock', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('mmstock/mmstock');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/mmstock');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $mm_sid) {
				$this->model_catalog_mmstock->deleteMmstock($mm_sid);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('mmstock/mmstock', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
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

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('mmstock/mmstock', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('mmstock/mmstock/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('mmstock/mmstock/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['mmstocks'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$mmstock_total = $this->model_catalog_mmstock->getTotalMmstocks();

		$results = $this->model_catalog_mmstock->getMmstocks($filter_data);

		foreach ($results as $result) {
			
			
			$product_var = "";
			if($result['p_id']){
				
				 $result['p_id'];
				
				$productinfo = $this->model_catalog_mmstock->getProductInfo($result['p_id']);
				
				if(!empty($productinfo)){
					
					$product_var = $productinfo['name']."(".$productinfo['quantity'] .")";
				}
				
				//print_r($productinfo);
				
			}
			
			
			
			
			$data['mmstocks'][] = array(
				'mm_sid' => $result['mm_sid'],
				'name'            => $result['name'],
				'email'      => $result['email'],
				'phone'      => $result['phone'],
				'p_id'      => $result['p_id'],
				'product_var'      => $product_var,
				'status'      => $result['status'],
				'date_added'      => $result['date_added'],
				'date_modified'      => $result['date_modified'],
				'edit'            => $this->url->link('mmstock/mmstock/edit', 'user_token=' . $this->session->data['user_token'] . '&mm_sid=' . $result['mm_sid'] . $url, true),
				'send'            => $this->url->link('mmstock/mmstock/send', 'user_token=' . $this->session->data['user_token'] . '&mm_sid=' . $result['mm_sid'] . $url, true)
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');

		$data['column_name'] = $this->language->get('column_name');
		$data['column_sort_order'] = $this->language->get('column_sort_order');
		$data['column_phone'] = $this->language->get('column_phone');
		$data['column_email'] = $this->language->get('column_email');
		$data['column_product_info'] = $this->language->get('column_product_info');
		$data['column_action'] = $this->language->get('column_action');

		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');

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

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('mmstock/mmstock', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
		$data['sort_sort_order'] = $this->url->link('mmstock/mmstock', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $mmstock_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('mmstock/mmstock', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($mmstock_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($mmstock_total - $this->config->get('config_limit_admin'))) ? $mmstock_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $mmstock_total, ceil($mmstock_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('mmstock/mmstock_list', $data));
	}

	protected function getForm() {
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['mm_sid']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_default'] = $this->language->get('text_default');
		$data['text_percent'] = $this->language->get('text_percent');
		$data['text_amount'] = $this->language->get('text_amount');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_store'] = $this->language->get('entry_store');
		$data['entry_keyword'] = $this->language->get('entry_keyword');
		$data['entry_image'] = $this->language->get('entry_image');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');

		$data['help_keyword'] = $this->language->get('help_keyword');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('mmstock/mmstock', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['mm_sid'])) {
			$data['action'] = $this->url->link('mmstock/mmstock/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('mmstock/mmstock/edit', 'user_token=' . $this->session->data['user_token'] . '&mm_sid=' . $this->request->get['mm_sid'] . $url, true);
		}

		$data['cancel'] = $this->url->link('mmstock/mmstock', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['mm_sid']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$mmstock_info = $this->model_catalog_mmstock->getMmstock($this->request->get['mm_sid']);
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($mmstock_info)) {
			$data['name'] = $mmstock_info['name'];
		} else {
			$data['name'] = '';
		}

		$this->load->model('setting/store');

		$data['stores'] = $this->model_setting_store->getStores();

		if (isset($this->request->post['mmstock_store'])) {
			$data['mmstock_store'] = $this->request->post['mmstock_store'];
		} elseif (isset($this->request->get['mm_sid'])) {
			$data['mmstock_store'] = $this->model_catalog_mmstock->getMmstockStores($this->request->get['mm_sid']);
		} else {
			$data['mmstock_store'] = array(0);
		}

		if (isset($this->request->post['keyword'])) {
			$data['keyword'] = $this->request->post['keyword'];
		} elseif (!empty($mmstock_info)) {
			$data['keyword'] = $mmstock_info['keyword'];
		} else {
			$data['keyword'] = '';
		}

		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($mmstock_info)) {
			$data['image'] = $mmstock_info['image'];
		} else {
			$data['image'] = '';
		}

		$this->load->model('tool/image');

		if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
		} elseif (!empty($mmstock_info) && is_file(DIR_IMAGE . $mmstock_info['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($mmstock_info['image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($mmstock_info)) {
			$data['sort_order'] = $mmstock_info['sort_order'];
		} else {
			$data['sort_order'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('mmstock/mmstock_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'mmstock/mmstock')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 2) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (utf8_strlen($this->request->post['keyword']) > 0) {
			$this->load->model('catalog/url_alias');

			$url_alias_info = $this->model_catalog_url_alias->getUrlAlias($this->request->post['keyword']);

			if ($url_alias_info && isset($this->request->get['mm_sid']) && $url_alias_info['query'] != 'mm_sid=' . $this->request->get['mm_sid']) {
				$this->error['keyword'] = sprintf($this->language->get('error_keyword'));
			}

			if ($url_alias_info && !isset($this->request->get['mm_sid'])) {
				$this->error['keyword'] = sprintf($this->language->get('error_keyword'));
			}
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'mmstock/mmstock')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		

		return !$this->error;
	}

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$this->load->model('catalog/mmstock');

			$filter_data = array(
				'filter_name' => $this->request->get['filter_name'],
				'start'       => 0,
				'limit'       => 5
			);

			$results = $this->model_catalog_mmstock->getMmstocks($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'mm_sid' => $result['mm_sid'],
					'name'            => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}

		$sort_order = array();

		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $json);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	
	protected function install(){
        $this->load->model('catalog/mmstock');
        $this->model_catalog_mmstock->install();
    }
}