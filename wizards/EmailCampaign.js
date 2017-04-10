/**
 * @fileoverview This email handles setting up an email_campaign for sending mass email
 *
 * <code>
 * 	var wiz = new AntWizard("EmailCampaign");
 * 	wiz.onFinished = function() { alert("The wizard is finished); };
 * 	wiz.onCancel = function() { alert("The wizard was canceled"); };
 * 	wiz.show();
 * </code>
 */

/**
 * @constructor
 */
function AntWizard_EmailCampaign()
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
     * email campaign form elements
     * 
     * @private
     * @var {Object}
     */
    this.campaignForm = new Object();
    
    /**
	 * email campaign data
	 * 
	 * @private
	 * @var {Object}
	 */
	this.campaignData = new Array();
    
    /**
     * Object Id
     * 
     * @private
     * @var {Integer}
     */
    this.objId = null;
    
    /**
     * Object type to use when getting views and fields
     * 
     * @private
     * @var {String}
     */
    this.objectType = "customer";
    
    /**
     * The id of campaign template selected
     * 
     * @private
     * @var {Integer}
     */
    //this.campaignTemplateId = null;
    
    /**
     * Filter Conditions for view
     * 
     * @private
     * @var {Array}
     */
    this.savedConditions = new Array;
    
    /**
     * Recipient type
     * 
     * @public
     * @var {String}
     */
    this.recipientType = null;
    
    /**
     * Design type
     * 
     * @public
     * @var {String}
     */
    //this.designType = null;

	/**
	 * Email campaing object
	 *
	 * @public
	 * @var {CAntObject("email_campaign")}
	 */
	this.campaignObject = null;
}

/**
 * Setup steps for this wizard
 *
 * @param {AntWizard} wizard Required handle to parent wizard class
 */
