<?php
class ControllerExtensionModuleWidSoldOut extends Controller {
	// catalog/controller/product/product/before
	public function widSoldOut(&$route, &$data) {
				
		if ($this->config->get('module_wid_sold_out_status')) { //from model('setting/setting')
			
			$this->load->language('extension/module/wid_sold_out');
			//$this->load->model('catalog/product');
			//$this->load->model('setting/setting');
			$sold_out_bg_color = $this->config->get('module_wid_sold_out_color'); //from model('setting/setting')
			//$sold_out_disable = $this->config->get('module_wid_sold_out_disable');
			
			$this->load->model('extension/module/wid_sold_out');
			//as input put the value of db.stock_status.name out of stock
			$stock_status_out_of_stock = $this->model_extension_module_wid_sold_out->getStockStatusOutOfStock('Out Of Stock');
			
			if (isset($data['product_id'])) {
					
				$data['product']['button_cart'] = $this->language->get('button_cart');
				
				$product_info = $this->model_catalog_product->getProduct($data['product_id']);
				if ($product_info) {				
							
					if ($product_info['quantity'] <= 0 && in_array($data['stock'], $stock_status_out_of_stock)) {
						
						//if catalog/language/*/extension/module/wid_sold_out.php doed not exist give the default value
						$button_cart_value = $this->language->get('button_cart_wid_sold_out');
						if ($button_cart_value == 'button_cart_wid_sold_out') {
							$button_cart_value = $product_info['stock_status'];
						}
 						
						$data['product']['button_cart'] = $button_cart_value;
						
						$wid_js = '';
						if ($this->config->get('module_wid_sold_out_disable')) { 
							$wid_js .= 'document.getElementById("button-cart").disabled = true;';
						}
						if ($this->config->get('module_wid_sold_out_color_enabled')) { 
							$wid_js .= 'document.getElementById("button-cart").style.background = "'.$sold_out_bg_color.' url() no-repeat right top";';
						}
						if ($this->config->get('module_wid_sold_out_watermark_enabled')) { 
						$wid_js .= 'var newnode = document.createElement("IMG");
											var newatt1 = document.createAttribute("style");
											newatt1.value = "position:absolute; top: 20px; left:20%; max-width: 60%; overflow:hidden;";
											var newatt2 = document.createAttribute("src");
											newatt2.value = "' . HTTP_SERVER . 'image/catalog/widsoldout_228x110.png";
											var newatt3 = document.createAttribute("class");
											newatt3.value = "img-responsive";
											newnode.setAttributeNode(newatt1);   
											newnode.setAttributeNode(newatt2);   
											newnode.setAttributeNode(newatt3); 
											var thefirstli = document.getElementsByClassName("thumbnails")[0].getElementsByTagName("li")[0].appendChild(newnode);
											';
						}
							$data['footer'] = '<script type="text/javascript">' .$wid_js . '</script>' .$data['footer'];
					}
				}	
			}
			
			//you have to mod product/*.twig replace {{ button_cart }} with {{ product.button_cart }}
			if (isset($data['products'])) {
				foreach ($data['products'] as $key => $product) {
					$product_info = $this->model_catalog_product->getProduct($data['products'][$key]['product_id']);
					$data['products'][$key]['button_cart'] = $this->language->get('button_cart');
					
					if ($product_info['quantity'] <= 0 && in_array($product_info['stock_status'], $stock_status_out_of_stock)) {
						
						$button_cart_value = $this->language->get('button_cart_wid_sold_out');
						if ($button_cart_value == 'button_cart_wid_sold_out') {
							$button_cart_value = $product_info['stock_status'];
						}
						$data['products'][$key]['button_cart'] = $button_cart_value;
						
						$wid_js = '';
						if ($this->config->get('module_wid_sold_out_disable')) { 
							$wid_js .= 'document.getElementById("button-cart-'.$data['products'][$key]['product_id'].'").disabled = true;';
						}
						if ($this->config->get('module_wid_sold_out_color_enabled')) { 
							$wid_js .= 'document.getElementById("button-cart-'.$data['products'][$key]['product_id'].'").style.background = "'.$sold_out_bg_color.' url() no-repeat right top";';
						}
						if ($this->config->get('module_wid_sold_out_watermark_enabled')) { 
						$wid_js .= 'var newnode = document.createElement("IMG");
											var newatt1 = document.createAttribute("style");
											//newatt1.value = "position:relative; bottom: 110px; margin-bottom: -110px; overflow:hidden;"; 
											newatt1.value = "position:absolute; top: 20px; left:20%; max-width: 60%; overflow:hidden;";
											var newatt2 = document.createAttribute("src");
											newatt2.value = "' . HTTPS_SERVER . 'image/catalog/widsoldout_228x110.png";
											var newatt3 = document.createAttribute("class");
											newatt3.value = "img-responsive";
											newnode.setAttributeNode(newatt1);   
											newnode.setAttributeNode(newatt2);   
											newnode.setAttributeNode(newatt3); 
											document.getElementById("image-'.$data['products'][$key]['product_id'].'").appendChild(newnode);
											';
						}
							$data['footer'] = '<script type="text/javascript">' .$wid_js . '</script>' .$data['footer'];
						
					}
					
				}
			}
			//$this->log->debug($data);
		}
	}
}
