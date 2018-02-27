<?php
	ini_set("display_errors", "on");
	include "_LayoutDatabase.php"; 
	$errmsg = "";
    $infomsg = "";
    if( $conn === false )  
    {   
        die( print_r( sqlsrv_errors(), true));  
    }
	$imageID = (int)$_POST["ID"];
	include "_SecurityCheck.php";
	$thisRoot = (isset($_SERVER['HTTPS']) ? "https" : "http")."://$_SERVER[HTTP_HOST]";
	//$thisRoot = "http://10.15.41.48";
	if($authstage != "Owner" && $authstage != "Writer"  && $authuser['Username'] != "Administrator"){
		$errmsg = $errmsg."Access Denied!";
		$infomsg = $infomsg.$authuser['Username'].$authstage;
		header('Location: '.$thisRoot.'/error.php?errcode=403&msg='.$infomsg.'&err='.$errmsg);
	}

function sqlsrvX_execute($queryX,$descriptionX){
	if( sqlsrv_execute( $queryX))
	{
		if( sqlsrv_rows_affected($queryX) > 0)
		{
			$GLOBALS["infomsg"] = $GLOBALS["infomsg"].$descriptionX.' successfull (rows affected: '.sqlsrv_rows_affected( $queryX).')<br/>';
		}
		else
		{
			$GLOBALS["errmsg"] = $GLOBALS["errmsg"]."No changes made for ".$descriptionX."<br/>";
		}
	}
	else
	{
		$GLOBALS["errmsg"] = $GLOBALS["errmsg"]."error in executing statement for ".$descriptionX."<br/>";
		header('Location: '.$GLOBALS["thisRoot"].'/edit.php?imgID='.$GLOBALS["imageID"].'&msg='.$GLOBALS["infomsg"].'&err='.$GLOBALS["errmsg"]);
		die( print_r( sqlsrv_errors(), true));
	}
}	

function updateParentTag($conn,$ChildID,$ParentID){
	$querytln="INSERT INTO [MEDDATADB].[dbo].[ExperimentParameterLinks]
      ( [ParentParameterID], [LinkedParameterID] )   
      VALUES (?, ? )";
	$querytld="DELETE FROM [MEDDATADB].[dbo].[ExperimentParameterLinks]
      WHERE [LinkedParameterID] = ?";
	$removeold = sqlsrv_prepare( $conn, $querytld, array( &$ChildID));
	if( sqlsrv_execute( $removeold)){
		//$GLOBALS["infomsg"] = $GLOBALS["infomsg"]."removed any old link";
	}
	if($ParentID != -1){
		$addnew = sqlsrv_prepare( $conn, $querytln, array( &$ParentID, &$ChildID));	
		sqlsrvX_execute($addnew,"tag ".$ChildID.", parent addition");
	}
}

function deleteTag($imageID,$conn,$tagID){
	$querytd="DELETE FROM [MEDDATADB].[dbo].[ExperimentParameters]
      WHERE [ID]= ? ";
	$querytlcd="DELETE FROM [MEDDATADB].[dbo].[ExperimentParameterLinks]
      WHERE [LinkedParameterID] = ?";
	$querytlpd="DELETE FROM [MEDDATADB].[dbo].[ExperimentParameterLinks]
      WHERE [ParentParameterID] = ?";
	$deleteLinks = sqlsrv_prepare( $conn, $querytlcd, array( &$tagID));
	if( sqlsrv_execute( $deleteLinks)){
		$GLOBALS["infomsg"] = $GLOBALS["infomsg"]."removed any child links";
	}
	$deleteLinks = sqlsrv_prepare( $conn, $querytlpd, array( &$tagID));
	if( sqlsrv_execute( $deleteLinks)){
		$GLOBALS["infomsg"] = $GLOBALS["infomsg"]."removed any parent links";
	}
	$QdeleteTag = sqlsrv_prepare( $conn, $querytd, array( &$tagID));
	sqlsrvX_execute($QdeleteTag,"tag ".$tagID." removal");
}

