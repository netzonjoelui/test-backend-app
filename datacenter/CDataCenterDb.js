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
function CDataCenterDb(dbid, appclass)
{
	this.m_dbid = dbid;
	this.m_appclass = appclass;

	
	this.m_appclass.appMain.innerHTML = "Loading database...";

	this.getData();

	this.m_dbinfo = new Object();
	this.m_dbinfo.folders = new Array();
	this.m_dbinfo.folders_add = new Array();
	this.m_dbinfo.folders_remove = new Array();
	this.m_dbinfo.calendars = new Array();
	this.m_dbinfo.calendars_remove = new Array();
	this.m_objects = new Array();
	
	// Register this class for reference by popups
	Ant.setPopupHandle(this, "adc_open_db");
	Ant.setHinst(this, "adc_open_db");
}

/*************************************************************************
*	Function:	getData
*
*	Purpose:	Retrieve database details via ajax
**************************************************************************/
CDataCenterDb.prototype.getData = function()
{
	this.m_ajax = new CAjax();
	
	var me = this;
	this.m_ajax.m_dbc = me;
	this.m_ajax.onload = function(root)
	{
		// The result will be held in a variable called 'retval'
		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var child = root.getChildNode(i);
				if (child.m_name == "name")
				{
					if (child.m_text)
						this.m_dbc.m_dbinfo.name = unescape(child.m_text);
				}
				if (child.m_name == "f_publish")
				{
					if (child.m_text)
						this.m_dbc.m_dbinfo.publish = unescape(child.m_text);
				}
				if (child.m_name == "scope")
				{
					if (child.m_text)
						this.m_dbc.m_dbinfo.scope = unescape(child.m_text);
				}
				if (child.m_name == "objects")
				{
					for (var m = 0; m < child.getNumChildren(); m++)
					{
						var obj_child = child.getChildNode(m);
						var num_vars = obj_child.getNumChildren();
						var obj = new Object();
						for (j = 0; j < num_vars; j++)
						{
							var dbattr = obj_child.getChildNode(j);
							switch (dbattr.m_name)
							{
							case "title":
								obj.title = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
								break;
							case "objname":
								obj.name = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
								break;
							case "id":
								obj.id = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
								break;
							case "f_primary":
								obj.f_primary = (dbattr.m_text) ? (dbattr.m_text=='t')?true:false : false;
								break;
							}
						}

						this.m_dbc.m_objects[this.m_dbc.m_objects.length] = obj;
					}
				}
				if (child.m_name == "folders")
				{
					for (var m = 0; m < child.getNumChildren(); m++)
					{
						var obj = child.getChildNode(m);
						var ind = this.m_dbc.m_dbinfo.folders.length;
						this.m_dbc.m_dbinfo.folders[ind] = new Object();
						this.m_dbc.m_dbinfo.folders[ind].id = obj.m_text;
						this.m_dbc.m_dbinfo.folders[ind].name = unescape(obj.getAttribute("name"));
					}
				}
				if (child.m_name == "calendars")
				{
					for (var m = 0; m < child.getNumChildren(); m++)
					{
						var obj = child.getChildNode(m);
						var ind = this.m_dbc.m_dbinfo.calendars.length;
						this.m_dbc.m_dbinfo.calendars[ind] = new Object();
						this.m_dbc.m_dbinfo.calendars[ind].id = obj.m_text;
						this.m_dbc.m_dbinfo.calendars[ind].name = unescape(obj.getAttribute("name"));
					}
				}
			}
		}

		this.m_dbc.buildInterface();
	};

	var url = "/datacenter/xml_getdbinfo.awp?dbid=" + this.m_dbid;

	this.m_ajax.exec(url);
}

/*************************************************************************
*	Function:	buildInterface
*
*	Purpose:	Create interface after data is loaded
**************************************************************************/
CDataCenterDb.prototype.buildInterface = function ()
{
	this.m_appclass.appMain.innerHTML = "";

	// Creat the add new database form
	this.ctbl = new CContentTable("Database: "+this.m_dbinfo.name, "100%");
	this.ctbl.print(this.m_appclass.appMain);
	var ctbl_con = Ant.m_document.createElement("div");
	this.ctbl.get_cdiv().appendChild(ctbl_con);
	Ant.Dom.styleSet(ctbl_con, "padding", "3px");

	var tabs = new CTabs();

	// Details
	var tabcon1 = tabs.addTab("Details");
	this.tabDetails(tabcon1);
	// Tables
	var tabcon_obj = tabs.addTab("Objects");
	this.tabObjects(tabcon_obj);
	// Tables
	var tabcon2 = tabs.addTab("Tables");
	this.tabTables(tabcon2);
	// Forms
	//var tabcon3 = tabs.addTab("Forms");
	//this.tabForms(tabcon3);
	// Workflow
	//var tabcon4 = tabs.addTab("Workflow");
	//this.tabForms(tabcon4);
	// Qeries
	var tabcon5 = tabs.addTab("Queries");
	this.tabQueries(tabcon5);
	// Reports
	this.m_conRpt= tabs.addTab("Reports");
	this.tabReports(this.m_conRpt);
	// Cubes
	//var tabcon7 = tabs.addTab("Cubes");
	//this.tabCubes(tabcon7);
	// Users
	var tabcon8 = tabs.addTab("Public Users");
	this.tabUsers(tabcon8);
	// Members
	var tabcon9 = tabs.addTab("Members");
	this.tabMembers(tabcon9);
	// Workflow
	var tabcon10 = tabs.addTab("Workflow");
	this.tabWorkflows(tabcon10);
	// Folders
	//var tabcon11 = tabs.addTab("Folders");
	//this.tabFolders(tabcon11);
	// Calendars
	//var tabcon12 = tabs.addTab("Calendars");
	//this.tabCalendars(tabcon12);
	
	tabs.print(ctbl_con);
}

