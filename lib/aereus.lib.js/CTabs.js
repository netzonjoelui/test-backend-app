/****************************************************************************
*	
*	Class:		CTabs
*
*	Purpose:	Build tab navigation
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CTabs()
{
	this.m_document = ALib.m_document;
	var doc = this.m_document;
	this.m_outerdv = doc.createElement("div");

	this.m_navOuter = alib.dom.createElement("div", this.m_outerdv); // Will act as a container for all navigation elements for getting real height

	// Create right div
	this.m_rightdiv = doc.createElement("div");
	alib.dom.styleSet(this.m_rightdiv, "float", "right");
	this.m_navOuter.appendChild(this.m_rightdiv);

	// Create nav row
	this.m_navrow = doc.createElement("div");
	this.m_navOuter.appendChild(this.m_navrow);
	var tbl = doc.createElement("table");
	this.m_navrow.appendChild(tbl);
	tbl.setAttribute("cellpadding","0");
	tbl.cellPadding = "0";	
	tbl.setAttribute("cellspacing","0");
	tbl.cellSpacing = "0";
	var tbl_bdy = doc.createElement("tbody");
	tbl.appendChild(tbl_bdy);
	// Create tab row
	this.m_tabrow = doc.createElement("tr");
	tbl_bdy.appendChild(this.m_tabrow);
	
	// Create hr row
	var dv = this.m_document.createElement("div");
	alib.dom.styleSetClass(dv, "CTTabHr");
	this.m_navOuter.appendChild(dv);

	// Create content div
	this.m_con = this.m_document.createElement("div");
	//alib.dom.styleSet(this.m_con, "padding", "1px");
	this.m_outerdv.appendChild(this.m_con);
	this.m_pages = new Array();
	this.m_next_index = 0;
	this.m_default_index = 0;
	this.m_numTabs = 0;
	this.curr_index = 0;
    
    this.cbData = new Object();
}

CTabs.prototype.addTab = function(label, clk_act, act_args)
{
	// Create tab object
	this.m_pages[this.m_next_index] = new Object();
	this.m_pages[this.m_next_index].label = label;
	this.m_pages[this.m_next_index].ind = this.m_next_index;
	this.m_pages[this.m_next_index].container = this.m_document.createElement("div");

	// Add tab to tabrow
	this.m_pages[this.m_next_index].td_l = this.m_document.createElement("td");
	this.m_tabrow.appendChild(this.m_pages[this.m_next_index].td_l);
	
	this.m_pages[this.m_next_index].td_b = this.m_document.createElement("td");
	this.m_pages[this.m_next_index].td_b.innerHTML = label;
	this.m_tabrow.appendChild(this.m_pages[this.m_next_index].td_b);

	var me = this;
	this.m_pages[this.m_next_index].td_b.m_cls = me;
	this.m_pages[this.m_next_index].td_b.m_ind = this.m_next_index;
	if (clk_act)
		this.m_pages[this.m_next_index].td_b.clk_act = clk_act;
	if (act_args)
		this.m_pages[this.m_next_index].td_b.act_args = act_args;
	this.m_pages[this.m_next_index].td_b.onclick = function ()
	{
	    this.m_cls.selectTab(this.m_ind);
            
		if (this.clk_act)
		{
			try
			{
				if (typeof this.clk_act == "string")
				{
					if (this.act_args)
					{
						var passargs = "";
						for (var j = 0; j < act_args.length; j++)
						{
							if (passargs.length>0) passargs += ",";
							passargs += "\"" + act_args[j] + "\"";
						}

						eval(this.clk_act + "(" + passargs + ")");
					}
					else
					{
						eval(this.clk_act + "()");
					}
				}
				else
				{
					if (this.act_args)
					{
						switch (this.act_args.length)
						{
						case 1:
							this.clk_act(this.act_args[0]);
							break;
						case 2:
							this.clk_act(this.act_args[0], this.act_args[1]);
							break;
						case 3:
							this.clk_act(this.act_args[0], this.act_args[1], this.act_args[2]);
							break;
						case 4:
							this.clk_act(this.act_args[0], this.act_args[1], 
											 this.act_args[2], this.act_args[3]);
							break;
						case 5:
							this.clk_act(this.act_args[0], this.act_args[1], 
											 this.act_args[2], this.act_args[3], this.act_args[4]);
							break;
						case 6:
							this.clk_act(this.act_args[0], this.act_args[1], 
											 this.act_args[2], this.act_args[3], this.act_args[4],
											 this.act_args[5]);
							break;
						case 7:
							this.clk_act(this.act_args[0], this.act_args[1], 
											 this.act_args[2], this.act_args[3], this.act_args[4],
											 this.act_args[5], this.act_args[6]);
							break;
						case 8:
							this.clk_act(this.act_args[0], this.act_args[1], 
											 this.act_args[2], this.act_args[3], this.act_args[4],
											 this.act_args[5], this.act_args[6], this.act_args[7]);
							break;
						case 9:
							this.clk_act(this.act_args[0], this.act_args[1], 
											 this.act_args[2], this.act_args[3], this.act_args[4],
											 this.act_args[5], this.act_args[6], this.act_args[7],
											 this.act_args[8]);
							break;
						case 10:
							this.clk_act(this.act_args[0], this.act_args[1], 
											 this.act_args[2], this.act_args[3], this.act_args[4],
											 this.act_args[5], this.act_args[6], this.act_args[7],
											 this.act_args[8], this.act_args[9]);
							break;
						}
					}
					else
					{
						this.clk_act();
					}
				}
			}
			catch (e) {}
		}
	}
	
	this.m_pages[this.m_next_index].td_r = this.m_document.createElement("td");
	this.m_tabrow.appendChild(this.m_pages[this.m_next_index].td_r);

	// Check for display of current tab
	if (this.m_next_index != this.m_default_index)
	{
		alib.dom.styleSet(this.m_pages[this.m_next_index].container, "display", "none");
		this.setTabState(this.m_next_index, "off");
	}
	else
	{
		this.setTabState(this.m_next_index, "on");
	}
	
	this.m_con.appendChild(this.m_pages[this.m_next_index].container);
	alib.dom.styleSetClass(this.m_pages[this.m_next_index].container, "CTTabBody");

	var lastind = this.m_next_index;
	this.m_next_index++;

	this.m_numTabs++;

	return this.m_pages[lastind].container;
}

CTabs.prototype.onSelectTab = function(index)
{
    return true;
}
    
CTabs.prototype.selectTab = function(indx)
{
    if(!this.onSelectTab(indx))
        return;
    
	this.curr_index = indx;
	if (this.m_lasttab)
		this.setTabState(this.m_lasttab, "off");
	else
		this.setTabState(this.m_default_index, "off");

	this.setTabState(indx, "on");
	
	this.m_lasttab = indx;
}

CTabs.prototype.setTabState = function(tabind, state)
{
	switch (state)
	{
	case 'on':
		alib.dom.styleSetClass(this.m_pages[tabind].td_l, "CTTabLeftOn");
		alib.dom.styleSetClass(this.m_pages[tabind].td_b, "CTTabCenterOn");
		alib.dom.styleSetClass(this.m_pages[tabind].td_r, "CTTabRightOn");
		alib.dom.styleSet(this.m_pages[tabind].container, "display", "block");
		break;
	case 'off':
		alib.dom.styleSetClass(this.m_pages[tabind].td_l, "CTTabLeftOff");
		alib.dom.styleSetClass(this.m_pages[tabind].td_b, "CTTabCenterOff");
		alib.dom.styleSetClass(this.m_pages[tabind].td_r, "CTTabRightOff");
		alib.dom.styleSet(this.m_pages[tabind].container, "display", "none");
		break;
	}
}

CTabs.prototype.setTabTitle = function(tabind, title)
{
	this.m_pages[tabind].td_b.innerHTML = title;
}

CTabs.prototype.deleteTab = function(tabind)
{
	this.m_pages[tabind].td_l.style.display = "none";
	this.m_pages[tabind].td_b.style.display = "none";
	this.m_pages[tabind].td_r.style.display = "none";
	
	this.m_numTabs--;
}

CTabs.prototype.getIndex = function()
{
	return this.curr_index;
}

CTabs.prototype.getRightCon = function()
{
	return this.m_rightdiv;
}

CTabs.prototype.getPageCon = function(ind)
{
	return this.m_pages[ind].container;
}

CTabs.prototype.print = function (container)
{
	if (container)
		container.appendChild(this.m_outerdv);
	else
		document.write(this.m_table.outerHTML);
}

CTabs.prototype.getTabHeight = function()
{
	return alib.dom.getContentHeight(this.m_navOuter);
}

CTabs.prototype.getHeight = function()
{
	return this.getTabHeight();
}

CTabs.prototype.getNumTabs = function()
{
	return this.m_numTabs;
}

CTabs.prototype.setHeight = function(height)
{
	// get Nav
	var navheight = this.getTabHeight();

	// outer
	alib.dom.styleSet(this.m_outerdv, "height", height);

	//var fullheight = alib.dom.getContentHeight(this.m_outerdv);

	alib.dom.styleSet(this.m_con, "height", (height-navheight)+"px");
	alib.dom.styleSet(this.m_con, "overflow", "auto");

	for (var i = 0; i < this.m_pages.length; i++)
	{
		//alib.dom.styleSet(this.m_pages[i].container, "height", "100%");
		//alib.dom.styleSet(this.m_pages[i].container, "overflow", "auto");
	}
}
