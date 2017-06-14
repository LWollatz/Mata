/**** Multiresolution Computed Tomography Viewer (MCTV) ****
*Contributions:
 * Bug fixes                                                 by Lasse Wollatz; L.Wollatz@soton.ac.uk
 *   - ensured images that are not a multiple of 256 in 
 *     height display correctly                                                May, 2017;
 *Contributions:
 * Further enhancements and cleaning of code                 by Lasse Wollatz; October, 2016; L.Wollatz@soton.ac.uk
 *   - improved error handling
 *   - error bar added for feedback
 *   - automatic adjustment of units added
 *   - outsourced styles into css
 *   - improved styles and backward compatibility
 * Bug fixes, 3D mods, threshold and enhancements written    by Lasse Wollatz; February, 2015; L.Wollatz@soton.ac.uk
 *                                                              Publication: L. Wollatz, S. J. Cox, and S. J. Johnston, (2015) Web-Based Manipulation of Multiresolution Micro-CT Images. e-Science 2015.
 *   - added tile loading
 *   - corrected placement of tiles
 *   - optimized tile loading to reduce delay
 *   - threshold modifications functionality added
 *   - zoom "underlay" to improve user experience
 *   - non-tiled zoom added
 *   - measuring functionality added
 *   - resolution and CT data loading from JSON
 *   - improved styles
 *   - added animations
 *   - sliders added for more feedback and improved navigation
 *   - cross-sectional thumbnail added
 *   - display and event handling of thumbnails corrected and enhanced
 * iPhone/iPad modifications written                         by Matthew K. Lindley; August 25, 2010
 * base code (MIV)                                           by Shawn Mikula; 2007; brainmaps@gmail.com.
 *                                                              Publication: S. Mikula, I. Trotts, J. M. Stone, and E. G. Jones, (2007) Internet-Enabled High-Resolution Brain Mapping and Virtual Microscopy. NeuroImage 35 (1): 9-15. 
 *                                                              (Updated version of his script available at http://www.connectomes.org.)
 *   - main html structure
 *   - tile placement
 *   - basic zoom
 *   - basic navigation
 *
 *You are free to use this software for non-commercial 
 *use only, and only if proper credit is clearly visibly
 *given wherever the code is used.
 ************************************************************/


var imgtype = ".jpg";           //filetype of image files, can be jpg,png,bmp should also work with gif NOT tif or dicom!
var tileSize = 256;             //size of a tile in pixel
/*variables that can be set through the url*/
//root                          //path to infoJson.txt -> requires either root or JSON!
//JSON                          //or directly specify the full path -> requires either root or JSON!
//start                         //number of first slice to display -> defaults to 1/3 of image stack
//vX                            //X position to center as fraction of the image width -> defaults to 0.5
//vY                            //Y position to center as fraction of the image height -> defaults to 0.5
//vT                            //initial zoom level -> defaults to maximum normal zoom
//coords                        //0 if no position information should be displayed -> defaults to 1
//width                         //declare image width if not defined in JSON
//height                        //declare image height if not defined in JSON
//res                           //declare image resolution if not defined in JSON
//zres                          //declare image z-resolution if not defined in JSON
//resunits                      //declare image resolution units if not defined in JSON
/*JSON*/
var JSONout;                    //content of JSON file
var getJSON = false;            //has JSON been requested?
var loadedJSON = false;         //has JSON file been loaded?
var JSON;                       //full path of JSON file including filename
var rootpath;                   //path where to check for infoJSON.txt
var labelspath;                 //full path for labels JSON file for current tile
/*positioning and sizing*/
var viewportWidth = 2000;       //width of viewable area in browser
var viewportHeight = 1000;      //height of viewable area in browser
var innerDiv;                   //innerDiv html element
var mTop;                       //current top position of image relative to viewport
var mLeft;                      //current left position of image relative to viewport
var gImageWidth, gImageHeight;  //width and height of image
var width, height;              //width and height of specific slice images
/*resolution/dimension*/
var resunits = "px";            //units of the resolution factors
var res = 1.0;                  //size of 1 pixel (resunits/px)
var zres = 1.0;                 //thickness of 1 slice (resunits/slice)
var JSONnum;                    //number of images (z dimension size)
/*zoom*/
var xtrazoomMax = 5;            //maximal additional zoom beyond actual size of the image
var gTierCount;                 //number of zoom levels due to tiling
var zoom = 0;                   //current zoom level
var xtrazoom = 1;               //current extra zoom beyond actual size of image
/*threshold*/
var thresLower;                 //Lower Threshold
var thresUpper;                 //Upper Threshold
var densmin = -1000;            //HU of minimum value (0)
var densmax = 1000;             //HU of maximum value (255)
var densunit = "N/A";           //density unit (e.g. HU)
/*mouse movement*/
var clickmode = 0;              //mode for mouse click/ drag
var lasteventX = 0;             //X coordiante from last known mouse movement
var lasteventY = 0;             //Y coordiante from last known mouse movement
var dragStartTop;               //Y coordiante from mouse down event
var dragStartLeft;              //X coordiante from mouse down event
var dragging = false;           //if mouse-drag and mouse-click set to pan
var measuring = false;          //if mouse-drag and mouse-click set to measure
/*mouse wheel*/
var wheelmode = 0;              //mode for mousewheel movement
var wheelobs = 0;               //count of mousewheel operations
/*touch*/
var touchIdentifier;
var gestureScale = 1;
/*code for checking performance*/
// try {
//  var Tstart = performance.now();
// } catch (err) {
//  var Tstart = new Date().getTime();
// }
///try {
//  var Tend = performance.now();
// } catch (err) {
//  var Tend = new Date().getTime();
// }
// try {
//  var TendL = performance.now();
// } catch (err) {
//  var TendL = new Date().getTime();
// }
// try {
//   var TendR = performance.now();
// } catch (err) {
//   var TendR = new Date().getTime();
// }
/*others*/
var path = '/';                 //path to specific slice images
var imgpath = '';               //path to specific tile
var slidePointer = 0;           //current image number (z dimension position)
var start;                      //first slice to load
var coords = 1;                 //if coordinates are displayed
var ActiveTask;                 //taskid of currently active task
var isControl = false;          //boolean if key controls are being displayed
var spaddingX = 20;             //horizontal artificial padding for slider tables


/*** BASICS ***
 * logError(errstr)
 * removeError(errstr)
 * isNumeric(n)
 * engUnit(number,unit)
 * stripPx(value)
 * getVar(name)
 */
function logError(errstr) { 
/*adds an error to the list of errors
 *if Error already exists, a counter is used to save space
 */
    var a, b, c;
    var remStr ="";
    var ecntr = 2;
    try {
        var allErrStr = document.getElementById('error').innerHTML;
        a = allErrStr.toLowerCase().indexOf(errstr.toLowerCase());
        if (a < 0) {
            document.getElementById('error').innerHTML += errstr + "<br/>";
        } else {
            b = allErrStr.toLowerCase().indexOf("<br", a);
            var remStr = allErrStr.substring(a, b + 5);
            c = allErrStr.toLowerCase().indexOf(" x", b - 11);
            if (c > 0 && c < b) {
                ecntr = parseFloat(allErrStr.substring(c + 2, b));
                ecntr += 1;
            }
            allErrStr = allErrStr.replace(remStr, errstr + " x" + ecntr + "<br/>");
            document.getElementById('error').innerHTML = allErrStr;
        }
    } catch (err) {}
}

function removeError(errstr) { 
/*removes an error from the list of errors
 *if Error existed multiple times, all entrys are removed
 *only the first part of the error message needs to be provided, 
 *  useful if part of the error message is variable
 */
    try {
        var allErrStr = document.getElementById('error').innerHTML;
        if(allErrStr != ""){
            var a = allErrStr.indexOf(errstr);
            if (a >= 0) {
                var b = allErrStr.indexOf("<br", a + 1);
                var remStr = allErrStr.substring(a, b + 5);
                allErrStr = allErrStr.replace(remStr, "");
                document.getElementById('error').innerHTML = allErrStr;
            }
        }
    } catch (err) {
        logError("Failed to remove Error: " + err);
    }
}

function isNumeric(n) { 
/*checks if value is a number
 */
    return !isNaN(parseFloat(n)) && isFinite(n);
}

function engUnit(number,unit){ 
/*converts number and unit into human readable string
 *unit has to be a metric unit for length as a string (nm to km)
 *any other unit will default to meter
 *unit will be changed if number is smaller than 0.1 or has more than 3 digits.
 */
    var value = parseFloat(number);
    var unitlevel = 0;
    if (Math.abs(value) >= 0.1 && Math.abs(value) < 100){
        return Math.round(value*100)/100 + " " + unit;
    }
    //detect zeros of unit
    if (unit === "nm"){
        unitlevel = -9;
    }else if (unit === "&mu;m"){
        unitlevel = -6;
    }else if (unit === "mm"){
        unitlevel = -3;
    }else if (unit === "cm"){
        unitlevel = -2;
    }else if (unit === "dm"){
        unitlevel = -1;
    }else if (unit === "km"){
        unitlevel = 3;
    }else if (unit === "px"){
        unitlevel = 0;
    }
    //get to next best unit
    while(Math.abs(value) < 0.1 && unitlevel > -9){
        value = value*10;
        unitlevel -= 1;
    }
    while(Math.abs(value) >= 100  && unitlevel < 3){
        value = value/10;
        unitlevel += 1;
    }
    if (unitlevel == 2 || unitlevel == -4 || unitlevel == -7){
        value = value/10;
        unitlevel += 1;
    }else if (unitlevel == 1 || unitlevel == -5 || unitlevel == -8){
        value = value*10;
        unitlevel -= 1;
    }
    //return string representation
	if (unit != "px"){
		if (unitlevel == -9){
			unit = "nm";
		}else if (unitlevel == -6){
			unit = "&mu;m";
		}else if (unitlevel == -3){
			unit = "mm";
		}else if (unitlevel == -2){
			unit = "cm";
		}else if (unitlevel == -1){
			unit = "dm";
		}else if (unitlevel == 0){
			unit = "m";
		}else if (unitlevel == 3){
			unit = "km";
		}
	}else{
		if (unitlevel == -9){
			unit = "e-9 px";
		}else if (unitlevel == -6){
			unit = "e-6 px";
		}else if (unitlevel == -3){
			unit = "e-3 px";
		}else if (unitlevel == 0){
			unit = "px";
		}else if (unitlevel == 3){
			unit = "e+3 px";
		}
	}
    return Math.round(value*100)/100 + " " + unit;
}

