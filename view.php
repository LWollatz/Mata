<!DOCTYPE html>
<html style="height: 100%;">
<?php
/*<!--php-->*/
include "_LayoutDatabase.php";
$errmsg = "";
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

/* $tsql = "SELECT *
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] = ?";*/
  
$tsql = "SELECT
  [ExperimentParameters].[ID]
, [ExperimentParameters].[Name]
, [ExperimentParameters].[Value]
, [ExperimentParameters].[Position]
, [LinkC].[ParentParameterID]
, COUNT([LinkP].[LinkedParameterID]) AS 'LinkedParameterID'
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  FULL JOIN [MEDDATADB].[dbo].[ExperimentParameterLinks] AS [LinkC]
  ON [LinkC].[LinkedParameterID] = [ExperimentParameters].[ID]
  FULL JOIN [MEDDATADB].[dbo].[ExperimentParameterLinks] AS [LinkP]
  ON [LinkP].[ParentParameterID] = [ExperimentParameters].[ID]
  WHERE [ExperimentParameters].[ExperimentID] = ?
  GROUP BY [ExperimentParameters].[ID],
  [ExperimentParameters].[Name],
  [ExperimentParameters].[Value], 
  [ExperimentParameters].[Position], 
  [LinkC].[ParentParameterID]
  ORDER BY [ExperimentParameters].[Position]";
//$tsql = str_replace("@1", $imageID, $tsql);

