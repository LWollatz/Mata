<html>
<head>
<title>MEDDATA</title>

<!--style-->
<link rel="stylesheet" href="http://fontawesome.io/assets/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="styles/main.css" type="text/css">

<!--php-->
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

<!--javascript-->
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/js/messages.js" type="text/javascript"></script>

</head>
<body>

<div style="top:0px" class="error" id="error">
<?php echo $ErrorMsg; ?>
</div>
<div style="top:0px" class="info" id="info">
<?php echo $InfoMsg; ?>
</div> 


<div id="header">
	<h1>Tags</h1>
	<form action="search.php" accept-charset="utf-8" method="post" class="menu">
		<!--<div class="menu">-->
			<a href="index.php"><i class="fa fa-home"></i> Home</a>
			<a href="tags.php"><i class="fa fa-tags"></i> Tags</a>
			<a href="info.php"><i class="fa fa-info"></i> Info</a>
			<input name="utf8" type="hidden" value="&#x2713;" />
			<button type="submit" class="btn btn-search search">
				<i class="fa fa-search"></i>
			</button>
			<input type="text" name="sphrase" class="search" value="" placeholder="Search.."/>
		<!--</div>-->
	</form>
</div>

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
<?php
/* Free statement and connection resources. */  
sqlsrv_free_stmt( $stmt);  
sqlsrv_close( $conn);  
?>



 
</body>
</html>