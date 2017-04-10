/**
 * @fileOverview Menu item class used to represent each menu item
 *
 * This class is a work in progress
 *
 * Example of how it is used:
 * <code>
 * 	var menu = new alib.ui.Menu();
 * 	var item = new alib.ui.MenuItem("Item 1");
 * 	item.onclick = function() { alert("Clicked"); };
 * 	menu.addItem(item, true, "opt_item_id");
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
 * @param {string} label The label of this menu item
 * @constructor
 */
alib.ui.MenuItem = function (label, options, id)
{
	/**
	 * Label
	 *
	 * @var {string}
	 */
	this.label = label;

	/**
	 * Options object
	 *
	 * @type {Object}
	 */
	this.options = options || new Object();

	/**
	 * Menu item container
	 *
	 * @type {DOMElement}
	 */
	this.con = null;

	/**
	 * Inner label container
	 *
	 * @type {DOMElement}
	 */
	this.labelCon = null;

	/**
	 * Generic object to hold callback properties
	 *
	 * @type {Object}
	 */
	this.cbData = new Object();

	/**
	 * Optional unique id
	 *
	 * @type {string}
	 */
	this.id = id || "";

	/**
	 * Is this item visible
	 *
	 * @type {bool}
	 */
	this.visible = true;
}

/**
 * Redner the menu item into the dom tree
 *
 * @param {DOMElement} con The parent container
 * @param {alib.ui.Menu} menu Handle to parent menu
 */
alib.ui.MenuItem.prototype.render = function(con, menu)
{
	if (!this.con)
	{
		this.con = alib.dom.createElement("div", con);
		alib.dom.styleSetClass(this.con, "alibMenuItem");

		// Add icon if set
		if (this.options.icon)
		{
			var iconCon = alib.dom.createElement("span", this.con);
			alib.dom.styleSetClass(iconCon, "alibMenuItemIcon");
			iconCon.innerHTML = this.options.icon;
		}

		alib.events.listen(this.con, "mouseover", function() {
			alib.dom.styleAddClass(this, "alibMenuItemHover");
		}, {});

		alib.events.listen(this.con, "mouseout", function() {
			alib.dom.styleRemoveClass(this, "alibMenuItemHover");
		}, {});

		alib.events.listen(this.con, "click", function(evt) {
			alib.events.triggerEvent(evt.data.mItem, "click");
		}, {mItem:this});

		this.labelCon = alib.dom.createElement("div", this.con);
		alib.dom.styleSetClass(this.labelCon, "alibMenuItemLabel");
		this.labelCon.innerHTML = this.label;

		// Setup manual onlclick callback
		alib.events.listen(this, "click", function(evt) { this.onclick(); });
	}
	else
	{
		con.appendChild(this.con);
	}
}

/**
 * Force click event
 */
alib.ui.MenuItem.prototype.click = function() {
	alib.events.triggerEvent(this, "click");
}

/**
 * Onclick callback
 */
alib.ui.MenuItem.prototype.onclick = function() {}

/**
 * Apply a search filter to this time
 *
 * If the filter does not match the label, this item will be hidden.
 *
 * @param {string} strQuery
 * @return {bool} True if item is visible, false if hidden
 */
alib.ui.MenuItem.prototype.applyVisibleFilter = function(strQuery)
{
	if(this.label.toLowerCase().indexOf(strQuery) !== -1 || !strQuery)
	{
		alib.dom.styleSet(this.con, "display", "block");
		this.visible = true;
	}
	else
	{
		alib.dom.styleSet(this.con, "display", "none");
		this.visible = false;
	}

	return this.visible;
}

/**
 * Set label
 *
 * @param {string} label The html to st to label to
 */
alib.ui.MenuItem.prototype.setLabel = function(label) {
	this.label = label;
	this.labelCon.innerHTML = this.label;
}

/**
 * Set label
 *
 * @param {bool} isSelected If true then the item is selected, otherwise false
 */
alib.ui.MenuItem.prototype.setSelected = function(isSelected) {
	if (isSelected)
		alib.dom.styleAddClass(this.con, "alibMenuItemHover");
	else
		alib.dom.styleRemoveClass(this.con, "alibMenuItemHover");
}
