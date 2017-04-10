/**
 * @fileoverview This is the notifiaction action interface
 */
function WorkFlow_Action_Notification(obj_type)
{
	this.g_antObject = new CAntObject(obj_type);
}

/*************************************************************************
*	Function:	taskCreateTask
*
*	Purpose:	Task action for workflow
**************************************************************************/
WorkFlow_Action_Notification.prototype.render = function(con, act)
{
	act.create_obj = "notification";
	act.type = WF_ATYPE_CREATEOBJ;

	var dv_cnd = alib.dom.createElement("fieldset", con);
	alib.dom.styleSet(dv_cnd, "margin", "6px 0px 3px 3px");
	var lbl = alib.dom.createElement("legend", dv_cnd);
	lbl.innerHTML = "Send Notification";

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
	txtName.value = act.getObjectValue("name");
	txtName.m_obj = act;
	txtName.onchange = function() { this.m_obj.setObjectValue("name", this.value); };
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = " short description of the notification";

	// Send To
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row,  "Send To");
	alib.dom.styleSetClass(td, "formLabel");
	alib.dom.styleSet(td, "vertical-align", "top");
	var td = alib.dom.createElement("td", row);
	var cbAssignTo = alib.dom.createElement("select", td);
	cbAssignTo.m_act = act;
	cbAssignTo.onchange = function() { this.m_act.setObjectValue("owner_id", this.value); }
	var assigned_to = act.getObjectValue("owner_id");
	for (var i = 0; i < this.g_antObject.getNumFields(); i++)
	{
		var field = this.g_antObject.getField(i);

		if (field.type == "object" && field.subtype == "user")
		{
			var varname = "<%"+field.name+"%>";

			if (!assigned_to)
			{
				act.setObjectValue("owner_id", varname);
				assigned_to = varname;
			}

			cbAssignTo[cbAssignTo.length] = new Option(this.g_antObject.title + "." + field.title, varname, 
													   false, (assigned_to==varname)?true:false);
		}
	}
	
	this.wf_frm_loadUsers(cbAssignTo, assigned_to);

	cbAssignTo.m_obj = act;
	cbAssignTo.onchange = function() { this.m_obj.setObjectValue("owner_id", this.value); };

	// Method
	var mrow = alib.dom.createElement("div", td);
	var chk = alib.dom.createElement("input", mrow);
	chk.type = "checkbox";
	chk.checked = true;
	chk.disabled = true;
	var lbl = alib.dom.createElement("span", mrow, "Notification Center");

	var mrow = alib.dom.createElement("div", td);
	var chk = alib.dom.createElement("input", mrow);
	chk.type = "checkbox";
	chk.checked = (act.getObjectValue("f_popup") == 't') ? true : false;
	var lbl = alib.dom.createElement("span", mrow, "Popup Alert");
	chk.act = act;
	chk.onclick = function() { this.act.setObjectValue("f_popup", (this.checked) ? 't' : 'f'); }

	var mrow = alib.dom.createElement("div", td);
	var chk = alib.dom.createElement("input", mrow);
	chk.type = "checkbox";
	chk.checked = (act.getObjectValue("f_email") == 't') ? true : false;
	var lbl = alib.dom.createElement("span", mrow, "Send Email");
	chk.act = act;
	chk.onclick = function() { this.act.setObjectValue("f_email", (this.checked) ? 't' : 'f'); }

	var mrow = alib.dom.createElement("div", td);
	var chk = alib.dom.createElement("input", mrow);
	chk.type = "checkbox";
	chk.checked = (act.getObjectValue("f_sms") == 't') ? true : false;
	var lbl = alib.dom.createElement("span", mrow, "SMS (text message to mobile)");
	chk.act = act;
	chk.onclick = function() { this.act.setObjectValue("f_sms", (this.checked) ? 't' : 'f'); }


	// Message
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row, "Message");
	alib.dom.styleSetClass(td, "formLabel");
	alib.dom.styleSet(td, "vertical-align", "top");

	var td = alib.dom.createElement("td", row);
	var taBody = alib.dom.createElement("textarea", td);
	alib.dom.styleSet(taBody, "height", "200px");
	alib.dom.styleSet(taBody, "width", "98%");
	taBody.value = act.getObjectValue("description");
	taBody.act = act;
	taBody.onchange = function() { this.act.setObjectValue("description", this.value); };
}

/*************************************************************************
*	Function:	wf_frm_loadUsers
*
*	Purpose:	Task action for workflow
**************************************************************************/
WorkFlow_Action_Notification.prototype.wf_frm_loadUsers = function(cbAssignTo, assigned_to)
{
	var ajax = new CAjax();
	ajax.cbAssignTo = cbAssignTo;
	ajax.assigned_to = assigned_to;
	ajax.onload = function(root)
	{
		// The result will be held in a variable called 'retval'
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
