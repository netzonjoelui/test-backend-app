/*======================================================================================
	
	Module:		CDropdownMenu

	Purpose:	Kind of like a window but embedded in the document

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2006 Aereus Corporation. All rights reserved.

	Usage:
				var ccTble = new CContentTable("My Window Name", "100px", "100px");
				ccTble.print(parent_div); // If no parent div then just doc.write

======================================================================================*/

// Define globals
// -----------------------------------------------------------
var g_mRootDiv = null;
var g_mClearTimer = null;
var g_mClearObj = new Object();

var mDivMClicked = null;
var mDivRootMenu = null;
var GTIMERCLEAR = null;

var mRootBtn = null;

var mChildActive = null;

var g_CDMenues = new Array();
var g_CDMenCount = 0;

function CDropAddHandler(doc)
{
	doc.onclick = function() 
	{
		if (g_mRootDiv)
		{
			if ((g_mRootDiv.m_HaveMouseFocus == false || !g_mRootDiv.m_HaveMouseFocus))
			{
				g_mRootDiv.unloadMe();
				g_mRootDiv = null;
			}
		}
	}
}

function CDropdownMenuDocClick()
{
	try
	{
		if (g_mRootDiv)
		{
			if ((g_mRootDiv.m_HaveMouseFocus == false || !g_mRootDiv.m_HaveMouseFocus))
			{
				if (g_mRootDiv.m_button.onclickold)
					g_mRootDiv.m_button.onclick = g_mRootDiv.m_button.onclickold;

				g_mRootDiv.unloadMe();
				g_mRootDiv = null;
			}
		}
	}
	catch (e) {}
}

function CDropdownMenu(pnt)
{
	if (pnt)
		this.m_parent = pnt;
	
	// Create an absolutely positioned invisible (for now) div
	this.zIndex = (pnt) ? pnt.zIndex+1 : 800;
	//this.m_div = ALib.m_document.createElement("div", alib.dom.m_document.body);
	this.m_div = ALib.m_document.createElement("div");
	this.m_div.style.position = "absolute";
	this.m_div.style.top = "0px";
	this.m_div.style.left = "0px";
	this.m_div.style.zIndex = this.zIndex ;
	this.m_div.menuref = this;
	this.m_div.onmouseover = function ()
	{
		this.menuref.handleMouseOver();
	}
	this.m_div.onmouseout = function ()
	{
		this.menuref.handleMouseOut();
	}
	this.m_div.style.display = "none";
	
	// Put table inside of div
	this.m_table = ALib.m_document.createElement("table");
	this.m_table.setAttribute("border","0");
	this.m_table.border = "0";
	this.m_table.setAttribute("cellpadding","0");
	this.m_table.cellPadding = "0";	
	this.m_table.setAttribute("cellspacing","0");
	this.m_table.cellSpacing = "0";
	this.m_table.className = "CDropdownMenuContainer";
	this.m_tbody = ALib.m_document.createElement("tbody");
	
	// Add table body
	this.m_table.appendChild(this.m_tbody);
	this.m_div.appendChild(this.m_table);
	this.m_fulldiv = ALib.m_document.createElement("span");
	this.m_id = g_CDMenCount;

	// Set type (rght, down, left, up)
	if (pnt)
		this.m_droptype = 'right';
	else
		this.m_droptype = 'down';

	// This is used for ANT
	if (typeof Ant != 'undefined')
		this.m_themename = Ant.m_theme;
	else
		this.m_themename = 'default';
	
	g_CDMenues[g_CDMenCount] = this;
	g_CDMenCount++;	
    
    // Handle Duplicate Menus
    this.handleDuplicates = false;
    this.dmTitles = new Array();
}

CDropdownMenu.prototype.destroyMenu = function (title)
{
	if(this.m_parent)
	{
		g_mRootDiv.destroyMenu();
	}
	else
	{
		//g_CDMenues.splice(index, howMany
		this.unloadMe();
		delete g_CDMenues[this.m_id];
	}
} 

CDropdownMenu.prototype.createLinkMenu = function (title)
{
	var div = ALib.m_document.createElement("a");
	div.href = "javascript:void(0);";
	div.menuref = this;
	div.onclick = function()
	{
		this.menuref.toggleMenu();
	}
	div.onmouseover = function ()
	{
		this.menuref.handleMouseOver();
	}
	div.onmouseout = function ()
	{
		this.menuref.handleMouseOut();
	}
	div.style.cursor = "pointer";
	div.innerHTML = title;
	
	this.m_button = div;
	
	this.m_fulldiv.appendChild(div);
	alib.dom.m_document.body.appendChild(this.m_div);
	//this.m_fulldiv.appendChild(this.m_div);
	
	return this.m_fulldiv;
}