AntWizard_EmailCampaign.prototype.setup = function(wizard)
{
    this.antObject = new CAntObject(this.objectType);
    
	this.wizard = wizard;
	this.wizard.title = "Send Mass Email";
	var me = this;

	// Add step 1 - build campaign
	this.wizard.addStep(function(con) {me.stepDetails(con);}, "Campaign Details");

	// Add step 2 - define recipients
	this.wizard.addStep(function(con) {me.stepRecipients(con);}, "Recipients");

	// Add step 3 - select a design
	this.wizard.addStep(function(con) {me.stepDesign(con);}, "Design");
    
    // Add step 4 - email compose
    this.wizard.addStep(function(con) {me.stepCompose(con);}, "Compose");

    // Add step 5 - testing
    this.wizard.addStep(function(con) {me.stepTest(con);}, "Test Campaign");
    
    // Final Step
    this.wizard.addStep(function(con) {me.stepFinished(con);}, "Finished");

    this.wizard.onFinished = function()
    {
        me.saveEmailCampaign();
    }
    
	// change width
	//this.wizard.width = 820; // 820 px

	// Load existing campaign if already loaded
	if (wizard.cbData.campObj)
	{
		this.campaignObject = wizard.cbData.campObj;
	}
	else
	{
		this.campaignObject = new CAntObject("email_campaign");
		this.campaignObject.setValue('f_trackcamp', true); // Default to true
	}

	// Add save & finish later
	wizard.saveAndFinishLater = function(step)
	{
		this.subclass.saveEmailCampaign(1); // status 1 = draft
	}
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
AntWizard_EmailCampaign.prototype.processStep = function() {}

/**
 * This function is called when the wizard steps back
 * 
 */
AntWizard_EmailCampaign.prototype.processBackStep = function() 
{
    this.mapCampaignData(); // Need to remap for changes
}

/**
 * This function is called after the process is showed
 * 
 */
AntWizard_EmailCampaign.prototype.processPostStep = function() 
{
    this.setCampaignData();
}

/**
 * Display basic details
 *
 * @param {DOMElement} con      Container Element
 * @return {bool} true on success, false on failure. Set this.lastErrorMessage if failed.
 */
AntWizard_EmailCampaign.prototype.stepDetails = function(con) 
{
    // Containers
	var divContainer = alib.dom.createElement("div", con);
    var divHeader = alib.dom.createElement("div", divContainer);
    var divForm = alib.dom.createElement("div", divContainer);
    
    divHeader.innerHTML = "This wizard will guide you through sending an email campaign. The first step to sending the campaign is to set some basic details.";
    
    // Header StyleSet
    alib.dom.styleSet(divHeader, "margin", "20px 0");
    alib.dom.styleSet(divContainer, "font-size", "12px");
    
    // Campaign Name
    var divCampainName = alib.dom.createElement("div", divForm);
    var campaignNameLabel = alib.dom.setElementAttr(alib.dom.createElement("div", divCampainName), [["innerHTML", "Campaign Name"]]);
	var txtCampaignName = alib.dom.setElementAttr(alib.dom.createElement("input", divCampainName), [["type", "text"], ["size", "50"]]);
	txtCampaignName.value = this.campaignObject.getValue("name");
	txtCampaignName.cls = this;
	txtCampaignName.onchange = function() { this.cls.campaignObject.setValue("name", this.value); }
    //this.campaignForm.campaignName = alib.dom.setElementAttr(alib.dom.createElement("input", divCampainName), [["type", "text"], ["size", "50"]]);
    var campaignNameDesc = alib.dom.setElementAttr(alib.dom.createElement("label", divCampainName), [["innerHTML", ' Example: "July 2013 Newsletter"']]);
	//this.campaignForm.campaignName.value = this.campaignObject.getValue("name");
    
    // Campaign Name StyleSet
    alib.dom.styleSet(divCampainName, "margin-bottom", "5px");
    alib.dom.styleSet(campaignNameLabel, "font-weight", "bold");
    alib.dom.styleSet(campaignNameLabel, "margin-bottom", "3px");
    
    alib.dom.setElementAttr(alib.dom.createElement("div", divForm), [["innerHTML", "This is the name you will use to distinguish this campaign. It will not be sent to the recipients."]]);
    
    // Subject Link
    var divSubjectLine = alib.dom.createElement("div", divForm);
    var subjectLineLabel = alib.dom.setElementAttr(alib.dom.createElement("div", divSubjectLine), [["innerHTML", "Enter the email subject line"]]);
    //this.campaignForm.subjectLine = alib.dom.setElementAttr(alib.dom.createElement("input", divSubjectLine), [["type", "text"], ["size", "70"]]);
	var subjectLine = alib.dom.setElementAttr(alib.dom.createElement("input", divSubjectLine), [["type", "text"], ["size", "70"]]);
	subjectLine.value = this.campaignObject.getValue("subject");
	subjectLine.cls = this;
	subjectLine.onchange = function() { this.cls.campaignObject.setValue("subject", this.value); }
    
    // Subject Link StyleSet
    alib.dom.styleSet(divSubjectLine, "margin", "20px 0");
    alib.dom.styleSet(subjectLineLabel, "font-weight", "bold");
    alib.dom.styleSet(subjectLineLabel, "margin-bottom", "3px");
    
    // Who is this campaign from
    var divCampaignFrom = alib.dom.createElement("div", divForm);
    var campaignFromLabel = alib.dom.setElementAttr(alib.dom.createElement("div", divCampaignFrom), [["innerHTML", "Who is this campaign from"]]);
    
    // Name
    var nameLabel = alib.dom.setElementAttr(alib.dom.createElement("label", divCampaignFrom), [["innerHTML", 'Name ']]);
    //this.campaignForm.campaignFromName = alib.dom.setElementAttr(alib.dom.createElement("input", divCampaignFrom), [["type", "text"], ["size", "40"]]);
	var campaignFromName = alib.dom.setElementAttr(alib.dom.createElement("input", divCampaignFrom), [["type", "text"], ["size", "40"]]);
	campaignFromName.value = this.campaignObject.getValue("from_name");
	campaignFromName.cls = this;
	campaignFromName.onchange = function() { this.cls.campaignObject.setValue("from_name", this.value); }
    
    // Email
    var emailLabel = alib.dom.setElementAttr(alib.dom.createElement("label", divCampaignFrom), [["innerHTML", 'Email ']]);
    //this.campaignForm.campaignFromEmail = alib.dom.setElementAttr(alib.dom.createElement("input", divCampaignFrom), [["type", "text"], ["size", "40"]]);
	var campaignFromEmail = alib.dom.setElementAttr(alib.dom.createElement("input", divCampaignFrom), [["type", "text"], ["size", "40"]]);
	campaignFromEmail.value = this.campaignObject.getValue("from_email");
	campaignFromEmail.cls = this;
	campaignFromEmail.onchange = function() { this.cls.campaignObject.setValue("from_email", this.value); }
    
    // Campaign From StyleSet
    alib.dom.styleSet(campaignFromName, "margin-right", "30px");
    alib.dom.styleSet(campaignFromLabel, "font-weight", "bold");
    alib.dom.styleSet(campaignFromLabel, "margin-bottom", "3px");
    
    // Track Report
    var divTrackReport = alib.dom.createElement("div", divForm);
    var trackReport = alib.dom.setElementAttr(alib.dom.createElement("input", divTrackReport), [["type", "checkbox"], ["checked", "true"]]);
	trackReport.checked = this.campaignObject.getValue("f_trackcamp");
	trackReport.cls = this;
	trackReport.onclick = function() { this.cls.campaignObject.setValue("f_trackcamp", this.checked); };
    var trackReportLabel = alib.dom.setElementAttr(alib.dom.createElement("label", divTrackReport), [["innerHTML", ' Track and report on this campaign<br />']]);
    var trackReportDesc = alib.dom.setElementAttr(alib.dom.createElement("label", divTrackReport), [["innerHTML", 'Keep track of number of emails opened, links clicked, and people who unsubscribe.']]);
    
    // Track Report Style
    alib.dom.styleSet(divTrackReport, "margin", "20px 0");
    alib.dom.styleSet(trackReportLabel, "font-weight", "bold");
    alib.dom.styleSet(trackReportDesc, "margin-left", "20px");
    alib.dom.styleSet(trackReport, "float", "left");
    alib.dom.styleSet(trackReport, "margin-top", "2px");
    
    var cls = this;
    this.processStep = function()
    {
        if(cls.campaignObject.getValue("name").length == 0)
        {
            this.lastErrorMessage = "Invalid Campaign Name";            
            return false;
        }
        
        if(cls.campaignObject.getValue("subject").length == 0)
        {
            this.lastErrorMessage = "Invalid Subject Line";            
            return false;
        }
        
        if(cls.campaignObject.getValue("from_name").length == 0)
        {
            this.lastErrorMessage = "Invalid Campaign From: Name";
            return false;
        }
        
        if(cls.campaignObject.getValue("from_email").length == 0)
        {
            this.lastErrorMessage = "Invalid Campaign From: Email";
            return false;
        }
        
        this.mapCampaignData();
        return true;
    }
}

/**
 * Display recipients
 *
 * @param {DOMElement} con      Container Element
 * @return {bool} true on success, false on failure. Set this.lastErrorMessage if failed.
 */
AntWizard_EmailCampaign.prototype.stepRecipients = function(con) 
{
    // Containers
	var divContainer = alib.dom.createElement("div", con);
    var divHeader = alib.dom.createElement("div", divContainer);
    var divForm = alib.dom.createElement("div", divContainer);
    
    divHeader.innerHTML = "Now lets figure out who you would like to be the recipients of this campaign. Select a list of recipients from the options below.";
    
    // Header StyleSet
    alib.dom.styleSet(divHeader, "margin", "20px 0");
    alib.dom.styleSet(divContainer, "font-size", "12px");

    var radioLabel = new Object();
    this.campaignForm.viewRadio = new Object();
    this.campaignForm.viewRadio.type = "objectRadio";
    
    // Using View
    var divView = alib.dom.createElement("div", divForm);
    this.campaignForm.viewRadio.view = alib.dom.setElementAttr(alib.dom.createElement("input", divView), [["type", "radio"], ["name", "recipientsType"], ["value", "view"]]);
    radioLabel.view = alib.dom.setElementAttr(alib.dom.createElement("label", divView), [["innerHTML", " Send messages to everyone using a view"]]);
    var selectContainer = alib.dom.createElement("div", divView);
    this.campaignForm.viewSelect = alib.dom.setElementAttr(alib.dom.createElement("select", selectContainer));
    
    // Set View Dropdown
    var viewIdx = this.campaignForm.viewSelect.length;
    this.campaignForm.viewSelect[viewIdx] = new Option("All", "");
    
    // Using View StyleSet
    alib.dom.styleSet(divView, "margin", "20px 0");
    alib.dom.styleSet(this.campaignForm.viewSelect, "display", "none");
    alib.dom.styleSet(selectContainer, "margin-left", "20px");
    alib.dom.styleSetClass(selectContainer, "loading");
    
    // Using Condition
    var divCondition = alib.dom.createElement("div", divForm);
    this.campaignForm.viewRadio.condition = alib.dom.setElementAttr(alib.dom.createElement("input", divCondition), [["type", "radio"], ["name", "recipientsType"], ["value", "condition"]]);
    radioLabel.condition = alib.dom.setElementAttr(alib.dom.createElement("label", divCondition), [["innerHTML", " Send to all recipients that meet the following conditions:<br />"]]);
    
    this.condOverlay = alib.dom.setElementAttr(alib.dom.createElement("div", divCondition));
    
    // Condition List Container
	if (this.savedConditions.length == 0 && this.campaignObject.getValue('to_conditions'))
	{
		var conditions = JSON.parse(this.campaignObject.getValue('to_conditions'));
		
		if (conditions && conditions.length)
		{
			this.savedConditions = conditions;
		}
	}
    var conditionFrame = new CWindowFrame(null, null, "3px");
    var frameCon = conditionFrame.getCon();
    conditionFrame.print(divCondition);    
    this.viewConditions = this.antObject.buildAdvancedQuery(frameCon, this.savedConditions);
    
    // Change the link title of add condition
    frameCon.lastChild.firstChild.innerHTML = " + Add Filter Condition";
    
    // Using Condition StyleSet
    this.setOverlayStyle(frameCon);
    alib.dom.styleSet(divCondition, "margin-bottom", "20px");
    alib.dom.styleSet(frameCon, "margin-left", "20px");
    
    // Using Email
    var divEmail = alib.dom.createElement("div", divForm);
    this.campaignForm.viewRadio.email = alib.dom.setElementAttr(alib.dom.createElement("input", divEmail), [["type", "radio"], ["name", "recipientsType"], ["value", "manual"]]);
    radioLabel.email = alib.dom.setElementAttr(alib.dom.createElement("label", divEmail), [["innerHTML", " Enter email addresses manually (separate with a comma ',')<br />"]]);
    this.campaignForm.viewEmail = alib.dom.setElementAttr(alib.dom.createElement("textarea", divEmail), [["cols", "105"], ["rows", "5"]]);
	this.campaignForm.viewEmail.value = this.campaignObject.getValue("to_manual");
	this.campaignForm.viewEmail.cls = this;
	this.campaignForm.viewEmail.onchange = function() { this.cls.campaignObject.setValue("to_manual", this.value); };
    
    // Using Email StyleSet
    alib.dom.styleSet(divCondition, "margin-bottom", "20px");
    alib.dom.styleSet(this.campaignForm.viewEmail, "margin-left", "20px");
    
    // Setup Radio Events
    for(radio in this.campaignForm.viewRadio)
    {
        var currentRadio = this.campaignForm.viewRadio[radio];
        
        currentRadio.cls = this;
        currentRadio.onclick = function()
        {
            this.checked = true;
			this.cls.campaignObject.setValue("to_type", this.value);
            //this.cls.recipientType = this.value;
            this.cls.unselectedElements(this);
        }
    }
    
    // Setup label onclick to trigger radio buttons
    for(radio in radioLabel)
    {
        var currentLabel = radioLabel[radio];
        alib.dom.styleSet(currentLabel, "cursor", "pointer");
        
        currentLabel.onclick = function()
        {
            this.previousSibling.onclick();
        }
    }
    
    // View Dropdown Event
    this.campaignForm.viewSelect.cls = this;
    this.campaignForm.viewSelect.onchange = function()
    {
        //this.cls.campaignForm.sendViewId = this.value;
		this.cls.campaignObject.setValue("to_view", this.value);
    }
    
    // Set View Type as default - Temporary
	switch (this.campaignObject.getValue('to_type'))
	{
	case 'manual':
    	this.campaignForm.viewRadio.email.checked = true;
    	this.campaignForm.viewRadio.email.onclick();
		break;
	case 'condition':
    	this.campaignForm.viewRadio.condition.checked = true;
    	this.campaignForm.viewRadio.condition.onclick();
		break;
	case 'view':
	default:
    	this.campaignForm.viewRadio.view.checked = true;
    	this.campaignForm.viewRadio.view.onclick();
		break;
	}
    
    // Load view types
    this.loadViewTypes();
    
    this.processStep = function()
    {
        this.mapCampaignData();
        return true;
    }
}

/**
 * Display html template designs
 *
 * @param {DOMElement} con      Container Element
 * @return {bool} true on success, false on failure. Set this.lastErrorMessage if failed.
 */
AntWizard_EmailCampaign.prototype.stepDesign = function(con) 
{
    // Containers
    var divContainer = alib.dom.createElement("div", con);
    var divHeader = alib.dom.createElement("div", divContainer);
    var divForm = alib.dom.createElement("div", divContainer);
    
    divHeader.innerHTML = "Now select the design and format you will be using for this email campaign";
    
    // Header StyleSet
    alib.dom.styleSet(divHeader, "margin", "20px 0");
    alib.dom.styleSet(divContainer, "font-size", "12px");
    
    radioLabel = new Object();
    this.campaignForm.designRadio = new Object();
    this.campaignForm.designRadio.type = "objectRadio";
    
    // Compose Scratch
    var divScratch = alib.dom.createElement("div", divForm);
    this.campaignForm.designRadio.scratch = alib.dom.setElementAttr(alib.dom.createElement("input", divScratch), [["type", "radio"], ["name", "designRadio"], ["value", "blank"]]);
    radioLabel.scratch = alib.dom.setElementAttr(alib.dom.createElement("label", divScratch), [["innerHTML", " Compose a new email from scratch"]]);
    
    // Compose Scratch StyleSet
    alib.dom.styleSet(divScratch, "margin", "20px 0");
    
    // Compose Template
    var divTemplate = alib.dom.createElement("div", divForm);
    this.campaignForm.designRadio.template = alib.dom.setElementAttr(alib.dom.createElement("input", divTemplate), [["type", "radio"], ["name", "designRadio"], ["value", "template"]]);
    radioLabel.template = alib.dom.setElementAttr(alib.dom.createElement("label", divTemplate), [["innerHTML", " Compose email using a pre-designed template"]]);
    var templateContainer = alib.dom.createElement("div", divTemplate);    
    
    this.htmlTemplate(templateContainer);
    
    // Compose Template StyleSet
    alib.dom.styleSet(divTemplate, "margin", "20px 0");
    alib.dom.styleSet(templateContainer, "margin-left", "20px");
    
    // Setup Radio Events
    for(radio in this.campaignForm.designRadio)
    {
        var currentRadio = this.campaignForm.designRadio[radio];
        
        currentRadio.cls = this;
        currentRadio.onclick = function()
        {
            this.checked = true;
            //this.cls.designType = this.value;
			this.cls.campaignObject.setValue('design_type', this.value);
            this.cls.unselectedElements(this);
        }
    }
    
    // Setup label onclick to trigger radio buttons
    for(radio in radioLabel)
    {
        var currentLabel = radioLabel[radio];
        alib.dom.styleSet(currentLabel, "cursor", "pointer");
        
        currentLabel.onclick = function()
        {
            this.previousSibling.onclick();
        }
    }
    
    // Set Compose Scratch as default - Temporary
	switch (this.campaignObject.getValue('design_type'))
	{
	case 'template':
		this.campaignForm.designRadio.template.checked = true;
		this.campaignForm.designRadio.template.onclick();
		break;
	case 'blank':
	default:
		this.campaignForm.designRadio.scratch.checked = true;
		this.campaignForm.designRadio.scratch.onclick();
		break;
	}
    
    this.processStep = function()
    {
        this.mapCampaignData();
        return true;
    }
}

/**
 * Display Compose Step
 *
 * @param {DOMElement} con      Container Element
 * @return {bool} true on success, false on failure. Set this.lastErrorMessage if failed.
 */
AntWizard_EmailCampaign.prototype.stepCompose = function(con) 
{
    // Containers
    var divContainer = alib.dom.createElement("div", con);
    var divHeader = alib.dom.createElement("div", divContainer);
    var divForm = alib.dom.createElement("div", divContainer);
    
    divHeader.innerHTML = "Now that we have the design selected, it's time to compose the body of the email.";
    
    // Header StyleSet
    alib.dom.styleSet(divHeader, "margin", "20px 0");
    alib.dom.styleSet(divContainer, "font-size", "12px");
    
    // Compose Tabs
    var divLoading = alib.dom.setElementAttr(alib.dom.createElement("div", divForm), [["innerHTML", "<div class='loading'></div>"]]);
    var divCompose = alib.dom.createElement("div", divForm);
    var composeTabs = new CTabs();
    composeTabs.print(divCompose);
    
    // Compose Html - Rich Textbox
    var divHtml = alib.dom.createElement("div", composeTabs.addTab("HTML"));
    var divPlain = alib.dom.createElement("div", composeTabs.addTab("Plain Text"));
    
    // Compose Plain
    this.buildRte(divHtml, null);
    this.campaignForm.composePlain = alib.dom.setElementAttr(alib.dom.createElement("textarea", divPlain), [["cols", "120"], ["rows", "20"]]);
    
    // Compose Plain Event
    this.campaignForm.composePlain.cls = this;
    this.campaignForm.composePlain.onchange = function()
    {
		this.cls.campaignObject.setValue('body_plain', this.value);

		// Set RTE if blank
		if (!this.cls.composeRte.getValue())
			this.cls.composeRte.setValue(this.value);
		/*
        this.cls.composeRte.setValue(this.value);
        this.cls.composeRte.updateText(this.value);
		*/
    }
    
    // Get the html template data
	if (this.campaignObject.getValue("body_html"))
	{
        divForm.removeChild(divLoading);

		// Html
		this.composeRte.setValue(this.campaignObject.getValue("body_html"));
		
		// Plain
		this.campaignForm.composePlain.value = this.campaignObject.getValue("body_plain");
	}
	else if(this.campaignObject.getValue("template_id") > 0 && this.campaignForm.designRadio.template.checked)
    {
        alib.dom.styleSet(divCompose, "display", "none");
        
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            alib.dom.styleSet(divCompose, "display", "block");
            divForm.removeChild(divLoading);
            if(!ret)
                return;
                
            if(ret.error)
                ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
            else
            {
                // Html
                this.cbData.cls.composeRte.setValue(ret.html);
                //this.cbData.cls.composeRte.updateText(ret.html);
                
                // Plain
                this.cbData.cls.campaignForm.composePlain.value = ret.plain;
            }
        };
        var args = new Array();
        args[args.length] = ['id', this.campaignObject.getValue('template_id')];
        ajax.exec("/controller/Email/getHtmlTemplateData", args);
    }
    else
    {
        divForm.removeChild(divLoading);
    }
}

