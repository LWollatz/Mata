<!DOCTYPE html>
<html lang="en">
<?php
/*<!--php-->*/
include "_LayoutDatabase.php";
if (isset($_GET["errcode"])){
	$errcode = (float)$_GET["errcode"];
}else{
	$errcode = 400;
}

?>
<head>
	<!--metadata-->
	<?php $PageTitle = "ERROR ".$errcode; ?>
	<?php include "_LayoutMetadata.php"; ?> 
	<!--style-->
	<?php include "_LayoutStyles.php"; ?> 
	<!--scripts-->
	<?php include "_LayoutJavascript.php"; ?> 
</head>

<body>

<?php include "_LayoutHeader.php"; ?>

<div id="content">

<h2><?php echo $errcode;?> ERROR</h2>
Something went wrong.
<?php if($errcode == 401){ ?>
You are not authorized - authorization failed or was not provided.
Please log in to THEMEDDATA domain.
<?php }else if($errcode == 403){ ?>
You are not authorized to view the page or resource you attempted to view.
<?php }else if($errcode == 404){ ?>
The page you were trying probably doesn't exists (yet).
<?php }else{ ?>
I have no idea what though - maybe have a look online :-)
<?php } ?>
</div>
<?php include "_LayoutFooter.php";?>
</body>
</html>