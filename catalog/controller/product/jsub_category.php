<?php
class ControllerProductJsubCategory extends Controller {
	public function index() {
		if($this->config->get('jsub_category_status')) {

			$this->load->language('product/jsub_category');

			$this->load->model('catalog/category');

			$this->load->model('tool/image');

			$this->document->addStyle('catalog/view/theme/default/stylesheet/jsub_category.css');

			if($this->config->get('jsub_category_carousel')) {
				$this->document->addStyle('catalog/view/javascript/jquery/owl-carousel/owl.carousel.css');
				$this->document->addScript('catalog/view/javascript/jquery/owl-carousel/owl.carousel.min.js');
			}


			$config_language_id = $this->config->get('config_language_id');

			$jsub_category_data = (array)$this->config->get('jsub_category_data');

			$data['jheading'] = $jsub_category_data[$config_language_id]['sub_heading'] ? $jsub_category_data[$config_language_id]['sub_heading'] : $this->language->get('text_sub_heading');

			$data['view'] = $jsub_category_data[$config_language_id]['sub_viewall'] ? $jsub_category_data[$config_language_id]['sub_viewall'] : $this->language->get('text_sub_viewall');

			$data['bgcolor']	= $this->config->get('jsub_category_bg');
			$data['bghcolor']	= $this->config->get('jsub_category_bg_hover_color');
			$data['bordercolor']	= $this->config->get('jsub_category_border');
			$data['titlecolor']	= $this->config->get('jsub_category_titlecolor');
			$data['desccolor']	= $this->config->get('jsub_category_desccolor');
			$data['viewallcolor']	= $this->config->get('jsub_category_viewallcolor');
			$data['carousel']	= $this->config->get('jsub_category_carousel');
			$data['carouselnav']	= $this->config->get('jsub_category_carnav');
			$data['carouselpagi']	= $this->config->get('jsub_category_carpage');
			$data['carouselauto']	= $this->config->get('jsub_category_carautoplay');
			$data['carouselitems']= $this->config->get('jsub_category_caritem');
			$data['sublayout']= $this->config->get('jsub_category_layout');

			$data['moduletitle'] = $this->config->get('jsub_category_title');

			if (isset($this->request->get['path'])) {
				$url = '';

				if (isset($this->request->get['sort'])) {
					$url .= '&sort=' . $this->request->get['sort'];
				}

				if (isset($this->request->get['order'])) {
					$url .= '&order=' . $this->request->get['order'];
				}

				if (isset($this->request->get['limit'])) {
					$url .= '&limit=' . $this->request->get['limit'];
				}

				$path = '';

				$parts = explode('_', (string)$this->request->get['path']);

				$category_id = (int)array_pop($parts);
			} else {
				$category_id = 0;
			}

			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {

				$data['jcategories'] = array();

				$jresults = $this->model_catalog_category->getCategories($category_id);

				foreach ($jresults as $jresult) {
					$filter_data = array(
						'filter_category_id'  => $jresult['category_id'],
						'filter_sub_category' => true
					);

					$image = '';
					if ($this->config->get('jsub_category_images')) {

						if ($jresult['image']) {
							$image = $this->model_tool_image->resize($jresult['image'], $this->config->get('jsub_category_width'), $this->config->get('jsub_category_height'));
						} else {
							$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('jsub_category_width'), $this->config->get('jsub_category_height'));
						}

					}

					$description = '';

					if ($this->config->get('jsub_category_description')) {

						$jresult['jdescription'] = strip_tags(html_entity_decode($jresult['description'], ENT_QUOTES, 'UTF-8'));

						if (utf8_strlen($jresult['jdescription']) > (int)$this->config->get('jsub_category_desclength')) {

							$description = utf8_substr($jresult['jdescription'], 0, (int)$this->config->get('jsub_category_desclength')) .'..';
						} else {
							$description = $jresult['jdescription'];
						}

					}


					$data['jcategories'][] = array(
						'name' 		  => $jresult['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
						'href' 		  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '_' . $jresult['category_id'] . $url),
						'description' =>  $description,
						'thumb'       => $image
					);
				}

				if(VERSION < '2.2.0.0') {
					if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/product/jsub_category.tpl')) {
						return $this->load->view($this->config->get('config_template') . '/template/product/jsub_category.tpl', $data);
					} else {
						return $this->load->view('default/template/product/jsub_category.tpl', $data);
					}
				} else {
					return $this->load->view('product/jsub_category', $data);
				}
			}
		}
	}
}