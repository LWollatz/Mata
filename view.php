<!DOCTYPE html>
<html style="height: 100%;">
<?php
/*<!--php-->*/
include "_LayoutDatabase.php";
/* get variables */
$imageID = (int)$_GET['imgID'];

include "_SecurityCheck.php";
if($authstage == "None"){
	$ErrorMsg = $ErrorMsg."You must be logged in to view datasets";
	header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
	header("Location: /error.php?errcode=401");
}
/*if($authstage == "Basic"){
	$ErrorMsg = $ErrorMsg."You do not have permission to view this dataset";
	header("HTTP/1.1 403 Forbidden");
}*/



/* Query SQL Server for the login of the user accessing the  
database. */   
$isql = "SELECT TOP 1 *
  FROM [MEDDATADB].[dbo].[Experiments]
  WHERE [ID] = ?";
//$isql = str_replace("@1", $imageID, $isql);

$osql = "SELECT TOP 1 [MEDDATADB].[dbo].[Users].[ID]
      ,[MEDDATADB].[dbo].[Users].[Username]
      ,[MEDDATADB].[dbo].[Users].[UserID]
      ,[MEDDATADB].[dbo].[Users].[Name]
      ,[MEDDATADB].[dbo].[Users].[Email]
  FROM [MEDDATADB].[dbo].[Users] 
  INNER JOIN [MEDDATADB].[dbo].[Experiments] 
  ON [MEDDATADB].[dbo].[Users].[UserID] = [MEDDATADB].[dbo].[Experiments].[FileSystemUserID]
  WHERE [MEDDATADB].[dbo].[Experiments].[ID] = ?";

$tsql = "SELECT *
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] = @1";
$tsql = str_replace("@1", $imageID, $tsql);

$fsql = "SELECT *
  FROM [MEDDATADB].[dbo].[ExperimentDataFiles]
  WHERE [ExperimentID] = ? 
  AND [IsDeleted] = 0 
  AND NOT [Filename] LIKE '%.tif'
  ORDER BY [Filename]";
  
$epsql = "SELECT [ParentExperimentID] AS [ExperimentID]
      ,[LinkedExperimentID]
      ,[Name]
  FROM [MEDDATADB].[dbo].[ExperimentLinks]
  LEFT JOIN [MEDDATADB].[dbo].[Experiments] 
  ON [MEDDATADB].[dbo].[ExperimentLinks].[ParentExperimentID] = [MEDDATADB].[dbo].[Experiments].[ID]
  WHERE [LinkedExperimentID] = ?";
  
$ecsql = "SELECT [ParentExperimentID] 
      ,[LinkedExperimentID] AS [ExperimentID]
      ,[Name]
  FROM [MEDDATADB].[dbo].[ExperimentLinks]
  LEFT JOIN [MEDDATADB].[dbo].[Experiments] 
  ON [MEDDATADB].[dbo].[ExperimentLinks].[LinkedExperimentID] = [MEDDATADB].[dbo].[Experiments].[ID]
  WHERE [ParentExperimentID] = ?";



