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

//find all parents for  experiment  
$psql = "SELECT [ParentExperimentID]
      ,[LinkedExperimentID]
      ,[Name]
  FROM [MEDDATADB].[dbo].[ExperimentLinks]
  LEFT JOIN [MEDDATADB].[dbo].[Experiments] 
  ON [MEDDATADB].[dbo].[ExperimentLinks].[ParentExperimentID] = [MEDDATADB].[dbo].[Experiments].[ID]
  WHERE [LinkedExperimentID] = ?";

//find all childs for  experiment  
$csql = "SELECT [ParentExperimentID]
      ,[LinkedExperimentID]
      ,[Name]
  FROM [MEDDATADB].[dbo].[ExperimentLinks]
  LEFT JOIN [MEDDATADB].[dbo].[Experiments] 
  ON [MEDDATADB].[dbo].[ExperimentLinks].[LinkedExperimentID] = [MEDDATADB].[dbo].[Experiments].[ID]
  WHERE [ParentExperimentID] = ?";

//get tags for an experiment
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

//get children of a tag
//describes the tag as key and value plus its children returns first nodeID inserted with this
$tagtypes = array(); //list of all ids => corresponding descriptor
$tagdescriptors = array(); //list of all descriptors
function gettagdescriptor($tagX){
	$tcsql = "SELECT
  [ExperimentParameters].[ID]
, [ExperimentParameters].[Name]
, [ExperimentParameters].[Value]
, [ExperimentParameters].[Position]
, [LinkP].[ParentParameterID]
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  FULL JOIN [MEDDATADB].[dbo].[ExperimentParameterLinks] AS [LinkP]
  ON [LinkP].[LinkedParameterID] = [ExperimentParameters].[ID]
  WHERE [LinkP].[ParentParameterID] = ?
  ORDER BY [ExperimentParameters].[Position]";
	if(array_key_exists($tagX['ID'],$GLOBALS["tagtypes"])){
		//saved already -> just copy
		return $GLOBALS["tagtypes"][$tagX['ID']];
	}else{
		//not saved -> need to create
		$tagtype = "T;".$tagX["Name"].";".$tagX["Value"].";[";
		$ctags = sqlsrv_query( $GLOBALS["conn"], $tcsql, array(&$tagX["ID"]));
		while($childX = sqlsrv_fetch_array($ctags)) {
			$temp = "{";
			/*if(array_key_exists($childX['ID'],$GLOBALS["tagtypes"])){
				//saved already -> just copy
				$temp = $temp.$GLOBALS["tagtypes"][$childX['ID']];
			}else{
				//not saved -> need to create*/
				$temp = $temp.gettagdescriptor($childX);
			/*}*/
			$temp = $temp."}";
			$tagtype = $tagtype.$temp;
		}
		$tagtype = rtrim($tagtype,";");
		$tagtype = $tagtype."]";
		//save it for future use
		$GLOBALS["tagtypes"][$tagX["ID"]] = $tagtype;
		if(array_key_exists($tagtype,$GLOBALS["tagdescriptors"])){
			array_push($GLOBALS["tagdescriptors"],$tagtype);
		}
		return $tagtype;
	}
	return $tagtype;
}

$fsql = "SELECT *
  FROM [MEDDATADB].[dbo].[ExperimentDataFiles]
  WHERE [ExperimentID] = ? 
  AND [IsDeleted] = 0 
  AND NOT [Filename] LIKE '%.jpg'
  ORDER BY [Filename]";

$options = array( "Scrollable" => SQLSRV_CURSOR_KEYSET );

$srinfo = sqlsrv_query( $conn, $isql, array(&$imageID));
//$srtags = sqlsrv_query( $conn, $tsql, array(&$imageID)); 
//$srfiles = sqlsrv_query( $conn, $fsql, array(&$imageID));
$srowner = sqlsrv_query( $conn, $osql, array(&$imageID));

if( $srinfo === false )  
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

?>
<head>
	<!--metadata-->
	<?php $PageTitle = "Network for ".$row['Name']." | MEDDATA"; ?>
	<?php $PageKeywords = ", network graph"; ?>
	<?php include "_LayoutMetadata.php"; ?> 
	<!--style-->
	<?php include "_LayoutStyles.php"; ?> 
	<link href="css/vis.css" rel="stylesheet" type="text/css" />
	<!--scripts-->
	<?php include "_LayoutJavascript.php"; ?>
	<script src="js/vis.js" type="text/javascript"></script>
	
	
</head>

<body>

<?php 
$MenuEntries = '<a href="view.php?imgID='.$imageID.'"><i class="fa fa-cross"></i> Close</a>';
include "_LayoutHeader.php"; 
?> 

<div id="content" style="height:100%;">




