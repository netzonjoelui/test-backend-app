function taskSendEmail(con, task_obj)
{
	task_obj.type = WF_ATYPE_SENDEMAIL;

	var dv_cnd = alib.dom.createElement("fieldset", con);
	alib.dom.styleSet(dv_cnd, "margin", "6px 0px 3px 3px");
	var lbl = alib.dom.createElement("legend", dv_cnd);
	lbl.innerHTML = "Send Email";

	//var lbl = alib.dom.createElement("div", con);
	//alib.dom.styleSetClass(lbl, "formLabel");
	//alib.dom.styleSet(lbl, "margin", "6px 0px 3px 3px");
	//lbl.innerHTML = "Send Email:";
	//var dv_cnd = alib.dom.createElement("div", con);

	var tbl = alib.dom.createElement("table", dv_cnd);
	var tbody = alib.dom.createElement("tbody", tbl);

	// From
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "From";
	var td = alib.dom.createElement("td", row);
	var txtFrom = alib.dom.createElement("input", td);
	var from = task_obj.getObjectValue("from");
	txtFrom.value = (from) ? from : g_noreply;
	txtFrom.m_task_obj = task_obj;
	txtFrom.onchange = function() { this.m_task_obj.setObjectValue("from", this.value); };

	// To
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	row.vAlign = "top";
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "To";
	var td = alib.dom.createElement("td", row);
	for (var i = 0; i < g_antObject.getNumFields(); i++)
	{
		var field = g_antObject.getField(i);

		if (field.subtype == "email" || (field.type == "fkey" && field.subtype == "users") || (field.type == "object" && field.subtype == "user"))
		{
			var emRow = alib.dom.createElement("div", td);
			var chk = alib.dom.createElement("input");
			chk.type = "checkbox";
			emRow.appendChild(chk);
			chk.value = "<%"+field.name+"%>";
			chk.checked = (task_obj.getObjectMultiValueExists("to", "<%"+field.name+"%>")) ? true : false;
			chk.m_task_obj = task_obj;
			chk.onchange = function() 
			{ 
				if(this.checked) 
					this.m_task_obj.setObjectMultiValue("to", this.value); 
				else 
					this.m_task_obj.delObjectMultiValue("to", this.value); 
			};
			var lbl = alib.dom.createElement("span", emRow);
			lbl.innerHTML = " "+g_antObject.title + "." + field.title;
		}
	}
	var emRow = alib.dom.createElement("div", td);
	var txtTo = alib.dom.createElement("input", emRow);
	txtTo.value = task_obj.getObjectValue("to_other");
	txtTo.m_task_obj = task_obj;
	txtTo.onchange = function() { this.m_task_obj.setObjectValue("to_other", this.value); };
	var lbl = alib.dom.createElement("span", emRow);
	lbl.innerHTML = " other email addresses - separate with commas";

	// Cc
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	row.vAlign = "top";
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Cc";
	var td = alib.dom.createElement("td", row);
	for (var i = 0; i < g_antObject.getNumFields(); i++)
	{
		var field = g_antObject.getField(i);

		if (field.subtype == "email" || (field.type == "fkey" && field.subtype == "users") || (field.type == "object" && field.subtype == "user"))
		{
			var emRow = alib.dom.createElement("div", td);
			var chk = alib.dom.createElement("input");
			chk.type = "checkbox";
			chk.value = "<%"+field.name+"%>";
			chk.checked = (task_obj.getObjectMultiValueExists("cc", "<%"+field.name+"%>")) ? true : false;
			chk.m_task_obj = task_obj;
			chk.onchange = function() 
			{ 
				if(this.checked) 
					this.m_task_obj.setObjectMultiValue("cc", this.value); 
				else 
					this.m_task_obj.delObjectMultiValue("cc", this.value); 
			};
			emRow.appendChild(chk);
			var lbl = alib.dom.createElement("span", emRow);
			lbl.innerHTML = " "+g_antObject.title + "." + field.title;
		}
	}
	var emRow = alib.dom.createElement("div", td);
	var txtCc = alib.dom.createElement("input", emRow);
	txtCc.value = task_obj.getObjectValue("cc_other");
	txtCc.m_task_obj = task_obj;
	txtCc.onchange = function() { this.m_task_obj.setObjectValue("cc_other", this.value); };
	var lbl = alib.dom.createElement("span", emRow);
	lbl.innerHTML = " other email addresses - separate with commas";

	// Bcc
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	row.vAlign = "top";
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Bcc";
	var td = alib.dom.createElement("td", row);
	for (var i = 0; i < g_antObject.getNumFields(); i++)
	{
		var field = g_antObject.getField(i);

		if (field.subtype == "email" || (field.type == "fkey" && field.subtype == "users") || (field.type == "object" && field.subtype == "user"))
		{
			var emRow = alib.dom.createElement("div", td);
			var chk = alib.dom.createElement("input");
			chk.type = "checkbox";
			chk.value = "<%"+ field.name +"%>";
			chk.checked = (task_obj.getObjectMultiValueExists("bcc", "<%"+field.name+"%>")) ? true : false;
			chk.m_task_obj = task_obj;
			chk.onchange = function() 
			{ 
				if(this.checked) 
					this.m_task_obj.setObjectMultiValue("bcc", this.value); 
				else 
					this.m_task_obj.delObjectMultiValue("bcc", this.value); 
			};
			emRow.appendChild(chk);
			var lbl = alib.dom.createElement("span", emRow);
			lbl.innerHTML = " "+g_antObject.title + "." + field.title;
		}
	}
	var emRow = alib.dom.createElement("div", td);
	var txtBcc = alib.dom.createElement("input", emRow);
	txtBcc.value = task_obj.getObjectValue("bcc_other");
	txtBcc.m_task_obj = task_obj;
	txtBcc.onchange = function() { this.m_task_obj.setObjectValue("bcc_other", this.value); };
	var lbl = alib.dom.createElement("span", emRow);
	lbl.innerHTML = " other email addresses - separate with commas";

	// New or Template
	// --------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	td.colSpan = 2;

	var rbtn1 = alib.dom.createElement("input");
	rbtn1.checked = (!task_obj.getObjectValue("fid")) ? true : false;
	rbtn1.type='radio';
	rbtn1.name = "email_compose";
	rbtn1.m_task_obj = task_obj;
	rbtn1.onchange = function() 
	{ 
		document.getElementById("frmComposeNewEmail").style.display = "block"; 
		document.getElementById("frmComposeTemplate").style.display = "none"; 
		document.getElementById("fileNameLabel").innerHTML = "No File Selected&nbsp;&nbsp;"; 
		
		this.m_task_obj.setObjectValue("fid", "");
		this.m_task_obj.setObjectValue("fname", "");
	}
	td.appendChild(rbtn1);
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = "Compose New Email ";

	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='radio';
	rbtn1.checked = (task_obj.getObjectValue("fid")) ? true : false;
	rbtn1.name = "email_compose";
	rbtn1.m_task_obj = task_obj;
	rbtn1.onchange = function()
	{ 
		document.getElementById("frmComposeNewEmail").style.display = "none"; 
		document.getElementById("frmComposeTemplate").style.display = "block"; 
	}
	td.appendChild(rbtn1);
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = "Use Email Template ";

	// ----------------------------------------------------------------
	// Compose New
	// ================================================================
	var div_compose_new = alib.dom.createElement("div", td);
	div_compose_new.id = "frmComposeNewEmail";

	// Subject
	// --------------------------------------------
	var lbl = alib.dom.createElement("div", div_compose_new);
	alib.dom.styleSet(div_compose_new, "display", (task_obj.getObjectValue("fid"))?"none":"block");
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = "Subject";
	var inprow = alib.dom.createElement("div", div_compose_new);
	var txtSubject = alib.dom.createElement("input", inprow);
	txtSubject.value = task_obj.getObjectValue("subject");
	txtSubject.m_task_obj = task_obj;
	txtSubject.onchange = function() { this.m_task_obj.setObjectValue("subject", this.value); };

	var lbl = alib.dom.createElement("div", div_compose_new);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = "Body";
	var inprow = alib.dom.createElement("div", div_compose_new);
	var taBody = alib.dom.createElement("textarea", inprow);
	alib.dom.styleSet(taBody, "height", "200px");
	alib.dom.styleSet(taBody, "width", "98%");
	taBody.value = task_obj.getObjectValue("body");
	taBody.m_task_obj = task_obj;
	taBody.onchange = function() { this.m_task_obj.setObjectValue("body", this.value); };

	// ----------------------------------------------------------------
	// Compose Template
	// ================================================================
	var div_compose_temp = alib.dom.createElement("div", td);
	/*div_compose_temp.id = "frmComposeTemplate";
	alib.dom.styleSet(div_compose_temp, "display", (task_obj.getObjectValue("fid"))?"block":"none");
	var emtfunct = function(dv, lbl, task_obj)
	{
		var cbrowser = new CFileOpen();
		cbrowser.filterType = "emt";
		cbrowser.m_lbl = lbl;
		cbrowser.m_task_obj = task_obj;
		cbrowser.onSelect = function(fid, name, path) 
		{
			this.m_lbl.innerHTML = name + "&nbsp;&nbsp;";
			this.m_task_obj.setObjectValue("fid", fid);
			this.m_task_obj.setObjectValue("fname", name);
			//this.m_imgCon.innerHTML = "<img src='/files/images/"+fid+"/140'>";
			//g_user.image_id = fid;
		}
		cbrowser.showDialog(g_taskDlg);
	}

	var lbl = alib.dom.createElement("span", div_compose_temp);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.id = "fileNameLabel";
	lbl.innerHTML = (task_obj.getObjectValue("fname")) ? task_obj.getObjectValue("fname") : "No File Selected&nbsp;&nbsp;";

	var btn = new CButton("Select File", emtfunct, [div_compose_temp, lbl, task_obj], "b1");
	btn.print(div_compose_temp);*/
    
    var objBrowser = new AntObjectBrowser("html_template");    
    //objBrowser.selectedObjId = this.campaignTemplateId;
    objBrowser.browserCon = div_compose_temp;
    objBrowser.cbData.cls = this;
    objBrowser.onSelect = function(oid, name) 
    {
        this.cbData.cls.campaignTemplateId = oid;
    }
    objBrowser.onLoad = function()
    {
        this.cbData.cls.objBrowserOverlay(this.browserCon);
        
        if(this.cbData.cls.campaignForm.designRadio.template.checked)
            alib.dom.styleSet(this.cbData.cls.condOverlay, "display", "none");
        
    }
    objBrowser.displaySelect();
}
