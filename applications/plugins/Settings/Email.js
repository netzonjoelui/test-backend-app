/**
 * System email settings plugin
 *
 * This plugin manages all system email settings
 *
 * @package Plugin_Settings
 * @category Email
 * @copyright copyright (c) 2003-2012 Aereus Corporation (http://www.aereus.com)
 */

/**
 * Class constructor
 */
function Plugin_Settings_Email()
{
    this.mainCon = null;
    this.innerCon = null;
    
    this.defaultDomain = null;
    this.themeName = null;
    this.currentAlias = null;
    
    this.emailData = new Object();    
}

/**
 * Print the pluging into the dom tree
 *
 * @param {AntView} antView The view used to display this plugin
 */
Plugin_Settings_Email.prototype.print = function(antView)
{
	this.mainCon = alib.dom.createElement('div', antView.con);
    
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "aobListHeader";
    this.titleCon.innerHTML = "System Email Settings";
    this.innerCon = alib.dom.createElement("div", this.mainCon);
    this.innerCon.className = "objectLoaderBody";
    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {           
        this.cls.emailData = ret;
        this.cls.defaultDomain = ret.defaultDomain;
        this.cls.themeName = ret.themeName;
        this.cls.buildInterface();
        
    };    
    ajax.exec("/controller/Email/getEmails");
    this.innerCon.innerHTML = "<div class='loading'></div>";
}

/**
 * Render dom elements for UI
 */
Plugin_Settings_Email.prototype.buildInterface = function()
{
    this.innerCon.innerHTML = "";
    
    var divHeader = alib.dom.createElement("div", this.innerCon);    
    
    var intro = alib.dom.createElement("p", divHeader);
	alib.dom.styleSetClass(intro, "info");
    intro.innerHTML = "The settings below can be used to manage the way Netric handles both incoming and outgoing email including SMTP settings, domains and email aliases.";
    
	this.buildInterfaceGeneral();
	this.buildInterfaceSmtp();
	this.buildInterfaceSmtpBulk();
    this.emailDomain();
    this.emailAlias();
    
    // user comment settings    
    // commentSettings(this.innerCon);
}

/**
 * Build smtp settings form
 */
Plugin_Settings_Email.prototype.buildInterfaceGeneral = function()
{
    var divMain = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divMain, "borderTop", "1px solid");
    alib.dom.styleSet(divMain, "marginTop", "5px");
    alib.dom.styleSet(divMain, "paddingTop", "5px");
    
	// Left contianer
	// -------------------------------------------------
    var divLeft = alib.dom.createElement("div", divMain);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "180px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "General Settings";

    
	// Right contianer
	// -------------------------------------------------
    var divRight = alib.dom.createElement("div", divMain);
    alib.dom.styleSet(divRight, "margin-left", "190px");
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);

	var genForm = new Object();
    genForm.compose_netric = createInputAttribute(alib.dom.createElement("input"), "checkbox", "compose_netric", "Use netric to compose email");
    genForm.compose_netric.inputLabel = " (otherwise use default mail client)";

    genForm.log_allmail = createInputAttribute(alib.dom.createElement("input"), "checkbox", "track_allmail", "Log all incoming and ougoing email in activty log");
    genForm.log_allmail.inputLabel = "";

	/*
    genForm.user = createInputAttribute(alib.dom.createElement("input"), "text", "user", "User", "200px", "");
    genForm.user.inputLabel = " (optional) Use only if your SMTP server requires authentication.";
	*/

    buildFormInput(genForm, tBody);
    
	// Add button for saving changes
    var divBtnDomain = alib.dom.createElement("div", divRight);
    alib.dom.styleSet(divBtnDomain, "marginTop", "10px");
    
	// Save changes button
	// -------------------------------------------------
	var button = alib.ui.Button("Save Changes", {
		className:"b1", tooltip:"Save General Settings", frmData:genForm, 
		onclick:function() { 
			var ajax = new CAjax("json");
			ajax.onload = function() { alib.statusShowAlert("Changes Saved!", 3000, "bottom", "right"); }
			var args = [
				["set[]", "email/compose_netric"],
                ["email/compose_netric", (this.frmData.compose_netric.checked)?'1':'0'], 
				["set[]", "email/log_allmail"],
                ["email/log_allmail", (this.frmData.log_allmail.checked)?'1':'0'], 
				/*
				["set[]", "email/smtp_user"],
                ["email/smtp_user", this.frmData.user.value], 
				*/
			];
    		ajax.exec("/controller/Admin/setSetting", args);
		}
	});
	button.print(divBtnDomain);

	// Load previous settings
    var ajax = new CAjax('json');
	ajax.cbData.frmData = genForm;
    ajax.onload = function(ret)
    {
		if (ret && !ret['error'])
		{
			this.cbData.frmData.compose_netric.checked = (ret["email/compose_netric"] == 1) ? true : false;
			this.cbData.frmData.log_allmail.checked = (ret["email/log_allmail"] == 1) ? true : false;
		}
    };
    ajax.exec("/controller/Admin/getSetting",
                [["get[]", "email/compose_netric"], ["get[]", "email/log_allmail"]]);
}

