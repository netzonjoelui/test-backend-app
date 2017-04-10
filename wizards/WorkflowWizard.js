/****************************************************************************
*	
*	Class:		WorkflowWizard
*
*	Purpose:	Wizard for creating/editing workflows
*
*****************************************************************************/
function WorkflowWizard(name, wid)
{
	// Edit existing workflow
	if(name && wid)
	{
		this.g_wid = wid;
		this.g_workflow = new WorkFlow(this.g_wid);
		this.g_workflow.object_type = name;
		this.g_workflow.cls = this;
		this.g_workflow.onload = function()
		{
			// Only called after workflow is loaded
			this.cls.showStep(0);			
		}
		this.g_workflow.load();
	}
	// New Workflow
	else
	{
		this.g_workflow = new WorkFlow();
		this.g_workflow.object_type = null;
		this.g_workflow.name = "My Workflow";
		this.g_workflow.notes = "Place notes here";
	}

	this.g_tblTasks = new CToolTable("100%");
	this.g_theme = (typeof Ant != "undefined") ? Ant.theme.name: "cheery";
	this.g_objTypes = new Array;
	this.getObjects();							// Populate g_objTypes
	this.buildActionTbl = true;
	
	this.steps = new Array();
	this.steps[0] = "Workflow Description";
	this.steps[1] = "Workflow Conditions";
	this.steps[2] = "Workflow Actions";
	this.steps[3] = "Finished";
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display wizard
**************************************************************************/
WorkflowWizard.prototype.showDialog = function(parentDlg)
{
	// Dialog Title
	if(this.g_workflow.object_type == null)
		var dlg_title = "Create Workflow";
	else
		var dlg_title = "Edit Workflow";
	
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.m_dlg = new CDialog(dlg_title, this.parentDlg);
	this.m_dlg.f_close = true;
	var dlg = this.m_dlg;

	this.body_dv = alib.dom.createElement("div");
	dlg.customDialog(this.body_dv, 820, 550);

	this.body_dv.innerHTML = "<div class='loading'></div>";
	if(this.g_workflow.object_type == null)			// New Workflow
		this.showStep(0);
}

/*************************************************************************
*	Function:	showStep
*
*	Purpose:	Used to display the contents of a given step
**************************************************************************/
WorkflowWizard.prototype.showStep = function(step)
{
	this.body_dv.innerHTML = ""; 
	this.verify_step_data = new Object();
	this.nextStep = step+1;

	// Path
	// ---------------------------------------------------------
	this.pathDiv = alib.dom.createElement("div", this.body_dv);
	this.pathDiv.innerHTML = "Step " + (step + 1) + " of " + this.steps.length + " - " + this.steps[step];
	alib.dom.styleSetClass(this.pathDiv, "wizardTitle");

	// Main content
	// ---------------------------------------------------------
	var div_main = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSetClass(div_main, "wizardBody");
    //alib.dom.styleSet(div_main, "height", "490");

	switch (step)
	{
	case 0:
		this.buildDescription(div_main);
		this.veriftyStep = function()
		{
			// Check for Object Type (Required)
			if(this.g_workflow.object_type)
				return true;
			else
			{
				this.verify_step_data.message = "Object Type not specified! Please select an Object Type.";
				return false;
			}
		}
		break;
	case 1:
		this.buildConditions(div_main);
		break;
	case 2:
		this.buildActions(div_main);
		break;
	case 3:
		div_main.innerHTML = "<h2>Congratulations, your workflow has been created!</h2>Click 'Save Workflow' below to save changes and continue working.";
		break;
	}

	// Buttons
	// ---------------------------------------------------------
	var dv_btn = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSet(dv_btn, "text-align", "right");

	var btn = new CButton("Back", 
	function(cls, step) 
	{
		// If leaving step 2, save conditions
		if(1 == step)
			cls.updateConditions();
		cls.showStep(step-1); 
	}, [this, step], "b1");
	btn.print(dv_btn);
	if (step == 0)
		btn.disable();

	if (step == (this.steps.length - 1))
	{
		var btn = new CButton("Save Workflow", function(cls) { cls.save(); }, [this], "b2");
		btn.print(dv_btn);
	}
	else
	{
		var next_funct = function(cls, step)
		{
			if (cls.veriftyStep())
			{
				// If leaving step 2, save conditions
				if(1 == step)
					cls.updateConditions();
				cls.showStep(step+1);
			}
			else
				ALib.Dlg.messageBox(cls.verify_step_data.message, cls.m_dlg);
		}
		var btn = new CButton("Continue", next_funct, [this, step], "b2");
		btn.print(dv_btn);
	}

	var btn = new CButton("Cancel", function(dlg, cls) { cls.cancel(); }, [this.m_dlg, this], "b3");
	btn.print(dv_btn);
}

/*************************************************************************
*	Function:	veriftyStep
*
*	Purpose:	This function should be over-rideen with each step
**************************************************************************/
WorkflowWizard.prototype.veriftyStep = function()
{
	return true;
}

/*************************************************************************
*	Function:	buildDescription
*
*	Purpose:	Build Description step
**************************************************************************/
WorkflowWizard.prototype.buildDescription = function(con)
{
	// Add Name, Description, Object Type
	// ==========================================================
	var main_dv = alib.dom.createElement("div", con);

	// Welcome
	var p = alib.dom.createElement("p", main_dv);
	alib.dom.styleSetClass(p, "info");
	p.innerHTML = "Welcome to the workflow wizard. Workflows automate both simple and complex tasks like 'if a lead gets updated by someone other than the owner, send the owner an email notifying them of the change' or 'if an opportunity is older than 30 days and has not been contacted, email a manager.' This wizard will guide you through the steps needed to create powerful automated workflows.";
	
	// Title
	var dv = alib.dom.createElement("div", main_dv);
	alib.dom.styleSet(dv, "margin", "10px 0px 3px 0px");
	var td = alib.dom.createElement("div", dv, "Name");
	alib.dom.styleSet(td, "float", "left");
	alib.dom.styleSet(td, "width", "75px");
	alib.dom.styleSet(td, "margin-top", "5px");
	alib.dom.styleSetClass(td, "strong");
	var td = alib.dom.createElement("div", dv);
	alib.dom.styleSet(td, "margin-left", "80px");
	var txtTitle = alib.dom.createElement("input");
	txtTitle.cls = this;
	txtTitle.type = "text";
	alib.dom.styleSet(txtTitle, "width", "98%");
	txtTitle.onchange = function() { this.cls.g_workflow.name = this.value; }
	td.appendChild(txtTitle);
	if (this.g_workflow.name)
		txtTitle.value = this.g_workflow.name;
	
	// Notes
	var dv = alib.dom.createElement("div", main_dv);
	alib.dom.styleSet(dv, "margin", "3px 0px 3px 0px");
	var td = alib.dom.createElement("div", dv, "Description");
	alib.dom.styleSet(td, "float", "left");
	alib.dom.styleSet(td, "width", "75px");
	alib.dom.styleSet(td, "margin-top", "5px");
	alib.dom.styleSetClass(td, "strong");
	var td = alib.dom.createElement("div", dv);
	alib.dom.styleSet(td, "margin-left", "80px");
	var txtNotes = alib.dom.createElement("textarea");
	txtNotes.cls = this;
	alib.dom.styleSet(txtNotes, "width", "98%");
	txtNotes.onchange = function() { this.cls.g_workflow.notes = this.value; }
	td.appendChild(txtNotes);
	if (this.g_workflow.notes)
		txtNotes.value = this.g_workflow.notes;

	// Object Type
	var dv = alib.dom.createElement("div", main_dv);
	alib.dom.styleSet(dv, "margin", "3px 0px 3px 0px");
	var td = alib.dom.createElement("div", dv);
	alib.dom.styleSet(td, "float", "left");
	alib.dom.styleSet(td, "width", "75px");
	alib.dom.styleSet(td, "margin-top", "5px");
	alib.dom.styleSetClass(td, "strong");
	td.innerHTML = "Object Type";
	var td = alib.dom.createElement("div", dv);
	alib.dom.styleSet(td, "margin-left", "80px");
	var dm = alib.dom.createElement("select");
	dm.cls = this;
	dm.onchange = function() 
	{
		if(this.value != "")
			this.cls.g_workflow.object_type = this.value;
	}
	dm[dm.length] = new Option("Select Object Type", "");
	
    if(this.g_objTypes)
    {
        for(object in this.g_objTypes)
        {
            var currentObject = this.g_objTypes[object];
            dm[dm.length] = new Option(currentObject.title, currentObject.name);
        }
    }
    
	td.appendChild(dm);
	if(this.g_workflow.object_type)
		dm.value = this.g_workflow.object_type;

	var notes = alib.dom.createElement("span", td, " select the type of object this workflow will be working with");

	// Active
	var dv = alib.dom.createElement("div", main_dv);
	alib.dom.styleSet(dv, "margin", "3px 0px 3px 0px");
	var td = alib.dom.createElement("div", dv);
	alib.dom.styleSet(td, "float", "left");
	alib.dom.styleSet(td, "width", "75px");
	alib.dom.styleSet(td, "margin-top", "5px");
	alib.dom.styleSetClass(td, "strong");
	td.innerHTML = "Published";
	var td = alib.dom.createElement("div", dv);
	alib.dom.styleSet(td, "margin-left", "80px");
	var chk = alib.dom.createElement("input", td);
	chk.cls = this;
	chk.type = "checkbox";
	chk.checked = this.g_workflow.fActive;
	chk.onchange = function() { this.cls.g_workflow.fActive = this.checked; }
	alib.dom.createElement("span", td, " if checked this workflow is active and will execute when conditions are met");


	// Add spacer
	alib.dom.createElement("br", main_dv);

	// Start Workflow When
	// ---------------------------------------------------
	var frm2 = new CWindowFrame("Start Workflow When");
	frm2.print(main_dv);
	var start_con = frm2.getCon();

	// Created
	var dv = alib.dom.createElement("div", start_con);
	var chk = alib.dom.createElement("input");
	chk.cls = this;
	chk.type = "checkbox";
	chk.checked = this.g_workflow.fOnCreate;
	chk.onchange = function() { this.cls.g_workflow.fOnCreate = this.checked; }
	dv.appendChild(chk);
	dv.appendChild(document.createTextNode("An object is first created"));
	
	// Updated
	var dv = alib.dom.createElement("div", start_con);
	var chkUpdated = alib.dom.createElement("input");
	chkUpdated.cls = this;
	chkUpdated.type = "checkbox";
	chkUpdated.checked = this.g_workflow.fOnUpdate;
	chkUpdated.onchange = function() { this.cls.g_workflow.fOnUpdate = this.checked; }
	dv.appendChild(chkUpdated);
	dv.appendChild(document.createTextNode("An object is updated/changed (conditions for update will be set later)"));

	// Condition Unmet
    var dv = alib.dom.createElement("div", start_con);
	alib.dom.styleSet(dv, "margin-left", "20px");
    var chk = alib.dom.createElement("input", dv);
    chk.cls = this;
    chk.type = "checkbox";
    chk.checked = this.g_workflow.fConditionUnmet;
	chk.disabled = (this.g_workflow.fOnUpdate) ? false : true;
    chk.onchange = function() {
        this.cls.g_workflow.fConditionUnmet = this.checked;
    }
	alib.dom.createElement("span", dv, "Only if conditions are previously unmet");
	alib.events.listen(chkUpdated, "click", function(evt) {
		evt.data.subCond.disabled = (this.checked) ? false : true;
	}, {subCond:chk});
    
    var imageCon = alib.dom.setElementAttr(alib.dom.createElement("img", dv), [["src", "/images/icons/help_12.png"]]);
    alib.dom.styleSet(imageCon, "cursor", "help");
    alib.dom.styleSet(imageCon, "margin-left", "3px");
    alib.ui.Tooltip(imageCon, "Use this option for launching workflows on the update of an object if and only if the properties have changed to make it match the conditions for this workflow. An example would be sending an email notification the first time a lead is assigned to a user, but after that subsequent updates will not cause notifications to be sent.", true);
	
	// Deleted
	var dv = alib.dom.createElement("div", start_con);
	var chk = alib.dom.createElement("input");
	chk.cls = this;
	chk.type = "checkbox";
	chk.checked = this.g_workflow.fOnDelete;
	chk.onchange = function() { this.cls.g_workflow.fOnDelete = this.checked; }
	dv.appendChild(chk);
	dv.appendChild(document.createTextNode("An object is deleted"));
	
	// Daily
	var dv = alib.dom.createElement("div", start_con);
	var chk = alib.dom.createElement("input");
	chk.cls = this;
	chk.type = "checkbox";
	chk.checked = this.g_workflow.fOnDaily;
	chk.onchange = function() { this.cls.g_workflow.fOnDaily = this.checked; }
	dv.appendChild(chk);
	dv.appendChild(document.createTextNode("Check daily if there are any objects matching the conditions for this workflow. Example use: send email to any customers if their birthday is today."));

	// Options
	// ------------------------------------------------
	var frm3 = new CWindowFrame("Options", null, "3px");
	frm3.print(main_dv);
	var other_con = frm3.getCon();
	
	// Singleton
	var dv = alib.dom.createElement("div", other_con);
	var chk = alib.dom.createElement("input");
	chk.cls = this;
	chk.type = "checkbox";
	chk.checked = this.g_workflow.fSingleton;
	chk.onchange = function() { this.cls.g_workflow.fSingleton= this.checked; }
	dv.appendChild(chk);
	dv.appendChild(document.createTextNode("Only allow one instance of this workflow per object at a time. Make sure longer running and complex workflows do not overlap."));
	
	// Manually Start
	var dv = alib.dom.createElement("div", other_con);
	var chk = alib.dom.createElement("input");
	chk.cls = this;
	chk.type = "checkbox";
	chk.checked = this.g_workflow.fAllowManual;
	chk.onchange = function() { this.cls.g_workflow.fAllowManual = this.checked; }
	dv.appendChild(chk);
	dv.appendChild(document.createTextNode("Allow user to manually start workflow"));
    
    
	
	
}

/*************************************************************************
*	Function:	buildConditions
*
*	Purpose:	Build Conditions step
**************************************************************************/
WorkflowWizard.prototype.buildConditions = function(con)
{
	this.g_antObject = new CAntObject(this.g_workflow.object_type);

	// Describe Conditions
	var p = alib.dom.createElement("p", con);
	alib.dom.styleSetClass(p, "info");
	p.innerHTML = "Use the form below to set any condtions required in order for this workflow to start. For example: if you only want a workflow to launch if a sales opportunity status was changed from 'Open' to 'Lost,' then you would add a condition below ['And' 'Status' 'is equal' 'Lost'] which would make sure the workflow only starts when those conditions are met. You can add multiple conditions if desired. If this workflow should launch on all objects without condition then just continue on to the next step.";

	// Add Condition
	// --------------------------------------
	var dv = alib.dom.createElement("div", con);
	alib.dom.styleSet(dv, "margin", "3px 0px 3px 0px");
	var frm1 = new CWindowFrame("Only start if the following conditions are met", null, "3px");
	var frmcon = frm1.getCon();
	frm1.print(dv);
	this.g_conditions = this.g_antObject.buildAdvancedQuery(frmcon, this.g_workflow.conditions);
}

/*************************************************************************
*	Function:	updateConditions
*
*	Purpose:	Update g_workflow.conditions
**************************************************************************/
WorkflowWizard.prototype.updateConditions = function()
{	
	// Clear old conditions
	this.g_workflow.delConditions();
	
	// Add all conditions
	for(var i = 0; i < this.g_conditions.getNumConditions(); i++)
	{
		var cond = this.g_conditions.getCondition(i);
		this.g_workflow.addCondition(cond.blogic, cond.fieldName, cond.operator, cond.condValue, cond.condId);
	}
}

/*************************************************************************
*	Function:	buildActions
*
*	Purpose:	Build Actions step
**************************************************************************/
WorkflowWizard.prototype.buildActions = function(con)
{
	// Add actions
	// --------------------------------------
	var dv = alib.dom.createElement("div", con);
	alib.dom.styleSet(dv, "margin", "3px 0px 3px 0px");
	var frm1 = new CWindowFrame("Set actions to be performed by this workflow once started");
	var frmcon = frm1.getCon();
	frm1.print(dv);
	var grid = new WorkFlow_ActionsGrid(this.g_workflow);
	grid.parentDlg = this.m_dlg;
	grid.print(frmcon);
}

/*************************************************************************
*	Function:	buildActionsTbl
*
*	Purpose:	Build Actions table
**************************************************************************/
WorkflowWizard.prototype.buildActionsTbl = function(con)
{
	// Purge table of existing actions
	this.g_tblTasks.clear();
	
	// Only add headers once
	if(this.buildActionTbl)
	{
		this.g_tblTasks.addHeader("Name");
		this.g_tblTasks.addHeader("Do");
		this.g_tblTasks.addHeader("When");
		this.g_tblTasks.addHeader("Condition");
		this.g_tblTasks.addHeader("Delete", "center", "50px");
	}
	this.g_tblTasks.print(con);
	this.buildActionTbl = false;	// Don't add headers agian

	// Create Tasks Table
	// --------------------------------------
	for (var i = 0; i < this.g_workflow.getNumTasks(); i++)
	{
		this.addAction(this.g_workflow.getTasks(i));
	}

	// Add Task Drop-down
	// --------------------------------------
	var dv = alib.dom.createElement("div", con);
	alib.dom.styleSet(dv, "padding", "3px");

	var dm = new CDropdownMenu();
	dm.addEntry("Send Email", function (cls) { cls.editAction(null, WF_ATYPE_SENDEMAIL); }, null, null, [this]);
	dm.addEntry("Create Task", function (cls) { cls.editAction(null, WF_ATYPE_CREATEOBJ, "task"); }, null, null, [this]);
	dm.addEntry("Create Invoice", function (cls) { cls.editAction(null, WF_ATYPE_CREATEOBJ, "invoice"); }, null, null, [this]);
	dm.addEntry("Update Field", function (cls) { cls.editAction(null, WF_ATYPE_UPDATEFLD); }, null, null, [this]);
	dm.addEntry("Start Child Workflow", function (cls) { cls.editAction(null, WF_ATYPE_STARTCHLD); }, null, null, [this]);
	dv.appendChild(dm.createButtonMenu("Add Action"));
}

/*************************************************************************
*	Function:	addAction
*
*	Purpose:	Add an action
**************************************************************************/
WorkflowWizard.prototype.addAction = function(act)
{
	var rw = this.g_tblTasks.addRow();
	var a = alib.dom.createElement("a");
	a.href = "javascript:void(0);";	
	a.act = act;
	a.cls = this;
	a.onclick = function()
	{
		this.cls.editAction(this.act, this.act.type, this.act.create_obj);
	}
	a.innerHTML = act.name;
	rw.addCell(a);
	rw.addCell(act.getTypeDesc());
	rw.addCell(act.getWhenDesc());
	rw.addCell(act.getCondDesc());

	var del_dv = alib.dom.createElement("div");
	rw.addCell(del_dv, true, "center");
	del_dv.innerHTML = "<img border='0' src='/images/themes/" + this.g_theme + "/icons/deleteTask.gif' />";
	alib.dom.styleSet(del_dv, "cursor", "pointer");
	del_dv.cls = this;
	del_dv.m_rw = rw;
	del_dv.m_id = act.id;
	del_dv.onclick = function()
	{
		var dlg = new CDialog("Remove Action", this.cls.m_dlg);
		var dv = alib.dom.createElement("div");
		dlg.customDialog(dv, 260, 40);

		var lbl = alib.dom.createElement("div", dv);
		alib.dom.styleSet(lbl, "text-align", "center");
		lbl.innerHTML = "Are you sure you want to remove this action?";
		dv.appendChild(lbl);
		
		var btn_dv = alib.dom.createElement("div", dv);
		alib.dom.styleSet(btn_dv, "text-align", "right");
		var btn = new CButton("Yes", 
		function(dlg, cls, row, id) 
		{
			dlg.hide();
			row.deleteRow();
			cls.g_workflow.delActionById(id);
		}, [dlg, this.cls, this.m_rw, this.m_id], "b1");
		btn.print(btn_dv);
		var btn = new CButton("No", function(dlg) { dlg.hide(); }, [dlg], "b1");
		btn.print(btn_dv);
	}
}

/*************************************************************************
*	Function:	editAction
*
*	Purpose:	Edit an action
**************************************************************************/
WorkflowWizard.prototype.editAction = function(act, type, subtype)
{
	if (act)
		var task_obj = act;
	else
		var task_obj = new WorkFlow_Action(g_workflow);

	var lbl = (act) ? "Edit Action" : "Create New Action";
	var g_taskDlg = new CDialog(lbl, this.m_dlg);
	g_taskDlg.f_close = true;
	var dv = alib.dom.createElement("div");
	g_taskDlg.customDialog(dv, 560, 500);

	var tbl = alib.dom.createElement("table", dv);
	var tbody = alib.dom.createElement("tbody", tbl);

	// Title
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Name";
	var td = alib.dom.createElement("td", row);
	var txtTaskTitle = alib.dom.createElement("input", td);
	txtTaskTitle.m_task_obj = task_obj;
	txtTaskTitle.value = task_obj.name;
	txtTaskTitle.onchange = function() { this.m_task_obj.name = this.value; };
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = "&nbsp;give this action a unique name";

	// Timeframe
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "When";
	var td = alib.dom.createElement("td", row);

	var txtWhenInterval = alib.dom.createElement("input", td);
	alib.dom.styleSet(txtWhenInterval, "width", "14px");
	txtWhenInterval.m_task_obj = task_obj;
	txtWhenInterval.value=task_obj.when.interval;
	txtWhenInterval.onchange = function() { this.m_task_obj.when.interval = this.value; };

	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = "&nbsp;";

	var cbWhenUnit = alib.dom.createElement("select", td);
	var time_units = wfGetTimeUnits();
	for (var i = 0; i < time_units.length; i++)
	{
		cbWhenUnit[cbWhenUnit.length] = new Option(time_units[i][1], time_units[i][0], false, (task_obj.when.unit==time_units[i][0])?true:false);
	}
	cbWhenUnit.m_task_obj = task_obj;
	cbWhenUnit.onchange = function() { this.m_task_obj.when.unit = this.value; };

	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = " after workflow starts (enter 0 for immediate)";

	// Conditions
	// --------------------------------------------
	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "formLabel");
	alib.dom.styleSet(lbl, "margin", "6px 0px 3px 3px");
	lbl.innerHTML = "If the following conditions are met:";
	var dv_cnd = alib.dom.createElement("div", dv);
	var tmpAntObj = new CAntObject(this.g_workflow.object_type);
	var condObj = tmpAntObj.buildAdvancedQuery(dv_cnd, task_obj.conditions);

	// Do
	// --------------------------------------------
	switch (type)
	{
	case WF_ATYPE_SENDEMAIL:
		var email_action = new WorkFlow_Action_Email(this.g_workflow.object_type);
		email_action.taskSendEmail(dv, task_obj);
		break;
	case WF_ATYPE_UPDATEFLD:
		this.actUpdateField(dv, task_obj);
		break;
	case WF_ATYPE_STARTCHLD:
		this.actChildWf(dv, task_obj);
		break;
	default: // Create new
		if (subtype == "task")
		{
			var task_action = new WorkFlow_Action_Task(this.g_workflow.object_type)
			task_action.taskCreateTask(dv, task_obj);
		}
		if (subtype == "invoice")
		{
			var task_invoice = new WorkFlow_Action_Invoice(this.g_workflow.object_type);
			task_invoice.taskCreateInvoice(dv, task_obj);
		}
	}
	
	// Buttons
	// --------------------------------------------
	var bntbar = alib.dom.createElement("div", dv);
	alib.dom.styleSet(bntbar, "margin", "6px 0px 3px 3px");
	
	var btn = new CButton("Save", 
	function(cls, dlg, task_obj, condObj, isnew) 
	{
		dlg.hide();
		cls.saveTask(task_obj, condObj, isnew);
	}, [this, g_taskDlg, task_obj, condObj, (act)?false:true], "b2");
	btn.print(bntbar);
	var btn = new CButton("Cancel", function(dlg) { dlg.hide(); }, [g_taskDlg], "b1");
	btn.print(bntbar);
	
}

