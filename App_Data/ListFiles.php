<?php if (isset($srfiles)){ ?>
<div id="filetree">
		<ul class="fa-ul">
			<li data-jstree='{"type":"root"}'>
<i class="fa-li fa fa-files-o"></i> <b>Files:</b>
<ul class="fa-ul">
<?php
/* Retrieve and display the results of the query. */
$savedparts = array();
while($file = sqlsrv_fetch_array($srfiles)) {
	$fileparts = explode('.',$file['Filename']);
	$fileending = end($fileparts);
	if (strpos(".exe.msi.", $fileending) === FALSE){
		$filepath = str_replace("M:", "//meddataserver.clients.soton.ac.uk", $file['BasePath']); //change this to the drive used in your installation
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
			  echo "<li data-jstree='{\"type\":\"folder\"}'><i class=\"fa fa-folder fa-fw\"></i> ".$curparts[$cntr];
			  echo "<ul class=\"fa-ul\">";
			  $cntr = $cntr + 1;
			}
		}
		$fileload = "";
		//$filetype = "fa-file-o";
		$filetype = "file";
		if (strpos(".txt.", $fileending) !== FALSE){
			//$filetype = "fa-file-text-o";
			$filetype = "file-text";
		}
		if (strpos(".js.json.php.c.cpp.h.xml.", $fileending) !== FALSE){
			//$filetype = "fa-file-code-o";
			$filetype = "file-code";
		}
		if (strpos(".zip.tar.", $fileending) !== FALSE){
			$filetype = "file-archive";
		}
		if (strpos(".doc.docx.rtf.", $fileending) !== FALSE){
			$filetype = "file-word";
		}
		if (strpos(".xls.xlsx.xlsm.", $fileending) !== FALSE){
			$filetype = "file-excel";
		}
		if (strpos(".ppt.pptx.pttm.", $fileending) !== FALSE){
			$filetype = "file-powerpoint";
		}
		if (strpos(".pdf.", $fileending) !== FALSE){
			$filetype = "file-pdf";
		}
		if (strpos(".stl.obj.", $fileending) !== FALSE){
			//$filetype = "fa-file-image-o";
			$filetype = "file-image-3D";
			/*if($hasSTL){
				$fileload = " <a title=\"view\" onclick=\"document.getElementById('vs_iframe').contentWindow.postMessage({msg_type:'load',url:'".$filepath."/".$fileendpath."'},'*')\"><i class=\"fa fa-fw fa-eye\"></i></a>";
			}*/
		}
		if (strpos(".png.jpg.jpeg.tif.tiff.gif.bmp.", $fileending) !== FALSE){
			//$filetype = "fa-file-image-o";
			$filetype = "file-image";
		}
		//$filetype = "<i class=\"fa fa-fw ".$filetype."\"></i>";
		if (strpos(".vol.raw.", $fileending) !== FALSE){
			//$filetype = "<span class=\"fa-fw fa-stack fa-1x\" style=\"font-size:50%;\"><i class=\"fa fa-fw fa-file-o fa-stack-2x\"></i><i class=\"fa fa-fw fa-cubes fa-stack-1x\"></i></span>";
			$filetype = "file-image-3D";
		}
		if (strpos(".exe.app.msi.", $fileending) !== FALSE){
			//$filetype = "<span class=\"fa-fw fa-stack fa-1x\" style=\"font-size:50%;\"><i class=\"fa fa-fw fa-file-o fa-stack-2x\"></i><i class=\"fa fa-fw fa-cogs fa-stack-1x\"></i></span>";
			$filetype = "file-code";
		}
		if (strpos(".vgi.", $fileending) !== FALSE){
			//$filetype = "<span class=\"fa-fw fa-stack fa-1x\" style=\"font-size:50%;\"><i class=\"fa fa-fw fa-file-o fa-stack-2x\"></i><i class=\"fa fa-fw fa-info fa-stack-1x\"></i></span>";
			$filetype = "file-text";
		}
		//$tmp = "<li>".$filetype." ".$filename.$fileload;
		$tmp = "<li data-jstree='{\"type\":\"".$filetype."\"}' ";
		if($authstage == "Owner" || $authstage == "Writer"){
			$tmp = $tmp."onclick='window.location.href=\"".$filepath."/".$fileendpath."\";' >";
			$tmp = $tmp."<a title=\"download ".strtoupper($fileending)."-file (".$filesize.$filesizeunit.")\" href=\"".$filepath."/".$fileendpath."\">".$filename." <i class=\"fa fa-download\"></i>(".$filesize.$filesizeunit.")</a>";
		}else{
			$tmp = $tmp.">";
			$tmp = $tmp.$filename;
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
			</li>
		</ul>
	</div>
	
<script>
	$(function (){
		$('#filetree')
		.jstree({
			'core' : {
				'multiple' : false,
				'themes' : {
					'dots' : true,
				}
			},
			'force_text' : true,
			'types' : {
				'root' : {
					"icon" : "fa fa-files-o"
				},
				'default' : {
					"icon" : "fa fa-file-o"
				},
				'folder' : {
					"icon" : "fa fa-folder-open"
				},
				'file' : {
					"icon" : "fa fa-file-o"
				},
				'file-text' : {
					"icon" : "fa fa-file-text-o"
				},
				'file-image' : {
					"icon" : "fa fa-file-image-o"
				},
				'file-image-3D' : {
					"icon" : "fa fa-file-image-o"
				},
				'file-archive' : {
					"icon" : "fa fa-file-archive-o"
				},
				'file-audio' : {
					"icon" : "fa fa-file-audio-o"
				},
				'file-video' : {
					"icon" : "fa fa-file-video-o"
				},
				'file-pdf' : {
					"icon" : "fa fa-file-pdf-o"
				},
				'file-word' : {
					"icon" : "fa fa-file-word-o"
				},
				'file-powerpoint' : {
					"icon" : "fa fa-file-powerpoint-o"
				},
				'file-excel' : {
					"icon" : "fa fa-file-excel-o"
				},
				'file-code' : {
					"icon" : "fa fa-file-code-o"
				}
			},
			'plugins' : [ 'types', 'conditionalselect' ]
		})
		.on('changed.jstree', function(e, data) {
			
		});
	})
</script>
	
<?php } ?>