/**
 * @fileoverview This class handles building object browsers in the JS created UI
 *
 * Example:
 * <code>
 * 	var ob = new AntObjectBrowser("customer");
 *	ob.print(document.body);
 * </code>
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObjectBrowser
 *
 * @constructor
 * @param {string} obj_type The name of the object type to load
 */
function AntObjectBrowser(obj_type)
{
	/**
	 * Instance of CAntObject of type obj_type - loads object data if oid is defined
	 *
	 * @public
	 * @type {CAntObject}
	 */
	this.mainObject = new CAntObject(obj_type);

	/**
	 * Array of loaded AntObjectBrowser_Items(s)
	 *
	 * @protected
	 * @type {AntObjectBrowser_Item[])
	 */
	this.objectList = new Array();

	/**
	 * Reference to object we have loaded this browser in the context of
	 *
	 * Usually used to copy common fields to new objects
	 *
	 * @protected
	 * @type {CAntObject}
	 */
	this.objectContext = null;

	/** 
	 * The current view that is being used to display this browser
	 *
	 * @private
	 * @type {AntObjectBrowserView}
	 */
	this.currentView = null;

	/**
	 * Conditions used to narrow object query
	 *
	 * @private
	 * @type {Array}
	 */
	this.conditions = new Array();

	/**
	 * Ordering of the object list
	 *
	 * @private
	 * @type {Array}
	 */
	this.sort_order = new Array();

	this.view_fields = new Array();
	this.m_filters = new Array(); // Used for filtering data - especially fkey references

	this.conditionObj = null;
	this.obj_type = obj_type;
	this.options = new Object(); // Used for callback function options

	this.m_advancedSearchDlg = null;
	this.antView = null; // optional AntView reference

	var def_view = this.mainObject.getDefaultView();
	this.loadView(def_view);

	this.searchView = new AntObjectBrowserView(obj_type);
	this.viewsFilterKey = ""; // See nodes on AntObjectBrowser::setViewsFilter

	this.fAllSelected = false;
	this.chkBoxes = new Array();
	this.customActions = new Array();
	//this.objectRows = new Array(); // Store a reference to the div of each object row

	this.optCreateNew = true; // Show new object button in toolbar
	this.optDelete = true; // Show delete button in toolbar
	this.optActions = true; // Show actions dropdown in toobar
	this.searchTitleText = "Search " + this.mainObject.titlePl;
	
	/**
	 * Refresh will force full reload
	 *
	 * @public
	 * @type {bool}
	 */
	this.optForceFullRefresh = false;

	/**
	 * Limit the number of objects to show per page
	 *
	 * @public
	 * @type {int}
	 */
	this.limit = (this.mainObject.showper) ? this.mainObject.showper : 50; // 50 objects per page

	this.hideCheckbox = false; // Can optionall hide the checkbox column. Usually used for reports
	this.useSelect = false; // Upon selecting customer onselect function is called
	this.mainBrowserWorkspace = false; // Load objects inline if main browser workspace
	this.outerCon = null;
	this.browserCon = null;

	/**
	 * Title container at the top of the browser
	 *
	 * @var {DOMElement}
	 */
	this.titleCon = null;


	/**
	 * AntObjectLoader class reference if loading inline
	 *
	 * @var {AntObjectLoader}
	 */
	this.loaderCls = null;

	/**
	 * The id of current/last object that was opened/loaded
	 *
	 * @private
	 * @var {string}
	 */
	this.curObjLoaded = null;

	/**
	 * The object id that is currently selected (for actions)
	 *
	 * @private
	 * @var {string}
	 */
    this.selectedObjId = null;

	/**
	 * Container object used by outside classes to store callback properties
	 *
	 * @public
	 * @type {Object}
	 */
	this.cbData = new Object();

	/**
	 * Flag used to put browser in mobile version or mode
	 *
	 * The global Ant object will have a flag called isMobile if we are working in
	 * mobile mode so this variable should be set here in the constructor automatically
	 * and not need to be set anywhere else in ANT except to test.
	 *
	 * @private
	 * @type {bool}
	 */
	this.mobile = (typeof Ant != "undefined" && Ant.isMobile) ? true : false; // Show mobile version

	/**
	 * Flag used to determine if the objects were loaded for the first time or not
	 *
	 * @private
	 * @type {bool}
	 */
	this.firstLoaded = false;

	// Set browser mode params
	if (this.mobile)
		this.mainObject.browserMode = "table";

	switch (this.mainObject.browserMode)
	{
	case 'previewH':
		this.viewmode = "table";
		this.preview = true; // Show preview paine
		this.previewOrientation = "h"; // ["v"=vertical, "h"=horizontal]
	case 'previewV':
		this.viewmode = "details";
		this.preview = true; // Show preview paine
		this.previewOrientation = "v"; // ["v"=vertical, "h"=horizontal]
		break;
	case 'table':
	default:
		this.viewmode = (obj_type=='activity' || obj_type=='status_update' || obj_type=='notification') ? "details" : "table";
		this.preview = false; // Show preview paine
		this.previewOrientation = "v"; // ["v"=vertical, "h"=horizontal]
		break;
	}

	/**
	 * Flag to indicate whether or not to show deleted items in the list
	 *
	 * @var {bool}
	 */
	this.showDeleted = false;

	/**
	 * Alternate handler used for opening message
	 *
	 * This is often used to set alternate handler for objects like draft emails
	 *
	 * @private
	 * @var {string}
	 */
	this.open_handler = "";
	
	/**
	 * If set the list will automatically refresh eveny n number of seconds
	 *
	 * @var {int}
	 */
	this.refreshInterval = null;

	/**
	 * Don't bother querying the server for objects after loading
	 *
	 * @public
	 * @var {bool}
	 */
	this.skipLoad = false;

	/**
	 * Set to true if browser is inline inside an aobject form
	 *
	 * @private
	 * @var {bool}
	 */
	this.inline = false;

	/**
	 * Outer table used for holding list of objects
	 *
	 * @var {DOMTable}
	 */
	this.m_listTable = null;

	/**
	 * Table body used for holding list of objects
	 *
	 * @var {DOMTbody}
	 */
	this.m_listTableBody = null;

	/**
	 * Browse by field (optional)
	 *
	 * If this is set, then this.browseByPath must also be set.
	 *
	 * @private
	 * @var {string}
	 */
    this.browseByField = null;

	/**
	 * If browse by field is set, then a path must also be set
	 *
	 * @private
	 * @var {string}
	 */
    this.browseByPath = null;
	
	/**
	 * Optional alternate root
	 *
	 * This can be used if we want to browse relative to a subobject liek a subfolder
	 *
	 * @private
	 * @var {int}
	 */
    this.browseByRootId = null;

	/**
	 * Toolbar search containers used for resizing
	 *
	 * @var {Object}
	 */
	this.toolbarSearchCons = new Object();

	/**
	 * List of objects being deleted
	 *
	 * This is used to queue updates and allow for immediate removal from the UI while deletion is still taking place
	 *
	 * @var {Array}
	 */
	this.deletingQueue = new Array();
}

/**
 * Set the header title
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {string} title The title string
 */
AntObjectBrowser.prototype.setTitle = function(title)
{
	if (this.titleCon)
		this.titleCon.innerHTML = title;
}

/**
 * Print the browser inside a container
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {DOMElement} con The container that will house the browser
 */
AntObjectBrowser.prototype.print = function(con)
{
	if (this.preview)
	{
		this.viewmode = "details";
		//if (typeof Ant != "undefined")
			//Ant.setNoBodyOverflow();
	}

	if (this.mobile)
		this.viewmode = "details";

	// Draw the browser
	this.browserCon = con;
	this.mainBrowserWorkspace = true;
	this.buildInterface();
}

/**
 * Print the browser in 'inline' mode which usually means inside an object form.
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {DOMElement} con The container that will house the browser
 * @param {bool} hidetoolbar If set to true then no toolbar will be printed
 * @param {string} title Alternative view title
 */
AntObjectBrowser.prototype.printInline = function(con, hidetoolbar, title)
{
	var hide_tb = (hidetoolbar) ? hidetoolbar : false;
	if (this.currentView && title)
		this.currentView.name = title;

	// Override user settings - inline must not have preview or details (unless mobile)
	this.preview = false;
	this.inline = true;

	if (this.mobile || this.obj_type == "activity" || this.obj_type == "comment" || this.obj_type == "status_update")
		this.viewmode = "details";
	else
		this.viewmode = "table";

	// Draw the browser
	this.browserCon = con;
	this.buildInterface(true, hide_tb);
}

/**
 * Print comments, which are really just objects, but with a comments interface
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {DOMElement} con The container that will house the browser
 * @param {string} obj_reference The object type "type:string" being referenced
 * @param {CAntObject} obj A handle to an object instnace that has comments
 * @param {bool} nocomments If we know there are no comments yet, do no try loading
 */
AntObjectBrowser.prototype.printComments = function(con, obj_reference, obj, nocomments)
{    
	if (obj_reference)
		this.obj_reference = obj_reference;
	if (obj)
		this.parentObject = obj;

	if (nocomments)
		this.skipLoad = true;

	this.viewmode = "details";
	this.printInline(con, true);
}

/**
 * Display object selection dialog. This will call onSelect(oid, label).
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {CDialog} parent_dlg Optional parent dialog for module usage.
 */
AntObjectBrowser.prototype.displaySelect = function(parent_dlg)
{
	var dlg = new CDialog("Select "+this.mainObject.title, parent_dlg);
	dlg.f_close = true;
	this.m_dlg = dlg;
	this.hideCheckbox = true;
	this.useSelect = true;
	this.limit = 50;

	var dv = alib.dom.createElement("div");

	// Views drop-down container
	this.m_viewsdd_con = alib.dom.createElement("span", dv);
	var btn = this.buildViewsDropdown();
	alib.dom.styleAddClass(btn, "noshadow");

	// Search Bar
	this.m_txtSearch = alib.dom.createElement("input", dv);
	this.m_txtSearch.type = "text";
	alib.dom.styleSetClass(this.m_txtSearch, "fancy");
	alib.dom.styleAddClass(this.m_txtSearch, "grLeft");
	this.m_txtSearch.m_cls = this;
	this.m_txtSearch.onkeyup = function(e)
	{
		if (typeof e == 'undefined') 
		{
			if (ALib.m_evwnd)
				e = ALib.m_evwnd.event;
			else
				e = window.event;
		}

		if (typeof e.keyCode != "undefined")
			var code = e.keyCode;
		else
			var code = e.which;

		if (code == 13) // keycode for a return
		{
			this.m_cls.getObjects();
		}
	}

	// Search button
	var searchButton = alib.ui.Button("Search", {
		className:"b1 grRight noshadow nomargin", tooltip:"Search", cls:this, 
		onclick:function() { this.cls.getObjects(); }
	});
	searchButton.print(dv);
	
	this.browserCon = alib.dom.createElement("div", dv);
	alib.dom.styleSet(this.browserCon, "margin-top", "3px");
	alib.dom.styleSetClass(this.browserCon, "mgb1");
	if (!this.mobile)
	{
		alib.dom.styleSet(this.browserCon, "height", "350px");
		alib.dom.styleSet(this.browserCon, "border", "1px solid");
		alib.dom.styleSet(this.browserCon, "overflow", "auto");
	}

	dlg.customDialog(dv, 600);

	// Set details mode if we are in mobile
	if (this.mobile)
		this.viewmode = "details";

	this.buildInterface(true, true);

	// Show bottom row actions
	var bottomButtonCon = alib.dom.createElement("div", dv);
	alib.dom.styleSet(bottomButtonCon, "text-align", "right");

	// New object button - used to create new object then return id when closed
	var button = alib.ui.Button("New " + this.mainObject.title, {
		className:"b2", 
		tooltip:"Click to createa a new new " + this.mainObject.title.toLowerCase(), 
		dlg:dlg, 
		bcls:this,
		onclick:function() { 
			var ol = loadObjectForm(this.bcls.mainObject.obj_type);

			// Set filter values
			for (var i in this.bcls.m_filters)
				ol.setValue(this.bcls.m_filters[i].fieldName, this.bcls.m_filters[i].value);

			alib.events.listen(ol, "close", function(evt) {
				evt.data.cls.select(evt.data.ol.mainObject.id, evt.data.ol.mainObject.getName());
			}, {cls:this.bcls, ol:ol}); 
		}
	});
	button.print(bottomButtonCon);

	// Close button
	var button = alib.ui.Button("Cancel", {
		className:"b1 nomargin", tooltip:"Cancel", dlg:dlg, 
		onclick:function() { this.dlg.hide(); }
	});
	button.print(bottomButtonCon);

	// Resize search input
	alib.dom.styleSet(this.m_txtSearch, "width", (alib.dom.getElementWidth(dv) - searchButton.getWidth() - 2) + "px");

	// Reposition now that we've built the interface
	dlg.reposition();
}

/**
 * Display object selection dialog. This will call onSelect(oid, label).
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {CDialog} parent_dlg Optional parent dialog for module usage.
 */
AntObjectBrowser.prototype.displaySelectInline = function()
{
	//this.hideCheckbox = true;
	this.useSelect = true;
	this.limit = 50;
	this.buildInterface(true, true);
}

/**
 * Internal function to select a customer then fire pubic onselect
 *
 * @private
 * @this {AntObjectBrowser}
 * @param {number} oid Selected object id
 * @param {string} label Optional label/name/title of this object
 */
AntObjectBrowser.prototype.select = function(oid, label)
{
	if (this.m_dlg)
		this.m_dlg.hide();

	this.onSelect(oid, label);
}


/**
 * Public callback function used to determine when the objects have loaded
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {number} num_objs The number of objects that exist
 */
AntObjectBrowser.prototype.onLoad = function(num_objs)
{
    // If object browser is used as select, lets remove the overflow auto and fixed height.
    if(this.useSelect)
    {
        this.m_resultsCon.removeAttribute("style");
        alib.dom.styleSet(this.browserCon, "padding-left", "5px");
    }
}

/**
 * Public callback function used to determine what object was returned
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {number} oid Selected object id
 * @param {string} label Optional label/name/title of this object
 */
AntObjectBrowser.prototype.onSelect = function(oid, label)
{
}

/**
 * Public callback function used to determine when a select is canceled
 *
 * @public
 * @this {AntObjectBrowser}
 */
AntObjectBrowser.prototype.onCancel = function()
{
}

/**
 * Add a custom action to this browser
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {string} name The name of this action - is displayed in the dropdown
 * @param {string} action_url Optional url to handle this action
 * @param {string} icon Path to an icon to display
 * @param {bool} flush If set to true, then remove items from list when action is executed
 */
AntObjectBrowser.prototype.addAction = function(name, action_url, icon, flush)
{
	var act = new Object();
	act.args = [];
	act.name = name;
	act.url = action_url;
	act.funct = "";
	act.toolbar = false;
	act.refresh = true;
	act.icon = (icon) ? icon : "/images/icons/circle_blue.png";
	act.flush = (flush) ? true : false;
	act.doneMsg = "";
	this.customActions[this.customActions.length] = act;
	return act;
}

/**
 * Add a customer toolbar item
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {function} cb_funct Callback function to call when action is clicked
 * @param {array} args Array of arguments to pass to the callback function
 */
AntObjectBrowser.prototype.addToolbarAction = function(cb_funct, cb_args)
{
	var act = new Object();
	act.cb_funct = cb_funct;
	act.cb_args = cb_args;
	/*
	act.funct = (funct) ? funct : "";
	*/
	act.toolbar = true;
	this.customActions[this.customActions.length] = act;

	return act;
}

/**
 * Set browse-by option for this browser
 *
 * Browsers can created heiarchial browse by entries like folders
 * by passing a field name of either type=object or type=fkey/grouping
 *
 * @public
 * @param {string} fieldname The name of the field containing browse by id for this object
 * @param {string} path The initial path of the browse by field
 * @param {int} browseByRootId Optional current working object, like a subfolder to browse relative to root
 * @return {bool} true on success, false on failure
 */
AntObjectBrowser.prototype.setBrowseBy = function(fieldname, path, browseByRootId)
{
	if (!fieldname || !path)
		return false;

	this.browseByField = fieldname;
	this.browseByPath = path;

	if (browseByRootId)
		this.browseByRootId = browseByRootId;
}

/**
 * Create path breadcrumbs for browse by path
 */
AntObjectBrowser.prototype.buildBrowseByPath = function()
{
	// Hide title div
	alib.dom.styleSet(this.listTitleDiv, "display", "none");
	alib.dom.styleSet(this.fsBreadcrumbCon, "display", "block");
	this.fsBreadcrumbCon.innerHTML = "";

	var parts = this.browseByPath.split("/");
	var fullPath = "";
    var fullPathCheck = 0;
	for (var i = 0; i < parts.length; i++)
	{
		var part = parts[i];
		var title = part;

		if (i == 0 && part == "") // root
		{
			title = "Global Files";
			part = "/";
            fullPathCheck = 1;
		}
		else if (i == 0 && part == "%userdir%")
		{
			title = "My Files";
            fullPathCheck = 0;
		}
        
		if (i > fullPathCheck)
			fullPath += "/";
		
		fullPath += part;

		if (i > 0)
			var sp = alib.dom.createElement("span", this.fsBreadcrumbCon, "&nbsp;/&nbsp;");

		var a = alib.dom.createElement("a", this.fsBreadcrumbCon);
		a.innerHTML = title;
		a.href = "javascript:void(0);";
		a.fullPath = fullPath;
		a.bcls = this;
		a.onclick = function()
		{
			this.bcls.changeBrowseByPath(this.fullPath);
		}
	}
}

/**
 * Load view settings to current view
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {AntObjectBrowserView} view The view to apply to this browser
 */
AntObjectBrowser.prototype.loadView = function(view)
{
	if (view==null)
		return;

	// Make a copy with slice
	this.conditions = view.conditions.slice(0);
	this.sort_order = view.sort_order.slice(0);
	this.view_fields = view.view_fields.slice(0); 
	this.currentView = view;
    
	if (this.listTitleDiv && this.listTitleDiv.lbl)
	{
		if (view.name)
			this.listTitleDiv.lbl.innerHTML = view.name;
		else if (!this.preview)
			this.listTitleDiv.lbl.innerHTML = "Search Results";
	}
}

/**
 * Clear (default) view settings except for columns
 *
 * @public
 * @this {AntObjectBrowser}
 */
AntObjectBrowser.prototype.clearView = function()
{
	// Clear conditions and sort order but leave view fields
	this.conditions = new Array();
	//this.sort_order = new Array();  - Leave default sort order
	this.currentView = null;

	if (this.listTitleDiv)
	{
		if (view.name)
			this.listTitleDiv.lbl.innerHTML = this.mainObject.titlePl;
	}
}

/**
 * Add conditions to the query
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {string} blogic Either "and" or "or" in relation to the past condition (if any)
 * @param {string} name The name of the field to query against
 * @param {string} operator The string operator
 * @param {string} value The value of the query condition.
 * @param {bool} nooverwrite If set to true, do not overwite conditions for a specific field name
 */
AntObjectBrowser.prototype.addCondition = function(blogic, fieldName, operator, condValue, nooverwrite)
{
	var set = true;

	if (nooverwrite)
	{
		for (var i = 0; this.conditions.length; i++)
		{
			if (this.conditions[i].fieldName == fieldName)
				set = false;
		}
	}

	if (set)
	{
		var cond = new Object();
		cond.blogic = blogic;
		cond.fieldName = fieldName;
		cond.operator = operator;
		cond.condValue = condValue;
		this.conditions[this.conditions.length] = cond;
	}
}

