/**
 * @fileoverview Used to select or update the edition
 *
 * <code>
 * 	var wiz = new AntWizard("UpdateEdition");
 * 	wiz.onFinished = function() { alert("The wizard is finished); };
 * 	wiz.onCancel = function() { alert("The wizard was canceled"); };
 * 	wiz.show();
 * </code>
 */

/**
 * @constructor
 */
function AntWizard_UpdateEdition()
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
	this.lastErrorMessage = true;

	/**
	 * Edition data
	 * 
	 * @private
	 * @var {Object}
	 */
	this.accountData = null;

	/**
	 * Billing form elements
	 * 
	 * @private
	 * @var {Object}
	 */
	this.formBilling = new Object();
}

/**
 * Setup steps for this wizard
 *
 * @param {AntWizard} wizard Required handle to parent wizard class
 */
AntWizard_UpdateEdition.prototype.setup = function(wizard)
{
	this.wizard = wizard;

	this.wizard.title = "Update Edition";

	var me = this;

	// Add step 1
	this.wizard.addStep(function(con) {me.stepOne(con);}, "Select Edition");

	// Add step 2
	this.wizard.addStep(function(con) {me.stepTwo(con);}, "Details");

	// Add step 3
	this.wizard.addStep(function(con) {me.stepFinished(con);}, "Finished");
}

/**
 * This function is called every time the user advances a step
 *
 * It may be overridden by each step when the step function is called.
 * However, it is reset by the wizard class before each step loads so
 * verification code is limited to that step and must be set each time.
 *
 * @return {bool} true on success, false on failure. Set this.lastErrorMessage if failed.
 */
AntWizard_UpdateEdition.prototype.processStep = function() {}

/**
 * Display step 3
 *
 * @public
 */
AntWizard_UpdateEdition.prototype.stepOne = function(con)
{
	if (this.accountData)
	{
		con.innerHTML = "";
		this.stepOneDisplay(con);
	}
	else
	{
		con.innerHTML = "<div class='loading'></div>";
		this.loadAccountData(con);
	}
}

/**
 * Display step 3
 *
 * @public
 */
