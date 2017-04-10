// Cross-Browser Rich Text Editor

//init variables
var isRichText = false;
var rng;
var currentRTE;
var allRTEs = "";
var activeRTE;
var g_rteDoc = new Array();

var isIE;
var isGecko;
var isSafari;
var isKonqueror;

var imagesPath;
var includesPath;
var cssFile;

var g_rte_def_font_family = "";
var g_rte_def_font_size = "";
var g_rte_def_font_color = "";

function initRTE(imgPath, incPath, css) 
{
	//set browser vars
	var ua = navigator.userAgent.toLowerCase();
	isIE = ((ua.indexOf("msie") != -1) && (ua.indexOf("opera") == -1) && (ua.indexOf("webtv") == -1)); 
	isGecko = (ua.indexOf("gecko") != -1);
	isSafari = (ua.indexOf("safari") != -1);
	isKonqueror = (ua.indexOf("konqueror") != -1);
	
	//check to see if designMode mode is available
	if (document.getElementById && document.designMode && !isSafari && !isKonqueror)
		isRichText = true;
	
	if (isIE)
	{
		document.onmouseover = raiseButton;
		document.onmouseout  = normalButton;
		document.onmousedown = lowerButton;
		document.onmouseup   = raiseButton;
	}
	
	//set paths vars
	imagesPath = imgPath;
	//includesPath = incPath;
	includesPath = "/lib/rte/";
	cssFile = css;
	
	//if (isRichText) document.writeln('<style type="text/css">@import "' + includesPath + 'rte.css";</style>');
	
	//for testing standard textarea, uncomment the following line
	//isRichText = false;
}

