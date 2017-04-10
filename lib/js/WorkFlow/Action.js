//============================================================================
//    Class: 	WorkFlow_Action
//============================================================================
var g_ActId = 0;
function WorkFlow_Action(type, workflow, parentAction)
{
	this.workflow = workflow;
	this.parentAction = (parentAction) ? parentAction : null;
	this.parentActionId = (parentAction) ? parentAction.id : null;
	this.parentActionEvent = null;

	this.id = null;
	this.name = "Untitled";
	this.edit_status = "nochange";

	// Set launch conditions to null (will inherit from WorkFlow)
    this.conditions = new Array();
	this.antConditionsObj = null;

	// Set launch time
	this.when = new Object();
	this.when.interval = 0;
	this.when.unit = type;

	// Set type
	this.type = type;

	// WF_ATYPE_SENDEMAIL
	this.send_email_fid 	= "object_name/type";

	// WF_ATYPE_UPDATEFLD
	this.update_field 		= null; // field_name
	this.update_to 			= null; // field_value

	// WF_ATYPE_CREATEOBJ
	this.create_obj 		= null; // object_name/type
	this.create_obj_fid		= null; // File ID for object template
	this.create_obj_values 	= new Array(); // [['field_name', 'field_value'], ['field2_name', 'field2_value']]
	
	// WF_ATYPE_STARTCHLD
	this.start_wfid 		= null; // The workflow id to start

	// WF_ATYPE_STOPWF
	this.stop_wfid 			= null; // The workflow id to stop - should default to this one

	// Array of child actions
	this.childActions = new Array(); 
	this.cbData = new Object();
	this.dirty = true;
}

/**
 * Save this action and any children actions
 *
 * When finished this.onsaved will be called
 *
 * @public
 * @this {WorkFlow_Action}
 * @param {bool} force If true, action will be saved even if it is not dirty
 */
WorkFlow_Action.prototype.save = function(force)
{
	if (this.isDirty() || force)
	{
		alib.m_debug = true;
		var ajax = new CAjax('json');
		ajax.cbData.cls = this;
		ajax.onload = function(aid)
		{
			if (!this.cbData.cls.id && aid)
				this.cbData.cls.id = aid;

			this.cbData.cls.dirty = false;
			this.cbData.cls.saveChildren();
		};

		var args = [
			["id", this.id],
			["workflow_id", this.workflow.id],
			["type", this.type],
			["when_interval", this.when.interval],
			// ["when_unit", this.when.unit], Depricated - Removed the wait condition and moved it to wf wait condition action
			["name", this.name],
			["send_email_fid", this.send_email_fid],
			["update_field", this.update_field],
			["update_to", this.update_to],
			["parent_action_id", (this.parentAction) ? this.parentAction.id : this.parentActionId],
			["parent_action_event", this.parentActionEvent],
			["create_obj", this.create_obj],
			["start_wfid", this.start_wfid],
			["stop_wfid", this.stop_wfid]
		];


		/* Depricated - Removed the check condition and moved it to wf checko condition action
		// Add antConditionsObj into args
		for (var i = 0; i < this.antConditionsObj.getNumConditions(); i++)
        {
            var currentCondition = this.antConditionsObj.getCondition(i);        
            var cid = currentCondition.condId;
        
            if(!cid > 0)
                cid = "new" + i;
                
			args[args.length] = ["conditions[]", cid];
			args[args.length] = ["condition_" + cid + "_blogic", currentCondition.blogic];
			args[args.length] = ["condition_" + cid + "_fieldname", currentCondition.fieldName];
			args[args.length] = ["condition_" + cid + "_operator", currentCondition.operator];
			args[args.length] = ["condition_" + cid + "_condvalue", currentCondition.condValue];
		}
		*/

		// Add object values
		for (var i = 0; i < this.getNumObjectValues(); i++)
		{
			var oval = this.getObjectValueByIdx(i);
			args[args.length] = ["ovals[]", oval.name];

			var pVarName = "oval_"+oval.name;
			if (oval.isMulti)
			{
				var mvals = this.getObjectMultiValues(oval.name);
				for (var j = 0; j < mvals.length; j++)
				{
					args[args.length] = [pVarName+"[]", mvals[j]];
				}
			}
			else
			{
				args[args.length] = [pVarName, oval.value];
			}
		}

		ajax.exec("/controller/WorkFlow/saveAction", args);
	}
	else
	{        
		this.saveChildren();
	}
}

