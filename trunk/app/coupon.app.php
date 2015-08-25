<?php

class CouponApp extends StoreadminbaseApp
{
    var $_coupon_mod;
    var $_store_id;
    var $_store_mod;
    var $_couponsn_mod;
    function __construct()
    {
        $this->CouponApp();
    }
    function CouponApp()
    {
        parent::__construct();
        $this->_store_id  = intval($this->visitor->get('manage_store'));
        $this->_store_mod =& m('store');
        $this->_coupon_mod =& m('coupon');
        $this->_couponsn_mod =& m('couponsn');
    }
    function index()
    {
        $page = $this->_get_page(10);
        $coupon = $this->_coupon_mod->find(array(
            'conditions' => 'store_id = '.$this->_store_id,
			'order' => 'coupon_id desc',
            'limit' => $page['limit'],
            'count' => true,
        ));
        $this->_curlocal(LANG::get('member_center'), 'index.php?app=member',
                         LANG::get('coupon'), 'index.php?app=coupon',
                         LANG::get('coupons_list'));
        $page['item_count'] = $this->_coupon_mod->getCount();
        $this->_format_page($page);
        $this->assign('page_info', $page);

        $this->_curitem('coupon');
        $this->_curmenu('store_coupons');
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('coupon'));
        $this->assign('coupons', $coupon);
        $this->import_resource(array(
            'script' => array(
                array(
                    'path' => 'dialog/dialog.js',
                    'attr' => 'id="dialog_js"',
                ),
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
        $this->assign('time', gmtime());
        $this->display('coupon.index.html');
    }
	
	function mall_coupon()
    {
        $page = $this->_get_page(10);
		$coupon = $this->_get_mall_coupon(false,$page['limit']);	
		$this->_curlocal(LANG::get('member_center'), 'index.php?app=member',
                         LANG::get('coupon'), 'index.php?app=coupon',
                         LANG::get('coupons_list'));
		$page['item_count'] = $this->_get_mall_coupon(true);			 
        $this->_format_page($page);
        $this->assign('page_info', $page);

        $this->_curitem('coupon');
        $this->_curmenu('mall_coupons');
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('coupon'));
        $this->assign('coupons', $coupon);
		$this->assign('type', 'mall_coupon');
        $this->assign('time', gmtime());
        $this->display('coupon.index.html');
    }
	
	function _get_mall_coupon($count=false,$limit='')
	{
		!empty($limit) && $add_limit = ' LIMIT '.$limit;
		$store_coupon_mod = &m('store_coupon');
		$coupon = $store_coupon_mod->getAll("SELECT * FROM {$store_coupon_mod->table} sc LEFT JOIN {$this->_coupon_mod->table} c ON sc.coupon_id=c.coupon_id WHERE c.if_issue = 1 AND sc.store_id=".$this->_store_id.' AND c.end_time > '.gmtime().' ORDER BY c.coupon_id desc'.$add_limit);
		if($count) return count($coupon);else return $coupon;
	}
	
	
	
    function add()
    {
        if (!IS_POST)
        {
            header("Content-Type:text/html;charset=" . CHARSET);
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
                $this->pop_warning('coupon_value_not');
                exit;
            }
            if (empty($use_times))
            {
                $this->pop_warning('use_times_not_zero');
                exit;
            }
            if ($min_amount < 0)
            {
                $this->pop_warning("min_amount_gt_zero");
                exit;
            }
            $start_time = gmstr2time(trim($_POST['start_time']));
            $end_time = gmstr2time_end(trim($_POST['end_time'])) - 1 ;
            if ($end_time < $start_time)
            {
                $this->pop_warning('end_gt_start');
                exit;
            }
            $coupon = array(
                'coupon_name' => trim($_POST['coupon_name']),
                'coupon_value' => $coupon_value,
                'store_id' => $this->_store_id,
                'use_times' => $use_times,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'min_amount' => $min_amount,
                'if_issue'  => trim($_POST['if_issue']) == 1 ? 1 : 0,
				'show_in_list'  => $_POST['show_in_list'],
            );
            $coupon_id = $this->_coupon_mod->add($coupon);
            if ($this->_coupon_mod->has_error())
            {
                $this->pop_warning($this->_coupon_mod->get_error());
                exit;
            }
			
			/* 处理上传的图片 */
            $coupon_image       =   $this->_upload_image($coupon_id);
            $coupon_image && $this->_coupon_mod->edit($coupon_id, array('coupon_image' => $coupon_image));
			
            $this->pop_warning('ok', 'coupon_add');
        }
    }

