<!DOCTYPE html>
<html lang="en">
<?php
/*<!--php-->*/
include "_LayoutDatabase.php";
include "_SecurityCheck.php";
/* Query SQL Server for the data */

//this page has 3 purposes:
//1) display all tag keys and link to list of all tag values under that key
//2) display all tag values under a tag key and link to list of experiments
//3) display all tag children under of a tag and link to a list of their children or if no children exist a list of experiments

$tsql = "SELECT [Name], [Value], COUNT(*) AS Count
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] > 2
  GROUP BY [Name], [Value]
  ORDER BY [Value] ASC";
$case = 1;
if (isset($_GET['Name'])){
	//CASE 2
	$case = 2;
	$tagname = htmlspecialchars($_GET['Name'],ENT_QUOTES);
	$tsql = "SELECT [Value] AS 'Text', COUNT(*) AS Count
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] > 2
  AND [Name] = ?
  GROUP BY [Value]
  ORDER BY [Value] ASC";
    $arguments = array(&$tagname);
}else if(isset($_GET['ID'])){
	//CASE 3
	$case = 3;
	$tagid = (int)$_GET['ID'];
	$tsql = "SELECT [Name] AS 'Text', [Value], COUNT(*) AS Count
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE CONCAT([Name],';',[Value]) IN(
    SELECT CONCAT([ExperimentParameters].[Name],';',[ExperimentParameters].[Value])
    FROM [MEDDATADB].[dbo].[ExperimentParameters]
    FULL JOIN [MEDDATADB].[dbo].[ExperimentParameterLinks] AS [LinkP]
    ON [LinkP].[LinkedParameterID] = [ExperimentParameters].[ID]
    WHERE [LinkP].[ParentParameterID] = ?
	)
  GROUP BY [Name], [Value]
  ORDER BY [Name] ASC";
	$arguments = array(&$tagid);
}else{
	//CASE 1
	$case = 1;
	$tsql = "SELECT [Name] AS 'Text', COUNT(*) AS Count
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] > 2
  GROUP BY [Name]
  ORDER BY [Name] ASC";
    $arguments = array();
}
   

$stmt = sqlsrv_query( $conn, $tsql, $arguments);  
$stmt2 = sqlsrv_query( $conn, $tsql, $arguments);  
if( $stmt === false )  
{  
     $ErrorMsg = $ErrorMsg."Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}

/* Get largest and smallest count. */
$min = 0;
$max = 0;
$total = 0;
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
	$total = $total + 1;
}
if($total <= 1){ $total = 2;}
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
<?php if($case == 1){ ?>
	All tag keys<br/>
<?php }else if($case == 2){ ?>
	All tags named <?php echo $tagname; ?><br/>
<?php }else{ ?>
	All tags under tag <?php echo $tagid; ?><br/>
<?php } ?>
<p class="tags">


<?php
/* Retrieve and display the results of the query. */
while($row = sqlsrv_fetch_array($stmt)) {
	//echo "<text style=\"font-size:".(60+80*($row['Count']-$min)/($max-$min))."%\"><a href=\"viewtag.php?Name=".$row['Name']."&Value=".$row['Value']."\" >".$row['Value']."</a></text>, ";
	$fontsize = 140-80*(($max/$row['Count'])-1)/($total-1);
	if($fontsize > 0){
		echo "<text style=\"font-size:".$fontsize."%\">";
		if($case == 1){
			echo "<a href=\"tags.php?Name=".$row['Text']."\" >";
			echo $row['Text'];
		}else if($case == 2){
			if($row['Text'] == ""){
				echo "<a href=\"viewtag.php?Name=".$tagname."&Value=".$row['Text']."\" >";
				echo $tagname;
			}else{
				echo "<a href=\"viewtag.php?Name=".$tagname."&Value=".$row['Text']."\" >";
				echo $row['Text'];
			}
		}else{
			//need to figure out what to do here depending on number of children
			echo "<a onMouseOver=\"this.innerHTML='".$row['Text'].": ".$row['Value']."';\" onMouseOut=\"this.innerHTML='".$row['Text']."';\" href=\"viewtag.php?Name=".$row['Text']."&Value=".$row['Value']."\" >";
			echo $row['Text'];
		}
		
		echo "</a>";
		echo "</text>, ";
	}
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