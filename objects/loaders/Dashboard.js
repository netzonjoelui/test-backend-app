/**
* @fileoverview This sub-loader will load Dashboards
* @depriacted We now use the form with a plugin
*
* @author    Marl Tumulak, marl.aereus@aereus.com
*             Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
* Creates an instance of AntObjectLoader_Dashboard.
*
* @constructor
* @param {CAntObject} obj Handle to object that is being viewed or edited
* @param {AntObjectLoader} loader Handle to base loader class
*/
function AntObjectLoader_Dashboard(obj, loader)
{
    this.mainObject = obj;
    this.dashboardId = this.mainObject.id;    
    this.loaderCls = loader;
    
    this.outerCon = null; // Outer container
    this.mainConMin = null; // Minified div for collapsed view
    this.mainCon = null; // Inside outcon and holds the outer table
    this.formCon = null; // inner container where form will be printed
    this.bodyCon = null;
    this.bodyFormCon = null; // Displays the form
    this.bodyNoticeCon = null; // Right above the form and used for notices and inline duplicate detection
    
    this.ctbl = null; // Content table used for frame when printed inline
    this.toolbar = null;        
    this.plugins = new Array();
    this.printOuterTable = true; // Can be used to exclude outer content table (usually used for preview)
    this.fEnableClose = true; // Set to false to disable "close" and "save and close"
    
    this.dropZone = "dashboardDropZone";    // name for dropzones
    this.appNavname = null;     // used for displaying the application dashboard
    this.dropdownPopulated = false;    
    this.allowEdit = false;
    this.dashboardWidgets = new Array();

	/**
	 * Layout array
	 *
	 * Structure: [ {width:string, widgets: [ {id:, widget:, data:} ] } ]
	 *
	 * @var {Array}
	 */
	this.layout = new Array();

	/**
	 * Current columns array
	 *
	 * @var {Array}
	 */
	this.columns = new Array();
    
    this.columnChange = false;
}

/**
 * Refresh the form
 */
AntObjectLoader_Dashboard.prototype.refresh = function()
{
}

/**
 * Enable to disable edit mode for this loader
 *
 * @param {bool} setmode True for edit mode, false for read mode
 */
AntObjectLoader_Dashboard.prototype.toggleEdit = function(setmode)
{    
}

/**
 * Callback is fired any time a value changes for the mainObject 
 */
AntObjectLoader_Dashboard.prototype.onValueChange = function(name, value, valueName)
{
    if(name=="num_columns")
        this.columnChange = true;
}

/**
 * Callback function used to notify the parent loader if the name of this object has changed
 */
AntObjectLoader_Dashboard.prototype.onNameChange = function(name)
{
}

/**
 * Print form on 'con'
 *
 * @param {DOMElement} con A dom container where the form will be printed
 * @param {array} plugis List of plugins that have been loaded for this form
 */
AntObjectLoader_Dashboard.prototype.print = function(con, plugins)
{
    this.outerCon = con;
    this.mainCon = alib.dom.createElement("div", con);
    this.formCon = this.mainCon;

    var outer_dv = alib.dom.createElement("div", this.formCon);
    
    this.bodyCon = alib.dom.createElement("div", outer_dv);    
    alib.dom.styleSet(this.bodyCon, "margin-top", "5px");
    
    // Notice container
    this.bodyNoticeCon = alib.dom.createElement("div", this.bodyCon);

    // Body container
    this.bodyFormCon = alib.dom.createElement("div", this.bodyCon);    
    
    // Dashboard Container
    this.dashboardFormCon = alib.dom.createElement("div", this.bodyFormCon);        
    this.dashboardHeaderCon = alib.dom.createElement("div", this.bodyFormCon);
    this.dashboardWidgetCon = alib.dom.createElement("div", this.bodyFormCon);

	// Hide title box if not in edit mode
	alib.dom.styleSet(this.loaderCls.titleCon, "display", "none");
    
    this.initDashboard();
    this.loadDashboard();
}

/**
 * Inistialize the dashboard
 */
AntObjectLoader_Dashboard.prototype.initDashboard = function()
{
    if(this.mainObject.security.edit)
        this.allowEdit = true;
        
    this.displayObjectForm();
}

/**
 * Reloads the dashboard
 */
