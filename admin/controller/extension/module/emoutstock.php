<?php
class ControllerExtensionModuleEmoutstock extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/emoutstock');
		$this->load->model('localisation/stock_status');	
		$this->document->setTitle($this->language->get('heading_title'));

		
		$this->load->model('tool/image');
		$this->load->model('localisation/language');
		
		//
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {	
			
			$post_data =  $this->request->post;		

			foreach($post_data as $key => $postdatas){				
			
				$insert_data['emoutstock_status'][$key]=$postdatas;				
			} 
			
			$this->model_setting_setting->editSetting('emoutstock', $insert_data);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_none'] = $this->language->get('text_none');
		
		/* $data['text_hour'] = $this->language->get('text_hour');
		$data['text_hour'] = $this->language->get('text_hour');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_popupsetting'] = $this->language->get('text_popupsetting');
		$data['text_txtsetting'] = $this->language->get('text_txtsetting'); */

		$data['tab_setting'] = $this->language->get('tab_setting');
		$data['tab_email_temp'] = $this->language->get('tab_email_temp');
		$data['tab_language'] = $this->language->get('tab_language');
		$data['tab_css'] = $this->language->get('tab_css');
		
		
		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_stock_status'] = $this->language->get('entry_stock_status');
		$data['entry_button_text'] = $this->language->get('entry_button_text');
		$data['entry_stock_name'] = $this->language->get('entry_stock_name');
		$data['entry_stock_bgcolor'] = $this->language->get('entry_stock_bgcolor');
		$data['entry_stock_fontcolor'] = $this->language->get('entry_stock_fontcolor');
		$data['entry_emailtemp_description'] = $this->language->get('entry_emailtemp_description');
		$data['entry_image'] = $this->language->get('entry_image');
		
		
		
		
		$data['entry_css'] = $this->language->get('entry_css');
	
		$data['entry_status'] = $this->language->get('entry_status');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		
				
		$data['mmlogo'] = "view/image/em-logo.png";
		$data['agedemo1'] = "view/image/agedemo1.png";
		$data['agedemo2'] = "view/image/agedemo2.png";
		
		
		$data['languages'] = $this->model_localisation_language->getLanguages();
	

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

		if (isset($this->error['title'])) {
			$data['error_title'] = $this->error['title'];
		} else {
			$data['error_title'] = '';
		}

		if (isset($this->error['rlink'])) {
			$data['error_rlink'] = $this->error['rlink'];
		} else {
			$data['error_rlink'] = '';
		}
		if (isset($this->error['logowidth'])) {
			$data['error_logowidth'] = $this->error['logowidth'];
		} else {
			$data['error_logowidth'] = '';
		}
		if (isset($this->error['logoheight'])) {
			$data['error_logoheight'] = $this->error['logoheight'];
		} else {
			$data['error_logoheight'] = '';
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

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/emoutstock', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/emoutstock', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		
		$data['action'] = $this->url->link('extension/module/emoutstock', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		
		if($this->request->server['REQUEST_METHOD'] != 'POST'){
			$module_info = $this->config->get('emoutstock_status'); 
			//echo "<pre>";
	//		print_r($module_info);			
		}
		
		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['emailtemp_description'])) {
			$data['emailtemp_description'] = $this->request->post['emailtemp_description'];
		} elseif (!empty($module_info)) {
			$data['emailtemp_description'] = $module_info['emailtemp_description'];
		} else {
			$data['emailtemp_description'] = '';
		}

		if (isset($this->request->post['stock_status'])) {
			$data['stock_status'] = $this->request->post['stock_status'];
		} elseif (!empty($module_info['stock_status'])) {
			$data['stock_status'] = $module_info['stock_status'];
		} else {
			$data['stock_status'] = '';
		}
		
		if (isset($this->request->post['stock_status_id'])) {
			$data['stock_status_id'] = $this->request->post['stock_status_id'];
		} elseif (!empty($module_info)) {
			$data['stock_status_id'] = $module_info['stock_status_id'];
		} else {
			$data['stock_status_id'] = '';
		}
		
		if (isset($this->request->post['stock_bgcolor'])) {
			$data['stock_bgcolor'] = $this->request->post['stock_bgcolor'];
		} elseif (!empty($module_info)) {
			$data['stock_bgcolor'] = $module_info['stock_bgcolor'];
		} else {
			$data['stock_bgcolor'] = '';
		}	
		
		if (isset($this->request->post['stock_fontcolor'])) {
			$data['stock_fontcolor'] = $this->request->post['stock_fontcolor'];
		} elseif (!empty($module_info)) {
			$data['stock_fontcolor'] = $module_info['stock_fontcolor'];
		} else {
			$data['stock_fontcolor'] = '';
		}	
		
		
		if (isset($this->request->post['button_text'])) {
			$data['button_text'] = $this->request->post['button_text'];
		} elseif (!empty($module_info)) {
			$data['button_text'] = $module_info['button_text'];
		} else {
			$data['button_text'] = '';
		}
		
		
		
		if (isset($this->request->post['cart_bgcolor'])) {
			$data['cart_bgcolor'] = $this->request->post['cart_bgcolor'];
		} elseif (!empty($module_info)) {
			$data['cart_bgcolor'] = $module_info['cart_bgcolor'];
		} else {
			$data['cart_bgcolor'] = '';
		}
		
		if (isset($this->request->post['cart_fontcolor'])) {
			$data['cart_fontcolor'] = $this->request->post['cart_fontcolor'];
		} elseif (!empty($module_info)) {
			$data['cart_fontcolor'] = $module_info['cart_fontcolor'];
		} else {
			$data['cart_fontcolor'] = '';
		}
		
		
		 /*   Image Show */	
		
		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($module_info)) {
			$data['image'] = $module_info['image'];
		} 
		else {
			$data['image'] = '';
		}
		
		$this->load->model('tool/image');
		
		if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 180, 180);
		} elseif (!empty($module_info) && is_file(DIR_IMAGE . $module_info['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($module_info['image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('mmtesti_user.png', 100, 100);
		}
		$data['placeholder'] = $this->model_tool_image->resize('mmtesti_user.png', 100, 100);
		
	 /*   Image Show End  */	
		
		
		
		
		if (isset($this->request->post['emstock_status'])) {
			$data['emstock_status'] = $this->request->post['emstock_status'];
		} elseif (!empty($module_info)) {
			$data['emstock_status'] = $module_info['emstock_status'];
		} else {
			$data['emstock_status'] = '';
		}
		


		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

	
	
		$data['stock_statuses'] = array();
		$stock_status_total = $this->model_localisation_stock_status->getTotalStockStatuses();
		$results = $this->model_localisation_stock_status->getStockStatuses();
		foreach ($results as $result) {
			$data['stock_statuses'][] = array(
				'stock_status_id' => $result['stock_status_id'],
				'name'            => $result['name'],

			);
		}
		$this->response->setOutput($this->load->view('extension/module/emoutstock', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/emoutstock')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}
		
	
		return !$this->error;
	}
	
	
	
	
	
}
