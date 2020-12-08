<?php
class ControllerExtensionModuleWidSoldOut extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/wid_sold_out');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$status_status = $this->config->get('module_wid_sold_out_status');
			$this->model_setting_setting->editSetting('module_wid_sold_out', $this->request->post);
			
			if (isset($this->request->post['module_wid_sold_out_status']) ) {
				$this->load->model('setting/modification');
				$row = $this->model_setting_modification->getModificationByCode('wid_sold_out');
				if ($this->request->post['module_wid_sold_out_status'] == 0 && $status_status!=0) {
					$this->model_setting_modification->disableModification($row['modification_id']);
					$this->response->redirect($this->url->link('marketplace/modification/refresh', 'user_token=' . $this->session->data['user_token'] , true));
				} elseif ($this->request->post['module_wid_sold_out_status'] == 1 && $status_status!=1) {
					$this->model_setting_modification->enableModification($row['modification_id']);
					$this->response->redirect($this->url->link('marketplace/modification/refresh', 'user_token=' . $this->session->data['user_token'] , true));
				}
			}
			
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
			
		}
		
		//$data['entry_wid_sold_out_color'] = $this->language->get('entry_wid_sold_out_color');
		//$data['entry_wid_sold_out_color_enabled'] = $this->language->get('entry_wid_sold_out_color_enabled');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->error['module_wid_sold_out_color'])) {
			$data['error_wid_sold_out_color'] = $this->error['module_wid_sold_out_color'];
		} else {
			$data['error_wid_sold_out_color'] = '';
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
			'href' => $this->url->link('extension/module/store', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/wid_sold_out', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_wid_sold_out_admin'])) {
			$data['module_wid_sold_out_admin'] = $this->request->post['module_wid_sold_out_admin'];
		} else {
			$data['module_wid_sold_out_admin'] = $this->config->get('module_wid_sold_out_admin');
		}
		
		if (isset($this->request->post['module_wid_sold_out_color'])) {
			$data['module_wid_sold_out_color'] = $this->request->post['module_wid_sold_out_color'];
		} else {
			$data['module_wid_sold_out_color'] = $this->config->get('module_wid_sold_out_color');
		}
		
		if (isset($this->request->post['module_wid_sold_out_color_enabled'])) {
			$data['module_wid_sold_out_color_enabled'] = $this->request->post['module_wid_sold_out_color_enabled'];
		} else {
			$data['module_wid_sold_out_color_enabled'] = $this->config->get('module_wid_sold_out_color_enabled');
		}			
		
		if (isset($this->request->post['module_wid_sold_out_disable'])) {
			$data['module_wid_sold_out_disable'] = $this->request->post['module_wid_sold_out_disable'];
		} else {
			$data['module_wid_sold_out_disable'] = $this->config->get('module_wid_sold_out_disable');
		}
		
		if (isset($this->request->post['module_wid_sold_out_watermark_enabled'])) {
			$data['module_wid_sold_out_watermark_enabled'] = $this->request->post['module_wid_sold_out_watermark_enabled'];
		} else {
			$data['module_wid_sold_out_watermark_enabled'] = $this->config->get('module_wid_sold_out_watermark_enabled');
		}	
				
		if (isset($this->request->post['module_wid_sold_out_status'])) {
			$data['module_wid_sold_out_status'] = $this->request->post['module_wid_sold_out_status'];
		} else {
			$data['module_wid_sold_out_status'] = $this->config->get('module_wid_sold_out_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/wid_sold_out', $data));
	}
	
	
	

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/wid_sold_out')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		/*
		if (isset($this->request->post['module_wid_sold_out_color']) && !$this->request->post['module_wid_sold_out_color'] ) {
			$this->error['module_wid_sold_out_color'] = $this->language->get('error_module_wid_sold_out_color');
		} 
		*/
		return !$this->error;
	}
	
	public function install() {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent('wid_sold_out', 'catalog/view/product/*/before', 'extension/module/wid_sold_out/widSoldOut');
		//$this->model_setting_event->addEvent('wid_sold_out_modules', 'catalog/view/extension/module/*/before', 'extension/module/wid_sold_out/widSoldOut');
	}

	public function uninstall() {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('wid_sold_out');
		
		$this->load->model('setting/modification');
		$row = $this->model_setting_modification->getModificationByCode('wid_sold_out');
		$this->model_setting_modification->disableModification($row['modification_id']);
		//$this->response->redirect($this->url->link('marketplace/modification/refresh', 'user_token=' . $this->session->data['user_token'] , true));
				
		//$this->model_setting_event->deleteEventByCode('wid_sold_out_modules');
	}
}