<html>
<head>
<!--metadata-->
<title>Info | MEDDATA</title>
<link rel="icon" 
      type="image/ico" 
      href="http://meddata.clients.soton.ac.uk/favicon.ico">

<!--style-->
<link rel="stylesheet" href="http://fontawesome.io/assets/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="styles/main.css" type="text/css">
<!--javascript-->
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/js/messages.js" type="text/javascript"></script>

</head>

<body>

<div id="header">
	<h1>MEDDATA 2</h1>
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
</div>
<h2>How to connect:</h2>
requires VPN to soton.ac.uk
<h3>Connect to website</h3>
open http://meddata.clients.soton.ac.uk in a browser
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

<h2>Software used</h2>
<ul>
	<li><i class="fa fa-windows"></i> Windows Server 2012 R2 Standard Evaluation</li>
	<li><a href="http://fontawesome.io/examples/">Heterogeneous Data Center</a> filewatcher</li>
	<li>For the website
		<ul>
			<li>Microsoft IIS</li>
			<li>Microsoft SQL Server</li>
			<li>MCTV</li>
			<li>PHP <?php echo phpversion(); ?></li><!-- phpinfo(); -->
			<li><i class="fa fa-html5"></i> HTML 5</li>
			<li><i class="fa fa-css3"></i> CSS 3</li>
			<li>Javascript</li>
			<li><a href="https://jquery.com/">jQuery</a></li>
			<li><i class="fa fa-fa"></i> <a href="http://fontawesome.io">Font Awesome</a></li>
		</ul>
	</li>
	<li>For the tiler
		<ul>
			<li>Erlang OTP 19.3</li>
			<li>Rabbit MQ 3.6.9</li>
			<li><a href="http://www.celeryproject.org/">Celery</a> 3.1.25</li>
			<li>Python 2.7 with PILlow</li>
		</ul>
	</li>
</ul>
</body>
</html>