AntObjectLoader_Dashboard.prototype.loadDashboard = function()
{
    // Dashboard Objects
    this.dashboardData = new Object();
    
    // Dashboard variables
    this.dashboardCols = new Array();
    this.dashboardWidgets = new Array();
    this.dashboardMenus = new Array();
    
    // Load saved dashboards
    this.buildInterface();
}

/**
 * Builds the Dashboard interface
 *
 * @this {AntObjectLoader_Dashboard}
 * @private
 */
AntObjectLoader_Dashboard.prototype.buildInterface = function()
{
    this.dashboardWidgetCon.innerHTML = "";
    
    if(this.dashboardId) // Saved Dashboard
        this.loadLayout();

    
    this.refreshWidgets();
}

/**
 * Displays the dashboad form
 *
 * @this {AntObjectLoader_Dashboard} 
 */
AntObjectLoader_Dashboard.prototype.displayObjectForm = function()
{    
    if(this.mainObject.security.edit)
    {
        var formLoader = new AntObjectLoader_Form(this.mainObject, this.loaderCls);
        
        this.loaderCls.pluginAddToolbarEntry("Add Widgets", 
                                            function(cbData) 
                                            {
                                                var widBrowser = new CWidgetBrowser();
                                                widBrowser.cls = cbData.cls;                                                
                                                widBrowser.onSelect = function(widgetId)
                                                {
                                                    this.cls.addWidget(widgetId);
                                                }
                                                widBrowser.showDialog();
                                            }, { cls:this }, "first");
        formLoader.print(this.dashboardFormCon);
        
        this.loaderCls.cbData.cls = this;
        this.loaderCls.onSave = function()
        {
            if(this.cbData.cls.columnChange) // Reload dashboard for new changes in number of column
            {
                this.cbData.cls.loadDashboard();
                this.cbData.cls.columnChange = false;
            }
        }
    }
}

/**
 * Adds the widget in the database
 *
 * @public
 * @this {CAntObject}
 * @param {Integer} widgetId  Widget Id
 */
AntObjectLoader_Dashboard.prototype.addWidget = function(widgetId)
{
    var ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        this.cbData.cls.renderWidget(0, ret);
    };
    var args = new Array();
    args[args.length] = ['dashboardId', this.mainObject.id];
    args[args.length] = ['widgetId', widgetId];
    args[args.length] = ['col', 0];
    ajax.exec("/controller/Dashboard/addWidget", args);
}

/**
 * Remove a widget from the dashboard
 *
 * @this {AntObjectLoader_Dashboard} 
 */
AntObjectLoader_Dashboard.prototype.removeWidget = function(dwid)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        for(widget in this.cbData.cls.dashboardWidgets)
        {
            var currentWidget = this.cbData.cls.dashboardWidgets[widget];
            
            if(currentWidget.m_id == ret)
            {
                var widgetCon = currentWidget.m_widgetBox.getCon();
                widgetCon.parentNode.removeChild(widgetCon);
                currentWidget.exit();
                delete currentWidget;
            }
        }
    };
    var args = new Array();    
    args[args.length] = ['dwid', dwid];
    args[args.length] = ['dashboardId', this.mainObject.id];
    ajax.exec("/controller/Dashboard/removeWidget", args);
}

/**
 * Load the dashboard layout
 *
 * @public
 * @this {CAntObject}
 */
AntObjectLoader_Dashboard.prototype.loadLayout = function()
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        this.cbData.cls.layout = ret;
        this.cbData.cls.renderDashboard();
    };
    var args = new Array();
    args[args.length] = ['dbid', this.mainObject.id];
    ajax.exec("/controller/Dashboard/getLayout", args);
}

/**
 * Render the dashboard onto a container
 *
 * @private
 */
