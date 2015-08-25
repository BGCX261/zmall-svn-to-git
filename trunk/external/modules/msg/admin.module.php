<?php

class MsgModule extends AdminbaseModule
{
	var $mod_msg; 
	var $mod_msglog;
	
    function __construct()
    {
        $this->MsgModule();
    }

    function MsgModule()
    {
        parent::__construct();	
		$this->mod_msg =& m('msg');
		$this->mod_msglog =& m('msglog');
    }

 	function index()
    {
		$search_options = array(
            'to_mobile'   => Lang::get('post_phone'),
            'user_name'   => Lang::get('user_name'),
        );
		$this->assign('search_options', $search_options);
		$field = 'seller_name';
        array_key_exists($_GET['field'], $search_options) && $field = $_GET['field'];
		$conditions = $this->_get_query_conditions(array(
			array(
                'field' => $field,       //按用户名,店铺名,支付方式名称进行搜索
                'equal' => 'LIKE',
                'name'  => 'search_name',
            ),
			array(
                'field' => 'state',         //可搜索字段title
                'equal' => '=',          //等价关系,可以是LIKE, =, <, >, <>
            ),
        ));
		$page = $this->_get_page(10);		
		$index=$this->mod_msglog->find(array(
	        'conditions' => 'type=0'.$conditions,
			'join' => 'belongs_to_user',
			'fields' => 'this.*,member.user_name',
            'limit' => $page['limit'],
			'order' => "id desc",
			'count' => true));
		$page['item_count'] = $this->mod_msglog->getCount();
        $this->_format_page($page);
	    $this->assign('page_info', $page);
		$this->assign('filtered', $conditions? 1 : 0); //是否有查询条件
		$this->assign('state_list', array(
			1 => Lang::get('send_success'),
            0 => Lang::get('send_failed'),
        ));
	    $this->assign('index', $index);//传递到风格里
        $this->display('index.html');
	}

	function user()
    {
		$condition = $this->_get_query_conditions(array(array(
                'field' => 'user_name',         //可搜索字段user_name
                'equal' => 'LIKE',          //等价关系,可以是LIKE, =, <, >, <>
            ),
        ));
		$page = $this->_get_page(10);		
		$user = $this->mod_msg->find(array(
	        'conditions' => '1=1'.$condition,
			'join'       => 'belongs_to_user',
			'fields'     => 'this.*,member.user_name,phone_mob',
            'limit' => $page['limit'],
			'order' => "id desc",
			'count' => true
		));
		$page['item_count'] = $this->mod_msg->getCount();
        $this->_format_page($page);
		
		$checked_functions = $functions = array();
        $functions = $this->_get_msg_functions();
		foreach($user as $key => $u)
		{
			$tmp[$key] = explode(',', $u['functions']);
			if ($functions)
			{
				 foreach ($functions as $func)
				 {
					 $checked_functions[$key][$func] = in_array($func, $tmp[$key]);
				 }
			}
		}
        
        
		$this->assign('functions', $functions);	
		$this->assign('checked_functions', $checked_functions);
	    $this->assign('page_info', $page);
	    $this->assign('user', $user);
       $this->display('user.html');
	}
	
	function statist()
	{
		// 可使用的短信数
		$available = $this->Sms_Get('SMS_Num');
		if($available < 0){
			$available = 0;
		}
		
		// 已使用的短信数
		$msgstatistics_mod = &m('msgstatistics');
		$msgstatistics = $msgstatistics_mod->get(0);
		$used = $msgstatistics['used'];
		
		// 已分配但未使用的短信数
		$allocated = 0;
		$msg_mod = &m('msg');
		$allmsg = $msg_mod->find(array('conditions'=>'','fields'=>'num'));
		foreach($allmsg as $key=>$val)
		{
			$allocated += $val['num'];
		}
		
		// 可用于分配的短信数
		$distributable = $available - $allocated;
		
		return array('available' => $available, 'distributable' => $distributable, 'used' => $used, 'allocated' => $allocated);
	}
	   
