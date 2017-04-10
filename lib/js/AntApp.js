/**
* @fileOverview AntApp: Used to process XML definitions to load apps into ANT
*
* ANT Application shell. Will process XML definition to load app into ANT.
* This class should be used for loading apps in both desktop and mobile
* environments and will lean heavy on AntView(s)
*
* var app = new AntApp("crm");
* app.main() // execute, load definition, build interface
*
* Example Def:
* <code>
* <applicaiton name='crm' title='Customer Relationship Management'>
*		<objects> // list of objects associated with this application (will be included in settings)
*			<object>email</object>
*			<object>email_message</object>
*		</objects>
*		<navigation>
*			<section name='Actions'>
*				<item type='object' name='new_customer' obj_type='customer' />
*				<item type='browse' name='activity' obj_type='activity' />
*				<item type='link' name='' url='http://www.aereus.com/support/answers/123' />
*				<item type='folder' name='Worship Team Files' path='/music/myfiles' />
*			</section>
*			<section name='Browse Messages'>
*				<item type='browse' name='All Mail' obj_type='email_thread'></view>
*			</section>
*		</navigation>
* </application>
* </code>
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of AntApp
 *
 * @constructor
 * @parma {string} name The unique name of the applicaiton to load
 */
function AntApp(name)
{
	/**
	* The system name of this app
	*
	* @param {string} name Unique ID of this app
	*/
    this.name = name;	

	/**
	* The human readable title of this app
	*
	* @default "Untitled"
	*/
    this.title = "Untitled";

	/**
	* The human readable short title of this app
	*
	* @default "Untitled"
	*/
    this.shortTitle = "Untitled";

	/**
	* Reference to AntView which is required in main
	*
	* @default null
	*/
    this.view = null;

	/**
	* Link to the rood node of the xml definition for this app
	*
	* @default null
	*/
    this.xmlDef = null;

	/**
	* View to load by default if in desktop mode
	*
	* @default null
	*/		
    this.defaultViewName = null; 

	/**
	 * Calendar view browser for my calendars
	 *
	 * @default null
	 * @type {AntCalendarBrowse}
	 */
	this.calendarBrowser = null;

	/**
	 * Currently visible object browser
	 *
	 * @default null
	 * @type {AntObjectBrowser}
	 */
	this.currentObjectBrowser = null;

	/**
	 * Currently visible dashboard
	 *
	 * @default null
	 * @type {AntAppDash}
	 */
	this.currentDashBoard = null;

	/**
	* Select Type of view for this app
	*
	* @param {string} "desktop" or "mobile"
	* @default "desktop"
	*/		
    this.clientMode = "desktop";
    this.refObjects = new Array(); // {name, type}
    this.refCalendars = new Array();
	this.hidenav="f";
    this.myCalendarView = null;	// Separate view used for calendar
    this.isNewWin = false;      // determines whether the application is viewed in new window

	/**
	 * Array of processes waiting to load before this application has finished loading
	 *
	 * There are a number of asynchronous calls made in the navigation that must be completed
	 * before any item view is called. For instance, if a grouping is still loading, you will
	 * not be able to pathe to the browse by group until it finished.
	 *
	 * @type {string}
	 * @private
	 */
    this.runningProcesses = new Array();	

	/**
	 * Left navigation bar
	 *
	 * @default null
	 * @type {CNavBar}
	 */
	this.navBar = null;

	/**
	 * List of sections for the navbar
	 *
	 * @default null
	 * @type {CNavBarSection}
	 */
	this.navBarSections = new Array();

	/**
	 * Generic object used by calling scrpts to store data for callback functions like onload
	 *
	 * @type {Object}
	 */
	this.cbData = new Object();

	/**
	 * Settings string can be 'none' to hide settings button, blank for default or a plugin class name
	 *
	 * @type {string}
	 */
	this.settings = "";

	/**
	 * Name of welcome tour to use for this application
	 *
	 * @type {string}
	 */
	this.tour = "";
}

/**
* Run application and build interface
*/
AntApp.prototype.main = function(view)
{
    this.view = view;
    this.loadAppDef();	
}


/**
* Set this process as currently loading. This will delay the calling of the loaded function
*
* @private
* @param {string} name The unique name of the process that is running - can be anything unique
*/
AntApp.prototype.setLoading = function(name)
{
	var found = false;

	// First check to see if this process already exists
	for (var i = 0; i < this.runningProcesses.length; i++)
	{
		if (this.runningProcesses[i] == name)
			found = true;
	}

	if (!found)
		this.runningProcesses[this.runningProcesses.length] = name;
}

/**
 * Set this process as currently loading. 
 *
 * Once all processes are finsihed loading, then the this.loaded function will be called
 * to indicate that the application is loaded and ready for work - such as loading items or views
 *
 * @private
 * @param {string} name The unique name of the process that is running - can be anything unique
 */
AntApp.prototype.setFinishedLoading = function(name)
{
	// Loop through running process array and remove if found
	for (var i = 0; i < this.runningProcesses.length; i++)
	{
		if (this.runningProcesses[i] == name)
			this.runningProcesses.splice(i, 1);
	}

	// If there are no more running processes, then call loaded
	if (this.runningProcesses.length==0)
		this.loaded();
}

/**
 * Internal function to call once the application is loaded
 *
 * @param bool noInterface Set to true if you want to skip building the interface
 */
AntApp.prototype.loaded = function(noInterface)
{
	var noint = (typeof noInterface != "undefined") ? noInterface : false;

	if (noint == false)
	{
		if (this.clientMode == "desktop" && this.defaultViewName)
			this.view.setDefaultView(this.defaultViewName);

		this.view.setViewsLoaded();

		this.resize();
	}

    this.onload();

	if (typeof Ant != "undefined")
		Ant.updateAppTitle();
}
AntApp.prototype.onload = function() {} // overload me

/**
* Get application definition
*
* @param bool buildInterface Set to true if you want to skip building the interface
*/
AntApp.prototype.loadAppDef = function(noInterface)
{
	var noint = (typeof noInterface != "undefined") ? noInterface : false;

	this.view.con.innerHTML = "<div class='loading'></div>";

    var ajax = new CAjax();
    ajax.appObj = this;
	ajax.cbData.noint = noint;
    ajax.onload = function(xmlroot)
    {
		// Clear loading status
		this.appObj.view.con.innerHTML = "";

		this.appObj.xmlDef = xmlroot;
        this.appObj.title = unescape(xmlroot.getAttribute("title"));
        this.appObj.shortTitle = unescape(xmlroot.getAttribute("short_title"));
        this.appObj.name = unescape(xmlroot.getAttribute("name"));
        this.appObj.scope = unescape(xmlroot.getAttribute("scope"));
        this.appObj.isSystem = unescape(xmlroot.getAttribute("isSystem"));
        this.appObj.userId = unescape(xmlroot.getAttribute("userId"));
        this.appObj.teamId = unescape(xmlroot.getAttribute("teamId"));
        this.appObj.settings = unescape(xmlroot.getAttribute("settings"));
        this.appObj.tour = unescape(xmlroot.getAttribute("tour"));

        /**
		* Get default navigation view
		*/
        var navnode = xmlroot.getChildNodeByName("navigation");
        if (navnode)

	        this.appObj.defaultViewName = unescape(navnode.getAttribute("default"));
			this.appObj.hidenav = unescape(navnode.getAttribute("hidenav"));


        /**
		* Get referenced objects
		*/
        var objects = xmlroot.getChildNodeByName("objects");
        if (objects)
   		{
            for (var j = 0; j < objects.getNumChildren(); j++)
                {
                var obj = objects.getChildNode(j);
                this.appObj.refObjects[this.appObj.refObjects.length] = {
                    name:unescape(obj.getAttribute("name")), 
                    title:unescape(obj.getAttribute("title")), 
                    fSystem:((obj.getAttribute("system")=='t')?true:false) // is a system object
                };
            }
        }

        // Get referenced calendars 
        var calendars = xmlroot.getChildNodeByName("calendars");
        if (calendars)
        {
            for (var j = 0; j < calendars.getNumChildren(); j++)
                {
                var cal = calendars.getChildNode(j);
                this.appObj.refCalendars[this.appObj.refCalendars.length] = {id:cal.getAttribute("id"), name:unescape(cal.getAttribute("name"))};
            }
        }
        
		if (!this.cbData.noint)
	    	this.appObj.buildInterface(); // once finished the this.loaded will be called
		else
			this.appObj.loaded(noint);
    };
	var url = "/applications/xml_getappdef.php?app="+this.name;
	ajax.exec(url);
}

/**
* Callback to be overridden
**/
AntApp.prototype.onload = function()
{
}

