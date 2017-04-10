/****************************************************************************
*	
*	Class:		CAntUserWizard
*
*	Purpose:	Wizard for inserting a new video
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*	Timezone, Theme, Bookmark, Zipcode, Email, Import, SmartPhone, ANTClient, Tutorials
*
*****************************************************************************/

var g_user = new Object();

function CAntUserWizard(user_id)
{
	this.user_id			= user_id;
	this.users				= new Array();
	this.login_file_id 		= null;
	this.welcome_file_id	= null;
	this.settings			= new Object();
	this.email_domain		= "";
	this.email_inc_server	= "";
	this.email_mode			= "server"; // server|client|alias - server=mx, client=pop3, alias=tmp email & forward
	this.email_display_name = "";
	this.email_address		= "";
	this.email_replyto		= "";
	this.fUseAntmail		= true;

	this.steps = new Array();
	this.steps[0] = "Getting Started";
	this.steps[1] = "Profile Information";
	this.steps[2] = "Email Setup";
	this.steps[3] = "Import Contacts";
	this.steps[4] = "ANTClient Setup";
	this.steps[5] = "Smartphone Setup";
	this.steps[6] = "Finished";

	this.getSettings();
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display wizard
**************************************************************************/
CAntUserWizard.prototype.showDialog = function(parentDlg, step)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.m_dlg = new CDialog("Personal Settings", this.parentDlg);
	this.m_dlg.f_close = true;
	var dlg = this.m_dlg;

	this.body_dv = alib.dom.createElement("div");

	dlg.customDialog(this.body_dv, 650, 520);

	var stp = (step) ? step : 0;
	this.showStep(stp);
}

/*************************************************************************
*	Function:	showStep
*
*	Purpose:	Used to display the contents of a given step
**************************************************************************/
CAntUserWizard.prototype.showStep = function(step)
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
		var h = alib.dom.createElement("h1", div_main);
		h.innerHTML = "Personal Settings Wizard";

		var p = alib.dom.createElement("p", div_main);
		p.innerHTML = "<strong>Welcome to Aereus Network Tools!</strong> This wizard will guide you through setting up your user account." +
					  "<br /><br />If you would like to start using ANT immediately simply skip this wizard by clicking \"Cancel\" below.";

		// Facebook integration
		// ----------------------------------------------------------
		var h = alib.dom.createElement("h3", div_main);
		alib.dom.styleSet(h, "margin-top", "30px");
		h.innerHTML = "Facebook Integration";
		var lbl = alib.dom.createElement("p", div_main);
		//alib.dom.styleSetClass(lbl, "notice");
		lbl.innerHTML = "Linking your facebook account allows for personalized information like photos and interests to be integrated into your ANT account.";

		var lbl = alib.dom.createElement("div", div_main);
		lbl.innerHTML = "Getting status";
		this.socialFBGetButton(lbl);

		this.veriftyStep = function()
		{
			return true;
		}
		break;
	case 1:
		var p = alib.dom.createElement("h1", div_main);
		p.innerHTML = "General Information";

		this.loadProfile(div_main);

		this.veriftyStep = function()
		{
			this.saveProfile();
			return true;
		}
		break;
	case 2:
		var p = alib.dom.createElement("h1", div_main);
		p.innerHTML = "Setup Email:";

		this.frmEmail(div_main);

		this.veriftyStep = function()
		{
			return true;
		}
		break;
	case 3:
		var p = alib.dom.createElement("h1", div_main);
		p.innerHTML = "Import Personal Contacts:";

		var p = alib.dom.createElement("p", div_main);
		p.innerHTML = "This is usually different from your company or organization's global contact/customer list. For example, a friend or relative could be a personal contact but not necessarily a customer. If your customer and personal contact database is the same data, skip this step by clicking \"Next\" below. Otherwise import your contacts by following these instructions.";

		this.frmImport(div_main);

		this.veriftyStep = function()
		{
			return true;
		}
		break;
	case 4:
		var p = alib.dom.createElement("h1", div_main);
		p.innerHTML = "Install ANTClient (optional)";

		var p = alib.dom.createElement("p", div_main);
		p.innerHTML = "ANTClient is an application that you can use if your computer is running Windows XP or newer and it can synchronize your document/files and Outlook data with ANT.<br /><br /><a href='http://www.aereus.com/downloads/antclient_setup.exe'>Click here to download ANTClient</a><br /><br />";

		var p = alib.dom.createElement("h3", div_main);
		p.innerHTML = "Instructions:";

		var p = alib.dom.createElement("p", div_main);
		p.innerHTML = "After the installation is complete, start \"ANT Client\" by going to Start/Windows Button->All Programs->Aereus and click " +
				 	  "ANTClient. You will be prompted for some basic settings, enter the following:<br /><br />" +
					  "ANT Server: <strong>"+document.domain+"</strong><br />" +
					  "User: <strong>"+g_user.name+"</strong><br />" +
					  "Pass: <strong>Use the password you just set on the profile page</strong>";
		this.veriftyStep = function()
		{
			return true;
		}
		break;
	case 5:
		var p = alib.dom.createElement("h1", div_main);
		p.innerHTML = "Do you have any of the following smart-phones?";

		this.frmSmartphones(div_main);

		break;
	case 6:
		div_main.innerHTML = "<h1>Congratulations!</h1><h3>Your account has been set up.</h3>Click 'Finish' below to close this wizard and " +
							 "start using ANT immediately.<br /><br />" +
							 "<h3>Learn More (recommended)</h3>"+
							 "<p>We highly recommend taking a few minutes to view some videos that will help you " +
							 "get the most out of Aereus Network Tools.</p><br />" +
							 "<a href='javascript:void(0);' onclick=\"loadSupportDoc('121');\">View Tutorials</a>";
		break;
	}

	// Buttons
	// ---------------------------------------------------------
	var dv_btn = alib.dom.createElement("div", this.body_dv);
	alib.dom.styleSetClass(dv_btn, "wizardFooter");

	var btn = new CButton("Back", function(cls, step) { cls.showStep(step-1); }, [this, step]);
	btn.print(dv_btn);
	if (step == 0)
		btn.disable();

	if (step == (this.steps.length - 1))
	{
		var btn = new CButton("Finish", function(cls) { cls.save(); }, [this]);
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

	var btn = new CButton("Cancel", function(dlg, cls) {  dlg.hide(); cls.onCancel(); }, [this.m_dlg, this], "b3");
	btn.print(dv_btn);
}