/**
 * Add a sort order to the query
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {string} field The field to sort by
 * @param {string} selorder Either "asc" or "desc" for ascending or descending
 */
AntObjectBrowser.prototype.addSortOrder = function(field, selorder)
{
	var sorder = (selorder) ? selorder : "asc";

	var ind = this.sort_order.length;
	this.sort_order[ind] = {fieldName:field, order:sorder};
}

/**
 * Create interface after data is loaded
 *
 * @private
 * @this {AntObjectBrowser}
 * @param {bool} inline Set to true of we are printing this browser inline - usually in a form
 * @param {bool} hidetoolbar If true the toolbar will not be printed for this browser
 */
AntObjectBrowser.prototype.buildInterface = function(inline, hidetoolbar)
{
	// Creat the browser
	if (!inline)
	{
		// If we are in preview mode then hide the title
		if (!this.preview)
		{
			this.titleCon = alib.dom.createElement("div", this.browserCon);
			alib.dom.styleSetClass(this.titleCon, "aobListHeader");

			if (this.antView)
				this.antView.getTitle(this.titleCon);
			else
				this.setTitle("Browse " + this.mainObject.titlePl);
		}

        this.innerCon = alib.dom.createElement("div", this.browserCon);
        this.innerCon.className = "aobBody";

		if (this.preview)
		{
			this.spCon = new CSplitContainer("verticle", "100%", "100%");
			this.spCon.resizable = true;
			
			var ctbl_con = this.spCon.addPanel("300px");;
			this.previewCon = this.spCon.addPanel("*");
			this.spCon.print(this.innerCon);            
		}
		else
		{            
			var ctbl_con = this.innerCon;
		}
	}
	else
	{
		var ctbl_con = this.browserCon;
	}
	this.m_browserCon = ctbl_con; // Inner container for toobar + results

	if (!hidetoolbar)
	{
		this.buildToobar(ctbl_con);
	}

	// Add window frame
	// ----------------------------------------------------------
	switch (this.obj_type)
	{
	case "comment":
		this.listTitleDiv = alib.dom.createElement("div"); // This is never displayed
		this.m_contextCon = alib.dom.createElement("div", ctbl_con);
        this.m_resultsCon = alib.dom.createElement("div", ctbl_con);        
		var addcon =  alib.dom.createElement("div", ctbl_con);
		this.printInlineAddComment(addcon);
		break;

	default:
		this.m_contextCon = alib.dom.createElement("div", ctbl_con);
		alib.dom.styleSet(this.m_contextCon, "float", "right");
		alib.dom.styleSetClass(this.m_contextCon, "aobListRight");
        
        this.fsBreadcrumbCon = alib.dom.createElement("div", ctbl_con);
		alib.dom.styleSetClass(this.fsBreadcrumbCon, "aobListBreadCrumbs");
		alib.dom.styleSet(this.fsBreadcrumbCon, "display", "none"); // Hide unless breadcrumbs are needed
        
        this.listTitleDiv = alib.dom.createElement("div", ctbl_con);
		alib.dom.styleSetClass(this.listTitleDiv, "aobListTitle");

		if (this.preview)
		{
			alib.dom.styleSet(this.listTitleDiv, "padding-left", "6px");
			var sel_all = alib.dom.createElement("input");
			sel_all.type = "checkbox";
			sel_all.cls = this;
			sel_all.onclick = function() { this.cls.fAllSelected = this.checked; this.cls.toggleSelectAll(); }
			this.listTitleDiv.appendChild(sel_all);
		}
		else if (this.browseByField)
		{
			this.buildBrowseByPath();
		}
		else
		{
			/*
			var viewsDm = this.getViewsDropdown();
			var btn = viewsDm.createLinkMenu((this.currentView && this.currentView.name) ? this.currentView.name : this.mainObject.titlePl);

			this.listTitleDiv.appendChild(btn);
			this.listTitleDiv.lbl = viewsDm.m_button;
			*/

			var lblText = (this.currentView && this.currentView.name) ? this.currentView.name : this.mainObject.titlePl;
			var link = alib.dom.createElement("a", this.listTitleDiv, lblText + " &#9660;");
			link.href = "javascript:void(0);";
			var menu = this.getViewsMenu();
			menu.attach(link);

			// Just use the link for the label
			this.listTitleDiv.lbl = link;

		}
		this.m_resultsCon = alib.dom.createElement("div", ctbl_con);

		break;
	}

    this.blankMessageCon = alib.dom.createElement("div", this.m_resultsCon);
    this.listObjectsCon = alib.dom.createElement("div", this.m_resultsCon);
    
	this.getObjects();
    this.resize();
}

/**
 * Build toolbar
 *
 * @private
 * @this {AntObjectBrowser}
 * @param {DOMElement} con The container to print the toolbar on
 */
AntObjectBrowser.prototype.buildToobar = function(con)
{
	this.m_toolbarCon = alib.dom.createElement("div", con);
	alib.dom.styleSetClass(this.m_toolbarCon, "aobToolbar");

	// TODO: use AntObjectBrowser.Toolbar class
	//var toolb = new AntObjectBrowser.Toolbar(this);
	//toolb.renderFull(this.m_toolbarCon);
	//toolb.renderPreview(this.m_toolbarCon);
	//toolb.renderMobile(this.m_toolbarCon);
	
	this.m_toolbarCon = alib.dom.createElement("div", con);
	alib.dom.styleSetClass(this.m_toolbarCon, "aobToolbar");
	var tb = new CToolbar();
	// Clear seach funct
	var fclear = function(cls) { cls.m_txtSearch.value = ''; cls.getObjects(); }

	// Handle cusom toolbars
	switch (this.obj_type)
	{
	case 'file':
	case 'folder':
		return this.buildToolbarFiles(tb);
		break;
	}

	// Build Actions Menu
	// --------------------------------
	var menuAct = new alib.ui.PopupMenu();
	
	this.addAdditionalActions();
	for (var i = 0; i < this.customActions.length; i++)
	{
		if (!this.customActions[i].toolbar)
		{
			var iconHtm = (this.customActions[i].icon) ? "<img src='" + this.customActions[i].icon + "' />" : null;
			var item = new alib.ui.MenuItem(this.customActions[i].name, {icon:iconHtm});
			item.cbData.cls = this;
			item.cbData.act = this.customActions[i];
			item.onclick = function() {
				this.cbData.cls.actionCustom(this.cbData.act);
			}
			menuAct.addItem(item);
		}
	}

	// Add Print
	var item = new alib.ui.MenuItem("Print", {icon:"<img src='/images/icons/print_10.png' />"});
	item.cbData.cls = this;
	item.onclick = function() { this.cbData.cls.actionPrint(); }
	if (!this.mobile)
		menuAct.addItem(item);

	// Add Export
	var item = new alib.ui.MenuItem("Export", {icon:"<img src='/images/icons/excel_10.png' />"});
	item.cbData.cls = this;
	item.onclick = function() { this.cbData.cls.actionExport(); }
	menuAct.addItem(item);

	// Add Import
	var item = new alib.ui.MenuItem("Import", {icon:"<img src='/images/icons/excel_10.png' />"});
	item.cbData.cls = this;
	item.onclick = function() { this.cbData.cls.actionImport(); }
	menuAct.addItem(item);

	// Add Email
	if (this.objectHasEmail())
	{
		var item = new alib.ui.MenuItem("Email", {icon:"<img src='/images/icons/email-b_10.png' />"});
		item.cbData.cls = this;
		item.onclick = function() { this.cbData.cls.actionEmail(); }
		menuAct.addItem(item);
	}

	// Add Edit
	var item = new alib.ui.MenuItem("Edit", {icon:"<img src='/images/icons/edit_10.png' />"});
	item.cbData.cls = this;
	item.onclick = function() { this.cbData.cls.actionEdit(); }
	menuAct.addItem(item);

	// Add Merge Records
	var item = new alib.ui.MenuItem("Merge Records", {icon:"<img src='/images/icons/merge_10.png' />"});
	item.cbData.cls = this;
	item.onclick = function() { this.cbData.cls.actionMerge(); }
	menuAct.addItem(item);


	// Add custom actions to the toolbar
	for (var i = 0; i < this.customActions.length; i++)
	{
		if (this.customActions[i].toolbar)
		{
			this.customActions[i].cb_funct(tb, this, this.customActions[i].cb_args[0]);
		}
	}

	// Render toolbar
	// --------------------------------
	if (this.preview || this.mobile)
	{
		tb.styleSet("margin-bottom", "0");

		// Delete button
		var btn = new CButton("Delete", function(cls) {cls.deleteObjects(); }, [this], "b1");
		tb.AddItem(btn.getButton(), "right");
		tb.print(this.m_toolbarCon);

		if (this.optCreateNew && this.mobile)
		{
			var btn = new CButton("New", function(cls) {cls.loadObjectForm(); }, [this], (this.inline) ? "b2 medium" : "b2");
			tb.AddItem(btn.getButton(), "left");
		}

		// View or move container
		this.m_viewsdd_con = alib.dom.createElement("span");
		tb.AddItem(this.m_viewsdd_con);

		// If we are working with email print the move button
		if (this.obj_type == "email_thread" || this.obj_type == "email_message")
		{
			var dynsel = new AntObjectGroupingSel("Move", "email_thread", 'mailbox_id', null, this.mainObject, {noNull:true, staticLabel:true});
			dynsel.print(this.m_viewsdd_con, "b1");
			dynsel.brwsercls = this;
			dynsel.onSelect = function(id, name)
			{
				if (this.brwsercls.cbData.groupingId == id)
					return;

				var sendArgs = [["obj_type", this.brwsercls.obj_type], ["field_name", 'mailbox_id'], 
								["move_from", 19], ["move_to", id]];
				var act = {url:"/controller/Object/moveByGrouping", args:sendArgs, 
							refresh:true, doneMsg:"Moved to "+name, flush:true};
				this.brwsercls.actionCustom(act);
			}
		}
		else
		{
			// Views drop-down container
			this.buildViewsDropdown();
		}


		// Add Actions dropdown
		var btn = new alib.ui.MenuButton("Actions", menuAct, {className:"b1"});
		tb.AddItem(btn.getButton());

		var button = alib.ui.Button("<img src='/images/icons/refresh_12.png' />", {
			className:"b1", tooltip:"Refresh", cls:this, 
			onclick:function() {this.cls.refresh(); }
		});

		//var btn = new CButton("<img src='/images/icons/refresh_12.png' />", function(cls) {cls.refresh();}, [this], "b1");
		tb.AddItem(button.getButton(), "left");
		
		// need to clear the div of the buttons
		var divBtnClear = alib.dom.createElement("div", tb.getContainer());
		alib.dom.styleSet(divBtnClear, "clear", "both");
		alib.dom.styleSet(divBtnClear, "visibility", "hidden");
		
		var divCon = alib.dom.createElement("div", this.m_toolbarCon);
		alib.dom.styleSetClass(divCon, "aobSearch");
		
		// div search container
		var divSearch = alib.dom.createElement("div", divCon);

		this.buildToolbarSearch(divSearch);

	}
	else
	{
		// div search container
		var divSearch = ALib.m_document.createElement("div");
		alib.dom.styleSet(divSearch, "width", "270px");
		tb.AddItem(divSearch, "right");
		this.buildToolbarSearch(divSearch);

		if (this.optCreateNew)
		{
			var btn = new CButton("New "+this.mainObject.title, function(cls) {cls.loadObjectForm(); }, [this], (this.inline) ? "b2 medium" : "b2");
			tb.AddItem(btn.getButton(), "left");
		}

		// Views drop-down container
		this.m_viewsdd_con = alib.dom.createElement("span");
		tb.AddItem(this.m_viewsdd_con);
		this.buildViewsDropdown();

		// Add Actions dropdown
		var classes = (this.inline) ? "b1 medium" : "b1";
		var btn = new alib.ui.MenuButton("Actions", menuAct, {className:classes});
		tb.AddItem(btn.getButton());

		var button = alib.ui.Button("<img src='/images/icons/refresh_" + ((this.inline) ? "10" : "12") + ".png' />", {
			className:(this.inline) ? "b1 medium" : "b1", tooltip:"Refresh", cls:this, 
			onclick:function() {this.cls.refresh(); }
		});
		tb.AddItem(button.getButton(), "left");

		if (this.optDelete)
		{
			var btn = new CButton("Delete", function(cls) {cls.deleteObjects(); }, [this], (this.inline) ? "b1 grRight medium" : "b1 grRight");
			tb.AddItem(btn.getButton(), "left");
		}

		tb.print(this.m_toolbarCon);
	}
}

/**
 * Build toolbar search form
 *
 * @param {DOMElement} con The container where we will be printint the search form
 * @param bool preview If we are in preview mode then span fill width of contianer
 */
AntObjectBrowser.prototype.buildToolbarSearch = function(divSearch, preview)
{
	// Add the containers and float
	var divFilter = alib.dom.createElement("div", divSearch);
	alib.dom.styleSet(divFilter, "float", "left");

    var divNewWin = alib.dom.createElement("div", divSearch);
    alib.dom.styleSet(divNewWin, "float", "right");
    
	var divGo = alib.dom.createElement("div", divSearch);
	alib.dom.styleSet(divGo, "float", "right");

	var divInput = alib.dom.createElement("div", divSearch);
	alib.dom.styleSet(divInput, "overflow", "hidden");


	// Advanced search options
	var btn = alib.ui.Button("&#9660;", {
		className:(this.inline || this.preview) ? "b1 grLeft medium noshadow" : "b1  grLeft noshadow", tooltip:"Click to view advanced search options", cls:this, 
		onclick:function() {this.cls.showAdvancedSearch(); }
	});
	btn.print(divFilter);
	
	// span container
	divInput.className = "clearIcon";
	
	// text search
	this.m_txtSearch = createInputAttribute(alib.dom.createElement("input", divInput), "text");
	alib.dom.styleSet(this.m_txtSearch, "width", "100%");
	//alib.dom.styleSet(this.m_txtSearch, "paddingRight", "25px");
	alib.dom.setInputBlurText(this.m_txtSearch, this.searchTitleText, "inputBlurText", "", "");
	alib.dom.styleAddClass(this.m_txtSearch, "fancy");
	alib.dom.styleAddClass(this.m_txtSearch, "grLeft");
	alib.dom.styleAddClass(this.m_txtSearch, "grRight");
	if (this.inline || this.preview)
		alib.dom.styleAddClass(this.m_txtSearch, "medium");
	
	// span icon
	var spanIcon = alib.dom.createElement("span", divInput);
	spanIcon.className = "deleteicon";
	alib.dom.styleSet(spanIcon, "visibility", "hidden");
	
	// span icon onclick
	spanIcon.cls = this;            
	spanIcon.divWidth = 195;
	spanIcon.m_txtSearch = this.m_txtSearch;
	spanIcon.onclick = function()
	{
		this.m_txtSearch.value = "";
		this.m_txtSearch.focus();
		alib.dom.styleSet(this, "visibility", "hidden");
		this.cls.getObjects();
	}
	
	// text search onkeyup
	this.m_txtSearch.m_cls = this;
	this.m_txtSearch.spanIcon = spanIcon;            
	this.m_txtSearch.onkeyup = function(e)
	{
		if (typeof e == 'undefined') 
		{
			if (ALib.m_evwnd)
				e = ALib.m_evwnd.event;
			else
				e = window.event;
		}

		if (typeof e.keyCode != "undefined")
			var code = e.keyCode;
		else
			var code = e.which;

		if (code == 13) // keycode for a return
			this.m_cls.getObjects();
			
		// display the span icon
		if(this.m_cls.m_txtSearch.value.length > 0)                        
			alib.dom.styleSet(this.spanIcon, "visibility", "visible");
		else
			alib.dom.styleSet(this.spanIcon, "visibility", "hidden");
	}
	
	// clear the div search
	//divClear(divSearch);
	var btn = alib.ui.Button("Go", {
		className:(this.inline || this.preview) ? "b1 grRight medium noshadow" : "b1 grRight noshadow", tooltip:"Click to search", cls:this, 
		onclick:function() {this.cls.getObjects(); }
	});
	btn.print(divGo);

	// Resize
	//alib.dom.styleSet(divInput, "margin-left", divFilter.offsetWidth + "px");
	//alib.dom.styleSet(divInput, "margin-right", divGo.offsetWidth + "px");

	// Set properties of class variable for dynamic resizing later
	this.toolbarSearchCons.divFilter = divFilter;
	this.toolbarSearchCons.divInput = divInput;
	this.toolbarSearchCons.divGo = divGo;
}

/**
 * Build toolbar
 *
 * @private
 * @this {AntObjectBrowser}
 * @param {CToolbar} tb The toolbar to populate
 */
