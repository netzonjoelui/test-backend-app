/*======================================================================================
	
	Module:		CContentTable

	Purpose:	Kind of like a window but embedded in the document

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2006 Aereus Corporation. All rights reserved.

	Usage:
				var ccTble = new CContentTable("My Window Name", "100px", "100px");
				ccTble.print(parent_div); // If no parent div then just doc.write

======================================================================================*/

/***********************************************************************************
 *
 *	Class: 		CContentTable
 *
 *	Purpose:	Create new content table class
 *
 *	Arguements:	title	- string: the default title for this window. Can be changed later.
 *				width	- (optional) string: width in px
 *				height	- (optional) string: height in px
 *
 ***********************************************************************************/
function CContentTable(title, width, height)
{
	/* return reference to inner div for div.innerHTML or div.createDiv */	
	// Create main table
	var table = ALib.m_document.createElement("table");
	this.m_table = table;
	table.className = "ContentTable";
	table.setAttribute("cellpadding","0");
	table.cellSpacing = "0";
	table.setAttribute("cellspacing","0");
	table.cellPadding = "0";
	table.setAttribute("border","0");
	table.border = "0";
	if (width)
	{
		this.m_width = width;
		alib.dom.styleSet(table, "width", width);
	}
	try
	{
		if (height)
			table.style.height = height;
	}
	catch (e)
	{
	}

	var tbl_body = ALib.m_document.createElement("TBODY")	
	
	// Create title bar row 
	var row = ALib.m_document.createElement("tr");
	var td_left = ALib.m_document.createElement("td");
	td_left.className = "ContentTableTitleLeftCorn";
	row.appendChild(td_left);
	var td_middle = ALib.m_document.createElement("td");
	td_middle.className = "ContentTableTitleCenter";
	
	this.m_context = ALib.m_document.createElement("div");
	alib.dom.styleSet(this.m_context, "float", "right");
	//alib.dom.styleSet(this.m_context, "padding-right", "3px");
	this.m_context.className = 'ContentTableTitleContext';
	td_middle.appendChild(this.m_context);

	this.m_spTitle = ALib.m_document.createElement("div");
	this.m_spTitle.className = "ContentTableTitleLabel";
	//alib.dom.styleSet(this.m_spTitle, "float", "left");
	alib.dom.styleSet(this.m_spTitle, "margin-right", (this.m_context.offsetWidth+3) + "px");
	this.m_spTitle.innerHTML = title;
	td_middle.appendChild(this.m_spTitle);

	row.appendChild(td_middle);
	var td_right = ALib.m_document.createElement("td");
	td_right.className = "ContentTableTitleRightCorn";
	row.appendChild(td_right);
	tbl_body.appendChild(row);
	
	// Create content row and div
	var row = ALib.m_document.createElement("tr");
	row.vAlign = "top";
	row.setAttribute("valign", "top");
	var td_left = ALib.m_document.createElement("td");
	td_left.className = "ContentTableBodyLeft";
	row.appendChild(td_left);
	var divContent = ALib.m_document.createElement("td");
	divContent.className = "ContentTableBody";
	row.appendChild(divContent);
	/*
	var divContent = ALib.m_document.createElement("div");
	divContent.style.height = "100%";
	td_middle.appendChild(divContent);
	*/
	var td_right = ALib.m_document.createElement("td");
	td_right.className = "ContentTableBodyRight";
	row.appendChild(td_right);
	tbl_body.appendChild(row);
	
	// Create footer row
	var row = ALib.m_document.createElement("tr");
	row.className = "ContentTableFooterRow";
	
	var td_left = ALib.m_document.createElement("td");
	td_left.className = "ContentTableFooterLeftCorn";
	row.appendChild(td_left);
	var td_middle = ALib.m_document.createElement("td");
	td_middle.className = "ContentTableFooterCenter";
	row.appendChild(td_middle);
	var td_right = ALib.m_document.createElement("td");
	td_right.className = "ContentTableFooterRightCorn";
	row.appendChild(td_right);
	tbl_body.appendChild(row);

	table.appendChild(tbl_body);
	/* Initiate local class variables */
	this.m_table = table;
	this.m_contentdiv = divContent;
}

