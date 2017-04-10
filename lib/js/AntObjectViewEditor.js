/**
 * @fileoverview This class handles editing object list views
 *
 * Example:
 * <code>
 * 	var ed = new AntObjectViewEditor("customer");
 * 	ed.hideApply = true; // Can be used to omit the 'Apply' button if we are editing a view outside the context of a browser
 * 	ed.onApply = function() { }		// if view is applied then this can be used to run the query in a browser
 * 	ed.onSave = function() { }		// fired if changes to a view are saved
 * 	ed.onCancel = function() { }	// fired if nothing is changed
 *	ed.showDialog(parentDialog);
 * </code>
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011-2012 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObjectViewEditor
 *
 * @constructor
 * @param {string} obj_type The name of the object type to load
 */
 
// DEFINE CONSTANT VARIABLES 
var SCOPE_EVERYONE      = 0;
var SCOPE_TEAM          = 1;
var SCOPE_ME            = 2;
var SCOPE_USER          = 3;
 
function AntObjectViewEditor(obj_type, view)
{
	/**
	 * Instance of CAntObject of type obj_type - loads object data if oid is defined
	 *
	 * @type {CAntObject}
	 * @public
	 */
	this.mainObject = new CAntObject(obj_type);

	/**
	 * If editing a view then this param will be set
	 *
	 * @public
	 * @var {AntObjectBrowserView}
	 */
	this.view = (view) ? view : new AntObjectBrowserView(obj_type);

	/**
	 * Buffer used for making edits
	 *
	 * @public
	 * @var {AntObjectBrowserView}
	 */
	this.viewBuf = new AntObjectBrowserView(obj_type);

	/**
	 * Container object used by outside classes to store callback properties
	 *
	 * @public
	 * @var {Object}
	 */
	this.cbData = new Object();

	/**
	 * The dialog for this editor
	 *
	 * @public
	 * @var {CDialog}
	 */
	this.dlg  = null;

	/**
	 * The div container for the editor
	 *
	 * @public
	 * @var {DOMElement}
	 */
	this.con = null;

	/**
	 * The div container for the view form
	 *
	 * @public
	 * @var {DOMElement}
	 */
	this.formCon = null;

	/**
	 * The div container for the save form
	 *
	 * @public
	 * @var {DOMElement}
	 */
	this.saveCon = null;
    
    /**
     * Determine if Class was loaded by the editor
     *
     * @public
     * @var {Boolean}
     */
    this.fEditor = false;
    
    /**
     * Will be set if the view is created for another user
     *
     * @public
     * @var {Integer}
     */
    this.user_id = null;
    
    /**
     * Will be set if the view is created for another user
     *
     * @public
     * @var {string}
     */
    this.user_name = null;
    
    /**
     * Will be used once view is saved. This will have the new View Id.
     *
     * @public
     * @var {Integer}
     */
    this.viewId = null;
}

/**
 * Close the dialog
 */
AntObjectViewEditor.prototype.close = function() 
{
	this.dlg.hide();
}

/**
 * Callback fired when changes to a view have been saved
 */
AntObjectViewEditor.prototype.onSave = function() 
{    
}

/**
 * Callback fired when a user cancels and closes the dialog
 */
AntObjectViewEditor.prototype.onCancel = function() 
{    
}

/**
 * Callback fired when a user applies the view - usually for browsers
 *
 * @param {AntObjectBrowserView} view The edited view object
 */
AntObjectViewEditor.prototype.onApply = function(view) 
{    
}

/**
 * Display the view editor dialog
 *
 * @public
 */
AntObjectViewEditor.prototype.showDialog = function(parentDialog)
{
	this.dlg = new CDialog("Advanced Filter");
    this.dlg.parentDlg = parentDialog;
	this.con = alib.dom.createElement("div");
	this.dlg.customDialog(this.con, 650);
	this.showViewForm();
	this.dlg.reposition();
}

/**
 * Build the interface
 *
 * @private
 */
