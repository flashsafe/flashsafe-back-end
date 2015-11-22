<?php

include_once('./core.php');

if($enter){
    
    /*
    INSERT INTO `flashsaalxawyvq4`.`files` (`id`, `uid`, `device`, `parent`, `type`, `name`, `format`, `size`, `pincode`, `hidden`, `count`) 
    VALUES (NULL, '1', '0', '0', 'dir', 'Test folder', '', '0', '', '0', '0');
    */
    
    $dir_id = abs($_REQUEST['dir_id']);
    $pincode = stripcslashes(htmlspecialchars(addslashes($_REQUEST['pincode'])));
    $create_dir = substr(stripcslashes(htmlspecialchars(addslashes($_POST['create']))), 0, 50);
    if($dir_id > 0) {
        $query_id = "`type`='dir' AND `id`=".$dir_id;
    } else {
        $dir_id = 0;
        $query_id = "`parent`=0";
    }
    $del = abs($_POST['delete']);
    
    $response = array();
    $dir_access = false;
    
    if($dir_id > 0) {
        $rst = mysql_query("SELECT `id`, `device`, `type`, `name`, `format`, `size`, `pincode`, `count`, `create_time`, `update_time`
                            FROM `$DB_files` WHERE (`uid`=".$me_info['id']." AND $query_id);", $con);
        if($rst){
            if(mysql_num_rows($rst) == 0) {
                $response['meta'] = array('code'=>'404', 'msg'=>'dir_not_found');
            } else {
                $data_dir = mysql_fetch_array($rst);
                mysql_free_result($rst);
                $response['meta']['info'] = '';
                
                if($data_dir['pincode'] == '' || ($data_dir['pincode'] != '' && $data_dir['pincode'] == $pincode)) {
                    $dir_access = true;
                } else {
                    $response['meta']['code'] = '423';
                    $response['meta']['msg'] = 'get_true_pincode';
                }
                
            }
        } else {
            $response['meta'] = array('code' => 911, 'msg' => 'Crash query #1');
        }
    } else{ $dir_access = true; }
    
    if($dir_access === true) {
        if($create_dir != '') {
            /** Создание альбома **/
            mysql_query("INSERT INTO `$DB_files` (`id`, `uid`, `device`, `parent`, `type`, `name`, `format`, `size`, `pincode`, `hidden`, `count`, `create_time`, `update_time`) 
                        VALUES (NULL, '".$me_info['id']."', '0', '$dir_id', 'dir', '$create_dir', '', '0', '', '0', '0', '".time()."', '".time()."');", $con);
            $response['meta'] = array('code'=>200, 'msg'=>'create_dir', 'dir_id'=>mysql_insert_id());
        
        } else if($_FILES['file']) {
            /** Загрузка файла **/
            $file_id = abs($_POST['file_id']);
            $file_name = basename($_FILES['file']['name']);
            $file_type = stripcslashes(htmlspecialchars(addslashes($_FILES['file']['type'])));
            $file_size = abs($_FILES['file']['size']);
            if($file_id == 0) {
                mysql_query("INSERT INTO `$DB_files` (`id`, `uid`, `device`, `parent`, `type`, `name`, `format`, `size`, `pincode`, `hidden`, `count`, `create_time`, `update_time`) 
                            VALUES (NULL, ".$me_info['id'].", '0', $dir_id, 'file', '$file_name', '$file_type', $file_size, '', 0, 0, ".time().", ".time().")", $con);
                mysql_query("UPDATE `$DB_users` SET `count_files`=`count_files`+1, `used_size`=`used_size`+$file_size WHERE `id`=".$me_info['id'], $con);
				$add_id = mysql_insert_id();
                if($dir_id>0) mysql_query("UPDATE `$DB_files` SET `count`+=1, `size`+=$file_size WHERE (`id`=$dir_id AND `type`='dir') LIMIT 1;", $con);
                $response['meta'] = array('code'=>200, 'msg'=>'upload_file', 'file_id'=>$add_id);
                
                $uploaddir = "./cloud/".$me_info['id']."/";
                if (!is_dir("./cloud/".$me_info['id'])){
                    mkdir("./cloud/".$me_info['id'], 0770);
                }
                
                move_uploaded_file($_FILES['file']['tmp_name'], $uploaddir.$file_name);
            } else {
                $rst = mysql_query("SELECT `type`, `size`, `pincode` FROM `$DB_files` WHERE (`id`=$file_id AND `uid`=".$me_info['id']." AND `parent`=$dir_id) LIMIT 1;", $con);
                if($rst && mysql_num_rows($rst) == 1) {
                    $data_file = mysql_fetch_array($rst);
                    if($data_file == 'file') {
                        $resize_file = $file_size-$data_file['size'];
                        mysql_query("UPDATE `$DB_files` SET `update_time`='".time()."', `size`=$file_size WHERE (`id`=$file_id AND `uid`=".$me_info['id'].") LIMIT 1;
                                    UPDATE `$DB_users` SET `used_size` += '$resize_file' WHERE `id`=".$me_info['id'].";", $con);
                        if($dir_id>0) mysql_query("UPDATE `$DB_files` SET `size`+='$resize_file' WHERE (`id`=$dir_id AND `type`='dir') LIMIT 1;", $con);
                        $response['meta'] = array('code'=>200, 'msg'=>'reload_file', 'file_id'=>$file_id);
                        
                        $uploaddir = "./cloud/".$me_info['id']."/";
                        move_uploaded_file($_FILES['file']['tmp_name'], $uploaddir.$file_name);
                    }
                }
            }   
        } else if($del>0) {
            /** Удаляем содержимое **/
            
            $rst = mysql_query("SELECT `id`, `device`, `type`, `name`, `format`, `size`, `pincode`, `count`, `create_time`, `update_time`
                        FROM `$DB_files` WHERE (`uid`=".$me_info['id']." AND `id`=$del AND `parent`=$dir_id);", $con);
            if($rst){
                if(mysql_num_rows($rst) == 0) {
                    $response['meta'] = array('code'=>'404', 'msg'=>'obj_not_found');
                } else {
                    $data = mysql_fetch_array($rst);
                    mysql_free_result($rst);
                    
                    if($data['type']=='file')
                    {
                        mysql_query("DELETE FROM `$DB_files` WHERE (`uid`=".$me_info['id']." AND `id`=$del AND `parent`=$dir_id);", $con);
                        if(!mysql_error())
                        {
                            $response['meta'] = array('code'=>200, 'msg'=>'File "'.$data['name'].'" was delete.');
                        }else{
                            $response['meta'] = array('code'=>911, 'msg'=>'Crash query #5');
                        }
                        unlink("./cloud/".$me_info['id']."/".$data['name']);
                    }
                    elseif($data['type']=='dir')
                    {
                        
                        $rst = mysql_query("SELECT `id`, `device`, `type`, `name`, `format`, `size`, `pincode`, `count`, `create_time`, `update_time`
                                FROM `$DB_files` WHERE (`uid`=".$me_info['id']." AND $query_id);", $con);
                        if($rst) {
                            if(mysql_num_rows($rst)>0) {
                                $response['meta'] = array('code'=>100, 'msg'=>'There are any objs');
                            } else {
                                mysql_query("DELETE FROM `$DB_files` WHERE (`uid`=".$me_info['id']." AND `id`=$del AND `parent`=$dir_id);", $con);
                                if(!mysql_error())
                                {
                                    $response['meta'] = array('code'=>200, 'msg'=>'Folder "'.$data['name'].'" was delete.');
                                }else{
                                    $response['meta'] = array('code'=>911, 'msg'=>'Crash query #4');
                                }
                            }
                        } else {
                            $response['meta'] = array('code'=>911, 'msg'=>'Crash query #3');
                        }
                        
                    }
                    
                }
            }
            
            
        } else {
            /** Вывод списка файлов и папок **/
            $response['meta']['code'] = '200';
            $rst = mysql_query("SELECT `id`, `device`, `type`, `name`, `format`, `size`, `pincode`, `count`, `create_time`, `update_time`
                    FROM `$DB_files` WHERE (`uid`=".$me_info['id']." AND `parent`=$dir_id);", $con);
            if($rst) {
                if(mysql_num_rows($rst)>0) {
                    $response['meta']['msg'] = 'ok';
                    while($data_ff = mysql_fetch_array($rst)) {
                        $wr_pin = false;
                        if($data_ff['pincode'] != '') $wr_pin = true;
                        $response['data'][] = array('id'=>$data_ff['id'] ,'type'=>$data_ff['type'], 'name'=>$data_ff['name'], 'format'=>$data_ff['format'], 'size'=>$data_ff['size'], 'pincode'=>$wr_pin, 'count'=>$data_ff['count'], 'create_time'=>$data_ff['create_time'], 'update_time'=>$data_ff['update_time']);
                    }
                    mysql_free_result($rst);
                } else {
                    $response['meta']['msg'] = 'null';
                }
            } else {
                $response['meta'] = array('code'=>911, 'msg'=>'Crash query #2');
            }
        }
    }
    echo json_encode($response);
} else {
    $res = array('meta'=>array('code'=>'423', 'msg'=>'take_token'), 'data'=>array());
    echo json_encode($res);
}

mysql_close($con);

/* <br /><br />
<form method="post">
PinCode: <input name="pincode" type="text" />
<br /><br />
New_folder: <input name="create" type="text" />
<br /><br />
<input type="submit" />
</form> */

?>