$fsql = "SELECT *
  FROM [MEDDATADB].[dbo].[ExperimentDataFiles]
  WHERE [ExperimentID] = ? 
  AND [IsDeleted] = 0 
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
$srtags = sqlsrv_query( $conn, $tsql, array(&$imageID)); 
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
	header($_SERVER["SERVER_PROTOCOL"]." 404 Unavailable");
	header("Location: /error.php?errcode=404");;
}
$datasetDeleted = False;
if($row["IsDeleted"] != 0){
	$errmsg = $errmsg."Dataset Deleted ";
	$datasetDeleted = True;
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
if($authstage == "Owner" || $authstage == "Writer" || $authuser['Username'] == "Administrator"){
	$MenuEntries = '<a href="edit.php?imgID='.$imageID.'"><i class="fa fa-edit"></i> Edit</a>';
}
include "_LayoutHeader.php"; 
?> 

<div id="content">

	<?php if( ($hasPreview || $hasSTL) && $authstage != "Basic" && !$datasetDeleted ){ ?>
	<div class="metadata">
	<?php }else{ ?>
	<div class="metadata fw">
	<?php } ?>
	<div id="basicinfo" style="margin-left:25px;">
		<b>Name:</b> <?php echo $row['Name'];?><br/>
		<b>Date:</b> <?php echo $row['Date']->format("d/m/Y H:i:s");?><br/>
		<b>Description:</b> <i><?php echo $row['Description'];?></i><br/>
		<b><i class="fa fa-user-md"></i>   Owner:</b> <?php echo $owner['Name']; ?><br/>
		<?php //echo " - you are ".$authstage."<br/>"; ?>
	</div>
	<div id="tagtree">
		<ul class="fa-ul">
			<li data-jstree='{"opened":true, "type":"root"}'>
				<i class="fa-li fa fa-tags"></i> <b>Tags:</b>
				<ul class="fa-ul" style="margin-top:0px;">
					<?php
					$level = 1;
					$open = false;
					$levels = array();
					/* Retrieve and display the results of the query. */
					while($tag = sqlsrv_fetch_array($srtags)) {
						if(array_key_exists($tag['ParentParameterID'],$levels)){
							$levels[$tag['ID']] = $levels[$tag['ParentParameterID']] + 1;
						}else{
							$levels[$tag['ID']] = 1;
						}
						while ($level > $levels[$tag['ID']]){
							echo "</ul></li>";
							$level = $level - 1;
							$open = false;
						}
						if($open && $level == $levels[$tag['ID']]){
							echo "</li>";
							$open = false;
						}
						while ($level < $levels[$tag['ID']]){
							echo "<ul class=\"fa-ul\">";
							$level = $level + 1;
						} 
						
						if($tag['LinkedParameterID'] == 0){
							echo "<li onclick='window.location.href=\"viewtag.php?Name=".$tag['Name']."&Value=".$tag['Value']."\";'><i class=\"fa fa-tag fa-fw\"></i> <a href=\"viewtag.php?Name=".$tag['Name']."&Value=".$tag['Value']."\" >".$tag['Name'].": ".$tag['Value']."</a>";
						}else{
							echo "<li data-jstree='{\"type\":\"header\"}'><i class=\"fa fa-tags fa-fw\"></i> ".$tag['Name'].":".$tag['Value'];
						}
						$open = true;
						
						$level = $levels[$tag['ID']];
						//echo "<li>".$tag['Name']."<a href=\"viewtag.php?Name=".$tag['Name']."&Value=".$tag['Value']."\" >".": ".$tag['Value']."</a></li>";
					}
					echo "</li>";
					while ($level > 1){
						echo "</ul></li>";
						$level = $level - 1;
						$open = false;
					}
					if($open){
						echo "</li>";
						$open = false;
					}
					?>
				</ul>
			</li>
		</ul>
		
	</div>
	<script>
	$(function (){
		$('#tagtree').jstree({
			'core' : {
				'themes' : {
					'dots' : false,
				}
			},
			'force_text' : true,
			'conditionalselect' : function(node, event){
				return false;
			},
			'types' : {
				'default' : {
					"icon" : "fa fa-tag"
				},
				'root' : {
					"icon" : "fa fa-tags"
				},
				'header' : {
					"icon" : "fa fa-tags"
				}
			},
			'plugins' : [ 'types','conditionalselect' ]
		});
	})
	</script>

	<?php 
if( !$hasPreview && !$hasSTL || $authstage == "Basic" || $datasetDeleted){ 
	?>
	</div>

	<div class="files">
	<?php 
} 
	?>
<?php 
	if(!$datasetDeleted && ($authstage == "Basic" || $authstage == "Viewer" || $authstage == "Writer" || $authstage == "Owner")){ 
?>
		
		<?php include "App_Data/ListFiles.php"; ?>
<?php 
	} 
?>


	<div id="relatree">
		<ul class="fa-ul">
			<li data-jstree='{"opened":true, "type":"root"}'>
				<i class=" fa fa-link"></i> <b>Related Datasets:</b><br/>
				<ul class="fa-ul" style="margin-top:0px;">
					<li onclick='window.location.href = "../netgraph.php?imgID=<?php echo $imageID;?>";'>(view network)</a></li>
					<?php while($item = sqlsrv_fetch_array($srparents)) { ?>
					<li data-jstree='{"type":"parent"}' onclick='window.location.href = "view.php?imgID=<?php echo $item['ExperimentID'];?>";' >
					<?php echo "<i class=\"fa-li fa fa-male\"></i> <a href=\"view.php?imgID=".$item['ExperimentID']."\" >".$item['Name']."</a>"; ?>
					</li>
					<?php }?>
					<?php while($item = sqlsrv_fetch_array($srchilds)) { ?>
					<li data-jstree='{"type":"child"}' onclick='window.location.href = "view.php?imgID=<?php echo $item['ExperimentID'];?>";' >
					<?php echo "<i class=\"fa-li fa fa-child\"></i> <a href=\"view.php?imgID=".$item['ExperimentID']."\" >".$item['Name']."</a>"; ?>
					</li>
					<?php }?>
				</ul>
			</li>
		</ul>
	</div>
	<script>
	$(function (){
		$('#relatree').jstree({
			'core' : {
				'themes' : {
					'dots' : false,
				}
			},
			'force_text' : true,
			'conditionalselect' : function(node, event){
				return false;
			},
			'types' : {
				'default' : {
					"icon" : "fa fa-line-chart"
				},
				'root' : {
					"icon" : "fa fa-link"
				},
				'child' : {
					"icon" : "fa fa-child"
				},
				'parent' : {
					"icon" : "fa fa-male"
				}
			},
			'plugins' : [ 'types', 'conditionalselect' ]
		});
	})
	</script>
	<br/>
	</div>

<?php 
	if($hasPreview && $authstage != "Basic" && !$datasetDeleted){ 
?>
	<div class="datacontent">
		<iframe src="mctv/mctv.htm?root=<?php echo $relpath; ?>/.previews/">
		</iframe>
	</div>
<?php 
	} 
?>

<?php 
	if($hasSTL && $authstage != "Basic" && !$datasetDeleted){ 
		$abspath = str_replace("../", "https://meddata.clients.soton.ac.uk/", $relpath);
		$string = file_get_contents($relpath."/.previews/infoSTL.txt");
		$stlfiles = explode("\n",$string);
?>
		<div class="datacontent" style="color:#ffffff;">
			<!--<iframe id="vs_iframe" src="http://www.viewstl.com/?embedded&local&color=white&bgcolor=black&shading=flat&rotation=no&orientation=bottom&noborder=yes&url=<?php echo $abspath; ?>/<?php echo $stlfiles[0]; ?>">
			</iframe>-->
			<iframe id="vs_iframe" src="viewstl/viewstl.php?embedded&url=<?php echo $abspath; ?>/<?php echo $stlfiles[0]; ?>&local&color=white&bgcolor=black&shading=flat&rotation=no&orientation=bottom&noborder=yes">
			</iframe>
			<?php echo $abspath; ?>/<?php echo $stlfiles[0]; ?>
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