/*************************************************************************
*	Function:	saveData
*
*	Purpose:	Saves all data to the database via ajax
**************************************************************************/
CDataCenterDb.prototype.saveData = function()
{
	var fun = function(uid, cls)
	{
		cls.m_appclass.m_navbar.setItemLabel("db_"+cls.m_dbid, cls.m_dbinfo.name);
		cls.ctbl.setTitle("Database: " + cls.m_dbinfo.name);
		cls.m_dbinfo.folders_add = new Array();
		cls.m_dbinfo.folders_remove = new Array();
		cls.m_dbinfo.calendars_remove = new Array();

		ALib.statusShowAlert("Changes Saved!", 3000, "bottom", "right");
	};

	var args = [["name", this.m_dbinfo.name], ["scope", this.m_dbinfo.scope], 
				["f_publish", this.m_dbinfo.publish], ["dbid", this.m_dbid]];

	for (var i = 0; i < this.m_dbinfo.folders_add.length; i++)
		args[args.length] = ["folders_add[]", this.m_dbinfo.folders_add[i]];

	for (var i = 0; i < this.m_dbinfo.folders_remove.length; i++)
		args[args.length] = ["folders_remove[]", this.m_dbinfo.folders_remove[i]];

	for (var i = 0; i < this.m_dbinfo.calendars_remove.length; i++)
		args[args.length] = ["calendars_remove[]", this.m_dbinfo.calendars_remove[i]];

	/*
	ALib.m_debug = true;
	AJAX_TRACE_RESPONSE = true;
	*/
	var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "save_database", 
						   args, fun, [this]);
}

CDataCenterDb.prototype.setDbField = function(field, val)
{
	switch (field)
	{
	case 'name':
		this.m_dbinfo.name = val;		
		break;
	case 'scope':
		this.m_dbinfo.scope = val;		
		break;
	case 'f_publish':
		this.m_dbinfo.publish = val;		
		break;
	}
}

//========================================================================
//
//  Section: Details tab
//                                                            
//========================================================================
CDataCenterDb.prototype.tabDetails = function(con)
{
	var dbid = this.m_dbid;
	var appclass = this.m_appclass;
	var me = this;

	// Add Toolbar
	var tb = new CToolbar();
	var btn = new CButton("Save Changes", function(){me.saveData()}, null, "b2");
	tb.AddItem(btn.getButton(), "left");
	var btn = new CButton("Cancel", function(){appclass.frmHome()}, null, "b1");
	tb.AddItem(btn.getButton(), "left");
	var btn = new CButton("Delete Database", function(dbid){appclass.deleteDatabase(dbid); }, [dbid], "b3");
	tb.AddItem(btn.getButton(), "left");
	tb.print(con);

	// General details
	// ---------------------------------------------------------------------
	var wf = new CWindowFrame("Details");
	wf.print(con);
	var dv = wf.getCon();
	
	var tbl = alib.dom.createElement("table", dv);
	var tbody = alib.dom.createElement("tbody", tbl);

	var row = alib.dom.createElement("tr", tbody);

	var td = alib.dom.createElement("td", row); 
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Database Name: ";

	var td = alib.dom.createElement("td", row); 
	var in_name = Ant.m_document.createElement("input");
	in_name.type = "text";
	in_name.value = this.m_dbinfo.name;
	in_name.onchange = function()
	{
		me.setDbField("name", this.value);
	}
	td.appendChild(in_name);

	// Add database id
	var td = alib.dom.createElement("td", row); 
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Database Id: ";
	var td = alib.dom.createElement("td", row); 
	td.innerHTML = this.m_dbid;

	// Scope
	var td = alib.dom.createElement("td", row); 
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Scope: ";
	var sel = alib.dom.createElement("select");
	sel[sel.length] = new Option("User", "user", false, (this.m_dbinfo.scope!='system') ? true : false);
	sel[sel.length] = new Option("System", "system", false, (this.m_dbinfo.scope=='system') ? true : false);
	sel.onchange = function()
	{
		me.setDbField("scope", this.value);
	}
	td.appendChild(sel);

	// Publish
	var td = alib.dom.createElement("td", row); 
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Publish: ";
	var f_pub = alib.dom.createElement("input");
	f_pub.type = "checkbox";
	f_pub.checked = (this.m_dbinfo.publish=='t') ? true : false;
	f_pub.onclick = function() { me.setDbField("f_publish", (this.checked)?'t':'f');}
	td.appendChild(f_pub);

	var tbl = alib.dom.createElement("table", con);
	alib.dom.styleSet(tbl, "width", "100%");
	var tbody = alib.dom.createElement("tbody", tbl);
	var row = alib.dom.createElement("tr", tbody);
	
	// Folders
	// ---------------------------------------------------------------------
	var td = alib.dom.createElement("td", row);
	td.vAlign = "top";
	var wf = new CWindowFrame("Files &amp; Folders");
	wf.print(td);
	var dv = wf.getCon();

	// Add table
	var tbl = new CToolTable("100%");
	tbl.addHeader("Name");
	tbl.addHeader("", "center", "20px");
	tbl.print(dv);
	for (var i = 0; i < this.m_dbinfo.folders.length; i++)
	{
		//var dv = alib.dom.createElement("div", dv_folderscon);
		//dv.innerHTML = this.m_dbinfo.folders[i].name; 
		var rw = tbl.addRow();
		rw.addCell(this.m_dbinfo.folders[i].name);

		var del_dv = alib.dom.createElement("div");
		del_dv.m_rw = rw;
		del_dv.cls = this;
		del_dv.fid = this.m_dbinfo.folders[i].id;
		del_dv.onclick = function()
		{
			this.cls.removeFolder(this.fid, this.m_rw);
		}
		del_dv.innerHTML = "<img border='0' src='/images/icons/deleteTask.gif' />";
		alib.dom.styleSet(del_dv, "cursor", "pointer");
		rw.addCell(del_dv);
	}

	var add_dv = alib.dom.createElement("div", dv);
	var a = alib.dom.createElement("a", add_dv);
	a.href = "javascript:void(0);";
	a.tbl = tbl;
	a.cls = this;
	a.onclick = function()
	{ 
		var cbrowser = new CFileOpen();
		cbrowser.filterType = "folder";
		cbrowser.tbl = this.tbl;
		cbrowser.cls = this.cls;
		cbrowser.onSelect = function(fid, name, path) 
		{
			this.cls.m_dbinfo.folders_add[this.cls.m_dbinfo.folders_add.length] = fid;

			var rw = this.tbl.addRow();
			rw.addCell(name);

			var del_dv = alib.dom.createElement("div");
			del_dv.m_rw = rw;
			del_dv.cls = this.cls;
			del_dv.fid = fid;
			del_dv.onclick = function()
			{
				this.cls.removeFolder(this.fid, this.m_rw);
			}
			del_dv.innerHTML = "<img border='0' src='/images/icons/deleteTask.gif' />";
			alib.dom.styleSet(del_dv, "cursor", "pointer");
			rw.addCell(del_dv);
		}
		cbrowser.showDialog(); 
	}
	a.innerHTML = "Select Folder";
	
	// Calendars
	// ---------------------------------------------------------------------
	var td = alib.dom.createElement("td", row);
	td.vAlign = "top";
	var wf = new CWindowFrame("Calendars");
	wf.print(td);
	var dv = wf.getCon();

	// Add table
	var tbl = new CToolTable("100%");
	tbl.addHeader("Name");
	tbl.addHeader("", "center", "20px");
	tbl.print(dv);
	for (var i = 0; i < this.m_dbinfo.calendars.length; i++)
	{
		var rw = tbl.addRow();
		rw.addCell(this.m_dbinfo.calendars[i].name);

		var del_dv = alib.dom.createElement("div");
		del_dv.m_rw = rw;
		del_dv.cls = this;
		del_dv.cid = this.m_dbinfo.calendars[i].id;
		del_dv.onclick = function()
		{
			this.cls.removeCalendar(this.cid, this.m_rw);
		}
		del_dv.innerHTML = "<img border='0' src='/images/icons/deleteTask.gif' />";
		alib.dom.styleSet(del_dv, "cursor", "pointer");
		rw.addCell(del_dv);
	}

	var add_dv = alib.dom.createElement("div", dv);
	var a = alib.dom.createElement("a", add_dv);
	a.href = "javascript:void(0);";
	a.tbl = tbl;
	a.cls = this;
	a.onclick = function()
	{
		var dlg_p = new CDialog();
		dlg_p.cls = this.cls;
		dlg_p.tbl = this.tbl;
		dlg_p.promptBox("Enter A Name For This Calendar", "Calendar Name:", "Application Calendar");
		dlg_p.onPromptOk = function(val)
		{
			var fun = function(cid, name, tbl, cls)
			{
				if (cid)
				{
					var rw = tbl.addRow();
					rw.addCell(name);

					var del_dv = alib.dom.createElement("div");
					del_dv.m_rw = rw;
					del_dv.cls = cls;
					del_dv.calid = cid;
					del_dv.onclick = function()
					{
						this.cls.removeCalendar(this.calid, this.m_rw);
					}
					del_dv.innerHTML = "<img border='0' src='/images/icons/deleteTask.gif' />";
					alib.dom.styleSet(del_dv, "cursor", "pointer");
					rw.addCell(del_dv);
				}
			};
			var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "create_calendar", [["name", val], ["dbid", this.cls.m_dbid]], fun, [val, this.tbl, this.cls]);
		}
	}
	a.innerHTML = "Create Calendar";
}

