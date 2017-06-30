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
  
$tsql = "SELECT *
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] = ?";
//$tsql = str_replace("@1", $imageID, $tsql);

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
	<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/graphdracula/1.0.3/dracula.min.js" type="text/javascript"></script>-->
	<!--<script src="js/dracula.min.js" type="text/javascript"></script>-->
	<!--<script src="js/dracula/raphael-min.js" type="text/javascript"></script>
	<script src="js/dracula/graffle.js" type="text/javascript"></script>
	<script src="js/dracula/graph.js" type="text/javascript"></script>-->
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
$tmp = "{id: ".$allids[0].", label: '".$allnames[0]."', level: ".$alllevels[0]."".$nodespecoptions."}";
array_push($nodes,$tmp);
for($i = 1; $i < sizeof($allids); $i++){
	$tmp = "{id: ".$allids[$i].", label: '".$allnames[$i]."', level: ".$alllevels[$i]."".$nodeoptions."}";
	array_push($nodes,$tmp);
}
$allowners = array();
$alltags = array();
for($i = 0; $i < sizeof($allids); $i++){
	//owner
	$srownerX = sqlsrv_query( $conn, $osql, array(&$allids[$i]));
	$ownerX = sqlsrv_fetch_array($srownerX);
	$tmp = "{id: 'O".$ownerX["ID"]."', label: '".$ownerX["Name"]."', level: ".$alllevels[$i]."".$nodeuseroptions."}";
	if(!in_array($ownerX["ID"],$allowners)){
		array_push($nodes,$tmp);
	}
	$tmp = "{from: 'O".$ownerX["ID"]."', to: ".$allids[$i].$edgeoptions."}";
	array_push($edges,$tmp);
	array_push($allowners,$ownerX["ID"]);
	//tags
	$srtagsX = sqlsrv_query( $conn, $tsql, array(&$allids[$i]));
	while($tagX = sqlsrv_fetch_array($srtagsX)) {
		if(!in_array($tagX["Name"].$tagX["Value"],$alltags)){
			$tmp = "{id: 'T".$tagX["Name"]."&Value=".$tagX["Value"]."', label: '".$tagX["Name"].": ".$tagX["Value"]."', level: ".$alllevels[$i]."".$nodetagoptions."}";
			array_push($nodes,$tmp);
			array_push($alltags,$tagX["Name"].$tagX["Value"]);
		}
		$tmp = "{from: 'T".$tagX["Name"]."&Value=".$tagX["Value"]."', to: ".$allids[$i].", title: '".$tagX["Name"]."'".$edgeoptions."}";
		array_push($edges,$tmp);
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
				icon: {
					face: 'FontAwesome',
					code: '\uf0f0',
					size: 50,
					color: '#323d43'
				}
			},
			tags: {
				shape:'icon',
				size: 50,
				mass: 4,
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
				nodeDistance: 500
			},
			hierarchicalRepulsion: {
				nodeDistance: 10
			}
		}/*,
		layout: {
			hierarchical: {
				direction: 'UD'
			}
		}*/
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
				//user clicked on dataset
				window.location.href = "../view.php?imgID=" + params.nodes[0];
			}else if(params.nodes[0].charAt(0) === "T"){
				//user clicked on tag
				window.location.href = "../viewtag.php?Name=" + params.nodes[0].substring(1);
			}
		}
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