/**
 * @fileOverview The check condition action is used to check if the workflow should be executed
 *
 * @author:	Marl Tumulak, marl.tumulak@aereus.com;
 * 			Copyright (c) 2016 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of Check Condition Action
 *
 * @constructor
 * @param {string} obj_type The type of object that is being approved with this action
 * @param {CDialog} dlg Optional reference to the dialog being used to edit this action
 */
function WorkFlow_Action_CheckCondition(obj_type, dlg)
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
 * @this {WorkFlow_Action_CheckCondition}
 * @param {DOMElement} con The container where we can print the form
 * @param {WorkFlow_Action} action The parent action
 */
WorkFlow_Action_CheckCondition.prototype.print = function(con, action)
{
    var innerCon = alib.dom.createElement("fieldset", con);
    alib.dom.styleSet(innerCon, "margin", "6px 0px 3px 3px");
    var lbl = alib.dom.createElement("legend", innerCon);
    lbl.innerHTML = "Check Condition";
    var tbl = alib.dom.createElement("table", innerCon);
    var tbody = alib.dom.createElement("tbody", tbl);

    var row = alib.dom.createElement("tr", tbody);
    var lbl = alib.dom.createElement("td", row);
    lbl.colSpan = 2;
    alib.dom.styleSetClass(lbl, "strong");
    lbl.innerHTML = "Only execute action if the following conditions are met:";
    var row = alib.dom.createElement("tr", tbody);
    var condCon = alib.dom.createElement("td", row);
    condCon.colSpan = 2;
    var dv_cnd = alib.dom.createElement("div", condCon);
    var tmpAntObj = new CAntObject(action.workflow.object_type);
    action.antConditionsObj = tmpAntObj.buildAdvancedQuery(dv_cnd, action.conditions);
}
