/**
* @fileOverview AntAppDash: Main application for the page dashboard
* @depricated This has been removed, the object dashboard loader now handles all dashboards, both custom and application. -joe
*
* @author: Marl Tumulak, marl.tumulak@aereus.com Copyright (c) 2011 Aereus Corporation. All rights reserved.
* @constructor: AntAppDash
* @param {string} con Required parameter.
*
*/
function AntAppDash(con)
{
	/**
	* Basic Variables 
	*/
    this.mainCon = con;
    this.m_document = null;     // Application Document - set by ant
    this.m_processid = null;    // Process id - set by Ant
    this.m_container = alib.dom.createElement("div", con);    // Handle to application container - set by Ant
    this.appNavname = null; // name of appname.navname
    this.dbDropdownCon = null;
}

/**
* Entry point for application
*/
AntAppDash.prototype.main = function()
{
    this.m_widgets = new Array();
    this.m_cols = new Array();
    this.m_menus = new Array();
    this.loadWidgets(this.m_container);
    this.mainCon.id = "appDashMainCon";
    this.m_container.id = "appDashCon";
    this.resize();
}

/**
* Resize UI
*/
AntAppDash.prototype.resize = function()
{
	alib.dom.styleSet(this.mainCon, "height", (getWorkspaceHeight() - 10) + "px");
}

/**
* Perform needed clean-up on app exit
*/
AntAppDash.prototype.exit = function()
{
	// Clear Menus
    for (var i = 0; i < this.m_menus.length; i++)
        {
        this.m_menus[i].destroyMenu();
    }

	// Unload widget contents
    this.unloadAllWidgets();
    this.m_container.innerHTML = "";
    this.m_cols = null;
    this.m_menus = null;
    this.m_widgets = null;
}

/**
* To be over-ridden by calling process to detect when definition is finished loading.
*
* @public
* @this {AntAppDash} 
* @param {Object} ret   Object that is a result from ajax
*/
AntAppDash.prototype.onload = function(ret)
{
}

AntAppDash.prototype.loadWidgets = function(con)
{
    con.innerHTML = "<div class='loading'></div>";    
    var ajax = new CAjax();
    ajax.m_con = con;
    ajax.m_dashclass = this;
    ajax.onload = function(root)
    {   
        var total_width = root.getAttribute('width');
        var spcon = new CSplitContainer("verticle", total_width);

        this.m_con.innerHTML = "";    
        this.m_dashclass.addWidgetSettings(this.m_con, total_width);

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
                if (!this.m_dashclass.m_cols)
                    this.m_dashclass.m_cols = new Array();
                this.m_dashclass.m_cols[this.m_dashclass.m_cols.length] = col;

				/**
				* Always add blank resize box at the end of each col
				*/
                var wbox = new CWidgetBox("dz_home", "100px");
                wbox.m_cls = this.m_dashclass;
                wbox.onMoved = function(newcon) 
                { 
                    this.m_cls.saveLayout(); 
                    //this.m_cls.loadGraph(newcon.m_con, newcon.m_gid);
                }
                wbox.onBeforeMove = function(from, to) 
                { 
                    //from.parentNode.m_con.innerHTML = "";    
                }
                wbox.print(col);

				/**
				* Get Widgets
				*/
                var num_wids = child.getNumChildren();
                for (w = 0; w < num_wids; w++)
				{
                    var wid = child.getChildNode(w);
                    if (wid.m_name == "widget")
					{
                        var className = "";
                        var title = "";
                        var id = "";
                        var removable = "";
                        var data = "";

                        var num_vars = wid.getNumChildren();
                        for (j = 0; j < num_vars; j++)
						{
                            var dbattr = wid.getChildNode(j);
                            switch (dbattr.m_name)
                            {
                                case "class":
                                    className = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
                                    break;
                                case "title":
                                    title = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
                                    break
                                case "id":
                                    id = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
                                    break
                                case "removable":
                                    removable = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
                                    break
                                case "data":
                                    data = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
                                    break
                            }
                        }
                        this.m_dashclass.addWidget(col, id, title, className, removable, false, data, width);    
                    }
                }
            }    
        }
        /**
		* Add buffer column
		*/
        var tmpcon = spcon.addPanel("*");

        this.m_dashclass.loadAllWidgets();
    };

    ajax.exec("/applications/xml_getwidgets.awp?appNavname=" + this.appNavname);
}