/**
 * Build smtp settings form
 */
Plugin_Settings_Email.prototype.buildInterfaceSmtp = function()
{
    var divMain = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divMain, "borderTop", "1px solid");
    alib.dom.styleSet(divMain, "marginTop", "5px");
    alib.dom.styleSet(divMain, "paddingTop", "5px");
    
	// Left contianer
	// -------------------------------------------------
    var divLeft = alib.dom.createElement("div", divMain);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "180px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Outgoing SMTP";

    var lblDesc = alib.dom.createElement("p", divLeft);
	lblDesc.innerHTML = "You can use these settings to direct all outgoing email through an alternate smtp server. If host is blank then Netric servers will be used.";

    
	// Right contianer
	// -------------------------------------------------
    var divRight = alib.dom.createElement("div", divMain);
    alib.dom.styleSet(divRight, "margin-left", "190px");
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);

	var smtpForm = new Object();
    smtpForm.host = createInputAttribute(alib.dom.createElement("input"), "text", "host", "Host Name", "200px", "");
    smtpForm.host.inputLabel = " Enter the host name or ip address of your outgoing mail server.";

    smtpForm.user = createInputAttribute(alib.dom.createElement("input"), "text", "user", "User", "200px", "");
    smtpForm.user.inputLabel = " (optional) Use only if your SMTP server requires authentication.";

    smtpForm.password = createInputAttribute(alib.dom.createElement("input"), "password", "password", "Password", "200px", "");
    smtpForm.password.inputLabel = " (optional) Use only if your SMTP server requires authentication.";

    smtpForm.port = createInputAttribute(alib.dom.createElement("input"), "text", "port", "Port", "40px", "");
    smtpForm.port.inputLabel = " (optional) If blank then default port 25 will be used.";
    
    buildFormInput(smtpForm, tBody);
    
	// Add button for saving changes
    var divBtnDomain = alib.dom.createElement("div", divRight);
    alib.dom.styleSet(divBtnDomain, "marginTop", "10px");
    
	// Save changes button
	// -------------------------------------------------
	var button = alib.ui.Button("Save Changes", {
		className:"b1", tooltip:"Save SMTP settings", frmData:smtpForm, 
		onclick:function() { 
			if (this.frmData.host.value=="")
				this.frmData.user.value = this.frmData.password.value = this.frmData.port.value = "";

			var ajax = new CAjax("json");
			ajax.onload = function() { alib.statusShowAlert("Changes Saved!", 3000, "bottom", "right"); }
			var args = [
				["set[]", "email/smtp_host"],
                ["email/smtp_host", this.frmData.host.value], 
				["set[]", "email/smtp_user"],
                ["email/smtp_user", this.frmData.user.value], 
				["set[]", "email/smtp_password"],
                ["email/smtp_password", this.frmData.password.value], 
				["set[]", "email/smtp_port"],
                ["email/smtp_port", this.frmData.port.value]
			];
    		ajax.exec("/controller/Admin/setSetting", args);
		}
	});
	button.print(divBtnDomain);

	// Load previous settings
    var ajax = new CAjax('json');
	ajax.cbData.frmData = smtpForm;
    ajax.onload = function(ret)
    {
		if (ret && !ret['error'])
		{
			this.cbData.frmData.host.value = ret["email/smtp_host"];
			this.cbData.frmData.user.value = ret["email/smtp_user"];
			this.cbData.frmData.password.value = ret["email/smtp_password"];
			this.cbData.frmData.port.value = ret["email/smtp_port"];
		}
    };
    ajax.exec("/controller/Admin/getSetting",
                [["get[]", "email/smtp_host"], ["get[]", "email/smtp_user"], ["get[]", "email/smtp_password"], ["get[]", "email/smtp_port"]]);
}

