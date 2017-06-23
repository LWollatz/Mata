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
$datasetDeleted = False;
if($row["IsDeleted"] != 0){
	$errmsg = $errmsg."Dataset Deleted ";
	$datasetDeleted = True;
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

/*$( function() {
	var availableExperiments = [
< ? p h p 
while($key = sqlsrv_fetch_array($experiments)) {
    echo '{value: "'.$key['ID'].'", label: "'.$key['Name'].'", desc: '.json_encode($key['Description']).'},';
} ? >
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
} );*/
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

<div class="floatspace">
<div class="btngroup">
	<button type="submit" name="Save" value="X" class="btn btn-submit" tabindex="1">
		<i class="fa fa-floppy-o"></i> Save
	</button>
	<button type="reset" form="mainform" class="btn btn-abort" tabindex="2">
		<i class="fa fa-undo"></i> Undo
	</button>
	<a class="btn btn-abort" style="color:#ffffff;" href="view.php?imgID=<?php echo $imageID;?>" tabindex="3">
		<i class="fa fa-close"></i> Cancel
	</a>
</div>
</div>

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

<?php if (file_exists($relpath."/.previews/infoJSON.txt") && !$datasetDeleted){ ?>
<div class="container">
	<table>
	<tr><td class="theader">Previewer Values:</td><td><input type="hidden" name="relpath" value="<?php echo $relpath.'/.previews/infoJSON.txt';?>"></td></tr>
	<?php
	$string = file_get_contents($relpath."/.previews/infoJSON.txt");
	$json_a = json_decode($string, true);?>
	<tr><td>width:</td><td><input type="text" name="ud_prvWidth" value="<?php echo $json_a['width'];?>"></td><td>px</td></tr>
	<tr><td>height:</td><td><input type="text" name="ud_prvHeight" value="<?php echo $json_a['height'];?>"></td><td>px</td></tr>
	<tr><td>x & y scale:</td><td><input type="text" name="ud_prvRes" value="<?php if(isset($json_a['res'])){echo $json_a['res'];}?>"></td><td id="unitres"><?php if(isset($json_a['resunits'])){echo html_entity_decode($json_a['resunits'], ENT_COMPAT | ENT_HTML5, "UTF-8");}?>/px</td></tr>
	<tr><td>z scale:</td><td><input type="text" name="ud_prvZres" value="<?php if(isset($json_a['zres'])){echo $json_a['zres'];}?>"></td><td id="unitzres"><?php if(isset($json_a['resunits'])){echo html_entity_decode($json_a['resunits'], ENT_COMPAT | ENT_HTML5, "UTF-8");}?>/px</td></tr>
	<tr><td>scale units:</td><td><input type="text" id="ud_prvResunit" onkeyup="unitchanger();" name="ud_prvResunit" value="<?php if(isset($json_a['resunits'])){echo html_entity_decode($json_a['resunits'], ENT_COMPAT | ENT_HTML5, "UTF-8");}?>"></td><td></td></tr> <!--html_entity_decode(-->
	<tr><td colspan=3><i>(You can use "microns" or "mu-m" for &mu;m.)</i></td></tr>
	<tr><td>black pixel =</td><td><input type="text" name="ud_prvDensmin" value="<?php if(isset($json_a['densmin'])){echo $json_a['densmin'];}?>"></td><td><?php if(isset($json_a['densunit'])){echo $json_a['densunit'];}?></td></tr>
	<tr><td>white pixel =</td><td><input type="text" name="ud_prvDensmax" value="<?php if(isset($json_a['densmax'])){echo $json_a['densmax'];}?>"></td><td><?php if(isset($json_a['densunit'])){echo $json_a['densunit'];}?></td></tr>
	</table>
</div>
<?php } ?>



<div class="container">
	<table style="max-width:100%;">
	<tr><td class="theader">Parent Datasets:</td><td></td></tr>
	<tr><td colspan=2>(To delete a link, click on the <i class="fa fa-unlink"></i> unlink icon. <br/> To import the metadata from that link click the <i class="fa fa-paste"></i> import icon.)</td></tr>
	<?php
	/* Retrieve and display the results of the query. */
	//$tag = sqlsrv_fetch($srtags, SQLSRV_SCROLL_FIRST);
	while($link = sqlsrv_fetch_array($parents)) {?>
		<tr>
			<td><?php echo $link['Name']; ?></td>
			<td>
			<button type="submit"  name="Un-Link" value="<?php echo $link['ParentExperimentID'];?>" class="btn btn-abort" tabindex="8" >
				<i class="fa fa-unlink"></i>
			</button>
			<button type="submit"  name="Import" value="<?php echo $link['ParentExperimentID'];?>" class="btn btn-submit" tabindex="6">
				<i class="fa fa-paste"></i>
			</button>
			</td>
		</tr>
	<?php }
	?>
	
	
	<tr>
		<td>
			<input name="NewID" type="hidden" value="<?php echo $imageID; ?>" />
			<!--<input id="OriExperiment" type="text" name="OriExperiment" value="">
			<input id="OriID" type="hidden" name="OriID" value="">-->
			<div class="ui-widget">
			  <select id="OriID" name="OriID" class="combobox">
				<option value="">Select one...</option>
<?php 
while($key = sqlsrv_fetch_array($experiments)) {
    echo '<option value="'.$key['ID'].'" alt="'.$key['Description'].'">'.$key['Name'].'</option>';
} ?>
			  </select>
			</div>
		</td>
		<td>
			<button type="submit"  name="Link" value="X" class="btn btn-submit" tabindex="5">
				<i class="fa fa-link"></i>
			</button>
		</td>
	</tr>
	<tr style="max-width:30%;width:200px;">
	<td colspan=3 ><div id="OriDescription" style="max-width:100%;overflow:hide;"></div></td>
	</tr>
	
	</table>
</div>
<script>
  $( function() {
    $.widget( "custom.combobox", {
      _create: function() {
        this.wrapper = $( "<span>" )
          .addClass( "custom-combobox" )
          .insertAfter( this.element );
        this.element.hide();
        this._createAutocomplete();
        this._createShowAllButton();
      },
      _createAutocomplete: function() {
        var selected = this.element.children( ":selected" ),
          value = selected.val() ? selected.text() : "";
        this.input = $( "<input>" )
          .appendTo( this.wrapper )
          .val( value )
          .attr( "title", "" )
          .addClass( "custom-combobox-input" )
          .autocomplete({
            delay: 0,
            minLength: 0,
            source: $.proxy( this, "_source" )
          });
        this._on( this.input, {
          autocompleteselect: function( event, ui ) {
            ui.item.option.selected = true;
            this._trigger( "select", event, {
              item: ui.item.option
            });
			$("#OriDescription").html( ui.item.desc );
          },
          autocompletechange: "_removeIfInvalid"
        });
      },
      _createShowAllButton: function() {
        var input = this.input,
          wasOpen = false;
        $( "<a>" )
          .attr( "tabIndex", -1 )
          .attr( "title", "Show All Items" )
          .appendTo( this.wrapper )
          .button({
            icons: {
              primary: "ui-icon-triangle-1-s"
            },
            text: false
          })
          .removeClass( "ui-corner-all ui-widget" )
          .addClass( "custom-combobox-toggle ui-corner-right" )
          .on( "mousedown", function() {
            wasOpen = input.autocomplete( "widget" ).is( ":visible" );
          })

          .on( "click", function() {
            input.trigger( "focus" );
            // Close if already visible
            if ( wasOpen ) {
              return;
            }
            // Pass empty string as value to search for, displaying all results
            input.autocomplete( "search", "" );
          });
      },
      _source: function( request, response ) {
        var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
        response( this.element.children( "option" ).map(function() {
          var text = $( this ).text();
		  var desc = $( this ).attr("alt");
          if ( this.value && ( !request.term || matcher.test(text) ) )
            return {
              label: text,
              value: text,
			  desc: desc,
              option: this
            };
        }) );
      },
      _removeIfInvalid: function( event, ui ) {
        // Selected an item, nothing to do
        if ( ui.item ) {
          return;
        }
        // Search for a match (case-insensitive)
        var value = this.input.val(),
          valueLowerCase = value.toLowerCase(),
          valid = false;
        this.element.children( "option" ).each(function() {
          if ( $( this ).text().toLowerCase() === valueLowerCase ) {
            this.selected = valid = true;
            return false;
          }
        });
        // Found a match, nothing to do
        if ( valid ) {
          return;
        }
        // Remove invalid value
        this.input
          .val( "" )
          .attr( "title", value + " didn't match any item" )
		  .css( "border-color", "#f00f2c" );
		$("#OriDescription").html( "" );
        this.element.val( "" );
		
        this._delay(function() {
          this.input.css( "border-color", "#266c8e" );
        }, 2500 );
        this.input.autocomplete( "instance" ).term = "";
      },
      _destroy: function() {
        this.wrapper.remove();
        this.element.show();
      }
    });
    $( "#OriID" ).combobox();
  } );
</script>

<?php if($authstage == "Owner"){ ?>
<div class="container">
<?php

	$editSQLOwnAccess = "SELECT [MEDDATADB].[dbo].[Experiments].[Owner] As ID, [MEDDATADB].[dbo].[Users].[Username] As Name
	  FROM [MEDDATADB].[dbo].[Experiments] 
	  JOIN [MEDDATADB].[dbo].[Users] ON  [MEDDATADB].[dbo].[Experiments].[Owner] = [MEDDATADB].[dbo].[Users].[ID]
	  WHERE [MEDDATADB].[dbo].[Experiments].[ID] = ?";
	  
	$editSQLUsrAccess = "SELECT [UserAccess].[UserID] As ID, [Users].[Username] As Name, [UserAccess].[WriteAccessGranted] As Permission
	  FROM [MEDDATADB].[dbo].[UserAccess]
	  JOIN [MEDDATADB].[dbo].[Users] ON  [UserAccess].[UserID] = [Users].[ID]
	  WHERE [UserAccess].[ExperimentID] = ?";
	  
	$editSQLAllUser = "SELECT [ID], [Username] As Name
	  FROM [MEDDATADB].[dbo].[Users]
	  WHERE NOT [ID] IN (SELECT [MEDDATADB].[dbo].[Experiments].[Owner] As ID
		FROM [MEDDATADB].[dbo].[Experiments] 
		WHERE [MEDDATADB].[dbo].[Experiments].[ID] = ?)
	  AND NOT [ID] IN (SELECT [MEDDATADB].[dbo].[UserAccess].[UserID] As ID
		FROM [MEDDATADB].[dbo].[UserAccess]
		WHERE [MEDDATADB].[dbo].[UserAccess].[ExperimentID] = ?)";
	  
	  
	$editSROwnAccess = sqlsrv_query( $conn, $editSQLOwnAccess, array(&$imageID));	
	$editSRUsrAccess = sqlsrv_query( $conn, $editSQLUsrAccess, array(&$imageID));
	$editSRAllUsers = sqlsrv_query( $conn, $editSQLAllUser, array(&$imageID,&$imageID));
	
	
	?>
	
	<table>
	<tr><td class="theader">Users allowed to view this dataset:</td><td></td></tr>
	<tr><td colspan=3>(To remove a user, click on the <i class="fa fa-minus"></i> remove icon.)</td></tr>
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
			<?php if($exp['Permission'] == 1){ ?>
			<td>Can Edit</td>
			<?php }else{ ?>
			<td>Can View</td>
			<?php } ?>
			<td>
			<button type="submit"  name="UsrDel" value="<?php echo $exp['ID'];?>" class="btn btn-abort" tabindex="7">
				<i class="fa fa-minus"></i>
			</button>
			</td>
		</tr>
	<?php }
	?>
	
	
	<tr>
		<td>
			<div class="ui-widget">
			  <select id="NewUSRID" name="NewUSRID" class="combobox">
				<option value="">Select one...</option>
<?php 
while($key = sqlsrv_fetch_array($editSRAllUsers)) {
    echo '<option value="'.$key['ID'].'">'.$key['Name'].'</option>';
} ?>
			  </select>
			</div>


		</td>
		<td>
			<select id="NewUSRprm" name="NewUSRprm" class="combobox">
				<option value="0">Can View</option>
				<option value="1">Can Edit</option>
			</select>
		</td>
		<td>
			<button type="submit"  name="UsrAdd" value="<?php echo $imageID; ?>" class="btn btn-submit" tabindex="4">
				<i class="fa fa-plus"></i>
			</button>
		</td>
	</tr>
	
	</table>
</div>
<script>
  $( function() {
    $.widget( "custom.combobox", {
      _create: function() {
        this.wrapper = $( "<span>" )
          .addClass( "custom-combobox" )
          .insertAfter( this.element );
        this.element.hide();
        this._createAutocomplete();
        this._createShowAllButton();
      },
      _createAutocomplete: function() {
        var selected = this.element.children( ":selected" ),
          value = selected.val() ? selected.text() : "";
        this.input = $( "<input>" )
          .appendTo( this.wrapper )
          .val( value )
          .attr( "title", "" )
          .addClass( "custom-combobox-input" )
          .autocomplete({
            delay: 0,
            minLength: 0,
            source: $.proxy( this, "_source" )
          });
        this._on( this.input, {
          autocompleteselect: function( event, ui ) {
            ui.item.option.selected = true;
            this._trigger( "select", event, {
              item: ui.item.option
            });
          },
          autocompletechange: "_removeIfInvalid"
        });
      },
      _createShowAllButton: function() {
        var input = this.input,
          wasOpen = false;
        $( "<a>" )
          .attr( "tabIndex", -1 )
          .attr( "title", "Show All Items" )
          .appendTo( this.wrapper )
          .button({
            icons: {
              primary: "ui-icon-triangle-1-s"
            },
            text: false
          })
          .removeClass( "ui-corner-all ui-widget" )
          .addClass( "custom-combobox-toggle ui-corner-right" )
          .on( "mousedown", function() {
            wasOpen = input.autocomplete( "widget" ).is( ":visible" );
          })

          .on( "click", function() {
            input.trigger( "focus" );
            // Close if already visible
            if ( wasOpen ) {
              return;
            }
            // Pass empty string as value to search for, displaying all results
            input.autocomplete( "search", "" );
          });
      },
      _source: function( request, response ) {
        var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
        response( this.element.children( "option" ).map(function() {
          var text = $( this ).text();
          if ( this.value && ( !request.term || matcher.test(text) ) )
            return {
              label: text,
              value: text,
              option: this
            };
        }) );
      },
      _removeIfInvalid: function( event, ui ) {
        // Selected an item, nothing to do
        if ( ui.item ) {
          return;
        }
        // Search for a match (case-insensitive)
        var value = this.input.val(),
          valueLowerCase = value.toLowerCase(),
          valid = false;
        this.element.children( "option" ).each(function() {
          if ( $( this ).text().toLowerCase() === valueLowerCase ) {
            this.selected = valid = true;
            return false;
          }
        });
        // Found a match, nothing to do
        if ( valid ) {
          return;
        }
        // Remove invalid value
        this.input
          .val( "" )
          .attr( "title", value + " didn't match any item" )
          .tooltip( "open" );
        this.element.val( "" );
        this._delay(function() {
          this.input.tooltip( "close" ).attr( "title", "" );
        }, 2500 );
        this.input.autocomplete( "instance" ).term = "";
      },
      _destroy: function() {
        this.wrapper.remove();
        this.element.show();
      }
    });
    $( "#NewUSRID" ).combobox();
  } );
</script>

<?php } ?>

<input name="utf8" type="hidden" value="&#x2713;" />
<br/>


<div class="floatspace">
<div class="btngroup">
	<button type="submit" name="Save" value="X" class="btn btn-submit" tabindex="1">
		<i class="fa fa-floppy-o"></i> Save
	</button>
	<button type="reset" form="mainform" class="btn btn-abort" tabindex="2">
		<i class="fa fa-undo"></i> Undo
	</button>
	<a class="btn btn-abort" style="color:#ffffff;" href="view.php?imgID=<?php echo $imageID;?>" tabindex="3">
		<i class="fa fa-close"></i> Cancel
	</a>
</div>
</div>

</form>

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