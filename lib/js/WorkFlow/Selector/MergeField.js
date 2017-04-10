/**
* @fileOverview Merge field selector
*
* This will take a handle to an object and print a friendly drop-down to add values into a text box or textarea as merge fields
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of WorkFlow_Selector_MergeField
 *
 * @constructor
 * @param {CAntObject} obj Reference to object type to pull fields from
 */
function WorkFlow_Selector_MergeField(obj)
{
	/**
	 * Array of objects that can be used to pull fields from
	 *
	 * @private
	 * @type {[CAntObject]}
	 */
    this.objects = [obj];	

	/**
	 * The input where we will be putting the merge field value
	 *
	 * @private
	 * @type {[CDialog]}
	 */
    this.input = null;	
}


/**
 * Print the grid inside a dom element/container
 *
 * @public
 * @return {DOMElement} con The container that will be used to house the grid
 */
WorkFlow_Selector_MergeField.prototype.attach = function(lnkCon, inpt)
{
	var menuAct = new alib.ui.PopupMenu();
	this.input = inpt;

	// Add object link
	var item = new alib.ui.MenuItem("Link to " + this.objects[0].title, {cls:this});
	item.onclick = function() { this.options.cls.insertText("<%object_link%>"); };
	menuAct.addItem(item);

	for (var j = 0; j < this.objects.length; j++)
	{
		for (var i = 0; i < this.objects[j].getNumFields(); i++)
		{
			var field = this.objects[j].getField(i);

			if (field.type != "object_multi" && field.type != "fkey_multi")
			{
				if (field.type == "object" && field.subtype)
				{
					var submenu = new alib.ui.SubMenu(this.objects[j].title + "." + field.title);
					this.addSubObjects(submenu, field);
					menuAct.addItem(submenu);
				}
				else
				{
					var item = new alib.ui.MenuItem(this.objects[j].title + "." + field.title, {mergeVal:"<%" + field.name + "%>", cls:this});
					item.onclick = function() { this.options.cls.insertText(this.options.mergeVal); };
					menuAct.addItem(item);
				}
			}
		}
	}

	menuAct.attach(lnkCon);
}


/**
 * Print the grid inside a dom element/container
 *
 * @public
 * @return {DOMElement} con The container that will be used to house the grid
 */
WorkFlow_Selector_MergeField.prototype.addSubObjects = function(subMenu, pField)
{
	var obj = new CAntObject(pField.subtype);

	for (var i = 0; i < obj.getNumFields(); i++)
	{
		var field = obj.getField(i)

		if (field.type != "object_multi" && field.type != "fkey_multi")
		{
			var item = new alib.ui.MenuItem(field.title, {mergeVal:"<%" + pField.name + "." + field.name + "%>", cls:this});
			item.onclick = function() { this.options.cls.insertText(this.options.mergeVal); };
			subMenu.addItem(item);
		}
	}
}

/**
 * Print the grid inside a dom element/container
 *
 * @public
 * @return {DOMElement} con The container that will be used to house the grid
 */
WorkFlow_Selector_MergeField.prototype.insertText = function(text)
{
	this.input.value = this.input.value + text;
}
