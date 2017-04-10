/****************************************************************************
*	
*	Class:		CToolbar
*
*	Purpose:	Create toolbar frame
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CToolbar()
{
	this.m_document = ALib.m_document;
	var doc = this.m_document;
	this.m_outerdv = doc.createElement("div");
	var tbl = doc.createElement("table");
	this.m_table = tbl;
	alib.dom.styleSetClass(tbl, "CToolbar");
	alib.dom.styleSet(tbl, "width", "100%");
	tbl.setAttribute("cellpadding","0");
	tbl.cellPadding = "0";	
	tbl.setAttribute("cellspacing","0");
	tbl.cellSpacing = "0";
	var tbl_bdy = doc.createElement("tbody");
	tbl.appendChild(tbl_bdy);
	var row = doc.createElement("tr");
	tbl_bdy.appendChild(row);
	var td = doc.createElement("td");
	row.appendChild(td);

	this.m_con = td;
	this.m_outerdv.appendChild(tbl);
}

/******************************************************************************
*	Function:	AddItem
*	Purpose:	Add any item to the toolbar
*******************************************************************************/
CToolbar.prototype.AddItem = function(element, align)
{
	var dv = this.m_document.createElement("div");
	if (align == "right")
	{
		alib.dom.styleSet(dv, "float", "right");
	}
	else
	{
		alib.dom.styleSet(dv, "float", "left");
	}
	
	dv.appendChild(element);
	this.m_con.appendChild(dv);
}

/******************************************************************************
*	Function:	addSpacer
*	Purpose:	Add a spacer to the tooblar
*******************************************************************************/
CToolbar.prototype.addSpacer = function(align)
{
	var dv = this.m_document.createElement("div");
	if (align == "right")
	{
		alib.dom.styleSet(dv, "float", "right");
	}
	else
	{
		alib.dom.styleSet(dv, "float", "left");
	}
	alib.dom.styleSetClass(dv, "CToolbarSpacer");
	
	this.m_con.appendChild(dv);
}

/******************************************************************************
*	Function:	addIcon
*	Purpose:	Add any item to the toolbar
*******************************************************************************/
CToolbar.prototype.addIcon = function(src, align, funct, fargs)
{
	var dv = this.m_document.createElement("div");
	alib.dom.styleSetClass(dv, "CToolbarIcon");
	if (align == "right")
	{
		alib.dom.styleSet(dv, "float", "right");
	}
	else
	{
		alib.dom.styleSet(dv, "float", "left");
	}
	dv.innerHTML = "<img src='" + src + "' border='0'>";
	dv.functname = funct;
	dv.onclick = function()
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
	}
	this.m_con.appendChild(dv);
}
/******************************************************************************
*	Function:	print
*	Purpose:	Append toolbar to container passed in dv (write if no container)
*******************************************************************************/
CToolbar.prototype.print = function (dv)
{
	if (dv)
		dv.appendChild(this.m_outerdv);
	else
		document.write(this.m_outerdv.outerHTML);
}

/******************************************************************************
*	Function:	getContainer
*	Purpose:	Get content container for toolbar
*******************************************************************************/
CToolbar.prototype.getContainer = function ()
{
	return this.m_con;
}

/******************************************************************************
*	Function:	getHeight
*	Purpose:	Get the total height of the toolbar
*******************************************************************************/
CToolbar.prototype.getHeight= function ()
{
	return this.m_con.offsetHeight;
}


/******************************************************************************
*	Function:	styleSetClass	
*	Purpose:	Set container class
*******************************************************************************/
CToolbar.prototype.setClass = function(cls)
{
	this.styleSetClass(cls);
}
CToolbar.prototype.styleSetClass = function(cls)
{
	alib.dom.styleSetClass(this.m_table, cls);
}

/******************************************************************************
*	Function:	setStyle	
*	Purpose:	Set container style
*******************************************************************************/
CToolbar.prototype.styleSet = function(sname, sval)
{
	alib.dom.styleSet(this.m_table, sname, sval);
}
