<?php
class ControllerextensionJadeAccount extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/jade_account');

		$this->load->model('setting/setting');

		$this->load->model('tool/image');

		$this->document->addStyle('view/stylesheet/jade_account/stylesheet.css');

		$this->document->addStyle('view/javascript/colorpicker/css/bootstrap-colorpicker.css');
		$this->document->addScript('view/javascript/colorpicker/js/bootstrap-colorpicker.js');

		$store_id = $data['store_id'] = 0;
		if(isset($this->request->get['store_id'])) {
			$store_id = $data['store_id'] = $this->request->get['store_id'];
		}

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('jade_account', $this->request->post, $store_id);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/jade_account', 'user_token=' . $this->session->data['user_token'] . '&store_id='.$store_id, true));
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_show'] = $this->language->get('text_show');
		$data['text_andrequired'] = $this->language->get('text_andrequired');
		$data['text_hide'] = $this->language->get('text_hide');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_show'] = $this->language->get('text_show');
		$data['text_hide'] = $this->language->get('text_hide');
		$data['text_char'] = $this->language->get('text_char');
		$data['text_register'] = $this->language->get('text_register');
		$data['text_logged'] = $this->language->get('text_logged');
		$data['text_affiliate_setting'] = $this->language->get('text_affiliate_setting');

		$data['text_control_panel'] = $this->language->get('text_control_panel');
		$data['text_widgets'] = $this->language->get('text_widgets');
		$data['text_orders'] = $this->language->get('text_orders');
		$data['text_custom_url'] = $this->language->get('text_custom_url');
		$data['text_contact'] = $this->language->get('text_contact');
		$data['text_modules'] = $this->language->get('text_modules');
		$data['text_affiliate_setting'] = $this->language->get('text_affiliate_setting');
		$data['text_affiliate_links'] = $this->language->get('text_affiliate_links');

		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_heading_title'] = $this->language->get('entry_heading_title');
		$data['entry_latest_orders'] = $this->language->get('entry_latest_orders');
		$data['entry_latestorders'] = $this->language->get('entry_latestorders');
		$data['entry_product_status'] = $this->language->get('entry_product_status');
		$data['entry_offer_title'] = $this->language->get('entry_offer_title');
		$data['entry_offer_product'] = $this->language->get('entry_offer_product');
		$data['entry_display_picture'] = $this->language->get('entry_display_picture');
		$data['entry_default_image'] = $this->language->get('entry_default_image');
		$data['entry_image_allow'] = $this->language->get('entry_image_allow');
		$data['entry_template'] = $this->language->get('entry_template');
		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_url'] = $this->language->get('entry_url');
		$data['entry_icon'] = $this->language->get('entry_icon');
		$data['entry_image_size'] = $this->language->get('entry_image_size');
		$data['entry_description_limit'] = $this->language->get('entry_description_limit');
		$data['entry_dp_size'] = $this->language->get('entry_dp_size');
		$data['entry_width'] = $this->language->get('entry_width');
		$data['entry_height'] = $this->language->get('entry_height');
		$data['entry_contactus'] = $this->language->get('entry_contactus');
		$data['entry_button_text'] = $this->language->get('entry_button_text');
		$data['entry_customeremail_status']	= $this->language->get('entry_customeremail_status');
		$data['entry_customersubject']	= $this->language->get('entry_customersubject');
		$data['entry_customermessage']	= $this->language->get('entry_customermessage');
		$data['entry_adminemail_status']	= $this->language->get('entry_adminemail_status');
		$data['entry_adminemail']	= $this->language->get('entry_adminemail');
		$data['entry_adminsubject']	= $this->language->get('entry_adminsubject');
		$data['entry_adminmessage']	= $this->language->get('entry_adminmessage');
		$data['entry_submit_button_text']	= $this->language->get('entry_submit_button_text');
		$data['entry_description']	= $this->language->get('entry_description');
		$data['entry_success_message']	= $this->language->get('entry_success_message');
		$data['entry_popup_title']	= $this->language->get('entry_popup_title');
		$data['entry_sort_order']	= $this->language->get('entry_sort_order');
		$data['entry_class']	= $this->language->get('entry_class');
		$data['entry_columnleft']	= $this->language->get('entry_columnleft');
		$data['entry_columnright']	= $this->language->get('entry_columnright');
		$data['entry_logintype']	= $this->language->get('entry_logintype');
		$data['entry_affiliate_status']	= $this->language->get('entry_affiliate_status');
		$data['entry_affiliate_title']	= $this->language->get('entry_affiliate_title');

		$data['help_offer_product'] = $this->language->get('help_offer_product');


		$data['column_widgets'] = $this->language->get('column_widgets');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_variale_value'] = $this->language->get('column_variale_value');
		$data['column_variale'] = $this->language->get('column_variale');

		$data['sc_store_name'] = $this->language->get('sc_store_name');
		$data['sc_store_url'] = $this->language->get('sc_store_url');
		$data['sc_store_logo'] = $this->language->get('sc_store_logo');
		$data['sc_name'] = $this->language->get('sc_name');
		$data['sc_email'] = $this->language->get('sc_email');
		$data['sc_telephone'] = $this->language->get('sc_telephone');
		$data['sc_enquiry'] = $this->language->get('sc_enquiry');
		$data['sc_date_added'] = $this->language->get('sc_date_added');

		$data['tab_general'] = $this->language->get('tab_general');
		$data['tab_customlink'] = $this->language->get('tab_customlink');
		$data['tab_affiliate_link'] = $this->language->get('tab_affiliate_link');
		$data['tab_product'] = $this->language->get('tab_product');
		$data['tab_profile'] = $this->language->get('tab_profile');
		$data['tab_template'] = $this->language->get('tab_template');
		$data['tab_colors'] = $this->language->get('tab_colors');
		$data['tab_contact'] = $this->language->get('tab_contact');
		$data['tab_support'] = $this->language->get('tab_support');
		$data['tab_contactlanguage'] = $this->language->get('tab_contactlanguage');
		$data['tab_customeremail'] = $this->language->get('tab_customeremail');
		$data['tab_adminemail'] = $this->language->get('tab_adminemail');

		$data['tab_general'] = $this->language->get('tab_general');

		$data['button_reset'] = $this->language->get('button_reset');
		$data['button_add_url'] = $this->language->get('button_add_url');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['contact_button'])) {
			$data['error_contact_button'] = $this->error['contact_button'];
		} else {
			$data['error_contact_button'] = array();
		}

		if (isset($this->error['url_title'])) {
			$data['error_url_title'] = $this->error['url_title'];
		} else {
			$data['error_url_title'] = array();
		}

		if (isset($this->error['affiliate_url_title'])) {
			$data['error_affiliate_url_title'] = $this->error['affiliate_url_title'];
		} else {
			$data['error_affiliate_url_title'] = array();
		}

		if (isset($this->error['latest_orders_title'])) {
			$data['error_latest_orders_title'] = $this->error['latest_orders_title'];
		} else {
			$data['error_latest_orders_title'] = array();
		}

		if (isset($this->error['affiliate_title'])) {
			$data['error_affiliate_title'] = $this->error['affiliate_title'];
		} else {
			$data['error_affiliate_title'] = array();
		}

		if (isset($this->error['offer_title'])) {
			$data['error_offer_title'] = $this->error['offer_title'];
		} else {
			$data['error_offer_title'] = array();
		}

		if (isset($this->error['popup_title'])) {
			$data['error_popup_title'] = $this->error['popup_title'];
		} else {
			$data['error_popup_title'] = array();
		}

		if (isset($this->error['submit_button_text'])) {
			$data['error_submit_button_text'] = $this->error['submit_button_text'];
		} else {
			$data['error_submit_button_text'] = array();
		}

		if (isset($this->error['success_message'])) {
			$data['error_success_message'] = $this->error['success_message'];
		} else {
			$data['error_success_message'] = array();
		}

		if (isset($this->error['dp_width'])) {
			$data['error_dp_width'] = $this->error['dp_width'];
		} else {
			$data['error_dp_width'] = '';
		}

		if (isset($this->error['template'])) {
			$data['error_template'] = $this->error['template'];
		} else {
			$data['error_template'] = '';
		}

		if (isset($this->error['adminemail'])) {
			$data['error_adminemail'] = $this->error['adminemail'];
		} else {
			$data['error_adminemail'] = '';
		}

		if (isset($this->error['adminsubject'])) {
			$data['error_adminsubject'] = $this->error['adminsubject'];
		} else {
			$data['error_adminsubject'] = array();
		}

		if (isset($this->error['customersubject'])) {
			$data['error_customersubject'] = $this->error['customersubject'];
		} else {
			$data['error_customersubject'] = array();
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
			'href' => $this->url->link('extension/jade_account', 'user_token=' . $this->session->data['user_token'], true)
		);

		if($store_id) {
			$data['action'] = $this->url->link('extension/jade_account', 'user_token=' . $this->session->data['user_token'] . '&store_id='. $store_id, true);
		} else {
			$data['action'] = $this->url->link('extension/jade_account', 'user_token=' . $this->session->data['user_token'], true);
		}

		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

		$this->load->model('setting/store');
		$stores = $this->model_setting_store->getStores();

		$data['stores'] = array();
		$data['stores'][] = array(
			'href' => $this->url->link('extension/jade_account', 'user_token=' . $this->session->data['user_token'].'&store_id=0', true),
			'name' => $this->language->get('text_default'),
			'store_id' => 0,
		);
		foreach ($stores as $key => $value) {
			$data['stores'][] = array(
				'href' => $this->url->link('extension/jade_account', 'user_token=' . $this->session->data['user_token'].'&store_id='.$value['store_id'], true),
				'name' => $value['name'],
				'store_id' => $value['store_id'],
			);
		}

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		// Widgets
		$data['widgets'] = array();
		$data['widgets'][] = array(
			'type'				=> 'transaction',
			'text'				=> $this->language->get('entry_transaction'),
		);

		$data['widgets'][] = array(
			'type'				=> 'wishlist',
			'text'				=> $this->language->get('entry_wishlist'),
		);

		$data['widgets'][] = array(
			'type'				=> 'reward_points',
			'text'				=> $this->language->get('entry_reward_points'),
		);

		$data['widgets'][] = array(
			'type'				=> 'orders',
			'text'				=> $this->language->get('entry_orders'),
		);

		$data['widgets'][] = array(
			'type'				=> 'downloads',
			'text'				=> $this->language->get('entry_downloads'),
		);

		// Theme Templates
		$data['templates'] = array();
		$data['templates'][] = array(
			'type'				=> 'account_1',
			'text'				=> $this->language->get('template_account_1'),
			'preview'			=> 'view/image/jadeaccount-dashboard/account_1.jpg',
		);

		$data['templates'][] = array(
			'type'				=> 'account_2',
			'text'				=> $this->language->get('template_account_2'),
			'preview'			=> 'view/image/jadeaccount-dashboard/account_2.jpg',
		);

		$data['templates'][] = array(
			'type'				=> 'account_3',
			'text'				=> $this->language->get('template_account_3'),
			'preview'			=> 'view/image/jadeaccount-dashboard/account_3.jpg',
		);

		$data['templates'][] = array(
			'type'				=> 'account_4',
			'text'				=> $this->language->get('template_account_4'),
			'preview'			=> 'view/image/jadeaccount-dashboard/account_4.jpg',
		);


		// Account Page Colors
		$data['account_colors'] = array();

		$data['account_colors'][] = array(
			'var'				=> 'content_background',
			'text'				=> $this->language->get('color_content_background'),
		);

		$data['account_colors'][] = array(
			'var'				=> 'content_font',
			'text'				=> $this->language->get('color_content_font'),
		);

		$data['account_colors'][] = array(
			'var'				=> 'section_font',
			'text'				=> $this->language->get('color_section_font'),
		);

		$data['account_colors'][] = array(
			'var'				=> 'section_moreinfo_background',
			'text'				=> $this->language->get('color_section_moreinfo_background'),
		);

		$data['account_colors'][] = array(
			'var'				=> 'section_moreinfo_font',
			'text'				=> $this->language->get('color_section_moreinfo_font'),
		);

		$data['account_colors'][] = array(
			'var'				=> 'links_border',
			'text'				=> $this->language->get('color_links_border'),
		);

		$data['account_colors'][] = array(
			'var'				=> 'links_font',
			'text'				=> $this->language->get('color_links_font'),
		);

		$data['account_colors'][] = array(
			'var'				=> 'links_background',
			'text'				=> $this->language->get('color_links_background'),
		);

		$data['account_colors'][] = array(
			'var'				=> 'links_border_bottom',
			'text'				=> $this->language->get('color_links_border_bottom'),
		);

		$data['account_colors'][] = array(
			'var'				=> 'latest_order_background',
			'text'				=> $this->language->get('color_latest_order_background'),
		);

		$data['account_colors'][] = array(
			'var'				=> 'table_font',
			'text'				=> $this->language->get('color_table_font'),
		);

		$data['account_colors'][] = array(
			'var'				=> 'related_font',
			'text'				=> $this->language->get('color_related_font'),
		);

		$data['account_colors'] = array_chunk($data['account_colors'], 6);

		$module_info = $this->model_setting_setting->getSetting('jade_account', $store_id);

		if (isset($this->request->post['jade_account_status'])) {
			$data['jade_account_status'] = $this->request->post['jade_account_status'];
		} elseif(isset($module_info['jade_account_status'])) {
			$data['jade_account_status'] = $module_info['jade_account_status'];
		} else {
			$data['jade_account_status'] = '';
		}

		if (isset($this->request->post['jade_account_affiliate_status'])) {
			$data['jade_account_affiliate_status'] = $this->request->post['jade_account_affiliate_status'];
		} elseif(isset($module_info['jade_account_affiliate_status'])) {
			$data['jade_account_affiliate_status'] = $module_info['jade_account_affiliate_status'];
		} else {
			$data['jade_account_affiliate_status'] = '';
		}

		if (isset($this->request->post['jade_account_contact'])) {
			$data['jade_account_contact'] = $this->request->post['jade_account_contact'];
		} elseif(isset($module_info['jade_account_contact'])) {
			$data['jade_account_contact'] = $module_info['jade_account_contact'];
		} else {
			$data['jade_account_contact'] = 1;
		}

		if (isset($this->request->post['jade_account_widget'])) {
			$data['jade_account_widget'] = $this->request->post['jade_account_widget'];
		} elseif(isset($module_info['jade_account_widget'])) {
			$data['jade_account_widget'] = (array)$module_info['jade_account_widget'];
		} else {
			$data['jade_account_widget'] = array();
		}

		if (isset($this->request->post['jade_account_latestorders'])) {
			$data['jade_account_latestorders'] = $this->request->post['jade_account_latestorders'];
		} elseif(isset($module_info['jade_account_latestorders'])) {
			$data['jade_account_latestorders'] = $module_info['jade_account_latestorders'];
		} else {
			$data['jade_account_latestorders'] = 1;
		}

		if (isset($this->request->post['jade_account_columnleft'])) {
			$data['jade_account_columnleft'] = $this->request->post['jade_account_columnleft'];
		} elseif(isset($module_info['jade_account_columnleft'])) {
			$data['jade_account_columnleft'] = $module_info['jade_account_columnleft'];
		} else {
			$data['jade_account_columnleft'] = '';
		}

		if (isset($this->request->post['jade_account_columnright'])) {
			$data['jade_account_columnright'] = $this->request->post['jade_account_columnright'];
		} elseif(isset($module_info['jade_account_columnright'])) {
			$data['jade_account_columnright'] = $module_info['jade_account_columnright'];
		} else {
			$data['jade_account_columnright'] = '';
		}

		if (isset($this->request->post['jade_account_display_picture'])) {
			$data['jade_account_display_picture'] = $this->request->post['jade_account_display_picture'];
		} elseif(isset($module_info['jade_account_display_picture'])) {
			$data['jade_account_display_picture'] = $module_info['jade_account_display_picture'];
		} else {
			$data['jade_account_display_picture'] = 1;
		}

		if (isset($this->request->post['jade_account_default_image'])) {
			$data['jade_account_default_image'] = $this->request->post['jade_account_default_image'];
		} elseif(isset($module_info['jade_account_default_image'])) {
			$data['jade_account_default_image'] = $module_info['jade_account_default_image'];
		} else {
			$data['jade_account_default_image'] = '';
		}

		if (isset($data['jade_account_default_image']) && is_file(DIR_IMAGE . $data['jade_account_default_image'])) {
			$data['thumb'] = $this->model_tool_image->resize($data['jade_account_default_image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		if (isset($this->request->post['jade_account_image_allow'])) {
			$data['jade_account_image_allow'] = $this->request->post['jade_account_image_allow'];
		} elseif(isset($module_info['jade_account_image_allow'])) {
			$data['jade_account_image_allow'] = $module_info['jade_account_image_allow'];
		} else {
			$data['jade_account_image_allow'] = 1;
		}

		if (isset($this->request->post['jade_account_dp_width'])) {
			$data['jade_account_dp_width'] = $this->request->post['jade_account_dp_width'];
		} elseif(isset($module_info['jade_account_dp_width'])) {
			$data['jade_account_dp_width'] = $module_info['jade_account_dp_width'];
		} else {
			$data['jade_account_dp_width'] = 130;
		}

		if (isset($this->request->post['jade_account_product_status'])) {
			$data['jade_account_product_status'] = $this->request->post['jade_account_product_status'];
		} elseif(isset($module_info['jade_account_product_status'])) {
			$data['jade_account_product_status'] = $module_info['jade_account_product_status'];
		} else {
			$data['jade_account_product_status'] = 1;
		}

		if (isset($this->request->post['jade_account_description'])) {
			$data['jade_account_description'] = $this->request->post['jade_account_description'];
		} elseif(isset($module_info['jade_account_description'])) {
			$data['jade_account_description'] = (array)$module_info['jade_account_description'];
		} else {
			$data['jade_account_description'] = array();
		}

		$this->load->model('catalog/product');
		$data['products'] = array();

		if (!empty($this->request->post['jade_account_product'])) {
			$products = $this->request->post['jade_account_product'];
		} elseif(isset($module_info['jade_account_product'])) {
			$products = (array)$module_info['jade_account_product'];
		} else {
			$products = array();
		}

		foreach ($products as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if ($product_info) {
				$data['products'][] = array(
					'product_id' => $product_info['product_id'],
					'name'       => $product_info['name']
				);
			}
		}

		if (isset($this->request->post['jade_account_template'])) {
			$data['jade_account_template'] = $this->request->post['jade_account_template'];
		} elseif(isset($module_info['jade_account_template'])) {
			$data['jade_account_template'] = $module_info['jade_account_template'];
		} else {
			$data['jade_account_template'] = 'jade_account_1';
		}

		if (isset($this->request->post['jade_account_url'])) {
			$data['urls'] = $this->request->post['jade_account_url'];
		} elseif(isset($module_info['jade_account_url'])) {
			$data['urls'] = (array)$module_info['jade_account_url'];
		} else {
			$data['urls'] = array();
		}

		if (isset($this->request->post['jade_account_affiliate_url'])) {
			$data['affiliate_urls'] = $this->request->post['jade_account_affiliate_url'];
		} elseif(isset($module_info['jade_account_affiliate_url'])) {
			$data['affiliate_urls'] = (array)$module_info['jade_account_affiliate_url'];
		} else {
			$data['affiliate_urls'] = array();
		}

		if (isset($this->request->post['jade_account_width'])) {
			$data['jade_account_width'] = $this->request->post['jade_account_width'];
		} elseif(isset($module_info['jade_account_width'])) {
			$data['jade_account_width'] = $module_info['jade_account_width'];
		} else {
			$data['jade_account_width'] = 100;
		}

		if (isset($this->request->post['jade_account_height'])) {
			$data['jade_account_height'] = $this->request->post['jade_account_height'];
		} elseif(isset($module_info['jade_account_height'])) {
			$data['jade_account_height'] = $module_info['jade_account_height'];
		} else {
			$data['jade_account_height'] = 100;
		}

		if (isset($this->request->post['jade_account_description_limit'])) {
			$data['jade_account_description_limit'] = $this->request->post['jade_account_description_limit'];
		} elseif(isset($module_info['jade_account_description_limit'])) {
			$data['jade_account_description_limit'] = $module_info['jade_account_description_limit'];
		} else {
			$data['jade_account_description_limit'] = 100;
		}

		if (isset($this->request->post['jade_account_colors'])) {
			$data['jade_account_colors'] = $this->request->post['jade_account_colors'];
		} elseif(isset($module_info['jade_account_colors'])) {
			$data['jade_account_colors'] = (array)$module_info['jade_account_colors'];
		} else {
			$data['jade_account_colors'] = array();
		}

		if (isset($this->request->post['jade_account_customeremail_status'])) {
			$data['jade_account_customeremail_status'] = $this->request->post['jade_account_customeremail_status'];
		} elseif(isset($module_info['jade_account_customeremail_status'])) {
			$data['jade_account_customeremail_status'] = $module_info['jade_account_customeremail_status'];
		} else {
			$data['jade_account_customeremail_status'] = 0;
		}

		if (isset($this->request->post['jade_account_adminemail_status'])) {
			$data['jade_account_adminemail_status'] = $this->request->post['jade_account_adminemail_status'];
		} elseif(isset($module_info['jade_account_adminemail_status'])) {
			$data['jade_account_adminemail_status'] = $module_info['jade_account_adminemail_status'];
		} else {
			$data['jade_account_adminemail_status'] = 0;
		}

		if (isset($this->request->post['jade_account_adminemail_email'])) {
			$data['jade_account_adminemail_email'] = $this->request->post['jade_account_adminemail_email'];
		} elseif(isset($module_info['jade_account_adminemail_email'])) {
			$data['jade_account_adminemail_email'] = $module_info['jade_account_adminemail_email'];
		} else {
			$data['jade_account_adminemail_email'] = $this->config->get('config_email');
		}

		if (isset($this->request->post['jade_account_email'])) {
			$data['jade_account_email'] = $this->request->post['jade_account_email'];
		} elseif(isset($module_info['jade_account_email'])) {
			$data['jade_account_email'] = (array)$module_info['jade_account_email'];
		} else {
			$data['jade_account_email'] = array();
		}

		$data['languageid'] = $this->config->get('config_language_id');

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->config->set('template_engine', 'template');
		$this->response->setOutput($this->load->view('extension/jade_account', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/jade_account')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['jade_account_url'] as $url_row => $url) {
			foreach ($url['desc'] as $language_id => $desc) {
				if ((utf8_strlen($desc['name']) < 2) || (utf8_strlen($desc['name']) > 255)) {
					$this->error['url_title'][$url_row][$language_id] = $this->language->get('error_url_title');
				}
			}
		}

		foreach ($this->request->post['jade_account_affiliate_url'] as $affiliate_url_row => $affiliate_url) {
			foreach ($affiliate_url['desc'] as $language_id => $affiliate_desc) {
				if ((utf8_strlen($affiliate_desc['name']) < 2) || (utf8_strlen($affiliate_desc['name']) > 255)) {
					$this->error['affiliate_url_title'][$affiliate_url_row][$language_id] = $this->language->get('error_url_title');
				}
			}
		}

		foreach ($this->request->post['jade_account_description'] as $language_id => $value) {
			if(!empty($this->request->post['jade_account_contact'])) {
				if ((utf8_strlen($value['contact_button']) < 2) || (utf8_strlen($value['contact_button']) > 255)) {
					$this->error['contact_button'][$language_id] = $this->language->get('error_contact_button');
				}
			}

			if(!empty($this->request->post['jade_account_latestorders'])) {
				if ((utf8_strlen($value['latest_orders_title']) < 3) || (utf8_strlen($value['latest_orders_title']) > 255)) {
					$this->error['latest_orders_title'][$language_id] = $this->language->get('error_latest_orders_title');
				}
			}

			if(!empty($this->request->post['jade_account_affiliate_status'])) {
				if ((utf8_strlen($value['affiliate_title']) < 3) || (utf8_strlen($value['affiliate_title']) > 255)) {
					$this->error['affiliate_title'][$language_id] = $this->language->get('error_affiliate_title');
				}
			}

			if(!empty($this->request->post['jade_account_product_status'])) {
				if ((utf8_strlen($value['offer_title']) < 3) || (utf8_strlen($value['offer_title']) > 255)) {
					$this->error['offer_title'][$language_id] = $this->language->get('error_offer_title');
				}
			}

			if(!empty($this->request->post['jade_account_contact'])) {
				if ((utf8_strlen($value['popup_title']) < 3) || (utf8_strlen($value['popup_title']) > 255)) {
					$this->error['popup_title'][$language_id] = $this->language->get('error_popup_title');
				}

				if ((utf8_strlen($value['submit_button_text']) < 3) || (utf8_strlen($value['submit_button_text']) > 255)) {
					$this->error['submit_button_text'][$language_id] = $this->language->get('error_submit_button_text');
				}

				if ((utf8_strlen($value['success_message']) < 3) || (utf8_strlen($value['success_message']) > 255)) {
					$this->error['success_message'][$language_id] = $this->language->get('error_success_message');
				}
			}
		}

		foreach ($this->request->post['jade_account_email'] as $language_id => $email_value) {
			if(!empty($this->request->post['jade_account_contact']) && !empty($this->request->post['jade_account_adminemail_status'])) {
				if ((utf8_strlen($email_value['adminsubject']) < 3) || (utf8_strlen($email_value['adminsubject']) > 255)) {
					$this->error['adminsubject'][$language_id] = $this->language->get('error_subject');
				}
			}

			if(!empty($this->request->post['jade_account_contact']) && !empty($this->request->post['jade_account_customeremail_status'])) {
				if ((utf8_strlen($email_value['customersubject']) < 3) || (utf8_strlen($email_value['customersubject']) > 255)) {
					$this->error['customersubject'][$language_id] = $this->language->get('error_subject');
				}
			}
		}

		if(!empty($this->request->post['jade_account_image_allow'])) {
			if(empty($this->request->post['jade_account_dp_width']) || (int)$this->request->post['jade_account_dp_width'] <= 0) {
				$this->error['dp_width'] = $this->language->get('error_dp_width');
			}
		}

		if(empty($this->request->post['jade_account_template'])) {
			$this->error['template'] = $this->language->get('error_template');
		}

		if(!empty($this->request->post['jade_account_contact']) && !empty($this->request->post['jade_account_adminemail_status'])) {
			if ((utf8_strlen($this->request->post['jade_account_adminemail_email']) > 96) || !filter_var($this->request->post['jade_account_adminemail_email'], FILTER_VALIDATE_EMAIL)) {
				$this->error['adminemail'] = $this->language->get('error_email');
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}
}