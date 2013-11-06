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
$cb_msg = stripcslashes($_POST['callback']);
$appid  = stripcslashes($_POST['appid']);
$appkey  = stripcslashes($_POST['appkey']);

// check the app key
$result = $mysqli->query("SELECT id FROM `vars` ".
                         "WHERE var_name='callback_app_key' AND var='".
                         $appkey."';");
if ($result->num_rows == 0) {
  // log error and exit script
  $mysqli->query("INSERT INTO debug_log (`id`, `date`, `text`) VALUES (NULL, ".
                 "NOW(), 'err: app_key not valid, connect from IP: ".$stat_ip.
                 "/".$ua."');");
  exit();
}

// decode JSON
$data = json_decode($cb_msg);
// check json decoding
if ($data === null) {
  $mysqli->query("INSERT INTO debug_log (`id`, `date`, `text`) ".
                 "VALUES (NULL, NOW(), 'err: json_decode return null json: ".
                 $cb_msg."');");
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
  exit();
}
$row = $result->fetch_assoc();
$obj_id = $row['object_id'];

// check if data is already in DB
$result = $mysqli->query("SELECT message_id FROM `message` WHERE object_id='".
                         $obj_id."' AND rx_timestamp='".$timestamp."';");
if ($result->num_rows == 0) {
  // log error and exit script
  $mysqli->query("INSERT INTO debug_log (`id`, `date`, `text`) VALUES (NULL, ".
                 "NOW(), 'err: msg already in DB for obj ID/timestamp".
                 $obj_id."/".$timestamp.");");
  exit();
}

// write to DB
$sql = "INSERT INTO `messages` (`message_id`, `object_id`, "
       ."`rx_timestamp`, `type`, `payload`, `station_id`, `station_lvl`) "
       ."VALUES (NULL, '".$obj_id."', '".$timestamp."', '".$msg_type."', '"
       .$msg_payload."', '".$station_id."', '".$station_lvl."');";
$mysqli->query($sql);
$mysqli->close();
?>
