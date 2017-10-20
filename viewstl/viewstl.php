
<!DOCTYPE html>



<html lang="en">
	<head>
		<title>Free online STL viewer</title>
		<meta charset="utf-8">
		<meta name="description" content="View 3D STL files directly in your browser - no software installation is required">
		<style type="text/css">
			html {margin: 0;}
			body {margin: 0;overflow:hidden;}
			
			.cinfo
			{
				width:250px;
				background:#DDDDDD;
				border: 1px solid;
				border-radius: 25px;
				padding:2px 10px 2px 10px;
				margin-bottom:20px;
			}
			
			.cinfo table {width:100%;}
			.cinfo tr {text-align:left;vertical-align:top;}
			
			#statswrap
			{
				display:inline-block;
				vertical-align:top;
				-webkit-transition: width 1.5s;
				transition: width 1.5s;
				overflow:hidden;
			}
			
			.cbar
			{
				width:20px;
				height:20px;
				display:inline-block;
				cursor:pointer;
				border:2px solid transparent;
			}
			
			.cact {margin-bottom:5px;}
			.cact span {border-bottom: 1px dotted #3434AB;color:#3434AB;cursor:pointer;}
			
			.vslink
			{
				position:absolute;
				bottom:0px;right:1px;
				font:10px Arial;color:#555555;
				text-decoration:none;
			}
			
			
			.btn-ts
			{
				background: #3498db;
				background-image: -webkit-linear-gradient(top, #3498db, #2980b9);
				background-image: -moz-linear-gradient(top, #3498db, #2980b9);
				background-image: -ms-linear-gradient(top, #3498db, #2980b9);
				background-image: -o-linear-gradient(top, #3498db, #2980b9);
				background-image: linear-gradient(to bottom, #3498db, #2980b9);
				-webkit-border-radius: 28;
				-moz-border-radius: 28;
				border-radius: 28px;
				font-family: Arial;
				color: #ffffff;
				font-size: 12px;
				padding: 10px 20px 10px 20px;
				text-decoration: none;
				margin-top:50px;
				margin-bottom:50px;
				cursor:pointer;
			}

			.btn-ts:hover
			{
				background: #3cb0fd;
				background-image: -webkit-linear-gradient(top, #3cb0fd, #3498db);
				background-image: -moz-linear-gradient(top, #3cb0fd, #3498db);
				background-image: -ms-linear-gradient(top, #3cb0fd, #3498db);
				background-image: -o-linear-gradient(top, #3cb0fd, #3498db);
				background-image: linear-gradient(to bottom, #3cb0fd, #3498db);
				text-decoration: none;
			}

			.btn-ts:before
			{
				content:  "\0000a0";
				display: inline-block;
				height: 25px;
				width: 25px;
				line-height: 25px;
				margin: 0px 5px 0px 0px;
				position: relative;
				top: 0px;
				left: 0px;
				background: url("/images/treatstock.png");
			}
			
			.btn-stc
			{
				background: #d93470;
				background-image: -webkit-linear-gradient(top, #d93470, #b82b42);
				background-image: -moz-linear-gradient(top, #d93470, #b82b42);
				background-image: -ms-linear-gradient(top, #d93470, #b82b42);
				background-image: -o-linear-gradient(top, #d93470, #b82b42);
				background-image: linear-gradient(to bottom, #d93470, #b82b42);
				font-family: Arial;
				color: #ffffff;
				font-size: 12px;
				padding: 10px 20px 10px 20px;
				text-decoration: none;
				cursor:pointer;
			}

			.btn-stc:hover
			{
				background: #d72ee6;
				text-decoration: none;
			}			
			
			.btn-stc:before
			{
				content:  "\0000a0";
				display: inline-block;
				height: 25px;
				width: 25px;
				line-height: 25px;
				margin: 0px 5px 0px 0px;
				position: relative;
				top: 0px;
				left: 0px;
				background: url("/images/save.png");
			}			
		</style>

				<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', 'UA-54015102-1', 'auto');
		ga('send', 'pageview');
		</script>
		
		<link rel="icon" type="image/png" href="/favicon.png" />
		
		<style>
			header
			{
				text-align:center;
				border: 1px solid #909090;
				border-radius: 12px;
				padding:5px;
				margin:5px;
				background:#FFFFFF;
			}

			footer
			{
				float:right;
				font:14px Arial;color:#1010FF;
				margin:30px;
			}
			
			footer a {text-decoration:none;font:14px Arial;color:#1010FF;}
			footer a:hover {text-decoration:none;font:14px Arial;color:#1010FF;}
			
			h2 {margin-bottom:5px;margin-top:0px;}
			
			h4 {margin-left:20px;margin-bottom:1px;margin-top:2px;color:#202050;}
			h5 {margin-left:20px;margin-bottom:2px;margin-top:0px;font-weight: normal;}
		
			.stitle
			{
				font:20px Arial;color:#2020DD;margin:10px 30px 10px 30px;
			}
		</style>			
	
		<script src="three.min.js"></script>
		<script src="webgl_detector.js"></script>
		<script src="OrbitControls.js"></script>
		<script src="parser.js"></script>
		<script src="general/read_internal.js"></script>
		<script src="general/read_external.js"></script>

		<script> var need_browser=false; </script>

<!--[if lt IE 10 ]>
		<script>
			need_browser=true;
		</script>
<![endif]-->

		<script>
			if (!(typeof FileReader !== "undefined"))
			{
				need_browser=true;
			}
		</script>

		<script>
			var is_ie = !!window.MSStream;
			var waiting=false;
			var mesh=null;
			var material=new THREE.MeshLambertMaterial({color:0x909090, overdraw: 1, wireframe: false, shading:THREE.FlatShading, vertexColors: THREE.FaceColors});
			var mesh_is_ready=false;
			var colored_model=false;
			
			if (!is_ie)
				//double side not supported on IE
				material.side = THREE.DoubleSide;
			
			var cancel_download=false;	
			var cancel_3dp=false;
			var max_file_size=40000000;
			var mesh_color='#909090';
			
			var xsize=0;
			var ysize=0;
			var zsize=0;
			var vol=0;
			var area=0;
			var triangles_count=0;
			var model_filename='';
			var view_units=1; //mm
			var file_units=1; //mm
			var bg_color=0xffffff;
			var deg90=Math.PI / 2;
			var orientation="front";
			var egh=null; //for edges
			var has_edges=false;			
			var url_is_local=false;
			
			function $id(id)
			{
				return document.getElementById(id);
			}

			function set_orientation(ovalue)
			{
				orientation=ovalue;
				if (!mesh) return;
				
				mesh.rotation.x=0;
				mesh.rotation.y=0;
				mesh.rotation.z=0;
			
				switch (ovalue)
				{
					case "right":
						//right - rotate -90 deg. around axis y
						mesh.rotateY(-deg90);
						break;

					case "left":
						//right - rotate 90 deg. around axis y
						mesh.rotateY(deg90);
						break;

					case "top":
						//top - rotate 90 deg. around axis x
						mesh.rotateX(deg90);
						break;

					case "bottom":
						//top - rotate -90 deg. around axis x
						mesh.rotateX(-deg90);
						break;
						
					case "back":
						//top - rotate 180 deg. around axis Y
						mesh.rotateY(deg90*2);
						break;						
					
					default:
						//Front / do nothing
				}
				
				if (mesh_is_ready)
					mesh.geometry.verticesNeedUpdate = true;
			}

			function set_edges(b)
			{
				has_edges=b;
				if (mesh==null) return;
				if (has_edges)
				{
					egh = new THREE.EdgesHelper( mesh, 0x000000 );
					egh.material.linewidth = 1;
					scene.add( egh );
				}
				else
					remove_edges();
			}
			
			function set_local(b)
			{
				url_is_local=b;
				if (mesh==null) return;
			}			
			
			function remove_edges()
			{
				if (egh) scene.remove( egh );
				egh=null;
			}

			function set_view_units(v)
			{
				view_units=v;
				$id("vunits").innerHTML=v=="1"?"mm":"in";
				setCookie("units", v=="1"?"mm":"in", 1000);
				recalc_units();
			}
			
			function set_file_units(v)
			{
				file_units=v;
				recalc_units();
			}
			
			function recalc_units()
			{
							}

						
			
			function do_resize()
			{
				var height = isNaN(window.innerHeight) ? window.clientHeight : window.innerHeight;
				var width = isNaN(window.innerWidth) ? window.clientWidth : window.innerWidth;
								height-=7;
				width-=7;
				$id('cjc').style.height=(height)+'px';
				$id('cjc').style.width=(width)+'px';
				$id('cjcwrap').style.height=(height)+'px';
				$id('cjcwrap').style.width=(width)+'px';
				$id('cjcdrag').style.top=((height-100)/2)+'px';
				$id('cjcdrag').style.left=((width-350)/2)+'px';
				$id('cjcpbar').style.top=((height-125)/2)+'px';
				$id('cjcpbar').style.left=((width-350)/2)+'px';
				$id('cjcproc').style.top=((height-125)/2)+'px';
				$id('cjcproc').style.left=((width-350)/2)+'px';
				
				camera.aspect = width / height;
				camera.updateProjectionMatrix();
				
								
				var rsize_width=$id("cjcwrap").getBoundingClientRect().width;
				var rsize_height=$id("cjcwrap").getBoundingClientRect().height;
				renderer.setSize(rsize_width-5, rsize_height-5);
			}
			
			function handleDragOver(e)
			{
				if (waiting) return;
			
				e.stopPropagation();
				e.preventDefault();
				e.dataTransfer.dropEffect = 'copy';			
			}
			
			function handleFileDrop(e)
			{
				e.stopPropagation();
				e.preventDefault();
				
				if (waiting) return;
			
				//first, check if its a file
				if (e.dataTransfer.files.length>0)
				{
					if (e.dataTransfer.files[0].size>max_file_size)
					{
						alert('File is too big - maximum allowed size is 35mb');
						return false;
					}
					
					if (!supported_file_type(e.dataTransfer.files[0].name))
					{
						alert('File type is not supported');
						return false;
					}
					read_file(e.dataTransfer.files[0]);
				}
				
				else if (typeof e.dataTransfer.getData("Text") === 'string')
				{
					//check - maybe a url?
					read_from_url(e.dataTransfer.getData("Text"));
				}				
			}
			
			function supported_file_type (filename)
			{
				switch (filename.split('.').pop().toLowerCase())
				{
					case 'stl':
					case 'obj':
					case 'vf':
						return true;
						break;
						
					default:
						return false;
				}
				
			}
			
			function switch_view(v)
			{
				$id('cjcdrag').style.display=((v=='drag')?'block':'none');
				$id('cjcpbar').style.display=((v=='pbar')?'block':'none');
				$id('cjcproc').style.display=((v=='proc')?'block':'none');
				$id('cjc').style.display=((v=='cjc')?'block':'none');
			}
			
			function after_error()
			{
				switch_view('drag');
				cancel_download=false;
				waiting=false;
				return false;
			}
			
			
			function after_file_load(filename, s)
			{
				var vf_data;
				
				try
				{
					vf_data=parse_3d_file(filename, s);
				}
				catch(err)
				{
					vf_data="Error parsing the file";
				}
				
				if (typeof vf_data === 'string')
				{
					alert(vf_data);
					switch_view('drag');
					waiting=false;
					return;
				}
				
				mesh_is_ready=false;
				
				if (mesh!=null) {scene.remove(mesh);mesh=null};
				
				var geo=new THREE.Geometry;
				geo.vertices=vf_data.vertices;
				geo.faces=vf_data.faces;				
				geo.computeBoundingBox();
					
				geo.computeCentroids();
				geo.computeFaceNormals();
				geo.computeVertexNormals();
				THREE.GeometryUtils.center(geo);
				mesh = new THREE.Mesh(geo, material);
				
				
				if (vf_data.colors)
				{
					set_color($id('white_color'),'#FFFFFF');
					colored_model=true;
				}
				else
					colored_model=false;
				
				update_mesh_color();
				
				//renderer.setClearColor(bg_color, 0); //background
				set_color(null, bg_color, true); //background
				
								set_orientation(orientation);
				
				scene.add(mesh);
				mesh_is_ready=true;
				
				directionalLight.position.x = geo.boundingBox.min.y * 2;
				directionalLight.position.y = geo.boundingBox.min.y * 2;
				directionalLight.position.z = geo.boundingBox.max.z * 2;

				pointLight.position.x = (geo.boundingBox.min.y+geo.boundingBox.max.y)/2;
				pointLight.position.y = (geo.boundingBox.min.y+geo.boundingBox.max.y)/2;
				pointLight.position.z = geo.boundingBox.max.z * 2;
				
				camera.position.set(0,0,Math.max(geo.boundingBox.max.x*3,geo.boundingBox.max.y*3,geo.boundingBox.max.z*3));
				controls.reset();
				switch_view('cjc');
				
				if (has_edges) set_edges(true);				
				
				xsize=geo.boundingBox.max.x-geo.boundingBox.min.x;
				ysize=geo.boundingBox.max.y-geo.boundingBox.min.y;
				zsize=geo.boundingBox.max.z-geo.boundingBox.min.z;				
				vol_area=calc_vol_and_area(geo);
				vol=vol_area[0];
				area=vol_area[1];
				triangles_count=geo.faces.length;
				model_filename=filename;
				
				
								
								if (info_msg_source!=null)
					post_info_message();
					
				if (photo_msg_source!=null)
					setTimeout(function(){post_photo_message();}, 1500);
								
				//$id('cjcwrap').addEventListener('dragover', handleDragOver, false);
				//$id('cjcwrap').addEventListener('drop', handleFileDrop, false);				
				
				waiting=false;
			}
			
			function set_color_by_name(color, is_bg_color)
			{
				is_bg_color=is_bg_color||false;
				switch (color.toLowerCase())
				{
					case "black":		set_color($id('black_color'),'#000000',is_bg_color); break;
					case "white":		set_color($id('white_color'),'#FFFFFF',is_bg_color); break;
					case "blue":		set_color($id('blue_color'),'#0000FF',is_bg_color); break;
					case "green":		set_color($id('green_color'),'#00FF00',is_bg_color); break;
					case "red":			set_color($id('red_color'),'#FF0000',is_bg_color); break;
					case "yellow":		set_color($id('yellow_color'),'#FFFF00',is_bg_color); break;
					case "gray":		set_color($id('gray_color'),'#909090',is_bg_color); break;
					case "azure":		set_color($id('azure_color'),'#00FFFF',is_bg_color); break;
					case "pink":		set_color($id('pink_color'),'#FF00FF',is_bg_color); break;
					case "purple":		set_color($id('purple_color'),'#703487',is_bg_color); break;
					case "darkblue":	set_color($id('darkblue_color'),'#102075',is_bg_color); break;
					case "brown":		set_color($id('brown_color'),'#654321',is_bg_color); break;
					case "transparent": set_color($id('white_color'),'transparent',true); break;					
					default:
						//any valid color value goes
						if (/^#[0-9A-F]{6}$/i.test(color))
							set_color($id('white_color'),color,is_bg_color);
				}
			}

			
			function set_color(o, o_color, is_bg_color)
			{
				is_bg_color=is_bg_color||false;
				
				if (is_bg_color)
				{
					bg_color=o_color;
					if (o_color=='transparent')
						renderer.setClearColor(0x000000, 0);
					else
						renderer.setClearColor(o_color, 1);
					return;
				}
				
								
				//mesh_color=o.style.background;
				mesh_color=o_color;
				update_mesh_color();
			}
	
			
			function update_mesh_color()
			{
				if (mesh==null) return;
				mesh.material.color.set(parseInt(mesh_color.substr(1),16));
			}
			
			function calc_vol_and_area(geo)
			{
				var x1,x2,x3,y1,y2,y3,z1,z2,z3,i;
				var len=geo.faces.length;
				var totalVolume=0;
				var totalArea=0;
				var a,b,c,s;
				
				for (i=0;i<len;i++)
				{
					x1=geo.vertices[geo.faces[i].a].x;
					y1=geo.vertices[geo.faces[i].a].y;
					z1=geo.vertices[geo.faces[i].a].z;
					x2=geo.vertices[geo.faces[i].b].x;
					y2=geo.vertices[geo.faces[i].b].y;
					z2=geo.vertices[geo.faces[i].b].z;
					x3=geo.vertices[geo.faces[i].c].x;
					y3=geo.vertices[geo.faces[i].c].y;
					z3=geo.vertices[geo.faces[i].c].z;
					
					totalVolume += 
						(-x3 * y2 * z1 + 
						x2 * y3 * z1 +
						x3 * y1 * z2 - 
						x1 * y3 * z2 - 
						x2 * y1 * z3 + 
						x1 * y2 * z3);
						
					a=geo.vertices[geo.faces[i].a].distanceTo(geo.vertices[geo.faces[i].b]);
					b=geo.vertices[geo.faces[i].b].distanceTo(geo.vertices[geo.faces[i].c]);
					c=geo.vertices[geo.faces[i].c].distanceTo(geo.vertices[geo.faces[i].a]);
					s=(a+b+c)/2;
					totalArea+=Math.sqrt(s*(s-a)*(s-b)*(s-c));
				}

				return [Math.abs(totalVolume/6), totalArea];
			}
			
			function numberWithCommas(x)
			{
				var parts = x.toString().split(".");
				parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
				return parts.join(".");
			}

			function set_shading(i)
			{
				if (i==2)
					material.wireframe=true;
				else
				{
					material.wireframe=false;
					material.shading=((i==1)?THREE.SmoothShading:THREE.FlatShading);
					if (mesh!=null)
						mesh.geometry.normalsNeedUpdate = true;
				}
			}
			
			function view_example(ename)
			{
				download_from_local('examples/'+ename, ename);
			}
			
			function reset()
			{
				if (waiting) return;
				switch_view('drag');
				remove_edges();
				if (mesh!=null) {scene.remove(mesh);mesh=null;}
				$id('fileselect').value='';
			}
			
			function prepare_upload_file()
			{
				if (waiting) return;
				if ($id('fileselect').value=='') return;
				
				upload_file($id('fileselect').files[0]);
			}			
			
			function open_img()
			{
				var imgform = document.createElement("form");
				imgform.target = "_blank";    
				imgform.method = "POST";
				imgform.action = "/snap_img/";

				// Create an input
				var imginput = document.createElement("input");
				imginput.type = "text";
				imginput.name = "img_data";
				imginput.value = renderer.domElement.toDataURL("image/png");

				// Add the input to the form
				imgform.appendChild(imginput);

				// Add the form to dom
				document.body.appendChild(imgform);

				// Just submit
				imgform.submit();
			}
			
					</script>
	</head>

	<body>
	
		<input type="file" style="position:absolute; top:-150px;" id="fileselect" onchange="prepare_upload_file();">
	
			
		<div id="mainwrap" style="text-align:center;margin:0 auto;visibility:hidden;">
			<div id="cjcwrap" style="position:relative;display:inline-block;">
				<div id="cjcdrag" style="position:absolute;height:100px;width:350px;text-align:center;">
										<span id="titlet1" style="font:20px Arial;color:#343434;">Drag & drop your STL file here</span><br>
					<span id="titlet2" style="font:14px Arial;color:#3434AB;cursor:pointer;" onclick="if (waiting) return false;$id('fileselect').click();">or <u>browse your computer</u></span>
														</div>
				
				<div id="cjcpbar" style="display:none;position:absolute;height:50px;width:350px;text-align:center;">
					<span id="pgt1" style="font:15px Arial;color:#343478;">Reading file ...</span><br>
					<progress id="file_pbar"  value="0" max="1"></progress><br><span id="fcancel" style="text-decoration:underline;cursor:pointer;" onclick="cancel_download=true;">(cancel)</span>
				</div>

				<div id="cjcproc" style="display:none;position:absolute;height:50px;width:350px;text-align:center;">
					<span id="prt1" style="font:15px Arial;color:#343478;">Processing ...</span><br>
				</div>
				
				<div id="cjc" onmousedown="controls.autoRotate=false;" style="display:none;position:absolute;left:0px;top:0px;margin:0 auto;text-align:center;"></div>
				<a class="vslink" href="http://www.viewstl.com" target="_blank">Viewstl.com</a>			</div>
		
							<div style="display:none;">									<div class="cbar" id="black_color" onclick="set_color(this,'#000000');" style="background:#000000;"></div>
									<div class="cbar" id="white_color" onclick="set_color(this,'#FFFFFF');" style="background:#FFFFFF;"></div>
									<div class="cbar" id="blue_color" onclick="set_color(this,'#0000FF');" style="background:#0000FF;"></div>
									<div class="cbar" id="green_color" onclick="set_color(this,'#00FF00');" style="background:#00FF00;"></div>
									<div class="cbar" id="red_color" onclick="set_color(this,'#FF0000');" style="background:#FF0000;"></div>
									<div class="cbar" id="yellow_color" onclick="set_color(this,'#FFFF00');" style="background:#FFFF00;"></div>
									<div class="cbar" id="gray_color" onclick="set_color(this,'#909090');" style="background:#909090;border:2px solid #012101;"></div>
									<div class="cbar" id="azure_color" onclick="set_color(this,'#00FFFF');" style="background:#00FFFF;"></div>
									<div class="cbar" id="pink_color" onclick="set_color(this,'#FF00FF');" style="background:#FF00FF;"></div>
									<div class="cbar" id="purple_color" onclick="set_color(this,'#703487');" style="background:#703487;"></div>
									<div class="cbar" id="darkblue_color" onclick="set_color(this,'#102075');" style="background:#102075;"></div>
									<div class="cbar" id="brown_color" onclick="set_color(this,'#654321');" style="background:#654321;"></div>
				</div>									
							
						
			
					</div>
		
		
		
		<script>
			var scene = new THREE.Scene();
			var is_webgl=webgl_Detector.webgl;
			var renderer = is_webgl ? new THREE.WebGLRenderer({preserveDrawingBuffer:true, alpha:true}): new THREE.CanvasRenderer();
			var mesh=null;
			var ambientLight     = null;
			var directionalLight = null;
			var pointLight       = null;
			var camera = new THREE.PerspectiveCamera(45, 1, 0.1, 10000);

			do_resize();

			renderer.setClearColor( bg_color, 1);
			$id('cjc').appendChild(renderer.domElement);
			camera.position.set(0,0,100);
			scene.add(camera);			
	
			ambientLight = new THREE.AmbientLight(0x202020);
			camera.add(ambientLight);
			
			directionalLight = new THREE.DirectionalLight(0xffffff, 0.75);
			directionalLight.position.x = 1;
			directionalLight.position.y = 1;
			directionalLight.position.z = 2;
			directionalLight.position.normalize();
			camera.add(directionalLight);
    
			pointLight = new THREE.PointLight(0xffffff, 0.3);
			pointLight.position.x = 0;
			pointLight.position.y = -25;
			pointLight.position.z = 10;
			camera.add(pointLight);			
			
			function animate()
			{
 			    requestAnimationFrame(animate);
 				renderer.render(scene, camera);
				controls.update();
			}			
			
						//handle messaging - relevant to embedded more only
			var info_msg_source=null;
			var info_msg_tag="";
			var photo_msg_source=null;
			var photo_msg_tag="";
			window.onmessage = function(e)
			{
				if (e.data == 'info')
				{
					info_msg_source=e.source;
					
					//if mesh is ready - send info message right now. else, wait until the mesh is ready
					if(mesh!=null)
						post_info_message();
				}
				else if (e.data.hasOwnProperty('msg_type'))
				{
					var msg_type=e.data.msg_type.toLowerCase();
					
					switch (msg_type)
					{
						case 'get_info':
							info_msg_source=e.source;
							info_msg_tag=e.data.hasOwnProperty('tag')?e.data.tag:"";
							//if mesh is ready - send info message right now. else, wait until the mesh is ready
							if(mesh!=null)
								post_info_message();						
							break;
							
						case 'load':
							if (e.data.hasOwnProperty('url'))
								read_from_url(e.data.url);
							else if (e.data.hasOwnProperty('file'))
								upload_file(e.data.file);
							else if (e.data.hasOwnProperty('local_url'))
								download_from_local(e.data.local_url, e.data.local_url, true);
							else
								console.log('no url is defined');
							break;
							
						case 'get_photo':
							photo_msg_source=e.source;
							photo_msg_tag=e.data.hasOwnProperty('tag')?e.data.tag:"";
							//if mesh is ready - send info message right now. else, wait until the mesh is ready
							if (mesh!=null)
								post_photo_message();
							break;
							
						case 'set_shading':
							if (mesh!=null)
							{
								stype=e.data.hasOwnProperty('type')?e.data.type:"flat";
								switch (stype.toLowerCase())
								{
									case "wireframe":
										set_shading(2);
										break;
										
									case "smooth":
										set_shading(1);
										break;
										
									default:
										set_shading(0);
								}
								
							}
							break;
							
						case 'set_color':
							if (mesh!=null)
								set_color_by_name(e.data.hasOwnProperty('value')?e.data.value:"");
							break;
								
						case 'set_bgcolor':
							if (mesh!=null)
								set_color_by_name(e.data.hasOwnProperty('value')?e.data.value:"", true);
							break;
							
						case 'set_orientation':
							set_orientation(e.data.hasOwnProperty('value')?e.data.value:"");
							break;
							
						case 'set_edges':
							if (mesh!=null)
								set_edges(e.data.hasOwnProperty('value')?(e.data.value.toLowerCase()=="yes"):false);
							break;							
					}
					
				}				
			};			
			
			function post_info_message()
			{
				if (info_msg_source==null) return;
				info_msg_source.postMessage({msg_type:'info', tag:info_msg_tag, filename:model_filename, volume:vol, x:xsize, y:ysize, z:zsize, color:mesh_color, triangles:triangles_count, area:area}, '*');
			}
			
			function post_photo_message()
			{
				if (photo_msg_source==null) return;
				
				photo_msg_source.postMessage({msg_type:'photo', tag:photo_msg_tag, img_data:renderer.domElement.toDataURL()}, '*');
			}
			
						
			//this function is being called from outside (from embedded page)
			function set_rotation(b)
			{
				controls.autoRotate=b;
				animate();
			}
			
			//this function is being called from outside (from embedded page)
			function set_clean_mode(b)
			{
				$id('titlet1').style.display=b?'none':'inline';
				$id('titlet2').style.display=b?'none':'inline';
				$id('pgt1').style.display=b?'none':'inline';
				$id('file_pbar').style.display=b?'none':'inline';
				$id('fcancel').style.display=b?'none':'inline';
				$id('prt1').style.display=b?'none':'inline';
			}
			
			//this function is being called from outside (from embedded page)
			function set_noborder(b)
			{
				$id('cjcwrap').style.border=(b?'none':'1px dashed #000000');
			}				
			
			var controls = new THREE.OrbitControls(camera, renderer.domElement);
			
			if (is_webgl)
				controls.autoRotate=true;
			else
			{
								controls.autoRotate=false;
			}
			
			animate();
			
			window.onresize=
			function()
			{
				do_resize();
			}

			$id('cjcwrap').addEventListener('dragover', handleDragOver, false);
			$id('cjcwrap').addEventListener('drop', handleFileDrop, false);
		</script>
		
		
		<script>
			if (need_browser)
				alert('Your browser is too old and is not supported by this website. Please install a modern browser (Chrome is recommended).');
		</script>
		
		<?php /** from here onwards are options which are originally interpreted with php it seems**/?>
		<script>
			switch_view('pbar');
			url_is_local=true;
			<?php $tempurl = $_GET['url'];?>
			read_from_url("<?php echo $tempurl; ?>");
		</script>
						
		<script>
			set_shading(0);
		</script>
						
		<script>
			controls.autoRotate=false;
		</script>
							
		
		<script>$id('white_color').onclick();</script>
								
		<script>set_color_by_name('black', true);</script>
								
		<script>set_orientation('bottom');</script>
		
		<script>setTimeout(function(){$id('mainwrap').style.visibility='visible';}, 200);</script>
		
	</body>
</html>