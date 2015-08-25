<?php

/**
 * ECMALL: 用户中心管理左侧菜单店小二权限管理列表
 * ============================================================================
 * 开 发 者: 模客网
 * 网站地址: http://www.psmoban.com
 * ----------------------------------------------------------------------------
 *
 * 如需增加权限管理项，可在以下数组中增加，并在对应的语言包增加语言项
 * 
 * 实例： array(APP  =>  array( ACT => array(Label,APP|ACT))
 *
 * 注意：1.除了白名单和黑名单中设置的APP|ACT外，其他APP|ACT，默认允许店小二访问；2.白名单中的APP必须是继承父类StoreadminbaseApp才有效
 *
 * ============================================================================
*/

!defined('ROOT_PATH') && exit('Forbidden');

// 店小二权限白名单，可在店小二管理->编辑权限 中，共选择的项目
$priv_white = array(
	'my_goods' 			=>	array(
		'add'			=>  	array('value'=>'my_goods|add'),
		'edit'			=>		array('value'=>'my_goods|edit'),
		'drop'			=>		array('value'=>'my_goods|drop'),
		'ajax_col'		=>		array('value'=>'my_goods|ajax_col'),
		'import_taobao'	=>		array('value'=>'my_goods|import_taobao'),
		'truncate'		=>		array('value'=>'my_goods|truncate'),
		'brand_list'    =>		array('label'=>LANG::get('brand_view'),'value'=>'my_goods|brand_list'),
		'brand_apply'   =>		array('value'=>'my_goods|brand_apply','depend'=>array('my_goods|brand_list')),
		'brand_edit'   =>		array('value'=>'my_goods|brand_edit','depend'=>array('my_goods|brand_list')),
		'brand_drop'   =>		array('value'=>'my_goods|brand_drop','depend'=>array('my_goods|brand_list')),
	),
	'seller_groupbuy'   => 	array(
		'add'			=>		array('value'=>'seller_groupbuy|add'),
		'edit'			=>		array('value'=>'seller_groupbuy|edit'),
		'drop'			=>		array('value'=>'seller_groupbuy|drop'),
		'cancel'		=>		array('value'=>'seller_groupbuy|cancel'),
		'desc'			=>		array('value'=>'seller_groupbuy|desc'),
		'log'			=>		array('value'=>'seller_groupbuy|log'),
		'finished'		=>		array('value'=>'seller_groupbuy|finished'),
		'export_ubbcode'=>		array('value'=>'seller_groupbuy|export_ubbcode'),
	),
	'my_qa'       		=> 	array(
		'reply'			=>		array('value'=>'my_qa|reply'),
		'edit_reply'	=>		array('value'=>'my_qa|edit_reply'),
		'drop'			=>		array('value'=>'my_qa|drop'),
	),
	'my_category'    	=> 	array(
		'add'			=>		array('value'=>'my_category|add'),
		'edit'			=>		array('value'=>'my_category|edit'),
		'drop'			=>		array('value'=>'my_category|drop'),
		'export'		=>		array('value'=>'my_category|export'),
		'import'		=>		array('value'=>'my_category|import'),
		'ajax_col'		=>		array('value'=>'my_category|ajax_col'),
	),
	'seller_order'     	=> array(
		'view'			=>		array('value'=>'seller_order|view'),
		'received_pay' 	=> 		array('value'=>'seller_order|received_pay'),
		'adjust_fee'	=>		array('value'=>'seller_order|adjust_fee'),
		'shipped'		=>		array('value'=>'seller_order|shipped'),
		'cancel_order'	=>		array('value'=>'seller_order|cancel_order'),
	),
	'my_store'   		=> array(
		'index'			=>		array('label'=>LANG::get('set'),'value'=>'my_store|index'),
	),
	'my_theme'  		=> array(
		'set'			=>		array('value'=>'my_theme|set'),
	),
	'my_payment'  		=> array(
		'install'		=>		array('value'=>'my_payment|install'),
		'config'		=>		array('value'=>'my_payment|config'),
		'uninstall'		=>		array('value'=>'my_payment|uninstall'),
	),
	'my_shipping'  		=> array(
		'add'			=>		array('value'=>'my_shipping|add'),
		'edit'			=>		array('value'=>'my_shipping|edit'),
		'drop'			=>		array('value'=>'my_shipping|drop'),
	),
	'my_navigation'  	=> array(
		'add'			=>		array('value'=>'my_navigation|add'),
		'edit'			=>		array('value'=>'my_navigation|edit'),
		'drop'			=>		array('value'=>'my_navigation|drop'),
	),
	'my_partner'  		=> array(
		'add'			=>		array('value'=>'my_partner|add'),
		'edit'			=>		array('value'=>'my_partner|edit'),
		'drop'			=>		array('value'=>'my_partner|drop'),
	),
	'coupon'  			=> array(
		'add'			=>		array('value'=>'coupon|add'),
		'edit'			=>		array('value'=>'coupon|edit'),
		'drop'			=>		array('value'=>'coupon|drop'),
		'export'		=>		array('value'=>'coupon|export'),
		'extend'		=>		array('value'=>'coupon|extend'),
		'issue'			=>		array('value'=>'coupon|issue'),
	),
				
);

// 店小二权限黑名单，把不想给店小二访问的操作写在此处，注意：当前操作的APP文件必须是继承父类 MemberbaseApp 才有效
$priv_black = array('member|profile','member|password','member|email','message|all','friend|all','buyer_order|all','buyer_groupbuy|all','my_question|all','my_favorite|all','my_address|all','my_coupon|all','my_waiter|all');


return array('white'=> $priv_white, 'black'=> $priv_black);

?>