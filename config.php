<?php
/*configuration file for php*/
ini_set("display_errors", "on");
$FSdomain = "MEDDATA"; //file-store server domain
$FSpath = "$_SERVER[HTTP_HOST]\\data"; //file-store shared folder public path
$FSroot = "M:\\"; //file-store physical path name (as stored in DB by HDC)
$DBserver = "MEDDATASERVER\SQLEXPRESS"; //serverName\instanceName
$DBname = "MEDDATADB"; //database name
?>