/*************************************************************************
*	Function:	veriftyStep
*
*	Purpose:	This function should be over-rideen with each step
**************************************************************************/
CAntUserWizard.prototype.veriftyStep = function()
{
	return true;
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CAntUserWizard.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	onFinished
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CAntUserWizard.prototype.onFinished = function()
{
}


/*************************************************************************
*	Function:	save
*
*	Purpose:	Save settings
**************************************************************************/
CAntUserWizard.prototype.save = function()
{
	var args = [["email_address", this.email_address],
				["email_replyto", this.email_replyto], 
				["email_display_name", this.email_display_name]];
    
	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Applying settings, please wait...";
	dlg.statusDialog(dv_load, 250, 100);
    
    /*function cbdone(ret, cls, dlg)
    {
        if (!ret['error'])
        {
            cls.onFinished(ret, cls.message);
            dlg.hide();
        }
        else
        {
            alert("There was an error: " + ret['error']);
        }
    }
    var rpc = new CAjaxRpc("/controller/Admin/saveWizardUser", "saveWizardUser", args, cbdone, [this, dlg], AJAX_POST, true, "json");*/
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.dlg = dlg;
    ajax.onload = function(ret)
    {
        this.cbData.dlg.hide();
        if (!ret['error'])
        {
            this.cbData.cls.m_dlg.hide();
            this.cbData.cls.onFinished(ret, this.cbData.cls.message);
        }            
        else
        {            
            ALib.statusShowAlert("There was an error: " + ret['error'], 3000, "bottom", "right");
            this.cbData.cls.showStep(ret['step']);
        }
            
    };
    ajax.exec("/controller/Admin/saveWizardUser", args);
}

/*************************************************************************
*	Function:	buildImportCustomer
*
*	Purpose:	Build Import Customer Page
**************************************************************************/
CAntUserWizard.prototype.buildImportCustomer = function(con)
{

	var p = alib.dom.createElement("p", con);
	p.innerHTML = "If you already have an existing database of customers then we highly recommended importing your data into ANT. Most contact management applications have an \"Export To CSV\" which will produce a file that can be imported into ANT.";

	var lbl = alib.dom.createElement("h3", con);
	lbl.innerHTML = "Step 1 - Export Your Data";

	var ul = alib.dom.createElement("ul", con);
	
	var li = alib.dom.createElement("li", ul);
	li.innerHTML = "<a href='#' target='_blank'>How to export contacts from Microsoft Outlook</a>";

	var li = alib.dom.createElement("li", ul);
	li.innerHTML = "<a href='#' target='_blank'>How to export contacts from ACT!</a>";

	var li = alib.dom.createElement("li", ul);
	li.innerHTML = "<a href='#' target='_blank'>How to export contacts from Salesforce</a>";

	var li = alib.dom.createElement("li", ul);
	li.innerHTML = "<a href='#' target='_blank'>General Exporting Guidelines</a>";

	var lbl = alib.dom.createElement("h3", con);
	lbl.innerHTML = "Step 2 - Import Your Data";

	var btn = new CButton("Start Import Wizard", function(userid, dlg) { var ob = new CAntObjectImpWizard("customer", userid); ob.showDialog(dlg); }, [this.user_id, this.m_dlg]);
	btn.print(con);
}

/*************************************************************************
*	Function:	buildEmailSetup
*
*	Purpose:	Build Email Setup Page
**************************************************************************/
CAntUserWizard.prototype.buildEmailSetup = function(con)
{
	var p = alib.dom.createElement("p", con);
	p.innerHTML = "ANT has a very powerful and easy to use email application which provides seamless integration with all other applications including the CRM and the Project Manager. This integration is very useful for features like automatically tracking all email activity to and from customers.";

	var div_new = alib.dom.createElement("div", con);
	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='radio';
	rbtn1.name = 'merge';
	rbtn1.cls = this;
	rbtn1.onclick = function() { this.cls.fUseAntmail = true; }
	div_new.appendChild(rbtn1);
	rbtn1.checked = this.fUseAntmail;
	var lbl = alib.dom.createElement("span", div_new);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = " Use ANT Email (recommended)";
	var desc = alib.dom.createElement("div", div_new);
	alib.dom.styleSet(desc, "margin", "5px");
	desc.innerHTML = "This does not mean you must immediately redirect all your email to ANT. ANT can be used to manage all your email and replace applications like Outlook, but you can also leave your current solution in place and configure ANT to gather email from your existing email server so messages are delivered both to ANT and your existing server.";

	var div_template = alib.dom.createElement("div", con);
	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='radio';
	rbtn1.name = 'merge';
	rbtn1.cls = this;
	rbtn1.onclick = function() { this.cls.fUseAntmail = false; }
	div_template.appendChild(rbtn1);
	rbtn1.checked = (this.fUseAntmail)?false:true;
	var lbl = alib.dom.createElement("span", div_template);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = " Do not use ANT Email";
	var desc = alib.dom.createElement("div", div_template);
	alib.dom.styleSet(desc, "margin", "5px");
	desc.innerHTML = "Email can and will still be sent from ANT to users and customers, but all email traffic will be forwarded to your existing email server.";
}

/*************************************************************************
*	Function:	frmProfile
*
*	Purpose:	Display user profile
**************************************************************************/
CAntUserWizard.prototype.frmProfile = function(main_con)
{
	var innerCon = alib.dom.createElement("div", main_con);
	alib.dom.styleSet(innerCon, "margin-top", "3px");

	// ----------------------------------------------------------
	// Right column
	// ----------------------------------------------------------
	var rdv = alib.dom.createElement("div", innerCon);
	alib.dom.styleSet(rdv, "float", "right");
	alib.dom.styleSet(rdv, "width", "150px");

	// Image
	// ----------------------------------------------------------
	var frm1 = new CWindowFrame("Image");
	frm1.print(rdv);
	var frmcon = frm1.getCon();
	alib.dom.styleSet(frmcon, "text-align", "center");
	var td = alib.dom.createElement("div", frmcon);
	td.innerHTML = (g_user.image_id) ? "<img src='/files/images/"+g_user.image_id+"/140'>" : "";
	td.onclick = function() { this.changeImage(); }
	td.changeImage = function()
	{
		var cbrowser = new AntFsOpen();
		cbrowser.filterType = "jpg:jpeg:png:gif";
		cbrowser.m_imgCon = this;
		cbrowser.onSelect = function(fid, name, path) 
		{
			this.m_imgCon.innerHTML = "<img src='/files/images/"+fid+"/140'>";
			g_user.image_id = fid;
		}
		cbrowser.showDialog();
	}
	var btn = new CButton("Select Image", function(div) { div.changeImage(); }, [td], "b1");
	btn.print(frmcon);
	var btn2 = new CButton("No Image", function(div) { td.innerHTML = ""; g_user.image_id=''; }, [td], "b3");
	btn2.print(frmcon);


	// ----------------------------------------------------------
	// Left column
	// ----------------------------------------------------------
	var ldv = alib.dom.createElement("div", innerCon);
	alib.dom.styleSet(ldv, "margin", "0 153px 0 0");

	// Details
	// ----------------------------------------------------------
	var tbl = alib.dom.createElement("table", ldv);
	var tbody = alib.dom.createElement("tbody", tbl);

	// User name and password
	// --------------------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	// Username
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "User Name";
	var td = alib.dom.createElement("td", row);
	td.innerHTML = g_user.name;
	// Password
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Password";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.type = "password";
	inp.value = "      ";
	inp.onchange = function() { g_user.password = this.value; }
	td.appendChild(inp);		

	// Full Name & Active
	// --------------------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	// Full Name
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Full Name";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input", td);
	inp.value = g_user.full_name;
	inp.onchange = function() { g_user.full_name = this.value; }
	// Verify Password
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Verify Password";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.type = "password";
	inp.value = "      ";
	inp.onchange = function() { g_user.password_verify = this.value; }
	td.appendChild(inp);		

	// Theme and Timezone
	// --------------------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	// Office Phone
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Office Phone";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input", td);
	inp.value = g_user.phone;
	inp.onchange = function() { g_user.phone = this.value; }
	

	// Timezone
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Timezone";
	var td = alib.dom.createElement("td", row);
	var dm = new CDropdownMenu(null, "300px");
	
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {
        for(timezone in ret)
        {
            var currentTimezone = ret[timezone];
            
            dm.addEntry(currentTimezone, function(currentTimezone) 
                                { 
                                    document.getElementById('w_h_tz').innerHTML = currentTimezone;
                                    g_user.timezone = currentTimezone; 
                                }, 
                                null, null, [currentTimezone]);
        }
        
        try
        {            
            document.getElementById('w_h_tz').innerHTML = "Select";
        }
        catch (e) {}
    };
    ajax.exec("/controller/User/getTimezones");
    
	td.appendChild(dm.createButtonMenu("<span id='w_h_tz'>Loading...</span>"));

	// Cell phone & Carrier
	// --------------------------------------------------------
	var row = alib.dom.createElement("tr", tbody);
	// Full Name
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Mobile Phone";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input", td);
	inp.value = g_user.mobile_phone;
	inp.onchange = function() { g_user.mobile_phone = this.value; }

	/*
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Carrier";
	var td = alib.dom.createElement("td", row);
	var sel_carrier = alib.dom.createElement("select", td);
	sel_carrier.onchange = function() { g_user.mobile_phone_carrier = this.value; }
	for (var c = 0; c < g_smscarriers.length; c++)
	{
		sel_carrier[sel_carrier.length] = new Option(g_smscarriers[c][0], g_smscarriers[c][1], false, (g_user.mobile_phone_carrier == g_smscarriers[c][1])?true:false);
	}
	*/
}

