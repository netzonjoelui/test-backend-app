/****************************************************************************
*	
*	Class:		CRenewWizard
*
*	Purpose:	Wizard for renewing ANT accounts
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*****************************************************************************/
function CRenewWizard(userid)
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
	this.steps[0] = "Trial Expired";
	this.steps[1] = "Billing Information";
	this.steps[2] = "Finished";
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display wizard
**************************************************************************/
CRenewWizard.prototype.showDialog = function(parentDlg)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.m_dlg = new CDialog("Renew Account", this.parentDlg);
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
CRenewWizard.prototype.showStep = function(step)
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
		var p = alib.dom.createElement("h1", div_main);
		p.innerHTML = "Your Account Is Ready To Be Renewed";

		var p = alib.dom.createElement("h3", div_main);
		alib.dom.styleSet(p, "margin", "5px 0 3px 0");
		p.innerHTML = "The free trial period for this account has expired.";

		var p = alib.dom.createElement("p", div_main);
		p.innerHTML = "Fortunately it is very easy to continue using ANT by clicking \"Next\" below and following the steps in this wizard. " +
					  "We sincerely hope you have found ANT to be useful during your free trial period and very much look forward " +
					  "to continuing to partner with you by proving the tools to easily and effectively manage your data.<br /><br />" +
					  "If you do not wish to renew at this time simply click the \"Cancel Renewal\" button below.";

		this.veriftyStep = function()
		{
			return true;
		}
		break;
	case 1:
		this.buildFrmData(div_main);

		this.veriftyStep = function()
		{
			if (this.card.name == "")
			{
				this.verify_step_data.message = "Please the name on your credit card";
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

	case 2:
		div_main.innerHTML = "<h1>Congratulations!</h2><h3>Your account has been successfully renewed.</h3>" +
						   	 "We very much appreciate your business and value your feedback. If at any time you think " +
						   	 "of anything that we could do to improve your experience while using this application, please let us know.<br /><br />" +
							 "<h3>Click \"Finish\" below to start using your account.</h3>";
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

	var btn = new CButton("Cancel Renewal", function(dlg, cls) { cls.cancel();  }, [this.m_dlg, this], "b3");
	btn.print(dv_btn);
}

/*************************************************************************
*	Function:	veriftyStep
*
*	Purpose:	This function should be over-rideen with each step
**************************************************************************/
CRenewWizard.prototype.veriftyStep = function()
{
	return true;
}

/*************************************************************************
*	Function:	cancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CRenewWizard.prototype.cancel = function()
{
	if (confirm("Are you sure you want to cancel your ANT account?"))
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
CRenewWizard.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	onFinished
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CRenewWizard.prototype.onFinished = function()
{
}

/*************************************************************************
*	Function:	save
*
*	Purpose:	Save settings
**************************************************************************/
CRenewWizard.prototype.save = function()
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

	this.m_dlg.hide();

	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Saving, please wait...";
	dlg.statusDialog(dv_load, 250, 100);

	//ALib.m_debug = true;
	//AJAX_TRACE_RESPONSE = true;
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.dlg = dlg;
    ajax.onload = function(ret)
    {
        this.dlg.hide();

        if (ret)
        {
            this.cls.onFinished(ret, this.cls.message);
        }
    };
    ajax.exec("/controller/Admin/renewAccount", args);
}

/*************************************************************************
*	Function:	testCcard
*
*	Purpose:	Test a credit card to make sure it is valid
**************************************************************************/
CRenewWizard.prototype.testCcard = function()
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
CRenewWizard.prototype.addObject = function(oid)
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
CRenewWizard.prototype.buildFrmData = function(con)
{
	var p = alib.dom.createElement("h2", con);
	p.innerHTML = "Select an edition:";

	var p = alib.dom.createElement("p", con);
	p.innerHTML = "During the trial period you have been enjoying the enhanced features of the enterprise edition including: workflow automation, custom reports, video email, unmetered storage, enterprise support, custom database objects, and more. Select the edition you would like to continue using. For more information and pricing, <a href='http://www.aereus.com/ant/signup' target='_blank'>click here</a>.";

	// Edition select
	var ed_sel = alib.dom.createElement("select", con);
	ed_sel.cls = this;
	ed_sel.onchange = function() { this.cls.edition = this.value; }
	ed_sel[ed_sel.length] = new Option("Enterprise ($39 user/mo)", "enterprise", false, (this.edition=="enterprise")?true:false);
	ed_sel[ed_sel.length] = new Option("Professional ($20 user/mo)", "professional", false, (this.edition=="professional")?true:false);


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
CRenewWizard.prototype.setFieldUse = function(field_name, oid)
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
CRenewWizard.prototype.getFieldUse = function(field_name)
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

