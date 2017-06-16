<?php
	include "_LayoutDatabase.php"; 
    if( $conn === false )  
    {   
        die( print_r( sqlsrv_errors(), true));  
    }
	$imageID = (int)$_POST["ID"];
	include "_SecurityCheck.php";
	if($authstage != "Owner" && $authstage != "Writer"){
		$errmsg = $errmsg."Access Denied!";
		$infomsg = $infomsg.$authuser['Username'].$authstage;
		header('Location: https://meddata.clients.soton.ac.uk/error.php?errcode=403&msg='.$infomsg.'&err='.$errmsg);
	}
/*echo $_POST['Save'];
echo $_POST['Link'];
echo $_POST['Un-Link'];*/

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

    $querytn="INSERT INTO [MEDDATADB].[dbo].[ExperimentParameters]
      ( [ExperimentID], [Name], [Value] )   
      VALUES (?, ?, ? )";

    $querytu="UPDATE [MEDDATADB].[dbo].[ExperimentParameters]
      SET [Value] = ?
      WHERE [ID]= ? ";
    
    $querytd="DELETE FROM [MEDDATADB].[dbo].[ExperimentParameters]
      WHERE [ID]= ? ";

    $errmsg = "";
    $infomsg = "";
    

    /*** UPDATE DESCRIPTION ***/
    $visitClose = sqlsrv_prepare( $conn, $query, array( &$ud_description, &$imageID));
    
    if( sqlsrv_execute( $visitClose))
    {
        if( sqlsrv_rows_affected( $visitClose) > 0)
        {
            echo "Statement executed.\n <br />".sqlsrv_rows_affected( $visitClose);
            $infomsg = $infomsg.'Description updated. (rows affected: '.sqlsrv_rows_affected( $visitClose).')<br/>';
        }
        else
        {
            echo "Statement executed but no rows changed.\n <br />";
            $errmsg = $errmsg."No changes made<br/>";
        }
    }
    else
    {
        echo "Error in executing statement.\n";
	    $errmsg = $errmsg."error in executing statement<br/>";
        header('Location: https://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
        die( print_r( sqlsrv_errors(), true));
    }

    
    /***UPDATE TAGS***/
    /*get current tags for possible update or delete*/
    $tsql = "SELECT *
      FROM [MEDDATADB].[dbo].[ExperimentParameters]
      WHERE [ExperimentID] = ?";
    $tsql = str_replace("?", $imageID, $tsql);
    $srtags = sqlsrv_query( $conn, $tsql);
    /*iterate over tags*/
	while($tag = sqlsrv_fetch_array($srtags)) {
		$ud_value = $_POST["ud_value".$tag['ID']];
		if( $ud_value != ""){
			$updateTag = sqlsrv_prepare( $conn, $querytu, array( &$ud_value, &$tag['ID']));
			if( sqlsrv_execute( $updateTag ))
			{
				if( sqlsrv_rows_affected( $updateTag ) > 0)
				{
					echo "Statement executed.\n <br />".sqlsrv_rows_affected( $updateTag );
					$infomsg = $infomsg."Tag ".$tag['Name']." updated. (rows affected: ".sqlsrv_rows_affected( $updateTag ).")<br/>";
				}
				else
				{
					echo "Statement executed but no rows changed.\n <br />";
					$errmsg = $errmsg."No changes made<br/>";
				}
			}
			else
			{
				echo "Error in executing statement.\n";
				$errmsg = $errmsg."error in executing statement<br/>";
				header('Location: https://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
				die( print_r( sqlsrv_errors(), true));
			}
		}else{
			$deleteTag = sqlsrv_prepare( $conn, $querytd, array( &$tag['ID']));
			if( sqlsrv_execute( $deleteTag ))
			{
				if( sqlsrv_rows_affected( $deleteTag ) > 0)
				{
					echo "Tag ".$tag['Name']." removed.\n <br />".sqlsrv_rows_affected( $deleteTag );
					$infomsg = $infomsg."Tag ".$tag['Name']." removed. (rows affected: ".sqlsrv_rows_affected( $deleteTag ).")<br/>";
				}
				else
				{
					echo "Statement executed but no rows changed.\n <br />";
					$errmsg = $errmsg."No changes made<br/>";
				}
			}
			else
			{
				echo "Error in executing statement.\n";
				$errmsg = $errmsg."error in executing statement<br/>";
				header('Location: https://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
				die( print_r( sqlsrv_errors(), true));
			}
		}
	}
	
	/***INSERT NEW TAG***/
    if( $ud_newkey != "" && $ud_newvalue != ""){
		$insertTag = sqlsrv_prepare( $conn, $querytn, array( &$imageID, &$ud_newkey, &$ud_newvalue));
		if( sqlsrv_execute( $insertTag ))
		{
			if( sqlsrv_rows_affected( $insertTag ) > 0)
			{
				echo "Statement executed.\n <br />".sqlsrv_rows_affected( $insertTag );
				$infomsg = $infomsg."Tag added. (rows affected: ".sqlsrv_rows_affected( $insertTag ).")<br/>";
			}
			else
			{
				echo "Statement executed but no rows changed.\n <br />";
				$errmsg = $errmsg."No changes made<br/>";
			}
		}
		else
		{
			echo "Error in executing statement.\n";
		    $errmsg = $errmsg."error in executing statement<br/>";
			header('Location: https://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
			die( print_r( sqlsrv_errors(), true));
		}
    }
	
	/***UPDATE PREVIEWER***/
	if (file_exists($relpath)){
		$string = file_get_contents($relpath);
		$json_obj = json_decode($string, true);
		$json_obj['width'] = $ud_prvWidth;
		$json_obj['height'] = $ud_prvHeight;
		$json_obj['res'] = $ud_prvRes;
		$json_obj['zres'] = $ud_prvZres;
		$json_obj['resunits'] = $ud_prvResunit;
		$json_obj['densmin'] = $ud_prvDensmin;
		$json_obj['densmax'] = $ud_prvDensmax;
		file_put_contents($relpath, json_encode($json_obj));
	}


    header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
}else if($_POST['Link']){
	$imageID = (int)$_POST["ID"];
	$parentID = (int)$_POST["OriID"];
	
	$queryln="INSERT INTO [MEDDATADB].[dbo].[ExperimentLinks]
      ( [ParentExperimentID], [LinkedExperimentID] )   
      VALUES (?, ?)";

    $errmsg = "";
    $infomsg = "";
	
	
	/***INSERT NEW TAG***/
    if( $parentID != 0){
		$insertLink = sqlsrv_prepare( $conn, $queryln, array( &$parentID, &$imageID));
		if( sqlsrv_execute( $insertLink ))
		{
			if( sqlsrv_rows_affected( $insertLink ) > 0)
			{
				echo "Statement executed.\n <br />".sqlsrv_rows_affected( $insertLink );
				$infomsg = $infomsg."Link added. (rows affected: ".sqlsrv_rows_affected( $insertLink ).")<br/>";
			}
			else
			{
				echo "Statement executed but no rows changed.\n <br />";
				$errmsg = $errmsg."No changes made<br/>";
			}
		}
		else
		{
			echo "Error in executing statement.\n";
		    $errmsg = $errmsg."error in executing linking statement<br/>";
			header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
			die( print_r( sqlsrv_errors(), true));
		}
    }
	
	
	header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);

	
}else if($_POST['Un-Link']){
	$imageID = (int)$_POST["ID"];
	$parentID = (int)$_POST['Un-Link'];
	
	$queryld="DELETE FROM [MEDDATADB].[dbo].[ExperimentLinks]
      WHERE [ParentExperimentID]= ? 
	  AND [LinkedExperimentID] = ?";

    $errmsg = "";
    $infomsg = "";
	
	
	$deleteLink = sqlsrv_prepare( $conn, $queryld, array( &$parentID, &$imageID));
	if( sqlsrv_execute( $deleteLink ))
	{
		if( sqlsrv_rows_affected( $deleteLink ) > 0)
		{
			echo "Link to ".$parentID." removed.\n <br />".sqlsrv_rows_affected( $deleteLink );
			$infomsg = $infomsg."Link to ".$parentID." removed. (rows affected: ".sqlsrv_rows_affected( $deleteLink ).")<br/>";
		}
		else
		{
			echo "Statement executed but no rows changed.\n <br />";
			$errmsg = $errmsg."No changes made to link<br/>";
		}
	}
	else
	{
		echo "Error in executing statement.\n";
		$errmsg = $errmsg."error in executing un-linking statement<br/>";
		header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
		die( print_r( sqlsrv_errors(), true));
	}
	
	header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);

	
}else if($_POST['Import']){
	$imageID = (int)$_POST["ID"];
	$parentID = (int)$_POST['Import'];
	
	$queryli="SELECT [Name],[Value],[Unit],[Type]
  FROM [MEDDATADB].[dbo].[ExperimentParameters]
  WHERE [ExperimentID] = ?
  AND NOT [Name] = ANY (
    SELECT [Name]
    FROM [MEDDATADB].[dbo].[ExperimentParameters]
    WHERE [ExperimentID] = ? )";
	
	$queryta="INSERT INTO [MEDDATADB].[dbo].[ExperimentParameters]
      ( [ExperimentID], [Name], [Value] ,[Unit], [Type] )   
      VALUES (?, ?, ?, ?, ?)";
	  
	//$options = array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
	
    $errmsg = "";
    $infomsg = "";
	
	//echo $queryli;
	$newtags = sqlsrv_query( $conn, $queryli, array( &$parentID, &$imageID));
	//echo "results ".sqlsrv_num_rows($newtags);
	
	while($tag = sqlsrv_fetch_array($newtags)) {
		$addTag = sqlsrv_prepare( $conn, $queryta, array( &$imageID, &$tag['Name'], &$tag['Value'], &$tag['Unit'], &$tag['Type']),$options);
		if( sqlsrv_execute( $addTag ))
		{
			if( sqlsrv_rows_affected( $addTag ) > 0)
			{
				echo "Tag from ".$parentID." added.\n <br />".sqlsrv_rows_affected( $addTag );
				$infomsg = $infomsg."Tag from ".$parentID." added. (rows affected: ".sqlsrv_rows_affected( $addTag ).")<br/>";
			}
			else
			{
				echo "Statement executed but no rows changed.\n <br />";
				$errmsg = $errmsg."No changes made to tags<br/>";
			}
		}
		else
		{
			echo "Error in executing statement.\n";
			$errmsg = $errmsg."error in executing tag importing statement<br/>";
			header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
			die( print_r( sqlsrv_errors(), true));
		}
	}
	
	header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);

	
}else if($_POST['UsrAdd']){
	$imageID = (int)$_POST["ID"];
	$userID = (int)$_POST["NewUSRID"];
	echo $userID;
	
	$queryau="INSERT INTO [MEDDATADB].[dbo].[UserAccess]
      ( [ExperimentID], [UserID], [WriteAccessGranted] )   
      VALUES (?, ?, 1)";

    $errmsg = "";
    $infomsg = "";
	
	
	/***INSERT NEW USER***/
    if( $userID !== ""){
		$insertUser= sqlsrv_prepare( $conn, $queryau, array( &$imageID, &$userID));
		if( sqlsrv_execute( $insertUser ))
		{
			if( sqlsrv_rows_affected( $insertUser ) > 0)
			{
				echo "Statement executed.\n <br />".sqlsrv_rows_affected( $insertUser );
				$infomsg = $infomsg."User added. (rows affected: ".sqlsrv_rows_affected( $insertUser ).")<br/>";
			}
			else
			{
				echo "Statement executed but no rows changed.\n <br />";
				$errmsg = $errmsg."No changes made<br/>";
			}
		}
		else
		{
			echo "Error in executing statement.\n";
		    $errmsg = $errmsg."error in adding user statement<br/>";
			header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
			die( print_r( sqlsrv_errors(), true));
		}
    }
	
	
	header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);

	
}else if(isset($_POST['UsrDel']) && $_POST['UsrDel'] !== ""){
	$imageID = (int)$_POST["ID"];
	$userID = (int)$_POST['UsrDel'];
	
	$queryud="DELETE FROM [MEDDATADB].[dbo].[UserAccess]
      WHERE [UserID]= ? 
	  AND [ExperimentID] = ?";

    $errmsg = "";
    $infomsg = "";
	
	
	$deleteLink = sqlsrv_prepare( $conn, $queryud, array( &$userID, &$imageID));
	if( sqlsrv_execute( $deleteLink ))
	{
		if( sqlsrv_rows_affected( $deleteLink ) > 0)
		{
			echo "Link to ".$userID." removed.\n <br />".sqlsrv_rows_affected( $deleteLink );
			$infomsg = $infomsg."Link to ".$userID." removed. (rows affected: ".sqlsrv_rows_affected( $deleteLink ).")<br/>";
		}
		else
		{
			echo "Statement executed but no rows changed.\n <br />";
			$errmsg = $errmsg."No changes made to link<br/>";
		}
	}
	else
	{
		echo "Error in executing statement.\n";
		$errmsg = $errmsg."error in executing un-linking statement<br/>";
		header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
		die( print_r( sqlsrv_errors(), true));
	}
	
	header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);

	
}else{
	$errmsg = "something went wrong";
	header('Location: https://meddata.clients.soton.ac.uk/edit.php?imgID='.$imageID.'msg='.$infomsg.'&err='.$errmsg);
}
?>