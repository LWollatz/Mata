<html>
<?php
header('Content-Type:text/html; charset=UTF-8');
$serverName = "MEDDATA"; //serverName\instanceName
$connectionInfo = array( "Database"=>"MEDDATADB" );
/* Connect using Windows Authentication. */  
$conn = sqlsrv_connect( $serverName, $connectionInfo);  
if( $conn === false )  
{  
     echo "Unable to connect.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}
/* get variables */
$imageID = (int)$_GET['imgID'];

/* Query SQL Server for the data */   
$isql = "SELECT TOP 1 *
  FROM [MEDDATADB].[dbo].[Experiments]
  WHERE [ID] = ?";
/*$isql = str_replace("@1", $imageID, $isql);*/

$tsql = "SELECT *
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] = ?";
/*$tsql = str_replace("@1", $imageID, $tsql);*/

$tnsql = "SELECT [Name], COUNT(*) AS Count
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] > 2 AND NOT [Name] = ANY (
    SELECT [Name]
    FROM [MEDDATADB].[dbo].[ExperimentParameters]
    WHERE [ExperimentID] = ?)
  GROUP BY [Name]
  ORDER BY Count DESC, [Name] ASC";

$srinfo = sqlsrv_query( $conn, $isql, array(&$imageID));
$srtags = sqlsrv_query( $conn, $tsql, array(&$imageID)); /*there must be a more efficient way, but how do I duplicate the result of a query?*/
$srtagsPre = sqlsrv_query( $conn, $tsql, array(&$imageID));
$tagkeys = sqlsrv_query( $conn, $tnsql, array(&$imageID));  
if( $srtags === false )  
{  
     echo "Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}
/* Retrieve the results of the query. */
$row = sqlsrv_fetch_array($srinfo);

/*get relative path for files*/
$relpath = str_replace("c:\\", "../", $row['DefaultBasePath']);
$relpath = str_replace("\\", "/", $relpath);
?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<meta charset="utf-8">
<title>Edit <?php echo $row['Name']; ?> | MEDDATA</title>

<!--style-->
<link rel="stylesheet" href="http://fontawesome.io/assets/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="styles/main.css" type="text/css">

<!--javascript-->
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
$( function() {
	var availableTags = [
<?php 
$srtagsCount = sqlsrv_num_rows($srtagsPre);
while($key = sqlsrv_fetch_array($tagkeys)) {
    echo '"'.$key['Name'].'",';
}
while($key = sqlsrv_fetch_array($srtagsPre)) {
    echo '"'.$key['Name'].'",';
} ?>
	  ""];
	$( "#tags" ).autocomplete({
		source: availableTags
	});
} );
</script>
</head>

<body>
<div id="header">
	<h1>Edit <?php echo $row['Name']; ?></h1>
	<form action="search.php" accept-charset="utf-8" method="post" class="menu">
		<!--<div class="menu">-->
			<a href="index.php"><i class="fa fa-home"></i> Home</a>
			<a href="tags.php"><i class="fa fa-tags"></i> Tags</a>
			<a href="info.php"><i class="fa fa-info"></i> Info</a>
			<a href="view.php?imgID=<?php echo $imageID?>"><i class="fa fa-close"></i> Cancel</a>
			<input name="utf8" type="hidden" value="&#x2713;" />
			<button type="submit" class="btn btn-search search">
				<i class="fa fa-search"></i>
			</button>
			<input type="text" name="sphrase" class="search" value="" placeholder="Search.."/>
		<!--</div>-->
	</form>
</div>

<div id="content">
<form action="update.php" accept-charset="utf-8" method="post">
<table style="width:100%;">
<tr>
<td style="word-wrap:break-word;">

<table>
<tr><td class="theader"><input type="hidden" name="ID" value="<?php echo $imageID;?>">Name:</td></tr>
<tr><td><?php echo $row['Name'];?></td></tr>
<tr><td class="theader"> Date: </td></tr>
<tr><td><?php echo $row['Date']->format("d/m/Y H:i:s");?></td></tr>
<tr><td class="theader">Description:</td></tr>
<tr><td>
	<?php $description = $row['Description'];?>
	<div class="inpdesc"><i id="leftchars"><?php echo (300-strlen($description)); ?></i> characters remaining</div>
	<textarea name="ud_description" onkeyup="textCounter(this);" cols="40" rows="10"><?php echo $description;?></textarea>
</td></tr>

</table>



</td>
<td>
<table>
<tr><td class="theader">Tags:</td><td></td></tr>
<tr><td colspan=2>(to delete a tag, leave the value empty)</td></tr>
<?php
/* Retrieve and display the results of the query. */
//$tag = sqlsrv_fetch($srtags, SQLSRV_SCROLL_FIRST);
while($tag = sqlsrv_fetch_array($srtags)) {?>
    <tr>
        <td><?php echo $tag['Name']; ?>:</td>
        <td><i><input type="text" name="ud_value<?php echo $tag['ID']; ?>" value="<?php echo $tag['Value'];?>"></i></td>
    </tr>
<?php }
?>
<tr>
    <td><text class="ui-widget">
	  <input id="tags" name="ud_newkey">
	</text>:</td>
	<td><input type="text" name="ud_newvalue" value=""></td>
</tr>

<tr><td class="theader">Previewer Values:</td><td><input type="hidden" name="relpath" value="<?php echo $relpath.'/.previews/infoJSON.txt';?>"></td></tr>
<?php
$string = file_get_contents($relpath."/.previews/infoJSON.txt");
$json_a = json_decode($string, true);?>
<tr><td>width:</td><td><input type="text" name="ud_prvWidth" value="<?php echo $json_a['width'];?>"></td></tr>
<tr><td>height:</td><td><input type="text" name="ud_prvHeight" value="<?php echo $json_a['height'];?>"></td></tr>
<tr><td>res:</td><td><input type="text" name="ud_prvRes" value="<?php echo $json_a['res'];?>"></td></tr>
<tr><td>zres:</td><td><input type="text" name="ud_prvZres" value="<?php echo $json_a['zres'];?>"></td></tr>
<tr><td>resunits:</td><td><input type="text" name="ud_prvResunit" value="<?php echo html_entity_decode($json_a['resunits'], ENT_COMPAT | ENT_HTML5, "UTF-8");?>"></td></tr> <!--html_entity_decode(-->
<tr><td>densmin:</td><td><input type="text" name="ud_prvDensmin" value="<?php echo $json_a['densmin'];?>"></td></tr>
<tr><td>densmax:</td><td><input type="text" name="ud_prvDensmax" value="<?php echo $json_a['densmax'];?>"></td></tr>
</table>

</td>
</tr></table>
<input name="utf8" type="hidden" value="&#x2713;" />
<button type="submit" class="btn btn-submit">
	<i class="fa fa-floppy-o"></i> Save changes
</button>
</form>
</div>

<?php
/* Free statement and connection resources. */  
sqlsrv_free_stmt( $stmt);  
sqlsrv_close( $conn);  
?>




<script>
function textCounter(txtarea)
{
 var counter = document.getElementById('leftchars');
 if ( txtarea.value.length > 300 ) {
  txtarea.value = txtarea.value.substring( 0, 300 );
  return false;
 } else {
  counter.innerHTML = 300 - txtarea.value.length;
 }
}
</script>

 
</body>
</html>