/*======================================================================================
	
	Module:		CRte

	Purpose:	Create RTE input textarea

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2007 Aereus Corporation. All rights reserved.
	
	Usage:		// Create rte

======================================================================================*/

/***********************************************************************************
 *
 *	Class: 		CRte
 *
 *	Purpose:	Rich text class
 *
 ***********************************************************************************/
function CRte(inpt)
{
	/* THIS WILL BE USED TO REDIRECT ALL TO THE NEW RTE
	var input = inpt || null;

	// Return the new UI class
	var rte = new alib.ui.Editor(input);
	return rte;
	*/

	this.ifrm = alib.dom.createElement("iframe");
	this.ifrm.border = '0';
	this.ifrm.frameBorder = '0';
    this.ifrm.src = "about:blank";
	this.ifrm.id = "CRteIframe";
	alib.dom.styleSetClass(this.ifrm, "CRteIframe");
	this.hdntxt = alib.dom.createElement("input");
    this.hdntxt.type = "hidden";
    if(typeof inpt != "undefined")    
	{
		if (inpt.id)
			this.hdntxt.id = inpt.id;
    
		if(inpt.getAttribute("name"))
			this.hdntxt.setAttribute("name", inpt.getAttribute("name"));
	}
    
	
	this.idoc = null;	
	this.f_src = false;	
	this.rte_id = '1';
	this.frm_input = (inpt) ? inpt : null;

	this.colors = [
					"FFFFFF",	"FFCCCC",	"FFCC99",	"FFFF99",	"FFFFCC",	"99FF99",	"99FFFF",	"CCFFFF",	
					"CCCCFF",	"FFCCFF",	"CCCCCC",	"FF6666",	"FF9966",	"FFFF66",	"FFFF33",	"66FF99",	
					"33FFFF",	"66FFFF",	"9999FF",	"FF99FF",	"C0C0C0",	"FF0000",	"FF9900",	"FFCC66",	
					"FFFF00",	"33FF33",	"66CCCC",	"33CCFF",	"6666CC",	"CC66CC",	"999999",	"CC0000",	
					"FF6600",	"FFCC33",	"FFCC00",	"33CC00",	"00CCCC",	"3366FF",	"6633FF",	"CC33CC",	
					"666666",	"990000",	"CC6600",	"CC9933",	"999900",	"009900",	"339999",	"3333FF",	
					"6600CC",	"993399",	"333333",	"660000",	"993300",	"996633",	"666600",	"006600",	
					"336666",	"000099",	"333399",	"663366",	"000000",	"330000",	"663300",	"663333",	
					"333300",	"003300",	"003333",	"000066",	"330099",	"330033"
					  ];
}

/***********************************************************************************
 *
 *	Function: 	setDocument
 *
 *	Purpose:	Set document
 *
 ***********************************************************************************/
CRte.prototype.setDocument = function()
{
    this.iwnd = this.ifrm.contentWindow || this.ifrm.contentDocument;

    if (this.iwnd && this.iwnd.document) 
	{
        this.idoc = this.iwnd.document;
    }
}

/***********************************************************************************
 *
 *	Function: 	onChange
 *
 *	Purpose:	To be overridden
 *
 ***********************************************************************************/
CRte.prototype.onChange = function()
{
}

/***********************************************************************************
 *
 *	Function: 	updateText
 *
 *	Purpose:	Update value in hidden text field
 *
 ***********************************************************************************/
CRte.prototype.updateText = function(html)
{
    //this.hdntxt.value = this.idoc.body.innerHTML;

	if (this.f_src)
	{
		if (alib.userAgent.ie) 
		{
			//fix for IE
			var output = escape_utf8(this.idoc.body.innerText);
			output = output.replace("%3CP%3E%0D%0A%3CHR%3E", "%3CHR%3E");
			output = output.replace("%3CHR%3E%0D%0A%3C/P%3E", "%3CHR%3E");
			
			this.hdntxt.value = unescape_utf8(output);
		} 
		else 
		{
			var htmlSrc = this.idoc.body.ownerDocument.createRange();
			htmlSrc.selectNodeContents(this.idoc.body);
			this.hdntxt.value = htmlSrc.toString();
		}

	}
	else
	{
		this.hdntxt.value = this.idoc.body.innerHTML;
	}

	if (this.frm_input)
		this.frm_input.value = this.hdntxt.value
}

