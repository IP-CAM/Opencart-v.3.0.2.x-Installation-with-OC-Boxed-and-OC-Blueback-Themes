<?php

class ModelSupercheckoutOrder extends Model {

    public function editCustomerId($order_id, $data) {

        $this->db->query("UPDATE `" . DB_PREFIX . "order` set customer_id = '" . (int) $data['customer_id'] . "', customer_group_id = '" . (int) $data['customer_group_id'] . "' , custom_field = '" . $this->db->escape(isset($data['custom_feilds']['account']) ? json_encode($data['custom_feilds']['account']) : '') . "' , date_modified = NOW() WHERE order_id = '" . (int) $order_id . "'");
    }

    public function editOrder($order_id, $data) {//print_r($data);
        $this->load->model('localisation/country');

        $this->load->model('localisation/zone');
        $country_info = $this->model_localisation_country->getCountry($data['shipping_country_id']);

        if ($country_info) {
            $shipping_country = $country_info['name'];
            $shipping_address_format = $country_info['address_format'];
        } else {
            $shipping_country = '';
            $shipping_address_format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
        }

        $zone_info = $this->model_localisation_zone->getZone($data['shipping_zone_id']);

        if ($zone_info) {
            $shipping_zone = $zone_info['name'];
        } else {
            $shipping_zone = '';
        }

        $country_info = $this->model_localisation_country->getCountry($data['payment_country_id']);

        if ($country_info) {
            $payment_country = $country_info['name'];
            $payment_address_format = $country_info['address_format'];
        } else {
            $payment_country = '';
            $payment_address_format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
        }

        $zone_info = $this->model_localisation_zone->getZone($data['payment_zone_id']);

        if ($zone_info) {
            $payment_zone = $zone_info['name'];
        } else {
            $payment_zone = '';
        }

        if (version_compare(VERSION, '2.2.0.0', '<')) {
            $data_total = (float) $data['total']['value'];
        } else {
            $data_total = (float) $data['total'];
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET customer_id = '" . (int) $data['customer_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape($data['fax']) . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($payment_country) . "', payment_country_id = '" . (int) $data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($payment_zone) . "', payment_zone_id = '" . (int) $data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($payment_address_format) .  "', payment_custom_field = '" . $this->db->escape(json_encode($data['payment_custom_field'])) .  "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "',  shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($shipping_country) . "', shipping_country_id = '" . (int) $data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($shipping_zone) . "', shipping_zone_id = '" . (int) $data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($shipping_address_format) . "', shipping_custom_field = '" . $this->db->escape(json_encode($data['shipping_custom_field'])) .  "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . $data_total . "', affiliate_id  = '" . (int) $data['affiliate_id'] . "',currency_id  = '" . (int) $data['currency_id'] . "',language_id  = '" . (int) $data['language_id'] . "',currency_code = '" . $this->db->escape($data['currency_code']) . "',currency_value = '" . (float) $data['currency_value'] . "', date_modified = NOW() WHERE order_id = '" . (int) $order_id . "'");
//        $this->db->query("UPDATE `" . DB_PREFIX . "order_history` SET comment = '" . $this->db->escape($data['comment']) . "' WHERE order_id = '" . (int) $order_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int) $order_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int) $order_id . "'");
        //$this->db->query("DELETE FROM " . DB_PREFIX . "order_download WHERE order_id = '" . (int)$order_id . "'");

        if (isset($data['products'])) {

            foreach ($data['products'] as $order_product) {
//                    echo"INSERT INTO " . DB_PREFIX . "order_product SET order_product_id = '" . (int)$order_product['product_id'] . "', order_id = '" . (int)$order_id . "', product_id = '" . (int)$order_product['product_id'] . "', name = '" . $this->db->escape($order_product['name']) . "', model = '" . $this->db->escape($order_product['model']) . "', quantity = '" . (int)$order_product['quantity'] . "', price = '" . (float)$order_product['price'] . "', total = '" . (float)$order_product['total'] . "', tax = '" . (float)$order_product['tax'] . "', reward = '" . (int)$order_product['reward'] . "'";
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET  order_id = '" . (int) $order_id . "', product_id = '" . (int) $order_product['product_id'] . "', name = '" . $this->db->escape($order_product['name']) . "', model = '" . $this->db->escape($order_product['model']) . "', quantity = '" . (int) $order_product['quantity'] . "', price = '" . (float) $order_product['price'] . "', total = '" . (float) $order_product['total'] . "', tax = '" . (float) $order_product['tax'] . "', reward = '" . (int) $order_product['reward'] . "'");
//			var_dump($order_product_id);die;
                $order_product_id = $this->db->getLastId();

                if (isset($order_product['option'])) {
                    foreach ($order_product['option'] as $order_option) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET  order_id = '" . (int) $order_id . "', order_product_id = '" . (int) $order_product_id . "', product_option_id = '" . (int) $order_option['product_option_id'] . "', product_option_value_id = '" . (int) $order_option['product_option_value_id'] . "', name = '" . $this->db->escape($order_option['name']) . "', `value` = '" . $this->db->escape($order_option['value']) . "', `type` = '" . $this->db->escape($order_option['type']) . "'");
                    }
                }

            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int) $order_id . "'");

        if (isset($data['vouchers'])) {
            foreach ($data['vouchers'] as $order_voucher) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_voucher SET  order_id = '" . (int) $order_id . "', voucher_id = '" . (int) $order_voucher['voucher_id'] . "', description = '" . $this->db->escape($order_voucher['description']) . "', code = '" . $this->db->escape($order_voucher['code']) . "', from_name = '" . $this->db->escape($order_voucher['from_name']) . "', from_email = '" . $this->db->escape($order_voucher['from_email']) . "', to_name = '" . $this->db->escape($order_voucher['to_name']) . "', to_email = '" . $this->db->escape($order_voucher['to_email']) . "', voucher_theme_id = '" . (int) $order_voucher['voucher_theme_id'] . "', message = '" . $this->db->escape($order_voucher['message']) . "', amount = '" . (float) $order_voucher['amount'] . "'");
                $order_voucher_id = $this->db->getLastId();
                $this->db->query("UPDATE " . DB_PREFIX . "voucher SET order_id = '" . (int) $order_id . "' WHERE voucher_id = '" . (int) $order_voucher_id . "'");
            }
        }

        // Get the total
        $total = 0;

        $this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int) $order_id . "'");

        if (isset($data['totals'])) {
            foreach ($data['totals'] as $order_total) {
//                    var_dump($order_total);die;
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET  order_id = '" . (int) $order_id . "', code = '" . $this->db->escape($order_total['code']) . "', title = '" . $this->db->escape($order_total['title']) . "',  `value` = '" . (float) $order_total['value'] . "', sort_order = '" . (int) $order_total['sort_order'] . "'");
                $total += $order_total['value'] == "" ? 0 : $order_total['value'];
            }

        }

        // Affiliate
        /* $affiliate_id = 0;
          $commission = 0;

          if (!empty($this->request->post['affiliate_id'])) {
          $this->load->model('sale/affiliate');

          $affiliate_info = $this->model_sale_affiliate->getAffiliate($this->request->post['affiliate_id']);

          if ($affiliate_info) {
          $affiliate_id = $affiliate_info['affiliate_id'];
          $commission = ($total / 100) * $affiliate_info['commission'];
          }
          }

          $this->db->query("UPDATE `" . DB_PREFIX . "order` SET total = '" . (float)$total . "', affiliate_id = '" . (int)$affiliate_id . "', commission = '" . (float)$commission . "' WHERE order_id = '" . (int)$order_id . "'");
         */
    }

}

?>