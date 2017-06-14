<?php if (isset($srfiles)){ ?>
<ul class="tree fa-ul">
<?php
/* Retrieve and display the results of the query. */
$savedparts = array();
while($file = sqlsrv_fetch_array($srfiles)) {
	$fileparts = explode('.',$file['Filename']);
	$fileending = end($fileparts);
	if (strpos(".tif.tiff.bmp.png.jpg", $fileending) === FALSE){
		$filepath = str_replace("c:\\", "//meddata.clients.soton.ac.uk/", $file['BasePath']);
		$filepath = str_replace("\\", "/", $filepath);
		$fileendpath = str_replace("\\", "/", $file['Filename']);
		//echo $fileendpath;
		$fileparts = explode('/',$fileendpath);
		$filename = end($fileparts);
		$curparts = $fileparts;
		$filesize = (int)$file['FileSize'];
		$filesizeunit= "B";
		if ($filesize >= 100){
			$filesize = $filesize * 0.0009765625;
			$filesizeunit= "KB";
		}
		if ($filesize >= 100){
			$filesize = $filesize * 0.0009765625;
			$filesizeunit= "MB";
		}
		if ($filesize >= 100){
			$filesize = $filesize * 0.0009765625;
			$filesizeunit= "GB";
		}
		$filesize = (int)($filesize * 100) * 0.01;
		//$filesize = (int)((int)$file['FileSize'] * 0.00009536743164)*0.01;
		
		array_splice($curparts, sizeof($curparts)-1, 1);
		
		//echo count($fileparts).",".sizeof($fileparts);
		
		//echo "<li>".$curparts[0]."</li>";
		if ($curparts != $savedparts){
			while (sizeof($savedparts)>sizeof($curparts) && sizeof($savedparts) >= 0){
			  echo "</ul></li>";
			  array_splice($savedparts, -1, 1);
			}
			$cntr = sizeof($savedparts)-1;
			while (($cntr >= 0) && ($savedparts[$cntr] != $curparts[$cntr])){
			  echo "</ul></li>";
			  $cntr = $cntr - 1;
			}
			$cntr = $cntr + 1;
			while ($cntr < sizeof($curparts)){
			  echo "<li class=\"tree\"><i class=\"fa fa-folder fa-fw\"></i> ".$curparts[$cntr];
			  echo "<ul class=\"tree fa-ul\">";
			  $cntr = $cntr + 1;
			}
		}
		$fileload = "";
		$filetype = "fa-file-o";
		if (strpos(".txt.", $fileending) !== FALSE){
			$filetype = "fa-file-text-o";
		}
		if (strpos(".js.json.php.c.cpp.h.xml.", $fileending) !== FALSE){
			$filetype = "fa-file-code-o";
		}
		if (strpos(".doc.docx.rtf.", $fileending) !== FALSE){
			$filetype = "fa-file-word-o";
		}
		if (strpos(".xls.xlsx.xlsm.", $fileending) !== FALSE){
			$filetype = "fa-file-excel-o";
		}
		if (strpos(".ppt.pptx.pttm.", $fileending) !== FALSE){
			$filetype = "fa-file-powerpoint-o";
		}
		if (strpos(".pdf.", $fileending) !== FALSE){
			$filetype = "fa-file-pdf-o";
		}
		if (strpos(".stl.obj.", $fileending) !== FALSE){
			$filetype = "fa-file-image-o";
			if($hasSTL){
				$fileload = " <a title=\"view\" onclick=\"document.getElementById('vs_iframe').contentWindow.postMessage({msg_type:'load',url:'".$filepath."/".$fileendpath."'},'*')\"><i class=\"fa fa-fw fa-eye\"></i></a>";
			}
		}
		$filetype = "<i class=\"fa fa-fw ".$filetype."\"></i>";
		if (strpos(".vol.raw.", $fileending) !== FALSE){
			$filetype = "<span class=\"fa-fw fa-stack fa-1x\" style=\"font-size:50%;\"><i class=\"fa fa-fw fa-file-o fa-stack-2x\"></i><i class=\"fa fa-fw fa-cubes fa-stack-1x\"></i></span>";
		}
		if (strpos(".exe.app.msi.", $fileending) !== FALSE){
			$filetype = "<span class=\"fa-fw fa-stack fa-1x\" style=\"font-size:50%;\"><i class=\"fa fa-fw fa-file-o fa-stack-2x\"></i><i class=\"fa fa-fw fa-cogs fa-stack-1x\"></i></span>";
		}
		if (strpos(".vgi.", $fileending) !== FALSE){
			$filetype = "<span class=\"fa-fw fa-stack fa-1x\" style=\"font-size:50%;\"><i class=\"fa fa-fw fa-file-o fa-stack-2x\"></i><i class=\"fa fa-fw fa-info fa-stack-1x\"></i></span>";
		}
		$tmp = "<li class=\"tree\">".$filetype." ".$filename.$fileload;
		if($authstage == "Owner" || $authstage == "Writer"){
			$tmp = $tmp." <a title=\"download\" href=\"".$filepath."/".$fileendpath."\">"."<i class=\"fa fa-download\"></i>(".$filesize.$filesizeunit.")</a>";
		}
		$tmp = $tmp."</li>";
		echo $tmp;
		
		$savedparts = $curparts;
	}
}
while (sizeof($savedparts)>0){
	echo "</ul></li>";
	array_splice($savedparts, -1, 1);
}
?>
</ul>
<?php } ?>