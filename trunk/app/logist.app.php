<?php

class LogistApp extends MallbaseApp
{
	var $_store_mod;
	var $_region_mod;
	var $_delivery_mod;
	var $_goods_mod;
	var $_address_mod;

    function __construct()
    {
        $this->LogistApp();
    }
    function LogistApp()
    {
        parent::__construct();
		$this->_store_mod = &m('store');
		$this->_region_mod= &m('region');
		$this->_delivery_mod = &m('delivery_template');
		$this->_goods_mod =& m('goods');
		$this->_address_mod =& m('address');
		
    }
    function index()
    {
		$delivery_template_id = intval($_GET['delivery_template_id']);
		$store_id = intval($_GET['store_id']); // 在deliery_template_id=0 的时候用到
		$city_id  = intval($_GET['city_id']);// 运送目的地ID
		$goods_id = intval($_GET['goods_id']);
		
		// 如果没有设置运费模板，则取该店铺默认的运费模板
		if(!$delivery_template_id || !$this->_delivery_mod->get($delivery_template_id))
		{
			$delivery = $this->_delivery_mod->get(array(
				'condtions'=>'store_id='.$store_id,
				'order'=>'template_id',
			));
	
			// 如果店铺也没有默认的运费模板
			if(empty($delivery)) {
				$this->json_error('store_no_delivery');
				return;
			}
		}
		else {
			$delivery = $this->_delivery_mod->get($delivery_template_id);
		}
		
		// 如果是通过IP自动获取省和城市id
		if(empty($city_id)) {
			$city_id = $this->_region_mod->get_city_id_by_ip();
		} 
		else {
			$city_id = intval($_GET['city_id']);
		}
		$logist_fee = $this->_delivery_mod->get_city_logist($delivery,$city_id);
		$city_name = $this->_region_mod->get_region_name($city_id);
		$logist = array(
			'logist_fee' => $logist_fee,
			'city_name'  => $city_name, // 获取运送目的地的城市名
		);
		$logist['inside'] = $this->is_inside_delivery($city_id,$goods_id);
		$trans_mod =& m('trans');
		$logist['trans'] = $trans_mod->get_trans_by_regoin($city_id,$store_id);
		$this->json_result($logist);
	}
	
	function is_inside_delivery($city_id,$goods_id)
	{
		$return = true;
		$goods = $this->_goods_mod->get(array(
			'conditions' => 'goods_id='.$goods_id,
			'fields' => 'goods_dests',
		));
		if(!empty($goods['goods_dests']))
		{
			$ids = explode('|',$goods['goods_dests']);
			foreach($ids as $key=>$id)
			{
				$child[$key] = $this->_region_mod->get_descendant($id);
				$ids = array_merge($ids,$child[$key]);
			}
			$delivery_city_ids = array_unique($ids);//去重复
			if($city_id !=0 || $city_id!=1){
				!in_array($city_id,$delivery_city_ids) && $return = false;}
		}
		return $return;
	}
	
}

?>
