/****************************************************************************
*	
*	Class:		CNavBar
*
*	Purpose:	Create standard navigation bar
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/

function CNavBar(width, height)
{
	this.m_con = ALib.m_document.createElement("div");
	if (!width)
		var width = "100%";
	alib.dom.styleSet(this.m_con, "width", width);
	if (height)
	{
		alib.dom.styleSet(this.m_con, "height", height);
	}

	this.m_items = new Array();
	this.m_itemcounter = 0;
	this.m_sections = new Array();
	this.m_sectioncounter = 0;
	this.m_lasesection = null;	
	this.m_appclass = null;
}

CNavBar.prototype.getLastSectionChild = function(parent_node)
{
	if (parent_node.m_children.length)
		return this.getLastSectionChild(parent_node.m_children[parent_node.m_children.length-1]);
	else
		return parent_node;
}

CNavBar.prototype.addSectionItem = function(label, icon, action, args, idname, selectable, sectionid, depth, parent_node)
{
	// Get unique id name
	var idname = (typeof idname == "undefined") ? null : idname;
	var name = (idname && idname != -1) ? idname : this.getNextItemId();
	var noOn = (selectable) ? false : true;
	var sect_id = (sectionid) ? sectionid : this.m_lasesection;

	// Create item/node
	this.m_items[name] = new CNavBarItem(this);
	var item = this.m_items[name];
	item.m_secname = sect_id;
	item.m_idname = name;
	item.m_noOn = noOn;

	if (typeof(depth) == "undefined")
		item.m_depth = 0;
	else
		item.m_depth = depth;

	var tr = ALib.m_document.createElement("tr");
	item.m_tr = tr;
	if (typeof(parent_node) != "undefined")
	{
		var insertafterItem = this.getLastSectionChild(parent_node); //(parent_node.m_children.length) ? parent_node.m_children[parent_node.m_children.length-1] : parent_node;
		insertAfter(this.m_sections[sect_id].m_tblbdy, tr, insertafterItem.m_tr);
	}
	else
		this.m_sections[sect_id].m_tblbdy.appendChild(tr);
	var td = ALib.m_document.createElement("td");
	alib.dom.styleSet(td, "width", "100%");
	tr.appendChild(td);
	item.m_con = td;
	
	// Make header table
	var tbl = ALib.m_document.createElement("table");
	alib.dom.styleSet(tbl, "border", "0px");
	alib.dom.styleSet(tbl, "width", "100%");
	alib.dom.styleSet(tbl, "table-layout", "fixed");
	tbl.cellPadding = 0;
	tbl.cellSpacing = 0;
	td.appendChild(tbl);
	var tbody = ALib.m_document.createElement("tbody");
	tbl.appendChild(tbody);

	// Add Item
	var tr = ALib.m_document.createElement("tr");
	tbody.appendChild(tr);
	var td = ALib.m_document.createElement("td");
	alib.dom.StyleAddClass(td, "CNavBarItem");
	//alib.dom.styleSet(td, "cursor", "pointer");
	//alib.dom.styleSet(td, "white-space", "nowrap");
	item.m_itemcon = td;

	// Add option div
	item.m_optdiv = alib.dom.createElement("div", td);
	//alib.dom.styleSet(item.m_optdiv, "float", "right");
	alib.dom.styleSetClass(item.m_optdiv, "CNavBarItemOpt");
	
	// if depth add spacers
	for (var i = 0; i < item.m_depth; i++)
	{
		var dv = ALib.m_document.createElement("div");
		//alib.dom.styleSet(dv, "float", "left");
		alib.dom.styleSet(dv, "display", "inline-block");
		alib.dom.styleSetClass(dv, "CTreeViewSpaceLine");
		td.appendChild(dv);
	}

	// icon
	var dv = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(dv, "CNavBarItemIcon");
	item.m_icon = dv;
	//alib.dom.styleSet(dv, "float", "left");
	//alib.dom.styleSet(dv, "display", "inline-table");
	if (icon)
	{
		if (typeof(icon) == "string")
			dv.innerHTML = "<img border='0' src='"+icon+"' />";
		else
			dv.appendChild(icon);
	}
	td.appendChild(dv);
	// label
	var dv = ALib.m_document.createElement("div");
	alib.dom.styleSetClass(dv, "CNavBarItemLink");
	//alib.dom.styleSet(dv, "float", "left");
	//alib.dom.styleSet(dv, "display", "inline-table");
	item.m_link = dv;
	dv.innerHTML = label;
	td.appendChild(dv);
	//this.m_items[name] = td;
	item.m_linktd = dv;

	td.m_name = name;
	td.m_nav = this;
	td.onmouseover = function () 
	{ 
		if (this.className != "CNavBarItemOn")
			this.m_nav.itemChangeState(this.m_name, "over"); 
	};
	td.onmouseout = function () 
	{ 
		if (this.className != "CNavBarItemOn")
			this.m_nav.itemChangeState(this.m_name, "out"); 
	};

	// Set link action
	item.m_linktd.m_action = action;
	item.m_linktd.m_nav = this;
	item.m_linktd.m_name = name;
	item.m_linktd.m_noOn = noOn;
	item.m_linktd.m_idname = (typeof idname != "undefined") ? idname : name;
	if (args)
		item.m_linktd.m_args = args;
	item.m_linktd.onclick = function () 
	{ 
		if (!this.m_noOn)
			this.m_nav.itemChangeState(this.m_name, "on"); 
		
		if (this.m_action)
		{
			if (typeof this.m_action == "string")
				eval(this.m_action);
			else
			{
				try
				{
					if (this.m_args && this.m_args.length)
					{
						switch (this.m_args.length)
						{
						case 3:
							this.m_action(this.m_args[0], this.m_args[1], this.m_args[2]);
							break;
						case 2:
							this.m_action(this.m_args[0], this.m_args[1]);
							break;
						case 1:
							this.m_action(this.m_args[0]);
							break;
						}
					}
					else
						this.m_action();
				}
				catch (e) {}
			}

			//var me = this;
			//ALib.History.registerBack(function() {me.onclick(); });
		}
	};

	var dv = alib.dom.createElement("div");
	dv.style.clear = "left";	
	td.appendChild(dv);

	tr.appendChild(td);

	return item;
}

CNavBar.prototype.itemChangeState = function(name, state)
{
	if (!this.m_items[name])
		return;

	if (!this.m_items[name].m_itemcon)
		return;

	switch (state)
	{
	case 'out':
		this.m_items[name].m_itemcon.className = "CNavBarItem";
		break;
	case 'over':
		this.m_items[name].m_itemcon.className = "CNavBarItemOver";
		break;
	case 'on':
		this.m_items[name].m_itemcon.className = "CNavBarItemOn";
		if (this.m_laston && this.m_laston != name)
			this.itemChangeState(this.m_laston, "out");
		this.m_laston = name;
		break;
	}
}

CNavBar.prototype.itemClearOnStates = function()
{
	for (item in this.m_items)
	{
		this.itemChangeState(item, 'out');
	}
}

CNavBar.prototype.addSectionDiv = function(sectionid)
{
	var sect_id = (sectionid) ? sectionid : this.m_lasesection;

	var tr = ALib.m_document.createElement("tr");
	this.m_sections[sect_id].m_tblbdy.appendChild(tr);
	var td = ALib.m_document.createElement("td");
	alib.dom.styleSet(td, "width", "100%");
	tr.appendChild(td);

	return td;
}

CNavBar.prototype.getNavBar = function()
{
	// Add closing HR
	var div = ALib.m_document.createElement("div");
	this.m_con.appendChild(div);
	var tbl = ALib.m_document.createElement("table");
	div.appendChild(tbl);
	alib.dom.styleSet(tbl, "border", "0px");
	alib.dom.styleSet(tbl, "width", "100%");
	tbl.cellPadding = '0';
	tbl.cellSpacing = '0';
	var tblbdy = ALib.m_document.createElement("tbody");
	tbl.appendChild(tblbdy);
	var tr = ALib.m_document.createElement("tr");
	tblbdy.appendChild(tr);
	var td = ALib.m_document.createElement("td");
	alib.dom.StyleAddClass(td, "CNavBarBorderBottom");
	tr.appendChild(td);
	
	return this.m_con;
}

CNavBar.prototype.print = function(con)
{
	con.appendChild(this.getNavBar());
}

CNavBar.prototype.getNextItemId = function()
{
	this.m_itemcounter++;

	var name = "item_" + this.m_itemcounter;

	return name;
}

CNavBar.prototype.getNextSectionId = function()
{
	this.m_sectioncounter++;

	var name = "section_" + this.m_sectioncounter;

	return name;
}

CNavBar.prototype.getSectionHeight = function(idname)
{
	var section = this.m_sections[idname];
	return section.m_div.offsetHeight;
}

CNavBar.prototype.setSectionHeight = function(idname, height, overflow, max)
{
	var section = this.m_sections[idname];

	/*
	if (typeof overflow == "undefined")
		var overflow = "auto";
		*/

	var set_height = height - section.m_headerhr.offsetHeight;
	if (section.m_headerlbl)
		set_height -= section.m_headerlbl.offsetHeight;

	if (typeof max != "undefined" && max)
	{
		alib.dom.styleSet(section.m_condiv, "max-height", set_height+"px");
		alib.dom.styleSet(section.m_condiv, "min-height", "15px");
	}
	else
		alib.dom.styleSet(section.m_condiv, "height", set_height+"px");
	alib.dom.styleSet(section.m_condiv, "overflow", overflow);
	//alib.ui.slimScroll(section.m_condiv, {});
}