<?php
$allnames = array($row["Name"]);
$alllevels = array(2);
$allids = array($imageID);
$newids = array(array(('id') => $imageID, ('level') => 2 ));
$edges = array();
$edgesT = array();
$edgeoptions = ", color:'#d4d4d4', chosen:{edge:function(values,id,selected,hovering){values.color = '#323d43';values.width = 3;}}";
$edgeoptions = ", color:{color:'#d4d4d4', highlight:'#323d43', hover:'#323d43'}";
$edgeDSoptions = ", width:3, color:'#369aca', smooth:{enabled:'false'}";
$nodeoptions = ", group: 'datasets'"; //, color:'#266c8e', font:{color:'#ffffff'}";
$nodeuseroptions = ", group: 'users'";
$nodetagoptions = ", group: 'tags'";
$nodetaggoptions = ", group: 'grouptags'";
$nodespecoptions = ", group: 'datasets', icon: {color:'#f00f2c'}"; //, color:'#f00f2c', font:{color:'#ffffff'}";

while(sizeof($newids) > 0){
	$curid = $newids[0]['id'];
	$curlvl = $newids[0]['level'];
	//echo "Now ".$curid."<br/>";
	//echo sizeof($newids)."->";
	array_splice($newids,0,1);
	//echo sizeof($newids)."<br/>";
	$srchilds = sqlsrv_query( $conn, $csql, array(&$curid), $options);
	while($child = sqlsrv_fetch_array($srchilds)) {
		//echo "  C ".$child['LinkedExperimentID']." ";
		if(!in_array($child['LinkedExperimentID'],$allids)){
			//echo "New ";
			array_push($allids,$child['LinkedExperimentID']);
			array_push($allnames,$child['Name']);
			array_push($alllevels,$curlvl + 1);
			array_push($newids,array(('id') => $child['LinkedExperimentID'],('level') => $curlvl + 1));
		}
		$tmp = "{from: ".$child['ParentExperimentID'].", to: ".$child['LinkedExperimentID'].$edgeDSoptions."}";
		if(!in_array($tmp,$edgesT)){
			array_push($edgesT,$tmp);
		}
		//echo sizeof($newids);
		//echo "<br/>";
	}
	$srparents = sqlsrv_query( $conn, $psql, array(&$curid), $options);
	while($parent = sqlsrv_fetch_array($srparents)) {
		//echo "  P ".$parent['ParentExperimentID']." ";
		if(!in_array($parent['ParentExperimentID'],$allids)){
			//echo "New ";
			array_push($allids,$parent['ParentExperimentID']);
			array_push($allnames,$parent['Name']);
			array_push($alllevels,$curlvl - 1);
			array_push($newids,array(('id') => $parent['ParentExperimentID'],('level') => $curlvl - 1));
		}
		$tmp = "{from: ".$parent['ParentExperimentID'].", to: ".$parent['LinkedExperimentID'].$edgeDSoptions."}";
		if(!in_array($tmp,$edgesT)){
			array_push($edgesT,$tmp);
		}
		//echo sizeof($newids);
		//echo "<br/>";
	}
}

$nodes = array();
$tmp = "{id: ".$allids[0].", label: '<b>".$allnames[0]."</b>', level: ".$alllevels[0]."".$nodespecoptions."}";
array_push($nodes,$tmp);
for($i = 1; $i < sizeof($allids); $i++){
	$tmp = "{id: ".$allids[$i].", label: '<b>".$allnames[$i]."</b>', level: ".$alllevels[$i]."".$nodeoptions."}";
	array_push($nodes,$tmp);
}
$allowners = array();
$alltags = array();
//$tagids = array();   //tag id as in the database
for($i = 0; $i < sizeof($allids); $i++){
	//owner
	$srownerX = sqlsrv_query( $conn, $osql, array(&$allids[$i]));
	$ownerX = sqlsrv_fetch_array($srownerX);
	$tmp = "{id: 'O".$ownerX["ID"]."', label: '<b>".$ownerX["Name"]."</b>', level: ".$alllevels[$i]."".$nodeuseroptions."}";
	if(!in_array($ownerX["ID"],$allowners)){
		array_push($nodes,$tmp);
	}
	$tmp = "{from: 'O".$ownerX["ID"]."', to: ".$allids[$i].$edgeoptions."}";
	array_push($edges,$tmp);
	array_push($allowners,$ownerX["ID"]);
	//tags
	$srtagsX = sqlsrv_query( $conn, $tsql, array(&$allids[$i]));
	while($tagX = sqlsrv_fetch_array($srtagsX)) {
		$tagdescriptor = gettagdescriptor($tagX);
		if($tagX["LinkedParameterID"] == 0){
			//this is a leave
			if(!in_array($tagdescriptor,$alltags)){
				$tmp = "{id: '".$tagdescriptor."',";
				$tmp = $tmp." label: '".$tagX["Name"].": <b>".$tagX["Value"]."</b>', level: ".$alllevels[$i]."";
				$tmp = $tmp.$nodetagoptions."}";
				array_push($alltags,$tagdescriptor);
				array_push($nodes,$tmp);
			}
		}else{
			//this is a branch
			if(!in_array($tagdescriptor,$alltags)){
				$tmp = "{id: '".$tagdescriptor."',";
				$tmp = $tmp." label: '".$tagX["Name"].": <b>".$tagX["Value"]."</b>', level: ".$alllevels[$i]."";
				$tmp = $tmp.$nodetaggoptions."}";
				array_push($alltags,$tagdescriptor);
				array_push($nodes,$tmp);
			}
		}
		if($tagX["ParentParameterID"] == null){
			//no parent
			$tmp = "{from: '".$tagtypes[$tagX["ID"]]."', to: ".$allids[$i].", title: '".$tagX["Name"]."'".$edgeoptions."}";
		}else{
			$tmp = "{from: '".$tagtypes[$tagX["ID"]]."', to: '".$tagtypes[$tagX["ParentParameterID"]]."', title: '".$tagX["Name"]."'".$edgeoptions."}";
		}
		if(!in_array($tmp,$edges)){
			array_push($edges,$tmp);
		}
	}
}
for($i = 0; $i < sizeof($edgesT); $i++){
	$tmp = $edgesT[$i];
	array_push($edges,$tmp);
}

 

