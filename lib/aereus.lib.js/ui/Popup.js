/**
 * @fileOverview Class to handle turning a dialog into a popup
 *
 * TODO: Need to handle closing the menu when clicked on the document
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Keep track of all open popups
 */
alib.ui.popups = null;

alib.ui.popupIds = 1;

/**
 * Creates an instance ui popup menu
 *
 * @constructor
 */
alib.ui.Popup = function(el, options)
{
	/**
	 * The element to hover
	 *
	 * @type {DOMElement}
	 */
	this.con = el;

	/**
	 * Options object
	 *
	 * @type {Object}
	 */
	this.options = options || new Object();

	/**
	 * Auto close on document click
	 *
	 * @type {bool}
	 */
	this.autoClose = (typeof this.options.autoClose != "undefined") ? this.options.autoClose : true;

	/**
	 * Element in the DOM popup is anchored to
	 *
	 * @type {DOMElement}
	 */
	this.anchorEl = (this.options.anchorEl) ? this.options.anchorEl : null;

	/**
	 * Is visible flag
	 *
	 * @type {bool}
	 */
	this.visible = (typeof this.options.visible != "undefined") ? this.options.visible : false;

	/**
	 * If this popup (or any children) are active
	 *
	 * @type {bool}
	 */
	this.active = false;

	/**
	 * Last hide time usually set with Date::getTime
	 *
	 * @private
	 * @type {int}
	 */
	this.lastHideTime = null;

	/**
	 * Parent menu
	 *
	 * @public
	 * @type {alib.ui.Popup}
	 */
	this.parent = null;

	/**
	 * Children menus
	 *
	 * @public
	 * @type {alib.ui.Popup[]}
	 */
	this.children = new Array;

	/**
	 * Position coords
	 *
	 * @type {Object}
	 */
	this.position = {top:0, left:0, relVert:"bottom", relHoriz:"left"};

	/**
	 * Flag to set if this has been registered
	 *
	 * @type {bool}
	 */
	this.isRegistered = false;

	// Add blur events for registering if the element is active
	alib.events.listen(el, "mouseover", function(evt) {
		evt.data.popup.setActive(true);
	}, {popup:this});

	alib.events.listen(el, "mouseout", function(evt) {
		evt.data.popup.setActive(false);
	}, {popup:this});

	if (this.options.parent)
		this.setParentPopup(this.options.parent);

	// Temp set id for tracking events
	this.id = alib.ui.popupIds + 1;
	alib.ui.popupIds++;
}

/**
 * Toggle visible
 *
 * @param {bool} visible If true then show
 */
alib.ui.Popup.prototype.setVisible = function(visible)
{
	// Manually set, or toggle
	if (typeof visible != "undefined")
		this.visible = visible;
	else
		this.visible = (this.visible) ? false : true;

	this.setActive(this.visible);

	alib.dom.styleSet(this.con, "position", "aboslute");
	alib.dom.styleSet(this.con, "z-index", "1000");

	var now = new Date();

	if (this.visible)
	{
		if (!this.isRegistered)
			this.registerPopup();

		alib.dom.styleSet(this.con, "display", "block");
		this.reposition();

		alib.events.triggerEvent(this, "onShow");
	}
	else
	{
		alib.dom.styleSet(this.con, "display", "none");
		this.lastHideTime = now.getTime();

		alib.events.triggerEvent(this, "onHide");

		for (var i in this.children)
		{
			this.children[i].setVisible(false);
		}
	}
}

/**
 * Walk up the parent-child tree of popups to get the root or first popup
 *
 * WARNING: this does not test for circular references which can case an infinite loop
 */
alib.ui.Popup.prototype.getRootPopup = function()
{
	if (this.parent)
		return this.parent.getRootPopup();
	else
		return this;
}

/**
 * Set this popup and any parent popups as active
 *
 * @param {bool} isActive If not set then toggle
 */