function stripPx(value) { 
/*converts pixel string into float
 * therefore '128px' -> 128.0
 */
    if (value === "") {
        return 0;
    }
    return parseFloat(value.substring(0, value.length - 2));
}

function getVar(name) { 
/*reads a variable from the URL
 */
    var get_string = document.location.search;
    var return_value = '';
    var name_index = 0;
    var end_of_value = -1;
    var value = '';
    do {
        name_index = get_string.indexOf(name + '=');
        if (name_index !== -1) {
            get_string = get_string.substr(name_index + name.length + 1, get_string.length - name_index);
            end_of_value = get_string.indexOf('&');
            if (end_of_value !== -1) {
                value = get_string.substr(0, end_of_value);
            } else {
                value = get_string;
            }
            if (return_value === '' || value === '') {
                return_value += value;
            } else {
                return_value += ', ' + value;
            }
        }
    } while (name_index !== -1);
    var space = return_value.indexOf('+');
    while (space !== -1) {
        return_value = return_value.substr(0, space) + ' ' + return_value.substr(space + 1, return_value.length);
        space = return_value.indexOf('+');
    }
    return (return_value);
}
/*** END BASICS ***/


/*** TILE HANDLER ***
 * getVisibleTiles()
 * createTile(pCol, pRow, tileSize, tileName, staticPath)
 * displayTile(pCol, pRow, tileName)
 * checkTiles(isForced)
 * refreshTiles()
 * refreshUnderlay()
 */
function getVisibleTiles() { 
/*get x and y of all tiles that are in the visible area
 *units of x and y are in tiles (starting at 0)
 *Note: upper bound is not defined here but only within the checkTiles function
 *      => wouldn't it be more sensible to check it here?
 */
    innerDiv = document.getElementById("innerDiv");
    //need to get an absolute function here!
    var mapX = stripPx(innerDiv.style.left);
    var mapY = stripPx(innerDiv.style.top);
    //changed from abs function to -1*.
    //this way only the tiles on screen +-neighbours tiles are loaded.
    var neighbours = 1; //minimum required is 1!
    var startX = -1 * Math.floor(mapX / (tileSize * xtrazoom)) - neighbours;
    var startY = -1 * Math.floor(mapY / (tileSize * xtrazoom)) - neighbours;
    //+neighbours and not +neighbours+1, as the start of the image is recorded...
    var tilesX = Math.ceil(viewportWidth / (tileSize * xtrazoom)) + neighbours;
    var tilesY = Math.ceil(viewportHeight / (tileSize * xtrazoom)) + neighbours;
    var visibleTileArray = [];
    var counter = 0;
    var x, y;
    for (x = startX; x < (tilesX + startX); x++) {
        for (y = startY; y < (tilesY + startY); y++) {
            //need to add upper bound here (number of available tiles in each direction is???)
            if (x >= 0 && y >= 0) {
                visibleTileArray[counter++] = [x, y];
            }
        }
    }
    return visibleTileArray;
}

function createTile(pCol, pRow, tileSize, tileName, staticPath) { 
/*function called if tile is not yet loaded
 */
    // console.timeStamp(tileName + " Start");
    var image = document.createElement("img");
    image.src = staticPath + tileName;
    image.setAttribute("id", tileName);
    image.style.opacity = 0;
    image.style.zIndex = -2;
    //start threshold
    //needed to place in onload to avoid threshold images before they are loaded
    image.onload = function () {
        var i;
        var imgstr;
        var v = 0;
        var imgid = "";
        // try {
        //   TendL = performance.now();
        // } catch (err) {
        //   TendL = new Date().getTime();
        // }
        // var time = TendL - Tstart;
        // document.getElementById('debug2').innerHTML = 'LT: ' + time;
        var c = document.createElement("canvas");
        var thresLowerVal = parseInt(255 * (thresLower - densmin) / (densmax - densmin), 10);
        var thresUpperVal = parseInt(255 * (thresUpper - densmin) / (densmax - densmin), 10);
        c.width = image.width;
        c.height = image.height;
        try{
            var ctx = c.getContext('2d');
            ctx.drawImage(image, 0, 0);
            var idata = ctx.getImageData(0, 0, c.width, c.height);
            var d = idata.data;
            for (i = 0; i < d.length; i += 4) {
                var r = d[i];
                v = parseInt(255 * (r - thresLowerVal) / (thresUpperVal - thresLowerVal), 10);
                v = (v >= 255) ? 255 : v;
                v = (v <= 0) ? 0 : v;
                d[i] = d[i + 1] = d[i + 2] = v;
                d[i + 3] = 255;
            }
            ctx.putImageData(idata, 0, 0);
            imgstr = c.toDataURL("image/png");
            removeError("Failed to apply threshold");
        }catch(err){
            logError("Failed to apply threshold: "+err);
            imgstr = image.src;
        }
        //reset the onload function, to avoid recursive threshold
        image.onload = function () {
            image.style.zIndex = 0;
            image.style.display = "block";
            imgid = tileName.replace(".", "\\.");
            $("#" + imgid + "").finish();
            $("#" + imgid + "").animate({
                opacity : 1
            }, 600);
            // console.timeStamp(tileName + " End");
            // try {
            //   Tend = performance.now();
            // }  catch (err) {
            //   Tend = new Date().getTime();
            // }
            // var time = Tend - Tstart;
            // document.getElementById('debug').innerHTML = 'ET: ' + time;
        };
		image.style.height = (c.height * xtrazoom) + "px";
		image.style.width = "auto";
        image.src = imgstr;
    };
    //end threshold
    var brighness = 50;
    image.style.position = "absolute";
    image.style.left = (pCol * tileSize * xtrazoom) + "px";
    image.style.top = (pRow * tileSize * xtrazoom) + "px";
    var imageTiles = document.getElementById("imageTiles");
    imageTiles.appendChild(image);

}

function displayTile(pCol, pRow, tileName) { 
/*called if tile is loaded already but needs to be updated
 */
    var image = document.getElementById(tileName);
    var imgid = tileName.replace(".", "\\.");
    $("#" + imgid + "").finish();
    $("#" + imgid + "").animate({
        opacity : 0
    }, 2);
    image.style.zIndex = -2;
    image.style.height = "";
    image.src = imgpath + tileName;
    //start threshold
    image.onload = function () {
        var i;
        var r, v;
        var imgstr;
        // try {
        //   TendL = performance.now();
        // } catch (err) {
        //  TendL = new Date().getTime();
        // }
        // var time = TendL - Tstart;
        // document.getElementById('debug2').innerHTML = 'LT: ' + time;
        var c = document.createElement("canvas");
        c.width = image.width;
        c.height = image.height;
        var thresLowerVal = parseInt(255 * (thresLower - densmin) / (densmax - densmin), 10);
        var thresUpperVal = parseInt(255 * (thresUpper - densmin) / (densmax - densmin), 10);
        try{
            var ctx = c.getContext('2d');
            ctx.drawImage(image, 0, 0);
            var idata = ctx.getImageData(0, 0, c.width, c.height);
            var d = idata.data;
            for (i = 0; i < d.length; i += 4) {
                r = d[i];
                v = parseInt(255 * (r - thresLowerVal) / (thresUpperVal - thresLowerVal), 10);
                v = (v >= 255) ? 255 : v;
                v = (v <= 0) ? 0 : v;
                d[i] = d[i + 1] = d[i + 2] = v;
                d[i + 3] = 255;
            }
            ctx.putImageData(idata, 0, 0);
            imgstr = c.toDataURL("image/png");
            removeError("Failed to apply threshold");
        }catch(err){
            logError("Failed to apply threshold: "+err);
            imgstr = image.src;
        }
        //reset the onload function, to avoid recursive threshold
        image.onload = function () {
            image.style.display = "block";
            image.style.zIndex = 0;
            imgid = tileName.replace(".", "\\.");
            $("#" + imgid + "").stop();
            $("#" + imgid + "").animate({
                opacity : 1
            }, 600);
            // try {
            //   Tend = performance.now();
            // } catch (err) {
            //   Tend = new Date().getTime();
            // }
            // var time = Tend - Tstart;
            // document.getElementById('debug').innerHTML = 'ET: ' + time;
        };
		image.style.height = (c.height * xtrazoom) + "px";
		image.style.width = "auto";
        image.src = imgstr;
        
    };
    //end threshold
}

function checkTiles(isForced) { 
/*goes through the tiles and updates them as necessary.
 *Updates every tile, if isForced
 */
    // console.timeStamp("checkTiles Start");
    // document.getElementById('debug').innerHTML = "";
    //static path stuff didn't work - but shouldn't be necessary any longer
    var staticPath = imgpath.substring(0);
    innerDiv = document.getElementById("innerDiv");
    var imageTiles = document.getElementById("imageTiles");
    var visibleTiles = getVisibleTiles();
    var tileArray = visibleTiles[0];
    var visibleTilesMap = {};
    var gTileCountWidth = new Array();
    var gTileCountHeight = new Array();
    var tempWidth = gImageWidth;
    var tempHeight = gImageHeight;
    
    var divider = Math.pow(2, (gTierCount - zoom - 1)) / xtrazoom;
    var j;
    var i = 0;
    while (i < visibleTiles.length) {
        tileArray = visibleTiles[i];
        gTileCountWidth = new Array();
        gTileCountHeight = new Array();
        tempWidth = gImageWidth;
        tempHeight = gImageHeight;
        divider = 2;
        //do I need to compute this for all zoom levels here?
        for (j = gTierCount - 1; j >= 0; j--) {
            gTileCountWidth[j] = Math.floor(tempWidth / (tileSize));
            if (tempWidth % (tileSize)) {
                gTileCountWidth[j]++;
            }
            gTileCountHeight[j] = Math.floor(tempHeight / (tileSize));
            if (tempHeight % (tileSize)) {
                gTileCountHeight[j]++;
            }
            tempWidth = Math.floor(gImageWidth / divider);
            tempHeight = Math.floor(gImageHeight / divider);
            divider *= 2;
            if (tempWidth % 2){tempWidth++;}
            if (tempHeight % 2){tempHeight++;}
        }
        
        moveThumb2();
        var pCol = tileArray[0];
        var pRow = tileArray[1];
        var tier = zoom;

        //why do I check this here? else -> repeat image which is already loaded?
        if (pCol < gTileCountWidth[zoom] && pRow < gTileCountHeight[zoom]) {
            var tileName = zoom + "-" + pCol + "-" + pRow + imgtype;

            visibleTilesMap[tileName] = true;
            var img = document.getElementById(tileName);
            if (!img) {
                //try and catch should no longer be necessary
                try {
                    createTile(pCol, pRow, tileSize, tileName, staticPath);
                    removeError("<b>Failed to create tile " + tileName + ":</b>");
                } catch (err) {
                    var image = document.createElement("img");
                    image.src = staticPath + tileName;
                    image.setAttribute("id", tileName);
                    image.style.position = "absolute";
                    image.style.left = (pCol * tileSize * xtrazoom) + "px";
                    image.style.top = (pRow * tileSize * xtrazoom) + "px";
                    image.style.zIndex = 0;
                    imageTiles.appendChild(image);
                    logError("<b>Failed to create tile " + tileName + ":</b> " + err);
                }
            } else if (isForced) {
                displayTile(pCol, pRow, tileName);
            }
        }
        i++;

    }
    
    var imgs = imageTiles.getElementsByTagName("img");
    for (i = 0; i < imgs.length; i++) {
        var id = imgs[i].getAttribute("id");
        if (!visibleTilesMap[id] && id !== "mainTile") {
            imageTiles.removeChild(imgs[i]);
            i--;
        }
    }

    // try {
    //  TendR = performance.now();
    // } catch (err) {
    //  TendR = new Date().getTime();
    // }
    // var time = TendR - Tstart;
    // document.getElementById('debug3').innerHTML = 'TTR: ' + time;
}