/***********************************************************************************
 *
 *	Function: 	getValue
 *
 *	Purpose:	Get text value of rte
 *
 ***********************************************************************************/
CRte.prototype.getValue = function()
{
	this.updateText();

    return this.hdntxt.value;
}

/***********************************************************************************
 *
 *	Function: 	setValue
 *
 *	Purpose:	Set text value of rte
 *
 ***********************************************************************************/
CRte.prototype.setValue = function(html)
{
	var frameHtml = "<!DOCTYPE html PUBLIC ";
	frameHtml += '"-//W3C//DTD XHTML 1.0 Strict//EN" ';
	frameHtml += '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
	frameHtml += "\n";
	frameHtml = "<html id=\"" + this.rte_id + "\">\n";
	frameHtml += "<head>\n";
	frameHtml += "<meta HTTP-EQUIV='content-type' CONTENT=\"text/html; charset=UTF-8\">\n";
	frameHtml += "<style>\n";
	frameHtml += "body {\n";
	frameHtml += " background: #FFFFFF;\n";
	frameHtml += " margin: 0px;\n";
	frameHtml += " padding: 3px;\n";
	if (typeof g_rte_def_font_family != "undefined")
		frameHtml += "font-family: "+ g_rte_def_font_family +";\n";
	if (typeof g_rte_def_font_size != "undefined")
		frameHtml += "font-size: "+ g_rte_def_font_size +";\n";
	if (typeof g_rte_def_font_color != "undefined")
		frameHtml += "color: "+ g_rte_def_font_color +";\n";
	frameHtml += "}\n";
	frameHtml += "p {margin-top:0;margin-bottom:0}\n";
	frameHtml += "table.rte tr td {border: 1px solid; padding: 10px;}\n";
	frameHtml += "</style>\n";
	frameHtml += "</head>\n";
	frameHtml += "<body>\n";
	if (html)
		frameHtml += html + "\n";
	frameHtml += "</body>\n";
	frameHtml += "</html>";

    // Check if idoc is an object and not a null value
    if(this.idoc)
    {
        this.idoc.open();
        this.idoc.write(frameHtml);
        this.idoc.close();
    }
}

/***********************************************************************************
 *
 *	Function: 	getHiddenInput
 *
 *	Purpose:	Get the input
 *
 ***********************************************************************************/
CRte.prototype.getHiddenInput = function()
{
	this.updateText();

    return this.hdntxt;
}

/***********************************************************************************
 *
 *	Function: 	setHeight
 *
 *	Purpose:	Set the height of iframe
 *
 ***********************************************************************************/
CRte.prototype.setHeight = function(height)
{
	this.ifrm.style.height = height;
}

/***********************************************************************************
 *
 *	Function: 	focus
 *
 *	Purpose:	Set the focus to the iframe
 *
 ***********************************************************************************/
CRte.prototype.focus = function(height)
{
	this.iwnd.focus();
}

/***********************************************************************************
 *
 *	Function: 	insertHtml
 *
 *	Purpose:	Update value in hidden text field
 *
 ***********************************************************************************/
CRte.prototype.insertHtml = function(html)
{
	if (alib.userAgent.ie) 
	{
		//retrieve selected range
		var sel = this.idoc.selection; 
		if (sel != null) 
		{
			var newRng = sel.createRange();
			newRng = this.rng;
			newRng.select();
		}
		
		this.rteCommand('paste', html);
	}
	else
	{
		this.rteCommand('insertHtml', html);
	}
}

