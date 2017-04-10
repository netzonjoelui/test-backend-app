/**
* @fileOverview AntAppSettings: Dynamic settings class for applications in ANT.
*
* Usage
* var settings = new AntAppSettings(&referenceToAntApp)
* settings.print(div);
*
* @author: joe, sky.stebnicki@aereus.com; Copyright (c) 2011 Aereus Corporation. All rights reserved.
* @constructor AntAppSettings
* @param {object} app reference to instance of AntApp
*/
function AntAppSettings(app)
{
	this.app = (app) ? app : null;
	this.nav_xml = "";						// Navigation xml
	this.default_type = "";					// Default Navigation item type
	this.navigation_items = new Array();	// Array of navigation items
    
    this.objTypes = null;
    this.mainCon = null;
    this.workflowCon = null;
	this.antView = null;

	/**
	 * Generic callback object for temp values
	 *
	 * @var {Object}
	 */
	this.cbData = new Object();
}

/**
* Callback is used when 'close' is clicked for this applet
*
* If this is defined the close button will be enabled
*/
// AntAppSettings.prototype.onclose = function() {}

/**
 * Load app definition by name and print
 *
 * @param {string} appName The application mame to load
 * @param {AntView} view The AntView that is managing this applet
 */
AntAppSettings.prototype.loadAndPrint = function(appName, view)
{
	this.antView = view;

	this.app = new AntApp(appName);
    this.app.appName = appName;
	this.app.view = view;
	this.app.cbData.applet = this;
	this.app.cbData.con = view.con;
	this.app.onload = function()
	{
		this.cbData.applet.print(this.cbData.con);
	}
	this.app.loadAppDef(true);
}

/**
* Run application and build interface
* @param {object} con  dom element container for settings
*/
AntAppSettings.prototype.print = function(con)
{
	/**
	* Add content Table
	*/    
    this.mainCon = con;
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "objectLoaderHeader";
    this.titleCon.innerHTML = this.app.title + " Settings";
    this.innerCon = alib.dom.createElement("div", this.mainCon);
    this.innerCon.className = "objectLoaderBody";

	/**
	* Add tabs
	*/
	var tabs = new CTabs();
	tabs.print(this.innerCon);

    // Load the object type
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        this.cbData.cls.objTypes = ret;
    };
    ajax.exec("/controller/Object/getObjects");
    
	var tabcon = tabs.addTab("General");
	this.buildGeneral(tabcon);
	this.workflowCon = tabs.addTab("Workflow");
	this.buildWorkflow(this.workflowCon);
	var tabcon = tabs.addTab("Navigation");
	this.buildLayout(tabcon);
}

/**
* Build tab elements for general settings
* @param {object} con  dom element of current tab
*/
AntAppSettings.prototype.buildGeneral = function(con)
{
	var formData = new Object();

	/**
	* Add main toolbar
	*/
	var toolbar = alib.dom.createElement("div", con);
	var tb = new CToolbar();

	if (this.onclose)
	{
		var close = alib.ui.Button("Close", {className:"b1", tooltip:"Close Settings", cls:this, onclick:function(){this.cls.onclose()}});
		tb.AddItem(close.getButton(), "left");
	}

	var btn = new CButton("Save Settings", 
	function(cls, form)
	{
		/**
		* Get form values
		*/
		var title = form.txtTitle.value;
		var short_title = form.txtShortTitle.value;
		var scope = form.cbScope.value;

		/**
		* Save values
		*/
		function cbfun(ret)
		{            
			if(!ret['error'])
				ALib.statusShowAlert("Settings Saved!", 3000, "bottom", "right");
			else			
				ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
		}
		
		var args = [["app", cls.app.name], ["title", title], ["short_title", short_title], ["scope", scope]];
        
        switch(scope)
        {
            case "user":
                if(cls.userDetails)
                    args[args.length] = ["userId", cls.userDetails.id];
                break;
            case "team":
                if(cls.teamDropdown)
                    args[args.length] = ["teamId", cls.teamDropdown.value];
                break;
        }
        
        ajax = new CAjax('json');        
        ajax.onload = function(ret)
        {
            if(!ret['error'])
                ALib.statusShowAlert("Settings Saved!", 3000, "bottom", "right");
            else            
                ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
        };
        ajax.exec("/controller/Application/saveGeneral", args);
	}, 
	[this, formData], "b2");
	tb.AddItem(btn.getButton(), "left");
	tb.print(toolbar);

	/**
	* General settings div
	*/
	var gsCon = alib.dom.createElement("div", con);
	var table = alib.dom.createElement("table", gsCon);
	table.setAttribute("cellpadding", 0);
	table.setAttribute("cellspacing", 0);
	alib.dom.styleSet(table, "width", "90%");
	var tbody = alib.dom.createElement("tbody", table);

	/**
	* Title
	*/
	var tr = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Title";
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formValue");
	formData.txtTitle = alib.dom.createElement("input");
	formData.txtTitle.type = "text";
	formData.txtTitle.value = this.app.title;
	alib.dom.styleSet(formData.txtTitle, "width", "300px");
	td.appendChild(formData.txtTitle);

	/**
	* Permissions
	*/
	var td = alib.dom.createElement("td", tr);
	td.colSpan = 2;
	td.innerHTML = "<img src='/images/icons/permissions_16.png' />&nbsp;&nbsp;<a href='javascript:void(0);' "
				 + "onclick=\"loadDacl(null, 'applications/"+this.app.name+"');\">Edit Application Permissions</a>";

	var tr = alib.dom.createElement("tr", tbody);

	/**
	* Short Title
	*/
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Short Title";
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formValue");
	formData.txtShortTitle = alib.dom.createElement("input");
	formData.txtShortTitle.type = "text";
	formData.txtShortTitle.value = this.app.shortTitle;
	alib.dom.styleSet(formData.txtShortTitle, "width", "300px");
	td.appendChild(formData.txtShortTitle);

	/**
	* Scope
	*/
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formLabel");
    alib.dom.styleSet(td, "verticalAlign", "top");
	td.innerHTML = "Publish to: ";
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formValue");    
    
    var scopeCon = alib.dom.createElement("div");
    alib.dom.styleSet(scopeCon, "width", "300px");
    
    // Scope Dropdown
    var divScopeDropdown = alib.dom.createElement("div", scopeCon);    
    alib.dom.styleSet(divScopeDropdown, "float", "left");
	formData.cbScope = alib.dom.createElement("select", divScopeDropdown);
    var scopeData = [["system", "Everyone"], ["user", "Specific User"], ["team", "A Team"], ["draft", "Nobody - Unpublish"]];
    buildDropdown(formData.cbScope, scopeData, this.app.scope);
	td.appendChild(scopeCon);
    
    // Scope User
    this.divScopeUser = alib.dom.createElement("div", scopeCon);
    alib.dom.styleSet(this.divScopeUser, "float", "left");
    alib.dom.styleSet(this.divScopeUser, "marginLeft", "10px");    
    this.divScopeUser.innerHTML = "<div class='loading'></div>";
    this.loadCurrentUser();
    
    // Scope Team
    this.divScopeTeam = alib.dom.createElement("div", scopeCon);
    alib.dom.styleSet(this.divScopeTeam, "float", "left");
    alib.dom.styleSet(this.divScopeTeam, "marginLeft", "10px");    
    this.divScopeTeam.innerHTML = "<div class='loading'></div>";
    this.teamDropdown();
    
    alib.dom.divClear(scopeCon);
    
    // Scope onchange event
    formData.cbScope.cls = this;    
    formData.cbScope.onchange = function()
    {
        this.cls.toggleScope(this.value);
    }
    
    this.toggleScope(this.app.scope);

	/**
	* Referenced objects
	*/
	var wf = new CWindowFrame("Referenced Objects");
	var roCon = wf.getCon();
	wf.print(con);

    var tableCon = alib.dom.createElement("element", roCon);
    var linkCon = alib.dom.createElement("element", roCon);
    
	var tbl = new CToolTable("100%");
	tbl.print(tableCon);
	tbl.addHeader("Name");
	//tbl.addHeader("Application");
	//tbl.addHeader("&nbsp;", "center", "100px");
	tbl.addHeader("&nbsp;", "center", "100px");
	tbl.addHeader("&nbsp;", "center", "100px");
	tbl.addHeader("Delete", "center", "50px");

    this.antObject = new CAntObjects();
    this.antObject.objCls = this;
    this.antObject.appName = this.app.name
    this.antObject.tblObject = tbl;
    this.antObject.fObjectReference = true;
    this.antObject.loadObjects(tableCon);
    
    // Setup call back function to display first the default referenced system objects
    this.antObject.onLoadObjects = function()
    {
        this.mapObject(this.objCls.app.refObjects);
    }
    
	
	/**
	* Add object actions
	*/
	var actDiv = alib.dom.createElement("div", linkCon);
	alib.dom.styleSet(actDiv, "padding", "5px");
	var a = alib.dom.createElement("a", actDiv);
	a.href = "javascript:void(0);";
	a.innerHTML = "Create New Object";
	a.cls = this;
    a.tbl = tbl;
	a.onclick= function()
	{
		this.cls.antObject.addNewObject();
	}
	
	var sp = alib.dom.createElement("span", actDiv);
	sp.innerHTML = " | ";
	
	var a = alib.dom.createElement("a", actDiv);
    a.href = "javascript:void(0);";
    a.innerHTML = "Reference Existing Object";
    a.cls = this;
    a.tbl = tbl;
    a.onclick = function()
    {
        var dlg_d = new CDialog("Reference Existing Object");
        var dlg_con = alib.dom.createElement("div");
        dlg_d.cls = this.cls;
        dlg_d.tbl = this.tbl;
        
        var table = alib.dom.createElement("table", dlg_con);
        var tableBody = alib.dom.createElement("tbody", table);
        var tr = alib.dom.createElement("tr", tableBody);
        var td = alib.dom.createElement("td", tr);
        var lbl = alib.dom.createElement("div", td);
        lbl.innerHTML = "<strong>Select an object: </strong>";
        lbl.style.padding = "3px";
        td.appendChild(lbl);
        var td = alib.dom.createElement("td", tr);
        var td_con = alib.dom.createElement("div", td);
        var sel_con = alib.dom.createElement("select");
        sel_con[sel_con.length] = new Option("Select", "");
        sel_con.cls = dlg_d.cls;
        sel_con.tbl = dlg_d.tbl;
        sel_con.onchange = function()
        {
            if(this.value != "")
            {
                var obj_ref = this.cls.checkObjectReference(this.value);
                
                /**
                * If Object Reference doesn't already exist
                */
                if(!obj_ref)
                {
                    var args = [["app", this.cls.app.name], ["obj_type", this.value]];                    
                    
                    ajax = new CAjax('json');
                    ajax.cls = this.cls;
                    ajax.tbl = this.tbl;
                    ajax.obj_type = this.value;
                    ajax.onload = function(ret)
                    {
                        if(!ret['error'])
                        {
                            dlg_d.hide();
                            /**
                            * Insert new object in Referenced Objects table
                            */
                            var newAntObj = new Object();
                            newAntObj.name = this.obj_type;
                            this.cls.antObject.listObject(newAntObj);
                        }
                        else
                        {
                            dlg_d.hide();
                            ALib.Dlg.messageBox("ERROR: Could not add object reference. Please try again.");
                        }    
                    };
                    ajax.exec("/controller/Application/addObjectReference", args);
                }
                /**
                * Object Reference already exists
                */
                else
                {
                    dlg_d.hide();
                    ALib.Dlg.messageBox("ERROR: Object Reference already exists!");
                }
            }
        }
        tr.appendChild(td);
        table.appendChild(tableBody);
        
        ajax = new CAjax('json');        
        ajax.con = sel_con;
        ajax.onload = function(ret)
        {
            for(object in ret)
                this.con[this.con.length] = new Option(ret[object].title, ret[object].name);
        };
        ajax.exec("/controller/Object/getObjects");
        
        var btn_con = alib.dom.createElement("div");
        alib.dom.styleSet(btn_con, "float", "right");
        var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
        btn_con.appendChild(btn.getButton());
        dlg_con.appendChild(btn_con);
        dlg_d.customDialog(dlg_con, 260, 60);
        td_con.appendChild(sel_con);
    }
    
    var sp = alib.dom.createElement("span", actDiv);
    sp.innerHTML = " | ";
    
    var a = alib.dom.createElement("a", actDiv);
	a.href = "javascript:void(0);";
	a.innerHTML = "Refresh";
	a.cls = this;
	a.tableCon = tableCon;
	a.onclick = function()
	{
		this.cls.antObject.loadObjects(this.tableCon);
    
        // Setup call back function to display first the default referenced system objects
        this.cls.antObject.onLoadObjects = function()
        {
            this.mapObject(this.objCls.app.refObjects);
        }
	}

	/**
	* Calendars
	*/
	var wf = new CWindowFrame("Application Calendars");
	var calCon = wf.getCon();
	wf.print(con);

	var tbl = new CToolTable("100%");
	tbl.print(calCon);
	tbl.addHeader("Name");
	tbl.addHeader("Delete", "center", "50px");

	for (var i = 0; i < this.app.refCalendars.length; i++)
	{
		var rw = tbl.addRow();

		rw.addCell(this.app.refCalendars[i].name);
		
		var del_dv = alib.dom.createElement("div");
		del_dv.m_rw = rw;
		del_dv.cls = this;
		del_dv.calid = this.app.refCalendars[i].id;
		del_dv.onclick = function()
		{
			this.cls.removeCalendar(this.calid, this.m_rw);
		}
		del_dv.innerHTML = "<img border='0' src='/images/icons/delete_10.png' />";
		alib.dom.styleSet(del_dv, "cursor", "pointer");
		rw.addCell(del_dv, false, "center");
		
		//rw.addCell("<img src='/images/icons/delete_10.png' />", false, "center");
	}

	/**
	* Add calendar action
	*/
	var actDiv = alib.dom.createElement("div", calCon);
	alib.dom.styleSet(actDiv, "padding", "5px");
	var a = alib.dom.createElement("a", actDiv);
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
            ajax = new CAjax('json');
            ajax.cls = this.cls;
            ajax.name = val;
            ajax.tbl = this.tbl;
            ajax.onload = function(ret)
            {
                if (!ret["error"])
                {
                    var rw = this.tbl.addRow();
                    rw.addCell(this.name);

                    var del_dv = alib.dom.createElement("div");
                    del_dv.m_rw = rw;
                    del_dv.cls = this.cls;
                    del_dv.calid = ret;
                    del_dv.onclick = function()
                    {
                        this.cls.removeCalendar(this.calid, this.m_rw);
                    }
                    del_dv.innerHTML = "<img border='0' src='/images/icons/delete_10.png' />";
                    alib.dom.styleSet(del_dv, "cursor", "pointer");
                    rw.addCell(del_dv, false, "center");
                }    
            };
            ajax.exec("/controller/Application/createCalendar", 
                        [["cal_name", val], ["app", this.cls.app.name]]);
            
		}
	}
	a.innerHTML = "Create Application Calendar";
}

