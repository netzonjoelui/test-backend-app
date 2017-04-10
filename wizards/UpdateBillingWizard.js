/****************************************************************************
*	
*	Class:		UpdateBillingWizard
*
*	Purpose:	Wizard for updateing billing information
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*****************************************************************************/
function UpdateBillingWizard(userid)
{
	this.userId = userid;
	this.edition = "enterprise";
	this.card = new Object();
	this.card.num = "";
	this.card.type = "visa";
	this.card.name = "";
	this.card.exp_month = "";
	this.card.exp_year = "";
	this.card.ccid = "";

	this.address = new Object();
	this.address.street = "";
	this.address.street2 = "";
	this.address.city = "";
	this.address.state = "";
	this.address.zip = "";

	this.steps = new Array();
	this.steps[0] = "Billing Information";
	this.steps[1] = "Finished";
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display wizard
**************************************************************************/
UpdateBillingWizard.prototype.showDialog = function(parentDlg)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.m_dlg = new CDialog("Billing Information", this.parentDlg);
	this.m_dlg.f_close = true;
	var dlg = this.m_dlg;

	this.body_dv = alib.dom.createElement("div");

	dlg.customDialog(this.body_dv, 650, 510);

	this.showStep(0);
}

/*************************************************************************
*	Function:	showStep
*
*	Purpose:	Used to display the contents of a given step
**************************************************************************/
UpdateBillingWizard.prototype.showStep = function(step)
{
	this.body_dv.innerHTML = ""; 
	this.cbTemplates = null;
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

	switch (step)
	{
	case 0:
		this.buildFrmData(div_main);

		this.veriftyStep = function()
		{
			if (this.card.name == "")
			{
				this.verify_step_data.message = "Please enter the name on your credit card";
				return false;
			}

			if (this.card.exp_month == "")
			{
				this.verify_step_data.message = "Please enter the month your credit card expires";
				return false;
			}

			if (this.card.exp_year == "")
			{
				this.verify_step_data.message = "Please enter the year your credit card expires";
				return false;
			}

			/*
			if (this.card.ccid == "")
			{
				this.verify_step_data.message = "Please enter the year your credit card expires";
				return false;
			}
			*/

			if (this.address.street == "")
			{
				this.verify_step_data.message = "Please enter your billing address street";
				return false;
			}

			if (this.address.city == "")
			{
				this.verify_step_data.message = "Please enter your billing address city";
				return false;
			}

			if (this.address.state == "")
			{
				this.verify_step_data.message = "Please enter your billing address state";
				return false;
			}

			if (this.address.zip == "")
			{
				this.verify_step_data.message = "Please enter your billing address zip";
				return false;
			}

			if (!this.testCcard())
			{
				return false;
			}

			return true;
		}
		break;

	case 1:
		div_main.innerHTML = "<h1>Finished!</h1>Thank you for taking the time to update your billing information. Click \"Finished\" below to continue using your account.";
		break;
	}

	// Buttons
	// ---------------------------------------------------------
	var dv_btn = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSet(dv_btn, "margin-top", "8px");
	alib.dom.styleSet(dv_btn, "text-align", "right");

	var btn = new CButton("Back", function(cls, step) { cls.showStep(step-1); }, [this, step]);
	btn.print(dv_btn);
	if (step == 0)
		btn.disable();

	if (step == (this.steps.length - 1))
	{
		var btn = new CButton("Finish", function(cls) { cls.save(); }, [this], "b2");
		btn.print(dv_btn);
	}
	else
	{
		var next_funct = function(cls, step)
		{
			if (cls.veriftyStep())
			{
				cls.showStep(step+1);
			}
			else
			{
				ALib.Dlg.messageBox(cls.verify_step_data.message, cls.m_dlg);
			}
		}

		var btn = new CButton("Next", next_funct, [this, step], "b2");
		btn.print(dv_btn);
	}

	var btn = new CButton("Cancel", function(dlg, cls) { cls.cancel();  }, [this.m_dlg, this], "b3");
	btn.print(dv_btn);
}

/*************************************************************************
*	Function:	veriftyStep
*
*	Purpose:	This function should be over-rideen with each step
**************************************************************************/
UpdateBillingWizard.prototype.veriftyStep = function()
{
	return true;
}