/**
* Create or print interface
*/
AntApp.prototype.buildInterface = function()
{
	// Set human readable title for this view
    this.view.title = this.shortTitle; //this.name.toUpperCase();
    switch (this.clientMode)
    {
        case 'mobile':
            this.buildInterfaceMobile();
            break;
        case 'desktop':
        default:
            this.buildInterfaceDesktop();
            break;
    }
}

/**
* Create or print mobile version of interface
*/
AntApp.prototype.buildInterfaceMobile = function()
{
	// Let application know we have started loading the navigation
	this.setLoading("main_mobile_navigation"); 

	/**
	* Create function to set application title
	*/
    this.view.bindOnViewLoad(function(opts, remPath) { opts.cls.setMobileApplicationTitle(remPath); }, { cls:this });

    this.view.setViewsToggle(true); // Subviews all toggle - if one is visible, the others are hidden

    this.appOuter = this.view.con;
    this.appTitle = null;

    this.appNav = alib.dom.createElement("div", this.appOuter);
    this.appMain = null;

	/** 
	* Get navigation
	*/
    var navnode = this.xmlDef.getChildNodeByName("navigation");
    if (navnode)
        this.buildMobileNavigation(navnode);

	// inform the app that the main thread for loading the navigation is finished
	this.setFinishedLoading("main_mobile_navigation");
}

/**
* Create nagivation from definition
*/
AntApp.prototype.buildMobileNavigation = function(navnode, secDiv)
{
    for (var i = 0; i < navnode.getNumChildren(); i++)
        {
        var childNode = navnode.getChildNode(i);

        switch (childNode.m_name)
        {
            case "section":
                //var secItems = alib.dom.createElement("section", this.appNav);

                var secTitle = alib.dom.createElement("article", this.appNav);
                alib.dom.styleSetClass(secTitle, "nav");
                secTitle.innerHTML = "<a><h2 class='navSecTitle'>"+childNode.getAttribute("title")+"</h2></a>";

                this.buildMobileNavigation(childNode, this.appNav);
                break;
            case "item":
                this.addNavMobileItem(secDiv, childNode);
                break;
        }
    }
}

/**
* Create or print desktop version of interface
*/
AntApp.prototype.buildInterfaceDesktop = function()
{
	// Let application know we have started loading the navigation
	this.setLoading("main_desktop_navigation"); 

	// Subviews all toggle - if one is visible, the others are hidden
    this.view.setViewsToggle(true); 

	// Create application title container
    this.titleCon = alib.dom.createElement("div", this.view.con);
    //this.titleCon.id = "apptitle";
    alib.dom.styleSetClass(this.titleCon, "apptitle");    
    this.view.bindOnViewLoad(function(opts, remPath) { 
			opts.cls.setDesktopeApplicationTitle(remPath); 
			opts.cls.setDesktopNavPathSate(remPath); 
		}, { cls:this });    

	// New window con
    var newWinCon = alib.dom.createElement("div", this.titleCon);
    alib.dom.styleSet(newWinCon, "float", "right");
    //alib.dom.styleSet(newWinCon, "margin-top", "-20px");
    //alib.dom.divClear(this.titleCon);

	// Populate title h1
    var ttl = alib.dom.createElement("h1", this.titleCon);
    ttl.innerHTML = this.title;

	// joe, 10-15-2013
	// Note: breadcrumbs are now part of the object loader rather than the application title
    //this.titleCon.bcCon = alib.dom.createElement("div", this.titleCon);
    //alib.dom.styleSet(this.titleCon.bcCon, "float", "left");
	
    this.view.onchange = function(path) { /*this.options.titleCon.innerHTML = path;*/ };    

	// Create application layout divs
	var appNav = alib.dom.createElement("div", this.view.con);
	alib.dom.styleSetClass(appNav, "appInstNav");

	// Main container
	this.appMain = alib.dom.createElement("div", this.view.con);
	alib.dom.styleSetClass(this.appMain, "appInstBody");
	if(this.hidenav=="t")
	{
		alib.dom.styleSet(appNav, "display", "none");
	}
	else
	{
		alib.dom.styleAddClass(this.appMain, "appInstBodyFloat");
	}

    var nb = new CNavBar();
	var navnode = this.xmlDef.getChildNodeByName("navigation");
	var childnode = navnode.getChildNodeByName("section");
	var mainnode = childnode.getChildNodeByName("item");
    
	nb.print(appNav);

	/* Commented out because it was messy. If we need to hide nav later it will need to be reviewed
	if(this.hidenav!="t")
	    nb.print(appNav);
    else
    {
        var viewItem = this.view.addView(name, nb, this.appMain);
        viewItem.options.dashboard = this.name + "." + mainnode.getAttribute("name"); // appname.navname        
        viewItem.render = function()
        {
            var dashboardObject = new CAntObject_Dashboard();
            dashboardObject.appNavname = this.options.dashboard;
            dashboardObject.print(this.con);                
        }
        viewItem.show();
		viewItem.onresize = function()
		{
			alib.dom.styleSet(this.con, "max-height", (getWorkspaceHeight() - 10) + "px");
			alib.dom.styleSet(this.con, "overflow", "auto");
		}
	}
	*/

	/**
	* Get Navigation
	*/
    var navnode = this.xmlDef.getChildNodeByName("navigation");
    if (navnode)
        this.buildDesktopNavigation(navnode, nb);

	// inform the app that the main thread for loading the navigation is finished
	this.setFinishedLoading("main_desktop_navigation");

	this.navBar = nb;

	// Add application settings
	var viewItem = this.view.addView("settings", {}, this.appMain);
	viewItem.options.app = this;
	viewItem.options.settings = this.settings;
	viewItem.render = function() 
	{
		this.con.innerHTML = "";


		// Load default settings
		if (!this.options.settings)
		{
			var appSettings = new AntAppSettings(this.options.app);
			appSettings.cbData.antView = this;
			appSettings.onclose = function() { this.cbData.antView.goup(); }
			appSettings.print(this.con);
		}
		else if (this.options.settings) 
		{
			// Load plugin
			this.con.innerHTML = "";
			this.options.app.loadPlugin(this.options.settings, this);
		}
	}
	// Show settings button
	var btn = alib.ui.Button("<img src='/images/icons/settings_16.png' />", {
		className:"b1", tooltip:"View settings for this application", view:this.view,
		onclick:function() 
		{
			this.view.navigate("settings");
		}
	});
	if (this.settings != "none")
		btn.print(newWinCon);

	// Show open in new window
    if(!this.isNewWin)
    {
        var btn = alib.ui.Button("<img src='/images/icons/new_window_16.png' />", 
        {
            className:"b1", tooltip:"Open this application to new window",
            onclick:function() 
            {
                var hash = document.location.hash;
                var parts = hash.split("/");
                
                window.open('http://' + window.location.host + '/app.php?app=' + parts[0].substring(1) + hash);
            }
        });
        btn.print(newWinCon);
    }

	// Add tour
	if (this.tour)
	{
		var tourCon = alib.dom.createElement("div", appNav, "<div data-tour='" + this.tour + "' data-tour-type='dialog'></div>");
		alib.dom.styleSet(tourCon, "display", 'none');
		Ant.HelpTour.loadTours(tourCon);
	}
}

/**
* Create navigation from definition
*/
AntApp.prototype.buildDesktopNavigation = function(navnode, navbar, sec)
{
	
	for (var i = 0; i < navnode.getNumChildren(); i++)
	{
		var childNode = navnode.getChildNode(i);
		switch (childNode.m_name)
		{
			case "section":
				var sec = navbar.addSection(unescape(childNode.getAttribute("title")));
				this.buildDesktopNavigation(childNode, navbar, sec);
				this.navBarSections[this.navBarSections.length] = sec;
				break;
			case "item":
				this.addNavDesktopItem(sec, childNode, navbar);
				break;
		}
	}
}