AntObjectBrowser.prototype.buildToolbarFiles = function(tb)
{
	if (this.preview || this.mobile)
	{
		tb.styleSet("margin-bottom", "0");

		// Delete button
		var btn = new CButton("Delete", function(cls) {cls.deleteObjects(); }, [this], "b1");
		tb.AddItem(btn.getButton(), "right");
		tb.print(this.m_toolbarCon);

		// View or move container
		this.m_viewsdd_con = alib.dom.createElement("span");
		tb.AddItem(this.m_viewsdd_con);

		// If we are working with email print the move button
		if (this.obj_type == "email_thread" || this.obj_type == "email_message")
		{
			var dynsel = new AntObjectGroupingSel("Move", "email_thread", 'mailbox_id', null, this.mainObject, {noNull:true, staticLabel:true});
			dynsel.print(this.m_viewsdd_con, "b1 grLeft");
			dynsel.brwsercls = this;
			dynsel.onSelect = function(id, name)
			{
				if (this.brwsercls.cbData.groupingId == id)
					return;

				var sendArgs = [["obj_type", this.brwsercls.obj_type], ["field_name", 'mailbox_id'], 
								["move_from", 19], ["move_to", id]];

				var act = {url:"/controller/Object/moveByGrouping", args:sendArgs, 
							refresh:true, doneMsg:"Moved to "+name, flush:true};
				this.brwsercls.actionCustom(act);
			}
		}
		else
		{
			// Views drop-down container
			this.buildViewsDropdown();
		}

		// Actions
		var dm_act = new CDropdownMenu();
		this.addAdditionalActions();

		for (var i = 0; i < this.customActions.length; i++)
		{
			if (!this.customActions[i].toolbar)
			{
				var dm_sub = dm_act.addEntry(this.customActions[i].name, 
											 function(cls, act){ cls.actionCustom(act); }, 
											 this.customActions[i].icon, null, [this, this.customActions[i]]);
			}
		}

		var dm_sub = dm_act.addEntry("Print", function(cls){ cls.actionPrint(); }, "/images/icons/print_10.png", null, [this]);
		var dm_sub = dm_act.addEntry("Export", function(cls){ cls.actionExport(); }, "/images/icons/excel_10.png", null, [this]);
		var dm_sub = dm_act.addEntry("Import", function(cls){ cls.actionImport(); }, "/images/icons/excel_10.png", null, [this]);
		if (this.objectHasEmail())
			var dm_sub = dm_act.addEntry("Email", function(cls){ cls.actionEmail(); }, "/images/icons/email-b_10.png", null, [this]);
		tb.AddItem(dm_act.createButtonMenu("Actions", null, null, "b1 grRight"));
		var dm_sub = dm_act.addEntry("Edit", function(cls){ cls.actionEdit(); }, "/images/icons/edit_10.png", null, [this]);
		var dm_sub = dm_act.addEntry("Merge Records", function(cls){ cls.actionMerge(); }, "/images/icons/merge_10.png", null, [this]);
		/* TODO: we need to revisit this
		var dm_sub = dm_act.addSubmenu("Create New", null, null, null);
		dm_sub.addEntry("Calendar Event", function(cls){ cls.actionCreateAssoc("calendar_event"); }, "/images/icons/calendar_event_10.png", null, [this]);
		*/

		// Add custom actions to the toolbar
		for (var i = 0; i < this.customActions.length; i++)
		{
			if (this.customActions[i].toolbar)
			{
				this.customActions[i].cb_funct(tb, this, this.customActions[i].cb_args[0]);
			}
		}
		
		var button = alib.ui.Button("<img src='/images/icons/refresh_12.png' />", {
			className:"b1", tooltip:"Refresh", cls:this, 
			onclick:function() {this.cls.refresh(); }
		});

		//var btn = new CButton("<img src='/images/icons/refresh_12.png' />", function(cls) {cls.refresh();}, [this], "b1");
		tb.AddItem(button.getButton(), "left");
		
		// need to clear the div of the buttons
		var divBtnClear = alib.dom.createElement("div", tb.getContainer());
		alib.dom.styleSet(divBtnClear, "clear", "both");
		alib.dom.styleSet(divBtnClear, "visibility", "hidden");
		
		var divWidth = 195;            
		
		var divCon = alib.dom.createElement("div", this.m_toolbarCon);
		alib.dom.styleSet(divCon, "marginTop", "5px");
		
		// div search container
		var divSearch = alib.dom.createElement("div", divCon);
		alib.dom.styleSet(divSearch, "float", "left");
		
		// span container
		var spanContainer = alib.dom.createElement("span", divSearch);
		spanContainer.className = "clearIcon";
		
		// text search
		this.m_txtSearch = createInputAttribute(alib.dom.createElement("input", spanContainer), "text");
		alib.dom.styleSet(this.m_txtSearch, "width", divWidth + "px");
		alib.dom.styleSet(this.m_txtSearch, "paddingRight", "25px");
		alib.dom.setInputBlurText(this.m_txtSearch, this.searchTitleText, "inputBlurText", "", "");
		alib.dom.styleAddClass(this.m_txtSearch, "fancy");
		alib.dom.styleAddClass(this.m_txtSearch, "grRight");
		
		// span icon
		var spanIcon = alib.dom.createElement("span", spanContainer);
		spanIcon.className = "deleteicon";
		alib.dom.styleSet(spanIcon, "visibility", "hidden");
		
		// span icon onclick
		spanIcon.cls = this;            
		spanIcon.divWidth = divWidth;
		spanIcon.m_txtSearch = this.m_txtSearch;
		spanIcon.onclick = function()
		{
			this.m_txtSearch.value = "";
			this.m_txtSearch.focus();
			alib.dom.styleSet(this, "visibility", "hidden");
			this.cls.getObjects();
		}
		
		// text search onkeyup
		this.m_txtSearch.m_tb = tb;
		this.m_txtSearch.m_cls = this;
		this.m_txtSearch.spanIcon = spanIcon;            
		this.m_txtSearch.onkeyup = function(e)
		{
			if (typeof e == 'undefined') 
			{
				if (ALib.m_evwnd)
					e = ALib.m_evwnd.event;
				else
					e = window.event;
			}

			if (typeof e.keyCode != "undefined")
				var code = e.keyCode;
			else
				var code = e.which;

			if (code == 13) // keycode for a return
				this.m_cls.getObjects();
				
			// display the span icon
			if(this.m_cls.m_txtSearch.value.length > 0)                        
				alib.dom.styleSet(this.spanIcon, "visibility", "visible");
			else
				alib.dom.styleSet(this.spanIcon, "visibility", "hidden");
		}
		
		// clear the div search
		divClear(divSearch);
		
		var divFilter = alib.dom.createElement("div", divCon);
		alib.dom.styleSet(divFilter, "float", "left");
		
		var btn = alib.ui.Button("<img src='/images/icons/arrow_down_12.png'>", {
			className:(this.inline) ? "b1 grRight medium" : "b1 grRight", tooltip:"Click to view advanced search options", cls:this, 
			onclick:function() {this.cls.showAdvancedSearch(); }
		});
		btn.print(divFilter);
		
		// clear the div search
		divClear(divCon);            
	}
	else
	{
		// Add Toolbar
		var btn = alib.ui.Button("<img src='/images/icons/arrow_down_12.png'>", {
			className:(this.inline) ? "b1 grRight medium" : "b1 grRight", tooltip:"Click to view advanced search options", cls:this, 
			onclick:function() {this.cls.showAdvancedSearch(); }
		});
		tb.AddItem(btn.getButton(), "right");
		
		// div search container
		var divSearch = ALib.m_document.createElement("div");
		
		// span container
		var spanContainer = alib.dom.createElement("span", divSearch);
		spanContainer.className = "clearIcon";
		
		// text search
		this.m_txtSearch = createInputAttribute(alib.dom.createElement("input", spanContainer), "text");
		if (this.inline)
			alib.dom.styleAddClass(this.m_txtSearch, "medium");
		alib.dom.styleSet(this.m_txtSearch, "width", 150 + "px");
		alib.dom.styleSet(this.m_txtSearch, "paddingRight", "25px");
		alib.dom.setInputBlurText(this.m_txtSearch, this.searchTitleText, "inputBlurText", "", "");
		alib.dom.styleAddClass(this.m_txtSearch, "fancy");
		alib.dom.styleAddClass(this.m_txtSearch, "grRight");
		
		// span icon
		var spanIcon = alib.dom.createElement("span", spanContainer);
		spanIcon.className = "deleteicon";
		alib.dom.styleSet(spanIcon, "visibility", "hidden");
		
		// span icon onclick
		spanIcon.cls = this;            
		spanIcon.divWidth = 150;
		spanIcon.m_txtSearch = this.m_txtSearch;
		spanIcon.onclick = function()
		{
			this.m_txtSearch.value = "";
			this.m_txtSearch.focus();
			alib.dom.styleSet(this, "visibility", "hidden");
			this.cls.getObjects();
		}
		
		// text search onkeyup
		this.m_txtSearch.m_tb = tb;
		this.m_txtSearch.m_cls = this;
		this.m_txtSearch.spanIcon = spanIcon;
		this.m_txtSearch.onkeyup = function(e)
		{
			if (typeof e == 'undefined') 
			{
				if (ALib.m_evwnd)
					e = ALib.m_evwnd.event;
				else
					e = window.event;
			}

			if (typeof e.keyCode != "undefined")
				var code = e.keyCode;
			else
				var code = e.which;

			if (code == 13) // keycode for a return
				this.m_cls.getObjects();
				
			// display the span icon
			if(this.m_cls.m_txtSearch.value.length > 0)                        
				alib.dom.styleSet(this.spanIcon, "visibility", "visible");
			else
				alib.dom.styleSet(this.spanIcon, "visibility", "hidden");
		}
		
		divClear(divSearch);
		
		// add div search container
		tb.AddItem(divSearch, "right");

		if (this.optCreateNew)
		{
			var button = alib.ui.Button("Upload File(s)", {
				className:(this.inline) ? "b1 medium" : "b2", tooltip:"Upload files from your computer", cls:this, 
				onclick:function() {this.cls.antFsUpload(); }
			});
		
			tb.AddItem(button.getButton(), "left");

			var button = alib.ui.Button("New Folder", {
				className:(this.inline) ? "b1 medium" : "b1", tooltip:"Crate a new folder", cls:this, 
				onclick:function() {this.cls.antFsCreateFolder(); }
			});
		
			tb.AddItem(button.getButton(), "left");
		}


		var button = alib.ui.Button("Move", {
			className:(this.inline) ? "b1 grLeft medium" : "b1 grLeft", tooltip:"Move selected files and folders", cls:this, 
			onclick:function() {this.cls.antFsMove(); }
		});
		tb.AddItem(button.getButton(), "left");

		var button = alib.ui.Button("<img src='/images/icons/refresh_" + ((this.inline) ? "10" : "12") + ".png' />", {
			className:(this.inline) ? "b1 grCenter medium" : "b1 grCenter", tooltip:"Refresh", cls:this, 
			onclick:function() {this.cls.refresh(); }
		});
		tb.AddItem(button.getButton(), "left");

		if (this.optDelete)
		{
			var btn = new CButton("Delete", function(cls) {cls.deleteObjects(); }, [this], (this.inline) ? "b1 grRight medium" : "b1 grRight");
			tb.AddItem(btn.getButton(), "left");
		}

		tb.print(this.m_toolbarCon);
	}
}

/**
 * Resize the browser. This is mostly important in preview mode where 100% height is set.
 *
 * @public
 * @this {AntObjectBrowser}
 */
AntObjectBrowser.prototype.resize = function()
{
	if (this.mobile)
		return;

	if (this.preview)
	{		
        alib.dom.styleSet(this.innerCon, "height", "200px");

        if (this.spCon && this.spCon.setHeight)
			this.spCon.setHeight("300px");

		// Resize the outer con
		var height = getWorkspaceHeight();

		if (this.titleCon)
			height -= alib.dom.getElementHeight(this.titleCon);

        alib.dom.styleSet(this.innerCon, "height", (height-3)+"px");

		// Resize the split container
		height = alib.dom.getContentHeight(this.innerCon);

		//if (height <= 0)
			//height = 300;

		if (height > 0 && this.spCon && this.spCon.setHeight)
			this.spCon.setHeight(height+"px");

		// Resize the objects contianer minus the toolbar
		var height = alib.dom.getElementHeight(this.m_browserCon);
		
		if (this.m_toolbarCon)
			height -= alib.dom.getElementHeight(this.m_toolbarCon);

		if (this.listTitleDiv)
			height -= alib.dom.getElementHeight(this.listTitleDiv);

		if (this.fsBreadcrumbCon)
			height -= alib.dom.getElementHeight(this.fsBreadcrumbCon);

		if (height > 0)
		{
			alib.dom.styleSet(this.m_resultsCon, "height", (height) + "px");
			alib.dom.styleSet(this.m_resultsCon, "overflow", "auto");
		}
	}
	else if (!this.inline)
	{
		var height = getWorkspaceHeight();

		if (this.titleCon)
			height -= alib.dom.getElementHeight(this.titleCon);

		if (this.m_toolbarCon)
			height -= alib.dom.getElementHeight(this.m_toolbarCon);

		if (this.listTitleDiv)
			height -= alib.dom.getElementHeight(this.listTitleDiv);

		if (this.fsBreadcrumbCon)
			height -= alib.dom.getElementHeight(this.fsBreadcrumbCon);

		if (height > 0)
		{
			alib.dom.styleSet(this.m_resultsCon, "height", (height) + "px");
			alib.dom.styleSet(this.m_resultsCon, "overflow", "auto");
		}
	}

	// Resize search inputs
	if (this.toolbarSearchCons.divInput && this.toolbarSearchCons.divFilter && this.toolbarSearchCons.divGo)
	{
		var filterWidth = this.toolbarSearchCons.divFilter.offsetWidth;
		var goWidth = this.toolbarSearchCons.divGo.offsetWidth;
		if (!filterWidth)
			filterWidth = 30; // default margin
		if (alib.userAgent.ie)
			goWidth += 3; // IE needs a little more room for the border
		//alib.dom.styleSet(this.toolbarSearchCons.divInput, "margin-left", (filterWidth) + "px");
		//alib.dom.styleSet(this.toolbarSearchCons.divInput, "margin-right", (goWidth) + "px");
		if (goWidth)
			alib.dom.styleSet(this.m_txtSearch, "width", "100%");
	}
}

/**
 * Create views dropdown
 *
 * @private
 * @this {AntObjectBrowser}
 */
AntObjectBrowser.prototype.buildViewsDropdown = function()
{
	this.m_viewsdd_con.innerHTML = "";

	var viewMenu = this.getViewsMenu();
    
	var classes = (this.inline) ? "b1 grLeft medium" : "b1 grLeft";
	var btn = new alib.ui.MenuButton("Views", viewMenu, {className:classes});
	//var btn = dm_view.createButtonMenu("Views", null, null, );
	//this.m_viewsdd_con.appendChild(btn.getButton());
	//btn.print(this.m_viewsdd_con);

	return btn;
}

/**
 * Get views drop-down
 */
AntObjectBrowser.prototype.getViewsDropdown = function() {

	var dm_view = new CDropdownMenu();
	for (var i = 0; i < this.mainObject.views.length; i++)
	{
		var view = this.mainObject.views[i];		
		var dm_sub = dm_view.addEntry(view.name, function(cls, view){ cls.loadView(view); cls.getObjects(); }, 
										"/images/icons/magnify_10.png", null, [this, view]);
		
	}
	dm_view.addEntry("Manage Views", function(cls, view){ cls.toggleViewsForm(); }, "/images/icons/settings_10.png", null, [this, view]);

	//addSubmenu = function (title, icon, funct, fargs)
	var dm_sub = dm_view.addSubmenu("Show Num Records", null, null, null);
	dm_sub.addEntry("25 Records", function(cls){ cls.setShowPer(25); }, "/images/icons/circle_blue.png", null, [this]);
	dm_sub.addEntry("50 Records", function(cls){ cls.setShowPer(50); }, "/images/icons/circle_blue.png", null, [this]);
	dm_sub.addEntry("100 Records", function(cls){ cls.setShowPer(100); }, "/images/icons/circle_blue.png", null, [this]);
	dm_sub.addEntry("200 Records", function(cls){ cls.setShowPer(200); }, "/images/icons/circle_blue.png", null, [this]);
	dm_sub.addEntry("500 Records", function(cls){ cls.setShowPer(500); }, "/images/icons/circle_blue.png", null, [this]);
	dm_sub.addEntry("1000 Records", function(cls){ cls.setShowPer(1000); }, "/images/icons/circle_blue.png", null, [this]);

    var dm_sub = dm_view.addSubmenu("Layout", null, null, null);
    dm_sub.addEntry("List Mode", function(cls){ cls.setBrowserMode("table"); }, "/images/icons/circle_blue.png", null, [this]);
    dm_sub.addEntry("Preview Mode", function(cls){ cls.setBrowserMode("previewV"); }, "/images/icons/circle_blue.png", null, [this]);

	return dm_view;
}

/**
 * Get views menu
 */
AntObjectBrowser.prototype.getViewsMenu = function() 
{
	var menu = new alib.ui.PopupMenu();

	for (var i = 0; i < this.mainObject.views.length; i++)
	{
		var view = this.mainObject.views[i];		

		var item = new alib.ui.MenuItem(view.name, {icon:"<img src='/images/icons/magnify_10.png' />"});
		item.cbData.cls = this;
		item.cbData.view = view;
		item.onclick = function() {
			this.cbData.cls.loadView(this.cbData.view);
			this.cbData.cls.getObjects();
		};
		menu.addItem(item);
	}
	
	var item = new alib.ui.MenuItem("Manage Views", {icon:"<img src='/images/icons/settings_10.png' />"});
	item.cbData.cls = this;
	item.onclick = function() { this.cbData.cls.toggleViewsForm(); };
	menu.addItem(item);

	// Add show number recirds submenu
	var subMenu = new alib.ui.SubMenu("Show Num Records");
	for (var i =  25; i < 500; i = i + 25)
	{
		var item = new alib.ui.MenuItem(i + " Records");
		item.cbData.cnt = i;
		item.cbData.cls = this;
		item.onclick = function() { this.cbData.cls.setShowPer(this.cbData.cnt); }
		subMenu.addItem(item);
	}
	menu.addItem(subMenu);

	// Add Layout submenu
	var subMenu = new alib.ui.SubMenu("Layout");

	var item = new alib.ui.MenuItem("List Mode", {});
	item.cbData.cls = this;
	item.onclick = function() { this.cbData.cls.setBrowserMode("table"); };
	subMenu.addItem(item);

	var item = new alib.ui.MenuItem("Preview Mode", {});
	item.cbData.cls = this;
	item.onclick = function() { this.cbData.cls.setBrowserMode("previewV"); };
	subMenu.addItem(item);

	menu.addItem(subMenu);

	return menu;
}

/**
 * Set how many items/objects to display per page
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {number} num The number of items to display per page
 */
AntObjectBrowser.prototype.setShowPer = function(num)
{
	this.limit=num; 
	this.getObjects();
    
    ajax = new CAjax('json');
    ajax.exec("/controller/User/setSettingUser", 
                [["set", "/objects/browse/showper/"+this.mainObject.name], ["val", num]]);
}

/**
 * Create views dropdown
 *
 * @private
 * @this {AntObjectBrowser}
 */
AntObjectBrowser.prototype.addAdditionalActions = function()
{
	switch (this.obj_type)
	{
	case 'email_thread':

		// Add to standard actions
		var act = this.addAction("Mark as Read", "/controller/Email/markRead", "/images/icons/read_10.png");
		var act = this.addAction("Mark as Unread", "/controller/Email/markUnread", "/images/icons/unread_10.png");
		var act = this.addAction("Mark as Junk Mail", "/controller/Email/markJunk", "/images/icons/spam_10.png");
		var act = this.addAction("Not Junk Mail", "/controller/Email/markNotjunk", "/images/icons/notspam_10.png");
		var act = this.addAction("Flag Message(s)", "/controller/Email/markFlag", "/images/icons/flag_on_10.png");

		break;
	}
}

/**
 * Set the view mode of this browser window and save the settings
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {string} mode Should be 'previewH', 'previewV', or 'table'
 */
AntObjectBrowser.prototype.setBrowserMode = function(mode)
{
	switch (mode)
	{
	case 'table':
		this.preview = false;
		this.viewmode = "table";
		this.browserCon.innerHTML = ""; 
		this.print(this.browserCon);
		break;
	case 'previewV':
		this.preview = true;
		this.viewmode = "details";
		this.browserCon.innerHTML = "";
		this.print(this.browserCon);
		break;
	}
    ajax = new CAjax('json');
    ajax.exec("/controller/User/setSettingUser", 
                [["set", "/objects/browse/mode/"+this.mainObject.name], ["val", mode]]);
}

/**
 * Toggle select/unselect all
 *
 * @public
 * @this {AntObjectBrowser}
 */
AntObjectBrowser.prototype.toggleSelectAll = function()
{
	for (var i = 0; i < this.objectList.length; i++)
	{
		this.objectList[i].select(this.fAllSelected);
	}
}

/**
 * Load objects into browser
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {number} offset The offset to start displaying. If null, then start at 0
 * @param {bool} update If set to true, then only load id and revision, not the rest of the data
 */
