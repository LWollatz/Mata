	
	<?php
	
	
	
	if (!isset($sortType)){
		$sortType = "None";
	}
	if (!isset($sortOrder)){
		$sortOrder = "ASC";
	}
	
	
	//$thisurlfull  -> remove ?sort option and proceed
	$thisurlLstExp = $thisurlfull;
	$tempA = strpos($thisurlLstExp,"?sort=");
	if (!$tempA){$tempA = strpos($thisurlLstExp,"&sort=");}
	if ($tempA){
		$tempA = $tempA+1;
		$tempB = strpos($thisurlLstExp,"&",$tempA);
		if ($tempB){
			$thisurlLstExp = substr($thisurlLstExp,0,$tempA).substr($thisurlLstExp,$tempB+1);
		}else{
			$thisurlLstExp = substr($thisurlLstExp,0,$tempA-1);
		}
	}
	$tempA = strpos($thisurlLstExp,"?order=");
	if (!$tempA){$tempA = strpos($thisurlLstExp,"&order=");}
	if ($tempA){
		$tempA = $tempA+1;
		$tempB = strpos($thisurlLstExp,"&",$tempA);
		if ($tempB){
			$thisurlLstExp = substr($thisurlLstExp,0,$tempA).substr($thisurlLstExp,$tempB+1);
		}else{
			$thisurlLstExp = substr($thisurlLstExp,0,$tempA-1);
		}
	}
	if($thisurlLstExp === $thisurl){
		$thisurlLstExp = $thisurlLstExp."?";
	}else{
		$thisurlLstExp = $thisurlLstExp."&";
	}
	
	$thisurlLstExp10 = $thisurlLstExp."sort=1&order=0";
	$thisurlLstExp11 = $thisurlLstExp."sort=1&order=1";
	$thisurlLstExp20 = $thisurlLstExp."sort=2&order=0";
	$thisurlLstExp21 = $thisurlLstExp."sort=2&order=1";
	$thisurlLstExp30 = $thisurlLstExp."sort=3&order=0";
	$thisurlLstExp31 = $thisurlLstExp."sort=3&order=1";
	
	$securitySQLOwnAccess = "SELECT [ID]
	  FROM [MEDDATADB].[dbo].[Experiments] 
	  WHERE [MEDDATADB].[dbo].[Experiments].[Owner] = ?";
	  
	$securitySQLUsrAccess = "SELECT [ExperimentID] AS ID, [WriteAccessGranted] AS Write
			FROM [MEDDATADB].[dbo].[UserAccess]
			WHERE [UserID] = ?";
	$securitySROwnAccess = sqlsrv_query( $conn, $securitySQLOwnAccess, array(&$authuser['ID']));	
	$securitySRUsrAccess = sqlsrv_query( $conn, $securitySQLUsrAccess, array(&$authuser['ID']));
	
	$expAccess = array();
	while($exp = sqlsrv_fetch_array($securitySROwnAccess)) {
		array_push($expAccess,array(('id') => $exp['ID'], ('level') => "owner"));
	}
	while($exp = sqlsrv_fetch_array($securitySRUsrAccess)) {
		//if((int)$exp['Write'] === 1){
		//	array_push($expAccess,array(('id') => $exp['ID'], ('level') => "edit"));
		//}else{
			array_push($expAccess,array(('id') => $exp['ID'], ('level') => "view"));
		//}
	}
	
	?>
	
	
	<table class="list">
		<col><col><col><col>
		<tr>
			<td></td>
			<td>Name <?php if ($sortType !== "None"){ ?>
			<?php if($sortType === "Name" && $sortOrder === "ASC"){ ?>
				<a href="<?php echo $thisurlLstExp11;?>"><i class="fa fa-sort-asc"></i></a>
			<?php }else if($sortType === "Name" && $sortOrder === "DESC"){ ?>
				<a href="<?php echo $thisurlLstExp10;?>"><i class="fa fa-sort-desc"></i></a>
			<?php }else{ ?>
				<a href="<?php echo $thisurlLstExp10;?>"><i class="fa fa-sort"></i></a>
			<?php } ?>
			<?php } ?>
			</td>
			<td>Date <?php if ($sortType !== "None"){ ?>
			<?php if($sortType === "Date" && $sortOrder === "ASC"){ ?>
				<a href="<?php echo $thisurlLstExp21;?>"><i class="fa fa-sort-amount-asc"></i></a>
			<?php }else if($sortType === "Date" && $sortOrder === "DESC"){ ?>
				<a href="<?php echo $thisurlLstExp20;?>"><i class="fa fa-sort-amount-desc"></i></a>
			<?php }else{ ?>
				<a href="<?php echo $thisurlLstExp20;?>"><i class="fa fa-arrows-v"></i></a>
			<?php } ?>
			<?php } ?>
			</td>
			<td>Description <?php if ($sortType !== "None"){ ?>
			<?php if($sortType === "Description" && $sortOrder === "ASC"){ ?>
				<a href="<?php echo $thisurlLstExp31;?>"><i class="fa fa-sort-alpha-asc"></i></a>
			<?php }else if($sortType === "Description" && $sortOrder === "DESC"){ ?>
				<a href="<?php echo $thisurlLstExp30;?>"><i class="fa fa-sort-alpha-desc"></i></a>
			<?php }else{ ?>
				<a href="<?php echo $thisurlLstExp30;?>"><i class="fa fa-arrows-v"></i></a>
			<?php } ?>
			<?php } ?>
			</td>
		</tr>
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
		?>
			<tr>
			<td>
		<?php 
			if($tmpPermission == "owner"){ ?>
				<i class="fa fa-circle"
		<?php 
			}else if($tmpPermission == "edit"){ ?>
				<i class="fa fa-bullseye"
		<?php 
			}else if($tmpPermission == "view"){ ?>
				<i class="fa fa-dot-circle"
		<?php
			}else{ ?>
				<i class="fa fa-circle-o"
		<?php
			} ?>
			<?php if(isset($row['Count'])){ ?>
				title="<?php echo $row['Count'];?>"
			<?php }?>
			></i>
			</td>
			<td><a href="view.php?imgID=<?php echo $row['ID'];?>"><?php echo $row['Name']; ?></a></td>
			<?php if(isset($row['Date'])){ ?>
				<td><i><?php echo $row['Date']->format("d/m/Y H:i:s"); ?></i></td>
			<?php }else{ echo "<td></td>"; } ?>
			<td><i><?php echo $description; ?></i></td>
		</tr>
		<?php
		}
		?>
	</table>
	<p>Key: <i class="fa fa-circle"></i>Owner, <i class="fa fa-bullseye"></i>Edit Permission, <i class="fa fa-dot-circle-o"></i>View Permission, <i class="fa fa-circle-o"></i>No Permission</p>
	
	
	<!--
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
	-->
