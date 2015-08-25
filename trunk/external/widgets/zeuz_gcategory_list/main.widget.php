<?php

/**
 * 商品分类挂件
 *
 * @return  array   $category_list
 */
class Zeuz_gcategory_listWidget extends BaseWidget
{
    var $_name = 'zeuz_gcategory_list';
    var $_ttl  = 86400;


    function _get_data()
    {
        $cache_server =& cache_server();
        $key = $this->_get_cache_id();
        $data = $cache_server->get($key);
        if($data === false)
        {
			$amount = (empty($this->options['amount']) || intval($this->options['amount'])<=0) ? 0 : intval($this->options['amount']);
			
	
			/* position: 给弹出层设置高度，使得页面效果美观 */
			$position = array('0px','0px','0px','0px','0px','0px','0px','0px');
			$data = $this->get_header_gcategories($amount,$position,1);// 参数说明（二级分类显示数量,弹出层位置,品牌是否为推荐）

			$data['wid'] = md5($key);
			$cache_server->set($key, $data, $this->_ttl);
        }
        return $data;
    }
	function parse_config($input)
    {
        return $input;
    }
	/* 所有商品类目，头部通用 tyioocom */
	function get_header_gcategories($amount, $position, $brand_is_recommend=1)
	{
		$gcategory_mod =& bm('gcategory', array('_store_id' => 0));
		$gcategories = array();
		if(!$amount)
		{
			$gcategories = $gcategory_mod->get_list(-1, true);
		}
		else
		{
			$gcategory = $gcategory_mod->get_list(0, true);
			$gcategories = $gcategory;
			foreach ($gcategory as $val)
			{
				$result = $gcategory_mod->get_list($val['cate_id'], true);
				$result = array_slice($result, 0, $amount);
				$gcategories = array_merge($gcategories, $result);
			}
		}

		import('tree.lib');
        $tree = new Tree();
        $tree->setTree($gcategories, 'cate_id', 'parent_id', 'cate_name');
		$gcategory_list = $tree->getArrayList(0);
		
		$i=0;
		$brand_mod=&m('brand');
		foreach($gcategory_list as $k => $v) {
			$gcategory_list[$k]['top']  =  isset($position[$i]) ? $position[$i] : '0px';
			$i++;
			
			$gcategory_list[$k]['brands'] = $brand_mod->find(array(
				'conditions'=>"tag = '".$v['value']."' AND recommended=".$brand_is_recommend, 
				'order'=>'sort_order asc,brand_id desc'
			));
		}
		
		return array('gcategories'=>$gcategory_list);
	}

}

?>