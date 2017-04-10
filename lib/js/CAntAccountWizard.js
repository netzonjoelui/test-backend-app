/****************************************************************************
*	
*	Class:		CAntAccountWizard
*
*	Purpose:	Wizard for inserting a new video
*
*	Author:		joe, sky.stebnicki@aereus.com
*				Copyright (c) 2010 Aereus Corporation. All rights reserved.
*
*	Deps:		Alib
*
*****************************************************************************/
function CAntAccountWizard(user_id)
{
	this.user_id				= user_id;
	this.users					= new Array();
	this.login_file_id 			= null;
	this.welcome_file_id		= null;
	this.header_image_id		= null;
	this.header_image_public_id	= null;
	this.settings				= new Object();
	this.email_domain			= "";
	this.email_inc_server		= "";
	this.email_mode				= "server"; // server|client|alias - server=mx, client=pop3, alias=tmp email & forward
	this.fUseAntmail			= true;
	this.company_name			= "";
	this.company_website		= "";
	this.hear_about_us 			= "";
	this.business_do			= "";
	this.crm_currently_using	= "";
	this.access_export_csv		= "Yes";
	this.database_consist_of	= "Business contacts";
	this.how_many_crm_users		= "";
	this.what_can_ant_do		= "";
	this.feature_interest		= "";
	
	this.steps = new Array();
	this.steps[0] = "Getting Started";
	this.steps[1] = "Questionnaire"
	this.steps[2] = "Add Teams";
	this.steps[3] = "Add Users";
	this.steps[4] = "Login &amp; Welcome Backgrounds";
	this.steps[5] = "Import Customers";
	this.steps[6] = "Email Setup";
	this.steps[7] = "Email Settings";
	this.steps[8] = "Finished";
}

/*************************************************************************
*	Function:	showDialog
*
*	Purpose:	Display wizard
**************************************************************************/
CAntAccountWizard.prototype.showDialog = function(parentDlg)
{
	this.parentDlg = (parentDlg) ? parentDlg : null;
	this.m_dlg = new CDialog("Account Settings", this.parentDlg);
	this.m_dlg.f_close = true;
	var dlg = this.m_dlg;

	this.body_dv = alib.dom.createElement("div");

	dlg.customDialog(this.body_dv, 650, 520);

	this.showStep(0);
}