function writeRichText(rte, rtename, html, width, height, buttons, readOnly) 
{
	if (isRichText) 
	{
		if (allRTEs.length > 0) allRTEs += ";";
		allRTEs += rte;
		
		if (readOnly) buttons = false;
		
		//adjust minimum table widths
		if (isIE) 
		{
			if (buttons && (width < 540)) width = 540;
			var tablewidth = width;
		} 
		else 
		{
			if (buttons && (width < 540)) width = 540;
			var tablewidth = width + 4;
		}

		document.writeln('<div class="rteDiv">');
		if (buttons == true) 
		{
			document.writeln('<table class="rteBack" cellpadding="0" cellspacing="0" id="Buttons2_' + rte + '" width="100%">');
			document.writeln('	<tr><td width="100%">');
			document.writeln('<div style="float:left;"><img id="bold" class="rteImage" src="' + imagesPath + 'bold.gif" width="25" height="24" alt="Bold" title="Bold" onClick="rteCommand(\'' + rte + '\', \'bold\', \'\')"></div>');
			document.writeln('<div style="float:left;"><img class="rteImage" src="' + imagesPath + 'italic.gif" width="25" height="24" alt="Italic" title="Italic" onClick="rteCommand(\'' + rte + '\', \'italic\', \'\')"></div>');
			document.writeln('<div style="float:left;"><img class="rteImage" src="' + imagesPath + 'underline.gif" width="25" height="24" alt="Underline" title="Underline" onClick="rteCommand(\'' + rte + '\', \'underline\', \'\')"></div>');
			document.writeln('<div style="float:left;"><img class="rteVertSep" src="' + imagesPath + 'blackdot.gif" width="1" height="20" border="0" alt=""></div>');
			document.writeln('<div style="float:left;"><img class="rteImage" src="' + imagesPath + 'left_just.gif" width="25" height="24" alt="Align Left" title="Align Left" onClick="rteCommand(\'' + rte + '\', \'justifyleft\', \'\')"></div>');
			document.writeln('<div style="float:left;"><img class="rteImage" src="' + imagesPath + 'centre.gif" width="25" height="24" alt="Center" title="Center" onClick="rteCommand(\'' + rte + '\', \'justifycenter\', \'\')"></div>');
			document.writeln('<div style="float:left;"><img class="rteImage" src="' + imagesPath + 'right_just.gif" width="25" height="24" alt="Align Right" title="Align Right" onClick="rteCommand(\'' + rte + '\', \'justifyright\', \'\')"></div>');
			document.writeln('<div style="float:left;"><img class="rteImage" src="' + imagesPath + 'justifyfull.gif" width="25" height="24" alt="Justify Full" title="Justify Full" onclick="rteCommand(\'' + rte + '\', \'justifyfull\', \'\')"></div>');
			document.writeln('<div style="float:left;"><img class="rteVertSep" src="' + imagesPath + 'blackdot.gif" width="1" height="20" border="0" alt=""></div>');
			document.writeln('<div style="float:left;"><img class="rteImage" src="' + imagesPath + 'hr.gif" width="25" height="24" alt="Horizontal Rule" title="Horizontal Rule" onClick="rteCommand(\'' + rte + '\', \'inserthorizontalrule\', \'\')"></div>');
			document.writeln('<div style="float:left;"><img class="rteVertSep" src="' + imagesPath + 'blackdot.gif" width="1" height="20" border="0" alt=""></div>');
			document.writeln('<div style="float:left;"><img class="rteImage" src="' + imagesPath + 'numbered_list.gif" width="25" height="24" alt="Ordered List" title="Ordered List" onClick="rteCommand(\'' + rte + '\', \'insertorderedlist\', \'\')"></div>');
			document.writeln('<div style="float:left;"><img class="rteImage" src="' + imagesPath + 'list.gif" width="25" height="24" alt="Unordered List" title="Unordered List" onClick="rteCommand(\'' + rte + '\', \'insertunorderedlist\', \'\')"></div>');
			document.writeln('<div style="float:left;"><img class="rteVertSep" src="' + imagesPath + 'blackdot.gif" width="1" height="20" border="0" alt=""></div>');
			document.writeln('<div style="float:left;"><div id="forecolor_' + rte + '"><img class="rteImage" src="' + imagesPath + 'textcolor.gif" width="25" height="24" alt="Text Color" title="Text Color" onClick="dlgColorPalette(\'' + rte + '\', \'forecolor\', \'\')"></div></div>');
			document.writeln('<div style="float:left;"><div id="hilitecolor_' + rte + '"><img class="rteImage" src="' + imagesPath + 'bgcolor.gif" width="25" height="24" alt="Background Color" title="Background Color" onClick="dlgColorPalette(\'' + rte + '\', \'hilitecolor\', \'\')"></div></div>');
			document.writeln('<div style="float:left;"><img class="rteImage" src="' + imagesPath + 'hyperlink.gif" width="25" height="24" alt="Insert Link" title="Insert Link" onClick="insertLink(\'' + rte + '\')"></div>');
			document.writeln('<div style="float:left;"><img class="rteImage" src="' + imagesPath + 'image.gif" width="25" height="24" alt="Add Image" title="Add Image" onClick="addImage(\'' + rte + '\')"></div>');
			document.writeln('<div style="float:left;"><div id="table_' + rte + '"><img class="rteImage" src="' + imagesPath + 'insert_table.gif" width="25" height="24" alt="Insert Table" title="Insert Table" onClick="dlgInsertTable(\'' + rte + '\', \'table\', \'\')"></div></div>');
			document.writeln('<div style="float:left;">');
			document.writeln('			<select tabindex="1000" id="fontname_' + rte + '" onchange="selectFont(\'' + rte + '\', this.id)">');
			document.writeln('				<option value="Font" selected>Font</option>');
			document.writeln('				<option value="Arial, Helvetica, sans-serif">Arial</option>');
			document.writeln('				<option value="Georgia, Times, sans-serif">Georgia</option>');
			document.writeln('				<option value="Tahoma, Arial, Verdana, Helvetica, sans-serif">Tahoma</option>');
			document.writeln('				<option value="Courier New, Courier, mono">Courier New</option>');
			document.writeln('				<option value="Times New Roman, Times, serif">Times New Roman</option>');
			document.writeln('				<option value="Verdana, Arial, Helvetica, sans-serif">Verdana</option>');
			document.writeln('			</select>');
			document.writeln('</div>');
			document.writeln('<div style="float:left;">');
			document.writeln('			<select tabindex="1001" unselectable="on" id="fontsize_' + rte + '" onchange="selectFont(\'' + rte + '\', this.id);">');
			document.writeln('				<option value="Size">Size</option>');
			document.writeln('				<option value="1">1</option>');
			document.writeln('				<option value="2">2</option>');
			document.writeln('				<option value="3">3</option>');
			document.writeln('				<option value="4">4</option>');
			document.writeln('				<option value="5">5</option>');
			document.writeln('				<option value="6">6</option>');
			document.writeln('				<option value="7">7</option>');
			document.writeln('			</select>');
			document.writeln('</div>');

			if (!readOnly) 
			{
				document.writeln('<div style="float:left;">SRC');
				document.writeln('<input type="checkbox" id="chkSrc' + rte + '" onclick="toggleHTMLSrc(\'' + rte + '\');" />');
				document.writeln('</div>');
			}
			document.writeln('</td></tr>');
			document.writeln('</table>');
		}
		document.writeln('<iframe id="' + rte + '" name="' + rte + '" width="' + width + '" height="' + height + '" src="' + includesPath + 'blank.htm" border="0" style="border: 1px solid;"></iframe>');
		//if (!readOnly) document.writeln('<br /><input type="checkbox" id="chkSrc' + rte + '" onclick="toggleHTMLSrc(\'' + rte + '\');" />&nbsp;View Source');
		document.writeln('<iframe width="154" height="104" id="cp' + rte + '" src="' + includesPath + 'palette.htm" marginwidth="0" marginheight="0" scrolling="no" style="display:none; position: absolute; top:0px; left:0px;"></iframe>');
		document.writeln('<input type="hidden" id="hdn' + rte + '" name="' + rtename + '" value="">');
		document.writeln('</div>');
		
		document.getElementById('hdn' + rte).value = html;
		enableDesignMode(rte, html, readOnly);
	} 
	else 
	{
		if (!readOnly) 
		{
			document.writeln('<textarea name="' + rte + '" id="' + rte + '" style="width: ' + width + '; height: ' + height + 'px;">' + html + '</textarea>');
		}
		else 
		{
			document.writeln('<textarea name="' + rte + '" id="' + rte + '" style="width: ' + width + 'px; height: ' + height + 'px;" readonly>' + html + '</textarea>');
		}
	}
}