AntObjectBrowser.prototype.getObjects = function(offset, update)
{    
	if (this.skipLoad) // skip first time
	{
		this.skipLoad = false;
		return;
	}

	if (typeof offset != "undefined" && offset != null)
	{
		if (offset != this.m_lastOffset)
		{
			var update = false;
			this.firstLoaded = false;
		}

		this.m_lastOffset = offset;
	}
	else
	{
		this.m_lastOffset = 0;
		offset = 0;
	}

	if (!update)
    {
        this.blankMessageCon.innerHTML = "<div class='loading'></div>";
        alib.dom.styleSet(this.listObjectsCon, "display", "none");
    }		

	// Make sure only one request is processed at the same time
	if (this.m_ajax)
	{
		this.m_ajax.abort();

		if (!this.firstLoaded && update)
			var update = false;
	}

	// If an automatic refresh timeout is running then clear it first
	if (this.refreshTimer)
		clearTimeout(this.refreshTimer);

	this.m_ajax = new CAjax('json');

	this.m_ajax.m_browseclass = this;
	this.m_ajax.updateMode = (update) ? true : false;
	this.m_ajax.onload = function(result)
	{

		if (!this.updateMode)
			this.m_browseclass.objectList = new Array();

		this.m_browseclass.firstLoaded = true;

		var objListArr = new Array(); // used if in update mode
		var num_objects = 0;

		if (!this.updateMode)
		{                
			this.m_browseclass.listObjectsCon.innerHTML = "";
			this.m_browseclass.m_listTable = null;
		}

		// Update browse by absolute path if set
		// -------------------------------------------
		if(result && result.browseByCurPath)
		{
			if (result.browseByCurPath != this.m_browseclass.browseByPath)
				this.m_browseclass.browseByPath = result.browseByCurPath;
		}

		// Populate browseby(folders) if set
		// -------------------------------------------
		if(result && result.browseByObjects && result.browseByObjects.length)
		{
            this.m_browseclass.blankMessageCon.innerHTML = "";            
            alib.dom.styleSet(this.m_browseclass.listTitleDiv, "display", "block");
            alib.dom.styleSet(this.m_browseclass.listObjectsCon, "display", "block");
            
			var num_folders = parseInt(result.browseByObjects.length);
			for (var i = 0; i < result.browseByObjects.length; i++)
			{
				var objData = result.browseByObjects[i];
				objData.isBrowse = true;
				var pre = "";
				if (this.m_browseclass.browseByPath != "/") // leave root out because it is implied in the / path
					pre = this.m_browseclass.browseByPath;
				objData.browseByPath = pre + "/" + objData.name;

				if (this.updateMode)
				{
					objListArr[objListArr.length] = objData;
				}
				else
				{
					this.m_browseclass.addObjectItem(objData);
				}
			}
		}

		// The result will be held in a variable called 'retval'
		if(result && result.objects.length)
		{
            // Display back the view name and hide the blankMessageCon
            this.m_browseclass.blankMessageCon.innerHTML = "";            
            alib.dom.styleSet(this.m_browseclass.listTitleDiv, "display", "block");
            alib.dom.styleSet(this.m_browseclass.listObjectsCon, "display", "block");
            
			// Clear contents of list container 
			this.m_browseclass.m_contextCon.innerHTML = "";

			// Set number of objects string
			// -------------------------------------------
			if (this.m_browseclass.m_contextCon && result.totalNum != "undefined")
			{
				var lbl = alib.dom.createElement("span", this.m_browseclass.m_contextCon);
				if (this.m_browseclass.preview)
					lbl.innerHTML = result.totalNum + " Items";
				else
					lbl.innerHTML = result.totalNum + " " + this.m_browseclass.mainObject.titlePl;
			}

			// Handle pagination
			// -------------------------------------------
			if (result.paginate)
			{
				var prev = result.paginate.prevPage;
				var next = result.paginate.nextPage;
				var pag_str = result.paginate.desc;					
				
				var lbl = alib.dom.createElement("span", this.m_browseclass.m_contextCon);
				lbl.innerHTML = " | " + pag_str;
                
				if (prev >= 0 || next >=0)
				{
					var lbl = alib.dom.createElement("span", this.m_browseclass.m_contextCon);
					lbl.innerHTML = " | ";
					if (prev!=-1)
					{
						var lnk = alib.dom.createElement("span", this.m_browseclass.m_contextCon);
						lnk.innerHTML = "&laquo; previous";
						alib.dom.styleSet(lnk, "cursor", "pointer");
						lnk.start = prev;
						lnk.m_browseclass = this.m_browseclass;
						lnk.onclick = function()
						{
							this.m_browseclass.getObjects(this.start);
						}
					}

					if (next)
					{
						var lnk2 = alib.dom.createElement("span", this.m_browseclass.m_contextCon);
						lnk2.innerHTML = " next &raquo;";
						alib.dom.styleSet(lnk2, "cursor", "pointer");
						lnk2.start = next;
						lnk2.m_browseclass = this.m_browseclass;
						lnk2.onclick = function()
						{
							this.m_browseclass.getObjects(this.start);
						}
					}
				}
			}

			// Populate objects
			// -------------------------------------------
			num_objects = parseInt(result.objects.length);
			for (var i = 0; i < result.objects.length; i++)
			{
				var objData = result.objects[i];

				if (this.updateMode && alib.indexOf(this.m_browseclass.deletingQueue, objData.id) == -1)
				{
					objListArr[objListArr.length] = objData;
				}
				else
				{
					this.m_browseclass.addObjectItem(objData);
				}
			}

			if (this.updateMode)
				this.m_browseclass.refreshUpdate(objListArr);
		}
		else if (!result && !result.browseByObjects || (result.browseByObjects && !result.browseByObjects.length))
		{
			// TODO: display the below if we did a plain text search
			//this.m_browseclass.listObjectsCon.innerHTML = " <div style='padding:5px;'>No " +this.m_browseclass.mainObject.titlePl+ " were found</div>";            

			var objType = this.m_browseclass.mainObject.getObjType();
			this.m_browseclass.blankMessageCon.innerHTML = Ant.EntityDefinitionLoader.get(objType).getBrowserBlankContent();

            // Hide the title and result con
            alib.dom.styleSet(this.m_browseclass.listObjectsCon, "display", "none");
            //if(!this.m_browseclass.browseByField)
                //alib.dom.styleSet(this.m_browseclass.listTitleDiv, "display", "none");
		}
        
		// Select first row if this is the first load and we are in preview mode
		if (this.m_browseclass.preview && num_objects && !this.updateMode)
			this.m_browseclass.selectObjectRow();

		// If we are refreshing then set timeout for next refresh
		if (this.m_browseclass.refreshInterval)
		{
			var cls = this.m_browseclass;
			this.m_browseclass.refreshTimer = setTimeout(function() { cls.refresh(); }, cls.refreshInterval);
		}

		// Cleanup & resize now that data is loaded
		this.m_browseclass.m_ajax = null;        
        this.m_browseclass.resize();
        
        // Call onload callback
        this.m_browseclass.onLoad(num_objects);
	};

	var url = "/controller/ObjectList/query";

	var args = new Array();
	args[args.length] = ["obj_type", this.obj_type];
	args[args.length] = ["offset", offset];
	args[args.length] = ["limit", this.limit];
	if (this.showDeleted)
		args[args.length] = ["showdeleted", "1"];
	if (update)
		args[args.length] = ["updatemode", "1"];

	if (this.browseByField)
		args[args.length] = ["browsebyfield", this.browseByField];

	if (this.browseByPath)
		args[args.length] = ["browsebypath", this.browseByPath];
	
	if (this.browseByRootId) // realtive root
	{
		args[args.length] = ["browsebyroot", this.browseByRootId];
	}
	
	this.getFormConditions(null, args);

	this.m_ajax.exec(url, args);
}


/**
 * Set this browser to refresh automatically
 *
 * @public
 * @param {number} interval The interval to refresh, if < 1 (0 or null) then disable
 * @this {AntObjectBrowser}
 */
AntObjectBrowser.prototype.setAutoRefresh = function(interval)
{	
	if (!interval)
	{
		this.refreshInterval = null;
		clearTimeout(this.refreshTimer);
		this.refreshTimer = null;
	}

	this.refreshInterval = interval;

	if (!this.refreshTimer && interval)
	{
		var cls = this;
		this.refreshTimer = setTimeout(function() { cls.refresh(); }, this.refreshInterval);
	}
}

/**
 * Reload or refresh object list
 *
 * @public
 * @this {AntObjectBrowser}
 */
AntObjectBrowser.prototype.refresh = function()
{	
	var offset = (typeof this.m_lastOffset != 'undefined') ? this.m_lastOffset : 0;

	// Make sure we are not already loading for the first time. 
	// If it is there is no need to refresh.
	if (this.m_ajax && !this.firstLoaded)
	{
		if (this.m_ajax.loading)
			return;
	}
	
	// Do not refresh if we are performing a text search
	if (this.m_txtSearch && this.m_txtSearch.value && this.m_txtSearch.value!=this.searchTitleText)
		return;

	// Do not refresh if we are in the process of deleting objects
	// When deletion has been returned, then refresh will be called again
	if (this.deletingQueue.length > 0)
	{
		console.log("Skipping refresh because there are " + this.deletingQueue.length + " items in the deleting queue");
		return;
	}

	var updateRefresh = (this.optForceFullRefresh) ? false : true;
	this.getObjects(offset, updateRefresh);
}


/**
 * Reload or refresh object list but only get data for changed items - added, deleted, updated
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {array} newlist New list of objects created from the query to the server
 */
AntObjectBrowser.prototype.refreshUpdate = function(newlist)
{
	var objcon = this.listObjectsCon;
	//ALib.m_debug = true;

	// 1. Purge any items that are in the browser list but are not in the newlist
	// ------------------------------------------------------------------------------------
	var removalQueue = new Array();
	for (var j = 0; j < this.objectList.length; j++)
	{
		if (this.refreshUpdateItemInList(this.objectList[j].id, newlist)==-1)
		{
			removalQueue[removalQueue.length] = this.objectList[j];
		}
	}

	// remove items in the queue
	for (var i = 0; i < removalQueue.length; i++)
	{
		removalQueue[i].remove();
	}
	removalQueue = null; // cleanup

	// 2. Update and insert new nodes where necessary
	// ------------------------------------------------------------------------------------
	var moveQueue = new Array();
	for (var i = 0; i < newlist.length; i++)
	{
		var expos = this.refreshUpdateItemInList(newlist[i].id, this.objectList, true)

		if (expos != -1)
		{
			// Update existing item if revision has been incremented
			if (newlist[i].revision > this.objectList[expos].revision || 
				(newlist[i].num_comments != this.objectList[expos].objData.num_comments))
			{
				var obj = new CAntObject(this.mainObject.name, newlist[i].id);
				obj.objListItem = this.objectList[expos];
				obj.onload = function()
				{
					this.objListItem.update(this.getData());
				}
				obj.load();
			}

			// If position in current list is different from new list then queue for moving later
			if (expos != i)
				moveQueue[moveQueue.length] = {obj:this.objectList[expos], toidx:i};
		}
		else
		{

			// This is a brand new item that does not yet exist in the list, load and add it
			var obj = new CAntObject(this.mainObject.name, newlist[i].id);
			obj.appBrowserClass = this;
			obj.appObjectCon = objcon;
			obj.appObjectConInsBefore = ((i+1) > this.objectList.length) ? null : this.objectList[i];
			obj.onload = function()
			{
				this.appBrowserClass.addObjectItem(this.getData(), this.appObjectConInsBefore);
			}

			// Put placeholder in objectList array for reconciling - keep the lists synchronzed while object data is loading
			// This must be after the appObjectConInsBefore is set above
			var item = new AntObjectBrowser_Item({id:newlist[i].id, security:{view:true}, revision:newlist[i].revision}, this);
			if ((i+1) > this.objectList.length)
			{
				this.objectList.push(item);
			}
			else
			{
				this.objectList.splice(i, 0, item);
			}

			// Load data - this has to be done after the new item is created
			obj.load();
		}
	}

	// move items in the queue
	for (var i = 0; i < moveQueue.length; i++)
	{
		moveQueue[i].obj.move(moveQueue[i].toidx);
	}
	moveQueue = null; // cleanup
}

/**
 * Check if an object item is in a list
 *
 * @private
 * @this {AntObjectBrowser}
 * @param {number} objid The id of the object to look for
 * @param {array} list The list to search for objid
 * @return {number} the index of the object found in the list or -1 if not found.
 */
AntObjectBrowser.prototype.refreshUpdateItemInList = function(objid, list)
{
	for (var j = 0; j < list.length; j++)
	{
		if (list[j].id == objid)
		{
			return j;
		}
	}

	return -1;
}

/**
 * Add an object to the list
 *
 * @private
 * @this {AntObjectBrowser}
 * @param {DOMElement} rowscon The container that is holding the items
 * @param {Object} objData The object data that this row represents
 * @param {AntObjectBrowser_Item} insertBeforeItem The item to insert before
 * @param {bool} update If set to true, redraw the item
 */
AntObjectBrowser.prototype.addObjectItem = function(objData, insertBeforeItem, update)
{
	if (update)
	{
		for (var i = 0; i < this.objectList.length; i++)
		{
			if (this.objectList[i].id == update)
				this.objectList[i].update(objData);
		}
	}
	else
	{
		// Create containing table for this list if it does not already exist
		if (!this.m_listTable)
			this.createListTable();

		var objListItem = new AntObjectBrowser_Item(objData, this);
        objListItem.selectedObjId = this.selectedObjId;

		// Check for browseBy
		if (objData.isBrowse && objData.browseByPath)
		{
			objListItem.isBrowse = objData.isBrowse;
			objListItem.browseByPath = objData.browseByPath;
		}

		if (insertBeforeItem)
			objListItem.print(this.m_listTableBody, insertBeforeItem);
		else
			objListItem.print(this.m_listTableBody);

		// find position of this item, it was added to the objectList array alraedy before the load
		var ind = -1;
		for (var i = 0; i < this.objectList.length; i++)
		{
			if (this.objectList[i].id == objListItem.id)
				ind = i;
		}
		
		if (ind >= 0) // If called with insertBeforeItem the item should already be set in the list
			this.objectList[ind] = objListItem;
		else
			this.objectList[this.objectList.length] = objListItem;
	}
}

/**
 * Create table for object list
 *
 * @private
 */
AntObjectBrowser.prototype.createListTable = function()
{
	// Setup the containing table
	this.m_listTable = alib.dom.createElement("table", this.listObjectsCon);
	alib.dom.styleSetClass(this.m_listTable, "aobListTable");
	this.m_listTable.cellPadding = 0;
	this.m_listTable.cellSpacing = 0;
	this.m_listTableBody = alib.dom.createElement("tbody", this.m_listTable);

	// Print headers
	if (this.viewmode == "table")
	{
		var rw = alib.dom.createElement("tr", this.m_listTableBody);

		var sel_all = alib.dom.createElement("input");
		sel_all.type = "checkbox";
		sel_all.cls = this;
		sel_all.onclick = function() { this.cls.fAllSelected = this.checked; this.cls.toggleSelectAll(); }
		if (!this.hideCheckbox)
		{
			var th = alib.dom.createElement("th", rw);
			alib.dom.styleSet(th, "text-align", "center");
			alib.dom.styleSet(th, "padding-left", "0px");
			alib.dom.styleSet(th, "padding-right", "5px");
			th.appendChild(sel_all);
		}

		// Now add the rest of the fields
		for (var j = 0; j < this.view_fields.length; j++)
		{
			var fld_def = this.mainObject.getFieldByName(this.view_fields[j].fieldName);
            if(fld_def)
			    var th = alib.dom.createElement("th", rw, fld_def.title);
		}
	}
}

/**
 * Select a row. If no id is provided, then select the first item in the list.
 *
 * @private
 * @this {AntObjectBrowser}
 * @param {number} id The object id of the item to select
 * @param {event} event DOM window even used to check if shift or ctrl keys are pressed
 */
AntObjectBrowser.prototype.selectObjectRow = function(id, event)
{
	var shiftPressed = false;
	var ctrlPressed = false;

	// used for folder browsing
	var isBrowse = false;
	var browseByPath = "";

	if (window.event)
	{
		shiftPressed = window.event.shiftKey;
		ctrlPressed = window.event.ctrlKey;
	}
	else
	{
		if (event)
		{
			shiftPressed = event.shiftKey;
			ctrlPressed = event.ctrlKey;
		}
	}

	if (!id)
		var id = (this.objectList.length) ? this.objectList[0].id : "";

	// Name / label of this item
	var name = id;

	if (shiftPressed)
		return this.selectDetRowShift(this.curObjLoaded, id);

	// Set list item selected
	for (var i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].id == id)
		{
			if (ctrlPressed)
			{
				if (this.objectList[i].selected())
				{
					this.objectList[i].select(false);
					this.objectList[i].seen = true;
				}
				else
				{
					this.objectList[i].select(true);
					this.objectList[i].seen = true;
				}
			}
			else
			{
				name = this.objectList[i].getName();
				this.objectList[i].select(true);
			}

			if (this.objectList[i].isBrowse)
			{
				isBrowse = this.objectList[i].isBrowse;
				browseByPath = this.objectList[i].browseByPath
			}
		}
		else if (!ctrlPressed)
		{
			this.objectList[i].select(false);
		}
	}

	if (!ctrlPressed && id)
	{
		if (isBrowse && browseByPath)
			this.changeBrowseByPath(browseByPath);
		else
			this.loadObjectForm(id, null, name);
	}
	else if (!ctrlPressed && id=="")
	{
		this.previewCon.innerHTML = "";
	}
}

/**
 * If we are navigating in folder/browseby view then this function changes the path
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {string} path The full path to load
 */
AntObjectBrowser.prototype.changeBrowseByPath = function(path)
{
	this.browseByPath  = path;
	this.getObjects(0, false); // Clear results and start at 0
	this.buildBrowseByPath();
}

/**
 * Implment shift+select rows. Select everything between currsel and newsel
 *
 * @private
 * @this {AntObjectBrowser}
 * @param {number} currsel Starting id already selected
 * @param {number} newsel New id selected
 */
AntObjectBrowser.prototype.selectDetRowShift = function(currsel, newsel)
{
	var currpos = null;
	var newpos = null;

	// Get currpos
	for (i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].id == currsel)
			currpos = i;
	}
	
	// Get newpos
	for (i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].id == newsel)
			newpos = i;
	}
	
	var start = (currpos < newpos) ? currpos : newpos;
	var end = (currpos > newpos) ? currpos : newpos;

	// Get newpos
	for (i = 0; i < this.objectList.length; i++)
	{
		if (i >= start && i <= end)
		{
			this.objectList[i].select(true);
		}
		else
		{
			this.objectList[i].select(false);
		}
	}
}

/**
 * Set user image
 *
 * @private
 * @this {AntObjectBrowser}
 * @param {DOMElement} con Container where the image will be printed
 * @param {number} user_id The unique id of the user
 * @param {sting} user_name Optional name of the user
 */
AntObjectBrowser.prototype.setUserImage = function(con, user_id, user_name)
{
	var path = "/files/userimages/";
	path += (user_id) ? user_id : user_name;
	path += "/48/48";

	var img = alib.dom.createElement("img", con);
	alib.dom.styleSet(img, "width", "48px");
	img.src = path;

	if (user_id)
		AntObjectInfobox.attach("user", user_id, img);
}


/**
 * Set AntView for loading objects
 *
 * @private
 * @this {AntObjectBrowser}
 * @param {AntView} parentView The parent view of this view
 */
AntObjectBrowser.prototype.setAntView = function(parentView)
{
	this.antView = parentView;
	this.antView.setViewsSingle(true);
	this.antView.options.bcls = this;
	this.antView.onresize = function()
	{
		this.options.bcls.resize();
	}

	// Set refresh interval time
	var refreshInterval = 60000;
	switch (this.obj_type)
	{
	case 'comment':
		refreshInterval = 10000;
		break;
	}

	// Add auto-refresh when displayed and clear when hidden - every 10 seconds
	this.antView.on("show", function(opts) { opts.cls.setAutoRefresh(refreshInterval); }, { cls:this });
	this.antView.on("hide", function(opts) { opts.cls.setAutoRefresh(null); }, { cls:this });
	this.setAutoRefresh(refreshInterval); // Assume this browser is visible on initialization

	this.getObjectView(this.obj_type);
}

/**
 * Dynamically get or create an object loader view
 *
 * @param {string} objType The object type we are loading
 */
