<?php

/**
     *    手机短信
     *
     *    @author    andcpp
     *    @return    void
*/
	 
class MsgApp extends StoreadminbaseApp
{
	var $mod_msg;
	var $mod_msglog;
    
	function __construct()
    {
        $this->MsgApp();
    }

    function MsgApp()
    {
        parent::__construct();
		$this->msginit();
    }
	
    function index()
    {
        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'),   'index.php?app=member',
                         LANG::get('msg'),         'index.php?app=msg',
                         LANG::get('set')
                         );

        /* 当前所处子菜单 */
        $this->_curmenu('set');
        /* 当前用户中心菜单 */
        $this->_curitem('msg');

		$user_id = $this->visitor->get('user_id');
		$row_msg = $this->mod_msg->get(array(
			'conditions' => 'msg.user_id='.$user_id,
			'join' => 'belongs_to_user',
			'fields' => 'this.*,phone_mob'
		));
		if (!IS_POST)
        {
			$this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('msg'));
			
			$checked_functions = $functions = array();
            $functions = $this->_get_msg_functions();
            $tmp = explode(',', $row_msg['functions']);
            if ($functions)
            {
                foreach ($functions as $func)
                {
                    $checked_functions[$func] = in_array($func, $tmp);
                }
            }
			$ret_url = SITE_URL . '/index.php?app=msg';
			$this->assign('ret_url', rawurlencode($ret_url));
			$this->assign('user',$row_msg);
			$this->assign('functions', $functions);
			$this->assign('checked_functions', $checked_functions);
			$this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js',
            ));
			$this->display('msg.index.html');
		}
		else
		{
			$functions = isset($_POST['functions']) ? implode(',', $_POST['functions']) : '';
			$data = array(
                'state' => intval($_POST['state']),
                'functions'    => $functions,
            );
			$this->mod_msg->edit('user_id='.$user_id,$data);
            $this->show_message('set_ok',
                'back_list',    'index.php?app=msg'
            );
		}
    }
    /**
     *    发送短消息
     *
     *    @author    Hyber
     *    @return    void
     */
    function send()
    {

        if (!IS_POST){
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),   'index.php?app=member',
                             LANG::get('msg'),         'index.php?app=msg',
                             LANG::get('sendmsg')
                             );
            /* 当前所处子菜单 */
            $this->_curmenu('sendmsg');
            /* 当前用户中心菜单 */
            $this->_curitem('msg');

            header('Content-Type:text/html;charset=' . CHARSET);

            //引入jquery表单插件
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js',
            ));
            $this->_config_seo('title', Lang::get('user_center') . ' - ' . Lang::get('sendmsg'));
            $this->display('msg.send.html');
        }
        else
        {	
			$mobile	 = $_POST['to_mobile'];	//号码
			$smsText = trim($_POST['msg_content']);		//内容
			$user_id = $this->visitor->get('user_id');
			$row_msg = $this->mod_msg->get("user_id=".$user_id);
			if($row_msg['state'] == 1 && $row_msg['num']>0 && !empty($mobile) && !empty($smsText))
			{
				$res = $this->Sms_Get('SMS_Send',$mobile,$smsText,$user_id);
				if($res>0)
				{
					$this->show_message('send_msg_successed', 'go_back', 'index.php?app=msg');
					return;
				}
				else
				{
					$this->show_warning('err_send_faild');
					return;
				}
			}
			else
			{
				$this->show_warning('err_send_faild');
				return;
			}

        }
    }
	
	function log()
    {
		 /* 当前位置 */
         $this->_curlocal(LANG::get('member_center'),   'index.php?app=member',
                             LANG::get('msg'),         'index.php?app=msg',
                             LANG::get('sendlog')
                             );
        /* 当前所处子菜单 */
        $this->_curmenu('sendlog');
        /* 当前用户中心菜单 */
        $this->_curitem('msg');
        $this->_config_seo('title', Lang::get('user_center') . ' - ' . Lang::get('sendlog'));
		$mod_msglog = &m('msglog');
		$page = $this->_get_page(10);		
		$msglog = $mod_msglog->find(array(
	        'conditions' => 'type=0 and state=1 and user_id='.$this->visitor->get('user_id'),
            'limit' => $page['limit'],
			'order' => "id desc",
			'count' => true));
		$page['item_count'] = $mod_msglog->getCount();
        $this->_format_page($page);
	    $this->assign('page_info', $page);
	    $this->assign('msglog', $msglog);
        $this->display('msg.log.html');
	}
	
	/*三级菜单*/
    function _get_member_submenu()
    {
        $array = array(
            array(
                'name' => 'set',
                'url' => 'index.php?app=msg',
            ),
            array(
                'name' => 'sendmsg',
                'url' => 'index.php?app=msg&act=send',
            ),
			array(
                'name' => 'sendlog',
                'url' => 'index.php?app=msg&act=log',
            ),
        );
        return $array;
    }
	
	function msginit()
	{
		$filename = ROOT_PATH . '/data/msg.inc.php';
		if (!file_exists($filename)){
			header('Location:index.php?app=member');
			exit;
		}
		$this->mod_msg =& m('msg');
		$this->mod_msglog =& m('msglog');
		$user_id = $this->visitor->get('user_id');
		$msg_id =$this->mod_msg->get("user_id='{$user_id}'");
		$mod_store = &m('store');
		$member_id = $mod_store->get($user_id); 
		if(!$msg_id && $member_id) //msg没有该user，并且store表里面有（未被删除）的时候增加到msg表
		{
			$this->mod_msg->add(array(
				"user_id" => $user_id,
			));
		}
	}
}

?>
