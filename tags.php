<!DOCTYPE html>
<html lang="en">
<?php
/*<!--php-->*/
include "_LayoutDatabase.php";
include "_SecurityCheck.php";
/* Query SQL Server for the data */   
$tsql = "SELECT [Name], [Value], COUNT(*) AS Count
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] > 2
  GROUP BY [Name], [Value]
  ORDER BY [Value] ASC";
$stmt = sqlsrv_query( $conn, $tsql);  
$stmt2 = sqlsrv_query( $conn, $tsql);  
if( $stmt === false )  
{  
     $ErrorMsg = $ErrorMsg."Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}

/* Get largest and smallest count. */
$min = 0;
$max = 0;
while($row = sqlsrv_fetch_array($stmt2)) {
	if ($min == 0){
		$min = $row['Count'];
	}
	if ($row['Count'] < $min){
		$min = $row['Count'];
	}
	if ($row['Count'] > $max){
		$max = $row['Count'];
	}
}
?>

<head>
	<!--metadata-->
	<?php $PageTitle = "Tags | MEDDATA"; ?>
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
All tags<br/>
<p class="tags">


<?php
/* Retrieve and display the results of the query. */
while($row = sqlsrv_fetch_array($stmt)) {
	echo "<text style=\"font-size:".(60+80*($row['Count']-$min)/($max-$min))."%\"><a href=\"viewtag.php?Name=".$row['Name']."&Value=".$row['Value']."\" >".$row['Value']."</a></text>, ";
}
?>
</p>
</div>

</div>

<!--footer-->
<?php 
sqlsrv_free_stmt( $stmt);
sqlsrv_free_stmt( $stmt2);  
include "_LayoutFooter.php"; 
?> 

</body>
</html>