AntObjectViewEditor.prototype.showViewForm = function()
{
    var viewName = "";
    if(this.view.name)
        viewName = " - " + this.view.name;
        
    this.dlg.setTitle("Advanced Search" + viewName);

	// Check if view con has already been created and toggle
	if (this.viewCon)
	{
		if (this.saveCon)
			alib.dom.styleSet(this.saveCon, "display", "none");

		alib.dom.styleSet(this.viewCon, "display", "block");
		return;
	}

	this.viewCon = alib.dom.createElement("div", this.con);
	var title = (this.view) ? "Edit: " + this.view.name : "New View Builder";
	
    var wfcon = alib.dom.createElement("div", this.viewCon);
    var loadingCon = alib.dom.createElement("div", this.viewCon);
    loadingCon.innerHTML = "<div class='loading'></div>";
    
	alib.dom.styleSet(wfcon, "height", "330px");
    alib.dom.styleSet(wfcon, "overflow", "auto");
	alib.dom.styleSet(wfcon, "display", "none");

	// Conditions
	this.conditionObj = null;
	this.mainObject.clearConditions();
	
	var options = new Object();
	options.parent_dlg = this.dlg;
    options.parent_dlg.reposition();
    
    var lbl = alib.dom.createElement("div", wfcon);
    alib.dom.styleSetClass(lbl, "strong");
    lbl.innerHTML = "Search Conditions:";
    
    for(cond in this.view.conditions)
    {
        var currentCond = this.view.conditions[cond];
        if(typeof currentCond.condValue == "undefined")
            currentCond.condValue = currentCond.value;
        
    }
    
	this.conditionObj = this.mainObject.buildAdvancedQuery(wfcon, this.view.conditions, options);
    
    loadingCon.innerHTML = "";
    alib.dom.styleSet(wfcon, "display", "block");
    
	var order_div = alib.dom.createElement("div", wfcon);
	alib.dom.styleSet(order_div, "margin-top", "8px");
	var lbl = alib.dom.createElement("div", order_div);
	alib.dom.styleSetClass(lbl, "strong");
	lbl.innerHTML = "Sort By:";
	var a_order = alib.dom.createElement("a", wfcon);
	a_order.href = "javascript:void(0);";
	a_order.innerHTML = "Add Sort Order";
	a_order.cls = this;
	a_order.order_div = order_div;
	a_order.onclick = function() { this.cls.addOrderBy(this.order_div); }
	for (var i = 0; i < this.view.sort_order.length; i++)
		this.addOrderBy(order_div, this.view.sort_order[i].fieldName, this.view.sort_order[i].order);

	var order_div = alib.dom.createElement("div", wfcon);
	alib.dom.styleSet(order_div, "margin-top", "8px");
	var lbl = alib.dom.createElement("div", order_div);
	alib.dom.styleSetClass(lbl, "strong");
	lbl.innerHTML = "View Columns:";
	var a_order = alib.dom.createElement("a", wfcon);
	a_order.href = "javascript:void(0);";
	a_order.innerHTML = "Add Field";
	a_order.cls = this;
	a_order.order_div = order_div;
	a_order.onclick = function() { this.cls.addViewColumn(this.order_div); }
	for (var i = 0; i < this.view.view_fields.length; i++)
    {
        var fieldName = this.view.view_fields[i].fieldName;
        
        if(typeof fieldName == "undefined")
            fieldName = this.view.view_fields[i];
        
        this.addViewColumn(order_div, fieldName);
    }
    
    this.buttonCon = alib.dom.createElement("div", this.viewCon);
    this.createButtons();
}

/**
 * Create dialog buttons
 *
 * @param {AntObjectBrowserView} view The edited view object
 */