/***********************************************************************************
 *
 *	Function: 	enableDesign
 *
 *	Purpose:	Enable design mode in iframe
 *
 ***********************************************************************************/
CRte.prototype.enableDesign = function(html)
{
	this.setDocument();
    
    // If idoc is null, do not execute and return false
    if(!this.idoc || this.idoc == null)
        return false;
        
    
	this.setValue(html);

	if (alib.userAgent.ie) 
	{
		this.idoc.designMode = "On";
	} 
	else 
	{
		this.ifrm.contentDocument.designMode = "on";
		
		if (alib.userAgent.gecko || alib.userAgent.webkit) 
		{
			//attach a keyboard handler for gecko browsers to make keyboard shortcuts work
			//oRTE.addEventListener("keypress", kb_handler, true);
			this.idoc.body.spellcheck = true;
		}
	}
    
    return true;
}

/***********************************************************************************
 *
 *	Function: 	createToolbar
 *
 *	Purpose:	Create toolbar
 *
 ***********************************************************************************/
CRte.prototype.createToolbar = function(container)
{
	var me = this;

	var imgroot = alib.getBasePath();

	// Add toolbar
	// ------------------------------------------
	var tb = new CToolbar();
	tb.setClass("");
	tb.addIcon(imgroot + "/images/bold.gif", "left", function(cls) {cls.rteCommand('bold', ''); }, [me]);
	tb.addIcon(imgroot + "/images/italic.gif", "left", function(cls) {cls.rteCommand('italic', ''); }, [me]);
	tb.addIcon(imgroot + "/images/underline.gif", "left", function(cls) {cls.rteCommand('underline', ''); }, [me]);
	//tb.addSpacer();
	//tb.addIcon(imgroot + "/images/paste.gif", "left", function(cls) {cls.paste(); }, [me]);
	tb.addSpacer();
	tb.addIcon(imgroot + "/images/left_just.gif", "left", function(cls) {cls.rteCommand('justifyleft', ''); }, [me]);
	tb.addIcon(imgroot + "/images/centre.gif", "left", function(cls) {cls.rteCommand('justifycenter', ''); }, [me]);
	tb.addIcon(imgroot + "/images/right_just.gif", "left", function(cls) {cls.rteCommand('justifyright', ''); }, [me]);
	tb.addIcon(imgroot + "/images/justifyfull.gif", "left", function(cls) {cls.rteCommand('justifyfull', ''); }, [me]);
	tb.addSpacer();
	tb.addIcon(imgroot + "/images/hr.gif", "left", function(cls) {cls.rteCommand('inserthorizontalrule', ''); }, [me]);
	// Image
	if (typeof AntFsOpen == "undefined") // Check for ANT AntFsOpen
	{
		function imgdlg(cls)
		{
			cls.setRange();

			var dlg_p = new CDialog();
			dlg_p.m_rtfref = cls;
			
			dlg_p.onPromptOk = function(val)
			{
				this.m_rtfref.insertImage(val);
			}

			dlg_p.promptBox("Enter the URL of your image", "Insert Image", "");
		}
		

		tb.addIcon(imgroot + "/images/image.gif", "left", imgdlg, [this]);
	}
	else // Use ANT file system
	{
		var cbrowser = new AntFsOpen();
		cbrowser.filterType = "jpg:jpeg:png:gif";
		cbrowser.cbData.m_rtfref = this;
		cbrowser.onSelect = function(fid, name, path) 
		{
			this.cbData.m_rtfref.insertImage("http://" + document.domain + "/files/images/"+fid);
		}

		/*
		var cbrowser = new CFileOpen();
		cbrowser.filterType = "jpg:jpeg:png:gif";
		cbrowser.m_rtfref = this;
		cbrowser.onSelect = function(fid, name, path) 
		{
			this.m_rtfref.insertImage("http://" + document.domain + "/files/images/"+fid);
		}
		*/
		
		tb.addIcon(imgroot + "/images/image.gif", "left", function(cbrowser, cls) { cls.setRange(); cbrowser.showDialog(); }, [cbrowser, this]);
	}
	// Link
	function lnkdlg(cls)
	{
		cls.setRange();

		var dlg_p = new CDialog();
		dlg_p.m_rtfref = cls;
		
		dlg_p.onPromptOk = function(val)
		{
			this.m_rtfref.insertLink(val);
		}

		dlg_p.promptBox("Enter the link path", "Insert Link", "");
	}

	tb.addIcon(imgroot + "/images/hyperlink.gif", "left", lnkdlg, [this]);

	// table
	function instable(cls)
	{
		cls.setRange();
		cls.insertHtml("<table class='rte'><tbody><tr><td>&nbsp;</td><td>&nbsp;</td></tr></tbody></table>");
	}

	tb.addIcon(imgroot + "/images/insert_table.gif", "left", instable, [this]);

	tb.addSpacer();
	tb.addIcon(imgroot + "/images/numbered_list.gif", "left", function(cls) {cls.rteCommand('insertorderedlist', ''); }, [me]);
	tb.addIcon(imgroot + "/images/list.gif", "left", function(cls) {cls.rteCommand('insertunorderedlist', ''); }, [me]);
	tb.addSpacer();
	var dmcon = new CDropdownMenu();
	var dcon = dmcon.addCon();
	this.createToolbarFntColor(dcon);
	tb.AddItem(dmcon.createImageMenu(imgroot + "/images/textcolor.gif", imgroot + "/images/textcolor.gif", imgroot + "/images/textcolor.gif"));
	var dmcon = new CDropdownMenu();
	var dcon = dmcon.addCon();
	this.createToolbarHlColor(dcon);
	tb.AddItem(dmcon.createImageMenu(imgroot + "/images/bgcolor.gif", imgroot + "/images/bgcolor.gif", imgroot + "/images/bgcolor.gif"));
	tb.addSpacer();
	// Font
	var dmfnt = new CDropdownMenu();
	dmfnt.mRteCls = this;
	dmfnt.onmousedown = function() { this.mRteCls.setRange(); }
	dmfnt.tabIndex = -1;
	var fonts = ["Arial", "Georgia", "Tahoma", "Courier New", "Times New Roman", "Verdana"];
	for (var i = 0; i < fonts.length; i++)
		dmfnt.addEntry("<span style='font-family:"+fonts[i]+"'>"+fonts[i]+"</span>", function (cls, f) { cls.setFont("fontname", f); }, null, null, [this, fonts[i]]);
	tb.AddItem(dmfnt.createButtonMenu("Font"));
	// Size
	var dmsz = new CDropdownMenu();
	dmsz.mRteCls = this;
	dmsz.onmousedown = function() { this.mRteCls.setRange(); }
	dmsz.tabIndex = -1;
	var sizes = [[1, "Smallest"], [2, "X-Small"], [3, "Small"], [4, "Normal"], [5, "Large"], [6, "X-Large"], [7, "Huge"]];
	for (var i = 0; i < sizes.length; i++)
		dmsz.addEntry(sizes[i][1], function (cls, f) { cls.setFont("fontsize", f); }, null, null, [this, sizes[i][0]]);
	tb.AddItem(dmsz.createButtonMenu("Size"));
	// Style
	var dmst = new CDropdownMenu();
	dmst.mRteCls = this;
	dmst.onmousedown = function() { this.mRteCls.setRange(); }
	dmst.tabIndex = -1;
	var styles = [["Body / Normal", "formatblock", "<p>"], 
				  ["Heading 1", "FormatBlock", "<h1>"], 
				  ["Heading 2", "FormatBlock", "<h2>"], 
				  ["Heading 3", "FormatBlock", "<h3>"], 
				  ["Heading 4", "FormatBlock", "<h4>"], 
				  ["Heading 5", "FormatBlock", "<h5>"],
				  ["Quote", "FormatBlock", "<blockquote>"]];
	for (var i = 0; i < styles.length; i++)
		dmst.addEntry(styles[i][0], function (cls, func, val) { cls.setFont(func, val); }, null, null, [this, styles[i][1], styles[i][2]]);
	tb.AddItem(dmst.createButtonMenu("Styles"));
	tb.addSpacer();
	// Src
	tb.addIcon(imgroot + "/images/src.gif", "left", function(cls) {cls.toggleHtmlSrc(); }, [me]);
	tb.print(container);
	// Paste
	/*
	var dmpaste = new CDropdownMenu();
	dmpaste.mRteCls = this;
	dmpaste.onmousedown = function() { this.mRteCls.setRange(); }
	dmpaste.tabIndex = -1;
	dmpaste.addEntry("Paste", function (cls) { cls.paste(); }, null, null, [this]);
	dmpaste.addEntry("Paste Plain Text", function (cls) { cls.paste(true); }, null, null, [this]);
	tb.AddItem(dmpaste.createButtonMenu("Paste"));
	*/
}

