<?php

if(isset($_POST['email']) && isset($_POST['password']) && isset($_POST['api_key'])){
    
    $set=setcookie('FT_SHELL', json_encode($_POST), time()+(3600*24*365*2), $_SERVER['PHP_SELF']);
    echo 1;
}
else if(isset($_GET['get'])){
    
    if(isset($_COOKIE['FT_SHELL'])){
	$data=$_COOKIE['FT_SHELL'];
	echo $data;
    }
    else echo -1;
}
else if(isset($_POST['remove_cookie'])){
    
    setcookie('FT_SHELL', '', time()-3600, $_SERVER['PHP_SELF']);
}