AntAppDash.prototype.loadWidget = function(eid, col)
{    
    var ajax = new CAjax();
    ajax.m_col = this.m_cols[col];
    ajax.m_dashclass = this;
    ajax.onload = function(root)
    {
        var num = root.getNumChildren();
        for (i = 0; i < num; i++)
            {
            var child = root.getChildNode(i);
            if (child.m_name == "column")
                {
                var width = child.getAttribute("width");

                var num_wids = child.getNumChildren();
                for (w = 0; w < num_wids; w++)
                    {
                    var wid = child.getChildNode(w);
                    if (wid.m_name == "widget")
                        {
                        var className = "";
                        var title = "";
                        var id = "";
                        var removable = "";
                        var data = "";

                        var num_vars = wid.getNumChildren();
                        for (j = 0; j < num_vars; j++)
                            {
                            var dbattr = wid.getChildNode(j);
                            switch (dbattr.m_name)
                            {
                                case "class":
                                    className = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
                                    break;
                                case "title":
                                    title = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
                                    break
                                case "id":
                                    id = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
                                    break
                                case "removable":
                                    removable = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
                                    break
                                case "data":
                                    data = (dbattr.m_text) ? unescape(dbattr.m_text) : "";
                                    break
                            }
                        }

                        this.m_dashclass.addWidget(this.m_col, id, title, className, removable, true, data, width);
                    }
                }
            }    
        }
    };    
    ajax.exec("/applications/xml_getwidgets.awp?eid=" + eid + "&appNavname=" + this.appNavname);
}
/**
* Append a widget to the dashboard 
*/
AntAppDash.prototype.addWidgetNew = function(col, id, title, className, removable, exec, data, width)
{
	/**
	* Get last widgetbox to insert before
	*/
    var lastbox = col.childNodes[col.childNodes.length - 1];

	/**
	* Create container (handles drag and drop)
	*/
    var wid_index = this.m_widgets.length;
    var wbox = new CWidgetBox("dz_home");
    wbox.m_cls = this;
    wbox.m_wind = wid_index;
    wbox.onMoved = function(newcon) 
    {
        this.m_cls.saveLayout(); 
        this.m_cls.m_widgets[this.m_wind].main();
    }
    wbox.onBeforeMove = function(from, to) 
    { 
        this.m_cls.m_widgets[this.m_wind].exit();
        //from.parentNode.m_con.innerHTML = "";    
    }
    var w_con = wbox.getCon();

 	/**
	* Used to determine what item is in container
	*/
    w_con.m_id = id; 
    wbox.printBefore(col, lastbox);

 	/**
	* Create content table
	*/
	var widgetCon = alib.dom.createElement("div", w_con);
	alib.dom.styleSetClass(widgetCon, "dbWidget");

	var widgetConHeader = alib.dom.createElement("div", widgetCon);
	alib.dom.styleSetClass(widgetConHeader, "dbWidgetHeader");

	var widgetConOpt = alib.dom.createElement("div", widgetConHeader);
	alib.dom.styleSetClass(widgetConOpt, "dbWidgetOpt");

	var widgetConTitle = alib.dom.createElement("div", widgetConHeader);
	alib.dom.styleSetClass(widgetConTitle, "dbWidgetTitle");

	var widgetConBody = alib.dom.createElement("div", widgetCon);
	alib.dom.styleSetClass(widgetConBody, "dbWidgetBody");
    
 	/**
	* Create widget application class    
	*/
	widgetConTitle.innerHTML = title;
    var widapp = eval("new " + className + "()");
    
    if(className = 'CWidReport')
        widapp.widgetWidth = width.replace('px', '');
        
    if (typeof data != "undefined")
        widapp.m_data = data;

 	/**
    * Add dropdown context menu
	*/
    var dm = new CDropdownMenu();
    dm.handleDuplicates = true;
	/*
    widapp.m_dm = dm;
	*/
    if (removable == 't')
  	{
        dm.addEntry('Remove Widget', function(cls, id) { cls.unloadWidget(id);}, null, null, [this, id]);
    }
    //cct.get_ctitle().appendChild(dm.createImageMenu());
	widgetConOpt.appendChild(dm.createImageMenu("/images/icons/gear_16.png", "/images/icons/gear_16.png", "/images/icons/gear_16.png"));

 	/**
    * Make dragable and print
	*/
    DragAndDrop.registerDragable(widgetConTitle, widgetCon, "dz_home");
    var drag_icon = ALib.m_document.createElement("div");
    drag_icon.innerHTML = "Move: " + widgetConTitle.innerHTML;
    DragAndDrop.setDragGuiCon(widgetConTitle, drag_icon, 15, 15);

 	/**
	* Execute widget
	*/
	/*
    if (typeof exec == "undefined" || exec == true)
        widapp.main();
	*/
}