/***********************************************************************************
 *
 *	Function: 	createToolbarFntColor
 *
 *	Purpose:	Create toolbar font color
 *
 ***********************************************************************************/
CRte.prototype.createToolbarFntColor = function(container)
{
	var me = this;

	var tbl = alib.dom.createElement("table", container);
	var tbody = alib.dom.createElement("tbody", tbl);

	var cntr = 0;
	var tr = alib.dom.createElement("tr", tbody);
	for (var i = 0; i < this.colors.length; i++)
	{
		var td = alib.dom.createElement("td", tr);
		td.menuref = container.menuref;
		td.clsref = me;
		td.clr = this.colors[i];
		alib.dom.styleSet(td, "background-color", "#"+this.colors[i]);
		alib.dom.styleSet(td, "width", "10px");
		alib.dom.styleSet(td, "height", "10px");
		alib.dom.styleSet(td, "border", "1px solid gray");
		td.onmousedown = function() { this.clsref.setRange(); }
		td.onmouseover = function() { this.style.border = '1px dotted white'; }
		td.onmouseout = function() { this.style.border = '1px solid gray'; }
		td.onclick = function() { this.clsref.setColor('forecolor', this.clr); this.menuref.unloadMe(); }

		cntr++;

		if (cntr >= 10)
		{
			var cntr = 0;
			var tr = alib.dom.createElement("tr", tbody);
		}
	}
}

