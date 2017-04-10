/****************************************************************************
*	
*	Class:		CWidTasks
*
*	Purpose:	Task Widget
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2006 Aereus Corporation. All rights reserved.
*
*****************************************************************************/

function CWidTasks()
{
	this.title = "Tasks";
	this.m_container = null;	// Set by calling process
	this.m_dm = null;			// Dropdown menu will be set by parent
    this.m_id = null;

	/**
	 * Array of tasks
	 *
	 * @type {CAntObject[]}
	 */
	this.tasksObjects = new Array();

    this.m_data = new Object();
}

/**
 * Entry point for application
 *
 * @public
 * @this {CWidTasks}
 */
CWidTasks.prototype.main = function()
{
	var cls = this;

	this.m_container.innerHTML = "";

	// Create context menu
	//this.m_dm.addEntry('Add Task', function() { loadObjectForm("task"); }, "/images/icons/taskIcon.gif");
	
	var cls = this;
	funct = function(cls, showFor, groupBy)
	{
		cls.displayTasks(showFor, groupBy);
        cls.m_data = "['" + showFor + "', '" + groupBy + "']";
        cls.saveWidgetData();
	};

	var sub2 = this.m_dm.addSubmenu("Group By");
	sub2.addEntry('None', funct, null, "<div id='widg_home_tasks_group_none'></div>", [cls, null, null]);
	sub2.addEntry('Overview', funct, null, "<div id='widg_home_tasks_group_overview'></div>", [cls, null, 'overview']);
	sub2.addEntry('Project', funct, null, "<div id='widg_home_tasks_group_project'></div>", [cls, null, 'project']);
	sub2.addEntry('Contact/Customer', funct, null, "<div id='widg_home_tasks_group_contact'></div>", [cls, null, 'customer_id']);
	sub2.addEntry('Priority', funct, null, "<div id='widg_home_tasks_group_priority'></div>", [cls, null, 'priority']);
	sub2.addEntry('Due Date', funct, null, "<div id='widg_home_tasks_group_deadline'></div>", [cls, null, 'deadline']);
	sub2.addEntry('Category', funct, null, "<div id='widg_home_tasks_group_category'></div>", [cls, null, 'category']);

	this.m_container.innerHTML = "<div class='loading'></div>";

	this.loadTasks();
}

/**
 * Perform needed clean-up on app exit
 *
 * @public
 * @this {CWidTasks}
 */
CWidTasks.prototype.exit= function()
{
	this.m_container.innerHTML = "";
}

/**
 * Loads the tasks for widget
 *
 * @public
 * @this {CWidTasks}
 * @param {string} showFor      Show the tasks for specific date range
 * @param {string} groupBy      Group the task by specificcategory
 */