$srinfo = sqlsrv_query( $conn, $isql, array(&$imageID));
$srtags = sqlsrv_query( $conn, $tsql); 
$srfiles = sqlsrv_query( $conn, $fsql, array(&$imageID));
$srowner = sqlsrv_query( $conn, $osql, array(&$imageID));
$srparents = sqlsrv_query( $conn, $epsql, array(&$imageID));
$srchilds = sqlsrv_query( $conn, $ecsql, array(&$imageID));
if( $srtags === false )  
{  
     echo "Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}
/* Retrieve the results of the query. */
$row = sqlsrv_fetch_array($srinfo);
$owner = sqlsrv_fetch_array($srowner);

/*Check if normal Experiment (not config data)*/
if($row["ExperimentTypeID"] != 0){
	$errmsg = $errmsg."Invalid ID ";
	header('Location: http://meddata.clients.soton.ac.uk/error.php?msg='.$infomsg.'&err='.$errmsg);
}



/*get relative path for files*/
$relpath = str_replace("c:\\", "../", $row['DefaultBasePath']);
$relpath = str_replace("\\", "/", $relpath);

$hasPreview = false;
$hasSTL = false;
if(file_exists($relpath."/.previews/infoJSON.txt")){
	$hasPreview = true;
}
if(file_exists($relpath."/.previews/infoSTL.txt")){
	$hasSTL = true;
}

?>
<head>
	<!--metadata-->
	<?php $PageTitle = $row['Name']." | MEDDATA"; ?>
	<?php $PageKeywords = ", multi-resolution, tile-based 3D CT image viewer"; ?>
	<?php include "_LayoutMetadata.php"; ?> 
	<!--style-->
	<?php include "_LayoutStyles.php"; ?> 
	<!--scripts-->
	<?php include "_LayoutJavascript.php"; ?>
	<script src="/js/folding.js" type="text/javascript"></script>
</head>

<body>

<?php 
if($authstage == "Owner" || $authstage == "Writer" ){
	$MenuEntries = '<a href="edit.php?imgID='.$imageID.'"><i class="fa fa-edit"></i> Edit</a>';
}
include "_LayoutHeader.php"; 
?> 

<div id="content">

	<?php if( ($hasPreview || $hasSTL) && $authstage != "Basic" ){ ?>
	<div class="metadata">
	<?php }else{ ?>
	<div class="metadata fw">
	<?php } ?>
	<?php
	/* Display the results of the query. */
	echo "<b>Name:</b> ".$row['Name']."<br/>";
	echo "<b>Date:</b> ".$row['Date']->format("d/m/Y H:i:s")."<br/>";
	echo "<b>Description:</b> <i>".$row['Description']."</i><br/>";
	echo "<b><i class=\"fa fa-user\"></i> Owner:</b> ".$owner['Name']."<br/>"; //." - you are ".$authstage."<br/>";
	?>
	<i class="fa fa-tags"></i> <b>Tags:</b> 
	<ul class="fa-ul" style="margin-top:0px;">
		<?php
		/* Retrieve and display the results of the query. */
		while($tag = sqlsrv_fetch_array($srtags)) {
			echo "<li><i class=\"fa-li fa fa-tag\"></i>".$tag['Name'].": <i><a href=\"viewtag.php?Name=".$tag['Name']."&Value=".$tag['Value']."\" >".$tag['Value']."</a></i></li>";
		}
		?>
	</ul>

	<?php 
if( !$hasPreview && !$hasSTL || $authstage == "Basic"){ 
	?>
	</div>

	<div class="files">
	<?php 
} 
	?>
<?php 
	if($authstage == "Viewer" || $authstage == "Writer" || $authstage == "Owner"){ 
?>
		<i class=" fa fa-files-o"></i> <b>Files:</b>
		<?php include "App_Data/ListFiles.php"; ?>
		<br/>
<?php 
	} 
?>
		<i class=" fa fa-link"></i> <b>Related Datasets:</b><br/>
		<ul class="fa-ul" style="margin-top:0px;">
			<li><i><a href="../netgraph.php?imgID=<?php echo $imageID;?>">(view network)</a></i></li>
			<?php while($item = sqlsrv_fetch_array($srparents)) {
				echo "<li><i class=\"fa-li fa fa-male\"></i> <a href=\"view.php?imgID=".$item['ExperimentID']."\" >".$item['Name']."</a></li>";
			}?>
			<?php while($item = sqlsrv_fetch_array($srchilds)) {
				echo "<li><i class=\"fa-li fa fa-child\"></i> <a href=\"view.php?imgID=".$item['ExperimentID']."\" >".$item['Name']."</a></li>";
			}?>
		</ul>
	</div>

<?php 
	if($hasPreview && $authstage != "Basic"){ 
?>
	<div class="datacontent">
		<iframe src="mctv/mctv.htm?root=<?php echo $relpath; ?>/.previews/">
		</iframe>
	</div>
<?php 
	} 
?>

<?php 
	if($hasSTL && $authstage != "Basic"){ 
		$abspath = str_replace("../", "https://meddata.clients.soton.ac.uk/", $relpath);
		$string = file_get_contents($relpath."/.previews/infoSTL.txt");
		$stlfiles = explode("\n",$string);
?>
		<div class="datacontent">
			<!--<iframe id="vs_iframe" src="http://www.viewstl.com/?embedded&url=<?php echo $abspath; ?>/<?php echo $stlfiles[0]; ?>&local&color=white&bgcolor=black&shading=flat&rotation=no&orientation=bottom&noborder=yes">
			</iframe>-->
			<iframe id="vs_iframe" src="viewstl/viewstl.htm?embedded&url=<?php echo $abspath; ?>/<?php echo $stlfiles[0]; ?>&local&color=white&bgcolor=black&shading=flat&rotation=no&orientation=bottom&noborder=yes">
			</iframe>
		</div>
<?php 
	} 
?>

</div>
<?php
/* Free statement and connection resources. */  
sqlsrv_free_stmt( $srinfo);  
sqlsrv_free_stmt( $srtags);
sqlsrv_free_stmt( $srfiles);
sqlsrv_free_stmt( $srowner);
include "_LayoutFooter.php"; 
?>
</body>
</html>