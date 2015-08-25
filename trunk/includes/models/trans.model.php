<?php
class transModel extends BaseModel
{
    var $table  = 'trans';
    var $prikey = 'id';
    var $_name  = 'trans';

    var $trans_type=1;

    //设置运费优惠类型为减运费或为优惠运费
    function set_trans_type($is_set=0)
    {
        $this->trans_type=$is_set;
    }

    function get_options_type()
    {
        if($this->trans_type)
        {
            return array(
            '1'=>'满件数运费',
            '2'=>'满金额运费',
        );
        }else{
            return array(
            '1'=>'满件数减运费',
            '2'=>'满金额减运费',
       	 );
        }
    	
    }

    function get_options_enabled()
    {
		return array('1'=>'启用','-1'=>'禁用');
    }

   

    function get_trans_info($order_id)
    {
		if($order_id<1)
		{
			return -3;
		}
    	$order_info=$this->get_order_info($order_id);
    	if(!$order_info)
    	{
    		return -1;
    	}

    	if(!$trans_info=$this->get_where_id($order_info['goods_amount'],$order_info['num'],$order_info['region_id'],$order_info['seller_id']))
    	{
    		return -2;
    	}
    	return $trans_info;
    }

    function get_where_id($money,$num,$region_id,$seller_id)
    {
        if(empty($seller_id) || empty($region_id))
        {
            return -2;
        }
		$region_mod = &m('region');
		$region_ids = $region_mod->get_parents($region_id); 
    	 //$region_ids=$this->_get_where_region($region_id,$seller_id);
	     $money_info=$this->_get_where_money($money,$region_ids,$seller_id);
	     $num_info=$this->_get_where_num($num,$region_ids,$seller_id);
	     
	     if(!$money_info && !$num_info)
	     {
	     	return 0;
	     }

	     if(!$money_info)
	     {
	     	return $num_info;
	     }

	     if(!$num_info)
	     {
	     	return $money_info;
	     }

	     if($num_info['trans_money']<=$money_info['trans_money'])
	     {
	     	return $num_info;
	     }
    }

    function _get_where_region($region_id,$seller_id)
    {
    	//360cd.cn
    	$region_model=&m('region');
    	$regions_list=$region_model->get_parents($region_id);
        $regions_list=array_reverse($regions_list);
    	if(is_array($regions_list) && count($regions_list)>0)
    	{
    		foreach($regions_list as $key=>$region)
    		{
                if(intval($seller_id)==0 || intval($region)==0)
                {
                    return 0;
                }
    			if($trans_info=$this->get(' store_id= '.$seller_id.' and region_id='.$region))
    			{
    				return $region;
    			}
    		}
    	}
    	return 0;
    }
    function _get_where_money($money,$region_ids,$seller_id)
    {
		$conditions = $region_ids ? " and region_id".db_create_in($region_ids) : '';
		$trans = $this->find(array(
			'conditions' => 'apply_money<='.$money." and enabled=1 and apply_type=2 and store_id= ".$seller_id.$conditions,
			'order' => 'trans_money asc',
		));
		if(!$trans)
		{
			return 0; 
		}
		return current($trans);
    	//$where_region=$region_id ? " and region_id".db_create_in($region_ids) : '';
    	//$where=array();
    	//$where['conditions']='apply_money<='.$money." and enabled=1 and apply_type=2 and store_id= ".$seller_id.$where_region;
    	//$where['order']=' apply_money desc';
    	//$where_info=$this->get($where);
    	//if(!$where_info)
//    	{
//    		return 0;
//    	}
//    	return $where_info;
    }

    function _get_where_num($num,$region_ids,$seller_id)
    {
		$conditions = $region_ids ? " and region_id".db_create_in($region_ids) : '';
		$trans = $this->find(array(
			'conditions' => 'apply_num<='.$num." and enabled=1 and apply_type=1 and store_id= ".$seller_id.$conditions,
			'order' => 'trans_money asc',
		));
		if(!$trans)
		{
			return 0; 
		}
		return current($trans);
    	//$where_region=$region_id?" and region_id=".$region_id:'';
//    	$where=array();
//    	$where['conditions']='apply_num<='.$num." and enabled=1 and apply_type=1   and store_id= ".$seller_id.$where_region;
//    	$where['order']=' apply_num desc';
//    	$where_info=$this->get($where);
//    	if(!$where_info)
//    	{
//    		return 0;
//    	}
//    	return $where_info;
    }

