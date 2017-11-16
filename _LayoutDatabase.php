<?php
header('Content-Type:text/html; charset=UTF-8');
ini_set('mssql.charset', 'utf8_general_ci');
$ErrorMsg = "";
$InfoMsg = "";
include "config.php";
$searchphrase = "";
//$DBserver = "MEDDATASERVER\SQLEXPRESS"; //serverName\instanceName
$thisurl = (isset($_SERVER['HTTPS']) ? "https" : "http")."://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
$thisurlfull = (isset($_SERVER['HTTPS']) ? "https" : "http")."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$connectionInfo = array( "Database"=>$DBname );
/* Connect using Windows Authentication. */  
$conn = sqlsrv_connect( $DBserver, $connectionInfo);  
if( $conn === false )  
{  
     $ErrorMsg = $ErrorMsg."Unable to connect.<br/>";  
     die( print_r( sqlsrv_errors(), true));  
}else{
     $InfoMsg = $InfoMsg."Connection successful.<br/>";
}

if (isset($_GET['msg'])){
	$InfoMsg = $InfoMsg.$_GET['msg'];
}
if (isset($_GET['err'])){
	$ErrorMsg = $ErrorMsg.$_GET['err'];
}
?>