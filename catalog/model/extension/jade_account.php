<?php
class ModelExtensionJadeAccount extends Model {
	public function ChangeDP($mydp, $customer_id) {
		$query = $this->db->query("SHOW COLUMNS FROM `". DB_PREFIX ."customer` WHERE `Field` = 'mydp'");
		if(!$query->num_rows) {
			$this->db->query("ALTER TABLE `". DB_PREFIX ."customer` ADD `mydp` varchar(255) NOT NULL AFTER `date_added`");
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "customer` SET mydp = '". $this->db->escape($mydp) ."' WHERE customer_id = '" . (int)$customer_id . "'");
	}

	public function getTotalAmount() {
		$query = $this->db->query("SELECT SUM(amount) AS total FROM `" . DB_PREFIX . "customer_transaction` WHERE customer_id = '" . (int)$this->customer->getId() . "' GROUP BY customer_id");

		if ($query->num_rows) {
			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function getTotalWishlist() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_wishlist WHERE customer_id = '" . (int)$this->customer->getId() . "'");

		return $query->row['total'];
	}

	public function getTotalPoints() {
		$query = $this->db->query("SELECT SUM(points) AS total FROM `" . DB_PREFIX . "customer_reward` WHERE customer_id = '" . (int)$this->customer->getId() . "' GROUP BY customer_id");

		if ($query->num_rows) {
			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function getTotalDownloads() {
		$implode = array();

		$order_statuses = $this->config->get('config_complete_status');

		foreach ($order_statuses as $order_status_id) {
			$implode[] = "o.order_status_id = '" . (int)$order_status_id . "'";
		}

		if ($implode) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id) LEFT JOIN " . DB_PREFIX . "product_to_download p2d ON (op.product_id = p2d.product_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND (" . implode(" OR ", $implode) . ")");

			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function SendContactEnquiry($post_data) {
		$email_description = $this->config->get('jade_account_email');
		$languageid = $this->config->get('config_language_id');

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$logo = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$logo = '';
		}

		$find = array(
			'[STORE_NAME]',
			'[STORE_URL]',
			'[STORE_LOGO]',
			'[USER_NAME]',
			'[USER_EMAIL]',
			'[USER_TELEPHONE]',
			'[USER_ENQUIRY]',
			'[DATE_ADDED]',
		);

		$replace = array(
			'STORE_NAME'		=> $this->config->get('config_name'),
			'STORE_URL'			=> $this->url->link('common/home', '', true),
			'STORE_LOGO'		=> '<img src="'. $logo .'" alt="'. $this->config->get('config_name') .'" title="'. $this->config->get('config_name') .'" />',
			'USER_NAME'			=> $post_data['name'],
			'USER_EMAIL'		=> $post_data['email'],
			'USER_TELEPHONE'	=> $post_data['telephone'],
			'USER_ENQUIRY'		=> $post_data['enquiry'],
			'DATE_ADDED'		=> date('Y-m-d H:i'),
		);

		// Customer Email
		if($this->config->get('jade_account_customeremail_status')) {
			$subject = !empty($email_description[$languageid]['customersubject']) ? html_entity_decode($email_description[$languageid]['customersubject'], ENT_QUOTES, 'UTF-8') : '';

			$message = !empty($email_description[$languageid]['customermessage']) ? html_entity_decode($email_description[$languageid]['customermessage'], ENT_QUOTES, 'UTF-8') : '';

			$subject = !empty($subject) ? str_replace($find, $replace, $subject) : '';

			$message = !empty($message) ? str_replace($find, $replace, $message) : '';

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($post_data['email']);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
			$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
			$mail->send();
		}

		// Admin Email
		if($this->config->get('jade_account_adminemail_status')) {
			$subject = !empty($email_description[$languageid]['adminsubject']) ? html_entity_decode($email_description[$languageid]['adminsubject'], ENT_QUOTES, 'UTF-8') : '';

			$message = !empty($email_description[$languageid]['adminmessage']) ? html_entity_decode($email_description[$languageid]['adminmessage'], ENT_QUOTES, 'UTF-8') : '';

			$subject = !empty($subject) ? str_replace($find, $replace, $subject) : '';

			$message = !empty($message) ? str_replace($find, $replace, $message) : '';

			if($this->config->get('jade_account_adminemail_email')) {
				$adminemail = $this->config->get('jade_account_adminemail_email');
			} else {
				$adminemail = $this->config->get('config_email');
			}

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setReplyTo($post_data['email']);
			$mail->setTo($adminemail);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
			$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
			$mail->send();
		}
	}
}