CNavBar.prototype.addSection = function(label, idname, manual_height)
{
	// Get unique id name
	var name = (idname) ? idname: this.getNextSectionId();
	this.m_lasesection = name;
	// Get height
	var height = (manual_height) ? manual_height : null;

	this.m_sections[name] = new CNavBarSection(this);

	var section = this.m_sections[name];
	section.m_idname = name;
	
	// Create containing div
	section.m_div = alib.dom.createElement("div", this.m_con);

	// Create content table
	/*
	section.m_outerdiv = alib.dom.createElement("table", section.m_div);
	section.m_outertbl = alib.dom.createElement("table", section.m_div);
	alib.dom.styleSet(section.m_outertbl, "border", "0px");
	alib.dom.styleSet(section.m_outertbl, "width", "100%");
	section.m_outertbl.cellPadding = '0';
	section.m_outertbl.cellSpacing = '0';
	section.m_outertblbdy = alib.dom.createElement("tbody", section.m_outertbl);
	*/
	//section.m_tblbdy = alib.dom.createElement("tbody");

	// Make header table
	//---------------------------------------------------------------
	
	// Add HR
	/*
	var tr = alib.dom.createElement("tr", section.m_outertblbdy);
	section.m_headerhr = tr;
	var td = alib.dom.createElement("td", tr);
	td.colSpan = "3";
	alib.dom.StyleAddClass(td, "CTMHeaderTopHr");
	*/
	// Add Label
	var dv = alib.dom.createElement("div", section.m_div);
	section.m_headerhr = dv;
	if (label)
	{
		/*
		var tr = alib.dom.createElement("tr", section.m_outertblbdy);
		var td = alib.dom.createElement("td", tr);
		alib.dom.StyleAddClass(td, "CTMLeftBorder");
		var td = alib.dom.createElement("td", tr);
		*/
		alib.dom.StyleAddClass(dv, "CNavBarSectionHeader");
		dv.innerHTML = label;
		/*
		var td = alib.dom.createElement("td", tr);
		alib.dom.StyleAddClass(td, "CTMRightBorder");
		*/
		section.m_headerlbl = dv;
	}

	// Make content table
	var tr = alib.dom.createElement("tr", section.m_outertblbdy);
	/*
	var td = alib.dom.createElement("td", tr);
	alib.dom.StyleAddClass(td, "CTMLeftBorder");
	*/

	//var td = alib.dom.createElement("td", tr);
	//alib.dom.StyleAddClass(td, "CNavBarSectionBody");
	section.m_condiv = alib.dom.createElement("div", section.m_div);
	alib.dom.StyleAddClass(section.m_condiv, "CNavBarSectionBody");
	if (height)
	{
		alib.dom.styleSet(section.m_condiv, "height", height);
		//alib.ui.slimScroll(section.m_condiv, {});
		alib.dom.styleSet(section.m_condiv, "overflow", "auto");
	}

	section.m_tbl = alib.dom.createElement("table", section.m_condiv);
	alib.dom.styleSet(section.m_tbl, "border", "0px");
	alib.dom.styleSet(section.m_tbl, "width", "100%");
	section.m_tbl.cellPadding = 0;
	section.m_tbl.cellSpacing = 0;
	section.m_tblbdy = alib.dom.createElement("tbody", section.m_tbl);

	/*
	var td = alib.dom.createElement("td", tr);
	alib.dom.StyleAddClass(td, "CTMRightBorder");
	*/

	return this.m_sections[name];
}