    function edit()
    {
        $coupon_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (empty($coupon_id))
        {
            echo Lang::get("no_coupon");
        }
        if (!IS_POST)
        {
            header("Content-Type:text/html;charset=" . CHARSET);
            $coupon = $this->_coupon_mod->get_info($coupon_id);
            $this->assign('coupon', $coupon);
            $this->display('coupon.form.html');
        }
        else
        {
            $coupon_value = floatval(trim($_POST['coupon_value']));
            $use_times = intval(trim($_POST['use_times']));
            $min_amount = floatval(trim($_POST['min_amount']));
            if (empty($coupon_value) || $coupon_value < 0 )
            {
                $this->pop_warning('coupon_value_not');
                exit;
            }
            if (empty($use_times))
            {
                $this->pop_warning('use_times_not_zero');
                exit;
            }
            if ($min_amount < 0)
            {
                $this->pop_warning("min_amount_gt_zero");
                exit;
            }
            $start_time = gmstr2time(trim($_POST['start_time']));
            $end_time = gmstr2time_end(trim($_POST['end_time']))-1;
            //echo gmstr2time_end(trim($_POST['end_time'])) . '-------' .$end_time;exit; 
            if ($end_time < $start_time)
            {
                $this->pop_warning('end_gt_start');
                exit;
            }
            $coupon = array(
                'coupon_name' => trim($_POST['coupon_name']),
                'coupon_value' => $coupon_value,
                'store_id' => $this->_store_id,
                'use_times' => $use_times,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'min_amount' => $min_amount,
                'if_issue'  => trim($_POST['if_issue']) == 1 ? 1 : 0,
				'show_in_list'  => $_POST['show_in_list'],
            );
			
			 /* 处理上传的图片 */
            $coupon_image       =   $this->_upload_image($coupon_id);
            if ($coupon_image)
            {
              $coupon['coupon_image'] = $coupon_image;
            }
			
            $this->_coupon_mod->edit($coupon_id, $coupon);
            if ($this->_coupon_mod->has_error())
            {
                $this->pop_warning($this->_coupon_mod->get_error());
                exit;
            }
            $this->pop_warning('ok','coupon_edit');
        }
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
            header("Content-Type:text/html;charset=" . CHARSET);
            $this->assign('id', $coupon_id);
            $this->display('coupon_export.html');
        }
        else
        {
            $amount = intval(trim($_POST['amount']));
            if (empty($amount))
            {
                $this->pop_warning('involid_data');
                exit;
            }
            $info = $this->_coupon_mod->get_info($coupon_id);
            $coupon_name = ecm_iconv(CHARSET, 'gbk', $info['coupon_name']);
            header('Content-type: application/txt');
            header('Content-Disposition: attachment; filename="coupon_' .date('Ymd'). '_' .$coupon_name.'.txt"');
            $sn_array = $this->generate($amount, $coupon_id);
            $crlf = get_crlf();
            foreach ($sn_array as $val)
            {
                echo $val['coupon_sn'] . $crlf;
            }
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
            header("Content-Type:text/html;charset=" . CHARSET);
            $this->assign('id', $coupon_id);
            $this->assign('send_model', Lang::get('send_model'));
            $this->display("coupon_extend.html");
        }
        else
        {
            if (empty($_POST['user_name']))
            {
                $this->pop_warning("involid_data");
                exit;
            }
            $user_name = str_replace(array("\r","\r\n"), "\n", trim($_POST['user_name']));
            $user_name = explode("\n", $user_name);
            $user_mod =&m ('member');
            $users = $user_mod->find(db_create_in($user_name, 'user_name'));
            if (empty($users))
            {
                $this->pop_warning('involid_data');
                exit;
            }
            if (count($users) > 30)
            {
                $this->pop_warning("amount_gt");
                exit;
            }
            else
            {
                $users = $this->assign_user($coupon_id, $users);
                $store = $this->_store_mod->get_info($this->_store_id);
                $coupon = $this->_coupon_mod->get_info($coupon_id);
                $coupon['store_name'] = $store['store_name'];
                $coupon['store_id'] = $this->_store_id;
                $this->_message_to_user($users, $coupon);
                $this->_mail_to_user($users, $coupon);
                $this->pop_warning("ok","coupon_extend");
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
        $use_times = $this->_coupon_mod->get(array('fields' => 'use_times', 'conditions' => 'store_id = ' . $this->_store_id . ' AND coupon_id = ' . $id));

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
            $couponsn = 'S'.$cpm . $tmp;
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

	function agree()
	{
		$agree = !empty($_GET['agree']) ? $_GET['agree'] : 1;
		$coupon_id = $_GET['id'];
		if(empty($coupon_id))
		{
			return;
		}
		$store_coupon_mod = &m('store_coupon');
		if($store_coupon_mod->edit('coupon_id='.$coupon_id.' AND store_id='.$this->_store_id,array('if_agree'=>$agree)))
		{
			$this->json_result(array('type'=>$agree));
		}
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

    function _get_member_submenu()
    {
        $menus = array(
            array(
                'name'  => 'store_coupons',
                'url'   => 'index.php?app=coupon',
            ),
			array(
                'name'  => 'mall_coupons',
                'url'   => 'index.php?app=coupon&act=mall_coupon',
            ),
        );
        return $menus;
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
            $this->show_warning($uploader->get_error());
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