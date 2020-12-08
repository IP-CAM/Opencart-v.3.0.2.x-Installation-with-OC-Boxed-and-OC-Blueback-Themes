<?php
class ModelMmstockMmstock extends Model {
			
	
	public function addnotification($data)
	{
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "mmstock SET  name='".$this->db->escape($data['name'])."', email='".$this->db->escape($data['email'])."', phone='".$this->db->escape($data['phone'])."', p_id='".$this->db->escape($data['proid'])."', status =0, date_added =NOW()");
	}
	
	
}