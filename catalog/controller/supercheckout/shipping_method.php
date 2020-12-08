<?php
class ControllerSupercheckoutShippingMethod extends Controller {

    public function index() {
    	
        $this->language->load('supercheckout/supercheckout');

        $this->load->model('account/address');
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/supercheckout/shipping_method.tpl')) {
                $data['default_theme']=$this->config->get('config_template');
        }else{
                $data['default_theme']='default';
        }

        //if customer is logged in whether through store or through facebook or google
        if ($this->customer->isLogged()) {
            $this->load->model('localisation/country');
            $shipping_address['country_id'] = $this->session->data['shipping_country_id'];
            $shipping_address['zone_id'] = $this->session->data['shipping_zone_id'];
	    $query = $this->db->query("SELECT code FROM " . DB_PREFIX . "zone WHERE zone_id = '".$this->session->data['shipping_zone_id']."'");
            if(isset($query->row['code'])){
                $zone_code= $query->row['code'];
            }
//	    $shipping_address['zone_code'] =isset($zone_code)?$zone_code:"";
        } elseif (isset($this->session->data['guest'])) {
		$this->session->data['guest']['shipping']['zone_id']=$this->session->data['shipping_zone_id'];
		$this->session->data['guest']['shipping']['country_id']=$this->session->data['shipping_country_id'];
		$shipping_address = $this->session->data['guest']['shipping'];
        }        
        //loading settings for supercheckout plugin from database or from default settigs
        $this->load->model('setting/setting');

        $result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));

        $this->settings = $result['supercheckout'];

        $data['settings'] = $result['supercheckout'];

        if (empty($data['settings'])) {

            $this->config->load('supercheckout_settings');
            $settings = $this->config->get('supercheckout_settings');
            $data['settings'] = $settings;

        }
        if (isset($data['settings']['step']['shipping_method']['logo']['xshipping.xshipping'])) {
            for ($i = 1; $i <= 12; $i++) {
                $data['settings']['step']['shipping_method']['logo']['xshipping.xshipping' . $i] = $data['settings']['step']['shipping_method']['logo']['xshipping.xshipping'];
            }
        }
        if(isset($data['settings']['step']['shipping_method']['logo'])){
            foreach ($data['settings']['step']['shipping_method']['logo'] as $key => $value) {
                if(file_exists('image/'.$value)){
                    $data['shipping_logo'][$key] = true;
                }else{
                    $data['shipping_logo'][$key] = false;
                }
            }
        }

        $data['error_no_shipping_product'] = $this->language->get('error_no_shipping_product');
        if (!empty($shipping_address)) {
            if(!isset($shipping_address['city']) && isset($this->session->data['shipping_address']['city'])){
                $shipping_address['city'] = $this->session->data['shipping_address']['city'];
            }else if(!isset($shipping_address['city']) && !isset($this->session->data['shipping_address']['city'])){
                $shipping_address['city'] = '';
            }
            if(!isset($shipping_address['zone_code']) && isset($this->session->data['shipping_address']['zone_code'])){
                $shipping_address['zone_code'] = $this->session->data['shipping_address']['zone_code'];
            }else if(!isset($shipping_address['zone_code']) && !isset($this->session->data['shipping_address']['zone_code'])){
                $shipping_address['zone_code'] = '';
            }
            if(!isset($shipping_address['postcode']) && isset($this->session->data['shipping_address']['postcode'])){
                $shipping_address['postcode'] = $this->session->data['shipping_address']['postcode'];
            }else if(!isset($shipping_address['postcode']) && !isset($this->session->data['shipping_address']['postcode'])){
                $shipping_address['postcode'] = isset($this->session->data['shipping']['shipping_postcode'])?$this->session->data['shipping']['shipping_postcode']:"";
            }
            if(!isset($shipping_address['iso_code_2']) && isset($this->session->data['shipping_address']['iso_code_2'])){
                $shipping_address['iso_code_2'] = $this->session->data['shipping_address']['iso_code_2'];
            }else if(!isset($shipping_address['iso_code_2']) && !isset($this->session->data['shipping_address']['iso_code_2'])){
                $shipping_address['iso_code_2'] = isset($this->session->data['shipping_iso_code_2'])?$this->session->data['shipping_iso_code_2']:"";
            }
            if(!isset($shipping_address['iso_code_3']) && isset($this->session->data['shipping_address']['iso_code_3'])){
                $shipping_address['iso_code_3'] = $this->session->data['shipping_address']['iso_code_3'];
            }else if(!isset($shipping_address['iso_code_3']) && !isset($this->session->data['shipping_address']['iso_code_3'])){
                $shipping_address['iso_code_3'] = isset($this->session->data['shipping_iso_code_3'])?$this->session->data['shipping_iso_code_3']:"";
            }
            if(!isset($shipping_address['firstname']) && isset($this->session->data['shipping_address']['firstname'])){
                $shipping_address['firstname'] = $this->session->data['shipping_address']['firstname'];
            }else if(!isset($shipping_address['firstname']) && !isset($this->session->data['shipping_address']['firstname'])){
                $shipping_address['firstname'] = '';
            }
            if(!isset($shipping_address['lastname']) && isset($this->session->data['shipping_address']['lastname'])){
                $shipping_address['lastname'] = $this->session->data['shipping_address']['lastname'];
            }else if(!isset($shipping_address['lastname']) && !isset($this->session->data['shipping_address']['lastname'])){
                $shipping_address['lastname'] = '';
            }
            if(!isset($shipping_address['company']) && isset($this->session->data['shipping_address']['company'])){
                $shipping_address['company'] = $this->session->data['shipping_address']['company'];
            }else if(!isset($shipping_address['company']) && !isset($this->session->data['shipping_address']['company'])){
                $shipping_address['company'] = '';
            }
            if(!isset($shipping_address['address_1']) && isset($this->session->data['shipping_address']['address_1'])){
                $shipping_address['address_1'] = $this->session->data['shipping_address']['address_1'];
            }else if(!isset($shipping_address['address_1']) && !isset($this->session->data['shipping_address']['address_1'])){
                $shipping_address['address_1'] = '';
            }
            // Shipping Methods
            $quote_data = array();
            if (version_compare(VERSION, '3.0.1', '<')) {
                $this->load->model('extension/extension');
                $results = $this->model_extension_extension->getExtensions('shipping');
            } else {
                $this->load->model('setting/extension');
                $results = $this->model_setting_extension->getExtensions('shipping');
            }

            foreach ($results as $result) {
                if ($this->config->get('shipping_'.$result['code'] . '_status')) {
                    $this->load->model('extension/shipping/' . $result['code']);

                    $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($shipping_address);

                    if ($quote) {
                        $quote_data[$result['code']] = array(
                                'title' => $quote['title'],
                                'quote' => $quote['quote'],
                                'sort_order' => $quote['sort_order'],
                                'error' => $quote['error'],
                                'code' => $quote['code']
                        );
                    }
                }
            }
            if(isset($quote_data['sameday']['quote']['Transport prin Sameday Curier']['code'])){
                $quote_data['sameday']['quote']['Transport prin Sameday Curier']['code'] = 'sameday.sameday';
            }
            if(isset($quote_data['fancourier']['quote']['Standard']['code'])){
                $quote_data['fancourier']['quote']['Standard']['code'] = 'fancourier.fancourier';
                $quote_data['fancourier']['quote']['Standard']['title'] = 'Standard';
            }
            
            $sort_order = array();

            foreach ($quote_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $quote_data);

            $this->session->data['shipping_methods'] = $quote_data;
        }
