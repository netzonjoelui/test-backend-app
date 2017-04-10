/****************************************************************************
*	
*	Class:		CDataCenterDb
*
*	Purpose:	Main (sub)application for editing a database
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/
function CDataCenterDashboard(appclass)
{
	this.m_appclass = appclass;
	this.m_cols = new Array();	
	
	this.loadWidgets(appclass.appMain);	
}

/*************************************************************************
*	Function:	loadWidgets
*
*	Purpose:	Load widgets and reports
**************************************************************************/
CDataCenterDashboard.prototype.loadWidgets = function(con)
{
	con.innerHTML = "Loading...";
	var ajax = new CAjax();
	ajax.m_appclass = this.m_appclass;
	ajax.m_con = con;
	ajax.m_dashclass = this;
	ajax.onload = function(root)
	{
		this.m_con.innerHTML = "";

		var spcon = new CSplitContainer();
		spcon.m_dashclass = this.m_dashclass;
		spcon.onPanelResizeStart = function() { this.m_dashclass.onColResizeStart(); };
		spcon.onPanelResize = function() { this.m_dashclass.onColResize(); };
		spcon.resizable = true;
		spcon.print(this.m_con);

		var num = root.getNumChildren();
		for (i = 0; i < num; i++)
		{
			var child = root.getChildNode(i);
			if (child.m_name == "column")
			{
				var width = child.getAttribute("width");
				var col = spcon.addPanel(width);
				this.m_dashclass.m_cols[this.m_dashclass.m_cols.length] = col;

				var num_wids = child.getNumChildren();
				for (w = 0; w < num_wids; w++)
				{
					var wid = child.getChildNode(w);
					if (wid.m_name == "widget")
					{
						var dbname = "";
						var graph_id = "";
						var id = "";
						var type = "";

						var num_vars = wid.getNumChildren();
						for (j = 0; j < num_vars; j++)
						{
							var dbattr = wid.getChildNode(j);
							switch (dbattr.m_name)
							{
							case "name":
								dbname = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
								break;
							case "id":
								id = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
								break;
							case "graph_id":
								graph_id = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
								break;
							case "type":
								type = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
								break;
							}
						}
						
						// Create contianer (handles drag and drop)
						var wbox = new CWidgetBox("dz_dashboard");
						wbox.m_cls = this.m_dashclass;
						wbox.onMoved = function(newcon) 
						{ 
							this.m_cls.saveLayout(); 
							this.m_cls.loadGraph(newcon.m_con, newcon.m_gid);
						}
						wbox.onBeforeMove = function(from, to) 
						{ 
							//from.parentNode.m_con.innerHTML = "";	
						}
						var w_con = wbox.getCon();
						w_con.m_id = id;
						w_con.m_gid = graph_id;
						wbox.print(col);

						// Create content table
						var cct = new CContentTable(dbname, "100%");
						var cct_con = cct.get_cdiv();
						w_con.m_con = cct_con;
						
						var del_dv = cct.get_ctitle();
						del_dv.m_id = id;
						del_dv.m_wcon = w_con;
						del_dv.onclick = function()
						{
							var wcon = this.m_wcon;
							var cb_fun = function(ret)
							{
								wcon.parentNode.removeChild(wcon);
							};
							var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "dashboard_del_rpt_graph", 
						   							[["eid", this.m_id]], cb_fun);
						}
						alib.dom.styleSet(del_dv, "padding-top", "3px");
						del_dv.innerHTML = "<img border='0' src='images/themes/"+Ant.m_theme+"/icons/deleteTask.gif' />";

						DragAndDrop.registerDragable(cct.getTitleCon(), cct.getOuterCon(), "dz_dashboard");
						var drag_icon = ALib.m_document.createElement("div");
						drag_icon.innerHTML = "Move: " + cct.getTitleCon().innerHTML;
						DragAndDrop.setDragGuiCon(cct.getTitleCon(), drag_icon, 15, 15);
						cct.print(w_con);
					}
				}
				
				// Always add blank resize box at the end of each col
				var wbox = new CWidgetBox("dz_dashboard", "300px");
				wbox.m_cls = this.m_dashclass;
				wbox.onMoved = function(newcon) 
				{ 
					this.m_cls.saveLayout(); 
					this.m_cls.loadGraph(newcon.m_con, newcon.m_gid);
				}
				wbox.onBeforeMove = function(from, to) 
				{ 
					//from.parentNode.m_con.innerHTML = "";	
				}
				wbox.print(col);
			}	
		}
		// Add buffer column
		spcon.addPanel("*");

		this.m_dashclass.loadAllReports();
	};
	ajax.exec("/datacenter/xml_getdcwidgets.awp");
}

