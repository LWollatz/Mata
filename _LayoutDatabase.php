<?php
header('Content-Type:text/html; charset=UTF-8');
$ErrorMsg = "";
$InfoMsg = "";
$serverName = "MEDDATA"; //serverName\instanceName
$connectionInfo = array( "Database"=>"MEDDATADB" );
/* Connect using Windows Authentication. */  
$conn = sqlsrv_connect( $serverName, $connectionInfo);  
if( $conn === false )  
{  
     $ErrorMsg = $ErrorMsg."Unable to connect.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}else{
     $InfoMsg = $InfoMsg."Connection successful.<br />";
}

if (isset($_GET['msg'])){
	$InfoMsg = $InfoMsg.$_GET['msg'];
}
if (isset($_GET['err'])){
	$ErrorMsg = $ErrorMsg.$_GET['err'];
}
?>