//        $shipping_methods_data = $this->session->data['available_shipping'];
        $all_shipping = $this->session->data['shipping_methods'];
        $this->session->data['shipping_methods'] = array();
        $all_shipping_keys = array_keys($all_shipping);
        $customer_address = array();
        if ($this->customer->isLogged() && isset($_POST['address_id'])) {
            $customer_address = $this->model_account_address->getAddress($this->request->post['address_id']);
        }
        $city_id = 0;
        if (!$this->customer->isLogged() || ($this->customer->isLogged() && isset($_POST['payment_address']) && $this->request->post['payment_address'] == "new") && isset($_POST['zone_id']) && isset($_POST['city'])) {
            $this->load->model('localisation/city');
            $city_id = $this->model_localisation_city->getCityId($this->request->post['zone_id'], $this->request->post['city']);
        }
        foreach ($this->session->data['available_shipping'] as $key => $value) {
            if ($this->customer->isLogged() && isset($_POST['payment_address']) && $this->request->post['payment_address'] == "existing" && (!isset($_POST['address_id']) || !$this->confirm_shipping_method($key, $customer_address['country_id'], $customer_address['zone_id'], $customer_address['city_id']))) {
                continue;
            }
            if ((!$this->customer->isLogged() || ($this->customer->isLogged() && isset($_POST['payment_address']) && $this->request->post['payment_address'] == "new")) && (!isset($_POST['country_id']) || !isset($_POST['zone_id']) || !isset($_POST['city']) || !$this->confirm_shipping_method($key, $this->request->post['country_id'], $this->request->post['zone_id'], $city_id))) {
                continue;
            }
            if(in_array($key, $all_shipping_keys)){
                $this->session->data['shipping_methods'][$key] = $all_shipping[$key];
            }
        }
        $data['language_id'] = $this->config->get('config_language_id');
        $data['text_shipping_method'] = $this->language->get('text_shipping_method');
        $data['text_comments'] = $this->language->get('text_comments');
        $data['button_continue'] = $this->language->get('button_continue');
        $data['shipping_required'] = $this->cart->hasShipping();
        if (empty($this->session->data['shipping_methods'])) {

            $data['error_warning'] = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact'));
        } else {

            $data['error_warning'] = '';
        }

        if (isset($this->session->data['shipping_methods'])) {

            $data['shipping_methods'] = $this->session->data['shipping_methods'];
        } else {

            $data['shipping_methods'] = array();
        }
        
        $shipping_sort_order = array();

        foreach ($data['shipping_methods'] as $key => $value) {
            $shipping_sort_order[$key] = $value['sort_order'];
        }

        array_multisort($shipping_sort_order, SORT_ASC, $data['shipping_methods']);
        

        //for getting first method set to default IF and only IF default is not set at the admin
       
        $get_first_method_shipping = array();
        foreach ($this->session->data['shipping_methods'] as $methods => $key) {
            $get_first_method_shipping[] = $methods;
        }

        $default_shipping = isset($this->settings['step']['shipping_method']['default_option'])?$this->settings['step']['shipping_method']['default_option']:array();
   
        $current_shipping_method = array();

        if(isset($this->session->data['shipping_method'])){
            $current_shipping_method = explode('.',$this->session->data['shipping_method']['code']); 
        }
        if(isset($this->session->data['shipping_method'])){
            // if(!in_array($current_shipping_method[1],$get_first_method_shipping)){
            if(isset($current_shipping_method[1]) && !in_array($current_shipping_method[1], $get_first_method_shipping)){ 
                if(!empty ($get_first_method_shipping)) {
                    if (!in_array($default_shipping, $get_first_method_shipping)) { 
                        if(isset($this->session->data['shipping_methods'][$get_first_method_shipping[0]]['quote'][$get_first_method_shipping[0]])) {
                            $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$get_first_method_shipping[0]]['quote'][$get_first_method_shipping[0]];
                        }
                    } else {
                        foreach($this->session->data['shipping_methods'][$default_shipping]['quote'] as $shipping_methods_key => $shipping_methods_val) {
                            $this->session->data['shipping_method'] = $shipping_methods_val;
                            break;
                        }
                    }
                }else { 
                    unset($this->session->data['shipping_method']);
                }
            }

        } else {
            if(!empty ($get_first_method_shipping)) { 
                if (!in_array($default_shipping, $get_first_method_shipping)) {
                    if(isset($this->session->data['shipping_methods'][$get_first_method_shipping[0]]['quote'][$get_first_method_shipping[0]])) {
                            $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$get_first_method_shipping[0]]['quote'][$get_first_method_shipping[0]];
                    }
                } else {
                    foreach($this->session->data['shipping_methods'][$default_shipping]['quote'] as $shipping_methods_key => $shipping_methods_val) {
                        $this->session->data['shipping_method'] = $shipping_methods_val;
                        break;
                    }
                }
            }else { 
                unset($this->session->data['shipping_method']);
            }
        }
    
        if (isset($this->session->data['shipping_method']['code'])) {
            $data['codeShipping'] = $this->session->data['shipping_method']['code'];

        } else {
            $data['codeShipping'] = $this->settings['step']['shipping_method']['default_option'].'.'.$this->settings['step']['shipping_method']['default_option'];
        }
        if (in_array("sameday", array_keys($this->session->data['shipping_methods']))) {
            $data['codeShipping'] = "sameday.sameday";
        }

        if (isset($this->session->data['comment'])) {

            $data['comment'] = $this->session->data['comment'];
        } else {

            $data['comment'] = '';
        }

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/supercheckout/shipping_method.tpl')) {

            $this->template = $this->config->get('config_template') . '/template/supercheckout/shipping_method.tpl';
        } else {

            $this->template = 'default/template/supercheckout/shipping_method.tpl';
        }
	$data['sessions'] = $this->session->data;
        if(version_compare(VERSION, '2.2.0.0', '<')) {
            $this->response->setOutput($this->load->view('default/template/supercheckout/shipping_method.tpl', $data));
        }
        else{
            $this->response->setOutput($this->load->view('supercheckout/shipping_method', $data));
        }
       
    }

    public function validate() {
        
        $this->language->load('supercheckout/supercheckout');

        $json = array();

        // Validate if shipping address has been set.
        $this->load->model('account/address');

        //if customer is logged in whether through store or through facebook or google
        if ($this->customer->isLogged()) {

            $shipping_address['country_id'] = $this->session->data['shipping_country_id'];
            $shipping_address['zone_id'] = $this->session->data['shipping_zone_id'];
            $shipping_address['postcode'] = isset($this->session->data['shipping']['shipping_postcode'])?$this->session->data['shipping']['shipping_postcode']:"";
            $shipping_address['iso_code_2']=isset($this->session->data['shipping_iso_code_2'])?$this->session->data['shipping_iso_code_2']:"";
            $shipping_address['iso_code_3'] = isset($this->session->data['shipping_iso_code_3'])?$this->session->data['shipping_iso_code_3']:"";
            $shipping_address['zone_code'] = '';
            $shipping_address['city'] = '';

        } elseif (isset($this->session->data['guest'])) {
            if (isset($this->session->data['use_for_shipping'])&& isset($this->session->data['guest']['payment'])) {              
                $this->session->data['guest']['shipping'] = $this->session->data['guest']['payment'];
            }
            $shipping_address = $this->session->data['guest']['shipping'];
        }

        if (empty($shipping_address)) {
//			$json['redirect'] = $this->url->link('supercheckout/supercheckout', '', 'SSL');
        }

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
//			$json['redirect'] = $this->url->link('supercheckout/cart');				
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
//        print_r($this->request->post); die;
        
        if($this->cart->hasShipping()){
                if (!$json) {
                    if (!isset($this->request->post['shipping_method'])) {
                    

                        $json['error']['warning'] = $this->language->get('error_shipping');

                    } else {
//                        print_r($this->session->data['shipping_methods']); die;
                        $shipping = explode('.', $this->request->post['shipping_method']);
                        if($shipping[1] == 'sameday'){
                            $shipping[1] = 'Transport prin Sameday Curier';
                        }
                        if($shipping[1] == 'fancourier'){
                            $shipping[1] = 'Standard';
                        }
                        if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {

                            $json['error']['warning'] = $this->language->get('error_shipping');

                        }
                    }
                    
                    if (!$json) {
                        $shipping = explode('.', $this->request->post['shipping_method']);
                        if($shipping[1] == 'sameday'){
                            $shipping[1] = 'Transport prin Sameday Curier';
                        } 
                        if($shipping[1] == 'fancourier'){
                            $shipping[1] = 'Standard';
                        }
                        
                        $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                    }
                }
        }
        $this->response->setOutput(json_encode($json));
    }

    function confirm_shipping_method($payment_method, $country_id, $zone_id, $city_id) {
        if (!isset($country_id) || !isset($zone_id) || !isset($city_id)) {
            return false;
        }
        if ($country_id == "" || $country_id == "null") $country_id = 0;
        if ($zone_id == "" || $zone_id == "null") $zone_id = 0;
        if ($city_id == "" || $city_id == "null") $city_id = 0;

        $this->load->model('setting/setting');
        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        } else {
            $store_id = 0;
        }
        $shipping_settings = $this->model_setting_setting->getSetting('shipping_' . $payment_method, $store_id);
        
        if (!isset($shipping_settings['shipping_' . $payment_method . '_status']) || $shipping_settings['shipping_' . $payment_method . '_status'] != 1) {
            return false;
        }
        
        $this->load->model('localisation/geo_zone');

        if ($payment_method == "xshipping") {
            if (!isset($shipping_settings['shipping_xshipping_methods'])) return false;
            $xshipping_settings = $shipping_settings['shipping_xshipping_methods'];
            $index = 0;
            for (;;) {
                $index++;
                if (!isset($xshipping_settings['geo_zone_id' . $index])) break;
                if ($xshipping_settings['status' . $index] != 1) continue;
                
                $geo_zone_id = $xshipping_settings['geo_zone_id' . $index];
                if ($geo_zone_id == 0) return true;

                if ($this->model_localisation_geo_zone->getTotalZoneToGeoZoneByDetail($geo_zone_id, $country_id, 0, 0) > 0) {
                    return true;
                }
                if ($this->model_localisation_geo_zone->getTotalZoneToGeoZoneByDetail($geo_zone_id, $country_id, $zone_id, 0) > 0) {
                    return true;
                }
                if ($this->model_localisation_geo_zone->getTotalZoneToGeoZoneByDetail($geo_zone_id, $country_id, $zone_id, $city_id) > 0) {
                    return true;
                }
            }
            return false;
        }

        if (!isset($shipping_settings['shipping_' . $payment_method . '_geo_zone_id'])) {
            return true;
        }
        
        $geo_zone_id = $shipping_settings['shipping_' . $payment_method . '_geo_zone_id'];
        if ($geo_zone_id == 0) {
            return true;
        }

        if ($this->model_localisation_geo_zone->getTotalZoneToGeoZoneByDetail($geo_zone_id, $country_id, 0, 0) > 0) {
            return true;
        }
        if ($this->model_localisation_geo_zone->getTotalZoneToGeoZoneByDetail($geo_zone_id, $country_id, $zone_id, 0) > 0) {
            return true;
        }
        if ($this->model_localisation_geo_zone->getTotalZoneToGeoZoneByDetail($geo_zone_id, $country_id, $zone_id, $city_id) > 0) {
            return true;
        }

        return false;
    }
}

?>