/**
 * Display testing
 *
 * @param {DOMElement} con Container Element
 * @return {bool} true on success, false on failure. Set this.lastErrorMessage if failed.
 */
AntWizard_EmailCampaign.prototype.stepTest = function(con) 
{
	// Header
    var title = alib.dom.createElement("h1", con);
	title.innerHTML = "Send Test";

	// Description
    var desc = alib.dom.createElement("p", con);
	desc.innerHTML = "Your campaign is just about ready to send; but before sending to all your recipients we recommend sending yourself a test to make sure the design and content match your expectations. If you want to skip the test just click \"Continue\" below.";

    var desc = alib.dom.createElement("hr", con);

	// Send test form
	// -------------------------------------------------
	var frmCon = alib.dom.createElement("div", con);
	var resCon = alib.dom.createElement("span"); // pre-create for later

	var lbl = alib.dom.createElement("span", frmCon, "&nbsp;&nbsp;&nbsp;Email Address ");
	alib.dom.styleSetClass(lbl, "wizardLabel");
	var txtTest = alib.dom.createElement("input", frmCon);
	txtTest.type = "text";

	// Spacer
	var frmCon = alib.dom.createElement("span", frmCon, "&nbsp;");

	var btn = alib.ui.Button("Send Test", {
			className:"b1", tooltip:"Click to send a test to this address", cls:this, 
			resCon:resCon, txtTest:txtTest,
			onclick:function() {
				var email = this.txtTest.value;
				if (!email)
				{
					alert("Please enter an email address to test");
					this.txtTest.focus();
					return;
				}

				this.txtTest.value = "";
				this.resCon.innerHTML = "<img src=\"/images/loading.gif\" /> Sending, please wait...";

				var bodyHtml = (this.cls.composeRte) ? this.cls.composeRte.getValue() : this.cls.campaignObject.getValue("body_html");

				var args = [
					["test_email", email], 
					["body_html", bodyHtml],
					["body_plain", this.cls.campaignObject.getValue("body_plain")],
					["from_email", this.cls.campaignObject.getValue("from_email")],
					["subject", this.cls.campaignObject.getValue("subject")],
				];

				var ajax = new CAjax('json');
				ajax.cbData.resCon = this.resCon;
				ajax.onload = function(ret)
				{
					this.cbData.resCon.innerHTML = (ret == 1) ? " Test message has been sent." : " There was a problem, please try again.";
				};
				ajax.exec("/controller/Email/testProcessEmailCampaign", args);
			}
		});
	btn.print(frmCon);


	frmCon.appendChild(resCon);

    var desc = alib.dom.createElement("hr", con);
}

