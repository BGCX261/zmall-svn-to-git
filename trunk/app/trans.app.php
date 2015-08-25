<?php

/**
 *    导航管理控制器
 *
 *    @author    Garbin
 *    @usage    none
 */
class TransApp extends StoreadminbaseApp
{
    var $_trans_mod;

    function __construct()
    {
        $this->TransApp();
    }

    function TransApp()
    {
        parent::__construct();
        $this->_trans_mod =& m('trans');
        $this->_store_id  = intval($this->visitor->get('manage_store'));
        $this->_user_id  = intval($this->visitor->get('user_id'));
        $options=array(
                'apply_type'=>$this->_trans_mod->get_options_type(),     
                'enabled'=>$this->_trans_mod->get_options_enabled(),//360cd.cn     
                );
        $this->assign('options',$options);
    }


    function _get_regions()
    {
        $model_region =& m('region');
        $regions = $model_region->get_list(0);
        if ($regions)
        {
            $tmp  = array();
            foreach ($regions as $key => $value)
            {
                $tmp[$key] = $value['region_name'];
            }
            $regions = $tmp;
        }
        $this->assign('regions', $regions);
    }

    function index()
    {
        $conditions ='';
        $conditions = $this->_get_query_conditions(array(array(
                'field' => 'title',         //可搜索字段title
                'equal' => 'LIKE',          //等价关系,可以是LIKE, =, <, >, <>
            ),
        ));

        /* 取得列表数据 */
        $conditions.=' and store_id ='.$this->_store_id;
      
        $page   =   $this->_get_page(10);    //获取分页信息
        $trans_list = $this->_trans_mod->find(array(
        'conditions'  => '1=1 '.$conditions,
        'limit'   => $page['limit'],
        'count'   => true   //允许统计
        ));

        $page['item_count']=$this->_trans_mod->getCount(); 
        $this->_format_page($page);   
        $this->assign('page_info', $page);  
        $this->assign('trans_list', $trans_list);

        /* 当前位置 */
        $this->_curlocal(LANG::get('member_center'), url('app=member'),
                         LANG::get('trans'), url('app=trans'),
                         LANG::get('trans_list'));
        $this->_curitem('trans');
        $this->_curmenu('trans_list');

        $this->import_resource(array(
            'script' => array(
                array(
                    'path' => 'dialog/dialog.js',
                    'attr' => 'id="dialog_js"',
                ),
                array(
                    'path' => 'mlselection.js',
                    'attr' =>'',
                ),array(
                    'path' => 'jquery.plugins/jquery.validate.js',
                    'attr' =>'',
                ),
                array(
                    'path' => 'jquery.ui/jquery.ui.js',
                    'attr' => '',
                ), array(
                    'path' => 'jquery.ui/i18n/' . i18n_code() . '.js',
                    'attr' => '',
                ),
                 array(
                    'path' => 'utils.js',
                    'attr' => '',
                ),array(
                    'path' => 'inline_edit.js',
                    'attr' => '',
                ),
                array(
                    'path' => 'jquery.plugins/jquery.validate.js',
                    'attr' => '',
                ),
                ),
            'style' =>  'jquery.ui/themes/ui-lightness/jquery.ui.css',
        ));



        $this->assign('filtered', $conditions? 1 : 0); //是否有查询条件
        //将分页信息传递给视图，用于形成分页条
        $this->_config_seo('title', Lang::get('member_center') . ' - ' . Lang::get('trans'));
        header("Content-Type:text/html;charset=" . CHARSET);
        $this->display('trans.index.html');
    }

