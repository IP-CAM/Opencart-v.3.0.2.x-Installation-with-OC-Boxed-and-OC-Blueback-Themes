<?php
class ControllerExtensionModuleClearCart extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/clear_cart');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_clear_cart', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
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
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/clear_cart', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/clear_cart', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_clear_cart_status'])) {
			$data['module_clear_cart_status'] = $this->request->post['module_clear_cart_status'];
		} else {
			$data['module_clear_cart_status'] = $this->config->get('module_clear_cart_status');
		}

        if (isset($this->request->post['module_clear_cart_quick_status'])) {
            $data['module_clear_cart_quick_status'] = $this->request->post['module_clear_cart_quick_status'];
        } else {
            $data['module_clear_cart_quick_status'] = $this->config->get('module_clear_cart_quick_status');
        }

        if (isset($this->request->post['module_clear_cart_shopping_status'])) {
            $data['module_clear_cart_shopping_status'] = $this->request->post['module_clear_cart_shopping_status'];
        } else {
            $data['module_clear_cart_shopping_status'] = $this->config->get('module_clear_cart_shopping_status');
        }

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/clear_cart', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/clear_cart')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

    public function install() {


        $enable=1;

        $this->load->model('setting/setting');

        $this->model_setting_setting->editSetting('module_clear_cart',array('module_clear_cart_status'=>$enable, 'module_clear_cart_quick_status'=>$enable, 'module_clear_cart_shopping_status'=>$enable));

    }
}