/****************************************************************************
*	
*	Class:		CDatasheet
*
*	Purpose:	Editable spreadsheet table
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
var g_cdt_tind = 0;
var g_cdt_tables = new Array();

function CDatasheet(width, height, show_headers, show_rowtitle)
{
	// Set options
	this.show_rowtitles = (show_rowtitle) ? show_rowtitle : true;
	this.clicksToEdit = "double"; // This can either be double or single and will determine clicks for edit

	// Create main table
	var table = ALib.m_document.createElement("table");
	table.className = "CDatasheetMainTable";

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
	// Initiate local class variables
	this.m_table = table;
	this.m_table_body = tbl_body;
	this.m_numrows = 0;
	this.m_rows = new Array();
	this.m_cols = new Array();
	this.m_rowBody = null;
	this.m_headersrow = null;

	// Set callback functions
	this.onCellChange = new Function();		// Editing of cell is finished
	this.onCellUpdate = new Function();		// Any change (keypress) to the cell

	// Set table unique id
	this.m_uni_id = "alib_cdt_" + g_cdt_tind;
	g_cdt_tables[g_cdt_tind] = this;
	g_cdt_tind++;
}

CDatasheet.prototype.addHeader = function (title, align, width, height, idname)
{
	// Initial indefined variables
	if (!align)
		var align = "left";
	//if (typeof showspacer == "undefined")
	//	var showspacer = true;	
		
	if (!this.m_headersrow)
	{
		this.m_headersrow = ALib.m_document.createElement("tr");
		this.m_table_body.appendChild(this.m_headersrow);

		// Check for row titles
		if (this.show_rowtitles)
		{
			var td_body = ALib.m_document.createElement("td");
			alib.dom.styleSetClass(td_body, "CDatasheetRowTitle");
			this.m_headersrow.appendChild(td_body);
		}
	}
	
	var td = ALib.m_document.createElement("td");
	// Content
	td.innerHTML = title;
	// Class
	alib.dom.styleSetClass(td, "CDatasheetHeaderCell");
	// Alignment
	td.align = align;
	td.setAttribute("align",align);
	// Width and Height
	if (width)
		td.style.width = width;
	if (height)
		td.style.height = height;
	// Add cell to headers row
	this.m_headersrow.appendChild(td);
	// Add to headers array
	this.m_cols[this.m_cols.length] = td;
	return td;
}

CDatasheet.prototype.addRow = function(idname, title)
{
	// Get unique id name
	var name = (idname) ? idname : this.m_numrows;
	this.m_lastRow = name;

	this.m_rows[name] = new CDatasheetRow();
	this.m_rows[name].m_hinst = this;
	this.m_rows[name].m_name = name;
	this.m_rows[name].m_uni_id = this.m_uni_id + "_row_" + name;

	this.m_rowBody = ALib.m_document.createElement("tr");
	alib.dom.styleSetClass(this.m_rowBody, "CDatasheetRow");
	this.m_rowBody.valign = "top";
	this.m_rowBody.setAttribute("valign", "top");
	this.m_table_body.appendChild(this.m_rowBody);
	this.m_rows[name].m_row = this.m_rowBody;

	this.m_numrows++;
	
	// Create row title
	if (this.show_rowtitles)
	{
		var td_body = ALib.m_document.createElement("td");
		alib.dom.styleSetClass(td_body, "CDatasheetRowTitle");
		if (typeof title != "undefined")
		{
			if (typeof title == "string" || typeof title == "number")
				td_body.innerHTML = title;
			else
			{
				try
				{
					td_body.appendChild(title);
				}
				catch (e) {}
			}
		}
		this.m_rows[name].m_titlecell = td_body;
		this.m_rows[name].m_row.appendChild(td_body);
	}

	return this.m_rows[name];
}

CDatasheet.prototype.numRows = function()
{
	return this.m_numrows;
}

CDatasheet.prototype.rows = function(name)
{
	return this.m_rows[name];
}

CDatasheet.prototype.getRows = function(name)
{
	return this.m_rows;
}

CDatasheet.prototype.removeRow = function(indx)
{
	this.m_table_body.removeChild(this.m_rows[indx].m_row);
	this.m_numrows = this.m_numrows - 1;
}

CDatasheet.prototype.addCell = function(content, align, width, height, readonly, colind, row)
{
	// Get unique id name
	var name = (row) ? row.m_name : this.m_lastRow;

	var f_readonly = (readonly) ? readonly : false;
	
	// Create body cell
	var td_body = ALib.m_document.createElement("td");
	td_body.m_row = row;
	td_body.m_colind = colind;
	td_body.f_readonly = f_readonly;
	td_body.m_tblcls = this;
	alib.dom.styleSetClass(td_body, (f_readonly) ? "CDatasheetCellRO" : "CDatasheetCell");
	td_body.align = (align) ? align : "left";
	if (width)
		td_body.style.width = width;
	if (height)
		td_body.style.width = height;
	if (typeof content == "string")
		td_body.innerHTML = content;
	else
	{
		try
		{
			td_body.appendChild(content);
		}
		catch (e) {}
	}

	if (this.clicksToEdit == "double")
	{
		var clkfctn = function()
		{
			if (this.m_tblcls.m_lastCellSelected)
				alib.dom.styleSetClass(this.m_tblcls.m_lastCellSelected, "CDatasheetCell");
			
			alib.dom.styleSetClass(this, "CDatasheetCellSelected");
			this.m_tblcls.m_lastCellSelected = this;
		}

		td_body.onclick = clkfctn;

		if (!f_readonly)
		{
			var dblclkfctn = function()
			{
				var buf = this.innerHTML;
				alib.dom.styleSetClass(this, "CDatasheetCellEdit");
				this.onclick = function() {};
				this.innerHTML = "";
				var inp = ALib.m_document.createElement("input");
				alib.dom.styleSet(inp, "width", "99%");
				alib.dom.styleSet(inp, "height", "100%");
				alib.dom.styleSet(inp, "text-align", (align) ? align : "left");
				inp.value = buf;
				inp.m_td = this;
				inp.onkeydown = function(e)
				{
					this.m_td.m_tblcls.onCellUpdate(this.m_td.m_row.m_name, this.m_td.m_colind);
				}
				inp.onblur = function ()
				{
					inp.m_td.innerHTML = this.value;
					alib.dom.styleSetClass(inp.m_td, "CDatasheetCell");

					inp.m_td.m_tblcls.onCellChange(this.m_td.m_row.m_name, this.m_td.m_colind);

					inp.m_td.onclick = clkfctn;
					inp.m_td.ondblclick = dblclkfctn;
				}
				this.appendChild(inp);
				this.ondblclick = function() {};
				inp.select();
				inp.focus();
			};

			td_body.ondblclick = dblclkfctn;
		}
	}
	else // Single click will edit
	{
		if (!f_readonly)
		{
			var clkfctn = function()
			{
				this.m_origbuf = this.innerHTML;
				alib.dom.styleSetClass(this, "CDatasheetCellEdit");
				this.onclick = function() {};
				this.innerHTML = "";
				var inp = ALib.m_document.createElement("input");
				alib.dom.styleSet(inp, "width", "100%");
				alib.dom.styleSet(inp, "height", "100%");
				alib.dom.styleSet(inp, "text-align", (align) ? align : "left");
				inp.value = this.m_origbuf;
				inp.m_td = this;
				inp.onkeyup = function(e)
				{
					this.m_td.m_tblcls.onCellUpdate(this.m_td.m_row.m_name, this.m_td.m_colind);
				}
				inp.onblur = function ()
				{
					inp.m_td.innerHTML = this.value;
					alib.dom.styleSetClass(inp.m_td, "CDatasheetCell");
					inp.m_td.onclick = clkfctn;
					if (this.value != inp.m_td.m_origbuf)
						inp.m_td.m_tblcls.onCellChange(this.m_td.m_row.m_name, this.m_td.m_colind);
				}
				this.appendChild(inp);
				this.onclick = function() {};
				inp.select();
				inp.focus();
			};

			td_body.onclick = clkfctn;
		}
	}

	this.m_rows[name].m_row.appendChild(td_body);
	
	return td_body;
}

CDatasheet.prototype.print = function (div_parent)
{

	if (div_parent)
		div_parent.appendChild(this.m_table);
	else
		document.write(this.m_table.outerHTML);

	this.fixColSize();
}

CDatasheet.prototype.getValue = function(row, col)
{
	if (this.m_rows[row].m_cols[col])
	{
		//return this.m_rows[row].m_cols[col].m_td.innerHTML;
		return this.m_rows[row].getValue(col);
	}
}

CDatasheet.prototype.setValue = function(row, col, val)
{
	if (this.m_rows[row].m_cols[col])
		this.m_rows[row].m_cols[col].m_td.innerHTML = val;
}

// Give auto cols a width so they do not resize on edit
CDatasheet.prototype.fixColSize = function()
{
	for (var i = 0; i < this.m_cols.length; i++)
	{
		var width = alib.dom.styleGet(this.m_cols[i], "width");
		alib.dom.styleSet(this.m_cols[i], "width", width);
	}
}

function CDatasheetRow()
{
	this.m_row;
	this.m_hinst;
	this.m_name;
	this.m_titlecell = null;
	this.m_uni_id = null;
	this.m_colind = 0;
	this.m_cols = new Array();
}

CDatasheetRow.prototype.setName = function(name)
{
	this.m_hinst.m_rows[name] = this.m_hinst.m_rows[this.m_name];
	this.m_hinst.m_rows[this.m_name] = null;
	this.m_name = name;
}

CDatasheetRow.prototype.setTitle = function(title)
{
	if (this.m_titlecell)
	{
		if (typeof title == "string" || typeof title == "number")
			this.m_titlecell.innerHTML = title;
		else
		{
			try
			{
				this.m_titlecell.appendChild(title);
			}
			catch (e) {}
		}
	}
}
 
CDatasheetRow.prototype.addCell = function (content, align, width, height, readonly)
{
	// Create defaults
	if (!content)
		var content = null;
	if (!align)
		var align = null;
	if (!width)
		var width = null;
	if (!height)
		var height = null;
	if (!readonly)
		var readonly = null;

	this.m_cols[this.m_colind] = new CDatasheetCell(content, this);

	this.m_cols[this.m_colind].m_td = this.m_hinst.addCell(content, align, width, height, readonly, this.m_colind, this);

	this.m_colind++;
}

CDatasheetRow.prototype.deleteRow = function()
{
	this.m_hinst.removeRow(this.m_name);
}

CDatasheetRow.prototype.getId = function()
{
	return this.m_uni_id;
}

CDatasheetRow.prototype.getValue = function(col)
{
	return this.m_cols[col].m_td.innerHTML;
}

CDatasheetRow.prototype.cols = function(colname)
{
	return this.m_cols[colname];
}

function CDatasheetCell(title, row)
{
	this.m_name = row;
	this.m_title = null;
	this.m_td = null;
}

CDatasheetCell.prototype.setTitle = function(title)
{
	if (typeof title == "string" || typeof title == "number")
		this.m_td.innerHTML = title;
	else
	{
		try
		{
			this.m_td.innerHTML = "";
			this.m_td.appendChild(title);
		}
		catch (e) {}
	}
}