AntObjectBrowser.prototype.getObjectView = function(objType)
{
	if (!this.antView)
		return null;

	// Check if we already created this view
	if (this.antView.getView(objType + ":[id]"))
		return this.antView.getView(objType + ":[id]")

	var viewItem = this.antView.addView(objType+":[id]", {});
	viewItem.options.obj_type = objType;
	viewItem.options.bwserCls = this;
    viewItem.options.loadLoaded = null;
	viewItem.options.parentPath = this.antView.getPath();

	viewItem.render = function() { }

	viewItem.onshow = function()  // draws in onshow so that it redraws every time
	{ 
		// Do not reload if this object id is already loaded
		//if (this.variable && this.options.lastLoaded == this.variable)
        //alert(this.options.lastLoaded + "==" + this.variable)
		if (this.options.lastLoaded == this.variable && !this.fromClone)
			return true;
            
		this.con.innerHTML = "";
		this.title = ""; // because objects are loaded in the same view, clear last title
		var ol = new AntObjectLoader(this.options.obj_type, this.variable);
		ol.setAntView(this);
        
		// Set associations and values for new objects
		if (!this.variable)
		{
			for (var i = 0; i < this.options.bwserCls.m_filters.length; i++)
			{
				var cond = this.options.bwserCls.m_filters[i];
				if (this.options.bwserCls.m_filters[i].fieldName == "associations")
					ol.mainObject.setMultiValue('associations', cond.value);
				else
					ol.setValue(cond.fieldName, cond.value);
			}
			if (this.options.bwserCls.obj_reference)
				ol.setValue("obj_reference", this.options.bwserCls.obj_reference);

			// Set common fields if objectContext is set and this is a new object (no id)
			if (this.options.bwserCls.objectContext != null)
			{
				var contextFields = this.options.bwserCls.objectContext.getFields();
				var objFields = this.options.bwserCls.mainObject.getFields();

				for (var i in contextFields)
				{
					var conField = contextFields[i];

					for (var j in objFields)
					{
						var objField = objFields[j];

						if ((objField.type == "object" || objField.type == "fkey") && objField.name == conField.name 
							&& objField.type == conField.type && objField.subtype == conField.subtype)
						{
							// Make sure value has not been set by filters which should override
							if (!ol.getValue(objField.name))
							{
								ol.setValue(objField.name, this.options.bwserCls.objectContext.getValue(objField.name));
							}
						}
					}
				}
			}
		}
		ol.print(this.con);
		ol.cbData.antView = this;
        ol.cbData.bwserCls = this.options.bwserCls;
		ol.cbData.parentPath = this.options.parentPath;
		ol.onClose = function() 
		{ 
			this.cbData.antView.options.lastLoaded = this.mainObject.id; // Set so this form reloads to new form if newly saved id

			// Added this to give the list 1 second to refresh because we are 
			// working with almost real-time indexes (elasticsearch) now
			var bcls = this.cbData.bwserCls;
			setTimeout(function(){ bcls.refresh(); }, 1000);
			//this.cbData.bwserCls.refresh(); 
			
			// Move up to previous view
			this.cbData.antView.goup(); 
		}
		ol.onRemove = function() { this.cbData.bwserCls.refresh(); }

        if(ol.cloneObject) // If current object is cloned, reset the lastLoaded variable
        {
            this.options.lastLoaded = null;
        }
        else
		    this.options.lastLoaded = this.variable;
	};

	return viewItem;
}

/**
 * Upload files to the current working directory
 */
AntObjectBrowser.prototype.antFsUpload = function()
{
	var cfupload = new AntFsUpload(this.browseByPath, this.m_dlg);
	cfupload.cbData.cls = this;
	cfupload.onUploadFinished = function()
	{
		this.cbData.cls.getObjects();
	}
	cfupload.showDialog();
}


/**
 * Create new browseby (folder) in the current directory
 */
AntObjectBrowser.prototype.antFsCreateFolder = function()
{
	var dlg_p = new CDialog();
	dlg_p.promptBox("Name:", "New Folder Name", "New Folder");
	dlg_p.m_cls = this;
	dlg_p.onPromptOk = function(val)
	{
		var args = [["path", this.m_cls.browseByPath], ["name", val]];
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this.m_cls;
        ajax.onload = function(ret)
        {
            if(!ret['error'])
                this.cbData.cls.getObjects();
			else 
				alert(ret['error']);
        };
        ajax.exec("/controller/AntFs/newFolder", args);
	}
    
    dlg_p.m_input.onblur = function ()
    {
        checkSpecialCharacters("folder", this.value, this);
    }
}

/**
 * Move files and folders to a new folder
 *
 * @param int toFid If set then call controller, otherwise show browse dialog
 */
AntObjectBrowser.prototype.antFsMove = function(toFid)
{
	var toFolder = (typeof toFid != "undefined") ? toFid : null;

	if (toFolder == null)
	{
		var cbrowser = new AntFsOpen();
		cbrowser.filterType = "folder";
		cbrowser.setTitle("Move Files &amp; Folders To:");
		cbrowser.cls = this;
		/*
		cbrowser.file_id = (file_id) ? file_id : null;
		cbrowser.folder_id = (folder_id) ? folder_id : null;
		*/
		cbrowser.onSelect = function(fid, name, path) 
		{
			this.cls.antFsMove(fid);
		}
		cbrowser.showDialog(); 

		return;
	}

	// Create loading div
	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Moving items, please wait...";
	dlg.statusDialog(dv_load, 150, 100);
	
	var args = [["obj_type", this.mainObject.name], ["move_to_id", toFolder]];

	var fIsSelected = false;
	for (var i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].selected())
		{
			args[args.length] = ["objects[]", this.objectList[i].id];
			fIsSelected = true;
		}
	}

	// Find out if nothing is selected (send all)
	if (!fIsSelected)
	{
		if (!confirm("This will move all "+this.mainObject.titlePl+" in the current view. Are you sure you want to continue?"))
		{
			dlg.hide();
			return;
		}

		args[args.length] = ["all_selected", "1"];
	}

	this.getFormConditions(null, args);
	
	ajax = new CAjax('json');
	ajax.cls = this;
	ajax.dlg = dlg;
	ajax.onload = function(ret)
	{
		this.dlg.hide();

		if (!ret['error'])
		{
			var waserror = false;
							
			for (moved in ret)
			{
				var currentDeleted = ret[moved];
				if (currentDeleted != "-1")
				{
					for (var j = 0; j < this.cls.objectList.length; j++)
					{
						if (this.cls.objectList[j].id == currentDeleted)
						{
							this.cls.objectList[j].remove();
						}
					}
				}
				else
				{
					waserror = true;
				}
			}

			if (waserror)
				ALib.statusShowAlert("ERROR: Not all objects were moved!", 3000, "bottom", "right");
			else
				ALib.statusShowAlert(ret.length + " Items Moved!", 3000, "bottom", "right");

			if (this.cls.viewmode == "details")
				this.cls.refresh();
		}
		else
		{
			ALib.statusShowAlert("ERROR: Could not contact server!", 3000, "bottom", "right");
		}    
	};

	ajax.exec("/controller/AntFs/move", args);
}
 
/**
 * Open an object form by id
 *
 * If view is avaiable then a view will be used to load the form
 *
 * @param {int} id The id of the object to load
 * @param {Array} param_fwd Array of array of params [['pname', 'pvalue']]
 * @param {string} label Used if we are in "select" mode to pass name to callback.
 * @param {string} obj_type Optional manual object type to load. By default this.obj_type is used.
 */
AntObjectBrowser.prototype.loadObjectForm = function(id, param_fwd, label, obj_type)
{
	var params = (param_fwd) ? param_fwd : new Array();
	if (!param_fwd)
		var param_fwd = null;

	var obj_type = obj_type || this.obj_type;

	// Set filters as params to foward to object
	for (var i = 0; i < this.m_filters.length; i++)
	{
		var cond = this.m_filters[i];
		if (this.m_filters[i].fieldName == "associations")
			params[params.length] = ["associations[]", cond.value];
		else
			params[params.length] = [cond.fieldName, cond.value];
	}
	if (this.obj_reference)
	{
		params[params.length] = ["obj_reference", this.obj_reference];
	}

	var oid = (id) ? id : "";
	if (oid)
		this.curObjLoaded = oid;

	// Set common fields if objectContext is set and this is a new object (no id)
	if (!oid && this.objectContext != null)
	{
		var contextFields = this.objectContext.getFields();
		var objFields = this.mainObject.getFields();

		for (var i in contextFields)
		{
			var conField = contextFields[i];

			for (var j in objFields)
			{
				var objField = objFields[j];

				if ((objField.type == "object" || objField.type == "fkey") && objField.name == conField.name 
					&& objField.type == conField.type && objField.subtype == conField.subtype)
				{
					// Make sure params are not yet set
					var notSet = true;
					for (var m in params)
					{
						if (params[m][0] == objField.name)
							notSet = false;
					}

					if (notSet)
					{
						params[params.length] = [objField.name, this.objectContext.getValue(objField.name)];
					}
				}
			}
		}
	}	

	// Find out if browser is functioning as a select popup
	if (this.useSelect)
	{
		this.select(oid, label);
	}
	else if (this.open_handler)
	{
		switch (this.open_handler)
		{
		case 'email_message_draft':
			loadObjectForm("email_message", oid);
			break;
		}
	}
	else if (this.antView && !this.preview && this.getObjectView(obj_type))
	{
		this.antView.navigate(obj_type + ":" + oid);
	}
	else
	{
		var url = '/obj/' + obj_type;
		if (oid)
			url += '/' + oid;

		// If views are being used, allow divs and viewable areas to be managed externally
		if (this.mainBrowserWorkspace && this.browserCon) // load in a new div in the browser container
		{
			var oldScrollTop = alib.dom.getScrollPosTop(); // this.browserCon.scrollTop;
			
			if (this.preview && this.previewCon)
			{
				this.previewCon.innerHTML = "";
				var objfrmCon = this.previewCon;
				objfrmCon.cls = this;
				objfrmCon.oldScrollTop = oldScrollTop;
				objfrmCon.close = function()
				{
					// TODO: perform close
					//ALib.m_debug = true;
					//ALib.trace("Perform Close");
				}
			}
			else
			{
				alib.dom.styleSet(this.titleCon, "display", "none");
				alib.dom.styleSet(this.innerCon, "display", "none");
				var objfrmCon = alib.dom.createElement("div", this.browserCon);
				objfrmCon.cls = this;
				objfrmCon.oldScrollTop = oldScrollTop;
				objfrmCon.close = function()
				{                        
					this.style.display = "none";
					alib.dom.styleSet(this.cls.titleCon, "display", "block");
					alib.dom.styleSet(this.cls.innerCon, "display", "block");
					objfrmCon.cls.browserCon.removeChild(this);
					alib.dom.setScrollPosTop(this.oldScrollTop);
				}
			}

			// Print object loader 
			var ol = new AntObjectLoader(obj_type, oid);
				
			if (this.preview)
			{
				ol.fEnableClose = false;
				ol.inline = true;
			}

			if (!id)
			{
				// Set associations and values
				for (var i = 0; i < this.m_filters.length; i++)
				{
					var cond = this.m_filters[i];
					if (this.m_filters[i].fieldName == "associations")
						ol.mainObject.setMultiValue('associations', cond.value);
					else
						ol.setValue(cond.fieldName, cond.value);
				}
				if (this.obj_reference)
					ol.setValue("obj_reference", this.obj_reference);
			}
				
			// Use ol.print only for default               
			ol.print(objfrmCon);
			
			ol.objfrmCon = objfrmCon;
			ol.objBrwsrCls = this;
			ol.onClose = function()
			{                    
				this.objfrmCon.close();
			}
			if (!this.preview)
			{
				ol.onSave = function()
				{
					this.objBrwsrCls.getObjects();
				}
			}
			ol.onRemove = function()
			{
				this.objBrwsrCls.getObjects();
			}
		}
		else if (this.loaderCls) // Browser is nested in an object loader (form)
		{
			var oldScrollTop = alib.dom.getScrollPosTop();
			this.loaderCls.hide();

			var objfrmCon = alib.dom.createElement("div", this.loaderCls.outerCon);
			objfrmCon.cls = this;
			objfrmCon.oldScrollTop = oldScrollTop;
			objfrmCon.close = function()
			{                    
				this.style.display = "none";
				objfrmCon.cls.loaderCls.show();
				objfrmCon.cls.loaderCls.outerCon.removeChild(this);
				alib.dom.setScrollPosTop(this.oldScrollTop);
			}

			// Print object loader 
			var ol = new AntObjectLoader(obj_type, oid);

			// Set associations and values
			for (var i = 0; i < this.m_filters.length; i++)
			{
				var cond = this.m_filters[i];
				if (this.m_filters[i].fieldName == "associations")
					ol.mainObject.setMultiValue('associations', cond.value);
				else
					ol.setValue(cond.fieldName, cond.value);
			}
			if (this.obj_reference)
				ol.setValue("obj_reference", this.obj_reference);
			
			ol.print(objfrmCon, this.loaderCls.isPopup);
				
			ol.objfrmCon = objfrmCon;
			ol.objBrwsrCls = this;
			ol.onClose = function()
			{
				this.objfrmCon.close();
			}
			ol.onSave = function()
			{
				this.objBrwsrCls.getObjects();
			}
			ol.onRemove = function()
			{
				this.objBrwsrCls.getObjects();
			}
		}
		else
		{
			loadObjectForm(obj_type, oid, null, null, params);
		}
	}
}

/**
 * Show advanced search dialog
 *
 * @param {AntObjectBrowserView} view Optional view to be edited other than the current view
 */
AntObjectBrowser.prototype.showAdvancedSearch = function(view)
{
	var ed = new AntObjectViewEditor(this.obj_type, this.currentView);
	ed.cbData.cls = this;
	ed.showDialog();
	ed.onApply = function(view)
	{
		this.cbData.cls.loadView(view);
		this.cbData.cls.getObjects();
	}
}


/**
 * Display views form
 */
AntObjectBrowser.prototype.toggleViewsForm = function()
{
	if (this.m_viewsDlg)
	{
		try
		{
			this.m_viewsDlg.hide();
			this.m_viewsDlg = null;
		}
		catch(e) { }
	}
	else
	{
		this.m_viewsDlg = new CDialog("Manage " + this.mainObject.title + " Views");
		this.m_viewsDiv = alib.dom.createElement("div");
		this.m_viewsDlg.customDialog(this.m_viewsDiv, 600, 400);
		this.showViewsDialog();
	}

}

/**
 * Render  "Manage Views" dialog
 */
AntObjectBrowser.prototype.showViewsDialog = function()
{
	// Display information
	var info = alib.dom.createElement("p", this.m_viewsDiv);
	alib.dom.styleSetClass(info, "info");
	info.innerHTML = "Views are a simple way to save advanced queries and quickly reload them. You can select your default view by checking the 'Default' column which means the first time you load this form, the selected view will be used to filter your results. To create a new view, click 'Create New View' below.";

	var wfViews = new CWindowFrame("Views", null, "0px");
	wfViews.print(this.m_viewsDiv);
	var wfcon = wfViews.getCon();
	alib.dom.styleSet(wfcon, "height", "250px");
	alib.dom.styleSet(wfcon, "overflow", "auto");

	var tbl = new CToolTable("100%");
	tbl.print(wfcon);
	tbl.addHeader("Name (click to edit)");
	tbl.addHeader("Description");
	tbl.addHeader("Default", "center", "30px");
	tbl.addHeader("&nbsp;", "center", "20px");

	var icon = (typeof(Ant)=='undefined') ? "/images/icons/deleteTask.gif" : "/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif";

	for (var i = 0; i < this.mainObject.views.length; i++)
	{
		var view = this.mainObject.views[i];

		if (view.filterKey != this.viewsFilterKey)
			continue;

		var rw = tbl.addRow();
		
		var lnk = alib.dom.createElement("a");
		lnk.innerHTML = view.name;
		lnk.href = "javascript:void(0)";
		lnk.cls = this;
		lnk.view = view;
		lnk.onclick = function() 
		{ 
			this.cls.toggleViewsForm();
			this.cls.showAdvancedSearch(this.view);
		} 
		rw.addCell(lnk);

		rw.addCell(view.description);

		var def = alib.dom.createElement("input");
		def.type = "radio";
		def.name = "default_view";
		def.cls = this;
		def.checked = view.fDefault;
		def.view = view;
		def.onclick = function() 
		{ 
			this.cls.setDefaultView(this.view);
		} 
		rw.addCell(def);

		if (!view.fSystem)
		{
			var del = alib.dom.createElement("a");
			del.innerHTML = "<img src='"+icon+"' border='0' />";
			del.href = "javascript:void(0)";
			del.cls = this;
			del.row = rw;
			del.view = view;
			del.onclick = function() 
			{ 
				this.cls.deleteView(this.view, this.row);
			} 
		}
		else
		{
			var del = alib.dom.createElement("span");
		}
		rw.addCell(del, false, "center");
	}

	var btn_dv = alib.dom.createElement("div", this.m_viewsDiv);
	alib.dom.styleSet(btn_dv, "margin", "3px");
	var btn = new CButton("Close", function(cls) {cls.toggleViewsForm(); }, [this], "b1");
	btn.print(btn_dv);

	var btn = new CButton("Create New View", function(cls) { cls.toggleViewsForm(); cls.showAdvancedSearch(); }, [this], "b1");
	btn.print(btn_dv);
}

/**
 * Set a view as the default to load the next time the browser is loaded
 *
 * @param {Object} view The view to set as the default
 */
AntObjectBrowser.prototype.setDefaultView = function(view)
{	
	for (var i = 0; i < this.mainObject.views.length; i++)
	{
		this.mainObject.views[i].fDefault = (this.mainObject.views[i] == view) ? true : false;
	}

	var args = [["obj_type", this.mainObject.name], ["view_id", view.id], ["filter_key", this.viewsFilterKey]];

	ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if (!ret['error'])
        {
            // Update cache to reflect new default view
			Ant.EntityDefinitionLoader.get(this.cls.obj_type).load();

            ALib.statusShowAlert("Default View Changed!", 3000, "bottom", "right");
        }
        else
        {
            ALib.statusShowAlert("ERROR: Could not contact server!", 3000, "bottom", "right");
        }   
    };
    ajax.exec("/controller/Object/setViewDefault", args);
}

/*************************************************************************
*	Function:	saveViewDialog
*
*	Purpose:	Save changes to a view
**************************************************************************/
AntObjectBrowser.prototype.saveViewDialog = function(view, saveas)
{
	var dlg = new CDialog("Save View", this.m_advancedSearchDlg);
	var dv = alib.dom.createElement("div");

	// Name
	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "label");
	lbl.innerHTML = "Name This View";
	var inp_dv = alib.dom.createElement("div", dv);
	var txtName = alib.dom.createElement("input", inp_dv);
	alib.dom.styleSet(txtName, "width", "98%");

	// Description
	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "label");
	lbl.innerHTML = "Description";
	var inp_dv = alib.dom.createElement("div", dv);
	var txtDescription = alib.dom.createElement("textarea", inp_dv);
	alib.dom.styleSet(txtDescription, "width", "98%");

	var save_view_id = "";

	if (view)
	{
		txtName.value = view.name;
		txtDescription.value = view.description;

		if (saveas)
		{
			txtName.value += " (copy)";
		}
		else
		{
			save_view_id = view.id;
		}
	}
	else
	{
		txtName.value = "My Custom View";
		txtDescription.value = "Describe this view here";
	}

	var dv_btn = alib.dom.createElement("div", dv);
	var btn = new CButton("Save", function(cls, dlg, name, description, save_view_id) {  dlg.hide(); cls.saveView(name.value, description.value, save_view_id);  }, 
							[this, dlg, txtName, txtDescription, save_view_id], "b2");
	btn.print(dv_btn);
	var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [dlg], "b1");
	btn.print(dv_btn);

	dlg.customDialog(dv, 300, 130);
}