AntObjectLoader_Dashboard.prototype.renderDashboard = function()
{
	var spCon = new CSplitContainer("verticle", "100%");
    spCon.resizable = true;
	spCon.print(this.dashboardWidgetCon);
    
	// First add columns
    for (col in this.layout)
	{
		var width = (this.layout[col].width) ? this.layout[col].width : null;
		this.columns[col] = spCon.addPanel(width, "hidden");
	}
        
	// Now add all widgets. We do this separately so that the width is accurately 
	// set after all columns have been added.
    for (col in this.layout)
	{
		if (this.layout[col].widgets)
		{
			for (var i in this.layout[col].widgets)
			{
				var con = alib.dom.createElement("div", this.columns[col]);

				this.renderWidget(col, this.layout[col].widgets[i]);
			}
		}

		// Always add blank resize box at the end of each col
		var widgetBox = new CWidgetBox(this.dropZone, "300px");
		widgetBox.wbCls = this;
        widgetBox.onMoved = function(newCon)
        {
            this.wbCls.saveLayout();
        }
		widgetBox.print(this.columns[col]);
	}
    
    spCon.cls = this;
    spCon.onPanelResize = function(sizes)
    {
        this.cls.saveLayout(this.m_columns);
    };
}

/**
 * Render a widget into the specified column
 *
 * @public
 * @param {int} col The column to insert this widget into
 * @param {Object} widget Widget instance to add
 */
AntObjectLoader_Dashboard.prototype.renderWidget = function(col, widget)
{
	// Get last widgetbox to insert before    
    var lastbox = this.columns[col].childNodes[this.columns[col].childNodes.length - 1];

    // Create contianer (handles drag and drop)    
    var wIndex = this.dashboardWidgets.length;
    var widgetBox = new CWidgetBox(this.dropZone);
    widgetBox.cls = this;
    widgetBox.wIndex = wIndex
    widgetBox.onMoved = function(newCon) 
    {
        this.cls.saveLayout(); 
        this.cls.dashboardWidgets[this.wIndex].main()
    }
    widgetBox.onBeforeMove = function(from, to) 
    { 
        //this.cls.dashboardWidgets[this.wIndex].exit();        
    }
    
    var outerCon = widgetBox.getCon();
     
    // Used to determine what item is in container    
    outerCon.m_id = widget.id;
    widgetBox.printBefore(this.columns[col], lastbox);

    // Create content table
	var widgetCon = alib.dom.createElement("div", outerCon);
	alib.dom.styleSetClass(widgetCon, "dbWidget");

	var widgetConHeader = alib.dom.createElement("div", widgetCon);
	alib.dom.styleSetClass(widgetConHeader, "dbWidgetHeader");

	var widgetConOpt = alib.dom.createElement("div", widgetConHeader);
	alib.dom.styleSetClass(widgetConOpt, "dbWidgetOpt");

	var widgetConTitle = alib.dom.createElement("div", widgetConHeader);
	alib.dom.styleSetClass(widgetConTitle, "dbWidgetTitle");

	var widgetConBody = alib.dom.createElement("div", widgetCon);
	alib.dom.styleSetClass(widgetConBody, "dbWidgetBody");
    
    outerCon.m_con = widgetConBody;

    // Create widget application class
    var widgetApp = eval("new " + widget.widget + "()");
	widgetConTitle.innerHTML = widgetApp.title;
    widgetApp.m_widgetBox = widgetBox;
    this.dashboardWidgets[wIndex] = widgetApp;
    widgetApp.dashboardCls = this;
    widgetApp.m_widTitle = widgetConTitle;
    widgetApp.m_container = widgetConBody;
    widgetApp.m_id = widget.id;    
        
    if (typeof widget.data != "undefined")
        widgetApp.m_data = widget.data;
    
    // Add dropdown context menu
    var dm = new CDropdownMenu();
    dm.handleDuplicates = true;
    this.dashboardMenus.push(dm);
    widgetApp.m_dm = dm;

	// Add view
	if (this.loaderCls.antView)
		widgetApp.antView = this.loaderCls.antView;
    
    // If user has permision to change layout
    if(this.allowEdit) 
    {
        dm.addEntry('Remove Widget', function(cls, id) { cls.removeWidget(id);}, null, null, [this, widget.id]);        
        widgetConOpt.appendChild(dm.createImageMenu("/images/icons/gear_16.png", "/images/icons/gear_16.png", "/images/icons/gear_16.png"));
        
        DragAndDrop.registerDragable(widgetConTitle, widgetCon, this.dropZone);
        var drag_icon = ALib.m_document.createElement("div");
        drag_icon.innerHTML = "Move: " + widgetConTitle.innerHTML;
        DragAndDrop.setDragGuiCon(widgetConTitle, drag_icon, 15, 15);
    }
    
    // Execute widget
	widgetApp.main();
}

