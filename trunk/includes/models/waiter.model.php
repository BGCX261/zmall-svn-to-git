<?php

/* 店小二 member */
class WaiterModel extends BaseModel
{
    var $table  = 'waiter';
    var $prikey = 'waiter_id';
    var $_name  = 'waiter';

    /*
     * 判断名称是否唯一
     */
	function check_waiter($waiter_name,$password)
	{
		$info = $this->get(array('conditions'=>"waiter_name='{$waiter_name}' AND password='".md5($password)."'",'fields'=>'waiter_id'));
        if (!$info)
        {
            $this->_error('auth_failed');

            return;
        }
		return $info['waiter_id'];
	}
}

?>