/**
* Add an item to a section of the main app navigation
*/
AntApp.prototype.addNavDesktopItem = function(sec, childNode, navbar)
{
	/**
	* Get common attributes
	*/
    var name = unescape(childNode.getAttribute("name"));
    var icon = unescape(childNode.getAttribute("icon"));
    var title = unescape(childNode.getAttribute("title"));
	var type =unescape(childNode.getAttribute("type"));

    if (!title)
        title = name;
	/** 
	* @param {string} name Required name for the childnode.
	*/
    if (!name) 
        return false;

    switch (type)
    {
	case 'dashboard':
		var secItem = sec.addItem(title, icon, 
			function(view, name){ view.navigate(name); }, 
			[this.view, name], name);

		var viewItem = this.view.addView(name, {nb:navbar}, this.appMain);
		viewItem.options.dashboard = this.name + "." + name; // appname.navname			
		viewItem.render = function()
		{
			var ajax = new CAjax('json');
			ajax.cbData.con = this.con;
			ajax.cbData.view = this;
			ajax.onload = function(ret)
			{
				var ol = new AntObjectLoader("dashboard", ret);
				ol.fEnableClose = false;
				ol.setAntView(this.cbData.view);
				ol.print(this.cbData.con);
			};        
			ajax.exec("/controller/Dashboard/loadAppDashForUser", [["dashboard_name", this.options.dashboard]]);
		}
		/*
		viewItem.onshow = function() 
		{
			//this.options.nb.itemChangeState(this.name, "on"); 				
		};

		viewItem.onresize = function()
		{
			alib.dom.styleSet(this.con, "max-height", (getWorkspaceHeight()) + "px");
			alib.dom.styleSet(this.con, "overflow", "auto");
		}
		*/
		
		break;

	case 'dashboards':

		var ajax = new CAjax('json');
		ajax.cbData.sec = sec;
		ajax.cbData.view = this.view;
		ajax.cbData.name = name;
		ajax.cbData.appCls = this;
		ajax.onload = function(ret)
		{
			var icon = "/images/icons/objects/dashboard_16.png";

			for(i in ret)
			{

				var secItem = this.cbData.sec.addItem(ret[i].name, icon, 
											function(view, name){ view.navigate(name); }, 
											[this.cbData.view, ret[i].uname], ret[i].uname);

				var viewItem = this.cbData.view.addView(ret[i].uname, {dbid:ret[i].id}, this.cbData.appCls.appMain);
				viewItem.render = function()
				{
					var ol = new AntObjectLoader("dashboard", this.options.dbid);
					ol.setAntView(this);
					ol.fEnableClose = false;
					ol.print(this.con);
				}

				/*
				viewItem.onresize = function()
				{
					alib.dom.styleSet(this.con, "max-height", (getWorkspaceHeight()) + "px");
					alib.dom.styleSet(this.con, "overflow", "auto");
				}
				*/
			}
		};
		ajax.exec("/controller/Dashboard/loadDashboards");

		break;

	case 'link':
		var url = unescape(childNode.getAttribute("url"));
		sec.addItem(title, icon, 
						function(url){ window.open(url); }, 
						[url, name], -1);
		break;

	case 'browse':
		var secItem = sec.addItem(title, icon, 
								  function(view, name){ view.navigate(name); }, 
								  [this.view, name], name);
		var viewItem = this.view.addView(name, {nb:navbar}, this.appMain);
		viewItem.options.type = childNode.getAttribute("obj_type");
		viewItem.options.preview = childNode.getAttribute("preview");
		viewItem.options.childNode = childNode;
		viewItem.options.appCls = this;
		viewItem.render = function()
		{
			var ob = new AntObjectBrowser(this.options.type);
			ob.setAntView(this);
			if (this.options.preview == "t")
				ob.preview = true;

			// Check for filter conditions
			var filter = this.options.childNode.getChildNodeByName("filter")
			if (filter)
			{
				for (var i = 0; i < filter.getNumChildren(); i++)
				{
					var cond = filter.getChildNode(i);
					var blogic = cond.getAttribute("blogic");
					var field = cond.getAttribute("field");
					var operator = cond.getAttribute("operator");
					var value = cond.getAttribute("value");
					ob.setFilter(field, value);
				}
			}

			// Check for activity type and set associations filter
			if (this.options.type == "activity")
			{
				for (var i = 0; i < this.options.appCls.refObjects.length; i++)
				{
					var op = (i > 0) ? "or" : "and";
					ob.addCondition(op, "type_id", "is_equal", this.options.appCls.refObjects[i].title);
				}
			}

			ob.print(this.con);
			this.options.browser = ob;
		}
		viewItem.onshow = function() { this.options.appCls.currentObjectBrowser = this.options.browser; /*this.options.nb.itemChangeState(this.name, "on");*/ };
		viewItem.onhide = function() { this.options.appCls.currentObjectBrowser = null; }


		/**
		* Check for browsable filtering based on fkey - used for things like groups and status keys
		*/
		var filterField = childNode.getAttribute("browseby");
		if (filterField)
		{
			var dm = new alib.ui.PopupMenu();

			var item = new alib.ui.MenuItem("Add New Subgroup", {icon:"<img src='/images/icons/add_10.png' />"});
			item.cbData.cls = this;
			item.cbData.obj_type = viewItem.options.type;
			item.cbData.filterField = filterField;
			item.cbData.secItem = secItem;
			item.onclick = function() { this.cbData.cls.addGrouping(this.cbData.obj_type, this.cbData.filterField, "", this.cbData.secItem, true); }
			dm.addItem(item);

			var ocon = secItem.getOptionCon();

			var img = alib.dom.createElement("img", ocon);
			img.src = "/images/icons/rightclick_off.gif";
			dm.attach(img);
			/*
			ocon.appendChild(dm.createImageMenu("/images/icons/rightclick_off.gif", 
												"/images/icons/rightclick_over.gif", 
												"/images/icons/rightclick_on.gif"));
												*/

			var grpdata = new Object();
			grpdata.obj_type = viewItem.options.type; 
			this.getGroupings(grpdata, filterField, secItem);
		}

		break;

	case 'folder':

		var secItem = sec.addItem(title, icon, 
		function(view, name){ view.navigate(name); }, 
		[this.view, name], name);
		var viewItem = this.view.addView(name, {nb:navbar}, this.appMain);
		viewItem.options.path = childNode.getAttribute("path");
		viewItem.render = function()
		{
			this.con.innerHTML = "";

			//alib.dom.styleSet(this.con, "max-height", (getWorkspaceHeight() - 10) + "px");
			//alib.dom.styleSet(this.con, "overflow", "auto");

			var browser = new AntObjectBrowser("file");
			//browser.optForceFullRefresh = true; // update refresh has a bug with folders for now
			browser.setBrowseBy("folder_id", this.options.path);
			browser.setAntView(this);
			browser.print(this.con);

			/*
			var br = new CFileBrowser();
			br.setAntView(this);
			br.setPath(this.options.path);
			br.print(this.con);
			*/
		}

		break;

	case 'object_newwin':
		sec.addItem(title, icon, 
					function(obj_type) {  loadObjectForm(obj_type); }, [childNode.getAttribute("obj_type")], -1);
		break;

	case 'object':
		sec.addItem(title, icon, 
						function(view, name){ view.navigate(name); }, 
						[this.view, name], name);
		var viewItem = this.view.addView(name, {nb:navbar, appcls:this}, this.appMain);
		viewItem.options.type = childNode.getAttribute("obj_type");
		viewItem.render = function() { }
		viewItem.onshow = function()  // draws in onshow so that it redraws every time
		{ 
			this.con.innerHTML = "";

			var ol = new AntObjectLoader(this.options.type);
			ol.setAntView(this);
			ol.print(this.con);
			ol.cbData.antView = this;
			ol.cbData.appcls = this.options.appcls;
			ol.onClose = function()
			{
				this.cbData.antView.goup();
			}
			ol.onRemove = function()
			{
			}
			ol.onSave = function()
			{
				// Trigger object saved event for app
				alib.events.triggerEvent(this.cbData.appcls, "objectsaved", {oid:this.oid, objType:this.objType, name:this.mainObject.getName()});
			}

			//this.options.nb.itemChangeState(this.name, "on"); 
		};
		break;

	case 'settings':
		sec.addItem(title, icon, 
					function(view, name){ view.navigate(name); }, 
					[this.view, name], name);
		var viewItem = this.view.addView(name, {nb:navbar}, this.appMain);
		viewItem.options.app = this;
		viewItem.render = function() 
		{
			this.con.innerHTML = "";

			var appSettings = new AntAppSettings(this.options.app);
			appSettings.cbData.antView = this;
			appSettings.onclose = function() { this.cbData.antView.goup(); }
			appSettings.print(this.con);
		}
		viewItem.onshow = function() { /*this.options.nb.itemChangeState(this.name, "on"); */ };
		break;

	case 'plugin':
		sec.addItem(title, icon, 
					function(view, name){ view.navigate(name); }, 
					[this.view, name], name);
					var viewItem = this.view.addView(name, {nb:navbar}, this.appMain);
		viewItem.options.app = this;
		viewItem.options.plugin = childNode.getAttribute("class");
		viewItem.render = function() 
		{
			this.con.innerHTML = "";
			this.options.app.loadPlugin(this.options.plugin, this);
		}
		viewItem.onshow = function() { /* this.options.nb.itemChangeState(this.name, "on"); */ };
		break;

	case 'calendar':
		sec.addItem(title, icon, 
					function(view, name){ view.navigate(name); }, 
					[this.view, name], name);
		var viewItem = this.view.addView(name, {nb:navbar}, this.appMain);
		viewItem.options.app = this;
		viewItem.options.calendar_id = childNode.getAttribute("id");	// Get calendar_id
		viewItem.render = function() 
		{
			this.con.innerHTML = "";

			// Load item calendar
			var caldv = alib.dom.createElement("div", this.con);
			this.options.cls.calendarBrowser = new AntCalendarBrowse(this.options.cls);
			this.options.cls.calendarBrowse.setAntView(caldv);
			this.options.cls.calendarBrowse.print(this);
		}
		viewItem.onshow = function() { }
		break;

	case 'myminical':
		// Check if Calendar view is defined
		if(this.myCalendarView == null)
		{
			this.myCalendarView = this.view.addView("mycalendar", {}, this.appMain);
			this.myCalendarView.options.cls = this;
			this.myCalendarView.options.nav = navbar;
			this.myCalendarView.render = function() 
			{ 
				this.con.innerHTML = "";
				var caldv = alib.dom.createElement("div", this.con);
				this.options.cls.calendarBrowser = new AntCalendarBrowse(this.options.cls);
				this.options.cls.calendarBrowser.setAntView(this);
				this.options.cls.calendarBrowser.print(caldv);
			}
			this.myCalendarView.onshow = function() 
			{ 
			}
		}
		this.cal_con = sec.addCon();
		this.calNav();
		break;

	case 'mycalendars':
		ajax = new CAjax('json');
		ajax.cls = this;
		ajax.sec = sec;
		ajax.onload = function(ret)
		{
			var icon = "/images/icons/calendar.png";
			
			for(calendar in ret.myCalendars)
				this.cls.insertCalendar(this.sec, ret.myCalendars[calendar]);
				
			this.cls.resize();
		};
		ajax.exec("/controller/Calendar/getCalendars");

		// Listen for new calendars and add to list
		alib.events.listen(this, "objectsaved", function(evt) { 
			if (evt.data.objType == "calendar")
				evt.data.appcls.insertCalendar(evt.data.nbsection, {id:evt.data.oid, name:evt.data.name, f_view:'t'});
		}, {appcls:this, nbsection:sec});
		break;

	case 'myothercals':
		ajax = new CAjax('json');
		ajax.cls = this;
		ajax.sec = sec;
		ajax.onload = function(ret)
		{
			var icon = "/images/icons/calendar.png";
			
			for(calendar in ret.otherCalendars)
				this.cls.insertCalendar(this.sec, ret.otherCalendars[calendar]);
				
			this.cls.resize();
		};
		ajax.exec("/controller/Calendar/getCalendars");
		break;

	case 'myminiprofile':
		if (typeof Ant != "undefined")
		{
			var pcon = alib.dom.createElement("div", sec.addCon());
			alib.dom.styleSet(pcon, "margin", "10px 0px 0px 3px");

			var imgcon = alib.dom.createElement("div", pcon);
			alib.dom.styleSet(imgcon, "width", "48px");
			alib.dom.styleSet(imgcon, "height", "48px");
			alib.dom.styleSet(imgcon, "float", "left");
			imgcon.innerHTML = "<img src='/files/userimages/current/48/48' style='width:48px;' />";

			var namecon = alib.dom.createElement("div", pcon);
			alib.dom.styleSet(namecon, "margin-left", "58px");
			alib.dom.styleSet(namecon, "padding-top", "15px");
			alib.dom.styleSet(namecon, "height", "33px"); // make sure it is the same height to handle float issues
			namecon.innerHTML = "<a href='#settings' class='boldLink'>" + Ant.user.fullName + "</a>";
		}
		break;

	case 'recentobjects':
		var ajax = new CAjax('json');
		ajax.cbData.sec = sec;
		ajax.onload = function(ret)
		{
			for(i in ret)
			{
				var icon = (ret[i].icon) ? 
					ret[i].icon : 
					"/images/icons/objects/generic_16.png";

				this.cbData.sec.addItem(ret[i].name, icon, function(obj_type, oid) {
											loadObjectForm(obj_type, oid);
										}, [ret[i].obj_type, ret[i].id], -1);
			}
		};
		ajax.exec("/controller/Object/getRecent");
		break;
		
	case "wizard":
		sec.addItem(title, icon, 
					function(wiz_type)
					{
						var wiz = new AntWizard(wiz_type);
						wiz.show(); 
					}, [childNode.getAttribute("wiz_type")], -1);
		
		break;

	default: // Do nothing but print in navbar for debugging
		sec.addItem(title, "/images/icons/plus.png", 
		function(){ }, 
		[], -1);
		break;
    }
}