// This function should be defined by client application 
// and called whenever content is changed
CNavBar.prototype.onExit = function()
{
}

CNavBar.prototype.deleteItem = function(idname)
{
	var row = this.m_items[idname].m_linktd.parentNode;
	var tbl = row.parentNode;
	tbl.removeChild(row);
}

CNavBar.prototype.setItemLabel = function(idname, label)
{
	// Get label div
	var dv = this.m_items[idname].m_linktd.childNodes.item(1);	
	if (dv)
		dv.innerHTML = label;
}

/***********************************************************************************
 *
 *	Function: 	CNavBarSection
 *
 *	Purpose:	Create CNavBarSection
 *
 *	Arguements:	nbobj	- (object): Navbar object
 *
 ***********************************************************************************/
function CNavBarSection(nbobj)
{
	this.m_div = null;
	this.m_tbl = null;
	this.m_tblbdy = null;
	this.m_idname = null;
	this.m_nb = nbobj;
}

CNavBarSection.prototype.addItem = function(label, icon, action, args, idname, selectable)
{
	if (typeof selectable == "undefined")
	{
		var selectable = (idname=="-1") ? false : true;
	}
		
	return this.m_nb.addSectionItem(label, icon, action, args, idname, selectable, this.m_idname);
}

CNavBarSection.prototype.addCon = function()
{
	return this.m_nb.addSectionDiv(this.m_idname);
}