// Create a right-click context menu
CDropdownMenu.prototype.createContextMenu = function(e, cls_out, cls_over, cls_on)
{
	// You can pass the id of an element
	if (typeof e == "string")
		e = ALib.getElementById(e);

	// Create a test button
	if (cls_out)
		this.m_clsOut = cls_out;
	
	if (cls_over)
		this.m_clsOver = cls_over;
	
	if (cls_on)
		this.m_clsOn = cls_on;
	
	e.menuref = this;

	e.m_cls = this;
	e.oncontextmenu= function() 
	{
		// Temporarily disable the onclick event (store in onclickold)
		this.onclickold = this.onclick;
		this.onclick = null;
	 	var cls = this.m_cls; cls.toggleMenu(); 
		// Resture onclick event
		this.onclick = function() { this.onclick = this.onclickold; };
		//this.onclick = onclickold;
		return false; 
	};

	var funover = function()
	{
		this.m_cls.handleMouseOver();
		
		if (this.menuref.m_clsOver && this.menuref.m_div.style.display == "none")
			alib.dom.styleSetClass(this, this.menuref.m_clsOver);
	}

	var funout = function()
	{
		this.m_cls.handleMouseOut();
		
		if (this.menuref.m_clsOut && this.menuref.m_div.style.display == "none")
			alib.dom.styleSetClass(this, this.menuref.m_clsOut);
	}

	if (alib.userAgent.ie)
	{
		
		e.attachEvent('mouseover', funover);
		e.attachEvent('mouseout', funout);
	}
	else
	{
		e.addEventListener('mouseover', funover, false);
		e.addEventListener('mouseout', funout, false);
	}

	this.m_button = e;
	//this.m_fulldiv.appendChild(this.m_div);
	alib.dom.m_document.body.appendChild(this.m_div);
	e.appendChild(this.m_fulldiv);
	
	//return this.m_fulldiv;
}

CDropdownMenu.prototype.createImageMenu = function (img_out, img_over, img_on)
{
	if (img_out)
		this.m_imageOut = img_out;
	else if (Ant)
		this.m_imageOut = "/images/themes/" + Ant.m_theme + "/buttons/dropdownOut.gif";
	
	if (img_over)
		this.m_imageOver = img_over;
	else if (Ant)
		this.m_imageOver = "/images/themes/" + Ant.m_theme + "/buttons/dropdownOver.gif";
	
	if (img_on)
		this.m_imageOn = img_on;
	else if (Ant)
		this.m_imageOn = "/images/themes/" + Ant.m_theme + "/buttons/dropdownOn.gif";

	var div = ALib.m_document.createElement("span");
	div.menuref = this;
	
	div.style.cursor = "pointer";
	div.m_image = ALib.m_document.createElement("img");
	div.m_image.border = "0";
	div.m_image.src = this.m_imageOut;
	div.appendChild(div.m_image);
	//div.innerHTML = "<img src='" + this.m_imageOut + "' border='0' />";

	div.m_imageOut = this.m_imageOut;
	div.m_imageOver = this.m_imageOver;

	div.onclick = function()
	{
		this.menuref.toggleMenu();
	}
	div.onmouseover = function ()
	{
		this.menuref.handleMouseOver();
		if (this.menuref.m_div.style.display == "none")
			div.m_image.src = this.m_imageOver;
	}
	div.onmouseout = function ()
	{
		this.menuref.handleMouseOut();
		if (this.menuref.m_div.style.display == "none")
			div.m_image.src = this.m_imageOut;
	}
	if (this.onmousedown)
		div.onmousedown = function() { this.menuref.onmousedown(); };

	this.m_button = div;
	this.m_fulldiv.appendChild(div);
	//this.m_fulldiv.appendChild(this.m_div);
	alib.dom.m_document.body.appendChild(this.m_div);
	
	return this.m_fulldiv;
}