/**
* Add an item to a section of the main app navigation
*/
AntApp.prototype.addNavMobileItem = function(secDiv, childNode)
{
    // Get common attributes
    var name = childNode.getAttribute("name");
    var icon = childNode.getAttribute("icon");
    var title = unescape(childNode.getAttribute("title"));
    var type = childNode.getAttribute("type");
    if (!title)
        title = name;
	/**
	* @param {string} name Required name of childnode.
	*/
    if (!name)
        return false;

    var entry = alib.dom.createElement("article", secDiv);
    alib.dom.styleSetClass(entry, "nav");
    switch (type)
    {
        case 'link':
            var url = childNode.getAttribute("url");
            entry.innerHTML = "<a target='_blank' href=\""+url+"\">"
            + "<span class='icon'><img src='"+icon+"' /></span><h2><span class='more'></span>"+title+"</h2></a>";
            break;
        case 'browse':
			entry.innerHTML = "<a behavior='selectable' href=\"#"+this.view.getPath()+"/"+name+"\" onclick=\"alib.dom.styleAddClass(this, 'selected');\">"
			+ "<span class='icon'><img src='"+icon+"' /></span><h2><span class='more'></span>"+title+"</h2></a>";
			entry.view = this.view;
			entry.name = name;
			//entry.onclick = function() { this.view.navigate(this.name); };

			var viewItem = this.view.addView(name, {});
			viewItem.options.type = childNode.getAttribute("obj_type");
			viewItem.options.childNode = childNode;
			viewItem.options.appCls = this;
			viewItem.render = function()
			{
				var ob = new AntObjectBrowser(this.options.type);
				ob.mobile = true;
				ob.setAntView(this);

				// Check for filter conditions
				var filter = this.options.childNode.getChildNodeByName("filter")
				if (filter)
				{
					for (var i = 0; i < filter.getNumChildren(); i++)
					{
						var cond = filter.getChildNode(i);
						var blogic = cond.getAttribute("blogic");
						var field = cond.getAttribute("field");
						var operator = cond.getAttribute("operator");
						var value = cond.getAttribute("value");
						ob.setFilter(field, value);
					}
				}

				ob.print(this.con);
			}
			viewItem.onshow = function() { this.options.appCls.currentObjectBrowser = this.options.browser; };
			viewItem.onhide = function() { this.options.appCls.currentObjectBrowser = null; }

			/**
			* Check for browsable filtering based on fkey - used for things like groups and status keys
			*/
			var filterField = childNode.getAttribute("browseby");
			if (filterField)
			{
				var grpdata = new Object();
				grpdata.obj_type = viewItem.options.type; 
				this.getGroupings(grpdata, filterField, null, secDiv);
			}

        break;

        case 'object':
            entry.innerHTML = "<a behavior='selectable' href=\"#"+this.view.getPath()+"/"+name+"\" onclick=\"alib.dom.styleAddClass(this, 'selected');\">"
            + "<span class='icon'><img src='"+icon+"' /></span><h2><span class='more'></span>"+title+"</h2></a>";

            var viewItem = this.view.addView(name, {});
            viewItem.options.type = childNode.getAttribute("obj_type");
            viewItem.render = function() { }
            viewItem.onshow = function()  // draws in onshow so that it redraws every time
            { 
                this.con.innerHTML = "";

                var ol = new AntObjectLoader(this.options.type);
                ol.print(this.con);
                ol.curView = this;
                ol.onClose = function() { }
                ol.onRemove = function() { }
            };
            break;

        case 'folder':
            entry.innerHTML = "<a behavior='selectable' href=\"#"+this.view.getPath()+"/"+name+"\" onclick=\"alib.dom.styleAddClass(this, 'selected');\">"
            + "<span class='icon'><img src='"+icon+"' /></span><h2><span class='more'></span>"+title+"</h2></a>";

            var viewItem = this.view.addView(name, {});
            viewItem.options.path= childNode.getAttribute("path");
            viewItem.render = function() { }
			/**
			* draws in onshow so that it redraws every time
			*/
            viewItem.onshow = function()  
            { 
                this.con.innerHTML = "";

                var browser = new AntObjectBrowser("file");
				browser.optForceFullRefresh = true; // update refresh has a bug with folders for now
				browser.setBrowseBy("folder_id", this.options.path);
				browser.setAntView(this);
				browser.print(this.con);
            };
            break;

        default: // Do nothing
            break;
    }
}

