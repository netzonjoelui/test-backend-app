/**
 * @fileOverview Menu class used to build menus
 *
 * This class is a work in progress
 *
 * Example:
 * <code>
 * 	var menu = new alib.ui.Menu();
 * 	menu.addItem(new alib.ui.MenuItem("Item 1"), true, "opt_item_id");
 * 	menu.addItem(new alib.ui.MenuItem("Item 2"), true, "opt_item2_id");
 * 	menu.render(document.getElementById('divid'));
 * </code>
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2012 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance ui menu
 *
 * @constructor
 */
alib.ui.Menu = function(options)
{
	/**
	 * Main div contianer created when rendered
	 *
	 * @type {DOMElement}
	 */
	this.mainCon = null;

	/**
	 * Options object
	 *
	 * @type {Object}
	 */
	this.options = options || new Object();

	/**
	 * Array of entries
	 *
	 * @type {Array}
	 */
	this.entries = new Array();

	/**
	 * Index of selected entry
	 *
	 * @type {int}
	 */
	this.selectedEntry = -1;

	/**
	 * Flag to determine if we should show a filter search box
	 *
	 * @type {bool}
	 */
	this.isFiltered = this.options.filtered || false;

	/**
	 * Flag to set this as a submenu of a parent menu
	 *
	 * @type {bool}
	 */
	this.isSubmenu = this.options.submenu || false;

	/**
	 * Search input
	 *
	 * @type {DOMElement.input}
	 */
	this.filteredInput = null;

	/**
	 * Filter container
	 *
	 * @type {DOMElement.div}
	 */
	this.filterCon = null;

	/**
	 * Mobile mode flag
	 *
	 * @type {bool}
	 */
	this.mobile = this.options.mobile || ((alib.dom.getClientWidth() < 800)?true:false);
}

/**
 * Add a menu item to the menu
 *
 * @param {alib.ui.MenuItem} item
 */
alib.ui.Menu.prototype.addItem = function(item)
{
	this.entries.push(item);
	this.refresh(); // If alredy rendered then redraw with new item
	
	alib.events.triggerEvent(this, "onAddItem");
}

/**
 * Clear all current items in the menu
 */
alib.ui.Menu.prototype.clear = function()
{
	this.mainCon.innerHTML = "";
	this.entries = new Array();
}

/**
 * Refresh based on added items
 */
alib.ui.Menu.prototype.refresh = function()
{
	if (!this.mainCon)
		return;

	// Clear rendered div because IE appears to leave it orphaned while clearing all child content
	for (var i in this.entries)
	{
		if (this.entries[i].con)
			this.entries[i].con.parentNode.removeChild(this.entries[i].con);
	}

	if (this.filterCon != null)
		this.filterCon.innerHTML = "";

	//this.mainCon.innerHTML = "";

	this.render();
}

/**
 * Render this item into the DOM tree
 *
 * @param {DOMElement} con The container to render this menu into
 */
alib.ui.Menu.prototype.render = function(con)
{
	if (!this.mainCon && con)
	{
		this.mainCon = alib.dom.createElement("div", con);
		alib.dom.styleSetClass(this.mainCon, "alibMenu");
	}

	// If filtered then add search box
	if (this.isFiltered)
	{
		if (!this.filterCon)
			this.filterCon = alib.dom.createElement("div", this.mainCon);

		this.renderFilterForm(this.filterCon);
	}

	for (var i in this.entries)
	{
		this.entries[i].render(this.mainCon, this);
	}
}

/**
 * Create filtered search box
 *
 * @param {DOMElement} con
 */
alib.ui.Menu.prototype.renderFilterForm = function(con)
{
	var filterCon = alib.dom.createElement("div", con);
	alib.dom.styleSet(filterCon, "margin", "0 5px 0 5px");
	alib.dom.styleSet(filterCon, "min-width", "100px");

	var input = alib.dom.createElement("input", filterCon);
	input.type = "text";
	alib.dom.styleSet(input, "width", "98%");
	this.filteredInput = input;

	alib.events.listen(input, "keyup", function(evt) {
		evt.data.menu.filterSearch(this.value);
	}, {menu:this});
}