/*************************************************************************
*	Function:	removeCalendar
*
*	Purpose:	Remove a calendar
*
**************************************************************************/
CDataCenterDb.prototype.removeCalendar = function(calid, row)
{
	this.m_dbinfo.calendars_remove[this.m_dbinfo.calendars_remove.length] = calid;
	row.deleteRow();
}

/*************************************************************************
*	Function:	removeFolder
*
*	Purpose:	Remove a folder
*
**************************************************************************/
CDataCenterDb.prototype.removeFolder = function(fid, row)
{
	this.m_dbinfo.folders_remove[this.m_dbinfo.folders_remove.length] = fid;
	row.deleteRow();
}

//========================================================================
//
//  Section: Forms tab
//                                                            
//========================================================================
CDataCenterDb.prototype.tabForms = function(con)
{
	var tb = new CToolbar();
	var btn = new CButton("Save Changes", "void(0)", null, "b2");
	tb.AddItem(btn.getButton(), "left");
	tb.print(con);
}

//========================================================================
//
//  Section: Queries tab
//                                                            
//========================================================================

/*************************************************************************
*	Function:	tabQueries
*
*	Purpose:	Create contents of queries tab page
*
*	Arguments:	con - the body container that will hold contents
**************************************************************************/
CDataCenterDb.prototype.tabQueries = function(con)
{
	var pro_params = "width=765,height=600,toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";

	var tb = new CToolbar();
	var btn = new CButton("SQL Query", "window.open('/datacenter/query.awp?dbid="+this.m_dbid+"', 'query_tool', '"+pro_params+"');", null, "b2");
	tb.AddItem(btn.getButton(), "left");
	tb.print(con);

	// Add window frame
	var wf = new CWindowFrame("Database Queries", null, "0px");
	wf.print(con);
	var dv = wf.getCon();

	// Add table
	var tbl = new CToolTable("100%");
	//tbl.addHeader("#", "center", "20px");
	tbl.addHeader("Query Name");
	tbl.addHeader("", "center", "30px");
	tbl.addHeader("Delete", "center", "50px");

	var ajax = new CAjax();
	ajax.m_tbl = tbl;
	ajax.m_app = this;
	ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var tname = "";
				var tid = "";

				var child = root.getChildNode(i);
				if (child.m_name == "query")
				{
					var num_vars = child.getNumChildren();
					for (j = 0; j < num_vars; j++)
					{
						var dbattr = child.getChildNode(j);
						switch (dbattr.m_name)
						{
						case "name":
							tname = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						case "id":
							tid = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						}
					}
				}

				this.m_app.addQueryToList(tid, tname);
			}
		}
	};

	var url = "/datacenter/xml_getqueries.awp?dbid="+this.m_dbid;
	ajax.exec(url);
	
	tbl.print(dv);
	this.m_queries = tbl;
}

/*************************************************************************
*	Function:	addQueryToList
*
*	Purpose:	Called once query has been added to database and ajax ret
*
*	Arguments:	qid - query id, name - name of query
**************************************************************************/
CDataCenterDb.prototype.addQueryToList = function(qid, name)
{
	var rw = this.m_queries.addRow();
	var pro_params = "width=765,height=600,toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";
	
	var del_dv = ALib.m_document.createElement("div");
	del_dv.m_rw = rw;
	del_dv.m_app = this;
	del_dv.m_qid = qid;
	del_dv.onclick = function()
	{
		this.m_app.deleteQuery(this.m_qid, this.m_rw);
	}
	del_dv.innerHTML = "<img border='0' src='/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	rw.addCell(name);
	var btn = new CButton("open", "window.open('/datacenter/query.awp?qid="+qid+"&dbid="+this.m_dbid+"', 'qryo"+qid+"', '"+pro_params+"');", null, "b2");
	rw.addCell(btn.getButton());
	rw.addCell(del_dv, true, "center");
}

