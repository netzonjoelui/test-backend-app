/**
* @fileOverview The approval action is used to request and handle approvals on any object type
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Approval Action
 *
 * @constructor
 * @param {string} obj_type The type of object that is being approved with this action
 * @param {CDialog} dlg Optional reference to the dialog being used to edit this action
 */
function WorkFlow_Action_Approval(obj_type, dlg)
{
	/**
	* The object that is the subject of this approval request
	*
	* @private
	* @type {CAntObject}
	*/
	this.mainObject = new CAntObject(obj_type);

	/**
	* Optional dialog reference
	*
	* @private
	* @type {[CDialog]}
	*/
    this.dialog = (dlg) ? dlg : null;	
}

/**
 * Print form
 *
 * @public
 * @this {WorkFlow_Action_Approval}
 * @param {DOMElement} con The container where we can print the form
 * @param {WorkFlow_Action} action The parent action
 */
WorkFlow_Action_Approval.prototype.print = function(con, action)
{
	var innerCon = alib.dom.createElement("fieldset", con);
	alib.dom.styleSet(innerCon, "margin", "6px 0px 3px 3px");
	var lbl = alib.dom.createElement("legend", innerCon);
	lbl.innerHTML = "Request Approval";
	var tbl = alib.dom.createElement("table", innerCon);
	var tbody = alib.dom.createElement("tbody", tbl);

	// Create user input
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	td.innerHTML = "Request Approval From: ";
	var td = alib.dom.createElement("td", row);
	var inpt = alib.dom.createElement("input", td);
	inpt.act = action;
	inpt.value = action.getObjectValue("owner_id");
	inpt.onchange = function()
	{
		this.act.setObjectValue("owner_id", this.value);
	}
	var td = alib.dom.createElement("td", row);
	var selCon = alib.dom.createElement("span", td);
	var selector = new WorkFlow_Selector_User(this.mainObject);
	selector.setParentDialog(this.dialog);
	selector.print(selCon, inpt);
}
