<?php

/**
 *    前台控制器基础类
 *
 *    @author    Garbin
 *    @usage    none
 */
class FrontendApp extends ECBaseApp
{
    function __construct()
    {
        $this->FrontendApp();
    }
    function FrontendApp()
    {
        Lang::load(lang_file('common'));
        Lang::load(lang_file(APP));
        parent::__construct();

        // 判断商城是否关闭
        if (!Conf::get('site_status'))
        {
            $this->show_warning(Conf::get('closed_reason'));
            exit;
        }
        # 在运行action之前，无法访问到visitor对象
    }
    function _config_view()
    {
        parent::_config_view();
        $this->_view->template_dir  = ROOT_PATH . '/themes';
        $this->_view->compile_dir   = ROOT_PATH . '/temp/compiled/mall';
        $this->_view->res_base      = SITE_URL . '/themes';
        $this->_config_seo(array(
            'title' => Conf::get('site_title'),
            'description' => Conf::get('site_description'),
            'keywords' => Conf::get('site_keywords')
        ));
		
		// 快递跟踪插件需要新建表 psmb
		$this->_create_table();
    }
    function display($tpl)
    {
		$this->_create_table();
		
        $cart =& m('cart');
        $this->assign('cart_goods_kinds', $cart->get_kinds(SESS_ID, $this->visitor->get('user_id')));
		
		/* 用于前台判断快递跟踪插件是否安装 psmb */
		$this->assign('enable_express', $this->_check_express_plugin());
		$this->auto_refuse();
		
        /* 新消息 */
        $this->assign('new_message', isset($this->visitor) ? $this->_get_new_message() : '');
        $this->assign('navs', $this->_get_navs());  // 自定义导航
        $this->assign('acc_help', ACC_HELP);        // 帮助中心分类code
        $this->assign('site_title', Conf::get('site_title'));
        $this->assign('site_logo', Conf::get('site_logo'));
        $this->assign('statistics_code', Conf::get('statistics_code')); // 统计代码
        $current_url = explode('/', $_SERVER['REQUEST_URI']);
        $count = count($current_url);
		$this->assign('qqconnect_open', $this->_get_enabled_plugins('on_qq_login', 'qqconnect') ? 1 : 0);
			$this->assign('xwbconnect_open', $this->_get_enabled_plugins('on_xwb_login', 'xwbconnect') ? 1 : 0);
			$this->assign('alipayconnect_open', $this->_get_enabled_plugins('on_alipay_login', 'alipayconnect') ? 1 : 0);
        $this->assign('current_url',  $count > 1 ? $current_url[$count-1] : $_SERVER['REQUEST_URI']);// 用于设置导航状态(以后可能会有问题)

        parent::display($tpl);
    }
	function _create_table()
	{
		$mod =&m('privilege');
		
		$result = $mod->db->getAll('SHOW COLUMNS FROM '. DB_PREFIX . 'member');
		$fields = array();
		foreach($result as $v) {
			$fields[] = $v['Field'];
		}
		if(!in_array('r_id', $fields)){
			$sql = 'ALTER TABLE `'.DB_PREFIX.'member` ADD `r_id` INT(5) NOT NULL';
			$mod->db->query($sql);
		}
		if(!in_array('r_name', $fields)){
			$sql = 'ALTER TABLE `'.DB_PREFIX.'member` ADD `r_name` varchar(25) NOT NULL';
			$mod->db->query($sql);
		}
		
		$result = $mod->db->getAll('SHOW COLUMNS FROM '. DB_PREFIX . 'coupon');
		$fields = array();
		foreach($result as $v) {
			$fields[] = $v['Field'];
		}
		if(!in_array('type', $fields)){
			$sql = 'ALTER TABLE `'.DB_PREFIX.'coupon` ADD `type` tinyint(1) NOT NULL';
			$mod->db->query($sql);
		}
		if(!in_array('tip', $fields)){
			$sql = 'ALTER TABLE `'.DB_PREFIX.'coupon` ADD `tip` TEXT NOT NULL';
			$mod->db->query($sql);
		}
		if(!in_array('type_content', $fields)){
			$sql = 'ALTER TABLE `'.DB_PREFIX.'coupon` ADD `type_content` varchar(255) NOT NULL';
			$mod->db->query($sql);
		}
		
		if(!in_array('coupon_image', $fields)){
			$sql = 'ALTER TABLE `'.DB_PREFIX.'coupon` ADD `coupon_image` varchar(255) NOT NULL';
			$mod->db->query($sql);
		}
		
		if(!in_array('show_in_list', $fields)){
			$sql = 'ALTER TABLE `'.DB_PREFIX.'coupon` ADD `show_in_list` tinyint(1) NOT NULL';
			$mod->db->query($sql);
		}
		
		$sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."store_coupon` (
			`item_id` int(25) NOT NULL auto_increment,
  			`store_id` int(25) NOT NULL,
  			`coupon_id` int(25) NOT NULL,
			`if_agree` tinyint(1) NULL,
			PRIMARY KEY  (`item_id`)
		) ENGINE = MYISAM DEFAULT CHARSET=".str_replace('-','',CHARSET).";";
		$mod->db->query($sql);
		