AntWizard_UpdateEdition.prototype.stepOneDisplay = function(con)
{
	var lbl = alib.dom.createElement("h1", con, "Changing your edition is quick &amp; easy. Select an option below.");

	var hr = alib.dom.createElement("hr", con);

	var lbl = alib.dom.createElement("span", con, "Your Usage: ");
	alib.dom.styleSetClass(lbl, "formLabel");

	var usage = alib.dom.createElement("span", con, this.accountData.usageDesc);
	alib.dom.styleSetClass(lbl, "formValue");

	var spacer = alib.dom.createElement("div", con);
	alib.dom.styleSet(spacer, "height", "10px");

	var tbl = alib.dom.createElement("table", con);
	alib.dom.styleSet(tbl, "width", "100%");
	var tbody = alib.dom.createElement("tbody", tbl);

	// Display edition pilars
	// -----------------------------------------
	var tr = alib.dom.createElement("tr", tbody);
	
	// Free edition
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSet(td, "width", "33%");
	var pil = alib.dom.createElement("div", td);
	alib.dom.styleSetClass(pil, "wizardEditionPil");
	var h1 = alib.dom.createElement("h1", pil, "Professional");
	var h3 = alib.dom.createElement("h3", pil, "$20 / user / month");
	var desc = alib.dom.createElement("div", pil);
	alib.dom.styleSetClass(desc, "desc");
	desc.innerHTML = "Up to 199 users, 3GB storage per user, Unlimited contacts/customers, Unlimited projects.";
	var rdo = alib.dom.createElement("input", pil);
	rdo.type = 'radio';
	rdo.name = "editions";
	rdo.value = 2;
	rdo.checked = (this.accountData.edition == rdo.value) ? true : false;
	rdo.cls = this;
	rdo.onclick = function() { this.cls.accountData.edition = this.value; }
	var lbl = alib.dom.createElement("span", pil, this.getActionName(2));
	alib.dom.styleSet(lbl, "font-weight", "bold");

	// Enterprise Edition
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSet(td, "width", "33%");
	var pil = alib.dom.createElement("div", td);
	alib.dom.styleSetClass(pil, "wizardEditionPil");
	var h1 = alib.dom.createElement("h1", pil, "Enterprise");
	var h3 = alib.dom.createElement("h3", pil, "$30 / user / month");
	var desc = alib.dom.createElement("div", pil);
	alib.dom.styleSetClass(desc, "desc");
	desc.innerHTML = "Unlimited number of users, unlimited storage, custom workflow automation, reports / business intelligence, custom applications.";
	var rdo = alib.dom.createElement("input", pil);
	rdo.type = 'radio';
	rdo.name = "editions";
	rdo.value = 3;
	rdo.checked = (this.accountData.edition == rdo.value) ? true : false;
	rdo.cls = this;
	rdo.onclick = function() { this.cls.accountData.edition = this.value; }
	var lbl = alib.dom.createElement("span", pil, this.getActionName(3));
	alib.dom.styleSet(lbl, "font-weight", "bold");

	// Free Edition
	var td = alib.dom.createElement("td", tr);
	alib.dom.styleSet(td, "width", "33%");
	var pil = alib.dom.createElement("div", td);
	alib.dom.styleSetClass(pil, "wizardEditionPil");
	var h1 = alib.dom.createElement("h1", pil, "Personal");
	var h3 = alib.dom.createElement("h3", pil, "Free");
	var desc = alib.dom.createElement("div", pil);
	alib.dom.styleSetClass(desc, "desc");
	desc.innerHTML = "1 user, 100MB storage, 250 customer accounts/contacts, 1 project.";
	var rdo = alib.dom.createElement("input", pil);
	rdo.type = 'radio';
	rdo.name = "editions";
	rdo.value = 1;
	rdo.checked = (this.accountData.edition == rdo.value) ? true : false;
	rdo.cls = this;
	rdo.onclick = function() { this.cls.accountData.edition = this.value; }
	var lbl = alib.dom.createElement("span", pil, this.getActionName(1));
	alib.dom.styleSet(lbl, "font-weight", "bold");

	// Information
	// -----------------------------------------
	var tr = alib.dom.createElement("tr", tbody);
	tr.setAttribute("valign", "top");

	// Billing and Invoices
	var td = alib.dom.createElement("td", tr);
	td.innerHTML = "<h3>Billing &amp; Invoices</h3>" +
				   "<p class='small'>An invoice will be sent with each billing cycle. Invoices can also be accessed in the \"Settings\" "+
				   "application.</p>" +
				   "<p class='small'>All accounts are billed month-to-month via a credit card. We accept Visa, MasterCard and American Express. "+
				   "Checks and POs are not accepted at this time.</p>";

	// Enterprise Edition
	var td = alib.dom.createElement("td", tr);
	td.innerHTML = "<h3>How It Works</h3>" +
				   "<p class='small'>When you change your edition the changes will be reflected in the next billing cycle.</p>" +
				   "<p class='small'>All editions are month-to-month so there are no long-term obligations. However, "+
				   "only one edition change per month is allowed.</p>";

	// Free Edition
	var td = alib.dom.createElement("td", tr);
	td.innerHTML = "<h3>Account Cancellation</h3>" +
				   "<p class='small'>We are always sorry to see any of our clients go, but we understand if you need to cancel your account. " +
				   "Upon cancellation, your account including all your data will be permanantly deleted and you will not be charged again " +
				   "if you have a pay account.</p>";

	var cancelDiv = alib.dom.createElement("div", td);
	alib.dom.styleSet(cancelDiv, "text-align", "right");
	var cancelA = alib.dom.createElement("a", cancelDiv, "Cancel Account");
	cancelA.href = 'javascript:void(0);';
	cancelA.cls = this;
	cancelA.onclick = function()
	{
		this.cls.accountData.edition = -1;
		this.cls.wizard.nextStep();
	}
}

/**
 * Load account data
 *
 * @param {DOMElement} con The container to print the first step on once done
 */
AntWizard_UpdateEdition.prototype.loadAccountData = function(con)
{
	ajax = new CAjax('json');
    ajax.cbData.cls = this;        
    ajax.cbData.con = con;
    ajax.onload = function(ret)
    {
		this.cbData.cls.accountData = ret;
		this.cbData.cls.stepOne(this.cbData.con);
    }
    ajax.exec("/controller/Admin/getEditionAndUsage");
}

