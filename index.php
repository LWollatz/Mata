<!DOCTYPE html>
<html lang="en">
<?php
/*<!--php-->*/
include "_LayoutDatabase.php";
include "_SecurityCheck.php";
$sortType = "Date";
$sortOrder = "DESC";
if (isset($_GET['sort'])){
	$sortID = (int)$_GET['sort'];
	if ($sortID === 1){
		$sortType = "Name";
	}else if ($sortID === 2){
		$sortType = "Date";
	}else if ($sortID === 3){
		$sortType = "Description";
	}
}
if (isset($_GET['order'])){
	if ((int)$_GET['order'] === 0){
		$sortOrder = "ASC";
	}
}
/* Query SQL Server for the data */   
$tsql = "SELECT TOP 1000 [ID]
      ,[Name]
      ,[Date]
      ,[Description]
      ,[DefaultBasePath]
  FROM [MEDDATADB].[dbo].[Experiments]
  WHERE [ExperimentTypeID] = 0
  AND [IsDeleted] = 0
  ORDER BY [".$sortType."]".$sortOrder;
$stmt = sqlsrv_query( $conn, $tsql);  
if( $stmt === false )  
{  
     $ErrorMsg = $ErrorMsg."Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}
?>
<head>
	<!--metadata-->
	<?php $PageTitle = "MATA"; ?>
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