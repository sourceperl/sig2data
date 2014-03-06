<?php
// connect to DB
$mysqli = new mysqli('localhost','bee_sigfox_cbk', '', 'beedb');

// check DB connect
if ($mysqli->connect_errno) {
  error_log('connect error: '.$mysqli->connect_error);
  exit();
}

// turn on auto-commit
$mysqli->autocommit(TRUE);

// define log function to SQL log
function sql_log($message) {
  // some vars
  global $mysqli;
  // log message to SQL DB
  $sql = "INSERT INTO `sig_debug_log` (`id`, `date`, `text`) VALUES (NULL, ".
         "NOW(), '".$message."');";
  // check SQL query
  if (! $mysqli->query($sql)) {
    error_log('invalid SQL query: '.$mysqli->error());
    exit();
  }
}

// log client
$stat_ip = $_SERVER['REMOTE_ADDR'];
$ua      = $_SERVER['HTTP_USER_AGENT'];

// manage POST param
$cb_msg = isset($_POST['callback']) ? stripcslashes($_POST['callback']) : "";
$appid  = isset($_POST['appid'])    ? stripcslashes($_POST['appid'])    : "";
$appkey = isset($_POST['appkey'])   ? stripcslashes($_POST['appkey'])   : "";

// check the app key
$sql = "SELECT id FROM `sig_vars` WHERE var_name='callback_app_key' ".
       "AND var='".$appkey."';";
$result = $mysqli->query($sql);

// error check
if (!$result) {
  error_log("invalid SQL query: ".$mysqli->error());
  exit();
}

// app_key mismatch
if ($result->num_rows == 0) {
  // log error and y3exit script
  sql_log("error: app_key not valid, connect from IP: ".$stat_ip."/".$ua);
  // log error and exit
  error_log("error: app_key not valid", 0);
  http_response_code(500);
  exit();
}
// release result dataset
$result->close();

// decode JSON
$data = json_decode($cb_msg);
// check json decoding
if ($data === null) {
  // log error and y3exit script
  sql_log("error: json_decode return null json: ".$cb_msg);
  // release result dataset
  error_log("error: json_decode return null");
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
  sql_log("error: unknown object UID: ".$uid);
  // release result dataset
  error_log("error: unknown object UID");
  exit();
}

$row = $result->fetch_assoc();
$obj_id = $row['object_id'];

// release result dataset
$result->close();

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
// SQL query error ?
if (! $mysqli->query($sql)) {
  error_log("SQL query error: ".$mysqli->error, 0);
  exit();
// check if insert or not (= duplicate)
} else if ($mysqli->affected_rows == 0) {
  // log warning
  sql_log("warning: callback duplicate object/timestamp: ".
          $obj_id."/".$timestamp);
  exit();
}

error_log("log: store msg ID#".$mysqli->insert_id." [object_id ".$obj_id."/payload ".$msg_payload."]");

$mysqli->close();
exit();
?>
