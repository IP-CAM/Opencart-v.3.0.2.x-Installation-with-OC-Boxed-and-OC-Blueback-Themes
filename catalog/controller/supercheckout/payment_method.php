<?php

class ControllerSupercheckoutPaymentMethod extends Controller {

    public function index() {
    	//$data['sessions'] =$this->session->data;
	//print_r($this->session->data);
        //setting for supercheckout plugin from database or from default settings
        $this->load->model('setting/setting');
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/supercheckout/payment_method.tpl')) {
                $data['default_theme']=$this->config->get('config_template');
        }else{
                $data['default_theme']='default';
        }
        $result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));
        
        $this->settings = $result['supercheckout'];
        $this->load->model('checkout/order');

        $data['settings'] = $result['supercheckout'];
        
        if (empty($data['settings'])) {
            
            $settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);            
            $data['settings'] = $settings['default_supercheckout'];
            $data['supercheckout']=$settings['default_supercheckout'];
            
        }
        if(isset($data['settings']['step']['payment_method']['logo'])){
            foreach ($data['settings']['step']['payment_method']['logo'] as $key => $value) {
                if(file_exists('image/'.$value)){
                    $data['payment_logo'][$key] = true;
                }else{
                    $data['payment_logo'][$key] = false;
                }
            }
        }
        $this->language->load('supercheckout/supercheckout');
        
        $this->load->model('account/address');
        // if customer is logged in whether through store or through facebook or google
        if ($this->customer->isLogged()) {
            
            $payment_address['country_id']=$this->session->data['payment_country_id'];
            $payment_address['zone_id']=$this->session->data['payment_zone_id'];
            $payment_address['iso_code_2']=isset($this->session->data['payment_iso_code_2'])?$this->session->data['payment_iso_code_2']:"";
            $payment_address['iso_code_3']=isset($this->session->data['payment_iso_code_3'])?$this->session->data['payment_iso_code_3']:"";
            $payment_address['postcode'] = isset($this->session->data['payment']['payment_postcode'])?$this->session->data['payment']['payment_postcode']:"";
            
        } elseif (isset($this->session->data['guest'])) {
            
            $payment_address = $this->session->data['guest']['payment'];
        }   
        
        if (!empty($payment_address)) {
            
            if(version_compare(VERSION, '2.2.0.0', '<')) {
                // Totals
                $total_data = array();
                $total = 0;
                $taxes = $this->cart->getTaxes();

                if (version_compare(VERSION, '3.0.1', '<')) {
                    $this->load->model('extension/extension');
                    $results = $this->model_extension_extension->getExtensions('total');
                } else {
                    $this->load->model('setting/extension');
                    $results = $this->model_setting_extension->getExtensions('total');
                }

                $sort_order = array();
                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
                }

                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                    if ($this->config->get($result['code'] . '_status')) {
                        $this->load->model('extension/total/' . $result['code']);

                        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data, $total, $taxes);
                    }
                }

                // Payment Methods
                $method_data = array();
                if (version_compare(VERSION, '3.0.1', '<')) {
                    $this->load->model('extension/extension');
                    $results = $this->model_extension_extension->getExtensions('payment');
                } else {
                    $this->load->model('setting/extension');
                    $results = $this->model_setting_extension->getExtensions('payment');
                }
                
                foreach ($results as $result) {
                    if ($this->config->get('payment_'.$result['code'] . '_status')) {
                        $this->load->model('extension/payment/' . $result['code']);

                        $method = $this->{'model_extension_payment_' . $result['code']}->getMethod($payment_address, $total);

                        if ($method) {
                            $method_data[$result['code']] = $method;
                        }
                    }
                }

                $sort_order = array();

                foreach ($method_data as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $method_data);

                $this->session->data['payment_methods'] = $method_data;
            }
            else{
                $totals = array();
                $total = 0;
                $taxes = $this->cart->getTaxes();

                // Because __call can not keep var references so we put them into an array. 			
                $total_data = array(
                        'totals' => &$totals,
                        'taxes'  => &$taxes,
                        'total'  => &$total
                );
                if (version_compare(VERSION, '3.0', '<')) {
                    $this->load->model('extension/extension');
                    $results = $this->model_extension_extension->getExtensions('total');
                } else {
                    $this->load->model('setting/extension');
                    $results = $this->model_setting_extension->getExtensions('total');
                }
                
                $sort_order = array();
                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');

                }

                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                    if ($this->config->get('total_' . $result['code'] . '_status')) {


                        $this->load->model('extension/total/' . $result['code']);
                        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);

                    }
                }
                
                // Payment Methods
                $method_data = array();
                if (version_compare(VERSION, '3.0', '<')) {
                    $this->load->model('extension/extension');
                    $results = $this->model_extension_extension->getExtensions('payment');
                } else {
                    $this->load->model('setting/extension');
                    $results = $this->model_setting_extension->getExtensions('payment');
                }
                
                foreach ($results as $result) {
                    if ($this->config->get('payment_'.$result['code'] . '_status')) {
                        $this->load->model('extension/payment/' . $result['code']);

                        $method = $this->{'model_extension_payment_' . $result['code']}->getMethod($payment_address, $total);

                        if ($method) {
                            $method_data[$result['code']] = $method;
                        }
                    }
                }

                $sort_order = array();

                foreach ($method_data as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $method_data);

                $this->session->data['payment_methods'] = $method_data;
            }
        }
