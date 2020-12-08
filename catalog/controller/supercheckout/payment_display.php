<?php
class ControllerSupercheckoutPaymentDisplay extends Controller {
    public function index($ajax = 1){
        $this->load->model('checkout/order');
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/supercheckout/payment_display.tpl')) {
                $data['default_theme']=$this->config->get('config_template');
        }else{
                $data['default_theme']='default';
        }
        //getting payment method for displaying on supercheckout page
        if (isset($this->session->data['payment_method']['code'])) {
            
            //$data['payment'] = $this->getChild('payment/' . $this->session->data['payment_method']['code']);
            $data['payment'] = $this->load->controller('extension/payment/' . $this->session->data['payment_method']['code']);
        }
                	
        $data['sessions'] =$this->session->data;
        if($ajax == 0){
            if(version_compare(VERSION, '2.2.0.0', '<')) {
                return $this->load->view('default/template/supercheckout/payment_display.tpl', $data);
            }else{
                return $this->load->view('supercheckout/payment_display', $data);
            }
        }else{
            if(version_compare(VERSION, '2.2.0.0', '<')) {
                $this->response->setOutput($this->load->view('default/template/supercheckout/payment_display.tpl', $data));
            }else{
                $this->response->setOutput($this->load->view('supercheckout/payment_display', $data));
            }
        }
    }
    
}
?>
