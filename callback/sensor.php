<?php
// JSON library
require_once '../../lib/JSON.php';
// connect to MySQL
include('../../secret/connect.php');
mysql_connect($host,$user,$pswd);
// log client
$stat_ip = $_SERVER['REMOTE_ADDR'];
$ua      = $_SERVER['HTTP_USER_AGENT'];
// manage POST param
$cb_msg = stripcslashes($_POST['callback']);
$appid  = stripcslashes($_POST['appid']);
// create a new instance of Services_JSON
$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
$value = $json->decode($cb_msg);
//Ecriture dans la base de données
mysql_select_db($base);
$sql = "INSERT INTO `bee_farm`.`sensor_posts` (`id`, `app_id`, `callback_id`, "
       ."`device_uid`, `type`, `payload`, `when`, `level`, `station`, "
       ."`http_ip`, `http_ua`, `http_date`) VALUES "
       ."(NULL, '".$appid."', '".$value['id']."', '"
       .$value['msg']['ctxt']['uid']."', '".$value['msg']['type']."', '"
       .$value['msg']['payload']."', '".$value['msg']['when']."', '"
       .$value['msg']['lvl']."', '".$value['msg']['station']."', '"
       .$stat_ip."', '".$ua."', NOW());";
$update = mysql_query($sql);
mysql_close();
?>