/**
 * @fileoverview Infobox popup used to display mini-loader on object highlight
 */


/**
 * Constructor
 */
var AntObjectInfobox = {};

/**
 * Cache the last AntObjectInfobox.maxCached loaders for quicker load
 *
 * @private
 * @type {{objType, id, loader, divCon}[]}
 */
AntObjectInfobox.loaders = new Array();

/**
 * Variable to determine the maximum number of dialogs to cache
 *
 * @type {int}
 */
AntObjectInfobox.maxCached = 25;

/**
 * Width of the popup dialog in px
 *
 * @type {int}
 */
AntObjectInfobox.dialogWidth = 450;

/**
 * Show infobox
 *
 * @param {string} objType The unique name of the object type to load
 * @param {string} oid The unique id of the object to load
 * @param {DOMElement} e The element to attach to and display when over
 */
AntObjectInfobox.attach = function(objType, oid, e)
{
	// Add hover
	var data = {
		type:objType, 
		id:oid, 
		div:e
	};
	alib.events.listen(e, "mouseover", function(evnt) { 
			this.active = true;
			AntObjectInfobox.delayShow(evnt.data.type, evnt.data.id, evnt.data.div)
		}, data);

	// Register mouse out
	alib.events.listen(e, "mouseout", function(evnt) { this.active = false; });
}

/**
 * Delayed show to imporve user experience
 *
 * @param {string} objType The unique name of the object type to load
 * @param {string} oid The unique id of the object to load
 * @param {DOMElement} e The element to attach to and display when over
 */
AntObjectInfobox.delayShow = function(objType, oid, div)
{
	if (!div.showTimer)
	{
		div.showTimer = window.setTimeout(function() {
			div.showTimer = null;
			if (div.active)
				AntObjectInfobox.show(objType, oid, div);
		}, 500); // Wait .5 second
	}
}

/**
 * Show infobox
 *
 * @param {string} objType The unique name of the object type to load
 * @param {string} oid The unique id of the object to load
 * @param {DOMElement} e The element to attach to and display when over
 */
AntObjectInfobox.show = function(objType, oid, e)
{
	var pos = alib.dom.getElementPosition(e);

	var x = pos.x;
	var y = pos.b;

	// make sure we don't run off the right
	if ((x + 22 + this.dialogWidth) > alib.dom.getClientWidth())
		x = alib.dom.getClientWidth() - (this.dialogWidth + 25);

	var div = this.getBoxDiv(objType, oid);

	alib.dom.styleSet(div, "top", y + "px");
	alib.dom.styleSet(div, "left", x + "px");
	alib.dom.styleSet(div, "display", "block");

	if (!div.rendered)
		this.renderLoader(objType, oid, div);

	alib.events.listen(e, "mouseout", function(evnt) { 
			AntObjectInfobox.delayHide(evnt.data.objType, evnt.data.oid)
		}, { objType:objType, oid:oid });

	// Cancel hide if set started
	if (div.hidetimer) 
	{
		window.clearTimeout(div.hidetimer);
		div.hidetimer = null;
	}
}

/**
 * Delayed hide
 *
 * @param {string} objType The unique name of the object type to load
 * @param {string} oid The unique id of the object to load
 * @param {int} time Number of ms to delay the hide
 */
AntObjectInfobox.delayHide = function(objType, oid, time)
{
	var div = this.getBoxDiv(objType, oid);
	var time = time || 1000;

	if (!div.hidetimer)
	{
		div.hidetimer = window.setTimeout(function() {
			div.hidetimer = null;
			alib.dom.styleSet(div, "display", "none");
		}, time); // Wait one second
	}
}

/**
 * Stop delayed hide
 *
 * @param {string} objType The unique name of the object type to load
 * @param {string} oid The unique id of the object to load
 */
AntObjectInfobox.stopHide = function(objType, oid)
{
	var div = this.getBoxDiv(objType, oid);

	if (div.hidetimer)
	{
		window.clearTimeout(div.hidetimer);
		div.hidetimer=null;
	}
}

/**
 * Get infobox dom if already created
 *
 * @param {string} objType The unique name of the object type to load
 * @param {string} oid The unique id of the object to load
 * @return {DOMElement} The container
 */
AntObjectInfobox.getBoxDiv = function(objType, oid)
{
	// First check to see if this box is cached
	for (var i in this.loaders)
	{
		if (this.loaders[i].objType == objType && this.loaders[i].id == oid)
		{
			return this.loaders[i].divCon;
		}
	}
	
	var div = alib.dom.createElement("div", document.body);
	alib.dom.styleSet(div, "position", "absolute");
	alib.dom.styleSet(div, "top", "0");
	alib.dom.styleSet(div, "left", "0");
	alib.dom.styleSet(div, "width", this.dialogWidth + "px");
	alib.dom.styleSet(div, "display", "none");
	alib.dom.styleSetClass(div, "objectInfobox");
	div.rendered = false;

	this.loaders.push({
		objType : objType,
		id : oid,
		divCon : div,
		loader : null
	});

	alib.events.listen(div, "mouseover", function(evnt) { 
			AntObjectInfobox.stopHide(evnt.data.objType, evnt.data.oid); 
		}, { objType:objType, oid:oid });
	alib.events.listen(div, "mouseout", function(evnt) { 
			AntObjectInfobox.delayHide(evnt.data.objType, evnt.data.oid); 
		}, { objType:objType, oid:oid });

	// Maintain size to make sure we only this.maxCached loaded
	while (this.loaders.length > 25)
	{
		document.body.removeChild(this.loaders[0].divCon);
		this.loaders.splice(0, 1);
	}

	return div;
}

/**
 * Render loader into a box div
 *
 * @param {string} objType The unique name of the object type to load
 * @param {string} oid The unique id of the object to load
 * @param {DOMElement} div The container
 */
AntObjectInfobox.renderLoader = function(objType, oid, div)
{
	div.rendered = true;

	// Set the loader reference
	var ocon = alib.dom.createElement("div", div);
	var ol = new AntObjectLoader(objType, oid);
	ol.hideToolbar = true;
	ol.printInline(ocon, false, "infobox");

	// Add buttons
	var bcon = alib.dom.createElement("div", div);
	alib.dom.styleSet(bcon, "text-align", "right");

	var btn = alib.ui.Button("View More Details", {
		className:"b1 medium", tooltip:"Click to open full details", objType:objType, oid:oid,
		onclick:function() {
			loadObjectForm(this.objType, this.oid);
		}
	});
	btn.print(bcon);

	var btn = alib.ui.Button("Close", {
		className:"b1 medium", tooltip:"Dismiss this dialog", objType:objType, oid:oid,
		onclick:function() {
			AntObjectInfobox.delayHide(this.objType, this.oid, 1); // Close in 1 ms
		}
	});
	btn.print(bcon);
}
