<?php

class Coupon_listApp extends MallbaseApp
{
	
    function index()
    {
		$condition = ' if_issue = 1 AND  c.end_time >'.gmtime().' AND c.show_in_list = 1 ';
		if($_GET['type'] == 'm')
		{
			$condition.= ' AND c.store_id = 0 ';
		}
		elseif($_GET['type'] == 's')
		{
			$condition.= ' AND c.store_id > 0 ';
			if (intval($_GET['cate_id']) > 0)
			{
				$scategory_mod =& m('scategory');
				$cate_ids = $scategory_mod->get_descendant($_GET['cate_id']);
				$condition_id=implode(',',$cate_ids);
				$condition_id && $condition.= ' AND cate_id IN(' . $condition_id . ')';
	
			}
		}
		if(!empty($_GET['interval']))
		{
			$interval = explode('-',$_GET['interval']);
			$min = min(intval($interval[0]),intval($interval[1]));
			$max = max(intval($interval[0]),intval($interval[1]));
			$condition.=" AND c.coupon_value  > {$min} AND c.coupon_value <= {$max} ";
		}
        $page = $this->_get_page(10);
		$coupon = $this->get_coupon_info($condition,$page['limit']);
        $page['item_count'] = $this->get_coupon_info($condition);
        $this->_format_page($page);
        $this->assign('page_info', $page);
		
		$scategory_mod = &m('scategory');
		$this->assign('scategories',$scategory_mod->find('parent_id = 0'));
        $this->_config_seo('title', Conf::get('site_name') . ' - ' . Lang::get('coupon'));
		$this->assign('intervals',$this->coupon_price_interval());
        $this->assign('coupons', $coupon);
        $this->display('coupon_list.index.html');
    }
	function view()
	{
		$coupon_id = $_GET['id'];
		if(empty($_GET['id']))
		{
			$this->show_warning('hack_attemp');
			exit;
		}
		$coupon_mod = &m('coupon');
		$coupon=$coupon_mod->get('coupon_id='.$coupon_id);
		$storecoupon = &m('store_coupon');
		$store_mod = &m('store');
		$append_data = "<p style='font-size:14px;border:1px solid #ddd;background:#fafafa;padding:10px;'><span style='margin-right:10px;'>".Lang::get('surport_store_list')."</span> ";
		if($coupon['open_agree_item'])
		{
			$store_info = $storecoupon->getAll("SELECT s.store_name,s.store_id FROM {$storecoupon->table} sc LEFT JOIN {$store_mod ->table} s ON sc.store_id=s.store_id WHERE sc.coupon_id={$coupon_id} AND if_agree= 2 ");
			if($store_info)
			{
				foreach($store_info as $k=>$v)
				{
					$append_data.="<a target='_blank' href='index.php?app=store&id={$v['store_id']}' style='margin-right:10px;'>{$v['store_name']}</a>";		
				}
			}
		}
		else
		{
			$append_data.=Lang::get('can_use_in_all_store');
		}
		$append_data.="</p>";
			
		$coupon['tip'].=$append_data;
		 /* 当前位置 */
        $curlocal =array(array('text' => Lang::get('coupon_tip')));
        $this->_curlocal($curlocal);
		$this->assign('coupon', $coupon);
		$this->display('coupon.view.html');
	}
	function get_coupon_info($condition,$limit='')
	{
		$coupon_mod = &m('coupon');
		$store_mod = &m('store');
		$storecoupon = &m('store_coupon');
		!empty($limit) &&  $lmt = ' LIMIT '. $limit;
		$coupon = $coupon_mod->getAll("SELECT c.*,s.store_name FROM {$coupon_mod->table} c LEFT JOIN {$store_mod->table} s ON c.store_id=s.store_id LEFT JOIN ecm_category_store cs ON s.store_id=cs.store_id WHERE {$condition} ORDER BY c.coupon_id desc {$lmt}");
		foreach($coupon as $key=>$val)
		{
			$coupon[$key]['coupon_value'] = intval($val['coupon_value']);
			$store_info = $storecoupon->getAll("SELECT s.store_name,s.store_id FROM {$storecoupon->table} sc LEFT JOIN {$store_mod ->table} s ON sc.store_id=s.store_id WHERE sc.coupon_id={$val['coupon_id']} AND if_agree= 2 ");
			if(empty($store_info) && $val['open_agree_item']) unset($coupon[$key]);
		}
		if(!empty($limit)) return $coupon;else return count($coupon);
	}

