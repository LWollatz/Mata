<div id="header">
	<div class="maintitle">
		<h1><?php echo $PageTitle; ?></h1>
	</div>
	<form action="search.php" accept-charset="utf-8" method="post" class="menu">
			<a href="index.php"><i class="fa fa-home"></i> Home</a>
			<a href="tags.php"><i class="fa fa-tags"></i> Tags</a>
			<!--<a href="info.php"><i class="fa fa-info"></i> Info</a>-->
			<?php if (isset($MenuEntries)){ echo $MenuEntries; }?>
			<!--<?php if (isset($imageID)){ ?>
				<a href="clean.php?ID=<?php echo $imageID; ?>"><i class="fa fa-recycle"></i></a>
			<?php }else{ ?>
				<a href="clean.php"><i class="fa fa-recycle"></i></a>
			<?php } ?>-->
			<?php if($_SERVER["AUTH_USER"] != ""){?> <a><i class="fa fa-user-md"></i> <?php echo str_replace($FSdomain."\\","",$_SERVER["AUTH_USER"]); ?></a><?php } ?>
			<input name="utf8" type="hidden" value="&#x2713;" />
			<button type="submit" class="btn btn-search search">
				<i class="fa fa-search"></i>
			</button>
			<input type="text" name="sphrase" class="search" value="<?php if(isset($searchphrase)){echo $searchphrase;}?>" placeholder="Search.."/>
	</form>
</div>
<div class="unilogo">
	<a href="http://www.southampton.ac.uk" target="_blank">
		<img style="width:100%;" src="sotonlogo.png"/>
	</a>
</div>

<?php if(isset($ErrorMsg)){ ?>
	<div style="top:0px" class="error" id="error">
		<?php echo $ErrorMsg; ?>
	</div>
<?php } ?>
<?php if(isset($InfoMsg)){ ?>
	<div style="top:0px" class="info" id="info">
		<?php echo $InfoMsg; ?>
	</div>
<?php } ?>