/**
*Delete calendar from calendars and application_calendars
* @param {number} cal_id  Id of calendar to delete
* @param {number} row  Row in table to delete
*/
AntAppSettings.prototype.removeCalendar = function(cal_id, row)
{
	var dlg = new CDialog();
	dlg.cls = this;
	dlg.row = row;
	dlg.cal_id = cal_id;
	dlg.confirmBox("Are you sure you want to delete this calendar?", "Delete Calendar");
	dlg.onConfirmOk = function()
	{
		var args = [["app", this.cls.app.name], ["cal_id", this.cal_id]];
        
        ajax = new CAjax('json');        
        ajax.row = this.row;
        ajax.onload = function(ret)
        {
            if(!ret['error'])
            {    
                ALib.statusShowAlert("Calendar Deleted!", 3000, "bottom", "right");
                this.row.deleteRow();    // Delete row from table
            }
            else
            {
                ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
            }    
        };
        ajax.exec("/controller/Application/deleteCalendar", args);
	}
}

/**
*Add object to Referenced Objects table
* @param {object} tbl  Referenced Object table
* @param {object} obj_type object type to add
* @param {boolean} co = Customer Object 
*/
AntAppSettings.prototype.addReferencedObject = function(tbl, obj_type, co)
{
	/**
	* Add new Object reference to refObjects array
	*/
	var index = this.app.refObjects.length;
	if(co)
	{	var obj_name = "co_"+obj_type;
		this.app.refObjects[index] = { name:obj_name, title:obj_type, fSystem:false };
	}
	else
		this.app.refObjects[index] = { name:obj_type, title:obj_type, fSystem:false };

	var rw = tbl.addRow();
	rw.addCell(this.app.refObjects[index].title, false);
	//rw.addCell("system");

	/**
	* Import data
	*/
	var lnk = alib.dom.createElement("a");
	lnk.innerHTML = "[import data]";
	lnk.href = "javascript:void(0);";
	lnk.obj_type = this.app.refObjects[index].name;
	lnk.onclick = function() 
	{
		var wiz = new AntWizard("EntityImport", {obj_type:this.obj_type});
		wiz.show();
	}
	rw.addCell(lnk, false, "center");

	/**
	* Import data
	*/
	var lnk = alib.dom.createElement("a");
	lnk.innerHTML = "[edit permissions]";
	lnk.href = "javascript:void(0);";
	lnk.obj_type = this.app.refObjects[index].name;
	lnk.onclick = function() { loadDacl(null, '/objects/' + this.obj_type); }
	rw.addCell(lnk, false, "center");

	/**
	*Edit object
	*/
	var lnk = alib.dom.createElement("a");
	lnk.innerHTML = "[edit object]";
	lnk.href = "javascript:void(0);";
	lnk.obj_type = this.app.refObjects[index].name;
	lnk.onclick = function() 
	{
		var objedt_dlg = new Ant.EntityDefinitionEdit(this.obj_type);
		objedt_dlg.showDialog();
	}
	rw.addCell(lnk, false, "center");

	/**
	* Delete
	*/
	if (this.app.refObjects[index].fSystem)
		var delLnk = "&nbsp;";
	else
	{
		var delLnk = alib.dom.createElement("div");
		delLnk.cls = this;
		delLnk.row = rw;
		delLnk.obj_type = obj_type;
		delLnk.innerHTML = "<img src='/images/icons/delete_10.png' />";
		delLnk.onclick = function()
		{
			this.cls.deleteReferencedObject(this.row, this.cls.app.refObjects[index].name);
		}
	}
	rw.addCell(delLnk, false, "center");
}

