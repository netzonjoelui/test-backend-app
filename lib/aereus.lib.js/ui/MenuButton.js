/**
 * @fileOverview Menu item class used to create a submenu item
 *
 * This class is a work in progress
 *
 * Example of how it is used:
 * <code>
 * </code>
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Create a new alib.ui.Button that is attached to a menu
 *
 * @constructor
 * @param {string} content The HTML content of this button
 * @param {alib.ui.PopupMenu} menu A popup menu to display when the button is clicked
 * @param {Object} options Generic options to pass to button
 */
alib.ui.MenuButton = function(content, menu, options)
{
	// Add dropdown image span if set in 
	if (typeof content == "string")
	{
		content += "<span class='alibMenuButtonIcon'>&#9660;</span>";
	}
	else
	{
		var dmIcon = alib.dom.createElement("span", content, "&#9660;");
		alib.dom.styleSetClass("alibMenuButtonIcon");
	}

	var btn = new alib.ui.Button(content, options);

	// listen for onclick to show the menu
	menu.attach(btn.getButton());
	
	// listen for menu show to depress the button
	alib.events.listen(menu, "onShow", function(evt) {
		btn.toggle(true);
	});
	
	// listen for menu hide to return button to unpressed state
	alib.events.listen(menu, "onHide", function(evt) {
		btn.toggle(false);
	});

	return btn;
}
