<!DOCTYPE html>
<html lang="en">
<head>
<!--php-->
<?php 
include "_LayoutDatabase.php";
/* Query SQL Server for the data */   
$tsql = "SELECT TOP 1000 [ID]
      ,[Name]
      ,[Date]
      ,[Description]
      ,[DefaultBasePath]
  FROM [MEDDATADB].[dbo].[Experiments]
  WHERE [ExperimentTypeID] = 0";
$stmt = sqlsrv_query( $conn, $tsql);  
if( $stmt === false )  
{  
     $ErrorMsg = $ErrorMsg."Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}
?>
<!--metadata-->
<?php $PageTitle = MEDDATA; ?>
<?php include "_LayoutMetadata.php"; ?> 
<!--style-->
<?php echo file_get_contents("_LayoutStyles.php"); ?> 
<!--scripts-->
<?php echo file_get_contents("_LayoutJavascript.php"); ?> 

</head>

<body>
<?php 
$MenuEntries = "";
include "_LayoutHeader.php"; 
?> 
<div id="content">
	All datasets<br/>
	<ul class="fa-ul li-def">
		<?php
		/* Retrieve and display the results of the query. */
		while($row = sqlsrv_fetch_array($stmt)) {
			$description = $row['Description'];
			if (strlen($description) > 40){
				$description = substr($description,0,37)."...";
			}
			echo "<li><i class=\"fa-li fa fa-circle-o\"></i><a href=\"view.php?imgID=".$row['ID']."\" >".$row['Name']."</a></br><i>".$description."</i></li>";
		}
		?>
	</ul>
</div>

<!--footer-->
<?php include "_LayoutFooter.php"; ?> 

</body>
</html>