CDataCenterDb.prototype.deleteQuery = function(tblid, row)
{
	if (tblid)
	{
		if (confirm("Are you sure you want to delete this query?"))
		{
			var fun = function(tblid)
			{
				row.deleteRow();
			};
			var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "delete_query", [["qid", tblid]], fun);
		}
	}
}

//========================================================================
//
//  Section: Tables tab
//                                                            
//========================================================================

/*************************************************************************
*	Function:	tabTables
*
*	Purpose:	Create contents of tables tab page
*
*	Arguments:	con - the body container that will hold contents
**************************************************************************/
CDataCenterDb.prototype.tabTables = function(con)
{
	con.innerHTML = "";
	var me = this;

	var tb = new CToolbar();
	btn = new CButton("Add Table", function(){me.createTable();}, null, "b1");
	tb.AddItem(btn.getButton(), "right");
	var add_dv = Ant.m_document.createElement("div");
	this.m_txtNewTable = Ant.m_document.createElement("input");
	this.m_txtNewTable.type = "text";
	this.m_txtNewTable.value = "new table name";
	alib.dom.styleSetClass(this.m_txtNewTable, "CToolbarInputBlur");
	this.m_txtNewTable.onfocus = function() { this.value = ""; alib.dom.styleSetClass(this, ""); }
	this.m_txtNewTable.onblur = function()
	{ 
		if (this.value == "")
		{
			alib.dom.styleSetClass(this, "CToolbarInputBlur"); 
			this.value = "new table name"; 
		}
	}
	add_dv.appendChild(this.m_txtNewTable);
	tb.AddItem(add_dv, "right");
	tb.print(con);

	// Add window frame
	var wf = new CWindowFrame("Database Tables", null, "0px");
	wf.print(con);
	var dv = wf.getCon();

	// Add table
	var tbl = new CToolTable("100%");
	//tbl.addHeader("#", "center", "20px");
	tbl.addHeader("Table Name");
	tbl.addHeader("", "center", "30px");
	tbl.addHeader("", "center", "70px");
	tbl.addHeader("Delete", "center", "50px");

	this.m_ajax = new CAjax();
	this.m_ajax.m_tbl = tbl;
	this.m_ajax.m_app = this;
	this.m_ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var tname = "";

				var child = root.getChildNode(i);
				if (child.m_name == "table")
				{
					var num_vars = child.getNumChildren();
					for (j = 0; j < num_vars; j++)
					{
						var dbattr = child.getChildNode(j);
						switch (dbattr.m_name)
						{
						case "name":
							tname = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						}
					}
				}

				this.m_app.addTableToList(tname);
			}
		}
	};

	var url = "/datacenter/xml_gettables.awp?dbid="+this.m_dbid;
	this.m_ajax.exec(url);
	
	tbl.print(dv);
	this.m_tbls = tbl;
}


CDataCenterDb.prototype.addTableToList = function(tname)
{
	var rw = this.m_tbls.addRow();
	var pro_params = "width=765,height=600,toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";
	var opn_params = "toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";
	
	var del_dv = ALib.m_document.createElement("div");
	del_dv.m_rw = rw;
	del_dv.m_app = this;
	del_dv.m_tname = tname;
	del_dv.onclick = function()
	{
		this.m_app.deleteTable(this.m_tname, this.m_rw);
	}
	del_dv.innerHTML = "<img border='0' src='/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	rw.addCell(tname);
	var btn = new CButton("open", "window.open('/datacenter/table_edit.awp?table="+tname+"&dbid="+this.m_dbid+"', 'tblo"+tname+"', '"+opn_params+"');", null, "b2");
	rw.addCell(btn.getButton());
	btn = new CButton("properties", "window.open('/datacenter/table_properties.awp?table="+tname+"&dbid="+this.m_dbid+"', 'tblp"+tname+"', '"+pro_params+"');");
	rw.addCell(btn.getButton());
	rw.addCell(del_dv, true, "center");
}
CDataCenterDb.prototype.createTable = function()
{
	var me = this;

	if (this.m_txtNewTable.value != "")
	{
		var tblname = this.m_txtNewTable.value;

		var fun = function(tname)
		{
			me.addTableToList(tname);	
			me.m_txtNewTable.value = "";
		};
		var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "create_table", 
							   [["tblname", tblname], ["dbid", this.m_dbid]], fun);
	}
}

CDataCenterDb.prototype.deleteTable = function(tblname, row)
{
	if (tblname)
	{
		if (confirm("Are you sure you want to delete "+tblname+"?"))
		{
			var fun = function(tblname)
			{
				row.deleteRow();
			};
			var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "delete_table", [["tname", tblname], ["dbid", this.m_dbid]], fun);
		}
	}
}

//========================================================================
//
//  Section: Objects tab
//                                                            
//========================================================================

/*************************************************************************
*	Function:	tabObjects
*
*	Purpose:	Create contents of objects tab page
*
*	Arguments:	con - the body container that will hold contents
**************************************************************************/
CDataCenterDb.prototype.tabObjects = function(con)
{
	con.innerHTML = "";

	// Add table
	var tbl = new CToolTable("100%");
	//tbl.addHeader("#", "center", "20px");
	tbl.addHeader("Name");
	tbl.addHeader("Primary", "center", "30px");
	tbl.addHeader("", "center", "30px");
	tbl.addHeader("", "center", "65px");
	tbl.addHeader("", "center", "65px");
	tbl.addHeader("", "center", "65px");
	tbl.addHeader("Delete", "center", "50px");

	// Add toolbar
	// -------------------------------------------------------------
	var add_dv = Ant.Dom.createElement("div");
	var txtNewObj = Ant.Dom.createElement("input");
	txtNewObj.type = "text";
	alib.dom.setInputBlurText(txtNewObj, "new object name", "CToolbarInputBlur", "", "");
	add_dv.appendChild(txtNewObj);

	var tb = new CToolbar();
	btn = new CButton("Add Object", function(cls, inp, tbl){cls.createObject(inp.value, tbl); inp.value=""; }, [this, txtNewObj, tbl], "b1");
	tb.AddItem(btn.getButton(), "right");
	tb.AddItem(add_dv, "right");
	tb.print(con);

	// Add window frame
	var wf = new CWindowFrame("Objects", null, "0px");
	wf.print(con);
	var dv = wf.getCon();

	var ajax = new CAjax();
	ajax.m_tbl = tbl;
	ajax.m_app = this;
	ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var oname = "";
				var oid = "";
				var dacl = "";

				var child = root.getChildNode(i);
				if (child.m_name == "object")
				{
					var num_vars = child.getNumChildren();
					for (j = 0; j < num_vars; j++)
					{
						var dbattr = child.getChildNode(j);
						switch (dbattr.m_name)
						{
						case "name":
							oname = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						case "id":
							oid = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						case "dacl":
							dacl = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						}
					}
				}

				this.m_app.addObjectToList(oid, oname, dacl, this.m_tbl);
			}
		}
	};

	var url = "/datacenter/xml_getobjects.php?dbid="+this.m_dbid;
	ajax.exec(url);
	
	tbl.print(dv);
}


