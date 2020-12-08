<?php

require_once DIR_SYSTEM . '/library/jreturnemail/ocjreturnemailTrait.php';
class ControllerExtensionJReturneMail extends Controller {
	use ocjreturnemailTrait;
	private $error = array();

	public function __construct($registry) {
		parent :: __construct($registry);
		$this->initJReturneMailTrait();
		$this->buildJReturneMailTables();
	}

	public function getAdminMenu() {
		$menu = array();
		if (VERSION <= '2.2.0.0') {
			$this->load->language('extension/jreturnemail_menu');
			$menu = array(
				'id'       => 'menu-jreturnemail',
				'icon'	   => 'fa-reply',
				'name'	   => $this->language->get('text_jreturnemail'),
				'href'     => $this->url->link('extension/jreturnemail', $this->JocToken . '=' . $this->session->data[$this->JocToken], true),
				'children' => array()
			);
		} else {
			if ($this->user->hasPermission('access', 'extension/jreturnemail')) {
				$this->load->language('extension/jreturnemail_menu');
				$menu = array(
					'id'       => 'menu-jreturnemail',
					'icon'	   => 'fa-reply',
					'name'	   => $this->language->get('text_jreturnemail'),
					'href'     => $this->url->link('extension/jreturnemail', $this->JocToken . '=' . $this->session->data[$this->JocToken], true),
					'children' => array()
				);
			}
		}
		return $menu;
	}