/**
* Delete object in Referenced Objects table
* @param {object} 	row Row in table to delete
* @param {object} obj_type object type to add
*/
AntAppSettings.prototype.deleteReferencedObject = function(row, obj_type)
{	
	var dlg = new CDialog();
	dlg.cls = this;
	dlg.row = row;
	dlg.obj_type = obj_type;
	dlg.confirmBox("Are you sure you want to delete this referenced object?", "Delete Referenced Object");
	dlg.onConfirmOk = function()
	{
		var args = [["app", this.cls.app.name], ["obj_type", this.obj_type]];        
        
        ajax = new CAjax('json');        
        ajax.row = this.row;
        ajax.onload = function(ret)
        {
            if(!ret['error'])
            {    
                ALib.statusShowAlert("Object Deleted!", 3000, "bottom", "right");
                this.row.deleteRow();    // Delete row from table
            }
            else
            {
                ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
            }
        };
        ajax.exec("/controller/Application/deleteObjectReference", args);
	}
}

/**
* Check if Object Reference already exists
* @param {object} obj_type object type to add
*/
AntAppSettings.prototype.checkObjectReference = function(obj_type)
{
	var ret = false;	// return true if object reference already exists 
	
	for (var i = 0; i < this.app.refObjects.length; i++)
	{
		if(this.app.refObjects[i].name == obj_type)
			ret = true;
	}
	
	return ret;
}

/**
* Show all active workflows 
* @param {object} con dom element of current tab
*/
AntAppSettings.prototype.buildWorkflow = function(con)
{
	con.innerHTML = "";

	/**
	* Add table
	*/
	var tbl = new CToolTable("100%");
	//tbl.addHeader("#", "center", "20px");
	tbl.addHeader("Name");
	tbl.addHeader("Object Type", "center", "60px");
	tbl.addHeader("Active", "center", "30px");
	tbl.addHeader("", "center", "30px");
	tbl.addHeader("Delete", "center", "50px");

	/**
	* Add toolbar
	*/
	var tb = new CToolbar();

	if (this.onclose)
	{
		var close = alib.ui.Button("Close", {className:"b1", tooltip:"Close Settings", cls:this, onclick:function(){this.cls.onclose()}});
		tb.AddItem(close.getButton(), "left");
	}

	/**
	* New workflow
	*/
	var btn = new CButton("Create Workflow", function(cls) { cls.openWorkflow(); }, [this], "b2");
	tb.AddItem(btn.getButton());

	var btn = new CButton("Refresh", function(cls, con) { cls.buildWorkflow(con); }, [this, con]);
	tb.AddItem(btn.getButton());
	tb.print(con);

	var p = alib.dom.createElement("p", con);
	alib.dom.styleSetClass(p, "notice");
	p.innerHTML = "Use workflows to automate tasks based on any object. To get started, click \"Create Workflow\" above and select the object type you would like to automate tasks for.";

	/**
	* Add window frame
	*/
	var wf = new CWindowFrame("Automated Workflows");
	wf.print(con);
	var wfDiv = wf.getCon();
    wfDiv.innerHTML = "<div class='loading'></div>";
    
	var ajax = new CAjax("json");
	ajax.m_tbl = tbl;
    ajax.m_app = this;
	ajax.m_wfDiv = wfDiv;
	ajax.onload = function(ret)
	{
        this.m_wfDiv.innerHTML = "";
        this.m_tbl.print(this.m_wfDiv);
		if (ret.length)
		{
			for (workflow in ret)
			{
                var currentWorkflow = ret[workflow];
				var oname = "";
				var id = "";
				var act = "";
				var object_type = "";

                if(currentWorkflow.name)
				    oname = currentWorkflow.name;

                if(currentWorkflow.f_active)
                {
                    if(currentWorkflow.f_active=="t")
                        act = "yes";
                    else
                        act = "no";
                }				    
                
                if(currentWorkflow.object_type)
				    object_type = currentWorkflow.object_type;
                    
                if(currentWorkflow.id > 0)
                {
                    id = currentWorkflow.id;
                    this.m_app.addWorkflowToList(id, oname, act, object_type, this.m_tbl);
                }
			}
		}
	};

	var strObjTypes = "activity:comment";
	for (var i = 0; i < this.app.refObjects.length; i++)
		strObjTypes += ":"+this.app.refObjects[i].name;
    
    var args = new Array();
    args[args.length] = ['otypes', strObjTypes];
	ajax.exec("/controller/WorkFlow/getWorkflow", args);
}

/**
* Open a workflow window
* @param {number} id the id of the workflow to open. If null then create new.
* @param {number} id he id of the workflow to open. If null then create new.
* @param {string} obj_type the object name of obj_type
*/
AntAppSettings.prototype.openWorkflow = function(id, obj_type)
{
	// Edit existing workflow 
	if(id && obj_type)
	{
		var wf_wizard = new WorkflowWizard(obj_type, id);
        wf_wizard.g_objTypes = this.objTypes;
		wf_wizard.showDialog();
	}
	// Create new workflow
	else
	{
		var wf_wizard = new WorkflowWizard();
        wf_wizard.g_objTypes = this.objTypes;
		wf_wizard.showDialog();
	}
    
    wf_wizard.cls = this;
    wf_wizard.onsave = function()
    {        
        this.cls.buildWorkflow(this.cls.workflowCon);
    }    
}

/**
* Add a row to the workflows table
* @param {number} id  The id of the workflow
* @param {string} name The name/title of this workflow
* @param {boolean} name Is active - yes/no used
* @param {string}  obj_type The object name of obj_type
* @param {object} tbl Reference to workflows table
*/
AntAppSettings.prototype.addWorkflowToList = function(id, name, act, obj_type, tbl)
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
	del_dv.innerHTML = "<img border='0' src='/images/icons/delete_10.png' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	rw.addCell(name);
	rw.addCell(obj_type);
	rw.addCell(act);
	btn = new CButton("open", function(cls, wfid, obj_type) { cls.openWorkflow(wfid, obj_type); }, [this, id, obj_type], "b2");
	rw.addCell(btn.getButton());
	rw.addCell(del_dv, true, "center");
}

/**
* Remove a workflow
*/
AntAppSettings.prototype.deleteWorkflow = function(wfid, row)
{
	var dlg = new CDialog("Remove Workflow");
	dlg.rw = row;
	dlg.wfid = wfid;
	dlg.confirmBox("Are you sure you want to delete this workflow?", "Remove Workflow");
	dlg.onConfirmOk = function()
	{
        ajax = new CAjax('json');        
        ajax.cbData.rw = this.rw;
        ajax.onload = function(ret)
        {
            this.cbData.rw.deleteRow();
        };
        ajax.exec("/controller/WorkFlow/deleteWorkflow", [["wid", this.wfid]]);

	}
}