/**
 * Build smtp settings form
 */
Plugin_Settings_Email.prototype.buildInterfaceSmtpBulk = function()
{
    var divMain = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divMain, "borderTop", "1px solid");
    alib.dom.styleSet(divMain, "marginTop", "10px");
    alib.dom.styleSet(divMain, "paddingTop", "10px");
    
	// Left contianer
	// -------------------------------------------------
    var divLeft = alib.dom.createElement("div", divMain);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "180px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Outgoing Bulk SMTP";

    var lblDesc = alib.dom.createElement("p", divLeft);
	lblDesc.innerHTML = "You can use these settings to direct all bulk email through an alternate smtp server. These settings only apply to mass-emails. If host is blank then Netric servers will be used.";

    
	// Right contianer
	// -------------------------------------------------
    var divRight = alib.dom.createElement("div", divMain);
    alib.dom.styleSet(divRight, "margin-left", "190px");
    var tableForm = alib.dom.createElement("table", divRight);
    var tBody = alib.dom.createElement("tbody", tableForm);

	var smtpForm = new Object();
    smtpForm.host = createInputAttribute(alib.dom.createElement("input"), "text", "host", "Host Name", "200px", "");
    smtpForm.host.inputLabel = " Enter the host name or ip address of your outgoing mail server.";

    smtpForm.user = createInputAttribute(alib.dom.createElement("input"), "text", "user", "User", "200px", "");
    smtpForm.user.inputLabel = " (optional) Use only if your SMTP server requires authentication.";

    smtpForm.password = createInputAttribute(alib.dom.createElement("input"), "password", "password", "Password", "200px", "");
    smtpForm.password.inputLabel = " (optional) Use only if your SMTP server requires authentication.";

    smtpForm.port = createInputAttribute(alib.dom.createElement("input"), "text", "port", "Port", "40px", "");
    smtpForm.port.inputLabel = " (optional) If blank then default port 25 will be used.";
    
    buildFormInput(smtpForm, tBody);
    
	// Add button for saving changes
    var divBtnDomain = alib.dom.createElement("div", divRight);
    alib.dom.styleSet(divBtnDomain, "marginTop", "10px");
    
	// Save changes button
	// -------------------------------------------------
	var button = alib.ui.Button("Save Changes", {
		className:"b1", tooltip:"Save SMTP settings", frmData:smtpForm, 
		onclick:function() { 
			if (this.frmData.host.value=="")
				this.frmData.user.value = this.frmData.password.value = this.frmData.port.value = "";
				
			var ajax = new CAjax("json");
			ajax.onload = function() { alib.statusShowAlert("Changes Saved!", 3000, "bottom", "right"); }
			var args = [
				["set[]", "email/smtp_bulk_host"],
                ["email/smtp_bulk_host", this.frmData.host.value], 
				["set[]", "email/smtp_bulk_user"],
                ["email/smtp_bulk_user", this.frmData.user.value], 
				["set[]", "email/smtp_bulk_password"],
                ["email/smtp_bulk_password", this.frmData.password.value], 
				["set[]", "email/smtp_bulk_port"],
                ["email/smtp_bulk_port", this.frmData.port.value]
			];
    		ajax.exec("/controller/Admin/setSetting", args);
		}
	});
	button.print(divBtnDomain);

	// Load previous settings
    var ajax = new CAjax('json');
	ajax.cbData.frmData = smtpForm;
    ajax.onload = function(ret)
    {
		if (ret && !ret['error'])
		{
			this.cbData.frmData.host.value = ret["email/smtp_bulk_host"];
			this.cbData.frmData.user.value = ret["email/smtp_bulk_user"];
			this.cbData.frmData.password.value = ret["email/smtp_bulk_password"];
			this.cbData.frmData.port.value = ret["email/smtp_bulk_port"];
		}
    };
    ajax.exec("/controller/Admin/getSetting",
                [["get[]", "email/smtp_bulk_host"], ["get[]", "email/smtp_bulk_user"], 
				 ["get[]", "email/smtp_bulk_password"], ["get[]", "email/smtp_bulk_port"]]);
	
}