/**
 * Display finished confirmation
 *
 * @param {DOMElement} con      Container Element
 * @return {bool} true on success, false on failure. Set this.lastErrorMessage if failed.
 */
AntWizard_EmailCampaign.prototype.stepFinished = function(con) 
{
	// Containers
    var divContainer = alib.dom.createElement("div", con);
    var divHeader = alib.dom.createElement("div", divContainer);
    var divForm = alib.dom.createElement("div", divContainer);
    
    divHeader.innerHTML = "Congratulations! You are ready to launch your campaign. Select one of the delivery options and click \"Finished\" below to complete this wizard.";
    
    // Header StyleSet
    alib.dom.styleSet(divHeader, "margin", "20px 0");
    alib.dom.styleSet(divContainer, "font-size", "12px");
    
    radioLabel = new Object();
    this.campaignForm.sendRadio = new Object();
    this.campaignForm.sendRadio.type = "objectRadio";
    
    // Send Now
    var divSend = alib.dom.createElement("div", divForm);
    this.campaignForm.sendRadio.now = alib.dom.setElementAttr(alib.dom.createElement("input", divSend), [["type", "radio"], ["name", "sendRadio"], ["value", "now"]]);
    radioLabel.now = alib.dom.setElementAttr(alib.dom.createElement("label", divSend), [["innerHTML", " Send campaign right now<br />"]]);
    var sendNowDesc = alib.dom.setElementAttr(alib.dom.createElement("label", divSend), [["innerHTML", "Selecting this option will begin sending messages immediately. Once every recipient has been sent the email, an optional confirmation email will be sent (see below)."]]);
    
    // Compose Scratch StyleSet
    alib.dom.styleSet(divSend, "margin", "20px 0");
    alib.dom.styleSet(radioLabel.now, "font-weight", "bold");
    alib.dom.styleSet(sendNowDesc, "margin", "5px 0 20px 20px");
    alib.dom.styleSet(sendNowDesc, "float", "left");
    
    // Schedule
    var divSchedule = alib.dom.createElement("div", divForm);
    this.campaignForm.sendRadio.schedule = alib.dom.setElementAttr(alib.dom.createElement("input", divSchedule), [["type", "radio"], ["name", "sendRadio"], ["value", "schedule"]]);
    radioLabel.schedule = alib.dom.setElementAttr(alib.dom.createElement("label", divSchedule), [["innerHTML", " Schedule campaign to be sent at a later time<br />"]]);
    var scheduleDesc = alib.dom.setElementAttr(alib.dom.createElement("label", divSchedule), [["innerHTML", "Set campaign to be sent at a future date/time using the fields below. Note, setting the time below denotes \"start time\" but it may take a while to actually send to all recipients."]]);
    var scheduleInputContainer = alib.dom.createElement("div", divSchedule);
    
    // Calendar Input
    var today = new Date();
    var dd = today.getDate();
    var mm = today.getMonth()+1;
    var yyyy = today.getFullYear();

    this.campaignForm.scheduleDate = alib.dom.setElementAttr(alib.dom.createElement("input", scheduleInputContainer), [["type", "text"], ["size", "15"], ["value", mm + "/" + dd + "/" + yyyy]]);
    var calendarClass = new CAutoCompleteCal(this.campaignForm.scheduleDate);
    
    // Time Input
    this.campaignForm.scheduleTime = alib.dom.setElementAttr(alib.dom.createElement("input", scheduleInputContainer), [["type", "text"], ["size", "10"], ["value", "8:30 AM"]]);
    var timeClass = new CAutoCompleteTime(this.campaignForm.scheduleTime);
    
    // Schedule StyleSet
    alib.dom.styleSet(divSchedule, "margin-bottom", "20px");
    alib.dom.styleSet(radioLabel.schedule, "font-weight", "bold");
    alib.dom.styleSet(scheduleDesc, "margin", "5px 0 10px 20px");
    alib.dom.styleSet(scheduleDesc, "float", "left");
    alib.dom.styleSet(scheduleInputContainer, "margin-left", "20px");
    alib.dom.styleSet(this.campaignForm.scheduleDate, "margin-right", "10px");
    
    // Spacer
    var spacer = alib.dom.createElement("hr", divForm);
    
    // Send Now
    var divConfirmation = alib.dom.createElement("div", divForm);
    this.campaignForm.sendConfirmation = alib.dom.setElementAttr(alib.dom.createElement("input", divConfirmation), [["type", "checkbox"], ["checked", true]]);
    var confirmationLabel = alib.dom.setElementAttr(alib.dom.createElement("label", divConfirmation), [["innerHTML", " Send confirmation to: "]]);
    this.campaignForm.emailConfirmation = alib.dom.setElementAttr(alib.dom.createElement("input", divConfirmation), [["type", "text"], ["size", 30]]);
    var emailLabel = alib.dom.setElementAttr(alib.dom.createElement("label", divConfirmation), [["innerHTML", " after the campaign has been sent."]]);
	this.campaignForm.emailConfirmation.value = this.campaignObject.getValue("confirmation_email");
	this.campaignForm.emailConfirmation.cls = this;
	this.campaignForm.emailConfirmation.onchange = function() { this.cls.campaignObject.setValue("confirmation_email", this.value); };
    
    // Compose Scratch StyleSet
    alib.dom.styleSet(divConfirmation, "margin-top", "30px");
    alib.dom.styleSet(this.campaignForm.sendConfirmation, "float", "left");
    alib.dom.styleSet(this.campaignForm.sendConfirmation, "margin-top", "9px");
    
    // Setup Radio Events
    for(radio in this.campaignForm.sendRadio)
    {
        var currentRadio = this.campaignForm.sendRadio[radio];
        
        currentRadio.cls = this;
        currentRadio.onclick = function()
        {
            this.checked = true;
            this.cls.unselectedElements(this);
        }
    }
    
    // Setup label onclick to trigger radio buttons
    for(radio in radioLabel)
    {
        var currentLabel = radioLabel[radio];
        alib.dom.styleSet(currentLabel, "cursor", "pointer");
        
        currentLabel.onclick = function()
        {
            this.previousSibling.onclick();
        }
    }
    
    // Setup Send Confirmation Event
    this.campaignForm.sendConfirmation.cls = this;
    this.campaignForm.sendConfirmation.onchange = function()
    {
		this.cls.campaignObject.setValue("f_confirmation", this.checked);
        if(this.checked)
            this.cls.campaignForm.emailConfirmation.removeAttribute("disabled");
        else
            this.cls.campaignForm.emailConfirmation.disabled = "disabled";
    }
    
    // Set Send now as default - Temporary
    this.campaignForm.sendRadio.now.checked = true;
    this.unselectedElements(this.campaignForm.sendRadio.now);    

	// Add another spacer
    var spacer = alib.dom.createElement("hr", divForm);
	alib.dom.styleSet(spacer, "margin", "20px 0 20px 0");

    var nextHdr = alib.dom.createElement("h3", divForm, "Click \"Finished\" to send this campaign");

    var ajax = new CAjax('json');
	ajax.cbData.nextHdr = nextHdr;
	ajax.onload = function(ret)
	{
		this.cbData.nextHdr.innerHTML = "Click \"Finished\" to send this campaign to " + ret + " recipients";
	};

	ajax.exec("/controller/Email/getEmailCampaignNumRec", [
			["to_type", this.campaignObject.getValue("to_type")],
			["to_manual", this.campaignObject.getValue("to_manual")],
			["to_view", this.campaignObject.getValue("to_view")],
			["to_conditions", (this.savedConditions) ? JSON.stringify(this.savedConditions) : ""],
	]);
    
	// Build Process Step Callback
	// --------------------------------------------------------
    var cls = this;
    this.processStep = function()
    {
        cls.saveEmailCampaign();
        return true;
    }
}

