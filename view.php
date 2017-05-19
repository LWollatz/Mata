<!DOCTYPE html>

<html style="height: 100%;">

<head>
<?php
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
$InfoMsg = $_GET['msg'];
$ErrorMsg = $_GET['err'];

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
<!--meta data-->
<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<meta content="utf-8" http-equiv="encoding">
<meta name="author" content="Lasse Wollatz">
<meta name="date" content="2017-04-25">
<meta name="description" content="multi-resolution, tile-based 3D CT image viewer">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $row['Name']; ?> | MEDDATA</title>
<link rel="icon" 
      type="image/ico" 
      href="http://meddata.clients.soton.ac.uk/favicon.ico">

<!--style-->
<link rel="stylesheet" href="http://fontawesome.io/assets/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="styles/main.css" type="text/css">

<!--javascript-->
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/js/messages.js" type="text/javascript"></script>
<script src="/js/folding.js" type="text/javascript" ></script>
<!-- end head-->

</head>

<body>
<div id="header">
	<h1>Dataset <?php echo $row['Name']; ?></h1>
	<form action="search.php" accept-charset="utf-8" method="post" class="menu">
		<!--<div class="menu">-->
			<a href="index.php"><i class="fa fa-home"></i> Home</a>
			<a href="tags.php"><i class="fa fa-tags"></i> Tags</a>
			<a href="info.php"><i class="fa fa-info"></i> Info</a>
			<a href="edit.php?imgID=<?php echo $imageID?>"><i class="fa fa-edit"></i> Edit</a>
			<input name="utf8" type="hidden" value="&#x2713;" />
			<button type="submit" class="btn btn-search search">
				<i class="fa fa-search"></i>
			</button>
			<input type="text" name="sphrase" class="search" value="" placeholder="Search.."/>
		<!--</div>-->
	</form>
</div>
<div style="top:0px" class="error" id="error">
<?php echo $ErrorMsg; ?>
</div>
<div style="top:0px" class="info" id="info">
<?php echo $InfoMsg; ?>
</div> 
<div id="content">
<div class="metadata">
<table style="width:100%;">
<tr>
<td style="width:150pt;max-width:150pt;word-wrap:break-word;">

<?php
/* Display the results of the query. */
echo "<b>Name:</b> ".$row['Name']."<br/>";
echo "<b>Date:</b> ".$row['Date']->format("d/m/Y H:i:s")."<br/>";
echo "<b>Description:</b> <i>".$row['Description']."</i><br/>";
echo "<b><i class=\"fa fa-user\"></i> Owner:</b> ".$owner['Name']."<br/>";
?>
<b><i class="fa fa-tags"></i>Tags:</b>
<ul class="fa-ul">
<?php
/* Retrieve and display the results of the query. */
while($tag = sqlsrv_fetch_array($srtags)) {
    echo "<li><i class=\"fa-li fa fa-tag\"></i>".$tag['Name'].": <i><a href=\"viewtag.php?Name=".$tag['Name']."&Value=".$tag['Value']."\" >".$tag['Value']."</a></i></li>";
}
?>
</ul>
</td>
<td>
<?php if(file_exists($relpath."/.previews/infoJSON.txt")){ ?>
<iframe style="width:100%;height:500pt" src="mctv/mctv.htm?root=<?php echo $relpath; ?>/.previews/">
</iframe>
<?php } ?>

<i class=" fa fa-clone"></i> Files:
<ul class="tree fa-ul">
<?php
/* Retrieve and display the results of the query. */
$savedparts = array();
while($file = sqlsrv_fetch_array($srfiles)) {
	$fileending = end(explode('.',$file['Filename']));
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
			while (($savedparts[$cntr] != $curparts[$cntr]) && ($cntr >= 0)){
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

</td>
</tr></table>
</div>

</div>

<?php
/* Free statement and connection resources. */  
sqlsrv_free_stmt( $srinfo);  
sqlsrv_free_stmt( $srtags);
sqlsrv_free_stmt( $fsql);
sqlsrv_close( $conn);
?>



 
</body>
</html>