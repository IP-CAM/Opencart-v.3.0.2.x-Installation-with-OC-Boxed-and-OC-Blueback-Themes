<?php
class ControllerExtensionPaymentEuPlatesc extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/euplatesc');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_euplatesc', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/euplatesc', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/euplatesc', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_euplatesc_title'])) {
			$data['payment_euplatesc_title'] = $this->request->post['payment_euplatesc_title'];
		} else {
			$data['payment_euplatesc_title'] = $this->config->get('payment_euplatesc_title');
		}

		
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_euplatesc_status'])) {
			$data['payment_euplatesc_status'] = $this->request->post['payment_euplatesc_status'];
		} else {
			$data['payment_euplatesc_status'] = $this->config->get('payment_euplatesc_status');
		}
		
		if (isset($this->request->post['payment_euplatesc_order_status'])) {
			$data['payment_euplatesc_order_status'] = $this->request->post['payment_euplatesc_order_status'];
		} else {
			$data['payment_euplatesc_order_status'] = $this->config->get('payment_euplatesc_order_status');
		}

		if (isset($this->request->post['payment_euplatesc_order_status_f'])) {
			$data['payment_euplatesc_order_status_f'] = $this->request->post['payment_euplatesc_order_status_f'];
		} else {
			$data['payment_euplatesc_order_status_f'] = $this->config->get('payment_euplatesc_order_status_f');
		}
		
		if (isset($this->request->post['payment_euplatesc_order_status_s'])) {
			$data['payment_euplatesc_order_status_s'] = $this->request->post['payment_euplatesc_order_status_s'];
		} else {
			$data['payment_euplatesc_order_status_s'] = $this->config->get('payment_euplatesc_order_status_s');
		}
		
		if (isset($this->request->post['payment_euplatesc_sort_order'])) {
			$data['payment_euplatesc_sort_order'] = $this->request->post['payment_euplatesc_sort_order'];
		} else {
			$data['payment_euplatesc_sort_order'] = $this->config->get('payment_euplatesc_sort_order');
		}
		
		if (isset($this->request->post['payment_euplatesc_mid'])) {
			$data['payment_euplatesc_mid'] = $this->request->post['payment_euplatesc_mid'];
		} else {
			$data['payment_euplatesc_mid'] = $this->config->get('payment_euplatesc_mid');
		}
		
		if (isset($this->request->post['payment_euplatesc_key'])) {
			$data['payment_euplatesc_key'] = $this->request->post['payment_euplatesc_key'];
		} else {
			$data['payment_euplatesc_key'] = $this->config->get('payment_euplatesc_key');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/euplatesc', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/euplatesc')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
}