/*************************************************************************
*	Function:	saveView
*
*	Purpose:	Save changes to a view (or enter a new one)
**************************************************************************/
AntObjectBrowser.prototype.saveView = function(name, description, save_view_id)
{
	// Create loading div
	var dlg = new CDialog(null, this.m_advancedSearchDlg);
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Saving, please wait...";
	dlg.statusDialog(dv_load, 150, 100);

	var view = null;

	this.searchView.conditions = new Array();
	for (var i = 0; i < this.conditionObj.getNumConditions(); i++)
	{
		var cond = this.conditionObj.getCondition(i);

		this.searchView.conditions[i] = new Object();
		this.searchView.conditions[i].blogic = cond.blogic;
		this.searchView.conditions[i].fieldName = cond.fieldName;
		this.searchView.conditions[i].operator = cond.operator;
		this.searchView.conditions[i].condValue = cond.condValue;
	}

	if (save_view_id)
	{
		view = this.mainObject.getViewById(save_view_id);
	}
	else
	{
		view = new AntObjectBrowserView(this.mainObject.name);
	}

	if (view)
	{
		view.conditions = this.searchView.conditions.slice(0);
		view.sort_order = this.searchView.sort_order.slice(0);
		view.view_fields = this.searchView.view_fields.slice(0); 
		view.name = name;
		view.filterKey = this.viewsFilterKey;
		view.description = description;
		view.cls = this;
		view.save_view_id = (save_view_id) ? save_view_id : null;
		view.dlg = dlg;
		view.onsave = function()
		{
			dlg.hide();
			if (!this.save_view_id)
				this.cls.mainObject.views[this.cls.mainObject.views.length] = this;

			ALib.statusShowAlert("View Saved!", 3000, "bottom", "right");

			this.cls.buildViewsDropdown();
			this.cls.showAdvancedSearch();
			this.cls.runSearch(this);

			// Update global object def cache
			if (typeof objectPreloadDef != "undefined")
				objectPreloadDef(this.cls.mainObject.name, true);
		}
		view.onsaveError = function()
		{
			dlg.hide();
			ALib.statusShowAlert("ERROR: Unable to connect to server!", 3000, "bottom", "right");
		}
		view.save();
	}
}

/*************************************************************************
*	Function:	runSearch
*
*	Purpose:	Apply and perform advanced query
**************************************************************************/
AntObjectBrowser.prototype.runSearch = function(view)
{
	// TODO: move to temp view
	/*
	this.currentViewFields = new Array();

	for (var i = 0; i < this.view_fields.length; i++)
	{
		this.currentViewFields[i] = this.view_fields[i].fieldName;
	}
	*/

	this.searchView.conditions = new Array();
	for (var i = 0; i < this.conditionObj.getNumConditions(); i++)
	{
		var cond = this.conditionObj.getCondition(i);

		this.searchView.conditions[i] = new Object();
		this.searchView.conditions[i].blogic = cond.blogic;
		this.searchView.conditions[i].fieldName = cond.fieldName;
		this.searchView.conditions[i].operator = cond.operator;
		this.searchView.conditions[i].condValue = cond.condValue;

		//ALib.m_debug = true;
		//ALib.trace(cond.fieldName + " : " + cond.condValue);
	}

	this.loadView((view) ? view : this.searchView);

	this.getObjects();
}

/*************************************************************************
*	Function:	addOrderBy
*
*	Purpose:	Add a sort order entry
**************************************************************************/
AntObjectBrowser.prototype.addOrderBy = function(con, fieldName, order)
{
	var sel_field = (fieldName) ? fieldName : "";
	var sel_order = (order) ? order : "asc";

	if (typeof this.orderBySerial == "undefined")
		this.orderBySerial = 1;
	else
		this.orderBySerial++;

	var dv = alib.dom.createElement("div", con);

	if (this.searchView.sort_order.length)
	{
		var lbl = alib.dom.createElement("span", dv);
		lbl.innerHTML = "Then By: ";
	}

	var ind = this.searchView.sort_order.length;
	this.searchView.sort_order[ind] = new Object();
	this.searchView.sort_order[ind].id = this.orderBySerial;
	this.searchView.sort_order[ind].fieldName = sel_field;
	this.searchView.sort_order[ind].order = sel_order;

	// Add field name
	var field_sel = alib.dom.createElement("select", dv);
	field_sel.orderobj = this.searchView.sort_order[ind];
	field_sel.onchange = function() { this.orderobj.fieldName = this.value; };
	var fields = this.mainObject.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		if (fields[i].type != "fkey_multi")
		{
			field_sel[field_sel.length] = new Option(fields[i].title, fields[i].name, false, (sel_field==fields[i].name)?true:false);
		}
	}

	if (!this.searchView.sort_order[ind].fieldName)
		this.searchView.sort_order[ind].fieldName = field_sel.value;

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " ";
	
	// Add order (asc/desc)
	var order_sel = alib.dom.createElement("select", dv);
	order_sel.orderobj = this.searchView.sort_order[ind];
	order_sel.onchange = function() { this.orderobj.order = this.value; };
	order_sel[order_sel.length] = new Option("Ascending", "asc", false, (sel_order == "asc")?true:false);
	order_sel[order_sel.length] = new Option("Descending", "desc", false, (sel_order == "desc")?true:false);

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " ";

	var icon = (typeof(Ant)=='undefined') ? "/images/icons/deleteTask.gif" : "/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif";
	var del = alib.dom.createElement("a", dv);
	del.innerHTML = "<img src='"+icon+"' border='0' />";
	del.href = "javascript:void(0)";
	del.odv = dv;
	del.pdv = con;
	del.cls = this;
	del.orderid = this.orderBySerial;
	del.onclick = function() 
	{ 
		for (var i = 0; i < this.cls.searchView.sort_order.length; i++)
		{
			if (this.cls.searchView.sort_order[i].id == this.orderid)
				this.cls.searchView.sort_order.splice(i, 1);
		}

		this.pdv.removeChild(this.odv); 
	} 
}

/*************************************************************************
*	Function:	addViewColumn
*
*	Purpose:	Add a column view drop-down
**************************************************************************/
AntObjectBrowser.prototype.addViewColumn = function(con, field_name)
{
	var selected_field = (field_name) ? field_name : "";

	if (typeof this.viewCOlSerial == "undefined")
		this.viewCOlSerial = 1;
	else
		this.viewCOlSerial++;

	var dv = alib.dom.createElement("div", con);

	var ind = this.searchView.view_fields.length;
	this.searchView.view_fields[ind] = new Object();
	this.searchView.view_fields[ind].id = this.viewCOlSerial;
	this.searchView.view_fields[ind].fieldName = selected_field;

	// Add field name
	var field_sel = alib.dom.createElement("select", dv);
	field_sel.viewobj = this.searchView.view_fields[ind];
	field_sel.onchange = function() { this.viewobj.fieldName = this.value; };
	var fields = this.mainObject.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		//if (fields[i].type != "fkey_multi")
		//{
			field_sel[field_sel.length] = new Option(fields[i].title, fields[i].name, false, (fields[i].name == selected_field)?true:false);
		//}
	}

	if (!this.searchView.view_fields[ind].fieldName)
		this.searchView.view_fields[ind].fieldName = field_sel.value;

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " ";

	var icon = (typeof(Ant)=='undefined') ? "/images/icons/deleteTask.gif" : "/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif";
	var del = alib.dom.createElement("a", dv);
	del.innerHTML = "<img src='"+icon+"' border='0' />";
	del.href = "javascript:void(0)";
	del.odv = dv;
	del.pdv = con;
	del.cls = this;
	del.viewid = this.viewCOlSerial;
	del.onclick = function() 
	{ 
		for (var i = 0; i < this.cls.searchView.view_fields.length; i++)
		{
			if (this.cls.searchView.view_fields[i].id == this.viewid)
				this.cls.searchView.view_fields.splice(i, 1);
		}

		this.pdv.removeChild(this.odv); 
	} 

	//getFields
}

/**
 * Delete selected objects
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {number} uid If set then delete a single object by id
 */
AntObjectBrowser.prototype.deleteObjects = function(uid)
{
	var singleuid = (uid) ? uid : null;
	ALib.Dlg.confirmBox("Are you sure you want to delete the selected items?", "Delete " + this.mainObject.titlePl, [this, singleuid]);
	ALib.Dlg.onConfirmOk = function(cls, singleuid)
	{
		var args = [["obj_type", cls.mainObject.name]];

		var fIsSelected = false;
		for (var i = 0; i < cls.objectList.length; i++)
		{
			//ALib.trace(cls.objectList[i].selected());
			if (cls.objectList[i].selected())
			{
				args[args.length] = ["objects[]", cls.objectList[i].id];
				fIsSelected = true;
			}
		}

		if (singleuid)
		{
			args[args.length] = ["objects[]", singleuid];
			fIsSelected = true;
		}

		// Find out if nothing is selected (send all)
		if (!fIsSelected && !singleuid)
		{
			if (!confirm("This will delete all "+cls.mainObject.titlePl+" in the current view. Are you sure you want to continue?"))
			{
				//dlg.hide();
				return;
			}

			args[args.length] = ["all_selected", "1"];
		}

		// Get list of objects to be deleted and put in toRemove array
		// We do this because we cannot dynamically change the objectList array while
		// iterating through it.
		var toRemove = new Array();
		if (fIsSelected)
		{
			for (var i in args)
			{
				var idx = cls.refreshUpdateItemInList(args[i][1], cls.objectList);
				if (idx != -1)
					toRemove.push(cls.objectList[idx]);
			}
		}
		else
		{
			for (var i = 0; i < cls.objectList.length; i++)
			{
				if (cls.objectList[i].selected())
				{
					toRemove.push(cls.objectList[i]);
				}
			}
		}

		// And now remove from ui and queue in the deleting queue to keep refreshes from inserting
		// the deleted object before processing is finished. This gives the impression of immediate processing.
		for (var i = 0; i < toRemove.length; i++)
		{
			cls.deletingQueue.push(toRemove[i].id);
			toRemove[i].remove();
		}

		toRemove = null; // clear memory (hopefully)

		cls.getFormConditions(null, args);


		/* TODO: continue move
		var xhr = new alib.net.Xhr();

		// Setup callback
		alib.events.listen(xhr, "load", function(evt) { 
			var data = this.getResponse();
		}, {defCls:this});

		// Timed out
		alib.events.listen(xhr, "error", function(evt) { 
		}, {defCls:this});

		var ret = xhr.send("/controller/ObjectList/deleteObjects", "POST", args);
		*/
        
        var ajax = new CAjax('json');
        ajax.cls = cls;
        //ajax.dlg = dlg;
        ajax.onload = function(ret)
        {
            //this.dlg.hide();

            if (!ret['error'])
            {
                var waserror = false;
                                
                for (deleted in ret)
                {
                    var currentDeleted = ret[deleted];
                    if (currentDeleted != "-1")
                    {
						// Set deleting queue
						for (var i in this.cls.deletingQueue)
						{
							// Check for null id which usually only happens if the index and object
							// storage gets out of sync in which case, just roll over and purge all for a rest
							if (currentDeleted == null)
							{
								this.cls.deletingQueue = new Array();
								break;
							}
							else if (this.cls.deletingQueue[i] == currentDeleted)
							{
								// Remove from queue
								this.cls.deletingQueue.splice(i, 1);
							}
						}

						/*
                        for (var j = 0; j < this.cls.objectList.length; j++)
                        {
                            if (this.cls.objectList[j].id == currentDeleted)
                            {
                                this.cls.objectList[j].remove();
                            }
                        }
						*/
                    }
                    else
                    {
                        waserror = true;
						this.cls.deletingQueue = new Array(); // Clear and refresh
                    }
                }

                if (waserror)
                    ALib.statusShowAlert("ERROR: Not all objects were deleted!", 3000, "bottom", "right");
                else
                    ALib.statusShowAlert(ret.length + " Items Deleted!", 3000, "bottom", "right");

				// Refresh in 1 second to give index time to commit changes
				var bcls = this.cls;
				setTimeout(function(){ bcls.refresh(); }, 1000);
                //this.cls.refresh();
            }
            else
            {
                ALib.statusShowAlert("ERROR: Could not contact server!", 3000, "bottom", "right");
            }    
        };

        ajax.exec("/controller/ObjectList/deleteObjects", args);
	}
}

/**
 * Delete a view
 *
 * @param {Object} view The view to delete
 * @param {DOMElement} row The row where the view label is printed
 */
AntObjectBrowser.prototype.deleteView = function(view, row)
{
	var dlg = new CDialog("", this.m_viewsDlg);
	dlg.confirmBox("Are you sure you want to delete "+view.name+"?", "Delete View", [view.id, row, this]);
	dlg.onConfirmOk = function(did, row, cls)
	{   
        ajax = new CAjax('json');
        ajax.cls = cls;
        ajax.row = row;
        ajax.onload = function(ret)
        {
            this.row.deleteRow();

            for (var i = 0; i < this.cls.mainObject.views.length; i++)
            {
                if (this.cls.mainObject.views[i].id == id)
                    this.cls.mainObject.views.splice(i, 1);
            }

            ALib.statusShowAlert("View Deleted!", 3000, "bottom", "right");
            this.cls.buildViewsDropdown();   
        };
        ajax.exec("/controller/Object/deleteView", 
                    [["dvid", did]]);
	}
}

/**
 * Find out of the mainObject (CAntObject} has a field with an 'email' subtype
 */
AntObjectBrowser.prototype.objectHasEmail = function()
{
	var fHasEmail = false;

	var fields = this.mainObject.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		if (fields[i].subtype == "email")
		{
			fHasEmail = true;
			break;
		}
	}

	return fHasEmail;
}

/*************************************************************************
*	Function:	actionEmail
*
*	Purpose:	Send bulk email
**************************************************************************/
AntObjectBrowser.prototype.actionEmail = function()
{
	var frmData = new Object();
    frmData.method = "standard";
	//frmData.method = "bulk"; 			// Standard/bulk
	frmData.useFields = new Array(); 	// Array of field names to pull from
	frmData.send = "compose"; 			// Compose/template
	frmData.template_id = ""; 			// Compose/template
	frmData.inp_field = "cmp_to"; 		// Compose/template

	var dlg = new CDialog("Send Email");
	var dv = alib.dom.createElement("div");

	// Method
	// -----------------------------------------------------
	var dv_inpfield = alib.dom.createElement("div");
	//alib.dom.styleSet(dv_inpfield, "display", "none");

	/*var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = "Method:";
	var inp_dv = alib.dom.createElement("div", dv);

	var radioMethod  = alib.dom.createElement("input");
	radioMethod.type = "radio";
	radioMethod.name = "method";
	radioMethod.checked = true;
	radioMethod.frmData = frmData;
	radioMethod.dv_inpfield = dv_inpfield;
	radioMethod.onclick = function() { this.dv_inpfield.style.display = "none"; this.frmData.method = "bulk"; }
	inp_dv.appendChild(radioMethod);	
    var radioLabel= alib.dom.createElement("span", inp_dv);
	radioLabel.innerHTML = "&nbsp;Bulk&nbsp;&nbsp;";
	
    var radioMethod  = alib.dom.createElement("input");    
	radioMethod.type = "radio";
	radioMethod.name = "method";
	radioMethod.checked = true;
	radioMethod.frmData = frmData;
	radioMethod.dv_inpfield = dv_inpfield;
	inp_dv.appendChild(radioMethod);
	var radioLabel= alib.dom.createElement("span", inp_dv);
	radioLabel.innerHTML = "&nbsp;Standard&nbsp;&nbsp;";*/
	
    /*var btn = new CButton("What is the difference?", function() { window.open('http://www.aereus.com/support/answers/40'); });
	btn.print(inp_dv);*/
	
	// Using
	dv.appendChild(dv_inpfield);
	var lbl = alib.dom.createElement("div", dv_inpfield);
	alib.dom.styleSetClass(lbl, "label");
	lbl.innerHTML = "Send Method:";
	var inp_dv = alib.dom.createElement("div", dv_inpfield);

	var radioMethod  = alib.dom.createElement("input");
	radioMethod.type = "radio";
	radioMethod.name = "using";
	radioMethod.checked = true;
	radioMethod.frmData = frmData;
	radioMethod.onclick = function() { if (this.checked) { this.frmData.inp_field = "cmp_to";} }
	inp_dv.appendChild(radioMethod);
	var radioLabel= alib.dom.createElement("span", inp_dv);
	radioLabel.innerHTML = "&nbsp;To&nbsp;";

	var radioMethod  = alib.dom.createElement("input");
	radioMethod.type = "radio";
	radioMethod.name = "using";
	radioMethod.frmData = frmData;
	radioMethod.onclick = function() { if (this.checked) { this.frmData.inp_field = "cmp_cc";} }
	inp_dv.appendChild(radioMethod);
	var radioLabel= alib.dom.createElement("span", inp_dv);
	radioLabel.innerHTML = "&nbsp;Cc&nbsp;";

	var radioMethod  = alib.dom.createElement("input");
	radioMethod.type = "radio";
	radioMethod.name = "using";
	radioMethod.frmData = frmData;
	radioMethod.onclick = function() { if (this.checked) { this.frmData.inp_field = "cmp_bcc";} }
	inp_dv.appendChild(radioMethod);
	var radioLabel= alib.dom.createElement("span", inp_dv);
	radioLabel.innerHTML = "&nbsp;Bcc&nbsp;";

	// Use Address
	// -----------------------------------------------------
	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "label");
	lbl.innerHTML = "Use Address:";

	var fields = this.mainObject.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		var field = fields[i];

		if (field.subtype == "email")
		{
			var inp_dv = alib.dom.createElement("div", dv);
			var chkUseAddress  = alib.dom.createElement("input");
			chkUseAddress.type = "checkbox";
			chkUseAddress.name = "use_field[]";
			chkUseAddress.checked = false;
			chkUseAddress.value = field.name;
			chkUseAddress.frmData = frmData;
			inp_dv.appendChild(chkUseAddress);
			var lbl = alib.dom.createElement("span", inp_dv);
			lbl.innerHTML = "&nbsp;"+field.title;

			chkUseAddress.onclick = function() 
			{ 
				if (this.checked)
				{
					this.frmData.useFields[this.frmData.useFields.length] = this.value;
				}
				else
				{
					for (var i = 0; i < this.frmData.useFields.length; i++)
					{
						if (this.frmData.useFields[i] == this.value)
							this.frmData.useFields.splice(i, 1);
					}
				}
			}
		}
	}

	// Send
	// ---------------------------------------------
	/*var dv_template = alib.dom.createElement("div");
	alib.dom.styleSet(dv_template, "display", "none");

	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = "Send:";
	var inp_dv = alib.dom.createElement("div", dv);
	var radioMethod  = alib.dom.createElement("input");
	radioMethod.type = "radio";
	radioMethod.name = "send";
	radioMethod.checked = true;
	radioMethod.dv_template = dv_template;
	radioMethod.frmData = frmData;
	radioMethod.onclick = function() { if (this.checked) { dv_template.style.display = "none"; this.frmData.template_id = "";} }
	inp_dv.appendChild(radioMethod);
	
    var radioLabel= alib.dom.createElement("span", inp_dv);
	radioLabel.innerHTML = "&nbsp;Compose New Message&nbsp;&nbsp;";
	var radioMethod  = alib.dom.createElement("input");
	radioMethod.type = "radio";
	radioMethod.name = "send";
	radioMethod.checked = false;
	radioMethod.dv_template = dv_template;
	radioMethod.onclick = function() { if (this.checked) dv_template.style.display = "block"; }
	inp_dv.appendChild(radioMethod);
	var radioLabel= alib.dom.createElement("span", inp_dv);
	radioLabel.innerHTML = "&nbsp;Use Email Template";

	// Template
	var emtfunct = function(lbl, dlg, frmData)
	{
		var cbrowser = new AntFsOpen();
		cbrowser.filterType = "emt";
		cbrowser.cbData.m_lbl = lbl;
		cbrowser.cbData.frmData = frmData;
		cbrowser.onSelect = function(fid, name, path) 
		{
			this.cbData.m_lbl.innerHTML = "&nbsp;&nbsp;" + name + "&nbsp;&nbsp;";
			this.cbData.frmData.template_id = fid;
			//this.m_task_obj.setObjectValue("fid", fid);
			//this.m_task_obj.setObjectValue("fname", name);
		}
		cbrowser.showDialog(dlg);
	}

	var lbl = alib.dom.createElement("span", dv_template);
	lbl.innerHTML = "&nbsp;&nbsp;No Template Selected!&nbsp;&nbsp;";
	dv.appendChild(dv_template);
	var btn = new CButton("Select Template", emtfunct, [lbl, dlg, frmData]);
	btn.print(dv_template);*/

	// Action buttons
	// ---------------------------------------------
	var dv_btn = alib.dom.createElement("div", dv);
    alib.dom.styleSet(dv_btn, "margin-top", "10px");
	var btn = new CButton("Compose Email", function(cls, dlg, frmData) { cls.actionEmailSubmit(frmData); dlg.hide(); }, 
							[this, dlg, frmData], "b2");
	btn.print(dv_btn);
	var btn = new CButton("Cancel", function(dlg) { dlg.hide(); }, [dlg]);
	btn.print(dv_btn);

	dlg.customDialog(dv, 300, 170);
}