/**
 * Recurrsively save children
 *
 * @public
 * @this {WorkFlow_Action}
 */
WorkFlow_Action.prototype.saveChildren = function()
{    
	// Loop through all children marked as dirty
	for (var i = 0; i < this.childActions.length; i++)
	{
		if (this.childActions[i].isDirty(true))
		{
			this.childActions[i].cbData.parentAct = this;
			this.childActions[i].onsaved = function()
			{
				this.cbData.parentAct.saveChildren();
			}
			this.childActions[i].save();

			// once child is saved this function will be called again until all children are processed
			return;
		}
	}

	// Register finished
	this.onsaved();
}

/**
 * Callback used to notify calling process when this and all child actions have been saved
 *
 * @public
 * @this {WorkFlow_Action}
 */
WorkFlow_Action.prototype.onsaved = function()
{
}

/**
 * Callback function called any time updates are saved for this action
 *
 * @public
 * @this {WorkFlow_Action}
 */
WorkFlow_Action.prototype.onupdate = function()
{
}

/**
 * Check if this action needs to be saved
 *
 * @public
 * @this {WorkFlow_Action}
 * @param {bool} checkChildren If set to true, action will be dirty if a child is dirty
 * @return {bool} true if action has changed since last save
 */
WorkFlow_Action.prototype.isDirty = function(checkChildren)
{
	var ret = this.dirty;

	if (checkChildren)
	{
		for (var i = 0; i < this.childActions.length; i++)
		{
			if (this.childActions[i].isDirty(true))
			{
				ret = true;
			}
			else
			{
			}
		}
	}

	return ret;
}

/**
 * Set properties from a data object
 *
 * @public
 * @this {WorkFlow_Action}
 * @param {Object} data Object with properties set to be copied to this action
 */
WorkFlow_Action.prototype.loadFromData = function(data)
{
	if (typeof data == "undefined")
		return false;

	if (data.id)
		this.id = parseInt(data.id);

	if (data.type)
		this.type = parseInt(data.type);

	if (data.workflow_id)
		this.workflow_id = data.workflow_id;

	if (data.when_interval)
		this.when.interval = data.when_interval;

	if (data.when_unit)
		this.when.unit = data.when_unit;

	if (data.name)
		this.name = data.name;

	if (data.send_email_fid)
		this.send_email_fid = parseInt(data.send_email_fid);

	if (data.update_field)
		this.update_field = data.update_field;

	if (data.update_to)
		this.update_to = data.update_to;

	if (data.parentActionId)
		this.parentActionId = parseInt(data.parentActionId);

	if (data.parentActionEvent)
		this.parentActionEvent = data.parentActionEvent;

	if (data.create_obj)
		this.create_obj = data.create_obj;

	if (data.start_wfid)
		this.start_wfid = parseInt(data.start_wfid);

	if (data.stop_wfid)
		this.stop_wfid = parseInt(data.stop_wfid);

	for (var name in data.object_values)
		this.setObjectValue(name, data.object_values[name]);

	for (var i = 0; i < data.conditions.length; i++)    
        this.addCondition(data.conditions[i].blogic, data.conditions[i].fieldName, data.conditions[i].operator, data.conditions[i].condValue, data.conditions[i].id);
		

	for (var i = 0; i < data.child_actions.length; i++)
		this.addAction(parseInt(data.child_actions[i].type), data.child_actions[i]);

	// Does not need to be saved until edited
	this.dirty = false;
}

/**
 * Remove this action
 *
 * @public
 * @this {WorkFlow_Action}
 */
