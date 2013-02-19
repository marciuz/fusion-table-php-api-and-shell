<?php
error_reporting(E_ALL);
session_start();
require("./class.ft_v1.php");

function results_table($mat){
    
    if(!is_array($mat) || count($mat)==0){
	
	return '';
    }
    else{
	
	$TABLE="<table id=\"tab_results\" summary=\"query results\">\n";
	
	// TH
	$ths=array_keys($mat[0]);
	for($i=0;$i<count($mat);$i++){
	    
	    if($i==0){
		$TABLE.="<thead>\n";
		$TABLE.="<tr>\n";
		foreach($ths as $th) $TABLE.="<th>".$th."</th>\n";
		$TABLE.="</tr>\n";
		$TABLE.="</thead>\n";
		$TABLE.="<tbody>\n";
	    }
	    
	    $col= ($i%2==0) ? 'c0':'c1';
	    
	    $TABLE.="<tr class=\"$col\">\n";
	    foreach($mat[$i] as $td){
		
		if(is_object($td)){
		    if(isset($td->geometry))
			$TABLE.="<td><em>".$td->geometry->type."</em></td>\n";
		    else if(isset($td->type))
			$TABLE.="<td><em>".$td->type."</em></td>\n";
		    else
			$TABLE.="<td><em>Type unknow: ".substr(print_r($td,true),0,255)."</em></td>\n";
		}
		else{
		    
		    if($td=='') $td='&nbsp;';
		    $TABLE.="<td>".$td."</td>\n";
		}
	    }
	    $TABLE.="</tr>\n";
	}
	
	$TABLE.="</tbody>\n";
	$TABLE.="</table>\n";
	
	return $TABLE;
    }
}

$FT= new FTv1();

$Results=new stdClass();
$Results->summary='';
$Results->results='';
$Results->raw_code='';

if(!isset($_POST['google_email']) || !isset($_POST['google_password']) || !isset($_POST['google_api_key'])
 || empty($_POST['google_email']) || empty($_POST['google_password']) || empty($_POST['google_api_key'])){
    
    $Results->summary="ERROR: please insert a valid google account + api key\n";
    echo json_encode($Results);
    exit;
}

// Auth
$auth=$FT->auth($_POST['google_email'], $_POST['google_password']);

// Set key
$FT->key=$_POST['google_api_key'];

if(trim($_POST['sql'])==''){
    
    $Results->summary="ERROR: please send some SQL";
    echo json_encode($Results);
    exit;
}
else{
    $_SESSION['history'][md5($_POST['sql'])]=$_POST['sql'];
}

// exec the query
$q=$FT->query($_POST['sql']);




if(isset($FT->error)){
    
    $last_error=$FT->error[(count($FT->error)-1)];
    
    $Results->summary="<p class=\"error\"><strong>Error (".$last_error->code.")</strong> ".$last_error->message."</p>\n";
}

else if(preg_match('/ *SELECT +/i',$_POST['sql'])){
    
    //echo "<pre>Query output: ".print_r($q,true)."</pre>";
    
    $Results->summary.= "<p class=\"var1\">Num rows: ".$FT->num_rows()."</p>\n";
    
    $mat=$FT->fetch_assoc_all();
    
    $Results->results=results_table($mat);
    
}
else if(preg_match('/ *(DELETE|UPDATE|INSERT) +/si',$_POST['sql'])){
    
    $Results->summary.= "<p class=\"var1\">Affected rows: ".$FT->affected_rows()."</p>\n";
    
}
else if(preg_match('/ *SHOW +TABLES */si',$_POST['sql']) || preg_match("/ *DESCRIBE +([A-z0-9_-]+)/si", $_POST['sql'])){
    
    $Results->summary.= "<p class=\"var1\">Num rows: ".$FT->num_rows()."</p>\n";
    $mat=$FT->fetch_assoc_all();
    $Results->results=results_table($mat);
}
else if(preg_match('/ *CREATE +TABLE */si',$_POST['sql'])){
    if(isset($FT->raw_out->tableId)){
	$Results->summary.= "<p class=\"var1\">tableId: ".$FT->raw_out->tableId."</p>\n";
	$FT->raw_out=json_encode($FT->raw_out);
    }
}
else if(preg_match('/ *DROP +TABLE */si',$_POST['sql'])){
    if(isset($FT->raw_out->tableId)){
	$Results->summary.= "<p class=\"var1\">tableId: ".$FT->raw_out->tableId."</p>\n";
	$FT->raw_out=json_encode($FT->raw_out);
    }
}
else if(preg_match('/ *FIND +/i',$_POST['sql'], $ff)){
    
    if($FT->raw_out==-1){
	$Results->summary.= "<p class=\"var1\">Table(s) not found.</p>\n";
    }
    else if(is_string($FT->raw_out) && $FT->raw_out!=''){
	$Results->summary.= "<p class=\"var1\">Table encid: ".$FT->raw_out."</p>\n";
    }
    else if(is_array($FT->raw_out) && count($FT->raw_out)>0){
	$Results->summary.= "<p class=\"var1\">Table encid(s): ".implode(" , ",$FT->raw_out)."</p>\n";
    }
    else{
	$Results->summary.= "<p class=\"var1\">Table(s) not found.</p>\n";
    }
    
}
else{
    
}

$Results->summary.= "<p class=\"var1\">Execution time: ".$FT->get_exec_time()."</p>\n";
$Results->raw_code=$FT->raw_out;
echo json_encode($Results);
exit;