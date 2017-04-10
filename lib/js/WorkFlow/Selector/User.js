/**
* @fileOverview User selector
*
* This selector is used by workflow action dialogs to get available 
* options for selecting a user as a variable.
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of WorkFlow_Selector_User
 *
 * @constructor
 * @this {WorkFlow_Selector_User}
 * @param {CAntObject} obj Reference to object type to pull fields from
 */
function WorkFlow_Selector_User(obj)
{
	/**
	* Array of objects that can be used to pull fields from
	*
	* @private
	* @type {[CAntObject]}
	*/
    this.objects = [obj];	

	/**
	* Optional parent dialog
	*
	* @private
	* @type {[CDialog]}
	*/
    this.parentDialog = null;	
}


/**
 * Print the grid inside a dom element/container
 *
 * @public
 * @this {WorkFlow_Selector_User}
 * @return {DOMElement} con The container that will be used to house the grid
 */
WorkFlow_Selector_User.prototype.print = function(con, inpt)
{
    var sel = alib.dom.createElement("select", con);
	sel.inpt = inpt;
    sel[sel.length] = new Option("Click to select user", "", false, true);

	for (var j = 0; j < this.objects.length; j++)
	{
		for (var i = 0; i < this.objects[j].getNumFields(); i++)
		{
			var field = this.objects[j].getField(i);

			if (field.type == "object" && field.subtype == "user")
			{
    			sel[sel.length] = new Option(this.objects[j].title + "." + field.title,"<%"+field.name+"%>");

				// Add manager
    			sel[sel.length] = new Option(this.objects[j].title + "." + field.title + ".Manager","<%"+field.name+".manager_id%>");
			}
		}
	}

    sel[sel.length] = new Option("Select Specific User", "browse");
	sel.parentDialog = this.parentDialog;
	sel.onchange = function()
	{
		if (this.value == "browse")
		{
			var ob = new AntObjectBrowser("user");
			ob.cbData.inpt = inpt;
			ob.onSelect = function(oid, name) 
			{
				this.cbData.inpt.value = oid;

				try
				{
					if (this.cbData.inpt.onchange)
						this.cbData.inpt.onchange();
				}
				catch (e) {}
			}
			ob.displaySelect(this.parentDialog);
		}
		else if (this.value)
		{
			this.inpt.value = this.value;

			try
			{
				if (this.inpt.onchange)
					this.inpt.onchange();
			}
			catch (e) {}
		}

		// Reset selection to the beginning
		this.selectedIndex = 0;
	}
}

/**
 * Set parent dialog for modal display
 *
 * @public
 * @this {WorkFlow_Selector_User}
 * @param {CDialog} dlg The parent dialog
 */
WorkFlow_Selector_User.prototype.setParentDialog = function(dlg)
{
	this.parentDialog = dlg;
}
