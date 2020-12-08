<?php
class ControllerMmstockMmstock extends Controller {
	private $error = array();

	public function index() {
		
		$this->load->language('mmstock/mmstock');
		$this->load->model('mmstock/mmstock');
		$this->document->setTitle($this->language->get('heading_title'));
		$data['text_limit'] = "Show:";
		$data['text_sort'] = "Sort By:";
		
		
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}
		
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		
		
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('mmstock/mmstock')
		);
		
		  

		/* Pagination start */
		  if (isset($this->request->get['limit'])) {
			$limit = $this->request->get['limit'];
		} else {
			$limit = 5;
		}
		  
		  $url = '';
		  $data['sorts'] = array();

			$data['sorts'][] = array(
				'text'  => $this->language->get('Default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('mmstock/mmstock',  '&sort=p.sort_order&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('Name (A - Z)'),
				'value' => 'mmtd.author-ASC',
				'href'  => $this->url->link('mmstock/mmstock',  '&sort=mmtd.author&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('Name (Z - A)'),
				'value' => 'mmtd.author-DESC',
				'href'  => $this->url->link('mmstock/mmstock',  '&sort=mmtd.author&order=DESC' . $url)
			);

			$data['sorts'][] = array(
					'text'  => $this->language->get('Rating (Highest)'),
					'value' => 'mmt.rating-DESC',
					'href'  => $this->url->link('mmstock/mmstock',  '&sort=mmt.rating&order=DESC' . $url)
				);

			$data['sorts'][] = array(
					'text'  => $this->language->get('Rating (Lowest)'),
					'value' => 'mmt.rating-ASC',
					'href'  => $this->url->link('mmstock/mmstock',  '&sort=mmt.rating&order=ASC' . $url)
				);
			

			
			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}
		  
		  
			$data['limits'] = array();

			$limits = array_unique(array(5,15, 20, 25, 30));

			sort($limits);

			foreach($limits as $value) {
				$data['limits'][] = array(
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('mmstock/mmstock',   $url . '&limit=' . $value)
				);
			} 
		  
		  
		   $filter_data = array(				
				'start'				=> ($page - 1) * $limit,
				'limit'				=> $limit,
				'sort'				=> $sort,
				'order'				=> $order
			);
		/* Pagination end */
		
		
		
			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}
		
		
		$this->load->model("tool/image");
		$data['testimonials_data'] = $this->model_mmstock_mmstock->getmmstock($filter_data);
		$testimonial_total = $this->model_mmstock_mmstock->getTotalTestimonials();
		
		$data['testimonials_login']=$this->customer->isLogged();
		$data['testimonials_setting'] = $this->model_mmstock_mmstock->getTestimonials_setting();
		
		
		
		
		/* Pagination Start */
		
		
			
		
		
		
		
			$pagination = new Pagination();
			$pagination->total = $testimonial_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('mmstock/mmstock','&page={page}');

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($testimonial_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($testimonial_total - $limit)) ? $testimonial_total : ((($page - 1) * $limit) + $limit), $testimonial_total, ceil($testimonial_total / $limit));

			
			if ($page == 1) {
			    $this->document->addLink($this->url->link('mmstock/mmstock',  true), 'canonical');
			} elseif ($page == 2) {
			    $this->document->addLink($this->url->link('mmstock/mmstock', true), 'prev');
			} else {
			    $this->document->addLink($this->url->link('mmstock/mmstock', '&page='. ($page - 1), true), 'prev');
			}

			if ($limit && ceil($testimonial_total / $limit) > $page) {
			    $this->document->addLink($this->url->link('mmstock/mmstock',   '&page='. ($page + 1), true), 'next');
			}

			$data['sort'] = $sort;
			$data['order'] = $order;
			$data['limit'] = $limit;
			
		
		/* Pagination End */
		
		
		
		$data['testimonials_href'] = $this->url->link('testimonials_href/testimonials_href');
			foreach($data['testimonials_data'] as $mmt_k => $mmt_val)
				{
					
								if ($mmt_val['image']!="")
								{
									$data[].= $this->model_tool_image->resize($mmt_val['image'], 120, 120);
								} 
								else {
										$data[].= $this->model_tool_image->resize('mmtesti_user.png', 120, 120);
								}
				}
		$data['testimonials_data']=$data;
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		$this->response->setOutput($this->load->view('mmstock/mmstock', $data));
	}
	
			public function email_notification()
			{
				
			print_r($this->request->post);
			$this->load->model('mmstock/mmstock');
			$results = $this->model_mmstock_mmstock->addnotification($this->request->post);
			
	}
		



}
