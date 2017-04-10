function taskCreateInvoice(con, task_obj)
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
	txtName.m_obj = task_obj;
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
	for (var i = 0; i < g_antObject.getNumFields(); i++)
	{
		var field = g_antObject.getField(i);

		if (field.type == "fkey" && field.subtype == "users")
		{
			var varname = "<%"+field.name+"%>";

			if (!assigned_to)
			{
				task_obj.setObjectValue("owner_id", varname);
				assigned_to = varname;
			}

			cbAssignTo[cbAssignTo.length] = new Option(g_antObject.title + "." + field.title, varname, 
													   false, (assigned_to==varname)?true:false);
		}
	}
	
	wf_frm_loadUsers(cbAssignTo, assigned_to);

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

	// Buill Default Credit Card`
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
	lbl.innerHTML = " Automatically bill this invoce to customer's default card";
}