/***********************************************************************************
 *
 *	Function: 	print
 *
 *	Purpose:	Append the content table to parent or print using document.write
 *
 *	Arguements:	div_parent - (optional) The parent container that will hold the
 *							 table. If none is specified, use document.write()
 *
 ***********************************************************************************/
CContentTable.prototype.print = function(div_parent)
{
	try 
	{
		if (div_parent)
		{
			this.m_parentdiv = div_parent;
			div_parent.appendChild(this.m_table);
		}
		else
			document.write(this.m_table.outerHTML);
	}
	catch (e) {}
}

/***********************************************************************************
 *
 *	Function: 	write
 *
 *	Purpose:	Add html to the body of the content table
 *
 *	Arguments:	htm - any html markup or text to append to the body. Must be string
 *				
 ***********************************************************************************/
CContentTable.prototype.write = function (htm)
{
	this.m_contentdiv.innerHTML += htm;
}

/***********************************************************************************
 *
 *	Function: 	get_cdiv (depreciated)
 *
 *	Purpose:	Get the body/content container. Please use getCon instead.
 *
 ***********************************************************************************/
CContentTable.prototype.get_cdiv = function ()
{
	return this.m_contentdiv;
}

/***********************************************************************************
 *
 *	Function: 	getCon
 *
 *	Purpose:	Get the body/content container.
 *
 ***********************************************************************************/
CContentTable.prototype.getCon = function ()
{
	return this.m_contentdiv;
}

/***********************************************************************************
 *
 *	Function: 	getTitleCon
 *
 *	Purpose:	Get the container that holds the title of the window/table
 *
 ***********************************************************************************/
CContentTable.prototype.getTitleCon = function()
{
	return this.m_spTitle;
}

/***********************************************************************************
 *
 *	Function: 	setTitle
 *
 *	Purpose:	Set the title (html)
 *
 *	Arguements:	title - string
 *
 ***********************************************************************************/
CContentTable.prototype.setTitle = function (title)
{
	this.m_spTitle.innerHTML = title;
}

/***********************************************************************************
 *
 *	Function: 	getOuterCon
 *
 *	Purpose:	Get entire table
 *
 ***********************************************************************************/
CContentTable.prototype.getOuterCon = function()
{
	return this.m_table;
}

/***********************************************************************************
 *
 *	Function: 	get_ctitle (depreciated)
 *
 *	Purpose:	Get context container. Usually in the upper right for close, max, min.
 *				This function has been depreciated, please use getContextCon
 *
 ***********************************************************************************/
CContentTable.prototype.get_ctitle = function ()
{
	return this.m_context;
}

/***********************************************************************************
 *
 *	Function: 	getContextCon
 *
 *	Purpose:	Get context container. Usually in the upper right for close, max, min.
 *
 ***********************************************************************************/
CContentTable.prototype.getContextCon = function ()
{
	return this.m_context;
}

/***********************************************************************************
 *
 *	Function: 	hide
 *
 *	Purpose:	Hides the entire table.
 *
 ***********************************************************************************/
CContentTable.prototype.hide = function ()
{
	this.m_table.style.display = "none";
}

/***********************************************************************************
 *
 *	Function: 	show
 *
 *	Purpose:	Displays the entire table.
 *
 ***********************************************************************************/
CContentTable.prototype.show = function ()
{
	this.m_table.style.display = "table";

	/*
	if (this.m_width)
		alib.dom.styleSet(this.m_table, "width", this.m_width);
		*/
}

/***********************************************************************************
 *
 *	Function: 	setStyle
 *
 *	Purpose:	Set custom style for the content div
 *
 ***********************************************************************************/
CContentTable.prototype.setStyle = function(sname, sval)
{
	alib.dom.styleSet(this.m_contentdiv, sname, sval);
}

/***********************************************************************************
 *
 *	Function: 	setHeight
 *
 *	Purpose:	Set height of the outer container
 *
 ***********************************************************************************/
CContentTable.prototype.setHeight = function(height)
{
	alib.dom.styleSet(this.m_table, "height", height);
}

/***********************************************************************************
 *
 *	Function: 	unload
 *
 *	Purpose:	Delete this table
 *
 ***********************************************************************************/
CContentTable.prototype.unload = function ()
{
	if (this.m_parentdiv)
	{
		this.m_parentdiv.removeChild(this.m_table);
	}
}