/*************************************************************************
*	Function:	loadGraph
*
*	Purpose:	Get and load flash object
**************************************************************************/
CDataCenterDashboard.prototype.loadGraph = function(con, id)
{
	con.innerHTML = "";

	var width = con.offsetWidth;
		
	var cb_fun = function(ret)
	{
		con.innerHTML = ret;
	};
	var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "report_graph_get_obj", 
						   [["gid", id], ["width", width]], cb_fun);
}

/*************************************************************************
*	Function:	loadAllReports
*
*	Purpose:	Get and load flash object
**************************************************************************/
CDataCenterDashboard.prototype.loadAllReports = function()
{
	for (var i = 0; i < this.m_cols.length; i++)
	{
		for (var j = 0; j < this.m_cols[i].childNodes.length; j++)
		{
			if (this.m_cols[i].childNodes[j].m_id)
			{
				this.loadGraph(this.m_cols[i].childNodes[j].m_con, this.m_cols[i].childNodes[j].m_gid);
			}
		}
	}
}

/*************************************************************************
*	Function:	saveLayout
*
*	Purpose:	Send modified layout to server
**************************************************************************/
CDataCenterDashboard.prototype.saveLayout = function()
{
	var args = new Array();

	args[0] = ['num_cols', this.m_cols.length];

	for (var i = 0; i < this.m_cols.length; i++)
	{
		var col_ws = "";

		for (var j = 0; j < this.m_cols[i].childNodes.length; j++)
		{
			if (this.m_cols[i].childNodes[j].m_id)
			{
				col_ws += (col_ws) ? ':' : '';
				col_ws += this.m_cols[i].childNodes[j].m_id;
			}
		}

		args[args.length] = ["col_"+i, col_ws];
	}

	var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "dashboard_save_layout", args);
}

/*************************************************************************
*	Function:	onColResize
*
*	Purpose:	Col has been resized
**************************************************************************/
CDataCenterDashboard.prototype.onColResize = function()
{
	this.loadAllReports();

	var args = new Array();

	args[0] = ['num_cols', this.m_cols.length];

	for (var i = 0; i < this.m_cols.length; i++)
	{
		args[args.length] = ["col_"+i, this.m_cols[i].offsetWidth + "px"];
	}

	var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "dashboard_save_layout_resize", args);
}

/*************************************************************************
*	Function:	onColResizeStart
*
*	Purpose:	Col is being resized
**************************************************************************/
CDataCenterDashboard.prototype.onColResizeStart = function()
{
	for (var i = 0; i < this.m_cols.length; i++)
	{
		for (var j = 0; j < this.m_cols[i].childNodes.length; j++)
		{
			if (this.m_cols[i].childNodes[j].m_id)
			{
				this.m_cols[i].childNodes[j].m_con.innerHTML = "";
			}
		}
	}
}

/*************************************************************************
*	Function:	loadRUDatabases
*
*	Purpose:	Load recently used databases
**************************************************************************/
CDataCenterDashboard.prototype.loadRUDatabases = function(ctb1_con)
{
	// Load Popular databases
	ctb1_con.innerHTML = "Loading...";
	var ajax = new CAjax();
	ajax.m_navsec = this.m_appclass.m_nBdbsec;
	ajax.m_appclass = this.m_appclass;
	ajax.m_con = ctb1_con;
	ajax.onload = function(root)
	{
		this.m_con.innerHTML = "";
		var ul = ALib.m_document.createElement("ul");
		this.m_con.appendChild(ul);

		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var dbname = "";
				var dbid = "";

				var child = root.getChildNode(i);
				if (child.m_name == "database")
				{
					var num_vars = child.getNumChildren();
					for (j = 0; j < num_vars; j++)
					{
						var dbattr = child.getChildNode(j);
						switch (dbattr.m_name)
						{
						case "name":
							dbname = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						case "id":
							dbid = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						}
					}
				}

				var li = ALib.m_document.createElement("li");
				alib.dom.styleSet(li, "cursor", "pointer");
				ul.appendChild(li);
				li.innerHTML = dbname;
				li.m_app = this.m_appclass;
				li.m_dbid = dbid;
				li.onclick = function()
				{
					this.m_app.frmDbEdit(this.m_dbid);
					this.m_navbar.itemChangeState("db_"+this.m_dbid, "on");
				}
			}
		}
		else
			this.m_con.innerHTML = "No databases found!";
	};
	ajax.exec("/datacenter/xml_getdatabases.awp");
}