CWidTasks.prototype.displayTasks = function(showFor, groupBy)
{
	if (typeof showFor == "undefined" && typeof groupBy == "undefined")
	{
        if(this.m_data)
        {
            var data = eval(this.m_data);
            showFor = data[0]
            groupBy = data[1];
        }
	}

    this.tasks = new Array();
    this.taskContainers = new Array();

    this.m_container.innerHTML = "";

	var tb = alib.dom.createElement("div", this.m_container);
	alib.dom.styleSet(tb, "margin-bottom", "10px");
	alib.dom.styleSet(tb, "text-align", "right");

	// Add task
	var ref = alib.dom.createElement("a", tb);
	ref.innerHTML = "add task";
	ref.href = "javascript:void(0);";
	ref.cls = this;
	ref.onclick = function() {
		var ol = loadObjectForm("task");
		alib.events.listen(ol, "close", function(evt) {
			evt.data.cls.loadTasks(); // refresh
		}, {cls:this.cls});
	}

	alib.dom.createElement("span", tb, " | ");

	// Add refresh
	var ref = alib.dom.createElement("a", tb);
	ref.innerHTML = "refresh";
	ref.href = "javascript:void(0);";
	ref.cls = this;
	ref.onclick = function() {
		this.cls.loadTasks();
	}


	// If we are in overview, then pre-create the containers so sorting is correct
	if (groupBy == "overview")
	{
		this.getContainer("Due Today");
		this.getContainer("Upcoming");
		this.getContainer("Later");
	}

	for(i in this.tasksObjects)
	{
		var currentTask = this.tasksObjects[i];
		var taskObj = new Object();
		taskObj.id = currentTask.id;

		var groupName = "";
		
		if(groupBy)
		{
			switch(groupBy)
			{
				case "start_date":
				case "deadline":
					var conIndex = currentTask.getValue(groupBy);
					var groupName = currentTask.getValue(groupBy);
					break;
				case "overview":
					var conIndex = this.getOverviewGroup(currentTask);
					var groupName = conIndex;
					break;
				default:
					var conIndex = currentTask.getValue(groupBy);
					var groupName = currentTask.getValueName(groupBy);
					break;
			}
		}
		
		// Get grouping contianer for this class
		var taskCon = this.getContainer(groupName);
		
		// Create checkbox
		var divCon = alib.dom.createElement("div", taskCon);
		alib.dom.styleSet(divCon, "float", "left");
		alib.dom.styleSet(divCon, "width", "20px");
		
		var taskCheckbox = alib.dom.setElementAttr(alib.dom.createElement("input", divCon), [["type", "checkbox"]]);
		taskCheckbox.checked = (currentTask.getValue("done") == 't') ? true : false;
		taskCheckbox.taskId = currentTask.id;
		taskCheckbox.cls = this;
		taskCheckbox.onchange = function()
		{
			this.cls.checkTask(this.taskId, (this.checked) ? 't' : 'f');
		}
		taskObj.checkbox = taskCheckbox;
		
		// Task Name
		var divCon = alib.dom.createElement("div", taskCon);
		alib.dom.styleSet(divCon, "float", "left");
		alib.dom.styleSet(divCon, "max-width", "80%");

		var lbl = "";
		if (currentTask.getValue("project"))
			lbl += currentTask.getValueName("project") + ": ";
		if (currentTask.getValue("customer_id"))
			lbl += currentTask.getValueName("customer_id") + ": ";

		lbl += currentTask.getValue("name");
		
		var lnk = alib.dom.createElement("a", divCon, lbl);
		lnk.href = "javascript:void(0);";
		lnk.cls = this;
		lnk.taskId = currentTask.id;
		lnk.onclick = function() {
			var ol = loadObjectForm("task", this.taskId);
			alib.events.listen(ol, "close", function(evt) {
				evt.data.cls.loadTasks(); // refresh
			}, {cls:this.cls});
		};
		taskObj.label = lnk;
		
		// Task delete icon
		var divCon = alib.dom.createElement("div", taskCon);
		alib.dom.styleSet(divCon, "float", "right");
		alib.dom.styleSet(divCon, "width", "20px");
		
		var taskDelete = alib.dom.createElement("img", divCon);
		taskDelete.src = "/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif";
		alib.dom.styleSet(taskDelete, "cursor", "pointer");
		taskDelete.cls = this;
		taskDelete.taskId = currentTask.id;
		taskDelete.taskName = currentTask.getValue("name");
		taskDelete.onclick = function()
		{
			this.cls.deleteTask(this.taskId, this.taskName);
		}
		taskObj.deleteIcon = taskDelete;
		
		var taskIndex = this.tasks.length;
		this.tasks[taskIndex] = taskObj;
		
		alib.dom.divClear(taskCon);
    }
}

/**
 * Add a named container for tasks
 *
 * @public
 * @this {CWidTasks} 
 * @param {string} name The unique name of the container
 */