/**
 * Disables the unselected elements
 *
 * @public
 * @this {AntWizard_EmailCampaign}
 * @param {Object} radioObj     The current object selected
 */
AntWizard_EmailCampaign.prototype.unselectedElements = function(radioObj) 
{
    if(this.wizard.nStep==2)
    {
        if(radioObj.checked)
        {
            switch(radioObj.value)
            {
                case "condition":
                    this.campaignForm.viewEmail.disabled = "disabled";
                    this.campaignForm.viewSelect.disabled = "disabled";
                    this.conditionOverlay(false);
                    break;
                case "manual":
                    this.campaignForm.viewSelect.disabled = "disabled";
                    this.campaignForm.viewEmail.removeAttribute("disabled");
                    this.conditionOverlay(true);
                    break;
                case "view":
                    this.campaignForm.viewEmail.disabled = "disabled";
                    this.campaignForm.viewSelect.removeAttribute("disabled");
                    this.conditionOverlay(true);
                    break;
            }
        }
    }
    else if(this.wizard.nStep==3 && radioObj.checked)
    {
        switch(radioObj.value)
        {
            case "template":
                this.conditionOverlay(false);
                break;
            case "blank":
                this.conditionOverlay(true);
            default:
                break;
        }
    }
    else if(this.wizard.nStep==5 && radioObj.checked)
    {
        switch(radioObj.value)
        {
            case "now":
                this.campaignForm.scheduleDate.disabled = "disabled";
                this.campaignForm.scheduleTime.disabled = "disabled";
                break;
            case "schedule":
                this.campaignForm.scheduleDate.removeAttribute("disabled");
                this.campaignForm.scheduleTime.removeAttribute("disabled");
            default:
                break;
        }
    }
}