//        var_dump($this->session->data);die;
        if (!isset($_SESSION['shipping_method'])) die("");
        $shipping_method_selected = explode('.', $this->session->data['shipping_method']['code'])[0];
        $shipping_method_payments = $this->session->data['available_shipping'][$shipping_method_selected];
        $get_first_method_payment = array();
        $shipping_methods_data = $this->session->data['available_shipping'];
        foreach ($shipping_methods_data as $key => $value) {
            if($key == explode('.', $this->session->data['shipping_method']['code'])[0]){
                foreach ($value as $method) {
                    $get_first_method_payment[] = $method;
                }
            }
        }
        
//        var_dump($this->session->data['shipping_method']['code']);die;
//        if(empty($get_first_method_payment)){
//            foreach ($shipping_methods_data as $key => $value) {
//                foreach ($value as $method) {
//                    $get_first_method_payment[] = $method;
//                }
//            }
//        }
//        $this->session->data['payment_methods'] = $get_first_method_payment;
//        foreach ($this->session->data['payment_methods'] as $methods) {
//            $get_first_method_payment[] = $methods['code'];
//        }
        
        $default_payment = isset($this->settings['step']['payment_method']['default_option']) ? $this->settings['step']['payment_method']['default_option'] : array();
        if (isset($this->session->data['payment_method']['code']) && !in_array($this->session->data['payment_method']['code'], $get_first_method_payment)) {
            if (!in_array($default_payment, $get_first_method_payment)) {
                foreach ($get_first_method_payment as $key => $value) {
                    if(isset($this->session->data['payment_methods'][$value])){
                        $this->session->data['payment_method'] = @$this->session->data['payment_methods'][$get_first_method_payment[$key]];
                    }
                }
//                $this->session->data['payment_method'] = $this->session->data['payment_methods'][$get_first_method_payment[0]];
            } else {
                $this->session->data['payment_method'] = $this->session->data['payment_methods'][$default_payment];
            }
        }

        $data['text_shipping_not_available'] = $this->language->get('text_shipping_not_available');
        $data['text_payment_method'] = $this->language->get('text_payment_method');
        $data['text_comments'] = $this->language->get('text_comments');
        $data['button_continue'] = $this->language->get('button_continue');

        if (empty($this->session->data['payment_methods'])) {
            $data['error_warning'] = sprintf($this->language->get('error_no_payment'), $this->url->link('information/contact'));
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->session->data['payment_methods'])) {
            
            foreach ($this->session->data['payment_methods'] as $key => $value) {
                if(in_array($key, $get_first_method_payment)){
                    $data['payment_methods'][$key] = $value;
                }
            }
        } else {
            $data['payment_methods'] = array();
        }