AntObjectViewEditor.prototype.createButtons = function()
{
    // Add buttons
    // -------------------------------------------
    this.buttonCon.innerHTML = "";
    var button_div = alib.dom.createElement("div", this.buttonCon);
    alib.dom.styleSet(button_div, "margin-top", "8px");

    if(!this.fEditor)
    {
        // Apply button
        var btn = alib.ui.Button("Apply", {
            className:"b2", tooltip:"Close and view results", cls:this, 
            onclick:function() { this.cls.apply(); }
        });
        btn.print(button_div);
    }

    // Cancel button
    var btn = alib.ui.Button("Cancel", {
        className:"b1", tooltip:"Cancel and close form", cls:this, 
        onclick:function() { this.cls.close(); this.cls.onCancel(); }
    });
    btn.print(button_div);

    var spacer = alib.dom.createElement("span", button_div);
    spacer.innerHTML = "&nbsp;|&nbsp;&nbsp;";

    if (this.view.id)
    {
        if (!this.view.fSystem)
        {
            var btn = alib.ui.Button("Save Changes", {
                className:"b1", tooltip:"Save changes to this view", cls:this, 
                onclick:function() { this.cls.showSaveForm(); }
            });
            btn.print(button_div);
        }

        // Save new view
        var btn = alib.ui.Button("Save As New View", {
            className:"b1", tooltip:"Create a new view using these settings", cls:this, 
            onclick:function() { this.cls.showSaveForm(true); }
        });
        btn.print(button_div);
    }
    else
    {
        var btn = alib.ui.Button("Save View", {
            className:"b1", tooltip:"Create a new view using these settings", cls:this, 
            onclick:function() { this.cls.showSaveForm(); }
        });
        btn.print(button_div);
    }
}

/**
 * All the view buffer to the actual view for saving
 *
 * @param {AntObjectBrowserView} view The edited view object
 */
AntObjectViewEditor.prototype.setViewFromBuf = function(view) 
{
    this.viewBuf.clearConditions();
    for (var i = 0; i < this.conditionObj.getNumConditions(); i++)
    {
        var cond = this.conditionObj.getCondition(i);
        this.viewBuf.addCondition(cond.blogic, cond.fieldName, cond.operator, cond.condValue);
    }
        
    if(typeof view == "object")
        view.copyView(this.viewBuf);
    else if(typeof this.view.copyView == 'function')
        this.view.copyView(this.viewBuf);
    else
    {
        var newView = new AntObjectBrowserView(this.mainObject.name);
        this.newView.copyView(this.viewBuf);
        return newView;
    }
}

/**
 * Callback fired when a user applies the view - usually for browsers
 *
 * @param {AntObjectBrowserView} view The edited view object
 */
AntObjectViewEditor.prototype.apply = function() 
{
	for (var i = 0; i < this.conditionObj.getNumConditions(); i++)
	{
		var cond = this.conditionObj.getCondition(i);
		this.viewBuf.addCondition(cond.blogic, cond.fieldName, cond.operator, cond.condValue);
	}

	this.close(); 
	this.onApply(this.viewBuf);
}

/**
 * Add an order by row to the form
 *
 * @private
 * @param {DOMElement} con The container to print the row into
 * @param {string} fieldName The name of the field we are adding
 * @param {string} order Either "asc" for ascending or "desc" for descending
 */
