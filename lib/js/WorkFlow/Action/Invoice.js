/****************************************************************************
*	
*	Class:		WorkFlow_Action_Invoice
*
*	Purpose:	Invoice action for workflow
*
*****************************************************************************/
function WorkFlow_Action_Invoice(obj_type)
{
	this.g_antObject = new CAntObject(obj_type);
}

/*************************************************************************
*	Function:	taskCreateInvoice
*
*	Purpose:	Invoice action for workflow
**************************************************************************/
WorkFlow_Action_Invoice.prototype.taskCreateInvoice = function(con, task_obj)
{
	task_obj.create_obj = "invoice";
	task_obj.type = WF_ATYPE_CREATEOBJ;

	var dv_cnd = alib.dom.createElement("fieldset", con);
	alib.dom.styleSet(dv_cnd, "margin", "6px 0px 3px 3px");
	var lbl = alib.dom.createElement("legend", dv_cnd);
	lbl.innerHTML = "Create Invoice";

	var tbl = alib.dom.createElement("table", dv_cnd);
	var tbody = alib.dom.createElement("tbody", tbl);

	var tmpAntObj = new CAntObject("invoice");

	// Name
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Name Title";
	var td = alib.dom.createElement("td", row);
	var txtName = alib.dom.createElement("input", td);
	txtName.value = task_obj.getObjectValue("name");
	txtName.m_obj = task_obj;
	txtName.onchange = function() { this.m_obj.setObjectValue("name", this.value); };
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = " this will be the name of the invoice";

	// Status
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Status";
	var td = alib.dom.createElement("td", row);
	var inp = tmpAntObj.fieldCreateValueInput(td, "status_id", task_obj.getObjectValue("status_id"));
	inp.m_obj = task_obj;
	inp.onchange = function() { this.m_obj.setObjectValue("status_id", this.value); }

	// Assign To
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Owner";
	var td = alib.dom.createElement("td", row);
	var cbAssignTo = alib.dom.createElement("select", td);
	cbAssignTo.m_act = task_obj;
	cbAssignTo.onchange = function() { this.m_act.setObjectValue("owner_id", this.value); }
	var assigned_to = task_obj.getObjectValue("owner_id");
	for (var i = 0; i < this.g_antObject.getNumFields(); i++)
	{
		var field = this.g_antObject.getField(i);

		if (field.type == "fkey" && field.subtype == "users")
		{
			var varname = "<%"+field.name+"%>";

			if (!assigned_to)
			{
				task_obj.setObjectValue("owner_id", varname);
				assigned_to = varname;
			}

			cbAssignTo[cbAssignTo.length] = new Option(this.g_antObject.title + "." + field.title, varname, 
													   false, (assigned_to==varname)?true:false);
		}
	}
	
	this.wf_frm_loadUsers(cbAssignTo, assigned_to);

	cbAssignTo.m_obj = task_obj;
	cbAssignTo.onchange = function() { this.m_obj.setObjectValue("owner_id", this.value); };

	// Product ID
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Product ID";
	var td = alib.dom.createElement("td", row);
	var txtName = alib.dom.createElement("input", td);
	txtName.value = task_obj.getObjectValue("ent_product_0");
	txtName.m_obj = task_obj;
	txtName.onchange = function() { this.m_obj.setObjectValue("ent_product_0", this.value); };
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = " REQUIRED: enter the id of a product";

	// Quantity
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Quantity";
	var td = alib.dom.createElement("td", row);
	var txtName = alib.dom.createElement("input", td);
	txtName.value = task_obj.getObjectValue("ent_quantity_0");
	txtName.m_obj = task_obj;
	txtName.onchange = function() { this.m_obj.setObjectValue("ent_quantity_0", this.value); };
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = " REQUIRED: the number of the selected product(s) to add";

	// Bill Default Credit Card
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "";
	var td = alib.dom.createElement("td", row);
	var chkBill = alib.dom.createElement("input");
	chkBill.type = "checkbox";
	td.appendChild(chkBill);
	chkBill.checked = (task_obj.getObjectValue("paywithdefcard") == 't') ? true : false;
	chkBill.m_obj = task_obj;
	chkBill.onclick = function() { this.m_obj.setObjectValue("paywithdefcard", (this.checked)?'1':'0'); };
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = " Automatically bill this invoice to customer's default card";

	// Auto-billing triggers
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "";
	var td = alib.dom.createElement("td", row);
	
	// Add success actions
	var hdr = alib.dom.createElement("div", td);
	hdr.innerHTML = "If transaction is approved:";
	alib.dom.styleSetClass(hdr, "formLabel");
	alib.dom.styleSet(hdr, "margin-bottom", "5px");

	var con = alib.dom.createElement("div", td);
	alib.dom.styleSet(con, "margin-bottom", "5px");
	var lbl = alib.dom.createElement("span", con);
	lbl.innerHTML = "Set invoice status to: ";
	var inpCon = alib.dom.createElement("span", con);
	var inp = tmpAntObj.fieldCreateValueInput(inpCon, "status_id", task_obj.getObjectValue("billing_success_status"));
	inp.m_obj = task_obj;
	inp.onchange = function() { this.m_obj.setObjectValue("billing_success_status", this.value); }

	var con = alib.dom.createElement("div", td);
	var lbl = alib.dom.createElement("span", con);
	lbl.innerHTML = "Send email notification to: ";
	var inp = alib.dom.createElement("input", con);
	inp.m_obj = task_obj;
	inp.value = task_obj.getObjectValue("billing_success_notify");
	inp.onchange = function() { this.m_obj.setObjectValue("billing_success_notify", this.value);  }

	// Add failure actions
	var hdr = alib.dom.createElement("div", td);
	hdr.innerHTML = "If transaction is declined:";
	alib.dom.styleSetClass(hdr, "formLabel");
	alib.dom.styleSet(hdr, "margin", "5px 0 5px 0");

	var con = alib.dom.createElement("div", td);
	alib.dom.styleSet(con, "margin-bottom", "5px");
	var lbl = alib.dom.createElement("span", con);
	lbl.innerHTML = "Set invoice status to: ";
	var inpCon = alib.dom.createElement("span", con);
	var inp = tmpAntObj.fieldCreateValueInput(inpCon, "status_id", task_obj.getObjectValue("billing_fail_status"));
	inp.m_obj = task_obj;
	inp.onchange = function() { this.m_obj.setObjectValue("billing_fail_status", this.value); }

	var con = alib.dom.createElement("div", td);
	var lbl = alib.dom.createElement("span", con);
	lbl.innerHTML = "Send email notification to: ";
	var inp = alib.dom.createElement("input", con);
	inp.m_obj = task_obj;
	inp.value = task_obj.getObjectValue("billing_fail_notify");
	inp.onchange = function() { this.m_obj.setObjectValue("billing_fail_notify", this.value);  }
}

/*************************************************************************
*	Function:	wf_frm_loadUsers
*
*	Purpose:	Invoice action for workflow
**************************************************************************/
WorkFlow_Action_Invoice.prototype.wf_frm_loadUsers = function(cbAssignTo, assigned_to)
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