CDropdownMenu.prototype.createButtonMenu = function(title, onclk_funct, args, className)
{
	if (!args)
		var args = null;
	if (!onclk_funct)
		var onclk_funct = null;

	var clsName = (className) ? className : "b1";

	/*
	var full_title = "<div style='float:left;' class='CDropdownMenuButtonPrefix'></div>";
	full_title += (title) ? title : '';
	full_title += "<div style='clear:both'></div>";
	*/
	var full_title = alib.dom.createElement("div");

	if (typeof title == "string")
		full_title.innerHTML = title;
	else
		full_title.appendChild(title);

	var icon = alib.dom.createElement("span");
	alib.dom.styleSetClass(icon, "CDropdownMenuButtonPrefix");
	icon.innerHTML = "&nbsp;&nbsp;";
	full_title.appendChild(icon);

	// Create a test button
	var clk = function(mid, onclk_funct, args)
	{
		g_CDMenues[mid].toggleMenu();

		if (typeof onclk_funct == "string")
			eval(onclk_funct);
		else
		{
			if (args)
			{
				switch(args.length)
				{
				case 1:
					onclk_funct(args[0]);
					break;
				case 2:
					onclk_funct(args[0], args[1]);
					break;
				case 3:
					onclk_funct(args[0], args[1], args[2]);
					break;
				case 4:
					onclk_funct(args[0], args[1], args[2], args[3]);
					break;
				case 5:
					onclk_funct(args[0], args[1], args[2], args[3], args[4]);
					break;
				case 6:
					onclk_funct(args[0], args[1], args[2], args[3], args[4], args[5]);
					break;
				default:
					alert("Too many arguments");
					break;
				}
			}
			else if (onclk_funct)
				onclk_funct();
		}
	}

	var btn = new CButton(full_title, clk, [this.m_id, onclk_funct, args], clsName);
	var button_con = btn.getButton();
	var button_tbl = btn.getTable();

	button_con.menuref = this;
	button_con.onmouseover = function ()
	{
		this.menuref.handleMouseOver();
	}
	button_con.onmouseout = function ()
	{
		this.menuref.handleMouseOut();
	}
	if (this.onmousedown)
		button_con.onmousedown = function() { this.menuref.onmousedown(); };
	if (typeof this.tabIndex != "undefined")
	{
		button_con.tabIndex = this.tabIndex;
	}
	
	this.m_button = button_con;
	this.m_fulldiv.appendChild(button_con);
	//this.m_fulldiv.appendChild(this.m_div);
	alib.dom.m_document.body.appendChild(this.m_div);
	
	return this.m_fulldiv;
}

CDropdownMenu.prototype.createCustomnMenu = function(element, cls_out, cls_over, cls_on)
{
	// Create a test button
	if (cls_out)
		this.m_clsOut = cls_out;
	
	if (cls_over)
		this.m_clsOver = cls_over;
	
	if (cls_on)
		this.m_clsOn = cls_on;
	
	element.menuref = this;

	element.onclick = function()
	{
		this.menuref.toggleMenu();
	}

	element.onmouseover = function ()
	{
		this.menuref.handleMouseOver();
		if (this.menuref.m_clsOver && this.menuref.m_div.style.display == "non")
			alib.dom.styleSetClass(this, this.menuref.m_clsOver);
	}
	element.onmouseout = function ()
	{
		this.menuref.handleMouseOut();
		if (this.menuref.m_clsOut && this.menuref.m_div.style.display == "none")
			alib.dom.styleSetClass(this, this.menuref.m_clsOut);
	}
	
	this.m_button = element;
	this.m_fulldiv.appendChild(element);
	//this.m_fulldiv.appendChild(this.m_div);
	alib.dom.m_document.body.appendChild(this.m_div);
	
	return this.m_fulldiv;
}

