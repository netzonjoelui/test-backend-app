/**
 * @fileOverview HelpTour is used to give people an inline tour
 *
 * To use just add "data-tour='tour/item/id'" to any rendered element
 *
 * <example>
 * 	Ant.HelpTour(document.body);
 * </example>
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Create namespace
 */
Ant.HelpTour = {}

/**
 * Tour items queue
 *
 * We only display one item at a time through this queue
 *
 * @private
 * @type {Array}
 */
Ant.HelpTour.itemQueue_ = new Array();

/**
 * Keep track of which tours have alraedy been displayed so we don't load them twice
 *
 * @private
 * @type {Array}
 */
Ant.HelpTour.itemsDisplayed_ = new Array();

/**
 * Load a tour for any matching objects that are children of 'el'
 *
 * @public
 * @param {DOMElement} el The parent element to check (including all children elements) for data-tour attributes
 * @param {string} namespace If set, only load tours that begin with namespace
 */
Ant.HelpTour.loadTours = function(el, namespace)
{
	var tours = new Array();
	var namespace = namespace || false;

	alib.dom.query('[data-tour]', el).each(function() {

		var tourId = this.getAttribute("data-tour");

		if (namespace)
		{
			// skip if namespace does not match
			if (tourId.substring(0, namespace.length) != namespace)
				return; 
		}

		if (!Ant.HelpTour.isInQueue(tourId))
			tours.push(tourId);
	});

	// If we have any tours queued up then load them
	if (tours.length > 0)
		this.getTourItemsData(tours);
}

/**
 * Load a tour for any matching objects that are children of 'el'
 *
 * @private
 * @param {string[]} tours Array of tours by id to get data for
 */
Ant.HelpTour.getTourItemsData = function(tours)
{
	var xhr = new alib.net.Xhr();

	// Setup callback
	alib.events.listen(xhr, "load", function(evt) { 
		var tourItems = this.getResponse();

		// Add to the display queue
		for (var i in tourItems)
			Ant.HelpTour.itemQueue_.push(tourItems[i]);

		Ant.HelpTour.displayQueue();
	});

	// Timed out, try again in a few sconds
	alib.events.listen(xhr, "error", function(evt) { 
	}, {helpClass:this});


	var ret = xhr.send("/controller/Help/getTourItems", "POST", {tourIds:tours});
}

/**
 * Check if an item is in the queue already
 *
 * @private
 * @param {string} tourId The unique id of the tour itme
 * @return {bool} If item is already in queue in return true
 */
Ant.HelpTour.isInQueue = function(tourId)
{
	for (var i in this.itemQueue_)
	{
		if (this.itemQueue_[i].id == tourId)
			return true;
	}

	return false;
}

/**
 * Check if an item has already been displayed by id
 *
 * @private
 * @param {string} tourId The unique id of the tour itme
 * @return {bool} If item is already in queue in return true
 */
Ant.HelpTour.wasDisplayed = function(tourId)
{
	for (var i in this.itemsDisplayed_)
	{
		if (this.itemsDisplayed_[i] == tourId)
			return true;
	}

	return false;
}

/**
 * Go through queue item by item and display
 *
 * @public
 */
Ant.HelpTour.displayQueue = function()
{
	var item = this.itemQueue_.shift();

	if (item)
	{
		var displayed = false;
		
		if (!Ant.HelpTour.wasDisplayed(item.id))
			displayed = this.display(item);

		// If there was a problem with this item, move to the next
		if (!displayed)
			this.displayQueue();
		else
			this.itemsDisplayed_.push(item.id);
	}
}

/**
 * Display a tour item from the queue
 *
 * @private
 * @param {Object} tourItem Object returned from server with id and html properties
 * @return {bool} true if displayed, false if there was a problem
 */
Ant.HelpTour.display = function(tourItem)
{
	// Get element to attach tour item to
	var ele = alib.dom.query("[data-tour='" + tourItem.id + "']");

	if (ele)
	{
		// Get first match from query results
		ele = ele[0];

		// Get the type of tour - popup (default), inline, dialog
		var type = (ele.getAttribute("data-tour-type")) ? ele.getAttribute("data-tour-type") : "popup";

		switch (type)
		{
		case "inline":
			this.displayInline(tourItem);
			break;
		case "dialog":
			this.displayDialog(tourItem);
			break;
		case "popup":
		default:
			this.displayPopup(tourItem);
			break;
		}

		return true;
	}
	else
	{
		// Tour element was not found
		return false;
	}
}