WorkFlow_Action.prototype.remove = function()
{
	// Loop through and delete all child actions
	for (var i = 0; i < this.childActions.length; i++)
	{
		this.childActions[i].remove();
	}

	// Delete from backend if this was previously saved
	if (this.id)
	{
		var ajax = new CAjax('json');
		ajax.onload = function(resp)
		{
			// TODO: so far we are just letting this process in the background
		};
		var url = "/admin/xml_get_workflows.php?otypes="+this.workflow.object_type;
		ajax.exec("/controller/WorkFlow/removeAction", [["id", this.id]]);
	}
}

/**
 * Add action
 *
 * @this {WorkFlow_Action}
 * @param {number} type One of the defined WF_ATYPE_* id numbers
 * @param {Object] data Optional data used to populate action properties
 * @return {number} number of actions
 */
WorkFlow_Action.prototype.addAction = function(type, data)
{
	var act = new WorkFlow_Action(type, this.workflow, this);

	if (typeof data != "undefined")
		act.loadFromData(data);

	this.childActions[this.childActions.length] = act;
	return act;
}

/**
 * Remove action
 *
 * @this {WorkFlow}
 * @param {WorkFlow_Action} act Reference to action that should be removed
 */
WorkFlow_Action.prototype.removeAction = function(act)
{
	for (var i = 0; i < this.childActions.length; i++)
	{
		if (this.childActions[i] == act)
		{
			this.childActions[i].remove();

			// Remove from array
			var ret = this.childActions.splice(i, 1);
		}
	}
}