CDropdownMenu.prototype.addEntry = function (title, funct, icon, icon_text, fargs)
{
    // if duplicate is found and check duplicate is set to true, do not continue
    if(this.checkDuplicates(title))
        return;
    
	var row = ALib.m_document.createElement("tr");
	row.menuref = this;
	
	// Create icon cell
	var cell_icon = ALib.m_document.createElement("td");
	cell_icon.className = "CDropdownMenuIcon";
	cell_icon.style.whiteSpace = "nowrap";
	if (icon)
	{
		cell_icon.innerHTML = "<img border='0' src='"+icon+"' style='padding: 0px; margin: 0px;' />";
	}
	else
	{
		cell_icon.innerHTML = "<div>"+((icon_text) ? icon_text : '')+"</div>";
	}
	row.appendChild(cell_icon);	
    
	// Create link cell
	var cell_link = ALib.m_document.createElement("td");
	cell_link.className = "CDropdownMenuLink";
	cell_link.nowrap = true;
	cell_link.style.whiteSpace = "nowrap";
	cell_link.innerHTML = title;
	row.appendChild(cell_link);
    
	// Create right arrow cell
	var cell_right = ALib.m_document.createElement("td");
	cell_right.className = "CDropdownMenuRight";
	cell_right.nowrap = true;
	cell_right.style.whiteSpace = "nowrap";
	cell_right.innerHTML = "&nbsp;";
	row.appendChild(cell_right);
    
	if (funct)
	{
		row.style.cursor = "pointer";
		row.functname = funct;
		row.onclick = function ()
		{
			if (typeof this.functname != "string")
			{
				if (typeof fargs != "undefined" && fargs)
				{
					switch (fargs.length)
					{
					case 1:
						this.functname(fargs[0]);
						break;
					case 2:
						this.functname(fargs[0], fargs[1]);
						break;
					case 3:
						this.functname(fargs[0], fargs[1], fargs[2]);
						break;
					case 4:
						this.functname(fargs[0], fargs[1], fargs[2], fargs[3]);
						break;
					case 5:
						this.functname(fargs[0], fargs[1], fargs[2], fargs[3], fargs[4]);
						break;
					case 6:
						this.functname(fargs[0], fargs[1], fargs[2], fargs[3], fargs[4], fargs[5]);
						break;
					case 7:
						this.functname(fargs[0], fargs[1], fargs[2], fargs[3], fargs[4], fargs[5], fargs[6]);
						break;
					}
				}
				else
					this.functname();
			}
			else
				eval(this.functname);
			g_mRootDiv.unloadMe();
			g_mRootDiv = null;
		}
	}
	row.onmouseover = function ()
	{
		if (this.menuref.m_activechild)
		{
			this.menuref.m_activechild.unloadMe();
			this.menuref.m_activechild = null;
		}
			
		CDMenuSetRowHighligh(true, this);
	}
	row.onmouseout = function ()
	{
		CDMenuSetRowHighligh(false, this);
	}
	this.m_tbody.appendChild(row);
}

CDropdownMenu.prototype.addSubmenu = function (title, icon, funct, fargs)
{
    var dlmenu = new CDropdownMenu(this);
    
    // if duplicate is found and check duplicate is set to true, do not continue
    if(this.checkDuplicates(title))
        return dlmenu;
	
	var row = ALib.m_document.createElement("tr");
	row.style.cursor = "pointer";
	
	// Create icon cell
	var cell_icon = ALib.m_document.createElement("td");
	cell_icon.className = "CDropdownMenuIcon";
	cell_icon.style.width = '15px';
	cell_icon.style.whiteSpace = "nowrap";
	if (icon)
		cell_icon.innerHTML = "<div style='width:15px;'><img border='0' src='"+icon+"' /></div>";
	else
		cell_icon.innerHTML = "<div style='width:15px;'></div>";
		
	row.appendChild(cell_icon);	
	// Create link cell
	var cell_link = ALib.m_document.createElement("td");
	cell_link.className = "CDropdownMenuLink";
	cell_link.innerHTML = title;
	cell_link.nowrap = true;
	cell_link.style.whiteSpace = "nowrap";
	row.appendChild(cell_link);
	
	// Create right arrow cell
	var cell_right = ALib.m_document.createElement("td");
	cell_right.className = "CDropdownMenuRight";
	cell_right.nowrap = true;
	cell_right.style.whiteSpace = "nowrap";
	// "+this.m_themename+"
	cell_right.innerHTML = "<div class='CDropdownMenuRightIcon' align='center'></div>";
	row.appendChild(cell_right);
	
	if (funct)
	{
		row.style.cursor = "pointer";
		row.functname = funct;
		row.onclick = function ()
		{
			if (typeof this.functname != "string")
			{
				if (typeof fargs != "undefined" && fargs)
				{
					switch (fargs.length)
					{
					case 1:
						this.functname(fargs[0]);
						break;
					case 2:
						this.functname(fargs[0], fargs[1]);
						break;
					case 3:
						this.functname(fargs[0], fargs[1], fargs[2]);
						break;
					case 4:
						this.functname(fargs[0], fargs[1], fargs[2], fargs[3]);
						break;
					case 5:
						this.functname(fargs[0], fargs[1], fargs[2], fargs[3], fargs[4]);
						break;
					case 6:
						this.functname(fargs[0], fargs[1], fargs[2], fargs[3], fargs[4], fargs[5]);
						break;
					case 7:
						this.functname(fargs[0], fargs[1], fargs[2], fargs[3], fargs[4], fargs[5], fargs[6]);
						break;
					}
				}
				else
					this.functname();
			}
			else
				eval(this.functname);
			g_mRootDiv.unloadMe();
			g_mRootDiv = null;
		}
	}
	
	row.menuref = dlmenu;
	dlmenu.m_button = row;
	row.onmouseover = function ()
	{
		this.menuref.handleMouseOver();
		if (this.menuref.m_div.style.display == "none")
			this.menuref.toggleMenu();
		
		window.clearTimeout(g_mClearObj.timer);
		g_mClearObj.m_id = null;
		
		CDMenuSetRowHighligh(true, this);
	}
	row.onmouseout = function ()
	{
		this.menuref.handleMouseOut();
		g_mClearObj.m_id = this.menuref.m_id;
		g_mClearObj.timer = setTimeout('CDMenuClearMenu()', 2000);
		CDMenuSetRowHighligh(false, this);
	}
	
	this.m_tbody.appendChild(row);
	//dlmenu.m_fulldiv.appendChild(dlmenu.m_div);
	alib.dom.m_document.body.appendChild(dlmenu.m_div);
	this.m_fulldiv.appendChild(dlmenu.m_fulldiv);
	
	return dlmenu;
}