/**
 * Get radio label
 *
 * Used to dynamically change the label based on current edition
 *
 * @param {int} edition The edition we are representing
 * @return {string} label name
 */
AntWizard_UpdateEdition.prototype.getActionName = function(edition)
{
	if (edition == this.accountData.edition)
		return "Your current plan";
	if (edition > this.accountData.edition)
		return "Upgrade!";
	if (edition < this.accountData.edition)
		return "Downgrade";
}

/**
 * Display step 2
 *
 * @public
 */
AntWizard_UpdateEdition.prototype.stepTwo= function(con)
{
	if (this.accountData.edition == -1) // cancel
	{
		var lbl = alib.dom.createElement("h1", con, "Cancel Account");
		var p = alib.dom.createElement('p', con, "WARNING: All your data will be permanantly deleted and will not be recoverable!");
		alib.dom.styleSetClass(p, "error");
		var p = alib.dom.createElement('p', con);
		p.innerHTML = "As an alternative to canceling your account, you could just downgrade to a 'Personal Edition' which will disable all accounts but the currently logged in user. However, all your data will be saved in case you decide to use it again at a later time.";
		var lbl = alib.dom.createElement("h3", con, "To downgrade click 'Back' below and select 'Personal Edition' on the previous screen.");
	}
	else if (this.accountData.edition == 1)
	{
		// display confirmation for free
		var lbl = alib.dom.createElement("h1", con, "Personal Edition");
		var p = alib.dom.createElement('p', con);
		p.innerHTML = "You have selected the 'Personal' edition and we are happy to offer this edition free of charge.</p><p>In the future if you need even more power then please remember that you can upgrade at any time by going to <strong>Settings &gt; Account &amp; Billing </strong> and clicking the 'Change Edition' button.";
	}
	else
	{
		this.stepTwoBilling(con);
	}
}

/**
 * Display step 2 - billing
 *
 * @public
 */
