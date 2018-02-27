<?php
	include "_LayoutDatabase.php"; 
    if( $conn === false )  
    {   
        die( print_r( sqlsrv_errors(), true));  
    }
    
    /*get basic post results*/
    $imageID = 0;
    if (isset($_GET["ID"])){
		$imageID = (int)$_GET["ID"];
	}

    
    $queryde="DELETE FROM [MEDDATADB].[dbo].[Experiments]
      WHERE [IsDeleted] = 1 
	  AND [ExperimentTypeID] = 0";

    $querydpA="DELETE FROM [MEDDATADB].[dbo].[ExperimentDataFiles]
      WHERE [IsDeleted] = 1";

    $querydpI="DELETE FROM [MEDDATADB].[dbo].[ExperimentDataFiles]
      WHERE [IsDeleted] = 1 
	  AND [ExperimentID] = ?";

    $errmsg = "";
    $infomsg = "";
	
	function deleteEntries($query,$querystr,$description){
		//$query = sqlsrv_prepare( $conn, $querystr);
		if( sqlsrv_execute( $query))
		{
			if( sqlsrv_rows_affected( $query) > 0)
			{
				echo "Statement executed.\n <br />".sqlsrv_rows_affected( $query);
				$GLOBALS["infomsg"] = $GLOBALS["infomsg"].'Deleted '.$description.' removed. (rows affected: '.sqlsrv_rows_affected( $query).')<br/>';
			}
			else
			{
				echo "Statement executed but no rows changed.\n <br />";
				$GLOBALS["errmsg"] = $GLOBALS["errmsg"]."No changes made<br/>";
			}
		}
		else
		{
			echo "Error in executing statement $querystr.\n";
			$GLOBALS["errmsg"] = $GLOBALS["errmsg"]."error in executing statement $querystr<br/>";
			header('Location: http://meddata.clients.soton.ac.uk/index.php?msg='.$infomsg.'&err='.$GLOBALS["errmsg"]);
			die( print_r( sqlsrv_errors(), true));
		}
	}
    

    /*** UPDATE ***/
	if($imageID == 0){
		$queryE = sqlsrv_prepare( $conn, $queryde);
		deleteEntries($queryE,$queryde,"Experiments");
		$queryF = sqlsrv_prepare( $conn, $querydpA);
		deleteEntries($queryF,$querydpA,"Files");
		header('Location: http://meddata.clients.soton.ac.uk/index.php?msg='.$infomsg.'&err='.$errmsg);
	}
	else
	{
		$deletePar = sqlsrv_prepare( $conn, $querydpI, array( &$imageID));
		deleteEntries($deletePar,$querydpI,"Files for this Experiment");
		header('Location: http://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
	}


    

?>