// Get the actual height of the whole section
CNavBarSection.prototype.getHeight = function()
{
	return this.m_nb.getSectionHeight(this.m_idname);
}

// Set the height of the section
CNavBarSection.prototype.setHeight = function(height, overflow)
{
	if (typeof overflow == "undefined")
		var overflow = "auto";

	this.m_nb.setSectionHeight(this.m_idname, height, overflow)
}

// Set the height of the section
CNavBarSection.prototype.setMaxHeight = function(height, overflow)
{
	if (typeof overflow == "undefined")
		var overflow = "auto";

	this.m_nb.setSectionHeight(this.m_idname, height, overflow, true)
}

/***********************************************************************************
 *
 *	Function: 	CNavBarItem
 *
 *	Purpose:	Creates node that handles each item.
 *
 *	Arguements:	nbobj	- (object): Navbar object
 *
 ***********************************************************************************/
function CNavBarItem(nbobj)
{
	this.m_secname 		= null;
	this.m_tr 			= null;
	this.tilde_dv 		= null;
	this.icon_dv 		= null;
	this.m_con			= null; // The outer container of this link/item
	this.m_link 		= null;
	this.m_linktd 		= null;
	this.m_nb 			= nbobj;
	this.m_expanded 	= false;
	this.m_depth 		= 0;
	this.m_parent		= null;
	this.m_children		= new Array();
}

CNavBarItem.prototype.addItem = function(label, icon, action, args, idname, selectable)
{
	if (typeof selectable == "undefined")
	{
		var selectable = (idname==-1) ? false : true;
	}

	var item = this.m_nb.addSectionItem(label, icon, action, args, idname, selectable, this.m_secname, (this.m_depth + 1), this);
	item.m_parent = this;
	item.setHasChildren(false);
	this.m_children[this.m_children.length] = item;
	this.setHasChildren(true);
	return item;
}

CNavBarItem.prototype.getOptionCon = function()
{
	/*
	var dv = alib.dom.createElement("div");
	//alib.dom.styleSet(dv, "float", "right");
	alib.dom.styleSet(dv, "display", "inline-table");
	insertAfter(this.m_itemcon, dv, this.m_linktd);
	return dv;
	*/

	return this.m_optdiv;
}

CNavBarItem.prototype.getLabelCon = function()
{
	return this.m_link;
}

CNavBarItem.prototype.hide = function()
{
	alib.dom.styleSet(this.m_tr, "display", "none");
}

CNavBarItem.prototype.show = function()
{
	if (alib.userAgent.ie)
		alib.dom.styleSet(this.m_tr, "display", "block");
	else
		alib.dom.styleSet(this.m_tr, "display", "table-row");
}

