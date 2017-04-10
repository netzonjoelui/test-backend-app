/**
* @fileOverview AntView(s) allow doms to be treated like pages.
*
* Each view has a parent manager (reposible for showing and hiding it) then  
* a child manager to handle sub-views. These are basically simple routers.
*
* Views enable a single-page application (no refresh) to have multi-level views
* and deep-linking through the use of a hash url.
*
* Example:
* <code>
* 	parentView.setViewsSingle(true); // Only display one view at a time - children hide parent view
*
* 	var viewItem = parentView.addView("viewname", {});
*
*	viewItem.options.param = "value to forward"; // options is a placeholder object for passing vars to callbacks
*
*	viewItem.render = function() // called only the first time the view is shown
*	{ 
*		this.con.innerHTML = "print my form here"; // this.con is automatically created
*	} 
*
*	viewItem.onshow = function()  // draws in onshow so that it redraws every time the view is displayed
*	{ 
*	};
*
*	viewItem.onhide = function()  // is called every time the view is hidden
*	{ 
*	};
* </code>
*
* @author: 	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
* Create a view instance of AntView and place in in an array in viewman
*
* @constructor
* @param {string} name Required unique string name of this view
* @param {object} viewman Required reference to AntViewManager object
* @param {object} parentView reference to a parent AntView object
*/
function AntView(name, viewman, parentView)
{
	/**
	 * Bound events
	 *
	 * @var {Array}
	 */
	this.boundEvents = new Array();

	this.parentViewManager = viewman;
	this.viewManager = null;
	this.parentView = (parentView) ? parentView : null;
	this.activeChildView = null;
	this.isActive = false;
	this.isRendered = false;
	this.options = new Object();
	this.conOuter = null; 		// container passed to place view in
	this.con = null;	 		// child container of conOuter that holds this rendered view
	this.conChildren = null; 	// child container of conOuter that holds all rendered children
	this.variable = ""; 		// variables can exist in url part. Example: view p:pnum would render view p with variable num
	this.pathRelative = ""; 	// path relative to this view
	this.title = "";			// optional human readable title
	this.defaultView = "";
	this.setOnTitleChange = new Array();
    this.fromClone = false;     // determines if the object was being cloned
    
	var parts = name.split(":");
	this.name = parts[0];
	if (parts.length > 1)
	{
		// Possible save the variable name if we are going to use multiple variables
	}
}

/**
 * Set a callback function to call given a certian event name
 *
 * @param {string} evname Required The name of the event to hook into
 * @param {function} cb_fun Required The function to call when a view loads
 * @param {object} options Required object that will be passed to the cb
 */
AntView.prototype.on = function(evname, cb_fun, options)
{
	this.boundEvents[this.boundEvents.length] = {cb:cb_fun, opts:options, name:evname};
}

/**
 * Set a callback function to call when this or a child view loads or is shown. 
 *
 * This is displayed every time the view is loaded which includes all child view loads. 
 * If you only want o call a function when this view is shown, then use bindOnViewShow instead.
 *
 * Example:
 * view.bindOnViewLoad(function(opts, remPath) { opts.cls.setClassFunPath(remPath); }, { cls:this });
 *
 * @param {string} cb_fun Required The function to call when a view loads
 * @param {object} options Required object that will be passed to the cb
 */
AntView.prototype.bindOnViewLoad = function(cb_fun, options)
{
	this.on("load", cb_fun, options);
	//this.boundEvents[this.boundEvents.length] = {cb:cb_fun, opts:options, name:"load"};
}

/**
 * Call all bound callback functions because this view just loaded
 *
 * @param {string} evname The name of the event that was fired
 */
AntView.prototype.triggerEvents = function(evname)
{
	for (var i = 0; i < this.boundEvents.length; i++)
	{
		if (this.boundEvents[i].name == evname)
		{
			if ("load" == evname) // add extra param
				this.boundEvents[i].cb(this.boundEvents[i].opts, this.pathRelative);
			else
				this.boundEvents[i].cb(this.boundEvents[i].opts);
		}
	}
}

/**
 * Call all bound callback functions because this view just loaded
 */
AntView.prototype.setDefaultView = function(viewname)
{
	this.defaultView = viewname;
}

/**
 * Call this function to fire all resize events
 *
 * @param {bool} resizeChildren If set to true, all active children will be resized
 */
AntView.prototype.resize = function(resizeChildren)
{
	var resizeCh = (resizeChildren) ? true : false;

	if (resizeCh)
		this.viewManager.resizeActiveView();

	this.onresize();
}

/**
 * Internal function to show this view. Will call render on the first time. redner shold be overridden.
 */
