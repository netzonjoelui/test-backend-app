var WF_TIME_UNIT_MINUTE	= 1;
var WF_TIME_UNIT_HOUR	= 2;
var WF_TIME_UNIT_DAY	= 3;
var WF_TIME_UNIT_WEEK	= 4;
var WF_TIME_UNIT_MONTH	= 5;
var WF_TIME_UNIT_YEAR	= 6;

var WF_ATYPE_SENDEMAIL 	= 1;
var WF_ATYPE_CREATEOBJ 	= 2;
var WF_ATYPE_UPDATEFLD 	= 3;
var WF_ATYPE_STARTCHLD 	= 4;
var WF_ATYPE_STOPWF 	= 5;
var WF_ATYPE_APPROVAL   = 8;
var WF_ATYPE_CALLPAGE 	= 9;
var WF_ATYPE_ASSIGNRR 	= 10;
var WF_ATYPE_WAITCONDITION 	= 11;
var WF_ATYPE_CHECKCONDITION 	= 12;


function WorkFlow(id)
{    
	this.id = (id) ? id : null;
	this.name = "Untitled";
	this.fActive = false;
	this.object_type = "";

	// Set launch conditions to null
	this.conditions = new Array();

	// List of actions to perform
	this.actions = new Array();
    this.gWfCondUid = 0;
}

WorkFlow.prototype.getNumConditions = function()
{
    return this.conditions.length;
}

WorkFlow.prototype.addCondition = function(blogic, fieldName, operator, condValue, condId)
{
    if(!condId)
    {
        var condId = "new" + this.gWfCondUid;
        this.gWfCondUid++;
    }    
    var cond = new WorkFlowCondition(blogic, fieldName, operator, condValue, condId);
    this.conditions[this.conditions.length] = cond;    
}

WorkFlow.prototype.delConditions = function()
{
    this.conditions = new Array();;
}

/**
 * Legacy: actions used to be called tasks
 */
WorkFlow.prototype.getNumTasks = function()
{
	return this.getNumActions();
}

/**
 * Get the number of actions at the root level of this workflow
 *
 * @this {WorkFlow}
 * @return {number} number of actions
 */
WorkFlow.prototype.getNumActions = function()
{
	return this.actions.length;
}

/**
 * Add action
 *
 * @this {WorkFlow}
 * @param {number} type One of the defined WF_ATYPE_* id numbers
 * @param {Object] data Optional data used to populate action properties
 * @return {number} number of actions
 */
WorkFlow.prototype.addAction = function(type, data)
{
	var act = new WorkFlow_Action(type, this);

	if (typeof data != "undefined")
		act.loadFromData(data);

	this.actions[this.actions.length] = act;
	return act;
}

/**
 * Remove action
 *
 * @this {WorkFlow}
 * @param {WorkFlow_Action} act Reference to action that should be removed
 */
WorkFlow.prototype.removeAction = function(act)
{
	for (var i = 0; i < this.actions.length; i++)
	{
		if (this.actions[i] == act)
		{
			this.actions[i].remove();

			// Remove from array
			this.actions.splice(i, 1);
		}
	}
}

WorkFlow.prototype.delActionById = function(id)
{
	for (var i = 0; i < this.actions.length; i++)
	{
		if (this.actions[i].id == id)
		{
			this.actions[i].edit_status = "delete";
			//this.actions[i].splice(i, 1);
			return;
		}
	}
}

WorkFlow.prototype.getTasks = function(ind)
{
	return this.actions[ind];
}

WorkFlow.prototype.delTask = function(id)
{
	return this.actions[ind];
}

WorkFlow.prototype.load = function()
{    
	ajax = new CAjax('json');
    ajax.cbData.cls = this;    
    ajax.cbData.id = this.id;
	ajax.onload = function(ret)
	{
        if(ret)
        {
            var wfInfo = ret.wfInfo;
            this.cbData.cls.id = wfInfo.id;
            this.cbData.cls.name = wfInfo.name;
            this.cbData.cls.notes = wfInfo.notes;
            this.cbData.cls.object_type = wfInfo.object_type;        
            this.cbData.cls.fActive = wfInfo.f_active;        
            this.cbData.cls.fOnCreate = wfInfo.f_on_create;        
            this.cbData.cls.fOnUpdate = wfInfo.f_on_update;        
            this.cbData.cls.fOnDelete = wfInfo.f_on_delete;        
            this.cbData.cls.fOnDaily= wfInfo.f_on_daily;        
            this.cbData.cls.fSingleton = wfInfo.f_singleton;        
            this.cbData.cls.fAllowManual = wfInfo.f_allow_manual;
            this.cbData.cls.fConditionUnmet = wfInfo.f_condition_unmet;
            
            var wfCondition = ret.wfCondition;
            for(cond in wfCondition)
            {
                var currentCond = wfCondition[cond];
                this.cbData.cls.addCondition(currentCond.blogic, currentCond.field_name, currentCond.operator, currentCond.cond_value, currentCond.id);
            }
        }        
        
		// Now get actions from server
		this.cbData.cls.loadActions();
	};
    
    ajax.exec("/controller/WorkFlow/getWorkflowDetails", [["wfid", this.id]]);
	
}