/**
* Build main layout modification form
* @param {object} con DOM element of current tab
*/
AntAppSettings.prototype.buildLayout = function(con)
{
    /**
    * Add main toolbar
    */
    var toolbar = alib.dom.createElement("div", con);
    var tb = new CToolbar();
    
	/**
	* Build main split container
	*/
    var splitCon = alib.dom.createElement("div", con);
	var appcon = new CSplitContainer();
	left_div = appcon.addPanel("330px");
	right_div = appcon.addPanel("*");
	
	if (this.onclose)
	{
		var close = alib.ui.Button("Close", {className:"b1", tooltip:"Close Settings", cls:this, onclick:function(){this.cls.onclose()}});
		tb.AddItem(close.getButton(), "left");
	}

	var btn = new CButton("Save Navigation", 
	                        function(cls, root)
	                        {
		                        cls.nav_xml = "";
		                        cls.saveLayout(root);		                        
	                        }, [this, left_div], "b2");	
    tb.AddItem(btn.getButton(), "left");
	
    var btn = new CButton("Select Default Item", 
	                        function(cls, root)
	                        {
		                        var dlg_d = new CDialog("Default Navigation Item");
		                        var dlg_con = alib.dom.createElement("div");
		                        
		                        var table = alib.dom.createElement("table", dlg_con);
		                        var tableBody = alib.dom.createElement("tbody", table);
		                        var tr = alib.dom.createElement("tr", tableBody);
		                        var td = alib.dom.createElement("td", tr);
		                        var lbl = alib.dom.createElement("div", td);
		                        lbl.innerHTML = "<strong>Select a default item: </strong>";
		                        lbl.style.padding = "3px";
		                        td.appendChild(lbl);
		                        var td = alib.dom.createElement("td", tr);
		                        var td_con = alib.dom.createElement("div", td);
		                        var sel_con = alib.dom.createElement("select");
		                        sel_con.cls = cls;
		                        sel_con[sel_con.length] = new Option("Select", "");
		                        /**
		                        * Used to reset default type
		                        */
		                        sel_con[sel_con.length] = new Option("Null", "");
		                        sel_con.onchange = function()
		                        {
			                        /**
			                        * Update default item type
			                        */
			                        dlg_d.hide();
			                        this.cls.default_type = this.value;
		                        }
		                        tr.appendChild(td);
		                        table.appendChild(tableBody);
		                        cls.addNavigationItems(sel_con);
		                        
		                        var btn_con = alib.dom.createElement("div");
		                        alib.dom.styleSet(btn_con, "float", "right");
		                        var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
		                        btn_con.appendChild(btn.getButton());
		                        dlg_con.appendChild(btn_con);
		                        dlg_d.customDialog(dlg_con, 260, 60);
		                        td_con.appendChild(sel_con);
	                        }, [this, left_div], "b1");		
    tb.AddItem(btn.getButton(), "left");
	
    var btn = new CButton("Reset to default", 
                            function(cls, splitCon)
                            {
                                ajax = new CAjax('json');
                                ajax.cbData.cls = cls;
                                ajax.onload = function(ret)
                                {
                                    if(!ret)
                                        ALib.statusShowAlert("Error Occured!", 3000, "bottom", "right");
                                        
                                    if(ret.error)
                                        ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
                                        
                                    if(ret)
                                    {
                                        var appcon = new CSplitContainer();
                                        left_div = appcon.addPanel("330px");
                                        right_div = appcon.addPanel("*");
                                        
                                        cls.buildLeftLayout(left_div, right_div);
                                        cls.buildRightLayout(right_div);
                                        
                                        splitCon.innerHTML = "";
                                        appcon.print(splitCon);
                                        
                                        alib.dom.styleSet(this.cbData.cls.resetDefault, "display", "none");
                                        ALib.statusShowAlert(this.cbData.cls.app.appName.capitalize() + " navigation is reset to default!", 3000, "bottom", "right");
                                    }
                                };
                                
                                var args = new Array();
                                args[args.length] = ['name', cls.app.appName];
                                args[args.length] = ['layout_xml', null];
                                ajax.exec("/controller/Application/saveLayout", args);
                            }, [this, splitCon], "b1");
    this.resetDefault = btn.getButton();
    tb.AddItem(this.resetDefault, "left");
    alib.dom.styleSet(this.resetDefault, "display", "none");
    
    tb.print(toolbar);
    
    
    
	/**
	* Append split container
	*/
	appcon.print(splitCon);
	
	/**
	* Build Left div
	*/
	var total_height = document.body.offsetHeight;
	left_div.style.height = total_height + "px";
	left_div.id = "nav_root";
	this.buildLeftLayout(left_div, right_div);
	
	/**
	* Build Right div
	*/
	this.buildRightLayout(right_div);
    
    this.checkDefault();
}

/**
* Build left navigation div under layout
* @param {object} con DOM element of left div in layout tab
* @param {string} right_div Navigation item details container
*/
AntAppSettings.prototype.buildLeftLayout = function(con, right_div)
{
	var nav_lbl = alib.dom.createElement("h2", con);
	nav_lbl.innerHTML = "Navigation";
	
	/**
	* Main left div, sortable container
	*/
	var left_div = alib.dom.createElement("div", con);
	DragAndDrop.registerDropzone(left_div, "main_dz");
	DragAndDrop.registerSortable(left_div);
	left_div.id = "left_div";
	
	/**
	* Get root node of navigation tree - see /customer/crm_def.php for an example
	*/
	var navXmlRoot = this.app.xmlDef.getChildNodeByName("navigation");
	
	/**
	* Build form by looping through navXmlRoot
	*/
	this.buildNavigation(left_div, right_div, navXmlRoot);
	
	/**
	* Add section button
	*/
	var button = alib.dom.createElement("div", con);
	alib.dom.styleSet(button, "text-align", "center");
	var btn = new CButton("+ Add Section", this.addSection, [this, left_div, right_div], "b1");
	button.appendChild(btn.getButton());
	button.id = "end_nav";
}

/**
* Build right navigation details div under layout
* @param {object} con DOM element of right div in layout tab
*/
AntAppSettings.prototype.buildRightLayout = function(con)
{
	var right_div = alib.dom.createElement("div", con);
	right_div.id = "right_div";
	
	/**
	* Default message under Navigation item details
	*/
	var main_lbl = alib.dom.createElement("p", right_div);
	alib.dom.styleSet(main_lbl, "margin", "0 0 0 5px");
	alib.dom.styleSetClass(main_lbl, "notice");
	main_lbl.innerHTML = "Select an item to the left to edit properties or click Add item to create a new item";
}


/**
* Build items in navigation
* @param {object} con DOM element of left div
* @param {object} right_div Navigation item details container
* @param {object} node Node of navigation tree to add 
*/
AntAppSettings.prototype.buildNavigation = function(con, right_div, node)
{	
	for(var i = 0; i < node.getNumChildren(); i++)
	{
		var child = node.getChildNode(i);			
		
		switch (child.m_name)
		{
			case "section":
				var section = alib.dom.createElement("div", con);
				DragAndDrop.registerDragableChild(null, section, null, "main_dz");
				alib.dom.styleSet(section, "margin", "5px 3px 5px 3px");
				section.style.border = "1px solid black";
				section.style.cursor = "move";
				section.title = unescape(child.getAttribute("title"));
				section.nav_type = "section";
				
				/**
				* Section Title
				*/
				var title = alib.dom.createElement("div", section);
				alib.dom.styleSet(title, "margin", "3px 3px 3px 3px");
				var sec_lbl = alib.dom.createElement("div");
				alib.dom.styleSet(sec_lbl, "margin", "0 100px 0 0");
				sec_lbl.innerHTML = "<strong>" + section.title + "</strong>";
				var sec_rename = alib.dom.createElement("div");
				alib.dom.styleSet(sec_rename, "margin", "0 3px 0 0");
				alib.dom.styleSet(sec_rename, "float", "right");
				var btn = new CButton("Rename", this.renameSection, [sec_lbl], "b1", null, null, null, 'link');
				sec_rename.appendChild(btn.getButton());
				var sec_delete = alib.dom.createElement("div");
				alib.dom.styleSet(sec_delete, "float", "right");
				var btn = new CButton("X", this.deleteItem, [section, right_div], "b3", null, null, null, 'link');
				sec_delete.appendChild(btn.getButton());
				title.appendChild(sec_delete);
				title.appendChild(sec_rename);
				title.appendChild(sec_lbl);
				
				var space = alib.dom.createElement("div", section);
				space.style.height = "5px";
				
				/**
				* Container for items
				*/
				var items = alib.dom.createElement("div", section);
				DragAndDrop.registerDropzone(items, "section_dz");
				DragAndDrop.registerSortable(items);
				
				/**
				* Add items
				*/
				this.buildNavigation(items, right_div, child);
				
				/**
				* Link to add item/link to section
				*/
				var link = alib.dom.createElement("a", section);
				link.href = 'javascript:void(0);'
				link.innerHTML = "<center>" + "Add item/link to section" + "</center>";
				link.items = items;
				link.ref = this;
				link.onclick = function() 
				{
                    alib.dom.styleSet(this.ref.resetDefault, "display", "block");
					this.ref.addItem(this.items, right_div);
				}
				break;
			case "item":
				var item = alib.dom.createElement("div", con);
				DragAndDrop.registerDragableChild(con.parentNode, item, null, "section_dz");
				alib.dom.styleSet(item, "margin", "5px 3px 5px 3px");
				item.style.border = "1px solid black";
				item.style.cursor = "move";
				item.nav_type = "item";

				/**
				* Set item attributes
				*/
				item.name = child.getAttribute("name");
				item.type = child.getAttribute("type");
				if(child.getAttribute("title") != "")
					item.title = unescape(child.getAttribute("title"));
				else
					item.title = unescape(child.getAttribute("name"));
				if(item.type == "calendar")
					item.calendar_id = child.getAttribute("id");
				if(child.getAttribute("icon") != "")
					item.icon = unescape(child.getAttribute("icon"));
				if(child.getAttribute("tooltip") != "")
					item.tooltip = unescape(child.getAttribute("tooltip"));
				if(item.type == "object" || item.type == "browse")
					item.obj_type = child.getAttribute("obj_type");
				if(item.type == "browse" && child.getAttribute("browseby") != "")
					item.browseby = child.getAttribute("browseby");
				if(item.type == "browse" && child.getAttribute("folder_id") != "")
					item.folder_id = child.getAttribute("folder_id");                
				if(item.type == "link" && child.getAttribute("url") != "")
					item.url = unescape(child.getAttribute("url"));
				
				/**
				* Item Title
				*/
				var title = alib.dom.createElement("div", item);
				alib.dom.styleSet(title, "margin", "3px 3px 3px 3px");
				var item_lbl = alib.dom.createElement("div");
				alib.dom.styleSet(item_lbl, "margin", "0 100px 0 0");
				item_lbl.innerHTML = item.title;
				var item_options = alib.dom.createElement("div");
				alib.dom.styleSet(item_options, "margin", "0 3px 0 0");
				alib.dom.styleSet(item_options, "float", "right");
				var btn = new CButton("Options", this.buildItemDetails, [this, right_div, item], "b1", null, null, null, 'link');
				item_options.appendChild(btn.getButton());
				var item_delete = alib.dom.createElement("div");
				alib.dom.styleSet(item_delete, "float", "right");
				var btn = new CButton("X", 
				function(cls, item, con) 
				{ 
					cls.deleteItem(item, con);  
					cls.deleteNavigationItem(item.name, item.type); 
				}, [this, item, right_div], "b3", null, null, null, 'link');
				item_delete.appendChild(btn.getButton());
				title.appendChild(item_delete);
				title.appendChild(item_options);
				title.appendChild(item_lbl);
				
				var space = alib.dom.createElement("div", item);
				space.style.height = "5px";
				
				/**
				* Add item to navigation_items array
				*/
				this.navigation_items[this.navigation_items.length] = {title:item.title, name:item.name, type:item.type};
				
				/**
				* Add items
				*/
				this.buildNavigation(item, right_div, child);
				break;
			case "filter":
				var filter = alib.dom.createElement("div", con);
				filter.style.display = "none";
				filter.nav_type = "filter";
				
				/**
				* Create new condition array
				*/
				var conditions = new Array();
				filter.conditions = conditions;
				
				this.buildNavigation(filter, right_div, child);
				break;
			case "condition":
				var condition = alib.dom.createElement("div", con);
				condition.style.display = "none";
				condition.nav_type = "condition";

				/**
				* Create new condition object
				*/
				var cond = new Object();
				cond.blogic = child.getAttribute("blogic");
				cond.field = child.getAttribute("field");
				cond.operator = child.getAttribute("operator");
				cond.value = child.getAttribute("value");

				/**
				* Add condition object to condition array
				*/
				con.conditions[con.conditions.length] = cond;
				
				this.buildNavigation(condition, right_div, child);
				break;
		}
	}
}

