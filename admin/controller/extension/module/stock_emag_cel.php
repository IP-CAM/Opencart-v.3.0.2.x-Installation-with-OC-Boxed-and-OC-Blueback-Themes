<?php
	class ControllerExtensionModuleStockEmagCel extends Controller	{
		
		private $error = [];
		
		public function index()	{
			$this->load->language('extension/module/stock_emag_cel');
			
			$this->document->setTitle($this->language->get('heading_title'));
			
			$this->load->model('setting/setting');
			
			if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
				if ($this->config->get('module_stock_emag_cel_emag_one_status') != $this->request->post['module_stock_emag_cel_emag_one_status'] || $this->config->get('module_stock_emag_cel_emag_two_status') != $this->request->post['module_stock_emag_cel_emag_two_status']) {
					$this->request->post['module_stock_emag_cel_emag_cron'] = 1;
				} else {
					$this->request->post['module_stock_emag_cel_emag_cron'] = 0;
				}
				
				$this->model_setting_setting->editSetting('module_stock_emag_cel', $this->request->post);
				
				$this->session->data['success'] = $this->language->get('text_success');
				
				$this->load->controller('catalog/product/emag');
				
				$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
			}
			
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
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
			);
			
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/stock_emag_cel', 'user_token=' . $this->session->data['user_token'], true)
			);
			
			$data['action'] = $this->url->link('extension/module/stock_emag_cel', 'user_token=' . $this->session->data['user_token'], true);
			
			$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
			
			// Emag Unu
			if (isset($this->request->post['module_stock_emag_cel_emag_one_username'])) {
				$data['module_stock_emag_cel_emag_one_username'] = $this->request->post['module_stock_emag_cel_emag_one_username'];
			} else {
				$data['module_stock_emag_cel_emag_one_username'] = $this->config->get('module_stock_emag_cel_emag_one_username');
			}
			
			if (isset($this->request->post['module_stock_emag_cel_emag_one_password'])) {
				$data['module_stock_emag_cel_emag_one_password'] = $this->request->post['module_stock_emag_cel_emag_one_password'];
			} else {
				$data['module_stock_emag_cel_emag_one_password'] = $this->config->get('module_stock_emag_cel_emag_one_password');
			}
			
			if (isset($this->request->post['module_stock_emag_cel_emag_one_status'])) {
				$data['module_stock_emag_cel_emag_one_status'] = $this->request->post['module_stock_emag_cel_emag_one_status'];
			} else {
				$data['module_stock_emag_cel_emag_one_status'] = $this->config->get('module_stock_emag_cel_emag_one_status');
			}
			
			// Emag Doi
			if (isset($this->request->post['module_stock_emag_cel_emag_two_username'])) {
				$data['module_stock_emag_cel_emag_two_username'] = $this->request->post['module_stock_emag_cel_emag_two_username'];
			} else {
				$data['module_stock_emag_cel_emag_two_username'] = $this->config->get('module_stock_emag_cel_emag_two_username');
			}
			
			if (isset($this->request->post['module_stock_emag_cel_emag_two_password'])) {
				$data['module_stock_emag_cel_emag_two_password'] = $this->request->post['module_stock_emag_cel_emag_two_password'];
			} else {
				$data['module_stock_emag_cel_emag_two_password'] = $this->config->get('module_stock_emag_cel_emag_two_password');
			}
			
			if (isset($this->request->post['module_stock_emag_cel_emag_two_status'])) {
				$data['module_stock_emag_cel_emag_two_status'] = $this->request->post['module_stock_emag_cel_emag_two_status'];
			} else {
				$data['module_stock_emag_cel_emag_two_status'] = $this->config->get('module_stock_emag_cel_emag_two_status');
			}
			
			// Cel
			if (isset($this->request->post['module_stock_emag_cel_cel_username'])) {
				$data['module_stock_emag_cel_cel_username'] = $this->request->post['module_stock_emag_cel_cel_username'];
			} else {
				$data['module_stock_emag_cel_cel_username'] = $this->config->get('module_stock_emag_cel_cel_username');
			}
			
			if (isset($this->request->post['module_stock_emag_cel_cel_password'])) {
				$data['module_stock_emag_cel_cel_password'] = $this->request->post['module_stock_emag_cel_cel_password'];
			} else {
				$data['module_stock_emag_cel_cel_password'] = $this->config->get('module_stock_emag_cel_cel_password');
			}
			
			if (isset($this->request->post['module_stock_emag_cel_cel_status'])) {
				$data['module_stock_emag_cel_cel_status'] = $this->request->post['module_stock_emag_cel_cel_status'];
			} else {
				$data['module_stock_emag_cel_cel_status'] = $this->config->get('module_stock_emag_cel_cel_status');
			}
			
			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');
			
			$this->response->setOutput($this->load->view('extension/module/stock_emag_cel', $data));
		}
		
		public function install() {
			$this->db->query("ALTER TABLE ".  DB_PREFIX ."product ADD COLUMN IF NOT EXISTS emag_one INT DEFAULT NULL");
			$this->db->query("ALTER TABLE ".  DB_PREFIX ."product ADD COLUMN IF NOT EXISTS emag_two INT DEFAULT NULL");
			$this->db->query("ALTER TABLE ".  DB_PREFIX ."product ADD COLUMN IF NOT EXISTS cel VARCHAR(64) DEFAULT NULL");
			
			$this->db->query("ALTER TABLE ".  DB_PREFIX ."product_option_value ADD COLUMN IF NOT EXISTS emag_one INT DEFAULT NULL");
			$this->db->query("ALTER TABLE ".  DB_PREFIX ."product_option_value ADD COLUMN IF NOT EXISTS emag_two INT DEFAULT NULL");
			$this->db->query("ALTER TABLE ".  DB_PREFIX ."product_option_value ADD COLUMN IF NOT EXISTS cel VARCHAR(64) DEFAULT NULL");
		}
		
		protected function validate() {
			if (!$this->user->hasPermission('modify', 'extension/module/stock_emag_cel')) {
				$this->error['warning'] = $this->language->get('error_permission');
			}
			
			return !$this->error;
		}
	}
	