/*************************************************************************
*	Function:	loadProfile
*
*	Purpose:	Load user profile
**************************************************************************/
CAntUserWizard.prototype.loadProfile = function(con)
{
	var ajax = new CAjax();
	ajax.clsref = this;
	ajax.con = con;
	ajax.onload = function(root)
	{
		if (root.getNumChildren())
		{
			var user_node = root.getChildNode(0);
			g_user.uid					= unescape(user_node.getChildNodeValByName("id"));
			g_user.image_id				= unescape(user_node.getChildNodeValByName("image_id"));
			g_user.theme				= unescape(user_node.getChildNodeValByName("theme"));
			g_user.timezone				= unescape(user_node.getChildNodeValByName("timezone"));
			g_user.name 				= unescape(user_node.getChildNodeValByName("name"));
			g_user.full_name 				= unescape(user_node.getChildNodeValByName("full_name"));
			g_user.f_active 				= unescape(user_node.getChildNodeValByName("active"));
			g_user.password					= unescape(user_node.getChildNodeValByName("password"));
			g_user.mobile_phone 			= unescape(user_node.getChildNodeValByName("mobile_phone"));
			g_user.mobile_phone_carrier		= unescape(user_node.getChildNodeValByName("mobile_phone_carrier"));
			g_user.phone 					= unescape(user_node.getChildNodeValByName("phone"));
			g_user.email 					= unescape(user_node.getChildNodeValByName("email"));
			this.clsref.email_display_name 	= unescape(user_node.getChildNodeValByName("email_display_name"));
			this.clsref.email_address 		= unescape(user_node.getChildNodeValByName("email_address"));
			this.clsref.email_replyto 		= unescape(user_node.getChildNodeValByName("email_replyto"));
		}

		this.clsref.frmProfile(con);
	};
	var url = "/users/xml_get_users.php?det=full&profile=true";
	ajax.exec(url);
}

