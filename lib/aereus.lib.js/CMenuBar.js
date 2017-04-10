/****************************************************************************
*	
*	Class:		CMenubar
*
*	Purpose:	Create menu bar like the file menu in windows
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CMenubar()
{
	this.m_document = ALib.m_document;
	var doc = this.m_document;
	this.m_outerdv = doc.createElement("div");
	var tbl = doc.createElement("table");
	alib.dom.styleSetClass(tbl, "CMenubar");
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
CMenubar.prototype.AddItem = function (title, align)
{
	var dv = this.m_document.createElement("div");
	if (align == "right")
	{
		alib.dom.styleSet(dv, "float", "right");
		alib.dom.styleSet(dv, "padding-right", "2px");
	}
	else
	{
		alib.dom.styleSet(dv, "float", "left");
		alib.dom.styleSet(dv, "padding-left", "2px");
	}
	
	dv.innerHTML = title;

	// Create dropdown menu	
	var dm = new CDropdownMenu();
	this.m_con.appendChild(dm.createCustomnMenu(dv, "CMenuBarLink", "CMenuBarLinkOver", "CMenuBarLinkOn"));

	return dm;
}

/******************************************************************************
*	Function:	print
*	Purpose:	Append toolbar to container passed in dv (write if no container)
*******************************************************************************/
CMenubar.prototype.print = function (dv)
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
CMenubar.prototype.getContainer = function ()
{
	return this.m_con;
}