/***********************************************************************************
 *
 *	Function: 	createToolbarHlColor
 *
 *	Purpose:	Create toolbar highlight color
 *
 ***********************************************************************************/
CRte.prototype.createToolbarHlColor = function(container)
{
	var me = this;

	container.clsref = this;

	var tbl = alib.dom.createElement("table", container);
	var tbody = alib.dom.createElement("tbody", tbl);

	var cntr = 0;
	var tr = alib.dom.createElement("tr", tbody);
	for (var i = 0; i < this.colors.length; i++)
	{
		var td = alib.dom.createElement("td", tr);
		td.menuref = container.menuref;
		td.clsref = me;
		td.clr = this.colors[i];
		alib.dom.styleSet(td, "background-color", "#"+this.colors[i]);
		alib.dom.styleSet(td, "width", "10px");
		alib.dom.styleSet(td, "height", "10px");
		alib.dom.styleSet(td, "border", "1px solid gray");
		td.onmousedown = function() { this.clsref.setRange(); }
		td.onmouseover = function() { this.style.border = '1px dotted white'; }
		td.onmouseout = function() { this.style.border = '1px solid gray'; }
		td.onclick = function() { this.clsref.setColor('hilitecolor', this.clr); this.menuref.unloadMe(); }

		cntr++;

		if (cntr >= 10)
		{
			var cntr = 0;
			var tr = alib.dom.createElement("tr", tbody);
		}
	}
}