function enableDesignMode(rte, html, readOnly) 
{
	var frameHtml = "<html id=\"" + rte + "\">\n";
	frameHtml += "<head>\n";
	frameHtml += "<meta HTTP-EQUIV='content-type' CONTENT='text/html; charset=UTF-8'>\n";
	frameHtml += "<style>\n";
	frameHtml += "body {\n";
	frameHtml += " background: #FFFFFF;\n";
	frameHtml += " margin: 0px;\n";
	frameHtml += " padding: 3px;\n";
	if (g_rte_def_font_family)
		frameHtml += "font-family: "+ g_rte_def_font_family +";\n";
	if (g_rte_def_font_size)
		frameHtml += "font-size: "+ g_rte_def_font_size +";\n";
	if (g_rte_def_font_color)
		frameHtml += "color: "+ g_rte_def_font_color +";\n";
	frameHtml += "}\n";
	frameHtml += " P {margin-top:0;margin-bottom:0}\n";
	frameHtml += "</style>\n";
	frameHtml += "</head>\n";
	frameHtml += "<body>\n";
	frameHtml += unescape(html) + "\n";
	frameHtml += "</body>\n";
	frameHtml += "</html>";

	if (document.all) 
	{
		var oRTE = frames[rte].document;
		oRTE.open();
		oRTE.write(frameHtml);
		oRTE.close();
		if (!readOnly) oRTE.designMode = "On";
		//document.getElementById(rte).contentWindow.body.onkeyup = function(e) { alert("HI"); };
		//rte_alertkey;
	} 
	else 
	{
		try 
		{
			if (!readOnly) document.getElementById(rte).contentDocument.designMode = "on";
			try 
			{
				var oRTE = document.getElementById(rte).contentWindow.document;
				oRTE.open();
				oRTE.write(frameHtml);
				oRTE.close();
				if (isGecko && !readOnly) 
				{
					//attach a keyboard handler for gecko browsers to make keyboard shortcuts work
					oRTE.addEventListener("keypress", kb_handler, true);
					document.getElementById(rte).contentWindow.document.body.spellcheck = true;
					//oRTE.addEventListener("keyup", rte_kbup_handler, true);
				}
			} 
			catch (e) 
			{
				alert("Error preloading content.");
			}
		} 
		catch (e) 
		{
			//gecko may take some time to enable design mode.
			//Keep looping until able to set.
			if (isGecko) 
				setTimeout("enableDesignMode('" + rte + "', \"" + html + "\", " + readOnly + ");", 10);
			else 
				return false;
		}
	}

	
	if (g_rte_def_font_family)
		rteCommand(rte, "fontname", g_rte_def_font_family);
}