/**
* Get filter field values - used for groups / status codes
* @param {object} grpdata Type of object is in grpdata.obj_type.
* @param {string} field_name Name of field in object.
* @param {string} parentNavItem
* @param {string} secDiv
*/
AntApp.prototype.getGroupings = function(grpdata, field_name, parentNavItem, secDiv)
{
	// Add this async process to the queue so the application knows when it is finished
	this.setLoading("get_grouping_" + "_" + grpdata.obj_type + "_" + field_name);

	ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.cbData.parentNavItem = (parentNavItem) ? parentNavItem : null;
    ajax.cbData.secDiv = (secDiv) ? secDiv : null;
    ajax.cbData.grpdata = grpdata;
    ajax.cbData.field_name = field_name;
	ajax.onload = function(groupings)
	{
		if (groupings.length)
		{
            if (this.cbData.cls.clientMode == "mobile")
			{
                this.cbData.cls.addGroupingsMobile(this.cbData.grpdata.obj_type, this.cbData.field_name, groupings, this.cbData.secDiv);
            }
            else
			{
                this.cbData.cls.addGroupings(this.cbData.grpdata.obj_type, this.cbData.field_name, groupings, this.cbData.parentNavItem);
                this.cbData.parentNavItem.expand(); // Open first level
            }
		}

		// Inform application we are finished building the navigation
		this.cbData.cls.setFinishedLoading("get_grouping_" + "_" + this.cbData.grpdata.obj_type + "_" + this.cbData.field_name);
	};
	ajax.exec("/controller/Object/getGroupings", 
			  [["obj_type", grpdata.obj_type], ["field", field_name]]);
}

/**
* Put item into the nav bar
*/
AntApp.prototype.addGroupings = function(obj_type, field_name, node, parentNavItem, level, new_group)
{
    //var icon = "/images/themes/"+Ant.m_theme+"/icons/newnote_small.png";
    if (!level)
        var level = 0;

    for (var c = 0; c < node.length; c++)
	{
        var child = node[c];


		var id = child.id;
		var title = child.title;
		var parent_id = child.parent_id;
		var color = child.color;
		var f_system = child.system;
		var viewName = child.viewname;

		// Hide system groupings - joe
		// We did this because inbox, drafts etc., are not in the application nav grouping than browseby
		if (f_system)
			continue;

		// Create color div
		var clr = alib.dom.createElement("div");
		alib.dom.styleSet(clr, "width", "14px");
		alib.dom.styleSet(clr, "height", "10px");
		alib.dom.styleSet(clr, "margin-top", "3px");
		alib.dom.styleSet(clr, "background-color", "#"+((color)?color:G_GROUP_COLORS[0][1]));
		ALib.Effect.round(clr, 2);

		var viewItem = this.view.addView(viewName, 
										{
											type:obj_type, 
											field_name:field_name, 
											filter_id:id, 
											f_system:f_system, 
											title:title, 
											appCls:this
										}, 
									    this.appMain);
		viewItem.render = function()
		{
			this.con.innerHTML = "";
			var ob = new AntObjectBrowser(this.options.type);
			ob.cbData.groupingId = this.options.filter_id; // store this for custom actions
			ob.setAntView(this);

			// If this is a deleted items grouping then set query
			if (this.options.f_system && ("Trash"==this.options.title || "Deleted Items"==this.options.title))
				ob.addCondition("and", 'f_deleted', 'is_equal', 't');
			else
				ob.setFilter(this.options.field_name, this.options.filter_id);

			ob.print(this.con);

			this.options.browser = ob;
		}
		
		viewItem.onshow = function() 
		{ 
			this.options.appCls.currentObjectBrowser = this.options.browser; 
			// Refresh is now automatic in the object browser
			//this.options.browser.setAutoRefresh(10000); // Refresh active list every 10 seconds
		};

		viewItem.onhide = function() 
		{ 
			this.options.appCls.currentObjectBrowser = null; 
			//this.options.browser.setAutoRefresh(null); // Disable. UPDATE: hendled in the browser
		}
        
		var entry = parentNavItem.addItem(title, clr, 
										 function(view, name){ view.navigate(name); }, 
										 [this.view, viewName], viewName);

		var lblcon = entry.getLabelCon();
		var ocon = entry.getOptionCon();

		var dm = new alib.ui.PopupMenu();

		// Add change color
		var submenu = new alib.ui.SubMenu("Change Color");
		for (var j = 0; j < G_GROUP_COLORS.length; j++)
		{
			/*
			colorent.addEntry(G_GROUP_COLORS[j][0], 
						function(cls, obj_type, field_name, clrdv, clr, gid)
						{cls.changeGroupingColor(obj_type, field_name, clrdv, clr, gid);}, null, 
						"<div style='width:9px;height:9px;background-color:#" + G_GROUP_COLORS[j][1] + "'></div>",
						[this, obj_type, field_name, clr, G_GROUP_COLORS[j][1], id]);

			*/
			var item = new alib.ui.MenuItem(G_GROUP_COLORS[j][0], {
				icon:"<div style='width:10px;height:10px;background-color:#" + G_GROUP_COLORS[j][1] + "'></div>"
			});
			item.cbData.cls = this;
			item.cbData.obj_type = obj_type;
			item.cbData.filterField = field_name;
			item.cbData.clrdv = clr;
			item.cbData.clr = G_GROUP_COLORS[j][1];
			item.cbData.gid = id;
			item.onclick = function() { 
				this.cbData.cls.changeGroupingColor(this.cbData.obj_type, this.cbData.field_name, this.cbData.clrdv, this.cbData.clr, this.cbData.gid);
			};
			submenu.addItem(item);
		}
		dm.addItem(submenu);

		// Add new Subgroup
		var item = new alib.ui.MenuItem("Add New Subgroup", {icon:"<img src='/images/icons/add_10.png' />"});
		item.cbData.cls = this;
		item.cbData.obj_type = obj_type;
		item.cbData.filterField = field_name;
		item.cbData.secItem = entry;
		item.cbData.gid = id;
		item.onclick = function() { 
			this.cbData.cls.addGrouping(this.cbData.obj_type, this.cbData.filterField, this.cbData.gid, this.cbData.secItem, true); 
		};
		dm.addItem(item);

		/*
		dm.addEntry("Add New Subgroup", function(cls, obj_type, field_name, gid, pnode) { cls.addGrouping(obj_type, field_name, gid, pnode, true); }, 
					"/images/icons/addStock.gif", null, [this, obj_type, field_name, id, entry]);
		*/

		if (!f_system)
		{
			var item = new alib.ui.MenuItem("Rename", {icon:"<img src='/images/icons/edit_10.png' />"});
			item.cbData.cls = this;
			item.cbData.obj_type = obj_type;
			item.cbData.field_name = field_name;
			item.cbData.gid = id;
			item.cbData.title = title;
			item.cbData.lblcon = lblcon;
			item.onclick = function() { 
				this.cbData.cls.renameGrouping(this.cbData.obj_type, this.cbData.field_name, this.cbData.gid, this.cbData.title, this.cbData.lblcon);
			};
			dm.addItem(item);

			var item = new alib.ui.MenuItem("Delete", {icon:"<img src='/images/icons/delete_10.png' />"});
			item.cbData.cls = this;
			item.cbData.obj_type = obj_type;
			item.cbData.field_name = field_name;
			item.cbData.gid = id;
			item.cbData.title = title;
			item.cbData.entry = entry;
			item.onclick = function() { 
				this.cbData.cls.deleteGrouping(this.cbData.obj_type, this.cbData.field_name, this.cbData.gid, this.cbData.title, this.cbData.entry);
			};
			dm.addItem(item);
		}

		var img = alib.dom.createElement("img", ocon);
		img.src = "/images/icons/rightclick_off.gif";
		dm.attach(img);
		/*
		ocon.appendChild(dm.createImageMenu("/images/icons/rightclick_off.gif", 
											"/images/icons/rightclick_over.gif", 
											"/images/icons/rightclick_on.gif"));
		*/

		entry.appCls = this;
		entry.viewTitle = title;
		entry.groupingField = field_name;
		entry.objectType = obj_type;
		entry.groupingToId = id;
		entry.registerDropzone("dzNavbarDrop_" + obj_type);
		entry.onDragEnter = function(e)
		{
		}
		entry.onDragExit = function(e)
		{
		}
		entry.onDragDrop = function(e)
		{
			if (this.appCls.currentObjectBrowser)
			{
				if (this.appCls.currentObjectBrowser.cbData.groupingId)
				{
					var sendArgs = [["obj_type", this.objectType], ["field_name", this.groupingField], 
									["move_from", this.appCls.currentObjectBrowser.cbData.groupingId], ["move_to", this.groupingToId]];
					var act = {url:"/controller/Object/moveByGrouping", args:sendArgs, 
								refresh:true, doneMsg:"Moved to "+this.viewTitle, flush:true};
					this.appCls.currentObjectBrowser.actionCustom(act);
				}
			}
		}

		if (child.children && child.children.length)
        {
            this.addGroupings(obj_type, field_name, child.children, entry, ++level);
        }

		if (level)
			entry.collapse();
            
        if(new_group)
            parentNavItem.expand();
	}
}