CWidTasks.prototype.getContainer = function(name)
{
	var name = name || "None"; // If no name then use default container

	// If already exists then return container
	if(this.taskContainers[name])
		return this.taskContainers[name];

	// Create container
	var projectCon = alib.dom.createElement("div", this.m_container);
	alib.dom.styleSet(projectCon, "margin-bottom", "10px");
	this.taskContainers[name] = projectCon;
	
	// Icon and label
	var headerCon = alib.dom.createElement('div', projectCon);
	
	var groupLabel = alib.dom.setElementAttr(alib.dom.createElement("span", headerCon), [["innerHTML", name]]);
	alib.dom.styleSet(groupLabel, "font-weight", "bold");

	// Horizontal Row
	var hrCon = alib.dom.createElement('div', headerCon);
	alib.dom.styleSetClass(hrCon, "horizontalline");
	alib.dom.styleSet(hrCon, "margin-bottom", "5px");
	
	var taskCon = alib.dom.createElement("div", projectCon); // Create a child container for every task
	alib.dom.styleSetClass(taskCon, "group" + name);

	return projectCon;
}

/**
 * Determine what group this task belongs to
 *
 * Groups include: New Tasks, Due Today, Upcoming, Later
 *
 * @public
 * @this {CWidTasks} 
 * @param {Object} task The task we will be classifying
 * @return {string} The label of the group this task belongs to
 */
CWidTasks.prototype.getOverviewGroup = function(task)
{
	var today = new Date();

	// TODO: add new

	// Check for due today
	if (task.getValue("deadline"))
	{
		var dueDate = new Date(task.getValue("deadline"));

		if (dueDate <= today)
			return "Due Today";
	}

	// Check for upcoming
	if (task.getValue("deadline"))
	{
		var futureDate = new Date();
		futureDate.setDate(today.getDate() + 3); // Add three days
		var dueDate = new Date(task.getValue("deadline"));

		if (dueDate <= futureDate)
			return "Upcoming";
	}

	return "Later";
}

/**
 * Loads the tasks for widget
 *
 * @public
 * @this {CWidTasks}
 */
CWidTasks.prototype.loadTasks = function()
{
	this.tasksObjects = new Array();

	var list = new AntObjectList("task");
	list.cbData.cls = this;
	list.addCondition("and", "user_id", "is_equal", -3); // Current user
	list.addCondition("and", "done", "is_equal", "f"); // Current user

	list.onLoad = function()
	{
		for (var i = 0; i < this.getNumObjects(); i++)
		{
			this.cbData.cls.tasksObjects.push(this.getObject(i));
		}
        	
		this.cbData.cls.displayTasks();
	}

	list.getObjects();
}

/**
 * Delete a task from the table and the database - rpc
 *
 * @public
 * @this {CWidTasks}
 * @param {Integer} id      Task Id
 * @param {String} name     Task Name
 */
CWidTasks.prototype.deleteTask = function(id, name)
{
	if (confirm("Are you sure you want to remove " + name + "?"))
	{	
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            this.cbData.cls.loadTasks();
        };
        ajax.exec("/controller/Project/checkTask",
                    [["type", "delete"], ["task_id", id]]);
	}
}

/**
 * Change the status of a task to complete
 *
 * @public
 * @this {CWidTasks}
 * @param {Integer} id      Task Id
 * @param {Boolean} status  Determines if task is deleted or not
 */
CWidTasks.prototype.checkTask = function(id, status)
{
    ajax = new CAjax('json');
    ajax.exec("/controller/Project/checkTask",
                [["type", "check"], ["task_id", id], ["task_res", status]]);
    
	for (var i = 0; i < this.tasks.length; i++)
    {
        if (this.tasks[i].id == id)
        {
            if (status=='t')
            {
                this.tasks[i].label.style.textDecoration = 'line-through';
                this.tasks[i].checkbox.checked = true;
            }
            else
            {
                this.tasks[i].label.style.textDecoration = '';
                this.tasks[i].checkbox.checked = false;
            }
            
        }
    }
}

/**
 * Saves the widget data
 *
 * @public
 * @this {CWidTasks} 
 */
CWidTasks.prototype.saveWidgetData = function()
{
    var args = new Array();
    args[args.length] = ['data', this.m_data];
    args[args.length] = ['dwid', this.m_id];
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.exec("/controller/Dashboard/saveData", args);
}