	public function index() {
		$this->load->language('extension/jreturnemail');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('view/stylesheet/jreturnemail/stylesheet.css');

		if (isset($this->request->get['store_id'])) {
			$data['store_id'] = $this->request->get['store_id'];
		} else {
			$data['store_id'] = 0;
		}

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			// remove trailing slash
			$this->request->post['jreturnemail_admin_dir'] = rtrim($this->request->post['jreturnemail_admin_dir'], '/');

			$this->model_setting_setting->editSetting('jreturnemail', $this->request->post, $data['store_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/jreturnemail', $this->JocToken . '=' . $this->session->data[$this->JocToken], true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');

		$data['text_emailadmin'] = $this->language->get('text_emailadmin');
		$data['text_emailcustomer'] = $this->language->get('text_emailcustomer');
		$data['text_emailcustomer_history'] = $this->language->get('text_emailcustomer_history');
		$data['text_emailcustomer_action'] = $this->language->get('text_emailcustomer_action');
		$data['text_sc_info'] = $this->language->get('text_sc_info');
		$data['text_info_return_history'] = $this->language->get('text_info_return_history');
		$data['text_email_from_admin'] = $this->language->get('text_email_from_admin');

		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_emailadmin'] = $this->language->get('entry_emailadmin');
		$data['entry_emailtocustomer'] = $this->language->get('entry_emailtocustomer');
		$data['entry_emailtocustomer_history'] = $this->language->get('entry_emailtocustomer_history');
		$data['entry_emailtocustomer_action'] = $this->language->get('entry_emailtocustomer_action');
		$data['entry_subject'] = $this->language->get('entry_subject');
		$data['entry_msg'] = $this->language->get('entry_msg');
		$data['entry_product_thumb'] = $this->language->get('entry_product_thumb');
		$data['entry_width'] = $this->language->get('entry_width');
		$data['entry_height'] = $this->language->get('entry_height');
		$data['entry_admin_dir'] = $this->language->get('entry_admin_dir');
		$data['entry_date_format'] = $this->language->get('entry_date_format');
		$data['entry_return_action'] = $this->language->get('entry_return_action');

		$data['column_variale'] = $this->language->get('column_variale');
		$data['column_variale_value'] = $this->language->get('column_variale_value');

		$data['tab_support'] = $this->language->get('tab_support');

		$data['sc_store_name'] = $this->language->get('sc_store_name');
		$data['sc_store_url'] = $this->language->get('sc_store_url');
		$data['sc_store_logo'] = $this->language->get('sc_store_logo');
		$data['sc_store_email'] = $this->language->get('sc_store_email');
		$data['sc_store_fax'] = $this->language->get('sc_store_fax');
		$data['sc_store_telephone'] = $this->language->get('sc_store_telephone');
		$data['sc_product_name'] = $this->language->get('sc_product_name');
		$data['sc_product_url'] = $this->language->get('sc_product_url');
		$data['sc_product_model'] = $this->language->get('sc_product_model');
		$data['sc_product_qty'] = $this->language->get('sc_product_qty');
		$data['sc_product_opened_status'] = $this->language->get('sc_product_opened_status');
		$data['sc_product_thumb'] = $this->language->get('sc_product_thumb');
		$data['sc_customer_id'] = $this->language->get('sc_customer_id');
		$data['sc_customer_firstname'] = $this->language->get('sc_customer_firstname');
		$data['sc_customer_lastname'] = $this->language->get('sc_customer_lastname');
		$data['sc_customer_email'] = $this->language->get('sc_customer_email');
		$data['sc_customer_telephone'] = $this->language->get('sc_customer_telephone');
		$data['sc_return_id'] = $this->language->get('sc_return_id');
		$data['sc_return_date_added'] = $this->language->get('sc_return_date_added');
		$data['sc_return_reason'] = $this->language->get('sc_return_reason');
		$data['sc_return_status'] = $this->language->get('sc_return_status');
		$data['sc_return_action'] = $this->language->get('sc_return_action');
		$data['sc_return_comment'] = $this->language->get('sc_return_comment');
		$data['sc_order_id'] = $this->language->get('sc_order_id');


		// Help
		$data['help_emailadmin'] = $this->language->get('help_emailadmin');
		$data['help_emailtocustomer'] = $this->language->get('help_emailtocustomer');
		$data['help_emailtocustomer_history'] = $this->language->get('help_emailtocustomer_history');
		$data['help_emailtocustomer_action'] = $this->language->get('help_emailtocustomer_action');
		$data['help_admin_dir'] = $this->language->get('help_admin_dir');
		$data['help_date_format'] = $this->language->get('help_date_format');
		$data['help_return_action'] = $this->language->get('help_return_action');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_sc_view'] = $this->language->get('button_sc_view');
		$data['button_sc_hide'] = $this->language->get('button_sc_hide');

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

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = array();
		}

		if (isset($this->error['emailadmin'])) {
			$data['error_emailadmin'] = $this->error['emailadmin'];
		} else {
			$data['error_emailadmin'] = '';
		}

		if (isset($this->error['productthumb'])) {
			$data['error_productthumb'] = $this->error['productthumb'];
		} else {
			$data['error_productthumb'] = '';
		}

		if (isset($this->error['admin_dir'])) {
			$data['error_admin_dir'] = $this->error['admin_dir'];
		} else {
			$data['error_admin_dir'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->JocToken . '=' . $this->session->data[$this->JocToken], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/jreturnemail', $this->JocToken . '=' . $this->session->data[$this->JocToken], true)
		);

		$data['action'] = $this->url->link('extension/jreturnemail', $this->JocToken . '=' . $this->session->data[$this->JocToken], true);

		$data['languages'] = $this->getLanguages();

		$this->load->model('localisation/return_action');
		$data['return_actions'] = $this->model_localisation_return_action->getReturnActions();


		$this->summernote_editor($data);

		$module_info = $this->model_setting_setting->getSetting('jreturnemail', $data['store_id']);


		if (isset($this->request->post['jreturnemail_status'])) {
			$data['jreturnemail_status'] = $this->request->post['jreturnemail_status'];
		} elseif (isset($module_info['jreturnemail_status'])) {
			$data['jreturnemail_status'] = $module_info['jreturnemail_status'];
		} else {
			$data['jreturnemail_status'] = 0;
		}

		if (isset($this->request->post['jreturnemail_date_format'])) {
			$data['jreturnemail_date_format'] = $this->request->post['jreturnemail_date_format'];
		} elseif (isset($module_info['jreturnemail_date_format'])) {
			$data['jreturnemail_date_format'] = $module_info['jreturnemail_date_format'];
		} else {
			$data['jreturnemail_date_format'] = $this->language->get('date_format_short');
		}

		if (isset($this->request->post['jreturnemail_admin_dir'])) {
			$data['jreturnemail_admin_dir'] = $this->request->post['jreturnemail_admin_dir'];
		} elseif (isset($module_info['jreturnemail_admin_dir'])) {
			$data['jreturnemail_admin_dir'] = $module_info['jreturnemail_admin_dir'];
		} else {
			$data['jreturnemail_admin_dir'] = 'admin';
		}

		if (isset($this->request->post['jreturnemail_productthumb_width'])) {
			$data['jreturnemail_productthumb_width'] = $this->request->post['jreturnemail_productthumb_width'];
		} elseif (isset($module_info['jreturnemail_productthumb_width'])) {
			$data['jreturnemail_productthumb_width'] = $module_info['jreturnemail_productthumb_width'];
		} else {
			$data['jreturnemail_productthumb_width'] = '100';
		}

		if (isset($this->request->post['jreturnemail_productthumb_height'])) {
			$data['jreturnemail_productthumb_height'] = $this->request->post['jreturnemail_productthumb_height'];
		} elseif (isset($module_info['jreturnemail_productthumb_height'])) {
			$data['jreturnemail_productthumb_height'] = $module_info['jreturnemail_productthumb_height'];
		} else {
			$data['jreturnemail_productthumb_height'] = '100';
		}

		if (isset($this->request->post['jreturnemail_emailadmin'])) {
			$data['jreturnemail_emailadmin'] = $this->request->post['jreturnemail_emailadmin'];
		} elseif (isset($module_info['jreturnemail_emailadmin'])) {
			$data['jreturnemail_emailadmin'] = $module_info['jreturnemail_emailadmin'];
		} else {
			$data['jreturnemail_emailadmin'] = $this->config->get('config_email');
		}

		if (isset($this->request->post['jreturnemail_emailtocustomer'])) {
			$data['jreturnemail_emailtocustomer'] = $this->request->post['jreturnemail_emailtocustomer'];
		} elseif (isset($module_info['jreturnemail_emailtocustomer'])) {
			$data['jreturnemail_emailtocustomer'] = $module_info['jreturnemail_emailtocustomer'];
		} else {
			$data['jreturnemail_emailtocustomer'] = 0;
		}

		if (isset($this->request->post['jreturnemail_emailtocustomer_history'])) {
			$data['jreturnemail_emailtocustomer_history'] = $this->request->post['jreturnemail_emailtocustomer_history'];
		} elseif (isset($module_info['jreturnemail_emailtocustomer_history'])) {
			$data['jreturnemail_emailtocustomer_history'] = $module_info['jreturnemail_emailtocustomer_history'];
		} else {
			$data['jreturnemail_emailtocustomer_history'] = 0;
		}

		if (isset($this->request->post['jreturnemail_emailtocustomer_action'])) {
			$data['jreturnemail_emailtocustomer_action'] = $this->request->post['jreturnemail_emailtocustomer_action'];
		} elseif (isset($module_info['jreturnemail_emailtocustomer_action'])) {
			$data['jreturnemail_emailtocustomer_action'] = $module_info['jreturnemail_emailtocustomer_action'];
		} else {
			$data['jreturnemail_emailtocustomer_action'] = 0;
		}
		if (isset($this->request->post['jreturnemail_return_action'])) {
			$data['jreturnemail_return_action'] = $this->request->post['jreturnemail_return_action'];
		} elseif (isset($module_info['jreturnemail_return_action'])) {
			$data['jreturnemail_return_action'] = $module_info['jreturnemail_return_action'];
		} else {
			$data['jreturnemail_return_action'] = array();
		}

		if (isset($this->request->post['jreturnemail_email'])) {
			$data['jreturnemail_email'] = $this->request->post['jreturnemail_email'];
		} elseif (isset($module_info['jreturnemail_email'])) {
			$data['jreturnemail_email'] = (array)$module_info['jreturnemail_email'];
		} else {
			$data['jreturnemail_email'] = array();
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->loadview('extension/jreturnemail', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/jreturnemail')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['jreturnemail_productthumb_width'] || !$this->request->post['jreturnemail_productthumb_height']) {
			$this->error['productthumb'] = $this->language->get('error_dimensions');
		}

		if (!$this->request->post['jreturnemail_admin_dir']) {
			$this->error['admin_dir'] = $this->language->get('error_admin_dir');
		}



		if ((utf8_strlen($this->request->post['jreturnemail_emailadmin']) > 96) || !filter_var($this->request->post['jreturnemail_emailadmin'], FILTER_VALIDATE_EMAIL)) {
			$this->error['emailadmin'] = $this->language->get('error_email');
		}

		foreach ($this->request->post['jreturnemail_email']['admin'] as $language_id => $value) {

			if ((utf8_strlen($value['subject']) < 3) || (utf8_strlen($value['subject']) > 255)) {
				$this->error['email']['admin'][$language_id]['subject'] = $this->language->get('error_subject');
			}

			$value['msg'] = strip_tags(html_entity_decode($value['msg'], ENT_QUOTES, 'UTF-8'));

			if ((utf8_strlen($value['msg']) < 3)) {
				$this->error['email']['admin'][$language_id]['msg'] = $this->language->get('error_msg');
			}
		}

		if ($this->request->post['jreturnemail_emailtocustomer']) {

			foreach ($this->request->post['jreturnemail_email']['customer'] as $language_id => $value) {

				if ((utf8_strlen($value['subject']) < 3) || (utf8_strlen($value['subject']) > 255)) {
					$this->error['email']['customer'][$language_id]['subject'] = $this->language->get('error_subject');
				}

				$value['msg'] = strip_tags(html_entity_decode($value['msg'], ENT_QUOTES, 'UTF-8'));

				if ((utf8_strlen($value['msg']) < 3)) {
					$this->error['email']['customer'][$language_id]['msg'] = $this->language->get('error_msg');
				}
			}
		}

		if ($this->request->post['jreturnemail_emailtocustomer_history']) {

			foreach ($this->request->post['jreturnemail_email']['customerhistory'] as $language_id => $value) {

				if ((utf8_strlen($value['subject']) < 3) || (utf8_strlen($value['subject']) > 255)) {
					$this->error['email']['customerhistory'][$language_id]['subject'] = $this->language->get('error_subject');
				}

				$value['msg'] = strip_tags(html_entity_decode($value['msg'], ENT_QUOTES, 'UTF-8'));

				if ((utf8_strlen($value['msg']) < 3)) {
					$this->error['email']['customerhistory'][$language_id]['msg'] = $this->language->get('error_msg');
				}
			}
		}
		if ($this->request->post['jreturnemail_emailtocustomer_action']) {

			foreach ($this->request->post['jreturnemail_email']['customeraction'] as $language_id => $value) {

				if ((utf8_strlen($value['subject']) < 3) || (utf8_strlen($value['subject']) > 255)) {
					$this->error['email']['customeraction'][$language_id]['subject'] = $this->language->get('error_subject');
				}

				$value['msg'] = strip_tags(html_entity_decode($value['msg'], ENT_QUOTES, 'UTF-8'));

				if ((utf8_strlen($value['msg']) < 3)) {
					$this->error['email']['customeraction'][$language_id]['msg'] = $this->language->get('error_msg');
				}
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}
		return !$this->error;
	}
}