/**
 * Search through all the items in the array for a query string and hide anything that does not match
 *
 * @param {string} strQuery
 */
alib.ui.Menu.prototype.filterSearch = function(strQuery)
{
	for (var i in this.entries)
	{
		this.entries[i].applyVisibleFilter(strQuery);
	}
}

/**
 * Search through all the items in the array for an id if set
 *
 * @param {string} strQuery
 * @return {alib.ui.MenuItem}
 */
alib.ui.Menu.prototype.getItemById = function(id)
{
	for (var i in this.entries)
	{
		if (this.entries[i].id == id)
			return this.entries[i];
	}

	return null;
}

/**
 * Put this menu in mobile mode
 *
 * @param {bool} on If in, then render this in one box
 * @return {alib.ui.MenuItem}
 */
alib.ui.Menu.prototype.setMobileMode = function(on) {
	this.mobile = on;
}

/**
 * Move down an item from current position, or select first
 */
alib.ui.Menu.prototype.moveDown = function() {
	if (this.entries.length == 0)
		return;

	// Make sure some items are visible
	var isVisible = false;
	for (var i in this.entries)
	{
		if (this.entries[i].visible)
			isVisible = true;
	}
	if (!isVisible)
		return;

	this.selectedEntry++;

	// If beyond range then wrap to 0
	if (this.selectedEntry > (this.entries.length - 1))
		this.selectedEntry = 0;

	// If next item is not visible, then skip to next
	if (!this.entries[this.selectedEntry].visible)
		return this.moveDown();

	// Hover active, remove over class from all others
	for (var i in this.entries)
		this.entries[i].setSelected((i==this.selectedEntry)?true:false);
}

/**
 * Move down an item from current position, or select first
 */
alib.ui.Menu.prototype.moveUp = function() {
	if (this.entries.length == 0)
		return;

	// Make sure some items are visible
	var isVisible = false;
	for (var i in this.entries)
	{
		if (this.entries[i].visible)
			isVisible = true;
	}
	if (!isVisible)
		return;

	if (this.selectedEntry <= 0)
		this.selectedEntry = this.entries.length - 1;
	else
		this.selectedEntry--;

	// If next item is not visible, then skip to next
	if (!this.entries[this.selectedEntry].visible)
		return this.moveUp();

	// Hover active, remove over class from all others
	for (var i in this.entries)
		this.entries[i].setSelected((i==this.selectedEntry)?true:false);
}

/**
 * Listen for arrow keys and return
 */
alib.ui.Menu.prototype.captureKeyEvents = function() {

	var me = this;
	var onkeyDownHndler = function(evt) {
		if (!evt) evt = event;
		var a = evt.keyCode;
		
		switch (a)
		{
		// Up arrow
		case 38:
			me.moveUp();
			return false;
			break;

		// Down arrow
		case 40:
			me.moveDown();
			return false;
			break;

		// Return or tab gets hit
		case 13:
			if (me.selectedEntry)
				me.entries[me.selectedEntry].click();
			return true;
			/*
			if (actb_display)
			{
				actb_curr.m_inac = false;
				actb_caretmove = 1;
				actb_penter();
				return false;
			}
			else
			{
				return true;
			}
			*/
			break;

		default:
			return true;
			break;
		}
	};

	alib.dom.addEvent(document, "keydown", onkeyDownHndler);
	//alib.dom.addEvent(document,"keypress", actb_keypress);

	this.clearKeyEvents = function(){
		alib.dom.removeEvent(document,"keydown",onkeyDownHndler);
		//alib.dom.removeEvent(document,"keypress",actb_keypress);
	}
}

/**
 * Clear key event listeners
 */
alib.ui.Menu.prototype.clearKeyEvents = function() {
	// Will be defined when captureKeyEvents is called
}

/*
function actb_keypress(e)
	{
		if (actb_caretmove) alib.dom.stopEvent(e);
		return !actb_caretmove;
	}
	*/

