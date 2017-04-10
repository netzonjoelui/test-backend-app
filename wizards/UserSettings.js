/**
 * @fileoverview This wizard walks users (usually new) through setting up their account
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011-2012 Aereus Corporation. All rights reserved.
 */

/**
 * @constructor
 */
function AntWizard_UserSettings()
{
	/**
	 * Handle to wizard, this MUST be set by the parent class or calling procedure
	 *
	 * @private
	 * @param {AntWizard}
	 */
	this.wizard = null;

	/**
	 * Last error
	 *
	 * @public
	 * @param {string}
	 */
	this.lastErrorMessage = "";

	/**
	 * Store user data
	 *
	 * @private
	 * @param {Object}
	 */
	this.data = {password:"", password_verify:""};
}

/**
 * Setup steps for this wizard
 *
 * This function is called by the AntWizard base class once the wizard has loaded for the first time
 *
 * @param {AntWizard} wizard Required handle to parent wizard class
 */
AntWizard_UserSettings.prototype.setup = function(wizard)
{
	this.wizard = wizard;

	this.wizard.title = "User Setup";

	var me = this;
	// Add step 1
	this.wizard.addStep(function(con) { me.stepProfile(con); }, "Getting Started");

	// Add step 2
	this.wizard.addStep(function(con) { me.stepPersonal(con); }, "Personalize");

	// Add step 3
	this.wizard.addStep(function(con) { me.stepThree(con); }, "End");
}

/**
 * This function is called every time the user advances a step
 *
 * It may be overridden by each step function below when the step function is called.
 * However, it is reset by the wizard class before each step loads so
 * verification code is limited to that step and must be set each time.
 *
 * If the function returns false, the wizard will not progress to the next step.
 * This function will not be called on the final step where "Finished" is presented.
 * Validation for that step must take place in the onFinished callback.
 *
 * @return {bool} true on success, false on failure. Set this.lastErrorMessage if failed.
 */
AntWizard_UserSettings.prototype.processStep = function() {}

/**
 * Display step 3
 *
 * @public
 */
AntWizard_UserSettings.prototype.stepProfile = function(con)
{
	var h = alib.dom.createElement("h1", con);
	h.innerHTML = "Personal Settings Wizard";

	var p = alib.dom.createElement("p", con);
	p.innerHTML = "<strong>Welcome to Aereus Network Tools!</strong> This wizard will guide you through setting up your user account." +
				  "<br /><br />If you would like to start using ANT immediately simply skip this wizard by clicking \"Cancel\" below.";

	// Basic Information
	// ----------------------------------------------------------
	var h = alib.dom.createElement("h3", con);
	alib.dom.styleSet(h, "margin-top", "30px");
	h.innerHTML = "General Information";

	var tbl = alib.dom.createElement("table", con);
	var tbody = alib.dom.createElement("tbody", tbl);

	// Full Name
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Full Name";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input", td);
	//inp.value = g_user.full_name;
	inp.onchange = function() { }
		
	// Email
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Your Email Address:";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.setAttribute('maxLength', 128);
	inp.type = "text";
	inp.style.width = "300px";
	inp.cls = this;
	//inp.value = this.email_address;
	inp.onchange = function() { };
	td.appendChild(inp);

	// Password
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Change Password";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.type = "password";
	inp.value = "      ";
	inp.onchange = function() { this.data.password = this.value; }
	td.appendChild(inp);		

	// Verify Password
	var row = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", row);
	alib.dom.styleSetClass(td, "formLabel");
	td.innerHTML = "Verify Password";
	var td = alib.dom.createElement("td", row);
	var inp = alib.dom.createElement("input");
	inp.type = "password";
	inp.value = "      ";
	inp.onchange = function() { this.data.password_verify = this.value; }
	td.appendChild(inp);

	// setup processing
	this.processStep = function()
	{
		if (this.data.password != this.data.password_verify)
		{
			this.lastErrorMessage = "The passwords you entered do not match";
			return false;
		}
		
		return true;
	}
}

/**
 * Display step 2
 *
 * @public
 */
AntWizard_UserSettings.prototype.stepPersonal = function(con)
{
	// Picture
	// ----------------------------------------------------------
	var h = alib.dom.createElement("h3", con);
	alib.dom.styleSet(h, "margin-top", "30px");
	h.innerHTML = "Profile Picture";
	var lbl = alib.dom.createElement("p", con);
	//alib.dom.styleSetClass(lbl, "notice");
	lbl.innerHTML = "Linking your facebook account allows for personalized information like photos and " +
					"interests to be integrated into your ANT account.";

	// Facebook integration
	// ----------------------------------------------------------
	var h = alib.dom.createElement("h3", con);
	alib.dom.styleSet(h, "margin-top", "30px");
	h.innerHTML = "Facebook Integration";
	var lbl = alib.dom.createElement("p", con);
	//alib.dom.styleSetClass(lbl, "notice");
	lbl.innerHTML = "Linking your facebook account allows for personalized information like photos and " +
					"interests to be integrated into your ANT account.";

	var lbl = alib.dom.createElement("div", con);
	lbl.innerHTML = "TODO";
	//this.socialFBGetButton(lbl);
}

/**
 * Display step 3
 *
 * @public
 */
AntWizard_UserSettings.prototype.stepThree = function(con)
{
	con.innerHTML = "Three";
}