/*************************************************************************
*    Function:    emailDomain
*
*    Purpose:    Creates the email domain
**************************************************************************/
Plugin_Settings_Email.prototype.emailDomain = function()
{
    var divDomain = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divDomain, "borderTop", "1px solid");
    alib.dom.styleSet(divDomain, "marginTop", "5px");
    alib.dom.styleSet(divDomain, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divDomain);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Domains";
    
    var divRight = alib.dom.createElement("div", divDomain);
    alib.dom.styleSet(divRight, "float", "left");
    
    var divtblDomain = alib.dom.createElement("div", divDomain);
    
    // print CToolTable
    this.tblDomain = new CToolTable("650px");    
    this.tblDomain.addHeader("Domains", "left", "500px");
    this.tblDomain.addHeader("Default", "center", "50px");
    this.tblDomain.addHeader("Delete", "center", "50px");
    
    this.tblDomain.print(divtblDomain);
    this.buildDomainRow();
    
    divClear(divDomain);
    
    var divBtnDomain = alib.dom.createElement("div", divDomain);
    alib.dom.styleSet(divBtnDomain, "marginLeft", "190px");
    alib.dom.styleSet(divBtnDomain, "marginTop", "10px");
    
    var btnAddDomain = createInputAttribute(alib.dom.createElement("input", divBtnDomain), "button", null, null, "100px", "Add Domain");        
    btnAddDomain.cls = this;
    btnAddDomain.onclick = function()
    {
        this.cls.domainModal();
    }
}

/*************************************************************************
*    Function:    buildDomainRow
* 
*    Purpose:    Build Domain Row
**************************************************************************/
Plugin_Settings_Email.prototype.buildDomainRow = function()
{
    if(!this.emailData.domains)
        return;
        
    // clear the current account table rows    
    this.tblDomain.clear();
    
    for(domain in this.emailData.domains)
    {
        var currentDomain = this.emailData.domains[domain];
        var rw = this.tblDomain.addRow();
                
        rw.addCell(currentDomain);
        
        // Default
        var radioDefault = createInputAttribute(alib.dom.createElement("input"), "radio");
        rw.addCell(radioDefault, null, "center");
        
        radioDefault.setAttribute("name", "domainDefault");
        radioDefault.cls = this;
        radioDefault.domain = currentDomain;
        radioDefault.onclick = function()
        {
            this.cls.domainSave(this.domain, "domainSetDefault");
        }
        
        // Delete
        var deleteLink = alib.dom.createElement("a");
        if(currentDomain == this.defaultDomain)
        {
            deleteLink.innerHTML = "&nbsp;";
            radioDefault.setAttribute("checked", "checked");
        }
        else
        {
            deleteLink.innerHTML = "<img src='/images/themes/" + this.themeName + "/icons/deleteTask.gif' border='0' />";
            deleteLink.href = "javascript: void(0);";        
            deleteLink.cls = this;
            deleteLink.domain = currentDomain;
            deleteLink.onclick = function()
            {
                if(confirm("Are you sure to delete " + this.domain + "?"))
                    this.cls.domainDelete(this.domain);
            }
        }
        
        rw.addCell(deleteLink, null, "center");
    }
}

