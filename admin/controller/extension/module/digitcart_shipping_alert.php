<?php
class ControllerExtensionModuleDigitCartShippingAlert extends Controller {
	private $error			= array();
	private $moduleName		= 'digitcart_shipping_alert';
	private $moduleFilePath	= 'extension/module/digitcart_shipping_alert';
	
	private function moduleList() {
		$module_list = 'extension/module';
		
		if (VERSION == '2.3.0.2') {
			$module_list = 'extension/extension';
		}
		
		if (version_compare(VERSION, '3') > 0) {
			$module_list = 'marketplace/extension';
		}
		
		return $module_list;
	}
	
	private function tokenString() {
		$token_string = 'token';
		
		if (version_compare(VERSION, '3') > 0) {
			$token_string = 'user_token';
		}
		
		return $token_string;
	}
	
	private function moduleType() {
		$module_type = '';
		
		if (version_compare(VERSION, '2.3') > 0) {
			$module_type = '&type=module';
		}
		
		return $module_type;
	}
	
	public function index() {
		$this->load->language($this->moduleFilePath);
		
		$data['heading_title'] = $this->language->get('heading_title');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_' . $this->moduleName, $this->request->post);
			
			$this->session->data['success'] = $this->language->get('text_success');
			
			$this->response->redirect($this->url->link($this->moduleList(), $this->tokenString() . '=' . $this->session->data[$this->tokenString()] . $this->moduleType(), true));
		}
		
		if (!$this->config->get('shipping_free_total') || !$this->config->get('shipping_free_status') || $this->config->get('shipping_free_geo_zone_id')) {
			$this->error['warning'] = $this->language->get('error_shipping');
		}
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		$data['breadcrumbs'] = array();
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->tokenString() . '=' . $this->session->data[$this->tokenString()], true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link($this->moduleList(), $this->tokenString() . '=' . $this->session->data[$this->tokenString()] . $this->moduleType(), true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->moduleFilePath, $this->tokenString() . '=' . $this->session->data[$this->tokenString()], true)
		);
		
		$data['action'] = $this->url->link($this->moduleFilePath, $this->tokenString() . '=' . $this->session->data[$this->tokenString()], true);
		
		$data['cancel'] = $this->url->link($this->moduleList(), $this->tokenString() . '=' . $this->session->data[$this->tokenString()] . $this->moduleType(), true);
		
		$vars = array(
			'status'	=> 0,
			'popup'	=> 0,
			'message'	=> array(),
			'alert'	=> 'info'
		);
		
		foreach ($vars as $var => $default) {
			if (isset($this->request->post['module_' . $this->moduleName . '_' . $var])) {
				$data['module_' . $this->moduleName . '_' . $var] = $this->request->post['module_' . $this->moduleName . '_' . $var];
			} elseif ($this->config->get('module_' . $this->moduleName . '_' . $var)) {
				$data['module_' . $this->moduleName . '_' . $var] = $this->config->get('module_' . $this->moduleName . '_' . $var);
			} else {
				$data['module_' . $this->moduleName . '_' . $var] = $default;
			}
		}
		
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view($this->moduleFilePath, $data));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', $this->moduleFilePath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		return !$this->error;
	}
}