/**
 * Displays the condition overlay if the condition view type is not selected
 *
 * @public
 * @this {AntWizard_EmailCampaign}
 * @param {Boolean} setEvent    Determines whether to disable the condition link or not
 * @param {String} type         Step Type
 */
AntWizard_EmailCampaign.prototype.conditionOverlay = function(display)
{
    if(display)
    {
        alib.dom.styleSet(this.condOverlay, "display", "block");
        if(this.wizard.nStep==2)
            alib.dom.styleSet(this.condOverlay, "height", this.condOverlay.nextSibling.offsetHeight + "px");
        else if(this.wizard.nStep==3)
            alib.dom.styleSet(this.condOverlay, "height", (this.condOverlay.parentNode.lastChild.offsetHeight + 20) + "px");
    }
    else
    {
        alib.dom.styleSet(this.condOverlay, "display", "none");
    }
}

/**
 * Loads the view Type
 *
 * @public
 * @this {AntWizard_EmailCampaign}
 * @param {DOMElement} con      Container Element
 */
AntWizard_EmailCampaign.prototype.loadViewTypes = function(con)
{
    var ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(!ret)
            return;
            
        if(ret.error)
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
        
        if(ret && ret.length > 0)
        {
            for(view in ret)
            {
                var currentView = ret[view];
                var selected = false;
                var viewIdx = this.cbData.cls.campaignForm.viewSelect.length;
                
                if(this.cbData.cls.campaignObject.getValue('to_view') == currentView.id)
                    selected = true;
                
                this.cbData.cls.campaignForm.viewSelect[viewIdx] = new Option(currentView.name, currentView.id, false, selected);
            }
        }
        
        var selectContainer = this.cbData.cls.campaignForm.viewSelect.parentNode;
        alib.dom.styleSet(selectContainer, "background", "none");
        alib.dom.styleSet(this.cbData.cls.campaignForm.viewSelect, "display", "block");
    };
    ajax.exec("/controller/Object/getViews", [["objectType", this.objectType]]);
}