function refreshTiles() { 
/*remove all tiles so that a reload is forced.
 */
    //refresh main tile
    var mainTile = document.getElementById("mainTile");
    var divider = Math.pow(2, (gTierCount - zoom - 1)) / xtrazoom;
    var MTwidth = (gImageWidth / divider) + "px";
    $("#mainTile").finish();
    $("#mainTile").animate({
        width : MTwidth
    }, 300, "swing");
    //remove other tiles
    var imageTiles = document.getElementById("imageTiles");
    var imgs = imageTiles.getElementsByTagName("img");
    while (imgs.length > 1) {
        if (imgs[0].id != "mainTile") {
            imageTiles.removeChild(imgs[0]);
        } else {
            imageTiles.removeChild(imgs[1]);
        }
    }
}

function refreshUnderlay() { 
/*update the underlying (bad-resolution) image
 */
    var temp = document.createElement("img");
    var mainTile = document.getElementById("mainTile");
    var timg = document.getElementById('timg');
    //start threshold
    temp.onload = function () {
        var i;
        var r, v;
        try{
            var c = document.createElement("canvas");
            c.width = timg.width;
            c.height = timg.height;
            var thresLowerVal = parseInt(255 * (thresLower - densmin) / (densmax - densmin), 10);
            var thresUpperVal = parseInt(255 * (thresUpper - densmin) / (densmax - densmin), 10);
            var ctx = c.getContext('2d');
            ctx.drawImage(timg, 0, 0);
            var idata = ctx.getImageData(0, 0, c.width, c.height);
            var d = idata.data;
            for (i = 0; i < d.length; i += 4) {
                r = d[i];
                v = parseInt(255 * (r - thresLowerVal) / (thresUpperVal - thresLowerVal), 10);
                v = (v >= 255) ? 255 : v;
                v = (v <= 0) ? 0 : v;
                d[i] = d[i + 1] = d[i + 2] = v;
                d[i + 3] = 255;
            }
            ctx.putImageData(idata, 0, 0);
            var imgstr = c.toDataURL("image/png");
            mainTile.onload = function () {
                var divider = Math.pow(2, (gTierCount - zoom - 1)) / xtrazoom;
                mainTile.style.width = gImageWidth / divider + "px";
            };
            mainTile.src = imgstr;
            removeError("Failed to apply threshold:");
        }catch(err){
            //failed to apply filter to image
            logError("Failed to apply threshold: "+err);
            mainTile.src = timg.src;
        }
    };
    //end threshold
    temp.src = imgpath + '0-0-0' + imgtype;
}
/*** END TILE HANDLER ***/


/*** ZOOM ***
 * zoomIn()
 * zoomOut()
 * updateZoom()
 * clickZoom(event)
 */
function zoomIn() { 
/*zooms in 1 level
 */
    var IDtop, IDleft;
    if (zoom !== gTierCount - 1) {
        //normal zoom through tiles
        $("#innerDiv").finish();
        innerDiv = document.getElementById("innerDiv");
        mTop = stripPx(innerDiv.style.top);
        mLeft = stripPx(innerDiv.style.left);
        IDtop = 2 * mTop - viewportHeight / 2 + 'px';
        IDleft = 2 * mLeft - viewportWidth / 2 + 'px';
        zoom = zoom + 1;
        $("#innerDiv").animate({
            top : IDtop,
            left : IDleft
        }, {
            duration : 300,
            complete : function () {
                checkTiles(1);
            }
        });
        refreshTiles();
        var imageLabels = document.getElementById("imageLabels");
        var divs = imageLabels.getElementsByTagName("div");
        for (var $i = 0; $i < divs.length; $i++) {
            var Ltemp = "L" + $i;
            $("#" + Ltemp + "").finish();
            IDtop = 2 * stripPx(document.getElementById(Ltemp).style.top) + 'px';
            IDleft = 2 * stripPx(document.getElementById(Ltemp).style.left) + 'px';
            $("#" + Ltemp + "").animate({
                top : IDtop,
                left : IDleft
            }, 300, "swing");
        }
        clickMode1()
    } else if (xtrazoom < xtrazoomMax) {
        //extra zoom beyond image resolution
        zoomdif = xtrazoom;
        xtrazoom = xtrazoom + 1;
        zoomdif = xtrazoom / zoomdif;
        $("#innerDiv").finish();
        innerDiv = document.getElementById("innerDiv");
        mTop = stripPx(innerDiv.style.top);
        mLeft = stripPx(innerDiv.style.left);
        IDtop = 0.5 * (1 - zoomdif) * viewportHeight + zoomdif * mTop + 'px';
        IDleft = 0.5 * (1 - zoomdif) * viewportWidth + zoomdif * mLeft + 'px';
        $("#innerDiv").animate({
            top : IDtop,
            left : IDleft
        }, {
            duration : 300,
            complete : function () {
                checkTiles(0);
            }
        });
        var imageLabels = document.getElementById("imageLabels");
        var divs = imageLabels.getElementsByTagName("div");
        for (var $i = 0; $i < divs.length; $i++) {
            var Ltemp = "L" + $i;
            $("#" + Ltemp + "").finish();
            IDtop = zoomdif * stripPx(document.getElementById(Ltemp).style.top) + 'px';
            IDleft = zoomdif * stripPx(document.getElementById(Ltemp).style.left) + 'px';
            $("#" + Ltemp + "").animate({
                top : IDtop,
                left : IDleft
            }, 300, "swing");
        }
        clickMode1()
        refreshTiles();
        //checkTiles(0);
    }
    updateZoom();
}

function zoomOut() { 
/*zooms out 1 level
 */
    if (xtrazoom > 1) {
        zoomdif = xtrazoom;
        xtrazoom = xtrazoom - 1;
        zoomdif = xtrazoom / zoomdif;
        $("#innerDiv").finish();
        innerDiv = document.getElementById("innerDiv");
        mTop = stripPx(innerDiv.style.top);
        mLeft = stripPx(innerDiv.style.left);
        var IDtop = 0.5 * (1 - zoomdif) * viewportHeight + zoomdif * mTop + 'px';
        var IDleft = 0.5 * (1 - zoomdif) * viewportWidth + zoomdif * mLeft + 'px';
        $("#innerDiv").animate({
            top : IDtop,
            left : IDleft
        }, {
            duration : 300,
            complete : function () {
                checkTiles(0);
            }
        });
        var imageLabels = document.getElementById("imageLabels");
        var divs = imageLabels.getElementsByTagName("div");
        for (var $i = 0; $i < divs.length; $i++) {
            var Ltemp = "L" + $i;
            $("#" + Ltemp + "").finish();
            IDtop = zoomdif * stripPx(document.getElementById(Ltemp).style.top) + 'px';
            IDleft = zoomdif * stripPx(document.getElementById(Ltemp).style.left) + 'px';
            $("#" + Ltemp + "").animate({
                top : IDtop,
                left : IDleft
            }, 300, "swing");
        }
        clickMode1()
        refreshTiles();
        //checkTiles(0);
    } else if (zoom != 0) {
        $("#innerDiv").finish();
        var innerDiv = document.getElementById("innerDiv");
        mTop = stripPx(innerDiv.style.top);
        mLeft = stripPx(innerDiv.style.left);
        var IDtop = mTop / 2 + viewportHeight / 4 + 'px';
        var IDleft = mLeft / 2 + viewportWidth / 4 + 'px';
        $("#innerDiv").animate({
            top : IDtop,
            left : IDleft
        }, {
            duration : 300,
            complete : function () {
                checkTiles(1);
            }
        });
        zoom = zoom - 1;
        refreshTiles();
        var imageLabels = document.getElementById('imageLabels');
        var divs = imageLabels.getElementsByTagName("div");
        for (var $i = 0; $i < divs.length; $i++) {
            var Ltemp = "L" + $i;
            $("#" + Ltemp + "").finish();
            IDtop = .5 * stripPx(document.getElementById(Ltemp).style.top) + 'px';
            IDleft = .5 * stripPx(document.getElementById(Ltemp).style.left) + 'px';
            $("#" + Ltemp + "").animate({
                top : IDtop,
                left : IDleft
            }, 300, "swing");
        }
        clickMode1()
        //checkTiles(1);
    }
    updateZoom();

}