		$sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."member_bind` (
  			`openid` varchar(255) NOT NULL,
  			`user_id` int(11) NOT NULL,
  			`app` varchar(50) NOT NULL
		) ENGINE = MYISAM DEFAULT CHARSET=".str_replace('-','',CHARSET).";";
		$mod->db->query($sql);
		$sql = 'CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'cate_pvs` (
  			`cate_id` int(11) NOT NULL,
  			`pvs` text NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET='.str_replace('-','',CHARSET).';';
		$mod->db->query($sql);
	
		$sql = 'CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'goods_prop` (
  			`pid` int(11) NOT NULL auto_increment,
  			`name` varchar(50) NOT NULL,
  			`status` int(1) NOT NULL,
  			`sort_order` int(11) NOT NULL,
  			PRIMARY KEY  (`pid`)
		) ENGINE=MyISAM  DEFAULT CHARSET='.str_replace('-','',CHARSET).';';
		$mod->db->query($sql);
	
		$sql = 'CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'goods_prop_value` (
  			`vid` int(11) NOT NULL auto_increment,
  			`pid` int(11) NOT NULL,
  			`prop_value` varchar(255) NOT NULL,
  			`status` int(1) NOT NULL,
  			`sort_order` int(11) NOT NULL,
  			PRIMARY KEY  (`vid`)
		) ENGINE=MyISAM  DEFAULT CHARSET='.str_replace('-','',CHARSET).';';
		$mod->db->query($sql);
	
		$sql = 'CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'goods_pvs` (
  			`goods_id` int(11) NOT NULL,
  			`pvs` text NOT NULL,
  			PRIMARY KEY  (`goods_id`)
		) ENGINE=MyISAM DEFAULT CHARSET='.str_replace('-','',CHARSET).';';
		$mod->db->query($sql);
			$result = $mod->db->getAll('SHOW COLUMNS FROM '. DB_PREFIX . 'order');
		$fields = array();
		foreach($result as $v) {
			$fields[] = $v['Field'];
		}
		if(!in_array('express_company', $fields)){
			$sql = 'ALTER TABLE `'.DB_PREFIX.'order` ADD `express_company` VARCHAR( 50 ) NOT NULL AFTER `invoice_no` ';
			$mod->db->query($sql);
		}
		
		$sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."waiter` (
  			`waiter_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  			`store_id` int(10) unsigned NOT NULL,
  			`waiter_name` varchar(60) NOT NULL DEFAULT '',
  			`email` varchar(60) NOT NULL DEFAULT '',
  			`password` varchar(32) NOT NULL DEFAULT '',
  			`real_name` varchar(60) DEFAULT NULL,
  			`gender` tinyint(3) unsigned NOT NULL DEFAULT '0',
  			`birthday` date DEFAULT NULL,
  			`phone_tel` varchar(60) DEFAULT NULL,
  			`phone_mob` varchar(60) DEFAULT NULL,
  			`im_qq` varchar(60) DEFAULT NULL,
			`im_ww` varchar(60) DEFAULT NULL,
  			`im_msn` varchar(60) DEFAULT NULL,
  			`im_skype` varchar(60) DEFAULT NULL,
  			`im_yahoo` varchar(60) DEFAULT NULL,
  			`reg_time` int(10) unsigned DEFAULT '0',
  			`last_login` int(10) unsigned DEFAULT NULL,
  			`last_ip` varchar(15) DEFAULT NULL,
  			`logins` int(10) unsigned NOT NULL DEFAULT '0',
  			PRIMARY KEY (`waiter_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=".str_replace('-','',CHARSET).';';
		$mod->db->query($sql);
		
		$sql = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."waiter_priv` (
  			`waiter_id` int(10) unsigned NOT NULL DEFAULT '0',
  			`store_id` int(10) unsigned NOT NULL DEFAULT '0',
  			`privs` text NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=".str_replace('-','',CHARSET).';';
		$mod->db->query($sql);
		$sql = 'CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'promotion` (
  			`pro_id` int(11) NOT NULL auto_increment,
  			`goods_id` int(11) NOT NULL,
  			`pro_name` varchar(50) NOT NULL,
  			`pro_desc` varchar(255) NOT NULL,
  			`start_time` int(11) NOT NULL,
  			`end_time` int(11) NOT NULL,
  			`store_id` int(11) NOT NULL,
  			`spec_price` text NOT NULL,
  			PRIMARY KEY  (`pro_id`)
		) ENGINE=MyISAM DEFAULT CHARSET='.str_replace('-','',CHARSET).';';
		$mod->db->query($sql);	
			/* store  table */
		$result = $mod->db->getAll('SHOW COLUMNS FROM '. DB_PREFIX . 'store');
		$fields = array();
		foreach($result as $v) {
			$fields[] = $v['Field'];
		}
		if(!in_array('amount_for_free_fee', $fields)){
			$sql = 'ALTER TABLE `'.DB_PREFIX.'store` ADD `amount_for_free_fee` decimal(10,2) NOT NULL ';
			$mod->db->query($sql);
		}
		if(!in_array('acount_for_free_fee', $fields)){
			$sql = 'ALTER TABLE `'.DB_PREFIX.'store` ADD `acount_for_free_fee` int(10) NOT NULL ';
			$mod->db->query($sql);
		}
		$sql='CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'ugrade` (
			 `grade_id` INT( 255 ) NOT NULL AUTO_INCREMENT ,
			 `grade_name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			 `grade` TINYINT( 3 ) NOT NULL ,
			 `grade_icon` VARCHAR( 255 ) NULL ,
			 `growth_needed` INT( 20 ) NOT NULL ,
			 `top_growth` INT( 20 )   NULL ,
			 `floor_growth` INT( 20 ) NOT NULL ,
			 `add_time` INT( 20 ) NULL,
			 PRIMARY KEY (`grade_id`)
			 ) ENGINE=MyISAM DEFAULT CHARSET='.str_replace('-','',CHARSET).';';
		$mod->db->query($sql);
		$ugrade_mod=&m('ugrade');
		$ugrade=$ugrade_mod->get(1);
		if(empty($ugrade))
		{
			$sql="INSERT INTO `".DB_PREFIX."ugrade` (`grade_id`, `grade_name`,`grade`,`growth_needed`,`floor_growth`,`add_time`) VALUES('1',  '普通会员',  '1', '0','0',".gmtime().")";
		}
		$mod->db->query($sql);
		$sql='CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'grade_goods` (
			`goods_id` INT( 255 ) NOT NULL ,
			`grade_id` INT( 20 ) NOT NULL ,
			`grade` INT( 20 ) NOT NULL ,
			`grade_discount` DECIMAL(4,2) NOT NULL DEFAULT  1
			) ENGINE=MyISAM DEFAULT CHARSET='.str_replace('-','',CHARSET).';';
		$mod->db->query($sql);
		
		
		$result = $mod->db->getAll('SHOW COLUMNS FROM '. DB_PREFIX . 'member');
		$fields = array();
		foreach($result as $v) {
			$fields[] = $v['Field'];
			if($v['Field'] == 'ugrade')
			{
				$default=$v['Default'];
			}
		}
		if($default == 0){
			$sql="ALTER TABLE  `".DB_PREFIX."member` ALTER  `ugrade` SET DEFAULT '1' ";
			$mod->db->query($sql);
		}
		$user_mod=&m('member');
		$users=$user_mod->find(array('conditions'=>'ugrade = 0','fields'=>'ugrade'));
		if($users)
		{
			foreach($users as $user){
				$user_mod->edit($user['user_id'],array('ugrade'=>1));
			}
		}
		
		
		if(!in_array('growth', $fields)){
			$sql='ALTER TABLE  `'.DB_PREFIX.'member` ADD  `growth` INT( 20 ) NOT NULL DEFAULT  0 AFTER  `feed_config`';
			$mod->db->query($sql);
		}	
		
		
		$result = $mod->db->getAll('SHOW COLUMNS FROM '. DB_PREFIX . 'goods');
		$fields = array();
		foreach($result as $v) {
			$fields[] = $v['Field'];
		}
		if(!in_array('if_open', $fields)){
			$sql='ALTER TABLE  `'.DB_PREFIX.'goods` ADD  `if_open` TINYINT( 3 ) NOT NULL DEFAULT  0 AFTER  `closed`';
			$mod->db->query($sql);
		}
		if(!in_array('delivery_template_id', $fields)){
			$sql='ALTER TABLE  `'.DB_PREFIX.'goods` ADD  `delivery_template_id` INT NOT NULL AFTER `tags`';
			$mod->db->query($sql);
		}
		
		if(!in_array('tips_id', $fields)){
			$sql='ALTER TABLE  `'.DB_PREFIX.'goods` ADD  `tips_id` INT NOT NULL AFTER `tags`';
			$mod->db->query($sql);
		}
		if(!in_array('goods_dests', $fields)){
			$sql='ALTER TABLE  `'.DB_PREFIX.'goods` ADD  `goods_dests` text NOT NULL';
			$mod->db->query($sql);
		}
		$sql = 'CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'delivery_template` (
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
		) ENGINE=MyISAM DEFAULT CHARSET='.str_replace('-','',CHARSET).';';
		
		$mod->db->query($sql);
		
		$sql='CREATE TABLE IF NOT EXISTS `'.DB_PREFIX.'store_tips` (
			 `tips_id` INT( 255 ) NOT NULL AUTO_INCREMENT ,
			 `tips_content` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
			 `add_time` INT( 20 ) NULL,
			 PRIMARY KEY (`tips_id`)
			 ) ENGINE=MyISAM DEFAULT CHARSET='.str_replace('-','',CHARSET).';';
		$mod->db->query($sql);
		
		$result = $mod->db->getAll('SHOW COLUMNS FROM '. DB_PREFIX . 'user_coupon');
		$fields = array();
		foreach($result as $v) {
			$fields[] = $v['Field'];
		}
		if(!in_array('bind_time', $fields)){
			$sql='ALTER TABLE  `'.DB_PREFIX.'user_coupon` ADD  `bind_time` INT(20) NULL ';
			$mod->db->query($sql);
		}
		
		
		$result = $mod->db->getAll('SHOW COLUMNS FROM '. DB_PREFIX . 'coupon');
		$fields = array();
		foreach($result as $v) {
			$fields[] = $v['Field'];
		}
		if(!in_array('open_agree_item', $fields)){
			$sql="ALTER TABLE  `".DB_PREFIX."coupon` ADD  `open_agree_item` TINYINT(3) DEFAULT '0' ";
			$mod->db->query($sql);
		}
		
	}
	function judge_cate_gnum()
	{
		$cate_id = $_GET['cate_id'] ? $_GET['cate_id'] : 0;
		if(!$cate_id)
		{
			echo ecm_json_encode(false);
		}
		$gcategory_mod = &m('gcategory');
		$count = $gcategory_mod->get_cat_goods_total($cate_id);
		if($count > 0)
		{
			$this->json_result(true);
		}else{
			echo 0;
			return;
		}
	}
	
	function  auto_refuse()
	{
		$store_coupon_mod = &m('store_coupon');
		$coupon_mod = &m('coupon');
		$coupon = $store_coupon_mod->getAll("SELECT *,sc.store_id FROM {$store_coupon_mod->table} sc LEFT JOIN {$coupon_mod->table} c ON sc.coupon_id=c.coupon_id WHERE c.if_issue = 1 AND   sc.if_agree IS NULL  AND c.end_time > ".gmtime());
		if(!empty($coupon))
		{
			foreach($coupon as $key=>$val)
			{
				$d=date('Y-m-d',$val["start_time"]);
				$m = strtotime("$d +5 days");
				if($m <= gmtime())
				{
					$store_coupon_mod->edit($val['item_id'],array('if_agree'=> 1));
				}
			}
		}
	}
	
	// 判断后台是否启用快递跟踪插件 psmb
	function _check_express_plugin()
	{
		$plugin_inc_file = ROOT_PATH . '/data/plugins.inc.php';
        if (is_file($plugin_inc_file))
        {
            $plugins =  include($plugin_inc_file);
			return isset($plugins['on_query_express']['kuaidi100']);
        }

        return false;
	}
    function login()
    {
        if ($this->visitor->has_login)
        {
            $this->show_warning('has_login');

            return;
        }
        if (!IS_POST)
        {
            if (!empty($_GET['ret_url']))
            {
                $ret_url = trim($_GET['ret_url']);
            }
            else
            {
                if (isset($_SERVER['HTTP_REFERER']))
                {
                    $ret_url = $_SERVER['HTTP_REFERER'];
                }
                else
                {
                    $ret_url = SITE_URL . '/index.php';
                }
            }
            /* 防止登陆成功后跳转到登陆、退出的页面 */
            $ret_url = strtolower($ret_url);            
            if (str_replace(array('act=login', 'act=logout',), '', $ret_url) != $ret_url)
            {
                $ret_url = SITE_URL . '/index.php';
            }

            if (Conf::get('captcha_status.login'))
            {
                $this->assign('captcha', 1);
            }
            $this->import_resource(array('script' => 'jquery.plugins/jquery.validate.js'));
            $this->assign('ret_url', rawurlencode($ret_url));
            $this->_curlocal(LANG::get('user_login'));
            $this->_config_seo('title', Lang::get('user_login') . ' - ' . Conf::get('site_title'));
			
			
			
            $this->display('login.html');
            /* 同步退出外部系统 */
            if (!empty($_GET['synlogout']))
            {
                $ms =& ms();
                echo $synlogout = $ms->user->synlogout();
            }
        }
        else
        {
            if (Conf::get('captcha_status.login') && base64_decode($_SESSION['captcha']) != strtolower($_POST['captcha']))
            {
                $this->show_warning('captcha_failed');

                return;
            }

            $login_name = trim($_POST['login_name']);
			if(!$login_name){
				$this->show_warning('login_user_needed');
                return;
			}
			$password  = $_POST['password'];
			$model_waiter = &m('waiter');
			$waiter_id = $model_waiter->check_waiter($login_name,$password);
			if ($waiter_id)
            {
                 /* 通过验证，执行登陆操作 */
                $this->_do_login_waiter($waiter_id);
				$this->show_message(Lang::get('login_successed') . $synlogin,
					'back_before_login', 'index.php?app=member',
					'enter_member_center', 'index.php?app=member'
            	);
            }
			else
			{
				is_email($login_name) && $conditions=" email = '". trim($login_name)."'";
				is_mobile($login_name) && $conditions=" phone_mob = '". trim($login_name)."'";
				if($conditions)
				{
					$user_mod=&m('member');
					$user=$user_mod->get(array('conditions'=>$conditions,'fields'=>'user_name'));
					$user_name=$user['user_name'];
				}
				else
				{
					$user_name=$login_name;
				}
	
				$ms =& ms();
				$user_id = $ms->user->auth($user_name, $password);
				if (!$user_id)
				{
					/* 未通过验证，提示错误信息 */
					$this->show_warning($ms->user->get_error());
	
					return;
				}
				else
				{
					/* 通过验证，执行登陆操作 */
					$this->_do_login($user_id);
	
					/* 同步登陆外部系统 */
					$synlogin = $ms->user->synlogin($user_id);
				}
	
				$this->show_message(Lang::get('login_successed') . $synlogin,
					'back_before_login', rawurldecode($_POST['ret_url']),
					'enter_member_center', 'index.php?app=member'
				);
			}
        }
    }
	function _get_enabled_plugins($event, $plugin_id) 
	{
		$plugin = array();
		$plugin_inc_file = ROOT_PATH .'/data/plugins.inc.php';
		if (is_file($plugin_inc_file) && file_exists($plugin_inc_file)) {
			$plugin = include($plugin_inc_file);
			return $plugin[$event][$plugin_id];
		}
	}

    function pop_warning ($msg, $dialog_id = '',$url = '')
    {
        if($msg == 'ok')
        {
            if(empty($dialog_id))
            {
                $dialog_id = APP . '_' . ACT;
            }
            if (!empty($url))
            {
                echo "<script type='text/javascript'>window.parent.location.href='".$url."';</script>";
            }
            echo "<script type='text/javascript'>window.parent.js_success('" . $dialog_id ."');</script>";
        }
        else
        {
            header("Content-Type:text/html;charset=".CHARSET);
            $msg = is_array($msg) ? $msg : array(array('msg' => $msg));
            $errors = '';
            foreach ($msg as $k => $v)
            {
                $error = $v[obj] ? Lang::get($v[msg]) . " [" . Lang::get($v[obj]) . "]" : Lang::get($v[msg]);
                $errors .= $errors ? "<br />" . $error : $error;
            }
            echo "<script type='text/javascript'>window.parent.js_fail('" . $errors . "');</script>";
        }
    }

    function logout()
    {
        $this->visitor->logout();

        /* 跳转到登录页，执行同步退出操作 */
        header("Location: index.php?app=member&act=login&synlogout=1");
        return;
    }
	
	/* 店小二登录 by psmb-andcpp */
    function _do_login_waiter($waiter_id)
    {
		$mod_waiter = &m('waiter');
		$mod_store = &m('store');
		$mod_priv = &m('waiterpriv');
		
		$waiter_info = $mod_waiter->get(array(
			'conditions' => "waiter_id = '{$waiter_id}'",
			'fields' => 'waiter_id, waiter_name, reg_time, last_login, last_ip, store_id',
		));
		$store_info = $mod_store->get(array(
			'conditions' => 'store_id='.$waiter_info['store_id'],
			'fields' => 'state', 
		));
		$privs = $mod_priv->get(array(
			'conditions' => "waiter_id = {$waiter_id}",
			'fields' => 'privs',
		));
		$waiter_info['user_id'] = 0;
		$waiter_info['user_name'] = $waiter_info['waiter_name'];
		$waiter_info['manage_store'] = $waiter_info['store_id'];
		$waiter_info['s'][$waiter_id.'_'.$waiter_info['store_id']] = array('waiter_id' => $waiter_id,'store_id' => $waiter_info['store_id'],'privs' => $privs['privs']);
		$waiter_info['state'] = $store_info['state'];
        /* 分派身份 */
        $this->visitor->assign($waiter_info);

        /* 更新用户登录信息 */
        $mod_waiter->edit("waiter_id = '{$waiter_id}'", "last_login = '" . gmtime()  . "', last_ip = '" . real_ip() . "', logins = logins + 1");
    }
	//end

    /* 执行登录动作 */
    function _do_login($user_id)
    {
        $mod_user =& m('member');

        $user_info = $mod_user->get(array(
            'conditions'    => "user_id = '{$user_id}'",
            'join'          => 'has_store',                 //关联查找看看是否有店铺
            'fields'        => 'user_id, user_name, reg_time, last_login, last_ip, store_id',
        ));

        /* 店铺ID */
        $my_store = empty($user_info['store_id']) ? 0 : $user_info['store_id'];

        /* 保证基础数据整洁 */
        //unset($user_info['store_id']);

        /* 分派身份 */
        $this->visitor->assign($user_info);

        /* 更新用户登录信息 */
        $mod_user->edit("user_id = '{$user_id}'", "last_login = '" . gmtime()  . "', last_ip = '" . real_ip() . "', logins = logins + 1");

        /* 更新购物车中的数据 */
        $mod_cart =& m('cart');
        $mod_cart->edit("(user_id = '{$user_id}' OR session_id = '" . SESS_ID . "') AND store_id <> '{$my_store}'", array(
            'user_id'    => $user_id,
            'session_id' => SESS_ID,
        ));

        /* 去掉重复的项 */
        $cart_items = $mod_cart->find(array(
            'conditions'    => "user_id='{$user_id}' GROUP BY spec_id",
            'fields'        => 'COUNT(spec_id) as spec_count, spec_id, rec_id',
        ));
        if (!empty($cart_items))
        {
            foreach ($cart_items as $rec_id => $cart_item)
            {
                if ($cart_item['spec_count'] > 1)
                {
                    $mod_cart->drop("user_id='{$user_id}' AND spec_id='{$cart_item['spec_id']}' AND rec_id <> {$cart_item['rec_id']}");
                }
            }
        }
    }

    /* 取得导航 */
    function _get_navs()
    {
        $cache_server =& cache_server();
        $key = 'common.navigation';
        $data = $cache_server->get($key);
        if($data === false)
        {
            $data = array(
                'header' => array(),
                'middle' => array(),
                'footer' => array(),
            );
            $nav_mod =& m('navigation');
            $rows = $nav_mod->find(array(
                'order' => 'type, sort_order',
            ));
            foreach ($rows as $row)
            {
                $data[$row['type']][] = $row;
            }
            $cache_server->set($key, $data, 86400);
        }

        return $data;
    }

    /**
     *    获取JS语言项
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function jslang()
    {
        $lang = Lang::fetch(lang_file('jslang'));
        parent::jslang($lang);
    }

    /**
     *    视图回调函数[显示小挂件]
     *
     *    @author    Garbin
     *    @param     array $options
     *    @return    void
     */
    function display_widgets($options)
    {
        $area = isset($options['area']) ? $options['area'] : '';
        $page = isset($options['page']) ? $options['page'] : '';
        if (!$area || !$page)
        {
            return;
        }
        include_once(ROOT_PATH . '/includes/widget.base.php');

        /* 获取该页面的挂件配置信息 */
        $widgets = get_widget_config($this->_get_template_name(), $page);

        /* 如果没有该区域 */
        if (!isset($widgets['config'][$area]))
        {
            return;
        }

        /* 将该区域内的挂件依次显示出来 */
        foreach ($widgets['config'][$area] as $widget_id)
        {
            $widget_info = $widgets['widgets'][$widget_id];
            $wn     =   $widget_info['name'];
            $options=   $widget_info['options'];

            $widget =& widget($widget_id, $wn, $options);
            $widget->display();
        }
    }

    /**
     *    获取当前使用的模板名称
     *
     *    @author    Garbin
     *    @return    string
     */
    function _get_template_name()
    {
        return 'default';
    }

    /**
     *    获取当前使用的风格名称
     *
     *    @author    Garbin
     *    @return    string
     */
    function _get_style_name()
    {
        return 'default';
    }

    /**
     *    当前位置
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function _curlocal($arr)
    {
        $curlocal = array(array(
            'text'  => Lang::get('index'),
            'url'   => SITE_URL . '/index.php',
        ));
        if (is_array($arr))
        {
            $curlocal = array_merge($curlocal, $arr);
        }
        else
        {
            $args = func_get_args();
            if (!empty($args))
            {
                $len = count($args);
                for ($i = 0; $i < $len; $i += 2)
                {
                    $curlocal[] = array(
                        'text'  =>  $args[$i],
                        'url'   =>  $args[$i+1],
                    );
                }
            }
        }

        $this->assign('_curlocal', $curlocal);
    }
    function _init_visitor()
    {
        $this->visitor =& env('visitor', new UserVisitor());
    }
}
/**
 *    前台访问者
 *
 *    @author    Garbin
 *    @usage    none
 */
class UserVisitor extends BaseVisitor
{
    var $_info_key = 'user_info';

    /**
     *    退出登录
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function logout()
    {
        /* 将购物车中的相关项的session_id置为空 */
        $mod_cart =& m('cart');
        $mod_cart->edit("user_id = '" . $this->get('user_id') . "'", array(
            'session_id' => '',
        ));

        /* 退出登录 */
        parent::logout();
    }
}
/**
 *    商城控制器基类
 *
 *    @author    Garbin
 *    @usage    none
 */
class MallbaseApp extends FrontendApp
{
    function _run_action()
    {
		//小二登录，前台保持退出状态 by andcpp 
		if($this->visitor->get("waiter_id"))
		{
			$this->visitor->waiter_logout();
		}
		//end 
		
        /* 只有登录的用户才可访问 */
        if (!$this->visitor->has_login && in_array(APP, array('apply')))
        {
            header('Location: index.php?app=member&act=login&ret_url=' . rawurlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']));

            return;
        }

        parent::_run_action();
    }

    function _config_view()
    {
        parent::_config_view();

        $template_name = $this->_get_template_name();
        $style_name    = $this->_get_style_name();

        $this->_view->template_dir = ROOT_PATH . "/themes/mall/{$template_name}";
        $this->_view->compile_dir  = ROOT_PATH . "/temp/compiled/mall/{$template_name}";
        $this->_view->res_base     = SITE_URL . "/themes/mall/{$template_name}/styles/{$style_name}";
    }

    /* 取得支付方式实例 */
    function _get_payment($code, $payment_info)
    {
        include_once(ROOT_PATH . '/includes/payment.base.php');
        include(ROOT_PATH . '/includes/payments/' . $code . '/' . $code . '.payment.php');
        $class_name = ucfirst($code) . 'Payment';

        return new $class_name($payment_info);
    }

    /**
     *   获取当前所使用的模板名称
     *
     *    @author    Garbin
     *    @return    string
     */
    function _get_template_name()
    {
        $template_name = Conf::get('template_name');
        if (!$template_name)
        {
            $template_name = 'default';
        }

        return $template_name;
    }

    /**
     *    获取当前模板中所使用的风格名称
     *
     *    @author    Garbin
     *    @return    string
     */
    function _get_style_name()
    {
        $style_name = Conf::get('style_name');
        if (!$style_name)
        {
            $style_name = 'default';
        }

        return $style_name;
    }
}

/**
 *    购物流程子系统基础类
 *
 *    @author    Garbin
 *    @usage    none
 */
class ShoppingbaseApp extends MallbaseApp
{
    function _run_action()
    {
		//小二登录，前台保持退出状态 by andcpp 
		if($this->visitor->get("waiter_id"))
		{
			$this->visitor->waiter_logout();
		}
		//end 

        /* 只有登录的用户才可访问 */
        if (!$this->visitor->has_login && !in_array(ACT, array('login', 'register', 'check_user')))
        {
            if (!IS_AJAX)
            {
                header('Location:index.php?app=member&act=login&ret_url=' . rawurlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']));

                return;
            }
            else
            {
                $this->json_error('login_please');
                return;
            }
        }

        parent::_run_action();
    }
}

/**
 *    用户中心子系统基础类
 *
 *    @author    Garbin
 *    @usage    none
 */
class MemberbaseApp extends MallbaseApp
{
    function _run_action()
    {
        /* 只有登录的用户才可访问 */
        if (!$this->visitor->has_login && !in_array(ACT, array('login', 'register', 'check_user','check_phone_mob','check_email_info')))
        {
            if (!IS_AJAX)
            {
                header('Location:index.php?app=member&act=login&ret_url=' . rawurlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']));

                return;
            }
            else
            {
                $this->json_error('login_please');
                return;
            }
        }
		
		/* 如果是店小二登录，则不允许访问权限列表中的黑名单（如：我是买家菜单） */
		if($this->visitor->get('waiter_id') && $this->_in_waiter_black_privileges())
		{
			$this->show_warning('no_permission');

            return;
		}

        parent::_run_action();
    }
	
	// 查看当前操作是否在店小二的白名单中
	function _in_waiter_white_privileges()
	{
		if($this->_in_waiter_black_privileges()){
			return false;
		}
		
		$priv_data = include(ROOT_PATH . '/includes/waiter_priv.inc.php');
		$priv_data = $priv_data['white'];
        if(!is_array($priv_data))
		{
			return false;
		}
		foreach($priv_data as $key=>$val)
		{
			foreach($val as $k=>$priv)
			{
				$priv = explode('|',$priv['value']);
				if (APP==$priv[0] && (ACT==$priv[1] || ACT=='index' || $priv[1]=='all'))
				{
					return true;
				}
			}
		}
		return false;
	}
	// 查看当前操作是否在店小二的黑名单中
	function _in_waiter_black_privileges()
	{
		$priv_data = include(ROOT_PATH . '/includes/waiter_priv.inc.php');
		$priv_data = $priv_data['black'];
		return in_array(APP.'|all',$priv_data) || in_array(APP.'|'.ACT, $priv_data);
	}
	
    /**
     *    当前选中的菜单项
     *
     *    @author    Garbin
     *    @param     string $item
     *    @return    void
     */
    function _curitem($item)
    {
        $this->assign('has_store', $this->visitor->get('has_store'));
        $this->assign('_member_menu', $this->_get_member_menu());
        $this->assign('_curitem', $item);
    }
    /**
     *    当前选中的子菜单
     *
     *    @author    Garbin
     *    @param     string $item
     *    @return    void
     */
    function _curmenu($item)
    {
        $_member_submenu = $this->_get_member_submenu();
        foreach ($_member_submenu as $key => $value)
        {
            $_member_submenu[$key]['text'] = $value['text'] ? $value['text'] : Lang::get($value['name']);
        }
        $this->assign('_member_submenu', $_member_submenu);
        $this->assign('_curmenu', $item);
    }
    /**
     *    获取子菜单列表
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function _get_member_submenu()
    {
        return array();
    }
    /**
     *    获取用户中心全局菜单列表
     *
     *    @author    Garbin
     *    @param    none
     *    @return    void
     */
    function _get_member_menu()
    {
        $menu = array();

        /* 我的ECMall */
        $menu['my_ecmall'] = array(
            'name'  => 'my_ecmall',
            'text'  => Lang::get('my_ecmall'),
            'submenu'   => array(
                'overview'  => array(
                    'text'  => Lang::get('overview'),
                    'url'   => 'index.php?app=member',
                    'name'  => 'overview',
                    'icon'  => 'ico1',
                ),
                'my_profile'  => array(
                    'text'  => Lang::get('my_profile'),
                    'url'   => 'index.php?app=member&act=profile',
                    'name'  => 'my_profile',
                    'icon'  => 'ico2',
                ),
                'message'  => array(
                    'text'  => Lang::get('message'),
                    'url'   => 'index.php?app=message&act=newpm',
                    'name'  => 'message',
                    'icon'  => 'ico3',
                ),
                'friend'  => array(
                    'text'  => Lang::get('friend'),
                    'url'   => 'index.php?app=friend',
                    'name'  => 'friend',
                    'icon'  => 'ico4',
                ),
                /*
                'my_credit'  => array(
                    'text'  => Lang::get('my_credit'),
                    'url'   => 'index.php?app=member&act=credit',
                    'name'  => 'my_credit',
                ),*/
            ),
        );


        /* 我是买家 */
        $menu['im_buyer'] = array(
            'name'  => 'im_buyer',
            'text'  => Lang::get('im_buyer'),
            'submenu'   => array(
                'my_order'  => array(
                    'text'  => Lang::get('my_order'),
                    'url'   => 'index.php?app=buyer_order',
                    'name'  => 'my_order',
                    'icon'  => 'ico5',
                ),
                /*'my_groupbuy'  => array(
                    'text'  => Lang::get('my_groupbuy'),
                    'url'   => 'index.php?app=buyer_groupbuy',
                    'name'  => 'my_groupbuy',
                    'icon'  => 'ico21',
                ),*/
                'my_question' =>array(
                    'text'  => Lang::get('my_question'),
                    'url'   => 'index.php?app=my_question',
                    'name'  => 'my_question',
                    'icon'  => 'ico17',

                ),
                'my_favorite'  => array(
                    'text'  => Lang::get('my_favorite'),
                    'url'   => 'index.php?app=my_favorite',
                    'name'  => 'my_favorite',
                    'icon'  => 'ico6',
                ),
                'my_address'  => array(
                    'text'  => Lang::get('my_address'),
                    'url'   => 'index.php?app=my_address',
                    'name'  => 'my_address',
                    'icon'  => 'ico7',
                ),
                'my_coupon'  => array(
                    'text'  => Lang::get('my_coupon'),
                    'url'   => 'index.php?app=my_coupon',
                    'name'  => 'my_coupon',
                    'icon'  => 'ico20',
                ),
            ),
        );

		if($this->visitor->get('waiter_id'))
		{
			$menu['im_buyer'] = array();
			$menu['my_ecmall'] = array();
		}

        if (!$this->visitor->get('has_store') && Conf::get('store_allow') && !$this->visitor->get('waiter_id')) //店小二隐藏开店按钮 by andcpp 
        {
            /* 没有拥有店铺，且开放申请，则显示申请开店链接 */
            /*$menu['im_seller'] = array(
                'name'  => 'im_seller',
                'text'  => Lang::get('im_seller'),
                'submenu'   => array(),
            );

            $menu['im_seller']['submenu']['overview'] = array(
                'text'  => Lang::get('apply_store'),
                'url'   => 'index.php?app=apply',
                'name'  => 'apply_store',
            );*/
            $menu['overview'] = array(
                'text' => Lang::get('apply_store'),
                'url'  => 'index.php?app=apply',
            );
        }
        if ($this->visitor->get('manage_store'))
        {
			$filename = ROOT_PATH . '/data/msg.inc.php';
			if (file_exists($filename))
			{
				$menu['my_ecmall']['submenu']['msg'] = array(
						'text'  => Lang::get('msg'),
						'url'   => 'index.php?app=msg',
						'name'  => 'msg',
						'icon'  => 'ico3',
					);
			}
            /* 指定了要管理的店铺 */
            $menu['im_seller'] = array(
                'name'  => 'im_seller',
                'text'  => Lang::get('im_seller'),
                'submenu'   => array(),
            );

            $menu['im_seller']['submenu']['my_goods'] = array(
                    'text'  => Lang::get('my_goods'),
                    'url'   => 'index.php?app=my_goods',
                    'name'  => 'my_goods',
                    'icon'  => 'ico8',
            );
			/*add by psmb */
			$menu['im_seller']['submenu']['promotion_manage'] = array(
			        'text'  => Lang::get('promotion_manage'),
					'url'   => 'index.php?app=seller_promotion',
					'name'  => 'promotion_manage',
					'icon'  => 'ico9',
			);
			/*end psmb */
            /*$menu['im_seller']['submenu']['groupbuy_manage'] = array(
                    'text'  => Lang::get('groupbuy_manage'),
                    'url'   => 'index.php?app=seller_groupbuy',
                    'name'  => 'groupbuy_manage',
                    'icon'  => 'ico22',
            );*/
            $menu['im_seller']['submenu']['my_qa'] = array(
                    'text'  => Lang::get('my_qa'),
                    'url'   => 'index.php?app=my_qa',
                    'name'  => 'my_qa',
                    'icon'  => 'ico18',
            );
            $menu['im_seller']['submenu']['my_category'] = array(
                    'text'  => Lang::get('my_category'),
                    'url'   => 'index.php?app=my_category',
                    'name'  => 'my_category',
                    'icon'  => 'ico9',
            );
            $menu['im_seller']['submenu']['order_manage'] = array(
                    'text'  => Lang::get('order_manage'),
                    'url'   => 'index.php?app=seller_order',
                    'name'  => 'order_manage',
                    'icon'  => 'ico10',
            );
            $menu['im_seller']['submenu']['my_store']  = array(
                    'text'  => Lang::get('my_store'),
                    'url'   => 'index.php?app=my_store',
                    'name'  => 'my_store',
                    'icon'  => 'ico11',
            );
            $menu['im_seller']['submenu']['my_theme']  = array(
                    'text'  => Lang::get('my_theme'),
                    'url'   => 'index.php?app=my_theme',
                    'name'  => 'my_theme',
                    'icon'  => 'ico12',
            );
			if($this->_is_staff()){
            $menu['im_seller']['submenu']['my_payment'] =  array(
                    'text'  => Lang::get('my_payment'),
                    'url'   => 'index.php?app=my_payment',
                    'name'  => 'my_payment',
                    'icon'  => 'ico13',
            );
			}
			/* 屏蔽掉原来的配送方式，改为 运费模板 psmb
            $menu['im_seller']['submenu']['my_shipping'] = array(
                    'text'  => Lang::get('my_shipping'),
                    'url'   => 'index.php?app=my_shipping',
                    'name'  => 'my_shipping',
                    'icon'  => 'ico14',
            );
			*/
			$menu['im_seller']['submenu']['my_delivery'] = array(
                    'text'  => Lang::get('my_delivery'),
                    'url'   => 'index.php?app=my_delivery',
                    'name'  => 'my_delivery',
                    'icon'  => 'ico14',
            );
			//360cd.cn
			$menu['im_seller']['submenu']['trans']  = array(
					'text'  => Lang::get('trans'),
					'url'   => 'index.php?app=trans',
					'name'  => 'trans',
					'icon'  => 'ico14',
			);
            
            $menu['im_seller']['submenu']['my_navigation'] = array(
                    'text'  => Lang::get('my_navigation'),
                    'url'   => 'index.php?app=my_navigation',
                    'name'  => 'my_navigation',
                    'icon'  => 'ico15',
            );
			$menu['im_seller']['submenu']['my_partner']  = array(
                    'text'  => Lang::get('my_partner'),
                    'url'   => 'index.php?app=my_partner',
                    'name'  => 'my_partner',
                    'icon'  => 'ico16',
            );
            $menu['im_seller']['submenu']['coupon']  = array(
                    'text'  => Lang::get('coupon_manage'),
                    'url'   => 'index.php?app=coupon',
                    'name'  => 'coupon',
                    'icon'  => 'ico19',
            );
			if($this->_is_staff()){
			if(!$this->visitor->get('waiter_id'))
			{
				$menu['im_seller']['submenu']['my_waiter']  = array(
                    'text'  => Lang::get('my_waiter'),
                    'url'   => 'index.php?app=my_waiter',
                    'name'  => 'my_waiter',
                    'icon'  => 'ico4',
            	);
			}
			}
        }
        return $menu;
    }
	
	/* Check is Staff by MingFONG at 2014-11-10 */
    function _is_staff()
    {
        $user = $this->visitor->get();
		//echo $user['ugrade'];
		//1.普通會員 2.VIP 3.Staff
		if($user['ugrade'] == 3){
			return $user['ugrade'];
		}
	}
	/* End by MingFONG at 2014-11-10 */
}

/**
 *    店铺管理子系统基础类
 *
 *    @author    Garbin
 *    @usage    none
 */
class StoreadminbaseApp extends MemberbaseApp
{
    function _run_action()
    {
        /* 只有登录的用户才可访问 */
        if (!$this->visitor->has_login && !in_array(ACT, array('login', 'register', 'check_user')))
        {
            if (!IS_AJAX)
            {
                header('Location:index.php?app=member&act=login&ret_url=' . rawurlencode($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']));

                return;
            }
            else
            {
                $this->json_error('login_please');
                return;
            }
        }
        $referer = $_SERVER['HTTP_REFERER'];
        if (strpos($referer, 'act=login') === false)
        {
            $ret_url = $_SERVER['HTTP_REFERER'];
            $ret_text = 'go_back';
        }
        else
        {
            $ret_url = SITE_URL . '/index.php';
            $ret_text = 'back_index';
        }

        /* 检查是否是店铺管理员 */
        if (!$this->visitor->get('manage_store'))
        {
            /* 您不是店铺管理员 */
            $this->show_warning(
                'not_storeadmin',
                'apply_now', 'index.php?app=apply',
                $ret_text, $ret_url
            );

            return;
        }

        /* 检查是否被授权 */
        $privileges = $this->_get_privileges();
		
        if (!$this->_check_visitor_access($privileges))
        {
            $this->show_warning('no_permission', $ret_text, $ret_url);

            return;
        }

        /* 检查店铺开启状态 */
        $state = $this->visitor->get('state');
        if ($state == 0)
        {
            $this->show_warning('apply_not_agree', $ret_text, $ret_url);

            return;
        }
        elseif ($state == 2)
        {
            $this->show_warning('store_is_closed', $ret_text, $ret_url);

            return;
        }

        /* 检查附加功能 */
        if (!$this->_check_add_functions())
        {
            $this->show_warning('not_support_function', $ret_text, $ret_url);
            return;
        }

        parent::_run_action();
    }
	function _check_visitor_access($privileges)
	{
		if($this->visitor->get('waiter_id')){
			return !($this->_in_waiter_white_privileges() && !$this->visitor->i_can('do_action', $privileges));
		}
		return $this->visitor->i_can('do_action', $privileges);
	}
	
    function _get_privileges()
    {
        $store_id = $this->visitor->get('manage_store');
        $privs = $this->visitor->get('s');

        if (empty($privs))
        {
            return '';
        }

        foreach ($privs as $key => $admin_store)
        {
            if ($admin_store['store_id'] == $store_id)
            {
                return $admin_store['privs'];
            }
        }
    }
    
    /* 获取当前店铺所使用的主题 */
    function _get_theme()
    {
        $model_store =& m('store');
        $store_info  = $model_store->get($this->visitor->get('manage_store'));
        $theme = !empty($store_info['theme']) ? $store_info['theme'] : 'default|default';
        list($curr_template_name, $curr_style_name) = explode('|', $theme);
        return array(
            'template_name' => $curr_template_name,
            'style_name'    => $curr_style_name,
        );
    }

    function _check_add_functions()
    {
        $apps_functions = array( // app与function对应关系
            'seller_groupbuy' => 'groupbuy',
            'coupon' => 'coupon',
			'my_waiter' => 'my_waiter',
        );
        if (isset($apps_functions[APP]))
        {
            $store_mod =& m('store');
            $settings = $store_mod->get_settings($this->_store_id);
            $add_functions = isset($settings['functions']) ? $settings['functions'] : ''; // 附加功能
            if (!in_array($apps_functions[APP], explode(',', $add_functions)))
            {
                return false;
            }
        }
        return true;
    }
}

/**
 *    店铺控制器基础类
 *
 *    @author    Garbin
 *    @usage    none
 */
class StorebaseApp extends FrontendApp
{
    var $_store_id;

    /**
     * 设置店铺id
     *
     * @param int $store_id
     */
    function set_store($store_id)
    {
        $this->_store_id = intval($store_id);

        /* 有了store id后对视图进行二次配置 */
        $this->_init_view();
        $this->_config_view();
    }

    function _config_view()
    {
        parent::_config_view();
        $template_name = $this->_get_template_name();
        $style_name    = $this->_get_style_name();

        $this->_view->template_dir = ROOT_PATH . "/themes/store/{$template_name}";
        $this->_view->compile_dir  = ROOT_PATH . "/temp/compiled/store/{$template_name}";
        $this->_view->res_base     = SITE_URL . "/themes/store/{$template_name}/styles/{$style_name}";
    }

    /**
     * 取得店铺信息
     */
    function get_store_data()
    {
        $cache_server =& cache_server();
        $key = 'function_get_store_data_' . $this->_store_id;
        $store = $cache_server->get($key);
        if ($store === false)
        {
            $store = $this->_get_store_info();
            if (empty($store))
            {
                $this->show_warning('the_store_not_exist');
                exit;
            }
            if ($store['state'] == 2)
            {
                $this->show_warning('the_store_is_closed');
                exit;
            }
            $step = intval(Conf::get('upgrade_required'));
            $step < 1 && $step = 5;
            $store_mod =& m('store');
            $store['credit_image'] = $this->_view->res_base . '/images/' . $store_mod->compute_credit($store['credit_value'], $step);

            empty($store['store_logo']) && $store['store_logo'] = Conf::get('default_store_logo');
            $store['store_owner'] = $this->_get_store_owner();
			$store['online_service'] = $this->_get_store_waiter();
            $store['store_navs']  = $this->_get_store_nav();
            $goods_mod =& m('goods');
            $store['goods_count'] = $goods_mod->get_count_of_store($this->_store_id);
            $store['store_gcates']= $this->_get_store_gcategory();
            $store['sgrade'] = $this->_get_store_grade('grade_name');
            $functions = $this->_get_store_grade('functions');
            $store['functions'] = array();
            if ($functions)
            {
                $functions = explode(',', $functions);
                foreach ($functions as $k => $v)
                {
                    $store['functions'][$v] = $v;
                }
            }
            $cache_server->set($key, $store, 1800);
        }
        return $store;
    }
	
	/* 取得店小二信息 */
    function _get_store_waiter()
    {
        if (!$this->_store_id)
        {
            /* 未设置前返回空 */
            return array();
        }
        $model_waiter = &m('waiter');
		$waiter_info = $model_waiter->find(array(
			'conditions' => 'store_id = '.$this->_store_id,
			'fields' => 'im_qq,im_ww'
		));
		foreach($waiter_info as $waiter)
		{
			$data['qq'][] = $waiter['im_qq'];
			$data['ww'][] = $waiter['im_ww'];
		}
        return $data;
    }


    /* 取得店铺信息 */
    function _get_store_info()
    {
        if (!$this->_store_id)
        {
            /* 未设置前返回空 */
            return array();
        }
        static $store_info = null;
        if ($store_info === null)
        {
            $store_mod  =& m('store');
            $store_info = $store_mod->get_info($this->_store_id);
        }

        return $store_info;
    }

    /* 取得店主信息 */
    function _get_store_owner()
    {
        $user_mod =& m('member');
        $user = $user_mod->get($this->_store_id);

        return $user;
    }

    /* 取得店铺导航 */
    function _get_store_nav()
    {
        $article_mod =& m('article');
        return $article_mod->find(array(
            'conditions' => "store_id = '{$this->_store_id}' AND cate_id = '" . STORE_NAV . "' AND if_show = 1",
            'order' => 'sort_order',
            'fields' => 'title',
        ));
    }
    /*  取的店铺等级   */

    function _get_store_grade($field)
    {
        $store_info = $store_info = $this->_get_store_info();
        $sgrade_mod =& m('sgrade');
        $result = $sgrade_mod->get_info($store_info['sgrade']);
        return $result[$field];
    }
    /* 取得店铺分类 */
    function _get_store_gcategory()
    {
        $gcategory_mod =& bm('gcategory', array('_store_id' => $this->_store_id));
        $gcategories = $gcategory_mod->get_list(-1, true);
        import('tree.lib');
        $tree = new Tree();
        $tree->setTree($gcategories, 'cate_id', 'parent_id', 'cate_name');
        return $tree->getArrayList(0);
    }

    /**
     *    获取当前店铺所设定的模板名称
     *
     *    @author    Garbin
     *    @return    string
     */
    function _get_template_name()
    {
        $store_info = $this->_get_store_info();
        $theme = !empty($store_info['theme']) ? $store_info['theme'] : 'default|default';
        list($template_name, $style_name) = explode('|', $theme);

        return $template_name;
    }

    /**
     *    获取当前店铺所设定的风格名称
     *
     *    @author    Garbin
     *    @return    string
     */
    function _get_style_name()
    {
        $store_info = $this->_get_store_info();
        $theme = !empty($store_info['theme']) ? $store_info['theme'] : 'default|default';
        list($template_name, $style_name) = explode('|', $theme);

        return $style_name;
    }
}

/* 实现消息基础类接口 */
class MessageBase extends MallbaseApp {};

/* 实现模块基础类接口 */
class BaseModule  extends FrontendApp {};

/* 消息处理器 */
require(ROOT_PATH . '/eccore/controller/message.base.php');

?>