/**
 * Selects/Displays the html template
 *
 * @public
 * @this {AntWizard_EmailCampaign} 
 */
AntWizard_EmailCampaign.prototype.htmlTemplate = function(con)
{
    var objBrowser = new AntObjectBrowser("html_template");    
    objBrowser.selectedObjId = this.campaignObject.getValue('template_id');
    objBrowser.browserCon = con;
    objBrowser.cbData.cls = this;
    objBrowser.onSelect = function(oid, name) 
    {
        this.cbData.cls.campaignObject.setValue("template_id", oid);
    }
    objBrowser.onLoad = function()
    {
        this.cbData.cls.objBrowserOverlay(this.browserCon);
        
        if(this.cbData.cls.campaignForm.designRadio.template.checked)
            alib.dom.styleSet(this.cbData.cls.condOverlay, "display", "none");
        
    }
    objBrowser.displaySelectInline();
}

/**
 * Sets the style of overlay
 *
 * @public
 * @this {AntWizard_EmailCampaign} 
 * @param {DOMElement} parentCon     Parent container of the overlay
 */
AntWizard_EmailCampaign.prototype.objBrowserOverlay = function(con)
{
    // Correct the height
    var browserListHeight = 300;
    if(con.lastChild.lastChild)
        browserListHeight = con.lastChild.lastChild.offsetHeight;
        
    if(!browserListHeight || browserListHeight == 0)
        browserListHeight = 300;
        
    alib.dom.styleSet(con.lastChild, "height", browserListHeight + "px");
    alib.dom.styleSet(con.lastChild, "max-height", "300px");
    
    this.condOverlay = alib.dom.setElementAttr(alib.dom.createElement("div"));
    con.insertBefore(this.condOverlay, con.firstChild)        
    this.setOverlayStyle(con);
    this.conditionOverlay(true); // Set true as temporary        
    
}

