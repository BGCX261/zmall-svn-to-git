<?php

class Store_tipsApp extends BackendApp
{
    var $_storetips_mod;

    function __construct()
    {
        $this->Store_tipsApp();
    }

    function Store_tipsApp()
    {
        parent::__construct();
        $this->_storetips_mod =& m('storetips');
    }

    function index()
    {
        $page = $this->_get_page();
        $this->_format_page($page);
		$tips = $this->_storetips_mod->find(array('limit'=>$page['limit'],'count'=>true));
		$page['item_count'] = $this->_storetips_mod->getCount();
		
		$this->assign('tips',$tips);
        $this->assign('page_info', $page);

        $this->display('tips.index.html');
    }

    function add()
    {
        if (!IS_POST)
        {
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js')
			);
            $this->display('tips.form.html');
        }
        else
        {
            $data = array(
                'tips_content'   => $_POST['tips_content'],
				'add_time'  =>gmtime()
            );
            $tips_id = $this->_storetips_mod->add($data);
            if (!$tips_id)
            {
                $this->show_warning($this->_storetips_mod->get_error());
                return;
            }
            $this->show_message('添加TIPS成功',
                '返回上一页面',    'index.php?app=store_tips',
                '继续添加', 'index.php?app=store_tips&amp;act=add'
            );
        }
    }
    function edit()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {	
            $this->show_warning('Hacking Attempt');
            return;
        }

        if (!IS_POST)
        {
			$tips=$this->_storetips_mod->get($id);
            $this->import_resource(array('script' => 'jquery.plugins/jquery.validate.js'));
			$this->assign('tips',$tips);
            $this->display('tips.form.html');
        }
        else
        {
            $data = array(
                'tips_content'   => $_POST['tips_content'],
            );
            $this->_storetips_mod->edit($id, $data);
            $this->show_message('编辑成功',
                '返回上一页',    'index.php?app=store_tips',
                '重新编辑',   'index.php?app=store_tips&amp;act=edit&amp;id=' . $id
            );
        }
    }
    function drop()
    {
        $id = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (!$id)
        {
            $this->show_warning('hack_attempt');
            return;
        }

        $ids = explode(',', $id);
        $ids = array_diff($ids, array(1)); // 默认等级不能删除
        if (!$this->_storetips_mod->drop($ids))
        {
            $this->show_warning($this->_storetips_mod->get_error());
            return;
        }
        $this->show_message('删除成功！');
    }
}
?>