CDataCenterDb.prototype.addObjectToList = function(oid, oname, dacl, tbl)
{
	var rw = tbl.addRow();
	var pro_params = "width=765,height=600,toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";
	var opn_params = "toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";
	
	var del_dv = alib.dom.createElement("div");
	del_dv.m_rw = rw;
	del_dv.m_app = this;
	del_dv.m_oname = oname;
	del_dv.onclick = function()
	{
		this.m_app.deleteObject(this.m_oname, this.m_rw);
	}
	var icon = (Ant) ? "/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif" : "/images/icons/deleteTask.gif"; 
	del_dv.innerHTML = "<img border='0' src='"+icon+"' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	rw.addCell(oname);
	// Primary radio
	var inp_primary = alib.dom.createElement("input");
	inp_primary.type = "radio";
	inp_primary.name = "primary_object";
	inp_primary.value = oid;
	inp_primary.cls = this;
	inp_primary.onclick = function() { this.cls.setPrimaryObject(this.value); };
	rw.addCell(inp_primary, false, "center");
	// Browse
	var btn = new CButton("browse", "window.open('/objbrowser.php?obj_type="+this.m_dbid+"."+oname+"', 'objb"+oname+"', '"+opn_params+"');", null, "b2");
	rw.addCell(btn.getButton(), false, "center");
	// Import
	btn = new CButton("import", function(oname) { var ob = new CAntObjectImpWizard(oname, Ant.user.id); ob.showDialog(); }, [this.m_dbid+"."+oname]);
	rw.addCell(btn.getButton(), false, "center");
	// Properties 
	btn = new CButton("properties", "window.open('/objects/object_edit.php?obj_type="+this.m_dbid+"."+oname+"', 'objp"+oname+"', '"+pro_params+"');");
	rw.addCell(btn.getButton(), false, "center");
	// Security 
	btn = new CButton("permissions", function(dacl) { loadDacl(dacl); }, [dacl]);
	rw.addCell(btn.getButton(), false, "center");
	// Print delete
	rw.addCell(del_dv, true, "center");
}
CDataCenterDb.prototype.createObject = function(oname, tbl)
{
	if (oname != "")
	{
		var fun = function(ret, oname, cls, tbl)
		{
			cls.addObjectToList(ret, oname, tbl);

			cls.m_objects[cls.m_objects.length].name = oname;
		};

		/*
		ALib.m_debug = true;
		AJAX_TRACE_RESPONSE = true;
		*/
		oname = this.escapeObjectName(oname);
		var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "create_object", 
							   [["oname", oname], ["dbid", this.m_dbid]], fun, [oname, this, tbl]);
	}
}


CDataCenterDb.prototype.deleteObject = function(oname, row)
{
	if (oname != "")
	{
		var fun = function(ret, cls, oname, row)
		{
			row.deleteRow();
			for (var i = 0; i < cls.m_objects.length; i++)
			{
				if (cls.m_objects[i].name == oname)
					cls.m_objects.splice(i, 1);
			}
		};

		/*
		ALib.m_debug = true;
		AJAX_TRACE_RESPONSE = true;
		*/
		var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "delete_object", 
							   [["oname", oname], ["dbid", this.m_dbid]], fun, [this, oname, row]);
	}
}

CDataCenterDb.prototype.setPrimaryObject = function(oid)
{
	if (oname != "")
	{
		var fun = function(ret, cls, oname, row)
		{
			row.deleteRow();
			for (var i = 0; i < cls.m_objects.length; i++)
			{
				if (cls.m_objects[i].name == oname)
					cls.m_objects.splice(i, 1);
			}
		};

		/*
		ALib.m_debug = true;
		AJAX_TRACE_RESPONSE = true;
		*/
		var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "delete_object", 
							   [["oname", oname], ["dbid", this.m_dbid]], fun, [this, oname, row]);
	}
}

CDataCenterDb.prototype.escapeObjectName = function(title)
{
	var name = title.toLowerCase();
	name = name.replace(" ", "_");
	//name = namestr.replace("'", "");
	name = name.replace(/[^a-zA-Z0-9_]+/g,'');
	return name;
}

//========================================================================
//
//  Section: Workflow tab
//                                                            
//========================================================================

