<html>
<head>
<!--metadata-->
<title>Searchresults | MEDDATA</title>
<link rel="icon" 
      type="image/ico" 
      href="http://meddata.clients.soton.ac.uk/favicon.ico">

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

	$searchphrase = $_POST["sphrase"];
	$searchphrase = htmlspecialchars($searchphrase,ENT_QUOTES);

/* Query SQL Server for the data */
  
$tsql = "SELECT TOP 20 [ID]
      ,[Name]
      ,[Date]
      ,[Description]
      ,[DefaultBasePath]
  FROM [MEDDATADB].[dbo].[Experiments]
  WHERE [ExperimentTypeID] = 0
  AND ([Name] LIKE '%'+?+'%'
  OR [Description] LIKE '%'+?+'%')";
  
$tsql = "SELECT TOP 20 [MEDDATADB].[dbo].[Experiments].[ID]
      ,[MEDDATADB].[dbo].[Experiments].[Name]
      ,[MEDDATADB].[dbo].[Experiments].[Description]
  FROM [MEDDATADB].[dbo].[Experiments] 
  INNER JOIN [MEDDATADB].[dbo].[ExperimentParameters] 
  ON [MEDDATADB].[dbo].[Experiments].[ID] = [MEDDATADB].[dbo].[ExperimentParameters].[ExperimentID]
  WHERE [MEDDATADB].[dbo].[Experiments].[ExperimentTypeID] = 0
  AND ([MEDDATADB].[dbo].[Experiments].[Name] LIKE '%'+?+'%'
  OR [MEDDATADB].[dbo].[Experiments].[Description] LIKE '%'+?+'%'
  OR [MEDDATADB].[dbo].[ExperimentParameters].[Name] LIKE '%'+?+'%'
  OR [MEDDATADB].[dbo].[ExperimentParameters].[Value] LIKE '%'+?+'%')
  GROUP BY [MEDDATADB].[dbo].[Experiments].[ID], [MEDDATADB].[dbo].[Experiments].[Name], [MEDDATADB].[dbo].[Experiments].[Description]
  ";
$options = array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
$stmt = sqlsrv_query( $conn, $tsql, array(&$searchphrase,&$searchphrase,&$searchphrase,&$searchphrase),$options);  
if( $stmt === false )  
{  
     $ErrorMsg = $ErrorMsg."Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}
$resultCount = sqlsrv_num_rows($stmt);
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
	<h1>MEDDATA 2</h1>
	<form action="search.php" accept-charset="utf-8" method="post" class="menu">
		<!--<div class="menu">-->
			<a href="index.php"><i class="fa fa-home"></i> Home</a>
			<a href="tags.php"><i class="fa fa-tags"></i> Tags</a>
			<a href="info.php"><i class="fa fa-info"></i> Info</a>
			<input name="utf8" type="hidden" value="&#x2713;" />
			<button type="submit" class="btn btn-search search">
				<i class="fa fa-search"></i>
			</button>
			<input type="text" name="sphrase" class="search" value="<?php echo $searchphrase ?>" placeholder="Search.."/>
		<!--</div>-->
	</form>
</div>

<div id="content">
<div>
First <?php echo $resultCount; ?> datasets containing '<i><?php echo $searchphrase; ?></i>'<br/>
<ul class="fa-ul li-def">
<?php
/* Retrieve and display the results of the query. */
while($row = sqlsrv_fetch_array($stmt)) {
    echo "<li><i class=\"fa-li fa fa-circle-o\"></i><a href=\"view.php?imgID=".$row['ID']."\" >".$row['Name']."</a></br><i>".$row['Description']."</i></li>";
}
?>
</ul>
</div>

</div>
<?php
/* Free statement and connection resources. */  
sqlsrv_free_stmt( $stmt);  
sqlsrv_close( $conn);  
?>



 
</body>
</html>