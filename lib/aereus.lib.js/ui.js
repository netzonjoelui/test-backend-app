/**
* @fileOverview Mail loader class for ui portion of framework
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
 * Ui namespace
 */
alib.ui = {}

/**
* Create a new instance of Alib_Ui_Button
*
* @private
* @this {alib.ui}
* @param {string|DOMElement} content Either the text of data to place in button, or a dom element for placement in button tag
* @param {Object} options Optional options to use when rendering the button
*/
alib.ui.Button = function(content, options, type)
{   
	if (typeof options == "undefined")
		var options = new Object();

	var button = new Alib_Ui_Button(content, options, type);
	return button;
}

/**
* Create a new instance of Alib_Ui_ButtonToggler
*
* @private
* @this {alib.ui}
* @param {Object} options Optional options to use when rendering the button toggler
*/
alib.ui.ButtonToggler = function(options)
{   
	if (typeof options == "undefined")
		var options = new Object();

	return new Alib_Ui_ButtonToggler(options);
}

/**
* Create a new instance of Alib_Ui_Autocomplete
*
* @private
* @this {alib.ui}
* @param {string|DOMElement} content Either the text of data to place in button, or a dom element for placement in button tag
* @param {Object} options Optional options to use when rendering the button
*/
alib.ui.AutoComplete = function(el, options)
{
	var ac = new Alib_Ui_AutoComplete(el, options);
	return ac;
}

/**
* Create a new instance of Alib_Ui_Tooltip
*
* @private
* @this {alib.ui}
* @param {string|DOMElement} content Either the text of data to place in button, or a dom element for placement in button tag
* @param {Object} the message to be displayed in the toolbar container
*/
alib.ui.Editor = function(el, options)
{
    var ed = new Alib_Ui_Editor(el, options);
    return ed;
}

/**
* Create a new instance of Alib_Ui_SlimScroll
*
* @private
* @this {alib.ui}
* @param {string|DOMElement} content Either the text of data to place in button, or a dom element for placement in button tag
* @param {Object} the message to be when setting the slimscroll
*/
alib.ui.slimScroll = function(el, options)
{
    var ed = new Alib_Ui_SlimScroll(el, options);
    return ed;
}

/**
* Create a new instance of Alib_Ui_Toolbar
*
* @private
* @this {alib.ui}
* @param {Object} the message to be displayed in the toolbar container
*/
alib.ui.Toolbar = function(options)
{
	var options = options || new Object();
    var tb = new Alib_Ui_Toolbar(options);
    return tb;
}

/**
* Create a new instance of Alib_Ui_ToolbarButton
*
* @private
* @this {alib.ui}
* @param {Object} the message to be displayed in the toolbar container
*/
alib.ui.ToolbarButton = function(caption, options)
{
	var options = options || new Object();
    var tb = new Alib_Ui_ToolbarButton(caption, options);
    return tb;
}

/**
* Create a new instance of Alib_Ui_ToolbarButton
*
* @private
* @this {alib.ui}
* @param {Object} the message to be displayed in the toolbar container
*/
alib.ui.ToolbarToggleButton = function(caption, options)
{
	var options = options || new Object();
    var tb = new Alib_Ui_ToolbarToggleButton(caption, options);
    return tb;
}

/**
* Create a new instance of Alib_Ui_ToolbarButton
*
* @private
* @this {alib.ui}
* @param {Object} the message to be displayed in the toolbar container
*/
alib.ui.ToolbarSeparator = function(options)
{
	var options = options || new Object();
    var tb = new Alib_Ui_ToolbarSeparator(options);
    return tb;
}
