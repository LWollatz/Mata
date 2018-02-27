<?php
header('Content-Type:text/html; charset=UTF-8');
ini_set('mssql.charset', 'utf8_general_ci');
/* initialize variables */
$ErrorMsg = "";
$InfoMsg = "";
$searchphrase = "";
/* read php configuration file */
include "config.php"; 
/* define urls for link creation */
$thisurl = (isset($_SERVER['HTTPS']) ? "https" : "http")."://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
$thisurlfull = (isset($_SERVER['HTTPS']) ? "https" : "http")."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
/* connect to database */
//$DBserver = "MEDDATASERVER\SQLEXPRESS"; //serverName\instanceName
$connectionInfo = array( "Database"=>$DBname );
$conn = sqlsrv_connect( $DBserver, $connectionInfo);  //Connect using Windows Authentication.
if( $conn === false )  //Check if connection was successful
{  
     $ErrorMsg = $ErrorMsg."Unable to connect.<br/>";  
     die( print_r( sqlsrv_errors(), true));  
}else{
     $InfoMsg = $InfoMsg."Connection successful.<br/>";
}
/* read info and error message from url */
if (isset($_GET['msg'])){
	$InfoMsg = $InfoMsg.$_GET['msg'];
}
if (isset($_GET['err'])){
	$ErrorMsg = $ErrorMsg.$_GET['err'];
}
?>