AntView.prototype.show = function()
{
	//ALib.m_debug = true;
	//ALib.trace("Show: " + this.getPath());

	if (!this.isRendered)
	{
		this.isRendered = true;
		this.render();
	}

	if (this.con)
	{
		//alib.fx.fadeIn(this.con, function() { alib.dom.styleSet(this, "display", "block");  });
		alib.dom.styleSet(this.con, "display", "block");
	}

	if (this.parentViewManager.isMobile)
	{
		var isBack = (window.avChangePageback) ? true : false;
		changePage(this.getPath(true), isBack); // The true param excludes vars to make change to containers rather than specific ids
		window.avChangePageback = false; // Reset flag for next time
	}

	if (this.defaultView && !this.hasChildrenVisible())
		this.navigate(this.defaultView);
	
	this.triggerEvents("show");
	this.onshow();
	this.onresize();
	this.isActive = true;
}

/**
* Internal function to hide this view.
*/
AntView.prototype.hide = function(exclChild)
{
	if (!this.isActive)
		return false;

	if (this.con)
	{
		this.con.style.display = "none";
		//alib.fx.fadeOut(this.con, function() { alib.dom.styleSet(this, "display", "none"); });
	}

	this.isActive = false;

	if (this.isRendered)
	{
		this.triggerEvents("hide");
		this.onhide();
	}
}

/**
* Pass-through to this.parentViewManager.addView with this as parent
* See AntViewManager::addView
*/
AntView.prototype.addView = function(name, optionargs, con)
{
	var usecon = (con) ? con : null;

	if (this.viewManager == null)
	{
		this.viewManager = new AntViewManager();
		if (this.parentViewManager)
			this.viewManager.isMobile = this.parentViewManager.isMobile;
	}

	//ALib.m_debug = true;
	//ALib.trace("Adding View: " + this.getPath() + "/" + name);

	return this.viewManager.addView(name, optionargs, usecon, this);
}

/**
* Get a child view by name
*
* @param {string} name unique name of the view to load
* @return {AntView} View if found by name, null if no child view exists
*/
AntView.prototype.getView = function(name)
{
	if (this.viewManager)
	{
		return this.viewManager.getView(name);
	}

	return null
}

/**
* Pass-through to this.parentViewManager.setViewsToggle
* See AntViewManager::addView
*/
AntView.prototype.setViewsToggle = function(fToggle)
{
	if (this.viewManager == null)
	{
		this.viewManager = new AntViewManager();

		if (this.parentViewManager)
			this.viewManager.isMobile = this.parentViewManager.isMobile;
	}
	
	this.viewManager.setViewsToggle(fToggle);
}

/**
* Pass-through to this.parentViewManager.setViewsToggle. When a child shows then hide this view - used for heiarch
* See AntViewManager::setViewsSingle
*/
AntView.prototype.setViewsSingle = function(fToggle)
{
	if (this.viewManager == null)
	{
		this.viewManager = new AntViewManager();

		if (this.parentViewManager)
			this.viewManager.isMobile = this.parentViewManager.isMobile;
	}
	
	this.viewManager.setViewsSingle(fToggle);
}

/**
 * Get the parent view if set
 *
 * @return {AntView|bool} View if parent is set, false if there is no parent
 */
AntView.prototype.getParentView = function()
{
	return (this.parentView) ? this.parentView : false;
}

/**
* Traverse views and get the full path of this view:
* view('app').view('customers') = 'app/customers'
*
* @param bool excludevars If set to true, then vars will not be included in the returned path
*/
AntView.prototype.getPath = function(excludevars)
{
	var name = this.name;
	var doNotPrintVar = (typeof excludevars != "undefined") ? excludevars : false;
    
	// Make sure the variable in included
	if (this.variable && doNotPrintVar == false)
		name += ":" + this.variable;
        
	if (this.parentView)
		var path = this.parentView.getPath() + "/" + name;
	else
		var path = name;

	return path;
}

/**
* Get a numan readable title. If not set, then create one.
*
* @this {AntView}
* @public
* @param {DOMElement} el An optional dom element to bind 'onchange' event to. When title of view changes, the innerHTML of el will change
* @return {string} The title of this view
*/
AntView.prototype.getTitle = function(el)
{
	if (this.title)
	{
		var title = this.title;
	}
	else
	{
		// replace dash with space
		var title = this.name.replace('-', ' ');
		// replace underline with space
		var title = this.name.replace('_', ' ');
		// ucword
		title = title.replace(/^([a-z])|\s+([a-z])/g, function ($1) { return $1.toUpperCase(); });
	}

	if (typeof el != "undefined")
	{
		el.innerHTML = title;
		this.setOnTitleChange[this.setOnTitleChange.length] = el;
	}
	else
	{
		return title;
	}
}

