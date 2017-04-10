/**
 * @fileOverview Menu class used to build popup menus
 *
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance ui popup menu
 *
 * @constructor
 */
alib.ui.PopupMenu = function(options)
{
	/**
	 * Options object
	 *
	 * @var {Object}
	 */
	this.options = options || new Object();

	/**
	 * Menu container
	 *
	 * @type {DOMElement}
	 */
	this.menuDiv = alib.dom.createElement("div");

	/**
	 * Popup reference
	 *
	 * @var {alib.ui.Popup}
	 */
	this.popup = new alib.ui.Popup(this.menuDiv);

	/**
	 * Flag to determine if menu has been rendered yet
	 *
	 * @type {bool}
	 */
	this.isRendered = false;

	/**
	 * Flag to set this as a submenu of a parent menu
	 *
	 * @type {bool}
	 */
	this.isSubmenu = this.options.submenu || false;

	/**
	 * Mobile mode flag
	 *
	 * @type {bool}
	 */
	this.mobile = this.options.mobile || ((alib.dom.getClientWidth() < 800)?true:false);

	/**
	 * Menu reference
	 *
	 * @var {alib.ui.Menu}
	 */
	this.menu = new alib.ui.Menu({submenu:this.isSubmenu, mobile:this.mobile});

	// Setup events for active and inactive 
	this.setupEvents();
}

/**
 * Setup events
 *
 * @protected
 */
alib.ui.PopupMenu.prototype.setupEvents = function()
{
	// Setup events for active and inactive
	alib.events.listen(this.popup, "isActive", function(evt) { 
		alib.events.triggerEvent(evt.data.popMenu, "isActive");
	}, {popMenu:this});

	alib.events.listen(this.popup, "isInactive", function(evt) { 
		alib.events.triggerEvent(evt.data.popMenu, "isInactive");
	}, {popMenu:this});

	// Trigger popup show event
	alib.events.listen(this.popup, "onShow", function(evt) { 
		alib.events.triggerEvent(evt.data.popMenu, "onShow");
	}, {popMenu:this});

	// Trigger popup hide event
	alib.events.listen(this.popup, "onHide", function(evt) { 
		alib.events.triggerEvent(evt.data.popMenu, "onHide");
	}, {popMenu:this});

	// Focus search/filtered input if available
	alib.events.listen(this, "onShow", function(evt) { 
		if (evt.data.popMenu.menu.filteredInput) {
			// Clear previous filter
			evt.data.popMenu.menu.filteredInput.value = "";
			evt.data.popMenu.menu.filterSearch("");
			// Focus on input for filter
			evt.data.popMenu.menu.filteredInput.focus();
		}
	}, {popMenu:this});

	// Focus search/filtered input if item is dynamically added and this menu is visible
	alib.events.listen(this.menu, "onAddItem", function(evt) { 
		if (evt.data.popMenu.menu.filteredInput && evt.data.popMenu.popup.isVisible()) {
			evt.data.popMenu.menu.filteredInput.focus();
			evt.data.popMenu.popup.reposition();
		}
	}, {popMenu:this});

	// Capture key events in the menu
	alib.events.listen(this, "onShow", function(evt) { 
		if (evt.data.popMenu.menu && evt.data.popMenu.popup.isVisible()) {
			evt.data.popMenu.menu.captureKeyEvents();
		}
	}, {popMenu:this});

	// Capture key events in the menu
	alib.events.listen(this, "onHide", function(evt) { 
		if (evt.data.popMenu.menu) {
			evt.data.popMenu.menu.clearKeyEvents();
		}
	}, {popMenu:this});

}

/**
 * Toggle menu
 *
 * @public
 */
alib.ui.PopupMenu.prototype.setVisible = function()
{
	if (!this.isRendered)
		this.render();

	this.popup.setVisible();
}

/**
 * Set if this menu has a filter/search box
 *
 * @public
 * @param {bool} isFiltered
 */
alib.ui.PopupMenu.prototype.setFiltered = function(isFiltered)
{
	this.menu.isFiltered = isFiltered || false;
}

/**
 * Show this menu
 *
 * @public
 */
