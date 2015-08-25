<?php
class MsgArrayfile extends BaseArrayfile 
{
    function __construct()
    {
        $this->MsgArrayfile();
    }
    
    function MsgArrayfile()
    {
        $this->_filename = ROOT_PATH . '/data/msg.inc.php';
    }
    
}
?>