/*************************************************************************
*	Function:	actUpdateField
*
*	Purpose:	Update action field
**************************************************************************/
WorkflowWizard.prototype.actUpdateField = function(con, task_obj)
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

	var tmpAntObj = new CAntObject(this.g_workflow.object_type);
	sel_fields.m_task_obj = task_obj;
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
				this.m_task_obj.frm_changeto = ref_obj.fieldCreateValueInput(this.m_inp_con, parts[2]);
			}
		}
		else
		{
			this.m_task_obj.frm_changeto = this.m_ant_obj.fieldCreateValueInput(this.m_inp_con, this.value);
		}

		this.m_task_obj.update_field = this.value;
	}

	var fields = tmpAntObj.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		sel_fields[sel_fields.length] = new Option(fields[i].title, fields[i].name, false, (task_obj.update_field == fields[i].name)?true:false);
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
																val, false, (task_obj.update_field == val)?true:false);
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
																		val, false, (task_obj.update_field == val)?true:false);
						}
					}
				}
				//sel_fields[sel_fields.length] = new Option(fields[i].title, fields[i].name, false, (task_obj.update_field == fields[i].name)?true:false);
			}
		}

	}

	var fld = (task_obj.update_field) ? task_obj.update_field : fields[0].name
	task_obj.frm_changeto = tmpAntObj.fieldCreateValueInput(inp_con, fld, task_obj.update_to);
}

