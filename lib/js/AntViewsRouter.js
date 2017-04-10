/**
* @fileOverview Main router for handling hashed URLS and routing them to AntViews
*
* Views are a little like pages but stay
* within the DOM. The main advantage is hash codes are used to navigate
* though a page. Using views allows you to bind function calls to url hashes.
* Each view only handles one lovel in the url but can have children so
* /my/url would be represented by views[my].views[url].show
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of AntViewsRouter
 *
 * @constructor
 */
function AntViewsRouter()
{
    this.cloneId = "";
	this.lastLoaded = "";
	this.defaultView = ""; // Used to set a default view
	this.options = new Object();
	var me = this;
	this.interval = window.setInterval(function(){ me.checkNav(); }, 50);
}

AntViewsRouter.prototype.checkNav = function()
{
	var load = "";
	if (document.location.hash)
	{
		var load = document.location.hash.substring(1);
	}
    
	if (load == "" && this.defaultView != "")
		load = this.defaultView;

	if (load != "" && load != this.lastLoaded)
	{
		this.lastLoaded = load;
		//ALib.m_debug = true;
		//ALib.trace(load);
		this.onchange(load);
	}
}

/**
* Can be overridden. Triggen when a hash changes in the URL
*/
AntViewsRouter.prototype.onchange = function(path)
{
}
