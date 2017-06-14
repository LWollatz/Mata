<!DOCTYPE html>
<html lang="en">
<?php
/*<!--php-->*/
include "_LayoutDatabase.php";
include "_SecurityCheck.php";
/* Query SQL Server for the data */   
$tsql = "SELECT TOP 1000 [ID]
      ,[Name]
      ,[Date]
      ,[Description]
      ,[DefaultBasePath]
  FROM [MEDDATADB].[dbo].[Experiments]
  WHERE [ExperimentTypeID] = 0
  AND [IsDeleted] = 0";
$stmt = sqlsrv_query( $conn, $tsql);  
if( $stmt === false )  
{  
     $ErrorMsg = $ErrorMsg."Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}
?>
<head>
	<!--metadata-->
	<?php $PageTitle = "MEDDATA"; ?>
	<?php include "_LayoutMetadata.php"; ?> 
	<!--style-->
	<?php include "_LayoutStyles.php"; ?> 
	<!--scripts-->
	<?php include "_LayoutJavascript.php"; ?> 
</head>

<body>
<?php 
$MenuEntries = "";
include "_LayoutHeader.php"; 
?> 
<div id="content">
	All datasets<br/>
	<?php 
	$experiments = $stmt;
	include "App_Data/ListExperiments.php";
	?>
</div>

<!--footer-->
<?php 
sqlsrv_free_stmt( $stmt);
include "_LayoutFooter.php"; 
?> 

</body>
</html>