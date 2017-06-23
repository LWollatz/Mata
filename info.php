<!DOCTYPE html>
<html lang="en">
<head>
	<!--metadata-->
	<?php $PageTitle = "Info | MEDDATA"; ?>
	<?php include "_LayoutMetadata.php"; ?> 
	<!--style-->
	<?php include "_LayoutStyles.php"; ?> 
	<!--scripts-->
	<?php include "_LayoutJavascript.php"; ?> 
</head>

<body>

<?php 
include "_LayoutHeader.php"; 
?> 

<!--<div id="header">
	<h1>MEDDATA</h1>
	<form action="search.php" accept-charset="utf-8" method="post" class="menu">
		<a href="index.php"><i class="fa fa-home"></i> Home</a>
		<a href="tags.php"><i class="fa fa-tags"></i> Tags</a>
		<a href="info.php"><i class="fa fa-info"></i> Info</a>
		<input name="utf8" type="hidden" value="&#x2713;" />
		<button type="submit" class="btn btn-search search">
			<i class="fa fa-search"></i>
		</button>
		<input type="text" name="sphrase" class="search" value="" placeholder="Search.."/>
	</form>
</div>-->

<div id="content">

<h2>How to connect:</h2>
requires VPN to soton.ac.uk
<h3>Connect to website</h3>
open https://meddata.clients.soton.ac.uk in a browser<br/>
sign in to your account (remember to switch the domain by adding "THEMEDDATA\" in front of your username!)
<h3>Connect to filestore</h3>
<ol>
	<li>If not on university network, connect via VPN</li>
	<li>In Windows Explorer go to Computer</li>
	<li>Click on "Map Network Drive"</li>
	<li>On the new window that appears
		<ol>
			<li>Choose a drive letter (e.g. M:)</li>
			<li>And set the folder: \\meddata.clients.soton.ac.uk\data</li>
			<li>Check "Reconnect at logon" if desired</li>
			<li>Check "Connect using different credentials" (necessary!)</li>
			<li>Click "Finish"</li>
		</ol>
	</li>
	<li>On the security window:
		<ol>
			<li>Choose "Use another Account"</li>
			<li>Username: meddata&lt;\username&gt;</li>
			<li>Password: Password for meddata</li>
			<li>Check "Remember my credentials" (if desired)</li>
			<li>Click "OK" and you are set up</li>
		</ol>
	</li>
	<li>Add new datasets in separate folders inside the folder with your username on this drive</li>
</ol>

<!--<?php var_dump($_SERVER); ?>-->

<h2>Software used</h2>
<ul class="tree fa-ul">
	<li class="tree"><i class="fa fa-fw fa-windows"></i>Windows Server 2012 R2 Standard Evaluation</li>
	<li class="tree"><i class="fa fa-fw fa-circle-o"></i><a href="http://fontawesome.io/examples/">Heterogeneous Data Center</a> filewatcher</li>
	<li class="tree"><i class="fa fa-fw fa-circle-o"></i>For the website
		<ul class="tree fa-ul">
			<li class="tree"><i class="fa fa-fw fa-circle-o"></i><?php echo $_SERVER["SERVER_SOFTWARE"]; ?></li>
			<li class="tree"><i class="fa fa-fw fa-circle-o"></i>Microsoft SQL Server</li>
			<li class="tree"><i class="fa fa-fw fa-circle-o"></i>MCTV</li>
			<li class="tree"><i class="fa fa-fw fa-circle-o"></i><a href="http://www.viewstl.com">viewstl</a></li>
			<li class="tree"><i class="fa fa-fw fa-circle-o"></i>PHP <?php echo phpversion(); ?></li><!-- phpinfo(); -->
			<li class="tree"><i class="fa fa-fw fa-html5"></i>HTML 5</li>
			<li class="tree"><i class="fa fa-fw fa-css3"></i>CSS 3</li>
			<li class="tree"><i class="fa fa-fw fa-circle-o"></i>Javascript</li>
			<li class="tree"><i class="fa fa-fw fa-circle-o"></i><a href="https://jquery.com/">jQuery</a></li>
			<li class="tree"><i class="fa fa-fw fa-fa"></i><a href="http://fontawesome.io">Font Awesome</a></li>
		</ul>
	</li>
	<li class="tree"><i class="fa fa-fw fa-circle-o"></i>For the tiler
		<ul class="tree fa-ul">
			<li class="tree"><i class="fa fa-fw fa-circle-o"></i>Erlang OTP 19.3</li>
			<li class="tree"><i class="fa fa-fw fa-circle-o"></i>Rabbit MQ 3.6.9</li>
			<li class="tree"><i class="fa fa-fw fa-circle-o"></i><a href="http://www.celeryproject.org/">Celery</a> 3.1.25</li>
			<li class="tree"><i class="fa fa-fw fa-circle-o"></i>Python 2.7 with PILlow</li>
		</ul>
	</li>
</ul>
<br/>
<!--<?php phpinfo(); ?>-->
</div>
<?php include "_LayoutFooter.php";?>
</body>
</html>