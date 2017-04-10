/**
* @fileOverview The child action is used to request and handle childs on any object type
*
* @author:    joe, sky.stebnicki@aereus.com; 
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Child Action
 *
 * @constructor
 * @param {string} objType The type of object that is being approved with this action
 * @param {CDialog} dlg Optional reference to the dialog being used to edit this action
 */
function WorkFlow_Action_Child(objType, dlg)
{
    /**
    * The object that is the subject of this child request
    *
    * @private
    * @type {CAntObject}
    */
    this.mainObject = new CAntObject(objType);

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
 * @this {WorkFlow_Action_Child}
 * @param {DOMElement} con The container where we can print the form
 * @param {WorkFlow_Action} action The parent action
 */
WorkFlow_Action_Child.prototype.print = function(con, taskObj)
{
    taskObj.type = WF_ATYPE_STARTCHLD;

    var lbl = alib.dom.createElement("div", con);
    alib.dom.styleSetClass(lbl, "formLabel");
    alib.dom.styleSet(lbl, "margin", "6px 0px 3px 3px");
    lbl.innerHTML = "Select a child workflow to launch: test";

    var frm_dv = alib.dom.createElement("div", con);
    var workflows = alib.dom.createElement("select", frm_dv);
    workflows.size = 20;
    workflows.style.width = "98%";
    workflows.taskObj = taskObj;
    workflows.onchange = function()
    {
        this.taskObj.start_wfid = this.value;
    }

    // Set: start_wfid
    ajax = new CAjax('json');
    ajax.workflows = workflows;
    ajax.taskObj = taskObj;
    ajax.onload = function(ret)
    {
        if(ret.length)
        {            
            for (workflow in ret)
            {
                var currentWorkflow = ret[workflow];
                
                if (currentWorkflow.f_active == 't')
                    this.workflows[this.workflows.length] = new Option(currentWorkflow.name, currentWorkflow.id, (currentWorkflow.id == this.taskObj.start_wfid)?true:false);                
            }
        }        
    };
    var args = new Array();
    args[args.length] = ['otypes', taskObj.workflow.object_type];
    ajax.exec("/controller/WorkFlow/getWorkflow", args);
}