/*************************************************************************
*	Function:	cancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
UpdateBillingWizard.prototype.cancel = function()
{
	if (confirm("Are you sure you want to exit?"))
	{
		this.m_dlg.hide(); 
		this.onCancel();
	}
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
UpdateBillingWizard.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	onFinished
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
UpdateBillingWizard.prototype.onFinished = function()
{
}

/*************************************************************************
*	Function:	save
*
*	Purpose:	Save settings
**************************************************************************/
UpdateBillingWizard.prototype.save = function()
{
	this.m_dlg.hide();

	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Saving, please wait...";
	dlg.statusDialog(dv_load, 150, 100);

	var args = [["ccard_number", this.card.num],["ccard_type", this.card.type],
				["ccard_name", this.card.name],["ccard_exp_month", this.card.exp_month],
				["ccard_exp_year", this.card.exp_year],["ccard_ccid", this.card.ccid],
				["address_street", this.address.street],["address_street2", this.address.street2],
				["address_city", this.address.city],["address_state", this.address.state],
				["address_zip", this.address.zip]];
				
	//ALib.m_debug = true;
	//AJAX_TRACE_RESPONSE = true;    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.dlg = dlg;
    ajax.onload = function(ret)
    {
        if (ret)
        {
            this.dlg.hide();
            ALib.statusShowAlert("Saved!", 3000, "bottom", "right");
            this.cls.onFinished();
        }
    };
    ajax.exec("/controller/Admin/updateBilling", args);
}

/*************************************************************************
*	Function:	testCcard
*
*	Purpose:	Test a credit card to make sure it is valid
**************************************************************************/
UpdateBillingWizard.prototype.testCcard = function()
{
	var args = [["edition", this.edition], 
				["ccard_number", this.card.num],
				["ccard_type", this.card.type],
				["ccard_name", this.card.name],
				["ccard_exp_month", this.card.exp_month],
				["ccard_exp_year", this.card.exp_year],
				["ccard_ccid", this.card.ccid],
				["address_street", this.address.street],
				["address_street2", this.address.street2],
				["address_city", this.address.city],
				["address_state", this.address.state],
				["address_zip", this.address.zip]];

	var dlg = new CDialog(null, this.m_dlg);
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Checking billing info, please wait...";
	dlg.statusDialog(dv_load, 250, 100);
	var ajax = new CAjax();
	var url = "/objects/xml_get_objectdef.php?function=renew_test_ccard";
	root = ajax.exec(url, args, false); // disable async so response is returned immediately
	dlg.hide();
	if (root.getNumChildren())
	{
		var ret = unescape(root.getChildNodeValByName("retval"))
		var message = unescape(root.getChildNodeValByName("message"))
		if (ret != 1)
		{
			this.verify_step_data.message = message;
			return false;
		}
	}

	return true;
}

/*************************************************************************
*	Function:	addObject
*
*	Purpose:	Add an object to the list to merge
**************************************************************************/
UpdateBillingWizard.prototype.addObject = function(oid)
{
	var obj = new CAntObject(this.obj_type, oid);
	obj.load();
	this.objects[this.objects.length] = obj;
}

