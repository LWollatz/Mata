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
  WHERE [ExperimentID] = @1";
$tsql = str_replace("@1", $imageID, $tsql);

$fsql = "SELECT *
  FROM [MEDDATADB].[dbo].[ExperimentDataFiles]
  WHERE [ExperimentID] = ? 
  AND [IsDeleted] = 0 
  AND NOT [Filename] LIKE '%.jpg'
  ORDER BY [Filename]";

$options = array( "Scrollable" => SQLSRV_CURSOR_KEYSET );

$srinfo = sqlsrv_query( $conn, $isql, array(&$imageID));
//$srtags = sqlsrv_query( $conn, $tsql); 
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
$edgeoptions = ", arrows:'to', color:'#369aca'";
$nodeoptions = ", group: 'datasets'"; //, color:'#266c8e', font:{color:'#ffffff'}";
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
		$tmp = "{from: ".$child['ParentExperimentID'].", to: ".$child['LinkedExperimentID'].$edgeoptions."}";
		if(!in_array($tmp,$edges)){
			array_push($edges,$tmp);
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
		$tmp = "{from: ".$parent['ParentExperimentID'].", to: ".$parent['LinkedExperimentID'].$edgeoptions."}";
		if(!in_array($tmp,$edges)){
			array_push($edges,$tmp);
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



?>

<div id="mynetwork" style="width:100%;height:100%;"></div>


</div>


<script type="text/javascript">
	var options = {
		groups: {
			datasets: {
				shape:'icon',
				icon: {
					face: 'FontAwesome',
					code: '\uf187',
					size: 50,
					color: '#266c8e'
				}
			}
		},
		layout: {
			hierarchical: {
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
			window.location.href = "../view.php?imgID=" + params.nodes[0];
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