/*************************************************************************
*	Function:	saveProfile
*
*	Purpose:	Save profile data (like save user)
**************************************************************************/
CAntUserWizard.prototype.saveProfile = function()
{
	var args = [["full_name", g_user.full_name], ["image_id", g_user.image_id],
				["theme", g_user.theme], ["timezone", g_user.timezone],
				["password", g_user.password], ["mobile_phone", g_user.mobile_phone], 
				["phone", g_user.phone], ["uid", this.user_id]];
    	
    //var rpc = new CAjaxRpc("/controller/User/saveWizardUser", "saveUserWiz", args, cbdone, null, AJAX_POST, true, "json");
    ajax = new CAjax('json');    
    ajax.exec("/controller/User/saveWizardUser", args);
}

/*************************************************************************
*	Function:	frmEmail
*
*	Purpose:	Display email information
**************************************************************************/
CAntUserWizard.prototype.frmEmail = function(main_con)
{
	if (!this.email_display_name)
		this.email_display_name = g_user.full_name;
	if (!this.email_address)
		this.email_address = g_user.email;

	var lbl = alib.dom.createElement("div", main_con);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = "Your Display Name (Full Name):";

	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 128);
	inp.type = "text";
	inp.style.width = "300px";
	inp.cls = this
	inp.value = this.email_display_name;
	inp.onchange = function() { this.cls.email_display_name = this.value; };
	main_con.appendChild(inp);

	var lbl = alib.dom.createElement("div", main_con);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = "Your Email Address:";

	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 128);
	inp.type = "text";
	inp.style.width = "300px";
	inp.cls = this;
	inp.value = this.email_address;
	inp.onchange = function() { this.cls.email_address = this.value; };
	main_con.appendChild(inp);

	var lbl = alib.dom.createElement("div", main_con);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = "Reply-to (if different from Email Address):";

	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 128);
	inp.type = "text";
	inp.style.width = "300px";
	inp.cls = this;
	inp.value = this.email_replyto;
	inp.onchange = function() { this.cls.email_replyto = this.value; };
	main_con.appendChild(inp);

	if (this.email_mode == "alias")
	{
		var lbl = alib.dom.createElement("h3", main_con);
		alib.dom.styleSet(lbl, "margin-top", "20px");
		lbl.innerHTML = "Email Forwarding Instructions:";

		var p = alib.dom.createElement("p", main_con);
		alib.dom.styleSet(p, "margin-top", "10px");
		p.innerHTML = "Email can be sent to <strong>"+g_user.email+"</strong> which is your system-generated email address. While " +
					  "email can be sent directly to this address like any other email address, " +
					  "we highly recommend setting the parameters above to your current/public email address " +
					  "and then utilize your current email server to forward all messages to your ANT system-generated address.<br /><br />" +
					  "For example, say your email address is <strong>me@mycompany.com</strong>. Then these would be the steps you would follow:<br />" +
					  "<ol><li>Set your email address to <strong>me@mycompany.com</strong> above</li>" +
					  "<li>Call your email administrator or set your existing email server/application to forward all messages " +
					  "to <strong>"+g_user.email + "</strong></li></ol><br />" +
					  "Messages sent to <strong>me@mycompany.com</strong> will be delivered (forwarded) to ANT and any email message sent from " +
					  "ANT will be sent from \"me@mycompany.com\".";
	}

	// Email Address
	// Reply To
	//
	// If POP3 then
	// Username
	// Pass
}