/*************************************************************************
*	Function:	tabWorkflows
*
*	Purpose:	Create contents of workflows tab page
*
*	Arguments:	con - the body container that will hold contents
**************************************************************************/
CDataCenterDb.prototype.tabWorkflows = function(con)
{
	con.innerHTML = "";

	// Add table
	var tbl = new CToolTable("100%");
	//tbl.addHeader("#", "center", "20px");
	tbl.addHeader("Name");
	tbl.addHeader("Object Type", "center", "60px");
	tbl.addHeader("Active", "center", "30px");
	tbl.addHeader("", "center", "30px");
	tbl.addHeader("Delete", "center", "50px");

	// Add toolbar
	// -------------------------------------------------------------
	var tb = new CToolbar();
	var dm = new CDropdownMenu();
	for (var i = 0; i < this.m_objects.length; i++)
	{
		if (this.m_objects[i])
			dm.addEntry(this.m_objects[i].title, function(cls, obj_type) { cls.openWorkflow(null, obj_type); }, null, null, [this, this.m_objects[i].name]);
	}
	//of_con.appendChild(dm.createButtonMenu("Click for Dropdown"));
	//btn = new CButton("Create Workflow", function(cls, inp, tbl){cls.createObject(inp.value, tbl); inp.value=""; }, [this, txtNewObj, tbl], "b2");
	tb.AddItem(dm.createButtonMenu("Create Workflow"));
	tb.print(con);

	// Add window frame
	var wf = new CWindowFrame("Objects", null, "0px");
	wf.print(con);
	var dv = wf.getCon();

	var ajax = new CAjax();
	ajax.m_tbl = tbl;
	ajax.m_app = this;
	ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var oname = "";
				var id = "";
				var act = "";
				var object_type = "";

				var child = root.getChildNode(i);
				if (child.m_name == "workflow")
				{
					var num_vars = child.getNumChildren();
					for (j = 0; j < num_vars; j++)
					{
						var dbattr = child.getChildNode(j);
						switch (dbattr.m_name)
						{
						case "name":
							oname = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						case "id":
							id = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						case "act":
							act = (dbattr.m_text) ? unescape(dbattr.m_text) : "f";
							break;
						case "object_type":
							object_type = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						}
					}
				}

				this.m_app.addWorkflowToList(id, oname, act, object_type, this.m_tbl);
			}
		}
	};

	var strObjTypes = "";
	for (var i = 0; i < this.m_objects.length; i++)
	{
		if (strObjTypes) strObjTypes += ":";
		strObjTypes += this.m_dbid + "." + this.m_objects[i].name;
	}
	var url = "/admin/xml_get_workflows.php?otypes="+strObjTypes;
	ajax.exec(url);
	
	tbl.print(dv);
}


CDataCenterDb.prototype.addWorkflowToList = function(id, name, act, obj_type, tbl)
{
	var rw = tbl.addRow();
	var pro_params = "width=765,height=600,toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";
	var opn_params = "toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";
	
	var del_dv = alib.dom.createElement("div");
	del_dv.m_rw = rw;
	del_dv.m_app = this;
	del_dv.m_wfid = id;
	del_dv.onclick = function()
	{
		this.m_app.deleteWorkflow(this.m_wfid, this.m_rw);
	}
	del_dv.innerHTML = "<img border='0' src='/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	rw.addCell(name);
	rw.addCell(obj_type);
	rw.addCell(act);
	btn = new CButton("open", function(cls, wfid, obj_type) { cls.openWorkflow(wfid, obj_type); }, [this, id, obj_type], "b2");
	rw.addCell(btn.getButton());
	rw.addCell(del_dv, true, "center");
}
CDataCenterDb.prototype.openWorkflow = function(id, obj_type, cbonsave, assoc)
{
	var params = 'width=800,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
	var onsave = (cbonsave) ? Base64.encode(cbonsave) : '';
	var url = '/admin/workflow/'+obj_type;
	if (id)
		url += '/'+id;

	if (assoc)
	{
		for (var i = 0; i < assoc.length; i++) 
		{
			url += "&" + assoc[i][0] + "=" + escape(assoc[i][1]);
		}
	}

	var cmp = window.open(url, 'edit_workflow', params);
}

CDataCenterDb.prototype.createWorkflow = function(oname, tbl)
{
	if (oname != "")
	{
		var fun = function(ret, oname, cls, tbl)
		{
			cls.addObjectToList(ret, oname, tbl);
		};

		/*
		ALib.m_debug = true;
		AJAX_TRACE_RESPONSE = true;
		*/
		var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "create_object", 
							   [["oname", oname], ["dbid", this.m_dbid]], fun, [oname, this, tbl]);
	}
}

CDataCenterDb.prototype.deleteWorkflow = function(wfid, row)
{
}

//========================================================================
//
//  Section: Reports tab
//                                                            
//========================================================================

/*************************************************************************
*	Function:	tabReports
*
*	Purpose:	Create contents of reports tab page
*
*	Arguments:	con - the body container that will hold contents
**************************************************************************/
CDataCenterDb.prototype.tabReports = function(con)
{
	con.innerHTML = "";

	var pro_params = "width=765,height=600,toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";

	var tb = new CToolbar();
	var btn = new CButton("New Graph", "window.open('/datacenter/report_graph_edit.awp?dbid="+this.m_dbid+"', 'new_graph', '"+pro_params+"');", null, "b2");
	tb.AddItem(btn.getButton(), "left");
	tb.print(con);

	// Add window frame
	var wf = new CWindowFrame("Reports and Graphs", null, "0px");
	wf.print(con);
	var dv = wf.getCon();

	// Add table
	var tbl = new CToolTable("100%");
	//tbl.addHeader("#", "center", "20px");
	tbl.addHeader("Name");
	tbl.addHeader("type", "center", "30px");
	tbl.addHeader("", "center", "75px");
	tbl.addHeader("Delete", "center", "50px");

	var ajax = new CAjax();
	ajax.m_tbl = tbl;
	ajax.m_app = this;
	ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var rname = "";
				var rid = "";
				var rtype = "";

				var child = root.getChildNode(i);
				if (child.m_name == "report")
				{
					var num_vars = child.getNumChildren();
					for (j = 0; j < num_vars; j++)
					{
						var dbattr = child.getChildNode(j);
						switch (dbattr.m_name)
						{
						case "name":
							rname = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						case "id":
							rid = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						case "type":
							rtype = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						}
					}
				}

				this.m_app.addReportToList(rid, rname, rtype);
			}
		}
	};

	var url = "/datacenter/xml_getreports.awp?dbid="+this.m_dbid;
	ajax.exec(url);
	
	tbl.print(dv);
	this.m_reportstbl = tbl;
}
CDataCenterDb.prototype.addReportToList = function(rid, rname, rtype)
{
	var me = this;
	var rw = this.m_reportstbl.addRow();
	var pro_params = "width=765,height=600,toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";
	var pro_act = "window.open('/datacenter/report_graph_edit.awp?dbid="+this.m_dbid+"&gid="+rid+"', 'gra"+rid+"', '"+pro_params+"');";
	var view_params = "width=560,height=420,toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes,scrollbars=yes";
	var view_act = 	"window.open('/datacenter/report_view_graph.awp?gid="+rid+"', 'viewgr"+rid+"', '"+view_params+"');";
	
	rw.addCell(rname);
	rw.addCell(rtype);
	//btn = new CButton("edit", pro_act);
	//rw.addCell(btn.getButton());
	//btn = new CButton("view", view_act, null, "b2");
	//rw.addCell(btn.getButton());

	var dm = new CDropdownMenu();
	dm.addEntry("View Graph", view_act);
	dm.addEntry("Edit Graph", pro_act);
	dm.addEntry("Add to Dashboard", "top.Ant.getPopupHandle(\"adc_open_db\").addReportToDash("+rid+");");
	var act = dm.createButtonMenu("Actions");
	rw.addCell(act);
	
	var del_dv = ALib.m_document.createElement("div");
	del_dv.m_rw = rw;
	del_dv.m_app = this;
	del_dv.m_rid = rid;
	del_dv.m_type = rtype;
	del_dv.onclick = function()
	{
		this.m_app.deleteReport(this.m_rid, this.m_rw, this.m_type);
	}
	del_dv.innerHTML = "<img border='0' src='/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	rw.addCell(del_dv, true, "center");
}
CDataCenterDb.prototype.addReportToDash = function(rid)
{
	var fun = function(uid)
	{
		alert("Report has been added to your dashboard!");
	};
	var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "dashboard_add_rpt_graph", [["rid", rid]], fun);
}

