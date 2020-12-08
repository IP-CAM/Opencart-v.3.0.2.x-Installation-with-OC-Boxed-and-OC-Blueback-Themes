<?php

/*
This file is part of "Breadcrumbs+" project and subject to the terms
and conditions defined in file "EULA.txt", which is part of this source
code package and also available on the project page: https://git.io/JvWAu.
*/

class ControllerExtensionModuleBreadcrumbs extends Controller {
	private $error = array();
	private $types = array('default', 'direct', 'short', 'long', 'last', 'manufacturer');

	public function index() {
		$this->load->language('extension/module/breadcrumbs');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (('POST' == $this->request->server['REQUEST_METHOD']) && $this->validate()) {
			$this->model_setting_setting->editSetting('module_breadcrumbs', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link(
				'marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
			);
		}

		if (isset($this->error['permission'])) {
			$data['error_permission'] = $this->error['permission'];
		} else {
			$data['error_permission'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link(
				'marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true
			),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link(
				'extension/module/breadcrumbs',
				'user_token=' . $this->session->data['user_token'], true
			),
		);

		$data['action'] = $this->url->link(
			'extension/module/breadcrumbs', 'user_token=' . $this->session->data['user_token'], true
		);

		$data['cancel'] = $this->url->link(
			'marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true
		);

		if (isset($this->request->post['module_breadcrumbs_status'])) {
			$data['status'] = $this->request->post['module_breadcrumbs_status'];
		} else {
			$data['status'] = $this->config->get('module_breadcrumbs_status');
		}

		if (isset($this->request->post['module_breadcrumbs_settings'])) {
			$data['breadcrumbs'] = $this->request->post['module_breadcrumbs_settings'];
		} else {
			$data['breadcrumbs'] = $this->config->get('module_breadcrumbs_settings');
		}

		if (!method_exists($this->document, 'addCustomScript') || !method_exists($this->document, 'getCustomScripts')) {
			$data['breadcrumbs']['json'] = false;
			$data['breadcrumbs_json_disabled'] = true;
		} else {
			if (isset($this->request->post['module_breadcrumbs_settings']['json'])) {
				$data['breadcrumbs']['json'] = $this->request->post['module_breadcrumbs_settings']['json'];
			} elseif (isset($this->config->get('module_breadcrumbs_settings')['json'])){
				$data['breadcrumbs']['json'] = $this->config->get('module_breadcrumbs_settings')['json'];
			} else {
				$data['breadcrumbs']['json'] = false;
			}
		}

		$data['types'] = $this->types;

		$data['heading_title'] = $this->language->get('heading_title');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/breadcrumbs', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/breadcrumbs')) {
			$this->error['permission'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function install() {
		$dir_stylesheet = DIR_CATALOG . 'view/theme/' .
			$this->config->get('theme_directory') . $this->config->get('config_theme') . '/stylesheet/';

		if ($dir_stylesheet) {
			$css_bold = $dir_stylesheet . 'breadcrumbs_plus_bold.css';
			$css_nolink = $dir_stylesheet . 'breadcrumbs_plus_nolink.css';

			if (!file_exists($css_bold)) {
				$css_text = "ul.breadcrumb li:last-child a {\n\tfont-weight: bold;\n}";
				file_put_contents($css_bold, $css_text, FILE_USE_INCLUDE_PATH);
			}

			if (!file_exists($css_nolink)) {
				$css_text = "ul.breadcrumb li:last-child a {\n\tcursor: default!important;\n\tpointer-events: none;\n\tcolor: inherit;\n}";
				file_put_contents($css_nolink, $css_text, FILE_USE_INCLUDE_PATH);
			}
		}

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('breadcrumbs_update');
		$this->model_setting_event->deleteEventByCode('breadcrumbs_style');
		$this->model_setting_event->deleteEventByCode('breadcrumbs_script');

		// Catch all view/*/before to find breadcrumbs
		$this->model_setting_event->addEvent(
			'breadcrumbs_update',
			'catalog/view/*/before',
			'extension/module/breadcrumbs/updateBreadcrumbs'
		);

		//catalog/view/common/header/before
		$this->model_setting_event->addEvent(
			'breadcrumbs_style',
			'catalog/controller/common/header/before',
			'extension/module/breadcrumbs/styleBreadcrumbs'
		);

		//catalog/view/*/after
		$this->model_setting_event->addEvent(
			'breadcrumbs_script',
			'catalog/view/*/after',
			'extension/module/breadcrumbs/addJsonLdScript'
		);
	}

	public function uninstall() {
		$dir_stylesheet = DIR_CATALOG . 'view/theme/' .
			$this->config->get('theme_directory') . $this->config->get('config_theme') . '/stylesheet/';

		if ($dir_stylesheet) {
			$css_bold = $dir_stylesheet . 'breadcrumbs_plus_bold.css';
			$css_nolink = $dir_stylesheet . 'breadcrumbs_plus_nolink.css';

			if (file_exists($css_bold)) {
				unlink($css_bold);
			}

			if (file_exists($css_nolink)) {
				unlink($css_nolink);
			}
		}

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('breadcrumbs_update');
		$this->model_setting_event->deleteEventByCode('breadcrumbs_style');
		$this->model_setting_event->deleteEventByCode('breadcrumbs_script');
	}
}
