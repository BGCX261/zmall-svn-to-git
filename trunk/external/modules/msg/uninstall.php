<?php

/**
 * 这里可以放一些卸载模块时需要执行的代码，比如删除表，删除目录、文件之类的
 */

$filename = ROOT_PATH . '/data/msg.inc.php';

$db=&db();
$db->query("DROP TABLE ".DB_PREFIX."msg");
$db->query("DROP TABLE ".DB_PREFIX."msglog");
$db->query("DROP TABLE ".DB_PREFIX."msg_statistics");



if (file_exists($filename))
{
    @unlink($filename);
}

?>