/*************************************************************************
*	Function:	showStep
*
*	Purpose:	Used to display the contents of a given step
**************************************************************************/
CAntAccountWizard.prototype.showStep = function(step)
{
	this.body_dv.innerHTML = ""; 
	this.cbTemplates = null;
	this.verify_step_data = new Object();
	this.nextStep = step+1;

	// Title/header
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
		p.innerHTML = "Welcome to Aereus Network Tools!";

		var p = alib.dom.createElement("h2", div_main);
		p.innerHTML = "This wizard will guide you through setting up your ANT account.";

		var p = alib.dom.createElement("p", div_main);
		p.innerHTML = "Accounts can have a single or multiple users. It is sometimes helpful to think of an account as an isolated application" +
					  "/database for your company or organization. " +
					  "If you are adding multiple users to the account, then data can be freely shared between users. The options you select " +
					  "in the next few steps will help define how all users interact with this account/application. After this wizard is completed " +
					  "you will be given the opportunity to setup your personal user information.";

		// load names form
		var frmdiv = alib.dom.createElement("div", div_main);
		frmdiv.innerHTML = "Loading...";
		if (!this.company_name)
			this.loadGeneralInfo(frmdiv);
		else
			this.frmGeneralNames(frmdiv);

		this.veriftyStep = function()
		{
			return true;
		}
		break;
	case 1:
		var p = alib.dom.createElement("h1", div_main);
		p.innerHTML = "ANT Questionnaire";
		
		this.buildAntQuestionnaire(div_main);
		
		break;
	case 2:
		var p = alib.dom.createElement("h1", div_main);
		p.innerHTML = "Add Additional Users";

		var p = alib.dom.createElement("p", div_main);
		p.innerHTML = "You can use the form below to add additional user accounts. Of course, you do not need to add yourself again, you are already set up as a user. Each individual in your organization should have a separate login.<br /><br />If you are the only one who will have access to ANT then just click \"Next\" below. You can always add additional users at a later time if needed.";

		// Users
		// -----------------------------------------------------------------
		var users_dv = alib.dom.createElement("div", div_main);
		var lbl = alib.dom.createElement("h2", users_dv);
		lbl.innerHTML = "Users";
		var lbl = alib.dom.createElement("span", users_dv);
		
		if (this.users.length)
		{
			for (var i = 0; i < this.users.length; i++)
			{
				this.addUserRow(users_dv, i);
			}
		}
		else
		{
				//this.addUserRow(users_dv);
				//this.addUserRow(users_dv);
		}

		var dv_add = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(a, "margin-top", "3px");
		var a = alib.dom.createElement("a", dv_add);
		a.innerHTML = "[add user]";
		a.href = "javascript:void(0);";
		a.dv = users_dv;
		a.cls = this;
		a.onclick = function()
		{
			this.cls.addUserRow(this.dv);	
		}

		this.veriftyStep = function()
		{
			return true;
		}
		break;
	case 3:
		var p = alib.dom.createElement("h2", div_main);
		p.innerHTML = "Customize:";

		// Images
		// -----------------------------------------------------------------
		var p = alib.dom.createElement("p", div_main);
		p.innerHTML = "You can customize the images users see when using ANT. The \"Welcome Screen\" appears on the home page for each user and the \"Login Image\" is the image that will be displayed before logging in. Simply click \"Next\" below if you do not wish to change these at this time. You can change them later if desired.";

		// ----------------------------------------------------------
		// Right column
		// ----------------------------------------------------------
		var rdv = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(rdv, "float", "right");
		alib.dom.styleSet(rdv, "width", "240px");
		alib.dom.styleSet(rdv, "margin", "0");
		
		var def_image = "<img src='/images/main_ant_med.png' />";
		var frm1 = new CWindowFrame("Login Image");
		frm1.print(rdv);
		var frmcon = frm1.getCon();
		alib.dom.styleSet(frmcon, "text-align", "center");
		var td_li = alib.dom.createElement("div", frmcon);
		td_li.innerHTML = (this.login_file_id) ? "<img src='/files/images/"+this.login_file_id+"/238/120'>" : def_image;
		td_li.onclick = function() { this.changeImage(); }
		td_li.cls = this;
		td_li.changeImage = function()
		{
			var cbrowser = new AntFsOpen(this.cls.m_dlg);
			cbrowser.filterType = "jpg:jpeg:png:gif";
			cbrowser.m_imgCon = this;
			cbrowser.cls = this.cls;
			cbrowser.onSelect = function(fid, name, path) 
			{
				this.m_imgCon.innerHTML = "<img src='/files/images/"+fid+"/238/120'>";
				this.cls.login_file_id= fid;
			}
			cbrowser.showDialog();
		}
		var btn = new CButton("Select Image", function(div) { div.changeImage(); }, [td_li], "b1");
		btn.print(frmcon);
		var btn2 = new CButton("Default Image", function(td_li, def_image, cls) { td_li.innerHTML = def_image; cls.login_file_id=''; }, [td_li, def_image, this], "b3");
		btn2.print(frmcon);

		// ----------------------------------------------------------
		// Left column
		// ----------------------------------------------------------
		var ldv = alib.dom.createElement("div", div_main);
		alib.dom.styleSet(ldv, "margin", "0 245px 0 0");

		var def_image = "<img src='/images/themes/skygrey/greeting.png' style='width:98%;' />";
		var frm1 = new CWindowFrame("Welcome Screen Image");
		frm1.print(ldv);
		var frmcon = frm1.getCon();
		alib.dom.styleSet(frmcon, "text-align", "center");
		var td_wel = alib.dom.createElement("div", frmcon);
		td_wel.innerHTML = (this.welcome_file_id) ? "<img src='/files/images/"+this.welcome_file_id+"' style='width:98%;' />" : def_image;
		td_wel.onclick = function() { this.changeImage(); }
		td_wel.cls = this;
		td_wel.changeImage = function()
		{
			var cbrowser = new AntFsOpen(this.cls.m_dlg);
			cbrowser.filterType = "jpg:jpeg:png:gif";
			cbrowser.m_imgCon = this;
			cbrowser.cls = this.cls;
			cbrowser.onSelect = function(fid, name, path) 
			{
				this.m_imgCon.innerHTML = "<img src='/files/images/"+fid+"/238/120'>";
				this.cls.welcome_file_id = fid;
			}
			cbrowser.showDialog();
		}
		var btn = new CButton("Select Image", function(div) { div.changeImage(); }, [td_wel], "b1");
		btn.print(frmcon);
		var btn2 = new CButton("Default Image", function(td_wel, def_image, cls) { td_wel.innerHTML = def_image; cls.welcome_file_id=''; }, [td_wel, def_image, this], "b3");
		btn2.print(frmcon);

		this.veriftyStep = function()
		{
			return true;
		}
		break;
	case 4:
		var p = alib.dom.createElement("h2", div_main);
		p.innerHTML = "Import Customers:";

		this.buildImportCustomer(div_main);

		this.veriftyStep = function()
		{
			return true;
		}
		break;
	case 5:
		var p = alib.dom.createElement("h2", div_main);
		p.innerHTML = "Email Preference:";

		this.buildEmailSetup(div_main);

		this.veriftyStep = function()
		{
			return true;
		}
		break;
	case 6:
		var p = alib.dom.createElement("h2", div_main);
		p.innerHTML = "Email Settings:";

		this.buildEmailSetup2(div_main);

		break;
	case 7:
		div_main.innerHTML = "<h2>Congratulations!</h2><h3>Your account has been set up.</h3>Click 'Finish' below to close this wizard and start the \"Personal Settings\" wizard.";
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
CAntAccountWizard.prototype.veriftyStep = function()
{
	return true;
}

/*************************************************************************
*	Function:	onCancel
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CAntAccountWizard.prototype.onCancel = function()
{
}

/*************************************************************************
*	Function:	onFinished
*
*	Purpose:	This function should be over-rideen
**************************************************************************/
CAntAccountWizard.prototype.onFinished = function()
{
	var wiz = new CAntUserWizard(this.user_id);
	wiz.showDialog(null, 0);
}

/*************************************************************************
*	Function:	save
*
*	Purpose:	Save settings
**************************************************************************/
CAntAccountWizard.prototype.save = function()
{
	var args = [["login_file_id", this.login_file_id], 
				["welcome_file_id", this.welcome_file_id], 
				["header_image_id", this.header_image_id], 
				["header_image_public_id", this.header_image_public_id], 
				["email_domain", this.email_domain], 
				["email_inc_server", this.email_inc_server],
				["company_name", this.company_name], 
				["company_website", this.company_website],
				["email_mode", this.email_mode], 
				["f_use_antmail", (this.fUseAntmail)?'t':'f'],
				["hear_about_us", this.hear_about_us],
				["business_do", this.business_do],
				["crm_currently_using", this.crm_currently_using],
				["access_export_csv", this.access_export_csv],
				["database_consist_of", this.database_consist_of],
				["how_many_crm_users", this.how_many_crm_users],
				["what_can_ant_do", this.what_can_ant_do],
				["feature_interest", this.feature_interest]];

	for (var i = 0; i < this.users.length; i++)
	{
		if (this.users[i].name && this.users[i].password)
			args[args.length] = ["users[]", this.users[i].name+"|"+this.users[i].password];
	}

	this.m_dlg.hide();

	var dlg = new CDialog();
	var dv_load = document.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Applying settings, please wait...";
	dlg.statusDialog(dv_load, 250, 100);
    
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
    ajax.exec("/controller/Admin/saveWizardAccount", args);
}

/*************************************************************************
*	Function:	loadGeneralInfo
*
*	Purpose:	Load user profile
**************************************************************************/
CAntAccountWizard.prototype.loadGeneralInfo = function(con)
{
	var ajax = new CAjax();
	ajax.clsref = this;
	ajax.con = con;
	ajax.onload = function(root)
	{
		if (root.getNumChildren())
		{
			this.clsref.company_name			= unescape(root.getChildNodeValByName("company_name"));
			this.clsref.company_website			= unescape(root.getChildNodeValByName("company_website"));
			this.clsref.login_file_id			= unescape(root.getChildNodeValByName("login_image"));
			this.clsref.welcome_file_id			= unescape(root.getChildNodeValByName("welcome_image"));
			this.clsref.header_image_id			= unescape(root.getChildNodeValByName("header_image"));
			this.clsref.header_image_public_id	= unescape(root.getChildNodeValByName("header_image_public"));
		}

		this.clsref.frmGeneralNames(con);
	};
	ajax.exec("/admin/xml_get_settings.php");
}

/*************************************************************************
*	Function:	frmGeneralNames
*
*	Purpose:	Display company name and website
**************************************************************************/
CAntAccountWizard.prototype.frmGeneralNames = function(con)
{
	con.innerHTML = "";

	// Company name
	var lbl = alib.dom.createElement("h4", con);
	lbl.innerHTML = "Company/Organization Name (required):";
	var inp_dv = alib.dom.createElement("div", con);
	var inp = alib.dom.createElement("input");
	alib.dom.styleSet(inp, "width", "250px");
	inp.type = "text";
	inp.value = this.company_name;
	inp.cls = this;
	inp.onchange = function() { this.cls.company_name = this.value; }
	inp_dv.appendChild(inp);		

	// Company website
	var lbl = alib.dom.createElement("h4", con);
	lbl.innerHTML = "Company/Organization Website (optional):";
	var inp_dv = alib.dom.createElement("div", con);
	var inp = alib.dom.createElement("input");
	alib.dom.styleSet(inp, "width", "250px");
	inp.type = "text";
	inp.value = this.company_website;
	inp.cls = this;
	inp.onchange = function() { this.cls.company_website = this.value; }
	inp_dv.appendChild(inp);		
	var lbl = alib.dom.createElement("div", con);
	lbl.innerHTML = " Please exclude any protocol prefix like 'http://'. Example: www.mycompany.com rather than http://www.mycompany.com";
}

/*************************************************************************
*	Function:	addUserRow
*
*	Purpose:	Add a user row
**************************************************************************/
CAntAccountWizard.prototype.addUserRow = function(con, idx)
{
	if (typeof idx == "undefined")
	{
		var idx = this.users.length;
		this.users[idx] = new Object();
		this.users[idx].id = idx;
		this.users[idx].name = "";
		this.users[idx].password = "";
	}

	var dv = alib.dom.createElement("div", con);
	alib.dom.styleSet(dv, "margin", "3px");
	
	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = "User Name: ";

	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 32);
	inp.type = "text";
	inp.style.width = "100px";
	inp.cls = this;
	inp.user = this.users[idx];
	inp.value = this.users[idx].name;
	inp.onkeypress = function(evnt)
	{
		var ev = (evnt) ? evnt : null;
		return filterInput(2, evnt, false, ".");
	}
	inp.onchange = function() { this.user.name = this.value; };
	dv.appendChild(inp);

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " password ";

	var pass = alib.dom.createElement("input");
	pass.type = "password";
	pass.style.width = "100px";
	pass.cls = this;
	pass.user = this.users[idx];
	pass.value = this.users[idx].password;
	pass.onchange = function() { this.user.link = this.value; };
	dv.appendChild(pass);

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = " verify password ";

	var passv = alib.dom.createElement("input");
	passv.type = "password";
	passv.style.width = "100px";
	passv.pass = pass;
	passv.cls = this;
	passv.user = this.users[idx];
	passv.user = this.users[idx];
	passv.value = this.users[idx].password;
	passv.onchange = function() { if (this.pass.value == this.value) this.user.password = this.value; };
	dv.appendChild(passv);

	var lbl = alib.dom.createElement("span", dv);
	lbl.innerHTML = "&nbsp;";
	
	var a = alib.dom.createElement("a", dv);
	a.innerHTML = "[remove]";
	a.href = "javascript:void(0);";
	a.thiscon = dv;
	a.parentcon = con;
	a.bid = this.users[idx].id;
	a.cls = this;
	a.onclick = function()
	{
		for (var i = 0; i < this.cls.users.length; i++)
		{
			if (this.cls.users[i].id == this.bid)
			{
				this.cls.users.splice(i, 1);
			}
		}
		this.parentcon.removeChild(this.thiscon);
	}
}