    function get_order_info($order_id)
    {
    	//360cd.cn
    	$order_model=&m('order');
    	//  $joinstr.=$order_model->parseJoin('order_id','order_id','ordergoods');
    	$where=array(
    		'fields'=>'this.order_amount,this.goods_amount,this.order_id,this.order_sn,orderextm.region_id,this.seller_id',//360cd.cn
    		'conditions'=>' order_alias.order_id='.$order_id,
    		//'joinstr'=>$joinstr,//360cd.cn        
    		'join'          => 'has_orderextm',            
    	);
    	$order_data=$order_model->get($where);
    	if(!$order_data)
    	{
    		//此处填写数据不存在内容
    		return 0;
    	}
        //360cd.cn
        $ordergoods_model=&m('ordergoods');
        $where=array(
            'fields'=>'sum(ordergoods.quantity) as num',//360cd.cn
            'conditions'=>' order_id='.$order_id,
            
            );
        $ordergoods_data=$ordergoods_model->get($where);
        if(!$ordergoods_data)
        {
            //此处填写数据不存在内容
            return 0;
        }
        //360cd.cn
        $order_data['num']=$ordergoods_data['num'];
    	return $order_data;
    	//360cd.cn
    }

    //订单匹配模式
    function update_order($order_id)
    {
		if($order_id<1)
		{
			return -3;
		}
    	$trans_info=$this->get_trans_info($order_id);
    	if(!$trans_info)
    	{
    		return -1;//无规则
    	}
    	if($trans_info==-1)
    	{
    		return -2;//订单为空
    	}
    	if($trans_info==-2)
    	{
    		return 0;//无可用规则
    	}
		
		

    	$trans_money=$trans_info['trans_money'];
    	$trans_id=$trans_info['id'];

    	//360cd.cn
    	$discount=$this->_update_shipping($order_id,$trans_money);
    	$order_result=$this->_update_order($order_id,$discount,$trans_id);
    	if(!$order_result || $order_result==-1)
    	{
    		return 0;
    	}    	
    	return 1;
    }

    function _update_order($order_id,$discount,$trans_id)
    {
    	//360cd.cn
    	$order_model=&m('order');
    	$where=" order_id={$order_id} and trans_id<=0";
    	$order_data=$order_model->get($where);
    	if(!$order_data)
    	{
    		//此处填写数据不存在内容
    		return 0;
    	}
    	$order_amount=$order_data['order_amount'];
    	$total=$order_amount-$discount;
    	if($total<0)
    	{
    		return -1;
    	}

    	return $order_model->edit($where,array('order_amount'=>$total,'discount'=>$discount,'trans_id'=>$trans_id));
    	//360cd.cn
    }

    function _update_shipping($order_id,$trans_money)
    {
		$discount = 0;
    	$orderextm_model=&m('orderextm');
    	$where=" order_id=".$order_id;
    	$data=$orderextm_model->get($where);
    	if(!$data)
    	{
    		return 0;
    	}
    	$shipping_fee=$data['shipping_fee'];
		if($shipping_fee>$trans_money)
		{
			$discount=$shipping_fee-$trans_money;   
			$orderextm_model->edit($where,array('shipping_fee'=>$trans_money));
		}
		return $discount;
    }

    function get_trans($trans_id)
    {
    	$data=$this->get($trans_id);
    	if(!$data)
    	{
    		return 0;
    	}
    	$content='';
        if($this->trans_type)
        {
            $text="运费优惠";
        }else{
             $text="减运费";
        }
    	if($data['apply_type']==1)
    	{
			$content='满'.$data['apply_num'].'件'.$text.'为'.$data['trans_money'];
    	}else{
    		$content='满'.$data['apply_money'].'金额'.$text.'为'.$data['trans_money'];
    	}
    	$data['desc']=$content;
    	return $data;
    }
	
	function get_trans_by_regoin($region_id,$store_id)
	{
		if(!$region_id || !$store_id)
		{
			return false;
		}
		$region_mod =&m('region');
		$region_ids = $region_mod->get_parents($region_id);
		$trans1 = $this->find(array(
			'conditions' => 'enabled=1 AND apply_type=1 AND region_id'.db_create_in($region_ids).' AND store_id='.$store_id,
			'order' => 'trans_money asc'
		));
		$trans2 = $this->find(array(
			'conditions' => 'enabled=1 AND apply_type=2 AND region_id'.db_create_in($region_ids).' AND store_id='.$store_id,
			'order' => 'trans_money asc'
		));
		if($trans1)
		{
			$trans1 = current($trans1);
			$data[] = '满'.$trans1['apply_num'].'件运费优惠为'.$trans['trans_money'];
		}
		if($trans2)
		{
			$trans2 = current($trans2);
			$data[] = '满'.$trans2['apply_money'].'金额减运费为'.$trans2['trans_money'];
		}
		return $data;
		
	}

}
?>