    /**
     *    添加地址
     *
     *    @author    Garbin
     *    @return    void
     */
    function add()
    {
        if (!IS_POST)
        {
            /* 当前位置 */
            $this->_curlocal(LANG::get('member_center'),    'index.php?app=member',
                             LANG::get('trans'), 'index.php?app=trans',
                             LANG::get('trans_add'));

            /* 当前用户中心菜单 */
            $this->_curitem('trans');

            /* 当前所处子菜单 */
            $this->_curmenu('trans_add');

           

          
            extract($this->_get_theme());
            $template_name = $this->_get_template_name();
            $style_name    = $this->_get_style_name();
            $this->_get_regions();
            
            header("Content-Type:text/html;charset=" . CHARSET);
            $this->display('trans.form.html');
        }
        else
        {
                     $data = array();
//得到字段提交上来的信息
                        $data['apply_num']=trim($_POST['apply_num']);
                        $data['apply_money']=trim($_POST['apply_money']);
                        $data['apply_type']=trim($_POST['apply_type']);
                        $data['title']=trim($_POST['title']);
                        $data['trans_money']=trim($_POST['trans_money']);
                        $data['enabled']=trim($_POST['enabled']);
                        $data['region_name']=trim($_POST['region_name']);
                        $data['region_id']=trim($_POST['region_id']);
                        $data['rules']=trim($_POST['rules']);
                        $data['store_id']=$this->_store_id;
           
          
            $id = $this->_trans_mod->add($data);
            if (!$id)
            {
                $this->pop_warning($this->_trans_mod->get_error());

                return;
            }
            else
            {
                /* 清除缓存 */
                $this->_clear_cache();
            }

          
            $this->pop_warning('ok');
        }
    }

    function edit()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
       
        if (!$id)
        {
            echo Lang::get('no_such_trans');

            return;
        }
        if (!IS_POST)
        {
            
            $trans_item = $this->_trans_mod->get_info($id);
            if (!$trans_item )
            {
                $this->show_warning('trans_empty');
                return;
            }

            
            //上传图片是传给iframe的参数
            $this->assign("id", $id);
           
            $this->_curlocal(LANG::get('member_center'), url('app=member'),
                             LANG::get('trans'), url('app=trans'),
                             LANG::get('trans_edit'));
            $this->_curitem('trans');
            $this->_curmenu('trans_edit');

            $this->_assign_form();
             $this->_get_regions();


            extract($this->_get_theme());
            //编辑器功能

         
            $this->assign('trans', $trans_item);
            header("Content-Type:text/html;charset=" . CHARSET);
            $this->display('trans.form.html');
        }
        else
        {
                      $data = array();
                        $data['apply_num']=trim($_POST['apply_num']);
                        $data['apply_money']=trim($_POST['apply_money']);
                        $data['apply_type']=trim($_POST['apply_type']);
                        $data['title']=trim($_POST['title']);
                        $data['trans_money']=trim($_POST['trans_money']);
                        $data['enabled']=trim($_POST['enabled']);
                        $data['region_name']=trim($_POST['region_name']);
                        $data['region_id']=trim($_POST['region_id']);
                        $data['rules']=trim($_POST['rules']);
                         $data['store_id']=$this->_store_id;

         
             /* 保存 */
            $rows = $this->_trans_mod->edit($id, $data);
            if ($this->_trans_mod->has_error())
            {
                $this->pop_warning($this->_trans_mod->get_error());
                return;
            }
            /* 清除缓存 */
            $rows && $this->_clear_cache();
            $this->pop_warning('ok', 'trans_edit');
        }
    }
    function drop()
    {
         $id = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (!$id)
        {
            $this->show_warning('no_trans_to_drop');
            return;
        }

        $ids = explode(',', $id);//获取一个类似array(1, 2, 3)的数组
        if (!$this->_trans_mod->drop($ids))
        {
            $this->show_warning($this->_trans_mod->get_error());
            return;
        }else{
             $this->_clear_cache();
        }
        $this->show_message('drop_ok');

       
    }

    function ajax_col()
    {
        $id     = empty($_GET['id']) ? 0 : intval($_GET['id']);
        $column = empty($_GET['column']) ? '' : trim($_GET['column']);
        $value  = isset($_GET['value']) ? trim($_GET['value']) : '';
        $data   = array();
        if (in_array($column ,array('recommended','sort_order')))
        {
            $data[$column] = $value;
            $this->_trans_mod->edit($id, $data);
            if(!$this->_trans_mod->has_error())
            {
                echo ecm_json_encode(true);
            }
        }       
        else
        {
            return ;
        }
        return ;
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
                'name'  => 'trans_list',
                'url'   => 'index.php?app=trans',
            ),
        );
        return $menus;
    }

    function _assign_form()
    {
        
    }

    
    
    /* 清除缓存 */
    function _clear_cache()
    {        
        $cache_server =& cache_server();
        $cache_server->delete('function_get_app_trans_data_' . $this->visitor->get('manage_store'));
    }

}

?>