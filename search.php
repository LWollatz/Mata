<!DOCTYPE html>
<html lang="en">
<?php
/*<!--php-->*/
include "_LayoutDatabase.php";
include "_SecurityCheck.php";

if (isset($_POST["sphrase"])){
	$searchphrase = $_POST["sphrase"];
	$searchphrase = htmlspecialchars($searchphrase,ENT_QUOTES);
}



/* Query SQL Server for the data 
**TODO: need to fix join operation to work with Experiments without parameters...** 
*/
  
$tsql = "SELECT TOP 20 [MEDDATADB].[dbo].[Experiments].[ID]
      ,[MEDDATADB].[dbo].[Experiments].[Name]
	  ,[MEDDATADB].[dbo].[Experiments].[Date]
      ,[MEDDATADB].[dbo].[Experiments].[Description]
  FROM [MEDDATADB].[dbo].[Experiments] 
  FULL OUTER JOIN [MEDDATADB].[dbo].[ExperimentParameters] 
  ON [MEDDATADB].[dbo].[Experiments].[ID] = [MEDDATADB].[dbo].[ExperimentParameters].[ExperimentID]
  WHERE [MEDDATADB].[dbo].[Experiments].[ExperimentTypeID] = 0
  AND ([MEDDATADB].[dbo].[Experiments].[Name] LIKE '%'+?+'%'
  OR [MEDDATADB].[dbo].[Experiments].[Description] LIKE '%'+?+'%'
  OR [MEDDATADB].[dbo].[ExperimentParameters].[Name] LIKE '%'+?+'%'
  OR [MEDDATADB].[dbo].[ExperimentParameters].[Value] LIKE '%'+?+'%')
  GROUP BY [MEDDATADB].[dbo].[Experiments].[ID], [MEDDATADB].[dbo].[Experiments].[Name], [MEDDATADB].[dbo].[Experiments].[Description], [MEDDATADB].[dbo].[Experiments].[Date]
  ";

$tsql = "SELECT TOP 20 ID, Name, Date, Description, Count FROM( SELECT 
		[MEDDATADB].[dbo].[Experiments].[ID] AS ID
      ,[MEDDATADB].[dbo].[Experiments].[Name] AS Name
	  ,[MEDDATADB].[dbo].[Experiments].[Date] AS Date
      ,[MEDDATADB].[dbo].[Experiments].[Description] AS Description
	  ,ISNULL(SUM(CASE WHEN [ExperimentParameters].[Name] LIKE '%'+?+'%' THEN 1 ELSE 0 END),0)
	   + ISNULL(SUM(CASE WHEN [ExperimentParameters].[Value] LIKE '%'+?+'%' THEN 1 ELSE 0 END),0)
	   + IIF(COUNT(CASE WHEN [Experiments].[Name] LIKE '%'+?+'%' THEN 1 END)>0,1,0) 
	   + IIF(COUNT(CASE WHEN [Experiments].[Description] LIKE '%'+?+'%' THEN 1 END)>0,1,0) 
		AS Count
  FROM [MEDDATADB].[dbo].[Experiments] 
  FULL OUTER JOIN [MEDDATADB].[dbo].[ExperimentParameters] 
  ON [MEDDATADB].[dbo].[Experiments].[ID] = [MEDDATADB].[dbo].[ExperimentParameters].[ExperimentID]
  WHERE [MEDDATADB].[dbo].[Experiments].[ExperimentTypeID] = 0
  GROUP BY [MEDDATADB].[dbo].[Experiments].[ID], [MEDDATADB].[dbo].[Experiments].[Name], [MEDDATADB].[dbo].[Experiments].[Description], [MEDDATADB].[dbo].[Experiments].[Date]
  ) AS temp
  WHERE Count > 0
  ORDER BY Count DESC";  

$options = array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
$stmt = sqlsrv_query( $conn, $tsql, array(&$searchphrase,&$searchphrase,&$searchphrase,&$searchphrase),$options);  
if( $stmt === false )  
{  
     $ErrorMsg = $ErrorMsg."Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}
$resultCount = sqlsrv_num_rows($stmt);
?>
<head>
	<!--metadata-->
	<?php $PageTitle = "Searchresults | MEDDATA"; ?>
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
<div>
Top <?php echo $resultCount; ?> datasets containing '<i><?php echo $searchphrase; ?></i>'<br/>

<?php 
	$experiments = $stmt;
	include "App_Data/ListExperiments.php";
?>

</div>

</div>

<!--footer-->
<?php
/* Free statement and connection resources. */  
sqlsrv_free_stmt( $stmt);  
include "_LayoutFooter.php"; 
?>



 
</body>
</html>