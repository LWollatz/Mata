<!DOCTYPE html>
<html style="height: 100%;">
<?php
/*<!--php-->*/
include "_LayoutDatabase.php";
/* get variables */
$imageID = (int)$_GET['imgID'];

/* Query SQL Server for the login of the user accessing the  
database. */   
$isql = "SELECT TOP 1 *
  FROM [MEDDATADB].[dbo].[Experiments]
  WHERE [ID] = @1";
$isql = str_replace("@1", $imageID, $isql);

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
  AND NOT [Filename] LIKE '%.jpg'
  ORDER BY [BasePath]";


$srinfo = sqlsrv_query( $conn, $isql);
$srtags = sqlsrv_query( $conn, $tsql); 
$srfiles = sqlsrv_query( $conn, $fsql, array(&$imageID));
$srowner = sqlsrv_query( $conn, $osql, array(&$imageID));
if( $srtags === false )  
{  
     echo "Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}
/* Retrieve the results of the query. */
$row = sqlsrv_fetch_array($srinfo);
$owner = sqlsrv_fetch_array($srowner);

/*get relative path for files*/
$relpath = str_replace("c:\\", "../", $row['DefaultBasePath']);
$relpath = str_replace("\\", "/", $relpath);
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
$MenuEntries = '<a href="edit.php?imgID='.$imageID.'"><i class="fa fa-edit"></i> Edit</a>';
include "_LayoutHeader.php"; 
?> 

<div id="content">

<?php if( file_exists($relpath."/.previews/infoJSON.txt")){ ?>
<div class="metadata">
<?php }else{ ?>
<div class="metadata fw">
<?php } ?>
<?php
/* Display the results of the query. */
echo "<b>Name:</b> ".$row['Name']."<br/>";
echo "<b>Date:</b> ".$row['Date']->format("d/m/Y H:i:s")."<br/>";
echo "<b>Description:</b> <i>".$row['Description']."</i><br/>";
echo "<b><i class=\"fa fa-user\"></i> Owner:</b> ".$owner['Name']."<br/>";
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
<?php if( !file_exists($relpath."/.previews/infoJSON.txt")){ ?>
</div>
<div class="files">
<?php } ?>

<i class=" fa fa-files-o"></i> <b>Files:</b>
<ul class="tree fa-ul">
<?php
/* Retrieve and display the results of the query. */
$savedparts = array();
while($file = sqlsrv_fetch_array($srfiles)) {
	$fileparts = explode('.',$file['Filename']);
	$fileending = end($fileparts);
	if (strpos(".tif.tiff.bmp.png.jpg", $fileending) === FALSE){
		$filepath = str_replace("c:\\", "//meddata.clients.soton.ac.uk/", $file['BasePath']);
		$filepath = str_replace("\\", "/", $filepath);
		$fileendpath = str_replace("\\", "/", $file['Filename']);
		//echo $fileendpath;
		$fileparts = explode('/',$fileendpath);
		$filename = end($fileparts);
		$curparts = $fileparts;
		array_splice($curparts, sizeof($curparts)-1, 1);
		
		//echo count($fileparts).",".sizeof($fileparts);
		
		//echo "<li>".$curparts[0]."</li>";
		if ($curparts != $savedparts){
			while (sizeof($savedparts)>sizeof($curparts) && sizeof($savedparts) >= 0){
			  echo "</ul></li>";
			  array_splice($savedparts, -1, 1);
			}
			$cntr = sizeof($savedparts)-1;
			while (($cntr >= 0) && ($savedparts[$cntr] != $curparts[$cntr])){
			  echo "</ul></li>";
			  $cntr = $cntr - 1;
			}
			$cntr = $cntr + 1;
			while ($cntr < sizeof($curparts)){
			  echo "<li class=\"tree\"><i class=\"fa fa-folder fa-fw\"></i> ".$curparts[$cntr];
			  echo "<ul class=\"tree fa-ul\">";
			  $cntr = $cntr + 1;
			}
		}
		
		$filetype = "fa-file-o";
		if (strpos(".txt", $fileending) !== FALSE){
			$filetype = "fa-file-text-o";
		}
		if (strpos(".js.json.php.c.cpp.h.xml", $fileending) !== FALSE){
			$filetype = "fa-file-code-o";
		}
		if (strpos(".doc.docx.rtf", $fileending) !== FALSE){
			$filetype = "fa-file-word-o";
		}
		if (strpos(".pdf", $fileending) !== FALSE){
			$filetype = "fa-file-pdf-o";
		}
		$filetype = "<i class=\"fa fa-fw ".$filetype."\"></i>";
		if (strpos(".vol.raw", $fileending) !== FALSE){
			$filetype = "<span class=\"fa-fw fa-stack fa-1x\" style=\"font-size:50%;\"><i class=\"fa fa-fw fa-file-o fa-stack-2x\"></i><i class=\"fa fa-fw fa-cubes fa-stack-1x\"></i></span>";
		}
		echo "<li class=\"tree\">".$filetype." <a href=\"".$filepath."/".$fileendpath."\">".$filename."</a></li>";
		
		$savedparts = $curparts;
	}
}
while (sizeof($savedparts)>1){
	echo "</ul></li>";
	array_splice($savedparts, -1, 1);
}
?>
</ul>
</div>
<?php if(file_exists($relpath."/.previews/infoJSON.txt")){ ?>
<div class="datacontent">
<iframe src="mctv/mctv.htm?root=<?php echo $relpath; ?>/.previews/">
</iframe>
</div>
<?php } ?>
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