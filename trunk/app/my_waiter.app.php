<?php

/**
 *    店小二控制器
 */
class My_waiterApp extends StoreadminbaseApp
{
    var $_store_id;
	var $_member_mod;
	var $_waiter_mod;
	var $_waiterpriv_mod;
	
    function __construct()
    {
        $this->My_waiterApp();
    }

    function My_waiterApp()
    {
        parent::__construct();

        $this->_store_id  	= intval($this->visitor->get('manage_store'));
		$this->_member_mod 	= &m('member');
		$this->_waiter_mod 	= &m('waiter');
		$this->_waiterpriv_mod = & m('waiterpriv');
    }

    function index()
    {
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
                    'path' => 'jquery.plugins/jquery.validate.js',
                    'attr' => '',
                ),
            ),
            'style' =>  'jquery.ui/themes/ui-lightness/jquery.ui.css',
        ));

        /* 取得列表数据 */
        $page   =   $this->_get_page(10);    //获取分页信息
        $waiters     = $this->_waiter_mod->find(array(
            'conditions'    => 'store_id = ' . $this->_store_id,
            'order'         => 'waiter_id ASC',
            'limit'         => $page['limit'],  //获取当前页的数据
            'count'         => true
        ));
        $page['item_count'] = $this->_waiter_mod->getCount();   //获取统计的数据
        $this->assign('waiters', $waiters);

        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                         LANG::get('my_waiter'), 'index.php?app=my_waiter',
                         LANG::get('waiter_list'));

        /* 当前用户中心菜单 */
        $this->_curitem('my_waiter');

        /* 当前所处子菜单 */
        $this->_curmenu('waiter_list');

        $this->_format_page($page);
        $this->assign('page_info', $page);          //将分页信息传递给视图，用于形成分页条
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('my_waiter'));
        $this->display('my_waiter.index.html');
    }

    /**
     *    添加店小二
     */
    function add()
    {
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                             LANG::get('my_waiter'), 'index.php?app=my_waiter',
                             LANG::get('add_waiter'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_waiter');	
            /* 当前所处子菜单 */
            $this->_curmenu('add_waiter');
            header('Content-Type:text/html;charset=' . CHARSET);
            $this->display('my_waiter.form.html');
        }
        else
        {
			$user_info = $this->_member_mod->get(array('conditions'=>'user_id='.$this->_store_id,'fields'=>'user_name'));
			
            $data = array(
				'store_id' => $this->_store_id,
                'waiter_name' => $user_info['user_name'] . ":" . trim($_POST['waiter_name']),
				'password' => md5($_POST['password']),
				'email' => trim($_POST['email']),
				'real_name' => trim($_POST['real_name']),
                'gender'    => trim($_POST['gender']),
                'phone_mob' => trim($_POST['phone_mob']),
                'im_qq'     => trim($_POST['im_qq']),
                'im_ww'    =>  trim($_POST['im_ww']),
				'reg_time ' => gmtime(),
            );
            if (!($waiter_id = $this->_waiter_mod->add($data)))
            {
                $this->pop_warning($this->_waiter_mod->get_error());

                return;
            }
            $this->pop_warning('ok');
        }
    }
    function edit()
    {
        $waiter_id = empty($_GET['waiter_id']) ? 0 : intval($_GET['waiter_id']);
        if (!$waiter_id)
        {
            echo Lang::get('no_such_waiter');

            return;
        }
        if (!IS_POST)
        {
            $waiter_info = $this->_waiter_mod->get("waiter_id = {$waiter_id}");
            if (empty($waiter_info))
            {
                echo Lang::get('no_such_waiter');

                return;
            }
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                             LANG::get('my_waiter'), 'index.php?app=my_waiter',
                             LANG::get('edit_waiter'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_waiter');

            /* 当前所处子菜单 */
            $this->_curmenu('edit_waiter');
            $this->assign('waiter', $waiter_info);
			
            header("Content-Type:text/html;charset=" . CHARSET);		
            $this->display('my_waiter.form.html');
        }
        else
        {
            $data = array(
				'real_name' => trim($_POST['real_name']),
                'gender'    => trim($_POST['gender']),
                'phone_mob' => trim($_POST['phone_mob']),
                'im_qq'     => trim($_POST['im_qq']),
                'im_ww'     => trim($_POST['im_ww']),
            );
			!empty($_POST['password']) && $data['password'] = md5(trim($_POST['password']));
            !empty($_POST['email'])    && $data['email']    = trim($_POST['email']);
            $this->_waiter_mod->edit("waiter_id = {$waiter_id}", $data);
            if ($this->_waiter_mod->has_error())
            {
                $this->pop_warning($this->_waiter_mod->get_error());

                return;
            }
            $this->pop_warning('ok');
        }
    }
	
	function priv()
	{
		$waiter_id = empty($_GET['waiter_id']) ? 0 : intval($_GET['waiter_id']);
        if (!$waiter_id)
        {
			$this->show_warning('no_such_waiter');
           
            return;
        }
		
		$priv_data = include(ROOT_PATH . '/includes/waiter_priv.inc.php');
		
		if(!isset($priv_data['white']) || empty($priv_data['white']))
		{
			$this->show_warning('no_waiter_priv');
			
			return;
		}
		$priv_data = $priv_data['white'];
		
		/*设置一下显示的文字label */
		foreach($priv_data as $key=>$val)
		{
			foreach($val as $k=>$v){
				!isset($v['label']) && $priv_data[$key][$k]['label'] = LANG::get($k);
			}
		}

        if (!IS_POST)
        {
            $waiter_info     = $this->_waiter_mod->get("waiter_id = {$waiter_id}");
            if (empty($waiter_info))
            {
                echo Lang::get('no_such_waiter');

                return;
            }
			
			$privs = $this->_waiterpriv_mod->get(array(
				'conditions' => "waiter_id = {$waiter_id} ",
				'fields' => 'privs',
			));
			$priv=explode(',', $privs['privs']);
			
			// 以下查出权限如果带有app|all,则还原$priv_data 里面相关的项的所有act权限，存入$priv传递到模板 by andcpp 2013-3-1
			foreach($priv as $k => $p)
			{
				$chceked[$k] = explode('|', $p);
				
			}
			foreach($chceked as $k1 => $ch)
			{
				if(in_array("all",$ch))
				{
					unset($priv[$k1]);
					if(isset($priv_data[$ch[0]])) {
						foreach($priv_data[$ch[0]] as $p1)
						{
							$priv[] = $p1['value'];
						}
					}
					
				}
			}
			$this->assign('checked_priv',$priv);
			// end
			
			/* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                             LANG::get('my_waiter'), 'index.php?app=my_waiter',
                             LANG::get('edit_priv'));

            /* 当前用户中心菜单 */
            $this->_curitem('my_waiter');

            /* 当前所处子菜单 */
            $this->_curmenu('edit_priv');
			
            $this->assign('waiter', $waiter_info);
			$this->assign('privs',$priv_data);
            $this->display('my_waiter.priv.html');
        }
        else
        {
			$privs = (isset($_POST['priv']) && $_POST['priv']!='priv') ? $_POST['priv']: '';
			if ($privs == '')
            {
                $this->show_warning('add_priv');
                return;
            }
			foreach($privs as $key => $p)
			{
				if(count($p) == count($priv_data[$key]))
				{
					$all = true;
					$privs_new[]  = "{$key}|all";
					
				}
				else
				{
					$all = false;
					$privs_new[] = "{$key}|index";
				}
				
				foreach($p as $k1=>$p1)
				{
					!$all && $privs_new[] = $p1;
					$depend = $priv_data[$key][$k1]['depend'];
					if($depend)
					{
						foreach($depend as $depend){
							$privs_new[] = $depend;
						}
					}
						
				}
			}
			$priv = implode(",",array_unique($privs_new));
			
            $data = array(
                 'waiter_id' => $waiter_id,
                 'store_id' => $this->_store_id,
                 'privs' => $priv,
            );
			if($this->_waiterpriv_mod->get($waiter_id))
			{
				$this->_waiterpriv_mod->edit($waiter_id,array('privs' => $priv));
			}
			else
			{
				$this->_waiterpriv_mod->add($data);
			}
			if($this->_waiterpriv_mod->has_error())
			{
				$this->show_warning($this->_waiterpriv_mod->get_error());
				return;
			}
			$this->show_message('edit_priv_ok',
				'back_list', 'index.php?app=my_waiter');
        
        }
	}
	
	
	
    function drop()
    {
        $waiter_id = isset($_GET['id']) ? trim($_GET['id']) : 0;
        if (!$waiter_id)
        {
            $this->show_warning('no_such_waiter');

            return;
        }
        $ids = explode(',', $waiter_id);//获取一个类似array(1, 2, 3)的数组
        $drop_count = $this->_waiter_mod->drop("store_id = " . $this->_store_id . " AND waiter_id " . db_create_in($ids));
        if (!$drop_count)
        {
            /* 没有可删除的项 */
            $this->show_warning('no_such_waiter');

            return;
        }

        if ($this->_waiter_mod->has_error())    //出错了
        {
            $this->show_warning($this->_waiter_mod->get_error());

            return;
        }

        $this->show_message('drop_successed');
    }

    /**
     *    三级菜单
     *
     *    @author    Garbin
     *    @return    void
     */
    function _get_member_submenu()
    {
        $menus = array(
            array(
                'name'  => 'waiter_list',
                'url'   => 'index.php?app=my_waiter',
            ),
        );
        if (ACT == 'edit')
        {
            $menus[] = array(
                'name'  => 'edit_waiter',
            );
        }
		if (ACT == 'priv')
        {
            $menus[] = array(
                'name'  => 'edit_priv',
            );
        }
        return $menus;
    }
	
	/*检查会员名称的唯一性*/
    function  check_user()
    {
    	$waiter_name = empty($_GET['waiter_name']) ? null : trim($_GET['waiter_name']);
        if (!$waiter_name)
        {
            echo ecm_json_encode(false);
            return ;
        }
		$user_info = $this->_member_mod->get(array('conditions'=>'user_id='.$this->_store_id,'fields'=>'user_name'));
		$waiter_name1 = $user_info['user_name'] . ":" . $waiter_name; 
		$waiter_info = $this->_waiter_mod->get("waiter_name = '{$waiter_name1}'");
		$member_info = $this->_member_mod->get("user_name = '{$waiter_name1}'");
		if($waiter_info || $member_info)
		{
			echo ecm_json_encode(false);
            return ;
		}
		else
		{
			echo ecm_json_encode(true);
		}
    }
	
}

?>