/**
* Set a human readable title
*/
AntView.prototype.setTitle = function(title)
{
	this.title = title;
	for (var i = 0; i < this.setOnTitleChange.length; i++)
	{
		try
		{
			this.setOnTitleChange[i].innerHTML = title;
		}
		catch(e) {}
	}
}

/**
* Check url part to see if the name matches this view
*/
AntView.prototype.nameMatch = function(name)
{
	if (typeof name == "undefined")
	{
		throw "No view name was passed to AntView::nameMatch for " + this.getPath();
	}

	var parts = name.split(":");
	name = parts[0];

	return (name == this.name) ? true : false;
}

/**
* Set the hash and load a view programatically. 
*/
AntView.prototype.navigate = function(viewname)
{
	document.location.hash = "#" + this.getPath() + "/" + viewname;
}

/**
* Check if going back a view is an option (are we on first level).
*
* This does not rely entirely on the history object because if 
* we are at the root view (home), then we don't want to go back.
*/
AntView.prototype.hasback = function()
{
	var ret = false; // Assume we are on the root
	var path = this.getPath();

	if (path.indexOf("/")!=-1)
	{
		if (history.length > 1)
			ret = true;
	}

	return ret;
}

/**
* Navigate up to parent view
*/
AntView.prototype.goup = function(depth)
{
	if (this.parentView)
	{
		document.location.hash = "#" + this.parentView.getPath();
	}
	else
	{
		history.go(-1);
	}

	// global avChangePageback is used in mobile to determine transition direction
	window.avChangePageback = true;
}

/**
* Check if child views are being shown = check for deep linking
*
* @return {bool} True if the hash path has child views visible, otherwise false
*/
AntView.prototype.hasChildrenVisible = function()
{
	if (document.location.hash == "#" + this.getPath() || document.location.hash == "") // last assumes default
		return false;
	else
		return true;
}

/**
* Go back
*/
AntView.prototype.goback = function(depth)
{
	history.go(-1);

	// global avChangePageback is used in mobile to determine transition direction
	window.avChangePageback = true;
}

/**
* Pass-through to this.parentViewManager.load
* See AntViewManager::load
*/
AntView.prototype.load = function(path)
{
	this.pathRelative = path; // path relative to this view

	if (this.viewManager != null)
	{
		if (!this.viewManager.load(path))
		{
			this.m_tmpLoadPath = path; // If it failed to load, cache for later just in case views are still loading
		}
	}
	else
	{
		this.m_tmpLoadPath = path; // If it failed to load, cache for later just in case views are still loading
	}
}

/**
* Clear loading flag that will cause all subsequent load calls to be queued until setViewsLoaded is called.
*/
AntView.prototype.setViewsLoaded = function()
{
	//ALib.m_debug = true;
	//ALib.trace("View ["+this.name+"] finished loading ");

	if (this.m_tmpLoadPath)
	{
		//ALib.m_debug = true;
		//ALib.trace("Found delayed load " + this.m_tmpLoadPath);
		this.load(this.m_tmpLoadPath);
		this.m_tmpLoadPath = "";
	}

	if (this.defaultView && !this.hasChildrenVisible())
		this.navigate(this.defaultView);

	// Call load callbacks for view
	this.triggerEvents("load");
}

/**
* Find out if this view had children views
*/
AntView.prototype.hideChildren = function()
{
	if (this.viewManager)
	{
		for (var i = 0; i < this.viewManager.views.length; i++)
		{
			this.viewManager.views[i].hide();
			this.viewManager.views[i].hideChildren();
		}
	}

	this.pathRelative = ""; // Reset relative path
}

/**
 * Gets the object id from hash url string
 *
 * @public
 * @this {AntView}
 * @param {string} objName      Object Name to be checked
 */
AntView.prototype.getHashObjectId = function(objName)
{
    if(this.name == objName)
        return this.variable;
        
    if (this.parentView)
        var objId = this.parentView.getHashObjectId(objName);
    
    if(objId)
        return objId;
    else
        return false;
}

/**
* Used to draw view and should be overriden. 
*
* If a containing element was passed on new AntView then this.con 
* will be populated with a div that can be manipulated with contents. 
* this.options is also available for any processing.
*/
AntView.prototype.render = function()
{
}

/**
* Can be overridden. Fires once a view is shown.
*/
AntView.prototype.onshow = function()
{
}

/**
* Can be overridden. Fires once a view is hidden.
*/
AntView.prototype.onhide = function()
{
}

/**
* Can be overridden. Fires once a view is shown for resizing.
*/
AntView.prototype.onresize = function()
{
	//alib.m_debug = true;
	//alib.trace("Resize: " + this.name);
}
