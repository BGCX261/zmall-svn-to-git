<?php

/**
 *    频道页 psmb
 *
 */
class ChannelsArrayfile extends BaseArrayfile
{
    function __construct()
    {
        $this->ChannelsArrayfile();
    }

    function ChannelsArrayfile()
    {
        $this->_filename = ROOT_PATH . '/data/channels.inc.php';
    }
}
?>
