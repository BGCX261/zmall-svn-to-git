<?php

define('LOCK_FILE', ROOT_PATH . '/data/init.lock');

if(!file_exists(LOCK_FILE)) 
{
	Psmb_init::create_table();
	
	/* 创建完表后，生成锁定文件 */
	file_put_contents(LOCK_FILE,1);
}

class Psmb_init {
	
	
	function Delivery_templateModel_format_template($region_mod, $delivery_template,$need_dest_ids=false)
	{
		if(!is_array($delivery_template)){
			return array();
		}
		
		$data = $deliverys = array();

		foreach($delivery_template as $template)
		{
			$data = array();
			$data['template_id'] = $template['template_id'];
			$data['name'] = $template['name'];
			$data['created'] = $template['created'];
			
			$template_types = explode(';', $template['template_types']);
			$template_dests = explode(';', $template['template_dests']);
			$template_start_standards = explode(';', $template['template_start_standards']);
			$template_start_fees = explode(';', $template['template_start_fees']);
			$template_add_standards = explode(';', $template['template_add_standards']);
			$template_add_fees = explode(';', $template['template_add_fees']);
			
			$i=0;
			foreach($template_types as $key=>$type)
			{
				$dests = explode(',',$template_dests[$key]);
				$start_standards = explode(',', $template_start_standards[$key]);
				$start_fees = explode(',', $template_start_fees[$key]);
				$add_standards = explode(',', $template_add_standards[$key]);
				$add_fees = explode(',', $template_add_fees[$key]);
				
				foreach($dests as $k=>$v)
				{
					$data['area_fee'][$i] = array(
						'type'=> $type,
						'dests'=>$region_mod->get_region_name($v),
						'start_standards'=> $start_standards[$k],
						'start_fees'	 => $start_fees[$k],
						'add_standards'  => $add_standards[$k],
						'add_fees'		 => $add_fees[$k]
					);
					if($need_dest_ids){
						$data['area_fee'][$i]['dest_ids'] = $v;
					}
					$i++;
				}
			}
			$deliverys[] = $data;	
		}
		return $deliverys;
	}
	
	function Delivery_templateModel_format_template_foredit($delivery_template, $region_mod)
	{
		$data[] = $delivery_template;
		$delivery = $this->Delivery_templateModel_format_template($region_mod, $data,true);
		$delivery = current($delivery);
		
		$area_fee_list = array();
		foreach($delivery['area_fee'] as $key=>$val)
		{
			$type = $val['type'];
			$area_fee_list[$type][] = $val;
		}
		$delivery['area_fee'] = $area_fee_list;
		
		foreach($delivery['area_fee'] as $key=>$val)
		{
			$default_fee=true;
			foreach($val as $k=>$v){
				if($default_fee){
					$delivery['area_fee'][$key]['default_fee'] = $v;
					$default_fee=false;
				} else {
					$delivery['area_fee'][$key]['other_fee'][] = $v;
				}
				unset($delivery['area_fee'][$key][$k]);
			}
		}

		return $delivery;
	}
	
	
	function create_table() 
	{
		/* 运费模板 */
		$result = db()->getAll('SHOW COLUMNS FROM '. DB_PREFIX . 'goods');
		$fields = array();
		foreach($result as $v) {
			$fields[] = $v['Field'];
		}
		if(!in_array('delivery_template_id', $fields)){
			$sql = 'ALTER TABLE `'.DB_PREFIX.'goods` ADD `delivery_template_id` INT (11) NOT NULL ';
			db()->query($sql);
		}
	
		$sql = "CREATE TABLE IF NOT EXISTS `". DB_PREFIX . "delivery_template` (
  			`template_id` int(11) NOT NULL AUTO_INCREMENT,
  			`name` varchar(50) NOT NULL,
  			`store_id` int(10) NOT NULL,
  			`template_types` text NOT NULL,
  			`template_dests` text NOT NULL,
  			`template_start_standards` text NOT NULL,
  			`template_start_fees` text NOT NULL,
  			`template_add_standards` text NOT NULL,
  			`template_add_fees` text NOT NULL,
  			`created` int(10) NOT NULL,
  			PRIMARY KEY (`template_id`)
		) ENGINE = MYISAM DEFAULT CHARSET=".str_replace('-','',CHARSET).";";
		db()->query($sql);
	
	}
}
?>