/*************************************************************************
*	Function:	actChildWf
*
*	Purpose:	Workflow action child
**************************************************************************/
WorkflowWizard.prototype.actChildWf = function(con, task_obj)
{
	task_obj.type = WF_ATYPE_STARTCHLD;

	var lbl = alib.dom.createElement("div", con);
	alib.dom.styleSetClass(lbl, "formLabel");
	alib.dom.styleSet(lbl, "margin", "6px 0px 3px 3px");
	lbl.innerHTML = "Select a child workflow to launch:";

	var frm_dv = alib.dom.createElement("div", con);
	var workflows = alib.dom.createElement("select", frm_dv);
	workflows.size = 20;
	workflows.style.width = "98%";
	workflows.task_obj = task_obj;
	workflows.onchange = function()
	{
		this.task_obj.start_wfid = this.value;
	}

	// Set: start_wfid
	var ajax = new CAjax();
	ajax.workflows = workflows;
	ajax.task_obj = task_obj;
	ajax.onload = function(ret)
	{
        if (ret.length)
        {
            for (workflow in ret)
            {
                var currentWorkflow = ret[workflow];
                
                var name = currentWorkflow.name;
                var id = currentWorkflow.id;
                var act = currentWorkflow.f_active;
                
                if (act == 't')
                    this.workflows[this.workflows.length] = new Option(name, id, false, (id == this.task_obj.start_wfid)?true:false);
            }
        }
	};
	
	var args = new Array();
    args[args.length] = ['otypes', this.g_workflow.object_type];
    ajax.exec("/controller/Workflow/getWorkflow", args);
}

