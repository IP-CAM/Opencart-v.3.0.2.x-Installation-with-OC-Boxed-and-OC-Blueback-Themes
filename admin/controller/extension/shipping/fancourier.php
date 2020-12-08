<?php
class ControllerExtensionShippingFANCourier extends Controller {
	private $error = array(); 
	
	public function index() {
		$this->load->language('extension/shipping/fancourier');
			
		$this->document->setTitle($this->language->get('heading_title'));
	
		$this->load->model('setting/setting');
				
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_fancourier', $this->request->post);
					
			$this->session->data['success'] = $this->language->get('text_success');
						
			//$this->response -> redirect($this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'));
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}
		
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');

                $data['entry_status'] = $this->language->get('entry_status');
                $data['entry_clientid'] = $this->language->get('entry_clientid');
				$data['help_entry_clientid'] = $this->language->get('help_entry_clientid');
                $data['entry_username'] = $this->language->get('entry_username');
				$data['help_entry_username'] = $this->language->get('help_entry_username');
                $data['entry_password'] = $this->language->get('entry_password');
				$data['help_entry_password'] = $this->language->get('help_entry_password');
                $data['entry_onlyadm'] = $this->language->get('entry_onlyadm');
                $data['entry_parcel'] = $this->language->get('entry_parcel');				
                $data['text_labels'] = $this->language->get('text_labels');
				$data['help_text_labels'] = $this->language->get('help_text_labels');
                $data['entry_paymentdest'] = $this->language->get('entry_paymentdest');
                $data['entry_fara_tva'] = $this->language->get('entry_fara_tva');								
				
				//afisare pret doar km suplimentari
				$data['entry_doar_km'] = $this->language->get('entry_doar_km');
				$data['help_entry_doar_km'] = $this->language->get('help_entry_doar_km');
				//sfarsit afisare pret doar km suplimentari
				
                $data['entry_payment0'] = $this->language->get('entry_payment0');
                $data['text_min_gratuit'] = $this->language->get('text_min_gratuit');
                //alex g valoare fixa
                $data['text_valoare_fixa'] = $this->language->get('text_valoare_fixa');
                //sfarsit valoare fixa
                
                //alex g valoare fixa Bucuresti
                $data['text_valoare_fixa_bucuresti'] = $this->language->get('text_valoare_fixa_bucuresti');
                //sfarsit valoare fixa Bucuresti
                
                $data['entry_ramburs'] = $this->language->get('entry_ramburs');
                $data['entry_totalrb'] = $this->language->get('entry_totalrb');
                $data['entry_contcolector'] = $this->language->get('entry_contcolector');
                $data['entry_paymentrbdest'] = $this->language->get('entry_paymentrbdest');
				$data['help_entry_paymentrbdest'] = $this->language->get('help_entry_paymentrbdest');
                $data['entry_asigurare'] = $this->language->get('entry_asigurare');
                $data['entry_content'] = $this->language->get('entry_content');
                $data['entry_comment'] = $this->language->get('entry_comment');
				//adaugare persoana de contact
				$data['entry_pers_cont_exp'] = $this->language->get('entry_pers_cont_exp');
				//adaugare optiune
				$data['entry_deschidere_livrare'] = $this->language->get('entry_deschidere_livrare');
				$data['help_entry_deschidere_livrare'] = $this->language->get('help_entry_deschidere_livrare');
				//end
                $data['entry_epod'] = $this->language->get('entry_epod');
                $data['help_entry_epod'] = $this->language->get('help_entry_epod');
				//end
                $data['entry_redcode'] = $this->language->get('entry_redcode');
                $data['entry_express'] = $this->language->get('entry_express');
				