/*************************************************************************
*	Function:	actionEmailSubmit
*
*	Purpose:	Send bulk email
**************************************************************************/
AntObjectBrowser.prototype.actionEmailSubmit = function(frmDataObj)
{
	//ALib.m_debug = true;
	//ALib.trace("Template ID: " + frmDataObj.template_id);

	// Create holder div
	var condv = alib.dom.createElement("div", alib.dom.m_document.body);
	alib.dom.styleSet(condv, "display", "none");
	alib.dom.styleSet(condv, "position", "absolute");

	// Create form
	var form = alib.dom.createElement("form", condv);
	form.setAttribute("method", "post");
	form.setAttribute("target", "emailcmp_objbrowser");
	//form.setAttribute("action", "/email/compose.awp?new_win=1");
    form.setAttribute("action", "/obj/email_message");

	// Tempalte ID
	var hiddenField = alib.dom.createElement("input");              
	hiddenField.setAttribute("type", "hidden");
	hiddenField.setAttribute("name", "fid");
	hiddenField.setAttribute("value", frmDataObj.template_id);
	form.appendChild(hiddenField);

	// Input field (non-bulk)
	var hiddenField = alib.dom.createElement("input");              
	hiddenField.setAttribute("type", "hidden");
	hiddenField.setAttribute("name", "inp_field");
	hiddenField.setAttribute("value", frmDataObj.inp_field);
	form.appendChild(hiddenField);

	// Obj Type
	var hiddenField = alib.dom.createElement("input");              
	hiddenField.setAttribute("type", "hidden");
	hiddenField.setAttribute("name", "obj_type");
	hiddenField.setAttribute("value", this.mainObject.name);
	form.appendChild(hiddenField);

	// Method
	var hiddenField = alib.dom.createElement("input");              
	hiddenField.setAttribute("type", "hidden");
	hiddenField.setAttribute("name", "send_method");
	hiddenField.setAttribute("value", (frmDataObj.method == "bulk")?"1":"0");
	form.appendChild(hiddenField);

	// Objects
	var fIsSelected = false;
	for (var i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].selected())
		{
			fIsSelected = true;
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "objects[]");
			hiddenField.setAttribute("value", this.objectList[i].id);
			form.appendChild(hiddenField);
		}
	}

	// Find out if nothing is selected (send all)
	if (!fIsSelected)
	{
		this.getFormConditions(form);
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "all_selected");
		hiddenField.setAttribute("value", "1");
		form.appendChild(hiddenField);
	}

	// Fields
	for (var i = 0; i < frmDataObj.useFields.length; i++)
	{
		if (frmDataObj.useFields[i])
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "using[]");
			hiddenField.setAttribute("value", frmDataObj.useFields[i]);
			form.appendChild(hiddenField);
		}
	}

	var params = 'width=780,height=600,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
	var cmp = window.open('', 'emailcmp_objbrowser', params);

	form.submit();

	alib.dom.m_document.body.removeChild(condv);
}

/*************************************************************************
*	Function:	getFormConditions
*
*	Purpose:	Get condtions for forwarding query through form
**************************************************************************/
AntObjectBrowser.prototype.getFormConditions = function(form, arr_args, objArgs)
{
	if (this.m_txtSearch && this.m_txtSearch.value && this.m_txtSearch.value!=this.searchTitleText)
	{
		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "cond_search");
			hiddenField.setAttribute("value", this.m_txtSearch.value);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["cond_search", this.m_txtSearch.value];

		if (objArgs)
			objArgs.cond_search = this.m_txtSearch.value;
	}
	
	var ccount = 0;
	for (var i = 0; i < this.conditions.length; i++, ++ccount)
	{
		var cond = this.conditions[i];

		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "conditions[]");
			hiddenField.setAttribute("value", ccount);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["conditions[]", ccount];

		if (objArgs)
		{
			if (!objArgs.conditions) objArgs.conditions = new Array();
			objArgs.conditions.push(ccount);
		}

		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "condition_blogic_"+ccount);
			hiddenField.setAttribute("value", cond.blogic);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["condition_blogic_"+ccount, cond.blogic];

		if (objArgs)
			objArgs["condition_blogic_"+ccount] = cond.blogic;

		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "condition_fieldname_"+ccount);
			hiddenField.setAttribute("value", cond.fieldName);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["condition_fieldname_"+ccount, cond.fieldName];

		if (objArgs)
			objArgs["condition_fieldname_"+ccount] = cond.fieldName;

		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "condition_operator_"+ccount);
			hiddenField.setAttribute("value", cond.operator);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["condition_operator_"+ccount, cond.operator];

		if (objArgs)
			objArgs["condition_operator_"+ccount] = cond.operator;

		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "condition_condvalue_"+ccount);
			hiddenField.setAttribute("value", cond.condValue);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["condition_condvalue_"+ccount, cond.condValue];

		if (objArgs)
			objArgs["condition_condvalue_"+ccount] = cond.condValue;
	}

	for (var i = 0; i < this.m_filters.length; i++, ++ccount)
	{
		var cond = this.m_filters[i];

		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "conditions[]");
			hiddenField.setAttribute("value", ccount);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["conditions[]", ccount];

		if (objArgs)
		{
			if (!objArgs.conditions) objArgs.conditions = new Array();
			objArgs.conditions.push(ccount);
		}

		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "condition_blogic_"+ccount);
			hiddenField.setAttribute("value", cond.blogic);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["condition_blogic_"+ccount, cond.blogic];

		if (objArgs)
			objArgs["condition_blogic_"+ccount] = "and";

		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "condition_fieldname_"+ccount);
			hiddenField.setAttribute("value", cond.fieldName);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["condition_fieldname_"+ccount, cond.fieldName];

		if (objArgs)
			objArgs["condition_fieldname_"+ccount] = cond.fieldName;

		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "condition_operator_"+ccount);
			hiddenField.setAttribute("value", "is_equal");
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["condition_operator_"+ccount, "is_equal"];

		if (objArgs)
			objArgs["condition_operator_"+ccount] = "is_equal";

		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "condition_condvalue_"+ccount);
			hiddenField.setAttribute("value", cond.value);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["condition_condvalue_"+ccount, cond.value];

		if (objArgs)
			objArgs["condition_condvalue_"+ccount] = cond.value;
	}

	// Get order by
	for (var i = 0; i < this.sort_order.length; i++)
	{
		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "order_by[]");
			hiddenField.setAttribute("value",this.sort_order[i].fieldName+" "+this.sort_order[i].order);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["order_by[]", this.sort_order[i].fieldName+" "+this.sort_order[i].order];

		if (objArgs)
		{
			if (!objArgs.order_by) objArgs.order_by = new Array();
			objArgs.order_by.push(this.sort_order[i].fieldName+" "+this.sort_order[i].order);
		}
	}


	if (this.browseByField)
	{
		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "browsebyfield");
			hiddenField.setAttribute("value", this.browseByField);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["browsebyfield", this.browseByField];

		if (objArgs)
			objArgs.browsebyfield = this.browseByField;
	}

	if (this.browseByPath)
	{
		if (form)
		{
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "browsebypath");
			hiddenField.setAttribute("value", this.browseByPath);
			form.appendChild(hiddenField);
		}

		if (arr_args)
			arr_args[arr_args.length] = ["browsebypath", this.browseByPath];

		if (objArgs)
			objArgs.browsebypath = this.browseByPath;
	}
}

/**
 * Show mass-edit form
 */
AntObjectBrowser.prototype.actionEdit = function()
{
	var frmData = new Object();
	frmData.fieldName = "bulk";	// Field name to update
	frmData.act = "add"; 		// Used for mutli_val
	frmData.value = "";			// Value to set

	var dlg = new CDialog("Edit Multiple " + this.mainObject.titlePl);
	var dv = alib.dom.createElement("div");
	dlg.customDialog(dv, 300);

	var val_div = alib.dom.createElement("div");

	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "label");
	lbl.innerHTML = "Select a field to update:";

	var fname_sel = alib.dom.createElement("select", dv);
	var fields = this.mainObject.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		if (!fields[i].readonly)
			fname_sel[fname_sel.length] = new Option(fields[i].title, fields[i].name, false, false);
	}
	fname_sel.val_div = val_div;
	fname_sel.frmData = frmData;
	fname_sel.cls = this;
	fname_sel.onchange = function()
	{
		this.cls.actionEditBuildValue(this.value, this.val_div, this.frmData);
		this.frmData.fieldName = this.value;
	}

	dv.appendChild(val_div);

	this.actionEditBuildValue(fname_sel.value, val_div, frmData);

	// Action buttons
	// ---------------------------------------------
	var dv_btn = alib.dom.createElement("div", dv);
	alib.dom.styleSet(dv_btn, "margin-top", "3px");
    
    var btn = alib.ui.Button("Make Changes", 
                    {
                        className:"b2", callback:this, frmData:frmData, dlg:dlg,
                        onclick:function() 
                        {
                            this.callback.actionEditSubmit(this.frmData); 
                            this.dlg.hide();
                        }
                    });                            
	dv_btn.appendChild(btn.getButton());
	
	var btn = alib.ui.Button("Cancel Edit", 
                    {
                        className:"b1", dlg:dlg,
                        onclick:function() 
                        {
                            this.dlg.hide();
                        }
                    });
    dv_btn.appendChild(btn.getButton());

	dlg.reposition();
}

/**
 * Build value entry for a selectd field in the mass edit form
 *
 * @param {string} field_name The name of the field the user selected to edit
 * @param {DOMElement} div The container where the input component will be printed
 * @param {array} frmData Input data used in building the component such as value and action (if multi fields)
 */
AntObjectBrowser.prototype.actionEditBuildValue = function(field_name, div, frmData)
{
    alib.dom.styleSet(div, "margin-top", "5px");
	div.innerHTML = "";

	var field = this.mainObject.getFieldByName(field_name);
    switch(field.type)
    {
        case "fkey_multi":
            var lbl = alib.dom.createElement("div", div);
            alib.dom.styleSetClass(lbl, "label");
            lbl.innerHTML = "Do the following:";
            var act_sel = alib.dom.createElement("select", div);
            act_sel[act_sel.length] = new Option("Add", "add", false, true);
            act_sel[act_sel.length] = new Option("Remove", "remove", false, false);
            act_sel.frmData = frmData;
            act_sel.onchange = function() { this.frmData.act = this.value; }
        case "fkey":
            var inp_div = alib.dom.createElement("div", div);
            this.mainObject.fieldCreateValueInput(inp_div, field.name);
            inp_div.inpRef.frmData = frmData;
            frmData.value = inp_div.inpRef.value;
            
            if(inp_div.inptType == "select")
                inp_div.inpRef.onchange = function() { this.frmData.value = this.value; }
                
            else if(inp_div.inptType == "dynselect")
                inp_div.inpRef.onSelect = function() { this.frmData.value = this.value; }
            break;
        default:
            var lbl = alib.dom.createElement("div", div);
            alib.dom.styleSetClass(lbl, "label");
            lbl.innerHTML = "Set value to:";

            var inp_div = alib.dom.createElement("div", div);
            this.mainObject.fieldCreateValueInput(inp_div, field.name);
            if (inp_div.inpRef)
            {
                inp_div.inpRef.frmData = frmData;
                frmData.value = inp_div.inpRef.value;
                switch(inp_div.inptType)
                {
                case "checkbox":
                    inp_div.inpRef.onclick = function()
                    {
                        this.frmData.value = (this.checked) ? true : false;
                    }
                    break;
                case "text":
                case "input":
                    alib.dom.styleSet(inp_div.inpRef, "width", "90%");
                case "select":
                    inp_div.inpRef.onchange = function() 
                    {
                        this.frmData.value = this.value; 
                    }
                    break;
                case "userBrowser":
                case "objectBrowser":
                    inp_div.inpRef.onSelect = function(id)
                    {
                        this.frmData.value = id;
                    }
                    break;
                case "dynselect":
                    inp_div.inpRef.onSelect = function()
                    {
                        this.frmData.value = this.value;
                    }
                    break;
                }
            }
            break;
    }
}

/*************************************************************************
*	Function:	actionEditSubmit
*
*	Purpose:	Submit mass-edit
**************************************************************************/
AntObjectBrowser.prototype.actionEditSubmit = function(frmData)
{
	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Saving changes, please wait...";
	dlg.statusDialog(dv_load, 150, 100);
    
	var args = [["obj_type", this.mainObject.name], ["field_name", frmData.fieldName], ["action", frmData.act], ["value", frmData.value]];

	// Objects
	var fIsSelected = false;
	for (var i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].selected())
		{
			fIsSelected = true;
			args[args.length] = ["objects[]", this.objectList[i].id];
		}
	}	

	// Find out if nothing is selected (send all)
	if (!fIsSelected)
	{
		this.getFormConditions(null, args);
		args[args.length] = ["all_selected", "1"];

		if (!confirm("This will edit all "+this.mainObject.titlePl+" in the current view. Are you sure you want to continue?"))
		{
			dlg.hide();
			return;
		}
	}
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.dlg = dlg;
    ajax.onload = function(ret)
    {
        this.dlg.hide();

        if (!ret['error'])
        {
            ALib.statusShowAlert(this.cls.mainObject.titlePl + "Saved!", 3000, "bottom", "right");
            this.cls.getObjects();
        }
        else
        {
            ALib.statusShowAlert("ERROR: Could not contact server!", 3000, "bottom", "right");
        }    
    };
    ajax.exec("/controller/Object/editObjects", args);
}

/**
 * Initilize and show import wizard for this object type
 *
 * @private
 */
AntObjectBrowser.prototype.actionImport = function()
{
	if (typeof Ant != "undefined" || g_userid) // Global app class
	{
		var uid = (Ant.user.id) ? Ant.user.id : g_userid;

		var wiz = new AntWizard("EntityImport", {obj_type:this.mainObject.name});
		//wiz.onFinished = function() { alert("The wizard is finished"); };
 		//wiz.onCancel = function() { alert("The wizard was canceled"); };
		wiz.show();
	}
}

/*************************************************************************
*	Function:	actionExport
*
*	Purpose:	Export list of objects
**************************************************************************/
AntObjectBrowser.prototype.actionExport = function()
{
	var condv = alib.dom.createElement("div", alib.dom.m_document.body);
	alib.dom.styleSet(condv, "display", "none");
	alib.dom.styleSet(condv, "position", "absolute");

	var form = alib.dom.createElement("form", condv);
	form.setAttribute("method", "post");
	form.setAttribute("target", "_blank");
	form.setAttribute("action", "/objects/export_csv.php?obj_type="+this.obj_type);

	//this.getFormConditions(form);

	// Objects
	var fIsSelected = false;
	for (var i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].selected())
		{
			fIsSelected = true;
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "objects[]");
			hiddenField.setAttribute("value", this.objectList[i].id);
			form.appendChild(hiddenField);
		}
	}


	// Find out if nothing is selected (send all)
	if (!fIsSelected)
	{
		this.getFormConditions(form);
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "all_selected");
		hiddenField.setAttribute("value", "1");
		form.appendChild(hiddenField);
	}

	form.submit();
	
	alib.dom.m_document.body.removeChild(condv);
}


/*************************************************************************
*	Function:	actionPrint
*
*	Purpose:	Export list of objects
**************************************************************************/
AntObjectBrowser.prototype.actionPrint = function()
{
	var condv = alib.dom.createElement("div", alib.dom.m_document.body);
	alib.dom.styleSet(condv, "display", "none");
	alib.dom.styleSet(condv, "position", "absolute");

	var form = alib.dom.createElement("form", condv);
	form.setAttribute("method", "post");
	form.setAttribute("target", "_blank");
	form.setAttribute("action", "/print/engine.php?obj_type="+this.obj_type);

	//this.getFormConditions(form);

	// Objects
	var fIsSelected = false;
	for (var i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].selected())
		{
			fIsSelected = true;
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "objects[]");
			hiddenField.setAttribute("value", this.objectList[i].id);
			form.appendChild(hiddenField);
		}
	}

	// Find out if nothing is selected (send all)
	if (!fIsSelected)
	{
		this.getFormConditions(form);
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "all_selected");
		hiddenField.setAttribute("value", "1");
		form.appendChild(hiddenField);
	}


	form.submit();
	
	alib.dom.m_document.body.removeChild(condv);
}

/*************************************************************************
*	Function:	actionMerge
*
*	Purpose:	Merge records
**************************************************************************/
AntObjectBrowser.prototype.actionMerge = function()
{
	var mobjs = new Array();
	for (var i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].selected())
		{
			mobjs[mobjs.length] = this.objectList[i].id;
		}
	}

	// Find out if nothing is selected (send all)
	if (mobjs.length < 2)
	{
		ALib.Dlg.messageBox("Please select at least two records to merge");
		return;
	}

	var wiz = new CAntObjectMergeWizard(this.obj_type);
	
	for (var i = 0; i < mobjs.length; i++)
		wiz.addObject(mobjs[i]);

	wiz.browserClass = this;
	wiz.onFinished = function()
	{
		this.browserClass.getObjects();
	}

	wiz.showDialog();
}