function updateZoom() { 
/*updates the zoom slider
 */
    var zoomsliderdiv;
    var zwidth = 150;
    var zheight = 10;
    var zpaddingY = 5;
    var zpaddingX = 20;
    var activewidth = Math.max(Math.round((zwidth * (zoom) / (gTierCount + xtrazoomMax - 2)) - 5), 0);
    var active2width = Math.max(Math.round((zwidth * (xtrazoom - 1) / (gTierCount + xtrazoomMax - 2))), 0);
    var inactivewidth = Math.max(Math.round((zwidth - zwidth * (zoom + xtrazoom - 1) / (gTierCount + xtrazoomMax - 2)) - 5), 0);
    
    //Create table for zoom slider
    var zoomstr = '<table id="zoomslidertable">';
    zoomstr += '<tr height="' + zheight + 'px">';
    zoomstr += '<td width="' + zpaddingX + 'px" height="' + zheight + 'px"></td>';
    if (activewidth) {
        zoomstr += '<td class="slider active" width="' + activewidth + 'px" height="' + zheight + 'px">';
        zoomstr += '</td>';
    } else {
        inactivewidth -= 5;
    }
    if (active2width) {
        zoomstr += '<td class="slider active2" width="' + active2width + 'px" height="' + zheight + 'px">';
        zoomstr += '</td>';
    }
    zoomstr += '<td class="slider knob tooltip" width="' + 10 + 'px" height="' + zheight + 'px" title="' + (zoom + xtrazoom - 1) + "/" + (gTierCount + xtrazoomMax - 2) + '"></td>';
    if (inactivewidth) {
        zoomstr += '<td class="slider inactive" width="' + inactivewidth + 'px" height="' + zheight + 'px">';
        zoomstr += '</td>';
    }
    zoomstr += '<td width="' + zpaddingX + 'px" height="' + zheight + 'px"></td></tr></table>';

    zoomsliderdiv = document.getElementById('zoomslider');
    zoomsliderdiv.innerHTML = zoomstr
    zoomsliderdiv.onmouseup = clickZoom;
    
    //update the small ruler shown at the bottom left
    document.getElementById('theScale').innerHTML = engUnit((Math.pow(2, gTierCount - zoom - 1) / (xtrazoom)) * res * 50,resunits);
}

function clickZoom(event) { 
/*move to position clicked on zoom slider
 */
    if (event) {
        xThumb = event.clientX;
        yThumb = event.clientY;
        var sliderLeft = document.getElementById("zoomslider").getBoundingClientRect().left;
        var sliderWidth = document.getElementById("zoomslider").getBoundingClientRect().right - sliderLeft;
        var perc = Math.min(Math.abs(xThumb - sliderLeft - spaddingX) / (sliderWidth - 2 * spaddingX), 1);
        totzoom = Math.round(0 + (gTierCount + xtrazoomMax - 2) * perc);
        newzoom = Math.max(Math.min(gTierCount - 1, totzoom), 0);
        newxtrazoom = Math.min(Math.max(1, totzoom + 1 - newzoom), xtrazoomMax);
        totzoom = newzoom + newxtrazoom - 1;
        oldzoom = zoom + xtrazoom - 1;
        if (oldzoom < totzoom) {
            for (j = oldzoom; j < totzoom; j++) {
                zoomIn();
            }
        } else if (oldzoom > totzoom) {
            for (j = oldzoom; j > totzoom; j--) {
                zoomOut();
            }
        }
    }
}
/*** END ZOOM ***/


/*** DENSITY RANGE (THRESHOLD) ***
 * updateThreshold()
 * clickThreshold(event)
 */
function updateThreshold() { 
/*refreshes the Threshold slider
 */
    var thressliderdiv;
    var totmin = Math.round(densmin);
    var totmax = Math.round(densmax);
    var swidth = 145;

    var inactive1width = Math.max(Math.round((swidth * (thresLower - totmin) / (totmax - totmin)) - 5), 0);
    var inactive2width = Math.max(Math.round((swidth * (totmax - thresUpper) / (totmax - totmin)) - 5), 0);
    var activewidth = Math.max(Math.round((swidth - swidth * (thresLower - totmin + totmax - thresUpper) / (totmax - totmin)) - 10), 0);

    //Creating table for threshold double slider
    var thresstr = '<table id="thresslidertable"><tr><td width="' + spaddingX + 'px"></td>';
    if (inactive1width) {
        thresstr += '<td class="slider inactive" width="' + inactive1width + 'px" height="1px">';
        thresstr += '</td>';
    }
    thresstr += '<td class="slider knob tooltip" width="10px"  height="10px" title="' + thresLower + '"></td>';
    if (activewidth) {
        thresstr += '<td class="slider active"  title="[' + thresLower + "," + thresUpper + ']" width="' + activewidth + 'px" height="1px">';
        thresstr += '</td>';
    }
    thresstr += '<td class="slider knob tooltip" width="10px"  height="10px" title="' + thresUpper + '"></td>';
    if (inactive2width) {
        thresstr += '<td class="slider inactive" width="' + inactive2width + 'px" height="1px">';
        thresstr += '</td>';
    }
    thresstr += '<td width="' + spaddingX + 'px"></td>';
    thresstr += '</tr></table>';
    
    thressliderdiv = document.getElementById('thresslider');
    thressliderdiv.innerHTML = thresstr;
    thressliderdiv.onmouseup = clickThreshold;
	
	thresunit = document.getElementById('densunit');
    thresunit.innerHTML = densunit;
}

function clickThreshold(event) { 
/*apply clicked position on threshold slider
 */
    if (event) {
        xThumb = event.clientX;
        yThumb = event.clientY;
        var sliderLeft = document.getElementById("thresslider").getBoundingClientRect().left;
        var sliderWidth = document.getElementById("thresslider").getBoundingClientRect().right - sliderLeft;
        var totmin = Math.round(densmin);
        var totmax = Math.round(densmax);
        var perc = Math.min(Math.abs(xThumb - sliderLeft - spaddingX) / (sliderWidth - 2 * spaddingX), 1);
        perc = Math.max(perc, 0);
        var newthres = Math.round(totmin + (totmax - totmin) * perc);
        deltamin = Math.abs(newthres - thresLower);
        deltamax = Math.abs(newthres - thresUpper);
        if (deltamin < deltamax) {
            thresLower = newthres
        } else {
            thresUpper = newthres
        }
        refreshUnderlay()
        updateThreshold()
        checkTiles(1)
    }
}
/*** END DENSITY RANGE (THRESHOLD) ***/

/*** SLICES ***
 * updateSlice()
 * clickSlice(event)
 * sliceNext(delta,scaled)
 * slicePrev(delta,scaled)
 * sliceNextDef()
 * slicePrevDef()
 */
function updateSlice() { 
/*update Slice slider
 */
    var slicesliderdiv;
    var swidth = 145;
    var activewidth = Math.max(Math.round((swidth * slidePointer / JSONnum) - 5), 0);
    var inactivewidth = Math.max(Math.round((swidth - swidth * slidePointer / JSONnum) - 5), 0);
    var sliderstr = '<table id="sliceslidertable"><tr>';
    sliderstr += '<td width="' + spaddingX + 'px"></td>';
    if (activewidth) {
        sliderstr += '<td class="slider active"  title="' + (slidePointer) + "/" + (JSONnum) + '" width="' + activewidth + 'px" height="10px">';
        sliderstr += '</td>';
    } else {
        inactivewidth -= 5;
    }
    sliderstr += '<td class="slider knob tooltip" width="' + 10 + 'px" title="' + (slidePointer) + "/" + (JSONnum) + '"></td>';
    if (inactivewidth) {
        sliderstr += '<td class="slider inactive" title="' + (slidePointer) + "/" + (JSONnum) + '" width="' + inactivewidth + 'px" height="10px">';
        sliderstr += '</td>';
    }
    sliderstr += '<td width="' + spaddingX + 'px"></td>';
    sliderstr += '</tr></table>';

    slicesliderdiv = document.getElementById('sliceslider');
    slicesliderdiv.innerHTML = sliderstr;
    slicesliderdiv.onmouseup = clickSlice;
    slicesliderdiv.ondragstart = function () {
        return false;
    }

}

function clickSlice(event) { 
/*move to position clicked on slice slider
 */
    if (event) {
        xThumb = event.clientX;
        yThumb = event.clientY;
        var sliderLeft = document.getElementById("sliceslider").getBoundingClientRect().left;
        var sliderWidth = document.getElementById("sliceslider").getBoundingClientRect().right - sliderLeft;
        var perc = Math.min(Math.abs(xThumb - sliderLeft - spaddingX) / (sliderWidth - 2 * spaddingX), 1);
        perc = Math.max(perc, 0);
        newslice = Math.round(JSONnum * perc);
        delta = newslice - slidePointer;
        if (delta > 0) {
            sliceNext(delta, false)
        } else if (delta < 0) {
            slicePrev(delta, false)
        }
    }
}

function sliceNext(delta, scaled) { 
/*display next image in stack
 */
    // try {
    //  Tstart = performance.now();
    // } catch (err) {
    //  Tstart = new Date().getTime();
    // }

    try {
        clearTimeout(ActiveTask);
    } catch (err) {}
    if (scaled) {
        slidePointer += Math.floor(gTierCount * Math.abs(delta) / (zoom + xtrazoom)) + 1;
    } else {
        slidePointer += Math.floor(Math.abs(delta));
    }
    while (slidePointer >= JSONnum) {
        slidePointer -= JSONnum;
    }
    path = JSONout.slides[slidePointer].path;
    width = JSONout.slides[slidePointer].width;
    height = JSONout.slides[slidePointer].height;
    if (JSONout.slides[slidePointer].labelspath != undefined) {
        labelspath = JSONout.slides[slidePointer].labelspath;
        loadLabels();
    } else {
        labelspath = "";
    }
    updatePosition();
    init();
}

function slicePrev(delta, scaled) { 
/*display previous image in stack
 */
    // try {
    //  Tstart = performance.now();
    // } catch (err) {
    //  Tstart = new Date().getTime();
    // }

    try {
        clearTimeout(ActiveTask);
    } catch (err) {}
    if (scaled) {
        slidePointer += -1 * Math.floor(gTierCount * Math.abs(delta) / (zoom + xtrazoom)) - 1;
    } else {
        slidePointer += -1 * Math.floor(Math.abs(delta));
    }
    while (slidePointer < 0) {
        slidePointer += JSONnum;
    }
    path = JSONout.slides[slidePointer].path;
    width = JSONout.slides[slidePointer].width;
    height = JSONout.slides[slidePointer].height;
    if (JSONout.slides[slidePointer].labelspath != undefined) {
        labelspath = JSONout.slides[slidePointer].labelspath;
        loadLabels();
    } else {
        labelspath = "";
    }
    updatePosition();
    init();
}

