/**
* @fileOverview alib.ui.Editor class
*
* This is the editor class to be used with alib.
*
* Exampl:
* <code>
* 	var button = alib.ui.Editor(document.getElementById("mytextarea"), {className:"blue"});
* </code>
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011-2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Alib_Ui_Editor
 *
 * @constructor
 * @param {INPUT} inpt Optional input element
 * @param {Object} options Optional options object
 */
function Alib_Ui_Editor(inpt, options)
{
	/**
	 * Set a default block element to be used when the user presses return
	 *
	 * Common example: "<p>"
	 *
	 * @public
	 * @var {string}
	 */
	this.defaultBlockElement = "<p>";

	/**
	 * Handle to editor
	 *
	 * @private
	 * @var {CKEDITOR.editor}
	 */
	this.editor = null;

	/**
	 * Handle to CodeMirror editors
	 *
	 * @private
	 * @var {CodeMirror}
	 */
	this.codeMirror = null;

	// Legacy
	this.ifrm = alib.dom.createElement("iframe");
	this.ifrm.border = '0';
	this.ifrm.frameBorder = '0';
    this.ifrm.src = "about:blank";
	this.ifrm.id = "CRteIframe";
	alib.dom.styleSetClass(this.ifrm, "CRteIframe");

	// Now create the hidden input that will store the raw values
	this.hdntxt = alib.dom.createElement("input");
    this.hdntxt.type = "hidden";
    if(typeof inpt != "undefined")    
	{
		if (inpt.id)
			this.hdntxt.id = inpt.id;
    
		if(inpt.getAttribute("name"))
			this.hdntxt.setAttribute("name", inpt.getAttribute("name"));
	}

	/**
	 * If set always work with the full document rather than just the body
	 *
	 * This param is handy when you want to set and get the full document with the title and head
	 */
	this.bodyOnly = true;

	/**
	 * Toolbar container
	 *
	 * @private
	 * @var {DOMElement}
	 */
	this.toolbarCon = null;
    this.editorCon = null;
	
	this.idoc = null;	
	this.f_src = false;	
	this.rte_id = '1';
	this.frm_input = (typeof inpt != "undefined") ? inpt : null;

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
                      
    this.toolButtonObj = new Object(); // Contains the toolbar buttons
    this.isFullscreen = false;
    
    this.originalHeight = null;
    this.originalWidth = null;

	/**
	 * Optional css source files to use for the editor
	 *
	 * @public
	 * @var {array}
	 */
	this.cssFiles = new Array();
}

/**
 * Print the rte
 *
 * @public
 */
Alib_Ui_Editor.prototype.print = function(container, width, height, html)
{
    this.mainCon = container;
    this.editorCon = alib.dom.createElement("div", this.mainCon);
    this.editorCon.id = "editorCon";
	if (container)
	{
		this.toolbarCon = alib.dom.createElement("div", this.editorCon);
		this.createToolbar();
        
		this.editorCon.appendChild(this.ifrm);

		if (width)
			this.ifrm.style.width = width;
        
		var htm = (typeof html != 'undefined') ? html : '';

        // Check return if false
		if(!this.enableDesign(htm))
            return false; // do not execute if idoc is null
        
		// Add on blur so that value is automatically updated
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
        {
            this.ifrm.attachEvent("onblur", bcb);
        }
		else
		{
            this.iwnd.addEventListener("blur",bcb,false);
			var cd = this.iwnd;
			this.ifrm.addEventListener("load",
                        function()
                        { 
                            cd.addEventListener("blur",bcb,false); 
                        } ,false);
		}

		var hdnTxtCon = alib.dom.createElement("div");
		hdnTxtCon.style.display = "none";
		this.editorCon.appendChild(hdnTxtCon);
		hdnTxtCon.appendChild(this.hdntxt);
	}

	if (height)
		this.setHeight(height);
}

/**
 * Onchange callback
 *
 * @public
 */
Alib_Ui_Editor.prototype.onChange = function()
{
}

/**
 * Set the height of the editor
 *
 * @public
 */