 	function add()
    {	
		$statist = $this->statist();
		$this->assign('statist', $statist);
		$user_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
		if(!IS_POST)
		{
			if(!empty($user_id))
			{
				$user = $this->mod_msg->get(array(
					'conditions' => 'msg.user_id='.$user_id,
					'join' => 'belongs_to_user',
					'fields' => 'this.*,member.user_name'
				));
			}
			$this->assign('user', $user);
			$this->display('add.html');
		}
		else
		{
			$user_name = trim($_POST['user_name']);
		   	$num = intval($_POST['num']);
		   	$add_dec = intval($_POST['add_dec']);
		   	$log_text = trim($_POST['log_text']);	
		   	if(empty($user_name) || empty($num))
		   	{
				$this->show_warning('err_no_null');
				return;
		   	}  
		   	if (preg_match("/[^0.-9]/",$num))
		   	{
			   $this->show_warning('err_not_num'); 
			   return;
		   	} 
			if($num > $statist['distributable']) {
				$this->show_warning(sprintf(Lang::get('distributable_note'),$statist['distributable'])); 
			   	return;
			}
			
		   	$row_msg = $this->mod_msg->get(array(
				'conditions' => "user_name='{$user_name}'",
				'join' => 'belongs_to_user',
				'fields' => 'msg.num,msg.user_id',
			));	
		   	if($row_msg)
		   	{
			   $num_old = $row_msg['num']; 
			   $id = $row_msg['user_id'];
			   if($add_dec)
			   {
					$num_new = $num_old + $num;
			   }
			   else
			   {
				   if($num_old >= $num)
				   {	   
						$num_new = $num_old - $num;
				   }
				   else
				   {
						$this->show_warning('err_num_smaller');
						return;
				   }
			   } 
			   $this->mod_msg->edit("user_id='{$id}'",array('num' => $num_new));
			   $edit_msglog = array(
			   		'user_id' => $id,
					'content' => $log_text,
					'quantity' => $num, //记录分配数目
					'state' => $add_dec, //1为增加，0为减少
					'type' => 1,//1为分配短信，0为发送短信
					'time' => gmtime(), 
			   );
			   $this->mod_msglog->add($edit_msglog);
			   $this->show_message('add_msgnum_successed',
                	'back_list',    'index.php?module=msg&amp;act=user',
                	'continue_add', 'index.php?module=msg&amp;act=add&amp;user_id='.$user_id
            	);
		    }
		    else
		    {
			   $this->show_warning('err_no_user'); 
			   return;
		    } 
		}
	}
	
	function send()
    {
        if (!IS_POST)
		{
			$this->display('send.html');
        }
        else
        {
			$mobile	 = $_POST['to_mobile'];	//号码
			$smsText = trim($_POST['msg_content']);//内容
			if(empty($mobile))
			{
				$this->show_warning('phone_no_null');
				return;
			}
			if(empty($smsText))
			{
				$this->show_warning('content_no_null');
				return;
			}
			$res = $this->Sms_Get('SMS_Send',$mobile,$smsText,0);
			if($res>0)
			{
				$this->show_message('send_msg_successed', 'go_back', 'index.php?module=msg');
				return;
			}
			else
			{
				$this->show_message('send_msg_faild', 'go_back', 'index.php?module=msg');
				return;
			}
        }
    }
	
	function drop()
    {
        $ids = isset($_GET['id']) ? trim($_GET['id']) : 0;
        if (!$ids)
        {
            $this->show_warning('no_such_log');

            return;
        }
        $ids = explode(',', $ids);//获取一个类似array(1, 2, 3)的数组
        if (!$this->mod_msglog->drop($ids))    //删除
        {
            $this->show_warning($this->mod_msglog->get_error());

            return;
        }

        $this->show_message('drop_successed');
    }
	
	function setting()
    {
		$model_msg = &af('msg');
        $setting = $model_msg->getAll(); //载入设置数据
        if (!IS_POST)
        {
            $this->assign('setting', $setting);
            $this->display('setting.html');
        }
        else
        {
            $data['msg_pid']     = $_POST['msg_pid'];
            $data['msg_key']     = $_POST['msg_key'];
			$data['msg_status']  = empty($_POST['msg_status']) ? array() : $_POST['msg_status'];
            $model_msg->setAll($data);
            $this->show_message('setting_successed');
        }
    }
}
?>