/**
* Build item details for selected item
* @param {object} cls  This class 
* @param {object} con  Navigation item details container
* @param {object} item = Container of item to set details on
*/
AntAppSettings.prototype.buildItemDetails = function(cls, con, item)
{
	/**
	* Right div
	*/    
    if(!cls.objTypes)
    {
        con.innerHTML = "<div class='loading'></div>";
        var functCls = cls;
        var callback = function()
        {            
            functCls.buildItemDetails(cls, con, item);
        }
        
        window.setTimeout(callback, 1000);
        return;
    }
    
    
    
	con.innerHTML = "";
	
	/**
	* Navigation Item Details
	*/
	var title_lbl = alib.dom.createElement("h2", con);
	title_lbl.innerHTML = "Navigation Item Details";
	var table = alib.dom.createElement("table", con);
	var tableBody = alib.dom.createElement("tbody", table);
	
	/**
	* Item Title/Label
	*/
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	var title_dv = alib.dom.createElement("div", con);
	title_dv.innerHTML = "<strong>Title/Label </strong>";
	title_dv.style.padding = "3px";
	td.appendChild(title_dv);
	var td = alib.dom.createElement("td", tr);
	var inp1 = alib.dom.createElement("input", td);
	inp1.setAttribute('maxLength', 128);
	inp1.type = "text";
	inp1.style.width = "160px";
	inp1.value = item.title;
	inp1.onchange = function() 
	{

		/**
		* Set new title and name
		*/
		if(inp1.value != "")
		{
			var old_name = item.name;
			
			item.title = inp1.value;
			item.childNodes[0].childNodes[2].innerHTML = inp1.value;
			var name = inp1.value.toLowerCase();
			name = name.replace(/ /g, "_");
			name = name.replace(/[^a-zA-Z0-9_]+/g,'');
			item.name = name;
			sys_name.innerHTML = name;
			
			/**
			* Update item name and title in navigation_items array
			*/
			cls.updateNavigationItemName(old_name, item.name, item.title);
		}
	}
	td.appendChild(inp1);
	tr.appendChild(td);
	

	/**
	* Item System Name
	*/
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	var name_dv = alib.dom.createElement("div", con);
	name_dv.innerHTML = "<strong>System Name </strong>";
	name_dv.style.padding = "3px";
	td.appendChild(name_dv);
	var td = alib.dom.createElement("td", tr);
	var sys_name = alib.dom.createElement("div", td);
	sys_name.innerHTML = item.name;
	td.appendChild(sys_name);
	tr.appendChild(td);
	
	/**
	* Item Icon
	*/
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	var icon_dv = alib.dom.createElement("div", con);
	icon_dv.innerHTML = "<strong>Icon </strong>";
	icon_dv.style.padding = "3px";
	td.appendChild(icon_dv);
	var td = alib.dom.createElement("td", tr);
	var inp2 = alib.dom.createElement("input", td);
	inp2.setAttribute('maxLength', 128);
	inp2.type = "text";
	inp2.style.width = "160px";
	if(item.icon != null && item.icon != "")
		inp2.value = item.icon;
	else
		inp2.value = "";
	inp2.onchange = function() 
	{
		item.icon = inp2.value;
	}
	td.appendChild(inp2);
	tr.appendChild(td);
	
	/**
	* Item Tooltip
	*/
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	var tooltip_dv = alib.dom.createElement("div", con);
	tooltip_dv.innerHTML = "<strong>Tooltip </strong>";
	tooltip_dv.style.padding = "3px";
	td.appendChild(tooltip_dv);
	var td = alib.dom.createElement("td", tr);
	var inp3 = alib.dom.createElement("input", td);
	inp3.setAttribute('maxLength', 128);
	inp3.type = "text";
	inp3.style.width = "160px";
	if(item.tooltip != null && item.tooltip != "")
		inp3.value = item.tooltip;
	else
		inp3.value = "";
	inp3.onchange = function() 
	{
		item.tooltip = inp3.value;
	}
	td.appendChild(inp3);
	tr.appendChild(td);
	
	/**
	* Item Type
	*/
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	var type_dv = alib.dom.createElement("div", con);
	type_dv.innerHTML = "<strong>Type </strong>";
	type_dv.style.padding = "3px";
	td.appendChild(type_dv);
	var td = alib.dom.createElement("td", tr);	
	var itm_type = alib.dom.createElement("select", td);
	itm_type.onchange = function() 
	{
		/**
		* Update item type in navigation_items
		*/
		cls.updateNavigationItemType(item.name, item.type, this.value)
		
		/**
		* Set new item type
		*/
		item.type = this.value;
		cls.buildItemDetails(cls, right_div, item);
	}
	itm_type[itm_type.length] = new Option("Browse", "browse");
	itm_type[itm_type.length] = new Option("Calendar", "calendar");
	itm_type[itm_type.length] = new Option("Folder", "folder");
	itm_type[itm_type.length] = new Option("Link", "link");
	itm_type[itm_type.length] = new Option("My Mini Calendar", "myminical");
	itm_type[itm_type.length] = new Option("My Calendars", "mycalendars");
	itm_type[itm_type.length] = new Option("My Other Calendars", "myothercals");
	itm_type[itm_type.length] = new Option("Object", "object");
	itm_type[itm_type.length] = new Option("Settings", "settings");
	itm_type.value = item.type;
	td.appendChild(itm_type);
	tr.appendChild(td);
	
	if(item.type == "browse" || item.type == "object")
	{
		var additional = alib.dom.createElement("div", con);
		
		/**
		* Object type
		*/
		var tr = alib.dom.createElement("tr", tableBody);
		var td = alib.dom.createElement("td", tr);
		var objt_dv = alib.dom.createElement("div", additional);
		objt_dv.innerHTML = "<strong>Object Type </strong>";
		objt_dv.style.padding = "3px";
		td.appendChild(objt_dv);
		var td = alib.dom.createElement("td", tr);
		var itm_objt = alib.dom.createElement("select", td);
		itm_objt.onchange = function()
		{
			item.obj_type = this.value;
			cls.buildItemDetails(cls, right_div, item);
		}
		
        if(cls.objTypes)
        {            
            cls.addObjectTypes(itm_objt, item.obj_type);
        }
        else
        {
            ajax = new CAjax('json');        
            ajax.onload = function(ret)
            {
                if(ret)
                {
                    cls.objTypes = ret;
                    cls.addObjectTypes(itm_objt, item.obj_type);
                    itm_objt.value = item.obj_type;
                }
            };
            ajax.exec("/controller/Object/getObjects");
        }
        
		td.appendChild(itm_objt);
		tr.appendChild(td);
		
		if(item.type == "browse")
		{            
			var g_antObject = new CAntObject(item.obj_type, null);
		
			/**
			* Browse By Field
			*/
			var tr = alib.dom.createElement("tr", tableBody);
			var td = alib.dom.createElement("td", tr);
			var browse_dv = alib.dom.createElement("div", additional);
			browse_dv.innerHTML = "<strong>Browse By Field </strong>";
			browse_dv.style.padding = "3px";
			td.appendChild(browse_dv);
			var td = alib.dom.createElement("td", tr);
			var field_dd = alib.dom.createElement("select", td);
			field_dd[field_dd.length] = new Option("Select Field", "");
			for (var i = 0; i < g_antObject.fields.length; i++)
			{
				var field = g_antObject.fields[i];
                var selected = false;
				
                if(field.name == item.browseby)
                    selected = true;
                
				if(field.type == "fkey" || field.type == "fkey_multi")
					field_dd[field_dd.length] = new Option(field.title, field.name, selected, selected);
			}
			field_dd.onchange = function()
			{
				if(field_dd.value != "")
					item.browseby = field_dd.value;
			}

			/**
			* Set initial value if browseby is set
			*/
			if(item.browseby != null)
				field_dd.value = item.browseby;
			td.appendChild(field_dd);
			tr.appendChild(td);
			
			/**
			* Filter Browse Results
			*/
			var filter_dv = alib.dom.createElement("div", additional);
			alib.dom.styleSet(filter_dv, "margin", "5px 5px 5px 5px");
			filter_dv.innerHTML = "<strong>Filter Browse Results</strong>";
			
			/**
			* Add Condition
			*/
			var cond_dv = alib.dom.createElement("div", additional);
			alib.dom.styleSet(cond_dv, "margin", "5px 5px 5px 5px");
			var obj = new CAntObject(item.obj_type, null);
			item.conditions = obj.buildAdvancedQuery(cond_dv);
		}
	}
	
	if(item.type == "link")
	{
		var link_div = alib.dom.createElement("div", con);
		
		/**
		* Item Link
		*/
		var tr = alib.dom.createElement("tr", tableBody);
		var td = alib.dom.createElement("td", tr);
		var lk_dv = alib.dom.createElement("div", con);
		lk_dv.innerHTML = "<strong>Link </strong>";
		lk_dv.style.padding = "3px";
		td.appendChild(lk_dv);
		var td = alib.dom.createElement("td", tr);
		var inp4 = alib.dom.createElement("input", td);
		inp4.setAttribute('maxLength', 128);
		inp4.type = "text";
		inp4.style.width = "160px";
		if(item.url != null && item.url != "")
			inp4.value = item.url;
		else
			inp4.value = "http://";
		inp4.onchange = function() 
		{
			if(inp4.value != "" || inp4.value != "http://")
				item.url = inp4.value;
		}
		td.appendChild(inp4);
		tr.appendChild(td);
	}
	
	if(item.type == "folder")
	{
		var folder_div = alib.dom.createElement("div", con);
		
		/**
		* Select Folder
		*/
		var tr = alib.dom.createElement("tr", tableBody);
		var td = alib.dom.createElement("td", tr);
		var fd_dv = alib.dom.createElement("div", con);
		fd_dv.innerHTML = "<strong>Folder </strong>";
		fd_dv.style.padding = "3px";
		td.appendChild(fd_dv);
		var td = alib.dom.createElement("td", tr);
		var btn = new CButton("Select Folder", cls.selectFolder, [item], "b1");
		td.appendChild(btn.getButton());
		tr.appendChild(td);
	}
	
	if(item.type == "calendar")
	{
		/**
		* Select Calendar Id
		*/
		var tr = alib.dom.createElement("tr", tableBody);
		var td = alib.dom.createElement("td", tr);
		var cal_dv = alib.dom.createElement("div");
		cal_dv.innerHTML = "<strong>Calendar </strong>";
		cal_dv.style.padding = "3px";
		td.appendChild(cal_dv);
		
		/**
		* Select Calendar Dropdown
		*/
		var td = alib.dom.createElement("td", tr);
		var cal_dd = alib.dom.createElement("select", td);
		cal_dd.app = cls.app;
		cal_dd[cal_dd.length] = new Option("Select Calendar", "");
		for (var i = 0; i < cls.app.refCalendars.length; i++)
		{
			cal_dd[cal_dd.length] = new Option(cal_dd.app.refCalendars[i].name, cal_dd.app.refCalendars[i].id);
		}
		cal_dd.onchange = function()
		{
			if(cal_dd.value != "")
				item.calendar_id = cal_dd.value;
		}
		/**
		* Set initial value if calendar_id is set
		*/
		if(item.calendar_id != null)
			cal_dd.value = item.calendar_id;
		td.appendChild(cal_dd);
		tr.appendChild(td);
	}
	table.appendChild(tableBody);
}


