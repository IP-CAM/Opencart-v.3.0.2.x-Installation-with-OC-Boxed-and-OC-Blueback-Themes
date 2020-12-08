<?php
class ControllerExtensionPaymentEuPlatesc extends Controller {
	public function index() {
		
		return $this->load->view('extension/payment/euplatesc');
	}

	public function confirm() {
		$json = array();
		
		if ($this->session->data['payment_method']['code'] == 'euplatesc') {
			$this->load->model('checkout/order');

			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			$mid=$this->config->get('payment_euplatesc_mid');
			$key=$this->config->get('payment_euplatesc_key');

			$allProducts = "";
			$products = $this->cart->getProducts();

			foreach($products as $product) {
				$name = $product['name'];
				$quantity = $product['quantity'];
				$allProducts .= $name . " x" . $quantity . "<br/>";
			}
			
			
			$allProducts=trim($allProducts);
			if(strlen($allProducts)>200){
				$allProducts=substr($allProducts,0,198)."..";
			}

			$dataAll = array(
						'amount'      => $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
						'curr'        => $order_info['currency_code'],
						'invoice_id'  => $this->session->data['order_id'],
						'order_desc'  => $allProducts,

						'merch_id'    => $mid,
						'timestamp'   => gmdate("YmdHis"),
						'nonce'       => md5(microtime() . mt_rand()),
			);
			  
			$dataAll['fp_hash'] = strtoupper($this->euplatesc_mac($dataAll,$key));

			$dataAll['email'] = $order_info['email'];
			$dataAll['phone'] = $order_info['telephone'];

			if ($this->cart->hasShipping()) {
				$dataAll['add'] = $order_info['shipping_address_1'];
				$dataAll['city'] = $order_info['shipping_city'];
			} else {
				$dataAll['add'] = $order_info['payment_address_1'];
				$dataAll['city'] = $order_info['payment_city'];		
			}
			$dataAll['ExtraData[successurl]'] = $this->url->link('checkout/success');
		
			$json['redirect'] = 'https://secure.euplatesc.ro/tdsprocess/tranzactd.php?' . http_build_query($dataAll);
			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_euplatesc_order_status'));
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}
	
	public function ipn() {
		$this->load->model('checkout/order');
		
		$zcrsp =  array (
			'amount'     => addslashes(trim(@$_POST['amount'])),
			'curr'       => addslashes(trim(@$_POST['curr'])),
			'invoice_id' => addslashes(trim(@$_POST['invoice_id'])),
			'ep_id'      => addslashes(trim(@$_POST['ep_id'])),
			'merch_id'   => addslashes(trim(@$_POST['merch_id'])),
			'action'     => addslashes(trim(@$_POST['action'])),
			'message'    => addslashes(trim(@$_POST['message'])),
			'approval'   => addslashes(trim(@$_POST['approval'])),
			'timestamp'  => addslashes(trim(@$_POST['timestamp'])),
			'nonce'      => addslashes(trim(@$_POST['nonce'])),
		);
		
		$zcrsp['fp_hash'] = strtoupper($this->euplatesc_mac($zcrsp, $this->config->get('payment_euplatesc_key')));
		$fp_hash=addslashes(trim(@$_POST['fp_hash']));
		if($zcrsp['fp_hash']===$fp_hash){
			if($zcrsp['action']=="0") {	
				$this->model_checkout_order->addOrderHistory($zcrsp['invoice_id'], $this->config->get('payment_euplatesc_order_status_s'));
			}else{
				$this->model_checkout_order->addOrderHistory($zcrsp['invoice_id'], $this->config->get('payment_euplatesc_order_status_f'));
			}
		}else{
			echo "Invalid FP_HASH";
		}
	}
	
	public function euplatesc_mac($data, $key){
		$str = NULL;
		foreach($data as $d){
			if($d === NULL || strlen($d) == 0)
				$str .= '-';
			else
				$str .= strlen($d) . $d;
		}
		$key = pack('H*', $key);                                            
		return $this->hmacsha1($key, $str);
	}
	
	public function hmacsha1($key,$data) {
		$blocksize = 64;
		$hashfunc  = 'md5';
		if(strlen($key) > $blocksize)
			$key = pack('H*', $hashfunc($key));
		   
		$key  = str_pad($key, $blocksize, chr(0x00));
		$ipad = str_repeat(chr(0x36), $blocksize);
		$opad = str_repeat(chr(0x5c), $blocksize);
		   
		$hmac = pack('H*', $hashfunc(($key ^ $opad) . pack('H*', $hashfunc(($key ^ $ipad) . $data))));
		return bin2hex($hmac);
	}
	
}