/*************************************************************************
*	Function:	frmImport
*
*	Purpose:	Display import form
**************************************************************************/
CAntUserWizard.prototype.frmImport = function(main_con)
{
	// ANT Client
	// ---------------------------------------------------------------------
	var div_new = alib.dom.createElement("div", main_con);

	var lbl = alib.dom.createElement("span", div_new);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = " My contacts are currently managed by Microsoft Outlook 2007 or later";
	var desc = alib.dom.createElement("div", div_new);
	alib.dom.styleSet(desc, "margin", "5px 0 10px 10px");
	desc.innerHTML = "On the next page you will be given the opportunity to install a client that will automatically synchronize contacts, calendar events, and tasks between Outlook and ANT.";

	// Import Wizard
	// ---------------------------------------------------------------------

	var lbl = alib.dom.createElement("div", main_con);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = " I use another program to manage my contacts";

	var desc = alib.dom.createElement("div", main_con);
	alib.dom.styleSet(desc, "margin", "5px 0 10px 10px");
	desc.innerHTML = "Use this tool to import a CSV file.<br />";
	var btn = new CButton("Start Import Wizard", function(userid, dlg) { var ob = new CAntObjectImpWizard("contact", userid); ob.showDialog(dlg); }, 
							[this.user_id, this.m_dlg]);
	btn.print(desc);

	// Import Wizard
	// ---------------------------------------------------------------------
	var div_template = alib.dom.createElement("div", main_con);
	var lbl = alib.dom.createElement("span", div_template);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = " If you do not want to import contacts now simply click \"Next\" below";
}