WorkFlow_Action.prototype.showDialog = function(parentDialog)
{
	var pdialog = (parentDialog) ? parentDialog : null;
	var lbl = (this.id) ? "Edit Action" : "Create New Action";
	var actDlg = new CDialog(lbl, pdialog);
	actDlg.f_close = true;
	var dv = alib.dom.createElement("div");
	actDlg.customDialog(dv, 560);

	var tbl = alib.dom.createElement("table", dv);
	var tbody = alib.dom.createElement("tbody", tbl);

	// Title
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "strong");
	td.innerHTML = "Name";
	var td = alib.dom.createElement("td", row);
	var txtTaskTitle = alib.dom.createElement("input", td);
	txtTaskTitle.act = this;
	txtTaskTitle.value = this.name;
	txtTaskTitle.onchange = function() { this.act.name = this.value; };
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = "&nbsp;give this action a unique name";

	// Depricated - Removed the wait condition and moved it to wf wait condition action
	// Timeframe
	// --------------------------------------------
	/*
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "strong");
	td.innerHTML = "When";
	var td = alib.dom.createElement("td", row);

	var txtWhenInterval = alib.dom.createElement("input", td);
	alib.dom.styleSet(txtWhenInterval, "width", "28px");
	txtWhenInterval.act = this;
	txtWhenInterval.value = this.when.interval;
	txtWhenInterval.onchange = function() { this.act.when.interval = this.value; };

	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = "&nbsp;";

	var cbWhenUnit = alib.dom.createElement("select", td);
	var time_units = wfGetTimeUnits();
	for (var i = 0; i < time_units.length; i++)
	{
		cbWhenUnit[cbWhenUnit.length] = new Option(time_units[i][1], time_units[i][0], false, (this.when.unit==time_units[i][0])?true:false);
	}
	cbWhenUnit.act = this;
	cbWhenUnit.onchange = function() { this.act.when.unit = this.value; };

	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = " after workflow starts (enter 0 for immediate)";
	*/

	// Depricated - Removed the check condition and moved it to wf check condition action
	// Conditions
	// --------------------------------------------
	/*
	var row = alib.dom.createElement("tr", tbody);
	var lbl = alib.dom.createElement("td", row);
	lbl.colSpan = 2;
	alib.dom.styleSetClass(lbl, "strong");
	lbl.innerHTML = "Only execute action if the following conditions are met (optional):";
	var row = alib.dom.createElement("tr", tbody);
	var condCon = alib.dom.createElement("td", row);
	condCon.colSpan = 2;
	var dv_cnd = alib.dom.createElement("div", condCon);
	var tmpAntObj = new CAntObject(this.workflow.object_type);
	this.antConditionsObj = tmpAntObj.buildAdvancedQuery(dv_cnd, this.conditions);
	*/

	// Do
	// --------------------------------------------
	var divDo = alib.dom.createElement("div", dv);
	alib.dom.styleSet(divDo, "height", '400px');
	alib.dom.styleSet(divDo, "overflow", 'auto');
	switch (this.type)
	{
	case WF_ATYPE_SENDEMAIL:
		var email_action = new WorkFlow_Action_Email(this.workflow.object_type);
		email_action.taskSendEmail(divDo, this);
		break;
	case WF_ATYPE_UPDATEFLD:
		var actCon = alib.dom.createElement("div", divDo);
        var updateAction = new WorkFlow_Action_Update(this.workflow.object_type, actDlg);
        updateAction.print(actCon, this);
		break;
	case WF_ATYPE_STARTCHLD:		
        var actCon = alib.dom.createElement("div", divDo);
        var childAction = new WorkFlow_Action_Child(this.workflow.object_type, actDlg);
        childAction.print(actCon, this);
		break;
	case WF_ATYPE_ASSIGNRR:		
        var actCon = alib.dom.createElement("div", divDo);
        var childAction = new WorkFlow_Action_AssignRR(this.workflow.object_type, actDlg);
        childAction.print(actCon, this);
		break;
	case WF_ATYPE_APPROVAL:
		var actCon = alib.dom.createElement("div", divDo);
		var app_action = new WorkFlow_Action_Approval(this.workflow.object_type, actDlg);
		app_action.print(actCon, this);
		// TODO: display approval form
		break;
    case WF_ATYPE_CALLPAGE:
        var actCon = alib.dom.createElement("div", divDo);
        var app_action = new WorkFlow_Action_CallPage(this.workflow.object_type, actDlg);
        app_action.print(actCon, this);
        break;
	case WF_ATYPE_WAITCONDITION:
		var actCon = alib.dom.createElement("div", divDo);
		var app_action = new WorkFlow_Action_WaitCondition(this.workflow.object_type, actDlg);
		app_action.print(actCon, this);
		break;
	case WF_ATYPE_CHECKCONDITION:
		var actCon = alib.dom.createElement("div", divDo);
		var app_action = new WorkFlow_Action_CheckCondition(this.workflow.object_type, actDlg);
		app_action.print(actCon, this);
		break;
	default: // Create new
		if (this.create_obj == "task")
		{
			var task_action = new WorkFlow_Action_Task(this.workflow.object_type)
			task_action.taskCreateTask(divDo, this);
		}
		if (this.create_obj == "invoice")
		{
			var task_invoice = new WorkFlow_Action_Invoice(this.workflow.object_type);
			task_invoice.taskCreateInvoice(divDo, this);
		}
		if (this.create_obj == "notification")
		{
			var task_action = new WorkFlow_Action_Notification(this.workflow.object_type)
			task_action.render(divDo, this);
		}
	}
	
	// Buttons
	// --------------------------------------------
	var bntbar = alib.dom.createElement("div", dv);
	alib.dom.styleSet(bntbar, "margin", "6px 0px 3px 3px");
	
	var btn = new CButton("Save", 
	function(cls, dlg, act, condObj, isnew) 
	{
		dlg.hide();
		cls.dirty = true;        
		//cls.saveTask(act, condObj, isnew);        
		cls.onupdate();
	}, [this, actDlg, this, this.conditions, (this.id)?false:true], "b2");
	btn.print(bntbar);
	var btn = new CButton("Cancel", function(dlg) { dlg.hide(); }, [actDlg], "b1");
	btn.print(bntbar);


	actDlg.reposition();
}

