<?php
// connect to DB
$mysqli = new mysqli('localhost','bee_sigfox_cbk', '', 'beedb');
// check DB connect
if ($mysqli->connect_errno) {
  printf("Connect failed: %s\n", $mysqli->connect_error);
  exit();
}
// turn on auto-commit
$mysqli->autocommit(TRUE);

// log client
$stat_ip = $_SERVER['REMOTE_ADDR'];
$ua      = $_SERVER['HTTP_USER_AGENT'];

// manage POST param
$cb_msg = isset($_POST['callback']) ? stripcslashes($_POST['callback']) : "";
$appid  = isset($_POST['appid'])    ? stripcslashes($_POST['appid'])    : "";
$appkey = isset($_POST['appkey'])   ? stripcslashes($_POST['appkey'])   : "";

// check the app key
$result = $mysqli->query("SELECT id FROM `vars` ".
                         "WHERE var_name='callback_app_key' AND var='".
                         $appkey."';");
if ($result->num_rows == 0) {
  // log error and exit script
  $mysqli->query("INSERT INTO debug_log (`id`, `date`, `text`) VALUES (NULL, ".
                 "NOW(), 'err: app_key not valid, connect from IP: ".$stat_ip.
                 "/".$ua."');");
  $mysqli->close();
  exit();
}

// decode JSON
$data = json_decode($cb_msg);
// check json decoding
if ($data === null) {
  $mysqli->query("INSERT INTO debug_log (`id`, `date`, `text`) ".
                 "VALUES (NULL, NOW(), 'err: json_decode return null json: ".
                 $cb_msg."');");
  $mysqli->close();
  exit();
} else {
  $mysqli->query("INSERT INTO debug_log (`id`, `date`, `text`) ".
                 "VALUES (NULL, NOW(), 'log: json_decode ok json: ".
                 $cb_msg."');");
}

// JSON params
$uid         = hexdec($data->{'msg'}->{'ctxt'}->{'uid'});
$timestamp   = $data->{'msg'}->{'when'};
$msg_type    = $data->{'msg'}->{'type'};
$msg_payload = $data->{'msg'}->{'payload'};
$station_id  = isset($data->{'msg'}->{'station'}) ? $data->{'msg'}->{'station'} : 0;
$station_lvl = isset($data->{'msg'}->{'lvl'}) ? $data->{'msg'}->{'lvl'} : 0;

// get object_id
$result = $mysqli->query("SELECT object_id FROM `objects` WHERE modem_id='".
                          $uid."';");
if ($result->num_rows == 0) {
  // log error and exit script
  $mysqli->query("INSERT INTO debug_log (`id`, `date`, `text`) ".
                 "VALUES (NULL, NOW(), 'err: unknown object uid: ".$uid."');");
  $mysqli->close();
  exit();
}
$row = $result->fetch_assoc();
$obj_id = $row['object_id'];

// insert in DB, if not already exist (if polling value or duplicate callback)
$sql = "INSERT INTO `messages` (`message_id`, `object_id`, "
       ."`rx_timestamp`, `type`, `payload`, `station_id`, `station_lvl`) "
       ."SELECT NULL, '".$obj_id."', '".$timestamp."', '".$msg_type."', '"
       .$msg_payload."', '".$station_id."', '".$station_lvl."' FROM DUAL "
       ."WHERE NOT EXISTS (SELECT * FROM `messages` WHERE `object_id` = '"
       .$obj_id."' AND `rx_timestamp`= '".$timestamp."' LIMIT 1);";
$mysqli->query($sql);
// check if insert or not (= duplicate)
if ($mysqli->affected_rows == 0) {
  // log warning
  $mysqli->query("INSERT INTO debug_log (`id`, `date`, `text`) "
                 ."VALUES (NULL, NOW(), "
                 ."'warn: callback duplicate obj_id/timestamp: "
                 .$obj_id."/".$timestamp."');");
}
$mysqli->close();
exit()
?>