/*************************************************************************
*	Function:	buildImportCustomer
*
*	Purpose:	Build Import Customer Page
**************************************************************************/
CAntAccountWizard.prototype.buildImportCustomer = function(con)
{

	var p = alib.dom.createElement("p", con);
	p.innerHTML = "If you already have an existing database of customers then we highly recommended importing your data into ANT. Customers are global contacts for your company or organization. Most contact management applications have an \"Export To CSV\" which will produce a file that can be imported into ANT. If you do not wish to import customers at this time simply click \"Next\" below. You can always import customers later if desired.";

	var lbl = alib.dom.createElement("h3", con);
	lbl.innerHTML = "Step 1 - Export Your Data";

	var ul = alib.dom.createElement("ul", con);
	
	var li = alib.dom.createElement("li", ul);
	li.innerHTML = "<a href='javascript:void(0);' onclick=\"loadSupportDoc('168');\">How to export contacts from Microsoft Outlook</a>";

	var li = alib.dom.createElement("li", ul);
	li.innerHTML = "<a href='javascript:void(0);' onclick=\"loadSupportDoc('166');\">How to export contacts from ACT!</a>";

	var li = alib.dom.createElement("li", ul);
	li.innerHTML = "<a href='javascript:void(0);' onclick=\"loadSupportDoc('167');\">How to export contacts from Salesforce.com</a>";

	var li = alib.dom.createElement("li", ul);
	li.innerHTML = "<a href='javascript:void(0);' onclick=\"loadSupportDoc('169');\">General Exporting Guidelines</a>";

	var lbl = alib.dom.createElement("h3", con);
	lbl.innerHTML = "Step 2 - Import Your Data";

	var ob = new CAntObjectImpWizard("customer", this.user_id);
	ob.wizcls = this;
	ob.onFinished = function() { this.wizcls.showStep(this.wizcls.nextStep); }
	var btn = new CButton("Start Import Wizard", function(userid, dlg, ob) { ob.showDialog(dlg); }, [this.user_id, this.m_dlg, ob]);
	btn.print(con);
}