/**
* Add navogation items to dropdown menu
* @param {object} dropdown  Dropdown menu to add items to
*/
AntAppSettings.prototype.addNavigationItems = function(dropdown)
{
	for(var i = 0; i < this.navigation_items.length; i++)
	{
		var type = this.navigation_items[i].type;
		var item = this.navigation_items[i].title;
		
		/**
		* Check for item.type == myminical, mycalendars, myothercals
		*/
		if(type == "myminical" || type == "mycalendars" || type == "myothercals")
			var val = "mycalendar";
		else
			var val = this.navigation_items[i].name;
		dropdown[dropdown.length] = new Option(item, val);
	}
}


/**
* Delete navigation item from navigation_items array
* @param {string} item_name Navigation item name to delete
* @param {string} item_type Navigation item type to delete
*/
AntAppSettings.prototype.deleteNavigationItem = function(item_name, item_type)
{
	for(var i = 0; i < this.navigation_items.length; i++)
	{
		/**
		* Delete item from navigation_items (removes first match found)
		*/
		if(this.navigation_items[i].name == item_name && this.navigation_items[i].type == item_type)
		{
			this.navigation_items.splice(i, 1);
			break;
		}
	}
}

/**
* Update item name and title in navigation_items array
* @param {string} old_name Old item name
* @param {string} new_name New item name
* @param {string} new_title New item title
*/
AntAppSettings.prototype.updateNavigationItemName = function(old_name, new_name, new_title)
{
	for(var i = 0; i < this.navigation_items.length; i++)
	{

		/**
		* Update item name and title (replaces first match found)
		*/
		if(this.navigation_items[i].name == old_name)
		{
			this.navigation_items[i].name = new_name;
			this.navigation_items[i].title = new_title;
			break;
		}
	}
}

/**
* Update item type in navigation_items array
* @param {string} item_name Item name
* @param {string} old_type Old item type
* @param {string} new_type New item type
*/
AntAppSettings.prototype.updateNavigationItemType = function(item_name, old_type, new_type)
{
	for(var i = 0; i < this.navigation_items.length; i++)
	{
		/**
		* Update item type (replaces first match found)
		*/
		if(this.navigation_items[i].name == item_name && this.navigation_items[i].type == old_type)
		{
			this.navigation_items[i].type = new_type;
			break;
		}
	}
}

/**
* Add section to navigation
* @param {object} cls This class
* @param {object} con Container to append new section to
* @param {object} right_div Navigation item details container
*/
AntAppSettings.prototype.addSection = function(cls, con, right_div)
{
	var dlg_d = new CDialog("New Section");
	var cont = alib.dom.createElement("div");
	var table = alib.dom.createElement("table", cont);
	var tableBody = alib.dom.createElement("tbody", table);
	
	/**
	* Add New Section
	*/	
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	var inp_dv = alib.dom.createElement("div", cont);
	inp_dv.innerHTML = "<strong>Title: </strong>";
	inp_dv.style.padding = "3px";
	td.appendChild(inp_dv);
	var td = alib.dom.createElement("td", tr);
	var inp1 = alib.dom.createElement("input", td);
	inp1.setAttribute('maxLength', 128);
	inp1.type = "text";
	inp1.style.width = "160px";
	inp1.value = "";
	td.appendChild(inp1);
	tr.appendChild(td);
	table.appendChild(tableBody);
	
	var btn_con = alib.dom.createElement("div");
	alib.dom.styleSet(btn_con, "float", "right");
	var btn = new CButton("Ok", 
	function(cls) 
	{
		if(inp1.value != "")
		{
			cls.appendSection(inp1.value, con, right_div);
		}
		dlg_d.hide(); 
	}, [cls], "b1");
	btn_con.appendChild(btn.getButton());
	var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
	btn_con.appendChild(btn.getButton());
	cont.appendChild(btn_con);
	dlg_d.customDialog(cont, 238, 48);
}