Alib_Ui_Editor.prototype.setHeight = function(height)
{
	alib.dom.styleSet(this.editorCon, "height", height);

	var newHeight = alib.dom.getElementHeight(this.editorCon) - alib.dom.getElementHeight(this.toolbarCon);
	alib.dom.styleSet(this.ifrm, "height", (newHeight - 2) + "px"); // subtract 2px for the border
}

/**
 * Focus on the input
 *
 * @public
 */
Alib_Ui_Editor.prototype.focus = function()
{
	this.iwnd.focus();
}

/**
 * Move editor in and out of design mode
 */
Alib_Ui_Editor.prototype.toggleHtmlSrc = function()
{
	if (this.f_src)
	{
		// Save last scroll position
		if (this.codeMirror)
		{
			var info = this.codeMirror.getScrollInfo();
			this.srcScrollPos = info.top;
		}

		var currentVal = this.codeMirror.getValue();
		this.f_src = false;
		this.enableDesign(currentVal);
		this.toolButtonObj.src.toggle(false);
	}
	else
	{
		var currentVal = this.getValue();
		this.setValue("");
		this.f_src = true;
		this.enableDesign("", false);

		// Load CSS
		var cssref = this.idoc.createElement('link');
		cssref.rel = "stylesheet";
		cssref.href = alib.getBasePath() + "ui/Editor/codemirror/lib/codemirror.css";
		this.idoc.getElementsByTagName("head")[0].appendChild(cssref);

		// Load codemirror into iframe
		var fileRef = this.idoc.createElement('script');
		fileRef.cls = this;
		fileRef.htmlValue= currentVal;
		fileRef.editorHtml = 'window.codeMirrorEditor = CodeMirror(document.body, {' +
									'lineNumbers: true,' +
									'lineWrapping: true,' +
									'mode: "application/x-httpd-php",' +
									'indentUnit: 4,' +
									'indentWithTabs: true,' +
									'enterMode: "keep",' +
									'tabMode: "shift"' +
								  '});';
		if (alib.userAgent.ie)
		{
			fileRef.onreadystatechange = function () 
			{ 
				if (this.readyState == "complete" || this.readyState == "loaded") 
				{
					var init = this.cls.idoc.createElement("script");
					init.innerHTML = this.editorHtml;
					this.cls.idoc.getElementsByTagName("head")[0].appendChild(init);
					this.cls.codeMirror = this.cls.iwnd.codeMirrorEditor;
					this.cls.codeMirror.setValue(this.htmlValue);
					this.cls.codeMirror.scrollTo(0, this.cls.srcScrollPos);
				}
			};
		}
		else
		{
			fileRef.onload = function () 
			{ 
				var init = this.cls.idoc.createElement("script");
				init.innerHTML = this.editorHtml;
				this.cls.idoc.getElementsByTagName("head")[0].appendChild(init);
				this.cls.codeMirror = this.cls.iwnd.codeMirrorEditor;
				this.cls.codeMirror.setValue(this.htmlValue);
				this.cls.codeMirror.scrollTo(0, this.cls.srcScrollPos);
			};
		}

		fileRef.type = "text/javascript";
		fileRef.src =  alib.getBasePath() + "ui/Editor/codemirror_full.js";
		this.idoc.getElementsByTagName("head")[0].appendChild(fileRef);

		this.toolButtonObj.src.toggle(true);
	}
		
	// Rebuild toolbar based on mode
	//this.createToolbar();
	
	// Changing the mode changes which buttons are enabled
	this.setToolbarMode();
}

/**
 * Note: This is the old src option
 *
 * Move editor in and out of design mode
Alib_Ui_Editor.prototype.toggleHtmlSrc = function()
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
 */

/**
 * Replace the innherHTML of an element in the editor document by id
 *
 * @param {string} id The id of the element to change
 * @param {string} html The raw html to insert
 */
Alib_Ui_Editor.prototype.setElementHtml = function(id, html)
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

/**
 * Paste contents of clipboard into the editor
 */
Alib_Ui_Editor.prototype.paste = function()
{
	this.setRange();
	this.rteCommand("Paste", false);
}

/**
 * Execute an command in the editor
 *
 * @public
 * @param {string} command Name of the command to run
 * @param {stirng} value The value to pass with the command
 */
Alib_Ui_Editor.prototype.rteCommand = function(command, option)
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

/**
 * Set and store the current selected range
 */
