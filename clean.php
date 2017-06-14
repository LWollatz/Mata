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
    

    /*** UPDATE ***/
	if($imageID == 0){
		$deleteExp = sqlsrv_prepare( $conn, $queryde);
		if( sqlsrv_execute( $deleteExp))
		{
			if( sqlsrv_rows_affected( $deleteExp) > 0)
			{
				echo "Statement executed.\n <br />".sqlsrv_rows_affected( $deleteExp);
				$infomsg = $infomsg.'Deleted Experiments removed. (rows affected: '.sqlsrv_rows_affected( $deleteExp).')<br/>';
			}
			else
			{
				echo "Statement executed but no rows changed.\n <br />";
				$errmsg = $errmsg."No changes made<br/>";
			}
		}
		else
		{
			echo "Error in executing statement $queryde.\n";
			$errmsg = $errmsg."error in executing statement queryde<br/>";
			header('Location: http://meddata.clients.soton.ac.uk/index.php?msg='.$infomsg.'&err='.$errmsg);
			die( print_r( sqlsrv_errors(), true));
		}
		
		$deletePar = sqlsrv_prepare( $conn, $querydpA, array());
		if( sqlsrv_execute( $deletePar))
		{
			if( sqlsrv_rows_affected( $deletePar) > 0)
			{
				echo "Statement executed.\n <br />".sqlsrv_rows_affected( $deletePar);
				$infomsg = $infomsg.'Deleted Files removed. (rows affected: '.sqlsrv_rows_affected( $deletePar).')<br/>';
			}
			else
			{
				echo "Statement executed but no rows changed.\n <br />";
				$errmsg = $errmsg."No changes made<br/>";
			}
		}
		else
		{
			echo "Error in executing statement $querydpA.\n";
			$errmsg = $errmsg."error in executing statement querydpA<br/>";
			header('Location: http://meddata.clients.soton.ac.uk/index.php?msg='.$infomsg.'&err='.$errmsg);
			die( print_r( sqlsrv_errors(), true));
		}
		header('Location: http://meddata.clients.soton.ac.uk/index.php?msg='.$infomsg.'&err='.$errmsg);
	}
	else
	{
		$deletePar = sqlsrv_prepare( $conn, $querydpI, array( &$imageID));
		if( sqlsrv_execute( $deletePar))
		{
			if( sqlsrv_rows_affected( $deletePar) > 0)
			{
				echo "Statement executed.\n <br />".sqlsrv_rows_affected( $deletePar);
				$infomsg = $infomsg.'Deleted Files for this Experiment removed. (rows affected: '.sqlsrv_rows_affected( $deletePar).')<br/>';
			}
			else
			{
				echo "Statement executed but no rows changed.\n <br />";
				$errmsg = $errmsg."No changes made by querydpI<br/>";
			}
		}
		else
		{
			echo "Error in executing statement $querydpI.\n";
			$errmsg = $errmsg."error in executing statement querydpI<br/>";
			header('Location: http://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
			die( print_r( sqlsrv_errors(), true));
		}
		header('Location: http://meddata.clients.soton.ac.uk/view.php?imgID='.$imageID.'&msg='.$infomsg.'&err='.$errmsg);
	}


    

?>