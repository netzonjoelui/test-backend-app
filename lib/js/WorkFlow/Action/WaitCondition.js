/**
 * @fileOverview The wait condition action is used to check when to execute the action
 *
 * @author:	Marl Tumulak, marl.tumulak@aereus.com;
 * 			Copyright (c) 2016 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of Wait Condition Action
 *
 * @constructor
 * @param {string} obj_type The type of object that is being approved with this action
 * @param {CDialog} dlg Optional reference to the dialog being used to edit this action
 */
function WorkFlow_Action_WaitCondition(obj_type, dlg)
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
 * @this {WorkFlow_Action_WaitCondition}
 * @param {DOMElement} con The container where we can print the form
 * @param {WorkFlow_Action} action The parent action
 */
WorkFlow_Action_WaitCondition.prototype.print = function(con, action)
{
    var innerCon = alib.dom.createElement("fieldset", con);
    alib.dom.styleSet(innerCon, "margin", "6px 0px 3px 3px");
    var lbl = alib.dom.createElement("legend", innerCon);
    lbl.innerHTML = "Wait Condition";
    var tbl = alib.dom.createElement("table", innerCon);
    var tbody = alib.dom.createElement("tbody", tbl);

    var row = alib.dom.createElement("tr", tbody);
    var td = alib.dom.createElement("td", row);
    alib.dom.styleSetClass(td, "strong");
    td.innerHTML = "When";
    var td = alib.dom.createElement("td", row);

    var txtWhenInterval = alib.dom.createElement("input", td);
    alib.dom.styleSet(txtWhenInterval, "width", "28px");
    txtWhenInterval.act = action;
    txtWhenInterval.value = action.when.interval;
    txtWhenInterval.onchange = function() { this.act.when.interval = this.value; };

    var lbl = alib.dom.createElement("span", td);
    lbl.innerHTML = "&nbsp;";

    var cbWhenUnit = alib.dom.createElement("select", td);
    var time_units = wfGetTimeUnits();
    for (var i = 0; i < time_units.length; i++)
    {
        cbWhenUnit[cbWhenUnit.length] = new Option(time_units[i][1], time_units[i][0], false, (action.when.unit==time_units[i][0])?true:false);
    }
    cbWhenUnit.act = action;
    cbWhenUnit.onchange = function() { this.act.when.unit = this.value; };

    var lbl = alib.dom.createElement("span", td);
    lbl.innerHTML = " after workflow starts (enter 0 for immediate)";
}
