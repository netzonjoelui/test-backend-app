/**
 * @fileOverview Menu class used to build popup menus
 *
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance a filtered ui popup window
 *
 * @constructor
 */
alib.ui.FilteredMenu = function(options)
{
	var popupMenu = new alib.ui.PopupMenu(options);
	popupMenu.setFiltered(true);
	return popupMenu;
}