WorkFlow_Action.prototype.getNumObjectValues = function()
{
	return this.create_obj_values.length;
}

WorkFlow_Action.prototype.getObjectValueByIdx = function(ind)
{
	var fMulti = (typeof this.create_obj_values[ind][1] == "object") ? true : false;

	return { name:this.create_obj_values[ind][0], value:this.create_obj_values[ind][1], isMulti:fMulti };
}

WorkFlow_Action.prototype.setObjectValue = function(name, value)
{    
	for (var i = 0; i < this.create_obj_values.length; i++)
	{
		if (this.create_obj_values[i][0] == name)
		{
			this.create_obj_values[i][1] = value;
			return;
		}
	}

	var ind = this.create_obj_values.length;
	this.create_obj_values[ind] = new Array();
	this.create_obj_values[ind][0] = name;
	this.create_obj_values[ind][1] = value;
}

WorkFlow_Action.prototype.getObjectValue = function(name)
{
	for (var i = 0; i < this.create_obj_values.length; i++)
	{
		if (this.create_obj_values[i][0] == name)
		{
			return this.create_obj_values[i][1];
		}
	}
	
	return null;
}

WorkFlow_Action.prototype.setObjectMultiValue = function(name, value)
{
	// Update value
	for (var i = 0; i < this.create_obj_values.length; i++)
	{
		if (this.create_obj_values[i][0] == name)
		{
			var bFound = false;
			for (var m = 0; m < this.create_obj_values[i][1].length; m++)
			{
				if (this.create_obj_values[i][1][m] == value)
					bFound == true;
			}

			if (!bFound)
			{
				this.create_obj_values[i][1][this.create_obj_values[i][1].length] = value;
			}

			return;
		}
	}

	// New value
	var ind = this.create_obj_values.length;
	this.create_obj_values[ind] = new Array();
	this.create_obj_values[ind][0] = name;
	this.create_obj_values[ind][1] = new Array();
	this.create_obj_values[ind][1][0] = value;
}

WorkFlow_Action.prototype.getObjectMultiValueExists = function(name, value)
{
	// Check if value is already set
	for (var i = 0; i < this.create_obj_values.length; i++)
	{
		if (this.create_obj_values[i][0] == name)
		{
			for (var m = 0; m < this.create_obj_values[i][1].length; m++)
			{
				if (this.create_obj_values[i][1][m] == value)
					return true;
			}
		}
	}

	// Does not exist
	return false;
}

WorkFlow_Action.prototype.getObjectMultiValues = function(name)
{
	var ret = new Array();
	// Check if value is already set
	for (var i = 0; i < this.create_obj_values.length; i++)
	{
		if (this.create_obj_values[i][0] == name)
		{
			for (var m = 0; m < this.create_obj_values[i][1].length; m++)
			{
				ret[ret.length] = this.create_obj_values[i][1][m];
			}
		}
	}

	// Does not exist
	return ret;
}

WorkFlow_Action.prototype.delObjectMultiValue = function(name, value)
{
	// Delete value
	for (var i = 0; i < this.create_obj_values.length; i++)
	{
		if (this.create_obj_values[i][0] == name)
		{
			for (var m = 0; m < this.create_obj_values[i][1].length; m++)
			{
				if (this.create_obj_values[i][1][m] == value)
					this.create_obj_values[i][1].splice(m, 1);
			}
		}
	}
}

//--------------------------------------------------------------------------
//	Conditions
//--------------------------------------------------------------------------
WorkFlow_Action.prototype.addCondition = function(blogic, fieldName, operator, condValue, condId)
{
	var cond = new WorkFlowCondition(blogic, fieldName, operator, condValue, condId);
	this.conditions[this.conditions.length] = cond;
}

WorkFlow_Action.prototype.addConditionObj = function(blogic, fieldName, operator, condValue)
{
	var cond = new WorkFlowCondition(blogic, fieldName, operator, condValue);
	this.conditions[this.conditions.length] = cond;
}