alib.ui.Popup.prototype.setActive = function(isActive)
{
	// Manually set, or toggle
	if (typeof isActive != "undefined")
		this.active = isActive;
	else
		this.active = (this.active) ? false : true;
		
	// Move up tree to register if the menu is active
	if (this.parent)
		this.parent.setActive(this.active);

	if (this.active)
	{
		alib.events.triggerEvent(this, "isActive");
	}
	else
		alib.events.triggerEvent(this, "isInactive");
}

/**
 * Set the parent popup
 *
 * @param {alib.ui.Popup} pop
 */
alib.ui.Popup.prototype.setParentPopup = function(pop)
{
	this.parent = pop;
	this.parent.children.push(this);
}

/**
 * Setup to close when document is clicked
 */
alib.ui.Popup.prototype.registerPopup = function(visible)
{
	if (!this.autoClose)
		return; // Do nothing

	if (alib.ui.popups == null)
	{
		// Initialize
		alib.ui.popups = new Array();

		alib.events.listen(document.body, "mousedown", function(evt) {
			for (var i in alib.ui.popups)
			{
				if (!alib.ui.popups[i].active)
					alib.ui.popups[i].setVisible(false);
			}
		}, {popup:this});
	}

	// Only root level popups should be registered in the global array
	if (!this.parent)
		alib.ui.popups.push(this);

	// Set flag for just in time processing
	this.isRegistered = true;
}

/**
 * Set position of top left
 *
 * @param {int} top The top position in px
 * @param {int} left The left position in px
 */
alib.ui.Popup.prototype.setPosition = function(t, l)
{
	this.position.top = t;
	this.position.left = l;
}

/**
 * Set auto close flag
 *
 * @param {bool} autoClose If true close this menu when the document is clicked
 */
alib.ui.Popup.prototype.setAutoClose = function(autoClose)
{
	this.autoClose = autoClose;
}

/**
 * Set position of top left
 *
 * @param {DOMElement} el The element to anchor position to
 * @param {string} vRel Relative vertical direction - "up"|"down"|"side"
 * @param {string} hRel Relative horizontal pos - "left"|"right"|"center"
 */
alib.ui.Popup.prototype.anchorToEl = function(el, vRel, hRel)
{
	if (!el)
		throw new Exception("Element is a required param");

	this.anchorEl = el;

	if (vRel)
		this.position.relVert = vRel;

	if (hRel)
		this.position.relHoriz = hRel;

	// Add blur events for registering if the element is active
	alib.events.listen(el, "mouseover", function(evt) {
		evt.data.popupLnk.setActive(true);
	}, {popupLnk:this});

	alib.events.listen(el, "mouseout", function(evt) {
		evt.data.popupLnk.setActive(false);
	}, {popupLnk:this});

	alib.events.listen(el, "blur", function(evt) {
		evt.data.popupLnk.setActive(false);
	}, {popupLnk:this});
}

/**
 * Reposition the element based on position settings
 */