AntObjectViewEditor.prototype.addOrderBy = function(con, fieldName, order)
{
	var sel_field = (fieldName) ? fieldName : "";
	var sel_order = (order) ? order : "asc";

	if (typeof this.orderBySerial == "undefined")
		this.orderBySerial = 1;
	else
		this.orderBySerial++;

	var dv = alib.dom.createElement("div", con);

	if (this.viewBuf.sort_order.length)
	{
		var lbl = alib.dom.createElement("span", dv);
		lbl.innerHTML = "Then By: ";
	}

	var ind = this.viewBuf.sort_order.length;
	this.viewBuf.sort_order[ind] = new Object();
	this.viewBuf.sort_order[ind].id = this.orderBySerial;
	this.viewBuf.sort_order[ind].fieldName = sel_field;
	this.viewBuf.sort_order[ind].order = sel_order;

	// Add field name
	var field_sel = alib.dom.createElement("select", dv);
	field_sel.orderobj = this.viewBuf.sort_order[ind];
	field_sel.onchange = function() { this.orderobj.fieldName = this.value; };
	var fields = this.mainObject.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		if (fields[i].type != "fkey_multi")
			field_sel[field_sel.length] = new Option(fields[i].title, fields[i].name, false, (sel_field==fields[i].name)?true:false);
	}

	if (!this.viewBuf.sort_order[ind].fieldName)
		this.viewBuf.sort_order[ind].fieldName = field_sel.value;

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " ";
	
	// Add order (asc/desc)
	var order_sel = alib.dom.createElement("select", dv);
	order_sel.orderobj = this.viewBuf.sort_order[ind];
	order_sel.onchange = function() { this.orderobj.order = this.value; };
	order_sel[order_sel.length] = new Option("Ascending", "asc", false, (sel_order == "asc")?true:false);
	order_sel[order_sel.length] = new Option("Descending", "desc", false, (sel_order == "desc")?true:false);

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " ";

	var icon = (typeof(Ant)=='undefined') ? "/images/icons/deleteTask.gif" : "/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif";
	var del = alib.dom.createElement("a", dv);
	del.innerHTML = "<img src='"+icon+"' border='0' />";
	del.href = "javascript:void(0)";
	del.odv = dv;
	del.pdv = con;
	del.cls = this;
	del.orderid = this.orderBySerial;
	del.onclick = function() 
	{ 
		for (var i = 0; i < this.cls.viewBuf.sort_order.length; i++)
		{
			if (this.cls.viewBuf.sort_order[i].id == this.orderid)
				this.cls.viewBuf.sort_order.splice(i, 1);
		}

		this.pdv.removeChild(this.odv); 
	} 
}

/**
 * Add view column entry to to the form
 *
 * @private
 * @param {DOMElement} con The container to print this row into
 * @param {string} field_name The name of the field to use for this column
 */
AntObjectViewEditor.prototype.addViewColumn = function(con, field_name)
{
	var selected_field = (field_name) ? field_name : "";

	if (typeof this.viewCOlSerial == "undefined")
		this.viewCOlSerial = 1;
	else
		this.viewCOlSerial++;

	var dv = alib.dom.createElement("div", con);

	var ind = this.viewBuf.view_fields.length;
	this.viewBuf.view_fields[ind] = new Object();
	this.viewBuf.view_fields[ind].id = this.viewCOlSerial;
	this.viewBuf.view_fields[ind].fieldName = selected_field;

	// Add field name
	var field_sel = alib.dom.createElement("select", dv);
	field_sel.viewobj = this.viewBuf.view_fields[ind];
	field_sel.onchange = function() { this.viewobj.fieldName = this.value; };
	var fields = this.mainObject.getFields();
	for (var i = 0; i < fields.length; i++)
	{
		field_sel[field_sel.length] = new Option(fields[i].title, fields[i].name, false, (fields[i].name == selected_field)?true:false);
	}

	if (!this.viewBuf.view_fields[ind].fieldName)
		this.viewBuf.view_fields[ind].fieldName = field_sel.value;

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " ";

	var icon = (typeof(Ant)=='undefined') ? "/images/icons/deleteTask.gif" : "/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif";
	var del = alib.dom.createElement("a", dv);
	del.innerHTML = "<img src='"+icon+"' border='0' />";
	del.href = "javascript:void(0)";
	del.odv = dv;
	del.pdv = con;
	del.cls = this;
	del.viewid = this.viewCOlSerial;
	del.onclick = function() 
	{ 
		for (var i = 0; i < this.cls.viewBuf.view_fields.length; i++)
		{
			if (this.cls.viewBuf.view_fields[i].id == this.viewid)
				this.cls.viewBuf.view_fields.splice(i, 1);
		}

		this.pdv.removeChild(this.odv); 
	} 
}