WorkFlow_Action.prototype.delConditions = function()
{
	this.conditions = new Array();;
}

//--------------------------------------------------------------------------
//	Other
//--------------------------------------------------------------------------
WorkFlow_Action.prototype.getTypeDesc = function()
{
	switch (this.type)
	{
	    case WF_ATYPE_SENDEMAIL:
		    return "Send Email";
	    case WF_ATYPE_UPDATEFLD:
		    return "Update Field";
	    case WF_ATYPE_STARTCHLD:
		    return "Start Child Workflow";
	    case WF_ATYPE_STOPWF:
		    return "Stop Workflow";
	    case WF_ATYPE_CREATEOBJ:
		    return "Create New " + this.create_obj;
	    case WF_ATYPE_APPROVAL:
		    return "Request Approval";
        case WF_ATYPE_CALLPAGE:
            return "Call Page";
        case WF_ATYPE_ASSIGNRR:
            return "Assign";
		case WF_ATYPE_WAITCONDITION:
			return "Wait";
		case WF_ATYPE_CHECKCONDITION:
			return "Execute Workflow";
	}
}

WorkFlow_Action.prototype.getTypeName = function()
{
	switch (this.type)
	{
	case WF_ATYPE_SENDEMAIL:
		return "email";
	case WF_ATYPE_UPDATEFLD:
		return "update";
	case WF_ATYPE_STARTCHLD:
		return "Start Child Workflow";
	case WF_ATYPE_STOPWF:
		return "Stop Workflow";
	case WF_ATYPE_CREATEOBJ:
		return "task";
	case WF_ATYPE_APPROVAL:
		return "approval";
	case WF_ATYPE_ASSIGNRR:
		return "assign";
	}
}

WorkFlow_Action.prototype.getWhenDesc = function()
{
	if (this.type == WF_ATYPE_CHECKCONDITION)
		return "If";
	if (this.when.interval == 0) {
		return "Immediately";
	}
	else {
		return this.when.interval + " " + wfGetTimeUnitName(this.when.unit) + " after workflow starts";
	}
}

WorkFlow_Action.prototype.getCondDesc = function()
{
	var buf = "";
    
    if(this.antConditionsObj)
    {
        this.conditions = new Array();
    
        for (var i = 0; i < this.antConditionsObj.getNumConditions(); i++)
        {
            var currentCondition = this.antConditionsObj.getCondition(i);
            
            this.addCondition(currentCondition.blogic, currentCondition.fieldName, currentCondition.operator, currentCondition.condValue, currentCondition.condId)
            
            if (buf.length)
                buf += " " + currentCondition.blogic + " ";
            
            buf += currentCondition.fieldName + " " + currentCondition.operator + " " + currentCondition.condValue;
        }
    }
    else
    {
        for (var i = 0; i < this.conditions.length; i++)
        {
            if (buf.length)
                buf += " " + this.conditions[i].blogic + " ";

            buf += this.conditions[i].fieldName + " " + this.conditions[i].operator + " " + this.conditions[i].condValue;
        }
    }
    
	return buf;
}

/**
 * Get sub-action events. All actions have '' as a subevent which means it launches on execution.
 *
 * @this {WorkFlow_Action}
 * @return {Object[]} Array of objects with .name and .description properties
 */
WorkFlow_Action.prototype.getSubActionEvents = function()
{
	var ret = [];

	// default
	ret[ret.length] = {name:"", description:"After Action Completes:"};

	if (this.type == WF_ATYPE_APPROVAL)
	{
		ret[ret.length] = {name:"approved", description:"On Approved:"};
		ret[ret.length] = {name:"declined", description:"On Declined:"};
	}

	return ret;
}

/**
 * Get summary description of this action
 *
 * @this {WorkFlow_Action}
 * @return {string} Human readable description of this action
 */
WorkFlow_Action.prototype.getSummary = function()
{
	var desc = this.name + ": " + this.getTypeDesc() + " " + this.getWhenDesc() + " " + this.getCondDesc();
	return desc;
}