/*************************************************************************
*	Function:	frmSmartphones
*
*	Purpose:	Display import form
**************************************************************************/
CAntUserWizard.prototype.frmSmartphones = function(main_con)
{
	var div_new = alib.dom.createElement("div", main_con);
	var lbl = alib.dom.createElement("span", div_new);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = "iPhone, Google Android, Windows Mobile, Palm Pre";
	var desc = alib.dom.createElement("div", div_new);
	alib.dom.styleSet(desc, "margin", "5px 0 10px 10px");
	desc.innerHTML = "ANT can synchronize your data with your smartphone over the air. Select your phone below for more information:";

	var ul = alib.dom.createElement("ul", div_new);
	
	var li = alib.dom.createElement("li", div_new);
	li.innerHTML = "<a href='javascript:void(0);' onclick=\"loadSupportDoc('81');\">iPhone</a>";
	var li = alib.dom.createElement("li", div_new);
	li.innerHTML = "<a href='javascript:void(0);' onclick=\"loadSupportDoc('163');\">Google Android</a>";
	var li = alib.dom.createElement("li", div_new);
	li.innerHTML = "<a href='javascript:void(0);' onclick=\"loadSupportDoc('151');\">Windows Mobile</a>";
	var li = alib.dom.createElement("li", div_new);
	li.innerHTML = "<a href='javascript:void(0);' onclick=\"loadSupportDoc('164');\">Palm Pre</a>";

	var div_template = alib.dom.createElement("div", main_con);
	alib.dom.styleSet(div_template, "margin-top", "5px");
	var lbl = alib.dom.createElement("span", div_template);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = "Blackberry";
	var desc = alib.dom.createElement("div", div_template);
	alib.dom.styleSet(desc, "margin", "5px 0 10px 10px");
	desc.innerHTML = "<a href='javascript:void(0);' onclick=\"loadSupportDoc('165');\">Click here</a> for information on setting up your blackberry with ANT.<br />";

	var div_template = alib.dom.createElement("div", main_con);
	var lbl = alib.dom.createElement("span", div_template);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = "I do not have a smart phone or I don't want to synchronze ANT with my phone";
	var desc = alib.dom.createElement("div", div_template);
	alib.dom.styleSet(desc, "margin", "5px 0 10px 10px");
	desc.innerHTML = "Click \"Next\" below to continue.";
}