function rte_kbup_handler(e)
{
	if(!e) 
	{
		//if the browser did not pass the event information to the
		//function, we will have to obtain it from the event register
		if(window.event) 
		{
			//Internet Explorer
			e = window.event;
		} 
		else 
		{
			//total failure, we have no way of referencing the event
			return;
		}
	}

	var rte = e.target.id;

	if(e.keyCode) 
	{
		//DOM
		e = e.keyCode;
	} 
	else if (typeof(e.which) == 'number') 
	{
		//NS 4 compatible
		e = e.which;
	} 
	else if(typeof(e.charCode) == 'number')
	{
		//also NS 6+, Mozilla 0.9+
		e = e.charCode;
	} 
	else 
	{
		//total failure, we have no way of obtaining the key code
		return;
	}

	//window.alert('The key pressed has keycode ' + e + ' and is key ' + String.fromCharCode( e ) );
	window.alert(rte.innerHTML);
}

function updateRTEs() 
{
	var vRTEs = allRTEs.split(";");
	for (var i = 0; i < vRTEs.length; i++) 
	{
		updateRTE(vRTEs[i]);
	}
}

function updateRTE(rte) 
{
	if (!isRichText) return;
	
	//set message value
	var oHdnMessage = document.getElementById('hdn' + rte);
	var oRTE = document.getElementById(rte);
	var readOnly = false;
	
	//check for readOnly mode
	if (document.all) 
	{
		// IE will be "On", Opera will be "on" (lower case)
		if (frames[rte].document.designMode != "On" &&
			frames[rte].document.designMode != "on") 
		{
			readOnly = true;
		}
	} 
	else 
	{
		if (document.getElementById(rte).contentDocument.designMode != "on") readOnly = true;
	}
	if (isRichText && !readOnly) 
	{
		//if viewing source, switch back to design view

		if (document.getElementById("chkSrc" + rte).checked) 
		{
			document.getElementById("chkSrc" + rte).checked = false;
			toggleHTMLSrc(rte);
		}
		if (oHdnMessage.value == null) oHdnMessage.value = "";
		if (document.all) 
		{
			var bdy = frames[rte].document.body.innerHTML;
			var re = new RegExp ('<p>', 'gi') ;
			var bdy = bdy.replace(re, '<br>') ;
			var re = new RegExp ('</p>', 'gi') ;
			var bdy = bdy.replace(re, '') ;
			oHdnMessage.value = bdy;
		} 
		else 
		{
			oHdnMessage.value = oRTE.contentWindow.document.body.innerHTML;
		}
		
		//if there is no content (other than formatting) set value to nothing
		if (stripHTML(oHdnMessage.value.replace("&nbsp;", " ")) == "" 
			&& oHdnMessage.value.toLowerCase().search("<hr") == -1
			&& oHdnMessage.value.toLowerCase().search("<img") == -1) oHdnMessage.value = "";
		//fix for gecko
		if (escape(oHdnMessage.value) == "%3Cbr%3E%0D%0A%0D%0A%0D%0A") oHdnMessage.value = "";
	}
}