/***********************************************************************************
 *
 *	Function: 	setColor
 *
 *	Purpose:	Set hilight or foreground color
 *
 ***********************************************************************************/
CRte.prototype.setColor = function(cmd, color)
{
	if (alib.userAgent.ie) 
	{
		this.iwnd.focus();
		//retrieve selected range
		var sel = this.idoc.selection; 
		if (sel != null) 
		{
			var newRng = sel.createRange();
			newRng = this.rng;
			newRng.select();
		}

		cmd = (cmd == "hilitecolor") ? "backcolor" : cmd;
		
	}

	this.rteCommand(cmd, "#"+color);
}

/***********************************************************************************
 *
 *	Function: 	setFont
 *
 *	Purpose:	Set Font Name
 *
 ***********************************************************************************/
CRte.prototype.setFont = function(cmd, font)
{
	if (alib.userAgent.ie) 
	{
		this.iwnd.focus();
		//retrieve selected range
		var sel = this.idoc.selection; 
		if (sel != null) 
		{
			var newRng = sel.createRange();
			newRng = this.rng;
			newRng.select();
		}
	}

	this.rteCommand(cmd, font);
}

/***********************************************************************************
 *
 *	Function: 	insertImage
 *
 *	Purpose:	Insert an image
 *
 ***********************************************************************************/
CRte.prototype.insertImage = function(path)
{
	if (alib.userAgent.ie) 
	{
		this.iwnd.focus();
		//retrieve selected range
		var sel = this.idoc.selection; 
		if (sel != null) 
		{
			var newRng = sel.createRange();
			newRng = this.rng;
			newRng.select();
		}
	}

	this.rteCommand("InsertImage", path);
}


/***********************************************************************************
 *
 *	Function: 	insertLink
 *
 *	Purpose:	Create or activate a link
 *
 ***********************************************************************************/
CRte.prototype.insertLink = function(path)
{
	if (alib.userAgent.ie) 
	{
		this.iwnd.focus();
		//retrieve selected range
		var sel = this.idoc.selection; 
		if (sel != null) 
		{
			var newRng = sel.createRange();
			newRng = this.rng;
			newRng.select();
		}
	}

	this.rteCommand("Unlink", null);
	this.rteCommand("CreateLink", path);
}

/***********************************************************************************
 *
 *	Function: 	paste
 *
 *	Purpose:	Paste contents of clipoard
 *
 ***********************************************************************************/
CRte.prototype.paste = function(convertoplain)
{
	if (alib.userAgent.ie) 
	{
		this.iwnd.focus();
		//retrieve selected range
		var sel = this.idoc.selection; 
		if (sel != null) 
		{
			var newRng = sel.createRange();
			newRng = this.rng;
			newRng.select();
		}
	}

	this.rteCommand("Paste", null);
}

/***********************************************************************************
 *
 *	Function: 	setRange
 *
 *	Purpose:	Set and store selection range
 *
 ***********************************************************************************/
CRte.prototype.setRange = function()
{
	//function to store range of current selection
	if (alib.userAgent.ie) 
	{
		var selection = this.idoc.selection; 
		if (selection != null) this.rng = selection.createRange();
	} 
	else 
	{
		var selection = this.iwnd.getSelection();
		this.rng = selection.getRangeAt(selection.rangeCount - 1).cloneRange();
	}
}

/***********************************************************************************
 *
 *	Function: 	rteCommand
 *
 *	Purpose:	Issue commands to the iframe window
 *
 *	Arguments:	1. command:string - name of command to run
 *				2. option:string - value to pass with command
 *
 ***********************************************************************************/
CRte.prototype.rteCommand = function(command, option)
{
	try 
	{
		this.iwnd.focus();
	  	this.idoc.execCommand(command, false, option);
		this.iwnd.focus();
	} 
	catch (e) 
	{
		alert(e);
	}
}