/*************************************************************************
*	Function:	buildFrmData
*
*	Purpose:	Create form for selecting data
**************************************************************************/
UpdateBillingWizard.prototype.buildFrmData = function(con)
{
	var p = alib.dom.createElement("p", con);
	p.innerHTML = "We need to update the credit card we have on file for your account. If you are not the administrator of this account, please contact the person in charge of accounts and/or technology in your organization and let them know the billing information should be updated for this account. If you have any questions feel free to call or email support at support@aereus.com. " +
					"<br><br>We very much appreciate your business and value your feedback.<br><br>";

	// Credit Card
	// ----------------------------------------------------------
	var p = alib.dom.createElement("h2", con);
	alib.dom.styleSet(p, "margin-top", "10px");
	p.innerHTML = "Billing Information:";

	var tbl = alib.dom.createElement("table", con);
	alib.dom.styleSet(tbl, "width", "100%");
	var tbody = alib.dom.createElement("tbody", tbl);
	var row = alib.dom.createElement("tr", tbody);
	
	// Credit Card type
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Credit Card Type";
	var td = alib.dom.createElement("td", row);
	var card_sel = alib.dom.createElement("select", td);
	card_sel.cls = this;
	card_sel.onchange = function() { this.cls.card.type = this.value; }
	card_sel[card_sel.length] = new Option("Visa", "visa", false, (this.card.type=="visa")?true:false);
	card_sel[card_sel.length] = new Option("Master Card", "mastercard", false, (this.card.type=="mastercard")?true:false);
	card_sel[card_sel.length] = new Option("American Express", "amex", false, (this.card.type=="amex")?true:false);
	
	// CCard Number
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Credit Card Number";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.value = this.card.num;
	inp.cls = this;
	inp.onchange = function() { this.cls.card.num = this.value; }
	td.appendChild(inp);
	var row = alib.dom.createElement("tr", tbody);

	// Expiration
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Expiration";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.size = 2;
	inp.maxLength = 2;
	inp.value = this.card.exp_month;
	inp.cls = this;
	inp.onchange = function() { this.cls.card.exp_month = this.value; }
	td.appendChild(inp);
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = " (MM) ";
	var inp = alib.dom.createElement("input");
	inp.size = 4;
	inp.maxLength = 4;
	inp.value = this.card.exp_year;
	inp.cls = this;
	inp.onchange = function() { this.cls.card.exp_year = this.value; }
	td.appendChild(inp);
	var lbl = alib.dom.createElement("span", td);
	lbl.innerHTML = " (YYYY) ";

	// Name on Card
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Name on Card";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.value = this.card.name;
	inp.cls = this;
	inp.onchange = function() { this.cls.card.name = this.value; }
	td.appendChild(inp);

	// Billing Address
	// ----------------------------------------------------------
	var p = alib.dom.createElement("h2", con);
	alib.dom.styleSet(p, "margin-top", "10px");
	p.innerHTML = "Billing Address:";

	var tbl = alib.dom.createElement("table", con);
	alib.dom.styleSet(tbl, "width", "100%");
	var tbody = alib.dom.createElement("tbody", tbl);

	var row = alib.dom.createElement("tr", tbody);
	// Street 1
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Street 1";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.value = this.address.street;
	inp.cls = this;
	inp.onchange = function() { this.cls.address.street = this.value; }
	td.appendChild(inp);
	// Street 2
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Street 2";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.value = this.address.street2;
	inp.cls = this;
	inp.onchange = function() { this.cls.address.street2 = this.value; }
	td.appendChild(inp);
	var row = alib.dom.createElement("tr", tbody);

	var row = alib.dom.createElement("tr", tbody);
	// City
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "City";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.value = this.address.city;
	inp.cls = this;
	inp.onchange = function() { this.cls.address.city = this.value; }
	td.appendChild(inp);
	// State
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "State";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.value = this.address.state;
	inp.cls = this;
	inp.onchange = function() { this.cls.address.state = this.value; }
	td.appendChild(inp);

	var row = alib.dom.createElement("tr", tbody);
	// Zip
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Zip";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.value = this.address.zip;
	inp.cls = this;
	inp.onchange = function() { this.cls.address.zip = this.value; }
	td.appendChild(inp);
}

/*************************************************************************
*	Function:	setFieldUse
*
*	Purpose:	Set which object_id to use
**************************************************************************/
UpdateBillingWizard.prototype.setFieldUse = function(field_name, oid)
{
	for (var i = 0; i < this.fields.length; i++)
	{
		if (this.fields[i].name == field_name)
		{
			this.fields[i].object_id = oid;
			return;
		}
	}

	// Not yet set, put the the first object
	this.fields[this.fields.length] = {name:field_name, object_id:oid};
	return;
}

/*************************************************************************
*	Function:	getFieldUse
*
*	Purpose:	Find out what object is to be used for field_name
**************************************************************************/
UpdateBillingWizard.prototype.getFieldUse = function(field_name)
{
	for (var i = 0; i < this.fields.length; i++)
	{
		if (this.fields[i].name == field_name)
			return this.fields[i].object_id;
	}

	// Not yet set
	this.setFieldUse(field_name, this.objects[0].id);
	return this.objects[0].id;
}