/**
* Append new section to container
* @param {string} sec_title Title of new section
* @param {object} con Container to append new section to
* @param {object} right_div Navigation item details container
*/
AntAppSettings.prototype.appendSection = function(sec_title, con, right_div)
{
	var section = alib.dom.createElement("div", con);
	DragAndDrop.registerDragableChild(null, section, null, "main_dz");
	alib.dom.styleSet(section, "margin", "5px 3px 5px 3px");
	section.style.border = "1px solid black";
	section.style.cursor = "move";
	section.title = sec_title;
	section.nav_type = "section";
	
	/**
	* Section Title
	*/	
	var title = alib.dom.createElement("div", section);
	alib.dom.styleSet(title, "margin", "3px 3px 3px 3px");
	var sec_lbl = alib.dom.createElement("div");
	sec_lbl.title = sec_title;
	alib.dom.styleSet(sec_lbl, "margin", "0 100px 0 0");
	sec_lbl.innerHTML = "<strong>" + sec_title + "</strong>";
	var sec_rename = alib.dom.createElement("div");
	alib.dom.styleSet(sec_rename, "margin", "0 3px 0 0");
	alib.dom.styleSet(sec_rename, "float", "right");
	var btn = new CButton("Rename", this.renameSection, [sec_lbl], "b1", null, null, null, 'link');
	sec_rename.appendChild(btn.getButton());
	var sec_delete = alib.dom.createElement("div");
	alib.dom.styleSet(sec_delete, "float", "right");
	var btn = new CButton("X", this.deleteItem, [section, right_div], "b3", null, null, null, 'link');
	sec_delete.appendChild(btn.getButton());
	title.appendChild(sec_delete);
	title.appendChild(sec_rename);
	title.appendChild(sec_lbl);
	
	var space = alib.dom.createElement("div", section);
	space.style.height = "5px";
	
	/**
	* Container for items
	*/	
	var items = alib.dom.createElement("div", section);
	DragAndDrop.registerDropzone(items, "section_dz");
	DragAndDrop.registerSortable(items);
				
	/**
	* Link to add item/link to section
	*/	
	var link = alib.dom.createElement("a", section);
	link.href = 'javascript:void(0);'
	link.innerHTML = "<center>" + "Add item/link to section" + "</center>";
	link.items = items;
	link.ref = this;
	link.onclick = function() 
	{
        alib.dom.styleSet(this.ref.resetDefault, "display", "block");
		this.ref.addItem(this.items, right_div);
	}
}

/**
* Add section to navigation
* @param {string} con Div to set title
*/
AntAppSettings.prototype.renameSection = function(con)
{
	var dlg_d = new CDialog("Rename Section");
	var cont = alib.dom.createElement("div");
	var table = alib.dom.createElement("table", cont);
	var tableBody = alib.dom.createElement("tbody", table);
	
	/**
	* Section Title
	*/	
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	var inp_dv = alib.dom.createElement("div", cont);
	inp_dv.innerHTML = "<strong>Title: </strong>";
	inp_dv.style.padding = "3px";
	td.appendChild(inp_dv);
	var td = alib.dom.createElement("td", tr);
	var inp1 = alib.dom.createElement("input", td);
	inp1.setAttribute('maxLength', 128);
	inp1.type = "text";
	inp1.style.width = "160px";
	inp1.value = con.parentNode.parentNode.title;
	td.appendChild(inp1);
	tr.appendChild(td);
	table.appendChild(tableBody);
	
	var btn_con = alib.dom.createElement("div");
	alib.dom.styleSet(btn_con, "float", "right");
	var btn = new CButton("Ok", 
	function() 
	{ 
		if(inp1.value != "")
		{
			con.parentNode.parentNode.title = inp1.value;
			con.innerHTML = "<strong>" + inp1.value + "</strong>";
		}
		dlg_d.hide(); 
	}, null, "b1");
	btn_con.appendChild(btn.getButton());
	var btn = new CButton("Cancel", function(){  dlg_d.hide(); }, null, "b1");
	btn_con.appendChild(btn.getButton());
	cont.appendChild(btn_con);
	dlg_d.customDialog(cont, 238, 48);
}

/**
* Add object types to dropdown menu
* @param {object} objTypes Array of objects to add to dropdown
* @param {object} dropdown Dropdown menu to add items to
*/
AntAppSettings.prototype.addObjectTypes = function(dropdown, itemObjType)
{
    
    for(type in this.objTypes)
    {
        var currentType = this.objTypes[type];
        var selected = false;
        
        if(currentType.name == itemObjType)
            selected = true;
        
        dropdown[dropdown.length] = new Option(currentType.fullTitle, currentType.name, selected);
    }
    
	/*for(var i = 0; i < types.length; i++)
	{
		dropdown[dropdown.length] = new Option(types[i][1], types[i][0]);
	}*/
}

/**
* Add default item to section
* @param {object} con Container to append item to
* @param {object} right_div Navigation item details container
*/
AntAppSettings.prototype.addItem = function(con, right_div)
{
	this.navigation_items[this.navigation_items.length] = {title:"New Item", name:"new_item", type:"link"};

	var item = alib.dom.createElement("div", con);
	DragAndDrop.registerDragableChild(con.parentNode, item, null, "section_dz");
	alib.dom.styleSet(item, "margin", "5px 3px 5px 3px");
	item.style.border = "1px solid black";
	item.style.cursor = "move";
	item.nav_type = "item";
	
	/**
	* Set item attributes
	*/	
	item.name = "new_item";
	item.type = "link";
	item.title = "New Item";
	item.url = "http://www.aereus.com";
	
	/**
	* Item Title
	*/	
	var title = alib.dom.createElement("div", item);
	alib.dom.styleSet(title, "margin", "3px 3px 3px 3px");
	var item_lbl = alib.dom.createElement("div");
	alib.dom.styleSet(item_lbl, "margin", "0 100px 0 0");
	item_lbl.innerHTML = item.title;
	var item_options = alib.dom.createElement("div");
	alib.dom.styleSet(item_options, "margin", "0 3px 0 0");
	alib.dom.styleSet(item_options, "float", "right");
	var btn = new CButton("Options", this.buildItemDetails, [this, right_div, item], "b1", null, null, null, 'link');
	item_options.appendChild(btn.getButton());
	var item_delete = alib.dom.createElement("div");
	alib.dom.styleSet(item_delete, "float", "right");
	var btn = new CButton("X", 
	function(cls, item, con) 
	{ 
		cls.deleteItem(item, con);  
		cls.deleteNavigationItem(item.name, item.type); 
	}, [this, item, right_div], "b3", null, null, null, 'link');
	item_delete.appendChild(btn.getButton());
	title.appendChild(item_delete);
	title.appendChild(item_options);
	title.appendChild(item_lbl);
	
	var space = alib.dom.createElement("div", item);
	space.style.height = "5px";
}

/**
* Delete item in navigation
* @param {object} con Element to delete
* @param {object} right_div Navigation item details container
*/
AntAppSettings.prototype.deleteItem = function(con, right_div)
{
	con.style.display = "none";
	con.conState = false;
	
	right_div.innerHTML = "";

	/**
	* Set Navigation item details to default message
	*/		
	var main_lbl = alib.dom.createElement("p", right_div);
	alib.dom.styleSet(main_lbl, "margin", "0 0 0 5px");
	alib.dom.styleSetClass(main_lbl, "notice");
	main_lbl.innerHTML = "Select an item to the left to edit properties or click Add item to create a new item";
}

/**
* Select a folder
* @param {object} item = item to set folder path to
*/
AntAppSettings.prototype.selectFolder = function(item)
{
	var file_browser = new AntFsOpen();
	file_browser.filterType = "folder";
	file_browser.onSelect = function(fid, name, path)
	{
		item.folder_id = fid;
	}
	file_browser.showDialog();
}

