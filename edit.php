<!DOCTYPE html>
<html style="height: 100%;">
<?php
/*<!--php-->*/
include "_LayoutDatabase.php";

/* get variables */
$imageID = (int)$_GET['imgID'];

/* GET AUTHORIZATION */
include "_SecurityCheck.php";
if($authstage != "Owner" && $authstage != "Writer"){
	$ErrorMsg = $ErrorMsg."You do not have permission to edit this dataset";
	header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");
	header("Location: /error.php?errcode=403");
}

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
  
$tvsql = "SELECT [Value], COUNT(*) AS Count
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] > 2 AND [Name] = ?
  GROUP BY [Value]
  ORDER BY Count DESC, [Value] ASC";
  
$tvasql = "SELECT [Value], COUNT(*) AS Count
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] > 2
  GROUP BY [Value]
  ORDER BY Count DESC, [Value] ASC";
  
$easql = "SELECT [ID], [Name], [Description]
  FROM [MEDDATADB].[dbo].[Experiments]
  WHERE [ExperimentTypeID] = 0";
  
$elsql = "SELECT [ParentExperimentID]
      ,[LinkedExperimentID]
      ,[Name]
  FROM [MEDDATADB].[dbo].[ExperimentLinks]
  LEFT JOIN [MEDDATADB].[dbo].[Experiments] 
  ON [MEDDATADB].[dbo].[ExperimentLinks].[ParentExperimentID] = [MEDDATADB].[dbo].[Experiments].[ID]
  WHERE [LinkedExperimentID] = ?";
  

	

$srinfo = sqlsrv_query( $conn, $isql, array(&$imageID));
$srtags = sqlsrv_query( $conn, $tsql, array(&$imageID)); /*there must be a more efficient way, but how do I duplicate the result of a query?*/
$srtagsPre = sqlsrv_query( $conn, $tsql, array(&$imageID));
$tagkeys = sqlsrv_query( $conn, $tnsql, array(&$imageID));
$tagvalues = sqlsrv_query( $conn, $tvasql);  
$parents = sqlsrv_query( $conn, $elsql, array(&$imageID));
$experiments = sqlsrv_query( $conn, $easql); 
if( $srtags === false )  
{  
     echo "Error in executing query.</br>";  
     die( print_r( sqlsrv_errors(), true));  
}
/* Retrieve the results of the query. */
$row = sqlsrv_fetch_array($srinfo);
/*Check if normal Experiment (not config data)*/
if($row["ExperimentTypeID"] != 0){
	$errmsg = $errmsg."Invalid ID ";
	header($_SERVER["SERVER_PROTOCOL"]." 404 Unavailable");
	header("Location: /error.php?errcode=404");;
}

/*get relative path for files*/
$relpath = str_replace("c:\\", "../", $row['DefaultBasePath']);
$relpath = str_replace("\\", "/", $relpath);
?>
<head>
<!--metadata-->
<?php $PageTitle = "Edit ".$row['Name']." | MEDDATA"; ?>
<?php $PageKeywords = ", multi-resolution, tile-based 3D CT image viewer"; ?>
<?php include "_LayoutMetadata.php"; ?> 
<!--style-->
<?php include "_LayoutStyles.php"; ?> 
<!--scripts-->
<?php include "_LayoutJavascript.php"; ?>
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

$( function() {
	var availableTagValues = [
<?php 
while($key = sqlsrv_fetch_array($tagvalues)) {
    echo '"'.$key['Value'].'",';
} ?>
	  ""];
	$( "#tagvalues" ).autocomplete({
		source: availableTagValues
	});
} );

$( function() {
	var availableExperiments = [
<?php 
while($key = sqlsrv_fetch_array($experiments)) {
    echo '{value: "'.$key['ID'].'", label: "'.$key['Name'].'", desc: '.json_encode($key['Description']).'},';
} ?>
	  {}];
	$( "#OriExperiment" ).autocomplete({
		minLength: 0,
		source: availableExperiments,
		focus: function(event,ui){
			$("#OriExperiment").val( ui.item.label );
			return false;
		},
		select: function(event,ui){
			$("#OriID").val( ui.item.value );
			$("#OriExperiment").val( ui.item.label );
			$("#OriDescription").html( ui.item.desc );
			return false;
		}
	})
	.autocomplete("instance")._renderItem = function(ul,item){
		return $("<li>").append("<div>" + item.label + "</div>").appendTo(ul);
	};
} );
</script>
</head>

