<!DOCTYPE html>
<html style="height: 100%;">
<?php
/*<!--php-->*/
include "_LayoutDatabase.php";
/* get variables */
$tagname = $_GET['Name'];
$tagvalue = $_GET['Value'];
//$tagname = htmlspecialchars($tagname,ENT_QUOTES);
//$tagvalue = htmlspecialchars($tagvalue,ENT_QUOTES);
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

include "_SecurityCheck.php";
/* Query SQL Server for the data */
  
$tsql = "SELECT DISTINCT TOP 20 [MEDDATADB].[dbo].[Experiments].[ID]
      ,[MEDDATADB].[dbo].[Experiments].[Name]
	  ,[MEDDATADB].[dbo].[Experiments].[Date]
      ,[MEDDATADB].[dbo].[Experiments].[Description]
  FROM [MEDDATADB].[dbo].[Experiments] 
  INNER JOIN [MEDDATADB].[dbo].[ExperimentParameters] 
  ON [MEDDATADB].[dbo].[Experiments].[ID] = [MEDDATADB].[dbo].[ExperimentParameters].[ExperimentID]
  WHERE [MEDDATADB].[dbo].[ExperimentParameters].[Name] = ?
  AND [MEDDATADB].[dbo].[ExperimentParameters].[Value] = ?
  AND [MEDDATADB].[dbo].[Experiments].[ExperimentTypeID] = 0
  ORDER BY [Experiments].[".$sortType."]".$sortOrder;
  
$stmt = sqlsrv_query( $conn, $tsql, array(&$tagname,&$tagvalue));  
if( $stmt === false )  
{  
     $ErrorMsg = $ErrorMsg."Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}
$tagname = htmlspecialchars($tagname,ENT_QUOTES);
$tagvalue = htmlspecialchars($tagvalue,ENT_QUOTES);
?>

<head>
	<!--metadata-->
	<?php $PageTitle = "Tag ".$tagvalue." | MEDDATA"; ?>
	<?php include "_LayoutMetadata.php"; ?> 
	<!--style-->
	<?php include "_LayoutStyles.php"; ?> 
	<!--scripts-->
	<?php include "_LayoutJavascript.php"; ?> 
</head>

<body>

<?php include "_LayoutHeader.php"; ?>

<div id="content">
<div>
First 20 datasets where '<i><?php echo $tagname; ?></i>' is '<i><?php echo $tagvalue; ?></i>'<br/>
<?php 
	$experiments = $stmt;
	include "App_Data/ListExperiments.php";
?>
</div>

</div>
<?php
/* Free statement and connection resources. */  
sqlsrv_free_stmt( $stmt);  
include "_LayoutFooter.php";  
?>



 
</body>
</html>