/**
* Save navigation layout
* @param {object} con Current container node
*/
AntAppSettings.prototype.saveLayout = function(con)
{
	for(var node = 0; node < con.childNodes.length; node++)
	{
		if(con.childNodes[node].nav_type)
		{
			switch(con.childNodes[node].nav_type)
			{
			case "section":
				if(con.childNodes[node].conState != false)
				{
					var title = escape(con.childNodes[node].title);
					this.nav_xml += "<section title='" + title + "'>";
					this.saveLayout(con.childNodes[node]);
					this.nav_xml += "</section>";
				}
				break;
			case "item":
				if(con.childNodes[node].conState != false)
				{
					/**
					* Get name and type
					*/		
					var name = " name='" + escape(con.childNodes[node].name) + "'";
					var type = " type='" + con.childNodes[node].type + "'";
					var obj_type = "";
					var browseby = "";
					var calendar_id = "";
					var folder_id = "";
					var tooltip = "";
					var title = "";
					var icon = "";
					var url = "";
					
					if(con.childNodes[node].type == "object" || con.childNodes[node].type == "browse")
					{
						/**
						* Get obj_type (required)
						*/		
						if(con.childNodes[node].obj_type != null)
							var obj_type = " obj_type='" + con.childNodes[node].obj_type + "'";
							
						/**
						* Check if browseby (optional)
						*/		
						if(con.childNodes[node].type == "browse")
						{
							if(con.childNodes[node].browseby != null)
								var browseby = " browseby='" + con.childNodes[node].browseby + "'";
						}
					}
					
					if(con.childNodes[node].type == "calendar")
					{
						/**
						* Get calendar_id (required)
						*/		
						if(con.childNodes[node].calendar_id != null)
							var calendar_id = " id='" + con.childNodes[node].calendar_id + "'";
					}
					
					/**
					* If type == folder, check for folder_id (optional)
					*/		
					if(con.childNodes[node].type == "folder")
					{
						if(con.childNodes[node].folder_id != null)
							var folder_id = " folder_id='" + con.childNodes[node].folder_id + "'";
					}
					
					/**
					* Check if title is set (optional)
					*/		
					if(con.childNodes[node].title != null)
						var title = " title='" + escape(con.childNodes[node].title) + "'";
					
					/**
					* Check if icon is set (optional)
					*/		
					if(con.childNodes[node].icon != null)
						var icon = " icon='" + escape(con.childNodes[node].icon) + "'";
						
					/**
					* Check if tooltip is set (optional)
					*/		
					if(con.childNodes[node].tooltip != null)
						var tooltip = " tooltip='" + escape(con.childNodes[node].tooltip) + "'";
					
					/**
					* Check if url is set (optional)
					*/		
					if(con.childNodes[node].type == "link" && con.childNodes[node].url != null)
						var url = " url='" + escape(con.childNodes[node].url) + "'";
					
					/**
					* Check for filter, or for new condition
					*/		
					if((con.childNodes[node].type == "browse" && con.childNodes[node].childNodes[2]) || (con.childNodes[node].type == "browse" && con.childNodes[node].conditions != null))
					{
						/**
						* If new condition set, ignore original conditions
						*/		
						if(con.childNodes[node].conditions != null && con.childNodes[node].conditions.getNumConditions() > 0)
						{
							this.nav_xml += "<item" + type + name + title + obj_type + icon + url + browseby + calendar_id + folder_id + tooltip + ">";
							this.nav_xml += "<filter>";
							
							/**
							* Add all new conditions					
							*/		
							for (var i = 0; i < con.childNodes[node].conditions.getNumConditions(); i++)
							{
								var cond = con.childNodes[node].conditions.getCondition(i);
								var blogic = " blogic='" + cond.blogic + "'";
								var field = " field='" + cond.fieldName + "'";
								var operator = " operator='" + cond.operator + "'";
								var value = " value='" + cond.condValue + "'";
								this.nav_xml += "<condition" + blogic + field + operator + value + " />";
							}

							this.nav_xml += "</filter>";
							this.nav_xml += "</item>";
							this.saveLayout(con.childNodes[node]);
						}
						else
						{
							this.nav_xml += "<item" + type + name + title + obj_type + icon + url + browseby + calendar_id + folder_id + tooltip + ">";
							this.saveLayout(con.childNodes[node]);
							this.nav_xml += "</item>";
						}
					}
					else
					{
						this.nav_xml += "<item" + type + name + title + obj_type + icon + url + browseby + calendar_id + folder_id + tooltip + " />";
						this.saveLayout(con.childNodes[node]);
					}
				}
				break;
			case "filter":
				/**
				* Filter tags will be added under condition case
				*/		
				this.saveLayout(con.childNodes[node]);
				break;
			case "condition":
				/**
				* Check if new conditions were set, if so original conditions were ignored
				*/		
				if(con.childNodes[node].parentNode.parentNode.conditions != null)
				{
					/**
					* Check if any new conditions were added
					*/		
					if(con.childNodes[node].parentNode.parentNode.conditions.getNumConditions() == 0)
					{
						/**
						* No new conditions, but original conditions exist
						*/		
						if(con.childNodes[node].parentNode.conditions != null && con.childNodes[node].parentNode.parentNode.type == "browse")
						{
							this.nav_xml += "<filter>";
							for(var i = 0; i < con.childNodes[node].parentNode.conditions.length; i++)
							{
								var cond_obj = con.childNodes[node].parentNode.conditions[i];
								var blogic = " blogic='" + cond_obj.blogic + "'";
								var field = " field='" + cond_obj.field + "'";
								var operator = " operator='" + cond_obj.operator + "'";
								var value = " value='" + cond_obj.value + "'";
								var cond = blogic + field + operator + value;
								this.nav_xml += "<condition" + cond + " />";
							}
							this.nav_xml += "</filter>";
						}
					}
				}
				else
				{
					/**
					* Add original conditions
					*/		
					if(con.childNodes[node].parentNode.conditions != null)
					{
						this.nav_xml += "<filter>";
						for(var i = 0; i < con.childNodes[node].parentNode.conditions.length; i++)
						{
							var cond_obj = con.childNodes[node].parentNode.conditions[i];
							var blogic = " blogic='" + cond_obj.blogic + "'";
							var field = " field='" + cond_obj.field + "'";
							var operator = " operator='" + cond_obj.operator + "'";
							var value = " value='" + cond_obj.value + "'";
							var cond = blogic + field + operator + value;
							this.nav_xml += "<condition" + cond + " />";
						}
						this.nav_xml += "</filter>";
					}
				}
				break;
			}
		}
		else
		{

			/**
			* If a non-navigation node has children, check children for navigation nodes
			*/		
			if(con.childNodes[node].childNodes != null)
				this.saveLayout(con.childNodes[node]);
			
			/**
			* Done, save xml
			*/		
			if(con.childNodes[node].id == "end_nav")
			{
				/**
				* Check if default type is set
				*/		
				if(this.default_type != "")
				{
					var xml = "<navigation default='"+this.default_type+"'>"+this.nav_xml+"</navigation>"
					this.saveLayoutXml(xml);
				}
				else
				{
					var xml = "<navigation>"+this.nav_xml+"</navigation>";
					this.saveLayoutXml(xml);
				}
			}
		}
	}
}

/**
* Save navigation xml for layout
* @param {string} xml Navigation xml
*/
AntAppSettings.prototype.saveLayoutXml = function(xml)
{
	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Saving, please wait...";
	dlg.statusDialog(dv_load, 150, 100);
	
	var args = [["name", this.app.name], ["title", this.app.title], ["layout_xml", xml]];
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if(!ret['error'])
        {
            dlg.hide();
            this.cls.checkDefault();
            ALib.statusShowAlert(this.cls.app.title + " Saved!", 3000, "bottom", "right");            
        }
        else
        {
            dlg.hide();
            ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
        }
    };
    ajax.exec("/controller/Application/saveLayout", args);
}

/**
* Displays the elements to select user
*/
AntAppSettings.prototype.loadCurrentUser = function()
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {        
        this.cbData.cls.userDetails = ret;
        this.cbData.cls.selectUser(ret);
    };
    
    var args = new Array();    
    
    if(this.app.userId)
        args[args.length] = ["userId", this.app.userId];
        
    ajax.exec("/controller/User/getUserDetails", args);
}

/**
* Displays the elements to select user
*/
AntAppSettings.prototype.selectUser = function()
{
    if(!this.userDetails)
        return;
    
    this.divScopeUser.innerHTML = "";
    
    // Select Manager
    var userBtn = alib.dom.setElementAttr(alib.dom.createElement("input", this.divScopeUser), [["type", "button"], ["value", "Select User"]]);
    
    // set user label style
    var userLabel = alib.dom.setElementAttr(alib.dom.createElement("label", this.divScopeUser), [["innerHTML", this.userDetails.name]]);
    alib.dom.styleSet(userLabel, "fontWeight", "bold");
    alib.dom.styleSet(userLabel, "fontSize", "12px");
    alib.dom.styleSet(userLabel, "margin", "0 5px");
    
    // add select user button feature    
    userBtn.userDetails = this.userDetails;
    userBtn.onclick = function()
    {
        var cbrowser = new CUserBrowser();
        cbrowser.cls = this;
        cbrowser.userDetails = this.userDetails;
        cbrowser.onSelect = function(cid, name) 
        {
            this.userDetails.id = cid;
            this.userDetails.name = name;
            this.cls.parentNode.childNodes[1].innerHTML = " " + name;
        }
        cbrowser.showDialog();
    }
}

/**
* Displays the dropdown of team
*/
AntAppSettings.prototype.teamDropdown = function()
{
    var userObject = new CAntObject("user")
    userObject.teamId = this.app.teamId;
    userObject.cls = this;
    userObject.onteamsloaded = function(ret)
    {
        this.cls.divScopeTeam.innerHTML = "";
        this.cls.teamDropdown = alib.dom.createElement("select", this.cls.divScopeTeam);
        buildDropdown(this.cls.teamDropdown, [["", "Select Team"]]);
        
        this.teamDropdown = this.cls.teamDropdown;
        
        var teamData = ret;        
        delete ret['teamCount'];
        this.populateTeam(ret, ret[0].parentId);
        this.addSpacedPrefix(teamData);
    }
    userObject.loadTeam();
}

/**
* Displays the elements to select user
*/
AntAppSettings.prototype.toggleScope = function(scope)
{
    alib.dom.styleSet(this.divScopeUser, "display", "none");
    alib.dom.styleSet(this.divScopeTeam, "display", "none");
            
    switch(scope)
    {
        case "user":
            alib.dom.styleSet(this.divScopeUser, "display", "inline-block");
            break;
        case "team":
            alib.dom.styleSet(this.divScopeTeam, "display", "inline-block");
            break;
    }
}

/**
 * Check navigation is default
 *
 * @public
 * @this {class}
 */
AntAppSettings.prototype.checkDefault = function()
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(ret.error)
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
                                        
        if(ret)
            alib.dom.styleSet(this.cbData.cls.resetDefault, "display", "block");
    };
    
    var args = new Array();
    args[args.length] = ['app', this.app.appName];
    ajax.exec("/controller/Application/checkNav", args);
}
