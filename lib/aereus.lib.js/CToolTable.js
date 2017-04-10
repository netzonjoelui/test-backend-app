/****************************************************************************
*	
*	Class:		CToolTable
*
*	Purpose:	Table encapsulation for simplified usage of html tables
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
var g_ctt_tind = 0;
var g_ctt_tables = new Array();

function CToolTable(width, height, custom_class)
{
	/* return reference to inner div for div.innerHTML or div.createDiv */	
	// Create main table
	var table = ALib.m_document.createElement("table");
	if (custom_class)
		table.className = custom_class;
	else
		table.className = "CTTMainTable";

	table.setAttribute("cellpadding","0");
	table.cellPadding = "0";	
	table.setAttribute("cellspacing","0");
	table.cellSpacing = "0";
	table.setAttribute("border","0");
	table.border = "0";
	if (width)
		table.style.width = width;
	if (height)
		table.style.height = height;

	var tbl_body = ALib.m_document.createElement("TBODY")	

	table.appendChild(tbl_body);
	/* Initiate local class variables */
	this.m_table = table;
	this.m_table_body = tbl_body;
	this.m_numrows = 0;
	this.m_rows = new Array();
	this.m_rowBody = null;
	this.m_rowSpacer = null;
	this.m_headersrow = null;

	// Set table unique id
	this.m_uni_id = "alib_ctt_" + g_ctt_tind;
	g_ctt_tables[g_ctt_tind] = this;
	g_ctt_tind++;
}

CToolTable.prototype.addHeader = function (name, align, width, height, custom_class, custom_padding)
{
	// Initial indefined variables
	if (!align)
		var align = "left";
	if (showspacer == "undefined")
		var showspacer = true;	
		
	if (!this.m_headersrow)
	{
		this.m_headersrow = ALib.m_document.createElement("tr");
		this.m_table_body.appendChild(this.m_headersrow);
	}
	
	var td = ALib.m_document.createElement("td");
	// Content
	if (typeof name == "string")
		td.innerHTML = name;
	else
		td.appendChild(name);
	// Class
	td.className = (custom_class) ? custom_class : "CTTHeaderCell";
	// Alignment
	td.align = align;
	td.setAttribute("align",align);
	// Width and Height
	if (width)
		td.style.width = width;
	if (height)
		td.style.height = height;
	td.style.padding = (custom_padding) ? custom_padding : "3px 5px 3px 5px"; // top, right, bottom, left
	// Add cell to headers row
	this.m_headersrow.appendChild(td);
	
	return td;
}

CToolTable.prototype.addRow = function(idname)
{
	// Get unique id name
	var name = this.m_numrows;
	this.m_lastRow = name;

	this.m_rows[name] = new CToolTableRow();
	this.m_rows[name].m_hinst = this;
	this.m_rows[name].m_name = name;
	this.m_rows[name].m_uni_id = this.m_uni_id + "_row_" + name;
	this.m_rows[name].id = idname;

	// Spacer row goes above body row
	this.m_rowSpacer = ALib.m_document.createElement("tr");
	this.m_rowBody = ALib.m_document.createElement("tr");
	this.m_rowBody.valign = "top";
    this.m_rowBody.setAttribute("valign", "top");    
    
    this.m_rowBody.onmouseover = function()
    {
        this.setAttribute("bgcolor", "#F0F0F0");
    }
    this.m_rowBody.onmouseout = function()
    {
        this.setAttribute("bgcolor", "#FFFFFF");
    }

	// Spacer row goes above body row
	this.m_table_body.appendChild(this.m_rowSpacer);
	this.m_table_body.appendChild(this.m_rowBody);
	
	this.m_rows[name].m_rowSpacer = this.m_rowSpacer;
	this.m_rows[name].m_row = this.m_rowBody;

	this.m_numrows++;
	
	return this.m_rows[name];
}

CToolTable.prototype.getRow = function(idname)
{
	for (var i = 0; i < this.m_rows.length; i++)
	{
		if (this.m_rows[i].id == idname)
			return this.m_rows[i];
	}
}

CToolTable.prototype.numRows = function()
{
	return this.m_numrows;
}

CToolTable.prototype.startRow = function()
{
	this.addRow();	
}

CToolTable.prototype.endRow = function()
{
}

// Empty the table
CToolTable.prototype.clear = function()
{
	for (var row in this.m_rows)
	{
		this.removeRow(row);
	}
}

CToolTable.prototype.removeRow = function(indx)
{
	try 
	{
		this.m_table_body.removeChild(this.m_rows[indx].m_rowSpacer);
		this.m_table_body.removeChild(this.m_rows[indx].m_row);
		this.m_numrows = this.m_numrows - 1;
	}
	catch (e) {}
}

CToolTable.prototype.addCell = function(content, bold, align, width, height, custom_class, custom_padding, indx)
{
	// Get unique id name
	var name = (indx) ? indx : this.m_lastRow;

	// Rows alternate, set class
	var cellclass = "";
	if (this.m_numrows % 2)
		cellclass = (bold) ? "CTTRowOneBold" : "CTTRowOne";
	else
		cellclass = (bold) ? "CTTRowTwoBold" : "CTTRowTwo";
	
	// Create spacer cell
	var td_spacer = ALib.m_document.createElement("td");
	td_spacer.className = "CTTRowSpacer";
	this.m_rows[name].m_rowSpacer.appendChild(td_spacer);
	
	// Create body cell
	var td_body = ALib.m_document.createElement("td");
	td_body.className = (custom_class) ? custom_class : cellclass;
	td_body.align = (align) ? align : "left";
	td_body.style.padding = (custom_padding) ? custom_padding : "3px 5px 3px 5px"; // top, right, bottom, left
	if (width)
		td_body.style.width = width;
	if (height)
		td_body.style.width = height;
	if (typeof content == "string")
		td_body.innerHTML = content;
    else if (typeof content == "number")
        td_body.innerHTML = content.toString();
	else
	{
		try
		{
			td_body.appendChild(content);
		}
		catch (e) {}
	}

	this.m_rows[name].m_row.appendChild(td_body);
	
	return td_body;
}

CToolTable.prototype.print = function (div_parent)
{
	if (div_parent)
	{
		this.m_parentdiv = div_parent;
		div_parent.appendChild(this.m_table);
	}
	else
		document.write(this.m_table.outerHTML);
}

function CToolTableRow()
{
	this.m_row;
	this.m_rowSpacer;
	this.m_hinst;
	this.m_name;
	this.m_uni_id = null;
}

CToolTableRow.prototype.addCell = function (content, bold, align, width, height, custom_class, custom_padding)
{
	// Create defaults
	if (!content)
		var content = null;
	if (!bold)
		var bold = null;
	if (!align)
		var align = null;
	if (!width)
		var width = null;
	if (!height)
		var height = null;
	if (!custom_class)
		var custom_class = null;
	if (!custom_padding)
		var custom_padding = null;

	return this.m_hinst.addCell(content, bold, align, width, height, custom_class, custom_padding, this.m_name);
}

CToolTableRow.prototype.deleteRow = function()
{
	this.m_hinst.removeRow(this.m_name);
}

CToolTableRow.prototype.getId = function()
{
	return this.m_uni_id;
}

CToolTableRow.prototype.setHeight = function(height)
{
	if (height)
		this.m_table.style.height = height;
}
