<?

include_once("./core.php");

if($enter) {
    $file_id = $_GET['file_id'];
    $get_file = mysql_query("SELECT `name` FROM `files` WHERE `id`=$file_id", $con);
    if($get_file && mysql_num_rows($get_file) == 1) {
	$f = mysql_fetch_array($get_file);
	mysql_free_result($get_file);
	//$get_user = mysql_query("SELECT `uid` FROM `devices` WHERE `device_id`=$device_id", $con);
	//if($get_user && mysql_num_rows($get_user) == 1) {
	    $uid = /*$get_user['uid']*/1;
	    //mysql_free_result($get_user);
	    $file = './cloud/'.$uid.'/'.$f['name'];
	    if (file_exists($file)) {
    	        header('Content-Description: File Transfer');
    	        header('Content-Type: application/octet-stream');
    	        header('Content-Disposition: attachment; filename="'.basename($file).'"');
    	        header('Expires: 0');
    	        header('Cache-Control: must-revalidate');
    	        header('Pragma: public');
    	        header('Content-Length: ' . filesize($file));
    	        readfile($file);
	    } else {
		echo '{"meta":{"code":"400"},"data":{"error":"File not found."}}';
	    }
	//} else {
	    //echo '{"meta":{"code":"400"},"data":{"error":"User not found."}}';
	//} 
    } else {
	echo '{"meta":{"code":"401"},"data":{"error":"File not found."}}';
    }
} else {
    echo '{"meta":{"code":"400"},"data":{"error":"Invalid access_token."}}';    
}

?>