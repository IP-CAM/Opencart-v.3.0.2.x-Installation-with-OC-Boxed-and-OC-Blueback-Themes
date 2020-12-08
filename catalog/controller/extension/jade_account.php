<?php
class ControllerExtensionJadeAccount extends Controller {
	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('extension/jade_account');

		$this->load->model('catalog/product');

		$this->load->model('account/order');

		$this->load->model('account/customer');

		$this->load->model('extension/jade_account');

		$this->load->model('tool/image');

		$this->document->addStyle('catalog/view/theme/default/stylesheet/jadeaccount/style.css');

		$this->document->addStyle('catalog/view/javascript/jquery/owl-carousel/owl.carousel.css');
		$this->document->addScript('catalog/view/javascript/jquery/owl-carousel/owl.carousel.min.js');

		$data['languageid'] = $languageid = $this->config->get('config_language_id');
		$account_description = $this->config->get('jade_account_description');
		$p_width = $this->config->get('jade_account_width');
		$p_height = $this->config->get('jade_account_width');

		$heading_title = !empty($account_description[$languageid]['heading_title']) ? $account_description[$languageid]['heading_title'] : '';
		$offer_title = isset($account_description[$languageid]['offer_title']) ? $account_description[$languageid]['offer_title'] : '';
		$recent_orders = isset($account_description[$languageid]['latest_orders_title']) ? $account_description[$languageid]['latest_orders_title'] : '';