/*************************************************************************
*	Function:	saveTask
*
*	Purpose:	Save task
**************************************************************************/
WorkflowWizard.prototype.saveTask = function(task_obj, condObj, isnew)
{
	// Purge and set conditions
	task_obj.delConditions();

	for (var i = 0; i < condObj.getNumConditions(); i++)
	{
		var cond = condObj.getCondition(i);
		task_obj.addCondition(cond.blogic, cond.fieldName, cond.operator, cond.condValue);
	}

	if (task_obj.frm_changeto)
		task_obj.update_to = task_obj.frm_changeto.value;

	if (isnew)
	{
		this.addAction(task_obj);
		this.g_workflow.addAction(task_obj);
	}
}

/*************************************************************************
*	Function:	getObjects
*
*	Purpose:	Populates g_objTypes array
**************************************************************************/
WorkflowWizard.prototype.getObjects = function()
{
    if(this.g_objTypes)
        return;
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(!ret)
            ALib.statusShowAlert("Error occured while loading objects!", 5000, "bottom", "right");
        else
            this.cbData.cls.g_objTypes = ret;
    };
    ajax.exec("/controller/Object/getObjects");
}

/*************************************************************************
*	Function:	cancel
*
*	Purpose:	Close the wizard
**************************************************************************/
WorkflowWizard.prototype.cancel = function()
{
	var dlg = new CDialog("Close Workflow", this.m_dlg);
	var dv = alib.dom.createElement("div");
	dlg.customDialog(dv, 260);

	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSet(lbl, "text-align", "center");
	lbl.innerHTML = "Are you sure you want to close this workflow?";
	dv.appendChild(lbl);
	
	var btn_dv = alib.dom.createElement("div", dv);
	alib.dom.styleSet(btn_dv, "text-align", "right");
	var btn = new CButton("Yes", 
	function(cls, dlg) { dlg.hide(); cls.m_dlg.hide(); }, [this, dlg], "b1");
	btn.print(btn_dv);
	var btn = new CButton("No", function(dlg) { dlg.hide(); }, [dlg], "b1");
	btn.print(btn_dv);
}

/*************************************************************************
*	Function:	save
*
*	Purpose:	Save workflow
**************************************************************************/
WorkflowWizard.prototype.save = function()
{
	// Create loading div
	var dlg = new CDialog();
	dlg.parentDlg = this.m_dlg;
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Saving, please wait...";
	dlg.statusDialog(dv_load, 150, 100);

	this.g_workflow.dlg = dlg;
	this.g_workflow.cls = this;
	this.g_workflow.onsave = function()
	{
		this.dlg.hide();
		ALib.statusShowAlert("Workflow Saved!", 3000, "bottom", "right");
		
		// Close wizard
		this.cls.m_dlg.hide();
        this.cls.onsave();        
	}
	this.g_workflow.onsaveError = function()
	{
		this.dlg.hide();
		ALib.statusShowAlert("ERROR SAVING WORKFLOW!", 3000, "bottom", "right");
	}
	this.g_workflow.save();
}

WorkflowWizard.prototype.onsave = function()
{
    // This function exists to be defined before save is called above
}