/***********************************************************************************
 *
 *	Function: 	stripHtml
 *
 *	Purpose:	Remove all html for pasting as plain text
 *
 *	Arguments:	1. command:string - name of command to run
 *				2. option:string - value to pass with command
 *
 ***********************************************************************************/
CRte.prototype.paste = function()
{
	this.setRange();
	this.rteCommand("Paste", false);
	/*
	try 
	{
		var re= /<\S[^><]*>/g
		arguments[i].value=arguments[i].value.replace(re, "")
	} 
	catch (e) 
	{
		alert(e);
	}
	*/
}

/***********************************************************************************
 *
 *	Function: 	setElementHtml
 *
 *	Purpose:	Replace the innerhtml of an element by id
 *
 *	Arguments:	1. id:string - id of element
 *				2. html:string - value to place in element innerHTML
 *
 ***********************************************************************************/
CRte.prototype.setElementHtml = function(id, html)
{
	// TODO: IE seems to have trouble with timeing and finding the element
	if(alib.userAgent.ie)
	{
		var e = this.idoc.getElementById(id);
	}
	else
		var e = this.ifrm.contentDocument.getElementById(id);
	if (e)
		e.innerHTML = html;
	/*
	try 
	{
	} 
	catch (e) 
	{
	}
	*/
}



/***********************************************************************************
 *
 *	Function: 	toggleHtmlSrc
 *
 *	Purpose:	Toggle HTML Source
 *
 ***********************************************************************************/
CRte.prototype.toggleHtmlSrc = function()
{
	if (this.f_src)
	{
		if (alib.userAgent.ie) 
		{
			//fix for IE
			var output = escape_utf8(this.idoc.body.innerText);
			output = output.replace("%3CP%3E%0D%0A%3CHR%3E", "%3CHR%3E");
			output = output.replace("%3CHR%3E%0D%0A%3C/P%3E", "%3CHR%3E");
			
			this.idoc.body.innerHTML = unescape_utf8(output);
		} 
		else 
		{
			var htmlSrc = this.idoc.body.ownerDocument.createRange();
			htmlSrc.selectNodeContents(this.idoc.body);
			this.idoc.body.innerHTML = htmlSrc.toString();
		}

		this.f_src = false;
	}
	else
	{
		if (alib.userAgent.ie) 
		{
			this.idoc.body.innerText = this.idoc.body.innerHTML;
		} 
		else 
		{
			var htmlSrc = this.idoc.createTextNode(this.idoc.body.innerHTML);
			this.idoc.body.innerHTML = "";
			this.idoc.body.appendChild(htmlSrc);
		}

		this.f_src = true;
	}
}

/***********************************************************************************
 *
 *	Function: 	print
 *
 *	Purpose:	Print rte
 *
 ***********************************************************************************/
CRte.prototype.print = function(container, width, height, html)
{
	if (container)
	{
		this.createToolbar(container);
		container.appendChild(this.ifrm);

		if (width)
			this.ifrm.style.width = width;
		if (height)
			this.ifrm.style.height = height;

		var htm = (typeof html != 'undefined') ? html : '';

        // Check return if false
		if(!this.enableDesign(htm))
            return false; // do not execute if idoc is null
        
		var me = this;
		var bcb = function()
		{
			if (alib.userAgent.ie && !me.rng) 
			{
				me.setRange()
				//retrieve selected range
				var sel = me.idoc.selection; 
				if (sel != null) 
				{
					var newRng = sel.createRange();
					newRng = me.rng;
					newRng.select();
				}
			}
			me.updateText();
			me.onChange();
		}

		if(alib.userAgent.ie)
			this.ifrm.attachEvent("onblur", bcb);
		else
		{
			this.iwnd.addEventListener("blur",bcb,false);
			var cd = this.iwnd;
			this.ifrm.addEventListener("load",function(){ cd.addEventListener("blur",bcb,false); } ,false);
		}

		var br = alib.dom.createElement("br");
		container.appendChild(br);
		container.appendChild(this.hdntxt);
	}
}