alib.ui.Popup.prototype.reposition = function()
{
	var t = this.position.top;
	var l = this.position.left;

	if (this.anchorEl)
	{
		var coords = this.calcAnchordElementPos();
		t = coords.top;
		l = coords.left;
	}

	// Check for bounds to make sure we have not gone out of the viewport
	var docWidth = alib.dom.getClientWidth() + alib.dom.getScrollPosLeft(document);
	var docHeight = alib.dom.getClientHeight() + alib.dom.getScrollPosTop(document);

	var relVert = (this.position.relVert) ? this.position.relVert : "down";
	var relHoriz = (this.position.relHoriz) ? this.position.relHoriz : "";

	// Make sure popup is not off the top of the viewport
	if (t < 0)
		t = 0;

	// Make sure that the popup is not below the viewport
	if ((t + alib.dom.getElementHeight(this.con)) >= docHeight)
		t = docHeight - alib.dom.getElementHeight(this.con);
	
	// Handle horizontal issues
	switch (relVert)
	{
	case 'up':
	case 'down':
		// Make sure that the popup is not off the right
		//console.log(l + "," + alib.dom.getElementWidth(this.con) + "," + docWidth);
		if ((l + alib.dom.getElementWidth(this.con)) > docWidth)
			l = docWidth - alib.dom.getElementWidth(this.con);
		// Make sure that the popup is not off the left
		if  (l < 0)
			l = 0;
		break;

	// Position on either side of the element
	case 'side':
		// TODO: handle changing orientation
		/*
		switch (relHoriz)
		{
		case 'left':
			if ((l + alib.dom.getElementWidth(this.con)) > docWidth)
			l = pos.x - popWidth;
			break;
		case 'right':
			l = pos.r;
			break;
		}
		*/
		break;
	}

	// Set position
	alib.dom.styleSet(this.con, "position", "absolute");
	alib.dom.styleSet(this.con, "top", t + "px");
	alib.dom.styleSet(this.con, "left", l + "px");
}

/**
 * Calcuate the position based on the anchored element settings
 *
 * @return {Object} {top:px, left:px}
 */
alib.ui.Popup.prototype.calcAnchordElementPos = function(relVert, relHoriz)
{
	var t = 0;
	var l = 0;

	// Get x, y, r, b positions of the element
	var pos = alib.dom.getElementPosition(this.anchorEl);
	var relVert = (relVert) ? relVert : this.position.relVert;
	var relHoriz = (relHoriz) ? relHoriz : this.position.relHoriz;

	switch (relVert)
	{
	// Position above the anchored element
	case 'up':
		var popHeight = alib.dom.getElementHeight(this.con);
		t = pos.y - popHeight;

		switch (relHoriz)
		{
		case 'left':
			l = pos.x;
			break;
		case 'right':
			l = pos.x + pos.r;
			break;
		case 'center':
			l = (pos.x + pos.r) / 2;
			break;
		}

		break;

	// Position below the anchored element
	case 'down':
		t = pos.b;

		switch (relHoriz)
		{
		case 'left':
			l = pos.x;
			break;
		case 'right':
			l = pos.x + pos.r;
			break;
		case 'center':
			l = (pos.x + pos.r) / 2;
			break;
		}
		break;

	// Position on either side of the element
	case 'side':
		t = pos.y;
		var popWidth = alib.dom.getElementWidth(this.con, false);

		switch (relHoriz)
		{
		case 'left':
			l = pos.x - popWidth;
			break;
		case 'right':
			l = pos.r;
			break;
		}
		break;
	}

	return {top:t, left:l};
}

/**
 * Returns whether the popup is currently visible or was visible within about
 * 150 ms ago. This is used by clients to handle a very specific, but common,
 * popup scenario. The button that launches the popup should close the popup
 * on mouse down if the popup is alrady open. The problem is that the popup
 * closes itself during the capture phase of the mouse down and thus the button
 * thinks it's hidden and this should show it again. This method provides a
 * good heuristic for clients. Typically in their event handler they will have
 * code that is:
 *
 * if (menu.isOrWasRecentlyVisible()) 
 * {
 *   menu.setVisible(false);
 * } 
 * else 
 * {
 *   ... // code to position menu and initialize other state
 *   menu.setVisible(true);
 * }
 *
 * @return {boolean} Whether the popup is currently visible or was visible 150ms ago
 */
alib.ui.Popup.prototype.isOrWasRecentlyVisible = function() 
{
	var now = new Date();
	return this.visible || (now.getTime() - this.lastHideTime < 150);
};

/**
 * Determine if this popup is recently visible
 *
 * @return {boolean} Whether the popup is currently visible or was visible 150ms ago
 */
alib.ui.Popup.prototype.isVisible = function() 
{
	var now = new Date();
	return this.visible;
};