/*************************************************************************
*	Function:	buildEmailSetup
*
*	Purpose:	Build Email Setup Page
**************************************************************************/
CAntAccountWizard.prototype.buildEmailSetup = function(con)
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
	desc.innerHTML = "This does not mean you must immediately redirect all your email to ANT. ANT can be used to manage all your email and replace applications like Outlook, but you can also leave your current solution in place and configure ANT to gather email from your existing email solution so messages are delivered both to ANT and your existing server.";

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
	desc.innerHTML = "Email can and will still be sent from ANT to users and customers, but all email traffic will be forwarded to your existing email solution.";
}

/*************************************************************************
*	Function:	buildEmailSetup2
*
*	Purpose:	Build Email Setup 2 Page
**************************************************************************/
CAntAccountWizard.prototype.buildEmailSetup2 = function(con)
{
	// POP 3
	// -------------------------------------------------------
	/*
	var div_template = alib.dom.createElement("div", con);
	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='radio';
	rbtn1.name = 'merge';
	rbtn1.cls = this;
	rbtn1.onclick = function() { this.cls.email_mode = "client"; }
	div_template.appendChild(rbtn1);
	rbtn1.checked = (this.email_mode=="client" && this.fUseAntmail)?true:false;
	var lbl = alib.dom.createElement("span", div_template);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = " Download messages directly from my email server (POP3)";
	var desc = alib.dom.createElement("div", div_template);
	alib.dom.styleSet(desc, "margin", "5px 0 10px 25px");
	desc.innerHTML = "Pull messages directly from the following mail server (if you are unsure please contact your current email administrator and ask for the \"POP3 incoming mail server address\"): ";

	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 128);
	inp.type = "text";
	inp.style.width = "200px";
	inp.value = this.email_inc_server;
	inp.cls = this;
	inp.onchange = function() { this.cls.email_inc_server = this.value; };
	desc.appendChild(inp);
	*/


	// ANT mail server
	// -------------------------------------------------------
	var div_template = alib.dom.createElement("div", con);
	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='radio';
	rbtn1.name = 'merge';
	rbtn1.cls = this;
	rbtn1.onclick = function() { this.cls.email_mode = "server"; }
	div_template.appendChild(rbtn1);
	rbtn1.checked = (this.email_mode=="server" && this.fUseAntmail)?true:false;
	var lbl = alib.dom.createElement("span", div_template);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = " I will set my domain to forward all email to ANT";
	var desc = alib.dom.createElement("div", div_template);
	alib.dom.styleSet(desc, "margin", "5px 0 10px 25px");
	desc.innerHTML = "Simply point your mail exchange (MX) record to <strong>\""+document.domain+"\"</strong> and enter your email domain below. For example, if your email address is \"me@mycompany.com\" then your email domain is \"mycompany.com\".";

	var inp_dv = alib.dom.createElement("div", desc);
	inp_dv.innerHTML = "Email Domain: ";

	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 128);
	inp.type = "text";
	inp.style.width = "200px";
	inp.value = this.email_domain;
	inp.cls = this;
	inp.onchange = function() { this.cls.email_domain = this.value; };
	inp_dv.appendChild(inp);


	// System generated
	// -------------------------------------------------------
	var div_new = alib.dom.createElement("div", con);
	var rbtn1 = alib.dom.createElement("input");
	rbtn1.type='radio';
	rbtn1.name = 'merge';
	rbtn1.cls = this;
	rbtn1.onclick = function() { this.cls.email_mode = "alias"; }
	div_new.appendChild(rbtn1);
	rbtn1.checked = (this.email_mode=="alias" && this.fUseAntmail)?true:false;
	var lbl = alib.dom.createElement("span", div_new);
	alib.dom.styleSetClass(lbl, "formLabel");
	lbl.innerHTML = " Each user will be responsible for manually forwarding email to a system-generated email address";
	var desc = alib.dom.createElement("div", div_new);
	alib.dom.styleSet(desc, "margin", "5px 0 10px 25px");
	desc.innerHTML = "Current email solutions will need to forward messages to <strong>\"username@"+document.domain+"\"</strong>";

	// None
	// -------------------------------------------------------
	if (!this.fUseAntmail)
	{
		var div_new = alib.dom.createElement("div", con);
		var rbtn1 = alib.dom.createElement("input");
		rbtn1.type='radio';
		rbtn1.name = 'merge';
		rbtn1.cls = this;
		rbtn1.onclick = function() { }
		div_new.appendChild(rbtn1);
		rbtn1.checked = true;
		var lbl = alib.dom.createElement("span", div_new);
		alib.dom.styleSetClass(lbl, "formLabel");
		lbl.innerHTML = " No settings - email will not be managed by ANT";
	}


	// Log communication
	// -----------------------------------------------------------------
	var div_new = alib.dom.createElement("div", con);
	var td = alib.dom.createElement("span", div_new);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Track Email:";
	var td = alib.dom.createElement("span", div_new);
	var inp = alib.dom.createElement("input");
	inp.type = "checkbox";
	inp.onclick = function() 
	{
		var val = (this.checked) ? 't' : 'f';
        
        ajax = new CAjax('json');        
        ajax.exec("/controller/Admin/setSetting", 
                    [["set", "customers/f_log_all_activities"], ["val", val]]);
	}
	td.appendChild(inp);
	var lbl = alib.dom.createElement("span", div_new);
	lbl.innerHTML = " - track all incoming and outgoing email messages sent as activities";
	                        
    ajax = new CAjax('json');
    ajax.inp = inp;
    ajax.onload = function(ret)
    {
        if (ret=='t') 
            this.inp.checked = true; 
        else 
            this.inp.checked = false;    
    };
    ajax.exec("/controller/Admin/getSetting", 
                [["get", "customers/f_log_all_activities"]]);

	this.veriftyStep = function()
	{
		switch (this.email_mode)
		{
		case 'client':
			if (!this.email_inc_server && this.fUseAntmail)
			{
				this.verify_step_data.message = "Please enter an incoming mail server before continuing!";
				return false;
			}
			break;
		case 'server':
			if (!this.email_domain && this.fUseAntmail)
			{
				this.verify_step_data.message = "Please enter your email domain before continuing!";
				return false;
			}
			break;
		case 'alias':
			break;
		}

		return true;
	}
}

