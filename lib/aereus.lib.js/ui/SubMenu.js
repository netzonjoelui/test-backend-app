/**
 * @fileOverview Menu item class used to create a submenu item
 *
 * This class is a work in progress
 *
 * Example of how it is used:
 * <code>
 * </code>
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance ui sub-menu
 *
 * @param {string} label The label of this menu item
 * @constructor
 */
alib.ui.SubMenu = function (label, options)
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
	 * Flag used to distinguish from regular items
	 *
	 * @type {bool}
	 */
	this.isSubmenu = true;

	/**
	 * Is this item visible
	 *
	 * @type {bool}
	 */
	this.visible = true;

	/**
	 * Mobile mode flag
	 *
	 * @type {bool}
	 */
	this.mobile = this.options.mobile || ((alib.dom.getClientWidth() < 800)?true:false);

	/**
	 * Handle to popup menu
	 *
	 * @type {alib.ui.PopupMenu}
	 */
	this.menu = new alib.ui.PopupMenu({submenu:true, mobile:this.mobile});

	// If mobile mode and a submenu then print back item on top
	if (this.mobile)
	{
		var item = new alib.ui.MenuItem("Close", {icon:"&lt;"});
		item.cbData.menu = this.menu;
		this.menu.addItem(item);
	}
}

/**
 * Redner the menu item into the dom tree
 *
 * @param {DOMElement} con The parent container
 * @param {alib.ui.Menu} menu Handle to parent menu
 */
alib.ui.SubMenu.prototype.render = function(con)
{
	this.con = alib.dom.createElement("div", con);
	alib.dom.styleSetClass(this.con, "alibMenuItem");

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

	var arrowCon = alib.dom.createElement("span", this.labelCon);
	alib.dom.styleSetClass(arrowCon, "alibSubMenuArrow");
	arrowCon.innerHTML = "&#9654;";

	// Setup manual onlclick callback
	alib.events.listen(this, "click", function(evt) { this.onclick(); });

	// Forward mobile mode if already set
	if (this.mobile)
		this.menu.setMobileMode(this.mobile);

	// Attach popup menu to this item
	if (this.mobile)
		this.menu.attach(this.con, "down", "left", false);
	else
		this.menu.attach(this.con, "side", "right", true);

	// Attach active events
	alib.events.listen(this.menu, "isActive", function(evt) {
		alib.dom.styleAddClass(evt.data.itemCon, "alibMenuItemHover");
	}, {itemCon:this.con});

	alib.events.listen(this.menu, "onShow", function(evt) {
		alib.dom.styleAddClass(evt.data.itemCon, "alibMenuItemHover");
	}, {itemCon:this.con});

	alib.events.listen(this.menu, "onHide", function(evt) {
		alib.dom.styleRemoveClass(evt.data.itemCon, "alibMenuItemHover");
	}, {itemCon:this.con});
}

/**
 * Force click event
 */
alib.ui.SubMenu.prototype.click = function() {
	alib.events.triggerEvent(this, "click");
}

/**
 * Onclick callback
 */
alib.ui.SubMenu.prototype.onclick = function() {}

/**
 * Pass through this.menu to add an item
 *
 * @param {alib.ui.MenuItem} item
 */
alib.ui.SubMenu.prototype.addItem = function(item)
{
	this.menu.addItem(item);
}

/**
 * Pass through this.menu to add a submenu
 *
 * @param {alib.ui.Menu} mmenu
 */
alib.ui.SubMenu.prototype.addSubmenu = function(menu)
{
	this.menu.addSubmenu(menu);
}

/**
 * Put this menu in mobile mode
 *
 * @param {bool} on If in, then render this in one box
 * @return {alib.ui.MenuItem}
 */
alib.ui.SubMenu.prototype.setMobileMode = function(on)
{
	this.mobile = on;
}

/**
 * Set label
 *
 * @param {string} label The html to st to label to
 */
alib.ui.SubMenu.prototype.setLabel = function(label) {
	this.label = label;
	this.labelCon.innerHTML = this.label;

	var arrowCon = alib.dom.createElement("span", this.labelCon);
	alib.dom.styleSetClass(arrowCon, "alibSubMenuArrow");
	arrowCon.innerHTML = "&#9654;";
}

/**
 * Set label
 *
 * @param {bool} isSelected If true then the item is selected, otherwise false
 */
alib.ui.SubMenu.prototype.setSelected = function(isSelected) {
	if (isSelected)
		alib.dom.styleAddClass(this.con, "alibMenuItemHover");
	else
		alib.dom.styleRemoveClass(this.con, "alibMenuItemHover");
}
