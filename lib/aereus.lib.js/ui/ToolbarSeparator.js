/**
* @fileOverview alib.ui.Alib_Ui_ToolbarSeparator class
*
* This is used to create a new toolbar spacer element
* Exampl:
* <code>
* 	var button = alib.ui.ToolbarSpacer();
* </code>
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Alib_Ui_ToolbarSeparator
 *
 * @constructor
 */
function Alib_Ui_ToolbarSeparator(options)
{
}

/**
 * Render the dom into the document
 */
Alib_Ui_ToolbarSeparator.prototype.print = function(con)
{
	var sep = alib.dom.createElement("span", con);
	alib.dom.styleSetClass(sep, "alibToolbarSeparator");
}