if($_POST['Save']){
    /*get basic post results*/
    $imageID = (int)$_POST["ID"];
    $ud_description = $_POST["ud_description"];
	$ud_description = htmlspecialchars($ud_description,ENT_QUOTES);
    $ud_newkey = $_POST["ud_newkey"];
    $ud_newvalue = $_POST["ud_newvalue"];
	/*get previewer post results*/
	$relpath = $_POST["relpath"];
	$ud_prvWidth = (int)$_POST["ud_prvWidth"];
	$ud_prvHeight = (int)$_POST["ud_prvHeight"];
	$ud_prvRes = (float)$_POST["ud_prvRes"];
	$ud_prvZres = (float)$_POST["ud_prvZres"];
	$ud_prvResunit = $_POST["ud_prvResunit"];
	//$ud_prvResunit = htmlentities($ud_prvResunit,ENT_IGNORE);
	$ud_prvResunit = htmlentities( $ud_prvResunit , ENT_COMPAT | ENT_HTML5 , "UTF-8" );
	$ud_prvDensmin = (double)$_POST["ud_prvDensmin"];
	$ud_prvDensmax = (double)$_POST["ud_prvDensmax"];

    
    $query="UPDATE [MEDDATADB].[dbo].[Experiments]
      SET [Description] = ?  
      WHERE [ID]= ? ";
	  
	$queryt="SELECT [Name], [Unit], [Type]
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ID] = ?";

    $querytn="INSERT INTO [MEDDATADB].[dbo].[ExperimentParameters]
      ( [ExperimentID], [Name], [Value], [Position] )   
      VALUES (?, ?, ?, ? )";
	  
	$querytnc="INSERT INTO [MEDDATADB].[dbo].[ExperimentParameters]
      ( [ExperimentID], [Name], [Value], [Unit], [Type]  )   
      VALUES (?, ?, ?, ?, ? )";
	  
	

    $querytu="UPDATE [MEDDATADB].[dbo].[ExperimentParameters]
      SET [Name] = ?, [Value] = ?, [Position] = ?
      WHERE [ID]= ? ";
	 
	$querythu="UPDATE [MEDDATADB].[dbo].[ExperimentParameters]
      SET [Name] = ?, [Position] = ?
      WHERE [ID]= ? ";
    
    $querytd="DELETE FROM [MEDDATADB].[dbo].[ExperimentParameters]
      WHERE [ID]= ? ";

    
    

    /*** UPDATE DESCRIPTION ***/
	//echo "***UPDATE DESCRIPTION***\n<br/>";
    $visitClose = sqlsrv_prepare( $conn, $query, array( &$ud_description, &$imageID));
    sqlsrvX_execute($visitClose,"description update");

    
    /***UPDATE TAGS***/
	//echo "***UPDATE TAGS***\n<br/>";
	/*get new tags and bring them into shape*/
	$ud_orderstr = $_POST["ud_order"];
	$ud_orderp = explode("&",$ud_orderstr);
	$ud_order = array();
	$cntr = 0;
	foreach ($ud_orderp as $tagfid) {
		$tag = array();
		$parts = explode("=",$tagfid);
		$tag["parentID"] = $parts[1];
		if($tag["parentID"] == "null"){
			$tag["parentID"] = -1;
		}
		$temp = $parts[0];
		$temp = substr($temp, 3);
		$temp = substr($temp, 0, -1);
		$tag["ID"] = $temp;
		$tag["name"] = htmlspecialchars($_POST["ud_name".$temp],ENT_QUOTES);
		$tag["value"] = htmlspecialchars($_POST["ud_value".$temp],ENT_QUOTES);
		$tag["position"] = $cntr;
		$ud_order[$tag["ID"]] = $tag;
		$cntr = $cntr + 1;
	}
	
	/*get current tags for possible update or delete*/
	//need to do this before inserting new one as to not accidentally delete the new tag
    $tsql = "SELECT *
      FROM [MEDDATADB].[dbo].[ExperimentParameters]
      WHERE [ExperimentID] = ?";
    //$tsql = str_replace("?", $imageID, $tsql);
    $srtags = sqlsrv_query( $conn, $tsql, array(&$imageID) );
	
	/* INSERT NEW */
	//echo "***INSERT NEW TAG***\n<br/>";
	$tag = $ud_order["new"];
	$newID = $tag["parentID"];
	if($tag["name"] != ""){
		//insert and get id
		$queryout = sqlsrv_query( $conn, $querytn."; SELECT SCOPE_IDENTITY() AS 'ID';", array( &$imageID, &$tag["name"], &$tag["value"], &$tag["position"]));
		sqlsrv_next_result($queryout);
		$temp = sqlsrv_fetch_array($queryout);
		$newID = $temp['ID'];
		//$errmsg = $errmsg." ADD ".$newID."<br/>";
		updateParentTag($conn,$newID,$tag["parentID"]);
	}
	
    
	/*iterate over and update or remove old tags*/
	while($tag = sqlsrv_fetch_array($srtags)) {
		if(array_key_exists($tag['ID'],$ud_order)){
			//TAG STILL THERE => ONLY UPDATE
			//$errmsg = $errmsg."UPDATE ".$tag['ID']."<br/>";
			$tagnew = $ud_order[$tag['ID']];
			if(!$tagnew['value']){
				$tagnew['value'] = $tag['Value'];
			}
			if(!$tagnew['name']){
				$tagnew['name'] = $tag['Name'];
			}
			if($tagnew['parentID'] == "new"){
				$tagnew['parentID'] = $newID;
			}
			updateParentTag($conn,$tag['ID'],$tagnew['parentID']);
			$updateTag = sqlsrv_prepare( $conn, $querytu, array( &$tagnew['name'], &$tagnew['value'], &$tagnew['position'], &$tag['ID']));
			sqlsrvX_execute($updateTag,"tag ".$tagnew['name'].":".$tagnew['value']." update");
		}else{
			//TAG NOT THERE => DELETE
			//$errmsg = $errmsg."DELETE ".$tag['ID']."<br/>";
			deleteTag($imageID,$conn,$tag['ID']);
		}
	}
	
	
	
	/***UPDATE PREVIEWER***/
	//echo "***UPDATE PREVIEWER***\n<br/>";
	if (file_exists($relpath)){
		$string = file_get_contents($relpath);
		$json_obj = json_decode($string, true);
		$json_obj['width'] = $ud_prvWidth;
		$json_obj['height'] = $ud_prvHeight;
		$json_obj['res'] = $ud_prvRes;
		$json_obj['zres'] = $ud_prvZres;
		$ud_prvResunit = $ud_prvResunit.trim();
		if ($ud_prvResunit === "micron" || $ud_prvResunit === "microns" || $ud_prvResunit === "mu-m"){
			$ud_prvResunit = "&mu;m";
		}
		$json_obj['resunits'] = $ud_prvResunit;
		$json_obj['densmin'] = $ud_prvDensmin;
		$json_obj['densmax'] = $ud_prvDensmax;
		file_put_contents($relpath, json_encode($json_obj));
	}

    //echo "***COMPLETED SAVE***\n<br/>";
    header('Location: '.$thisRoot.'/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
	
/*** ADD PARENT DATASET ***/
}else if($_POST['Link']){
	$imageID = (int)$_POST["ID"];
	$parentID = (int)$_POST["OriID"];
	
	$queryln="INSERT INTO [MEDDATADB].[dbo].[ExperimentLinks]
      ( [ParentExperimentID], [LinkedExperimentID] )   
      VALUES (?, ?)";

    $errmsg = "";
    $infomsg = "";
	
	
	/***INSERT NEW LINK***/
    if( $parentID != 0){
		$insertLink = sqlsrv_prepare( $conn, $queryln, array( &$parentID, &$imageID));
		sqlsrvX_execute($insertLink,"link to ".$parentID." addition");
    }
	
	
	header('Location: '.$thisRoot.'/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);

/*** REMOVE PARENT DATASET ***/
}else if($_POST['Un-Link']){
	$imageID = (int)$_POST["ID"];
	$parentID = (int)$_POST['Un-Link'];
	
	$queryld="DELETE FROM [MEDDATADB].[dbo].[ExperimentLinks]
      WHERE [ParentExperimentID]= ? 
	  AND [LinkedExperimentID] = ?";

    $errmsg = "";
    $infomsg = "";
	
	
	$deleteLink = sqlsrv_prepare( $conn, $queryld, array( &$parentID, &$imageID));
	sqlsrvX_execute($deleteLink,"link to ".$parentID." removal");
	
	header('Location: '.$thisRoot.'/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);

/*** IMPORT TAGS FROM PARENT DATASET ***/
}else if($_POST['Import']){
	$imageID = (int)$_POST["ID"];
	$parentID = (int)$_POST['Import'];

    //select tags and their parent key
    $queryli="SELECT 
	Tags.[ID], 
	Tags.[Name],
	Tags.[Value],
	Tags.[Unit],
	Tags.[Type],
	Parent.[Name] AS ParentName
  FROM [MEDDATADB].[dbo].[ExperimentParameters] AS Tags
  LEFT JOIN [MEDDATADB].[dbo].[ExperimentParameterLinks] AS Link ON Tags.[ID] = Link.[LinkedParameterID]
  LEFT JOIN [MEDDATADB].[dbo].[ExperimentParameters] AS Parent ON Link.[ParentParameterID] = Parent.[ID]
  WHERE Tags.[ExperimentID] = ?
  AND NOT Tags.[Name] = ANY (
    SELECT [Name]
    FROM [MEDDATADB].[dbo].[ExperimentParameters]
    WHERE [ExperimentID] = ? )
  ORDER BY Tags.[Position]";
  
  //get parent tag ID for experiment
    $queryPT="SELECT [ID], [Position]
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] = ?
  AND [Name] = ?";
  
  //get new position number
   $queryCT="SELECT 
	COUNT(Tags.[ID]) AS Count
  FROM [MEDDATADB].[dbo].[ExperimentParameters] AS Tags
  WHERE Tags.[ExperimentID] = ?
  GROUP BY Tags.[ExperimentID]";
	
	$queryta="INSERT INTO [MEDDATADB].[dbo].[ExperimentParameters]
      ( [ExperimentID], [Name], [Value] ,[Unit], [Type] )   
      VALUES (?, ?, ?, ?, ?); SELECT @@IDENTITY AS ID;";
	
	//add tag link
	$queryTLA="INSERT INTO [MEDDATADB].[dbo].[ExperimentParameterLinks]
      ( [ParentParameterID], [LinkedParameterID] )   
      VALUES (?, ?)";
	  
	//update position
	$queryTUP="UPDATE [MEDDATADB].[dbo].[ExperimentParameters]
      SET [Position] = ?  
      WHERE [ID] = ?";
	  
	//$options = array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
	
    $errmsg = "";
    $infomsg = "";
	
	$newtags = sqlsrv_query( $conn, $queryli, array( &$parentID, &$imageID));
	$tempCT = sqlsrv_query( $conn, $queryCT, array( &$imageID));
	$tempQR = sqlsrv_fetch_array($tempCT);
	$posCntr = $tempQR['Count'];
	if($posCntr === NULL){
		$posCntr = 0;
	}
	
	while($tag = sqlsrv_fetch_array($newtags)) {
		$addTag = sqlsrv_prepare( $conn, $queryta, array( &$imageID, &$tag['Name'], &$tag['Value'], &$tag['Unit'], &$tag['Type']),$options);
		if( sqlsrv_execute( $addTag ))
		{
			if( sqlsrv_rows_affected( $addTag ) > 0)
			{
				//echo "Tag from ".$parentID." added.\n <br />".sqlsrv_rows_affected( $addTag );
				$infomsg = $infomsg."Tag from ".$parentID." added. (rows affected: ".sqlsrv_rows_affected( $addTag ).")<br/>";
				
				$next_result = sqlsrv_next_result($addTag); 
				$child = sqlsrv_fetch_array($addTag);
				
				if($tag['ParentName'] != null){
					$parents = sqlsrv_query( $conn, $queryPT, array( &$imageID, $tag['ParentName']));
					$parent = sqlsrv_fetch_array($parents);
					
					
					$addTagLink = sqlsrv_prepare( $conn, $queryTLA, array( &$parent['ID'], &$child['ID']),$options);
					$newpos = $parent['Position'] + 1;
					$updateTagPosition = sqlsrv_prepare( $conn, $queryTUP, array( &$newpos, &$child['ID']),$options);
					if( sqlsrv_execute( $addTagLink ))
					{
						sqlsrv_execute( $updateTagPosition );
						$infomsg = $infomsg."Tag successfully linked. (rows affected: ".sqlsrv_rows_affected( $addTagLink ).")<br/>";
					}else{
						$errmsg = $errmsg."error in executing tag importing statement when linking ".$parent['ID']." and ".$child['ID']."<br/>";
						header('Location: '.$thisRoot.'/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
						die( print_r( sqlsrv_errors(), true));
					}
				}else{
					$newpos = $posCntr;
					$updateTagPosition = sqlsrv_prepare( $conn, $queryTUP, array( &$newpos, &$child['ID']),$options);
					sqlsrv_execute( $updateTagPosition );
				}
				$posCntr += 1;
				
			}
			else
			{
				//echo "Statement executed but no rows changed.\n <br />";
				$errmsg = $errmsg."No changes made to tags<br/>";
			}
		}
		else
		{
			//echo "Error in executing statement.\n";
			$errmsg = $errmsg."error in executing tag importing statement<br/>";
			header('Location: '.$thisRoot.'/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
			die( print_r( sqlsrv_errors(), true));
		}
	}
	
	header('Location: '.$thisRoot.'/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);

/*** ADD USER ACCESS ***/	
}else if($_POST['UsrAdd']){
	if($authstage != "Writer"  && $authuser['Username'] != "Administrator"){
		$errmsg = $errmsg."Access Denied!";
		$infomsg = $infomsg.$authuser['Username'].$authstage;
		header('Location: '.$thisRoot.'/error.php?errcode=403&msg='.$infomsg.'&err='.$errmsg);
	}
	$imageID = (int)$_POST["ID"];
	$userID = (int)$_POST["NewUSRID"];
	$userPermission = (int)$_POST["NewUSRprm"];
	if($userPermission !== 1){
		$userPermission = 0;
	}
	
	$queryau="INSERT INTO [MEDDATADB].[dbo].[UserAccess]
      ( [ExperimentID], [UserID], [WriteAccessGranted] )   
      VALUES (?, ?, ?)";

    $errmsg = "";
    $infomsg = "";
	
	
	/***INSERT NEW USER***/
    if( $userID !== ""){
		$insertUser= sqlsrv_prepare( $conn, $queryau, array( &$imageID, &$userID, &$userPermission));
		sqlsrvX_execute($insertUser,"user ".$userID." addition");
    }
	
	
	header('Location: '.$thisRoot.'/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);

/*** REMOVE USER ACCESS ***/	
}else if(isset($_POST['UsrDel']) && $_POST['UsrDel'] !== ""){
	if($authstage != "Writer"  && $authuser['Username'] != "Administrator"){
		$errmsg = $errmsg."Access Denied!";
		$infomsg = $infomsg.$authuser['Username'].$authstage;
		header('Location: '.$thisRoot.'/error.php?errcode=403&msg='.$infomsg.'&err='.$errmsg);
	}
	$imageID = (int)$_POST["ID"];
	$userID = (int)$_POST['UsrDel'];
	
	$queryud="DELETE FROM [MEDDATADB].[dbo].[UserAccess]
      WHERE [UserID]= ? 
	  AND [ExperimentID] = ?";

    $errmsg = "";
    $infomsg = "";
	
	
	$deleteUser = sqlsrv_prepare( $conn, $queryud, array( &$userID, &$imageID));
	sqlsrvX_execute($deleteUser,"user removal");
	
	header('Location: '.$thisRoot.'/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);

/*** OTHER REQUEST ***/	
}else{
	$errmsg = "something went wrong";
	header('Location: '.$thisRoot.'/edit.php?imgID='.$imageID.'msg='.$infomsg.'&err='.$errmsg);
	die( print_r( sqlsrv_errors(), true));
}
?>