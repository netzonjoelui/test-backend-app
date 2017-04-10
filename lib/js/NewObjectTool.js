/**
 * @fileOverview This tool will handle a global "Create New" dropdown
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntUpdateStream
 *
 * @constructor
 */
Ant.NewObjectTool = function()
{
	/**
	 * The container that will house the rendered tool
	 *
	 * @var {DOMElement}
	 */
	this.con = null;

	/**
	 * The mode can be 'inline' or 'popup'
	 *
	 * @var {string}
	 */
	this.mode = 'popup';

	/**
	 * If renderd inline then an antView will be provided
	 *
	 * @var {AntView}
	 */
	this.antView = null;

	/**
	 * This will be the cache of objects loaded from the server
	 *
	 * For now we will just manually define the most commonly used
	 *
	 * @private
	 * @var {Object[]}
	 */
	this.objects_ = [
		{title: "Email Message", obj_type: "email_message", icon:"/images/icons/objects/email_message_16.png"},
		{title: "Task", obj_type: "task", icon:"/images/icons/objects/task_16.png"},
		{title: "Case", obj_type: "case", icon:"/images/icons/objects/case_16.png"},
		{title: "Project", obj_type: "project", icon:"/images/icons/objects/project_16.png"},
		{title: "Log Time", obj_type: "time", icon:"/images/icons/objects/time_16.png"},
		{title: "Project Story", obj_type: "project_story", icon:"/images/icons/objects/project_story_16.png"},
		{title: "Calendar Event", obj_type: "calendar_event", icon:"/images/icons/objects/calendar_event_16.png"},
		{title: "Reminder", obj_type: "reminder", icon:"/images/icons/objects/reminder_16.png"},
		{title: "Note", obj_type: "note", icon:"/images/icons/objects/note_16.png"},
		{title: "Person or Organization", obj_type: "customer", icon:"/images/icons/objects/customers/person_16.png"},
		{title: "Lead", obj_type: "lead", icon:"/images/icons/objects/lead_16.png"},
		{title: "Opportunity", obj_type: "opportunity", icon:"/images/icons/objects/opportunity_16.png"}
	];
}

/**
 * Render new objects into an AntView
 *
 * @param {AntView} antView Render this into an antview rather than a popup
 */
Ant.NewObjectTool.prototype.renderView = function(antView)
{
	this.antView = antView;

	// Set the mode to print inline
	this.mode = 'inline';

	// Get objects
 	this.getObjects();
}

/**
 * Anchor popup interface to an element
 *
 * @param DOMElement con The container that will house the rendered tool
 */
Ant.NewObjectTool.prototype.anchorToEl = function(con)
{
	this.con = alib.dom.createElement("a", con, "<img src='/images/icons/add_24.png' />");
	this.con.href = "javascript:void(0);";
	this.con.title = "Click to create a new object";

	// Get objects
 	this.getObjects();
}

/**
 * Get list of objects
 */
Ant.NewObjectTool.prototype.getObjects = function()
{
	// For now just manually build drop-down with common objects
	if (this.mode == "inline")
		return this.buildViewInterface();
	else
		return this.buildDropDown();

	// Poll the server until we get data or timeout
	var xhr = new alib.net.Xhr();

	// Retrieve results
	alib.events.listen(xhr, "load", function(evt) { 

		var objects = this.getResponse();

		evt.data.cls.objects_ = objects;

		// For now just manually build drop-down with common objects
		if (evt.data.cls.mode == "inline")
			evt.data.cls.buildViewInterface();
		else
			evt.data.cls.buildDropDown();
	}, {cls:this});

	// Timed out
	alib.events.listen(xhr, "error", function(evt) { 
		// Try again in 3 seconds
		setTimeout(function() { evt.data.toolCls.getObjects(); }, 3000);
	}, {toolCls:this});

	xhr.send("/controller/Object/getObjects");
}

/**
 * Build manual dropdown list
 */
