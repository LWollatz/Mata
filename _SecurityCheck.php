<?php
$authstage = "None";
if($_SERVER["AUTH_USER"] != ""){
	$securitycheckUSER = str_replace("MEDDATA\\","",$_SERVER["AUTH_USER"]);
	$securitycheckSQLUSER = "SELECT TOP 1 *
	  FROM [MEDDATADB].[dbo].[Users] 
	  WHERE [UserName] = ?";
	$securitycheckSRUSER = sqlsrv_query( $conn, $securitycheckSQLUSER, array(&$securitycheckUSER));
	$authuser = sqlsrv_fetch_array($securitycheckSRUSER);
	$authstage = "Basic";
	if (isset($imageID)){
		$securitycheckSQLOPTIONS = array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
		$securitycheckSQLOWNER = "SELECT TOP 1 [MEDDATADB].[dbo].[Users].[ID]
		  ,[MEDDATADB].[dbo].[Users].[Username]
		  ,[MEDDATADB].[dbo].[Users].[Name]
	  FROM [MEDDATADB].[dbo].[Users] 
	  INNER JOIN [MEDDATADB].[dbo].[Experiments] 
	  ON [MEDDATADB].[dbo].[Users].[UserID] = [MEDDATADB].[dbo].[Experiments].[FileSystemUserID]
	  WHERE [MEDDATADB].[dbo].[Experiments].[ID] = ?";
		$securitycheckSROWNER = sqlsrv_query( $conn, $securitycheckSQLOWNER, array(&$imageID));
		$securitycheckOWNER = sqlsrv_fetch_array($securitycheckSROWNER);
		if($securitycheckOWNER['Username'] == $authuser['Username']){
			$authstage = "Owner";
		}else{
			$securitycheckSQLSACCESS = "SELECT [WriteAccessGranted]
			FROM [MEDDATADB].[dbo].[UserAccess]
			WHERE [UserID] = ? AND ExperimentID = ?";
			$securitycheckSRSACCESS = sqlsrv_query( $conn, $securitycheckSQLSACCESS, array(&$authuser['ID'], &$imageID),$securitycheckSQLOPTIONS);
			/**TODO: ADD GROUPACCESS CHECK**/
			if(sqlsrv_num_rows($securitycheckSRSACCESS) > 0){
				$securitycheckSACCESS = sqlsrv_fetch_array($securitycheckSRSACCESS);
				if($securitycheckSACCESS['WriteAccessGranted'] == 1){
					$authstage = "Writer";
				}else{
					$authstage = "Reader";
				}
			}
		}
	}
}
?>