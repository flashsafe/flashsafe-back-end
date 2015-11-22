<?php
    
include_once('./core.php');

if(!$enter) { // Если неавторизован
    if($device_id > 0) { // Если существующий девайс
        if($access_token == '') { // Если access_token не передан (1 этап авторизации)
            $rst = mysql_query("SELECT `version`, `secret`, `secret2` FROM `devices` WHERE `device_id`=".$device_id, $con);
            if($rst) {
                if(mysql_num_rows($rst) == 1) {
                    $data = mysql_fetch_array($rst);
                    mysql_free_result($rst);
                    if($data['version'] == 1) {
                        $time = time();
                        $secret = md5($device_id.$data['secret'].$time);
						mysql_query("INSERT INTO `auth` (`device_id`, `token1`, `token2`, `timestamp`, `ip`) VALUES (".$device_id.", '".$secret."', '".md5($secret.$data['secret2'].$time)."', ".$time.", '".$ip."')", $con);
                        $response = array('meta'=>array('code'=>'200'), 'data'=>array('timestamp'=>$time, 'token'=>$secret));
                        echo json_encode($response);
                    } else {
						echo 'Err 3';
					}
                    unset($data);
                }else{
                    echo 'Err 2';
                }
            } else {
                echo 'Err 1';
            }
            echo ' '.mysql_error();
        } else { // Если access_token передан (2 этап авторизации)
            $rst = mysql_query("SELECT `timestamp` FROM `auth` WHERE `device_id`=".$device_id." AND `token2`='".$access_token."' AND `ip`='".$ip."'", $con);
            if($rst) {
                if(mysql_num_rows($rst) == 1) {
                    $time = time();
                    mysql_query("UPDATE `devices` SET `token` = '".$access_token."', `timestamp` = ".$time." WHERE `device_id`=".$device_id." LIMIT 1", $con);
					$rst_prof = mysql_query("SELECT `users`.`id`, `users`.`name`, `users`.`lastname`, `users`.`total_size`, `users`.`used_size` FROM `users` LEFT JOIN `devices` ON `devices`.`token` = '".$access_token."' WHERE `users`.`id`=`devices`.`uid`", $con);
                    if($rst_prof && mysql_num_rows($rst_prof) == 1) { $info = mysql_fetch_array($rst_prof); } else { echo 'Err 5'; }
					$response = array('meta'=>array('code'=>'200'), 'data'=>array('uid'=>$info['id'], 'name'=>$info['name'], 'lastname'=>$info['lastname'], 'total_size'=>$info['total_size'], 'used_size'=>$info['used_size'], 'timestamp'=>$time, 'token'=>$access_token, 'timeout'=>6000));
					mysql_query("DELETE FROM `auth` WHERE (`token2`='".$access_token."');", $con);
                } else {
                    $response = array('meta'=>array('code'=>'423', 'msg'=>'Fail token'), 'data'=>array());
                }
                echo json_encode($response);
            } else {
                echo 'Err 4';
                echo ' '.mysql_error();
            }
        }
    } else { // Если несуществующий девайс        
    }   
} else { // Если авторизован
}

?>