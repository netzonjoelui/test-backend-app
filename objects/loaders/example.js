/**
 * @fileoverview This is an example of a sub-loader for AntObjectLoader
 *
 * @author	joe, sky.stebnicki@aereus.com
 * 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObjectLoader_Example.
 *
 * @constructor
 * @param {CAntObject} obj Handle to object that is being viewed or edited
 * @param {AntObjectLoader} loader Handle to base loader class
 */
function AntObjectLoader_Example(obj, loader)
{
}

/**
 * Refresh the form
 */
AntObjectLoader_Example.prototype.refresh = function()
{
	this.toggleEdit(this.editMode);
}

/**
 * Enable to disable edit mode for this loader
 *
 * @param {bool} setmode True for edit mode, false for read mode
 */
AntObjectLoader_Example.prototype.toggleEdit = function(setmode)
{
}

/**
 * Print form on 'con'
 *
 * @param {DOMElement} con A dom container where the form will be printed
 * @param {array} plugis List of plugins that have been loaded for this form
 */
AntObjectLoader_Example.prototype.print = function(con, plugins)
{
	if (plugins)
		this.plugins = plugins;

	/*
	 * The parent class contains the following containers for printing the loader:
	 *
	 * this.loaderCls.titleCon = the container used to print the title
	 * this.loaderCls.toolbarCon = the container that will hold the toolbar if any
	 * this.loaderCls.noticeCon = a notice container for inline notices (like duplicate detection
	 * this.loaderCls.formCon = the container where the form should be printed)
	 */

}

/**
 * Callback is fired any time a value changes for the mainObject 
 */
AntObjectLoader_Example.prototype.onValueChange = function(name, value, valueName)
{	
}

/**
 * Callback function used to notify the parent loader if the name of this object has changed
 */
AntObjectLoader_Example.prototype.onNameChange = function(name)
{
}
