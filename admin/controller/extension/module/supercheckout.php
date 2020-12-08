<?php
require_once(DIR_SYSTEM . 'library/kbsupercheckout/newslatter/Batch.php');
require_once(DIR_SYSTEM . 'library/kbsupercheckout/newslatter/MailChimp.php');
require_once(DIR_SYSTEM . 'library/kbsupercheckout/newslatter/Webhook.php');

use DrewM\MailChimp\MailChimp;
class ControllerExtensionModuleSupercheckout extends Controller {

    private $error = array();
    private $texts = array('title', 'tooltip', 'description', 'text');
    private $session_token_key = 'token';
    private $session_token = '';
    private $module_path = '';

    public function __construct($registry) {
        parent::__construct($registry);
        if (VERSION >= 3.0) {
            $this->session_token_key = 'user_token';
            $this->session_token = $this->session->data['user_token'];
        } else {
            $this->session_token_key = 'token';
            $this->session_token = $this->session->data['token'];
        }
        if (VERSION <= '2.2.0') {
            $this->module_path = 'module';
        } else {
            $this->module_path = 'extension/module';
        }
    }

    public function index() {

        $this->load->language($this->module_path . '/supercheckout');
        $this->document->setTitle($this->language->get('heading_title_main'));
        $this->load->model('setting/setting');
        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        } else {
            $store_id = 0;
        }
        $data['store_id'] = $store_id;
        $this->preventReinstall();
        
        $classes_array = $this->getClasses();
        if (isset($classes_array['anchor_classes']['supercheckout_classes'])) {
            $data['anchor_classes'] = $classes_array['anchor_classes']['supercheckout_classes'];
        }
        
        if (isset($classes_array['anchor_classes_trigger']['supercheckout_trigger'])) {
            $data['anchor_classes_trigger'] = $classes_array['anchor_classes_trigger']['supercheckout_trigger'];
        }

        // Load settings for supercheckout plugin from database or from default settings
        $old_settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
        $old_default_settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
        if (!empty($old_settings)) {
            $new_settings = array();
            if (!isset($old_settings['supercheckout']['general']['adv_id'])) {
                $new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
                $old_settings['supercheckout']['general'] = array_merge($old_settings['supercheckout']['general'], $new_settings['default_supercheckout']['general']);
                $this->model_setting_setting->editSetting('supercheckout', $old_settings, $store_id);
            }
        }else{
            $settings_data['supercheckout'] = $old_default_settings['default_supercheckout'];
            $this->model_setting_setting->editSetting('supercheckout', $settings_data, $store_id);
        }
        if (!empty($old_default_settings)) {
            $new_settings = array();
            if (isset($old_settings['supercheckout']['general']['adv_id'])) {
                $new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
                $old_default_settings['default_supercheckout']['general'] = array_merge($old_default_settings['default_supercheckout']['general'], $new_settings['default_supercheckout']['general']);
                $this->model_setting_setting->editSetting('default_supercheckout', $old_default_settings, $store_id);
            }
        }
        $result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { {
                $this->session->data['success'] = $this->language->get('supercheckout_text_success');
                if (isset($this->request->post['supercheckout']['general']['settings']['value'])) {
                    $settings = str_replace("amp;", "", urldecode($this->request->post['supercheckout']['general']['settings']['bulk']));
                    parse_str($settings, $this->request->post);
                }
                $old_settings2 = $this->model_setting_setting->getSetting('supercheckout', $store_id);
                $this->request->post['supercheckout']['general']['column_width'] = $old_settings2['supercheckout']['general']['column_width'];
                $old_settings2['supercheckout']['general'] = $this->request->post['supercheckout']['general'];
                $old_settings2['supercheckout']['testing_mode'] = $this->request->post['supercheckout']['testing_mode'];
                $old_settings2['supercheckout']['custom'] = $this->request->post['supercheckout']['custom'];
                $old_settings2['supercheckout']['step']['login']['option']['guest']['display'] = $this->request->post['supercheckout']['step']['login']['option']['guest']['display'];
                $this->model_setting_setting->editSetting('supercheckout', $old_settings2, $store_id);
                $enable_status['module_supercheckout_status'] = $this->request->post['supercheckout']['general']['enable'];
                $this->model_setting_setting->editSetting('module_supercheckout', $enable_status, $store_id);
                if (!isset($this->request->post['save'])) {
                    $this->response->redirect($this->url->link($this->module_path . '/supercheckout', $this->session_token_key . '=' . $this->session_token, 'SSL'));
                } else if (!isset($this->session_token)) {
                    $this->response->redirect($this->url->link('extension/extension', $this->session_token_key . '=' . $this->session_token, 'SSL'));
                }
            }
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['heading_title_main'] = $this->language->get('heading_title_main');

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            $this->session->data['success'] = '';
        } else {
            $data['success'] = '';
        }
        // Words
        $data['settings_display'] = $this->language->get('settings_display');
        $data['settings_require'] = $this->language->get('settings_require');
        $data['settings_enable'] = $this->language->get('settings_enable');
        $data['supercheckout_text_enabled'] = $this->language->get('supercheckout_text_enabled');
        $data['supercheckout_text_disabled'] = $this->language->get('supercheckout_text_disabled');

        $data['supercheckout_entry_product'] = $this->language->get('supercheckout_entry_product');
        $data['supercheckout_entry_image'] = $this->language->get('supercheckout_entry_image');
        $data['supercheckout_entry_layout'] = $this->language->get('supercheckout_entry_layout');
        $data['supercheckout_entry_position'] = $this->language->get('supercheckout_entry_position');
        $data['supercheckout_entry_status'] = $this->language->get('supercheckout_entry_status');
        $data['supercheckout_entry_sort_order'] = $this->language->get('supercheckout_entry_sort_order');

        //General Settings tab & info
        $data['supercheckout_text_newsletter_enable'] = $this->language->get('supercheckout_text_newsletter_enable');
        $data['supercheckout_text_general'] = $this->language->get('supercheckout_text_general');
        $data['supercheckout_text_general_enable'] = $this->language->get('supercheckout_text_general_enable');
        $data['supercheckout_text_general_guestenable'] = $this->language->get('supercheckout_text_general_guestenable');
        $data['supercheckout_text_general_guest_manual'] = $this->language->get('supercheckout_text_general_guest_manual');
        $data['supercheckout_text_custom_style'] = $this->language->get('supercheckout_text_custom_style');
        $data['supercheckout_text_testing_url'] = $this->language->get('supercheckout_text_testing_url');
        $data['supercheckout_text_testing_enable'] = $this->language->get('supercheckout_text_testing_enable');
        $data['text_copy'] = $this->language->get('text_copy');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');

        $data['supercheckout_text_general_default'] = $this->language->get('supercheckout_text_general_default');
        $data['supercheckout_text_register'] = $this->language->get('supercheckout_text_register');
        $data['supercheckout_text_guest'] = $this->language->get('supercheckout_text_guest');

        $data['supercheckout_text_step_login_option'] = $this->language->get('supercheckout_text_step_login_option');
        $data['supercheckout_text_login'] = $this->language->get('supercheckout_text_login');
        $data['step_login_option_register_display'] = $this->language->get('supercheckout_text_register');
        $data['step_login_option_guest_display'] = $this->language->get('supercheckout_text_guest');

        //Language
        $data['supercheckout_text_language'] = $this->language->get('supercheckout_text_language');

        //Tooltips
        //General
        $data['general_enable_newsletter_tooltip'] = $this->language->get('general_enable_newsletter_tooltip');
        $data['general_enable_supercheckout_tooltip'] = $this->language->get('general_enable_supercheckout_tooltip');
        $data['custom_style_supercheckout_tooltip'] = $this->language->get('custom_style_supercheckout_tooltip');
        $data['general_guestenable_supercheckout_tooltip'] = $this->language->get('general_guestenable_supercheckout_tooltip');
        $data['general_guest_manual_supercheckout_tooltip'] = $this->language->get('general_guest_manual_supercheckout_tooltip');
        $data['general_default_supercheckout_tooltip'] = $this->language->get('general_default_supercheckout_tooltip');
        $data['step_login_option_supercheckout_tooltip'] = $this->language->get('step_login_option_supercheckout_tooltip');
        $data['guest_enable_disabled_supercheckout_tooltip'] = $this->language->get('guest_enable_disabled_supercheckout_tooltip');
        $data['field_disabled_supercheckout_tooltip'] = $this->language->get('field_disabled_supercheckout_tooltip');
        $data['supercheckout_text_testing_enable_tooltip'] = $this->language->get('supercheckout_text_testing_enable_tooltip');
        $data['supercheckout_text_testing_url_tooltip'] = $this->language->get('supercheckout_text_testing_url_tooltip');

        //errors
        $data['error_empty_field'] = $this->language->get('error_empty_field');
        $data['error_invalid_url'] = $this->language->get('error_invalid_url');
        $data['error_max_url'] = $this->language->get('error_max_url');
        
        //Buttons
        $data['button_save'] = $this->language->get('button_save');
        $data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_add_module'] = $this->language->get('button_add_module');
        $data['button_remove'] = $this->language->get('button_remove');

        $store_setting = $this->model_setting_setting->getSetting('config', $store_id);
        if (isset($store_setting['config_checkout_guest']))
            $data['guest_enable'] = $store_setting['config_checkout_guest'];

        if (version_compare(VERSION, '2.1.0.1', '<')) {
            $this->load->model('sale/customer_group');
            $results_customer_group = $this->model_sale_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
        } else {
            $this->load->model('customer/customer_group');
            $results_customer_group = $this->model_customer_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        //Breadcrumbs
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('supercheckout_text_home'),
            'href' => $this->url->link('common/home', $this->session_token_key . '=' . $this->session_token, 'SSL'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('supercheckout_text_module'),
            'href' => $this->url->link('extension/extension', $this->session_token_key . '=' . $this->session_token, 'SSL'),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->module_path . '/supercheckout', $this->session_token_key . '=' . $this->session_token, 'SSL'),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('supercheckout_text_general'),
            'href' => $this->url->link($this->module_path . '/supercheckout', $this->session_token_key . '=' . $this->session_token, 'SSL'),
            'separator' => ' :: '
        );