function sliceNextDef(){ 
/*default function for moving to next slice
 *used for click events as no argument required 
 */
    sliceNext(1,true);
}

function slicePrevDef(){ 
/*default function for moving to previous slice
 * used for click events as no argument required 
 */
    slicePrev(1,true);
}
/*** END SLICES ***/


/*** OTHER UPDATES ***/
function updatePosition() { 
/*updates position displayed
 */
    var innerDiv = document.getElementById("innerDiv");
    var clientX, clientY;
    var event = window.event;
    if (!event) {
        clientX = lasteventX;
        clientY = lasteventY;
    }else{
        clientX = event.clientX;
        clientY = event.clientY;
    }
    if (coords) {
        var errstr = "<b>Unable to resolve position.</b>"
            try {
                document.getElementById('coords').innerHTML = "<b>&nbsp;Position</b> (in " + resunits + ")<b>:</b><br/>&nbsp;&nbsp;" + Math.round((res * (-stripPx(innerDiv.style.left) + clientX - 0) / (1 / (Math.pow(2, gTierCount - 1 - zoom) / xtrazoom)) * 10)) / 10 + ", " + Math.round((res * (-stripPx(innerDiv.style.top) + clientY - 16) / (1 / (Math.pow(2, gTierCount - 1 - zoom) / xtrazoom)) * 10)) / 10 + ", " + Math.round(zres * slidePointer * 10) / 10;
                removeError(errstr)
            } catch (err) {
                document.getElementById('coords').innerHTML = "<b>&nbsp;Position</b> (in " + resunits + ")<b>:</b><br/>&nbsp;&nbsp;unknown";
                logError(errstr+" "+err)
            }
    }
}

function updateInfo() { 
/*updates all sliders
 */
    updateZoom();
    updateThreshold();
    if (JSONnum) {
        updateSlice();
    }
    showThumb(); //should be in if statement
    updatePosition();
}

function centreView() { 
/*center image with respect to the display
 */
    var innerDiv = document.getElementById("innerDiv");
    if ((getVar('vX').length) > 0) {
        innerDiv.style.left = -getVar('vX') * width / (Math.pow(2, gTierCount - 1 - zoom) / xtrazoom) + viewportWidth / 2 + "px";
    } else {
        innerDiv.style.left =  - (width / (Math.pow(2, gTierCount - 1 - zoom) / xtrazoom)) / 2 + viewportWidth / 2 + 'px';
    }
	if ((getVar('vY').length) > 0) {
        innerDiv.style.top = -getVar('vY') * height / (Math.pow(2, gTierCount - 1 - zoom) / xtrazoom) + viewportHeight / 2 + "px";
    } else {
        innerDiv.style.top =  - (height / (Math.pow(2, gTierCount - 1 - zoom) / xtrazoom)) / 2 + viewportHeight / 2 + 'px';
    }
}

function clickOverlay() { 
/*on Mouse Click Show/Hide the overlay
 */
    //var overlay = document.getElementById('overlay');
    if ($("#overlay").hasClass("display")) {
        $("#overlay").removeClass('display');
		$("#overlaynav").removeClass('fa-chevron-left');
		$("#overlaynav").addClass('fa-chevron-right');
		
    }else{
		$("#overlay").addClass('display');
		$("#overlaynav").removeClass('fa-chevron-right');
		$("#overlaynav").addClass('fa-chevron-left');
	}
}


/*** THUMBNAIL ***
 * showThumb()
 * hideThumb()
 * moveThumb2()
 * clickThumb(event)
 * clickThumbC(event)
 ************************************************************
 * Note:
 * there are two thumbnails:
 *   normal one and 
 *   crossectional one (denoted with 'C')
 * each thumbnail is a div (denoted by '0') consisting of
 *   an "image+label"-div (denoted by '') and
 *   a "position rectangle"-div (denoted by '2')
 ************************************************************/
function showThumb() { 
/*display thumbnail
 */

    //thumbnail of planar view
    var Thumb = document.getElementById('Thumb');
    var timg = document.getElementById('timg');
    timg.src = imgpath + '0-0-0' + imgtype;
    refreshUnderlay()

    var Thumb0 = document.getElementById('Thumb0');
    Thumb0.style.height = gImageHeight / (Math.pow(2, gTierCount - 1)) + 'px';
    Thumb0.style.width = gImageWidth / (Math.pow(2, gTierCount - 1)) + 'px';
    Thumb0.style.display = "block";
    Thumb.style.display = "block";

    var Thumb2 = document.getElementById('Thumb2');
    Thumb2.style.display = "block";
    Thumb2.onmouseup = clickThumb;
    Thumb.onmouseup = clickThumb;
    Thumb.ondragstart = function () {
        return false;
    }

    //thumbnail of crossectional view
    var ThumbC = document.getElementById('ThumbC');
    ThumbC.innerHTML = '<div style="position:absolute;width:100%;text-align:center;">&nbsp;cross-sectional view</div>';
    ThumbC.innerHTML += '<div><img src="' + rootpath + '/tc' + imgtype + '"></div>';

    var Thumb0C = document.getElementById('Thumb0C');
    Thumb0C.style.height = JSONnum / (Math.pow(2, gTierCount - 1)) + 1 + 'px';
    Thumb0C.style.width = gImageWidth / (Math.pow(2, gTierCount - 1)) + 'px';
    Thumb0C.style.display = "block";
    ThumbC.style.display = "block";

    var Thumb2C = document.getElementById('Thumb2C');
    Thumb2C.style.display = "block";
    Thumb2C.onmouseup = clickThumbC;
    ThumbC.onmouseup = clickThumbC;
    ThumbC.ondragstart = function () {
        return false;
    }

}

function hideThumb() { 
/*hide thumbnail
 */
    document.getElementById('Thumb0').style.display = "none";
    document.getElementById('Thumb0C').style.display = "none";
}

function moveThumb2() { 
/*adjust thumbnail position indicator
 */
    //display rectangle of current view
    var innerDiv = document.getElementById("innerDiv");
    
    //rectangle for cross-sectional view
    var Thumb2C = document.getElementById("Thumb2C");
    topT = stripPx(innerDiv.style.top);
    leftT = stripPx(innerDiv.style.left);
    Thumb2C.style.width = viewportWidth / (Math.pow(2, zoom) * xtrazoom) + 'px';
    Thumb2C.style.height = '0px';
    Thumb2C.style.left = -leftT / (Math.pow(2, zoom) * xtrazoom) + 'px';
    Thumb2C.style.top = slidePointer / (Math.pow(2, gTierCount - 1)) + 'px';

    //rectangle for planar view
    var Thumb2 = document.getElementById("Thumb2");
    topT = stripPx(innerDiv.style.top);
    leftT = stripPx(innerDiv.style.left);
    Thumb2.style.width = viewportWidth / (Math.pow(2, zoom) * xtrazoom) + 'px';
    Thumb2.style.height = viewportHeight / (Math.pow(2, zoom) * xtrazoom) + 'px';
    Thumb2.style.left = -leftT / (Math.pow(2, zoom) * xtrazoom) + 'px';
    Thumb2.style.top = -topT / (Math.pow(2, zoom) * xtrazoom) + 'px';
}

function clickThumb(event) { 
/*move to position clicked in thumbnail
 */
    if (event) {
        xThumb = event.clientX; //X position of mouse on click with respect to viewport
        yThumb = event.clientY;
        var innerDiv = document.getElementById("innerDiv");

        //calculate new left position
        var ThumbWidth = stripPx(document.getElementById("Thumb0").style.width); //width of thumbnail element
        var ThumbLeft = viewportWidth - stripPx(document.getElementById("Thumb0").style.right) - stripPx(document.getElementById("Thumb0").style.border) - ThumbWidth; //left position of thumbnail element
        var xThumbRel = xThumb - ThumbLeft; //left position of click with respect to thumbnail
        var ImageZoomedWidth = width / (Math.pow(2, gTierCount - zoom - 1) / xtrazoom);
        var ImageZoomedLeft = ImageZoomedWidth * xThumbRel / ThumbWidth;
        innerDiv.style.left = viewportWidth / 2 - ImageZoomedLeft + 'px';

        //calculate new top position
        var ThumbHeight = stripPx(document.getElementById("Thumb0").style.height); //height of thumbnail element
        var ThumbTop = viewportHeight - stripPx(document.getElementById("Thumb0").style.bottom) - stripPx(document.getElementById("Thumb0").style.border) - ThumbHeight; //top position of thumbnail element
        var yThumbRel = yThumb - ThumbTop; //top position of click with respect to thumbnail
        var ImageZoomedHeight = height / (Math.pow(2, gTierCount - zoom - 1) / xtrazoom);
        var ImageZoomedTop = ImageZoomedHeight * yThumbRel / ThumbHeight;
        innerDiv.style.top = viewportHeight / 2 - ImageZoomedTop + 'px';

        //apply changes
        checkTiles(0);
        moveThumb2();
    }
}

function clickThumbC(event) { 
/*move to position clicked in cross-section thumbnail
 */
    if (event) {
        xThumb = event.clientX;
        yThumb = event.clientY;
        var innerDiv = document.getElementById("innerDiv");

        //calculate new left position
        var ThumbWidth = stripPx(document.getElementById("Thumb0C").style.width); //width of thumbnail element
        var ThumbLeft = viewportWidth - stripPx(document.getElementById("Thumb0C").style.right) - stripPx(document.getElementById("Thumb0C").style.border) - ThumbWidth; //left position of thumbnail element
        var xThumbRel = xThumb - ThumbLeft; //left position of click with respect to thumbnail
        var ImageZoomedWidth = width / (Math.pow(2, gTierCount - zoom - 1) / xtrazoom);
        var ImageZoomedLeft = ImageZoomedWidth * xThumbRel / ThumbWidth;
        innerDiv.style.left = viewportWidth / 2 - ImageZoomedLeft + 'px';

        //calculate new slice number
        slidePointer = Math.round(JSONnum * (Math.abs(yThumb - viewportHeight + stripPx(document.getElementById("Thumb0C").style.bottom) + stripPx(document.getElementById("Thumb0C").style.height)) + 2) / stripPx(document.getElementById("Thumb0C").style.height));

        //apply changes
        sliceNext(1);
        moveThumb2();
    }
}
/*** END THUMBNAIL ***/

/*** CONTROLS-INFO ***
 * clickControls(event)
 * showControlsBtn()
 * hideControlsBtn()
 * showControls()
 * hideControls()
 */