/**
* Execute main function for all widgets once loaded
* 
* @this {AntObjectLoader_Dashboard} 
* @event onColResize
*/
AntObjectLoader_Dashboard.prototype.executeWidgets = function()
{
    for(widget in this.dashboardWidgets)
    {
        var currentWidget = this.dashboardWidgets[widget];
        currentWidget.main();
    }
}

/**
* Execuate exit function for all widgets to close out
* 
* @this {AntObjectLoader_Dashboard} 
*/
AntObjectLoader_Dashboard.prototype.exitWidgets = function()
{
    for(widget in this.dashboardWidgets)
    {
        var currentWidget = this.dashboardWidgets[widget];
        currentWidget.exit();
        if (currentWidget.m_dm)
            currentWidget.m_dm.destroyMenu();
    }
}

/**
* Save the dashboard layout
* 
* @param {Array} spColumns    Array columns of CSplitContainer
* @this {AntObjectLoader_Dashboard} 
*/
AntObjectLoader_Dashboard.prototype.saveLayout = function(spColumns)
{
    var args = new Array();    
    args[args.length] = ['dashboardId', this.dashboardId];
    args[args.length] = ["columnCount", this.columns.length];
    
    for(col in this.columns)
    {
        var currentCol = this.columns[col];
        
        var colWidgets = "";

        for (var j = 0; j < currentCol.childNodes.length; j++)
        {
            if (currentCol.childNodes[j].m_id)
            {
                colWidgets += (colWidgets) ? ':' : '';
                colWidgets += currentCol.childNodes[j].m_id;
            }
        }

        args[args.length] = ["col_" + col, colWidgets];
    }
    
    if(typeof spColumns !== "undefined")
    {
        for(column in spColumns)
        {
            var currentColumn = spColumns[column];
            args[args.length] = ["columnWidth_" + column, currentColumn.offsetWidth];
        }
    }
    
    ajax = new CAjax('json');    
    ajax.exec("/controller/Dashboard/saveLayout", args);
}

/**
* Col has been resized
* 
* @this {AntObjectLoader_Dashboard} 
* @event onColResize
*/
AntObjectLoader_Dashboard.prototype.onColResize = function()
{
    this.executeWidgets();
    var args = new Array();
    args[args.length] = ['dashboardId', this.dashboardId];
    
    for(col in this.dashboardCols)
    {
        var currentCol = this.dashboardCols[col];
        args[args.length] = ["col_" + col, currentCol.offsetWidth + "px"];
    }
    
    ajax = new CAjax('json');    
    ajax.exec("/controller/Dashboard/saveLayoutResize", args);
}


/**
* Col is being resized
* 
* @this {AntObjectLoader_Dashboard} 
* @event onColResizeStart
*/
AntObjectLoader_Dashboard.prototype.onColResizeStart = function()
{
    this.exitWidgets();
    for(col in this.dashboardCols)
    {
        var currentCol = this.dashboardCols[col];
        
        for (var j = 0; j < currentCol.childNodes.length; j++)
        {
            if (currentCol.childNodes[j].m_id)
                currentCol.childNodes[j].m_con.innerHTML = "";
        }
    }
}

/**
* Saves the widget data
* @param {integer} dwid     Dashboard Widget Id
* @param {string} data      Widget data
*  
* @this {CAntObject} 
*/
AntObjectLoader_Dashboard.prototype.saveData = function(dwid, data)
{
    var args = new Array();
    args[args.length] = ['dwid', dwid];
    args[args.length] = ['data', data];
    
    ajax = new CAjax('json');
    ajax.exec("/controller/Dashboard/saveData", args);
}

/**
* Refresh all the widgets
*  
* @this {CAntObject} 
*/
AntObjectLoader_Dashboard.prototype.refreshWidgets = function()
{
    for(widget in this.dashboardWidgets)
    {
        var currentWidget = this.dashboardWidgets[widget];
        
        if(typeof(currentWidget.refresh) == "function")
            currentWidget.refresh();
    }
    
    var cls = this;
    var callback = function()
    {
        cls.refreshWidgets();
    }
    
    window.setTimeout(callback, 60000);
}
