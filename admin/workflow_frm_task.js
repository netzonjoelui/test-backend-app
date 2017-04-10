function taskCreateTask(con, task_obj)
{
	task_obj.create_obj = "task";
	task_obj.type = WF_ATYPE_CREATEOBJ;

	var dv_cnd = alib.dom.createElement("fieldset", con);
	alib.dom.styleSet(dv_cnd, "margin", "6px 0px 3px 3px");
	var lbl = alib.dom.createElement("legend", dv_cnd);
	lbl.innerHTML = "Create Task";

	var tbl = alib.dom.createElement("table", dv_cnd);
	var tbody = alib.dom.createElement("tbody", tbl);

	// Subject
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Subject";
	var td = alib.dom.createElement("td", row);
	var txtName = alib.dom.createElement("input", td);
	txtName.value = task_obj.getObjectValue("name");
	txtName.m_obj = task_obj;
	txtName.onchange = function() { this.m_obj.setObjectValue("name", this.value); };
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = " this will be the name the end-user sees when the task is created";

	// Assign To
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Assign To";
	var td = alib.dom.createElement("td", row);
	var cbAssignTo = alib.dom.createElement("select", td);
	cbAssignTo.m_act = task_obj;
	cbAssignTo.onchange = function() { this.m_act.setObjectValue("user_id", this.value); }
	var assigned_to = task_obj.getObjectValue("user_id");
	for (var i = 0; i < g_antObject.getNumFields(); i++)
	{
		var field = g_antObject.getField(i);

		if (field.type == "fkey" && field.subtype == "users")
		{
			var varname = "<%"+field.name+"%>";

			if (!assigned_to)
			{
				task_obj.setObjectValue("user_id", varname);
				assigned_to = varname;
			}

			cbAssignTo[cbAssignTo.length] = new Option(g_antObject.title + "." + field.title, varname, 
													   false, (assigned_to==varname)?true:false);
		}
	}
	
	wf_frm_loadUsers(cbAssignTo, assigned_to);

	//cbAssignTo[cbAssignTo.length] = new Option("Me", 37, false, false);
	//cbAssignTo[cbAssignTo.length] = new Option("Kris Carter", 5, false, false);

	cbAssignTo.m_obj = task_obj;
	cbAssignTo.onchange = function() { this.m_obj.setObjectValue("user_id", this.value); };

	/*
	for (var i = 2; i < time_units.length; i++) // Start on days
	{
		//cbWhenUnit[cbWhenUnit.length] = new Option(time_units[i][1], time_units[i][0], false, (task_obj.when.unit==time_units[i][0])?true:false);
		cbAssignTo[cbAssignTo.length] = new Option(time_units[i][1], time_units[i][0], false, false);
	}
	 */

	// Due Date
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Task Is Due";
	var td = alib.dom.createElement("td", row);

	var txtWhenInterval = alib.dom.createElement("input", td);
	alib.dom.styleSet(txtWhenInterval, "width", "14px");
	var interval = task_obj.getObjectValue("due_interval");
	if (!interval)
		task_obj.setObjectValue("due_interval", "0");
	txtWhenInterval.value = (interval != null) ? interval : "0";
	txtWhenInterval.m_obj = task_obj;
	txtWhenInterval.onchange = function() { this.m_obj.setObjectValue("due_interval", this.value); };

	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = "&nbsp;";

	var cbWhenUnit = alib.dom.createElement("select", td);
	var time_units = wfGetTimeUnits();
	var due_unit = task_obj.getObjectValue("due_unit");
	if (!due_unit)
		task_obj.setObjectValue("due_unit", time_units[2][0]); // Days
	for (var i = 2; i < time_units.length; i++) // Start on days
	{
		cbWhenUnit[cbWhenUnit.length] = new Option(time_units[i][1], time_units[i][0], false, false);
	}
	cbWhenUnit.m_obj = task_obj;
	cbWhenUnit.onchange = function() { this.m_obj.setObjectValue("due_unit", this.value); };

	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = " after task is created (enter 0 for immediate)";
}

function wf_frm_tasks_loadUsers(cbAssignTo, assigned_to)
{
	var ajax = new CAjax();
	ajax.cbAssignTo = cbAssignTo;
	ajax.assigned_to = assigned_to;
	ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var child = root.getChildNode(i);
				if (child.m_name == "user")
				{
					var id = child.getChildNodeValByName("id");
					var title = child.getChildNodeValByName("title");
					var team_name = child.getChildNodeValByName("team_name");
					var name = child.getChildNodeValByName("full_name");
					if (!name) name = "untitled";

					this.cbAssignTo[this.cbAssignTo.length] = new Option(unescape(name), id, 
																		   false, (this.assigned_to==id)?true:false);
				}
			}
		}
	};

	var url = "/users/xml_get_users.php?fval=0";
	ajax.exec(url);
}
