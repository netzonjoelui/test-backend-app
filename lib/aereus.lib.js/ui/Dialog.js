/*
* @fileOverview alib.ui.Dialog
*
* Create modual dialog windows
*
* Exampl:
* <code>
* 	var dlg = alib.ui.
* </code>
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011-2013 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of alib.Ui.Dialog
 *
 * @constructor
 * @param {Object} Dialog Options
 */
alib.ui.Dialog = function(opts)
{
	if (opts.title)
		this.title = opts.title;
}

/**
 * Whether the dialog is modal. Defaults to true.
 *
 * @type {boolean}
 * @private
 */
alib.ui.Dialog.prototype.modal = true;


/**
 * Whether the dialog is draggable. Defaults to true.
 *
 * @type {boolean}
 * @private
 */
alib.ui.Dialog.prototype.draggable = true;


/**
 * Opacity for background mask.  Defaults to 50%.
 *
 * @type {number}
 * @private
 */
alib.ui.Dialog.prototype.backgroundElementOpacity = 0.50;

/**
 * Dialog's title.
 *
 * @type {string}
 * @private
 */
alib.ui.Dialog.prototype.title = '';

/**
 * Show the menu
 */
alib.ui.Dialog.prototype.show = function()
{
	alib.ui.dialogManager.showDialog(this);
}

/**
 * Show the menu
 */
alib.ui.Dialog.prototype.hide = function()
{
	alib.ui.dialogManager.showDialog(this);
}