		$data['heading_title'] = $heading_title;
		$data['offer_title'] = $offer_title;
		$data['recent_orders'] = $recent_orders;

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/account', '', true)
		);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		/* Customer Profile Starts */
		$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
		$data['myname'] = $this->customer->getFirstName() .' '. $this->customer->getLastName();
		$data['myemail'] = $this->customer->getEmail();
		$data['mytelephone'] = $this->customer->getTelephone();

		if($this->config->get('jade_account_dp_width')) {
			$dp_width = $this->config->get('jade_account_dp_width');
		} else {
			$dp_width = 130;
		}

		if(!empty($customer_info['mydp']) && $this->config->get('jade_account_image_allow')) {
			$data['mydp'] = $this->model_tool_image->resize($customer_info['mydp'], $dp_width, $dp_width);
		} else if($this->config->get('jade_account_default_image')) {
			$data['mydp'] = $this->model_tool_image->resize($this->config->get('jade_account_default_image'), $dp_width, $dp_width);
		} else {
			$data['mydp'] = $this->model_tool_image->resize('no_image.png', $dp_width, $dp_width);
		}

		$data['display_picture'] = $this->config->get('jade_account_display_picture');
		$data['dp_allow'] = $this->config->get('jade_account_image_allow');

		$data['edit'] = $this->url->link('account/edit', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);
		/* Customer Profile Ends */

		/* Promotional Products Starts */
		$data['product_status'] = $this->config->get('jade_account_product_status');
		$products = (array)$this->config->get('jade_account_product');
		foreach ($products as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);
			if ($product_info) {
				if ($product_info['image']) {
					$image = $this->model_tool_image->resize($product_info['image'], $p_width, $p_height);
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $p_width, $p_height);
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$product_info['special']) {
					$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				$data['products'][] = array(
					'product_id'  => $product_info['product_id'],
					'thumb'       => $image,
					'name'        => $product_info['name'],
					'minimum'     => $product_info['minimum'],
					'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('jade_account_description_limit')) . '..',
					'price'       => $price,
					'special'     => $special,
					'href'        => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
				);
			}
		}
		/* Promotional Products Ends */

		/* Widget Starts */
		$data['account_widget'] = (array)$this->config->get('jade_account_widget');

		$any_widget = false;
		foreach($data['account_widget'] as $each_widget) {
			if($each_widget) {
				$any_widget 	= true;
			}
		}

		$data['any_widget'] = $any_widget;



		$data['transactions_moreinfo']	= $this->url->link('account/transaction', '', true);
		$data['wishlists_moreinfo']	= $this->url->link('account/wishlist', '', true);
		$data['rewards_moreinfo']	= $this->url->link('account/reward', '', true);
		$data['orders_moreinfo']	= $this->url->link('account/order', '', true);
		$data['downloads_moreinfo']	= $this->url->link('account/download', '', true);

		$data['t_transactions'] 	= $this->model_extension_jade_account->getTotalAmount();
		$data['t_transactions'] 	= $this->currency->format($data['t_transactions'], $this->session->data['currency']);

		$data['t_wishlists'] 	= $this->model_extension_jade_account->getTotalWishlist();
		$data['t_rewards'] 		= $this->model_extension_jade_account->getTotalPoints();
		$data['t_orders'] 		= $this->model_account_order->getTotalOrders();
		$data['t_downloads'] 	= $this->model_extension_jade_account->getTotalDownloads();
		/* Widget Ends */

		/* Account Url Starts */
		$data['account_urls'] = (array)$this->config->get('jade_account_url');

		function URLSortOrder($a, $b) {
		    return $a['sort_order'] - $b['sort_order'];
		}

		usort($data['account_urls'], 'URLSortOrder');
		/* Account Url Ends */

		/* Affiliate Account Url Starts */
		$data['affiliate_title'] = isset($account_description[$languageid]['affiliate_title']) ? $account_description[$languageid]['affiliate_title'] : '';


		$data['affiliate_links'] = (array)$this->config->get('jade_account_affiliate_url');
		$data['affiliate_status'] = $this->config->get('jade_account_affiliate_status');

		function URLAffiliateSortOrder($a, $b) {
		    return $a['sort_order'] - $b['sort_order'];
		}

		usort($data['affiliate_links'], 'URLAffiliateSortOrder');

		$affiliate_info = $this->model_account_customer->getAffiliate($this->customer->getId());
		if($affiliate_info) {
			$data['aff_usertype'] = 'logged';
		} else {
			$data['aff_usertype'] = 'register';
		}
		/* Affiliate Url Ends */

		/* Latest Orders Starts */
		$data['show_recentorders'] = $this->config->get('jade_account_latestorders');

		$latest_orders = $this->model_account_order->getOrders(0, 10);

		$data['orders'] = array();
		foreach ($latest_orders as $per_order) {
			$product_total = $this->model_account_order->getTotalOrderProductsByOrderId($per_order['order_id']);
			$voucher_total = $this->model_account_order->getTotalOrderVouchersByOrderId($per_order['order_id']);

			$data['orders'][] = array(
				'order_id'   => $per_order['order_id'],
				'name'       => $per_order['firstname'] . ' ' . $per_order['lastname'],
				'status'     => $per_order['status'],
				'products'   => ($product_total + $voucher_total),
				'total'      => $this->currency->format($per_order['total'], $per_order['currency_code'], $per_order['currency_value']),
				'date_added' => date($this->language->get('date_format_short'), strtotime($per_order['date_added'])),
				'view'       => $this->url->link('account/order/info', 'order_id=' . $per_order['order_id'], true),
			);
		}
		/* Latest Orders Ends */


		/* Color Style Starts */

		$data['popup_title'] = isset($account_description[$languageid]['popup_title']) ? $account_description[$languageid]['popup_title'] : $this->language->get('popup_title');
		$data['submit_button_text'] = isset($account_description[$languageid]['submit_button_text']) ? $account_description[$languageid]['submit_button_text'] : $this->language->get('text_button_submit');
		$data['success_message'] = isset($account_description[$languageid]['success_message']) ? $account_description[$languageid]['success_message'] : $this->language->get('text_success_message');
		$data['popup_description'] = isset($account_description[$languageid]['description']) ? html_entity_decode($account_description[$languageid]['description'], ENT_QUOTES, 'UTF-8') : '';

		$data['color_style'] = $this->account_style();
		/* Color Style Ends */



		/* Contact Admin Starts */
		$data['contact_status'] = $this->config->get('jade_account_contact');

		$button_contact = isset($account_description[$languageid]['contact_button']) ? $account_description[$languageid]['contact_button'] : '';
		$data['button_contact'] = $button_contact;

		if($this->config->get('jade_account_contact') && $this->config->get('jade_account_template') != 'account_2') {
			$data['contact_html'] = $this->account_contact();
		} else {
			$data['contact_html'] = '';
		}
		/* Contact Admin Ends  */


		$data['japanel_transactions'] = $this->language->get('japanel_transactions');
		$data['japanel_wishlists'] = $this->language->get('japanel_wishlists');
		$data['japanel_rewards'] = $this->language->get('japanel_rewards');
		$data['japanel_orders'] = $this->language->get('japanel_orders');
		$data['japanel_downloads'] = $this->language->get('japanel_downloads');

		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');

		$data['col_order_id'] = $this->language->get('col_order_id');
		$data['col_products'] = $this->language->get('col_products');
		$data['col_status'] = $this->language->get('col_status');
		$data['col_total'] = $this->language->get('col_total');
		$data['col_date_added'] = $this->language->get('col_date_added');
		$data['col_action'] = $this->language->get('col_action');

		$data['text_moreinfo'] = $this->language->get('text_moreinfo');
		$data['text_no_results'] = $this->language->get('text_no_results');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_email'] = $this->language->get('entry_email');
		$data['entry_enquiry'] = $this->language->get('entry_enquiry');
		$data['entry_telephone'] = $this->language->get('entry_telephone');

		if($this->config->get('jade_account_columnleft')) {
			$data['column_left'] = $this->load->controller('common/column_left');
		} else {
			$data['column_left'] = '';
		}

		if($this->config->get('jade_account_columnright')) {
			$data['column_right'] = $this->load->controller('common/column_right');
		} else {
			$data['column_right'] = '';
		}

		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		if($this->config->get('jade_account_template')) {
			$active_template = 'extension/jade_account/'. $this->config->get('jade_account_template');
		} else {
			$active_template = 'extension/jade_account/account_1';
		}

		$this->response->setOutput($this->load->view($active_template, $data));
	}

	public function account_contact() {
		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_email'] = $this->language->get('entry_email');
		$data['entry_enquiry'] = $this->language->get('entry_enquiry');
		$data['entry_telephone'] = $this->language->get('entry_telephone');

		$data['languageid'] = $languageid = $this->config->get('config_language_id');
		$account_description = $this->config->get('jade_account_description');

		$data['popup_title'] = isset($account_description[$languageid]['popup_title']) ? $account_description[$languageid]['popup_title'] : $this->language->get('popup_title');
		$data['submit_button_text'] = isset($account_description[$languageid]['submit_button_text']) ? $account_description[$languageid]['submit_button_text'] : $this->language->get('text_button_submit');
		$data['success_message'] = isset($account_description[$languageid]['success_message']) ? $account_description[$languageid]['success_message'] : $this->language->get('text_success_message');
		$data['popup_description'] = isset($account_description[$languageid]['description']) ? html_entity_decode($account_description[$languageid]['description'], ENT_QUOTES, 'UTF-8') : '';

		$data['myname'] = $this->customer->getFirstName() .' '. $this->customer->getLastName();
		$data['myemail'] = $this->customer->getEmail();
		$data['mytelephone'] = $this->customer->getTelephone();

		return $this->load->view('extension/jade_account/contact', $data);
	}

	public function account_style() {
		if($this->config->get('jade_account_dp_width')) {
			$data['dp_width'] = $this->config->get('jade_account_dp_width') . 'px';
		} else {
			$data['dp_width'] = '';
		}

		/* Account Page Colors Starts */
		$colors = (array)$this->config->get('jade_account_colors');

		$data['colors'] = array(
			'content_background'			=> isset($colors['content_background']) ? $colors['content_background'] : '',
			'content_font'					=> isset($colors['content_font']) ? $colors['content_font'] : '',
			'section_font'					=> isset($colors['section_font']) ? $colors['section_font'] : '',
			'section_moreinfo_background'	=> isset($colors['section_moreinfo_background']) ? $colors['section_moreinfo_background'] : '',
			'section_moreinfo_font'			=> isset($colors['section_moreinfo_font']) ? $colors['section_moreinfo_font'] : '',
			'links_border'					=> isset($colors['links_border']) ? $colors['links_border'] : '',
			'links_font'					=> isset($colors['links_font']) ? $colors['links_font'] : '',
			'links_background'				=> isset($colors['links_background']) ? $colors['links_background'] : '',
			'links_border_bottom'			=> isset($colors['links_border_bottom']) ? $colors['links_border_bottom'] : '',
			'latest_order_background'		=> isset($colors['latest_order_background']) ? $colors['latest_order_background'] : '',
			'table_font'					=> isset($colors['table_font']) ? $colors['table_font'] : '',
			'related_font'					=> isset($colors['related_font']) ? $colors['related_font'] : '',
		);

		/* Account Page Colors Ends */

		return $this->load->view('extension/jade_account/color_style', $data);
	}

	public function ChangeDP() {
		$this->load->language('extension/jade_account');

		$this->load->model('tool/image');

		$this->load->model('account/customer');

		$this->load->model('extension/jade_account');

		$json = array();

		if (!empty($this->request->files['file']['name']) && is_file($this->request->files['file']['tmp_name'])) {

			$filename = basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '', html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8')));

			if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 64)) {
				$json['error'] = $this->language->get('error_filename');
			}

			$allowed = array();

			$config_file_mime_allowed = "jpe\npng\njpg\njpeg\nbmp\ngif";
			$extension_allowed = preg_replace('~\r?\n~', "\n", $config_file_mime_allowed);

			$filetypes = explode("\n", $extension_allowed);

			foreach ($filetypes as $filetype) {
				$allowed[] = trim($filetype);
			}

			if (!in_array(strtolower(substr(strrchr($filename, '.'), 1)), $allowed)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			$allowed = array();
			$config_file_mime_allowed = "image/png\nimage/jpeg\nimage/gif\nimage/bmp";
			$mime_allowed = preg_replace('~\r?\n~', "\n", $config_file_mime_allowed);

			$filetypes = explode("\n", $mime_allowed);

			foreach ($filetypes as $filetype) {
				$allowed[] = trim($filetype);
			}

			if (!in_array($this->request->files['file']['type'], $allowed)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			$content = file_get_contents($this->request->files['file']['tmp_name']);

			if (preg_match('/\<\?php/i', $content)) {
				$json['error'] = $this->language->get('error_filetype');
			}

			if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
				$json['error'] = $this->language->get('error_upload_' . $this->request->files['file']['error']);
			}
		} else {
			$json['error'] = $this->language->get('error_upload');
		}

		if(!$this->config->get('jade_account_image_allow')) {
			$json['error'] = $this->language->get('error_filepermission');
		}

		if (!$json) {
			$account_pic = 'account-pic';

			if (!file_exists(DIR_IMAGE . $account_pic))
			{
			    mkdir(DIR_IMAGE . $account_pic, 0777, true);
			}

			if (is_file(DIR_IMAGE . $account_pic .'/'. $filename)) {
				$pathinfo = pathinfo(DIR_IMAGE . $account_pic .'/'. $filename);
				if($pathinfo) {
					$rand = rand(1, 100);
					$pic = $pathinfo['filename'] . '-' . $rand . '.'. $pathinfo['extension'];
				} else{
					$pic = $filename;
				}
			} else{
				$pic = $filename;
			}

			move_uploaded_file($this->request->files['file']['tmp_name'], DIR_IMAGE . $account_pic .'/'. $pic);

			$mydp = $account_pic . '/' . $pic;

			// Change DP
			$this->model_extension_jade_account->ChangeDP($mydp, $this->customer->getId());

			// Get DP
			$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

			// Set Thumb
			if (!empty($customer_info['mydp']) && is_file(DIR_IMAGE . $customer_info['mydp'])) {
				$json['mydp_thumb'] = $this->model_tool_image->resize($customer_info['mydp'], 130, 130);
			} else {
				$json['mydp_thumb'] = '';
			}

		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function sendcontact() {
		$this->load->language('extension/jade_account');

		$this->load->model('extension/jade_account');

		$json = array();

		if ((utf8_strlen(trim($this->request->post['name'])) < 1) || (utf8_strlen(trim($this->request->post['name'])) > 32)) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$json['error']['email'] = $this->language->get('error_email');
		}

		if(isset($this->request->post['telephone'])) {
			if ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32)) {
				$json['error']['telephone'] = $this->language->get('error_telephone');
			}
		}

		if (empty($this->request->post['enquiry'])) {
			$json['error']['enquiry'] = $this->language->get('error_enquiry');
		}

		if(!$json) {
			$post_data = array(
				'name'			=> isset($this->request->post['name']) ? $this->request->post['name'] : '',
				'email'			=> isset($this->request->post['email']) ? $this->request->post['email'] : '',
				'telephone'		=> isset($this->request->post['telephone']) ? $this->request->post['telephone'] : '',
				'enquiry'		=> isset($this->request->post['enquiry']) ? $this->request->post['enquiry'] : '',
			);

			$this->model_extension_jade_account->SendContactEnquiry($post_data);

			$json['success'] = true;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}