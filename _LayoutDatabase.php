<?php
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
?>