/**
 * Load actions array
 *
 * @this {WorkFlow}
 * @private
 */
WorkFlow.prototype.loadActions = function()
{
	ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.onload = function(actions)
	{
		for (var i = 0; i < actions.length; i++)
		{
			this.cbData.cls.addAction(actions[i].type, actions[i]);
		}

		this.cbData.cls.onload();
	};    
	ajax.exec("/controller/WorkFlow/getWorkFlowActions", [["wfid", this.id]]);
}

WorkFlow.prototype.onload = function()
{
	// This function exists to be defined before load is called above
}

WorkFlow.prototype.save = function()
{
	var args = [["name", this.name], ["notes", this.notes], ["object_type", this.object_type],
				["f_on_create", (this.fOnCreate)?'t':'f'], ["f_on_update", (this.fOnUpdate)?'t':'f'], 
				["f_on_delete", (this.fOnDelete)?'t':'f'], ["f_on_daily", (this.fOnDaily)?'t':'f'],
				["f_allow_manual", (this.fAllowManual)?'t':'f'], 
				["f_singleton", (this.fSingleton)?'t':'f'], 
                ["f_active", (this.fActive)?'t':'f'],
                ["f_condition_unmet", (this.fConditionUnmet)?'t':'f']];
	if (this.id)
		args[args.length] = ["wid", this.id];
        
    // Add workflow conditions
    for (var i = 0; i < this.getNumConditions(); i++)
    {
        var cid = this.conditions[i].id;
        args[args.length] = ["conditions[]", cid];
        args[args.length] = ["condition_blogic_"+cid, this.conditions[i].blogic];
        args[args.length] = ["condition_fieldname_"+cid, this.conditions[i].fieldName];
        args[args.length] = ["condition_operator_"+cid, this.conditions[i].operator];
        args[args.length] = ["condition_condvalue_"+cid, this.conditions[i].condValue];
    }
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if (ret)
        {
            if (!this.cbData.cls.id)
            {
                this.cbData.cls.id = ret;
            }

            if (this.cbData.cls.getNumTasks())
                this.cbData.cls.saveActions();
            else
                this.cbData.cls.onsave();
        }
        else
            this.cbData.cls.onsaveError();
    };
    ajax.exec("/controller/WorkFlow/saveWorkflow", args);
}

WorkFlow.prototype.saveActions = function()
{
	for (var i = 0; i < this.getNumActions(); i++)
	{
		if (this.actions[i].isDirty(true)) // the true param will search child actions
		{
			this.actions[i].cbData.workflow = this;
			this.actions[i].onsaved = function()
			{
				this.cbData.workflow.saveActions();
			}
			this.actions[i].save();

			// once child is saved this function will be called again until all children are processed
			return;
		}
	}

	this.onsave();
}

WorkFlow.prototype.onsave = function()
{
	// This function exists to be defined before save is called above
}

WorkFlow.prototype.onsaveError = function()
{
	// This function exists to be defined before save is called above
}



//============================================================================
//    Class: 	WorkFlowCondition
//    			Class that stores and manages single/multi conditions for
//    			any objects.
//============================================================================
function WorkFlowCondition(blogic, fieldName, operator, condValue, condId)
{
    var cond = new Object();
	cond.id = condId;
	cond.blogic = blogic;
	cond.fieldName = fieldName;
	cond.operator = operator;
	cond.condValue = condValue;
    
    return cond;
}


//============================================================================
//     Workflow Functions
//============================================================================
function wfGetTimeUnits()
{
	var buf = new Array();

	buf[0] = new Array(WF_TIME_UNIT_MINUTE, "Minute(s)");
	buf[1] = new Array(WF_TIME_UNIT_HOUR, "Hour(s)");
	buf[2] = new Array(WF_TIME_UNIT_DAY, "Day(s)");
	buf[3] = new Array(WF_TIME_UNIT_WEEK, "Week(s)");
	buf[4] = new Array(WF_TIME_UNIT_MONTH, "Month(s)");
	buf[5] = new Array(WF_TIME_UNIT_YEAR, "Year(s)");

	return buf;
}

function wfGetTimeUnitName(unit)
{
	var buf = "";
	var units = wfGetTimeUnits();

	for (var i = 0; i < units.length; i++)
	{
		if (units[i][0] == unit)
			buf = units[i][1];
	}

	return buf;
}