/*************************************************************************
*    Function:    emailAlias
*
*    Purpose:    Creates the email alias
**************************************************************************/
Plugin_Settings_Email.prototype.emailAlias = function()
{
    var divAlias = alib.dom.createElement("div", this.innerCon);
    alib.dom.styleSet(divAlias, "borderTop", "1px solid");
    alib.dom.styleSet(divAlias, "marginTop", "5px");
    alib.dom.styleSet(divAlias, "paddingTop", "5px");
    
    var divLeft = alib.dom.createElement("div", divAlias);
    alib.dom.styleSet(divLeft, "float", "left");
    alib.dom.styleSet(divLeft, "width", "190px");
    
    var lblTitle = alib.dom.createElement("p", divLeft);
    alib.dom.styleSet(lblTitle, "fontWeight", "bold");
    alib.dom.styleSet(lblTitle, "fontSize", "12px");
    lblTitle.innerHTML = "Alias";
    
    var divRight = alib.dom.createElement("div", divAlias);
    alib.dom.styleSet(divRight, "float", "left");
    
    var divtblAlias = alib.dom.createElement("div", divAlias);
    
    // print CToolTable
    this.tblAlias = new CToolTable("650px");    
    this.tblAlias.addHeader("Address", "left", "550px");    
    this.tblAlias.addHeader("Delete", "center", "50px");
    
    this.tblAlias.print(divtblAlias);
    this.buildAliasRow(null);
    
    divClear(divAlias);
    
    var divBtnAlias = alib.dom.createElement("div", divAlias);
    alib.dom.styleSet(divBtnAlias, "marginLeft", "190px");
    alib.dom.styleSet(divBtnAlias, "marginTop", "10px");
    
    var btnAddAlias = createInputAttribute(alib.dom.createElement("input", divBtnAlias), "button", null, null, "100px", "Add Alias");    
    btnAddAlias.cls = this;
    btnAddAlias.onclick = function()
    {
        this.cls.aliasModal(1, "New Alias", null);
    }
}

/*************************************************************************
*    Function:    buildAliasRow
* 
*    Purpose:    Build Alias Row
**************************************************************************/
Plugin_Settings_Email.prototype.buildAliasRow = function(domainName)
{
    if(!this.emailData.alias)
        return;
        
    // clear the current account table rows    
    this.tblAlias.clear();
    
    for(alias in this.emailData.alias)
    {
        var currentAlias = this.emailData.alias[alias];        
        if(domainName == currentAlias.domainName)
        {
            delete this.emailData.alias[alias];
            continue;
        }
        
        var rw = this.tblAlias.addRow();
        
        var aliasLink = alib.dom.createElement("a");
        aliasLink.innerHTML = currentAlias.address;
        aliasLink.href = "javascript: void(0);";
        aliasLink.cls = this;
        aliasLink.currentAlias = currentAlias;
        aliasLink.onclick = function()
        {
            this.cls.currentAlias = this.currentAlias.address;
            this.cls.aliasModal(null, this.currentAlias.aliasName, this.currentAlias.gotoAddress, this.currentAlias.domainName);
        }
        
        rw.addCell(aliasLink);
        
        // Delete
        var deleteLink = alib.dom.createElement("a");
        if(currentAlias == this.defaultAlias)
        {
            deleteLink.innerHTML = "&nbsp;";            
        }
        else
        {
            deleteLink.innerHTML = "<img src='/images/themes/" + this.themeName + "/icons/deleteTask.gif' border='0' />";
            deleteLink.href = "javascript: void(0);";        
            deleteLink.cls = this;
            deleteLink.address = currentAlias.address;            
            deleteLink.onclick = function()
            {
                if(confirm("Are you sure to delete " + this.address + "?"))
                    this.cls.aliasDelete(this.address);
            }
        }
        
        rw.addCell(deleteLink, null, "center");
    }
}

