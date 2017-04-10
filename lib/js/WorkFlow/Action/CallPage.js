/**
* @fileOverview The callpage action is used to request and opens a webpage url
*
* @author:    joe, sky.stebnicki@aereus.com; 
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of CallPage Action
 *
 * @constructor
 * @param {string} obj_type The type of object that is being approved with this action
 * @param {CDialog} dlg Optional reference to the dialog being used to edit this action
 */
function WorkFlow_Action_CallPage(obj_type, dlg)
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
 * @this {WorkFlow_Action_CallPage}
 * @param {DOMElement} con The container where we can print the form
 * @param {WorkFlow_Action} action The parent action
 */
WorkFlow_Action_CallPage.prototype.print = function(con, action)
{
    var innerCon = alib.dom.createElement("fieldset", con);
    alib.dom.styleSet(innerCon, "margin", "6px 0px 3px 3px");
    var lbl = alib.dom.createElement("legend", innerCon);
    lbl.innerHTML = "Call Page";
    
    var tableForm = alib.dom.createElement("table", innerCon);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    var urlForm = new Object();
    urlForm.url = alib.dom.setElementAttr(alib.dom.createElement("input"), [["type", "text"], ["value", action.getObjectValue("url")], ["label", "Url:"], ["width", "500px"]]);
    buildFormInput(urlForm, tBody);
    
    urlForm.url.act = action;
    urlForm.url.onchange = function() 
    {
        this.act.setObjectValue("url", this.value); 
    };
}
