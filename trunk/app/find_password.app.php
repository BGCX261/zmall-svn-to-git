<?php
/**
 * 找回密码控制器
 * @author andcpp
 */
class Find_passwordApp extends MallbaseApp
{
    var $_password_mod;
    function __construct()
    {
        $this->Find_passwordApp();
    }

    function Find_passwordApp()
    {
        parent::FrontendApp();
        $this->_password_mod = &m("member");
    }

    /**
     * 显示文本框及处理提交的用户信息
     *
     */
    function index()
    {
       if(!IS_POST)
       {
           $this->import_resource('jquery.plugins/jquery.validate.js');
           $this->display("find_password.html");
       }
       else
       {
           $addr = $_SERVER['HTTP_REFERER'];
           if (empty($_POST['username']) || empty($_POST['captcha']))
           {
               $this->show_warning("unsettled_required",
                   'go_back', $addr);
               return ;
           }
           if (base64_decode($_SESSION['captcha']) != strtolower($_POST['captcha']))
           {
               $this->show_warning("captcha_faild",
                   'go_back', $addr);
               return ;
           }
           $username = trim($_POST['username']);

           /* 简单验证是否是该用户 */
           $ms =& ms();     //连接用户系统
           $info = $ms->user->get($username, true);
           if (empty($info))
           {
               $this->show_warning('not_exist',
                   'go_back', $addr);

               return;
           }
		   $this->import_resource('jquery.plugins/jquery.validate.js');
		   $mod_member = &m('member');
		   $info1 = $mod_member->get($info['user_id']);
		   $this->assign('user',$info1);
		   $model_msg = &af('msg');
			$setting = $model_msg->getAll(); //载入设置数据
			if ($setting['msg_status']['find_password'])
            {
                $this->assign('phone_code', 1);
            }
		   $this->assign('email',substr_replace($info['email'], '****', 3, -10));
		   $this->assign('phone_mob',substr_replace($info1['phone_mob'], '****', 3, -4));
           $this->display("find_password.step2.html");
       }
    }

    /**
     * 显示设置密码及处理提交的新密码信息
     *
     */
    function set_password()
    {
        if (!IS_POST)
        {
            if (!isset($_GET['id']) || !isset($_GET['key']) || empty($_GET['key']))
            {
                $this->show_warning("request_error",
                    'back_index', 'index.php');
                return ;
            }
			$id = intval(trim($_GET['id']));
            $res = $this->_password_mod->get_info($id);
            if (($_SESSION['email_code'] || $_SESSION['phone_code']) && ($res['activation'] == $_GET['key']))
            {
                $this->import_resource('jquery.plugins/jquery.validate.js');
            	$this->display("find_password.step3.html");
            }
			else
			{
				$this->show_warning("err_bad_url",
                    'back_index', 'index.php');
                return ;
			}
            
        }
        else
        {
            if (empty($_POST['new_password']) || empty($_POST['confirm_password']))
            {
                $this->show_warning("unsettled_required");
                return ;
            }
            if (trim($_POST['new_password']) != trim($_POST['confirm_password']))
            {
                $this->show_warning("password_not_equal");
                return ;
            }
            $password = trim($_POST['new_password']);
            $passlen = strlen($password);
            if ($passlen < 6 || $passlen > 20)
            {
                $this->show_warning('password_length_error');

                return;
            }
            $id = intval($_GET['id']);
            $ms =& ms();        //连接用户系统
            $ms->user->edit($id, '', array('password' => $password), true); //强制修改
            if ($ms->user->has_error())
            {
                $this->show_warning($ms->user->get_error());

                return;
            }
			$ret = $this->_password_mod->edit($id, array('activation' => ''));
			$_SESSION['email_code'] = '';
			$_SESSION['phone_code'] = '';
            $this->display("find_password.step4.html");
            return ;
        }

    }
	
	//发送注册验证 by psmb-andcpp
	function sendcode()
	{
		if (!IS_POST)
        {
            $this->show_warning('Hacking Attempt');
			return;
        }
        else
        {
			$code = $_POST['code'];
			$phone_mob = $_POST['phone_mob'];
			$smsText = sprintf(Lang::get('your_check_code'),$code);
			$res = $this->Sms_Get('SMS_Send',$phone_mob,$smsText,0);
			if ($res>0)
			{
				$_SESSION['phone_code'] = md5($phone_mob.$code);
				$_SESSION['last_send_time_phone_code'] = time();
				$this->json_result('','success');
			}
			else
			{
				$this->json_error('msg_send_failure');
			}
        }
	}
	
	function sendemail()
	{
		if (!IS_POST)
        {
			$this->show_warning('Hacking Attempt');
			return;
        }
        else
        {
            $code = trim($_POST['code']);
			$email = trim($_POST['email']);
			$username = trim($_POST['username']);
            $ms =& ms(); 
            $info = $ms->user->get($username, true);
			$mail = get_mail('touser_send_code', array('user' => $info, 'word' => $code));
			$mailer =& get_mailer();
			$mail_result = $mailer->send($email, addslashes($mail['subject']), addslashes($mail['message']), CHARSET, 1);
			if ($mail_result)
            {
				$_SESSION['email_code'] = md5($email.$code);
				$_SESSION['last_send_time_email_code'] = time();
                $this->json_result('', 'mail_send_succeed');
            }
            else
            {
                $this->json_error('mail_send_failure', implode("\n", $mailer->errors));
            }
       }
		
	}
	
	function step2()
	{
		if(!IS_POST)
		{
			$this->show_warning('Hacking Attempt');
			return;
		}
		else
		{
			$addr = $_SERVER['HTTP_REFERER'];
			$phone_check_code = trim($_POST['phone_check_code']);
			$email_check_code = trim($_POST['email_check_code']);
			$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
			if(!$user_id)
			{
				$this->show_warning('not_exist');
				return;
			}
			$model_member = &m('member');
			$member_info = $model_member->get($user_id);
			if(empty($email_check_code) && empty($phone_check_code))
			{
				$this->show_warning('captcha_required');
				return;
			}
			if($email_check_code)
			{
				$email_key = md5($member_info['email'].$email_check_code);
				if((empty($email_check_code) || $email_key != $_SESSION['email_code']))
				{
					$this->show_warning('err_check_code_err','go_back', $addr);
					return;
				}
				if((!isset($_SESSION['last_send_time_email_code']) || (time()-$_SESSION['last_send_time_email_code'])>120))
				{
					$this->show_warning('err_check_code_timeout','go_back', $addr);
					return;	
				}
				
				$ret = $this->_password_mod->edit($user_id, array('activation' => $_SESSION['email_code']));

				header('Location: index.php?app=find_password&act=set_password&id='.$user_id.'&key='.$email_key);
			}
			elseif($phone_check_code)
			{
				$phone_key = md5($member_info['phone_mob'].$phone_check_code);
				if((empty($phone_check_code) || $phone_key != $_SESSION['phone_code']))
				{
					$this->show_warning('err_check_code_err','go_back', $addr);
					return;
				}
				if((!isset($_SESSION['last_send_time_phone_code']) || (time()-$_SESSION['last_send_time_phone_code'])>120))
				{
					$this->show_warning('err_check_code_timeout','go_back', $addr);
					return;	
				}
				$ret = $this->_password_mod->edit($user_id, array('activation' => $_SESSION['phone_code']));
				header('Location: index.php?app=find_password&act=set_password&id='.$user_id.'&key='.$phone_key);
			}
			else
			{
				$this->show_warning('captcha_required');
				return;
			}
		}
	}
	//end
	
}
?>