/*************************************************************************
*    Function:    domainModal
*
*    Purpose:    Create a new domain
**************************************************************************/
Plugin_Settings_Email.prototype.domainModal = function()
{    
    var dlg = new CDialog("New Domain");
    dlg.f_close = true;
        
    var divModal = alib.dom.createElement("div");
    var tableForm = alib.dom.createElement("table", divModal);
    var tBody = alib.dom.createElement("tbody", tableForm);
    
    var domainInput = new Object();
    domainInput.name = createInputAttribute(alib.dom.createElement("input"), "text", "domainName", "Domain Name", "300px", "mydomain.com");
    buildFormInput(domainInput, tBody);
    
    // Done button
    var divButton = alib.dom.createElement("div", divModal);
    alib.dom.styleSet(divButton, "text-align", "right");
    var btn = new CButton("Save and Close", 
                        function(dlg, cls, domainInput)
                        {
                            cls.domainSave(domainInput.value, "domainAdd");
                            dlg.hide();
                        }, 
                        [dlg, this, domainInput.name], "b1");
    btn.print(divButton);
    
    var btn = new CButton("Cancel", 
                        function(dlg) 
                        {  
                            dlg.hide(); 
                        }, 
                        [dlg]);
    btn.print(divButton);

    dlg.customDialog(divModal, 450);
}

/*************************************************************************
*    Function:    domainSave
* 
*    Purpose:    saves the domain (sets default / saves new domain)
**************************************************************************/
Plugin_Settings_Email.prototype.domainSave = function(domain, functionName)
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.domain = domain;
    ajax.functionName = functionName;
    ajax.dlg = showDialog("Saving Domain, please wait...");
    ajax.onload = function(ret)
    {
        if(this.functionName=="domainSetDefault")
        {
            ALib.statusShowAlert(this.domain + " is set to default!", 3000, "bottom", "right");
            this.cls.defaultDomain = this.domain;
        }
        else
        {
            ALib.statusShowAlert(this.domain + " has been added!", 3000, "bottom", "right");
            this.cls.emailData.domains[this.domain] = new Object();
            this.cls.emailData.domains[this.domain] = this.domain;
        }
        
        this.dlg.hide();
        this.cls.buildDomainRow();        
    };
    ajax.exec("/controller/Admin/" + functionName,
                [["name", domain]]);
}

/*************************************************************************
*    Function:    domainDelete
*
*    Purpose:    delete a domain
**************************************************************************/
Plugin_Settings_Email.prototype.domainDelete = function(domain)
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.domain = domain;
    ajax.dlg = showDialog("Deleting domain, please wait...");
    ajax.onload = function(ret)
    {
        delete this.cls.emailData.domains[this.domain];
        this.cls.buildDomainRow();
        this.cls.buildAliasRow(this.domain);
        this.dlg.hide();
        ALib.statusShowAlert(this.domain + " has been removed!", 3000, "bottom", "right");        
    };
    ajax.exec("/controller/Admin/domainDelete",
                [["did", domain]]);
}

