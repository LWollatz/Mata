	
	<?php
	
	$securitySQLOwnAccess = "SELECT [ID]
	  FROM [MEDDATADB].[dbo].[Experiments] 
	  WHERE [MEDDATADB].[dbo].[Experiments].[Owner] = ?";
	  
	$securitySQLUsrAccess = "SELECT [ExperimentID] AS ID
			FROM [MEDDATADB].[dbo].[UserAccess]
			WHERE [UserID] = ?";
	$securitySROwnAccess = sqlsrv_query( $conn, $securitySQLOwnAccess, array(&$authuser['ID']));	
	$securitySRUsrAccess = sqlsrv_query( $conn, $securitySQLUsrAccess, array(&$authuser['ID']));
	
	$expAccess = array();
	while($exp = sqlsrv_fetch_array($securitySROwnAccess)) {
		array_push($expAccess,array(('id') => $exp['ID'], ('level') => "owner"));
	}
	while($exp = sqlsrv_fetch_array($securitySRUsrAccess)) {
		array_push($expAccess,array(('id') => $exp['ID'], ('level') => "view"));
	}
	
	?>
	
	
	<ul class="fa-ul li-def">
		<?php
		/* Retrieve and display the results of the query. */
		while($row = sqlsrv_fetch_array($experiments)) {
			$description = $row['Description'];
			if (strlen($description) > 40){
				$description = substr($description,0,37)."...";
			}
			$tmpPermission = "none";
			foreach($expAccess as $key => $val){
				if($val['id'] == $row['ID']){
					$tmpPermission = $val['level'];
				}
			}
			if($tmpPermission == "none"){
				echo "<li class=\"li2\"><i class=\"fa-li fa fa-circle-o\"></i>";
			}else if($tmpPermission == "owner"){
				echo "<li><i class=\"fa-li fa fa-circle\"></i>";
			}else{
				echo "<li><i class=\"fa-li fa fa-bullseye\"></i>";
			}
			echo "<a href=\"view.php?imgID=".$row['ID']."\" >".$row['Name']."</a></br><i>".$description."</i></li>";
		}
		?>
	</ul>