<body>

<?php 
$MenuEntry1 = '<a class="btn btn-abort" href="view.php?imgID='.$imageID.'"><i class="fa fa-close"></i> Close</a>';
$MenuEntry2 = '<button type="submit" form="mainform" class="btn btn-submit"> 
	<i class="fa fa-floppy-o"></i> Save
</button>';
$MenuEntry3 = '<button type="reset" form="mainform" class="btn btn-abort">
	<i class="fa fa-undo"></i> Undo
</button>';
$MenuEntries = $MenuEntry1;
include "_LayoutHeader.php"; 
?> 

<div id="content">
<form action="update.php" accept-charset="utf-8" method="post" id="mainform">

<div class="container">
	<table>
	<tr><td class="theader"><input type="hidden" name="ID" value="<?php echo $imageID;?>">Name:</td></tr>
	<tr><td><?php echo $row['Name'];?></td></tr>
	<tr><td class="theader"> Date: </td></tr>
	<tr><td><?php echo $row['Date']->format("d/m/Y H:i:s");?></td></tr>
	<tr><td class="theader">Description:</td></tr>
	<tr><td>
		<?php $description = $row['Description'];?>
		<div class="inpdesc"><i id="leftchars"><?php echo (300-strlen($description)); ?></i> characters remaining</div>
		<textarea name="ud_description" onkeyup="textCounter(this);" cols="40" rows="10" maxlength="300"><?php echo $description;?></textarea>
	</td></tr>
	</table>
</div>

<div class="container">
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
		<td><input id="tagvalues" type="text" name="ud_newvalue" value=""></td>
	</tr>
	</table>
</div>

