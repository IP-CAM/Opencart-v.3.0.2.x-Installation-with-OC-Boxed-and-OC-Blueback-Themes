<?php
class ModelAccountJReturnEmail extends Model {
	public function sendEmailReturnAdd($return_id) {
		/*Order Return email starts*/
		if ($this->config->get('jreturnemail_status')) {

			$this->load->model('setting/setting');
			$this->load->model('checkout/order');
			$this->load->model('tool/image');

			$admin_dir = 'admin/';
			if ($this->config->get('jreturnemail_admin_dir')) {
				$admin_dir = $this->config->get('jreturnemail_admin_dir');
				$admin_dir = rtrim($admin_dir, '/');
				$admin_dir .= '/';
			}

			$return_query = $this->db->query("SELECT r.* FROM `" . DB_PREFIX . "return` r WHERE r.return_id = '" . (int)$return_id . "'");

			$return_info = $return_query->row;

			$order_info = $this->model_checkout_order->getOrder($return_info['order_id']);

			if ($order_info) {



			$admin_url = new Url(HTTP_SERVER.$admin_dir, HTTPS_SERVER.$admin_dir);

			$setting_config = $this->model_setting_setting->getSetting('config',$order_info['store_id'] );

			$getconfig =  function($key) use($setting_config) {
				if (!empty($setting_config[$key])) {
					return $setting_config[$key];
				} else {
					return $this->config->get($key);
				}
			};

			$config_language_id = $order_info['language_id'];
			if (empty($config_language_id)) {
				$config_language_id = (int)$this->config->get('config_language_id');
			}

			/*update return info array with additioanl information*/
			$return_info['reason'] = '';
			$return_info['action'] = '';
			$return_info['status'] = '';

			$return_reason_query = $this->db->query("SELECT rr.name FROM " . DB_PREFIX . "return_reason rr WHERE rr.return_reason_id = '" . (int)$return_info['return_reason_id'] . "' AND rr.language_id = '" . (int)$config_language_id . "'");


			if ($return_reason_query->row) {
				$return_info['reason'] = $return_reason_query->row['name'];
			}

			$return_action_query = $this->db->query("SELECT ra.name FROM " . DB_PREFIX . "return_action ra WHERE ra.return_action_id = '" . (int)$return_info['return_action_id'] . "' AND ra.language_id = '" . (int)$config_language_id . "'");

			if ($return_action_query->row) {
				$return_info['action'] = $return_action_query->row['name'];
			}

			$return_status_query = $this->db->query("SELECT rs.name FROM " . DB_PREFIX . "return_status rs WHERE rs.return_status_id = '" . (int)$return_info['return_status_id'] . "' AND rs.language_id = '" . (int)$config_language_id . "'");

			if ($return_status_query->row) {
				$return_info['status'] = $return_status_query->row['name'];
			}

			$product_info = array('image' => '', 'product_id' => $return_info['product_id']);

			if (empty($return_info['product_id'])) {
				$query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "order_product` WHERE order_id = '". (int)$return_info['order_id'] ."' AND name = '". $this->db->escape($return_info['product']) ."' AND model = '". $this->db->escape($return_info['model']) ."'");
					//  AND quantity = '". $this->db->escape($return_info['quantity']) ."'
				if ($query->row) {
					$return_info['product_id'] = $query->row['product_id'];
				}
			}

			if (!empty($return_info['product_id'])) {
				$product_query = $this->db->query("SELECT p.image, p.product_id FROM `" . DB_PREFIX . "product` p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.product_id = '". (int)$return_info['product_id'] ."' AND pd.language_id = '" . (int)$config_language_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$order_info['store_id'] . "'");
				if ($product_query->row) {
					$product_info = $product_query->row;
				}
			}

			$email_template = $this->config->get('jreturnemail_email');

			$store_name = $order_info['store_name'];
			if (empty($order_info['store_name'])) {
				$store_name = $getconfig('config_name');
			}

			$store_email = $getconfig('config_email');
			$store_fax = $getconfig('config_fax');
			$store_telephone = $getconfig('config_telephone');


			$store_url = $order_info['store_url'];
			if (empty($store_url)) {
				if ($this->request->server['HTTPS']) {
					$store_url = ($getconfig('config_ssl')) ? $getconfig('config_ssl') : HTTPS_SERVER;
				} else {
					$store_url = ($getconfig('config_url')) ? $getconfig('config_url') : HTTP_SERVER;
				}
			}


			$admin_email = $getconfig('config_email');

			if($this->config->get('jreturnemail_emailadmin') && filter_var($this->config->get('jreturnemail_emailadmin'), FILTER_VALIDATE_EMAIL)) {
				$admin_email = $this->config->get('jreturnemail_emailadmin');
			}

			$customer_email = $return_info['email'];
			if (empty($customer_email)) {
				$customer_email = $order_info['email'];
			}
			if (empty($customer_email)) {
				$customer_email = $this->customer->getEmail();
			}


			if($this->config->get('jreturnemail_productthumb_height') && $this->config->get('jreturnemail_productthumb_width')) {
				$product_thumb_width = $this->config->get('jreturnemail_productthumb_width');
				$product_thumb_height = $this->config->get('jreturnemail_productthumb_height');
			} else {
				$product_thumb_width = 100;
				$product_thumb_height = 100;
			}

			if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {

				$store_logo = $store_url .'image/'. $this->config->get('config_logo');
			} else {
				$store_logo = '';
			}

			if (is_file(DIR_IMAGE . $product_info['image'])) {
				$product_thumb = $this->model_tool_image->resize($product_info['image'], $product_thumb_width, $product_thumb_height);
			} else {
				$product_thumb = '';
			}

			$date_format = $this->language->get('date_format_short');
			if($this->config->get('jreturnemail_date_format')) {
				$date_format = $this->config->get('jreturnemail_date_format');
			}


			$template_admin_mail = array();
			if(isset($email_template['admin'][(int)$config_language_id])) {
				$template_admin_mail = $email_template['admin'][(int)$config_language_id];
			} else {
				reset($email_template['admin']);
				$first_key = key($email_template['admin']);
				if (isset($email_template['admin'][$first_key])) {
					$template_admin_mail = $email_template['admin'][$first_key];
				}
			}

			if ($template_admin_mail) {

			$find = array(
				'[STORE_NAME]',
				'[STORE_URL]',
				'[STORE_LOGO]',
				'[STORE_EMAIL]',
				'[STORE_FAX]',
				'[STORE_TELEPHONE]',

				'[PRODUCT_NAME]',
				'[PRODUCT_URL]',
				'[PRODUCT_MODEL]',
				'[PRODUCT_QTY]',
				'[PRODUCT_OPENED_STATUS]',
				'[PRODUCT_THUMB]',

				'[CUSTOMER_ID]',
				'[CUSTOMER_FIRSTNAME]',
				'[CUSTOMER_LASTNAME]',
				'[CUSTOMER_EMAIL]',
				'[CUSTOMER_TELEPHONE]',

				'[RETURN_ID]',
				'[RETURN_DATE_ADDED]',
				'[RETURN_REASON]',
				'[RETURN_STATUS]',
				'[RETURN_COMMENT]',
				'[RETURN_ACTION]',
				'[ORDER_ID]',
			);

			foreach ($order_info as $key => $value) {
				if (in_array($key, array('order_id','store_name','store_url'))) {
					continue;
				}
				$find[] = '[ORDER_'. strtoupper(strtolower($key))  .']';
			}

			$replace = array(
				'STORE_NAME' => $store_name,
				'STORE_URL' => $store_url,
				'STORE_LOGO' => $store_logo ? '<img src="'.$store_logo.'" alt="'.$store_name.'" />' : '' ,
				'STORE_EMAIL' => $store_email,
				'STORE_FAX' => $store_fax,
				'STORE_TELEPHONE' => $store_telephone,

				'PRODUCT_NAME' => $return_info['product'],
				'PRODUCT_URL' => $product_info['product_id'] ? $admin_url->link('catalog/product/edit', 'product_id=' . $product_info['product_id'], true) : '',
				'PRODUCT_MODEL' => $return_info['model'],
				'PRODUCT_QTY' => $return_info['quantity'],
				'PRODUCT_OPENED_STATUS' => $return_info['opened'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
				'PRODUCT_THUMB' => $product_thumb ? '<img src="'.  $product_thumb .'" alt="'.$return_info['product'].'" />' : '',

				'CUSTOMER_ID' => $return_info['customer_id'],
				'CUSTOMER_FIRSTNAME' => $return_info['firstname'],
				'CUSTOMER_LASTNAME' => $return_info['lastname'],
				'CUSTOMER_EMAIL' => $customer_email,
				'CUSTOMER_TELEPHONE' => $return_info['telephone'],

				'RETURN_ID' => $return_info['return_id'],
				'RETURN_DATE_ADDED' => date($date_format , strtotime($return_info['date_added']) ),
				'RETURN_REASON' => $return_info['reason'],
				'RETURN_STATUS' => $return_info['status'],
				'RETURN_COMMENT' => $return_info['comment'],
				'RETURN_ACTION' => $return_info['action'],
				'ORDER_ID' => $return_info['order_id'],

			);

			foreach ($order_info as $key => $value) {
				if (in_array($key, array('order_id','store_name','store_url'))) {
					continue;
				}

				if (is_array($value)) {
					$value = json_encode($value);
				}

				$replace['ORDER_'.strtoupper(strtolower($key))] = $value;
			}

			$subject = str_replace(array("\r\n", "\r", "\n"), '', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '', trim(str_replace($find, $replace, $template_admin_mail['subject']))));

			$message = str_replace(array("\r\n", "\r", "\n"), '', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '', trim(str_replace($find, $replace, $template_admin_mail['msg']))));

			if(VERSION <= '2.0.1.1') {
				$mail = new Mail($this->config->get('config_mail'));
			} else if(VERSION >= '3.0.0.0') {
					$mail = new Mail($this->config->get('config_mail_engine'));
					$mail->parameter = $this->config->get('config_mail_parameter');
					$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
					$mail->smtp_username = $this->config->get('config_mail_smtp_username');
					$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
					$mail->smtp_port = $this->config->get('config_mail_smtp_port');
					$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
				} else {
				$mail = new Mail();
				$mail->protocol = $this->config->get('config_mail_protocol');
				$mail->parameter = $this->config->get('config_mail_parameter');
				$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
				$mail->smtp_username = $this->config->get('config_mail_smtp_username');
				$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail->smtp_port = $this->config->get('config_mail_smtp_port');
				$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
			}

			$mail->setTo($admin_email);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setReplyTo($customer_email);
			$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
			$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
			$mail->send();
			// echo "Return Add Admin E-mail \n\n";
			// print_r($mail);
			}
			if ($this->config->get('jreturnemail_emailtocustomer')) {

				$template_customer_mail = array();
				if(isset($email_template['customer'][(int)$config_language_id])) {
					$template_customer_mail = $email_template['customer'][(int)$config_language_id];
				} else {
					reset($email_template['customer']);
					$first_key = key($email_template['customer']);
					if (isset($email_template['customer'][$first_key])) {
						$template_customer_mail = $email_template['customer'][$first_key];
					}
				}

				if ($template_customer_mail) {

				$find = array(
					'[STORE_NAME]',
					'[STORE_URL]',
					'[STORE_LOGO]',
					'[STORE_EMAIL]',
					'[STORE_FAX]',
					'[STORE_TELEPHONE]',

					'[PRODUCT_NAME]',
					'[PRODUCT_URL]',
					'[PRODUCT_MODEL]',
					'[PRODUCT_QTY]',
					'[PRODUCT_OPENED_STATUS]',
					'[PRODUCT_THUMB]',

					'[CUSTOMER_ID]',
					'[CUSTOMER_FIRSTNAME]',
					'[CUSTOMER_LASTNAME]',
					'[CUSTOMER_EMAIL]',
					'[CUSTOMER_TELEPHONE]',

					'[RETURN_ID]',
					'[RETURN_DATE_ADDED]',
					'[RETURN_REASON]',
					'[RETURN_STATUS]',
					'[RETURN_COMMENT]',
					'[RETURN_ACTION]',
					'[ORDER_ID]',
				);

				foreach ($order_info as $key => $value) {
					if (in_array($key, array('order_id','store_name','store_url'))) {
						continue;
					}
					$find[] = '[ORDER_'. strtoupper(strtolower($key))  .']';
				}

				$replace = array(
					'STORE_NAME' => $store_name,
					'STORE_URL' => $store_url,
					'STORE_LOGO' => $store_logo ? '<img src="'.$store_logo.'" alt="'.$store_name.'" />' : '' ,
					'STORE_EMAIL' => $store_email,
					'STORE_FAX' => $store_fax,
					'STORE_TELEPHONE' => $store_telephone,

					'PRODUCT_NAME' => $return_info['product'],
					'PRODUCT_URL' => $product_info['product_id'] ? $this->url->link('product/product', 'product_id=' . $product_info['product_id'], true) : '',
					'PRODUCT_MODEL' => $return_info['model'],
					'PRODUCT_QTY' => $return_info['quantity'],
					'PRODUCT_OPENED_STATUS' => $return_info['opened'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
					'PRODUCT_THUMB' => $product_thumb ? '<img src="'.  $product_thumb .'" alt="'.$return_info['product'].'" />' : '',

					'CUSTOMER_ID' => $return_info['customer_id'],
					'CUSTOMER_FIRSTNAME' => $return_info['firstname'],
					'CUSTOMER_LASTNAME' => $return_info['lastname'],
					'CUSTOMER_EMAIL' => $customer_email,
					'CUSTOMER_TELEPHONE' => $return_info['telephone'],

					'RETURN_ID' => $return_info['return_id'],
					'RETURN_DATE_ADDED' => date($date_format , strtotime($return_info['date_added']) ),
					'RETURN_REASON' => $return_info['reason'],
					'RETURN_STATUS' => $return_info['status'],
					'RETURN_COMMENT' => $return_info['comment'],
					'RETURN_ACTION' => $return_info['action'],
					'ORDER_ID' => $return_info['order_id'],

				);

				foreach ($order_info as $key => $value) {
					if (in_array($key, array('order_id','store_name','store_url'))) {
						continue;
					}

					if (is_array($value)) {
						$value = json_encode($value);
					}

					$replace['ORDER_'. strtoupper(strtolower($key))] = $value;
				}

				$subject = str_replace(array("\r\n", "\r", "\n"), '', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '', trim(str_replace($find, $replace, $template_customer_mail['subject']))));

				$message = str_replace(array("\r\n", "\r", "\n"), '', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '', trim(str_replace($find, $replace, $template_customer_mail['msg']))));

				if(VERSION <= '2.0.1.1') {
					$mail = new Mail($this->config->get('config_mail'));
				} else if(VERSION >= '3.0.0.0') {
					$mail = new Mail($this->config->get('config_mail_engine'));
					$mail->parameter = $this->config->get('config_mail_parameter');
					$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
					$mail->smtp_username = $this->config->get('config_mail_smtp_username');
					$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
					$mail->smtp_port = $this->config->get('config_mail_smtp_port');
					$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
				} else {
					$mail = new Mail();
					$mail->protocol = $this->config->get('config_mail_protocol');
					$mail->parameter = $this->config->get('config_mail_parameter');
					$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
					$mail->smtp_username = $this->config->get('config_mail_smtp_username');
					$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
					$mail->smtp_port = $this->config->get('config_mail_smtp_port');
					$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
				}

				$mail->setTo($customer_email);
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
				$mail->setReplyTo($admin_email);
				$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
				$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
				$mail->send();
				// echo "Return Add Customer E-mail \n\n";
				// print_r($mail);

				}

			}

			}
		}

		/*Order Return email ends*/
	}
}