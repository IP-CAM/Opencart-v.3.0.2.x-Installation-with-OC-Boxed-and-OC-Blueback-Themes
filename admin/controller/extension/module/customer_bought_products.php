<?php
class ControllerExtensionModuleCustomerBoughtProducts extends Controller {
	public function index() {
	  	$url = htmlspecialchars($_SERVER['HTTP_REFERER']);

		echo nl2br('There is nothing to configure for this module, it is just used to install and uninstall events.<br /><br />Please use your back button or <a href=' . $url . '>Go Back</a>'); 
	}
	
	public function install() {
        $this->load->model('setting/event');
			
        $this->model_setting_event->addEvent('vger_customer_bought_products', 'admin/view/customer/customer_form/after', 'extension/module/customer_bought_products/boughtProducts');
    }
		
	public function uninstall() {
		$this->load->model('setting/event');

        $this->model_setting_event->deleteEventByCode('vger_customer_bought_products');
	}
		
	
/*
Name: 		customer_bought_products
Trigger:	admin/view/customer/customer_form/after
Action:		extension/module/customer_bought_products/addBoughtProducts
Order:		0
Purpose:	Show the products a customer has bought in the customer edit form.
*/
	public function boughtProducts(&$route, &$data, &$output) {

		$this->load->language('customer/customer');
		$this->load->language('extension/module/customer_bought_products');
		
		if (isset($this->request->get['customer_id'])) {
			$customer_id = $this->request->get['customer_id'];
		} else {
			$customer_id = 0;
		}
		
		$find = $this->language->get('tab_reward') . '</a></li>';
		$replace = $this->language->get('tab_reward') . '</a></li><li><a href="#tab-product" data-toggle="tab">' . $this->language->get('tab_product') . '</a></li>';
		$output = str_replace($find, $replace, $output);
	
		$find = '<div class="tab-pane" id="tab-reward">';
	
		$replace = '<div class="tab-pane" id="tab-product">
              <fieldset>
                <legend>'. $this->language->get('text_products_bought') .'</legend>
                <div id="products"></div>
              </fieldset>
            </div>
			<div class="tab-pane" id="tab-reward">';
		
		$output = str_replace($find, $replace, $output);
	
		$find = '$(\'#ip\').delegate(\'.pagination a\', \'click\', function(e) {';
	
		$replace = '$(\'#products\').delegate(\'.pagination a\', \'click\', function(e) { e.preventDefault(); $(\'#products\').load(this.href); });
					$(\'#products\').load(\'index.php?route=extension/module/customer_bought_products/products&user_token='.$this->session->data['user_token'].'&customer_id='.$customer_id.'\');

					$(\'#ip\').delegate(\'.pagination a\', \'click\', function(e) {';
		
		$output = str_replace($find, $replace, $output);
	
	}
	
	public function products() {
		$this->load->language('extension/module/customer_bought_products');

		$this->load->model('extension/customer_bought_products/customer_bought_products');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['products'] = array();

		if (isset($this->request->get['customer_id'])) {
			$customer_id = $this->request->get['customer_id'];
		} else {
			$customer_id = 0;
		}
		
		$products = $this->model_extension_customer_bought_products_customer_bought_products->getProductPurchasesByCustomerId($customer_id);

		$this->load->model('tool/image');
		
		if ($products) {
			foreach ($products as $product) {
				if ($product['image'] && file_exists(DIR_IMAGE . $product['image'])) {
					$image = $this->model_tool_image->resize($product['image'], 40, 40);
				} else {
					$image = $this->model_tool_image->resize('no_image.jpg', 40, 40);
				}
		
				$product_data[] = array(
					'image'    => $image,
					'name'     => $product['name'],
					'quantity' => $this->model_extension_customer_bought_products_customer_bought_products->getTotalProductPurchasesByCustomerId($product['product_id'], $customer_id)
				);
			}
			
			$product_total = count($product_data);
			
			usort($product_data, function($a, $b) {
				return $b['quantity'] - $a['quantity'];
			});
			
			$data['products'] = array_slice($product_data, ($page - 1) * 10, 10);
		} else {
			$product_total = '0';
		}
		
		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('extension/module/customer_bought_products/products', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $this->request->get['customer_id'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($product_total - 10)) ? $product_total : ((($page - 1) * 10) + 10), $product_total, ceil($product_total / 10));

		$this->response->setOutput($this->load->view('extension/customer_bought_products/customer_bought_products', $data));
	}
}
?>