<?php if (file_exists($relpath."/.previews/infoJSON.txt")){ ?>
<div class="container">
	<table>
	<tr><td class="theader">Previewer Values:</td><td><input type="hidden" name="relpath" value="<?php echo $relpath.'/.previews/infoJSON.txt';?>"></td></tr>
	<?php
	$string = file_get_contents($relpath."/.previews/infoJSON.txt");
	$json_a = json_decode($string, true);?>
	<tr><td>width:</td><td><input type="text" name="ud_prvWidth" value="<?php echo $json_a['width'];?>"></td><td>px</td></tr>
	<tr><td>height:</td><td><input type="text" name="ud_prvHeight" value="<?php echo $json_a['height'];?>"></td><td>px</td></tr>
	<tr><td>x & z resolution:</td><td><input type="text" name="ud_prvRes" value="<?php if(isset($json_a['res'])){echo $json_a['res'];}?>"></td><td id="unitres"><?php if(isset($json_a['resunits'])){echo html_entity_decode($json_a['resunits'], ENT_COMPAT | ENT_HTML5, "UTF-8");}?>/px</td></tr>
	<tr><td>z resolution:</td><td><input type="text" name="ud_prvZres" value="<?php if(isset($json_a['zres'])){echo $json_a['zres'];}?>"></td><td id="unitzres"><?php if(isset($json_a['resunits'])){echo html_entity_decode($json_a['resunits'], ENT_COMPAT | ENT_HTML5, "UTF-8");}?>/px</td></tr>
	<tr><td>resolution units:</td><td><input type="text" id="ud_prvResunit" onkeyup="unitchanger();" name="ud_prvResunit" value="<?php if(isset($json_a['resunits'])){echo html_entity_decode($json_a['resunits'], ENT_COMPAT | ENT_HTML5, "UTF-8");}?>"></td><td></td></tr> <!--html_entity_decode(-->
	<tr><td>black pixel =</td><td><input type="text" name="ud_prvDensmin" value="<?php if(isset($json_a['densmin'])){echo $json_a['densmin'];}?>"></td><td><?php if(isset($json_a['densunit'])){echo $json_a['densunit'];}?></td></tr>
	<tr><td>white pixel =</td><td><input type="text" name="ud_prvDensmax" value="<?php if(isset($json_a['densmax'])){echo $json_a['densmax'];}?>"></td><td><?php if(isset($json_a['densunit'])){echo $json_a['densunit'];}?></td></tr>
	</table>
</div>
<?php } ?>

<div class="container">
	<table>
	<tr><td class="theader">Parent Datasets:</td><td></td></tr>
	<tr><td colspan=2>(To delete a link, click on the <i class="fa fa-unlink"></i> unlink icon. <br/> To import the metadata from that link click the <i class="fa fa-paste"></i> import icon.)</td></tr>
	<?php
	/* Retrieve and display the results of the query. */
	//$tag = sqlsrv_fetch($srtags, SQLSRV_SCROLL_FIRST);
	while($link = sqlsrv_fetch_array($parents)) {?>
		<tr>
			<td><?php echo $link['Name']; ?></td>
			<td>
			<button type="submit"  name="Un-Link" value="<?php echo $link['ParentExperimentID'];?>" class="btn btn-submit">
				<i class="fa fa-unlink"></i>
			</button>
			<button type="submit"  name="Import" value="<?php echo $link['ParentExperimentID'];?>" class="btn btn-submit">
				<i class="fa fa-paste"></i>
			</button>
			</td>
		</tr>
	<?php }
	?>
	
	
	<tr>
		<td>
			<input name="NewID" type="hidden" value="<?php echo $imageID; ?>" />
			<input id="OriExperiment" type="text" name="OriExperiment" value="">
			<input id="OriID" type="hidden" name="OriID" value="">
			<br/><span id="OriDescription"></span>
		</td>
		<td>
			<button type="submit"  name="Link" value="X" class="btn btn-submit">
				<i class="fa fa-link"></i>
			</button>
		</td>
	</tr>
	
	</table>
</div>

<?php if($authstage == "Owner"){ ?>
<div class="container">
<?php

	$editSQLOwnAccess = "SELECT [MEDDATADB].[dbo].[Experiments].[Owner] As ID, [MEDDATADB].[dbo].[Users].[Username] As Name
	  FROM [MEDDATADB].[dbo].[Experiments] 
	  JOIN [MEDDATADB].[dbo].[Users] ON  [MEDDATADB].[dbo].[Experiments].[Owner] = [MEDDATADB].[dbo].[Users].[ID]
	  WHERE [MEDDATADB].[dbo].[Experiments].[ID] = ?";
	  
	$editSQLUsrAccess = "SELECT [MEDDATADB].[dbo].[UserAccess].[UserID] As ID, [MEDDATADB].[dbo].[Users].[Username] As Name
	  FROM [MEDDATADB].[dbo].[UserAccess]
	  JOIN [MEDDATADB].[dbo].[Users] ON  [MEDDATADB].[dbo].[UserAccess].[UserID] = [MEDDATADB].[dbo].[Users].[ID]
	  WHERE [MEDDATADB].[dbo].[UserAccess].[ExperimentID] = ?";
	  
	$editSQLAllUser = "SELECT [ID], [Username] As Name
	  FROM [MEDDATADB].[dbo].[Users]
	  WHERE NOT [ID] IN (SELECT [MEDDATADB].[dbo].[Experiments].[Owner] As ID
	  FROM [MEDDATADB].[dbo].[Experiments] 
	  WHERE [MEDDATADB].[dbo].[Experiments].[ID] = ?)";
	  
	  
	$editSROwnAccess = sqlsrv_query( $conn, $editSQLOwnAccess, array(&$imageID));	
	$editSRUsrAccess = sqlsrv_query( $conn, $editSQLUsrAccess, array(&$imageID));
	$editSRAllUsers = sqlsrv_query( $conn, $editSQLAllUser, array(&$imageID));
	
	
	?>
	
	<table>
	<tr><td class="theader">Users allowed to view this dataset:</td><td></td></tr>
	<tr><td colspan=3>(To remove a user, click on the <i class="fa fa-minus"></i> remove icon.</td></tr>
	<?php $exp = sqlsrv_fetch_array($editSROwnAccess); ?>
		<tr>
			<td><?php echo $exp['Name']; ?></td>
			<td>Owner</td>
			<td></td>
		</tr>
	<?php
	while($exp = sqlsrv_fetch_array($editSRUsrAccess)) {?>
		<tr>
			<td><?php echo $exp['Name']; ?></td>
			<td>Can Edit</td>
			<td>
			<button type="submit"  name="UsrDel" value="<?php echo $exp['ID'];?>" class="btn btn-submit">
				<i class="fa fa-minus"></i>
			</button>
			</td>
		</tr>
	<?php }
	?>
	
	
	<tr>
		<td>
			<input id="NewUSR" name="NewUSR" type="text" value="" />
			<input id="NewUSRID" name="NewUSRID" type="hidden" value="">
		</td>
		<td>Can Edit</td>
		<td>
			<button type="submit"  name="UsrAdd" value="<?php echo $imageID; ?>" class="btn btn-submit">
				<i class="fa fa-plus"></i>
			</button>
		</td>
	</tr>
	
	</table>
</div>
<script>
$( function() {
	var allUsers = [
<?php 
while($key = sqlsrv_fetch_array($editSRAllUsers)) {
    echo '{value: "'.$key['ID'].'", label: "'.$key['Name'].'"},';
} ?>
	  {}];
	$( "#NewUSR" ).autocomplete({
		minLength: 0,
		source: allUsers,
		focus: function(event,ui){
			$("#NewUSR").val( ui.item.label );
			return false;
		},
		select: function(event,ui){
			$("#NewUSRID").val( ui.item.value );
			$("#NewUSR").val( ui.item.label );
			return false;
		}
	})
	.autocomplete("instance")._renderItem = function(ul,item){
		return $("<li>").append("<div>" + item.label + "</div>").appendTo(ul);
	};
} );
</script>
<?php } ?>

<input name="utf8" type="hidden" value="&#x2713;" />
<br/>
<div class="btngroup">
	<button type="submit" name="Save" value="X" class="btn btn-submit">
		<i class="fa fa-floppy-o"></i> Save
	</button>
	<button type="reset" form="mainform" class="btn btn-abort">
		<i class="fa fa-undo"></i> Undo
	</button>
	<a class="btn btn-abort" style="color:#ffffff;" href="view.php?imgID=<?php echo $imageID;?>">
		<i class="fa fa-close"></i> Cancel
	</a>
</div>
</form>

<!--
<form action="importExperiment.php" accept-charset="utf-8" method="post" id="importform">
<input name="NewID" type="hidden" value="<?php echo $imageID; ?>" />
<div class="btngroup">
	<input id="OriExperiment" type="text" name="OriExperiment" value="">
	<input id="OriID" type="hidden" name="OriID" value="">
	<button type="submit" form="importform" class="btn btn-submit">
		<i class="fa fa-paste"></i> Import
	</button>
	<a class="btn btn-abort" style="color:#ffffff;" href="view.php?imgID=<?php echo $imageID;?>">
		<i class="fa fa-close"></i> Cancel
	</a>
	<br/><span id="OriDescription"></span>
</div>
</form>
-->

</div>

<?php
/* Free statement and connection resources. */  
sqlsrv_free_stmt( $srinfo);
sqlsrv_free_stmt( $srtags);
sqlsrv_free_stmt( $srtagsPre);
sqlsrv_free_stmt( $tagkeys);
include "_LayoutFooter.php"; 
?>

<script>

function unitchanger()
{
 var eUnit = document.getElementById('ud_prvResunit');
 var unit = eUnit.value;
 document.getElementById('unitres').innerHTML = unit + "/px";
 document.getElementById('unitzres').innerHTML = unit + "/px";
}
</script>


<script>
function escapeHTML(text){
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return text.replace(/[&<>"']/g, function(m) {return map[m];});
}
function textCounter(txtarea)
{
 var counter = document.getElementById('leftchars');
 var u = encodeURIComponent(txtarea.value).match(/%[89ABab]/g);
 var txtlen = escapeHTML(txtarea.value).length + (u ? u.length : 0);
 if ( txtlen > 300 ) {
  txtarea.value = txtarea.value.substring( 0, 300 );
  u = encodeURIComponent(txtarea.value).match(/%[89ABab]/g);
  txtlen = escapeHTML(txtarea.value).length + (u ? u.length : 0);
  counter.innerHTML = 300 - txtlen;
  //txtarea.style.border-color = "#ff0000";
  return false;
 } else {
  counter.innerHTML = 300 - txtlen;
 }
}
</script>

 
</body>
</html>