function rteCommand(rte, command, option) 
{
	//function to perform command
	var oRTE;
	if (document.all) 
	{
		oRTE = frames[rte];
	} 
	else 
	{
		oRTE = document.getElementById(rte).contentWindow;
	}
	
	try 
	{
		oRTE.focus();
	  	oRTE.document.execCommand(command, false, option);
		oRTE.focus();
	} 
	catch (e) 
	{
//		alert(e);
//		setTimeout("rteCommand('" + rte + "', '" + command + "', '" + option + "');", 10);
	}
}

function toggleHTMLSrc(rte) 
{
	//contributed by Bob Hutzel (thanks Bob!)
	var oRTE;
	if (document.all) 
	{
		oRTE = frames[rte].document;
	} 
	else 
	{
		oRTE = document.getElementById(rte).contentWindow.document;
	}
	
	if (document.getElementById("chkSrc" + rte).checked) 
	{
		//showHideElement("Buttons2_" + rte, "hide");
		if (document.all) 
		{
			oRTE.body.innerText = oRTE.body.innerHTML;
		} 
		else 
		{
			var htmlSrc = oRTE.createTextNode(oRTE.body.innerHTML);
			oRTE.body.innerHTML = "";
			oRTE.body.appendChild(htmlSrc);
		}
	} 
	else 
	{
		//showHideElement("Buttons1_" + rte, "show");
		showHideElement("Buttons2_" + rte, "show");
		if (document.all) 
		{
			//fix for IE
			var output = escape(oRTE.body.innerText);
			output = output.replace("%3CP%3E%0D%0A%3CHR%3E", "%3CHR%3E");
			output = output.replace("%3CHR%3E%0D%0A%3C/P%3E", "%3CHR%3E");
			
			oRTE.body.innerHTML = unescape(output);
		} 
		else 
		{
			var htmlSrc = oRTE.body.ownerDocument.createRange();
			htmlSrc.selectNodeContents(oRTE.body);
			oRTE.body.innerHTML = htmlSrc.toString();
		}
	}
}

function dlgColorPalette(rte, command) 
{
	//function to display or hide color palettes
	setRange(rte);
	
	//get dialog position
	var oDialog = document.getElementById('cp' + rte);
	var buttonElement = document.getElementById(command + '_' + rte);
	var iLeftPos = getOffsetLeft(buttonElement);
	var iTopPos = getOffsetTop(buttonElement) + (buttonElement.offsetHeight + 4);
	oDialog.style.left = (iLeftPos) + "px";
	oDialog.style.top = (iTopPos) + "px";
	
	if ((command == parent.command) && (rte == currentRTE)) 
	{
		//if current command dialog is currently open, close it
		if (oDialog.style.display == "none") 
		{
			showHideElement(oDialog, 'show');
		} 
		else 
		{
			showHideElement(oDialog, 'hide');
		}
	} 
	else 
	{
		//if opening a new dialog, close all others
		var vRTEs = allRTEs.split(";");
		for (var i = 0; i < vRTEs.length; i++) 
		{
			showHideElement('cp' + vRTEs[i], 'hide');
		}
		showHideElement(oDialog, 'show');
	}
	
	//save current values
	parent.command = command;
	currentRTE = rte;
}

function setColor(color) 
{
	//function to set color
	var rte = currentRTE;
	var parentCommand = parent.command;
	
	if (document.all) 
	{
		//retrieve selected range
		var sel = frames[rte].document.selection; 
		if (parentCommand == "hilitecolor") parentCommand = "backcolor";
		if (sel != null) 
		{
			var newRng = sel.createRange();
			newRng = rng;
			newRng.select();
		}
	}
	
	rteCommand(rte, parentCommand, color);
	showHideElement('cp' + rte, "hide");
}


function RteInsertHtml(html, rte) 
{
	if (document.all) 
	{
		//retrieve selected range
		var sel = frames[rte].document.selection; 
		if (sel != null) 
		{
			var newRng = sel.createRange();
			newRng = rng;
			newRng.select();
		}
		
		rteCommand(rte, 'paste', html);
	}
	else
	{
		rteCommand(rte, 'insertHTML', html);
	}
}

