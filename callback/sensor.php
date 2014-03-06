<?php
// connect to DB
$mysqli = new mysqli('localhost','bee_sigfox_cbk', '', 'beedb');
// check DB connect
if ($mysqli->connect_errno) {
  printf("Connect failed: %s\n", $mysqli->connect_error);
  http_response_code(500);
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
$result = $mysqli->query("SELECT id FROM `sig_vars` ".
                         "WHERE var_name='callback_app_key' AND var='".
                         $appkey."';");
if ($result->num_rows == 0) {
  // log error and exit script
  $mysqli->query("INSERT INTO `sig_debug_log` (`id`, `date`, `text`) VALUES (NULL, ".
                 "NOW(), 'err: app_key not valid, connect from IP: ".$stat_ip.
                 "/".$ua."');");
  $mysqli->close();
  http_response_code(500);
  exit();
}

// decode JSON
$data = json_decode($cb_msg);
// check json decoding
if ($data === null) {
  $mysqli->query("INSERT INTO `sig_debug_log` (`id`, `date`, `text`) ".
                 "VALUES (NULL, NOW(), 'err: json_decode return null json: ".
                 $cb_msg."');");
  $mysqli->close();
  http_response_code(500); 
  exit();
} else {
  $mysqli->query("INSERT INTO `sig_debug_log` (`id`, `date`, `text`) ".
                 "VALUES (NULL, NOW(), 'log: json_decode ok json: ".
                 $cb_msg."');");
}

// JSON params
$uid         = hexdec($data->{'msg'}->{'ctxt'}->{'uid'});
$timestamp   = isset($data->{'msg'}->{'received'})
               ? $data->{'msg'}->{'received'}/1000 : "";
$msg_type    = isset($data->{'msg'}->{'type'}) 
               ? $data->{'msg'}->{'type'}          : "";
$msg_payload = isset($data->{'msg'}->{'payload'})
               ? $data->{'msg'}->{'payload'}       : "";
$station_id  = isset($data->{'msg'}->{'station'})
               ? $data->{'msg'}->{'station'}       : 0;
$station_lvl = isset($data->{'msg'}->{'lvl'})
               ? $data->{'msg'}->{'lvl'}           : 0;

// get object_id
$result = $mysqli->query("SELECT object_id FROM `sig_objects` WHERE modem_id='".
                          $uid."';");
if ($result->num_rows == 0) {
  // log error and exit script
  $mysqli->query("INSERT INTO `sig_debug_log` (`id`, `date`, `text`) ".
                 "VALUES (NULL, NOW(), 'err: unknown object uid: ".$uid."');");
  $mysqli->close();
  exit();
}
$row = $result->fetch_assoc();
$obj_id = $row['object_id'];

// insert in DB, if not already exist (if polling value or duplicate callback)
// add 06/03/2014, duplicate bug workaround (before UNB modem firmware change):
//   if same payload within a window of +/- 600s since last message,
//   insert is discard
$sql = "INSERT INTO `sig_messages` (`message_id`, `object_id`, "
       ."`rx_timestamp`, `type`, `payload`, `station_id`, `station_lvl`) "
       ."SELECT NULL, '".$obj_id."', '".$timestamp."', '".$msg_type."', '"
       .$msg_payload."', '".$station_id."', '".$station_lvl."' FROM DUAL "
       ."WHERE NOT EXISTS (SELECT * FROM `sig_messages` WHERE (`object_id` = '"
       .$obj_id."') AND ((`rx_timestamp`= '".$timestamp."') OR "
       ."(`rx_timestamp` BETWEEN '".($timestamp - 600)."' AND '"
       .($timestamp + 600)."' AND `payload` = '".$msg_payload."'))"
       ." LIMIT 1);";
/* old request
$sql = "INSERT INTO `sig_messages` (`message_id`, `object_id`, "
       ."`rx_timestamp`, `type`, `payload`, `station_id`, `station_lvl`) "
       ."SELECT NULL, '".$obj_id."', '".$timestamp."', '".$msg_type."', '"
       .$msg_payload."', '".$station_id."', '".$station_lvl."' FROM DUAL "
       ."WHERE NOT EXISTS (SELECT * FROM `sig_messages` WHERE `object_id` = '"
       .$obj_id."' AND `rx_timestamp`= '".$timestamp."' LIMIT 1);";
*/
$mysqli->query($sql);
// check if insert or not (= duplicate)
if ($mysqli->affected_rows == 0) {
  // log warning
  $mysqli->query("INSERT INTO `sig_debug_log` (`id`, `date`, `text`) "
                 ."VALUES (NULL, NOW(), "
                 ."'warn: callback duplicate obj_id/timestamp: "
                 .$obj_id."/".$timestamp."');");
}
$mysqli->close();
exit()
?>