        //links
        $data['action'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, 'SSL');
        $data['action_save_classes'] = $this->url->link($this->module_path . '/supercheckout/saveClasses', $this->session_token_key . '=' . $this->session_token, 'SSL');
        $data['action_save_classes_trigger'] = $this->url->link($this->module_path . '/supercheckout/saveClassesTrigger', $this->session_token_key . '=' . $this->session_token, 'SSL');
        $data['route'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key . '=' . $this->session_token, 'SSL');
        $data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key . '=' . $this->session_token, 'SSL');
        $data['token'] = $this->session_token;
        $data['supercheckout'] = array();

        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        } else {
            $store_id = $this->config->get('config_store_id');
        }


        if (isset($this->request->post['supercheckout'])) {
            $data['supercheckout'] = $this->request->post['supercheckout'];
        } elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
            $settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
            $data['supercheckout'] = $settings['supercheckout'];
        }
        $data['supercheckout_modules'] = array();
        if (isset($this->request->post['supercheckout_module'])) {
            $data['supercheckout_modules'] = $this->request->post['supercheckout_module'];
        } elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
            $modules = $this->model_setting_setting->getSetting('supercheckout', $store_id);
            if (!empty($modules['supercheckout_module'])) {
                $data['supercheckout_modules'] = $modules['supercheckout_module'];
            } else {
                $data['supercheckout_modules'] = array();
            }
        }
        if (empty($settings)) {
            $settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
            $data['settings'] = $settings['default_supercheckout'];
            $data['supercheckout'] = $settings['default_supercheckout'];
        }
        
        //Store Settings
        $settings['general']['default_email'] = $this->config->get('config_email');
        $settings['step']['confirm']['fields']['agree']['information_id'] = $this->config->get('config_checkout_id');
        $settings['step']['confirm']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_checkout_id');

        if (!empty($data['supercheckout'])) {
            $data['supercheckout'] = $this->merge($settings, $data['supercheckout']);
        } else {
            $data['supercheckout'] = $settings;
        }
        $data['supercheckout']['general']['store_id'] = $store_id;

        $data['supercheckout']['testing_mode']['url'] = HTTP_CATALOG.'index.php?route=supercheckout/supercheckout';
        $tabs_data['store_id'] = $store_id;
        $tabs_data['active'] = 1;
        $data['tabs'] = $this->load->controller($this->module_path . '/supercheckout/tabs', $tabs_data);
        $data['store_id'] = $store_id;
        $data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key . '=' . $this->session_token . '&type=module&store_id=' . $store_id, true);
        $data['text_default'] = $this->language->get('text_default');
        $data['current_url'] = html_entity_decode($this->url->link($this->module_path . '/supercheckout', $this->session_token_key . '=' . $this->session_token, true));
        $data['store_switcher'] = $this->load->controller($this->module_path . '/supercheckout/store_swticher', $data);
        
        $this->load->model('design/layout');
        $data['layouts'] = $this->model_design_layout->getLayouts();
        
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();
        $this->template = $this->module_path . '/kbsupercheckout/supercheckout.tpl';


        //code for opencart2.0
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        if (VERSION < '2.2.0') {
            $this->response->setOutput($this->load->view($this->module_path . '/kbsupercheckout/supercheckout.tpl', $data));
        }else{
            $this->response->setOutput($this->load->view($this->module_path . '/kbsupercheckout/supercheckout', $data));
        }
        
        //code for 2.0 ends here
    }

    public function store_swticher($data = array()) {
            $this->load->language($this->module_path . '/kbsupercheckout');
            $this->load->model('setting/store');
            $data['stores'] = $this->model_setting_store->getStores();
            if (!empty($data['stores'])) {
                if (VERSION < '2.2.0') {
                    $this->response->setOutput($this->load->view($this->module_path . '/kbsupercheckout/supercheckout.tpl', $data));
                }else{
                    $this->response->setOutput($this->load->view($this->module_path . '/kbsupercheckout/supercheckout', $data));
                }
            } else {
                return "";
            }
        }
        
        public function tabs($data = array()) {
            $this->load->language($this->module_path . '/kbsupercheckout');

            $store_id = $data['store_id'];

            $data['supercheckout_text_general'] = $this->language->get('supercheckout_text_general');
            $data['supercheckout_text_login'] = $this->language->get('supercheckout_text_login');
            $data['supercheckout_text_payment_address'] = $this->language->get('supercheckout_text_payment_address');
            $data['supercheckout_text_shipping_address'] = $this->language->get('supercheckout_text_shipping_address');
            $data['supercheckout_text_shipping_method'] = $this->language->get('supercheckout_text_shipping_method');
            $data['supercheckout_text_ship2pay'] = $this->language->get('supercheckout_text_ship2pay');
            $data['supercheckout_text_payment_method'] = $this->language->get('supercheckout_text_payment_method');
            $data['supercheckout_text_confirm'] = $this->language->get('supercheckout_text_cart');
            $data['supercheckout_text_design'] = $this->language->get('supercheckout_text_design');
            $data['supercheckout_text_mailchimp'] = $this->language->get('supercheckout_text_mailchimp');
            

            $data['tab_general_settings'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
            $data['tab_login'] = $this->url->link($this->module_path . '/supercheckout/login', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
            $data['tab_payment_address'] = $this->url->link($this->module_path . '/supercheckout/payment_address', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
            $data['tab_shipping_address'] = $this->url->link($this->module_path . '/supercheckout/shipping_address', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
            $data['tab_shipping'] = $this->url->link($this->module_path . '/supercheckout/shipping_method', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
            $data['tab_ship2pay'] = $this->url->link($this->module_path . '/supercheckout/ship2pay', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
            $data['tab_payment'] = $this->url->link($this->module_path . '/supercheckout/payment_method', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
            $data['tab_confirm'] = $this->url->link($this->module_path . '/supercheckout/confirm', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
            $data['tab_design_checkout'] = $this->url->link($this->module_path . '/supercheckout/design_checkout', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
            $data['tab_mailchimp'] = $this->url->link($this->module_path . '/supercheckout/newsletter', $this->session_token_key . '=' . $this->session_token . '&store_id=' . $store_id, true);
            
            if (VERSION < 2.2) {
                return $this->load->view($this->module_path . '/kbsupercheckout/tabs.tpl', $data);
            }else{
                return $this->load->view($this->module_path . '/kbsupercheckout/tabs', $data);
            }
        }

        public function login() {

		$this->load->language($this->module_path . '/supercheckout');
		$this->document->setTitle($this->language->get('heading_title_main'));
		$this->load->model('setting/setting');

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = 0;
		}
		$this->preventReinstall();
		$classes_array = $this->getClasses();

		if (isset($classes_array['anchor_classes']['supercheckout_classes']))
			$data['anchor_classes'] = $classes_array['anchor_classes']['supercheckout_classes'];
		if (isset($classes_array['anchor_classes_trigger']['supercheckout_trigger']))
			$data['anchor_classes_trigger'] = $classes_array['anchor_classes_trigger']['supercheckout_trigger'];

		// Load settings for supercheckout plugin from database or from default settings
		$this->load->model('setting/setting');
                
		//Check for old settings
		$old_settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
		$old_default_settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
		if (!empty($old_settings)) {
			$new_settings = array();
			if (!isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_settings['supercheckout']['general'] = array_merge($old_settings['supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('supercheckout', $old_settings, $store_id);
			}
		}
		if (!empty($old_default_settings)) {
			$new_settings = array();
			if (isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_default_settings['default_supercheckout']['general'] = array_merge($old_default_settings['default_supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('default_supercheckout', $old_default_settings, $store_id);
			}
		}
		$result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { {
				$this->session->data['success'] = $this->language->get('supercheckout_text_success_login');
				if (isset($this->request->post['supercheckout']['general']['settings']['value'])) {
					$settings = str_replace("amp;", "", urldecode($this->request->post['supercheckout']['general']['settings']['bulk']));
					parse_str($settings, $this->request->post);
				}
                                $old_settings2 = $this->model_setting_setting->getSetting('supercheckout', $store_id);
                                $old_settings2['supercheckout']['step']['google_login'] = $this->request->post['supercheckout']['step']['google_login'];
                                $old_settings2['supercheckout']['step']['facebook_login'] = $this->request->post['supercheckout']['step']['facebook_login'];
				$this->model_setting_setting->editSetting('supercheckout', $old_settings2, $store_id);
				if (!isset($this->request->post['save'])) {
					$this->response->redirect($this->url->link($this->module_path . '/supercheckout/login', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
				else if (!isset($this->session_token)) {
					$this->response->redirect($this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
			}
		}

                if (isset($this->session->data['success'])) {
                    $data['success'] = $this->session->data['success'];
                    $this->session->data['success'] = '';
                } else {
                    $data['success'] = '';
                }

		$data['heading_title'] = $this->language->get('heading_title');
		$data['heading_title_main'] = $this->language->get('heading_title_main');
                $data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');

                //error
                $data['error_form'] = $this->language->get('error_form');
                $data['error_facebook_app_id'] = $this->language->get('error_facebook_app_id');
                $data['error_facebook_secret_key'] = $this->language->get('error_facebook_secret_key');
                $data['error_google_app_id'] = $this->language->get('error_google_app_id');
                $data['error_google_client_id'] = $this->language->get('error_google_client_id');
                $data['error_google_secret_key'] = $this->language->get('error_google_secret_key');
                $data['error_popup_image'] = $this->language->get('error_popup_image');
                
		//Login tab and info
		$data['supercheckout_text_facebook_login'] = $this->language->get('supercheckout_text_facebook_login');
		$data['supercheckout_text_facebook_login_display'] = $this->language->get('supercheckout_text_facebook_login_display');
		$data['supercheckout_text_google_login_display'] = $this->language->get('supercheckout_text_google_login_display');
		$data['supercheckout_text_facebook_app_id'] = $this->language->get('supercheckout_text_facebook_app_id');
		$data['supercheckout_text_facebook_app_secret'] = $this->language->get('supercheckout_text_facebook_app_secret');
		$data['supercheckout_text_google_app_id'] = $this->language->get('supercheckout_text_google_app_id');
		$data['supercheckout_text_google_client_id'] = $this->language->get('supercheckout_text_google_client_id');
		$data['supercheckout_text_google_app_secret'] = $this->language->get('supercheckout_text_google_app_secret');
                $data['supercheckout_text_login'] = $this->language->get('supercheckout_text_login');
                $data['heading_facebook'] = $this->language->get('heading_facebook');
                $data['heading_google'] = $this->language->get('heading_google');
                
		
                //Language
		$data['supercheckout_text_language'] = $this->language->get('supercheckout_text_language');

		//Tooltips
		
		//Login
		$data['facebook_login_display_supercheckout_tooltip'] = $this->language->get('facebook_login_display_supercheckout_tooltip');
		$data['facebook_app_id_supercheckout_tooltip'] = $this->language->get('facebook_app_id_supercheckout_tooltip');
		$data['facebook_secret_supercheckout_tooltip'] = $this->language->get('facebook_secret_supercheckout_tooltip');
		$data['google_login_display_supercheckout_tooltip'] = $this->language->get('google_login_display_supercheckout_tooltip');
		$data['google_app_id_supercheckout_tooltip'] = $this->language->get('google_app_id_supercheckout_tooltip');
		$data['google_client_id_supercheckout_tooltip'] = $this->language->get('google_client_id_supercheckout_tooltip');
		$data['google_secret_supercheckout_tooltip'] = $this->language->get('google_secret_supercheckout_tooltip');

                //errors
                $data['error_empty_field'] = $this->language->get('error_empty_field');
		
		//Buttons
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add_module'] = $this->language->get('button_add_module');
		$data['button_remove'] = $this->language->get('button_remove');

		//Check coupon & voucher status in store
		$data['coupon_status'] = $this->config->get('total_coupon_status');
		$data['voucher_status'] = $this->config->get('total_voucher_status');
		$data['reward_status'] = $this->config->get('total_reward_status');
		$store_setting = $this->model_setting_setting->getSetting('config', $store_id);
		if (isset($store_setting['config_checkout_guest']))
			$data['guest_enable'] = $store_setting['config_checkout_guest'];
		
		if(version_compare(VERSION, '2.1.0.1', '<')) {
                        $this->load->model('sale/customer_group');
                        $results_customer_group = $this->model_sale_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }else{
                        $this->load->model('customer/customer_group');
                        $results_customer_group = $this->model_customer_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }
			
		
		//Right menu cookies check
		if (isset($this->request->cookie['rightMenu'])) {
			$data['rightMenu'] = true;
		}
		else {
			$data['rightMenu'] = false;
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}
		else {
			$data['error_warning'] = '';
		}

		//Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_home'),
		    'href' => $this->url->link('common/home', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => false
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_module'),
		    'href' => $this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('heading_title_main'),
		    'href' => $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);
                
		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_login'),
		    'href' => $this->url->link($this->module_path . '/supercheckout/login', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		//links
		$data['action'] = $this->url->link($this->module_path . '/supercheckout/login', $this->session_token_key .'=' . $this->session_token . '&store_id=' . $store_id, 'SSL');
		$data['action_save_classes'] = $this->url->link($this->module_path . '/supercheckout/saveClasses', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['action_save_classes_trigger'] = $this->url->link($this->module_path . '/supercheckout/saveClassesTrigger', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['route'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['token'] = $this->session_token;
		$data['supercheckout'] = array();

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = $this->config->get('config_store_id');
		}


		if (isset($this->request->post['supercheckout'])) {
			$data['supercheckout'] = $this->request->post['supercheckout'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			$data['supercheckout'] = $settings['supercheckout'];
		}

		$data['supercheckout_modules'] = array();
		if (isset($this->request->post['supercheckout_module'])) {
			$data['supercheckout_modules'] = $this->request->post['supercheckout_module'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$modules = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			if (!empty($modules['supercheckout_module'])) {
				$data['supercheckout_modules'] = $modules['supercheckout_module'];
			}
			else {
				$data['supercheckout_modules'] = array();
			}
		}

		if (empty($settings)) {
			$settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
			$data['settings'] = $settings['default_supercheckout'];
			$data['supercheckout'] = $settings['default_supercheckout'];
		}

		//Store Settings
		$settings['general']['default_email'] = $this->config->get('config_email');
		//$settings['step']['payment_address']['fields']['agree']['information_id'] = $this->config->get('config_account_id');
		//$settings['step']['payment_address']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_account_id');
		$settings['step']['confirm']['fields']['agree']['information_id'] = $this->config->get('config_checkout_id');
		$settings['step']['confirm']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_checkout_id');

		if (!empty($data['supercheckout'])) {
                    $data['supercheckout'] = $this->merge($settings, $data['supercheckout']);
		}else {
                    $data['supercheckout'] = $settings;
		}
		$data['supercheckout']['general']['store_id'] = $store_id;

                $tabs_data['store_id'] = $store_id;
                $tabs_data['active'] = 2;
                $data['tabs'] = $this->load->controller($this->module_path . '/supercheckout/tabs', $tabs_data);
                $data['store_id'] = $store_id;
                $data['current_url'] = html_entity_decode($this->url->link($this->module_path . '/supercheckout/login', $this->session_token_key . '=' . $this->session_token, true));
                $data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key . '=' . $this->session_token . '&type=module&store_id=' . $store_id, true);
                $data['text_default'] = $this->language->get('text_default');
                $data['store_switcher'] = $this->load->controller($this->module_path . '/supercheckout/store_swticher', $data);
		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$this->template = $this->module_path . '/kbsupercheckout/login.tpl';


		//code for opencart2.0

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
                if (VERSION < '2.2.0') {
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/login.tpl', $data));
                }else{
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/login', $data));
                }
		//code for 2.0 ends here
	}
        
        public function payment_address() {

		$this->load->language($this->module_path . '/supercheckout');
		$this->document->setTitle($this->language->get('heading_title_main'));
		$this->load->model('setting/setting');

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = 0;
		}
		$this->preventReinstall();
		$classes_array = $this->getClasses();

		if (isset($classes_array['anchor_classes']['supercheckout_classes']))
			$data['anchor_classes'] = $classes_array['anchor_classes']['supercheckout_classes'];
		if (isset($classes_array['anchor_classes_trigger']['supercheckout_trigger']))
			$data['anchor_classes_trigger'] = $classes_array['anchor_classes_trigger']['supercheckout_trigger'];

		// Load settings for supercheckout plugin from database or from default settings
		$this->load->model('setting/setting');

		//Check for old settings
		$old_settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
		$old_default_settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
		if (!empty($old_settings)) {
			$new_settings = array();
			if (!isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_settings['supercheckout']['general'] = array_merge($old_settings['supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('supercheckout', $old_settings, $store_id);
			}
		}
		if (!empty($old_default_settings)) {
			$new_settings = array();
			if (isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_default_settings['default_supercheckout']['general'] = array_merge($old_default_settings['default_supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('default_supercheckout', $old_default_settings, $store_id);
			}
		}
		$result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { {
				$this->session->data['success'] = $this->language->get('supercheckout_text_success_PayAdd');
				if (isset($this->request->post['supercheckout']['general']['settings']['value'])) {
					$settings = str_replace("amp;", "", urldecode($this->request->post['supercheckout']['general']['settings']['bulk']));
					parse_str($settings, $this->request->post);
				}
				$old_settings2 = $this->model_setting_setting->getSetting('supercheckout', $store_id);
                                $old_settings2['supercheckout']['step']['payment_address']['fields'] = $this->request->post['supercheckout']['step']['payment_address']['fields'];
                                $old_settings2['supercheckout']['option']['guest']['payment_address'] = $this->request->post['supercheckout']['option']['guest']['payment_address'];
                                $old_settings2['supercheckout']['option']['logged']['payment_address'] = $this->request->post['supercheckout']['option']['logged']['payment_address'];
				$this->model_setting_setting->editSetting('supercheckout', $old_settings2, $store_id);
				if (!isset($this->request->post['save'])) {
					$this->response->redirect($this->url->link($this->module_path . '/supercheckout/payment_address', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
				else if (!isset($this->session_token)) {
					$this->response->redirect($this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
			}
		}

                if (isset($this->session->data['success'])) {
                    $data['success'] = $this->session->data['success'];
                    $this->session->data['success'] = '';
                } else {
                    $data['success'] = '';
                }
	

		$data['heading_title'] = $this->language->get('heading_title');
		$data['heading_title_main'] = $this->language->get('heading_title_main');
                $data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['settings_checked'] = $this->language->get('settings_checked');

		// Words
		$data['settings_display'] = $this->language->get('settings_display');
		$data['settings_require'] = $this->language->get('settings_require');
		$data['settings_enable'] = $this->language->get('settings_enable');
		$data['supercheckout_text_enabled'] = $this->language->get('supercheckout_text_enabled');
		$data['supercheckout_text_disabled'] = $this->language->get('supercheckout_text_disabled');

		$data['supercheckout_entry_firstname'] = $this->language->get('supercheckout_entry_firstname');
		$data['supercheckout_entry_lastname'] = $this->language->get('supercheckout_entry_lastname');
		$data['supercheckout_entry_telephone'] = $this->language->get('supercheckout_entry_telephone');
		$data['supercheckout_entry_company'] = $this->language->get('supercheckout_entry_company');
		$data['supercheckout_entry_company_id'] = $this->language->get('supercheckout_entry_company_id');
		$data['supercheckout_entry_tax_id'] = $this->language->get('supercheckout_entry_tax_id');
		$data['supercheckout_entry_address_1'] = $this->language->get('supercheckout_entry_address_1');
		$data['supercheckout_entry_address_2'] = $this->language->get('supercheckout_entry_address_2');
		$data['supercheckout_entry_postcode'] = $this->language->get('supercheckout_entry_postcode');
		$data['supercheckout_entry_city'] = $this->language->get('supercheckout_entry_city');
		$data['supercheckout_entry_country'] = $this->language->get('supercheckout_entry_country');
		$data['supercheckout_entry_zone'] = $this->language->get('supercheckout_entry_zone');
		$data['supercheckout_entry_shipping'] = $this->language->get('supercheckout_entry_shipping');

		//Payment address
		$data['supercheckout_text_payment_address'] = $this->language->get('supercheckout_text_payment_address');
		$data['supercheckout_text_guest_customer'] = $this->language->get('supercheckout_text_guest_customer');
		$data['supercheckout_text_registrating_customer'] = $this->language->get('supercheckout_text_registrating_customer');
		$data['supercheckout_text_logged_in_customer'] = $this->language->get('supercheckout_text_logged_in_customer');

                //Language
		$data['supercheckout_text_language'] = $this->language->get('supercheckout_text_language');

		//Tooltips
		//General
		$data['general_enable_newsletter_tooltip'] = $this->language->get('general_enable_newsletter_tooltip');
		$data['general_enable_supercheckout_tooltip'] = $this->language->get('general_enable_supercheckout_tooltip');
		$data['custom_style_supercheckout_tooltip'] = $this->language->get('custom_style_supercheckout_tooltip');
		$data['general_guestenable_supercheckout_tooltip'] = $this->language->get('general_guestenable_supercheckout_tooltip');
		$data['general_guest_manual_supercheckout_tooltip'] = $this->language->get('general_guest_manual_supercheckout_tooltip');
		$data['general_default_supercheckout_tooltip'] = $this->language->get('general_default_supercheckout_tooltip');
		$data['step_login_option_supercheckout_tooltip'] = $this->language->get('step_login_option_supercheckout_tooltip');
		$data['guest_enable_disabled_supercheckout_tooltip'] = $this->language->get('guest_enable_disabled_supercheckout_tooltip');
		$data['field_disabled_supercheckout_tooltip'] = $this->language->get('field_disabled_supercheckout_tooltip');
		$data['text_warning'] = $this->language->get('text_warning');

		//Buttons
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add_module'] = $this->language->get('button_add_module');
		$data['button_remove'] = $this->language->get('button_remove');

		$store_setting = $this->model_setting_setting->getSetting('config', $store_id);
		if (isset($store_setting['config_checkout_guest']))
			$data['guest_enable'] = $store_setting['config_checkout_guest'];
		
		if(version_compare(VERSION, '2.1.0.1', '<')) {
                        $this->load->model('sale/customer_group');
                        $results_customer_group = $this->model_sale_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }else{
                        $this->load->model('customer/customer_group');
                        $results_customer_group = $this->model_customer_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}
		else {
			$data['error_warning'] = '';
		}

		//Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_home'),
		    'href' => $this->url->link('common/home', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => false
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_module'),
		    'href' => $this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('heading_title_main'),
		    'href' => $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);


		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_payment_address'),
		    'href' => $this->url->link($this->module_path . '/supercheckout/payment_address', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		//links
		$data['action'] = $this->url->link($this->module_path . '/supercheckout/payment_address', $this->session_token_key .'=' . $this->session_token . '&store_id=' . $store_id, 'SSL');
		$data['action_save_classes'] = $this->url->link($this->module_path . '/supercheckout/saveClasses', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['action_save_classes_trigger'] = $this->url->link($this->module_path . '/supercheckout/saveClassesTrigger', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['route'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['token'] = $this->session_token;
		$data['supercheckout'] = array();

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = $this->config->get('config_store_id');
		}


		if (isset($this->request->post['supercheckout'])) {
			$data['supercheckout'] = $this->request->post['supercheckout'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			$data['supercheckout'] = $settings['supercheckout'];
		}

		$data['supercheckout_modules'] = array();
		if (isset($this->request->post['supercheckout_module'])) {
			$data['supercheckout_modules'] = $this->request->post['supercheckout_module'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$modules = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			if (!empty($modules['supercheckout_module'])) {
				$data['supercheckout_modules'] = $modules['supercheckout_module'];
			}
			else {
				$data['supercheckout_modules'] = array();
			}
		}

		if (empty($settings)) {
			$settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
			$data['settings'] = $settings['default_supercheckout'];
			$data['supercheckout'] = $settings['default_supercheckout'];
		}

		//Store Settings
		$settings['general']['default_email'] = $this->config->get('config_email');
		//$settings['step']['payment_address']['fields']['agree']['information_id'] = $this->config->get('config_account_id');
		//$settings['step']['payment_address']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_account_id');
		$settings['step']['confirm']['fields']['agree']['information_id'] = $this->config->get('config_checkout_id');
		$settings['step']['confirm']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_checkout_id');

		if (!empty($data['supercheckout'])) {
                    $data['supercheckout'] = $this->merge($settings, $data['supercheckout']);
		}else {
                    $data['supercheckout'] = $settings;
		}
		$data['supercheckout']['general']['store_id'] = $store_id;

                if(version_compare(VERSION, '2.1.0.1', '<')) {
                    $this->load->model('sale/custom_field');
                    $custom_fields = $this->model_sale_custom_field->getCustomFields();
                }else{
                    $this->load->model('customer/custom_field');
                    $custom_fields = $this->model_customer_custom_field->getCustomFields();
                }
                
                foreach ($custom_fields as $key => $value) {
                    if($value['location'] == 'address'){
                        if (VERSION <= 2.1) {
                            $this->load->model('sale/custom_field');
                            $custom_field_name = $this->model_sale_custom_field->getCustomFieldDescriptions($value['custom_field_id']);
                        }else{
                            $this->load->model('customer/custom_field');
                            $custom_field_name = $this->model_customer_custom_field->getCustomFieldDescriptions($value['custom_field_id']);
                        }
                        
                        $data['custom_fields_status'][$value['custom_field_id']] = $value['status'];
                        if($value['status'] == 1 && isset($data['supercheckout']['option']['guest']['payment_address']['fields'][$value['custom_field_id']]['display'])){
                            $custom_data1['guest'][$value['custom_field_id']]['display'] = $data['supercheckout']['option']['guest']['payment_address']['fields'][$value['custom_field_id']]['display'];
                        } else {
                            $custom_data1['guest'][$value['custom_field_id']]['display'] = $value['status'];
                        }
                        if($value['status'] == 1 && isset($data['supercheckout']['option']['logged']['payment_address']['fields'][$value['custom_field_id']]['display'])){
                            $custom_data1['logged'][$value['custom_field_id']]['display'] = $data['supercheckout']['option']['logged']['payment_address']['fields'][$value['custom_field_id']]['display'];
                        } else {
                            $custom_data1['logged'][$value['custom_field_id']]['display'] = $value['status'];
                        }
                        $custom_data1['guest'][$value['custom_field_id']]['require'] = '1';
                        $custom_data1['logged'][$value['custom_field_id']]['require'] = '1';
                        
                        if(isset($data['supercheckout']['option']['guest']['payment_address']['fields'][$value['custom_field_id']]['require'])){
                            $custom_data1['guest'][$value['custom_field_id']]['require'] = $data['supercheckout']['option']['guest']['payment_address']['fields'][$value['custom_field_id']]['require'];
                        }
                        if(isset($data['supercheckout']['option']['logged']['payment_address']['fields'][$value['custom_field_id']]['require'])){
                            $custom_data1['logged'][$value['custom_field_id']]['require'] = $data['supercheckout']['option']['logged']['payment_address']['fields'][$value['custom_field_id']]['require'];
                        }
                        
                        $custom_data2[$value['custom_field_id']]['title'] = $custom_field_name[$this->config->get('config_language_id')]['name'];
                        $custom_data2[$value['custom_field_id']]['id'] = $value['custom_field_id'];
                        $custom_data2[$value['custom_field_id']]['sort_order'] = $value['sort_order'];
                        if(isset($data['supercheckout']['step']['payment_address']['fields'][$value['custom_field_id']]['sort_order'])){
                            $custom_data2[$value['custom_field_id']]['sort_order'] = $data['supercheckout']['step']['payment_address']['fields'][$value['custom_field_id']]['sort_order'];
                        }
                    }
                }
                if(isset($custom_data1)){
                    $data['customer_group_field_array'] = array();
                    foreach ($custom_data1['guest'] as $key => $value) {
                        $data['supercheckout']['option']['guest']['payment_address']['fields'][$key] = $value; 
                    }
                    foreach ($custom_data1['logged'] as $key => $value) {
                        $data['supercheckout']['option']['logged']['payment_address']['fields'][$key] = $value; 
                    }
                    foreach ($custom_data2 as $key => $value) {
                        $data['custom_group_field_array'][] = $value['id'];
                        $data['supercheckout']['step']['payment_address']['fields'][$key] = $value;
                    }
                }
//                var_dump(in_array('1', $data['custom_group_field_array']));die;

                $tabs_data['store_id'] = $store_id;
                $tabs_data['active'] = 3;
                $data['tabs'] = $this->load->controller($this->module_path . '/supercheckout/tabs', $tabs_data);
                $data['store_id'] = $store_id;
                $data['current_url'] = html_entity_decode($this->url->link($this->module_path . '/supercheckout/payment_address', $this->session_token_key . '=' . $this->session_token, true));
                $data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key . '=' . $this->session_token . '&type=module&store_id=' . $store_id, true);
                $data['text_default'] = $this->language->get('text_default');
                $data['store_switcher'] = $this->load->controller($this->module_path . '/supercheckout/store_swticher', $data);
		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$this->template = $this->module_path . '/kbsupercheckout/payment_address.tpl';


		//code for opencart2.0

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
                if (VERSION < '2.2.0') {
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/payment_address.tpl', $data));
                }else{
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/payment_address', $data));
                }
	}
        
        public function shipping_address() {

		$this->load->language($this->module_path . '/supercheckout');
		$this->document->setTitle($this->language->get('heading_title_main'));
		$this->load->model('setting/setting');

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = 0;
		}
		$this->preventReinstall();
		$classes_array = $this->getClasses();

		if (isset($classes_array['anchor_classes']['supercheckout_classes']))
			$data['anchor_classes'] = $classes_array['anchor_classes']['supercheckout_classes'];
		if (isset($classes_array['anchor_classes_trigger']['supercheckout_trigger']))
			$data['anchor_classes_trigger'] = $classes_array['anchor_classes_trigger']['supercheckout_trigger'];

		// Load settings for supercheckout plugin from database or from default settings
		$this->load->model('setting/setting');

		//Check for old settings
		$old_settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
		$old_default_settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
		if (!empty($old_settings)) {
			$new_settings = array();
			if (!isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_settings['supercheckout']['general'] = array_merge($old_settings['supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('supercheckout', $old_settings, $store_id);
			}
		}
		if (!empty($old_default_settings)) {
			$new_settings = array();
			if (isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_default_settings['default_supercheckout']['general'] = array_merge($old_default_settings['default_supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('default_supercheckout', $old_default_settings, $store_id);
			}
		}
		$result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { {
				$this->session->data['success'] = $this->language->get('supercheckout_text_success_shipAdd');
				if (isset($this->request->post['supercheckout']['general']['settings']['value'])) {
					$settings = str_replace("amp;", "", urldecode($this->request->post['supercheckout']['general']['settings']['bulk']));
					parse_str($settings, $this->request->post);
				}
				$old_settings2 = $this->model_setting_setting->getSetting('supercheckout', $store_id);
                                $old_settings2['supercheckout']['step']['shipping_address']['fields'] = $this->request->post['supercheckout']['step']['shipping_address']['fields'];
                                $old_settings2['supercheckout']['option']['guest']['shipping_address'] = $this->request->post['supercheckout']['option']['guest']['shipping_address'];
                                $old_settings2['supercheckout']['option']['logged']['shipping_address'] = $this->request->post['supercheckout']['option']['logged']['shipping_address'];
				$this->model_setting_setting->editSetting('supercheckout', $old_settings2, $store_id);
				if (!isset($this->request->post['save'])) {
					$this->response->redirect($this->url->link($this->module_path . '/supercheckout/shipping_address', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
				else if (!isset($this->session_token)) {
					$this->response->redirect($this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
			}
		}

                if (isset($this->session->data['success'])) {
                    $data['success'] = $this->session->data['success'];
                    $this->session->data['success'] = '';
                } else {
                    $data['success'] = '';
                }

		$data['heading_title'] = $this->language->get('heading_title');
		$data['heading_title_main'] = $this->language->get('heading_title_main');

		// Words
		$data['settings_display'] = $this->language->get('settings_display');
		$data['settings_require'] = $this->language->get('settings_require');
		$data['settings_enable'] = $this->language->get('settings_enable');
		$data['supercheckout_text_enabled'] = $this->language->get('supercheckout_text_enabled');
		$data['supercheckout_text_disabled'] = $this->language->get('supercheckout_text_disabled');

		$data['supercheckout_entry_firstname'] = $this->language->get('supercheckout_entry_firstname');
		$data['supercheckout_entry_lastname'] = $this->language->get('supercheckout_entry_lastname');
		$data['supercheckout_entry_telephone'] = $this->language->get('supercheckout_entry_telephone');
		$data['supercheckout_entry_company'] = $this->language->get('supercheckout_entry_company');
		$data['supercheckout_entry_company_id'] = $this->language->get('supercheckout_entry_company_id');
		$data['supercheckout_entry_tax_id'] = $this->language->get('supercheckout_entry_tax_id');
		$data['supercheckout_entry_address_1'] = $this->language->get('supercheckout_entry_address_1');
		$data['supercheckout_entry_address_2'] = $this->language->get('supercheckout_entry_address_2');
		$data['supercheckout_entry_postcode'] = $this->language->get('supercheckout_entry_postcode');
		$data['supercheckout_entry_city'] = $this->language->get('supercheckout_entry_city');
		$data['supercheckout_entry_country'] = $this->language->get('supercheckout_entry_country');
		$data['supercheckout_entry_zone'] = $this->language->get('supercheckout_entry_zone');
		$data['supercheckout_entry_shipping'] = $this->language->get('supercheckout_entry_shipping');
                $data['text_warning'] = $this->language->get('text_warning');

		//Payment address
		$data['supercheckout_text_guest_customer'] = $this->language->get('supercheckout_text_guest_customer');
		$data['supercheckout_text_registrating_customer'] = $this->language->get('supercheckout_text_registrating_customer');
		$data['supercheckout_text_logged_in_customer'] = $this->language->get('supercheckout_text_logged_in_customer');

		//Shipping address
		$data['supercheckout_text_shipping_address'] = $this->language->get('supercheckout_text_shipping_address');


                //Language
		$data['supercheckout_text_language'] = $this->language->get('supercheckout_text_language');

		//Tooltips
		//General
		$data['general_enable_newsletter_tooltip'] = $this->language->get('general_enable_newsletter_tooltip');
		$data['general_enable_supercheckout_tooltip'] = $this->language->get('general_enable_supercheckout_tooltip');
		$data['custom_style_supercheckout_tooltip'] = $this->language->get('custom_style_supercheckout_tooltip');
		$data['general_guestenable_supercheckout_tooltip'] = $this->language->get('general_guestenable_supercheckout_tooltip');
		$data['general_guest_manual_supercheckout_tooltip'] = $this->language->get('general_guest_manual_supercheckout_tooltip');
		$data['general_default_supercheckout_tooltip'] = $this->language->get('general_default_supercheckout_tooltip');
		$data['step_login_option_supercheckout_tooltip'] = $this->language->get('step_login_option_supercheckout_tooltip');
		$data['guest_enable_disabled_supercheckout_tooltip'] = $this->language->get('guest_enable_disabled_supercheckout_tooltip');
		$data['field_disabled_supercheckout_tooltip'] = $this->language->get('field_disabled_supercheckout_tooltip');

		//Buttons
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add_module'] = $this->language->get('button_add_module');
		$data['button_remove'] = $this->language->get('button_remove');

		$store_setting = $this->model_setting_setting->getSetting('config', $store_id);
		if (isset($store_setting['config_checkout_guest']))
			$data['guest_enable'] = $store_setting['config_checkout_guest'];
		
		if(version_compare(VERSION, '2.1.0.1', '<')) {
                        $this->load->model('sale/customer_group');
                        $results_customer_group = $this->model_sale_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }else{
                        $this->load->model('customer/customer_group');
                        $results_customer_group = $this->model_customer_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}
		else {
			$data['error_warning'] = '';
		}

		//Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_home'),
		    'href' => $this->url->link('common/home', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => false
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_module'),
		    'href' => $this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('heading_title_main'),
		    'href' => $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);
                
		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_shipping_address'),
		    'href' => $this->url->link($this->module_path . '/supercheckout/shipping_address', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		//links
		$data['action'] = $this->url->link($this->module_path . '/supercheckout/shipping_address', $this->session_token_key .'=' . $this->session_token . '&store_id=' . $store_id, 'SSL');
		$data['action_save_classes'] = $this->url->link($this->module_path . '/supercheckout/saveClasses', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['action_save_classes_trigger'] = $this->url->link($this->module_path . '/supercheckout/saveClassesTrigger', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['route'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['token'] = $this->session_token;
		$data['supercheckout'] = array();

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = $this->config->get('config_store_id');
		}


		if (isset($this->request->post['supercheckout'])) {
			$data['supercheckout'] = $this->request->post['supercheckout'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			$data['supercheckout'] = $settings['supercheckout'];
		}

		$data['supercheckout_modules'] = array();
		if (isset($this->request->post['supercheckout_module'])) {
			$data['supercheckout_modules'] = $this->request->post['supercheckout_module'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$modules = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			if (!empty($modules['supercheckout_module'])) {
				$data['supercheckout_modules'] = $modules['supercheckout_module'];
			}
			else {
				$data['supercheckout_modules'] = array();
			}
		}

		if (empty($settings)) {
			$settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
			$data['settings'] = $settings['default_supercheckout'];
			$data['supercheckout'] = $settings['default_supercheckout'];
		}

		//Store Settings
		$settings['general']['default_email'] = $this->config->get('config_email');
		//$settings['step']['payment_address']['fields']['agree']['information_id'] = $this->config->get('config_account_id');
		//$settings['step']['payment_address']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_account_id');
		$settings['step']['confirm']['fields']['agree']['information_id'] = $this->config->get('config_checkout_id');
		$settings['step']['confirm']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_checkout_id');

		if (!empty($data['supercheckout'])) {
                    $data['supercheckout'] = $this->merge($settings, $data['supercheckout']);
		}else {
                    $data['supercheckout'] = $settings;
		}
		$data['supercheckout']['general']['store_id'] = $store_id;

                if(version_compare(VERSION, '2.1.0.1', '<')) {
                    $this->load->model('sale/custom_field');
                    $custom_fields = $this->model_sale_custom_field->getCustomFields();
                }else{
                    $this->load->model('customer/custom_field');
                    $custom_fields = $this->model_customer_custom_field->getCustomFields();
                }
                
                foreach ($custom_fields as $key => $value) {
                    if($value['location'] == 'address'){
                        if (VERSION <= 2.1) {
                            $this->load->model('sale/custom_field');
                            $custom_field_name = $this->model_sale_custom_field->getCustomFieldDescriptions($value['custom_field_id']);
                        }else{
                            $this->load->model('customer/custom_field');
                            $custom_field_name = $this->model_customer_custom_field->getCustomFieldDescriptions($value['custom_field_id']);
                        }
                        
                        $data['custom_fields_status'][$value['custom_field_id']] = $value['status'];
                        if($value['status'] == 1 && isset($data['supercheckout']['option']['guest']['shipping_address']['fields'][$value['custom_field_id']]['display'])){
                            $custom_data1['guest'][$value['custom_field_id']]['display'] = $data['supercheckout']['option']['guest']['shipping_address']['fields'][$value['custom_field_id']]['display'];
                        } else {
                            $custom_data1['guest'][$value['custom_field_id']]['display'] = $value['status'];
                        }
                        if($value['status'] == 1 && isset($data['supercheckout']['option']['logged']['shipping_address']['fields'][$value['custom_field_id']]['display'])){
                            $custom_data1['logged'][$value['custom_field_id']]['display'] = $data['supercheckout']['option']['logged']['shipping_address']['fields'][$value['custom_field_id']]['display'];
                        } else {
                            $custom_data1['logged'][$value['custom_field_id']]['display'] = $value['status'];
                        }
                        $custom_data1['guest'][$value['custom_field_id']]['require'] = '1';
                        $custom_data1['logged'][$value['custom_field_id']]['require'] = '1';
                        
                        if(isset($data['supercheckout']['option']['guest']['shipping_address']['fields'][$value['custom_field_id']]['require'])){
                            $custom_data1['guest'][$value['custom_field_id']]['require'] = $data['supercheckout']['option']['guest']['shipping_address']['fields'][$value['custom_field_id']]['require'];
                        }
                        if(isset($data['supercheckout']['option']['logged']['shipping_address']['fields'][$value['custom_field_id']]['require'])){
                            $custom_data1['logged'][$value['custom_field_id']]['require'] = $data['supercheckout']['option']['logged']['shipping_address']['fields'][$value['custom_field_id']]['require'];
                        }
                        
                        $custom_data2[$value['custom_field_id']]['title'] = $custom_field_name[$this->config->get('config_language_id')]['name'];
                        $custom_data2[$value['custom_field_id']]['id'] = $value['custom_field_id'];
                        $custom_data2[$value['custom_field_id']]['sort_order'] = $value['sort_order'];
                        if(isset($data['supercheckout']['step']['shipping_address']['fields'][$value['custom_field_id']]['sort_order'])){
                            $custom_data2[$value['custom_field_id']]['sort_order'] = $data['supercheckout']['step']['shipping_address']['fields'][$value['custom_field_id']]['sort_order'];
                        }
                    }
                }
                if(isset($custom_data1)){
                    $data['customer_group_field_array'] = array();
                    foreach ($custom_data1['guest'] as $key => $value) {
                        $data['supercheckout']['option']['guest']['shipping_address']['fields'][$key] = $value; 
                    }
                    foreach ($custom_data1['logged'] as $key => $value) {
                        $data['supercheckout']['option']['logged']['shipping_address']['fields'][$key] = $value; 
                    }
                    foreach ($custom_data2 as $key => $value) {
                        $data['custom_group_field_array'][] = $value['id'];
                        $data['supercheckout']['step']['shipping_address']['fields'][$key] = $value;
                    }
                }
                
                $tabs_data['store_id'] = $store_id;
                $tabs_data['active'] = 4;
                $data['tabs'] = $this->load->controller($this->module_path . '/supercheckout/tabs', $tabs_data);
                $data['store_id'] = $store_id;
                $data['current_url'] = html_entity_decode($this->url->link($this->module_path . '/supercheckout/shipping_address', $this->session_token_key . '=' . $this->session_token, true));
                $data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key . '=' . $this->session_token . '&type=module&store_id=' . $store_id, true);
                $data['text_default'] = $this->language->get('text_default');
                $data['store_switcher'] = $this->load->controller($this->module_path . '/supercheckout/store_swticher', $data);
		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$this->template = $this->module_path . '/kbsupercheckout/shipping_address.tpl';


		//code for opencart2.0

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
                if (VERSION < '2.2.0') {
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/shipping_address.tpl', $data));
                }else{
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/shipping_address', $data));
                }
	}
        
        public function shipping_method() {

		$this->load->language($this->module_path . '/supercheckout');
		$this->document->setTitle($this->language->get('heading_title_main'));
		$this->load->model('setting/setting');

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = 0;
		}
		$this->preventReinstall();
		$classes_array = $this->getClasses();

		if (isset($classes_array['anchor_classes']['supercheckout_classes']))
			$data['anchor_classes'] = $classes_array['anchor_classes']['supercheckout_classes'];
		if (isset($classes_array['anchor_classes_trigger']['supercheckout_trigger']))
			$data['anchor_classes_trigger'] = $classes_array['anchor_classes_trigger']['supercheckout_trigger'];

		// Load settings for supercheckout plugin from database or from default settings
		$this->load->model('setting/setting');

		//Check for old settings
		$old_settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
		$old_default_settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
		if (!empty($old_settings)) {
			$new_settings = array();
			if (!isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_settings['supercheckout']['general'] = array_merge($old_settings['supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('supercheckout', $old_settings, $store_id);
			}
		}
		if (!empty($old_default_settings)) {
			$new_settings = array();
			if (isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_default_settings['default_supercheckout']['general'] = array_merge($old_default_settings['default_supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('default_supercheckout', $old_default_settings, $store_id);
			}
		}
		$result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { {
				$this->session->data['success'] = $this->language->get('supercheckout_text_success_shipMethod');
				if (isset($this->request->post['supercheckout']['general']['settings']['value'])) {
					$settings = str_replace("amp;", "", urldecode($this->request->post['supercheckout']['general']['settings']['bulk']));
					parse_str($settings, $this->request->post);
				}
                                
                                $old_settings2 = $this->model_setting_setting->getSetting('supercheckout', $store_id);
                                $this->request->post['supercheckout']['step']['shipping_method']['three-column'] = $old_settings2['supercheckout']['step']['shipping_method']['three-column'];
                                $this->request->post['supercheckout']['step']['shipping_method']['two-column'] = $old_settings2['supercheckout']['step']['shipping_method']['two-column'];
                                $this->request->post['supercheckout']['step']['shipping_method']['one-column'] = $old_settings2['supercheckout']['step']['shipping_method']['one-column'];
                                $this->request->post['supercheckout']['step']['shipping_method']['available'] = $old_settings2['supercheckout']['step']['shipping_method']['available'];
                                $old_settings2['supercheckout']['step']['shipping_method'] = $this->request->post['supercheckout']['step']['shipping_method'];
                                $old_settings2['supercheckout']['shipping_logo']['default_option'] = $this->request->post['supercheckout']['shipping_logo']['default_option'];
                                $old_settings2['supercheckout']['step']['shipping_method']['logo'] = $this->request->post['supercheckout']['step']['shipping_method']['logo'];
                                $this->model_setting_setting->editSetting('supercheckout', $old_settings2, $store_id);
				if (!isset($this->request->post['save'])) {
					$this->response->redirect($this->url->link($this->module_path . '/supercheckout/shipping_method', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
				else if (!isset($this->session_token)) {
					$this->response->redirect($this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
			}
		}

                if (isset($this->session->data['success'])) {
                    $data['success'] = $this->session->data['success'];
                    $this->session->data['success'] = '';
                } else {
                    $data['success'] = '';
                }
		
		$data['heading_title'] = $this->language->get('heading_title');
		$data['heading_title_main'] = $this->language->get('heading_title_main');
                $data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
                $data['text_img_hint'] = $this->language->get('text_img_hint');

		// Words
		$data['settings_display'] = $this->language->get('settings_display');
		$data['settings_require'] = $this->language->get('settings_require');
		$data['settings_enable'] = $this->language->get('settings_enable');
		$data['supercheckout_text_enabled'] = $this->language->get('supercheckout_text_enabled');
		$data['supercheckout_text_disabled'] = $this->language->get('supercheckout_text_disabled');

		$data['supercheckout_entry_title'] = $this->language->get('supercheckout_entry_title');
		$data['supercheckout_entry_logo'] = $this->language->get('supercheckout_entry_logo');
		$data['supercheckout_entry_add_logo'] = $this->language->get('supercheckout_entry_add_logo');
		

		//Shipping method
		$data['supercheckout_text_shipping_method'] = $this->language->get('supercheckout_text_shipping_method');
		$data['supercheckout_text_shipping_method_display_options'] = $this->language->get('supercheckout_text_shipping_method_display_options');
		$data['supercheckout_text_shipping_method_display_title'] = $this->language->get('supercheckout_text_shipping_method_display_title');
		$data['supercheckout_text_shipping_method_default_option'] = $this->language->get('supercheckout_text_shipping_method_default_option');
		$data['supercheckout_text_shipping_method_logo_display_options'] = $this->language->get('supercheckout_text_shipping_method_logo_display_options');

		//Payment method
		$data['supercheckout_text_only'] = $this->language->get('supercheckout_text_only');
		$data['supercheckout_text_with_image'] = $this->language->get('supercheckout_text_with_image');
		$data['supercheckout_image_only'] = $this->language->get('supercheckout_image_only');

		//Confirm
		$data['supercheckout_text_confirm'] = $this->language->get('supercheckout_text_confirm');
		$data['supercheckout_text_confirm_display'] = $this->language->get('supercheckout_text_confirm_display');
		$data['supercheckout_text_agree'] = $this->language->get('supercheckout_text_agree');
		$data['supercheckout_text_comments'] = $this->language->get('supercheckout_text_comments');

                //Language
		$data['supercheckout_text_language'] = $this->language->get('supercheckout_text_language');

		//Tooltips
		//General
		$data['custom_style_supercheckout_tooltip'] = $this->language->get('custom_style_supercheckout_tooltip');
		$data['step_login_option_supercheckout_tooltip'] = $this->language->get('step_login_option_supercheckout_tooltip');
		$data['guest_enable_disabled_supercheckout_tooltip'] = $this->language->get('guest_enable_disabled_supercheckout_tooltip');
		$data['field_disabled_supercheckout_tooltip'] = $this->language->get('field_disabled_supercheckout_tooltip');

		//Shipping Method
		$data['shipping_method_display_options_supercheckout_tooltip'] = $this->language->get('shipping_method_display_options_supercheckout_tooltip');
		$data['shipping_method_display_title_supercheckout_tooltip'] = $this->language->get('shipping_method_display_title_supercheckout_tooltip');
		$data['shipping_method_default_option_supercheckout_tooltip'] = $this->language->get('shipping_method_default_option_supercheckout_tooltip');
		$data['shipping_method_logo_display_options_tooltip'] = $this->language->get('shipping_method_logo_display_options_tooltip');
                $data['supercheckout_entry_shipping_method_title_tooltip'] = $this->language->get('supercheckout_entry_shipping_method_title_tooltip');
		$data['supercheckout_entry_shipping_method_logo_tooltip'] = $this->language->get('supercheckout_entry_shipping_method_logo_tooltip');
                
                //errors
                $data['error_empty_field'] = $this->language->get('error_empty_field');
                
		//Buttons
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add_module'] = $this->language->get('button_add_module');
		$data['button_remove'] = $this->language->get('button_remove');

		$store_setting = $this->model_setting_setting->getSetting('config', $store_id);
		if (isset($store_setting['config_checkout_guest']))
			$data['guest_enable'] = $store_setting['config_checkout_guest'];
		
		if(version_compare(VERSION, '2.1.0.1', '<')) {
                        $this->load->model('sale/customer_group');
                        $results_customer_group = $this->model_sale_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }else{
                        $this->load->model('customer/customer_group');
                        $results_customer_group = $this->model_customer_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }
		
		//Right menu cookies check
		if (isset($this->request->cookie['rightMenu'])) {
			$data['rightMenu'] = true;
		}
		else {
			$data['rightMenu'] = false;
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}
		else {
			$data['error_warning'] = '';
		}

		//Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_home'),
		    'href' => $this->url->link('common/home', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => false
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_module'),
		    'href' => $this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('heading_title_main'),
		    'href' => $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);
                
		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_shipping_method'),
		    'href' => $this->url->link($this->module_path . '/supercheckout/shipping_method', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		//links
		$data['action'] = $this->url->link($this->module_path . '/supercheckout/shipping_method', $this->session_token_key .'=' . $this->session_token . '&store_id=' . $store_id, 'SSL');
		$data['action_save_classes'] = $this->url->link($this->module_path . '/supercheckout/saveClasses', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['action_save_classes_trigger'] = $this->url->link($this->module_path . '/supercheckout/saveClassesTrigger', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['route'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['token'] = $this->session_token;
		$data['supercheckout'] = array();

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = $this->config->get('config_store_id');
		}


		if (isset($this->request->post['supercheckout'])) {
			$data['supercheckout'] = $this->request->post['supercheckout'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			$data['supercheckout'] = $settings['supercheckout'];
		}
		$data['supercheckout_modules'] = array();
		if (isset($this->request->post['supercheckout_module'])) {
			$data['supercheckout_modules'] = $this->request->post['supercheckout_module'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$modules = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			if (!empty($modules['supercheckout_module'])) {
				$data['supercheckout_modules'] = $modules['supercheckout_module'];
			}
			else {
				$data['supercheckout_modules'] = array();
			}
		}
		if (empty($settings)) {
			$settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
			$data['settings'] = $settings['default_supercheckout'];
			$data['supercheckout'] = $settings['default_supercheckout'];
		}

		//Store Settings
		$settings['general']['default_email'] = $this->config->get('config_email');
		//$settings['step']['payment_address']['fields']['agree']['information_id'] = $this->config->get('config_account_id');
		//$settings['step']['payment_address']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_account_id');
		$settings['step']['confirm']['fields']['agree']['information_id'] = $this->config->get('config_checkout_id');
		$settings['step']['confirm']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_checkout_id');

		if (!empty($data['supercheckout'])) {
                    $data['supercheckout'] = $this->merge($settings, $data['supercheckout']);
		}else {
                    $data['supercheckout'] = $settings;
		}
		$data['supercheckout']['general']['store_id'] = $store_id;

		//Get Shipping methods
		$this->load->model('setting/extension');
		$data['shipping_methods'] = array();
		$shipping_methods = $this->model_setting_extension->getInstalled('shipping');
		foreach ($shipping_methods as $shipping) {
			if ($this->config->get('shipping_'.$shipping . '_status')) {
				$this->load->language('extension/shipping/' . $shipping);
				$data['shipping_methods'][] = array(
				    'code' => $shipping,
				    'title' => $this->language->get('heading_title')
				);
			}
		}
                foreach ($data['shipping_methods'] as $key => $value) {
                    if(isset($data['supercheckout']['step']['shipping_method']['logo'][$value['code'].'.'.$value['code']]) && $data['supercheckout']['step']['shipping_method']['logo'][$value['code'].'.'.$value['code']] != ''){
                        $data['shipping_logo'][$value['code'].'.'.$value['code']] = $data['supercheckout']['step']['shipping_method']['logo'][$value['code'].'.'.$value['code']];
                    }else{
                        if(!file_exists(DIR_IMAGE.'kbsupercheckout/'.$value['code'].'.'.$value['code'].'.png')){
                            $data['shipping_logo'][$value['code'].'.'.$value['code']] = 'kbsupercheckout/shipping_logo.png';
                        }else{
                            $data['shipping_logo'][$value['code'].'.'.$value['code']] = 'kbsupercheckout/'.$value['code'].'.'.$value['code'].'.png';
                        }
                    }
                    
                }
                foreach ($data['shipping_logo'] as $key => $value) {
                    $data['supercheckout']['step']['shipping_method']['logo'][$key] = $value;
                }

                $data['image_dir_url'] = HTTP_CATALOG.'image/';
                $tabs_data['store_id'] = $store_id;
                $tabs_data['active'] = 5;
                $data['tabs'] = $this->load->controller($this->module_path . '/supercheckout/tabs', $tabs_data);
                $data['store_id'] = $store_id;
                $data['current_url'] = html_entity_decode($this->url->link($this->module_path . '/supercheckout/shipping_method', $this->session_token_key . '=' . $this->session_token, true));
                $data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key . '=' . $this->session_token . '&type=module&store_id=' . $store_id, true);
                $data['text_default'] = $this->language->get('text_default');
                $data['store_switcher'] = $this->load->controller($this->module_path . '/supercheckout/store_swticher', $data);
		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$this->template = $this->module_path . '/kbsupercheckout/shipping_method.tpl';

		//code for opencart2.0

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
                if (VERSION < '2.2.0') {
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/shipping_method.tpl', $data));
                }else{
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/shipping_method', $data));
                }
		
	}
        
        public function ship2pay() {

		$this->load->language($this->module_path . '/supercheckout');
		$this->document->setTitle($this->language->get('heading_title_main'));
		$this->load->model('setting/setting');

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = 0;
		}
		$this->preventReinstall();
		$classes_array = $this->getClasses();

		if (isset($classes_array['anchor_classes']['supercheckout_classes']))
			$data['anchor_classes'] = $classes_array['anchor_classes']['supercheckout_classes'];
		if (isset($classes_array['anchor_classes_trigger']['supercheckout_trigger']))
			$data['anchor_classes_trigger'] = $classes_array['anchor_classes_trigger']['supercheckout_trigger'];

		// Load settings for supercheckout plugin from database or from default settings
		$this->load->model('setting/setting');

		//Check for old settings
		$old_settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
		$old_default_settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
		if (!empty($old_settings)) {
			$new_settings = array();
			if (!isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_settings['supercheckout']['general'] = array_merge($old_settings['supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('supercheckout', $old_settings, $store_id);
			}
		}
		if (!empty($old_default_settings)) {
			$new_settings = array();
			if (isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_default_settings['default_supercheckout']['general'] = array_merge($old_default_settings['default_supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('default_supercheckout', $old_default_settings, $store_id);
			}
		}
		$result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { {
				$this->session->data['success'] = $this->language->get('supercheckout_text_success_ship2pay');
				if (isset($this->request->post['supercheckout']['general']['settings']['value'])) {
					$settings = str_replace("amp;", "", urldecode($this->request->post['supercheckout']['general']['settings']['bulk']));
					parse_str($settings, $this->request->post);
				}
				$old_settings2 = $this->model_setting_setting->getSetting('supercheckout', $store_id);
                                $old_settings2['supercheckout']['step']['shipping_method']['available'] = $this->request->post['supercheckout']['step']['shipping_method']['available'];
				$this->model_setting_setting->editSetting('supercheckout', $old_settings2, $store_id);
				if (!isset($this->request->post['save'])) {
					$this->response->redirect($this->url->link($this->module_path . '/supercheckout/ship2pay', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
				else if (!isset($this->session_token)) {
					$this->response->redirect($this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
			}
		}

                if (isset($this->session->data['success'])) {
                    $data['success'] = $this->session->data['success'];
                    $this->session->data['success'] = '';
                } else {
                    $data['success'] = '';
                }
		//Adding required scripts/jquery for supercheckout page

		$data['heading_title'] = $this->language->get('heading_title');
		$data['supercheckout_text_ship2pay'] = $this->language->get('supercheckout_text_ship2pay');

                //Language
		$data['supercheckout_text_language'] = $this->language->get('supercheckout_text_language');

		//errors
                $data['error_empty_field'] = $this->language->get('error_empty_field');
		
		//Buttons
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add_module'] = $this->language->get('button_add_module');
		$data['button_remove'] = $this->language->get('button_remove');

		$store_setting = $this->model_setting_setting->getSetting('config', $store_id);
		if (isset($store_setting['config_checkout_guest']))
			$data['guest_enable'] = $store_setting['config_checkout_guest'];
		
		//Right menu cookies check
		if (isset($this->request->cookie['rightMenu'])) {
			$data['rightMenu'] = true;
		}
		else {
			$data['rightMenu'] = false;
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}
		else {
			$data['error_warning'] = '';
		}

		//Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_home'),
		    'href' => $this->url->link('common/home', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => false
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_module'),
		    'href' => $this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('heading_title_main'),
		    'href' => $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);
                
		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_ship2pay'),
		    'href' => $this->url->link($this->module_path . '/supercheckout/ship2pay', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		//links
		$data['action'] = $this->url->link($this->module_path . '/supercheckout/ship2pay', $this->session_token_key .'=' . $this->session_token . '&store_id=' . $store_id, 'SSL');
		$data['action_save_classes'] = $this->url->link($this->module_path . '/supercheckout/saveClasses', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['action_save_classes_trigger'] = $this->url->link($this->module_path . '/supercheckout/saveClassesTrigger', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['route'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['token'] = $this->session_token;
		$data['supercheckout'] = array();

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = $this->config->get('config_store_id');
		}


		if (isset($this->request->post['supercheckout'])) {
			$data['supercheckout'] = $this->request->post['supercheckout'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			$data['supercheckout'] = $settings['supercheckout'];
		}
		$data['supercheckout_modules'] = array();
		if (isset($this->request->post['supercheckout_module'])) {
			$data['supercheckout_modules'] = $this->request->post['supercheckout_module'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$modules = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			if (!empty($modules['supercheckout_module'])) {
				$data['supercheckout_modules'] = $modules['supercheckout_module'];
			}
			else {
				$data['supercheckout_modules'] = array();
			}
		}

		if (empty($settings)) {
			$settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
			$data['settings'] = $settings['default_supercheckout'];
			$data['supercheckout'] = $settings['default_supercheckout'];
		}
                
		//Store Settings
		$settings['general']['default_email'] = $this->config->get('config_email');
		//$settings['step']['payment_address']['fields']['agree']['information_id'] = $this->config->get('config_account_id');
		//$settings['step']['payment_address']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_account_id');
		$settings['step']['confirm']['fields']['agree']['information_id'] = $this->config->get('config_checkout_id');
		$settings['step']['confirm']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_checkout_id');

		if (!empty($data['supercheckout'])) {
                    $data['supercheckout'] = $this->merge($settings, $data['supercheckout']);
		}else {
                    $data['supercheckout'] = $settings;
		}
		$data['supercheckout']['general']['store_id'] = $store_id;

		//Get Shipping methods
		$this->load->model('setting/extension');
		$data['shipping_methods'] = array();
		$shipping_methods = $this->model_setting_extension->getInstalled('shipping');
		foreach ($shipping_methods as $shipping) {
			if ($this->config->get('shipping_'.$shipping . '_status')) {
				$this->load->language('extension/shipping/' . $shipping);
				$data['shipping_methods'][] = array(
				    'code' => $shipping,
				    'title' => $this->language->get('heading_title')
				);
			}
		}

                //Get Payment methods
		$this->load->model('setting/extension');
		$data['payment_methods'] = array();
		$payment_methods = $this->model_setting_extension->getInstalled('payment');
		foreach ($payment_methods as $payment) {
			if ($this->config->get('payment_'.$payment . '_status')) {
				$this->load->language('extension/payment/' . $payment);
				$data['payment_methods'][] = array(
				    'code' => $payment,
				    'title' => $this->language->get('heading_title')
				);
			}
		}
                $tabs_data['store_id'] = $store_id;
                $tabs_data['active'] = 6;
                $data['tabs'] = $this->load->controller($this->module_path . '/supercheckout/tabs', $tabs_data);
                $data['store_id'] = $store_id;
                $data['current_url'] = html_entity_decode($this->url->link($this->module_path . '/supercheckout/ship2pay', $this->session_token_key . '=' . $this->session_token, true));
                $data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key . '=' . $this->session_token . '&type=module&store_id=' . $store_id, true);
                $data['text_default'] = $this->language->get('text_default');
                $data['store_switcher'] = $this->load->controller($this->module_path . '/supercheckout/store_swticher', $data);
		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$this->template = $this->module_path . '/kbsupercheckout/ship2pay.tpl';


		//code for opencart2.0

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
                if (VERSION < '2.2.0') {
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/ship2pay.tpl', $data));
                }else{
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/ship2pay', $data));
                }
		//code for 2.0 ends here
	}
        
        public function payment_method() {

		$this->load->language($this->module_path . '/supercheckout');
		$this->document->setTitle($this->language->get('heading_title_main'));
		$this->load->model('setting/setting');

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = 0;
		}
		$this->preventReinstall();
		$classes_array = $this->getClasses();

		if (isset($classes_array['anchor_classes']['supercheckout_classes']))
			$data['anchor_classes'] = $classes_array['anchor_classes']['supercheckout_classes'];
		if (isset($classes_array['anchor_classes_trigger']['supercheckout_trigger']))
			$data['anchor_classes_trigger'] = $classes_array['anchor_classes_trigger']['supercheckout_trigger'];

		// Load settings for supercheckout plugin from database or from default settings
		$this->load->model('setting/setting');

		//Check for old settings
		$old_settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
		$old_default_settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
		if (!empty($old_settings)) {
			$new_settings = array();
			if (!isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_settings['supercheckout']['general'] = array_merge($old_settings['supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('supercheckout', $old_settings, $store_id);
			}
		}
		if (!empty($old_default_settings)) {
			$new_settings = array();
			if (isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_default_settings['default_supercheckout']['general'] = array_merge($old_default_settings['default_supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('default_supercheckout', $old_default_settings, $store_id);
			}
		}
		$result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { {
				$this->session->data['success'] = $this->language->get('supercheckout_text_success_PayMethod');
				if (isset($this->request->post['supercheckout']['general']['settings']['value'])) {
					$settings = str_replace("amp;", "", urldecode($this->request->post['supercheckout']['general']['settings']['bulk']));
					parse_str($settings, $this->request->post);
				}
                                $old_settings2 = $this->model_setting_setting->getSetting('supercheckout', $store_id);
                                $this->request->post['supercheckout']['step']['payment_method']['three-column'] = $old_settings2['supercheckout']['step']['payment_method']['three-column'];
                                $this->request->post['supercheckout']['step']['payment_method']['two-column'] = $old_settings2['supercheckout']['step']['payment_method']['two-column'];
                                $this->request->post['supercheckout']['step']['payment_method']['one-column'] = $old_settings2['supercheckout']['step']['payment_method']['one-column'];
                                $old_settings2['supercheckout']['step']['payment_method'] = $this->request->post['supercheckout']['step']['payment_method'];
                                $old_settings2['supercheckout']['payment_logo']['default_option'] = $this->request->post['supercheckout']['payment_logo']['default_option'];
                                $old_settings2['supercheckout']['step']['payment_method']['logo'] = $this->request->post['supercheckout']['step']['payment_method']['logo'];
                                $this->model_setting_setting->editSetting('supercheckout', $old_settings2, $store_id);
				if (!isset($this->request->post['save'])) {
					$this->response->redirect($this->url->link($this->module_path . '/supercheckout/payment_method', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
				else if (!isset($this->session_token)) {
					$this->response->redirect($this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
			}
		}

                if (isset($this->session->data['success'])) {
                    $data['success'] = $this->session->data['success'];
                    $this->session->data['success'] = '';
                } else {
                    $data['success'] = '';
                }
		
		$data['heading_title'] = $this->language->get('heading_title');
		$data['heading_title_main'] = $this->language->get('heading_title_main');
                $data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_img_hint'] = $this->language->get('text_img_hint');

		// Words
		$data['settings_display'] = $this->language->get('settings_display');
		$data['settings_require'] = $this->language->get('settings_require');
		$data['settings_enable'] = $this->language->get('settings_enable');
		$data['supercheckout_text_enabled'] = $this->language->get('supercheckout_text_enabled');
		$data['supercheckout_text_disabled'] = $this->language->get('supercheckout_text_disabled');

		//General Settings tab & info
		$data['supercheckout_text_custom_style'] = $this->language->get('supercheckout_text_custom_style');
                $data['supercheckout_entry_title'] = $this->language->get('supercheckout_entry_title');
		$data['supercheckout_entry_logo'] = $this->language->get('supercheckout_entry_logo');
		$data['supercheckout_entry_add_logo'] = $this->language->get('supercheckout_entry_add_logo');
                
		$data['supercheckout_text_general_default'] = $this->language->get('supercheckout_text_general_default');
		$data['supercheckout_text_register'] = $this->language->get('supercheckout_text_register');
		$data['supercheckout_text_guest'] = $this->language->get('supercheckout_text_guest');

		$data['supercheckout_text_step_login_option'] = $this->language->get('supercheckout_text_step_login_option');
		$data['supercheckout_text_login'] = $this->language->get('supercheckout_text_login');
		$data['step_login_option_register_display'] = $this->language->get('supercheckout_text_register');
		$data['step_login_option_guest_display'] = $this->language->get('supercheckout_text_guest');
               
		//Payment method
		$data['supercheckout_text_payment_method'] = $this->language->get('supercheckout_text_payment_method');
		$data['supercheckout_text_payment_method_display_options'] = $this->language->get('supercheckout_text_payment_method_display_options');
		$data['supercheckout_text_payment_method_logo_display_options'] = $this->language->get('supercheckout_text_payment_method_logo_display_options');
		$data['supercheckout_text_only'] = $this->language->get('supercheckout_text_only');
		$data['supercheckout_text_with_image'] = $this->language->get('supercheckout_text_with_image');
		$data['supercheckout_image_only'] = $this->language->get('supercheckout_image_only');
		$data['supercheckout_text_payment_method_default_option'] = $this->language->get('supercheckout_text_payment_method_default_option');

		//Confirm
		$data['supercheckout_text_confirm'] = $this->language->get('supercheckout_text_confirm');
		$data['supercheckout_text_confirm_display'] = $this->language->get('supercheckout_text_confirm_display');
		$data['supercheckout_text_agree'] = $this->language->get('supercheckout_text_agree');
		$data['supercheckout_text_comments'] = $this->language->get('supercheckout_text_comments');

                //Language
		$data['supercheckout_text_language'] = $this->language->get('supercheckout_text_language');

		//Tooltips
		//General
		$data['custom_style_supercheckout_tooltip'] = $this->language->get('custom_style_supercheckout_tooltip');
		$data['guest_enable_disabled_supercheckout_tooltip'] = $this->language->get('guest_enable_disabled_supercheckout_tooltip');
		$data['field_disabled_supercheckout_tooltip'] = $this->language->get('field_disabled_supercheckout_tooltip');

		//Payment Method
		$data['payment_method_display_options_supercheckout_tooltip'] = $this->language->get('payment_method_display_options_supercheckout_tooltip');
		$data['payment_method_logo_display_options_supercheckout_tooltip'] = $this->language->get('payment_method_logo_display_options_supercheckout_tooltip');
		$data['payment_method_default_option_supercheckout_tooltip'] = $this->language->get('payment_method_default_option_supercheckout_tooltip');
		$data['supercheckout_entry_payment_method_title_tooltip'] = $this->language->get('supercheckout_entry_payment_method_title_tooltip');
		$data['supercheckout_entry_payment_method_logo_tooltip'] = $this->language->get('supercheckout_entry_payment_method_logo_tooltip');

                //errors
                $data['error_empty_field'] = $this->language->get('error_empty_field');
		//Buttons
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add_module'] = $this->language->get('button_add_module');
		$data['button_remove'] = $this->language->get('button_remove');

		$store_setting = $this->model_setting_setting->getSetting('config', $store_id);
		if (isset($store_setting['config_checkout_guest']))
			$data['guest_enable'] = $store_setting['config_checkout_guest'];
		
		if(version_compare(VERSION, '2.1.0.1', '<')) {
                        $this->load->model('sale/customer_group');
                        $results_customer_group = $this->model_sale_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }else{
                        $this->load->model('customer/customer_group');
                        $results_customer_group = $this->model_customer_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}
		else {
			$data['error_warning'] = '';
		}

		//Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_home'),
		    'href' => $this->url->link('common/home', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => false
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_module'),
		    'href' => $this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('heading_title_main'),
		    'href' => $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);
                
		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_payment_method'),
		    'href' => $this->url->link($this->module_path . '/supercheckout/payment_method', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		//links
		$data['action'] = $this->url->link($this->module_path . '/supercheckout/payment_method', $this->session_token_key .'=' . $this->session_token . '&store_id=' . $store_id, 'SSL');
		$data['action_save_classes'] = $this->url->link($this->module_path . '/supercheckout/saveClasses', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['action_save_classes_trigger'] = $this->url->link($this->module_path . '/supercheckout/saveClassesTrigger', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['route'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['token'] = $this->session_token;
		$data['supercheckout'] = array();

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = $this->config->get('config_store_id');
		}


		if (isset($this->request->post['supercheckout'])) {
			$data['supercheckout'] = $this->request->post['supercheckout'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			$data['supercheckout'] = $settings['supercheckout'];
		}
		$data['supercheckout_modules'] = array();
		if (isset($this->request->post['supercheckout_module'])) {
			$data['supercheckout_modules'] = $this->request->post['supercheckout_module'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$modules = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			if (!empty($modules['supercheckout_module'])) {
				$data['supercheckout_modules'] = $modules['supercheckout_module'];
			}
			else {
				$data['supercheckout_modules'] = array();
			}
		}

		if (empty($settings)) {
			$settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
			$data['settings'] = $settings['default_supercheckout'];
			$data['supercheckout'] = $settings['default_supercheckout'];
		}

		//Store Settings
		$settings['general']['default_email'] = $this->config->get('config_email');
		//$settings['step']['payment_address']['fields']['agree']['information_id'] = $this->config->get('config_account_id');
		//$settings['step']['payment_address']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_account_id');
		$settings['step']['confirm']['fields']['agree']['information_id'] = $this->config->get('config_checkout_id');
		$settings['step']['confirm']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_checkout_id');

		if (!empty($data['supercheckout'])) {
                    $data['supercheckout'] = $this->merge($settings, $data['supercheckout']);
		}else {
                    $data['supercheckout'] = $settings;
		}
//                var_dump($data['supercheckout']['step']['payment_method']);die;
		$data['supercheckout']['general']['store_id'] = $store_id;

		//Get Payment methods
		$this->load->model('setting/extension');
		$data['payment_methods'] = array();
		$payment_methods = $this->model_setting_extension->getInstalled('payment');
		foreach ($payment_methods as $payment) {
			if ($this->config->get('payment_'.$payment . '_status')) {
				$this->load->language('extension/payment/' . $payment);
				$data['payment_methods'][] = array(
				    'code' => $payment,
				    'title' => $this->language->get('heading_title')
				);
			}
		}

                $data['image_dir_url'] = HTTP_CATALOG.'image/';
                $tabs_data['store_id'] = $store_id;
                $tabs_data['active'] = 7;
                $data['tabs'] = $this->load->controller($this->module_path . '/supercheckout/tabs', $tabs_data);
                $data['store_id'] = $store_id;
                $data['current_url'] = html_entity_decode($this->url->link($this->module_path . '/supercheckout/payment_method', $this->session_token_key . '=' . $this->session_token, true));
                $data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key . '=' . $this->session_token . '&type=module&store_id=' . $store_id, true);
                $data['text_default'] = $this->language->get('text_default');
                $data['store_switcher'] = $this->load->controller($this->module_path . '/supercheckout/store_swticher', $data);
		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$this->template = $this->module_path . '/kbsupercheckout/payment_method.tpl';


		//code for opencart2.0

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
                if (VERSION < '2.2.0') {
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/payment_method.tpl', $data));
                }else{
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/payment_method', $data));
                }
		//code for 2.0 ends here
	}
        
        public function confirm() {

		$this->load->language($this->module_path . '/supercheckout');
		$this->document->setTitle($this->language->get('heading_title_main'));
		$this->load->model('setting/setting');

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = 0;
		}
		$this->preventReinstall();
		$classes_array = $this->getClasses();

		if (isset($classes_array['anchor_classes']['supercheckout_classes']))
			$data['anchor_classes'] = $classes_array['anchor_classes']['supercheckout_classes'];
		if (isset($classes_array['anchor_classes_trigger']['supercheckout_trigger']))
			$data['anchor_classes_trigger'] = $classes_array['anchor_classes_trigger']['supercheckout_trigger'];

		// Load settings for supercheckout plugin from database or from default settings
		$this->load->model('setting/setting');
                
		//Check for old settings
		$old_settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
		$old_default_settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
		if (!empty($old_settings)) {
			$new_settings = array();
			if (!isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_settings['supercheckout']['general'] = array_merge($old_settings['supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('supercheckout', $old_settings, $store_id);
			}
		}
		if (!empty($old_default_settings)) {
			$new_settings = array();
			if (isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_default_settings['default_supercheckout']['general'] = array_merge($old_default_settings['default_supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('default_supercheckout', $old_default_settings, $store_id);
			}
		}
		$result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { {
				$this->session->data['success'] = $this->language->get('supercheckout_text_success_cart');
				if (isset($this->request->post['supercheckout']['general']['settings']['value'])) {
					$settings = str_replace("amp;", "", urldecode($this->request->post['supercheckout']['general']['settings']['bulk']));
					parse_str($settings, $this->request->post);
				}
				$old_settings2 = $this->model_setting_setting->getSetting('supercheckout', $store_id);
                                $old_settings2['supercheckout']['step']['cart']['image_width'] = $this->request->post['supercheckout']['step']['cart']['image_width'];
                                $old_settings2['supercheckout']['step']['cart']['image_height'] = $this->request->post['supercheckout']['step']['cart']['image_height'];
                                $old_settings2['supercheckout']['step']['confirm']['fields'] = $this->request->post['supercheckout']['step']['confirm']['fields'];
                                $old_settings2['supercheckout']['option']['guest']['cart'] = $this->request->post['supercheckout']['option']['guest']['cart'];
                                $old_settings2['supercheckout']['option']['logged']['cart'] = $this->request->post['supercheckout']['option']['logged']['cart'];
                                $old_settings2['supercheckout']['option']['guest']['confirm'] = $this->request->post['supercheckout']['option']['guest']['confirm'];
                                $old_settings2['supercheckout']['option']['logged']['confirm'] = $this->request->post['supercheckout']['option']['logged']['confirm'];
				$this->model_setting_setting->editSetting('supercheckout', $old_settings2, $store_id);
				if (!isset($this->request->post['save'])) {
					$this->response->redirect($this->url->link($this->module_path . '/supercheckout/confirm', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
				else if (!isset($this->session_token)) {
					$this->response->redirect($this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
			}
		}

                if (isset($this->session->data['success'])) {
                    $data['success'] = $this->session->data['success'];
                    $this->session->data['success'] = '';
                } else {
                    $data['success'] = '';
                }

		$data['heading_title'] = $this->language->get('heading_title');
		$data['heading_title_main'] = $this->language->get('heading_title_main');
		$data['error_positive_number'] = $this->language->get('positive_number');
		$data['error_positive_number'] = $this->language->get('positive_number');
		$data['error_number'] = $this->language->get('error_number');
		$data['error_empty_field'] = $this->language->get('error_empty_field');

		// Words
		$data['settings_display'] = $this->language->get('settings_display');
		$data['settings_require'] = $this->language->get('settings_require');
		$data['settings_enable'] = $this->language->get('settings_enable');
		$data['supercheckout_text_enabled'] = $this->language->get('supercheckout_text_enabled');
		$data['supercheckout_text_disabled'] = $this->language->get('supercheckout_text_disabled');

		//General Settings tab & info
		$data['supercheckout_text_newsletter_enable'] = $this->language->get('supercheckout_text_newsletter_enable');
		$data['supercheckout_text_general'] = $this->language->get('supercheckout_text_general');
		$data['supercheckout_text_general_enable'] = $this->language->get('supercheckout_text_general_enable');
		$data['supercheckout_text_general_guestenable'] = $this->language->get('supercheckout_text_general_guestenable');
		$data['supercheckout_text_general_guest_manual'] = $this->language->get('supercheckout_text_general_guest_manual');
		$data['supercheckout_text_custom_style'] = $this->language->get('supercheckout_text_custom_style');

		$data['supercheckout_text_general_default'] = $this->language->get('supercheckout_text_general_default');
		$data['supercheckout_text_register'] = $this->language->get('supercheckout_text_register');
		$data['supercheckout_text_guest'] = $this->language->get('supercheckout_text_guest');

		$data['supercheckout_text_step_login_option'] = $this->language->get('supercheckout_text_step_login_option');
		$data['supercheckout_text_login'] = $this->language->get('supercheckout_text_login');
		$data['step_login_option_register_display'] = $this->language->get('supercheckout_text_register');
		$data['step_login_option_guest_display'] = $this->language->get('supercheckout_text_guest');
                
		//Cart		
		$data['text_show'] = $this->language->get('text_show');
		$data['text_hide'] = $this->language->get('text_hide');
		$data['supercheckout_text_cart'] = $this->language->get('supercheckout_text_cart');
		$data['supercheckout_text_warning'] = $this->language->get('supercheckout_text_warning');
		$data['supercheckout_text_applicable'] = $this->language->get('supercheckout_text_applicable');
		$data['supercheckout_text_image_size'] = $this->language->get('supercheckout_text_image_size');
		$data['supercheckout_text_cart_display'] = $this->language->get('supercheckout_text_cart_display');
		$data['field_name_title']['supercheckout_text_cart_columns_image'] = $this->language->get('supercheckout_text_cart_columns_image');
		$data['field_name_title']['supercheckout_text_cart_columns_name'] = $this->language->get('supercheckout_text_cart_columns_name');
		$data['field_name_title']['supercheckout_text_cart_columns_model'] = $this->language->get('supercheckout_text_cart_columns_model');
		$data['field_name_title']['supercheckout_text_cart_columns_quantity'] = $this->language->get('supercheckout_text_cart_columns_quantity');
		$data['field_name_title']['supercheckout_text_cart_columns_price'] = $this->language->get('supercheckout_text_cart_columns_price');
		$data['field_name_title']['supercheckout_text_cart_columns_total'] = $this->language->get('supercheckout_text_cart_columns_total');
		$data['supercheckout_text_cart_option_coupon'] = $this->language->get('supercheckout_text_cart_option_coupon');
		$data['supercheckout_text_cart_option_voucher'] = $this->language->get('supercheckout_text_cart_option_voucher');
		$data['supercheckout_text_cart_option_reward'] = $this->language->get('supercheckout_text_cart_option_reward');

		//Confirm
		$data['supercheckout_text_confirm'] = $this->language->get('supercheckout_text_confirm');
		$data['supercheckout_text_confirm_display'] = $this->language->get('supercheckout_text_confirm_display');
		$data['supercheckout_text_agree'] = $this->language->get('supercheckout_text_agree');
		$data['supercheckout_text_comments'] = $this->language->get('supercheckout_text_comments');

                //Language
		$data['supercheckout_text_language'] = $this->language->get('supercheckout_text_language');

		//Tooltips
		//General
		$data['general_default_supercheckout_tooltip'] = $this->language->get('general_default_supercheckout_tooltip');
		$data['step_login_option_supercheckout_tooltip'] = $this->language->get('step_login_option_supercheckout_tooltip');
		$data['guest_enable_disabled_supercheckout_tooltip'] = $this->language->get('guest_enable_disabled_supercheckout_tooltip');
		$data['field_disabled_supercheckout_tooltip'] = $this->language->get('field_disabled_supercheckout_tooltip');

		//Cart
		$data['image_size_supercheckout_tooltip'] = $this->language->get('image_size_supercheckout_tooltip');
		$data['cart_display_supercheckout_tooltip'] = $this->language->get('cart_display_supercheckout_tooltip');
		$data['cart_option_coupon_supercheckout_tooltip'] = $this->language->get('cart_option_coupon_supercheckout_tooltip');
		$data['cart_option_reward_supercheckout_tooltip'] = $this->language->get('cart_option_reward_supercheckout_tooltip');
		$data['cart_option_voucher_supercheckout_tooltip'] = $this->language->get('cart_option_voucher_supercheckout_tooltip');
		$data['cart_option_coupon_disabled_supercheckout_tooltip'] = $this->language->get('cart_option_coupon_disabled_supercheckout_tooltip');
		$data['cart_option_reward_disabled_supercheckout_tooltip'] = $this->language->get('cart_option_reward_disabled_supercheckout_tooltip');
		$data['cart_option_reward_applicable_supercheckout_tooltip'] = $this->language->get('cart_option_reward_applicable_supercheckout_tooltip');
		$data['cart_option_voucher_disabled_supercheckout_tooltip'] = $this->language->get('cart_option_voucher_disabled_supercheckout_tooltip');
                $data['supercheckout_text_guest_customer'] = $this->language->get('supercheckout_text_guest_customer');
		$data['supercheckout_text_registrating_customer'] = $this->language->get('supercheckout_text_registrating_customer');
		$data['supercheckout_text_logged_in_customer'] = $this->language->get('supercheckout_text_logged_in_customer');
                
		//Buttons
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add_module'] = $this->language->get('button_add_module');
		$data['button_remove'] = $this->language->get('button_remove');

		//Check coupon & voucher status in store
		$data['coupon_status'] = $this->config->get('total_coupon_status');
		$data['voucher_status'] = $this->config->get('total_voucher_status');
		$data['reward_status'] = $this->config->get('total_reward_status');
		$store_setting = $this->model_setting_setting->getSetting('config', $store_id);
		if (isset($store_setting['config_checkout_guest']))
			$data['guest_enable'] = $store_setting['config_checkout_guest'];
		
		if(version_compare(VERSION, '2.1.0.1', '<')) {
                        $this->load->model('sale/customer_group');
                        $results_customer_group = $this->model_sale_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }else{
                        $this->load->model('customer/customer_group');
                        $results_customer_group = $this->model_customer_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }
		if ($store_setting['config_checkout_id']) {
			$this->load->model('catalog/information');
			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));
			if ($information_info) {
				$data['text_agree'] = 1;
			}
			else {
				$data['text_agree'] = 0;
			}
		}
		else {
			$data['text_agree'] = 0;
		}
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}
		else {
			$data['error_warning'] = '';
		}

		//Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_home'),
		    'href' => $this->url->link('common/home', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => false
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_module'),
		    'href' => $this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('heading_title_main'),
		    'href' => $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);
                
		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_confirm'),
		    'href' => $this->url->link($this->module_path . '/supercheckout/confirm', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		//links
		$data['action'] = $this->url->link($this->module_path . '/supercheckout/confirm', $this->session_token_key .'=' . $this->session_token . '&store_id=' . $store_id, 'SSL');
		$data['action_save_classes'] = $this->url->link($this->module_path . '/supercheckout/saveClasses', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['action_save_classes_trigger'] = $this->url->link($this->module_path . '/supercheckout/saveClassesTrigger', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['route'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['token'] = $this->session_token;
		$data['supercheckout'] = array();

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = $this->config->get('config_store_id');
		}


		if (isset($this->request->post['supercheckout'])) {
			$data['supercheckout'] = $this->request->post['supercheckout'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			$data['supercheckout'] = $settings['supercheckout'];
		}

		$data['supercheckout_modules'] = array();
		if (isset($this->request->post['supercheckout_module'])) {
			$data['supercheckout_modules'] = $this->request->post['supercheckout_module'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$modules = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			if (!empty($modules['supercheckout_module'])) {
				$data['supercheckout_modules'] = $modules['supercheckout_module'];
			}
			else {
				$data['supercheckout_modules'] = array();
			}
		}

		if (empty($settings)) {
			$settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
			$data['settings'] = $settings['default_supercheckout'];
			$data['supercheckout'] = $settings['default_supercheckout'];
		}

		//Store Settings
		$settings['general']['default_email'] = $this->config->get('config_email');
		//$settings['step']['payment_address']['fields']['agree']['information_id'] = $this->config->get('config_account_id');
		//$settings['step']['payment_address']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_account_id');
		$settings['step']['confirm']['fields']['agree']['information_id'] = $this->config->get('config_checkout_id');
		$settings['step']['confirm']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_checkout_id');

		if (!empty($data['supercheckout'])) {
                    $data['supercheckout'] = $this->merge($settings, $data['supercheckout']);
		}else {
                    $data['supercheckout'] = $settings;
		}
		$data['supercheckout']['general']['store_id'] = $store_id;

                $tabs_data['store_id'] = $store_id;
                $tabs_data['active'] = 8;
                $data['tabs'] = $this->load->controller($this->module_path . '/supercheckout/tabs', $tabs_data);
                $data['store_id'] = $store_id;
                $data['current_url'] = html_entity_decode($this->url->link($this->module_path . '/supercheckout/confirm', $this->session_token_key . '=' . $this->session_token, true));
                $data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key . '=' . $this->session_token . '&type=module&store_id=' . $store_id, true);
                $data['text_default'] = $this->language->get('text_default');
                $data['store_switcher'] = $this->load->controller($this->module_path . '/supercheckout/store_swticher', $data);
		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$this->template = $this->module_path . '/kbsupercheckout/confirm.tpl';


		//code for opencart2.0

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
                if (VERSION < '2.2.0') {
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/confirm.tpl', $data));
                }else{
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/confirm', $data));
                }
		//code for 2.0 ends here
	}
        
        public function design_checkout() {

		$this->load->language($this->module_path . '/supercheckout');
		$this->document->setTitle($this->language->get('heading_title_main'));
		$this->load->model('setting/setting');

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = 0;
		}
		$this->preventReinstall();
		$classes_array = $this->getClasses();

		if (isset($classes_array['anchor_classes']['supercheckout_classes']))
			$data['anchor_classes'] = $classes_array['anchor_classes']['supercheckout_classes'];
		if (isset($classes_array['anchor_classes_trigger']['supercheckout_trigger']))
			$data['anchor_classes_trigger'] = $classes_array['anchor_classes_trigger']['supercheckout_trigger'];

		// Load settings for supercheckout plugin from database or from default settings
		$this->load->model('setting/setting');

		//Check for old settings
		$old_settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
		$old_default_settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
		if (!empty($old_settings)) {
			$new_settings = array();
			if (!isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_settings['supercheckout']['general'] = array_merge($old_settings['supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('supercheckout', $old_settings, $store_id);
			}
		}
		if (!empty($old_default_settings)) {
			$new_settings = array();
			if (isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_default_settings['default_supercheckout']['general'] = array_merge($old_default_settings['default_supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('default_supercheckout', $old_default_settings, $store_id);
			}
		}
		$result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { {
				$this->session->data['success'] = $this->language->get('supercheckout_text_success_design');
				if (isset($this->request->post['supercheckout']['general']['settings']['value'])) {
					$settings = str_replace("amp;", "", urldecode($this->request->post['supercheckout']['general']['settings']['bulk']));
					parse_str($settings, $this->request->post);
				}
				$old_settings2 = $this->model_setting_setting->getSetting('supercheckout', $store_id);
                                $this->request->post['supercheckout']['step']['login']['option'] = $old_settings2['supercheckout']['step']['login']['option'];
                                $old_settings2['supercheckout']['general']['layout'] = $this->request->post['supercheckout']['general']['layout'];
                                $old_settings2['supercheckout']['general']['column_width'] = $this->request->post['supercheckout']['general']['column_width'];
                                $old_settings2['supercheckout']['general']['checkout_style'] = $this->request->post['supercheckout']['general']['checkout_style'];
                                $old_settings2['supercheckout']['step']['html'] = $this->request->post['supercheckout']['step']['html'];
                                $old_settings2['supercheckout']['step']['html_value'] = $this->request->post['supercheckout']['step']['html_value'];
                                $old_settings2['supercheckout']['step']['modal_value'] = $this->request->post['supercheckout']['step']['modal_value'];
                                $old_settings2['supercheckout']['step']['login'] = $this->request->post['supercheckout']['step']['login'];
                                $old_settings2['supercheckout']['step']['payment_method']['three-column'] = $this->request->post['supercheckout']['step']['payment_method']['three-column'];
                                $old_settings2['supercheckout']['step']['payment_method']['two-column'] = $this->request->post['supercheckout']['step']['payment_method']['two-column'];
                                $old_settings2['supercheckout']['step']['payment_method']['one-column'] = $this->request->post['supercheckout']['step']['payment_method']['one-column'];
                                $old_settings2['supercheckout']['step']['shipping_method']['three-column'] = $this->request->post['supercheckout']['step']['shipping_method']['three-column'];
                                $old_settings2['supercheckout']['step']['shipping_method']['two-column'] = $this->request->post['supercheckout']['step']['shipping_method']['two-column'];
                                $old_settings2['supercheckout']['step']['shipping_method']['one-column'] = $this->request->post['supercheckout']['step']['shipping_method']['one-column'];
                                $old_settings2['supercheckout']['step']['payment_address']['three-column'] = $this->request->post['supercheckout']['step']['payment_address']['three-column'];
                                $old_settings2['supercheckout']['step']['payment_address']['two-column'] = $this->request->post['supercheckout']['step']['payment_address']['two-column'];
                                $old_settings2['supercheckout']['step']['payment_address']['one-column'] = $this->request->post['supercheckout']['step']['payment_address']['one-column'];
                                $old_settings2['supercheckout']['step']['shipping_address']['three-column'] = $this->request->post['supercheckout']['step']['shipping_address']['three-column'];
                                $old_settings2['supercheckout']['step']['shipping_address']['two-column'] = $this->request->post['supercheckout']['step']['shipping_address']['two-column'];
                                $old_settings2['supercheckout']['step']['shipping_address']['one-column'] = $this->request->post['supercheckout']['step']['shipping_address']['one-column'];
                                $old_settings2['supercheckout']['step']['cart']['three-column'] = $this->request->post['supercheckout']['step']['cart']['three-column'];
                                $old_settings2['supercheckout']['step']['cart']['two-column'] = $this->request->post['supercheckout']['step']['cart']['two-column'];
                                $old_settings2['supercheckout']['step']['cart']['one-column'] = $this->request->post['supercheckout']['step']['cart']['one-column'];
                                $old_settings2['supercheckout']['step']['confirm']['three-column'] = $this->request->post['supercheckout']['step']['confirm']['three-column'];
                                $old_settings2['supercheckout']['step']['confirm']['two-column'] = $this->request->post['supercheckout']['step']['confirm']['two-column'];
                                $old_settings2['supercheckout']['step']['confirm']['one-column'] = $this->request->post['supercheckout']['step']['confirm']['one-column'];
				$this->model_setting_setting->editSetting('supercheckout', $old_settings2, $store_id);
				if (!isset($this->request->post['save'])) {
					$this->response->redirect($this->url->link('extension/module/supercheckout/design_checkout', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
				else if (!isset($this->session_token)) {
					$this->response->redirect($this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
			}
		}

                if (isset($this->session->data['success'])) {
                    $data['success'] = $this->session->data['success'];
                    $this->session->data['success'] = '';
                } else {
                    $data['success'] = '';
                }

		$data['heading_title'] = $this->language->get('heading_title');
		$data['heading_title_main'] = $this->language->get('heading_title_main');

		// Words
		$data['settings_display'] = $this->language->get('settings_display');
		$data['settings_require'] = $this->language->get('settings_require');
		$data['settings_enable'] = $this->language->get('settings_enable');
		$data['supercheckout_text_enabled'] = $this->language->get('supercheckout_text_enabled');
		$data['supercheckout_text_disabled'] = $this->language->get('supercheckout_text_disabled');

		$data['supercheckout_entry_product'] = $this->language->get('supercheckout_entry_product');
		$data['supercheckout_entry_image'] = $this->language->get('supercheckout_entry_image');
		$data['supercheckout_entry_layout'] = $this->language->get('supercheckout_entry_layout');
		$data['supercheckout_entry_position'] = $this->language->get('supercheckout_entry_position');
		$data['supercheckout_entry_status'] = $this->language->get('supercheckout_entry_status');
		$data['supercheckout_entry_sort_order'] = $this->language->get('supercheckout_entry_sort_order');

		$data['supercheckout_entry_firstname'] = $this->language->get('supercheckout_entry_firstname');
		$data['supercheckout_entry_lastname'] = $this->language->get('supercheckout_entry_lastname');
		$data['supercheckout_entry_telephone'] = $this->language->get('supercheckout_entry_telephone');
		$data['supercheckout_entry_company'] = $this->language->get('supercheckout_entry_company');
		$data['supercheckout_entry_company_id'] = $this->language->get('supercheckout_entry_company_id');
		$data['supercheckout_entry_tax_id'] = $this->language->get('supercheckout_entry_tax_id');
		$data['supercheckout_entry_address_1'] = $this->language->get('supercheckout_entry_address_1');
		$data['supercheckout_entry_address_2'] = $this->language->get('supercheckout_entry_address_2');
		$data['supercheckout_entry_postcode'] = $this->language->get('supercheckout_entry_postcode');
		$data['supercheckout_entry_city'] = $this->language->get('supercheckout_entry_city');
		$data['supercheckout_entry_country'] = $this->language->get('supercheckout_entry_country');
		$data['supercheckout_entry_zone'] = $this->language->get('supercheckout_entry_zone');
		$data['supercheckout_entry_shipping'] = $this->language->get('supercheckout_entry_shipping');

		//General Settings tab & info
		$data['supercheckout_text_newsletter_enable'] = $this->language->get('supercheckout_text_newsletter_enable');
		$data['supercheckout_text_general'] = $this->language->get('supercheckout_text_general');
		$data['supercheckout_text_general_enable'] = $this->language->get('supercheckout_text_general_enable');
		$data['supercheckout_text_general_guestenable'] = $this->language->get('supercheckout_text_general_guestenable');
		$data['supercheckout_text_general_guest_manual'] = $this->language->get('supercheckout_text_general_guest_manual');
		$data['supercheckout_text_custom_style'] = $this->language->get('supercheckout_text_custom_style');

		$data['supercheckout_text_general_default'] = $this->language->get('supercheckout_text_general_default');
		$data['supercheckout_text_register'] = $this->language->get('supercheckout_text_register');
		$data['supercheckout_text_guest'] = $this->language->get('supercheckout_text_guest');

		$data['supercheckout_text_step_login_option'] = $this->language->get('supercheckout_text_step_login_option');
		$data['supercheckout_text_login'] = $this->language->get('supercheckout_text_login');
		$data['step_login_option_register_display'] = $this->language->get('supercheckout_text_register');
		$data['step_login_option_guest_display'] = $this->language->get('supercheckout_text_guest');
                
                //error
                $data['error_form'] = $this->language->get('error_form');
                $data['error_facebook_app_id'] = $this->language->get('error_facebook_app_id');
                $data['error_facebook_secret_key'] = $this->language->get('error_facebook_secret_key');
                $data['error_google_app_id'] = $this->language->get('error_google_app_id');
                $data['error_google_client_id'] = $this->language->get('error_google_client_id');
                $data['error_google_secret_key'] = $this->language->get('error_google_secret_key');
                $data['error_popup_image'] = $this->language->get('error_popup_image');
                
		//Login tab and info
		$data['supercheckout_text_facebook_login'] = $this->language->get('supercheckout_text_facebook_login');
		$data['supercheckout_text_facebook_login_display'] = $this->language->get('supercheckout_text_facebook_login_display');
		$data['supercheckout_text_google_login_display'] = $this->language->get('supercheckout_text_google_login_display');
		$data['supercheckout_text_facebook_app_id'] = $this->language->get('supercheckout_text_facebook_app_id');
		$data['supercheckout_text_facebook_app_secret'] = $this->language->get('supercheckout_text_facebook_app_secret');
		$data['supercheckout_text_google_app_id'] = $this->language->get('supercheckout_text_google_app_id');
		$data['supercheckout_text_google_client_id'] = $this->language->get('supercheckout_text_google_client_id');
		$data['supercheckout_text_google_app_secret'] = $this->language->get('supercheckout_text_google_app_secret');


		//Payment address
		$data['supercheckout_text_payment_address'] = $this->language->get('supercheckout_text_payment_address');
		$data['supercheckout_text_guest_customer'] = $this->language->get('supercheckout_text_guest_customer');
		$data['supercheckout_text_registrating_customer'] = $this->language->get('supercheckout_text_registrating_customer');
		$data['supercheckout_text_logged_in_customer'] = $this->language->get('supercheckout_text_logged_in_customer');

		//Shipping address
		$data['supercheckout_text_shipping_address'] = $this->language->get('supercheckout_text_shipping_address');


		//Shipping method
		$data['supercheckout_text_shipping_method'] = $this->language->get('supercheckout_text_shipping_method');
		$data['supercheckout_text_shipping_method_display_options'] = $this->language->get('supercheckout_text_shipping_method_display_options');
		$data['supercheckout_text_shipping_method_display_title'] = $this->language->get('supercheckout_text_shipping_method_display_title');
		$data['supercheckout_text_shipping_method_default_option'] = $this->language->get('supercheckout_text_shipping_method_default_option');

		//Payment method
		$data['supercheckout_text_payment_method'] = $this->language->get('supercheckout_text_payment_method');
		$data['supercheckout_text_payment_method_display_options'] = $this->language->get('supercheckout_text_payment_method_display_options');
		$data['supercheckout_text_payment_method_logo_display_options'] = $this->language->get('supercheckout_text_payment_method_logo_display_options');
		$data['supercheckout_text_only'] = $this->language->get('supercheckout_text_only');
		$data['supercheckout_text_with_image'] = $this->language->get('supercheckout_text_with_image');
		$data['supercheckout_image_only'] = $this->language->get('supercheckout_image_only');
		$data['supercheckout_text_payment_method_default_option'] = $this->language->get('supercheckout_text_payment_method_default_option');

		//Cart
		$data['supercheckout_text_cart'] = $this->language->get('supercheckout_text_cart');
		$data['supercheckout_text_warning'] = $this->language->get('supercheckout_text_warning');
		$data['supercheckout_text_applicable'] = $this->language->get('supercheckout_text_applicable');
		$data['supercheckout_text_image_size'] = $this->language->get('supercheckout_text_image_size');
		$data['supercheckout_text_cart_display'] = $this->language->get('supercheckout_text_cart_display');
		$data['supercheckout_text_cart_columns_image'] = $this->language->get('supercheckout_text_cart_columns_image');
		$data['supercheckout_text_cart_columns_name'] = $this->language->get('supercheckout_text_cart_columns_name');
		$data['supercheckout_text_cart_columns_model'] = $this->language->get('supercheckout_text_cart_columns_model');
		$data['supercheckout_text_cart_columns_quantity'] = $this->language->get('supercheckout_text_cart_columns_quantity');
		$data['supercheckout_text_cart_columns_price'] = $this->language->get('supercheckout_text_cart_columns_price');
		$data['supercheckout_text_cart_columns_total'] = $this->language->get('supercheckout_text_cart_columns_total');
		$data['supercheckout_text_cart_option_coupon'] = $this->language->get('supercheckout_text_cart_option_coupon');
		$data['supercheckout_text_cart_option_voucher'] = $this->language->get('supercheckout_text_cart_option_voucher');
		$data['supercheckout_text_cart_option_reward'] = $this->language->get('supercheckout_text_cart_option_reward');

		//Confirm
		$data['supercheckout_text_confirm'] = $this->language->get('supercheckout_text_confirm');
		$data['supercheckout_text_confirm_display'] = $this->language->get('supercheckout_text_confirm_display');
		$data['supercheckout_text_agree'] = $this->language->get('supercheckout_text_agree');
		$data['supercheckout_text_comments'] = $this->language->get('supercheckout_text_comments');

		//HTML
		$data['html_content'] = $this->language->get('html_content');
		$data['supercheckout_text_html'] = $this->language->get('supercheckout_text_html');
		$data['supercheckout_text_html_header'] = $this->language->get('supercheckout_text_html_header');
		$data['supercheckout_text_html_footer'] = $this->language->get('supercheckout_text_html_footer');
		$data['supercheckout_text_html_description'] = $this->language->get('supercheckout_text_html_description');

		//Design
		$data['supercheckout_text_design'] = $this->language->get('supercheckout_text_design');
		$data['supercheckout_text_payment_address_description'] = $this->language->get('supercheckout_text_payment_address_description');
		$data['supercheckout_text_shipping_address_description'] = $this->language->get('supercheckout_text_shipping_address_description');
		$data['supercheckout_text_shipping_method_description'] = $this->language->get('supercheckout_text_shipping_method_description');
		$data['supercheckout_text_payment_method_description'] = $this->language->get('supercheckout_text_payment_method_description');
		$data['supercheckout_text_cart_description'] = $this->language->get('supercheckout_text_cart_description');
		$data['supercheckout_text_confirm_description'] = $this->language->get('supercheckout_text_confirm_description');
		$data['text_column_1'] = $this->language->get('text_column_1');
		$data['text_column_2'] = $this->language->get('text_column_2');
		$data['text_column_3'] = $this->language->get('text_column_3');
		$data['text_step_checkout'] = $this->language->get('text_step_checkout');
		$data['text_edit_html'] = $this->language->get('text_edit_html');
		$data['text_save'] = $this->language->get('text_save');
		$data['text_close'] = $this->language->get('text_close');
                
                //Language
		$data['supercheckout_text_language'] = $this->language->get('supercheckout_text_language');

		//Tooltips
		//General
		$data['general_enable_newsletter_tooltip'] = $this->language->get('general_enable_newsletter_tooltip');
		$data['general_enable_supercheckout_tooltip'] = $this->language->get('general_enable_supercheckout_tooltip');
		$data['custom_style_supercheckout_tooltip'] = $this->language->get('custom_style_supercheckout_tooltip');
		$data['general_guestenable_supercheckout_tooltip'] = $this->language->get('general_guestenable_supercheckout_tooltip');
		$data['general_guest_manual_supercheckout_tooltip'] = $this->language->get('general_guest_manual_supercheckout_tooltip');
		$data['general_default_supercheckout_tooltip'] = $this->language->get('general_default_supercheckout_tooltip');
		$data['step_login_option_supercheckout_tooltip'] = $this->language->get('step_login_option_supercheckout_tooltip');
		$data['guest_enable_disabled_supercheckout_tooltip'] = $this->language->get('guest_enable_disabled_supercheckout_tooltip');
		$data['field_disabled_supercheckout_tooltip'] = $this->language->get('field_disabled_supercheckout_tooltip');

		//Buttons
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add_module'] = $this->language->get('button_add_module');
		$data['button_remove'] = $this->language->get('button_remove');

		//Check coupon & voucher status in store
		$data['coupon_status'] = $this->config->get('total_coupon_status');
		$data['voucher_status'] = $this->config->get('total_voucher_status');
		$data['reward_status'] = $this->config->get('total_reward_status');
		$store_setting = $this->model_setting_setting->getSetting('config', $store_id);
		if (isset($store_setting['config_checkout_guest']))
			$data['guest_enable'] = $store_setting['config_checkout_guest'];
		
		if(version_compare(VERSION, '2.1.0.1', '<')) {
                        $this->load->model('sale/customer_group');
                        $results_customer_group = $this->model_sale_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }else{
                        $this->load->model('customer/customer_group');
                        $results_customer_group = $this->model_customer_customer_group->getCustomerGroup($store_setting['config_customer_group_id']);
                }
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}
		else {
			$data['error_warning'] = '';
		}

		//Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_home'),
		    'href' => $this->url->link('common/home', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => false
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_module'),
		    'href' => $this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('heading_title_main'),
		    'href' => $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);
                
		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_design'),
		    'href' => $this->url->link($this->module_path . '/supercheckout/design_checkout', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

                if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = $this->config->get('config_store_id');
		}
		//links
		$data['action'] = $this->url->link($this->module_path . '/supercheckout/design_checkout', $this->session_token_key .'=' . $this->session_token . '&store_id=' . $store_id, 'SSL');
		$data['action_save_classes'] = $this->url->link($this->module_path . '/supercheckout/saveClasses', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['action_save_classes_trigger'] = $this->url->link($this->module_path . '/supercheckout/saveClassesTrigger', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['route'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['token'] = $this->session_token;
		$data['supercheckout'] = array();

		if (isset($this->request->post['supercheckout'])) {
			$data['supercheckout'] = $this->request->post['supercheckout'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			$data['supercheckout'] = $settings['supercheckout'];
		}
//                var_dump($data['supercheckout']['step']);die;
		$data['supercheckout_modules'] = array();
		if (isset($this->request->post['supercheckout_module'])) {
			$data['supercheckout_modules'] = $this->request->post['supercheckout_module'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$modules = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			if (!empty($modules['supercheckout_module'])) {
				$data['supercheckout_modules'] = $modules['supercheckout_module'];
			}
			else {
				$data['supercheckout_modules'] = array();
			}
		}
		if (empty($settings)) {
			$settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
			$data['settings'] = $settings['default_supercheckout'];
			$data['supercheckout'] = $settings['default_supercheckout'];
		}
                if (!isset($this->request->get['layout'])) {
                        $data['layout'] = $data['supercheckout']['general']['layout'];
                }
                else {
                        $data['layout'] = $this->request->get['layout'];
                }
                if(isset($data['supercheckout']['step']['html_value']['value']['footer'])){
                    $data['supercheckout']['step']['html_value']['value']['header'] = html_entity_decode($data['supercheckout']['step']['html_value']['value']['header']);
                }
                if(isset($data['supercheckout']['step']['html_value']['value']['header'])){
                    $data['supercheckout']['step']['html_value']['value']['footer'] = html_entity_decode($data['supercheckout']['step']['html_value']['value']['footer']);
                }
                foreach ($data['supercheckout']['step']['html'] as $key => $value) {
                    $value['value'] = html_entity_decode($value['value']);
                    $data['supercheckout']['step']['html'][$key] = $value;
                }
		//Store Settings
		$settings['general']['default_email'] = $this->config->get('config_email');
		//$settings['step']['payment_address']['fields']['agree']['information_id'] = $this->config->get('config_account_id');
		//$settings['step']['payment_address']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_account_id');
		$settings['step']['confirm']['fields']['agree']['information_id'] = $this->config->get('config_checkout_id');
		$settings['step']['confirm']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_checkout_id');

		if (!empty($data['supercheckout'])) {
                    $data['supercheckout'] = $this->merge($settings, $data['supercheckout']);
		}else {
                    $data['supercheckout'] = $settings;
		}
		$data['supercheckout']['general']['store_id'] = $store_id;

                $tabs_data['store_id'] = $store_id;
                $tabs_data['active'] = 9;
                $data['tabs'] = $this->load->controller($this->module_path . '/supercheckout/tabs', $tabs_data);
                $data['store_id'] = $store_id;
                $data['current_url'] = html_entity_decode($this->url->link($this->module_path . '/supercheckout/design_checkout', $this->session_token_key . '=' . $this->session_token, true));
                $data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key . '=' . $this->session_token . '&type=module&store_id=' . $store_id, true);
                $data['text_default'] = $this->language->get('text_default');
                $data['store_switcher'] = $this->load->controller($this->module_path . '/supercheckout/store_swticher', $data);
		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$this->template = $this->module_path . '/kbsupercheckout/design_checkout.tpl';


		//code for opencart2.0

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
                if (VERSION < '2.2.0') {
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/design_checkout.tpl', $data));
                }else{
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/design_checkout', $data));
                }
		//code for 2.0 ends here
	}
        
        public function newsletter() {

		$this->load->language($this->module_path . '/supercheckout');
		$this->document->setTitle($this->language->get('heading_title_main'));
		$this->load->model('setting/setting');

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = 0;
		}
		$this->preventReinstall();
		$classes_array = $this->getClasses();

		if (isset($classes_array['anchor_classes']['supercheckout_classes']))
			$data['anchor_classes'] = $classes_array['anchor_classes']['supercheckout_classes'];
		if (isset($classes_array['anchor_classes_trigger']['supercheckout_trigger']))
			$data['anchor_classes_trigger'] = $classes_array['anchor_classes_trigger']['supercheckout_trigger'];

		// Load settings for supercheckout plugin from database or from default settings
		$this->load->model('setting/setting');

		//Check for old settings
		$old_settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
		$old_default_settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
		if (!empty($old_settings)) {
			$new_settings = array();
			if (!isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_settings['supercheckout']['general'] = array_merge($old_settings['supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('supercheckout', $old_settings, $store_id);
			}
		}
		if (!empty($old_default_settings)) {
			$new_settings = array();
			if (isset($old_settings['supercheckout']['general']['adv_id'])) {
				$new_settings = array('default_supercheckout' => array('general' => array('version' => '2.2', 'adv_id' => 0, 'plugin_id' => 'OC0001')));
				$old_default_settings['default_supercheckout']['general'] = array_merge($old_default_settings['default_supercheckout']['general'], $new_settings['default_supercheckout']['general']);
				$this->model_setting_setting->editSetting('default_supercheckout', $old_default_settings, $store_id);
			}
		}
		$result = $this->model_setting_setting->getSetting('supercheckout', $this->config->get('config_store_id'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) { {
				$this->session->data['success'] = $this->language->get('supercheckout_text_success_newsletter');
				if (isset($this->request->post['supercheckout']['general']['settings']['value'])) {
					$settings = str_replace("amp;", "", urldecode($this->request->post['supercheckout']['general']['settings']['bulk']));
					parse_str($settings, $this->request->post);
				}
				$old_settings2 = $this->model_setting_setting->getSetting('supercheckout', $store_id);
                                $old_settings2['supercheckout']['mailchimp'] = $this->request->post['supercheckout']['mailchimp'];
				$this->model_setting_setting->editSetting('supercheckout', $old_settings2, $store_id);
				if (!isset($this->request->post['save'])) {
					$this->response->redirect($this->url->link($this->module_path . '/supercheckout/newsletter', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
				else if (!isset($this->session_token)) {
					$this->response->redirect($this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'));
				}
			}
		}

                if (isset($this->session->data['success'])) {
                    $data['success'] = $this->session->data['success'];
                    $this->session->data['success'] = '';
                } else {
                    $data['success'] = '';
                }


		$data['heading_title'] = $this->language->get('heading_title');
		$data['supercheckout_text_mailchimp'] = $this->language->get('supercheckout_text_mailchimp');
                $data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
                $data['text_get_list'] = $this->language->get('text_get_list');
                
		$data['supercheckout_text_mailchimp_enable'] = $this->language->get('supercheckout_text_mailchimp_enable');
		$data['supercheckout_text_mailchimp_api'] = $this->language->get('supercheckout_text_mailchimp_api');
		$data['supercheckout_text_mailchimp_list'] = $this->language->get('supercheckout_text_mailchimp_list');
		$data['text_mailchimp_empty_list'] = $this->language->get('text_mailchimp_empty_list');
		$data['text_mailchimp_invalid_key'] = $this->language->get('text_mailchimp_invalid_key');
                $data['error_empty_field'] = $this->language->get('error_empty_field');

                //Language
		$data['supercheckout_text_language'] = $this->language->get('supercheckout_text_language');

		//Tooltips
		$data['supercheckout_text_mailchimp_enable_tooltip'] = $this->language->get('supercheckout_text_mailchimp_enable_tooltip');
		$data['supercheckout_text_mailchimp_api_tooltip'] = $this->language->get('supercheckout_text_mailchimp_api_tooltip');
		$data['supercheckout_text_mailchimp_list_tooltip'] = $this->language->get('supercheckout_text_mailchimp_list_tooltip');
                
		//Buttons
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_remove'] = $this->language->get('button_remove');

		//Right menu cookies check
		if (isset($this->request->cookie['rightMenu'])) {
			$data['rightMenu'] = true;
		}
		else {
			$data['rightMenu'] = false;
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}
		else {
			$data['error_warning'] = '';
		}

		//Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_home'),
		    'href' => $this->url->link('common/home', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => false
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_module'),
		    'href' => $this->url->link('extension/extension', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		    'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('heading_title_main'),
		    'href' => $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		);
                
		$data['breadcrumbs'][] = array(
		    'text' => $this->language->get('supercheckout_text_mailchimp'),
		    'href' => $this->url->link($this->module_path . '/supercheckout/newsletter', $this->session_token_key .'=' . $this->session_token, 'SSL'),
		);

		//links
		$data['mailchimp_list_url'] = html_entity_decode($this->url->link($this->module_path . '/supercheckout/mailchimp_getList', $this->session_token_key .'=' . $this->session_token . '&store_id=' . $store_id, 'SSL'));
		$data['action'] = $this->url->link($this->module_path . '/supercheckout/newsletter', $this->session_token_key .'=' . $this->session_token . '&store_id=' . $store_id, 'SSL');
		$data['action_save_classes'] = $this->url->link($this->module_path . '/supercheckout/saveClasses', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['action_save_classes_trigger'] = $this->url->link($this->module_path . '/supercheckout/saveClassesTrigger', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['route'] = $this->url->link($this->module_path . '/supercheckout', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key .'=' . $this->session_token, 'SSL');
		$data['token'] = $this->session_token;
		$data['supercheckout'] = array();

		if (isset($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		}
		else {
			$store_id = $this->config->get('config_store_id');
		}


		if (isset($this->request->post['supercheckout'])) {
			$data['supercheckout'] = $this->request->post['supercheckout'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$settings = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			$data['supercheckout'] = $settings['supercheckout'];
		}

		$data['supercheckout_modules'] = array();
		if (isset($this->request->post['supercheckout_module'])) {
			$data['supercheckout_modules'] = $this->request->post['supercheckout_module'];
		}
		elseif ($this->model_setting_setting->getSetting('supercheckout', $store_id)) {
			$modules = $this->model_setting_setting->getSetting('supercheckout', $store_id);
			if (!empty($modules['supercheckout_module'])) {
				$data['supercheckout_modules'] = $modules['supercheckout_module'];
			}
			else {
				$data['supercheckout_modules'] = array();
			}
		}

		if (empty($settings)) {
			$settings = $this->model_setting_setting->getSetting('default_supercheckout', 0);
			$data['settings'] = $settings['default_supercheckout'];
			$data['supercheckout'] = $settings['default_supercheckout'];
		}

		//Store Settings
		$settings['general']['default_email'] = $this->config->get('config_email');
		//$settings['step']['payment_address']['fields']['agree']['information_id'] = $this->config->get('config_account_id');
		//$settings['step']['payment_address']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_account_id');
		$settings['step']['confirm']['fields']['agree']['information_id'] = $this->config->get('config_checkout_id');
		$settings['step']['confirm']['fields']['agree']['error'][0]['information_id'] = $this->config->get('config_checkout_id');

		if (!empty($data['supercheckout'])) {
                    $data['supercheckout'] = $this->merge($settings, $data['supercheckout']);
		}else {
                    $data['supercheckout'] = $settings;
		}
		$data['supercheckout']['general']['store_id'] = $store_id;


                $tabs_data['store_id'] = $store_id;
                $tabs_data['active'] = 10;
                $data['tabs'] = $this->load->controller($this->module_path . '/supercheckout/tabs', $tabs_data);
                $data['store_id'] = $store_id;
                $data['current_url'] = html_entity_decode($this->url->link($this->module_path . '/supercheckout/newsletter', $this->session_token_key . '=' . $this->session_token, true));
                $data['cancel'] = $this->url->link('marketplace/extension', $this->session_token_key . '=' . $this->session_token . '&type=module&store_id=' . $store_id, true);
                $data['text_default'] = $this->language->get('text_default');
                $data['store_switcher'] = $this->load->controller($this->module_path . '/supercheckout/store_swticher', $data);
		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$this->template = $this->module_path . '/kbsupercheckout/newsletter.tpl';


		//code for opencart2.0

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
                if (VERSION < '2.2.0') {
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/newsletter.tpl', $data));
                }else{
                    $this->response->setOutput($this->load->view($this->module_path.'/kbsupercheckout/newsletter', $data));
                }
		//code for 2.0 ends here
	}
        
	private function validate() {
		if (!$this->user->hasPermission('modify', $this->module_path . '/supercheckout')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if (!$this->error) {
			return true;
		}
		else {
			return false;
		}
	}

	public function merge(array &$array1, array &$array2) {
		$merged = $array1;
		foreach ($array2 as $key => &$value) {
			if (is_array($value) && isset($merged [$key]) && is_array($merged [$key])) {
				$merged [$key] = $this->merge($merged [$key], $value);
			}
			else {
				$merged [$key] = $value;
			}
		}
		return $merged;
	}

	public function get_title($fields, $texts) {
		$this->load->model('catalog/information');
		$array_full = $fields;
		$result = array();
		foreach ($fields as $key => $value) {
			foreach ($texts as $text) {
				if (isset($array_full[$text])) {
					if (!is_array($array_full[$text])) {
						$result[$text] = $this->language->get($array_full[$text]);
					}
					else {
						if (isset($array_full[$text][(int) $this->config->get('config_language_id')])) {
							$result[$text] = $array_full[$text][(int) $this->config->get('config_language_id')];
						}
						else {
							$result[$text] = current($array_full[$text]);
						}
					}
					if ((strpos($result[$text], '%s') !== false) && isset($array_full['information_id'])) {
						$information_info = $this->model_catalog_information->getInformation($array_full['information_id']);
						$result[$text] = sprintf($result[$text], $information_info['title']);
					}
				}
			}
			if (is_array($array_full[$key])) {
				$result[$key] = $this->get_title($array_full[$key], $texts);
			}
		}
		return $result;
	}

	public function install() {
		$this->load->model('setting/setting');


		$default_settings = $this->getDefaultSettings();
		$this->model_setting_setting->editSetting('default_supercheckout', $default_settings, 0);
		$this->model_setting_setting->editSetting('supercheckout', $default_settings, 0);
		$check_classes = $this->model_setting_setting->getSetting('supercheckout_classes');
		$check_classes_trigger = $this->model_setting_setting->getSetting('supercheckout_trigger');
		if (empty($check_classes)) {
			$this->model_setting_setting->editSetting('supercheckout_classes', array('supercheckout_classes' => '#display_payment .button, #display_payment .btn, #display_payment .button_oc, #display_payment input[type=submit]'));
		}
		if (empty($check_classes_trigger)) {
			$this->model_setting_setting->editSetting('supercheckout_trigger', array('supercheckout_trigger' => '#button-confirm,#display_payment .button, #display_payment .btn, #display_payment .button_oc, #display_payment input[type=submit]'));
		}
	}

	public function uninstall() {

		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('default_supercheckout');
		$this->model_setting_setting->deleteSetting('supercheckout');
	}

	public function getDefaultSettings() {
            
		$this->load->language($this->module_path . '/supercheckout');
                $this->load->model('setting/extension');
                $data_payment_methods = array();
		$payment_methods = $this->model_setting_extension->getInstalled('payment');
                foreach ($payment_methods as $payment) {
                        if ($this->config->get('payment_'.$payment . '_status')) {
                                $this->load->language('extension/payment/' . $payment);
                                $data_payment_methods[] = $payment;
                        }
                }
		$data_shipping_methods = array();
		$shipping_methods = $this->model_setting_extension->getInstalled('shipping');
		foreach ($shipping_methods as $shipping) {
			if ($this->config->get('shipping_'.$shipping . '_status')) {
				$this->load->language('extension/shipping/' . $shipping);
                                $shipping_default = $shipping;
				$data_shipping_methods[$shipping] = $data_payment_methods;
			}
		}
		
           
		return array('default_supercheckout' => array('general' => array(
			    'enable' => 0,
			    'guestenable' => 0,
			    'guest_manual' => 0,
			    'layout' => '3-Column',
			    'main_checkout' => 1,
			    'column_width' => array(
				'one-column' => array(
				    1 => '100', 2 => '0', 3 => '0', 'inside' => array(1 => '0', 2 => '0')),
				'three-column' => array(
				    1 => '30', 2 => '25', 3 => '45', 'inside' => array(1 => '0', 2 => '0')),
				'two-column' => array(1 => '30', 2 => '70', 3 => '0', 'inside' => array(1 => '50', 2 => '50'))
			    ),
			    'default_option' => 'guest',
			    'custom_style' => '',
			    'store_id' => 0,
			    'settings' => array('value' => 0, 'bulk' => ''),
			    'version' => '2.2',
			    'adv_id' => 0,
			    'plugin_id' => 'OC0001'
			),
                        'mailchimp' => array(
                            'enable' => 0,
                            'api' => ''
                        ),
                        'testing_mode' => array(
                            'enable' => 0,
                            'url' => ''
                        ),
			'payment_logo' => array(
			    'default_option' => 'textonly'
			),
			'shipping_logo' => array(
			    'default_option' => 'textonly'
			),
			'step' => array(
			    'login' => array(
				'sort_order' => 1,
				'three-column' => array('column' => 1, 'row' => 0, 'column-inside' => 0),
				'two-column' => array('column' => 1, 'row' => 0, 'column-inside' => 1),
				'one-column' => array('column' => 0, 'row' => 0, 'column-inside' => 0),
				'width' => '50',
				'option' => array(
				    'guest' => array('title' => 'supercheckout_text_guest',
					'description' => 'step_option_guest_desciption',
					'display' => 1
				    ),
				    'login' => array('display' => 1
				    )
				),
				'enable_slider' => 0
			    ),
			    'payment_address' => array(
				'sort_order' => '2',
				'three-column' => array('column' => 1, 'row' => 1, 'column-inside' => 0),
				'two-column' => array('column' => 1, 'row' => 1, 'column-inside' => 1),
				'one-column' => array('column' => 0, 'row' => 1, 'column-inside' => 0),
				'width' => '50',
				'fields' => array(
				    'firstname' => array(
					'id' => 'firstname',
					'title' => $this->language->get('supercheckout_entry_firstname'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 1,
					'class' => ''
				    ),
				    'lastname' => array(
					'id' => 'lastname',
					'title' => $this->language->get('supercheckout_entry_lastname'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 2,
					'class' => ''
				    ),
				    'telephone' => array(
					'id' => 'telephone',
					'title' => $this->language->get('supercheckout_entry_telephone'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 3,
					'class' => ''
				    ),
				    'company' => array(
					'id' => 'company',
					'title' => $this->language->get('supercheckout_entry_company'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 9,
					'class' => ''
				    ),
				    
				    'address_1' => array(
					'id' => 'address_1',
					'title' => $this->language->get('supercheckout_entry_address_1'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 13,
					'class' => ''
				    ),
				    'address_2' => array(
					'id' => 'address_2',
					'title' => $this->language->get('supercheckout_entry_address_2'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 14,
					'class' => ''
				    ),
				    'city' => array(
					'id' => 'city',
					'title' => $this->language->get('supercheckout_entry_city'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 15,
					'class' => ''
				    ),
				    'postcode' => array(
					'id' => 'postcode',
					'title' => $this->language->get('supercheckout_entry_postcode'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 16,
					'class' => ''
				    ),
				    'country_id' => array(
					'id' => 'country_id',
					'title' => $this->language->get('supercheckout_entry_country'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 17,
					'class' => ''
				    ),
				    'zone_id' => array(
					'id' => 'zone_id',
					'title' => $this->language->get('supercheckout_entry_zone'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 18,
					'class' => ''
				    ),
				    'shipping' => array(
					'id' => 'shipping',
					'title' => $this->language->get('supercheckout_entry_shipping'),
					'custom' => 0,
					'display' => 0,
					'checked' => 0,
					'sort_order' => 20,
					'class' => '',
					'value' => 1
				    )
				)
			    ),
			    'shipping_address' => array(
				'sort_order' => '3',
				'three-column' => array('column' => 1, 'row' => 2, 'column-inside' => 0),
				'two-column' => array('column' => 1, 'row' => 2, 'column-inside' => 1),
				'one-column' => array('column' => 0, 'row' => 2, 'column-inside' => 0),
				'width' => '30',
				'fields' => array(
				    'firstname' => array(
					'id' => 'firstname',
					'title' => $this->language->get('supercheckout_entry_firstname'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 1,
					'class' => ''
				    ),
				    'lastname' => array(
					'id' => 'lastname',
					'title' => $this->language->get('supercheckout_entry_lastname'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 2,
					'class' => ''
				    ),
				    'address_1' => array(
					'id' => 'address_1',
					'title' => $this->language->get('supercheckout_entry_address_1'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 4,
					'class' => ''
				    ),
				    'address_2' => array(
					'id' => 'address_2',
					'title' => $this->language->get('supercheckout_entry_address_2'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 5,
					'class' => ''
				    ),
				    'city' => array(
					'id' => 'city',
					'title' => $this->language->get('supercheckout_entry_city'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 6,
					'class' => ''
				    ),
				    'postcode' => array(
					'id' => 'postcode',
					'title' => $this->language->get('supercheckout_entry_postcode'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 7,
					'class' => ''
				    ),
				    'country_id' => array(
					'id' => 'country_id',
					'title' => $this->language->get('supercheckout_entry_country'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 8,
					'class' => ''
				    ),
				    'zone_id' => array(
					'id' => 'zone_id',
					'title' => $this->language->get('supercheckout_entry_zone'),
					'custom' => 0,
					'display' => 0,
					'require' => 0,
					'sort_order' => 9,
					'class' => ''
				    ),
				)
			    ),
			    'shipping_method' => array(
				'sort_order' => 4,
				'three-column' => array('column' => 2, 'row' => 0, 'column-inside' => 0),
				'two-column' => array('column' => 1, 'row' => 0, 'column-inside' => 3),
				'one-column' => array('column' => 0, 'row' => 3, 'column-inside' => 0),
				'display' => 1,
				'display_title' => 1,
				'display_options' => 1,
				'default_option' => $shipping_default,
				'available' => $data_shipping_methods,
				'width' => '30'
			    ),
			    'payment_method' => array(
				'sort_order' => 5,
				'three-column' => array('column' => 2, 'row' => 1, 'column-inside' => 0),
				'two-column' => array('column' => 2, 'row' => 0, 'column-inside' => 3),
				'one-column' => array('column' => 0, 'row' => 4, 'column-inside' => 0),
				'display' => 1,
				'display_options' => 1,
				'default_option' => $data_payment_methods[0],
				'width' => '30'
			    ),
			    'cart' => array(
				'sort_order' => 6,
				'three-column' => array('column' => 3, 'row' => 0, 'column-inside' => 0),
				'two-column' => array('column' => 2, 'row' => 0, 'column-inside' => 2),
				'one-column' => array('column' => 0, 'row' => 5, 'column-inside' => 0),
				'image_width' => 230,
				'image_height' => 230,
				'width' => '50',
				'option' => array(
				    'voucher' => array(
					'id' => 'voucher',
					'title' => array(1 => 'voucher'),
					'tooltip' => array(1 => 'voucher'),
					'type' => 'text',
					'refresh' => '3',
					'custom' => 0,
					'class' => ''
				    ),
				    'coupon' => array(
					'id' => 'coupon',
					'title' => array(1 => 'coupon'),
					'tooltip' => array(1 => 'coupon'),
					'type' => 'text',
					'refresh' => '3',
					'custom' => 0,
					'class' => ''
				    )
				),
			    ),
			    'confirm' => array(
				'sort_order' => 7,
				'three-column' => array('column' => 3, 'row' => 1, 'column-inside' => 0),
				'two-column' => array('column' => 2, 'row' => 1, 'column-inside' => 4),
				'one-column' => array('column' => 0, 'row' => 6, 'column-inside' => 0),
				'width' => '50',
				'fields' => array(
				    'comment' => array(
					'id' => 'comment',
					'title' => $this->language->get('supercheckout_text_comments'),
					'custom' => 0,
					'class' => ''
				    ),
				    'agree' => array(
					'id' => 'agree',
					'title' => $this->language->get('supercheckout_text_agree'),
					'value' => 0,
					'custom' => 0,
					'class' => ''
				    )
				)
			    ),
			    'html' => array(
				'0_0' => array(
				    'sort_order' => 8,
				    'three-column' => array('column' => 3, 'row' => 4, 'column-inside' => 1),
				    'two-column' => array('column' => 2, 'row' => 1, 'column-inside' => 4),
				    'one-column' => array('column' => 0, 'row' => 7, 'column-inside' => 1),
				    'value' => ""
				)
			    ),
			    'modal_value' => 1,
			    'facebook_login' => array(
                                'display' => 0,
                                'app_id' => '',
                                'app_secret' => ''
                            ),
			    'google_login' => array(
                                'display' => 0,
                                'app_id' => '',
                                'client_id' => '',
                                'app_secret' => ''
                            ),
			),
			'option' => array(
			    'guest' => array(
				'display' => 1,
				'login' => array(),
				'payment_address' => array(
				    'title' => 'supercheckout_text_your_details',
				    'description' => 'option_guest_payment_address_description',
				    'display' => 1,
				    'fields' => array(
					'firstname' => array('display' => 1,
					    'require' => 1
					),
					'lastname' => array(
					    'display' => 1,
					    'require' => 1
					),
					'telephone' => array(
					    'display' => 1,
					    'require' => 1
					),
					'company' => array(
					    'display' => 1,
					    'require' => 0
					),
					
					'customer_group_id' => array(
					    'display' => 1,
					    'require' => 0
					),
					
					'address_1' => array(
					    'display' => 1,
					    'require' => 1
					),
					'address_2' => array(
					    'display' => 0,
					    'require' => 0
					),
					'city' => array(
					    'display' => 1,
					    'require' => 1
					),
					'postcode' => array(
					    'display' => 1,
					    'require' => 1
					),
					'country_id' => array(
					    'display' => 1,
					    'require' => 1
					),
					'zone_id' => array(
					    'display' => 1,
					    'require' => 1
					),
					'shipping' => array(
					    'display' => 1,
					    'value' => '0',
					    'checked' => 1
					)
				    )
				),
				'shipping_address' => array(
				    'display' => 1,
				    'title' => 'option_guest_shipping_address_title',
				    'description' => 'option_guest_shipping_address_description',
				    'fields' => array(
					'firstname' => array(
					    'display' => 1,
					    'require' => 1
					),
					'lastname' => array(
					    'display' => 0,
					    'require' => 0
					),
					'company' => array(
					    'display' => 1,
					    'require' => 0
					),
					'address_1' => array(
					    'display' => 1,
					    'require' => 1
					),
					'address_2' => array(
					    'display' => 0,
					    'require' => 0
					),
					'city' => array(
					    'display' => 1,
					    'require' => 0
					),
					'postcode' => array(
					    'display' => 1,
					    'require' => 1
					),
					'country_id' => array(
					    'display' => 1,
					    'require' => 1
					),
					'zone_id' => array(
					    'display' => 1,
					    'require' => 1
					),
				    )
				),
				'shipping_method' => array(
				    'title' => 'option_guest_shipping_method_title',
				    'description' => 'supercheckout_text_shipping_method',
				),
				'payment_method' => array(
				    'title' => 'option_guest_payment_method_title',
				    'description' => 'supercheckout_text_payment_method',
				),
				'cart' => array(
				    'display' => 1,
				    'option' => array(
					'voucher' => array(
					    'display' => 1
					),
					'coupon' => array(
					    'display' => 1
					),
					'reward' => array(
					    'display' => 1
					)
				    ),
				    'columns' => array(
					'image' => 1,
					'name' => 1,
					'model' => 1,
					'quantity' => 1,
					'price' => 1,
					'total' => 1
				    )
				),
				'confirm' => array(
				    'display' => 1,
				    'fields' => array(
					'comment' => array(
					    'display' => 1
					),
					'agree' => array(
					    'display' => 1,
					    'require' => 1
					)
				    )
				)
			    ),
			    'logged' => array(
				'login' => array(),
				'payment_address' => array(
				    'display' => 1,
				    'title' => 'option_logged_payment_address_title',
				    'description' => 'option_logged_payment_address_description',
				    'fields' => array(
					'firstname' => array('display' => 1,
					    'require' => 1
					),
					'lastname' => array(
					    'display' => 1,
					    'require' => 1
					),
					'telephone' => array(
					    'display' => 1,
					    'require' => 1
					),
					'company' => array(
					    'display' => 1,
					    'require' => 0
					),
					
					'customer_group_id' => array(
					    'display' => 1,
					    'require' => 0
					),
					
					'address_1' => array(
					    'display' => 1,
					    'require' => 1
					),
					'address_2' => array(
					    'display' => 0,
					    'require' => 0
					),
					'city' => array(
					    'display' => 1,
					    'require' => 0
					),
					'postcode' => array(
					    'display' => 1,
					    'require' => 1
					),
					'country_id' => array(
					    'display' => 1,
					    'require' => 1
					),
					'zone_id' => array(
					    'display' => 1,
					    'require' => 1
					),
                                        'shipping' => array(
					    'display' => 1,
					    'value' => '0',
					    'checked' => 1
					),
					'address_id' => array()
				    )
				),
				'shipping_address' => array(
				    'display' => 1,
				    'title' => 'option_logged_shipping_address_title',
				    'description' => 'option_logged_shipping_address_description',
				    'fields' => array(
					'firstname' => array(
					    'display' => 1,
					    'require' => 1
					),
					'lastname' => array(
					    'display' => 0,
					    'require' => 0
					),
					'company' => array(
					    'display' => 1,
					    'require' => 0
					),
					'address_1' => array(
					    'display' => 1,
					    'require' => 1
					),
					'address_2' => array(
					    'display' => 0,
					    'require' => 0
					),
					'city' => array(
					    'display' => 1,
					    'require' => 1
					),
					'postcode' => array(
					    'display' => 1,
					    'require' => 1
					),
					'country_id' => array(
					    'display' => 1,
					    'require' => 1
					),
					'zone_id' => array(
					    'display' => 1,
					    'require' => 1
					),
				    )
				),
				'shipping_method' => array(
				    'title' => 'option_logged_shipping_method_title',
				    'description' => 'supercheckout_text_shipping_method',
				),
				'payment_method' => array(
				    'title' => 'option_logged_payment_method_title',
				    'description' => 'supercheckout_text_payment_method',
				),
				'cart' => array(
				    'display' => 1,
				    'option' => array(
					'voucher' => array(
					    'display' => 1
					),
					'coupon' => array(
					    'display' => 1
					),
					'reward' => array(
					    'display' => 1
					)
				    ),
				    'columns' => array(
					'image' => 1,
					'name' => 1,
					'model' => 1,
					'quantity' => 1,
					'price' => 1,
					'total' => 1
				    )
				),
				'confirm' => array(
				    'display' => 1,
				    'fields' => array(
					'comment' => array(
					    'display' => 1
					),
					'agree' => array(
					    'display' => 1,
					    'require' => 1
					)
				    )
				)
			    )
			)
		));
	}

	public function saveClasses() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('supercheckout_classes', $this->request->post);
	}

	public function saveClassesTrigger() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('supercheckout_trigger', $this->request->post);
	}

	public function getClasses() {
		$classes = array();
		$this->load->model('setting/setting');
		$classes['anchor_classes'] = $this->model_setting_setting->getSetting('supercheckout_classes');
		$classes['anchor_classes_trigger'] = $this->model_setting_setting->getSetting('supercheckout_trigger');
		return $classes;
	}

    public function preventReinstall() {
        $this->load->model('setting/setting');
        $check_classes = $this->model_setting_setting->getSetting('supercheckout_classes');
        $check_classes_trigger = $this->model_setting_setting->getSetting('supercheckout_trigger');
        if (empty($check_classes)) {
            $this->model_setting_setting->editSetting('supercheckout_classes', array('supercheckout_classes' => '#display_payment .button, #display_payment .btn, #display_payment .button_oc, #display_payment input[type=submit]'));
        }
        if (empty($check_classes_trigger)) {
            $this->model_setting_setting->editSetting('supercheckout_trigger', array('supercheckout_trigger' => '#button-confirm,#display_payment .button, #display_payment .btn, #display_payment .button_oc, #display_payment input[type=submit]'));
        }
    }
    
    public function mailchimp_getList() {
        $key = $this->request->get['key'];
        $MailChimp = new MailChimp($key);
        $flag = 1;
        $data = '';
        $result = $MailChimp->get('lists');
        if ($MailChimp->success()) {
            $data = $result;
        } else {
            $flag = 0;
        }
        $json = array('flag' => $flag, 'data' => $data);
        $this->response->setOutput(json_encode($json));
    }

}

?>