/**
 * Sets the style of overlay
 *
 * @public
 * @this {AntWizard_EmailCampaign} 
 * @param {DOMElement} parentCon     Parent container of the overlay
 */
AntWizard_EmailCampaign.prototype.setOverlayStyle = function(parentCon)
{
    if(this.wizard.nStep==2)
        alib.dom.styleSet(this.condOverlay, "margin-left", "20px");
    else if(this.wizard.nStep==3)
        alib.dom.styleSet(this.condOverlay, "margin-left", "0px");
    
    alib.dom.styleSet(this.condOverlay, "position", "absolute");
    alib.dom.styleSet(this.condOverlay, "display", "none");
    alib.dom.styleSet(this.condOverlay, "opacity", "0.6");
    alib.dom.styleSet(this.condOverlay, "background-color", "#dddddd");
    alib.dom.styleSet(this.condOverlay, "width", parentCon.offsetWidth + "px");
}

/**
 * Maps the campaign data
 *
 * @public
 * @this {AntWizard_EmailCampaign}  
 */
AntWizard_EmailCampaign.prototype.mapCampaignData = function()
{
    for(data in this.campaignForm)
    {
        var currentData = this.campaignForm[data];
        
        switch(currentData.type)
        {
            case "text":
            case "textarea":
            case "hidden":
            case "select-one":
                this.campaignData[data] = currentData.value;
                break;
            case "checkbox":
                this.campaignData[data] = currentData.checked;
                break;
            case "objectRadio":
                this.campaignData[data] = new Array();
                for(radio in currentData)
                {
                    var currentRadio = currentData[radio];
                    
                    if(radio !== "type") // Do not include the type object
                    {
                        this.campaignData[data][radio] = currentRadio.checked;
                    }
                }
                break;
            default:
                break;
        }
    }
    
    // Map Conditions
    if(this.viewConditions)
    {
        this.savedConditions = new Array(); // Need to reset saved conditions
        
        for (x=0; x<this.viewConditions.getNumConditions(); x++)
        {
            var condition = this.viewConditions.getCondition(x);
            var conditionIdx = this.savedConditions.length;
            
            this.savedConditions[conditionIdx] = new Object();
            this.savedConditions[conditionIdx].blogic = condition.blogic;
            this.savedConditions[conditionIdx].fieldName = condition.fieldName;
            this.savedConditions[conditionIdx].operator = condition.operator;
            this.savedConditions[conditionIdx].condValue = condition.condValue;
        }
    }
}

/**
 * Maps the campaign data
 *
 * @public
 * @this {AntWizard_EmailCampaign}  
 */
AntWizard_EmailCampaign.prototype.setCampaignData = function()
{
    for(data in this.campaignForm)
    {
        var currentData = this.campaignForm[data];
        
        if(typeof this.campaignData[data] == "undefined")
            continue;
        
        switch(currentData.type)
        {
            case "text":
            case "textarea":
            case "hidden":
                currentData.value = this.campaignData[data];
                break;
            case "select-one":
                currentData.value = this.campaignData[data];
                break;
            case "checkbox":
                currentData.checked = this.campaignData[data];
                break;
            case "objectRadio":
                for(radio in currentData)
                {
                    var currentRadio = currentData[radio];
                    
                    if(radio !== "type") // Do not include the type object
                    {
                        currentRadio.checked = this.campaignData[data][radio];
                        
                        if(currentRadio.checked)
                        {
                            this.unselectedElements(currentRadio);
                        }
                    }
                }
                break;
            default:
                break;
        }
    }
}

/**
 * Builds the Rich Textbox input for email compose
 *
 * @public
 * @this {AntWizard_EmailCampaign}
 * @param {type} name   description
 */
AntWizard_EmailCampaign.prototype.buildRte = function(con, value)
{
    this.campaignForm.composeHtml = alib.dom.setElementAttr(alib.dom.createElement("input"), [["type", "hidden"], ["value", value]]);

    this.composeRte = alib.ui.Editor(this.campaignForm.composeHtml);
    con.innerHTML = "";
    this.composeRte.print(con, '95%', "300px", value);
    
    var cls = this;
    this.composeRte.onChange = function()
    {
		cls.campaignObject.setValue('body_html', this.getValue());
        cls.campaignForm.composePlain.value = this.getValue().replace(/<\s*br[^>]?>/,'\n').replace(/(<([^>]+)>)/g, "");
    }
}

/**
 * Saves the Email Campaign
 *
 * @public
 * @this {AntWizard_EmailCampaign}
 * @param {type} name   description
 */
AntWizard_EmailCampaign.prototype.saveEmailCampaign = function(stat)
{
	// Save the object
	this.campaignObject.setValue('status', (stat) ? stat : 3); // Default to 'pending' which will send the campaign
	if (this.savedConditions)
		this.campaignObject.setValue('to_conditions', JSON.stringify(this.savedConditions));
	if (this.composeRte)
		this.campaignObject.setValue('body_html', this.composeRte.getValue());

    if(this.campaignForm.sendRadio && this.campaignForm.sendRadio.schedule.checked) 
		this.campaignObject.setValue('ts_start', this.campaignForm.scheduleDate.value + " " + this.campaignForm.scheduleTime.value); 
	this.campaignObject.cbData.cls = this;
	this.campaignObject.onsave = function()
	{
		if (!this.id)
		{
			alib.statusShowAlert("ERROR: There was a problem communicating with the server!", 3000, "bottom", "right");
			return;
		}

		alib.statusShowAlert("Email Campaign successfully created!", 3000, "bottom", "right");
	}
	this.campaignObject.onsaveError = function(msg)
	{
		//alert(msg);
	}

	this.campaignObject.save();
}