/**
 * Save changes to a view
 *
 * @private 
 * @param {Boolean} saveas  Determine whether to save as new or not
 */
AntObjectViewEditor.prototype.showSaveForm = function(saveas)
{
	this.dlg.setTitle("Save View");
    
	// Hide the view form
	alib.dom.styleSet(this.viewCon, "display", "none");

	// Create save div
	if (this.saveCon == null)
		this.saveCon = alib.dom.createElement("div", this.con);
	else
	{
		alib.dom.styleSet(this.saveCon, "display", "block");
		this.saveCon.innerHTML = "";
	}

	// Create dialog div
	var dv = alib.dom.createElement("div", this.saveCon);
    
    var inputData = new Object();

	// Name
	var lbl = alib.dom.createElement("h4", dv);
	alib.dom.styleSet(lbl, "margin", "5px 0 5px 0");
	lbl.innerHTML = "Name This View";
	var inp_dv = alib.dom.createElement("div", dv);
	inputData.txtName = alib.dom.createElement("input", inp_dv);
	alib.dom.styleSet(inputData.txtName, "width", "98%");

	// Description
	var lbl = alib.dom.createElement("h4", dv);
	alib.dom.styleSet(lbl, "margin", "5px 0 5px 0");
	lbl.innerHTML = "Description";
	var inp_dv = alib.dom.createElement("div", dv);
	alib.dom.styleSet(inp_dv, "margin", "5px 0 5px 0");
	inputData.txtDescription = alib.dom.createElement("textarea", inp_dv);
	alib.dom.styleSet(inputData.txtDescription, "width", "98%");

	var save_view_id = "";

	if (this.view.id)
	{
		inputData.txtName.value = this.view.name;
		inputData.txtDescription.value = this.view.description;

		if (saveas)
			inputData.txtName.value += " (copy)";
		else
			save_view_id = this.view.id;
	}
	else
	{
		inputData.txtName.value = "My Custom View";
		inputData.txtDescription.value = "Describe this view here";
	}

	// Scope
	var lbl = alib.dom.createElement("h4", dv);
	alib.dom.styleSet(lbl, "margin", "5px 0 5px 0");
	lbl.innerHTML = "Scope - View will be available to:";
    
    // Scope Dropdown
    var inp_dv = alib.dom.createElement("div", dv);
    alib.dom.styleSet(inp_dv, "margin", "5px 0 5px 0");
    inputData.selectScope = alib.dom.createElement("select", inp_dv);
    alib.dom.styleSet(inputData.selectScope, "margin-right", "10px");
    
    // Team Dropdown
    inputData.selectTeam = alib.dom.createElement("select", inp_dv);
    alib.dom.styleSet(inputData.selectTeam, "display", "none");
    
    // Select User
    var userCon = alib.dom.createElement("div", inp_dv);
    alib.dom.styleSet(userCon, "display", "none");
    this.showUsers(userCon);
    
    // Scope Event
    inputData.selectScope.selectTeam = inputData.selectTeam;
    inputData.selectScope.userCon = userCon;
    inputData.selectScope.onchange = function()
    {
        alib.dom.styleSet(this.selectTeam, "display", "none");
        alib.dom.styleSet(this.userCon, "display", "none");
        
        if(this.value == SCOPE_TEAM) // Team
            alib.dom.styleSet(this.selectTeam, "display", "inline-block");
        else if(this.value == SCOPE_USER) // User
            alib.dom.styleSet(this.userCon, "display", "inline-block");
    }
    
    // Populate Team Dropdown
    var userObject = new CAntObject("user");
    userObject.teamId = this.view.teamid;
    userObject.teamDropdown = inputData.selectTeam;
    userObject.onteamsloaded = function(ret)
    {
        var teamData = ret;
        delete ret['teamCount'];
        this.populateTeam(ret, ret[0].parentId);
        this.addSpacedPrefix(teamData);
    }
    userObject.loadTeam();
    
    var selectedScope = null;
    switch(this.view.scope)
    {
        case "User":
            selectedScope = SCOPE_USER;
            break;
        case "Team":
            selectedScope = SCOPE_TEAM;
            break;
        case "Everyone":
            selectedScope = SCOPE_EVERYONE;
            break;
        case "Me":
        default:
            selectedScope = SCOPE_ME;
            break;
    }
    
    // Populate Scope Dropdown
    buildDropdown(inputData.selectScope, [[SCOPE_ME, "Me"], [SCOPE_EVERYONE, "Everyone"], [SCOPE_TEAM, "Team"], [SCOPE_USER, "User"]], selectedScope);
    inputData.selectScope.onchange();
    
	// Default Input
	var lbl = alib.dom.createElement("h4", dv, "Default");
	alib.dom.styleSet(lbl, "margin", "5px 0 5px 0");
    var inp_dv = alib.dom.createElement("div", dv);
    alib.dom.styleSet(inp_dv, "margin", "5px 0 5px 0");
    
    // Default Yes
    var attrData = [["type", "radio"], ["name", "defaultChk"], ["value", "1"]];
    inputData.defRadioYes = alib.dom.setElementAttr(alib.dom.createElement("input", inp_dv), attrData);
    alib.dom.setElementAttr(alib.dom.createElement("span", inp_dv), [["innerHTML", "Yes"], ["margin-right", "10px"]]);
    
    // Default No
    var attrData = [["type", "radio"], ["name", "defaultChk"], ["value", "0"]];
    inputData.defRadioNo = alib.dom.setElementAttr(alib.dom.createElement("input", inp_dv), attrData);
    alib.dom.setElementAttr(alib.dom.createElement("span", inp_dv), [["innerHTML", "No"]]);
    
    if(this.view.fDefault)
        inputData.defRadioYes.checked = true;
    else
        inputData.defRadioNo.checked = true;

    var lbl = alib.dom.createElement("span", inp_dv, "&nbsp;&nbsp;&nbsp;-Should this view be used as the default for the scope selected?");

	// Button container
	var dv_btn = alib.dom.createElement("div", dv);

    inputData.save_view_id = save_view_id;
    
	// Save button
	var btn = alib.ui.Button("Save", {
		className:"b2", tooltip:"Click to save changes", cls:this, 
		inputData:inputData,
		onclick:function() 
                { 
                    this.cls.saveView(this.inputData); 
                }
	});
	btn.print(dv_btn);

	// Cancel
	var btn = alib.ui.Button("Cancel", {
		className:"b1", tooltip:"Close without saving any changes", cls:this, 
		onclick:function() { this.cls.showViewForm(); }
	});
	btn.print(dv_btn);
}