Alib_Ui_Editor.prototype.setRange = function()
{
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

/**
 * Create or activate a link
 *
 * @param {string} path The URI
 */
Alib_Ui_Editor.prototype.insertLink = function(path)
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

/**
 * Insert an image into the document at the current selected range
 *
 * @param {string} path The path to the image
 */
Alib_Ui_Editor.prototype.insertImage = function(path)
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

/**
 * Build the toolbar based on the settings for this editor
 */
Alib_Ui_Editor.prototype.createToolbar = function()
{
	var container = this.toolbarCon;
	container.innerHTML = "";

	// Add toolbar
	var tb = new alib.ui.Toolbar();

	/*
	if (this.f_src)
	{
		this.createToolbarSrc(tb);
	}
	else
	{
		this.createToolbarHtml(tb);
	}
	*/
	this.createToolbarHtml(tb);

	// Print toolbar
	tb.print(container);
}

/**
 * Build the toolbar based on the settings for this editor
 *
 * @param {alib.ui.Toolbar) tb The current toolbar to add items to
 */
Alib_Ui_Editor.prototype.createToolbarHtml = function(tb)
{
	var me = this;

	var imgroot = alib.getBasePath();

	// bold
	this.toolButtonObj.bold = alib.ui.ToolbarToggleButton("<img src='" + imgroot + "/images/bold.gif' />", {
		onclick:function(){this.cls.rteCommand("bold", ''); }, cls:this
	});
	tb.addChild(this.toolButtonObj.bold, true, "bold");

	// italic
	this.toolButtonObj.italic = alib.ui.ToolbarToggleButton("<img src='" + imgroot + "/images/italic.gif' />", {
			onclick:function(){this.cls.rteCommand("italic", ''); 
	}, cls:this});
	tb.addChild(this.toolButtonObj.italic, true, "italic");

	// underline
	this.toolButtonObj.underline = alib.ui.ToolbarButton("<img src='" + imgroot + "/images/underline.gif' />", {
			onclick:function(){this.cls.rteCommand("underline", ''); }, cls:this
	});
	tb.addChild(this.toolButtonObj.underline, true, "underline");

	// Spacer
	tb.addChild(new alib.ui.ToolbarSeparator());

	// Justify
	var toggler = new alib.ui.ButtonToggler();
	var b1 = new alib.ui.ToolbarButton("<img src='" + imgroot + "/images/left_just.gif' />", {
		onclick:function(){this.cls.rteCommand("justifyleft", ''); }, cls:this
	});
	toggler.add(b1, "left");
	tb.addChild(b1, true, "justifyleft");
	b1.toggle(true);
	var b2 = new alib.ui.ToolbarButton("<img src='" + imgroot + "/images/centre.gif' />", {
		onclick:function(){this.cls.rteCommand("justifycenter", ''); }, cls:this
	});
	toggler.add(b2, "center");
	tb.addChild(b2, true, "justifycenter");
	var b3 = new alib.ui.ToolbarButton("<img src='" + imgroot + "/images/right_just.gif' />", {
		onclick:function(){this.cls.rteCommand("justifyright", ''); }, cls:this
	});
	toggler.add(b3, "right");
	tb.addChild(b3, true, "justifyright");
	this.toolButtonObj.justify = toggler;
    
    // Spacer
    tb.addChild(new alib.ui.ToolbarSeparator());
    
    // Horizontal Rule Button
    var btnHr = new alib.ui.ToolbarButton("<img src='" + imgroot + "/images/hr.gif' />", {
    onclick:function(){this.cls.rteCommand("inserthorizontalrule", ''); }, cls:this
    });
    tb.addChild(btnHr, "Horizontal Rule");
    
    // Image Button
    var btnImage = new alib.ui.ToolbarButton("<img src='" + imgroot + "/images/image.gif' />", {
    onclick:function() { this.cls.insertImageDialog(); }, cls:this
    });
    tb.addChild(btnImage, "Insert Image");
    
    // Link Button
    var btnLink = new alib.ui.ToolbarButton("<img src='" + imgroot + "/images/hyperlink.gif' />", {
    onclick:function() { this.cls.insertLinkDialog(); }, cls:this
    });
    tb.addChild(btnLink, "Insert Link");
    
    // Table Button
    var btnTable = new alib.ui.ToolbarButton("<img src='" + imgroot + "/images/insert_table.gif' />", {
    onclick:function() { this.cls.setRange(); this.cls.insertHtml("<table class='rte'><tbody><tr><td>&nbsp;</td><td>&nbsp;</td></tr></tbody></table>"); }, cls:this
    });
    tb.addChild(btnTable, "Insert Table");
    
    // Spacer
    tb.addChild(new alib.ui.ToolbarSeparator());
    
    // Number List Button
    this.toolButtonObj.olist = new alib.ui.ToolbarButton("<img src='" + imgroot + "/images/numbered_list.gif' />", {
    	onclick:function() { 
					this.cls.rteCommand('insertorderedlist', ''); 
		}, cls:this
    });
    tb.addChild(this.toolButtonObj.olist, "Insert Numbered List");
    
    // Unordered List Button
    this.toolButtonObj.ulist = new alib.ui.ToolbarButton("<img src='" + imgroot + "/images/list.gif' />", {
    	onclick:function() { 
					this.cls.rteCommand('insertunorderedlist', ''); 
		}, cls:this
    });
    tb.addChild(this.toolButtonObj.ulist, "Insert Numbered List");
    
    // Spacer
    tb.addChild(new alib.ui.ToolbarSeparator());
    
    // Font Color
    this.toolButtonObj.color = new alib.ui.ToolbarButton(this.createDropdown("color").createImageMenu(imgroot + "/images/textcolor.gif", imgroot + "/images/textcolor.gif", imgroot + "/images/textcolor.gif"));
    tb.addChild(this.toolButtonObj.color, "Set Font Color");
    
    // Background Color Size
    this.toolButtonObj.bgColor = new alib.ui.ToolbarButton(this.createDropdown("bgcolor").createImageMenu(imgroot + "/images/bgcolor.gif", imgroot + "/images/bgcolor.gif", imgroot + "/images/bgcolor.gif"));
    tb.addChild(this.toolButtonObj.bgColor, "Set Background Color");
    
    // Spacer
    tb.addChild(new alib.ui.ToolbarSeparator());
    
    // Font Style
    this.toolButtonObj.fontStyle = new alib.ui.ToolbarButton(this.createDropdown("style").createButtonMenu("Font", null, null, "small"));
    tb.addChild(this.toolButtonObj.fontStyle, "Set Font Style");
    
    // Font Size
    this.toolButtonObj.fontSize = new alib.ui.ToolbarButton(this.createDropdown("size").createButtonMenu("Size", null, null, "small"));
    tb.addChild(this.toolButtonObj.fontSize, "Set Font Size");
    
    // Font Styles
    this.toolButtonObj.fontTemplate = new alib.ui.ToolbarButton(this.createDropdown("template").createButtonMenu("Styles", null, null, "small"));
    tb.addChild(this.toolButtonObj.fontTemplate, "Styles");
    
    // Spacer
    tb.addChild(new alib.ui.ToolbarSeparator());
    
    // Src
    this.toolButtonObj.src = new alib.ui.ToolbarButton("SRC", {
    	onclick:function() { this.cls.toggleHtmlSrc(); }, cls:this
    });
    tb.addChild(this.toolButtonObj.src, "Src");
    
    // Fullscreen
    this.toolButtonObj.fullScreen = new alib.ui.ToolbarButton("Full Screen", {
    onclick:function(){this.cls.toggleFullscreen(); }, cls:this
    });
    tb.addChild(this.toolButtonObj.fullScreen, true, "Full Screen");
	
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

/**
 * If we are in source mode then disable all editor buttons
 */
Alib_Ui_Editor.prototype.setToolbarMode = function()
{
	if (this.f_src)
	{
		this.toolButtonObj.italic.disable();
		this.toolButtonObj.bold.disable();
		this.toolButtonObj.underline.disable();
		this.toolButtonObj.justify.disable();
		this.toolButtonObj.fontTemplate.disable();
		this.toolButtonObj.fontSize.disable();
		this.toolButtonObj.fontStyle.disable();
		this.toolButtonObj.color.disable();
		this.toolButtonObj.bgColor.disable();
		this.toolButtonObj.olist.disable();
		this.toolButtonObj.ulist.disable();
	}
	else
	{
		this.toolButtonObj.italic.enable();
		this.toolButtonObj.bold.enable();
		this.toolButtonObj.underline.enable();
		this.toolButtonObj.justify.enable();
		this.toolButtonObj.fontTemplate.enable();
		this.toolButtonObj.fontSize.enable();
		this.toolButtonObj.fontStyle.enable();
		this.toolButtonObj.color.enable();
		this.toolButtonObj.bgColor.enable();
		this.toolButtonObj.olist.enable();
		this.toolButtonObj.ulist.enable();
	}
}

/**
 * Create toolbar highlight color
 *
 * @param {DOMElement} container The container where the icon will be
 */
Alib_Ui_Editor.prototype.createToolbarHlColor = function(container)
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

/**
 * Create toolbar font color
 *
 * @param {DOMElement} container The container where the icon will be
 */
Alib_Ui_Editor.prototype.createToolbarFntColor = function(container)
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

/**
 * SEt highlight or foregound color of the selected range
 *
 * @param {string} cmd Name of the command to run
 * @param {string} color The hex name of the color to use
 */
Alib_Ui_Editor.prototype.setColor = function(cmd, color)
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

/**
 * Set the fond of the selected range or the whole document if none selected
 *
 * @param {string} cmd Name of the command to run
 * @param {string} font The hex name of the font to use
 */
Alib_Ui_Editor.prototype.setFont = function(cmd, font)
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

/**
 * Set the local document variable
 *
 * @private
 */
Alib_Ui_Editor.prototype.setDocument = function()
{
    this.iwnd = this.ifrm.contentWindow || this.ifrm.contentDocument;

    if (this.iwnd && this.iwnd.document) 
	{
        this.idoc = this.iwnd.document;
    }
}

/**
 * Update value in hidden text field for forms
 *
 * @private
 * @param {string} html The source to update the text field to
 */
Alib_Ui_Editor.prototype.updateText = function(html)
{
    //this.hdntxt.value = this.idoc.body.innerHTML;

	if (this.f_src && this.codeMirror)
	{
		this.hdntxt.value = this.codeMirror.getValue();
		/*
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
		*/
	}
	else
	{
		var bodyHtml = this.idoc.body.innerHTML;

		/** Work in progress
		if (!this.bodyOnly && this.origHTMLShell)
		{
			var re = /(<body\b[^>]*>)[^<>]*(<\/body>)/i;
			bodyHtml = this.origHTMLShell.replace(re, "$1" + bodyHtml + "$2");
		}
		*/

		this.hdntxt.value = bodyHtml;
	}

	if (this.frm_input)
		this.frm_input.value = this.hdntxt.value
}

/**
 * Get the text value of the editor
 *
 * @public
 */
Alib_Ui_Editor.prototype.getValue = function()
{
	this.updateText();

    return this.hdntxt.value;
}

/**
 * Sert the value of the editor
 *
 * @public
 * @param {string} html The html of the document to set
 */
Alib_Ui_Editor.prototype.setValue = function(html)
{
	if (this.f_src && this.codeMirror)
	{
		this.codeMirror.setValue(html);
	}
	else
	{
		if (!this.bodyOnly && html)
		{
			// If html has full document then use the set value
			var frameHtml = html;

			/** Work in progress for handing full html
			var re = /(<body\b[^>]*>)[^<>]*(<\/body>)/i;
			this.origHTMLShell = html.replace(re, "$1" + '' + "$2");
			*/
		}
		else
		{
			// html is just an HTML part so encapsulate it in an HTML doc
			var frameHtml = "<!DOCTYPE HTML>";
			frameHtml += "\n";
			frameHtml = "<html id=\"" + this.rte_id + "\">\n";
			frameHtml += "<head>\n";
			frameHtml += "<meta HTTP-EQUIV='content-type' CONTENT=\"text/html; charset=UTF-8\">\n";
			if (this.cssFiles.length)
			{
				for (var i in this.cssFiles)
					frameHtml += '<link href="' + this.cssFiles[i] + '" media="screen" rel="stylesheet" type="text/css">' + "\n";
			}
			else
			{
				frameHtml += "<style>\n";
				frameHtml += "body {\n";
				frameHtml += " background: #FFFFFF;\n";
				frameHtml += " margin: 0px;\n";
				frameHtml += " padding: 10px;\n";
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
			}
			frameHtml += "</head>\n";
			frameHtml += "<body contenteditable='true'>";
			if (html)
				frameHtml += html;
			frameHtml += "</body>";
			frameHtml += "</html>";
		}

		// Check if idoc is an object and not a null value
		if(this.idoc)
		{
			this.idoc.open();
			this.idoc.write(frameHtml);
			this.idoc.close();
		}
	}

	this.updateText();
}

/**
 * Get the text input that is being used to store the text of the editor for forms
 *
 * @private
 * @return {INPUT}
 */
Alib_Ui_Editor.prototype.getHiddenInput = function()
{
	this.updateText();

    return this.hdntxt;
}

/**
 * Insert HTML at the current carot
 *
 * @public
 * @param {string} html The html text to enter
 */
Alib_Ui_Editor.prototype.insertHtml = function(html)
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

/**
 * Endable design mode in the editor iframe
 *
 * @param {string} html Optional HTML to insert into the iframe once design mode is enabled
 * @param {bool} on Defaults to true if undefiled, but toggles design mode
 */
Alib_Ui_Editor.prototype.enableDesign = function(html, on)
{
	this.setDocument();
	var designModeOn = (typeof on == "undefined") ? true : on;
    
    // If idoc is null, do not execute and return false
    if(!this.idoc || this.idoc == null)
        return false;
        
	this.setValue(html);

	var editorBody = this.idoc.body;

    // turn off spellcheck
	if ('spellcheck' in editorBody && designModeOn) 
		editorBody.spellcheck = true;
        
	if ('contentEditable' in editorBody && designModeOn)
	{
		// allow contentEditable
		editorBody.contentEditable = true;
	}
	else 
	{  
		// Firefox earlier than version 3
		if ('designMode' in this.idoc && designModeOn) 
		{
				// turn on designMode
			this.idoc.designMode = "on";                
		}
	}

	// Attach events
	var me = this;

	var toggleTbFunc = function(e)
	{
		if (me.f_src)
			return;

		// Untoggle bold, italic, and under buttons
		me.toolButtonObj.bold.toggle(false);
		me.toolButtonObj.italic.toggle(false);
		me.toolButtonObj.underline.toggle(false);
		
		var currentElem = me.currentCaretElem();
		
		if(!currentElem)
			currentElem = e.target();

		while(currentElem = me.toggleTagname(currentElem));
	}

	if(alib.userAgent.ie)
	{
		this.ifrm.attachEvent("onclick", toggleTbFunc);
		this.ifrm.attachEvent("onkeyup", toggleTbFunc);
	}
	else
	{
		this.iwnd.addEventListener("click",toggleTbFunc,false);
		this.iwnd.addEventListener("keyup",toggleTbFunc,false);
		var cd = this.iwnd;
		this.ifrm.addEventListener("load",
					function()
					{ 
						cd.addEventListener("click",toggleTbFunc,false); 
						cd.addEventListener("keyup",toggleTbFunc,false); 
					}, false);
	}

	// Handle default blockElement if set
	if (this.defaultBlockElement)
	{
		this.listen("keydown", function(e) {
			var currentElem = me.currentCaretElem();
			if(!currentElem)
				me.setFont("formatBlock", me.defaultBlockElement)
		});
	}

	/*
	if (alib.userAgent.ie) 
	{
		this.idoc.designMode = (designModeOn) ? "On" : "Off";
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
	*/
    
    return true;
}

/**
 * Listen for events in the current editor
 */
Alib_Ui_Editor.prototype.listen = function(evntName, cbFunction)
{
	if(alib.userAgent.ie)
	{
		alib.dom.addEvent(this.ifrm, evntName, cbFunction);
	}
	else
	{
		if (this.iwnd)
		{
			alib.dom.addEvent(this.ifrm, evntName, cbFunction);
		}
		else
		{
			alib.dom.addEvent(this.ifrm, evntName, cbFunction);

			/* TODO: I'm not sure why this is always being used throughout this class but leaving here just in case
			var cd = this.iwnd;
			this.ifrm.addEventListener("load",
						function()
						{ 
							cd.addEventListener("click",toggleTbFunc,false); 
							cd.addEventListener("keyup",toggleTbFunc,false); 
						}, false);
						*/
		}
	}
}

/**
 * Checks if parent tag has tagname
 *
 * @param {DOMElement} elem     element name to be checked
 */
Alib_Ui_Editor.prototype.toggleTagname = function(elem)
{
    switch(elem.tagName)
    {
        case "B":
            this.toolButtonObj.bold.toggle(true);
            break;
        case "I":
            this.toolButtonObj.italic.toggle(true);
            break;
        case "U":
            this.toolButtonObj.underline.toggle(true);
            break;
        default:
            this.checkInlineStyle(elem);
            break;
    }
    
    if(elem.parentNode)
        return elem.parentNode;
    else
        return null;
}

/**
 * Checks the inline style
 *
 * @param {DOMElement} elem     element name to be checked
 */
Alib_Ui_Editor.prototype.checkInlineStyle = function(elem)
{
	// TODO: Marl, this is throwing an exception with jquery which alib.dom.styleGet us using
	// Error: TypeError: a.ownerDocument is null
	// Source File: file:///C:/Users/Sky%20Stebnicki/sandbox/lib/js/trunk/jquery.min.js
	// Line: 4
	return;

    // Check Bold
    var bold = alib.dom.styleGet(elem, "font-weight");
    switch(bold)
    {
        case "bold":
        case "bolder":
        case "600":
        case "700":
        case "800":
        case "900":
            this.toolButtonObj.bold.toggle(true);
            break;
    }
    
    // Check Italize
    var italic = alib.dom.styleGet(elem, "font-style");
    switch(italic)
    {
        case "italic":
            this.toolButtonObj.italic.toggle(true);
            break;
    }
    
    // Check Underline
    var underline = alib.dom.styleGet(elem, "text-decoration");
    switch(underline)
    {
        case "underline":
            this.toolButtonObj.underline.toggle(true);
            break;
    }
}

/**
 * Gets the current element tagname of caret
 *
 */
Alib_Ui_Editor.prototype.currentCaretElem = function()
{
    var target = null;
            
    if(this.iwnd.getSelection)
    {
        target = this.iwnd.getSelection().getRangeAt(0).commonAncestorContainer;
        if(target.nodeType===1)
            return target;
        else
            return target.parentNode;
    }
    else if(this.idoc.selection)
    {
        return this.idoc.selection.createRange().parentElement();
    }
}

/**
 * Toggles the fullscreen display
 *
 */
Alib_Ui_Editor.prototype.toggleFullscreen = function()
{
    if(!this.isFullscreen)
    {
        if(!this.originalHeight)
            this.originalHeight = this.ifrm.style.height;
        
        if(!this.originalWidth)
            this.originalWidth = this.ifrm.style.width;
        
        var browserHeight = alib.dom.GetDocumentHeight();
		//var newHeight = browserHeight - alib.dom.getElementHeight(this.toolbarCon);
        
        alib.dom.styleSet(this.editorCon, "position", "absolute");
        alib.dom.styleSet(this.editorCon, "z-index", "10");
        //alib.dom.styleSet(this.editorCon, "overflow", "hidden");
        alib.dom.styleSet(this.editorCon, "top", "0");
        alib.dom.styleSet(this.editorCon, "left", "0");
        //alib.dom.styleSet(this.editorCon, "height", browserHeight + "px");
        alib.dom.styleSet(this.editorCon, "width", "100%");

		// Set body overflow to hidden
		this.originalBodyOverflow = alib.dom.styleGet(document.body, "overflow");
		alib.dom.styleSet(document.body, "overflow", "hidden");

		this.setHeight(browserHeight);
        
		/*
        this.ifrm.style.width = "100%";
        this.ifrm.style.height = newHeight + "px";
		*/
        this.ifrm.style.width = "100%";
        
        //this.toolButtonObj.fullScreen.setText("Exit Fullscreen");        
		this.toolButtonObj.fullScreen.toggle(true);
        this.isFullscreen = true;
    }
    else
    {
        this.ifrm.style.width = this.originalWidth;
        //this.ifrm.style.height = this.originalHeight;
		alib.dom.styleSet(document.body, "overflow", this.originalBodyOverflow);
        
        alib.dom.styleSet(this.editorCon, "position", "static");
        //alib.dom.styleSet(this.editorCon, "height", this.originalHeight);
        alib.dom.styleSet(this.editorCon, "width", this.originalWidth);

		this.setHeight(this.originalHeight);
        
		this.toolButtonObj.fullScreen.toggle(false);
        //this.toolButtonObj.fullScreen.setText("Fullscreen");
        this.isFullscreen = false;
    }
}

/**
 * Shows the insert image dialog
 *
 */
Alib_Ui_Editor.prototype.insertImageDialog = function()
{
    if (typeof AntFsOpen == "undefined") // Check for ANT AntFsOpen
    {
        this.setRange();

        var imgDialog = new CDialog();
        imgDialog.cls = this;
        
        imgDialog.onPromptOk = function(val)
        {
            this.cls.insertImage(val);
        }

        imgDialog.promptBox("Enter the URL of your image", "Insert Image", "");
    }
    else // Use ANT file system
    {
        var cbrowser = new AntFsOpen();
        cbrowser.filterType = "jpg:jpeg:png:gif";
        cbrowser.cbData.cls = this;
        cbrowser.onSelect = function(fid, name, path) 
        {
            this.cbData.cls.insertImage("http://" + document.domain + "/files/images/"+fid);
        }
        
        this.setRange(); 
        cbrowser.showDialog();
    }
}

/**
 * Shows the insert link dialog
 *
 */
Alib_Ui_Editor.prototype.insertLinkDialog = function()
{
    this.setRange();

    var linkDialog = new CDialog();
    linkDialog.cls = this;
    
    linkDialog.onPromptOk = function(val)
    {
        this.cls.insertLink(val);
    }

    linkDialog.promptBox("Enter the link path", "Insert Link", "");
}

/**
 * Shows the insert link dialog
 *
 */
Alib_Ui_Editor.prototype.createDropdown = function(type)
{
    var dmcon = new CDropdownMenu();
    dmcon.cls = this;
    var dcon = dmcon.addCon();
    
    switch(type)
    {
        case "color":
            this.createToolbarFntColor(dcon);
            break;
        case "bgcolor":
            this.createToolbarHlColor(dcon);
            break;
        case "style":
            dmcon.onmousedown = function() { this.cls.setRange(); }
            dmcon.tabIndex = -1;
            var fonts = ["Arial", "Georgia", "Tahoma", "Courier New", "Times New Roman", "Verdana"];
            for (var i = 0; i < fonts.length; i++)
                dmcon.addEntry("<span style='font-family:"+fonts[i]+"'>"+fonts[i]+"</span>", function (cls, f) { cls.setFont("fontname", f); }, null, null, [this, fonts[i]]);
            break;
        case "size":
            dmcon.onmousedown = function() { this.cls.setRange(); }
            dmcon.tabIndex = -1;
            var sizes = [[1, "Smallest"], [2, "X-Small"], [3, "Small"], [4, "Normal"], [5, "Large"], [6, "X-Large"], [7, "Huge"]];
            for (var i = 0; i < sizes.length; i++)
                dmcon.addEntry(sizes[i][1], function (cls, f) { cls.setFont("fontsize", f); }, null, null, [this, sizes[i][0]]);
            break;
        case "template":
            dmcon.onmousedown = function() { this.cls.setRange(); }
            dmcon.tabIndex = -1;
            var styles = [["Body / Normal", "formatBlock", "<p>"], 
                          ["Heading 1", "formatBlock", "<h1>"], 
                          ["Heading 2", "formatBlock", "<h2>"], 
                          ["Heading 3", "formatBlock", "<h3>"], 
                          ["Heading 4", "formatBlock", "<h4>"], 
                          ["Heading 5", "formatBlock", "<h5>"],
                          ["Quote", "formatBlock", "<blockquote>"]];
            for (var i = 0; i < styles.length; i++)
                dmcon.addEntry(styles[i][0], function (cls, func, val) { cls.setFont(func, val); }, null, null, [this, styles[i][1], styles[i][2]]);
            break;
    }
    
    return dmcon;
}