/*************************************************************************
*	Function:	buildAntQuestionnaire
*
*	Purpose:	Build ANT Questionnaire
**************************************************************************/
CAntAccountWizard.prototype.buildAntQuestionnaire = function(con)
{
	var table = alib.dom.createElement("table", con);
	var tableBody = alib.dom.createElement("tbody", table);
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	
	// How did you hear about us?
	var lbl = alib.dom.createElement("h4", con);
	alib.dom.styleSet(lbl, "margin", "10px 0 3px 0");
	lbl.innerHTML = "How did you hear about us? (Google/Bing search, LinkedIn, Facebook, Blogs, Website, Other)";
	lbl.style.width = "300px"
	var inp_dv = alib.dom.createElement("div", con);
	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 128);
	inp.type = "text";
	inp.style.width = "200px";
	inp.value = this.hear_about_us;
	inp.cls = this;
	inp.onchange = function() { this.cls.hear_about_us = this.value; };
	inp_dv.appendChild(inp);
	td.appendChild(lbl);
	td.appendChild(inp);

	// What does your business do?
	var td = alib.dom.createElement("td", tr);	
	var lbl = alib.dom.createElement("h4", con);
	alib.dom.styleSet(lbl, "margin", "10px 0 3px 0");
	lbl.innerHTML = "Briefly, what does your business do?";
	lbl.style.width = "300px"
	var inp_dv = alib.dom.createElement("div", con);
	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 128);
	inp.type = "text";
	inp.style.width = "200px";
	inp.value = this.business_do;
	inp.cls = this;
	inp.onchange = function() { this.cls.business_do = this.value; };
	inp_dv.appendChild(inp);
	td.appendChild(lbl);
	td.appendChild(inp);
	tr.appendChild(td);
	
	// What CRM are you currently using?
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	var lbl = alib.dom.createElement("h4", con);
	alib.dom.styleSet(lbl, "margin", "10px 0 3px 0");
	lbl.innerHTML = "What CRM Product are you currently using? (Salesforce, Outlook, ACT, etc.)";
	lbl.style.width = "300px"
	var inp_dv = alib.dom.createElement("div", con);
	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 128);
	inp.type = "text";
	inp.style.width = "200px";
	inp.value = this.crm_currently_using;
	inp.cls = this;
	inp.onchange = function() { this.cls.crm_currently_using = this.value; };
	inp_dv.appendChild(inp);
	td.appendChild(lbl);
	td.appendChild(inp);
	
	// Do you have access to export data to a CSV file?
	var td = alib.dom.createElement("td", tr);	
	var lbl = alib.dom.createElement("h4", con);
	alib.dom.styleSet(lbl, "margin", "10px 0 3px 0");
	lbl.innerHTML = "Do you have access to export your current data into a CSV file?";
	lbl.style.width = "300px"
	var inp_dv = alib.dom.createElement("div", con);
	var selectBox = alib.dom.createElement("select");
	var option = alib.dom.createElement("option");
	option.text = "Yes";
	selectBox.appendChild(option);
	option = alib.dom.createElement("option");
	option.text = "No";
	selectBox.appendChild(option);
	option = alib.dom.createElement("option");
	option.text = "Not sure";
	selectBox.appendChild(option);
	selectBox.value = this.access_export_csv;
	selectBox.cls = this;
	selectBox.onchange = function() { this.cls.access_export_csv = this.value; };
	inp_dv.appendChild(selectBox);
	td.appendChild(lbl);
	td.appendChild(selectBox);
	tr.appendChild(td);
	
	// What does your database consist of?
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	var lbl = alib.dom.createElement("h4", con);
	alib.dom.styleSet(lbl, "margin", "10px 0 3px 0");
	lbl.innerHTML = "What does your database primarily consist of?";
	lbl.style.width = "300px"
	var inp_dv = alib.dom.createElement("div", con);
	var selectBox = alib.dom.createElement("select");
	var option = alib.dom.createElement("option");
	option.text = "Business contacts";
	selectBox.appendChild(option);
	option = alib.dom.createElement("option");
	option.text = "Personal contacts";
	selectBox.appendChild(option);
	option = alib.dom.createElement("option");
	option.text = "A good mix of both";
	selectBox.appendChild(option);
	selectBox.value = this.database_consist_of;
	selectBox.cls = this;
	selectBox.onchange = function() { this.cls.database_consist_of = this.value; };
	inp_dv.appendChild(selectBox);
	td.appendChild(lbl);
	td.appendChild(selectBox);
	
	// How many users do you anticipate using your CRM?
	/*
	var td = alib.dom.createElement("td", tr);
	var lbl = alib.dom.createElement("h4", con);
	alib.dom.styleSet(lbl, "margin", "10px 0 3px 0");
	lbl.innerHTML = "How many users do you anticipate using your CRM?";
	lbl.style.width = "300px"
	var inp_dv = alib.dom.createElement("div", con);
	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 6);
	inp.type = "text";
	inp.style.width = "200px";
	inp.value = this.how_many_crm_users;
	inp.cls = this;
	inp.onchange = function() { this.cls.how_many_crm_users = this.value; };
	inp_dv.appendChild(inp);
	td.appendChild(lbl);
	td.appendChild(inp);
	tr.appendChild(td);
	table.appendChild(tableBody);
	*/

	var table = alib.dom.createElement("table", con);
	var tableBody = alib.dom.createElement("tbody", table);
	
	// What can ANT's CRM do for you?
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	var lbl = alib.dom.createElement("h4", con);
	alib.dom.styleSet(lbl, "margin", "10px 0 3px 0");
	lbl.innerHTML = "Briefly, what are some things	you want to have ANT's CRM do for you? What are some of your CRM needs?";
	var inp_dv = alib.dom.createElement("div", con);
	var inp = alib.dom.createElement("textarea");
	inp.cols = "80";
	inp.rows = "2";
	inp.value = this.what_can_ant_do;
	inp.cls = this;
	inp.onchange = function() { this.cls.what_can_ant_do = this.value; };
	inp_dv.appendChild(inp);
	td.appendChild(lbl);
	td.appendChild(inp);
	tr.appendChild(td);
	
	// What are some features you are interested in?
	var tr = alib.dom.createElement("tr", tableBody);
	var td = alib.dom.createElement("td", tr);
	var lbl = alib.dom.createElement("h4", con);
	alib.dom.styleSet(lbl, "margin", "10px 0 0 0");
	lbl.innerHTML = "What are some other features you are interested in, or would like to learn more about?";
	td.appendChild(lbl);
	tr.appendChild(td);
	table.appendChild(tableBody);

	var table = alib.dom.createElement("table", con);
	var tableBody = alib.dom.createElement("tbody", table);

	var tr = alib.dom.createElement("tr", tableBody);	// Email Integration
	var td = alib.dom.createElement("td", tr); 
	var inp = alib.dom.createElement("input");
	inp.type = "checkbox";
	inp.value = this.feature_interest + "Email Integration, ";
	inp.cls = this;
	inp.onchange = function() { this.cls.feature_interest += this.value; };
	td.appendChild(inp);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Email Integration";

	var td = alib.dom.createElement("td", tr);	// Mobil Sync
	var inp = alib.dom.createElement("input");
	inp.type = "checkbox";
	inp.value = this.feature_interest + "Mobil Sync, ";
	inp.cls = this;
	inp.onchange = function() { this.cls.feature_interest += this.value; };
	td.appendChild(inp);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Mobil Sync";

	var td = alib.dom.createElement("td", tr);	// File Sync
	var inp = alib.dom.createElement("input");
	inp.type = "checkbox";
	inp.value = this.feature_interest + "File Sync, ";
	inp.cls = this;
	inp.onchange = function() { this.cls.feature_interest += this.value; };
	td.appendChild(inp);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "File Sync (Storage, Backup, Web access)";
	tr.appendChild(td);
	
	var tr = alib.dom.createElement("tr", tableBody);	// Video Email
	var td = alib.dom.createElement("td", tr); 
	var inp = alib.dom.createElement("input");
	inp.type = "checkbox";
	inp.value = this.feature_interest + "Video Email, ";
	inp.cls = this;
	inp.onchange = function() { this.cls.feature_interest += this.value; };
	td.appendChild(inp);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Video Email";

	var td = alib.dom.createElement("td", tr);	// Workflows 
	var inp = alib.dom.createElement("input");
	inp.type = "checkbox";
	inp.value = this.feature_interest + "Workflows, ";
	inp.cls = this;
	inp.onchange = function() { this.cls.feature_interest += this.value; };
	td.appendChild(inp);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Workflows";

	var td = alib.dom.createElement("td", tr);	// Project Management
	var inp = alib.dom.createElement("input");
	inp.type = "checkbox";
	inp.value = this.feature_interest + "Project Management, ";
	inp.cls = this;
	inp.onchange = function() { this.cls.feature_interest += this.value; };
	td.appendChild(inp);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Project Management";
	tr.appendChild(td);

	var tr = alib.dom.createElement("tr", tableBody);	// Tracking & Reporting
	var td = alib.dom.createElement("td", tr); 
	var inp = alib.dom.createElement("input");
	inp.type = "checkbox";
	inp.value = this.feature_interest + "Tracking & Reporting, ";
	inp.cls = this;
	inp.onchange = function() { this.cls.feature_interest += this.value; };
	td.appendChild(inp);
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Tracking & Reporting";
	tr.appendChild(td);
	table.appendChild(tableBody);
}