/**
 * Save a view
 *
 * @param {string} name The name of the view
 * @param {string} description Optional description of the view
 * @param {int} save_view_id Optional id of view to edit
 */
AntObjectViewEditor.prototype.saveView = function(inputData, name, description, save_view_id)
{
	// Create loading div
	var dlg = new CDialog(null, this.m_advancedSearchDlg);
    dlg.parentDlg = true;
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Saving, please wait...";
	dlg.statusDialog(dv_load, 150, 100);

	var view = null;
    
    if(!this.searchView)
        this.searchView = new Object();
    
    // Define arrays so it wont throw an error     
    this.searchView.conditions = new Array();
    this.searchView.sort_order = new Array();
	this.searchView.view_fields = new Array();
    
    // Create Input Variables
    var name = inputData.txtName.value;
    var description = inputData.txtDescription.value;
    var save_view_id = inputData.save_view_id;
    var teamId = inputData.selectTeam.value;
    var defaultView = false;
    
    // Scope Value
    var scopeIdx = inputData.selectScope.selectedIndex;
    var scope = inputData.selectScope.options[scopeIdx].text
    
    if(inputData.defRadioYes.checked)
        defaultView = true;
    
    // Instantiate View Object
	if (save_view_id && !this.viewId) // If this.viewId already has value, lets create a new instance for current view
        view = this.mainObject.getViewById(save_view_id);
	else
        view = new AntObjectBrowserView(this.mainObject.name);
    
	if(view)
	{
        this.setViewFromBuf(view)
        
        // If view is saved again without closing the dialog, we need to use the new View Id
        if(this.viewId) // View Id will only have value once view is already saved.
            view.id = this.viewId;
        
		view.name = name;
		view.filterKey = this.viewsFilterKey;
        view.description = description;
        view.f_default = defaultView;
        view.scope = scope;
		
        if(inputData.selectScope.value == SCOPE_TEAM)
            view.team_id = teamId;
            
        if(inputData.selectScope.value == SCOPE_USER)
            view.user_id = this.user_id;
        
        view.cls = this;
		view.save_view_id = (save_view_id) ? save_view_id : null;
		view.dlg = dlg;
		view.onsave = function(ret)
		{
			dlg.hide();
			if (!this.save_view_id)
				this.cls.mainObject.views[this.cls.mainObject.views.length] = this;

			ALib.statusShowAlert("View Saved!", 3000, "bottom", "right");
            this.cls.viewId = ret; // Should set the new View Id so it wont create a duplicate entry when saved again

            // Execute Self onSave Callback
            this.cls.onSave();
            
            if(typeof this.cls.buildViewsDropdown == 'function')
            {
                this.cls.buildViewsDropdown();
                this.cls.showAdvancedSearch();
                this.cls.runSearch(this);
            }

			// Update global object def cache
			Ant.EntityDefinitionLoader.get(this.cls.mainObject.name).load();
            
            // Update Current View                
            this.cls.view.fDefault = defaultView;
            this.cls.view.userName = this.cls.username;
            this.cls.view.userid = this.cls.user_id;
            this.cls.view.fSystem = false;
            this.cls.createButtons();
            
            this.cls.view.name = name;
            this.cls.showViewForm();
		}
		view.onsaveError = function()
		{
			dlg.hide();
			ALib.statusShowAlert("ERROR: Unable to connect to server!", 3000, "bottom", "right");
            this.cls.showViewForm();
		}
		view.save();
	}
    else
    {
        var error = "ERROR: Unable save current view because another user has made some changes. Please click 'Refresh Views' button to view changes!";
        dv_load.innerHTML = error;
        
        
        var errorFunc = function()
        {
            dlg.hide();
            ALib.statusShowAlert(error, 3000, "bottom", "right");
        }
        
        window.setTimeout(errorFunc, 3000);
    }
}

/**
 * Shows the user dialog 
 *
 * @public
 * @this {class}
 * @param {DOMElement} userCon  Container for user browser
 */
AntObjectViewEditor.prototype.showUsers = function(userCon)
{
    var userLabel = alib.dom.setElementAttr(alib.dom.createElement("span", userCon), [["innerHTML", "None Selected"]]);
    alib.dom.styleSet(userLabel, "margin-right", "10px");
    
    if(this.view.userName && this.view.userName.length > 0 && this.view.scope == "User")
    {
        userLabel.innerHTML = this.view.userName;
        this.user_id = this.view.userid;
        this.username = this.view.userName;
    }
    
    var cbrowser = new CUserBrowser();
    cbrowser.cls = this;
    cbrowser.userLabel = userLabel;
    cbrowser.onSelect = function(cid, name) 
    {
        this.cls.user_id = cid;
        this.cls.username = name;
        this.userLabel.innerHTML = " " + name;
    }
    
    var btn = alib.ui.Button("Select", 
                            {
                                className:"b1", tooltip:"Select User for this view", cbUser:cbrowser, 
                                onclick:function() 
                                {
                                    this.cbUser.showDialog();
                                }
                            });
    btn.print(userCon);
}
