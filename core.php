<?php
error_reporting(E_ALL & ~E_NOTICE);

session_start();

include_once('./bd.auth');

/** Распознавание IP посетителя **/

if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip=$_SERVER['HTTP_CLIENT_IP'];
} else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip=$_SERVER['REMOTE_ADDR'];
}

$con = mysql_connect($DB_SERVER, $DB_USER, $DB_PASS);
mysql_query("set names utf8");
mysql_select_db($DB_NAME);

$device_id = abs($_POST['id']);
$access_token = stripcslashes(htmlspecialchars(addslashes($_REQUEST['access_token'])));

$access = false;

if($access_token != '') {
    $rst_prof = mysql_query("SELECT `users`.`id`, `devices`.`device_id`, `users`.`name`, `users`.`lastname`, `users`.`total_size`, `users`.`used_size`, `devices`.`token`, `devices`.`timestamp` FROM `users` LEFT JOIN `devices` ON `devices`.`token`='".$access_token."' WHERE `users`.`id`=`devices`.`uid`", $con);
	if($rst_prof && mysql_num_rows($rst_prof) == 1) $me_info = mysql_fetch_array($rst_prof);
    mysql_free_result($rst_prof);
    if($me_info['timestamp'] > 0 && (time() - $me_info['timestamp']) <= 6000000) $enter = true;
    unset($me_info['token']);
}

?>