CDropdownMenu.prototype.addCon = function()
{
	var row = alib.dom.createElement("tr", this.m_tbody);
	
	// Create icon cell
	var cell = alib.dom.createElement("td", row);
	cell.colSpan = 3;
	cell.menuref = this;

	return cell;
}

CDropdownMenu.prototype.unloadMe = function ()
{
	if (this.m_activechild)
	{
		this.m_activechild.unloadMe();
	}
	this.m_activechild = null;
	
	if (this.m_parent)
		CDMenuSetRowHighligh(false, this.m_button);
	else
	{
		if (this.m_imageOut)
		{
			if (this.m_button && this.m_button.m_image)
				this.m_button.m_image.src = this.m_imageOut;
		}
		
		if (this.m_clsOut)
		{
			if (this.m_button)
				alib.dom.styleSetClass(this.m_button, this.m_clsOut);
		}
	}
		
	this.m_div.onFadeFinished = function() { this.style.display = "none"; };
	ALib.Effect.fadeout(this.m_div, 200);
}

CDropdownMenu.prototype.handleMouseOver = function ()
{
	this.setFocus(true);
	
	// Cancel clear if set to current object
	if (g_mClearObj.m_id == this.m_id)
	{
		window.clearTimeout(g_mClearObj.timer);
		g_mClearObj.m_id = null;
	}
	
	// Open if another dropdown is already open
	if (g_mRootDiv)
	{
		if ((g_mRootDiv.m_HaveMouseFocus == false))
		{
			g_mRootDiv.unloadMe();
			g_mRootDiv = null;
		}
	}
}

CDropdownMenu.prototype.handleMouseOut = function ()
{
	this.m_HaveMouseFocus=false
	if (this.m_parent)
		this.m_parent.m_HaveMouseFocus=false
}

CDropdownMenu.prototype.setFocus = function(focused)
{
	if (focused == true)
		this.m_HaveMouseFocus=true;
	if (this.m_parent)
	{
		CDMenuSetRowHighligh(true, this.m_button);
		this.m_parent.setFocus(true);
	}
}

