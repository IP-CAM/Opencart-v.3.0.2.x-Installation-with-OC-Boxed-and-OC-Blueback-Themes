<?php
class ControllerExtensionJsubCategory extends Controller {
	private $error = array();

	public function index() {
		if(isset($this->request->get['store_id'])) {
			$data['store_id'] = $this->request->get['store_id'];
		} else {
			$data['store_id'] = 0;
		}

		$this->document->addStyle('view/javascript/jadeagile/colorpicker/css/bootstrap-colorpicker.css');
		$this->document->addScript('view/javascript/jadeagile/colorpicker/js/bootstrap-colorpicker.js');
		$this->document->addStyle('view/stylesheet/jadeagile/stylesheet.css');

		$this->load->language('extension/jsub_category');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('jsub_category', $this->request->post, $data['store_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/jsub_category', 'user_token=' . $this->session->data['user_token'].'&store_id='. $data['store_id'], true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_grid'] = $this->language->get('text_grid');
		$data['text_list'] = $this->language->get('text_list');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_carousel']  = $this->language->get('text_carousel');
		$data['text_stores'] = $this->language->get('text_stores');

		$data['tab_general'] = $this->language->get('tab_general');
		$data['tab_languagetext'] = $this->language->get('tab_languagetext');
		$data['tab_sizesetting'] = $this->language->get('tab_sizesetting');
		$data['tab_colorsettings'] = $this->language->get('tab_colorsettings');
		$data['tab_support'] = $this->language->get('tab_support');

		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_description'] = $this->language->get('entry_description');
		$data['entry_images'] = $this->language->get('entry_images');
		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_carousel'] = $this->language->get('entry_carousel');
		$data['entry_caritem'] = $this->language->get('entry_caritem');
		$data['entry_sub_heading'] = $this->language->get('entry_sub_heading');
		$data['entry_view_all'] = $this->language->get('entry_view_all');
		$data['entry_width'] = $this->language->get('entry_width');
		$data['entry_height'] = $this->language->get('entry_height');
		$data['entry_layout'] = $this->language->get('entry_layout');
		$data['entry_desclength'] = $this->language->get('entry_desclength');
		$data['entry_title_color'] = $this->language->get('entry_title_color');
		$data['entry_desc_color'] = $this->language->get('entry_desc_color');
		$data['entry_viewall_color'] = $this->language->get('entry_viewall_color');
		$data['entry_background'] = $this->language->get('entry_background');
		$data['entry_border_color'] = $this->language->get('entry_border_color');
		$data['entry_bg_hover_color'] = $this->language->get('entry_bg_hover_color');
		$data['entry_carnav'] = $this->language->get('entry_carnav');
		$data['entry_carpage'] = $this->language->get('entry_carpage');
		$data['entry_carautoplay'] = $this->language->get('entry_carautoplay');

		$data['entry_width'] = $this->language->get('entry_width');
		$data['entry_height'] = $this->language->get('entry_height');

		$data['help_carousel'] = $this->language->get('help_carousel');
		$data['help_carnav'] = $this->language->get('help_carnav');
		$data['help_carpage'] = $this->language->get('help_carpage');
		$data['help_caritem'] = $this->language->get('help_caritem');
		$data['help_carautoplay'] = $this->language->get('help_carautoplay');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['jsub_category_width'])) {
			$data['error_jsub_category_width'] = $this->error['jsub_category_width'];
		} else {
			$data['error_jsub_category_width'] = '';
		}

		if (isset($this->error['jsub_category_height'])) {
			$data['error_jsub_category_height'] = $this->error['jsub_category_height'];
		} else {
			$data['error_jsub_category_height'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/jsub_category', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

		if(isset($data['store_id'])) {
			$data['action'] = $this->url->link('extension/jsub_category', 'user_token=' . $this->session->data['user_token'].'&store_id='. $data['store_id'], true);
		} else{
			$data['action'] = $this->url->link('extension/jsub_category', 'user_token=' . $this->session->data['user_token'], true);
		}

		$this->load->model('setting/store');

		$stores = $this->model_setting_store->getStores();

		$data['stores'] = array();
		$data['stores'][] = array(
			'href' => $this->url->link('extension/jsub_category', 'user_token=' . $this->session->data['user_token'].'&store_id=0', true),
			'name' => $this->language->get('text_default'),
			'store_id' => 0,
		);
		foreach ($stores as $key => $value) {
			$data['stores'][] = array(
				'href' => $this->url->link('extension/jsub_category', 'user_token=' . $this->session->data['user_token'].'&store_id='.$value['store_id'], true),
				'name' => $value['name'],
				'store_id' => $value['store_id'],
			);
		}

		$module_info = $this->model_setting_setting->getSetting('jsub_category', $data['store_id']);

		if (isset($this->request->post['jsub_category_status'])) {
			$data['jsub_category_status'] = $this->request->post['jsub_category_status'];
		} else if(isset($module_info['jsub_category_status'])) {
			$data['jsub_category_status'] = $module_info['jsub_category_status'];
		} else {
			$data['jsub_category_status'] = '';
		}

		if (isset($this->request->post['jsub_category_description'])) {
			$data['jsub_category_description'] = $this->request->post['jsub_category_description'];
		} else if(isset($module_info['jsub_category_description'])) {
			$data['jsub_category_description'] = $module_info['jsub_category_description'];
		} else {
			$data['jsub_category_description'] = '';
		}

		if (isset($this->request->post['jsub_category_carousel'])) {
			$data['jsub_category_carousel'] = $this->request->post['jsub_category_carousel'];
		} else if(isset($module_info['jsub_category_carousel'])) {
			$data['jsub_category_carousel'] = $module_info['jsub_category_carousel'];
		} else {
			$data['jsub_category_carousel'] = '';
		}

		if (isset($this->request->post['jsub_category_carnav'])) {
			$data['jsub_category_carnav'] = $this->request->post['jsub_category_carnav'];
		} else if(isset($module_info['jsub_category_carnav'])) {
			$data['jsub_category_carnav'] = $module_info['jsub_category_carnav'];
		} else {
			$data['jsub_category_carnav'] = '';
		}

		if (isset($this->request->post['jsub_category_carpage'])) {
			$data['jsub_category_carpage'] = $this->request->post['jsub_category_carpage'];
		} else if(isset($module_info['jsub_category_carpage'])) {
			$data['jsub_category_carpage'] = $module_info['jsub_category_carpage'];
		} else {
			$data['jsub_category_carpage'] = '';
		}

		if (isset($this->request->post['jsub_category_carautoplay'])) {
			$data['jsub_category_carautoplay'] = $this->request->post['jsub_category_carautoplay'];
		} else if(isset($module_info['jsub_category_carautoplay'])) {
			$data['jsub_category_carautoplay'] = $module_info['jsub_category_carautoplay'];
		} else {
			$data['jsub_category_carautoplay'] = '';
		}

		if (isset($this->request->post['jsub_category_caritem'])) {
			$data['jsub_category_caritem'] = $this->request->post['jsub_category_caritem'];
		} else if(isset($module_info['jsub_category_caritem'])) {
			$data['jsub_category_caritem'] = $module_info['jsub_category_caritem'];
		} else {
			$data['jsub_category_caritem'] = '';
		}

		if (isset($this->request->post['jsub_category_images'])) {
			$data['jsub_category_images'] = $this->request->post['jsub_category_images'];
		} else if(isset($module_info['jsub_category_images'])) {
			$data['jsub_category_images'] = $module_info['jsub_category_images'];
		} else {
			$data['jsub_category_images'] = '';
		}

		if (isset($this->request->post['jsub_category_title'])) {
			$data['jsub_category_title'] = $this->request->post['jsub_category_title'];
		} else if(isset($module_info['jsub_category_title'])) {
			$data['jsub_category_title'] = $module_info['jsub_category_title'];
		} else {
			$data['jsub_category_title'] = '';
		}

		if (isset($this->request->post['jsub_category_data'])) {
			$data['jsub_category_data'] = $this->request->post['jsub_category_data'];
		} else if(isset($module_info['jsub_category_data'])) {
			$data['jsub_category_data'] = $module_info['jsub_category_data'];
		} else {
			$data['jsub_category_data'] = array();
		}

		if (isset($this->request->post['jsub_category_width'])) {
			$data['jsub_category_width'] = $this->request->post['jsub_category_width'];
		} else if(isset($module_info['jsub_category_width'])) {
			$data['jsub_category_width'] = $module_info['jsub_category_width'];
		} else {
			$data['jsub_category_width'] = '';
		}

		if (isset($this->request->post['jsub_category_height'])) {
			$data['jsub_category_height'] = $this->request->post['jsub_category_height'];
		} else if(isset($module_info['jsub_category_height'])) {
			$data['jsub_category_height'] = $module_info['jsub_category_height'];
		} else {
			$data['jsub_category_height'] = '';
		}

		if (isset($this->request->post['jsub_category_layout'])) {
			$data['jsub_category_layout'] = $this->request->post['jsub_category_layout'];
		} else if(isset($module_info['jsub_category_layout'])) {
			$data['jsub_category_layout'] = $module_info['jsub_category_layout'];
		} else {
			$data['jsub_category_layout'] = 1;
		}

		if (isset($this->request->post['jsub_category_desclength'])) {
			$data['jsub_category_desclength'] = $this->request->post['jsub_category_desclength'];
		} else if(isset($module_info['jsub_category_desclength'])) {
			$data['jsub_category_desclength'] = $module_info['jsub_category_desclength'];
		} else {
			$data['jsub_category_desclength'] = '';
		}

		if (isset($this->request->post['jsub_category_bg'])) {
			$data['jsub_category_bg'] = $this->request->post['jsub_category_bg'];
		} else if(isset($module_info['jsub_category_bg'])) {
			$data['jsub_category_bg'] = $module_info['jsub_category_bg'];
		} else {
			$data['jsub_category_bg'] = '';
		}

		if (isset($this->request->post['jsub_category_border'])) {
			$data['jsub_category_border'] = $this->request->post['jsub_category_border'];
		} else if(isset($module_info['jsub_category_border'])) {
			$data['jsub_category_border'] = $module_info['jsub_category_border'];
		} else {
			$data['jsub_category_border'] = '';
		}

		if (isset($this->request->post['jsub_category_bg_hover_color'])) {
			$data['jsub_category_bg_hover_color'] = $this->request->post['jsub_category_bg_hover_color'];
		} else if(isset($module_info['jsub_category_bg_hover_color'])) {
			$data['jsub_category_bg_hover_color'] = $module_info['jsub_category_bg_hover_color'];
		} else {
			$data['jsub_category_bg_hover_color'] = '';
		}

		if (isset($this->request->post['jsub_category_titlecolor'])) {
			$data['jsub_category_titlecolor'] = $this->request->post['jsub_category_titlecolor'];
		} else if(isset($module_info['jsub_category_titlecolor'])) {
			$data['jsub_category_titlecolor'] = $module_info['jsub_category_titlecolor'];
		} else {
			$data['jsub_category_titlecolor'] = '';
		}

		if (isset($this->request->post['jsub_category_desccolor'])) {
			$data['jsub_category_desccolor'] = $this->request->post['jsub_category_desccolor'];
		} else if(isset($module_info['jsub_category_desccolor'])) {
			$data['jsub_category_desccolor'] = $module_info['jsub_category_desccolor'];
		} else {
			$data['jsub_category_desccolor'] = '';
		}

		if (isset($this->request->post['jsub_category_viewallcolor'])) {
			$data['jsub_category_viewallcolor'] = $this->request->post['jsub_category_viewallcolor'];
		} else if(isset($module_info['jsub_category_viewallcolor'])) {
			$data['jsub_category_viewallcolor'] = $module_info['jsub_category_viewallcolor'];
		} else {
			$data['jsub_category_viewallcolor'] = '';
		}

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$store_info = $this->model_setting_store->getStore($data['store_id']);
		if($store_info) {
			$data['store_name'] = $store_info['name'];
		}else{
			$data['store_name'] = $this->language->get('text_default');
		}

		if (isset($this->error['jsub_category_width'])) {
			$data['error_jsub_category_width'] = $this->error['jsub_category_width'];
		} else {
			$data['error_jsub_category_width'] = '';
		}

		if (isset($this->error['jsub_category_height'])) {
			$data['error_jsub_category_height'] = $this->error['jsub_category_height'];
		} else {
			$data['error_jsub_category_height'] = '';
		}

		if (isset($this->error['jsub_category_caritem'])) {
			$data['error_jsub_category_caritem'] = $this->error['jsub_category_caritem'];
		} else {
			$data['error_jsub_category_caritem'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$old_template = $this->config->get('template_engine');
		$this->config->set('template_engine', 'template');

		$this->response->setOutput($this->load->view('extension/jsub_category', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/jsub_category')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['jsub_category_width']) {
			$this->error['jsub_category_width'] = $this->language->get('error_jsub_category_width');
		}

		if (!$this->request->post['jsub_category_height']) {
			$this->error['jsub_category_height'] = $this->language->get('error_jsub_category_height');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}
}