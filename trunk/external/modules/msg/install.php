<?php
$filename = ROOT_PATH . '/data/msg.inc.php';
file_put_contents($filename, "<?php return array(); ?>");
$db=&db();
$db->query("DROP TABLE IF EXISTS ".DB_PREFIX."msg");
$db->query("CREATE TABLE ".DB_PREFIX."msg (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned NOT NULL DEFAULT '0',
  num int(10) unsigned NOT NULL DEFAULT '0',
  functions varchar(255) DEFAULT NULL,
  state tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE = MYISAM DEFAULT CHARSET=".str_replace('-','',CHARSET).";");
 
$db->query("DROP TABLE IF EXISTS ".DB_PREFIX."msglog");
$db->query("CREATE TABLE ".DB_PREFIX."msglog (
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned NOT NULL DEFAULT '0',
  to_mobile varchar(100) DEFAULT NULL,
  content text DEFAULT NULL,
  quantity tinyint(3) unsigned NOT NULL DEFAULT '0',
  state tinyint(3) unsigned NOT NULL DEFAULT '0',
  result varchar(10) DEFAULT NULL,
  type int(10) unsigned NULL DEFAULT '0',
  `time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE = MYISAM DEFAULT CHARSET=".str_replace('-','',CHARSET).";");

$db->query("DROP TABLE IF EXISTS ".DB_PREFIX."msg_statistics");
$db->query("CREATE TABLE ".DB_PREFIX."msg_statistics (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `available` int(10) unsigned NOT NULL DEFAULT '0',
  `used` int(10) unsigned NOT NULL DEFAULT '0',
  `allocated` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE = MYISAM DEFAULT CHARSET=".str_replace('-','',CHARSET).";");
$db->query("INSERT INTO ".DB_PREFIX."msg_statistics (`user_id`, `available`, `used`, `allocated`) VALUES
(0, 0, 0, 0)");
?>