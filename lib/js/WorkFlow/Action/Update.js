/**
* @fileOverview The update action is used to request and handle updates on any object type
*
* @author:    joe, sky.stebnicki@aereus.com; 
*             Copyright (c) 2012 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of Update Action
 *
 * @constructor
 * @param {string} objType The type of object that is being approved with this action
 * @param {CDialog} dlg Optional reference to the dialog being used to edit this action
 */
function WorkFlow_Action_Update(objType, dlg)
{
    /**
    * The object that is the subject of this update request
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
 * @this {WorkFlow_Action_Update}
 * @param {DOMElement} con The container where we can print the form
 * @param {WorkFlow_Action} action The parent action
 */
WorkFlow_Action_Update.prototype.print = function(con, taskObj)
{
    var dv_cnd = alib.dom.createElement("fieldset", con);
    alib.dom.styleSet(dv_cnd, "margin", "6px 0px 3px 3px");
    var lbl = alib.dom.createElement("legend", dv_cnd);
    lbl.innerHTML = "Update Field";

    var lbl = alib.dom.createElement("div", dv_cnd);
    lbl.innerHTML = "Change: ";

    var sel_fields = alib.dom.createElement("select", lbl);

    var lbl = alib.dom.createElement("div", dv_cnd);
    lbl.innerHTML = "To: ";

    var inp_con = alib.dom.createElement("span", lbl);

    var tmpAntObj = new CAntObject(taskObj.workflow.object_type);
    sel_fields.m_taskObj = taskObj;
    sel_fields.m_inp_con = inp_con;
    sel_fields.m_ant_obj = tmpAntObj;
    sel_fields.onchange = function()
    {
        if (this.value.indexOf(".")!=-1)
        {
            var parts = this.value.split(".");
            if (parts.length==3)
            {
                var ref_obj = new CAntObject(parts[1]);
                this.m_taskObj.frm_changeto = ref_obj.fieldCreateValueInput(this.m_inp_con, parts[2]);
            }
        }
        else
        {
            this.m_taskObj.frm_changeto = this.m_ant_obj.fieldCreateValueInput(this.m_inp_con, this.value);
        }

        this.m_taskObj.update_field = this.value;        
    }

    var fields = tmpAntObj.getFields();
    for (var i = 0; i < fields.length; i++)
    {
        sel_fields[sel_fields.length] = new Option(fields[i].title, fields[i].name, false, (taskObj.update_field == fields[i].name)?true:false);
        if (fields[i].type == "object")
        {
            if (fields[i].subtype)
            {
                var ref_obj = new CAntObject(fields[i].subtype);
                var ref_fields = ref_obj.getFields();
                for (var j = 0; j < ref_fields.length; j++)
                {
                    var val = fields[i].name+"."+ref_obj.name+"."+ref_fields[j].name;
                    sel_fields[sel_fields.length] = new Option(fields[i].title+"."+ref_fields[j].title, 
                                                                val, false, (taskObj.update_field == val)?true:false);
                }
            }
            else
            {
                for (var m = 0; m < this.g_objTypes.length; m++)
                {
                    if (this.g_objTypes[m][0] != tmpAntObj.name)
                    {
                        var ref_obj = new CAntObject(this.g_objTypes[m][0]);
                        var ref_fields = ref_obj.getFields();
                        for (var j = 0; j < ref_fields.length; j++)
                        {
                            var val = fields[i].name+"."+ref_obj.name+"."+ref_fields[j].name;
                            sel_fields[sel_fields.length] = new Option(fields[i].title+"."+ref_obj.title+"."+ref_fields[j].title, 
                                                                        val, false, (taskObj.update_field == val)?true:false);
                        }
                    }
                }                
            }
        }

    }
    var fld = (taskObj.update_field) ? taskObj.update_field : fields[0].name;
    taskObj.frm_changeto = tmpAntObj.fieldCreateValueInput(inp_con, fld, taskObj.getObjectValue("update_to"));    
    taskObj.frm_changeto.act = taskObj;
    taskObj.frm_changeto.onchange = function()
    {
        this.act.setObjectValue("update_to", this.value);
    }
}
