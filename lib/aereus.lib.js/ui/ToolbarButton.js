/**
* @fileOverview alib.ui.ToolbarButton class
*
* This is used to create a new button using a div with the toolbar styles
*
* Exampl:
* <code>
* 	var button = alib.ui.ToolbarButton("Button Content", {className:"blue", con:document.getElementById("toolbardiv"), tooltip:"My Text"});
* </code>
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Alib_Ui_Button
 *
 * @constructor
 */
function Alib_Ui_ToolbarButton(content, options)
{
	var options = options || new Object();

	// Set default class
	if (!options.className)
		options.className = "alibToolbarButton";

	var btn = new Alib_Ui_Button(content, options, "div");
	return btn;
}