AntWizard_UpdateEdition.prototype.stepTwoBilling = function(con)
{
	var lbl = alib.dom.createElement("h1", con, "Please update your billing information below");

	var hr = alib.dom.createElement("hr", con);


	// Credit Card
	// -----------------------------------------
	
	/*
	var info = alib.dom.createElement("p", con);
	alib.dom.styleSetClass(info, "notice");
	info.innerHTML = "Talk about what they are doing here";
	*/

	var fieldSet = alib.dom.createElement("fieldset", con);
	var legend = alib.dom.createElement("legend", fieldSet, "Credit Card (all fields required)");

	var tbl = alib.dom.createElement("table", fieldSet);
	var tbody = alib.dom.createElement("tbody", tbl);

	var tr = alib.dom.createElement("tr", tbody);

	var td = alib.dom.createElement("td", tr, "Name on card");
	var td = alib.dom.createElement("td", tr);
	this.formBilling.ccName = alib.dom.createElement("input", td);
	alib.dom.styleSet(this.formBilling.ccName, "width", "300px");

	var tr = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", tr, "Card Number: ");
	var td = alib.dom.createElement("td", tr);
	this.formBilling.ccNumber= alib.dom.createElement("input", td);
	alib.dom.styleSet(this.formBilling.ccNumber, "width", "300px");

	var tr = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", tr, "Expires: ");
	var td = alib.dom.createElement("td", tr);
	this.formBilling.ccExpMonth = alib.dom.createElement("input", td);
	alib.dom.styleSet(this.formBilling.ccExpMonth, "width", "30px");
	lbl = alib.dom.createElement("span", td, " (MM) ");
	this.formBilling.ccExpYear = alib.dom.createElement("input", td);
	alib.dom.styleSet(this.formBilling.ccExpYear, "width", "30px");
	lbl = alib.dom.createElement("span", td, " (YY) - enter month and year. For example: January 2015 would be \"0115\"");

	// Billing Address
	// -----------------------------------------
	var fieldSet = alib.dom.createElement("fieldset", con);
	var legend = alib.dom.createElement("legend", fieldSet, "Billing Address (all fields required)");

	var tbl = alib.dom.createElement("table", fieldSet);
	var tbody = alib.dom.createElement("tbody", tbl);

	var tr = alib.dom.createElement("tr", tbody);

	var td = alib.dom.createElement("td", tr, "Street Address: ");
	var td = alib.dom.createElement("td", tr);
	td.setAttribute("colSpan", 3);
	this.formBilling.address = alib.dom.createElement("input", td);
	alib.dom.styleSet(this.formBilling.address, "width", "300px");

	var tr = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", tr, "City: ");
	var td = alib.dom.createElement("td", tr);
	td.setAttribute("colSpan", 3);
	this.formBilling.city = alib.dom.createElement("input", td);
	alib.dom.styleSet(this.formBilling.city, "width", "300px");

	var tr = alib.dom.createElement("tr", tbody);
	var td = alib.dom.createElement("td", tr, "State: ");
	var td = alib.dom.createElement("td", tr);
	this.formBilling.state = alib.dom.createElement("input", td);
	alib.dom.styleSet(this.formBilling.state, "width", "160px");
	var td = alib.dom.createElement("td", tr, "Zip: ");
	this.formBilling.zip = alib.dom.createElement("input", td);
	alib.dom.styleSet(this.formBilling.zip, "width", "60px");

	// Set validating and processing function
	this.processStep = function()
	{
		// Check for blank fields first
		if (!this.formBilling.ccName.value)
		{
			this.lastErrorMessage = "Please the name on your credit card";
			return false;
		}

		if (!this.formBilling.ccNumber.value)
		{
			this.lastErrorMessage = "A valid credit card number is required";
			return false;
		}

		if (!this.formBilling.ccExpMonth.value)
		{
			this.lastErrorMessage = "Please enter the month when your credit card expires";
			return false;
		}

		if (!this.formBilling.ccExpYear.value)
		{
			this.lastErrorMessage = "Please enter the year when your credit card expires";
			return false;
		}

		if (!this.formBilling.address.value)
		{
			this.lastErrorMessage = "Please enter your billing street address or P.O. box number";
			return false;
		}

		if (!this.formBilling.city.value)
		{
			this.lastErrorMessage = "Please enter your billing city";
			return false;
		}

		if (!this.formBilling.state.value)
		{
			this.lastErrorMessage = "Please enter your billing state or region";
			return false;
		}

		if (!this.formBilling.zip.value)
		{
			this.lastErrorMessage = "Please enter your billing zip/postal code";
			return false;
		}

		// Register async flag, the wizard will wait until we move this flag to false
		this.wizard.setProcessing();

		// Save values
		var ajax = new CAjax('json');
		ajax.cbData.cls = this;        
		ajax.onload = function(ret)
		{
			this.cbData.cls.wizard.setProcessingFinished(true);
		}

		var params = [
						["address_street", this.formBilling.address.value],
						["address_street2", ""],
						["address_city", this.formBilling.city.value],
						["address_state", this.formBilling.state.value],
						["address_zip", this.formBilling.zip.value],
						["ccard_name", this.formBilling.ccName.value],
						["ccard_number", this.formBilling.ccNumber.value],
						["ccard_exp_month", this.formBilling.ccExpMonth.value],
						["ccard_exp_year", this.formBilling.ccExpYear.value]
		];
		ajax.exec("/controller/Admin/updateBilling", params);

		return true;
	}
}

/**
 * Display step 3
 *
 * @public
 */
AntWizard_UpdateEdition.prototype.stepFinished = function(con)
{
	con.innerHTML = "<h1>Updating account, please wait...</h1><div class='loading'></div>";

	//this.wizard.setProcessing();

	// Save values
	var ajax = new CAjax('json');
	ajax.cbData.cls = this;        
	ajax.cbData.con = con;
	ajax.onload = function(ret)
	{
		this.cbData.con.innerHTML = "<h1>Finished Updating Your Edition</h1>"
								 + "<table><tr><td style='padding-right:10px;'><img src='/images/icons/success_128.png'></td><td>"
								 + "<p class='success'>Congratulations, your changes have been successfully applied. "
								 + "Click 'Finished' below to close this wizard.</p>"
								 + "</td></tr></table>";
		//this.cbData.cls.wizard.setProcessingFinished(true);
	}

	var params = [["edition", this.accountData.edition]];
	ajax.exec("/controller/Admin/setAddition", params);
}
