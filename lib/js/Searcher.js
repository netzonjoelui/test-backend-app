/**
 * @fileOverview Use this for the header search box
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of Ant.Searcher
 *
 * @constructor
 */
Ant.Searcher = function()
{
	/**
	 * The container that will house the search input
	 *
	 * @var {DOMElement}
	 */
	this.inpt = null;

	/**
	 * The 'Go' button
	 *
	 * @var {button}
	 */
	this.button = null;

	/**
	 * Popup reference
	 *
	 * @var {alib.ui.Popup}
	 */
	this.popup = null;

	/**
	 * Handle to net xhr
	 *
	 * @var {alib.net.Xhr}
	 */
	this.xhr = null;
}

/**
 * Render into to dom tree
 *
 * @param DOMElement con The container that will house the rendered tool
 */
Ant.Searcher.prototype.render = function(con)
{
	this.inpt = alib.dom.createElement("input", con);
	this.inpt.placeholder = "Search";

	this.button = alib.dom.createElement("button", con, "Go");

	this.resultsCon = alib.dom.createElement("div", con);
	alib.dom.styleSetClass(this.resultsCon, "searcherPopup");

	this.popup = new alib.ui.Popup(this.resultsCon);
	this.popup.anchorToEl(this.inpt, "down", "left");

	// Setup Listeners
	alib.events.listen(this.inpt, "keyup", function(evt) { evt.data.cls.showResults() }, {cls:this});
	alib.events.listen(this.inpt, "focus", function(evt) { evt.data.cls.showResults() }, {cls:this});
}

/**
 * Show results pane
 */
Ant.Searcher.prototype.showResults = function()
{
	var searchString = this.inpt.value;

	if (!searchString)
	{
		this.popup.setVisible(false);
		return;
	}

	// Resize to match input
	alib.dom.styleSet(this.resultsCon, "width", (alib.dom.getElementWidth(this.inpt) - 10) + "px");

	this.popup.setVisible(true);

	// Do not query the same value
	if (this.inpt.value != this.lastQuery)
	{
		this.resultsCon.innerHTML = "Searching...";
		this.getResults();
	}
}

/**
 * Get query results and print
 */
Ant.Searcher.prototype.getResults = function()
{
	// Do not query the same value
	if (this.inpt.value == this.lastQuery)
		return;

	// Abort past xhr if in progress
	if (this.xhr != null)
	{
		if (this.xhr.isInProgress())
			this.xhr.abort();
	}

	// Poll the server until we get data or timeout
	this.xhr = new alib.net.Xhr();

	// Retrieve results
	alib.events.listen(this.xhr, "load", function(evt) { 

		evt.data.searchCls.resultsCon.innerHTML = "";

		var resp = this.getResponse();

		// Loop through all types
		for (var i in resp)
		{
			var objects = resp[i];

			if (objects.length > 0)
			{
				var row = alib.dom.createElement("div", evt.data.searchCls.resultsCon, i);
				alib.dom.styleSetClass(row, "searcherGroupHeader");
			}

			for (var j in objects)
			{
				var obj = objects[j];
				var row = alib.dom.createElement("div", evt.data.searchCls.resultsCon);
				alib.dom.styleSetClass(row, "searcherItemRow");

				row.innerHTML = "<img src='/images/icons/objects/" + obj.iconName + "_16.png' /> " + obj.title;
				row.oid = obj.id;
				row.objType = obj.objType;
				row.onclick = function() {
					loadObjectForm(this.objType, this.oid);
					evt.data.searchCls.popup.setVisible(false);
				}
			}
		}

	}, {searchCls:this});

	// Timed out
	alib.events.listen(this.xhr, "error", function(evt) { 
	}, {searchCls:this});

	this.xhr.send("/controller/Search/query", "GET", {q:this.inpt.value});

	this.lastQuery = this.inpt.value;
}
