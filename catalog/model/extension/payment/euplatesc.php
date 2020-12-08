<?php
class ModelExtensionPaymentEuPlatesc extends Model {
	public function getMethod($address, $total) {
		
		$status = true;
		

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'euplatesc',
				'title'      => $this->config->get('payment_euplatesc_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_euplatesc_sort_order')
			);
		}

		return $method_data;
	}
}