/*************************************************************************
*	Function:	actionCreateAssoc
*
*	Purpose:	Create new object and associate selected records
**************************************************************************/
AntObjectBrowser.prototype.actionCreateAssoc = function(obj_type)
{
	var assoc = new Array();
	for (var i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].selected())
		{
			assoc[assoc.length] = this.obj_type + ":" + this.objectList[i].id;
		}
	}

	// Find out if nothing is selected (send all)
	if (assoc.length < 1)
	{
		ALib.Dlg.messageBox("Please select at least one record to associate with");
		return;
	}

	loadObjectForm(obj_type, "", null, assoc);
}


/*************************************************************************
*	Function:	actionCustom
*
*	Purpose:	Export list of objects
**************************************************************************/
AntObjectBrowser.prototype.actionCustom = function(act)
{
	var args = [["obj_type", this.mainObject.name]];

	// Objects
	var fIsSelected = false;
	var removalQueue = new Array();
	for (var i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].selected())
		{
			fIsSelected = true;
			args[args.length] = ["objects[]", this.objectList[i].id];

			removalQueue[removalQueue.length] = this.objectList[i];
		}
	}
	if (act.flush)
	{
		for (var i = 0; i < removalQueue.length; i++)
		{
			removalQueue[i].remove();
		}
	}
	
	// Other arguments
	if (act.args)
	{
		for (var i = 0; i < act.args.length; i++)
		{
			args[args.length] = [act.args[i][0], act.args[i][1]];
		}
	}

	// Find out if nothing is selected (send all)
	if (!fIsSelected)
	{
		this.getFormConditions(null, args);
		args[args.length] = ["all_selected", "1"];

		if (!confirm("This apply to all "+this.mainObject.titlePl+" in the current view. Are you sure you want to continue?"))
		{
			dlg.hide();
			return;
		}
	}

	if (act.options)
	{
		var dlg = new CDialog("Options");
		var dv = alib.dom.createElement("div");
		dlg.customDialog(dv, 300, 100);

		var val_div = alib.dom.createElement("div", val_div);

		for (var i = 0; i < act.options.length; i++)
		{
			var lbl = alib.dom.createElement("div", dv);
			alib.dom.styleSetClass(lbl, "label");
			lbl.innerHTML = act.options[i].caption;

			var ind = args.length;
			args[ind] = [act.options[i].name, act.options[i].values[0][0]];

			var fname_sel = alib.dom.createElement("select", dv);
			for (var m = 0; m < act.options[i].values.length; m++)
			{
				fname_sel[fname_sel.length] = new Option(act.options[i].values[m][1], act.options[i].values[m][0], false, false);
			}
			fname_sel.arg = args[ind];
			fname_sel.onchange = function()
			{
				this.arg[1] = this.value;
			}
		}

		// Action buttons
		// ---------------------------------------------
		var dv_btn = alib.dom.createElement("div", dv);
		alib.dom.styleSet(dv_btn, "margin-top", "3px");

		var subm = function(dlg, act, args)
		{
			var ajax = new CAjax('json');
			ajax.cbData.dlg = dlg;
			ajax.onload = function(ret)
			{
				this.cbData.dlg.hide();
				ALib.statusShowAlert("Action Completed!", 3000, "bottom", "right");
			};
			ajax.exec(act.url, args);
		}

		var btn = new CButton("Continue", subm, [dlg, act, args], "b2");
		btn.print(dv_btn);
		var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [dlg], AJAX_POST);
		btn.print(dv_btn);
	}
	else
	{
		var ajax = new CAjax('json');
		ajax.cbData.cls = this;
		ajax.cbData.act = act;
		ajax.onload = function(ret)
		{
			var msg = (act.doneMsg) ? act.doneMsg : "Action Completed!";
			ALib.statusShowAlert(msg, 3000, "bottom", "right");
			if (act.refresh)
			{
				// Refresh in 1 second to give index time to commit changes
				var bcls = this.cbData.cls;
				setTimeout(function(){ bcls.refresh(); }, 1000);
				//this.cbData.cls.refresh();
			}
		};
		ajax.exec(act.url, args);
	}
}

/*************************************************************************
*	Function:	actionCustomForm
*
*	Purpose:	Export list of objects
**************************************************************************/
AntObjectBrowser.prototype.actionCustomForm = function(act)
{
	var condv = alib.dom.createElement("div", alib.dom.m_document.body);
	alib.dom.styleSet(condv, "display", "none");
	alib.dom.styleSet(condv, "position", "absolute");

	var form = alib.dom.createElement("form", condv);
	form.setAttribute("method", "post");
	form.setAttribute("target", "_blank");
	form.setAttribute("action", act.url);

	// Objects
	var fIsSelected = false;
	for (var i = 0; i < this.objectList.length; i++)
	{
		if (this.objectList[i].selected())
		{
			fIsSelected = true;
			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", "objects[]");
			hiddenField.setAttribute("value", this.objectList[i].id);
			form.appendChild(hiddenField);
		}
	}

	// Find out if nothing is selected (send all)
	if (!fIsSelected)
	{
		this.getFormConditions(form);
		var hiddenField = alib.dom.createElement("input");              
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "all_selected");
		hiddenField.setAttribute("value", "1");
		form.appendChild(hiddenField);

		if (!confirm("This apply to all "+this.mainObject.titlePl+" in the current view. Are you sure you want to continue?"))
		{
			dlg.hide();
			return;
		}
	}

	if (act.options)
	{
		var dlg = new CDialog("Options");
		var dv = alib.dom.createElement("div");
		dlg.customDialog(dv, 300, 300);

		var val_div = alib.dom.createElement("div", val_div);

		for (var i = 0; i < act.options.length; i++)
		{
			var lbl = alib.dom.createElement("div", dv);
			alib.dom.styleSetClass(lbl, "label");
			lbl.innerHTML = act.options[i].caption;

			var hiddenField = alib.dom.createElement("input");              
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", act.options[i].name);
			hiddenField.setAttribute("value", act.options[i].values[0][0]);
			form.appendChild(hiddenField);

			var fname_sel = alib.dom.createElement("select", dv);
			for (var m = 0; m < act.options[i].values.length; m++)
			{
				fname_sel[fname_sel.length] = new Option(act.options[i].values[m][1], act.options[i].values[m][0], false, false);
			}
			fname_sel.hiddenField = hiddenField;
			fname_sel.onchange = function()
			{
				this.hiddenField.value = this.value;
			}
		}

		// Action buttons
		// ---------------------------------------------
		var dv_btn = alib.dom.createElement("div", dv);
		alib.dom.styleSet(dv_btn, "margin-top", "3px");

		var btn = new CButton("Continue", function(dlg, form, condv) { form.submit(); dlg.hide(); alib.dom.m_document.body.removeChild(condv); }, 
								[dlg, form, condv], "b2");
		btn.print(dv_btn);
		var btn = new CButton("Cancel", function(dlg) {  dlg.hide(); }, [dlg]);
		btn.print(dv_btn);
	}
	else
	{
		form.submit();
		alib.dom.m_document.body.removeChild(condv);
	}
}

/**
 * Add a filter condition which is like a condition but hidden
 *
 * This is usually to filter browser 'select' dialogs by various fields
 * like: only show milestones for a the selected project
 *
 * @public
 * @param {string} fieldName The name of the field to filter
 * @param {string} value The value to filter fieldName with
 */
AntObjectBrowser.prototype.setFilter = function(fieldName, value, blogic)
{
	var ind = this.m_filters.length;
	this.m_filters[ind] = new Object();
	this.m_filters[ind].fieldName = fieldName;
	this.m_filters[ind].value = value;
	this.m_filters[ind].blogic = (blogic && ind > 0) ? blogic : "and";

	this.mainObject.setFilter(fieldName, value);
}

/**
 * Remove a filter from this browser
 *
 * @public
 * @param {string} fieldName The name of the field to filter
 * @param {string} value The value to filter fieldName with
 */
AntObjectBrowser.prototype.removeFilter = function(fieldName, value)
{
	for (i = 0; i < this.m_filters.length; i++)
	{
		if (this.m_filters[i].fieldName == fieldName && this.m_filters[i].value == value)
		{
			this.m_filters.splice(i, 1);
		}
	}

	for (i = 0; i < this.m_filters.length; i++)
	{
		//ALib.trace(this.m_filters[i].fieldName);
	}

	//this.mainObject.setFilter(fieldName, value);
}

/*************************************************************************************
*	Description:	setViewsFilter
*
*	Arguments:		Views can be filtered for sub-sets of data. If defined, then only
*					views with a matching filterKey will be displayed. The default view
*					will be used for view_fields and order but nothing more.
**************************************************************************************/
AntObjectBrowser.prototype.setViewsFilter = function(filterKey)
{
    return; // Do not set filter
    
	this.viewsFilterKey = filterKey;

	// Pull default view for this key
	if (this.mainObject.defaultViewExists(filterKey))
		this.loadView(this.mainObject.getDefaultView(filterKey));
	else
		this.clearView();
}

/*************************************************************************************
*	Description:	printInlineAddComment
*
*	Arguments:		Print form to add a comment inline
**************************************************************************************/
AntObjectBrowser.prototype.printInlineAddComment = function(con, showform)
{
	con.innerHTML = "";
	con.cls = this;
	var fmrCon = alib.dom.createElement("div", con);
	alib.dom.styleSet(fmrCon, "margin-top", "5px");
	// There is a bug with the width where when the containing div is hidden (new object) the width is 0
	// so we set it to 400px by default to fix the issue. However, this creates problems with mobile browserss
	//var width = (this.mobile) ? "99%" : "400px";
	var width = "99%";
	alib.dom.styleSet(fmrCon, "width", width);

	if (showform)
	{
		if (!this.comment_users)
			this.comment_users = new Array();

		var tbl = alib.dom.createElement("table", fmrCon);
		alib.dom.styleSet(tbl, "width", "100%");
		tbl.setAttribute("cellPadding", "0");
		tbl.setAttribute("cellSpacing", "0");
		var tbody = alib.dom.createElement("tbody", tbl);
		var row = alib.dom.createElement("tr", tbody);
		row.vAlign = "top";

		// Comment box
		var td_lbl = alib.dom.createElement("td", row);
		alib.dom.styleSet(td_lbl, "width", "55px");
		//td_lbl.innerHTML = "Comment:";
		td_lbl.innerHTML = "<img src='/files/userimages/current/48/48' style='width:48px;' />";
		var td = alib.dom.createElement("td", row);
		var ta_comment = alib.dom.createElement("textarea", td);
		alib.dom.styleSet(ta_comment, "width", "100%");
		alib.dom.styleSet(ta_comment, "height", "50px");
		alib.dom.textAreaAutoResizeHeight(ta_comment, 50);
		ta_comment.focus();
		
		// Notification
		var row = alib.dom.createElement("tr", tbody);
		var td_lbl = alib.dom.createElement("td", row);
		td_lbl.innerHTML = "Notify:";
		var td_inp = alib.dom.createElement("td", row);
		alib.dom.styleSet(td_inp, "padding-top", "5px");
		var inp_notify = alib.dom.createElement("input", td_inp);
		var t = new CTextBoxList(inp_notify, { bitsOptions:{editable:{addKeys: [188, 13, 186, 59], addOnBlur:true }}, plugins: {autocomplete: { placeholder: "Start typing a name for suggestions", minLength: 2, queryRemote: true, remote: {url:"/users/json_autocomplete.php"}}}});
		//t.acLoadValues("/users/json_autocomplete.php");

		// Add attachments row
		var row = alib.dom.createElement("tr", tbody);
		var td_lbl = alib.dom.createElement("td", row);
		var divAttachment = alib.dom.createElement("td", row);

		// Action buttons
		var row = alib.dom.createElement("tr", tbody);
		var td_lbl = alib.dom.createElement("td", row);
		var dv_button = alib.dom.createElement("td", row);
		alib.dom.styleSet(dv_button, "padding-top", "7px");
		alib.dom.styleSet(dv_button, "padding-bottom", "10px");
		alib.dom.styleSet(dv_button, "text-align", "right");

		// Create add attachment button holder
		var attachments = new Array();
		var attachmentButtonCon = alib.dom.createElement("span", dv_button);
        var cfupload = new AntFsUpload('%tmp%');
        cfupload.cbData.cls = this;
        cfupload.onRemoveUpload = function (fid) {
			for (i in attachments)
			{
				if (i == fid)
					attachments.splice(i, 1);
			}
        }
        cfupload.onQueueComplete = function () { 
            for(file in this.m_uploadedFiles)
				attachments.push(this.m_uploadedFiles[file]['id']);
        }
        cfupload.showTmpUpload(attachmentButtonCon, divAttachment, 'Add Attachment');

		// If we are working with a case and there is a customer attached to the case, then add public checkbox
		var sendToCustChekBox = null;
		if (this.parentObject)
		{
			if (this.parentObject.obj_type == "case" && this.parentObject.getValue("customer_id"))
			{
				sendToCustChekBox = alib.dom.createElement("input", dv_button);
				sendToCustChekBox.type = "checkbox";
				alib.dom.styleSet(sendToCustChekBox, "vertical-align", "middle");
				sendToCustChekBox.checked = false;

				alib.dom.createElement("span", dv_button, 
										"&nbsp;Sent comment to " + 
										this.parentObject.getValueName("customer_id") + 
										"&nbsp;&nbsp;&nbsp;");
			}
		}
	
		// Add comment button
		var btn = new CButton("Add Comment", function(cls, ta_comment, t_notify, con, sendToCustChekBox, attachments) { 
				cls.saveComment(ta_comment.value, t_notify, attachments, con, (sendToCustChekBox !== null) ? sendToCustChekBox.checked : false); 
			}, [this, ta_comment, t, con, sendToCustChekBox, attachments], "b2"
		);
		btn.print(dv_button);

		// Save for parent form if needed (use on save to make sure that comments are saved)
		this.commentObj = { ta_comment: ta_comment, t:t, con:con };

		//var btn = new CButton("Cancel", function(cls, con) { cls.printInlineAddComment(con, false); }, [this, con], "b1");

		var btn = alib.ui.Button("Cancel", {
			className:"b1 nomargin", tooltip:"Cancel Comment", cls:this, con:con,
			onclick:function() { this.cls.printInlineAddComment(this.con, false); }
		});
		btn.print(dv_button);

		if (!con.fNotifyLoaded)
		{
			con.fNotifyLoaded = true;

			// Add customers or users to notify if parent object exists
			// -----------------------------------------
			if (this.parentObject)
			{
				var fields = this.parentObject.getFields();
				for (var j = 0; j < fields.length; j++)
				{
					var field = fields[j];
					var field_val = "";
					var field_lbl = "";
					var otype = "";

					//if (field.type == "object" && (field.subtype == "user" || field.subtype == "customer"))
					if (field.type == "object" && field.subtype == "user")
					{
						field_val = this.parentObject.getValue(field.name);
						field_lbl = this.parentObject.getValueName(field.name);
						otype = field.subtype;
					}
					else if (field.type == "object" && field.subtype == "")
					{
						// TODO: handle object reference and check for user
					}

					if (field_val)
					{
						var bFound = false;
						for (var i = 0; i < this.comment_users.length; i++)
						{
							if (this.comment_users[i].id == otype+":"+field_val)
								bFound = true;
						}

						if (!bFound)
							this.comment_users[this.comment_users.length] = {id:otype+":"+field_val, name:field_lbl};
					}
				}
			}

			// Loop through added users/customers to be notified
			if (this.comment_users)
			{
				for (var i = 0; i < this.comment_users.length; i++)
				{
					if ((g_userid && this.comment_users[i].id != "user:"+g_userid) || !g_userid)
						t.add(this.comment_users[i].id, this.comment_users[i].name);
				}
			}
		}
	}
	else
	{
		// No need to save comment
		this.commentObj = null;

		var con2 = alib.dom.createElement("div", fmrCon);
		alib.dom.styleSet(con2, "margin-top", "5px");
		con2.con = con;
		con2.innerHTML = "<input type='text' class='comment' placeholder='Add Comment' style='width:"+width+";'>";
		con2.onclick = function() { this.con.cls.printInlineAddComment(this.con, true); };
	}
}

/**
 * Add entry to the comment_users notification array for adding comments
 *
 * TODO: we should extend this to include email addresses
 *
 * @param {string} entry The recipient can be an object reference, email or even a text entry which will be ignored
 */
AntObjectBrowser.prototype.addCommentsMember = function(member)
{
	if (!member)
		return;

	var notifiedObj = getNotifiedParts(member);

	// Add user to notify for next comment
	// -----------------------------------------
	if (!this.comment_users)
		this.comment_users = new Array();

	// Prevent duplicate entries
	var bFound = false;
	for (var i = 0; i < this.comment_users.length; i++)
	{
		if (this.comment_users[i].id == (notifiedObj.type + ":" + notifiedObj.id) || this.comment_users[i].id == notifiedObj.email)
			bFound = true;
	}

	if (!bFound)
	{
		if (notifiedObj.type == "email" && notifiedObj.email != "")
		{
			this.comment_users[this.comment_users.length] = {id:notifiedObj.email, name:notifiedObj.name};
		}
		else if (notifiedObj.type != "text")
		{
			// Object reference
			this.comment_users[this.comment_users.length] = {id:notifiedObj.type + ":" + notifiedObj.id, name:notifiedObj.name};
		}
	}
}

/**
 * Save the comment object
 *
 * @param {string} comment The actual comment to add
 * @param {TextBoxList} t_notify The text box list with people to notify
 * @param {int[]} attachments Array of uploaded file ids
 * @param {DOMElement} con The container where the comment is printed for callback to clear the form when done
 * @param {bool} sendToCust If true, get 'customer_id' value from parent object to send notification to
 */
AntObjectBrowser.prototype.saveComment = function(comment, t_notify, attachments, con, sendToCust)
{
	// Note from Sky: Moved this to the top because IE was returning empty for getValues if 
	// called below the block immediately following... makes no sense but welcome to IE!
	var values = t_notify.getValues();

    con.innerHTML = "<div class='loading'></div>";
	var obj = new CAntObject("comment");
	obj.setValue("comment", comment);
	if (this.obj_reference)
	{
		obj.setValue("obj_reference", this.obj_reference);
		obj.setMultiValue("associations", this.obj_reference);
	}

	var notify = "";
	for (var i = 0; i < values.length; i++)
	{
		if (notify) notify += ",";
		if (values[i][0])
			 notify += values[i][0];
		else if (values[i][1]) // email, no object
			 notify += values[i][1];
	}

	// Check for adding customer reference
	// Currenlty this is only used for cases and the reference is to customer_id field
	// We may expand in the future, but this is working well for the time being - joe
	if (this.parentObject && sendToCust)
	{
		if (notify) notify += ",";
		notify += "customer:" + this.parentObject.getValue("customer_id");
	}

	if (notify)
		obj.setValue("notify", notify);

	// Attachments
	for (var i in attachments)
		obj.setMultiValue("attachments", attachments[i]);

	obj.setValue("owner_id", "-3");
	obj.brwsercls = this;
	obj.t_notify = t_notify;
	obj.brwsercon = con;
	obj.onsave = function() 
	{ 
		this.onload = function()
		{
			// Refresh in 1 second to give index time to commit changes
			var bcls = this.brwsercls;
			setTimeout(function(){ bcls.refresh(); }, 1000);
			//this.brwsercls.refresh(); 

			this.brwsercon.fNotifyLoaded = false;
			this.brwsercls.printInlineAddComment(this.brwsercon, false); 
		}

		this.load();
	}
	obj.save();
}

/**
 * Set browser in context of a referenced object
 *
 * This is useful for copying common fields to new objects
 *
 * @param {CAntObject} obj The object we are in the context of
 */
AntObjectBrowser.prototype.setObjectContext = function(obj)
{
	this.objectContext = obj;
}