/**
* Put item into the nav bar
*/
AntApp.prototype.addGroupingsMobile = function(obj_type, field_name, node, secDiv, level)
{
    if (!level)
        var level = 0;

    for (var c = 0; c < node.length; c++)
	{
        var child = node[c];

		var id = child.id;
		var title = child.title;
		var parent_id = child.parent_id;
		var color = child.color;
		var viewName = child.viewname;

		var viewItem = this.view.addView(viewName, {type:obj_type, field_name:field_name, filter_id:id, appCls:this});
		viewItem.render = function()
		{
			this.con.innerHTML = "";
			var ob = new AntObjectBrowser(this.options.type);
			ob.mobile = true;
			ob.setAntView(this);
			ob.setFilter(this.options.field_name, this.options.filter_id);
			ob.print(this.con);
			this.options.browser = ob;
		}
		viewItem.onshow = function() { this.options.appCls.currentObjectBrowser = this.options.browser; };
		viewItem.onhide = function() { this.options.appCls.currentObjectBrowser = null; }

		var entry = alib.dom.createElement("article", secDiv);
		alib.dom.styleSetClass(entry, "nav");

		var spacer = "";
		for (var s = 0; s < level; s++)
			spacer += "&nbsp;";

		entry.innerHTML = "<a behavior='selectable' href=\"#"+this.view.getPath()+"/"+viewName+"\" onclick=\"alib.dom.styleAddClass(this, 'selected');\">"
					    + "<span class='icon'></span><h2><span class='more'></span>" + spacer + title + "</h2></a>";

		if (child.children && child.children.length)
			this.addGroupingsMobile(obj_type, field_name, child.children, secDiv, ++level);
    }
}

/**
* The container was resized so we shoud redraw
*/
AntApp.prototype.resize = function()
{
	// Resize leftnav
	if (this.navBarSections.length)
	{
		var topHeight = 0;
		var total_height = getWorkspaceHeight();

		// Exclode the last section
		for (var i = 0; i < (this.navBarSections.length-1); i++)
		{
			topHeight += this.navBarSections[i].getHeight();
		}

		this.navBarSections[this.navBarSections.length-1].setMaxHeight(total_height-topHeight);
	}

	// Resize the current active view and all children
	this.view.resize(true);
	
	/*
	if (this.currentObjectBrowser)
		this.currentObjectBrowser.resize();

	if (this.calendarBrowser)
		this.calendarBrowser.resize();

	if (this.currentDashBoard)
		this.currentDashBoard.resize();
	*/
}

/**
* Change the color of a grouping field like category or status
*/
AntApp.prototype.changeGroupingColor = function(obj_type, field_name, clrdv, clr, gid)
{
	ajax = new CAjax('json');
	ajax.cbData.clrdv = clrdv;
	ajax.cbData.clr = clr;
	ajax.onload = function(ret)
	{
		if (ret)
		{
			alib.dom.styleSet(this.cbData.clrdv, "background-color", "#"+this.cbData.clr);
			ALib.Effect.round(this.cbData.clrdv, 2);
		}
	};
	ajax.exec("/controller/Object/setGroupingColor", 
			  [["obj_type", obj_type], ["field", field_name], ["color", clr], ["gid", gid]]);
}

/**
* Add a grouping field value
*/
AntApp.prototype.addGrouping = function(obj_type, field_name, pgid, pnode, new_group)
{
    var name = prompt('Enter a name for new subgroup', "New Group");

    if (!name)
        return;

	ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.cbData.pnode = pnode;
	ajax.cbData.obj_type = obj_type;
    ajax.cbData.field_name = field_name;
	ajax.cbData.new_group = new_group;
	ajax.onload = function(ret)
	{
		if (ret && ret.id)
		{
			var node = new Array(ret);
			this.cbData.cls.addGroupings(this.cbData.obj_type, this.cbData.field_name, node, this.cbData.pnode, null, this.cbData.new_group);
		}
	};
	ajax.exec("/controller/Object/createGrouping", 
			  [["obj_type", obj_type], ["field", field_name], ["parent_id", pgid], ["title", name]]);
}

/**
* Rename a grouping field value
*/
AntApp.prototype.renameGrouping = function(obj_type, field_name, gid, gname, lbldv)
{
    var ldiv = (typeof(lbldv) != "undefined") ? lbldv : null;

    var name = prompt('Enter a name for this group', gname);

    if (!name)
        return;

	ajax = new CAjax('json');
	ajax.cbData.lbldv = ldiv;
	ajax.cbData.title = name;
	ajax.onload = function(ret)
	{
		if (ret && this.cbData.lbldv)
		{
			this.cbData.lbldv.innerHTML = this.cbData.title;
		}
	};
	ajax.exec("/controller/Object/renameGrouping", 
			  [["obj_type", obj_type], ["field", field_name], ["title", name], ["gid", gid]]);
}

/**
* Delete a grouping field value
**/
AntApp.prototype.deleteGrouping = function(obj_type, field_name, gid, gname, gitem)
{
    ALib.Dlg.confirmBox("Are you sure you want to delete " + gname + "?", "Delete Group", [obj_type, field_name, gid, gitem]);
    ALib.Dlg.onConfirmOk = function(obj_type, field_name, gid, gitem)
    {
		ajax = new CAjax('json');
		ajax.cbData.gitem = gitem;
		ajax.onload = function(ret)
		{
			if (ret)
				this.cbData.gitem.deleteItem();
		};
		ajax.exec("/controller/Object/deleteGrouping", 
				  [["obj_type", obj_type], ["field", field_name], ["title", name], ["gid", gid]]);
    }
}

/**
* Used to set the application title for this.view and children
*/
AntApp.prototype.setMobileApplicationTitle = function(remPath, childView)
{
    var view = (childView) ? childView : this.view;

    if (view.conAppTitle)
    {
        view.conAppTitle.innerHTML = "<h1>" + this.title + "</h1>";
        alib.dom.styleSetClass(view.conAppTitle, "apptitle");
    }

    if (view.viewManager)
    {
        for (var i = 0; i < view.viewManager.views.length; i++)
        {
            this.setMobileApplicationTitle(remPath, view.viewManager.views[i]);
        }
    }
}

/**
* Used to create application breadcrumbs
*
* @param string remPath The path after this view [thisviewname]/sub/path would be "sub/path"
* @param AntView childView Optional view used rather than this.view to traverse tree
*/
AntApp.prototype.setDesktopeApplicationTitle = function(remPath, childView)
{
	// We no longer use these breadcrumbs
	// because the object loader handles bc
	return;

    if (!childView)
	{
        this.titleCon.bcCon.innerHTML = "";
    }
    else
	{
        var sp = alib.dom.createElement("span", this.titleCon.bcCon);
        sp.innerHTML = " > ";
    }

    var view = (childView) ? childView : this.view;
    var lnk = alib.dom.createElement("a", this.titleCon.bcCon);
    lnk.href = "#"+view.getPath();
    lnk.view = view;
    //lnk.onclick = function() { this.view.navigate(this.view.getPath()); }
    view.getTitle(lnk); // passing the element will bind it to be updated on title change

    /**
	* Now load active child views into title
	*/
    var nextViewName = remPath;
    var postFix = "";
    if (remPath.indexOf("/")!=-1)
  	{
        var parts = remPath.split("/");
        var nextViewName = parts[0];
        if (parts.length > 1)
            {
			/*
			* Skip of first which is current view
			*/
            for (var i = 1; i < parts.length; i++) 
                {
                if (postFix != "")
                    postFix += "/";
                postFix += parts[i];
            }
        }
    }

	// Traverse remaining views
    if (view.viewManager && nextViewName)
   	{
        for (var i = 0; i < view.viewManager.views.length; i++)
            {
            if (view.viewManager.views[i].nameMatch(nextViewName))
                this.setDesktopeApplicationTitle(postFix, view.viewManager.views[i]);
        }
    }
}

/**
* Set the current active navigation state based on view name
*
* @param string remPath The path after this view [thisviewname]/sub/path would be "sub/path"
*/
AntApp.prototype.setDesktopNavPathSate = function(remPath)
{
    if (remPath)
  	{
        var parts = remPath.split("/");
        var activeNav = parts[0];
		if (this.navBar)
			this.navBar.itemChangeState(activeNav, "on");
    }
}