/**
* Append widget to a column
*/
AntAppDash.prototype.addWidget = function(col, id, title, className, removable, exec, data, width)
{
	/**
	* Get last widgetbox to insert before
	*/
    var lastbox = col.childNodes[col.childNodes.length - 1];

	/**
	* Create contianer (handles drag and drop)
	*/
    var wid_index = this.m_widgets.length;
    var wbox = new CWidgetBox("dz_home");
    wbox.m_cls = this;
    wbox.m_wind = wid_index;
    wbox.onMoved = function(newcon) 
    {
        this.m_cls.saveLayout(); 
        this.m_cls.m_widgets[this.m_wind].main();
    }
    wbox.onBeforeMove = function(from, to) 
    { 
        this.m_cls.m_widgets[this.m_wind].exit();
        //from.parentNode.m_con.innerHTML = "";    
    }
    var w_con = wbox.getCon();
 	/**
	* Used to determine what item is in container
	*/
    w_con.m_id = id; 
    wbox.printBefore(col, lastbox);

	// Create content table
	// ---------------------------------------------
	var widgetCon = alib.dom.createElement("div", w_con);
	alib.dom.styleSetClass(widgetCon, "dbWidget");

	var widgetConHeader = alib.dom.createElement("div", widgetCon);
	alib.dom.styleSetClass(widgetConHeader, "dbWidgetHeader");

	var widgetConOpt = alib.dom.createElement("div", widgetConHeader);
	alib.dom.styleSetClass(widgetConOpt, "dbWidgetOpt");

	var widgetConTitle = alib.dom.createElement("div", widgetConHeader);
	alib.dom.styleSetClass(widgetConTitle, "dbWidgetTitle");

	var widgetConBody = alib.dom.createElement("div", widgetCon);
	alib.dom.styleSetClass(widgetConBody, "dbWidgetBody");

	widgetConTitle.innerHTML = title;
    
    var cct_con = widgetConBody;
    w_con.m_con = cct_con;    
 	/**
	* Create widget application class    
	*/
    var widapp = eval("new " + className + "()");
    widapp.m_wbox = wbox;
    widapp.m_dashclass = this;
    widapp.m_container = cct_con;
    widapp.m_widTitle = widgetConTitle;
    widapp.m_id = id;    
    widapp.appNavname = this.appNavname;
    
    if(className = 'CWidReport')
        widapp.widgetWidth = width.replace('px', '');
        
    if (typeof data != "undefined")
        widapp.m_data = data;

 	/**
    * Add dropdown context menu
	*/
    var dm = new CDropdownMenu();
    dm.handleDuplicates = true;
    
    this.m_menus.push(dm);
    widapp.m_dm = dm;
    if (removable == 't')
  	{
        dm.addEntry('Remove Widget', function(cls, id) { cls.unloadWidget(id);}, null, null, [this, id]);
    }
    widgetConOpt.appendChild(dm.createImageMenu("/images/icons/gear_16.png", "/images/icons/gear_16.png", "/images/icons/gear_16.png"));

 	/**
    * Make dragable and print
	*/
    DragAndDrop.registerDragable(widgetConTitle, widgetCon, "dz_home");
    var drag_icon = ALib.m_document.createElement("div");
    drag_icon.innerHTML = "Move: " + widgetConTitle.innerHTML;
    DragAndDrop.setDragGuiCon(widgetConTitle, drag_icon, 15, 15);

 	/**
	* Execute widget
	*/
    if (typeof exec == "undefined" || exec == true)
        widapp.main();
        
    this.m_widgets[wid_index] = widapp;
}

/**
* Execute main function for all widgets once loaded
*/
AntAppDash.prototype.loadAllWidgets = function()
{
    for (var i = 0; i < this.m_widgets.length; i++)
    {
        this.m_widgets[i].main();
    }
}


/**
* Execuate exit function for all widgets to close out
*/
AntAppDash.prototype.unloadAllWidgets = function()
{
    for (var i = 0; i < this.m_widgets.length; i++)
        {
        this.m_widgets[i].exit();
        if (this.m_widgets[i].m_dm)
            this.m_widgets[i].m_dm.destroyMenu();
    }
}

/**
* Col has been resized
*
* @event onColResize
*/
AntAppDash.prototype.onColResize = function()
{
    this.loadAllWidgets();

    var args = new Array();

    args[0] = ['num_cols', this.m_cols.length];

    // Set appNavename argument to identify which dashboard to be updated
    args[1] = ['appNavname', this.appNavname];

    for (var i = 0; i < this.m_cols.length; i++)
	{
        args[args.length] = ["col_"+i, this.m_cols[i].offsetWidth + "px"];
    }
    
    ajax = new CAjax('json');    
    ajax.exec("/controller/Application/dashboardSaveLayoutResize", args);
}