function dlgInsertTable(rte, command) 
{
	//function to open/close insert table dialog
	//save current values
	setRange(rte);
	parent.command = command;
	currentRTE = rte;
	var windowOptions = 'history=no,toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=no,resizable=no,width=360,height=200';
	window.open(includesPath + 'insert_table.htm', 'InsertTable', windowOptions);
}

function insertLink(rte) 
{
	//function to insert link
	var szURL = prompt("Enter a URL:", "");
	try 
	{
		//ignore error for blank urls
		rteCommand(rte, "Unlink", null);
		rteCommand(rte, "CreateLink", szURL);
	} 
	catch (e) 
	{
		//do nothing
	}
}

function addImage(rte) 
{
	activeRTE = rte;
	window.open("/userfiles/popup_browse_files.awp?browse=1&filter=jpg:jpeg:png:gif:bmp&callback=RteInsertImage");
}

function RteInsertImage(fid, fname)
{
	if (fid) 
	{
		var imagePath = "/userfiles/file_download.awp?view=1&fid=" + fid;
		rteCommand(activeRTE, 'InsertImage', imagePath);
	}
}

// Ernst de Moor: Fix the amount of digging parents up, in case the RTE editor itself is displayed in a div.
// KJR 11/12/2004 Changed to position palette based on parent div, so palette will always appear in proper location regardless of nested divs
function getOffsetTop(elm) 
{
	var mOffsetTop = elm.offsetTop;
	var mOffsetParent = elm.offsetParent;
	var parents_up = 2; //the positioning div is 2 elements up the tree
	
	while(parents_up > 0) {
		mOffsetTop += mOffsetParent.offsetTop;
		mOffsetParent = mOffsetParent.offsetParent;
		parents_up--;
	}
	
	return mOffsetTop;
}

// Ernst de Moor: Fix the amount of digging parents up, in case the RTE editor itself is displayed in a div.
// KJR 11/12/2004 Changed to position palette based on parent div, so palette will always appear in proper location regardless of nested divs
function getOffsetLeft(elm) 
{
	var mOffsetLeft = elm.offsetLeft;
	var mOffsetParent = elm.offsetParent;
	var parents_up = 2;
	
	while(parents_up > 0) 
	{
		mOffsetLeft += mOffsetParent.offsetLeft;
		mOffsetParent = mOffsetParent.offsetParent;
		parents_up--;
	}
	
	return mOffsetLeft;
}

function selectFont(rte, selectname) 
{
	//function to handle font changes
	var idx = document.getElementById(selectname).selectedIndex;
	// First one is always a label
	if (idx != 0) 
	{
		var selected = document.getElementById(selectname).options[idx].value;
		var cmd = selectname.replace('_' + rte, '');
		rteCommand(rte, cmd, selected);
		document.getElementById(selectname).selectedIndex = 0;
	}
}

function kb_handler(evt) 
{
	var rte = evt.target.id;
	
	//contributed by Anti Veeranna (thanks Anti!)
	if (evt.ctrlKey) 
	{
		var key = String.fromCharCode(evt.charCode).toLowerCase();
		var cmd = '';
		switch (key) 
		{
			case 'b': cmd = "bold"; break;
			case 'i': cmd = "italic"; break;
			case 'u': cmd = "underline"; break;
		};

		if (cmd) 
		{
			rteCommand(rte, cmd, null);
			
			// stop the event bubble
			evt.preventDefault();
			evt.stopPropagation();
		}
 	}
	else
	{
		//alert(evt.which);
		//rte_alertkey(evt);
	}
}

function insertHTML(html, pass_rte) 
{
	//function to add HTML -- thanks dannyuk1982
	if (pass_rte)
		var rte = pass_rte;
	else
		var rte = currentRTE;

	var oRTE;
	if (document.all) {
		oRTE = frames[rte];
	} else {
		oRTE = document.getElementById(rte).contentWindow;
	}
	
	oRTE.focus();
	if (document.all) {
		var oRng = oRTE.document.selection.createRange();
		oRng.pasteHTML(html);
		oRng.collapse(false);
		oRng.select();
	} else {
//		oRTE.document.execCommand('insertHTML', false, html);
		oRTE.document.execCommand('insertHTML', false, html);
	}
}

