/**
* @fileOverview alib.ui.toolbar class
*
* This is used to build dynamic toolbars
*
* Example:
* <code>
*     var button = alib.ui.Tooltip(element, "Test Tooltip");
* </code>
*
* @author:    joe, sky.stebnicki@aereus.com; 
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Alib_Ui_Toolbar
 *
 * @constructor
 * @param {string} element      Html element to be attached with tooltip
 * @param {string} message      Message of the tooltip
 */
function Alib_Ui_Toolbar()
{
	/**
	 * The main outer container
	 *
	 * @private
	 * @var {DIV}
	 */
	this.mainCon = null;

	/**
	 * Child entries/buttons
	 *
	 * @var {Object[]) Object with properties: id (optional), button
	 */
	this.children = new Array();
}

/**
 * Add an item to the toolbar
 *
 * @public
 * @param {Ui_Component} uiComponent The component (button) to add
 * @param {bool} enabled The default state of this child
 * @param {string} id An optional id for referencing the child by name
 */
Alib_Ui_Toolbar.prototype.addChild = function(uiComponent, enabled, id)
{
	var id = id || null;
	var enabled = enabled || true;

	this.children.push({button:uiComponent, enabled:enabled, id:id});
}

/**
 * Get a child by id name
 *
 * @public
 * @param {string} id The unique id of the child to get
 */
Alib_Ui_Toolbar.prototype.getChild = function(id)
{
	for (var i = 0; i < this.children.length; i++)
	{
		if (this.children[i].id == id)
			return this.children[i].button;
	}
}


/**
 * Get the total height of this toolbar
 *
 * @public
 * @return {int} The exact height of this toolbar in pixels
 */
Alib_Ui_Toolbar.prototype.getHeight = function()
{
}

/**
 * Print the toolbar to a container
 *
 * @public
 * @param {DOMElement|string} con The container to print this toolbar into
 */
Alib_Ui_Toolbar.prototype.print = function(con)
{
	this.mainCon = alib.dom.createElement("div", con);
	this.mainCon.innerHTML = "";

	alib.dom.styleSetClass(this.mainCon, "alibToolbar");

	for (var i = 0; i < this.children.length; i++)
	{
		this.children[i].button.print(this.mainCon);
	}
}