/**
* Col is being resized
*
* @event onColResizeStart
*/
AntAppDash.prototype.onColResizeStart = function()
{
    this.unloadAllWidgets();

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


/**
* Send modified layout to server
*/
AntAppDash.prototype.saveLayout = function()
{
    var args = new Array();

    args[0] = ['num_cols', this.m_cols.length];

    /**
	* Set appNavename argument to identify which dashboard to be updated
	*/
    args[1] = ['appNavname', this.appNavname];
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
    
    ajax = new CAjax('json');    
    ajax.exec("/controller/Application/dashboardSaveLayout", args);
}

/**
* Remove a widget from the dashboard
*/
AntAppDash.prototype.unloadWidget = function(id)
{
    var args = new Array();

    args[0] = ['eid', id];
    args[1] = ['appNavname', this.appNavname];

    for (var i = 0; i < this.m_widgets.length; i++)
        {
        if (this.m_widgets[i].m_id == id)
        {            
            ajax = new CAjax('json');
            ajax.cls = this;
            ajax.wid = this.m_widgets[i];
            ajax.onload = function(ret)
            {
                this.cls.unloadWidgetCb(ret, this.wid);
            };
            ajax.exec("/controller/Application/dashboardDelWidget", args);

            break;
        }
    }
}
AntAppDash.prototype.unloadWidgetCb = function(retval, wid)
{
    wid.m_wbox.getCon().parentNode.removeChild(wid.m_wbox.getCon());
    wid.exit();
    delete wid;
}

AntAppDash.prototype.addWidgetSettings = function(con, total_width)
{    
    var table = ALib.m_document.createElement("table");
    var tbody = ALib.m_document.createElement("tbody");
    table.style.cssFloat = "right";
    table.style.marginRight = "20px";
    table.style.width = "total_width";
    table.appendChild(tbody);
	/**
	* Widgets Row
	*/
    var row = ALib.m_document.createElement("tr");
    tbody.appendChild(row);

    var td = ALib.m_document.createElement("td");
    row.appendChild(td);
    //td.innerHTML = "<img src='/images/icons/settings_16.png' border='0' />";

    this.dbDropdownCon = ALib.m_document.createElement("td");
    row.appendChild(this.dbDropdownCon);
    
    var td = ALib.m_document.createElement("td");
    row.appendChild(td);
    var aAddWidget = ALib.m_document.createElement("a");    
    aAddWidget.href="javascript: void(0)";
    aAddWidget.innerHTML = "+Add Widget";
    aAddWidget.cls = this;
    aAddWidget.onclick = function()
    {
        var browser = new CWidgetBrowser();
        browser.appNavname = this.cls.appNavname;
        browser.m_cls = this.cls;
        browser.onSelect = function(widget_id)
        {
            var appcls = this.m_cls;
            var args = new Array();

            args[0] = ['wid', widget_id];
            args[1] = ['appNavname', appcls.appNavname];
            
            ajax = new CAjax('json');
            ajax.cls = appcls;
            ajax.onload = function(ret)
            {
                this.cls.loadWidget(ret, 0);
            };
            ajax.exec("/controller/Application/addWidget", args);
        }
        browser.showDialog();

    }
    td.appendChild(aAddWidget);

  	/**
	* Dashboard width row
	*/
    var td = ALib.m_document.createElement("td");
    row.appendChild(td);
    //td.innerHTML = "<img src='/images/icons/settings_16.png' border='0' />";

	/* NOTE: for now all dashboards have a static width of 100%
    var td = ALib.m_document.createElement("td");
    row.appendChild(td);
    td.innerHTML = "| &nbsp; ";

    var td = ALib.m_document.createElement("td");
    row.appendChild(td);
    var dm = new CDropdownMenu();
    
    this.m_menus.push(dm);
    var funct = function(width, appcls)
    {
        var args = new Array();

        args[0] = ['width', width];
        args[1] = ['appNavname', appcls.appNavname];
        
        ajax = new CAjax('json');
        ajax.cls = appcls;
        ajax.onload = function(ret)
        {
            this.cls.exit();
            this.cls.main();
        };
        ajax.exec("/controller/Application/dashboardSetTotalWidth", args);
    }    

    dm.addEntry("100%", funct, "/images/themes/" + Ant.theme.name+ "/icons/circle_blue.png", null, ['100%', this]);
	// Add range

    for (var i = 800; i <= 1600; i+=25)
        dm.addEntry(i + "px", funct, "/images/themes/" + Ant.theme.name+ "/icons/circle_blue.png", null, [i, this])

    td.appendChild(dm.createLinkMenu("Width"));
	*/

    con.appendChild(table);

    var divClear = ALib.m_document.createElement("div");
    divClear.style.clear = "both";
    con.appendChild(divClear);
    
    this.onload();
}
