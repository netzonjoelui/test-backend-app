/****************************************************************************
*	
*	Class:		CSplitContainer
*
*	Purpose:	Create a div-based frame set similar to html frames but using divs
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CSplitContainer(orientation, width, height)
{
	// Types can be "verticle" or "horizontal"
	this.m_orientation = (orientation) ? orientation : "verticle";

	this.m_document = ALib.m_document;
	this.m_con = this.m_document.createElement("div");

	this.m_tbl = this.m_document.createElement("table");
    alib.dom.styleSet(this.m_tbl, "table-layout", "fixed");

	if (width)
		alib.dom.styleSet(this.m_tbl, "width", width);
	else
		alib.dom.styleSet(this.m_tbl, "width", "100%");

	if (height)
		alib.dom.styleSet(this.m_tbl, "height", height);

	this.m_tbl.setAttribute("cellpadding","0");
	this.m_tbl.cellPadding = "0";	
	this.m_tbl.setAttribute("cellspacing","0");
	this.m_tbl.cellSpacing = "0";
	this.m_con.appendChild(this.m_tbl);

	this.m_tbody = this.m_document.createElement("tbody");
	this.m_tbl.appendChild(this.m_tbody);

	if (height)
		alib.dom.styleSet(this.m_tbody, "height", height);

	if (this.m_orientation == "verticle")
	{
		this.m_row = this.m_document.createElement("tr");
		this.m_tbody.appendChild(this.m_row);
	}

	this.resizable = false;
	this.m_columns = new Array();

	this.onPanelResize = new Function();
	this.onPanelResizeStart = new Function();

	this.m_height = (height) ? height : null;
}

CSplitContainer.prototype.addPanel = function(size, overflow)
{
	if (typeof overflow == "undefined")
		var overflow = "auto";

	// if this is not the first panel and this.resizable == true
	if (this.m_columns.length >= 1 && this.resizable)
	{
		var res_dv = this.m_document.createElement("td");
		// Add a column for resize bar
		if (this.m_orientation == "verticle")
		{
			alib.dom.styleSetClass(res_dv, "CSplitContainerVertResizeBar");
			res_dv.onmouseover = function () { alib.dom.styleSetClass(this, "CSplitContainerVertResizeBarOver"); };
			res_dv.onmouseout = function () { alib.dom.styleSetClass(this, "CSplitContainerVertResizeBar"); };
			this.m_row.appendChild(res_dv);
		}
		else
		{
			alib.dom.styleSetClass(res_dv, "CSplitContainerHorizResizeBar");
			res_dv.onmouseover = function () { alib.dom.styleSetClass(this, "CSplitContainerHorizResizeBarOver"); };
			res_dv.onmouseout = function () { alib.dom.styleSetClass(this, "CSplitContainerHorizResizeBar"); };
			this.m_row = this.m_document.createElement("tr");
			this.m_row.appendChild(res_dv);
			this.m_tbody.appendChild(this.m_row);
		}
	}
	
	var col_dv = this.m_document.createElement("td");
	alib.dom.styleSet(col_dv, "vertical-align", "top");

	var col_inner_dv = this.m_document.createElement("div"); // Used to place content so we can use overflow attribute (won't work with td)
	col_dv.col_inner_dv = col_inner_dv;
	col_dv.appendChild(col_inner_dv);
	alib.dom.styleSet(col_inner_dv, "overflow", overflow);
	//alib.ui.slimScroll(col_inner_dv, {});
	if (this.m_orientation == "verticle" && this.m_height)
	{
		alib.dom.styleSet(col_inner_dv, "height", this.m_height);
	}

	if (this.m_orientation == "verticle")
	{
		if (size != "*" && size != "")
		{
			alib.dom.styleSet(col_dv, "width", size);
		}
		this.m_row.appendChild(col_dv);
	}
	else
	{
		if (size != "*" && size != "")
		{
			alib.dom.styleSet(col_dv, "height", size);
		}
		this.m_row = this.m_document.createElement("tr");
		this.m_row.appendChild(col_dv);
		this.m_tbody.appendChild(this.m_row);
	}
	
	this.m_columns[this.m_columns.length] = col_dv;
	
	// Make resize bar dragable (if exists)
	if (res_dv)
	{
		// Add a column for resize bar
		if (this.m_orientation == "verticle")
		{
			DragAndDrop.registerDragable(res_dv);
			res_dv.m_cls = this;
			res_dv.m_leftcon = this.m_columns[this.m_columns.length - 2];
			res_dv.m_rightcon = this.m_columns[this.m_columns.length - 1];
			res_dv.onDragStart = function (x, y)
			{
				this.minY = y;
				this.maxY = y;

				this.startX = x;
				
				// maxX should be set to bounds of container
				var l_pos = alib.dom.getElementPosition(this.m_leftcon);
				var r_pos = alib.dom.getElementPosition(this.m_rightcon);

				this.minX = l_pos.x;
				this.maxX = r_pos.r - this.offsetWidth;

				this.m_leftConWidth = this.m_leftcon.offsetWidth;
				this.m_rightConWidth = this.m_rightcon.offsetWidth;

				alib.dom.styleSetClass(this.m_dragCon, "CSplitContainerVertResizeBarOver");
				this.m_cls.onPanelResizeStart();
			};

			res_dv.onDrag = function(x, y)
			{
				var change = x - this.startX;
				var l = (this.m_leftConWidth + change);
				var r = (this.m_rightConWidth + (change*-1));
				alib.dom.styleSet(this.m_leftcon, "width", l + "px");
				alib.dom.styleSet(this.m_rightcon, "width", r + "px");
				//this.m_leftcon.innerHTML = this.m_leftcon.style.width;
				//this.m_rightcon.innerHTML = this.m_rightcon.style.width;
			};

			res_dv.onDragEnd = function(x, y)
			{
				this.m_cls.onPanelResize();
			};
		}
		else
		{
			DragAndDrop.registerDragable(res_dv);
			res_dv.m_cls = this;
			res_dv.m_topcon = this.m_columns[this.m_columns.length - 2];
			res_dv.m_bottomcon = this.m_columns[this.m_columns.length - 1];
			res_dv.onDragStart = function (x, y)
			{
				this.minX = x;
				this.maxX = x;

				this.startY = y;
				
				// maxY should be set to bounds of container
				var t_pos = alib.dom.getElementPosition(this.m_topcon);
				var b_pos = alib.dom.getElementPosition(this.m_bottomcon);

				this.minY = t_pos.y;
				this.maxY = b_pos.b - this.offsetHeight;

				this.m_topConHeight = this.m_topcon.offsetHeight;
				this.m_bottomConHeight = this.m_bottomcon.offsetHeight;

				alib.dom.styleSetClass(this.m_dragCon, "CSplitContainerHorizResizeBarOver");
				
				this.m_cls.onPanelResizeStart();
			};

			res_dv.onDrag = function(x, y)
			{
				var change = y - this.startY;
				var t = (this.m_topConHeight + change);
				var b = (this.m_bottomConHeight + (change * -1));
				alib.dom.styleSet(this.m_topcon, "height", t + "px");
				alib.dom.styleSet(this.m_bottomcon, "height", b + "px");
				//this.m_leftcon.innerHTML = this.m_leftcon.style.width;
				//this.m_rightcon.innerHTML = this.m_rightcon.style.width;
			};

			res_dv.onDragEnd = function(x, y)
			{
				this.m_cls.onPanelResize();
			};
		}
	}
	return col_inner_dv;
}

CSplitContainer.prototype.getPanelCon = function(indx)
{
	return this.m_columns[indx];
}

CSplitContainer.prototype.print = function(con)
{
	con.appendChild(this.m_con);
}

CSplitContainer.prototype.setHeight = function(height)
{
	this.m_height = height;

	alib.dom.styleSet(this.m_tbl, "height", height);
	alib.dom.styleSet(this.m_tbody, "height", height);

	if (this.m_orientation == "verticle" && this.m_height)
	{
		for (var i = 0; i  < this.m_columns.length; i++)
		{
			alib.dom.styleSet(this.m_columns[i].col_inner_dv, "height", this.m_height);
		}
	}
}