alib.ui.PopupMenu.prototype.show = function()
{
	//this.menu.filteredInput.value = ""; // Clear
	if (!this.popup.isRendered)
		this.popup.render();

	this.popup.setVisible(true);

	if (this.menu.filteredInput)
	{
		this.menu.filteredInput.focus();
	}
}

/**
 * Hide this menu
 *
 * @public
 * @param {bool} recur If true then walk up this.parentMenu tree to close the root
 */
alib.ui.PopupMenu.prototype.hide = function(recur)
{
	var pop = (recur) ? this.popup.getRootPopup() : this;
	pop.setVisible(false);
}

/**
 * Render the menu
 *
 * @public
 */
alib.ui.PopupMenu.prototype.render = function()
{
	document.body.appendChild(this.menuDiv);
	alib.dom.styleSet(this.menuDiv, "display", "none");
	alib.dom.styleSet(this.menuDiv, "position", "absolute");

	// Setup menu div
	this.menu.render(this.menuDiv);

	// Make sure we don't render twice
	this.isRendered = true;
}

/**
 * Attache this popup menu to an element
 *
 * @public
 * @param {DOMElement} el The element to attach to
 * @param {string} vRel Relative vertical direction - "up"|"down"|"side"
 * @param {string} hRel Relative horizontal pos - "left"|"right"|"center"
 * @param {bool} onHover Show the menu on hover in addition to click
 */
alib.ui.PopupMenu.prototype.attach = function(el, vRel, hRel, onHover)
{

	var vertRel = vRel || "down";
	var horizRel = hRel || null;
	var showOnHover = onHover || false;

	this.popup.anchorToEl(el, vertRel, horizRel);


	if (showOnHover)
	{
		// Show - delayed 300ms 
		alib.events.listen(el, "mouseover", function(evt) {

			this.active = true;

			if (!evt.data.pmenu.isRendered)
				evt.data.pmenu.render();

			var el = this;

			window.setTimeout(function() { 
				// If the menu is not active then hide
				if (evt.data.pmenu.popup.active || el.active)
					evt.data.pmenu.popup.setVisible(true);
			}, 300);
				
		}, {pmenu:this});

		// Hide - delayed 300ms 
		alib.events.listen(el, "mouseout", function(evt) {

			this.active = false;

			// Give the user .3 second to activate sub-popup
			window.setTimeout(function() { 
				// If the menu is not active then hide
				if (!evt.data.pmenu.popup.active)
					evt.data.pmenu.popup.setVisible(false);
			}, 300);

		}, {pmenu:this});
	}
	else
	{
		alib.events.listen(el, "click", function(evt) {

			if (!evt.data.pmenu.isRendered)
				evt.data.pmenu.render();
				
			evt.data.pmenu.popup.setVisible();
		}, {pmenu:this});
	}
}

/**
 * Pass through this.menu to add an item
 *
 * @param {alib.ui.MenuItem} item
 */
alib.ui.PopupMenu.prototype.addItem = function(item)
{
	// Check for a submenu to set parent popup
	if (item.menu && item.menu.setParent)
		item.menu.setParent(this);
		
	this.menu.addItem(item);

	if (!item.isSubmenu)
	{
		alib.events.listen(item, "click", function(evt) {
			evt.data.menu.hide(true); // Recurrsively close
		}, {menu:this});
	}
}

/**
 * Pass through this.menu to add a submenu
 *
 * @param {alib.ui.PopupMenu} mmenu
 */
alib.ui.PopupMenu.prototype.setParent = function(popupMenu)
{
	if (this.popup && popupMenu.popup)
		this.popup.setParentPopup(popupMenu.popup);
}

/**
 * Clear all current items in the menu
 */
alib.ui.PopupMenu.prototype.clear = function()
{
	if (this.menu)
		this.menu.clear();
}

/**
 * Search through all the items in the array for an id if set
 *
 * @param {string} strQuery
 * @return {alib.ui.MenuItem}
 */
alib.ui.PopupMenu.prototype.getItemById = function(id)
{
	if (this.menu)
		return this.menu.getItemById(id);
}

/**
 * Put this menu in mobile mode
 *
 * @param {bool} on If in, then render this in one box
 * @return {alib.ui.MenuItem}
 */
alib.ui.PopupMenu.prototype.setMobileMode = function(on)
{
	this.mobile = on;
	this.menu.setMobileMode(on);
}
