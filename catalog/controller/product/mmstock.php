<?php
class ControllerProductMmstock extends Controller {
	public function index() {
		
		$this->load->model('tool/image');

		$data =array();

		
		
		

		
	
		
		
		return $this->load->view('product/mmstock', $data);
	}
}