/**
* Calendar navigator
*/
AntApp.prototype.calNav = function(year, month, day, sel)
{
    var todaydate=new Date() //DD added

    var year = (year) ? year : todaydate.getFullYear();
    var month = (month) ? month : todaydate.getMonth()+1;
    var day = (day) ? day : todaydate.getDate();

    var sel = (sel) ? sel : '';

    this.cal_con.innerHTML = "";

    var dy=['S','M','T','W','T','F','S'];

    var oD = new Date(year, month-1, 1); //DD replaced line to fix date bug when current day is 31st
    oD.od=oD.getDay()+1; //DD replaced line to fix date bug when current day is 31st

    var scanfortoday=(year==todaydate.getFullYear() && month==todaydate.getMonth()+1)? todaydate.getDate() : 0 //DD added

    var num_days = calGetMonthNumDays(year, month);

    // Print navigation and month/year title
    // ---------------------------------------------------------------------------------
    var tbl = alib.dom.createElement("table", this.cal_con);
    alib.dom.styleSet(tbl, "width", "100%");
    alib.dom.styleSet(tbl, "font-size", "12px");
    var tbody = alib.dom.createElement("tbody", tbl);
    var tr = alib.dom.createElement("tr", tbody);
    var td = alib.dom.createElement("td", tr);
    alib.dom.styleSet(td, "width", "10px");
    td.innerHTML = "&lt;";
    alib.dom.styleSet(td, "cursor", "pointer");
    td.month = month;
    td.year = year;
    td.m_cls = this;
    td.onclick = function()
    {
        if (this.month == 1)
            {
            this.year = this.year - 1;
            this.month = 12;
        }
        else
            this.month = this.month - 1;
        this.m_cls.calNav(this.year, this.month);
    }
    var td = alib.dom.createElement("td", tr);
    alib.dom.styleSet(td, "text-align", "center");
    td.innerHTML = calGetMonthName(month) + ", " + year;
    var td = alib.dom.createElement("td", tr);
    alib.dom.styleSet(td, "width", "10px");
    td.innerHTML = "&gt;";
    alib.dom.styleSet(td, "cursor", "pointer");
    td.month = month;
    td.year = year;
    td.m_cls = this;
    td.onclick = function()
    {
        if (this.month == 12)
            {
            this.year = this.year + 1;
            this.month = 1;
        }
        else
            this.month = this.month + 1;
        this.m_cls.calNav(this.year, this.month);
    }

    /**
	* Print mini-cal
    */
    var tbl = alib.dom.createElement("table", this.cal_con);
    alib.dom.styleSet(tbl, "width", "100%");
    tbl.cellSpacing = 0;
    tbl.cellPadding = 0;
    //alib.dom.styleSetClass(tbl, "CalendarMonthMainTable");
    var tbody = alib.dom.createElement("tbody", tbl);

    var headers_tr = alib.dom.createElement("tr", tbody);
    for(s=0;s<7;s++)
        {
        var td = alib.dom.createElement("td", headers_tr);
        alib.dom.styleSet(td, "text-align", "center");
        alib.dom.styleSet(td, "font-weight", "bold");
        td.innerHTML = dy[s];

    }
    var curDate = new Date(year, month-1, 1);
    if (curDate.getDay()>0)
        {
        curDate = calDateAddSubtract(curDate, 'day', curDate.getDay()*(-1));
    }

    var tr = alib.dom.createElement("tr", tbody);
    tr.vAlign = "top";
    var d = 0; // number of days
    this.m_monthnumrows = 1;
    for(i=1; i<=42; i++)
        {
        var x=((i-oD.od>=0)&&(i-oD.od<num_days))? i-oD.od+1 : '&nbsp;';

        var td = alib.dom.createElement("td", tr);
        alib.dom.styleSet(td, "width", "14%");
        alib.dom.styleSet(td, "text-align", "center");
        alib.dom.styleSet(td, "padding", "3px");
        alib.dom.styleSet(td, "cursor", "pointer");

        if (x != "&nbsp;")
            {
            var act_lnk = '<a href="javascript:void(0);" style="text-decoration:none;" onclick="calEventOpen(null, null, ';
            act_lnk += "[['date_start', '"+month+"/"+x+"/"+year+"'], ['date_end', '"+month+"/"+x+"/"+year+"'], ['all_day', 't']]);\">+ event</a>";
        }
        else
            var act_lnk = "";

        if (x==scanfortoday) //DD added
            alib.dom.styleSet(td, "font-weight", "bold");

        /**
		* Check for selection background
		*/
        switch (sel)
        {
            case 'month':
                if (curDate.getMonth()+1 == month)
                    alib.dom.styleSet(td, "background-color", "#e3e3e3");
                break;
            case 'week':
                var testDate = new Date(year, month-1, day);
                var dateFrom = (testDate.getDay()>0) ? calDateAddSubtract(testDate, 'day', testDate.getDay()*(-1)) : testDate;
                var dateTo = (testDate.getDay()<6) ? calDateAddSubtract(testDate, 'day', 6-testDate.getDay()) : testDate;

                if (curDate.getTime() >= dateFrom.getTime() && curDate.getTime() <= dateTo.getTime())
                    alib.dom.styleSet(td, "background-color", "#e3e3e3");
                break;
            case 'day':
                var testDate = new Date(year, month-1, day);
                if (curDate.getTime() == testDate.getTime())
                    alib.dom.styleSet(td, "background-color", "#e3e3e3");
                break;
        }

        td.innerHTML = curDate.getDate();

        td.m_cls = this;
        td.m_year = curDate.getFullYear();
        td.m_month = curDate.getMonth()+1;
        td.m_day = curDate.getDate();
        td.onclick = function()
        {
            try
            {
                if (this.m_cls.calendarBrowser)
            	{
					// Navigate to Calendar view and clear selected item
                    this.m_cls.view.navigate("mycalendar");
                    this.m_cls.myCalendarView.options.nav.itemClearOnStates();

                    var view = this.m_cls.calendarBrowser.activeView;

                    switch(view)
                    {
                        case 'day':
                            this.m_cls.calendarBrowser.renderDay(this.m_year, this.m_month, this.m_day);
                            break;
                        case 'week':
                            this.m_cls.calendarBrowser.renderWeek(this.m_year, this.m_month, this.m_day);
                            break;
                        case 'month':
                            this.m_cls.calendarBrowser.renderMonth(this.m_year, this.m_month);
                            break;
                    }
                }
                else
                    {
                    /**
					* Navigate to Calendar view and clear selected item
					*/
                    this.m_cls.view.navigate("mycalendar");
                    this.m_cls.myCalendarView.options.nav.itemClearOnStates();
                }
            }
            catch(e) { }
        }

        curDate = calDateAddSubtract(curDate, 'day', 1);

        if(((i)%7==0)&&(d<num_days))
            {
            tr = alib.dom.createElement("tr", tbody);
            tr.vAlign = "top";
            this.m_monthnumrows++;
        }
        else if(((i)%7==0)&&(d>=num_days))
            {
            break;
        }
    }
}

