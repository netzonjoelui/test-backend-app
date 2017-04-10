/**
* @fileOverview WorkFlow_ActionsGrid
*
* The actions grid creates a table of actions for a given
* workflow and optional parent action. The grid can be nested
* as actions can have an unlimited number of child actions/grids
*
* @author:	joe, sky.stebnicki@aereus.com; 
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
 * Creates an instance of WorkFlow_ActionsGrid
 *
 * @constructor
 * @this {WorkFlow_ActionsGrid}
 * @param {WorkFlow} wf Required handle to a workflow object
 * @param {WorkFlow_Action} parentAction Optional parent action
 */
function WorkFlow_ActionsGrid(wf, parentAction)
{
	/**
	* The workflow ID to pull actions for
	*
	* @private
	* @type {WorkFlow}
	*/
    this.workflow = wf;	

	/**
	* Optional parent action used for nesting grids
	*
	* @private
	* @type {WorkFlow_Action}
	*/
    this.parentAction = (parentAction) ? parentAction : null;

	/**
	* Optional parent action event
	*
	* @private
	* @type {WorkFlow_Action}
	*/
    this.parentActionEvent = null;

	/**
	* The container for all grid actions
	*
	* @private
	* @type {DOMElement}
	*/
    this.gridCon = null;

	/**
	* Optional parent dialog
	*
	* @public
	* @type {CDialog}
	*/
    this.parentDlg = null;
}

/**
 * Print the grid inside a dom element/container
 *
 * @public
 * @this {WorkFlow_ActionsGrid}
 * @return {DOMElement} con The container that will be used to house the grid
 */
WorkFlow_ActionsGrid.prototype.print = function(con)
{
	// Create container for the actions grid
	this.gridCon = alib.dom.createElement("div", con);

	// Create container for 'add action' bar
	var addActCon = alib.dom.createElement("div", con);
	alib.dom.styleSet(addActCon, "padding", "3px");
	var addActLnk = alib.dom.createElement("a", addActCon, (this.parentAction == null) ? "Add Action" : "Add Sub-Action");
	addActLnk.href = "javascript:void(0);";
	
	var menuAct = new alib.ui.PopupMenu();

	// Send Email
	var item = new alib.ui.MenuItem("Send Email", {cls:this});
	item.onclick = function() { this.options.cls.newAction(WF_ATYPE_SENDEMAIL); }
	menuAct.addItem(item);

	// Send Notification
	var item = new alib.ui.MenuItem("Send Notification", {cls:this});
	item.onclick = function() { this.options.cls.newAction(WF_ATYPE_CREATEOBJ, "notification"); }
	menuAct.addItem(item);

	// Create Task
	var item = new alib.ui.MenuItem("Create Task", {cls:this});
	item.onclick = function() { this.options.cls.newAction(WF_ATYPE_CREATEOBJ, "task"); }
	menuAct.addItem(item);

	// Create Invoice
	var item = new alib.ui.MenuItem("Create Invoice", {cls:this});
	item.onclick = function() { this.options.cls.newAction(WF_ATYPE_CREATEOBJ, "invoice"); }
	menuAct.addItem(item);

	// Update Field
	var item = new alib.ui.MenuItem("Update Field", {cls:this});
	item.onclick = function() { this.options.cls.newAction(WF_ATYPE_UPDATEFLD); }
	menuAct.addItem(item);

	// Start Child Workflow
	var item = new alib.ui.MenuItem("Start Child Workflow", {cls:this});
	item.onclick = function() { this.options.cls.newAction(WF_ATYPE_STARTCHLD); }
	menuAct.addItem(item);

	// Request Approval
	var item = new alib.ui.MenuItem("Request Approval", {cls:this});
	item.onclick = function() { this.options.cls.newAction(WF_ATYPE_APPROVAL); }
	menuAct.addItem(item);

	// Call Page
	var item = new alib.ui.MenuItem("Call Page", {cls:this});
	item.onclick = function() { this.options.cls.newAction(WF_ATYPE_CALLPAGE); }
	menuAct.addItem(item);

	// Round Robin
	var item = new alib.ui.MenuItem("Assign Round Robin", {cls:this});
	item.onclick = function() { this.options.cls.newAction(WF_ATYPE_ASSIGNRR); }
	menuAct.addItem(item);

	// Wait Condition
	var item = new alib.ui.MenuItem("Wait Condition", {cls:this});
	item.onclick = function() { this.options.cls.newAction(WF_ATYPE_WAITCONDITION); }
	menuAct.addItem(item);

	// Check Condition
	var item = new alib.ui.MenuItem("Check Condition", {cls:this});
	item.onclick = function() { this.options.cls.newAction(WF_ATYPE_CHECKCONDITION); }
	menuAct.addItem(item);

	menuAct.attach(addActLnk);

	/*
	var dm = new CDropdownMenu();
	dm.addEntry("Send Email", function (cls) { cls.newAction(WF_ATYPE_SENDEMAIL); }, null, null, [this]);
	dm.addEntry("Send Notification", function (cls) { cls.newAction(WF_ATYPE_CREATEOBJ, "notification"); }, null, null, [this]);
	dm.addEntry("Create Task", function (cls) { cls.newAction(WF_ATYPE_CREATEOBJ, "task"); }, null, null, [this]);
	dm.addEntry("Create Invoice", function (cls) { cls.newAction(WF_ATYPE_CREATEOBJ, "invoice"); }, null, null, [this]);
	dm.addEntry("Update Field", function (cls) { cls.newAction(WF_ATYPE_UPDATEFLD); }, null, null, [this]);
	dm.addEntry("Start Child Workflow", function (cls) { cls.newAction(WF_ATYPE_STARTCHLD); }, null, null, [this]);
    dm.addEntry("Request Approval", function (cls) { cls.newAction(WF_ATYPE_APPROVAL); }, null, null, [this]);
	dm.addEntry("Call Page", function (cls) { cls.newAction(WF_ATYPE_CALLPAGE); }, null, null, [this]);
	addActCon.appendChild(dm.createLinkMenu("Add Action"));
	*/

	this.loadActions();
}