	function coupon_price_interval()
	{
		$coupon_mod = &m('coupon');
		$coupon = current($coupon_mod->getAll("SELECT max(coupon_value) as max_value, min(coupon_value) as min_value FROM {$coupon_mod->table} c"));
		$result = array();
		if($coupon['min_value'] > 0 && $coupon['min_value'] <> $coupon['max_value'])
		{
			$interval = $coupon['max_value']/5;
			$start = intval($coupon['min_value']);
			for($i=0;$i<5;$i++)
			{
				$end = $i == 4 ? intval($coupon['max_value']) : $start + $interval;
				$result[$i] = $start.'-'.$end;
				$start = $end;
			}
		}
		return $result;
	}
	
    function assign_user()
    {
		$coupon_id = $_GET['coupon_id'];
		$user_id = $_GET['user_id'];
		$store_id = $_GET['store_id']?$_GET['store_id']:0;
		if(!$coupon_id)
		{
		 $this->json_error('no_the_coupon');
         return;
		}
		if(!$user_id)
		{
		 $this->json_error('login_first');
         return;
		}
        $count = 1;
        $arr = current($this->generate($count,$coupon_id,$store_id));
		if(empty($arr))
		{
		 $this->json_result(array('getallready'=> true));
         return;
		}
		$user_mod = &m('member');
		$coupon_mod = &m('coupon');
        $user_mod->createRelation('bind_couponsn', $user_id, array($arr['coupon_sn'] => array('coupon_sn' =>$arr['coupon_sn'],'bind_time'=>gmtime())));
		$coupon = $coupon_mod->get(array(
            'conditions' => 'coupon_id='.$coupon_id,
			'join'   => 'belong_to_store',
			'fields'=>'coupon.*,store.store_name'
		));
		$coupon['coupon_value'] = intval($coupon['coupon_value']);
		$coupon['min_amount'] = intval($coupon['min_amount']);
		$coupon['end_time'] = date('Y-m-d',$coupon['end_time']);
		$coupon['store_name'] = !empty($coupon['store_name']) ? $coupon['store_name'] : Conf::get('site_name');
        $this->json_result(array(
            'coupon'      =>   $coupon 
        ));
    }

    function generate($num, $coupon_id,$store_id)
    {
		$coupon_mod = &m('coupon');
		$couponsn_mod = &m('couponsn');
        $use_times = $coupon_mod->get(array('fields' => 'use_times', 'conditions' => 'store_id = ' . $store_id . ' AND coupon_id = ' . $coupon_id));

        if ($num > 1000)
        {
            $num = 1000;
        }
        if ($num < 1)
        {
            $num = 1;
        }
        $times = $use_times['use_times'];
        $add_data = array();
        $str = '';
        $pix = 0;
        if (file_exists(ROOT_PATH . '/data/generate.txt'))
        {
            $s = file_get_contents(ROOT_PATH . '/data/generate.txt');
            $pix = intval($s);
        }
        $max = $pix + $num;
        file_put_contents(ROOT_PATH . '/data/generate.txt', $max);
        $couponsn = '';
        $tmp = '';
        $cpm = '';
        $str = '';
		$pre = $store_id ? 'S' : 'M';
        for ($i = $pix + 1; $i <= $max; $i++ )
        {
            $cpm = sprintf("%08d", $i);
            $tmp = mt_rand(1000, 9999);
            $couponsn = $pre.$cpm . $tmp;
            $str .= "('{$couponsn}',{$coupon_id}, {$times}),";
            $add_data[] = array(
                'coupon_sn' => $couponsn,
                'coupon_id' => $coupon_id,
                'remain_times' => $times,
                );
        }
        $string = substr($str,0, strrpos($str, ','));
		$c=$couponsn_mod->getAll("SELECT * FROM {$couponsn_mod->table} cs  JOIN ecm_user_coupon AS uc ON cs.coupon_sn = uc.coupon_sn WHERE uc.user_id=".$this->visitor->get('user_id')." AND cs.coupon_id=".$coupon_id." AND remain_times > 0");
		if(!empty($c)) return array();
        $couponsn_mod->db->query("INSERT INTO {$couponsn_mod->table} (coupon_sn, coupon_id, remain_times) VALUES {$string}", 'SILENT');
        return $add_data;
    }

    function _create_random($num, $id, $times)
    {
        $arr = array();
        for ($i = 1; $i <= $num; $i++)
        {
            $arr[$i]['coupon_sn'] =  mt_rand(10000, 99999);
            $arr[$i]['coupon_id'] = $id;
            $arr[$i]['remain_times'] = $times;
        }
        return $arr;
    }
}

?>