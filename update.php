<?php
    header('Content-Type:text/html; charset=UTF-8');
    $serverName = "MEDDATA";
    $connectionInfo = array( "Database"=>"MEDDATADB" );
    $conn = sqlsrv_connect( $serverName, $connectionInfo);  
    if( $conn === false )  
    {   
        die( print_r( sqlsrv_errors(), true));  
    }
    
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
        header('Location: http://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
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
				header('Location: http://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
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
				header('Location: http://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
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
			header('Location: http://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
			die( print_r( sqlsrv_errors(), true));
		}
    }
	
	/***UPDATE PREVIEWER***/
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


    header('Location: http://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);

?>