CDropdownMenu.prototype.toggleMenu = function()
{
	var pos = alib.dom.getElementPosition(this.m_button);
	var tmpy = pos.y;
	var tmpx = pos.x;

	tmpheight=this.m_button.offsetHeight;

	// Get document width
	var doc_height = alib.dom.getDocumentHeight();
	if (ALib.m_document)
	{
		var doc_width = ALib.m_document.body.clientWidth;
	}
	else
	{
		var doc_width = document.body.clientWidth;
	}

	if (this.m_div.style.display == "block")
	{
		this.m_div.onfaded = function()
		{
			this.style.display = 'none';
		}
		//ALib.Effect.fade(this.m_div, 200);
		
		if (this.m_parent)
		{
			if (this.m_parent.m_activechild != this)
			{
				this.m_parent.m_activechild.unloadMe()
				this.m_parent.m_activechild = null;
			}
		}
		else
		{
			this.unloadMe();
			g_mRootDiv = null;

			// Unloaded root, detach event
			if (alib.userAgent.ie)
			{
				ALib.m_document.detachEvent('click', CDropdownMenuDocClick);
			}
			else
			{
				ALib.m_document.removeEventListener('click', CDropdownMenuDocClick, false);
			}
		}	
	}
	else
	{
		this.m_div.style.display = "block";
		ALib.Effect.fadein(this.m_div, -1);

		// Find out of we are out of space
		if ('right' == this.m_droptype)
		{
			//alert(tmpx + this.m_div.offsetWidth + " " + doc_width);
			if ((tmpx + this.m_button.offsetWidth + this.m_div.offsetWidth) >= doc_width)
				this.m_droptype = 'left';
		}

		switch (this.m_droptype)
		{
		case 'up':
			this.m_div.style.top = tmpy - this.m_div.offsetHeight + 'px';
			if ((tmpy - this.m_div.offsetHeight) < 10)
			{
				this.m_div.style.top = tmpy + tmpheight + 'px';
			}
			this.m_div.style.left = tmpx + 'px';
			break;
		case 'down':
			if ((tmpy + this.m_div.offsetHeight) >= doc_height)
				this.m_div.style.top = doc_height - this.m_div.offsetHeight - 1 + 'px';
			else
				this.m_div.style.top = tmpy + tmpheight + 'px';

			if ((tmpx + this.m_div.offsetWidth) >= doc_width)
				this.m_div.style.left = doc_width - this.m_div.offsetWidth - 1 + 'px';
			else
				this.m_div.style.left = tmpx + 'px';
			break;
		case 'right':
			if ((tmpy + this.m_div.offsetHeight) >= doc_height)
				this.m_div.style.top = doc_height - this.m_div.offsetHeight - 1 + 'px';
			else
				this.m_div.style.top = tmpy + 'px';

			this.m_div.style.left = tmpx + this.m_button.offsetWidth - 1 + 'px';
			break;
		case 'left':
			if ((tmpy + this.m_div.offsetHeight) >= doc_height)
				this.m_div.style.top = doc_height - this.m_div.offsetHeight - 1 + 'px';
			else
				this.m_div.style.top = tmpy + 'px';

			this.m_div.style.left = tmpx - this.m_div.offsetWidth + 1 + 'px';
			break;
		}
		
		// clear existing menu
		if (g_mRootDiv && !this.m_parent)
		{
			g_mRootDiv.unloadMe();
		}
			
		if (this.m_parent)
		{
			if (this.m_parent.m_activechild && this.m_parent.m_activechild != this)
				this.m_parent.m_activechild.unloadMe();
			this.m_parent.m_activechild = this;
		}
		else
		{
			g_mRootDiv = this;

			if (this.m_imageOn)
				this.m_button.m_image.src = this.m_imageOn;

			if (this.m_clsOn)
			{
				alib.dom.styleSetClass(this.m_button, this.m_clsOn);
			}
		}

		if (alib.userAgent.ie)
		{
			ALib.m_document.attachEvent('onclick', CDropdownMenuDocClick);
		}
		else
		{
			ALib.m_document.addEventListener('click', CDropdownMenuDocClick, false);
		}
	}
}

CDropdownMenu.prototype.checkDuplicates = function(title)
{
    // Check for duplicates if enabled
    if(this.handleDuplicates)
    {
        for(dmTitle in this.dmTitles)
        {
            if(this.dmTitles[dmTitle] == title) // found existing
                return true;
        }
    }
    
    // Save Titles in the array
    var idx = this.dmTitles.length;
    this.dmTitles[idx] = title;
    
    return false;
}

function CDMenuClearMenu()
{
	g_CDMenues[g_mClearObj.m_id].unloadMe();
}

function CDMenuSetRowHighligh(setRow, row)
{
	if (setRow == true)
	{
		try
		{
			row.childNodes.item(0).className = "CDropdownMenuIconOver";
			row.childNodes.item(1).className = "CDropdownMenuLinkOver";
			row.childNodes.item(2).className = "CDropdownMenuRightOver";
			row.childNodes.item(2).childNodes.item(0).className = "CDropdownMenuRightIconOver";
		} catch (e) {}
	}
	else
	{
		try
		{
			row.childNodes.item(0).className = "CDropdownMenuIcon";
			row.childNodes.item(1).className = "CDropdownMenuLink";
			row.childNodes.item(2).className = "CDropdownMenuRight";
			row.childNodes.item(2).childNodes.item(0).className = "CDropdownMenuRightIcon";
		} catch (e) {}
	}
}