CDataCenterDb.prototype.deleteReport = function(rid, row, type)
{
	if (rid)
	{
		if (confirm("Are you sure you want to delete this "+type+"?"))
		{
			var fun = function(uid)
			{
				row.deleteRow();
			};
			var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "report_delete_"+type, [["rid", rid]], fun);
		}
	}
}

//========================================================================
//
//  Section: Cubes tab
//                                                            
//========================================================================

/*************************************************************************
*	Function:	tabCubes
*
*	Purpose:	Create contents of cubes tab page
*
*	Arguments:	con - the body container that will hold contents
**************************************************************************/
CDataCenterDb.prototype.tabCubes = function(con)
{
	var me = this;

	var tb = new CToolbar();
	btn = new CButton("Add Cube", function(){me.createTable();}, null, "b1");
	tb.AddItem(btn.getButton(), "right");
	var add_dv = Ant.m_document.createElement("div");
	this.m_txtNewCube = Ant.m_document.createElement("input");
	this.m_txtNewCube.type = "text";
	add_dv.appendChild(this.m_txtNewCube);
	tb.AddItem(add_dv, "right");
	tb.print(con);

	// Add window frame
	var wf = new CWindowFrame("Database Cubes", null, "0px");
	wf.print(con);
	var dv = wf.getCon();

	// Add table
	var tbl = new CToolTable("100%");
	//tbl.addHeader("#", "center", "20px");
	tbl.addHeader("Cube Name");
	tbl.addHeader("", "center", "30px");
	tbl.addHeader("", "center", "70px");
	tbl.addHeader("Delete", "center", "50px");

	this.m_ajax = new CAjax();
	this.m_ajax.m_tbl = tbl;
	this.m_ajax.m_app = this;
	this.m_ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var tname = "";
				var tid = "";

				var child = root.getChildNode(i);
				if (child.m_name == "table")
				{
					var num_vars = child.getNumChildren();
					for (j = 0; j < num_vars; j++)
					{
						var dbattr = child.getChildNode(j);
						switch (dbattr.m_name)
						{
						case "name":
							tname = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						case "id":
							tid = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						}
					}
				}

				this.m_app.addTableToList(tid, tname);
			}
		}
	};

	var url = "/datacenter/xml_gettables.awp?dbid="+this.m_dbid;
	this.m_ajax.exec(url);
	
	tbl.print(dv);
	this.m_tbls = tbl;
}

//========================================================================
//
//  Section: Users tab
//                                                            
//========================================================================

/*************************************************************************
*	Function:	tabUsers
*
*	Purpose:	Create contents of user tab page
*
*	Arguments:	con - the body container that will hold contents
**************************************************************************/
CDataCenterDb.prototype.tabUsers = function(con)
{
	var me = this;

	var tb = new CToolbar();
	btn = new CButton("Add User", function(){me.createUser();}, null, "b1");
	tb.AddItem(btn.getButton(), "right");
	var add_dv = Ant.m_document.createElement("div");
	this.m_txtNewUserName = Ant.m_document.createElement("input");
	this.m_txtNewUserName.type = "text";
	this.m_txtNewUserName.value = "username";
	alib.dom.styleSetClass(this.m_txtNewUserName, "CToolbarInputBlur");
	this.m_txtNewUserName.onfocus = function() { this.value = ""; alib.dom.styleSetClass(this, ""); }
	this.m_txtNewUserName.onblur = function()
	{ 
		if (this.value == "")
		{
			alib.dom.styleSetClass(this, "CToolbarInputBlur"); 
			this.value = "username"; 
		}
	}
	add_dv.appendChild(this.m_txtNewUserName);
	this.m_txtNewUserPass = Ant.m_document.createElement("input");
	this.m_txtNewUserPass.type = "text";
	this.m_txtNewUserPass.value = "password";
	alib.dom.styleSetClass(this.m_txtNewUserPass, "CToolbarInputBlur");
	this.m_txtNewUserPass.onfocus = function() { this.value = ""; alib.dom.styleSetClass(this, ""); }
	this.m_txtNewUserPass.onblur = function() 
	{ 
		if (this.value == "")
		{
			alib.dom.styleSetClass(this, "CToolbarInputBlur"); 
			this.value = "password";
		}
	}
	add_dv.appendChild(this.m_txtNewUserPass);
	tb.AddItem(add_dv, "right");
	tb.print(con);

	// Add window frame
	var wf = new CWindowFrame("Database Users", null, "0px");
	wf.print(con);
	var dv = wf.getCon();

	// Add table
	var tbl = new CToolTable("100%");
	tbl.addHeader("User Name");
	tbl.addHeader("", "center", "100px");
	tbl.addHeader("Delete", "center", "50px");

	var ajax = new CAjax();
	ajax.m_tbl = tbl;
	ajax.m_app = this;
	ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var uname = "";
				var uid = "";

				var child = root.getChildNode(i);
				if (child.m_name == "user")
				{
					var num_vars = child.getNumChildren();
					for (j = 0; j < num_vars; j++)
					{
						var dbattr = child.getChildNode(j);
						switch (dbattr.m_name)
						{
						case "name":
							uname = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						case "id":
							uid = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						}
					}
				}

				this.m_app.addUserToList(uid, uname);
			}
		}
	};

	var url = "/datacenter/xml_getusers.awp?dbid="+this.m_dbid;
	ajax.exec(url);
	
	tbl.print(dv);
	this.m_userstbl = tbl;
}