				//Collect Point
				$data['entry_ridicare_paypoint'] = $this->language->get('entry_ridicare_paypoint');
				$data['entry_ridicare_keba'] = $this->language->get('entry_ridicare_keba');
				//end Collect Point
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['clientid'])) {
			$data['error_clientid'] = $this->error['clientid'];
		} else {
			$data['error_clientid'] = '';
		}

		if (isset($this->error['username'])) {
			$data['error_username'] = $this->error['username'];
		} else {
			$data['error_username'] = '';
		}

		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_password'] = '';
		}

		if (isset($this->error['labels'])) {
			$data['error_labels'] = $this->error['labels'];
		} else {
			$data['error_labels'] = '';
		}

		if (isset($this->error['min_gratuit'])) {
			$data['error_min_gratuit'] = $this->error['min_gratuit'];
		} else {
			$data['error_min_gratuit'] = '';
		}
		
		//alex g valoare fixa
		if (isset($this->error['valoare_fixa'])) {
			$data['error_valoare_fixa'] = $this->error['valoare_fixa'];
		} else {
			$data['error_valoare_fixa'] = '';
		}
		//sfarsit alex g
		
		//alex g valoare fixa bucuresti
		if (isset($this->error['valoare_fixa_bucuresti'])) {
			$data['error_valoare_fixa_bucuresti'] = $this->error['valoare_fixa_bucuresti'];
		} else {
			$data['error_valoare_fixa_bucuresti'] = '';
		}
		//sfarsit alex g
		

  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_extension'),
			'href'      => $this->url->link('marketplace/shipping', 'user_token=' . $this->session->data['user_token']. '&type=shipping', true),
      		
   		);
		
   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/shipping/fancourier', 'user_token=' . $this->session->data['user_token'], true),
      		
   		);
		
		$data['action'] = $this->url->link('extension/shipping/fancourier', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token']. '&type=shipping', true);

		if (isset($this->request->post['shipping_fancourier_status'])) {
			$data['shipping_fancourier_status'] = $this->request->post['shipping_fancourier_status'];
		} else {
			$data['shipping_fancourier_status'] = $this->config->get('shipping_fancourier_status');
		}

		if (isset($this->request->post['shipping_fancourier_clientid'])) {
			$data['shipping_fancourier_clientid'] = $this->request->post['shipping_fancourier_clientid'];
		} else {
			$data['shipping_fancourier_clientid'] = $this->config->get('shipping_fancourier_clientid');
		}

		if (isset($this->request->post['shipping_fancourier_username'])) {
			$data['shipping_fancourier_username'] = $this->request->post['shipping_fancourier_username'];
		} else {
			$data['shipping_fancourier_username'] = $this->config->get('shipping_fancourier_username');
		}
		
		if (isset($this->request->post['shipping_fancourier_password'])) {
			$data['shipping_fancourier_password'] = $this->request->post['shipping_fancourier_password'];
		} else {
			$data['shipping_fancourier_password'] = $this->config->get('shipping_fancourier_password');
		}

		if (isset($this->request->post['shipping_fancourier_onlyadm'])) {
			$data['shipping_fancourier_onlyadm'] = $this->request->post['shipping_fancourier_onlyadm'];
		} else {
			$data['shipping_fancourier_onlyadm'] = $this->config->get('shipping_fancourier_onlyadm');
		}

		if (isset($this->request->post['shipping_fancourier_parcel'])) {
			$data['shipping_fancourier_parcel'] = $this->request->post['shipping_fancourier_parcel'];
		} else {
			$data['shipping_fancourier_parcel'] = $this->config->get('shipping_fancourier_parcel');
		}

		if (isset($this->request->post['shipping_fancourier_labels'])) {
			$data['shipping_fancourier_labels'] = $this->request->post['shipping_fancourier_labels'];
		} else {
			$data['shipping_fancourier_labels'] = $this->config->get('shipping_fancourier_labels');
		}

		if (isset($this->request->post['shipping_fancourier_paymentdest'])) {
			$data['shipping_fancourier_paymentdest'] = $this->request->post['shipping_fancourier_paymentdest'];
		} else {
			$data['shipping_fancourier_paymentdest'] = $this->config->get('shipping_fancourier_paymentdest');
		}

		if (isset($this->request->post['shipping_fancourier_fara_tva'])) {
			$data['shipping_fancourier_fara_tva'] = $this->request->post['shipping_fancourier_fara_tva'];
		} else {
			$data['shipping_fancourier_fara_tva'] = $this->config->get('shipping_fancourier_fara_tva');
		}
		
		
		//afisare valoare doar km suplimentari
		if (isset($this->request->post['shipping_fancourier_doar_km'])) {
			$data['shipping_fancourier_doar_km'] = $this->request->post['shipping_fancourier_doar_km'];
		} else {
			$data['shipping_fancourier_doar_km'] = $this->config->get('shipping_fancourier_doar_km');
		}
		//sfarsit afisare valoare doar km suplimentari
			

		if (isset($this->request->post['shipping_fancourier_payment0'])) {
			$data['shipping_fancourier_payment0'] = $this->request->post['shipping_fancourier_payment0'];
		} else {
			$data['shipping_fancourier_payment0'] = $this->config->get('shipping_fancourier_payment0');
		}

		if (isset($this->request->post['shipping_fancourier_min_gratuit'])) {
			$data['shipping_fancourier_min_gratuit'] = $this->request->post['shipping_fancourier_min_gratuit'];
		} else {
			$data['shipping_fancourier_min_gratuit'] = $this->config->get('shipping_fancourier_min_gratuit');
		}
		
		//alex g valoare fixa
		if (isset($this->request->post['shipping_fancourier_valoare_fixa'])) {
			$data['shipping_fancourier_valoare_fixa'] = $this->request->post['shipping_fancourier_valoare_fixa'];
		} else {
			$data['shipping_fancourier_valoare_fixa'] = $this->config->get('shipping_fancourier_valoare_fixa');
		}
		//sfarsit valoare fixa
		
		//alex g valoare fixa bucuresti
		if (isset($this->request->post['shipping_fancourier_valoare_fixa_bucuresti'])) {
			$data['shipping_fancourier_valoare_fixa_bucuresti'] = $this->request->post['shipping_fancourier_valoare_fixa_bucuresti'];
		} else {
			$data['shipping_fancourier_valoare_fixa_bucuresti'] = $this->config->get('shipping_fancourier_valoare_fixa_bucuresti');
		}
		//sfarsit valoare fixa bucuresti

		if (isset($this->request->post['shipping_fancourier_ramburs'])) {
			$data['shipping_fancourier_ramburs'] = $this->request->post['shipping_fancourier_ramburs'];
		} else {
			$data['shipping_fancourier_ramburs'] = $this->config->get('shipping_fancourier_ramburs');
		}

		if (isset($this->request->post['shipping_fancourier_totalrb'])) {
			$data['shipping_fancourier_totalrb'] = $this->request->post['shipping_fancourier_totalrb'];
		} else {
			$data['shipping_fancourier_totalrb'] = $this->config->get('shipping_fancourier_totalrb');
		}

		if (isset($this->request->post['shipping_fancourier_contcolector'])) {
			$data['shipping_fancourier_contcolector'] = $this->request->post['shipping_fancourier_contcolector'];
		} else {
			$data['shipping_fancourier_contcolector'] = $this->config->get('shipping_fancourier_contcolector');
		}

		if (isset($this->request->post['shipping_fancourier_paymentrbdest'])) {
			$data['shipping_fancourier_paymentrbdest'] = $this->request->post['shipping_fancourier_paymentrbdest'];
		} else {
			$data['shipping_fancourier_paymentrbdest'] = $this->config->get('shipping_fancourier_paymentrbdest');
		}

		if (isset($this->request->post['shipping_fancourier_asigurare'])) {
			$data['shipping_fancourier_asigurare'] = $this->request->post['shipping_fancourier_asigurare'];
		} else {
			$data['shipping_fancourier_asigurare'] = $this->config->get('shipping_fancourier_asigurare');
		}

		if (isset($this->request->post['shipping_fancourier_content'])) {
			$data['shipping_fancourier_content'] = $this->request->post['shipping_fancourier_content'];
		} else {
			$data['shipping_fancourier_content'] = $this->config->get('shipping_fancourier_content');
		}

		if (isset($this->request->post['shipping_fancourier_comment'])) {
			$data['shipping_fancourier_comment'] = $this->request->post['shipping_fancourier_comment'];
		} else {
			$data['shipping_fancourier_comment'] = $this->config->get('shipping_fancourier_comment');
		}
		
		//boby 02.05.2014 adaugare persoana de contact
		if (isset($this->request->post['pers_contact_expeditor'])) {
			$data['shipping_fancourier_pers_contact_expeditor'] = $this->request->post['shipping_fancourier_pers_contact_expeditor'];
		} else {
			$data['shipping_fancourier_pers_contact_expeditor'] = $this->config->get('shipping_fancourier_pers_contact_expeditor');
		}
		//end boby
		
		//boby 05.05.2014 deschidere la livrare
		if (isset($this->request->post['shipping_fancourier_deschidere_livrare'])) {
			$data['shipping_fancourier_deschidere_livrare'] = $this->request->post['shipping_fancourier_deschidere_livrare'];
		} else {
			$data['shipping_fancourier_deschidere_livrare'] = $this->config->get('shipping_fancourier_deschidere_livrare');
		}

        if (isset($this->request->post['shipping_fancourier_epod'])) {
            $data['shipping_fancourier_epod'] = $this->request->post['shipping_fancourier_epod'];
        } else {
            $data['shipping_fancourier_epod'] = $this->config->get('shipping_fancourier_epod');
        }
		//end boby

		if (isset($this->request->post['shipping_fancourier_redcode'])) {
			$data['shipping_fancourier_redcode'] = $this->request->post['shipping_fancourier_redcode'];
		} else {
			$data['shipping_fancourier_redcode'] = $this->config->get('shipping_fancourier_redcode');
		}

		if (isset($this->request->post['shipping_fancourier_express'])) {
			$data['shipping_fancourier_express'] = $this->request->post['shipping_fancourier_express'];
		} else {
			$data['shipping_fancourier_express'] = $this->config->get('shipping_fancourier_express');
		}
		
		 if (isset($this->request->post['shipping_fancourier_ridicare_paypoint'])) {
            $data['shipping_fancourier_ridicare_paypoint'] = $this->request->post['shipping_fancourier_ridicare_paypoint'];
        } else {
            $data['shipping_fancourier_ridicare_paypoint'] = $this->config->get('shipping_fancourier_ridicare_paypoint');
        }
		
		 if (isset($this->request->post['shipping_fancourier_ridicare_keba'])) {
            $data['shipping_fancourier_ridicare_keba'] = $this->request->post['shipping_fancourier_ridicare_keba'];
        } else {
            $data['shipping_fancourier_ridicare_keba'] = $this->config->get('shipping_fancourier_ridicare_keba');
        }
		
		
		
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/fancourier', $data));
	}
	
	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/fancourier')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['shipping_fancourier_clientid']) {
			$this->error['clientid'] = $this->language->get('error_clientid');
		}
		
		if (!$this->request->post['shipping_fancourier_username']) {
			$this->error['username'] = $this->language->get('error_username');
		}

		if (!$this->request->post['shipping_fancourier_password']) {
			$this->error['password'] = $this->language->get('error_password');
		}
                
		if (!$this->request->post['shipping_fancourier_labels']) {
			$this->error['labels'] = $this->language->get('error_labels');
		}

		if ($this->request->post['shipping_fancourier_labels']) {
                        if (!is_numeric($this->request->post['shipping_fancourier_labels'])){
                            $this->error['labels'] = $this->language->get('error_labels');
                        }
		}

		if ($this->request->post['shipping_fancourier_min_gratuit']) {
                        if (!is_numeric($this->request->post['shipping_fancourier_min_gratuit'])){
                            $this->error['min_gratuit'] = $this->language->get('error_min_gratuit');
                        }
		}
		
		//alex g valoare fixa
		if ($this->request->post['shipping_fancourier_valoare_fixa']) {
                        if (!is_numeric($this->request->post['shipping_fancourier_valoare_fixa'])){
                            $this->error['valoare_fixa'] = $this->language->get('error_valoare_fixa');
                        }
		}
		//sfarsit valoare fixa
		
		//alex g valoare fixa bucuresti
		if ($this->request->post['shipping_fancourier_valoare_fixa_bucuresti']) {
                        if (!is_numeric($this->request->post['shipping_fancourier_valoare_fixa_bucuresti'])){
                            $this->error['valoare_fixa_bucuresti'] = $this->language->get('error_valoare_fixa_bucuresti');
                        }
		}
		//sfarsit valoare fixa bucuresti 
		
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}
}
?>