function showHideElement(element, showHide) 
{
	//function to show or hide elements
	//element variable can be string or object
	if (document.getElementById(element)) 
	{
		element = document.getElementById(element);
	}
	
	if (showHide == "show") 
	{
		element.style.display = "block";
	} 
	else if (showHide == "hide") 
	{
		element.style.display = "none";
	}
}

function setRange(rte) 
{
	//function to store range of current selection
	var oRTE;
	if (document.all) 
	{
		oRTE = frames[rte];
		var selection = oRTE.document.selection; 
		if (selection != null) rng = selection.createRange();
	} 
	else 
	{
		oRTE = document.getElementById(rte).contentWindow;
		var selection = oRTE.getSelection();
		rng = selection.getRangeAt(selection.rangeCount - 1).cloneRange();
	}
}

function stripHTML(oldString) 
{
	//function to strip all html
	var newString = oldString.replace(/(<([^>]+)>)/ig,"");
	
	//replace carriage returns and line feeds
   newString = newString.replace(/\r\n/g," ");
   newString = newString.replace(/\n/g," ");
   newString = newString.replace(/\r/g," ");
	
	//trim string
	newString = trim(newString);
	
	return newString;
}

function trim(inputString) 
{
   // Removes leading and trailing spaces from the passed string. Also removes
   // consecutive spaces and replaces it with one space. If something besides
   // a string is passed in (null, custom object, etc.) then return the input.
   if (typeof inputString != "string") return inputString;
   var retValue = inputString;
   var ch = retValue.substring(0, 1);
	
   while (ch == " ") 
   { // Check for spaces at the beginning of the string
      retValue = retValue.substring(1, retValue.length);
      ch = retValue.substring(0, 1);
   }
   ch = retValue.substring(retValue.length - 1, retValue.length);
	
   while (ch == " ") 
   { // Check for spaces at the end of the string
      retValue = retValue.substring(0, retValue.length - 1);
      ch = retValue.substring(retValue.length - 1, retValue.length);
   }
	
	// Note that there are two spaces in the string - look for multiple spaces within the string
   while (retValue.indexOf("  ") != -1) 
   {
		// Again, there are two spaces in each of the strings
      retValue = retValue.substring(0, retValue.indexOf("  ")) + retValue.substring(retValue.indexOf("  ") + 1, retValue.length);
   }
   return retValue; // Return the trimmed string back to the user
}

//*****************
//IE-Only Functions
//*****************
function checkspell() 
{
	//function to perform spell check
	try 
	{
		var tmpis = new ActiveXObject("ieSpell.ieSpellExtension");
		tmpis.CheckAllLinkedDocuments(document);
	}
	catch(exception) 
	{
		if(exception.number==-2146827859) 
		{
			if (confirm("ieSpell not detected.  Click Ok to go to download page."))
				window.open("http://www.iespell.com/download.php","DownLoad");
		} 
		else 
		{
			alert("Error Loading ieSpell: Exception " + exception.number);
		}
	}
}

function raiseButton(e) 
{
	//IE-Only Function
	var el = window.event.srcElement;
	
	className = el.className;
	if (className == 'rteImage' || className == 'rteImageLowered') 
	{
		el.className = 'rteImageRaised';
	}
}

function normalButton(e) 
{
	//IE-Only Function
	var el = window.event.srcElement;
	
	className = el.className;
	if (className == 'rteImageRaised' || className == 'rteImageLowered') 
	{
		el.className = 'rteImage';
	}
}

function lowerButton(e) 
{
	//IE-Only Function
	var el = window.event.srcElement;
	
	className = el.className;
	if (className == 'rteImage' || className == 'rteImageRaised') 
	{
		el.className = 'rteImageLowered';
	}
}
