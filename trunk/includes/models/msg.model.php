<?php
class MsgModel extends BaseModel
{
    var $table  = 'msg';
    var $prikey = 'id';
    var $_name  = 'msg';
	
	var $_relation = array(
        // 属于一个会员
        'belongs_to_user' => array(
            'model'         => 'member',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'user_id',
            'reverse'       => 'has_msg',
        ),
    );
}
?>