/*************************************************************************
*    Function:    aliasModal
*
*    Purpose:    Create a new alias
**************************************************************************/
Plugin_Settings_Email.prototype.aliasModal = function(insertMode, aliasName, gotoAddress, domainName)
{
    var labelModal = "Edit Alias";
    if(insertMode)    
        labelModal = "New Alias";
    
    var dlg = new CDialog(labelModal);
    dlg.f_close = true;
        
    var divModal = alib.dom.createElement("div");
    
    var aliasInput = new Object();
    
    aliasInput.aliasName = createInputAttribute(alib.dom.createElement("input"), "text", "aliasName", "Alias Name", "200px", aliasName);
    aliasInput.aliasName.label = " &nbsp;@ ";
    aliasInput.domainName = createInputAttribute(alib.dom.createElement("select"), null, "domainName");    
    buildFormInputDiv(aliasInput, divModal);
    divClear(divModal);
    
    var forwardToLabel = alib.dom.createElement("div", divModal);
    forwardToLabel.innerHTML = "Forward To:";
    alib.dom.styleSet(forwardToLabel, "margin", "10px 0 0 0");
    
    aliasInput.gotoAddress = createInputAttribute(alib.dom.createElement("textarea", divModal), null, "gotoAddress");    
    aliasInput.gotoAddress.innerHTML = gotoAddress;
    alib.dom.styleSet(aliasInput.gotoAddress, "width", "430px");
    alib.dom.styleSet(aliasInput.gotoAddress, "height", "200px");
    
        
    // Done button
    var divButton = alib.dom.createElement("div", divModal);
    alib.dom.styleSet(divButton, "text-align", "right");
    var btn = new CButton("Save and Close", 
                        function(dlg, cls, aliasInput, insertMode)
                        {                            
                            cls.aliasave(aliasInput.aliasName.value, aliasInput.domainName.value, aliasInput.gotoAddress.value, insertMode);
                            dlg.hide();
                        }, 
                        [dlg, this, aliasInput, insertMode, aliasName], "b1");
    btn.print(divButton);
    
    var btn = new CButton("Cancel", 
                        function(dlg) 
                        {  
                            dlg.hide(); 
                        }, 
                        [dlg]);
    btn.print(divButton);

    dlg.customDialog(divModal, 450);
    
    for(domain in this.emailData.domains)
    {
        var selected = false;
        var currentDomain = this.emailData.domains[domain];        
        
        if(domainName == currentDomain)
            selected = true;
            
        aliasInput.domainName[aliasInput.domainName.length] = new Option(currentDomain, currentDomain, false, selected);
    }    
    alib.dom.styleSet(aliasInput.domainName, "visibility", "visible");
}

/*************************************************************************
*    Function:    aliasave
* 
*    Purpose:    saves the alias (sets default / saves new alias)
**************************************************************************/
Plugin_Settings_Email.prototype.aliasave = function(aliasName, domainName, gotoAddress, insertMode)
{
    var address = aliasName + '@' + domainName;
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.insertMode = insertMode;
    ajax.domainName = domainName;
    ajax.dlg = showDialog("Saving alias, please wait...");
    ajax.onload = function(ret)
    {
        this.dlg.hide();
        if(!ret['error'])
        {            
            if(!this.insertMode)
                delete this.cls.emailData.alias[this.cls.currentAlias];

            this.cls.emailData.alias[ret.address] = new Object();
            this.cls.emailData.alias[ret.address].address = ret.address;
            this.cls.emailData.alias[ret.address].gotoAddress = ret.gotoAddress;
            this.cls.emailData.alias[ret.address].aliasName = ret.aliasName;
            this.cls.emailData.alias[ret.address].domainName = ret.domainName;
            this.cls.buildAliasRow(null);
            
            if(this.insertMode)
                ALib.statusShowAlert(ret.address + ' has been added!', 5000, "bottom", "right");            
            else            
                ALib.statusShowAlert(ret.address + ' has been updated!', 5000, "bottom", "right");
        }
        else
            ALib.statusShowAlert(ret['error'], 5000, "bottom", "right");
        
    };
    ajax.exec("/controller/Admin/aliasAdd",
                [["address", address], ["gotoAddress", gotoAddress], ["insertMode", insertMode], ["currentAlias", this.currentAlias]]);
}

/*************************************************************************
*    Function:    aliasDelete
*
*    Purpose:    delete a alias
**************************************************************************/
Plugin_Settings_Email.prototype.aliasDelete = function(address)
{
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.address = address;
    ajax.dlg = showDialog("Deleting alias, please wait...");
    ajax.onload = function(ret)
    {        
        delete this.cls.emailData.alias[this.address];
        this.cls.buildAliasRow();
        this.dlg.hide();
        ALib.statusShowAlert(this.address + " has been removed!", 3000, "bottom", "right");
    };
    ajax.exec("/controller/Admin/aliasDelete",
                [["address", address]]);
}