function clickControls(event) { 
/*handles event when "Controls" button is clicked
 */
    if(isControl){
        hideControls();
    }else{
        showControls();
    }
}

function showControlsBtn() { 
/*displays "Controls" button
 */
    document.getElementById("cntrlButton").style.display = "block";
}

function hideControlsBtn() { 
/*hides "Controls" button
 */
    document.getElementById("cntrlButton").style.display = "none";
    if(isControl){
        hideControls();
    }
}

function showControls() { 
/*displays div with image of controls
 */
    document.getElementById("controls").style.display = "block";
    $("#controls").animate({
        opacity : 0.75
    }, 500);
    isControl = true;
}

function hideControls() { 
/*hides div with image of controls
 */
    $("#controls").animate({
        opacity : 0
    }, 500);
    setTimeout(function () {
        document.getElementById("controls").style.display = "none";
        isControl = false;
    }, 500);
}
/*** END OF CONTROLS-INFO ***/


/*** AJAX/FILE LOADING ***
 * getHTTPObject()
 * getJSONAttribute(attribute, JSONdic, defval)
 * labelsHandler()
 * loadLabels()
 * JSONread()
 * loadJSON()
 */
function getHTTPObject() { 
/*requests an object from the server and returns it
 */
    var xhr;
    try {
        xhr = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (err) {
        try {
            xhr = new ActiveXObject("Microsoft.XMLHTTP");
        } catch (Err2) {
            xhr = false;
        }
    }
    if (!xhr && typeof XMLHttpRequest !== ' undefined') {
        xhr = new XMLHttpRequest();
    }
    return xhr;
}

function getJSONAttribute(attribute, JSONdic, defval) { 
/*attempts to get and return requested attribute from a JSON dictionary,
 *returns defval if not found
 */
    
    if (attribute in JSONdic) {
        var ans = JSONdic[attribute];
        return ans;
    }
    return defval;
}

labels = getHTTPObject();

function labelsHandler() { 
/*places labels in the labels div
 */
    if (labels.readyState == 4) {
        var labels2 = eval('(' + labels.responseText + ')');
        var lab = labels2.labels.length;

        for (var $i = 0; $i < lab; $i++) {
            var label = labels2.labels[$i].label;
            var name = label;
            var nX = labels2.labels[$i].x;
            var nY = labels2.labels[$i].y;
            
            if (labels2.labels[$i].name != undefined) {
                name = labels2.labels[$i].name;
            }

            if (labels2.labels[$i].url != undefined) {
                label = '<a href="' + labels2.labels[$i].url + '" title="' + name + '" target="_blank">' + label + '</a>';
            }

            pinImage = document.createElement("div");
            pinImage.style.position = "absolute";
            pinImage.style.left = (nX * gImageWidth / (Math.pow(2, gTierCount - 1 - zoom) / xtrazoom)) + "px";
            pinImage.style.top = (nY * gImageHeight / (Math.pow(2, gTierCount - 1 - zoom) / xtrazoom)) + "px";
            pinImage.style.width = 8 * label.length + "px";
            pinImage.style.height = "2px";
            pinImage.style.zIndex = 1;
            pinImage.setAttribute("id", "L" + $i);
            pinImage.innerHTML = label;
            document.getElementById("imageLabels").appendChild(pinImage);
        }
    }
}

function loadLabels() { 
/*requests labels to be loaded
 */
    var urlLabels = labelspath;
    var pinImage = document.getElementById("L0");
    if (pinImage) {
        imageLabels = document.getElementById("imageLabels");
        var divs = imageLabels.getElementsByTagName("div");
        while (divs.length > 0)
            imageLabels.removeChild(divs[0]);
    } else {
        labels.open("GET", urlLabels, true);
        labels.onreadystatechange = labelsHandler;
        labels.send(null);
    }
}

function JSONread() { 
/*reads in the information about the stack of images and 
 *sets global variables according to its content
 */
    var isError = false;
    if (JSONrequest.readyState == 4 && JSONrequest.status != 404) {
        removeError("<b>JSON not ready.</b>");
        removeError("<b>Couldn't load JSON.</b> Status:");
        isError = false;
        try {
            JSONout = eval('(' + JSONrequest.responseText + ')');
            removeError("Failed to read JSON: ");
        } catch (err) {
            isError = true;
            document.getElementById('debug').innerHTML = "Failed to read JSON: " + err;
            logError("Failed to read JSON: " + err);
        }
        if (!isError) {
            JSONnum = JSONout.slides.length;
            /***useful DICOM tags
             * (0018,0050) Slice Thickness             ------- slices can have overlap so don't use this!
             * (0018,0088) Spacing Between Slices      <------ This is the z-Resolution
             * (0018,1050) Spatial Resolution
             * (0018,1164) Imager Pixel Spacing
             * (0018,6048) Pixel Component Range Start
             * (0018,604A) Pixel Component Range Stop
             * (0018,604C) Pixel Component Physical Units
             * (0018,1240) Upper/Lower Pixel Values
             * (0018,6024) Physical Units X Direction
             * (0018,6026) Physical Units Y Direction
             * (0018,9322) Reconstruction Pixel Spacing
             * (0028,0030) Pixel Spacing                <------ This is x and y resolution as a string seperated by a "\"
             * (0028,0034) Pixel Aspect Ratio           ------- for CT should always be = 1
             * (0028,0108) Smallest Pixel Value in Series
             * (0028,0109) Largest Pixel Value in Series
             * (0054,1001) Units                        <------ Does not seem to be commonly defined, but standart is mm/voxel
             */

            /*get extra image info from JSON file if available*/
            height = getJSONAttribute("height", JSONout, height);
            width = getJSONAttribute("width", JSONout, width);

            resunits = getJSONAttribute("Units", JSONout, resunits);
            resunits = getJSONAttribute("resunits", JSONout, resunits);

            res = getJSONAttribute("PixelSpacing", JSONout, res);
            res = getJSONAttribute("0028,0030", JSONout, res); //need to check format
            res = getJSONAttribute("res", JSONout, res);

            zres = getJSONAttribute("SpacingBetweenSlices", JSONout, zres);
            zres = getJSONAttribute("0018,0088", JSONout, zres); //need to check format
            zres = getJSONAttribute("zres", JSONout, zres);

            /*try{ densM = JSONout.RescaleSlope; } catch(err) {} //need to convert
            try{ densM = JSONout["0028,1052"]; } catch(err) {} //need to convert
            try{ densB = JSONout.RescaleIntercept; } catch(err) {} //need to convert
            try{ densB = JSONout["0028,1053"]; } catch(err) {} //need to convert
             */
			
			 
            densmin = getJSONAttribute("densmin", JSONout, densmin);
            densmax = getJSONAttribute("densmax", JSONout, densmax);
			densunit = getJSONAttribute("densunit", JSONout, densunit);
            thresLower = Math.max(densmin, thresLower);
            thresUpper = Math.min(densmax, thresUpper);

            imgtype = getJSONAttribute("filetype", JSONout, ".jpg");

            start = getVar('start');
            if (start.length > 0) {
                slidePointer = parseInt(start, 10);
            } else {
                slidePointer = parseInt(JSONnum / 3, 10);
            }

            path = JSONout.slides[slidePointer].path; //path to specific slice images
            height = getJSONAttribute("height", JSONout.slides[slidePointer], height); //height of specific slice
            width = getJSONAttribute("width", JSONout.slides[slidePointer], width); //width of specific slice

            if (JSONout.slides[slidePointer].labelspath != undefined) {
                labelspath = JSONout.slides[slidePointer].labelspath;
                loadLabels();
            }

            loadedJSON = true;

            startup();

        }

    } else {
        if (JSONrequest.readyState != 4) {
            logError("<b>JSON not ready.</b>");
        } else {
            logError("<b>Couldn't load JSON.</b> Status:" + JSONrequest.status);
        }
    }
}

function loadJSON() { 
/*requests content of file describing the image stack
 */
    JSONrequest = getHTTPObject();
    JSONrequest.onreadystatechange = JSONread;
    JSONrequest.open("GET", JSON, true);
    JSONrequest.send(null);
}
/*** END AJAX/FILE LOADING ***/



/*** MOUSE WHEEL HANDLES ***
 *Mouse wheel settings (radio buttons):
 * wheelMode1()
 * wheelMode2()
 *Generic handlers:
 * handle(delta)
 * wheel(event)
 */
function wheelMode1() { 
/*Mouse wheel used for zoom
 */
    document.getElementById('chkMW1').checked = true;
    document.getElementById('chkMW1fa').className = "fa fa-square";
    document.getElementById('chkMW2fa').className = "fa fa-square-o";
    wheelmode = 0;
}

function wheelMode2() { 
/*Mouse wheel used for slices
 */
    document.getElementById('chkMW2').checked = true;
    document.getElementById('chkMW1fa').className = "fa fa-square-o";
    document.getElementById('chkMW2fa').className = "fa fa-square";
    wheelmode = 1;
}

function handle(delta) { 
/*handles mouse-wheel rotation
 */
    //calls the right function with the rotation argument from the mouse-wheel.
    try {
        clearTimeout(ActiveTask);
    } catch (err) {}
    wheelobs += delta;
    if (wheelobs <= -2) {
        wheelobs = 0;
        if (wheelmode == 0) {
            zoomIn();
        } else {
            slicePrev(delta, true);
        }
    } else if (wheelobs >= 2) {
        wheelobs = 0;
        if (wheelmode == 0) {
            zoomOut();
        } else {
            sliceNext(delta, true);
        }
    }
}

function wheel(event) { 
/*called after mouse wheel is moved
 */
    //called after mouse wheel is moved
    var delta = 0;
    if (!event) {
        event = window.event;
    }
    if (event.wheelDelta) {
        delta = event.wheelDelta / 120;
    } else if (event.detail) {
        delta = -event.detail / 3;
    }
    if (delta) {
        //alert("wheel");
        handle(delta);
    }
    if (event.preventDefault) {
        event.preventDefault();
    }
    event.returnValue = false;
}

function addWheelEvent() { 
/*adds event listener for mousewheel
 */
    if (window.addEventListener) {
        window.addEventListener('DOMMouseScroll', wheel, false); //FF
        window.addEventListener('mousewheel', wheel, false); // Opera, Chrome,Safari
        //window.addEventListener('wheel', wheel, false); //IE9+
    }else{
        try{
            if(elem.attachEvent) { 
                elem.attachEvent ("onmousewheel", wheel); // IE8-? IE7 emulator doesn't know this
            }
        }catch(err){}
    }
    window.onmousewheel = document.onmousewheel = wheel;
}
/*** END MOUSE WHEEL HANDLES ***/

/*** KEYBOARD HANDLES ***
 * capturekey(e)
 */
function capturekey(e) { 
/*calls appropriate functions in case of key being pressed
 */
    var k = (typeof event != 'undefined') ? window.event.keyCode : e.keyCode;
    try {
        clearTimeout(ActiveTask);
    } catch (err) {}
    if (k == 187 || k == 61 || k == 107) {
        /* =/+ (in FF 61, else 187) or Numpad+ (107) -> zoom in */
        zoomIn();
    } else if (k == 189 || k == 173 || k == 109) {
        /* -/_ (in FF 173, else 189) or Numpad- (109) -> zoom out */
        zoomOut();
    } else if (k == 39 || k == 40 || k == 34) {
        /* RightArrow (39), DownArrow (40) or PageDown (34) -> next slice */
        sliceNext(1);
    } else if (k == 37 || k == 38 || k == 33) {
        /* LeftArrow (37), UpArrow (38) or PageUp (33) -> previous slice */ 
        slicePrev(-1);
    } else if (k == 82) {
        /* r (82) -> increase minimum threshold */
        thresLower = Math.min(1 * thresLower + 1,thresUpper);
        updateThreshold();
        checkTiles(1);
    } else if (k == 70) {
        /* f (70) -> dencrease minimum threshold */ 
        thresLower = Math.max(1 * thresLower - 1,densmin);
        updateThreshold();
        checkTiles(1);
    } else if (k == 84) {
        /* t (84) -> increase maximum threshold */ 
        thresUpper = Math.min(1 * thresUpper + 1,densmax);
        checkTiles(1);
    } else if (k == 71) {
        /* g (71) -> decrease maximum threshold */ 
        thresUpper = Math.max(1 * thresUpper - 1,thresLower);
        checkTiles(1);
    } else if (k == 81) {
        /* q (81) -> zoom in */
        zoomIn();
    } else if (k == 69) {
        /* e (69) -> zoom out */
        zoomOut();
//  } else if (k == 87) {
//      /* w (87) -> pan */
//      pan(-1,0);
//  } else if (k == 83) {
//      /* s (83) -> pan */
//      pan(1,0);
//  } else if (k == 65) {
//      /* a (65) -> pan */
//      pan(0,-1);
//  } else if (k == 68) {
//      /* d (68) -> pan */
//      pan(0,1);
    } else if (k == 88) {
        /* x (88) -> previous slice */
        slicePrev(-1);
    } else if (k == 67) {
        /* c (67) -> next slice */
        sliceNext(1);
    }
}

if (navigator.appName != "Mozilla") {
    document.onkeyup = capturekey;
} else {
    document.addEventListener("keypress", capturekey, true);
}
/*** END KEYBOARD HANDLES ***/


/*** MOUSE HANDLES ***
 *Generic:
 * is_touch_device()
 * adjustRuler()
 *Mouse Click Settings (Radio Buttons):
 * clickMode1()
 * clickMode2()
 *Mouse Drag Event:
 * startMove(event)
 * processMove(event)
 * stopMove()
 *Apple Specific Events:
 * appleStartTouch(event)
 * appleMoveEnd(event)
 * appleMove(event)
 * appleMoving(event)
 */
/**Generic**/
function is_touch_device() { 
/*checks if client device supports touch commands
 */
    try {
        document.createEvent("TouchEvent");
        return true;
    } catch (err) {
        return false;
    }
}

function adjustRuler(){ 
/*calculates and sets style of the ruler div
 */
    var innerDiv = document.getElementById("innerDiv");
    var clientX, clientY;
    var event = window.event;
    if (!event) {
        clientX = lasteventX;
        clientY = lasteventY;
    }else{
        clientX = event.clientX;
        clientY = event.clientY;
    }
    Mtop = res * (clientY - dragStartTop) / (1 / (Math.pow(2, gTierCount - 1 - zoom) / xtrazoom));
    Mleft = res * (clientX - dragStartLeft) / (1 / (Math.pow(2, gTierCount - 1 - zoom) / xtrazoom));
    Mdist = Math.round(Math.sqrt(Math.pow(Mtop, 2) + Math.pow(Mleft, 2)) * 10) / 10;
    rulerdiv = document.getElementById('ruler');
    rulerdiv.alt = Mdist + resunits;
    rulerwidth = Math.sqrt(Math.pow((clientY - dragStartTop), 2) + Math.pow((clientX - dragStartLeft), 2));
	rulerangle = Math.atan2(Mtop, Mleft) * 180 / Math.PI;
    rulerdiv.style.top = ((dragStartTop + clientY) / 2) + 'px';
    rulerdiv.style.left = ((dragStartLeft + clientX - rulerwidth) / 2) + 'px';
    rulerdiv.style.height = '0px';
    rulerdiv.style.width = Math.round(rulerwidth) + 'px';
    if (rulerangle < -90 || rulerangle > 90) {
        rulerangle = rulerangle + 180;
    }
    //rotation might be an issue on Safari and on IE9-
    rulerdiv.style.transform = "rotate(" + rulerangle + "deg)"; //;-ms-transform: rotate(30deg);-webkit-transform: rotate(30deg)
    rulerdiv.style.webkitTransform = "rotate(" + rulerangle + "deg)";
    rulerdiv.innerHTML = engUnit(Mdist,resunits);
    rulerdiv.style.zIndex = "2";
    rulerdiv.style.display = "block";
}

/**Mouse Click Settings (Radio Buttons)**/
function clickMode1() { 
/*on Mouse Click/Drag: pan/drag the image
 */
    var ruler = document.getElementById('ruler');
    ruler.style.display = "none";
    if (is_touch_device()) {
        document.getElementById("cmlbl").innerHTML = 'Touch Mode:';
    }
    document.getElementById('chkMC1').checked = true;
    document.getElementById('chkMC1fa').className = "fa fa-square";
    document.getElementById('chkMC2fa').className = "fa fa-square-o";
    document.getElementById('sleepRuler').style.display = "block";
    document.getElementById('outerDiv').style.cursor = "move";
    clickmode = 0;
}

function clickMode2() { 
/*on Mouse Click/Drag: measure distance
 */
    if (is_touch_device()) {
        document.getElementById("cmlbl").innerHTML = 'Touch Mode:';
    }
    document.getElementById('chkMC2').checked = true;
    document.getElementById('chkMC1fa').className = "fa fa-square-o";
    document.getElementById('chkMC2fa').className = "fa fa-square";
    document.getElementById('sleepRuler').style.display = "none";
    document.getElementById('outerDiv').style.cursor = "default";
    clickmode = 1;
}

/**Mouse Drag Event**/
function startMove(event) { 
/*called on mousedown - 
 *saves initial position and defines mousemove event
 */
    innerDiv = document.getElementById("innerDiv");
    if (!event) {
        event = window.event;
    }
    dragStartLeft = event.clientX;
    dragStartTop = event.clientY;
    mTop = stripPx(innerDiv.style.top);
    mLeft = stripPx(innerDiv.style.left);
    if (clickmode == 0) {
        dragging = true;
    } else {
        measuring = true;
    }

    return false;
}

function processMove(event) { 
/*updates the coordinates displayed and 
 *executes the appropriate function in case of panning or measuring active
 */
    var Mtop, Mleft, Mdist;
    var ruler;
    var rulerwidth, rulerangle;
    innerDiv = document.getElementById("innerDiv");
    
    if (!event) {
        event = window.event;
    }
    if (event) {
        lasteventX = event.clientX
        lasteventY = event.clientY
    }
    
    updatePosition();
    
    if (dragging) {
        innerDiv.style.top = mTop + (lasteventY - dragStartTop) + 'px';
        innerDiv.style.left = mLeft + (lasteventX- dragStartLeft) + 'px';
    } else if (measuring) {
        adjustRuler();
    }
}

function stopMove() { 
/*called on mouseup - 
 *resets the mousemove events and updates tiles in case image was panned
 */
    if (dragging) {
        dragging = false;
        //only load new tiles once moving has stopped
        checkTiles(0);
    }
    measuring = false;
}

/**Apple Device Event Handlers Block**/
function appleStartTouch(event) { 
/*Touch event started
 */
    innerDiv = document.getElementById("innerDiv");
    if (event.touches.length == 1) {
        touchIdentifier = event.touches[0].identifier;
        dragStartLeft = event.touches[0].clientX;
        dragStartTop = event.touches[0].clientY;
        mTop = stripPx(innerDiv.style.top);
        mLeft = stripPx(innerDiv.style.left);

        if (clickmode == 0) {
            dragging = true;
        } else {
            measuring = true;
        }
        return true;
    }
}

function appleMoveEnd(event) { 
/*Touch event ended
 */
    dragging = false;
    measuring = false;
    appleMove(event);
}

function appleMove(event) { 
/*Touch event ongoing
 */
    var Mtop, Mleft, Mdist;
    var ruler;
    var rulerwidth, rulerangle;
    innerDiv = document.getElementById("innerDiv");
    if (event) {
        lasteventX = event.changedTouches[0].clientX
        lasteventY = event.changedTouches[0].clientY
    }
    updatePosition();
    if ((event.changedTouches.length == 1) && (dragging == true) && (touchIdentifier == event.changedTouches[0].identifier)) {
        innerDiv.style.top = mTop + (lasteventY - dragStartTop) + 'px';
        innerDiv.style.left = mLeft + (lasteventX - dragStartLeft) + 'px';
    } else if ((event.changedTouches.length == 1) && (measuring == true) && (touchIdentifier == event.changedTouches[0].identifier)) {
        adjustRuler();
    }
    event.preventDefault();
    checkTiles(0);
}

function appleMoving(event) { 
/*handles apple touch moving event
*/
    event.preventDefault();
    appleMove(event);
}
/*** END MOUSE HANDLES ***/

/*** INITIATION ***
 * startup()
 * init()
 * initOnclicks()
 * winsize()
 * $(document).ready
 */
function startup() { 
/*initialises all the variables, loads JSON, etc.
*/
    
    // console.timeStamp("startup")

    //Threshold sliders
    thresLower = Math.max(densmin, -1000);
    thresUpper = Math.min(densmax, 1000);
    
    if (typeof jQuery === 'undefined') {
        // no jQuery
        logError("Please make sure your browser supports jQuery.");
    }

    winsize();
    document.getElementById('error').innerHTML = ""; //error div displays overlay at top of screen
    //one can use logError and removeError to access it
    var divs = document.getElementById("imageLabels").getElementsByTagName("div");
    while (divs.length > 0){
        imageLabels.removeChild(divs[0]);
    }
    var width2 = getVar('width');
    var height2 = getVar('height');
    var coords2 = getVar('coords');
    if (width2.length > 0) width = width2;
    if (height2.length > 0) height = height2;
    if (coords2.length > 0) coords = coords2;
    
    var start = getVar('start');
    if (start.length > 0) {
        slidePointer = parseInt(start, 10);
    }

    //try to load JSON. the JSON file can either be specified directly, or a root directory can be given.
    var rootpath2 = getVar('root');
    if (rootpath2.length > 1) rootpath = rootpath2;
    var JSON2 = getVar('JSON');
    if (JSON2.length > 1) { //if specified directly, then load JSON file
        JSON = JSON2;
    } else {
        JSON = rootpath + "/infoJSON.txt"; //otherwise load the file with name "infoJSON.txt" from rootpath specified
    }
    if (JSON.length > 1 && !loadedJSON) { //for 3D images display navigation elements for moving through the slices
        getJSON = true;
        document.getElementById("slices").style.display = "block";
        loadJSON();
        document.getElementById('wheelMode').style.display = "block";
        return;
    }

    /* resolution (in plane) in px/resunits */
    var res2 = getVar('res');
    if (res2.length > 1) {
        try {
            res = parseFloat(res2);
        } catch (err) {}
    }
    if (isNumeric(res)) {
        try {
            res = parseFloat(res);
        } catch (err) {
            res = 1.0;
        }
    } else {
        res = 1.0;
    }

    /* z-resolution (between slides) in px/resunits */
    zres2 = getVar('zres');
    if (zres2.length > 1) {
        try {
            zres = parseFloat(zres2);
        } catch (err) {}
    }
    if (isNumeric(zres)) {
        try {
            zres = parseFloat(zres);
        } catch (err) {
            zres = 1.0;
        }
    } else {
        zres = 1.0;
    }

    /* unit for the resolution */
    resunits2 = getVar('resunits');
    if (resunits2.length > 0)
        resunits = resunits2;
    if (resunits.length <= 0) {
        resunits = "px";
    }
	
	/* unit for the density */
    densunit2 = getVar('densunit');
    if (densunit2.length > 0)
        densunit = densunit2;
    if (densunit.length <= 0) {
        densunit = "N/A";
    }

    imgpath = path;

    //calculate number of zoom levels
    gImageWidth = width;
    gImageHeight = height;
    tempWidth = gImageWidth;
    tempHeight = gImageHeight;
    divider = 2;
    gTierCount = 1;
    while (tempWidth > tileSize || tempHeight > tileSize) {
        tempWidth = Math.floor(gImageWidth / divider)
            tempHeight = Math.floor(gImageHeight / divider);
        divider *= 2;
        if (tempWidth % 2)
            tempWidth++;
        if (tempHeight % 2)
            tempHeight++;
        gTierCount++;
    }
    vTier2 = getVar('vT');
    if (vTier2.length > 0) {
        zoom = Math.max(0,vTier2);
		if (zoom > gTierCount - 1){
			xtrazoom = Math.min(zoom - gTierCount + 1,xtrazoomMax);
			zoom = gTierCount - 1;
		}
    } else {
        zoom = gTierCount - 1;
    }

    //position innerDiv containing the image relative to viewport
    centreView();

    /*attach functions to outerDiv which contains the innerDiv
     * this is important as the user may click in an area outside the
     * actual image and this way can still interact with the image,
     * e.g. if the image is out of the viewing area
     */
    var outerDiv = document.getElementById("outerDiv");
    outerDiv.onmousedown = startMove;
    outerDiv.onmousemove = processMove;
    outerDiv.onmouseup = stopMove;
    outerDiv.ondragstart = function () {
        return false;
    }

    /*Capture Mobile Device Events*/
    outerDiv.ontouchstart = appleStartTouch;
    outerDiv.ontouchend = appleMoveEnd;
    outerDiv.ontouchmove = appleMoving;
    outerDiv.ongesturestart = function (event) {
        event.preventDefault();
        gestureScale = event.scale;
        parent.document.ontouchmove = function (event) {
            event.preventDefault();
        };
    }
    outerDiv.ongestureend = function (event) {
        event.preventDefault();
        if (event.scale > gestureScale) {
            zoomIn();
        } else {
            zoomOut();
        }
        parent.document.ontouchmove = null;
    };

    /*now call original initialisation function for the rest.*/
	winsize();
    init();

    /*Capture touch device events*/
    if (is_touch_device()) {
        /*for touch devices no mouse wheel support, but touching = zooming*/
        document.getElementById("wheelMode").style.display = 'none';
        document.getElementById("cmlbl").innerHTML = 'Touch Mode:';
    } else {
        /*for normal devices mousewheel support includes different modes*/
        document.getElementById("wheelMode").style.display = 'block';
    }
    

    /*entry animation
     * moves in toolbox from the side, once ready
     * this is not onlz a nice effect, but prevents
     * users from trying to access functions before
     * they have been loaded. Unfortunately on older
     * browsers animations aren't supported so the
     * panel will be off screen and never appear
     */
    /*setTimeout(function () {
        $("#overlay").animate({
            left : "-200px"
        }, 1500, "swing");
    }, 1500);*/
	
	/*setTimeout(function () {
        $("#overlay").animate({
            top : "100px"
        }, 1500, "swing");
    }, 1500);*/

    // console.timeStamp("startup end");
    
    initOnclicks();
    addWheelEvent();
}

function init() { 
/*initiate, but also called for new slides...
*/
    var imageLabels = document.getElementById("imageLabels");
    var divs = imageLabels.getElementsByTagName("div");

    // try {
    //  Tstart = performance.now();
    // } catch (err) {
    //  Tstart = new Date().getTime();
    // }

    while (divs.length > 0) {
        imageLabels.removeChild(divs[0]);
    }
    imgpath = path;
    ActiveTask = setTimeout(function () {
        checkTiles(1);
    }, 0);
    updateInfo(); //check if all necessary
} //*** End of Init()

function initOnclicks() {
/*initiates html elements
*/
    /*hide elements*/
    document.getElementById("controls").style.display = "none";
    /*create onclick events*/
    document.getElementById('chkMW1').onclick = wheelMode1;
    document.getElementById('chkMW2').onclick = wheelMode2;
    document.getElementById('chkMW1div').onmouseup = wheelMode1;
    document.getElementById('chkMW2div').onmouseup = wheelMode2;
    document.getElementById('chkMC2').onclick = clickMode2;
    document.getElementById('chkMC1').onclick = clickMode1;
    document.getElementById('chkMC2div').onmouseup = clickMode2;
    document.getElementById('chkMC1div').onmouseup = clickMode1;
    document.getElementById("cntrlButton").onmouseup = clickControls;
    document.getElementById("closeControls").onmouseup = hideControls;
    document.getElementById("zoomouticon").onmouseup = zoomOut;
    document.getElementById("zoominicon").onmouseup = zoomIn;
    document.getElementById("sliceprevicon").onmouseup = slicePrevDef;
    document.getElementById("slicenexticon").onmouseup = sliceNextDef;
    document.getElementById("zoomouticonfa").onmouseup = zoomOut;
    document.getElementById("zoominiconfa").onmouseup = zoomIn;
    document.getElementById("slicepreviconfa").onmouseup = slicePrevDef;
    document.getElementById("slicenexticonfa").onmouseup = sliceNextDef;
	
	document.getElementById("overlaybtn").onclick = clickOverlay;
	
}


function hideFullTools() { 
/*hide Mouse Wheel option and position
 */
    document.getElementById('wheelMode').style.display = "none";
    document.getElementById('coords').style.display = "none";
}

function showFullTools() { 
/*show Mouse Wheel option and position
 */
    document.getElementById('wheelMode').style.display = "block";
    document.getElementById('coords').style.display = "block";
}

function winsize() { 
/*repositions the image and deals with variable changes in case of the window being rescaled
 *this always happens on page load!
 */
    var contentbox, overlayTop;
    var errmsg = "<b>Failed on resize:</b> ";
    tviewportWidth = viewportWidth;
    tviewportHeight = viewportHeight;

    if (typeof(window.innerWidth) == 'number') {
        viewportWidth = window.innerWidth;
        viewportHeight = window.innerHeight;
    } else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
        viewportWidth = document.documentElement.clientWidth;
        viewportHeight = document.documentElement.clientHeight;
    } else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
        viewportWidth = document.body.clientWidth;
        viewportHeight = document.body.clientHeight;
    }

    try {
        contentbox = document.getElementById('contentbox');
        viewportWidth = contentbox.clientWidth;
        viewportHeight = contentbox.clientHeight;
    } catch (err) {
        //setTimeout("winsize();", 5000);
    }
    
    
    if (viewportHeight != tviewportHeight || viewportWidth != tviewportWidth) {
        if (viewportHeight != tviewportHeight){
            try {
                overlayTop = Math.max(Math.min((viewportHeight - 500), 145), 0);
                document.getElementById("overlay").style.top = overlayTop + 'px';
                document.getElementById("overlay").style.height = (viewportHeight - overlayTop) + 'px';
            } catch (err) {
                logError(errmsg + err);
                //Fails on load as element not yet created
            }
        }
        try {
            if (viewportWidth >= 700 && viewportHeight >= 450) {
                if(loadedJSON){
                  showThumb();
                }
                showControlsBtn();
            }else if (viewportWidth <= 600 || viewportHeight <= 400) {
                hideThumb();
                hideControlsBtn();
            }
			if (viewportHeight >= 450) {
				showFullTools();
            } else if (viewportHeight <= 400) {
				hideFullTools();
            }
            removeError(errmsg);
            //updateInfo();
        } catch (err) {
            logError(errmsg + err);
            //Fails on load as element not yet created
        }
    }

}

window.onresize = winsize;

/*execute once page is loaded*/
$(document).ready(
    function(){
        winsize();
        startup();
    }
);
/*** END INITIATION ***/
