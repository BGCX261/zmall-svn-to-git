<?php
class MsglogModel extends BaseModel
{
    var $table  = 'msglog';
    var $prikey = 'id';
    var $_name  = 'msglog';
	
	var $_relation = array(
        // ����һ����Ա
        'belongs_to_user' => array(
            'model'         => 'member',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'user_id',
            'reverse'       => 'has_msglog',
        ),
    );
}
?>