Ant.NewObjectTool.prototype.buildDropDown = function()
{
	var menu = new alib.ui.FilteredMenu();

	for (var i in this.objects_)
	{
		var item = new alib.ui.MenuItem(this.objects_[i].title, {icon:"<img src='" + this.objects_[i].icon + "' />"});
		item.cbData.obj_type = this.objects_[i].obj_type;
		item.onclick = function() { loadObjectForm(this.cbData.obj_type); };
		menu.addItem(item);
	}
	/*
	// Email Message
	var item = new alib.ui.MenuItem("Email Message", {icon:"<img src='/images/icons/objects/email_message_10.png' />"});
	item.onclick = function() { loadObjectForm('email_message'); };
	menu.addItem(item);

	// Task
	var item = new alib.ui.MenuItem("Task", {icon:"<img src='/images/icons/objects/task_10.png' />"});
	item.onclick = function() { loadObjectForm('task'); };
	menu.addItem(item);

	// Case
	var item = new alib.ui.MenuItem("Case", {icon:"<img src='/images/icons/objects/case_10.png' />"});
	item.onclick = function() { loadObjectForm('case'); };
	menu.addItem(item);

	// Contact
	var item = new alib.ui.MenuItem("Person or Organization", {icon:"<img src='/images/icons/objects/customers/person_10.png' />"});
	item.onclick = function() { loadObjectForm('customer'); };
	menu.addItem(item);

 	// Calendar Event
	var item = new alib.ui.MenuItem("Calendar Event", {icon:"<img src='/images/icons/objects/calendar_event_10.png' />"});
	item.onclick = function() { loadObjectForm('calendar_event'); };
	menu.addItem(item);

	// Note
	var item = new alib.ui.MenuItem("Note", {icon:"<img src='/images/icons/objects/note_10.png' />"});
	item.onclick = function() { loadObjectForm('note'); };
	menu.addItem(item);
	*/

	menu.attach(this.con);
}

/**
 * Build interface into AntView
 */
Ant.NewObjectTool.prototype.buildViewInterface = function()
{
	var con = this.antView.con;

	for (var i in this.objects_)
	{
		var entry = alib.dom.createElement("article", con);
		alib.dom.styleSetClass(entry, "nav");
		entry.innerHTML = "<a behavior='selectable' href=\"#"+this.antView.getPath()+"/"+this.objects_[i].obj_type+"\" onclick=\"alib.dom.styleAddClass(this, 'selected');\">"
						+ "<span class='icon'><img src='"+this.objects_[i].icon+"' /></span><h2><span class='more'></span>New "+this.objects_[i].title+"</h2></a>";
		var viewNew = this.antView.addView(this.objects_[i].obj_type, {type:this.objects_[i].obj_type});
		viewNew.onshow = function()
		{
			this.con.innerHTML = "";

			var ol = new AntObjectLoader(this.options.type);
			ol.setAntView(this);
			ol.print(this.con);
			ol.cbData.antView = this;
			ol.onClose = function()
			{
				this.cbData.antView.goup();
			}
			ol.onRemove = function()
			{
			}
			ol.onSave = function()
			{
			}
		}
	}
	/*
	// Email Message
	var item = new alib.ui.MenuItem("Email Message", {icon:"<img src='/images/icons/objects/email_message_10.png' />"});
	item.onclick = function() { loadObjectForm('email_message'); };
	menu.addItem(item);

	// Task
	var item = new alib.ui.MenuItem("Task", {icon:"<img src='/images/icons/objects/task_10.png' />"});
	item.onclick = function() { loadObjectForm('task'); };
	menu.addItem(item);

	// Case
	var item = new alib.ui.MenuItem("Case", {icon:"<img src='/images/icons/objects/case_10.png' />"});
	item.onclick = function() { loadObjectForm('case'); };
	menu.addItem(item);

	// Contact
	var item = new alib.ui.MenuItem("Person or Organization", {icon:"<img src='/images/icons/objects/customers/person_10.png' />"});
	item.onclick = function() { loadObjectForm('customer'); };
	menu.addItem(item);

 	// Calendar Event
	var item = new alib.ui.MenuItem("Calendar Event", {icon:"<img src='/images/icons/objects/calendar_event_10.png' />"});
	item.onclick = function() { loadObjectForm('calendar_event'); };
	menu.addItem(item);

	// Note
	var item = new alib.ui.MenuItem("Note", {icon:"<img src='/images/icons/objects/note_10.png' />"});
	item.onclick = function() { loadObjectForm('note'); };
	menu.addItem(item);

	menu.attach(this.con);
	*/
}