/**
* Insert a calendar into the list
*/
AntApp.prototype.insertCalendar = function(sec, cal)
{
    var share_id = (cal.share_id) ? cal.share_id : null;
    var calendar_id = cal.id;
    var cal_event = {share_id:share_id, calendar_id:calendar_id};

    var cb = alib.dom.createElement("input");
    alib.dom.styleSet(cb, "cursor", "pointer");
    
    cb.type = "checkbox";
    if (cal.f_view == 't')
        cb.checked = true;

    var itemname = (share_id) ? "share_"+share_id : "cal_"+cal.id;
    var entry = sec.addItem("", cb, 
                        function(cls, cal_event, cb)
                        {
                            /** 
		                    * Change checkbox selection
		                    */		
                            if(cb.checked)
                                cb.checked = false;
                            else
                                cb.checked = true;
                                
                            cb.onchange();
                            
                        }, [this, cal_event, cb], itemname, false);	
                        
    // Add checkbox event
    cb.cal_event = cal_event;
    cb.cls = this;
    cb.calendar = cal;
    cb.onchange = function()
    {
        var f_view = (this.checked) ? 't' : 'f';
        var calendarId = this.calendar.id;
        var calendarArg = "calendar_id";
        if (this.cal_event.share_id)
        {
            calendarId = this.cal_event.share_id;
            calendarArg = "share_id";
        }
        
        ajax = new CAjax('json');
        ajax.cbData.f_view = f_view;
        ajax.cbData.calendar = this.calendar;
        ajax.cbData.cls = this.cls;
        ajax.onload = function(ret)
        {
            this.cbData.cls.processCalendar(this.cbData.calendar, this.cbData.f_view);
        };
        
        var params = [["f_view", f_view], [calendarArg, calendarId]];
        ajax.exec("/controller/Calendar/setFView", params);
    }

    var lblcon = entry.getLabelCon();
    var ocon = entry.getOptionCon();

    var color = cal.color;

    /** 
	* Create color div
	*/
    var clr = alib.dom.createElement("div", lblcon);
    alib.dom.styleSet(clr, "float", "left");
    alib.dom.styleSet(clr, "width", "14px");
    alib.dom.styleSet(clr, "height", "10px");
    alib.dom.styleSet(clr, "margin-top", "3px");
    alib.dom.styleSet(clr, "background-color", "#"+color);
    ALib.Effect.round(clr, 2);

    /** 
	* Now add text
	*/
    var lbl = alib.dom.createElement("div", lblcon);
    alib.dom.styleSet(lbl, "margin-left", "18px");
    lbl.innerHTML = unescape(cal.name);

    /**
	* Add dropdown
	*/
	var dm = new alib.ui.PopupMenu();


	var submenu = new alib.ui.SubMenu("Change Color");
	for (var j = 0; j < G_GROUP_COLORS.length; j++)
	{
		var item = new alib.ui.MenuItem(G_GROUP_COLORS[j][0], {
			icon:"<div style='width:9px;height:9px;background-color:#" + G_GROUP_COLORS[j][1] + "'></div>"
		});
		item.cbData.cls = this;
		item.cbData.cid = cal.id;
		item.cbData.share_id = share_id;
		item.cbData.color =  G_GROUP_COLORS[j][1];
		item.cbData.clrDiv =  clr;
		item.onclick = function() { 
			this.cbData.cls.changeCalColor(this.cbData.cid, this.cbData.share_id, this.cbData.color, this.cbData.clrDiv);
		};
		submenu.addItem(item);
	}
	dm.addItem(submenu);

    var delname = (share_id) ? "Remove Share" : "Delete Calendar";
	var item = new alib.ui.MenuItem(delname, {icon:"<img src='/images/icons/delete_10.png' />"});
	item.cbData.cls = this;
	item.cbData.cid = cal.id;
	item.cbData.share_id = share_id;
	item.cbData.name = unescape(cal.name);
	item.onclick = function() { 
		if (this.cbData.share_id) 
			this.cbData.cls.removeShare(this.cbData.share_id, this.cbData.name); 
		else 
			this.cbData.cls.deleteCalendar(this.cbData.cid, this.cbData.name); }
	dm.addItem(item);

    if (!share_id)
    {
		var item = new alib.ui.MenuItem("Share Calendar", {icon:"<img src='/images/icons/share_16.png' />"});
		item.cbData.cls = this;
		item.cbData.cid = cal.id;
		item.onclick = function() { 
			var antBrowser = new AntObjectBrowser("user");
			antBrowser.cbData.clsRef = this.cbData.cls;
			antBrowser.cbData.cid = this.cbData.cid;
			antBrowser.onSelect = function(objId, objLabel) 
			{
				var ajax = new CAjax('json');
				ajax.onload = function(ret)
				{
                	alib.statusShowAlert("Share invitation sent to " + objLabel, 3000, "bottom", "right");
				};
				ajax.exec("/controller/Calendar/addSharedCalendar", [["calendar_id", this.cbData.cid], ["user_id", objId]]);
			}
			antBrowser.displaySelect();         
		}
		dm.addItem(item);

		var item = new alib.ui.MenuItem("Permissions", {icon:"<img src='/images/icons/permissions_16.png' />"});
		item.cbData.cls = this;
		item.cbData.cid = cal.id;
		item.onclick = function() { 
			loadDacl(null, "calendars/" + this.cbData.cid);
		};
		dm.addItem(item);
    }

	var img = alib.dom.createElement("img", ocon);
	img.src = "/images/icons/rightclick_off.gif";
	dm.attach(img);
	/*
    ocon.appendChild(dm.createImageMenu("/images/icons/rightclick_off.gif", 
    "/images/icons/rightclick_over.gif", 
    "/images/icons/rightclick_on.gif"));
	*/
}

/**
* Process the calendar event
*/
AntApp.prototype.processCalendar= function(calendar, f_view)
{
    for(cb in this.calendarBrowser.calendars)
    {
        var currentCalendar = this.calendarBrowser.calendars[cb];
        
        if(currentCalendar.id == calendar.id)
        {
            delete this.calendarBrowser.calendars[cb];
            break;
        }
    }
    
    if(f_view == "t")
    {
        var idx = this.calendarBrowser.calendars.length;
        this.calendarBrowser.calendars[idx] = calendar;
    }
    
    this.calendarBrowser.getEvents();
}


/**
* Change the color for a calendar
*/
AntApp.prototype.changeCalColor= function(calendar_id, share_id, color, clrdv)
{
    if (share_id)
        var params = [["color", color], ["share_id", share_id]];
    else
        var params = [["color", color], ["calendar_id", calendar_id]];
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.clrdv = clrdv;
    ajax.onload = function(ret)
    {
        this.clrdv.style.backgroundColor = '#'+ret; 
        ALib.Effect.round(this.clrdv, 2);
        this.cls.calendarBrowser.refresh();
    };
    ajax.exec("/controller/Calendar/calSetColor", params);
}

/**
* Delete a calendar belonging to this user
*/
AntApp.prototype.deleteCalendar = function(id, name)
{
    var dlg = new CDialog();
    dlg.confirmBox("Are you sure you want to delete "+unescape(name)+" and all it's events?", "Delete Calendar", [this, id]);
    dlg.onConfirmOk = function(cls, id)
    {
        ajax = new CAjax('json');
        ajax.cls = cls;
        ajax.id = id;
        ajax.onload = function(ret)
        {
            if (ret)
            {
                ALib.statusShowAlert("Calendar Deleted!", 3000, "bottom", "right");
                this.cls.navBar.deleteItem('cal_' + this.id);
            }
        };
        ajax.exec("/controller/Calendar/deleteCalendar", [["id", id]]);
    }
}

/**
* Delete a calendar belonging to this user
*/
AntApp.prototype.removeShare = function(id, name)
{
    var dlg = new CDialog();
    dlg.confirmBox("Are you sure you want to remove "+unescape(name)+"?", "Remove Shared Calendar", [this, id]);
    dlg.onConfirmOk = function(cls, id)
    {
        ajax = new CAjax('json');
        ajax.cls = cls;
        ajax.id = id;
        ajax.onload = function(ret)
        {
            if (ret)
            {
                ALib.statusShowAlert("Calendar Share Removed!", 3000, "bottom", "right");
                this.cls.navBar.deleteItem('share_' + this.id);
            }             
        };
        ajax.exec("/controller/Calendar/deleteShare", [["id", id]]);
    }
}

/**
 * Load a plugin
 *
 * @param string className = the name of the applet
 */
AntApp.prototype.loadPlugin = function(className, antView)
{

	var classParts = className.split("_"); // Nomenclature is Plugin_[modulename]_[classname]

	// Module specific
	if (classParts.length == 3)
	{
		var moduleDir = classParts[1];
		var fileName = classParts[2];
	}

	// Global plugins
	if (classParts.length == 2)
	{
		var moduleDir = "";
		var fileName = classParts[1];
	}

	// Set default resize for antView - can be overridden by plugin if desired
	antView.onresize = function()
	{
		alib.dom.styleSet(this.con, "max-height", (getWorkspaceHeight()) + "px");
		alib.dom.styleSet(this.con, "overflow", "auto");
	}

	// Set plugin path
	var filepath = "/applications/plugins";
	if (moduleDir)
		filepath += "/" + moduleDir;
	filepath += "/" + fileName + ".js";

	// Check if script is already loaded
	if (!document.getElementById("js_app_" + this.name + "_pl" + className))
	{
		// Load External file into this document
		var fileRef = document.createElement('script');
		fileRef.pluginClassName = className;
		fileRef.antView = antView;

		if (alib.userAgent.ie)
		{
			fileRef.onreadystatechange = function () 
			{ 
				if (this.readyState == "complete" || this.readyState == "loaded") 
				{
					var applet = eval("new "+this.pluginClassName+"()");
					if (applet.print)
						applet.print(this.antView);
				}
			};
		}
		else
		{
			fileRef.onload = function () 
			{ 
                var applet = eval("new "+this.pluginClassName+"()");
				if (applet.print)
            		applet.print(this.antView);
			};
		}

		fileRef.type = "text/javascript";
		fileRef.id = "js_app_" + this.name + "_pl" + className;
		fileRef.src =  filepath;
		document.getElementsByTagName("head")[0].appendChild(fileRef);

	}
	else
	{
		var applet = eval("new "+className+"()");
		if (applet.print)
			applet.print(antView);
	}
}

/**
 * Get application header hight
 *
 * @public
 * @this {AntApp}
 * @return {number} Height in px of the application header/title
 */
AntApp.prototype.getAppHeaderHeight = function()
{
	/** 
	 * We no longer print the title container
	 * so this has be disabled for the time being and returns 0
	if (this.titleCon)
	{
		return alib.dom.getElementHeight(this.titleCon);
	}
	*/

	return 0;
}