CDataCenterDb.prototype.addUserToList = function(uid, uname)
{
	var rw = this.m_userstbl.addRow();
	
	var del_dv = ALib.m_document.createElement("div");
	del_dv.m_rw = rw;
	del_dv.m_app = this;
	del_dv.m_uid = uid;
	del_dv.onclick = function()
	{
		this.m_app.deleteUser(this.m_uid, this.m_rw);
	}
	del_dv.innerHTML = "<img border='0' src='/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	rw.addCell(uname);
	var me = this;
	btn = new CButton("change password", function(){me.changeUserPassword(uid);});
	rw.addCell(btn.getButton());
	rw.addCell(del_dv, true, "center");
}

CDataCenterDb.prototype.createUser = function()
{
	var me = this;

	if (this.m_txtNewUserName.value != "" 
		&& this.m_txtNewUserName.value != "username"
		&& this.m_txtNewUserPass.value != "")
	{
		var uname = this.m_txtNewUserName.value;
		var upass = this.m_txtNewUserPass.value;

		var fun = function(uid)
		{
			me.addUserToList(uid, uname);	
			me.m_txtNewUserName.value = "username";
			me.m_txtNewUserPass.value = "password";
		};
		var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "create_user", 
							   [["name", uname], ["password", upass], ["dbid", this.m_dbid]], fun);
	}
}

CDataCenterDb.prototype.deleteUser = function(uid, row)
{
	if (uid)
	{
		if (confirm("Are you sure you want to delete this user?"))
		{
			var fun = function(uid)
			{
				row.deleteRow();
			};
			var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "delete_user", [["uid", uid]], fun);
		}
	}
}

CDataCenterDb.prototype.changeUserPassword = function(uid)
{
	if (uid)
	{
		var pass = prompt("Pleas enter a new password:", "password");
		if (pass)
		{
			var fun = function(uid)
			{
				alert("Password has been changed");
			};
			var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "change_user_password", [["uid", uid], ["password", pass]], fun);
		}
	}
}

//========================================================================
//
//  Section: Members tab
//                                                            
//========================================================================

/*************************************************************************
*	Function:	tabMembers
*
*	Purpose:	Create contehis.m_objectsnts of members tab page
*
*	Arguments:	con - the body container that will hold contents
**************************************************************************/
CDataCenterDb.prototype.tabMembers = function(con)
{
	var me = this;

	var tb = new CToolbar();
	btn = new CButton("Invite ANT User", function(){me.createUser();}, null, "b1");
	tb.AddItem(btn.getButton(), "right");
	var add_dv = Ant.m_document.createElement("div");
	this.m_txtNewMember = Ant.m_document.createElement("input");
	this.m_txtNewMember.type = "text";
	this.m_txtNewMember.value = "username";
	alib.dom.styleSetClass(this.m_txtNewMember, "CToolbarInputBlur");
	this.m_txtNewMember.onfocus = function() { this.value = ""; alib.dom.styleSetClass(this, ""); }
	this.m_txtNewMember.onblur = function()
	{ 
		if (this.value == "")
		{
			alib.dom.styleSetClass(this, "CToolbarInputBlur"); 
			this.value = "username"; 
		}
	}
	add_dv.appendChild(this.m_txtNewMember);
	tb.AddItem(add_dv, "right");
	tb.print(con);

	// Add window frame
	var wf = new CWindowFrame("ANT Users", null, "0px");
	wf.print(con);
	var dv = wf.getCon();

	// Add table
	var tbl = new CToolTable("100%");
	tbl.addHeader("User Name");
	tbl.addHeader("", "center", "100px");
	tbl.addHeader("Delete", "center", "50px");

	var ajax = new CAjax();
	ajax.m_tbl = tbl;
	ajax.m_app = this;
	ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var uname = "";
				var uid = "";

				var child = root.getChildNode(i);
				if (child.m_name == "user")
				{
					var num_vars = child.getNumChildren();
					for (j = 0; j < num_vars; j++)
					{
						var dbattr = child.getChildNode(j);
						switch (dbattr.m_name)
						{
						case "name":
							uname = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						case "id":
							uid = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
							break;
						}
					}
				}

				this.m_app.addUserToList(uid, uname);
			}
		}
	};

	var url = "/datacenter/xml_getusers.awp?dbid="+this.m_dbid;
	ajax.exec(url);
	
	tbl.print(dv);
	this.m_userstbl = tbl;
}

CDataCenterDb.prototype.addUserToList = function(uid, uname)
{
	var rw = this.m_userstbl.addRow();
	
	var del_dv = ALib.m_document.createElement("div");
	del_dv.m_rw = rw;
	del_dv.m_app = this;
	del_dv.m_uid = uid;
	del_dv.onclick = function()
	{
		this.m_app.deleteUser(this.m_uid, this.m_rw);
	}
	del_dv.innerHTML = "<img border='0' src='/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	rw.addCell(uname);
	var me = this;
	btn = new CButton("change password", function(){me.changeUserPassword(uid);});
	rw.addCell(btn.getButton());
	rw.addCell(del_dv, true, "center");
}

CDataCenterDb.prototype.createUser = function()
{
	var me = this;

	if (this.m_txtNewUserName.value != "" 
		&& this.m_txtNewUserName.value != "username"
		&& this.m_txtNewUserPass.value != "")
	{
		var uname = this.m_txtNewUserName.value;
		var upass = this.m_txtNewUserPass.value;

		var fun = function(uid)
		{
			me.addUserToList(uid, uname);	
			me.m_txtNewUserName.value = "username";
			me.m_txtNewUserPass.value = "password";
		};
		var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "create_user", 
							   [["name", uname], ["password", upass], ["dbid", this.m_dbid]], fun);
	}
}

CDataCenterDb.prototype.deleteUser = function(uid, row)
{
	if (uid)
	{
		if (confirm("Are you sure you want to delete this user?"))
		{
			var fun = function(uid)
			{
				row.deleteRow();
			};
			var rpc = new CAjaxRpc("/datacenter/xml_actions.awp", "delete_user", [["uid", uid]], fun);
		}
	}
}