/***********************************************************************************
 *
 *	Function: 	setHasChildren
 *
 *	Purpose:	Change node the style of one with children
 *
 *	Arguements:	haschildren	- bool = does this have children
 *
 ***********************************************************************************/
CNavBarItem.prototype.setHasChildren = function(haschildren)
{
	if (!this.tilde_dv)
	{
		this.tilde_dv = alib.dom.createElement("div");
		this.tilde_dv.m_node = this;
		this.tilde_dv.onclick = function()
		{
			if (this.m_node.m_expanded)
				this.m_node.collapse();
			else
				this.m_node.expand();
		}
		//alib.dom.styleSet(this.tilde_dv, "float", "left");
		alib.dom.styleSet(this.tilde_dv, "display", "inline-table");
		this.m_itemcon.insertBefore(this.tilde_dv, this.m_icon);
	}

	if (haschildren)
	{
		if (this.m_expanded)
			alib.dom.styleSetClass(this.tilde_dv, "CTreeViewTildeWithSubOpen");
		else
			alib.dom.styleSetClass(this.tilde_dv, "CTreeViewTildeWithSubClosed");
	}
	else
	{
		alib.dom.styleSetClass(this.tilde_dv, "CTreeViewTildeNoSub");
	}
}

/***********************************************************************************
 *
 *	Function: 	expand
 *
 *	Purpose:	Expand this node (display children if they exist)
 *
 ***********************************************************************************/
CNavBarItem.prototype.expand = function()
{
	if (this.m_children.length)
	{
		for (var i = 0; i < this.m_children.length; i++)
			this.m_children[i].show();

		this.m_expanded = true;

		alib.dom.styleSetClass(this.tilde_dv, "CTreeViewTildeWithSubOpen");
	}
}

/***********************************************************************************
 *
 *	Function: 	collapse
 *
 *	Purpose:	Collapse this node (collapse children if they exist)
 *
 ***********************************************************************************/
CNavBarItem.prototype.collapse = function()
{
	if (this.m_children.length)
	{
		for (var i = 0; i < this.m_children.length; i++)
		{
			this.m_children[i].collapse();
			this.m_children[i].hide();
		}

		this.m_expanded = false;

		alib.dom.styleSetClass(this.tilde_dv, "CTreeViewTildeWithSubClosed");
	}
}

/***********************************************************************************
 *
 *	Function: 	deleteItem
 *
 *	Purpose:	Deletes this item
 *
 ***********************************************************************************/
CNavBarItem.prototype.deleteItem = function()
{
	if (this.m_children.length);
	{
		for (var i = 0; i < this.m_children.length; i++)
			this.m_children[i].deleteItem();
	}

	this.m_nb.deleteItem(this.m_idname);

	if (this.m_parent)
	{
		if (this.m_parent.m_children.length < 2)
			this.m_parent.setHasChildren(false);
	}
}

/***********************************************************************************
 *
 *	Function: 	registerDropzone
 *
 *	Purpose:	Setup item to handle drop for drag and drop with a given zone name
 *
 ***********************************************************************************/
CNavBarItem.prototype.registerDropzone = function(zonename)
{
	DragAndDrop.registerDropzone(this.m_con, zonename);
	this.m_con.m_cnbItem = this;
	this.m_con.onDragEnter = function(e)
	{
		var evt = (e) ? e : NULL;
		this.m_cnbItem.onDragEnter(e);
	}
	this.m_con.onDragExit = function(e)
	{
		var evt = (e) ? e : NULL;
		this.m_cnbItem.onDragEnter(e);
	}
	this.m_con.onDragDrop = function(e)
	{
		var evt = (e) ? e : NULL;
		this.m_cnbItem.onDragDrop(e);
	}
}

/***********************************************************************************
 *
 *	Function: 	onDragEnter
 *
 *	Purpose:	To be overridden
 *
 ***********************************************************************************/
CNavBarItem.prototype.onDragEnter = function(e)
{
}

/***********************************************************************************
 *
 *	Function: 	onDragExit
 *
 *	Purpose:	To be overridden
 *
 ***********************************************************************************/
CNavBarItem.prototype.onDragExit = function(e)
{
}

/***********************************************************************************
 *
 *	Function: 	onDragDrop
 *
 *	Purpose:	To be overridden
 *
 ***********************************************************************************/
CNavBarItem.prototype.onDragDrop = function(e)
{
}