/**
 * Build actions grid by looping through all actions in the workflow
 *
 * @public
 * @this {WorkFlow_ActionsGrid}
 */
WorkFlow_ActionsGrid.prototype.loadActions = function()
{
	if (this.parentAction)
	{
		for (var i =0; i < this.parentAction.childActions.length; i++)
		{
			var fAdd = true;
			if (this.parentActionEvent)
			{
				if (this.parentActionEvent != this.parentAction.childActions[i].parentActionEvent)
					fAdd = false;
			}
			else if (this.parentAction.childActions[i].parentActionEvent)
				fAdd = false;

			if (fAdd)
				this.insertActionRow(this.parentAction.childActions[i]);
		}
	}
	else if (this.workflow)
	{
		for (var i =0; i < this.workflow.actions.length; i++)
		{
			this.insertActionRow(this.workflow.actions[i]);
		}
	}
}

/**
 * Add a new action
 *
 * @public
 * @this {WorkFlow_ActionsGrid}
 * @param {number} type Globally defined WF_ATYPE_*
 */
WorkFlow_ActionsGrid.prototype.newAction = function(type, subtype)
{
	if (this.parentAction)
	{
		var act = this.parentAction.addAction(type);
	}
	else if (this.workflow)
	{
		var act = this.workflow.addAction(type);
	}
	else
		return false;

	if (subtype)
		act.create_obj = subtype;

	if (this.parentActionEvent)
		act.parentActionEvent = this.parentActionEvent;

	act.cbData.actGrid = this;

	// If 'OK' is clicked, then make sure we insert the action row
	act.onupdate = function()
	{
		this.cbData.actGrid.insertActionRow(this);
	}
	
	// If 'cancel' is clicked then do nothing
	act.showDialog(this.parentDlg);
}

/**
 * Edit an existing action
 *
 * @public
 * @this {WorkFlow_ActionsGrid}
 * @param {WorkFlow_Action} act Handle to action class
 * @param {DOMElement} descCon The DOM element containing the description of edited action
 */
WorkFlow_ActionsGrid.prototype.editAction = function(act, descCon)
{
	act.showDialog(this.parentDlg);

	// On finished update grid display
	act.cbData.descCon = descCon;
	act.onupdate = function()
	{        
		this.cbData.descCon.innerHTML = this.getSummary();
	}
}

/**
 * Insert into action row into the grid
 *
 * @public
 * @this {WorkFlow_ActionsGrid}
 * @param {WorkFlow_Action} act Handle to action class
 */
WorkFlow_ActionsGrid.prototype.insertActionRow = function(act)
{
	var actCon = alib.dom.createElement("div", this.gridCon);
	alib.dom.styleSet(actCon, "margin-bottom", "5px");
	alib.dom.styleSet(actCon, "padding", "3px 0 3px 3px");
	alib.dom.styleSet(actCon, "border-bottom", "1px solid #ccc");
	actCon.myAct = act;

	var iconCon = alib.dom.createElement("img", actCon);
	iconCon.src = "/images/icons/tri.png";
	alib.dom.styleSet(iconCon, "margin-right", "3px");

	var descCon = alib.dom.createElement("span", actCon);
	descCon.innerHTML = act.getSummary();
	alib.dom.styleSet(descCon, "cursor", "pointer");
	alib.dom.styleSet(descCon, "padding-right", "10px");
	alib.dom.styleSetClass(descCon, "strong");
	descCon.act = act;
	descCon.wfagcls = this;
	descCon.onclick = function()
	{
		this.wfagcls.editAction(this.act, this);
	}

	var remLink = alib.dom.createElement("a", actCon);
	remLink.innerHTML = "<img src='/images/icons/delete_10.png' />";
	remLink.href = "javascript:void(0);";
	remLink.wfagcls = this;
	remLink.act = act;
	remLink.actCon = actCon;
	remLink.onclick = function()
	{
		this.wfagcls.removeAction(this.act, this.actCon);
	}

	// Now create child grid for this new action
	var subActEvents = act.getSubActionEvents();
	for (var i = 0; i < subActEvents.length; i++)
	{
		var actConGrid = alib.dom.createElement("div", actCon); 

		alib.dom.styleSet(actConGrid, "padding-left", "15px");

		// Create label
		var lbl = alib.dom.createElement("div", actConGrid);
		lbl.innerHTML = subActEvents[i].description;
		alib.dom.styleSetClass(lbl, "italic");

		var grid = new WorkFlow_ActionsGrid(this.workflow, act);
		grid.parentActionEvent = subActEvents[i].name;
		grid.print(actConGrid);
	}
}

/**
 * Remove action including all children
 *
 * @public
 * @this {WorkFlow_ActionsGrid}
 * @param {WorkFlow_Action} act Handle to action class
 */
WorkFlow_ActionsGrid.prototype.removeAction = function(act, con)
{
	// Delete action
	if (this.parentAction)
	{
		this.parentAction.removeAction(act);
	}
	else if (this.workflow)
	{
		this.workflow.removeAction(act);
	}
	else
	{
		act.remove();
	}

	// Remove DOM element from the grid
	this.gridCon.removeChild(con);
}