/*************************************************************************
*	Function:	frmSmartphones
*
*	Purpose:	Display import form
**************************************************************************/
CAntUserWizard.prototype.getSettings = function()
{
	var ajax = new CAjax();
	ajax.cls = this;
	ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			this.cls.email_mode = root.getChildNodeValByName("email_mode");
		}
	};
	ajax.exec("/admin/xml_get_settings.php");
}
/*************************************************************************
*	Function:	socialFBGetButton
*
*	Purpose:	Get appropriate button for facebook account link
**************************************************************************/
CAntUserWizard.prototype.socialFBGetButton = function(dv)
{
	function cbdone(ret, cls, dv)
	{
		
	}	
    
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.dv = dv;
    ajax.onload = function(ret)
    {
        var cls = this.cbData.cls;
        var dv = this.cbData.dv;
        
        if (!ret['error'])
        {
            this.cbData.dv.innerHTML = "";
            var img = alib.dom.createElement("img", dv);
            img.src = '/images/facebook_connect.gif';
            img.style.cursor = "pointer";
            img.cls = cls;
            img.dv = dv;
            img.onclick = function()
            {
                 var params = 'width=600,height=300,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
                window.open('/users/social/fb.php', 'soc_fb', params);
                this.cls.socialBeginFbStatusCheck(this.dv);
                this.dv.innerHTML = "<div class='loading'></div>";
            }
        }
        else
        {
            dv.innerHTML = "<strong>Your facebook account has been linked to ANT</strong> &nbsp;";
            var a = alib.dom.createElement("a", dv);
            a.href = 'javascript:void(0);';
            a.innerHTML = "[disconnect]";
            a.cls = cls;
            a.dv = dv;
            a.onclick = function()
            {
                ajax = new CAjax('json');
                ajax.cbData.cls = this.cls;
                ajax.cbData.dv = this.dv;
                ajax.onload = function(ret)
                {
                    this.cbData.cls.socialFBGetButton(this.cbData.dv);
                };
                ajax.exec("/controller/User/socFbDisconnect");
                this.dv.innerHTML = "<div class='loading'></div>";
            }

        }
    };
    ajax.exec("/controller/User/socFbGetAccessToken");
}

/*************************************************************************
*	Function:	socialBeginFbStatusCheck
*
*	Purpose:	Check once every 10 seconds to see if user granted access
*				to their facebook account.
**************************************************************************/
CAntUserWizard.prototype.socialBeginFbStatusCheck = function(dv)
{
	// Now save email info
	// -------------------------------------------
	/*function cbdone(ret, cls, dv)
	{
		if (!ret['error'])
		{
			cls.socialBeginFbTimer = setTimeout(function() { cls.socialBeginFbStatusCheck(dv); }, 10000); // Evert 10 seconds
		}
		else
		{
			cls.socialFBGetButton(dv);

			if (!g_user.image_id)
				cls.socialFbGetProfileImage();
		}

	}		
    var rpc = new CAjaxRpc("/controller/User/socFbGetAccessToken", "socFbGetAccessToken", null, cbdone, [this, dv], AJAX_POST, true, "json");*/
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.dv = dv;
    ajax.onload = function(ret)
    {
        if (!ret['error'])
        {
            this.cbData.cls.socialBeginFbTimer = setTimeout(function() { this.cbData.cls.socialBeginFbStatusCheck(this.cbData.dv); }, 10000); // Evert 10 seconds
        }
        else
        {
            this.cbData.cls.socialFBGetButton(this.cbData.dv);

            if (!g_user.image_id)
                this.cbData.cls.socialFbGetProfileImage();
        }
    };
    ajax.exec("/controller/User/socFbGetAccessToken");
}

/*************************************************************************
*	Function:	socialFbGetProfileImage
*
*	Purpose:	Get facebook data if not set for current user
**************************************************************************/
CAntUserWizard.prototype.socialFbGetProfileImage = function()
{
	// Now save email info
	// -------------------------------------------
	/*function cbdone(ret, cls)
	{
		if (ret != "-1")
		{
			g_user.image_id = ret;
		}

	}	
    var rpc = new CAjaxRpc("/controller/User/socFbGetProfilePic", "socFbGetProfilePic", null, cbdone, [this], AJAX_POST, true, "json");*/
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if (ret != "-1")
            g_user.image_id = ret;
    };
    ajax.exec("/controller/User/socFbGetProfilePic");    
}