/**
 * Display a tour item as a popup
 *
 * @private
 * @param {Object} tourItem Object returned from server with id and html properties
 * @return {bool} true if displayed, false if there was a problem
 */
Ant.HelpTour.displayPopup = function(tourItem)
{
	var info = alib.dom.createElement("div", document.body, tourItem.html);
	alib.dom.styleSetClass(info, "helpTourCon");
	alib.dom.styleAddClass(info, "popup");

	var popup = new alib.ui.Popup(info, {autoClose:false});

	// Add the dismiss button
	var buttonCon = alib.dom.createElement("div", info);
	alib.dom.styleSetClass(buttonCon, "helpTourConButtons");

    var btn = alib.ui.Button("Okay, got it!",  {
		className:"b1", popup:popup, tourId:tourItem.id,
		onclick:function() 
		{
			this.popup.setVisible(false);
			Ant.HelpTour.dismiss(this.tourId);
		}
	});                            
	btn.print(buttonCon);

	// Get element to attach tour item to
	var ele = alib.dom.query("[data-tour='" + tourItem.id + "']");

	if (ele)
	{
		// Get first match from query results
		ele = ele[0];

		// Attach popup to element
		popup.anchorToEl(ele, "down");

		// Show popup
		popup.setVisible();

		return true;
	}
	else
	{
		// Tour element was not found
		return false;
	}
}

/**
 * Display a tour item as a popup
 *
 * @private
 * @param {Object} tourItem Object returned from server with id and html properties
 * @return {bool} true if displayed, false if there was a problem
 */
Ant.HelpTour.displayInline = function(tourItem)
{
	// Get element to print tour content into
	var ele = alib.dom.query("[data-tour='" + tourItem.id + "']");

	if (ele)
	{
		// Get first match from query results
		ele = ele[0];
		alib.dom.styleSet(ele, "display", "none");

		ele.innerHTML = tourItem.html;
		alib.fx.slideDown(ele);

		// Add the dismiss button
		var buttonCon = alib.dom.createElement("div", ele);

		var lnk = alib.dom.createElement("a", buttonCon, "hide");
		lnk.href = "javascript:void(0);";
		lnk.ele = ele;
		lnk.tourId = tourItem.id;
		lnk.onclick = function() {
			//alib.dom.styleSet(this.ele, "display", "none");
			alib.fx.slideUp(this.ele);
			Ant.HelpTour.dismiss(this.tourId);
		}

		return true;
	}
	else
	{
		// Tour element was not found
		return false;
	}
}

/**
 * Display a tour item as a dialog
 *
 * @private
 * @param {Object} tourItem Object returned from server with id and html properties
 * @return {bool} true if displayed, false if there was a problem
 */
Ant.HelpTour.displayDialog = function(tourItem)
{
	var dlg = new CDialog("");

	var con = alib.dom.createElement("div");
	alib.dom.styleSetClass(con, "helpTourCon");
	alib.dom.styleAddClass(con, "dialog");
	con.innerHTML = tourItem.html;

	// Add the dismiss button
	var buttonCon = alib.dom.createElement("div", con);
	alib.dom.styleSetClass(buttonCon, "helpTourConButtons");

	var chk = alib.dom.createElement("input");
	chk.type = "checkbox";
	chk.checked = true;

    var btn = alib.ui.Button("Okay, got it!",  {
		className:"b1", dlg:dlg, tourId:tourItem.id, noShowAgain:chk,
		onclick:function() 
		{
			this.dlg.hide();
			
			Ant.HelpTour.dismiss(this.tourId, this.noShowAgain.checked);
		}
	});                            
	btn.print(buttonCon);

	buttonCon.appendChild(chk);
	alib.dom.createElement("span", buttonCon, " Do not show me this again");

	dlg.customDialog(con, 500);

	// Get first match from query results
	return true;
}

/**
 * Dismiss a tour
 *
 * @public
 * @param {string} tourId The id of the tour to dismiss
 * @param {bool} noShowAgain Default to no longer showing this again, but leave option open
 */
Ant.HelpTour.dismiss = function(tourId, noShowAgain)
{
	// Go for the next item in the queue
	this.displayQueue();

	// Set items as seen so we don't show it again
	var nosh = (typeof noShowAgain != "undefined") ? noShowAgain : true;
	
	if (nosh)
	{
		var xhr = new alib.net.Xhr();
		var ret = xhr.send("/controller/Help/setTourItemDismissed", "POST", {tour_id:tourId});
	}
}
