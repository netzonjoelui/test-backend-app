/**
* @fileOverview alib.ui.button class
*
* This is used to toggle buttons like a radio group
*
* Example with radio:
* <code>
* 	var button1 = alib.ui.Button("Button Content", {className:"blue", con:document.getElementById("toolbardiv"), tooltip:"My Text"});
* 	var button2 = alib.ui.Button("Button Content", {className:"blue", con:document.getElementById("toolbardiv"), tooltip:"My Text"});
* 	var button3 = alib.ui.Button("Button Content", {className:"blue", con:document.getElementById("toolbardiv"), tooltip:"My Text"});
*
* 	var toggler = new alib.ui.ButtonToggler();
* 	toggler.add(button1, "b1name");
* 	toggler.add(button2, "b2name");
* 	toggler.add(button3, "b3name");
* </code>
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Alib_Ui_ButtonToggler
 *
 * @constructor
 * @param {Object} options
 */
function Alib_Ui_ButtonToggler(options)
{
	/**
	 * Buttons array
	 *
	 * @type {Alib_Ui_Button[]}
	 */
	this.buttons = new Array();
}

/**
 * Add a button to the toggler list
 *
 * @param {Alib_Ui_Button} button The button to add
 * @param {string} name An optional unique name to use
 */
Alib_Ui_ButtonToggler.prototype.add = function(button, name)
{
	var name = name || this.buttons.length;

	button.addEvent("click", function(opt){
		opt.cls.select(opt.name);
	}, { cls:this, name:name });

	this.buttons[name] = button;
}

/**
 * Select a button in the array of buttons and unselect all others
 *
 * @param {Alib_Ui_Button} button The button to add
 * @param {string} name An optional unique name to use
 */
Alib_Ui_ButtonToggler.prototype.select = function(name)
{
	// If we are working with a single button, then just toggle
	if (this.buttons.length == 1)
	{
		this.buttons[name].changeStat();
		///this.toggle(name);
		return;
	}

	// Else unselect all others and select current
	for (var i in this.buttons)
		this.buttons[i].toggle((i == name) ? true : false);
}

/**
 * Toggle a button
 *
 * @param {Alib_Ui_Button} button The button to add
 * @param {string} name An optional unique name to use
 */
Alib_Ui_ButtonToggler.prototype.toggle = function(name)
{
	for (var i in this.buttons)
	{
		if (i == name)
			this.buttons[i].toggle();
	}
}

/**
 * Disable all buttons
 */
Alib_Ui_ButtonToggler.prototype.disable = function()
{
	for (var i in this.buttons)
		this.buttons[i].disable();
}

/**
 * Enable all buttons
 */
Alib_Ui_ButtonToggler.prototype.enable = function()
{
	for (var i in this.buttons)
		this.buttons[i].enable();
}
