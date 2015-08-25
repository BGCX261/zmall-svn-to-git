<?php

class CouponApp extends BackendApp
{
    var $_coupon_mod;
    var $_couponsn_mod;
    function __construct()
    {
        $this->CouponApp();
    }
    function CouponApp()
    {
        parent::__construct();
        $this->_coupon_mod =& m('coupon');
        $this->_couponsn_mod =& m('couponsn');
    }
    function index()
    {
        $page = $this->_get_page(10);
        $coupon = $this->_coupon_mod->find(array(
            'conditions' => 'store_id = 0',
            'limit' => $page['limit'],
            'count' => true,
			'order' => 'coupon_id desc',
        ));
        $page['item_count'] = $this->_coupon_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);
		
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('coupon'));
        $this->assign('coupons', $coupon);
        $this->assign('time', gmtime());
        $this->display('coupon.index.html');
    }

	function view()
	{
		$coupon_id = $_GET['id'];
		if(empty($_GET['id']))
		{
			$this->show_warning('hack_attemp');
			exit;
		}
		$page = $this->_get_page(10);
		$coupons = $this->_couponsn_mod->find(array(
			'conditions'=>'coupon.coupon_id='.$coupon_id,
			'join'=>'bind_user,belongs_to_coupon',
			'limit' => $page['limit'],
            'count' => true,
		));
		$page['item_count'] = $this->_couponsn_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);
		if($coupons)
		{
			$member_mod = &m('member');
			$user_info = array();
			foreach($coupons as $key=>$val)
			{
				if($val['user_id'])
				{
					$user_info = $member_mod->get($val['user_id']);
					$coupons[$key]['user_name'] = $user_info['user_name'];
				}
			}
		}
		$this->assign('coupons', $coupons);
		$this->display('coupon.view.html');
	}

    function add()
    {
        if (!IS_POST)
        {
			$this->import_resource(array(
				'script' => array(
					array(
						'path' => 'jquery.ui/jquery.ui.js',
						'attr' => '',
					),
					array(
						'path' => 'jquery.ui/i18n/' . i18n_code() . '.js',
						'attr' => '',
					),
					array(
						'path' => 'jquery.plugins/jquery.validate.js',
						'attr' => '',
					),
				),
				'style' =>  'jquery.ui/themes/ui-lightness/jquery.ui.css',
			));
			$this->assign('build_editor', $this->_build_editor(array(
                'name' => 'tip',
                'content_css' => SITE_URL . "/themes/mall/{$template_name}/styles/{$style_name}/css/ecmall.css"
            )));
            
            $this->assign('build_upload', $this->_build_upload(array('belong' => BELONG_ARTICLE, 'item_id' => 0))); // 构建swfupload上传组件
            header("Content-Type:text/html;charset=" . CHARSET);
			$gcategory_mod = &bm('gcategory',array('store_id'=>0));
			$store_mod = &m('store');
			$store = $store_mod->find(array('conditions'=>'state=1','fields'=>'store_name,store_id'));
			$this->assign('store',$store);
			$this->assign('scategories',$this->_get_scategories());
			$this->assign('sgrades',$this->_get_sgrade());
			$this->assign('gcategories',$gcategory_mod->get_options(0));
            $this->assign('today', gmtime());
            $this->display('coupon.form.html');
        }
        else
        {
            $coupon_value = floatval(trim($_POST['coupon_value']));
            $use_times = intval(trim($_POST['use_times']));
            $min_amount = floatval(trim($_POST['min_amount']));
            if (empty($coupon_value) || $coupon_value < 0 )
            {
                $this->show_warning('coupon_value_not');
                exit;
            }
            if (empty($use_times))
            {
                $this->show_warning('use_times_not_zero');
                exit;
            }
            if ($min_amount < 0)
            {
                $this->show_warning("min_amount_gt_zero");
                exit;
            }
            $start_time = gmstr2time(trim($_POST['start_time']));
            $end_time = gmstr2time_end(trim($_POST['end_time'])) - 1 ;
            if ($end_time < $start_time)
            {
                $this->show_warning('end_gt_start');
                exit;
            }
			
            $coupon = array(
                'coupon_name' => trim($_POST['coupon_name']),
                'coupon_value' => $coupon_value,
                'store_id' => 0,
                'use_times' => $use_times,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'min_amount' => $min_amount,
                'if_issue'  => trim($_POST['if_issue']) == 1 ? 1 : 0,
				'tip' => $_POST['tip'],
				'open_agree_item' => $_POST['open_agree_item'],
				'show_in_list'  => $_POST['show_in_list'],
            );
			if($_POST['open_agree_item'])
			{
				$coupon['type_content'] = $_POST['type'] == 4 ? serialize($_POST['type_content'][$_POST['type']]) : $_POST['type_content'][$_POST['type']];
				$coupon['type'] = $_POST['type'];
			}
            $coupon_id = $this->_coupon_mod->add($coupon);
            if ($this->_coupon_mod->has_error())
            {
                $this->show_warning($this->_coupon_mod->get_error());
                exit;
            }
			
			 /* 处理上传的图片 */
            $coupon_image       =   $this->_upload_image($coupon_id);
            if ($coupon_image === false)
            {
                return;
            }
			
            $coupon_image && $this->_coupon_mod->edit($coupon_id, array('coupon_image' => $coupon_image));
			if($_POST['open_agree_item'])
			{
				$store_ids = $this->_save_store_coupon($_POST['type'],$_POST['type_content'][$_POST['type']],$coupon_id);
				if(trim($_POST['if_issue']) == 1)
				{
					if($store_ids)
					{
						foreach($store_ids as $key=>$val)
						{
							$ms =& ms();
							$content = get_msg(Lang::get('to_store_owner_who_can_use_coupon'), array('coupon_name'=>$_POST['coupon_name']));
							$ms->pm->send(MSG_SYSTEM, $val, '', $content);
						}
					}
				}
			}
			
			$this->show_message('add_coupon_success',
                'back_list',    'index.php?app=coupon'
            );
        }
    }

    function edit()
    {
        $coupon_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$current_coupon = $this->_coupon_mod->get_info($coupon_id);
        if (empty($coupon_id))
        {
            echo Lang::get("no_coupon");
        }
        if (!IS_POST)
        {
			$this->import_resource(array(
				'script' => array(
					array(
						'path' => 'jquery.ui/jquery.ui.js',
						'attr' => '',
					),
					array(
						'path' => 'jquery.ui/i18n/' . i18n_code() . '.js',
						'attr' => '',
					),
					array(
						'path' => 'jquery.plugins/jquery.validate.js',
						'attr' => '',
					),
				),
				'style' =>  'jquery.ui/themes/ui-lightness/jquery.ui.css',
			));
            header("Content-Type:text/html;charset=" . CHARSET);
			$gcategory_mod = &bm('gcategory',array('store_id'=>0));
			$this->assign('scategories',$this->_get_scategories());
			$this->assign('sgrades',$this->_get_sgrade());
			$this->assign('gcategories',$gcategory_mod->get_options(0));
			$current_coupon['selected'] = array();
			$current_coupon['selected'][$current_coupon['type']] = $current_coupon['type_content'];
			if($current_coupon['coupon_image'])
			{
				$current_coupon['coupon_image'] = dirname(site_url()) . "/" .$current_coupon['coupon_image'];
			}
			$store_mod = &m('store');
			$store = $store_mod->find(array('conditions'=>'state=1','fields'=>'store_name,store_id'));
			if($current_coupon['type'] == 4)
			{
				$type_content = unserialize($current_coupon['type_content']);
				if(!empty($type_content))
				{
					foreach($type_content as $key=>$val)
					{
						$store[$val]['selected'] = 1;
					}
				}
			}
			
			
			$this->assign('build_editor', $this->_build_editor(array(
                'name' => 'tip',
                'content_css' => SITE_URL . "/themes/mall/{$template_name}/styles/{$style_name}/css/ecmall.css"
            )));
            
            $this->assign('build_upload', $this->_build_upload(array('belong' => BELONG_ARTICLE, 'item_id' => 0))); // 构建swfupload上传组件
			$this->assign('store',$store);
            $this->assign('coupon', $current_coupon);
            $this->display('coupon.form.html');
        }
        else
        {
			if(!$current_coupon['if_issue'])
			{
				$coupon_value = floatval(trim($_POST['coupon_value']));
				$use_times = intval(trim($_POST['use_times']));
				$min_amount = floatval(trim($_POST['min_amount']));
				if (empty($coupon_value) || $coupon_value < 0 )
				{
					$this->show_warning('coupon_value_not');
					exit;
				}
				if (empty($use_times))
				{
					$this->show_warning('use_times_not_zero');
					exit;
				}
				if ($min_amount < 0)
				{
					$this->show_warning("min_amount_gt_zero");
					exit;
				}
				$start_time = gmstr2time(trim($_POST['start_time']));
				$end_time = gmstr2time_end(trim($_POST['end_time']))-1;
				//echo gmstr2time_end(trim($_POST['end_time'])) . '-------' .$end_time;exit; 
				if ($end_time < $start_time)
				{
					$this->show_warning('end_gt_start');
					exit;
				}
				$coupon = array(
					'coupon_name' => trim($_POST['coupon_name']),
					'coupon_value' => $coupon_value,
					'store_id' => 0,
					'use_times' => $use_times,
					'start_time' => $start_time,
					'end_time' => $end_time,
					'min_amount' => $min_amount,
					'if_issue'  => trim($_POST['if_issue']) == 1 ? 1 : 0,
					'tip' => $_POST['tip'],
					'open_agree_item' => $_POST['open_agree_item'],
					'show_in_list'  => $_POST['show_in_list'],
				);
				if($_POST['open_agree_item'])
				{
					$coupon['type_content'] = $_POST['type'] == 4 ? serialize($_POST['type_content'][$_POST['type']]) : $_POST['type_content'][$_POST['type']];
					$coupon['type'] = $_POST['type'];
				}
				else
				{
					$coupon['type_content'] = '';
					$coupon['type'] = '';
				}
				 /* 处理上传的图片 */
				$coupon_image       =   $this->_upload_image($coupon_id);
				if ($coupon_image)
				{
				  $coupon['coupon_image'] = $coupon_image;
				}
				if($_POST['open_agree_item'])
				{
					$store_ids = $this->_save_store_coupon($_POST['type'],$_POST['type_content'][$_POST['type']],$coupon_id);
					if(trim($_POST['if_issue']) == 1)
					{
						if($store_ids)
						{
							foreach($store_ids as $key=>$val)
							{
								$ms =& ms();
								$content = get_msg(Lang::get('to_store_owner_who_can_use_coupon'), array('coupon_name'=>$_POST['coupon_name']));
								$ms->pm->send(MSG_SYSTEM, $val, '', $content);
							}
						}
					}
				}
				else
				{
					$store_coupon_mod = &m('store_coupon');
					$store_coupon_mod->drop('coupon_id='.$coupon_id);
				}
			}
			else
			{
				 $coupon['if_issue']  = trim($_POST['if_issue']) == 1 ? 1 : 0;
				 $coupon['tip'] = $_POST['tip'];
			}
            $this->_coupon_mod->edit($coupon_id, $coupon);
            if ($this->_coupon_mod->has_error())
            {
                $this->show_warning($this->_coupon_mod->get_error());
                exit;
            }
			
			
            $this->show_message('edit_coupon_success',
                'back_list',    'index.php?app=coupon'
            );
        }
    }

	function _save_store_coupon($type,$post_data,$coupon_id)
	{
		$model = &m('store');
		$fields = 'store_id';
		switch($type)
		{
			
			case 1:
			$scategory_mod = &m('scategory');
			$conditions = 'cate_id '.db_create_in($scategory_mod->get_descendant($post_data));
			$join = 'has_scategory';
			$fields = 's.store_id';
			break;
			
			case 2 :
			$conditions = 'sgrade =  '.$post_data;
			break;
			
			case 3 :
			$model = &m('goods');
			$conditions = 'cate_id_1 =  '.$post_data.' OR cate_id_2 ='.$post_data.' OR cate_id_3='.$post_data.' OR cate_id_4='.$post_data;
			break;
			
			case 4 :
			$storeids = implode(',',$post_data);
			$conditions = 'store_id IN ('.$storeids.')';
			break;
		}
		$ids = $model->find(array(
			'conditions'=>$conditions,
			'join'      => $join,
			'fields'    => $fields
		));
		if($ids)
		{
			$store_coupon_mod = &m('store_coupon');
			$store_coupon_mod->drop('coupon_id='.$coupon_id);
			$result = array();
			foreach($ids as $key=>$val)
			{
				$store_coupon_mod->add(array('store_id'=>$val['store_id'],'coupon_id'=>$coupon_id));  
				$result[] = $val['store_id'];
			}	
			$result = array_unique($result);
		}
		return $result;
	}
	
    function issue()
    {
        $coupon_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (empty($coupon_id))
        {
            $this->show_warning("no_coupon");
            exit;
        }
        $this->_coupon_mod->edit($coupon_id, array('if_issue' => 1));
        if ($this->_coupon_mod->has_error())
        {
            $this->show_message($this->_coupon_mod->get_error());
            exit;
        }
        $this->show_message('issue_success',
            'back_list', 'index.php?app=coupon');
    }

    function drop()
    {
        $coupon_id = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (empty($coupon_id))
        {
            $this->show_warning('no_coupon');
            exit;
        }
        $time = gmtime();
        $coupon_ids = explode(',', $coupon_id);//vdump($this->_coupon_mod->find("((if_issue = 1 AND end_time > {$time})) AND coupon_id ".db_create_in($coupon_ids)));
        $this->_coupon_mod->drop("(if_issue = 0 OR (if_issue = 1 AND end_time < {$time})) AND coupon_id ".db_create_in($coupon_ids));
        if ($this->_coupon_mod->has_error())
        {
            $this->show_warning($this->_coupon_mod->get_error());
        }
        $this->show_message('drop_ok',
            'back_list', 'index.php?app=coupon');
    }

	function _get_scategories()
	{
		$scategory_mod = &m('scategory');
		$scategories = $scategory_mod->find('parent_id = 0');
		$data = array();
		foreach($scategories as $key=>$val)
		{
			$data[$key] = $val['cate_name'];
		}
		return $data;
	}
	
	function _get_sgrade()
	{
		$sgrade_mod = &m('sgrade');
		$sgrades = $sgrade_mod->find();
		$data = array();
		foreach($sgrades as $key=>$val)
		{
			$data[$key] = $val['grade_name'];
		}
		return $data;
	}


    function export()
    {
        $coupon_id = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (empty($coupon_id))
        {
            echo Lang::get('no_coupon');
            exit;
        }
        if (!IS_POST)
        {
			$this->import_resource(array(
				'script' => array(
					array(
						'path' => 'jquery.plugins/jquery.validate.js',
						'attr' => '',
					),
				),
			));
            header("Content-Type:text/html;charset=" . CHARSET);
            $this->assign('id', $coupon_id);
            $this->display('coupon_export.html');
        }
        else
        {
            $amount = intval(trim($_POST['amount']));
            if (empty($amount))
            {
                $this->show_warning('involid_data');
                exit;
            }
            $info = $this->_coupon_mod->get_info($coupon_id);
            $coupon_name = ecm_iconv(CHARSET, 'gbk', $info['coupon_name']);
            //header('Content-type: application/txt');
            //header('Content-Disposition: attachment; filename="coupon_' .date('Ymd'). '_' .$coupon_name.'.txt"');
            $sn_array = $this->generate($amount, $coupon_id);
			/*
            $crlf = get_crlf();
            foreach ($sn_array as $val)
            {
                echo $val['coupon_sn'] . $crlf;
            }
			*/
			$this->show_message('成功生成优惠券号码',
					'back_list', 'index.php?app=coupon');
        }
    }

    function extend()
    {
        $coupon_id = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (empty($coupon_id))
        {
            echo Lang::get('no_coupon');
            exit;
        }
        if (!IS_POST)
        {
			$this->import_resource(array(
				'script' => array(
					array(
						'path' => 'jquery.plugins/jquery.validate.js',
						'attr' => '',
					),
				),
			));
            header("Content-Type:text/html;charset=" . CHARSET);
            $this->assign('id', $coupon_id);
            $this->assign('send_model', Lang::get('send_model'));
            $this->display("coupon_extend.html");
        }
        else
        {
            if (empty($_POST['user_name']))
            {
                $this->show_warning("involid_data");
                exit;
            }
            $user_name = str_replace(array("\r","\r\n"), "\n", trim($_POST['user_name']));
            $user_name = explode("\n", $user_name);
            $user_mod =&m ('member');
            $users = $user_mod->find(db_create_in($user_name, 'user_name'));
            if (empty($users))
            {
                $this->show_warning('involid_data');
                exit;
            }
            if (count($users) > 30)
            {
                $this->show_warning("amount_gt");
                exit;
            }
            else
            {
                $users = $this->assign_user($coupon_id, $users);
                $coupon = $this->_coupon_mod->get_info($coupon_id);
                $coupon['store_name'] = Conf::get('site_name');
                $coupon['store_id'] = 0;
                $this->_message_to_user($users, $coupon);
                $this->_mail_to_user($users, $coupon);
                $this->show_message('extend_coupon_success',
					'back_list', 'index.php?app=coupon');
				}
        }
    }

    function _message_to_user($users, $coupon)
    {
        $ms =& ms();
        foreach ($users as $key => $val)
        {
            $content = get_msg('touser_send_coupon', array(
            'price' => $coupon['coupon_value'],
            'start_time' =>  local_date('Y-m-d',$coupon['start_time']),
            'end_time' => local_date("Y-m-d", $coupon['end_time']),
            'coupon_sn' => $val['coupon']['coupon_sn'],
            'min_amount' => $coupon['min_amount'],
            'url' => SITE_URL . '/' . url('app=store&id=' . $coupon['store_id']),
            'store_name' => $coupon['store_name'],
            ));
            $msg_id = $ms->pm->send(MSG_SYSTEM, $val['user_id'], '',$content);
        }
    }

    function _mail_to_user($users, $coupon)
    {
        foreach ($users as $val)
        {
            $mail = get_mail('touser_send_coupon', array('user' => $val, 'coupon' => $coupon));
            if (!$mail)
            {
                continue;
            }
            $this->_mailto($val['email'], addslashes($mail['subject']), addslashes($mail['message']));
        }
    }

    function assign_user($id, $users)
    {
        $_user_mod =& m('member');
        $count = count($users);
        $users = array_values($users);
        $arr = $this->generate($count, $id);
        $i = 0;
        foreach ($users as $key => $user)
        {
                $users[$key]['coupon'] = $arr[$i];
                $_user_mod->createRelation('bind_couponsn', $user['user_id'], array($arr[$i]['coupon_sn'] => array('coupon_sn' =>$arr[$i]['coupon_sn'],'bind_time'=>gmtime())));
                $i = $i + 1;
        }
        return $users;
    }

    function generate($num, $id)
    {
        $use_times = $this->_coupon_mod->get(array('fields' => 'use_times', 'conditions' => 'store_id = 0 AND coupon_id = ' . $id));

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
        for ($i = $pix + 1; $i <= $max; $i++ )
        {
            $cpm = sprintf("%08d", $i);
            $tmp = mt_rand(1000, 9999);
            $couponsn = 'M'.$cpm . $tmp;
            $str .= "('{$couponsn}', {$id}, {$times}),";
            $add_data[] = array(
                'coupon_sn' => $couponsn,
                'coupon_id' => $id,
                'remain_times' => $times,
                );
        }
        $string = substr($str,0, strrpos($str, ','));
        $this->_couponsn_mod->db->query("INSERT INTO {$this->_couponsn_mod->table} (coupon_sn, coupon_id, remain_times) VALUES {$string}", 'SILENT');
        return $add_data;
    }

    function _sql_insert($data)
    {
        $str = '';
        foreach ($data as $val)
        {
            $str .= "('{$val['coupon_sn']}', {$val['coupon_id']}, {$val['remain_times']}),";
        }
        $string = substr($str,0, strrpos($str, ','));
        $res = $this->_couponsn_mod->db->query("INSERT INTO {$this->_couponsn_mod->table} (coupon_sn, coupon_id, remain_times) VALUES {$string}", 'SILENT');
        $error = $this->_couponsn_mod->db->errno();
        return array('res' => $res, 'errno' => $error);
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
	   /**
     *    处理上传标志
     *
     *    @author    psmoban.com
     *    @param     int $coupon_id
     *    @return    string
     */
    function _upload_image($coupon_id)
    {
        $file = $_FILES['coupon_image'];
        if ($file['error'] == UPLOAD_ERR_NO_FILE) // 没有文件被上传
        {
            return '';
        }
        import('uploader.lib');             //导入上传类
        $uploader = new Uploader();
        $uploader->allowed_type(IMAGE_FILE_TYPE); //限制文件类型
        $uploader->addFile($_FILES['coupon_image']);//上传image
        if (!$uploader->file_info())
        {
            $this->show_warning($uploader->get_error() , 'go_back', 'index.php?app=coupon&amp;act=edit&amp;id=' . $coupon_id);
            return false;
        }
        /* 指定保存位置的根目录 */
        $uploader->root_dir(ROOT_PATH);

        /* 上传 */
        if ($file_path = $uploader->save('data/files/mall/coupon', $coupon_id))   //保存到指定目录，并以指定文件名$coupon_id存储
        {
            return $file_path;
        }
        else
        {
            return false;
        }
    }
}

?>