//        var_dump($this->session->data['payment_methods']);die;
        $data['language_id'] = $this->config->get('config_language_id');
        if (isset($this->session->data['payment_method']['code'])) {
            $data['code'] = $this->session->data['payment_method']['code'];
        } else {
            $data['code'] = $this->settings['step']['payment_method']['default_option'];
        }

        if (isset($this->session->data['comment'])) {
            $data['comment'] = $this->session->data['comment'];
        } else {
            $data['comment'] = '';
        }

        if ($this->config->get('config_checkout_id')) {
            $this->load->model('catalog/information');

            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));

            if ($information_info) {
                $data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/info', 'information_id=' . $this->config->get('config_checkout_id'), 'SSL'), $information_info['title'], $information_info['title']);
            } else {
                $data['text_agree'] = '';
            }
        } else {
            $data['text_agree'] = '';
        }

        if (isset($this->session->data['agree'])) {
            $data['agree'] = $this->session->data['agree'];
        } else {
            $data['agree'] = '';
        }

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/supercheckout/payment_method.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/supercheckout/payment_method.tpl';
        } else {
            $this->template = 'default/template/supercheckout/payment_method.tpl';
        }

//        $data['show_payment_details'] = $this->load->controller('payment/' . $this->session->data['payment_method']['code']);
        $data['sessions'] =$this->session->data;
	
        if(version_compare(VERSION, '2.2.0.0', '<')) {
            $this->response->setOutput($this->load->view('default/template/supercheckout/payment_method.tpl', $data));
        }
        else{
            $this->response->setOutput($this->load->view('supercheckout/payment_method', $data));
        }
    }

    public function validate() {
        //print_r($this->session->data);
        //loading settings for supecheckout plugin from database or from default settings
        $this->load->model('setting/setting');
        
        $result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));
        
        $this->settings = $result['supercheckout'];
        
        $data['settings'] = $result['supercheckout'];
        
        if (empty($data['settings'])) {
            
            $this->config->load('supercheckout_settings');
            $settings = $this->config->get('supercheckout_settings');
            $data['settings'] = $settings;
            
        }

        $this->language->load('supercheckout/supercheckout');

        $json = array();

        // Validate if payment address has been set.
        $this->load->model('account/address');
        
        // if customer is logged in whether through store or through facebook or google        
        if ($this->customer->isLogged()) {
            
            $payment_address['country_id']=$this->session->data['payment_country_id'];
            $payment_address['zone_id']=$this->session->data['payment_zone_id'];
            $payment_address['iso_code_2']=isset($this->session->data['payment_iso_code_2'])?$this->session->data['payment_iso_code_2']:"";
            $payment_address['iso_code_3']=isset($this->session->data['payment_iso_code_3'])?$this->session->data['payment_iso_code_3']:"";
            $payment_address['postcode'] = isset($this->session->data['payment']['payment_postcode'])?$this->session->data['payment']['payment_postcode']:"";
            
        } elseif (isset($this->session->data['guest'])) {
            
            $payment_address = $this->session->data['guest']['payment'];
            
        } 

        if (empty($payment_address)) {
            
            $json['redirect'] = $this->url->link('supercheckout/supercheckout', '', 'SSL');
            
        }

        // Validate cart has products and has stock.			
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            
            $json['redirect'] = $this->url->link('supercheckout/cart');
            
        }

        // Validate minimum quantity requirments.			
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $json['redirect'] = $this->url->link('supercheckout/cart');

                break;
            }
        }
        //if no error is found
        if (!$json) {
            if (!isset($this->request->post['payment_method'])) {
                
                $json['error']['warning'] = $this->language->get('error_payment');
                
            } else {                
                if (!isset($this->session->data['payment_methods'][$this->request->post['payment_method']])) {
                    
                    $json['error']['warning'] = $this->language->get('error_payment');
                    
                }
            }
            if (!$json) {
                
                $this->session->data['payment_method'] = $this->session->data['payment_methods'][$this->request->post['payment_method']];
                
            }
        }
        //print_r($this->session->data);
        $this->response->setOutput(json_encode($json));
    }

}

?>