?>

<div id="mynetwork" style="width:100%;height:100%;"></div>


</div>


<script type="text/javascript">
	var options = {
		edges: {
			arrows:'to',
			arrowStrikethrough: false
		},
		groups: {
			datasets: {
				shape:'icon',
				size: 50,
				mass: 6,
				fixed:{
					x:false,
					y:true
				},
				font: {
					multi: 'html',
					strokeWidth: 2,
					background: '#ffffff'
				},
				widthConstraint: {
					minimum: 5,
					maximum: 10
				},
				icon: {
					face: 'FontAwesome',
					code: '\uf187',
					size: 50,
					color: '#266c8e'
				}
			},
			users: {
				shape:'icon',
				size: 50,
				mass: 8,
				fixed:{
					x:false,
					y:false
				},
				font: {
					multi: 'html',
					strokeWidth: 2
				},
				icon: {
					face: 'FontAwesome',
					code: '\uf0f0',
					size: 50,
					color: '#323d43'
				}
			},
			grouptags: {
				shape:'icon',
				size: 50,
				mass: 3,
				fixed:{
					x:false,
					y:false
				},
				font: {
					multi: 'html',
					strokeWidth: 2
				},
				widthConstraint: {
					maximum: 10
				},
				icon: {
					face: 'FontAwesome',
					code: '\uf02c',
					size: 50,
					color: '#323d43'
				}
			},
			tags: {
				shape:'icon',
				size: 50,
				mass: 3,
				fixed:{
					x:false,
					y:false
				},
				font: {
					multi: 'html',
					strokeWidth: 2
				},
				widthConstraint: {
					maximum: 10
				},
				icon: {
					face: 'FontAwesome',
					code: '\uf02b',
					size: 50,
					color: '#323d43'
				}
			}
		},
		interaction: {
			hover: true
		},
		physics: {
			repulsion: {
				nodeDistance: 50
			},
			hierarchicalRepulsion: {
				nodeDistance: 50
			}
		},
		layout: {
			hierarchical: {
				enabled: true,
				direction: 'UD'
			}
		}
	};

    // create an array with nodes
    var nodes = new vis.DataSet([
		<?php echo implode(", ",$nodes);?>
    ]);

    // create an array with edges
    var edges = new vis.DataSet([
		<?php echo implode(", ",$edges);?>
    ]);

    // create a network
    var container = document.getElementById('mynetwork');

    // provide the data in the vis format
    var data = {
        nodes: nodes,
        edges: edges
    };
    

    // initialize your network!
    var network = new vis.Network(container, data, options);
	
	network.on("doubleClick", function(params) {
		params.event = "[original event]";
		if(params.nodes != []){
			if(typeof params.nodes[0] != "string"){
				if(typeof params.nodes[0] == "number"){
					//user clicked on dataset
					window.location.href = "../view.php?imgID=" + params.nodes[0];
				}
			}else if(params.nodes[0].charAt(0) === "T"){
				//user clicked on tag
				$temp = params.nodes[0].split(";");
				window.location.href = "../viewtag.php?Name=" + $temp[1] + "&Value=" + $temp[2];
			}
		}
	});
	
	network.once("stabilized", function(params) {
		var optionsX = options;
		//alert(optionsX['layout']['hierarchical']['enabled']);
		optionsX['layout']['hierarchical']['enabled'] = false;
		//alert(optionsX['layout']['hierarchical']['enabled']);
		network.setOptions(optionsX);
	});
	
</script>


<!--<script type="text/javascript">

	var redraw;
	var height = 300;
	var width = 400;

	window.onload = function() {
		
		var Dracula = require('graphdracula');
		
		var g = new Dracula.Graph();
		
		g.addEdge("cherry", "apple");
		g.addEdge("strawberry", "cherry");
		
		var layouter = new Dracula.Layout.Spring(g);
		layouter.layout();
		
		var renderer = new Dracula.Renderer.Raphael('canvas',g,width,height);
		renderer.draw();
		
		redraw = function() {
			layouter.layout();
			renderer.draw();
		};
	};

	</script>-->


<?php
/* Free statement and connection resources. */  
sqlsrv_free_stmt( $srinfo);  
sqlsrv_free_stmt( $srchilds);
sqlsrv_free_stmt( $srparents);
sqlsrv_free_stmt( $srowner);
include "_LayoutFooter.php"; 
?>
</body>
</html>