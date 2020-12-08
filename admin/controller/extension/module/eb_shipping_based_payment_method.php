<?php
/********************************************
Extension Name : Shipping Based Payment Method
Extension Version : 1.0
Author : EXTENSIONS BAZAAR
Website : www.extensionsbazaar.com
*********************************************/
class ControllerExtensionModuleEbShippingBasedPaymentMethod extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/eb_shipping_based_payment_method');

		$this->document->setTitle($this->language->get('heading_title2'));

		$data['text_default'] = $this->language->get('text_default');
		$data['entry_message'] = $this->language->get('entry_message');

		$this->load->model('setting/setting');
		

		if(isset($this->request->get['store_id'])) {
			$data['store_id'] = $this->request->get['store_id'];
		}else{
			$data['store_id']	= 0;
		}
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_eb_shipping_based_payment_method', $this->request->post,$data['store_id']);
			$this->session->data['success'] = $this->language->get('text_success');
			
			if($this->request->post['stay']==1){
				$this->response->redirect($this->url->link('extension/module/eb_shipping_based_payment_method', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));	
			}else{
				$this->response->redirect($this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
			}
		}

		$data['heading_title'] = $this->language->get('heading_title2');

		//  Text
		$data['text_edit'] 	= $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled']	= $this->language->get('text_disabled');
		$data['text_yes'] 	= $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		
		
		// Entry
		$data['entry_status']= $this->language->get('entry_status');
		$data['entry_shipping_method']= $this->language->get('entry_shipping_method');
		$data['entry_payment_method']= $this->language->get('entry_payment_method');
		
		//Help
		$data['help_status']= $this->language->get('help_status');
		
		// Button
		$data['button_shipping'] 	= $this->language->get('button_shipping');
		$data['button_remove'] 	= $this->language->get('button_remove');
		$data['button_save'] 	= $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->error['no_of_days'])) {
			$data['error_no_of_days'] = $this->error['no_of_days'];
		} else {
			$data['error_no_of_days'] = '';
		}

		if (isset($this->error['redirect_url'])) {
			$data['error_redirect_url'] = $this->error['redirect_url'];
		} else {
			$data['error_redirect_url'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title2'),
			'href' => $this->url->link('extension/module/eb_shipping_based_payment_method', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/eb_shipping_based_payment_method', 'user_token=' . $this->session->data['user_token'] . '&store_id='. $data['store_id'], true);

		$data['store_action'] =  $this->url->link('extension/module/eb_shipping_based_payment_method','user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$this->load->model('setting/store');
		
		$data['stores'] = $this->model_setting_store->getStores();

		$store_info = $this->model_setting_setting->getSetting('module_eb_shipping_based_payment_method', $data['store_id']);
	
		$this->load->model('setting/extension');
		
		$data['shippings'] = array();
		$shippings = $this->model_setting_extension->getInstalled('shipping');
		foreach($shippings as $shipping){
			if($this->config->get('shipping_'.$shipping . '_status')){
				$this->load->language('extension/shipping/' . $shipping);
				$data['shippings'][] = array(
					'name'       => $this->language->get('heading_title'),
					'code'		=> $shipping,
				);
			}
		}
		
		$data['payments'] = array();
		$payments = $this->model_setting_extension->getInstalled('payment');
		foreach($payments as $payment){
			if($this->config->get('payment_'.$payment . '_status')){
				$this->load->language('extension/payment/' . $payment);
				$data['payments'][] = array(
					'name'       => $this->language->get('heading_title'),
					'code'		=> $payment,
				);
			}
		}
		
		if (isset($this->request->post['module_eb_shipping_based_payment_method_status'])) {
			$data['module_eb_shipping_based_payment_method_status'] = $this->request->post['module_eb_shipping_based_payment_method_status'];
		}elseif(isset($store_info['module_eb_shipping_based_payment_method_status'])){
			$data['module_eb_shipping_based_payment_method_status'] = $store_info['module_eb_shipping_based_payment_method_status'];
		}else {
			$data['module_eb_shipping_based_payment_method_status'] = '';
		}
		
		if (isset($this->request->post['module_eb_shipping_based_payment_method'])) {
			$module_eb_shipping_based_payment_method = $this->request->post['module_eb_shipping_based_payment_method'];
		}elseif(!empty($store_info['module_eb_shipping_based_payment_method'])){
			$module_eb_shipping_based_payment_method = $store_info['module_eb_shipping_based_payment_method'];
		}else {
			$module_eb_shipping_based_payment_method = array();
		}
		
		$data['module_eb_shipping_based_payment_methods'] = array();
		foreach ($module_eb_shipping_based_payment_method as $eshipping) {
		  $data['module_eb_shipping_based_payment_methods'][] = array(
		  	'shipping' 			=> $eshipping['shipping'],
		  	'payment' 			=> $eshipping['payment'],
		  );			 
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/eb_shipping_based_payment_method', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/eb_shipping_based_payment_method')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
	
		return !$this->error;
	}
}