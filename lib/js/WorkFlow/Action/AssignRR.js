/**
* @fileOverview Use Round-Robin to assign any object to a user
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
function WorkFlow_Action_AssignRR(obj_type, dlg)
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
WorkFlow_Action_AssignRR.prototype.print = function(con, action)
{
    var lbl = alib.dom.createElement("div", con);
    lbl.innerHTML = "Set: ";

    var sel_fields = alib.dom.createElement("select", lbl);

    var tmpAntObj = new CAntObject(action.workflow.object_type);
    sel_fields.m_taskObj = action;
    sel_fields.m_ant_obj = tmpAntObj;
    sel_fields.onchange = function()
    {
        this.m_taskObj.update_field = this.value;        
    }

    var fields = tmpAntObj.getFields();
    for (var i = 0; i < fields.length; i++)
    {     
        if (fields[i].type == "object" && fields[i].subtype == "user")
        {
            sel_fields[sel_fields.length] = new Option(fields[i].title, fields[i].name, false, (action.update_field == fields[i].name)?true:false);

            if (fields[i].subtype)
            {
                var ref_obj = new CAntObject(fields[i].subtype);
                var ref_fields = ref_obj.getFields();
                for (var j = 0; j < ref_fields.length; j++)
                {
                    var val = fields[i].name+"."+ref_obj.name+"."+ref_fields[j].name;
                    sel_fields[sel_fields.length] = new Option(fields[i].title+"."+ref_fields[j].title, 
                                                                val, false, (action.update_field == val)?true:false);
                }
            }
        }

    }

    // Add to
    var lbl = alib.dom.createElement("div", con);
    lbl.innerHTML = "Assign to (enter users names separated by a comma ',': ";

    var inp_con = alib.dom.createElement("div", con);
    var ta = alib.dom.createElement("textarea", inp_con);
    alib.dom.styleSet(ta, "width", "100%");
    ta.value = action.getObjectValue("update_to");
    ta.act = action;
    ta.onchange = function() {
        this.act.setObjectValue("update_to", this.value);
    }
}
