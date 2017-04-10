function Plugin_Messages_Settings()
{
    this.gsFormData = new Object();     // general setting form data
    this.asFormData = new Object();     // account setting form data
    this.fsFormData = new Object();     // filter setting form data
    this.ssFormData = new Object();     // signature setting form data
    this.tsFormData = new Object();     // Video Email Theme setting form data
    this.msFormData = new Object();     // Spam setting form data
    
    this.gsSaveData = new Object();             // general setting save data
    this.accounts = new Object();             // account setting form data
    this.fsSaveData = new Object();             // filter setting form data
    this.ssSaveData = new Object();             // signature setting form data
    this.tsSaveData = new Object();             // Video Email Theme setting form data
    this.msSaveData = new Object();             // Spam setting form data
    
    this.mainCon = null;
    this.titleCon = null;
    this.innerCon = null;
	
	this.accountsCon = null;
    
    this.accountTbl = null;
    this.filterTbl = null;
    this.signatureTbl = null;
    this.themeTbl = null;    
}

Plugin_Messages_Settings.prototype.print = function(antView)
{
    this.mainCon = antView.con;
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    this.titleCon.className = "objectLoaderHeader";
    this.titleCon.innerHTML = "Email Settings";
    this.innerCon = alib.dom.createElement("div", this.mainCon);
    this.innerCon.className = "objectLoaderBody";
    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {
        if(ret.gsSaveData.retVal==1)
        {
            this.cls.gsSaveData = ret.gsSaveData;    // General Settings Saved Data
            this.cls.accounts = ret.accounts;   	 // Accounts
            this.cls.fsSaveData = ret.fsSaveData;    // Filter Settings Saved Data
            this.cls.ssSaveData = ret.ssSaveData;    // Signature Settings Saved Data
            this.cls.tsSaveData = ret.tsSaveData;    // Theme Settings Saved Data
            this.cls.msSaveData = ret.msSaveData;    // Spam Settings Saved Data
                    
            this.cls.buildInterface();
        }
    };    
    ajax.exec("/controller/Email/getEmailSettings");
}

Plugin_Messages_Settings.prototype.buildInterface = function()
{
    
    
    // Filter Setting - Table
    this.filterTbl = new CToolTable("100%");    
    this.filterTbl.addHeader("Name", "left", "150px");
    this.filterTbl.addHeader("Subject Cond", "left", "160px");
    this.filterTbl.addHeader("To Cond", "left", "150px");
    this.filterTbl.addHeader("From Cond", "left", "150px");
    this.filterTbl.addHeader("Body COnd", "left", "150px");
    this.filterTbl.addHeader("Delete", "center", "50px");
    
    // Signature Setting - Table
    /*this.signatureTbl = new CToolTable("100%");    
    this.signatureTbl.addHeader("Name", "left", "350px");
    this.signatureTbl.addHeader("Default", "center", "50px");    
    this.signatureTbl.addHeader("Edit", "center", "50px");    
    this.signatureTbl.addHeader("Delete", "center", "50px");*/
    
    // Video Email Theme Setting - Table
    this.themeTbl = new CToolTable("100%");    
    this.themeTbl.addHeader("Name", "left", "350px");
    this.themeTbl.addHeader("Scope", "center", "70px");
    this.themeTbl.addHeader("Edit", "center", "50px");    
    this.themeTbl.addHeader("Delete", "center", "50px");
    
    var tabs = new CTabs();
    tabs.print(this.innerCon);
    
    this.buildGeneral(tabs.addTab("General"));
	this.accountsCon = tabs.addTab("Accounts");
    this.buildAccountTab();
    this.buildSpam(tabs.addTab("Spam"));
    //this.buildSignature(tabs.addTab("Signature"));
    this.buildFilters(tabs.addTab("Filters"));
    this.buildVideoEmailTheme(tabs.addTab("Video Email Themes"));
}

/*************************************************************************
*    Function:    buildGeneral
* 
*    Purpose:    Build General tab for mail settings
**************************************************************************/
Plugin_Messages_Settings.prototype.buildGeneral = function(con)
{
    // Add main toolbar
    var toolbar = alib.dom.createElement("div", con);
    var tb = new CToolbar();

    // save changes button
    var btn = new CButton("Save Changes", 
    function(cls)
    {
        var args = new Array();
        for(var general in cls.gsFormData)
            {
            var value = "";
            switch(cls.gsFormData[general].type)
            {   
                case "radio":
                    value = cls.gsFormData[general].checked;
                    break;
                case "checkbox":
                    value = (cls.gsFormData[general].checked) ? 1:0;
                    break;
                case "select-one":
                case "text":
                default:
                    value = cls.gsFormData[general].value;                     
                    break;
            }
            args[args.length] = [cls.gsFormData[general].id, value];            
        }
                
        ajax = new CAjax('json');
        ajax.dlg = showDialog("Saving general settings, please wait...");
        ajax.onload = function()
        {
            this.dlg.hide();
            ALib.statusShowAlert("General Settings Saved!", 3000, "bottom", "right");
        };
        ajax.exec("/controller/Email/saveGeneralSettings", args);

    }, 
    [this], "b2");
    tb.AddItem(btn.getButton(), "left");

    // Cancel button\
    var btn = new CButton("Cancel", 
    function(cls)
    {
        cls.inputFormClear(cls.gsFormData);
    }, 
    [this], "b1");
    tb.AddItem(btn.getButton(), "left");

    // add button to the toolbar
    tb.print(toolbar);

    // Composing Options - Left Header
    this.divLeftHeader(con, "Composing Options", "175px");
    
    // Composing Options - Input Form
    var gsComposingCon = alib.dom.createElement("div", con);
    alib.dom.styleSet(gsComposingCon, "float", "left");
    alib.dom.styleSet(gsComposingCon, "width", "665px");
    
    // general setting table - Composing Options    
    var gsComposingTbody = buildTable(gsComposingCon);

    // Check Spelling - Checkbox    
    this.gsFormData.chkMessageSpelling = createInputAttribute(alib.dom.createElement("input"), "checkbox", "checkSpelling");    
    this.gsFormData.chkMessageSpelling.label = "Check each message for spelling errors before sending.";
    
    // check if setting value is set
    if(this.gsSaveData.checkSpelling==1)
        this.gsFormData.chkMessageSpelling.setAttribute("checked", "checked");
    
    // Automatic add contacts - Checkbox    
    this.gsFormData.chkAutoAddContact = createInputAttribute(alib.dom.createElement("input"), "checkbox", "addRecipients");    
    this.gsFormData.chkAutoAddContact.label = "Automatically add new recipients to contacts.";
    
    // check if setting value is set
    if(this.gsSaveData.addRecipients==1)
        this.gsFormData.chkAutoAddContact.setAttribute("checked", "checked");
    
    // build checkbox input - Composing Options
    buildFormInput(this.gsFormData, gsComposingTbody);
    
    // build general dropdown - Composing Options
    this.buildGeneralDropdown(gsComposingTbody, "Compose", this.gsSaveData);
    
    // clears the floating divs
    divClear(con);
    var hr = alib.dom.createElement("hr", con);
        
    // Reader Options - Left Header
    this.divLeftHeader(con, "Reader Options", "175px");
    
    // Reader Options - Input Form
    var gsReaderCon = alib.dom.createElement("div", con);
    alib.dom.styleSet(gsReaderCon, "float", "left");
    alib.dom.styleSet(gsReaderCon, "width", "665px");
    
    // general setting table - Reader Options    
    var gsReaderTbody = buildTable(gsReaderCon);

    // build general dropdown - Reader Options
    this.buildGeneralDropdown(gsReaderTbody, "Read", this.gsSaveData);
    
    // clears the floating divs
    divClear(con);    
    var hr = alib.dom.createElement("hr", con);
    
    this.divLeftHeader(con, "Forwarding Options", "175px");
    
    // Forwarding - Input Form
    var gsForwardingCon = alib.dom.createElement("div", con);
    alib.dom.styleSet(gsForwardingCon, "float", "left");
    alib.dom.styleSet(gsForwardingCon, "width", "665px");
        
    // general setting table - Forwarding    
    var gsForwardingTbody = buildTable(gsForwardingCon);

    // Do not forward Option - Radio
    var tr = buildTdLabel(gsForwardingTbody, "Do Not Forward", 90);
    
    this.gsFormData.rdDontForward = createInputAttribute(alib.dom.createElement("input"), "radio", "dontForward", null, null, "off");
    this.gsFormData.rdDontForward.setAttribute("name", "forwarding");    
    
    // check if setting value is set
    if(this.gsSaveData.forwarding=="on")
        this.gsFormData.rdDontForward.removeAttribute("checked");
    else
        this.gsFormData.rdDontForward.setAttribute("checked", "checked");
        
    this.gsFormData.rdDontForward.cls = this;
    this.gsFormData.rdDontForward.onclick = function()
    {        
        this.cls.gsFormData.txtForwardTo.setAttribute("disabled", "disabled");
        this.cls.gsFormData.cbForwardType.setAttribute("disabled", "disabled");
    }

    var td = alib.dom.createElement("td", tr);
    alib.dom.styleSetClass(td, "formValue");
    td.appendChild(this.gsFormData.rdDontForward);

    // Do not forward Option - Radio
    var tr = buildTdLabel(gsForwardingTbody, "Forward Messages");
    
    this.gsFormData.rdForward = createInputAttribute(alib.dom.createElement("input"), "radio", "forwardMessage", null, null, "on");
    this.gsFormData.rdForward.setAttribute("name", "forwarding");    
    this.gsFormData.rdForward.cls = this;
    
    // check if setting value is set
    if(this.gsSaveData.forwarding=="on")
        this.gsFormData.rdForward.setAttribute("checked", "checked");
        
    this.gsFormData.rdForward.cls = this;
    this.gsFormData.rdForward.onclick = function()
    {        
        this.cls.gsFormData.txtForwardTo.removeAttribute("disabled");
        this.cls.gsFormData.cbForwardType.removeAttribute("disabled");
    }

    var td = alib.dom.createElement("td", tr);
    alib.dom.styleSetClass(td, "formValue");
    td.appendChild(this.gsFormData.rdForward);

    // Foward Message To - Textbox
    this.gsFormData.txtForwardTo = createInputAttribute(alib.dom.createElement("input"), "text", "forwardingTo");
    
    // check if setting value is set
    if(this.gsSaveData.forwarding=="on" && this.gsSaveData.forwardingTo)
        this.gsFormData.txtForwardTo.setAttribute("value", this.gsSaveData.forwardingTo);
    else
        this.gsFormData.txtForwardTo.setAttribute("disabled", "disabled");
        
    // append text input for forward to
    td.appendChild(this.gsFormData.txtForwardTo);

    var label = alib.dom.createElement("label");
    label.innerHTML = " and ";
    td.appendChild(label);

    // Forwarding Action - Select Dropdown
    this.gsFormData.cbForwardType = createInputAttribute(alib.dom.createElement("select"), null, "forwardingAction");    
    
    // check if setting value is set
    var keepInboxSelected = false;
    var deleteSelected = false
    if(this.gsSaveData.forwarding=="on")
    {   
        if(this.gsSaveData.forwardingAction=="delete")
            deleteSelected = true;
        else
            keepInboxSelected = true;
    }
    else
        this.gsFormData.cbForwardType.setAttribute("disabled", "disabled");
        
    
    this.gsFormData.cbForwardType[this.gsFormData.cbForwardType.length] = new Option("Keep a copy in ANT", "keep_inbox", false, keepInboxSelected);
    this.gsFormData.cbForwardType[this.gsFormData.cbForwardType.length] = new Option("Delete in ANT", "delete", false, deleteSelected);    
    
    // append text input for forward Action
    td.appendChild(this.gsFormData.cbForwardType);
    
	// clears the floating divs
    divClear(con);    

    var hr = alib.dom.createElement("hr", con);
    
    this.divLeftHeader(con, "IMAP/POP3", "175px");
    
    // Forwarding - Input Form
    var gsImapPopCon = alib.dom.createElement("div", con);
    alib.dom.styleSet(gsImapPopCon, "float", "left");
    alib.dom.styleSet(gsImapPopCon, "width", "665px");

	gsImapPopCon.innerHTML = "<p class='info'>You can send and receive email using any email client that supports either POP3 or IMAP "
						   + "by using the settings below.</p>"
						   + "<h4>Incoming Mail Server</h4>"
						   + "POP3: pop.netricos.com<br />"
						   + "IMAP: imap.netricos.com"
						   + "<h4>Outgoing Mail Server</h4>"
						   + "smtp.netricos.com (requires authentication using the user/pass below)<br />"
						   + "<h4>User name used for both incoming and outgoing (use the same password you use to log into netric)</h4>";
	var defEmailAddr = alib.dom.createElement("div", gsImapPopCon);
	var ajax = new CAjax("json");
	ajax.cbData.lblCon = defEmailAddr;
	ajax.onload = function(ret) { this.cbData.lblCon.innerHTML = ret; }
    ajax.exec("/controller/Email/getDefaultEmailAddress");


    // clears the floating divs
    divClear(con);        
}

/*************************************************************************
*    Function:    buildGeneralDropdown
* 
*    Purpose:    Build common dropdown elements for general tab
**************************************************************************/
Plugin_Messages_Settings.prototype.buildGeneralDropdown = function(gsTbody, type)
{
    // Default Font - Select Dropdown
    var fontOption = new Array("System Default", "Arial, Helvetica, sans-serif", "Courier New, Courier, mono", "Times New Roman, Times, serif", "Verdana, Arial, Helvetica, sans-serif");
    var fontValue = new Array("", "Arial", "Courier New", "Times New Roman", "Verdana");
    
    var tr = buildTdLabel(gsTbody, "Default Font", 95);    

    var fontFace = new Object();
    fontFace = createInputAttribute(alib.dom.createElement("select"), null, "fontFace" + type);
    
    for(var x=0; x<fontOption.length; x++)
    {
        // check if setting value is set
        var selected = false;
        if(this.gsSaveData.fontFaceRead==fontValue[x] && type=="Read")
            selected = true;
        else if(this.gsSaveData.fontFaceCompose==fontValue[x] && type=="Compose")
            selected = true;
            
        fontFace[fontFace.length] = new Option(fontOption[x], fontValue[x], false, selected);
    }        
    
    var td = alib.dom.createElement("td", tr);
    alib.dom.styleSetClass(td, "formValue");
    td.appendChild(fontFace);
    
    // check if read/compose type
    if(type=="Read")
        this.gsFormData.cbFontCompose = fontFace;
    else
        this.gsFormData.cbFontRead = fontFace;

    // Default Size - Select Dropdown
    var sizeValue = new Array("8pt", "9pt", "10pt", "11pt", "12pt", "13pt", "14pt", "16pt", "18pt");
    
    var tr = buildTdLabel(gsTbody, "Default Size");

    var fontSize = new Object();
    fontSize = createInputAttribute(alib.dom.createElement("select"), null, "fontSize" + type);
    
    fontSize[fontSize.length] = new Option("System Default", "");
    for(var x=0; x<sizeValue.length; x++)
    {
        // check if setting value is set
        var selected = false;
        if(this.gsSaveData.fontSizeRead==sizeValue[x] && type=="Read")
            selected = true;
        else if(this.gsSaveData.fontSizeCompose==sizeValue[x] && type=="Compose")
            selected = true;
        
        fontSize[fontSize.length] = new Option(sizeValue[x], sizeValue[x], false, selected);
    }        

    var td = alib.dom.createElement("td", tr);
    alib.dom.styleSetClass(td, "formValue");
    td.appendChild(fontSize);
    
    // check if read/compose type
    if(type=="Read")
        this.gsFormData.cbSizeCompose = fontSize;
    else
        this.gsFormData.cbSizeRead = fontSize;
    
    // Default Color - Select Dropdown
    var colorOption = new Array("System Default", "Black", "Aqua", "Blue", "DarkSlate Blue", "Midnite Blue", "Fuchia", "Gray", "Green", "Army Green",
                                "Lime", "Maroon", "Navy", "Olive", "Purple", "Mild Purple", "Lite Purple", "Dark Purple",
                                "Red", "Silver", "Teal", "White", "Yellow", "Level 2 Grey", "Level 4 Grey", "Level 5 Grey");
    
    var colorValue = new Array("", "000000", "00FFFF", "0000FF", "483D8B", "191970", "FF00FF", "808080", "008000", "45463E",
                                "00FF00", "800000", "000080", "808000", "800080", "3A58BA", "666699", "5B005B", 
                                "FF0000", "C0C0C0", "008080", "FFFFFF", "FFFF00", "333", "666", "999");
        
    var tr = buildTdLabel(gsTbody, "Default Color");

    var fontColor = new Object();
    fontColor = createInputAttribute(alib.dom.createElement("select"), null, "fontColor" + type);
    
    for(var x=0; x<colorValue.length; x++)
    {
        // check if setting value is set
        var selected = false;
        if(this.gsSaveData.fontColorRead==colorValue[x] && type=="Read")
            selected = true;
        else if(this.gsSaveData.fontColorCompose==colorValue[x] && type=="Compose")
            selected = true;
            
        fontColor[fontColor.length] = new Option(colorOption[x], colorValue[x], false, selected);
    }        

    alib.dom.styleSetClass(td, "formValue");
    var td = alib.dom.createElement("td", tr);
    td.appendChild(fontColor);
    
    // check if read/compose type
    if(type=="Read")
        this.gsFormData.cbColorCompose = fontColor;
    else
        this.gsFormData.cbColorRead = fontColor;
}

/**
 * Render the account tab
 */
Plugin_Messages_Settings.prototype.buildAccountTab = function()
{ 
	var con = this.accountsCon;

	// Clean container
	con.innerHTML = "";

	// Add new account
	var addCon = alib.dom.createElement("div", con, null, {class:"mgb1"});
	var addLnk = alib.dom.createElement("a", addCon, "Add Account", {href:"javascript:void(0);"});
	addLnk.cls = this;
	addLnk.onclick = function() {
		this.cls.showAccountDialog();
	}

	// Create accounts table
	var tbl = new CToolTable("100%"); 
    tbl.addHeader("Name", "left", "180px");
    tbl.addHeader("Address", "left", "300px");
    tbl.addHeader("Reply To", "left", "180px");
    tbl.addHeader("Default", "left", "50px");
    tbl.addHeader("&nbsp;", "left", "50px");
    tbl.addHeader("Delete", "center", "50px");

	// Add accounts
	for(i in this.accounts)
	{            
		var account = this.accounts[i];
		
		var rw = tbl.addRow();
		rw.addCell(account.name);
		rw.addCell(account.email_address);
		rw.addCell(account.reply_to);
		rw.addCell((account.f_default)?"Yes":"No", null, "center");
		
		// Edit Link
		var editLink = alib.dom.createElement("a", null, "[edit]", {href:"javascript: void(0);"});    
		editLink.aid = account.id;
		editLink.cls = this;
		editLink.onclick = function() {
			this.cls.showAccountDialog(this.aid);
		}
		rw.addCell(editLink, null, "center");
		
		// delete link column
		if(account.f_default)
		{
			var deleteLink = "&nbsp;";        
		}
		else
		{
			var deleteLink = alib.dom.createElement("a", null, "<img src='/images/icons/delete_10.png' border='0' />");
			deleteLink.href = "javascript: void(0);";
			deleteLink.account = account;
			deleteLink.cls = this;
			deleteLink.m_rw = rw;
			deleteLink.onclick = function()
			{
				// confirm if user is sure to perform delete action
				if(!confirm("Are you sure to delete this account?"))
					return;
				
				var args = new Array();
				args[args.length] = ['accountId', this.account.id];
				args[args.length] = ['accountName', this.account.name];
				
				ajax = new CAjax('json');
				ajax.cls = this.cls;
				ajax.rw = this.m_rw
				ajax.dlg = showDialog("Deleting send from account, please wait...");
				ajax.onload = function(ret)
				{
					delete this.cls.accounts[ret.accountId];
					
					// delete the row selected
					this.rw.deleteRow();
					this.dlg.hide();
					ALib.statusShowAlert("Account Name: " + ret.accountName + " has been Deleted!", 3000, "bottom", "right");
				};
				ajax.exec("/controller/Email/deleteSendFromAccount", args);
			}
		}
		
		rw.addCell(deleteLink, null, "center");
    }

	tbl.print(con);
}

/**
 * Add or edit an account
 *
 * @param {int} accountId The id of the account to edit if editing, otherwise create new account
 */
Plugin_Messages_Settings.prototype.showAccountDialog = function(accountId)
{
	var dlg = new CDialog((accountId) ? "Edit Account" : "New Account");
	var dlgOuter = alib.dom.createElement("div");
	var dlgCon = alib.dom.createElement("div", dlgOuter, null, {class:"wizardBody"});
	dlg.customDialog(dlgOuter, 600);

	var accountObj = (accountId) ? this.accounts[accountId] : new Object();

	var formData = {
		accountId: accountObj.id
	};

	// Your Name - Inputbox
	alib.dom.createElement("div", dlgCon, "Display Name", {class: "wizardLabel col-3"});
	var inpCon = alib.dom.createElement("div", dlgCon, null, {class: "col-6 mgb1"});
    formData.txtName = alib.dom.createElement("input", inpCon, null, {type:"text", name:"yourName", style:"width:98%;"});
	formData.txtName.value = (accountObj.name) ? accountObj.name : "";
	alib.dom.createElement("div", dlgCon, "", {class: "col-3"});
	alib.dom.createElement("div", dlgCon, null, {class:"clear"}); // clear floats
    
    // Email Address - Inputbox
	alib.dom.createElement("div", dlgCon, "Email Address", {class: "wizardLabel col-3"});
	var inpCon = alib.dom.createElement("div", dlgCon, null, {class: "col-6 mgb1"});
    formData.txtEmailAddress = alib.dom.createElement("input", inpCon, null, {type:"text", name:"emailAddress", style:"width:98%;"});
	formData.txtEmailAddress.value = (accountObj.email_address) ? accountObj.email_address : "";
	alib.dom.createElement("div", dlgCon, null, {class: "col-3"});
	alib.dom.createElement("div", dlgCon, null, {class:"clear"}); // clear floats
    
    // Email Address - Inputbox
	alib.dom.createElement("div", dlgCon, "Reply To", {class: "wizardLabel col-3 mgb1"});
	var inpCon = alib.dom.createElement("div", dlgCon, null, {class: "col-6 mgb1"});
    formData.txtReplyTo = alib.dom.createElement("input", inpCon, null, {type:"text", name:"replyTo", style:"width:98%;"});
	formData.txtReplyTo.value = (accountObj.reply_to) ? accountObj.reply_to : "";
	alib.dom.createElement("div", inpCon, "(usually the same as address above)");
	alib.dom.createElement("div", dlgCon, null, {class: "col-3"});
	alib.dom.createElement("div", dlgCon, null, {class:"clear"}); // clear floats
    
    // Default Account - checkbox
	var spacer = alib.dom.createElement("div", dlgCon, "&nbsp;", {class: "col-3"});
	var inpCon = alib.dom.createElement("div", dlgCon, null, {class: "col-9 mgb1"});
    formData.ckDefault = alib.dom.createElement("input", inpCon, null, {type:"checkbox", name:"defaultAccount"});
	formData.ckDefault.checked = (accountId) ? accountObj.f_default : false;
	alib.dom.createElement("span", inpCon, "Make this my default account");
	alib.dom.createElement("div", dlgCon, null, {class:"clear"}); // clear floats

    // Signature
	var spacer = alib.dom.createElement("div", dlgCon, "Signature", {class: "wizardLabel col-3"});
	var inpCon = alib.dom.createElement("div", dlgCon, null, {class: "col-9 mgb1"});
    formData.signature = alib.ui.Editor();
	formData.signature.print(inpCon, '95%', '120px');
	if (accountObj.signature)
		formData.signature.setValue(accountObj.signature);
	alib.dom.createElement("div", dlgCon, null, {class:"clear"}); // clear floats

	// Type
	alib.dom.createElement("div", dlgCon, "Server Type", {class: "wizardLabel col-3"});
	var inpCon = alib.dom.createElement("div", dlgCon, null, {class: "col-9 mgb1"});
	formData.cbType = alib.dom.createElement("select", inpCon);
	if (accountObj.f_system)
	{
		formData.cbType[formData.cbType.length] = new Option("System Account", "", false, (accountObj.type=="")?true:false);
		formData.cbType.disabled = true;
	}
	else
	{
		formData.cbType[formData.cbType.length] = new Option("None - just reply from this address", "", false, (accountObj.type=="")?true:false);
		formData.cbType[formData.cbType.length] = new Option("IMAP", "imap", false, (accountObj.type=="imap")?true:false);
		formData.cbType[formData.cbType.length] = new Option("POP3", "pop3", false, (accountObj.type=="pop3")?true:false);
	}
	alib.dom.createElement("div", dlgCon, null, {class:"clear"});

	// Create server div for hiding and showing based on type
	var serversCon = alib.dom.createElement("div", dlgCon);
	if (!accountObj.type || accountObj.f_system)
		alib.dom.styleSet(serversCon, "display", "none");

	// Hide servers con if not needed
	formData.cbType.onchange = function() {
		alib.dom.styleSet(serversCon, "display", (this.value) ? "block" : "none");
	}

	// Incoming Host
	alib.dom.createElement("div", serversCon, "Incoming Server", {class: "wizardLabel col-3"});
	var inpCon = alib.dom.createElement("div", serversCon, null, {class: "col-6 mgb1"});
	formData.txtHost = alib.dom.createElement("input", inpCon, null, {type:"text", name:"host", style:"width:98%;"});
	formData.txtHost.value = (accountObj.host) ? accountObj.host : "";
	var optCon = alib.dom.createElement("div", serversCon, null, {class: "col-3"});
    formData.ckSsl = alib.dom.createElement("input", optCon, null, {type:"checkbox"});
	formData.ckSsl.checked = (accountObj.ssl) ? accountObj.ssl : false;
	alib.dom.createElement("span", optCon, " Require SSL");
	alib.dom.createElement("div", serversCon, null, {class:"clear"});

	// Outgoing host
	alib.dom.createElement("div", serversCon, "Outgoing Server", {class: "wizardLabel col-3"});
	var inpCon = alib.dom.createElement("div", serversCon, null, {class: "col-6 mgb1"});
	formData.txtHostOut = alib.dom.createElement("input", inpCon, null, {type:"text", name:"host_out", style:"width:98%;"});
	formData.txtHostOut.value = (accountObj.hostOut) ? accountObj.hostOut : "";
	var optCon = alib.dom.createElement("div", serversCon, null, {class: "col-3"});
    formData.ckSslOut = alib.dom.createElement("input", optCon, null, {type:"checkbox"});
	formData.ckSslOut.checked = (accountObj.sslOut) ? accountObj.ssl : false;
	alib.dom.createElement("span", optCon, " Require SSL");
	alib.dom.createElement("div", serversCon, null, {class:"clear"});

	// Username
	alib.dom.createElement("div", serversCon, "User Name", {class: "wizardLabel col-3"});
	var inpCon = alib.dom.createElement("div", serversCon, null, {class: "col-6 mgb1"});
	formData.txtUserName = alib.dom.createElement("input", inpCon, null, {type:"text", name:"username", style:"width:98%;"});
	formData.txtUserName.value = (accountObj.username) ? accountObj.username : "";
	alib.dom.createElement("div", serversCon, null, {class: "col-3"});
	alib.dom.createElement("div", serversCon, null, {class:"clear"});

	// Password
	alib.dom.createElement("div", serversCon, "Password", {class: "wizardLabel col-3"});
	var inpCon = alib.dom.createElement("div", serversCon, null, {class: "col-6 mgb1"});
	formData.txtPassword = alib.dom.createElement("input", inpCon, null, {type:"password", name:"password", style:"width:98%;"});
	formData.txtPassword.value = (accountObj.username) ? accountObj.username : "";
	alib.dom.createElement("div", serversCon, null, {class: "col-3"});
	alib.dom.createElement("div", serversCon, null, {class:"clear"});


	// Add buttons container
	var btnCon = alib.dom.createElement("div", dlgOuter);
	alib.dom.styleSetClass(btnCon, "wizardFooter");

	var button = alib.ui.Button("Save Changes", {
		className: "b2", 
		tooltip: "Save changes to this account", 
		dlg:dlg, 
		cls:this,
		data: formData,
		onclick:function() { 
			alib.dom.styleAddClass(this, "working");

			// Now send the request
			var xhr = new alib.net.Xhr();

			// Setup callback
			alib.events.listen(xhr, "load", function(evt) { 
				var ret = this.getResponse();

				// Change default if set
				if (ret.f_default)
				{
					for (var i in evt.data.cls.accounts)
						evt.data.cls.accounts[i].f_default = false;
				}

				// Update account for rendering table
				evt.data.cls.accounts[ret.id] = ret;


				// Re-render table
				evt.data.cls.buildAccountTab();

				// Close dialog
				evt.data.dlg.hide();

			}, {cls:this.cls, dlg:this.dlg});

			// Timed out
			alib.events.listen(xhr, "error", function(evt) { 
			}, {defCls:this});

			var postData = {
				accountId: this.data.accountId,
				name: this.data.txtName.value,
				email_address: this.data.txtEmailAddress.value,
				reply_to: this.data.txtReplyTo.value,
				signature: this.data.signature.getValue(),
				f_default: (this.data.ckDefault.checked) ? 1 : 0,
				// Inbound
				type: this.data.cbType.value,
				host: this.data.txtHost.value,
				ssl: (this.data.ckSsl.checked) ? 1 : 0,
				//port: this.data.txtPort.value,
				username: this.data.txtUserName.value,
				password: this.data.txtPassword.value,
				// Outbound
				host_out: this.data.txtHostOut.value,
				//port_out: this.data.txtPortOut.value,
				ssl_out: (this.data.ckSslOut.checked) ? 1 : 0
				/*
				username_out: this.data.txtUserNameOut.value,
				password_out: this.data.txtPasswordOut.value,
				*/
			}

			var ret = xhr.send("/controller/Email/saveAccountSettings", "POST", postData);
		}
	});
	button.print(btnCon);

	// Cancel button
	var button = alib.ui.Button("Cancel", {
		className: "b1", 
		tooltip: "Close account without saving changes", 
		dlg:dlg, 
		onclick:function() { 
			this.dlg.hide();
		}
	});
	button.print(btnCon);

	dlg.reposition();
}

/*************************************************************************
*    Function:    buildFilters
* 
*    Purpose:    Build Fitlers tab for mail settings
**************************************************************************/
Plugin_Messages_Settings.prototype.buildFilters = function(con)
{
    // Add main toolbar
    var toolbar = alib.dom.createElement("div", con);
    var tb = new CToolbar();

    // Save Filter -  button
    var btn = new CButton("Save Changes", 
    function(cls)
    {
        var args = new Array();
        for(var asForm in cls.fsFormData)
            {
            var value = "";
            switch(cls.fsFormData[asForm].type)
            {
                case "checkbox":
                    value = (cls.fsFormData[asForm].checked) ? 1:0;
                    break;
                case "select-one":
                case "text":
                default:
                    value = cls.fsFormData[asForm].value; 
                    break;
            }
            args[args.length] = [cls.fsFormData[asForm].id, value];            
        }
        
        ajax = new CAjax('json');
        ajax.cls = cls;
        ajax.dlg = showDialog("Saving filter settings, please wait...");
        ajax.onload = function(ret)
        {
            // update Current Filters Save Data
            this.cls.fsSaveData.currentFilters[ret.filterId] = new Object();
            this.cls.fsSaveData.currentFilters[ret.filterId].id = ret.filterId;
            this.cls.fsSaveData.currentFilters[ret.filterId].name = ret.filterName;
            this.cls.fsSaveData.currentFilters[ret.filterId].kw_subject = ret.subjectContains;
            this.cls.fsSaveData.currentFilters[ret.filterId].kw_to = ret.toContains;
            this.cls.fsSaveData.currentFilters[ret.filterId].kw_from = ret.fromContains;
            this.cls.fsSaveData.currentFilters[ret.filterId].kw_body = ret.bodyContains;
            this.cls.fsSaveData.currentFilters[ret.filterId].act_move_to = ret.moveToFolder;
            this.cls.fsSaveData.currentFilters[ret.filterId].act_mark_read = ret.markRead;
            this.cls.fsSaveData.currentFilters[ret.filterId].theme_name = ret.themeName;
            
            // recreate Current Filter Table row
            this.cls.buildFilterRow();
            
            // clear the filter input form            
            this.cls.inputFormClear(this.cls.fsFormData);
            document.getElementById('leftHeaderFilter').innerHTML = "Create Filter";
            this.dlg.hide();
            ALib.statusShowAlert("Email Filter Saved!", 3000, "bottom", "right");
        };
        ajax.exec("/controller/Email/saveFilterSettings", args);
    }, 
    [this], "b2");
    tb.AddItem(btn.getButton(), "left");

    // Cancel Filter -  button
    var btn = new CButton("Cancel", 
    function(cls, fsFormData)
    {
        cls.inputFormClear(fsFormData);
        document.getElementById('leftHeaderFilter').innerHTML = "Create Filter";
    }, 
    [this, this.fsFormData], "b1");
    tb.AddItem(btn.getButton(), "left");
    
    // add button to the toolbar
    tb.print(toolbar);
    
    // Create Filter  - Left Header
    this.divLeftHeader(con, "Create Filter", "90px", "leftHeaderFilter");
    
    // Create Filter - Input Form
    var fsFilterCon = alib.dom.createElement("div", con);
    alib.dom.styleSet(fsFilterCon, "float", "left");
    alib.dom.styleSet(fsFilterCon, "width", "750px");
    
    // Create Filter Input - table
    var fsTbody = buildTable(fsFilterCon);
    
    // Filter Name - Inputbox    
    this.fsFormData.txtFilterName = createInputAttribute(alib.dom.createElement("input"), "text", "filterName", "Filter Name", "190px");
    
    // Filter Id - Hidden Input
    this.fsFormData.hdnFilterId = createInputAttribute(alib.dom.createElement("input"), "hidden", "filterId");
        
    // Filter When - Row Label    
    this.fsFormData.lblLabelWhen = alib.dom.createElement("label");
    this.fsFormData.lblLabelWhen.label = "FILTER WHEN";
        
    // Subject Contains - Inputbox    
    this.fsFormData.txtSubjectContains = createInputAttribute(alib.dom.createElement("input"), "text", "subjectContains", "Subject Contains", "190px");
    this.fsFormData.txtSubjectContains.inputLabel = " Enter any words you would like to filter in the subject. Example: Sales Opportunity";
    
    // To Contains - Inputbox    
    this.fsFormData.txtToContains = createInputAttribute(alib.dom.createElement("input"), "text", "toContains", "To Contains", "190px");
    this.fsFormData.txtToContains.inputLabel = " Enter any words or addresses to filter out recipients. Example: someone@somewhere.net";
    
    // From Contains - Inputbox    
    this.fsFormData.txtFromContains = createInputAttribute(alib.dom.createElement("input"), "text", "fromContains", "From Contains", "190px");
    this.fsFormData.txtFromContains.inputLabel = " Enter any words or addresses to filter out senders. Example: someone@somewhere.net";
    
    // Body Contains - Inputbox    
    this.fsFormData.txtBodyContains = createInputAttribute(alib.dom.createElement("input"), "text", "bodyContains", "Body Contains", "190px");
    this.fsFormData.txtBodyContains.inputLabel = " Enter any words you would like to filter in the body. Example: filter words spaced";
    
    // Then - Row Label    
    this.fsFormData.lblLabelThen = alib.dom.createElement("label");
    this.fsFormData.lblLabelThen.label = "Then";
    
    // Move to Folder - Select Dropdown
    this.fsFormData.cbMoveToFolder = createInputAttribute(alib.dom.createElement("select"), null, "moveToFolder", "Move To Folder");
    
    // Move to Folder Save Data
    for(moveToFolder in this.fsSaveData.moveToFolder)
        this.fsFormData.cbMoveToFolder[this.fsFormData.cbMoveToFolder.length] = new Option(this.fsSaveData.moveToFolder[moveToFolder].name, this.fsSaveData.moveToFolder[moveToFolder].id);
    
    // build filter input form
    var tr = buildFormInput(this.fsFormData, fsTbody);
    
    // set the width of the td label
    var td = tr.firstChild;
    td.width = "95";
    
    var td = tr.lastChild;
    
    // Mark Read - Label
    var label = alib.dom.createElement("label", td);
    label.innerHTML = " Mark Read ";
    
    // Mark Read - Checkbox
    this.fsFormData.chkMarkRead = createInputAttribute(alib.dom.createElement("input", td), "checkbox", "markRead");    
    
    // clear the floating divs
    divClear(con);
    var hr = alib.dom.createElement("hr", con);
    
    // Current Filter - Table List
    var div = this.divLeftHeader(con, "Current Filters", "100px");
    alib.dom.styleSet(div, "float", "none");        
    
    var fsCurrentFiltersCon = alib.dom.createElement("div", con);
    
    // print Current Filter CToolTable
    this.filterTbl.print(fsCurrentFiltersCon);
    
    // Create Filter Table row
    this.buildFilterRow();    
}

/*************************************************************************
*    Function:    buildFilterRow
* 
*    Purpose:    Build Current Filter row
**************************************************************************/
Plugin_Messages_Settings.prototype.buildFilterRow = function()
{
        if(!this.fsSaveData)
            return;
            
        // clear the current account table rows
        this.filterTbl.clear();
    
        for(currentFilter in this.fsSaveData.currentFilters)
        {            
            var currentFilterData = this.fsSaveData.currentFilters[currentFilter];
            
            var rw = this.filterTbl.addRow();
            
            // edit name link column
            var editLink = alib.dom.createElement("a");
            editLink.innerHTML = currentFilterData.name;
            editLink.href = "javascript: void(0);";
            editLink.m_currentFilterData = currentFilterData;
            editLink.cls = this;
            editLink.onclick = function()
            {
                // assign value to the Email Filter Input form
                this.cls.fsFormData.hdnFilterId.value = this.m_currentFilterData.id;
                this.cls.fsFormData.txtFilterName.value = this.m_currentFilterData.name;
                this.cls.fsFormData.txtSubjectContains.value = this.m_currentFilterData.kw_subject;
                this.cls.fsFormData.txtToContains.value = this.m_currentFilterData.kw_to;
                this.cls.fsFormData.txtFromContains.value = this.m_currentFilterData.kw_from;
                this.cls.fsFormData.txtBodyContains.value = this.m_currentFilterData.kw_body;
                
                if(this.m_currentFilterData.act_move_to)
                    this.cls.fsFormData.cbMoveToFolder.value = this.m_currentFilterData.act_move_to;
                else
                    this.cls.fsFormData.cbMoveToFolder.value = "";
                
                if(this.m_currentFilterData.act_mark_read==1)                
                    this.cls.fsFormData.chkMarkRead.checked = true;
                else
                    this.cls.fsFormData.chkMarkRead.checked = false;
                    
                document.getElementById('leftHeaderFilter').innerHTML = "Edit Filter";
            }
            
            // center columns
            rw.addCell(editLink, null, null, null, null, "CTTRowTwoBold");
            rw.addCell(currentFilterData.kw_subject);
            rw.addCell(currentFilterData.kw_to);
            rw.addCell(currentFilterData.kw_from);
            rw.addCell(currentFilterData.kw_body);
                        
            // delete link column
            var deleteLink = alib.dom.createElement("a");
            var themeName = currentFilterData.theme_name;
            
            deleteLink.innerHTML = "<img src='/images/themes/" + themeName + "/icons/deleteTask.gif' border='0' />";
            deleteLink.href = "javascript: void(0);";
            deleteLink.m_currentFilterData = currentFilterData;            
            deleteLink.cls = this;
            deleteLink.m_rw = rw;
            deleteLink.onclick = function()
            {
                // confirm if user is sure to perform delete action
                if(!confirm("Are you sure to delete this filter?"))
                    return;
                
                var args = new Array();
                args[args.length] = ['filterId', this.m_currentFilterData.id];
                args[args.length] = ['filterName', this.m_currentFilterData.name];
                
                ajax = new CAjax('json');
                ajax.cls = this.cls;
                ajax.rw = this.m_rw;
                ajax.dlg = showDialog("Deleting email filter, please wait...");
                ajax.onload = function(ret)
                {
                    delete this.cls.fsSaveData.currentFilters[ret.filterId];
                    
                    // delete the row selected
                    this.rw.deleteRow();
                    this.dlg.hide();
                    ALib.statusShowAlert("Email Filter: " + ret.filterName + " has been Deleted!", 3000, "bottom", "right");
                };
                ajax.exec("/controller/Email/deleteEmailFilter", args);
            }            
            rw.addCell(deleteLink, null, "center", null, null, "CTTRowTwoBold");
    }    
}

/*************************************************************************
*    Function:    buildSignature
* 
*    Purpose:    Build Signature tab for mail settings
**************************************************************************/
Plugin_Messages_Settings.prototype.buildSignature = function(con)
{
    // Add main toolbar
    var toolbar = alib.dom.createElement("div", con);
    var tb = new CToolbar();

    // Save Filter -  button
    var btn = new CButton("Save Changes", 
    function(cls)
    {
        var args = new Array();
        for(var signature in cls.ssFormData)
            {
            var value = "";
            switch(cls.ssFormData[signature].type)
            {
                case "checkbox":
                    value = (cls.ssFormData[signature].checked) ? 1:0;
                    break;
                case "select-one":
                case "text":
                default:
                    value = cls.ssFormData[signature].value; 
                    break;
            }
            args[args.length] = [cls.ssFormData[signature].id, value];            
        }
        
        ajax = new CAjax('json');
        ajax.cls = cls;
        ajax.dlg = showDialog("Saving signature settings, please wait...");
        ajax.onload = function(ret)
        {
            // update My Signatures Save Data
            this.cls.ssSaveData.mySignatures[ret.signatureId] = new Object();
            this.cls.ssSaveData.mySignatures[ret.signatureId].id = ret.signatureId;
            this.cls.ssSaveData.mySignatures[ret.signatureId].name = ret.signatureName;
            this.cls.ssSaveData.mySignatures[ret.signatureId].use_default = ret.defaultSignature;
            this.cls.ssSaveData.mySignatures[ret.signatureId].signature = ret.signature;
            this.cls.ssSaveData.mySignatures[ret.signatureId].theme_name = ret.themeName;
            
            // add signature in signature tab
            var newSig = true;
            for(signature in this.cls.asFormData.cbSignature)
            {
                if(signature > 0)
                {
                    var currentSignature = this.cls.asFormData.cbSignature[signature];
                
                    if(currentSignature.value==ret.signatureId)
                    {
                        currentSignature.text = ret.signatureName;
                        newSig = false;
                    }
                }
            }
            
            if(newSig)
                this.cls.asFormData.cbSignature[this.cls.asFormData.cbSignature.length] = new Option(ret.signatureName, ret.signatureId);
            
            var signatureId = 0;            
            if(ret.defaultSignature==1)
            {
                signatureId = ret.signatureId;
                this.cls.ssSaveData.mySignatures[ret.signatureId].use_default = "Yes";                                
            }
            else
                this.cls.ssSaveData.mySignatures[ret.signatureId].use_default = "No";
            
            // recreate My Signature Table row
            this.cls.buildSignatureRow(signatureId);
            
            // clear the signature input form
            var div = document.getElementById('divSignatureRte');
        
            this.cls.ssFormData.txtSignature = this.cls.buildRte(div, "signature", "");
            this.cls.inputFormClear(this.cls.ssFormData);
            document.getElementById('leftHeaderSignature').innerHTML = "Create Signature";
            this.dlg.hide();
            ALib.statusShowAlert("Signature Saved!", 3000, "bottom", "right");
        };
        ajax.exec("/controller/Email/saveSignatureSettings", args);
    }, 
    [this], "b2");
    tb.AddItem(btn.getButton(), "left");

    // Cancel-  button
    var btn = new CButton("Cancel", 
    function(cls)
    {
        var div = document.getElementById('divSignatureRte');
        
        cls.ssFormData.txtSignature = cls.buildRte(div, "signature", "");
        cls.inputFormClear(cls.ssFormData);
        document.getElementById('leftHeaderSignature').innerHTML = "Create Signature";
    }, 
    [this], "b1");
    tb.AddItem(btn.getButton(), "left");
    
    // add button to the toolbar
    tb.print(toolbar);
    
    // Create Filter  - Left Header    
    this.divLeftHeader(con, "Create Signature", "120px", "leftHeaderSignature");
    
    // Create Filter - Input Form
    var ssSignatureCon = alib.dom.createElement("div", con);
    alib.dom.styleSet(ssSignatureCon, "float", "left");
    alib.dom.styleSet(ssSignatureCon, "width", "720px");
    
    // Create Filter Input - table    
    var ssTbody = buildTable(ssSignatureCon);
    
    // Signature Name - Inputbox    
    this.ssFormData.txtSignatureName = createInputAttribute(alib.dom.createElement("input"), "text", "signatureName", "Signature Name", "300px");
    
    // Signature Id - Hidden Input
    this.ssFormData.hdnSignatureId= createInputAttribute(alib.dom.createElement("input"), "hidden", "signatureId");
    
    // Default Signature - Checkbox
    this.ssFormData.chkDefaultSignature = createInputAttribute(alib.dom.createElement("input"), "checkbox", "defaultSignature");    
    this.ssFormData.chkDefaultSignature.label = " Make this my default signature";    
    
    // build filter input form
    var tr = buildFormInput(this.ssFormData, ssTbody);
    tr.parentNode.firstChild.firstChild.width = 100;
    
    // clear the floating divs
    divClear(con);
    
    // set the rich textbox    
    var divSignatureLabel = this.divLeftHeader(con, "Signature", "177px", "leftHeaderSignature");
    alib.dom.styleSet(divSignatureLabel.firstChild, "fontWeight", "normal");
    alib.dom.styleSet(divSignatureLabel.firstChild, "textAlign", "right");
    var divSignatureRte = alib.dom.createElement("div", con);
    divSignatureRte.id = "divSignatureRte";
    alib.dom.styleSet(divSignatureRte, "float", "left");
    alib.dom.styleSet(divSignatureRte, "marginLeft", "53px");
    
    // build signature rich textbox editor    
    this.ssFormData.txtSignature = this.buildRte(divSignatureRte, "signature", "");
    
    // clear the floating divs
    divClear(con);
    var hr = alib.dom.createElement("hr", con);
    
    // My Signatures - Table List
    var div = this.divLeftHeader(con, "My Signatures", "100px");
    alib.dom.styleSet(div, "float", "none");
    
    var ssSignaturesCon = alib.dom.createElement("div", con);
    
    // print Current Filter CToolTable
    this.signatureTbl.print(ssSignaturesCon);
    
    // Create Signature Table row
    this.buildSignatureRow();    
}

/*************************************************************************
*    Function:    buildSignatureRow
* 
*    Purpose:    Build Current Filter row
**************************************************************************/
Plugin_Messages_Settings.prototype.buildSignatureRow = function(signatureId)
{
        if(!this.ssSaveData)
            return;
            
        // clear the current account table rows
        this.signatureTbl.clear();
        
        for(currentSignature in this.ssSaveData.mySignatures)
        {
            var currentSignatureData = this.ssSaveData.mySignatures[currentSignature];
            
            if(currentSignatureData.id != signatureId && signatureId>0)
                currentSignatureData.use_default = "No";            
            
            
            var rw = this.signatureTbl.addRow();
            
            // signature column
            rw.addCell(currentSignatureData.name);
            rw.addCell(currentSignatureData.use_default, null, "center");            
            
            // edit name link column
            var editLink = alib.dom.createElement("a");
            editLink.innerHTML = "[edit]";
            editLink.href = "javascript: void(0);";
            editLink.m_currentSignatureData = currentSignatureData;
            editLink.cls = this;
            editLink.onclick = function()
            {
                // assign value to the Email Signature Input form
                this.cls.ssFormData.hdnSignatureId.value = this.m_currentSignatureData.id;
                this.cls.ssFormData.txtSignatureName.value = this.m_currentSignatureData.name;
                
                if(this.m_currentSignatureData.use_default=="Yes")                
                    this.cls.ssFormData.chkDefaultSignature.checked = true;
                else
                    this.cls.ssFormData.chkDefaultSignature.checked = false;
                    
                var div = document.getElementById('divSignatureRte');        
                
                this.cls.ssFormData.txtSignature = this.cls.buildRte(div, "signature", this.m_currentSignatureData.signature);
                document.getElementById('leftHeaderSignature').innerHTML = "Edit Signature";
            }
            rw.addCell(editLink, null, "center");            
                        
            // delete link column
            var deleteLink = alib.dom.createElement("a");
            var themeName = currentSignatureData.theme_name;
            
            deleteLink.innerHTML = "<img src='/images/themes/" + themeName + "/icons/deleteTask.gif' border='0' />";
            deleteLink.href = "javascript: void(0);";
            deleteLink.m_currentSignatureData = currentSignatureData;            
            deleteLink.cls = this;
            deleteLink.m_rw = rw;
            deleteLink.onclick = function()
            {
                // confirm if user is sure to perform delete action
                if(!confirm("Are you sure to delete this signature?"))
                    return;
                
                var args = new Array();
                args[args.length] = ['signatureId', this.m_currentSignatureData.id];
                args[args.length] = ['signatureName', this.m_currentSignatureData.name];
                
                ajax = new CAjax('json');
                ajax.cls = this.cls;
                ajax.rw = this.m_rw;
                ajax.dlg = showDialog("Deleting signature, please wait...");
                ajax.onload = function(ret)
                {
                    delete this.cls.ssSaveData.mySignatures[ret.signatureId];
                    delete this.cls.asFormData.cbSignature[ret.signatureId]
                    
                    
                    // delete the row selected
                    this.rw.deleteRow();
                    this.dlg.hide();
                    ALib.statusShowAlert("Email Signature: " + ret.signatureName + " has been Deleted!", 3000, "bottom", "right");
                };
                ajax.exec("/controller/Email/deleteSignature", args);
            }            
            rw.addCell(deleteLink, null, "center", null, null, "CTTRowTwoBold");
    }    
}

/*************************************************************************
*    Function:    buildVideoEmailTheme
* 
*    Purpose:    Build Video Email Theme tab for mail settings
**************************************************************************/
Plugin_Messages_Settings.prototype.buildVideoEmailTheme = function(con)
{
    // Add main toolbar
    var toolbar = alib.dom.createElement("div", con);
    var tb = new CToolbar();

    // Save Filter -  button
    var btn = new CButton("Save Changes", 
    function(cls)
    {
        var args = new Array();
        for(var theme in cls.tsFormData)
            {
            var value = "";
            switch(cls.tsFormData[theme].type)
            {
                case "button":
                    break;
                case "checkbox":
                    value = (cls.tsFormData[theme].checked) ? 1:0;
                    break;
                case "select-one":
                case "text":
                default:
                    value = cls.tsFormData[theme].value; 
                    break;
            }
            args[args.length] = [cls.tsFormData[theme].id, value];            
        }
        
        ajax = new CAjax('json');
        ajax.cls = cls;
        ajax.dlg = showDialog("Saving theme settings, please wait...");
        ajax.onload = function(ret)
        {
            // update Video Email Themes Save Data
            this.cls.tsSaveData.themes[ret.themeId] = new Object();
            this.cls.tsSaveData.themes[ret.themeId].id = ret.themeId;
            this.cls.tsSaveData.themes[ret.themeId].name = ret.name; // Video Email Theme Name
            this.cls.tsSaveData.themes[ret.themeId].header_file_id = ret.headerImageId;
            this.cls.tsSaveData.themes[ret.themeId].footer_file_id = ret.footerImageId;
            this.cls.tsSaveData.themes[ret.themeId].button_off_file_id = ret.buttonImageId;
            this.cls.tsSaveData.themes[ret.themeId].scope = ret.scope;            
            this.cls.tsSaveData.themes[ret.themeId].html = ret.customHtml;
            this.cls.tsSaveData.themes[ret.themeId].background_color = ret.backgroundColor;
            this.cls.tsSaveData.themes[ret.themeId].theme_name = ret.themeName; // this is used for delete image
            
            // set the file name selected
            this.cls.tsSaveData.themes[ret.themeId].header_file_name = this.cls.tsFormData.btnHeaderImage.parentNode.lastChild.innerHTML;
            this.cls.tsSaveData.themes[ret.themeId].footer_file_name = this.cls.tsFormData.btnFooterImage.parentNode.lastChild.innerHTML;
            this.cls.tsSaveData.themes[ret.themeId].button_file_name = this.cls.tsFormData.btnButtonImage.parentNode.lastChild.innerHTML;
            
            // recreate Video Email Themes Table row
            this.cls.buildThemeRow();
            
            // clear the Video Email Theme input form
            this.cls.tsFormData.btnHeaderImage.parentNode.lastChild.innerHTML = " None Selected";
            this.cls.tsFormData.btnFooterImage.parentNode.lastChild.innerHTML = " None Selected";
            this.cls.tsFormData.btnButtonImage.parentNode.lastChild.innerHTML = " None Selected";
            this.cls.inputFormClear(this.cls.tsFormData);
            document.getElementById('leftHeaderTheme').innerHTML = "Create Theme";
            
            this.dlg.hide();
            ALib.statusShowAlert("Video Email Theme Saved!", 3000, "bottom", "right");
        };
        ajax.exec("/controller/Email/saveThemeSettings", args);
    }, 
    [this], "b2");
    tb.AddItem(btn.getButton(), "left");

    // Cancel-  button
    var btn = new CButton("Cancel", 
    function(cls)
    {
        cls.tsFormData.btnHeaderImage.parentNode.lastChild.innerHTML = " None Selected";
        cls.tsFormData.btnFooterImage.parentNode.lastChild.innerHTML = " None Selected";
        cls.tsFormData.btnButtonImage.parentNode.lastChild.innerHTML = " None Selected";
        cls.inputFormClear(cls.tsFormData);
    }, 
    [this], "b1");
    tb.AddItem(btn.getButton(), "left");
    
    // add button to the toolbar
    tb.print(toolbar);
    
    // Create Filter  - Left Header    
    this.divLeftHeader(con, "Create Theme", "120px", "leftHeaderTheme");
    
    // Create Filter - Input Form
    var tsThemeCon = alib.dom.createElement("div", con);
    alib.dom.styleSet(tsThemeCon, "float", "left");
    alib.dom.styleSet(tsThemeCon, "width", "720px");
    
    // Create Filter Input - table    
    var tsTbody = buildTable(tsThemeCon);
    
    // Theme Name - Inputbox    
    this.tsFormData.txtThemeName = createInputAttribute(alib.dom.createElement("input"), "text", "name", "Theme Name", "300px");
    
    // Theme Id - Hidden Input
    this.tsFormData.hdnThemeId= createInputAttribute(alib.dom.createElement("input"), "hidden", "themeId");
    
    // Header Select Image - Hidden Input
    this.tsFormData.hdnHeaderImageId = createInputAttribute(alib.dom.createElement("input"), "hidden", "headerImageId", null, null, 0);
    
    // Header Select Image - Button
    this.tsFormData.btnHeaderImage = createInputAttribute(alib.dom.createElement("input"), "button", "headerImage", "Header Image <br />(720 X 100 pixels)", null, "Select File");
    this.tsFormData.btnHeaderImage.inputLabel = " None Selected";
    
    this.tsFormData.btnHeaderImage.cls = this;
    this.tsFormData.btnHeaderImage.onclick = function()
    {
        var cbrowser = new AntFsOpen();
        cbrowser.cbData.cls = this.cls;
        cbrowser.onSelect = function(id, name) 
        {
            this.cbData.cls.tsFormData.btnHeaderImage.parentNode.lastChild.innerHTML = " " + name;
            this.cbData.cls.tsFormData.hdnHeaderImageId.value = id;            
        }
        cbrowser.showDialog();
    }
    
    // Footer Select Image - Hidden Input
    this.tsFormData.hdnFooterImageId = createInputAttribute(alib.dom.createElement("input"), "hidden", "footerImageId", null, null, 0);
    
    // Footer Select Image - Button
    this.tsFormData.btnFooterImage = createInputAttribute(alib.dom.createElement("input"), "button", "footerImage", "Footer Image <br />(720 X 40 pixels)", null, "Select File");
    this.tsFormData.btnFooterImage.inputLabel = " None Selected";
    
    this.tsFormData.btnFooterImage.cls = this;
    this.tsFormData.btnFooterImage.onclick = function()
    {
        var cbrowser = new AntFsOpen();
        cbrowser.cbData.cls = this.cls;
        cbrowser.onSelect = function(id, name) 
        {
            this.cbData.cls.tsFormData.btnFooterImage.parentNode.lastChild.innerHTML = " " + name;
            this.cbData.cls.tsFormData.hdnFooterImageId.value = id;            
        }
        cbrowser.showDialog();
    }
    
    // Button Background Select Image - Hidden Input
    this.tsFormData.hdnButtonImageId = createInputAttribute(alib.dom.createElement("input"), "hidden", "buttonImageId", null, null, 0);
    
    // Button Background Select Image - Button
    this.tsFormData.btnButtonImage = createInputAttribute(alib.dom.createElement("input"), "button", "buttonImage", "Button Image <br />(720 X 40 pixels)", null, "Select File");
    this.tsFormData.btnButtonImage.inputLabel = " None Selected";
    
    this.tsFormData.btnButtonImage.cls = this;
    this.tsFormData.btnButtonImage.onclick = function()
    {
        var cbrowser = new AntFsOpen();
        cbrowser.cbData.cls = this.cls;
        cbrowser.onSelect = function(id, name) 
        {
            this.cbData.cls.tsFormData.btnButtonImage.parentNode.lastChild.innerHTML = " " + name;
            this.cbData.cls.tsFormData.hdnButtonImageId.value = id;            
        }
        cbrowser.showDialog();
    }
    
    // Background Color - Textbox input
    this.tsFormData.txtBackgroundColor = createInputAttribute(alib.dom.createElement("input"), "text", "backgroundColor", "Background Color (hex)", "140px");
    
    // Custom Html - Textbox input
    this.tsFormData.txtCustomHtml = createInputAttribute(alib.dom.createElement("textarea"), null, "customHtml", "Or Use Custom HTML (advanced)<br />More Information");
    
    // Scope - Select Dropbown
    this.tsFormData.cbScope = createInputAttribute(alib.dom.createElement("select"), null, "scope", "Scope");
    
    this.tsFormData.cbScope [this.tsFormData.cbScope.length] = new Option("Theme can only be used by me", "");
    this.tsFormData.cbScope [this.tsFormData.cbScope.length] = new Option("Theme can be used by any user", "global");
    
    // build filter input form
    var tr = buildFormInput(this.tsFormData, tsTbody);
    tr.parentNode.firstChild.firstChild.width = 185;
    
    // clear the floating divs
    divClear(con);
    var hr = alib.dom.createElement("hr", con);
    
    // Video Email Theme - Table List
    var div = this.divLeftHeader(con, "Video Email Themes", "150px");
    alib.dom.styleSet(div, "float", "none");
    
    var tsThemeCon = alib.dom.createElement("div", con);
    
    // print Current Filter CToolTable
    this.themeTbl.print(tsThemeCon);
    
    // Create Theme Table row
    this.buildThemeRow();    
}

/*************************************************************************
*    Function:    buildThemeRow
* 
*    Purpose:    Build Video Email Theme row
**************************************************************************/
Plugin_Messages_Settings.prototype.buildThemeRow = function()
{
        if(!this.tsSaveData)
            return;
            
        // clear the current account table rows
        this.themeTbl.clear();
    
        for(theme in this.tsSaveData.themes)
        {
            var themeData = this.tsSaveData.themes[theme];
            
            // add new row
            var rw = this.themeTbl.addRow();
            
            // Video Email Theme column
            rw.addCell(themeData.name);
            if(themeData.scope=="NULL")
                themeData.scope = "";
                
                
            rw.addCell(themeData.scope, null, "center");

            // edit link column
            var editLink = alib.dom.createElement("a");
            editLink.innerHTML = "[edit]";
            editLink.href = "javascript: void(0);";
            editLink.m_themeData = themeData;
            editLink.cls = this;
            editLink.onclick = function()
            {
                // assign value to the Theme Input form
                this.cls.tsFormData.hdnThemeId.value = this.m_themeData.id;
                this.cls.tsFormData.txtThemeName.value = this.m_themeData.name;
                this.cls.tsFormData.hdnHeaderImageId.value = this.m_themeData.header_file_id;                
                this.cls.tsFormData.hdnFooterImageId.value = this.m_themeData.footer_file_id;
                this.cls.tsFormData.hdnButtonImageId.value = this.m_themeData.button_off_file_id;
                this.cls.tsFormData.cbScope.value = this.m_themeData.scope;
                this.cls.tsFormData.txtBackgroundColor.value = this.m_themeData.background_color;
                this.cls.tsFormData.txtCustomHtml.value = this.m_themeData.html;
                
                if(this.m_themeData.header_file_name)
                    this.cls.tsFormData.btnHeaderImage.parentNode.lastChild.innerHTML = this.m_themeData.header_file_name;
                    
                if(this.m_themeData.footer_file_name)
                    this.cls.tsFormData.btnFooterImage.parentNode.lastChild.innerHTML = this.m_themeData.footer_file_name;
                    
                if(this.m_themeData.button_file_name)
                    this.cls.tsFormData.btnButtonImage.parentNode.lastChild.innerHTML = this.m_themeData.button_file_name;
                
                document.getElementById('leftHeaderTheme').innerHTML = "Edit Theme";
            }
            rw.addCell(editLink, null, "center");
            
            // delete link column
            var deleteLink = alib.dom.createElement("a");
            var themeName = themeData.theme_name;
            
            deleteLink.innerHTML = "<img src='/images/themes/" + themeName + "/icons/deleteTask.gif' border='0' />";
            deleteLink.href = "javascript: void(0);";            
            deleteLink.m_themeData = themeData;
            deleteLink.cls = this;
            deleteLink.m_rw = rw;
            deleteLink.onclick = function()
            {
                // confirm if user is sure to perform delete action
                if(!confirm("Are you sure to delete this theme?"))
                    return;
                
                var args = new Array();
                args[args.length] = ['themeId', this.m_themeData.id];
                args[args.length] = ['themeName', this.m_themeData.name];
                                
                ajax = new CAjax('json');
                ajax.cls = this.cls;
                ajax.rw = this.m_rw;
                ajax.dlg = showDialog("Deleting theme, please wait...");
                ajax.onload = function(ret)
                {
                    delete this.cls.tsSaveData.themes[ret.themeId];
                    
                    // delete the row selected
                    this.rw.deleteRow();
                    this.dlg.hide();
                    ALib.statusShowAlert("Video Email Theme: " + ret.themeName + " has been Deleted!", 3000, "bottom", "right");
                };
                ajax.exec("/controller/Email/deleteTheme", args);
            }            
            rw.addCell(deleteLink, null, "center", null, null, "CTTRowTwoBold");
    }    
}
    
/*************************************************************************
*    Function:    buildSpam
* 
*    Purpose:    Build Spam tab for mail settings
**************************************************************************/
Plugin_Messages_Settings.prototype.buildSpam = function(con)
{    
    // Create Spam Blacklist  - Left Header
    this.divLeftHeader(con, "Create Blacklist", "120px", "leftHeaderBlacklist");
    
    // Create Spam Blacklist - Input Form
    var msSpamCon = alib.dom.createElement("div", con);
    alib.dom.styleSet(msSpamCon, "float", "left");
    alib.dom.styleSet(msSpamCon, "width", "400px");
    alib.dom.styleSet(msSpamCon, "marginTop", "5px");
    
    // Create Spam Blacklist Input - table
    var msTbody = buildTable(msSpamCon);
    
    // Blacklist - Inputbox    
    this.msFormData.txtBlacklist = createInputAttribute(alib.dom.createElement("input"), "text", "blacklist", "Blacklist", "180px");
    
    // Blacklist List - Select List
    this.msFormData.slctBlacklist = createInputAttribute(alib.dom.createElement("select"), null, "blacklistList", null, "290px");
    this.msFormData.slctBlacklist.size = 13;
    
    // build Blacklist input form
    var tr = buildFormInput(this.msFormData, msTbody);
    
    // set the width of the td label
    var td = tr.firstChild;
    td.width = "50";
    
    // append Add Blacklist button
    var btnAddBlacklist  = createInputAttribute(alib.dom.createElement("input", this.msFormData.txtBlacklist.parentNode), "button", null, null, null, "Add to Blacklist");    
    alib.dom.styleSet(btnAddBlacklist, "marginLeft", "5px");
    btnAddBlacklist.m_msFormData = this.msFormData;
    btnAddBlacklist.cls = this;
    btnAddBlacklist.onclick = function()
    {
        var args = new Array();
        args[args.length] = ['blacklist', this.m_msFormData.txtBlacklist.value];        
        args[args.length] = ['preference', "blacklist_from"];
        
        ajax = new CAjax('json');
        ajax.cls = this.cls;
        ajax.dlg = showDialog("Saving spam blacklist, please wait...");
        ajax.onload = function(ret)
        {
            // rebuild the spam for blacklist and whitelist
            this.cls.buildSpamList(ret);
            
            this.dlg.hide();
            ALib.statusShowAlert("Spam Blacklist: " + ret.blacklist + " Saved!", 3000, "bottom", "right");
        };
        ajax.exec("/controller/Email/saveSpam", args);
        this.m_msFormData.txtBlacklist.value = "";
    }
    
    // append Remove Blacklist button
    alib.dom.createElement("br", this.msFormData.slctBlacklist.parentNode)
    var btnRemoveBlacklist = createInputAttribute(alib.dom.createElement("input", this.msFormData.slctBlacklist.parentNode), "button", null, null, null, "Remove Selected Address");    
    btnRemoveBlacklist.m_msFormData = this.msFormData;
    btnRemoveBlacklist.cls = this;
    btnRemoveBlacklist.onclick = function()
    {        
        var selectedIndex = this.m_msFormData.slctBlacklist.selectedIndex;
        if(selectedIndex==-1)
            return;
        
        var args = new Array();
        args[args.length] = ['spamId', this.m_msFormData.slctBlacklist.value];
        args[args.length] = ['blacklist', this.m_msFormData.slctBlacklist[selectedIndex].text];
                
        ajax = new CAjax('json');
        ajax.dlg = showDialog("Deleting spam blacklist, please wait...");
        ajax.onload = function(ret)
        {
            this.dlg.hide();
            ALib.statusShowAlert("Spam Blacklist: " + ret.blacklist + " has been Deleted!", 3000, "bottom", "right");
        };
        ajax.exec("/controller/Email/deleteSpam", args);
        this.m_msFormData.slctBlacklist.remove(selectedIndex);
    }
    
    // Blacklist Info - div
    var div = this.divLeftHeader(con, "Blacklist Information", "300px");
    var label = alib.dom.createElement("p", div);
    label.innerHTML = "A blacklist entry can be added or removed using the form to the left. The email server will automatically forward any messaged received from anyone in the blacklist in your spam directory. You may enter either the full email address or use the * wildcard. *@domain.com will block all users from domain.com username@domain.com would only block username from domain.com. *domain* will block any address with the word 'domain' in the address.";
    alib.dom.styleSet(label, "textAlign", "justify");
    alib.dom.styleSet(label, "lineHeight", "20px");
    
    // clear the floating divs
    divClear(con);
    var hr = alib.dom.createElement("hr", con);
    
    /*
    ** START WHITE LIST
    */
    
    // Create Spam Whitelist  - Left Header
    this.divLeftHeader(con, "Create Whitelist", "120px", "leftHeaderWhitelist");
    
    // Create Spam Whitelist - Input Form
    var msSpamCon = alib.dom.createElement("div", con);
    alib.dom.styleSet(msSpamCon, "float", "left");
    alib.dom.styleSet(msSpamCon, "width", "400px");
    alib.dom.styleSet(msSpamCon, "marginTop", "5px");
    
    // Create Spam Whitelist Input - table
    var msTbody = buildTable(msSpamCon);
            
    // Whitelist - Inputbox    
    this.msFormData.txtWhitelist = createInputAttribute(alib.dom.createElement("input"), "text", "whitelist", "Whitelist", "180px");
    
    // append inputbox
    var tr = buildTdLabel(msTbody, this.msFormData.txtWhitelist.label, 50);
    var td = alib.dom.createElement("td", tr);    
    alib.dom.styleSetClass(td, "formValue");
    td.appendChild(this.msFormData.txtWhitelist);
    
    // append Add Whitelist button
    var btnAddWhitelist  = createInputAttribute(alib.dom.createElement("input", this.msFormData.txtWhitelist.parentNode), "button", null, null, null, "Add to Whitelist");    
    alib.dom.styleSet(btnAddWhitelist, "marginLeft", "5px");
    btnAddWhitelist.m_msFormData = this.msFormData;
    btnAddWhitelist.cls = this;
    btnAddWhitelist.onclick = function()
    {
        var args = new Array();
        args[args.length] = ['whitelist', this.m_msFormData.txtWhitelist.value];        
        args[args.length] = ['preference', "whitelist_from"];
                
        ajax = new CAjax('json');
        ajax.cls = this.cls;
        ajax.dlg = showDialog("Saving spam whitelist, please wait...");
        ajax.onload = function(ret)
        {
            // rebuild the spam for blacklist and whitelist
            this.cls.buildSpamList(ret);
            
            this.dlg.hide();
            ALib.statusShowAlert("Spam Whitelist: " + ret.whitelist + " Saved!", 3000, "bottom", "right");
        };
        ajax.exec("/controller/Email/saveSpam", args);
        this.m_msFormData.txtWhitelist.value = "";
    }
    
    // Whitelist List - Select List
    this.msFormData.slctWhitelist = createInputAttribute(alib.dom.createElement("select"), null, "whitelistList", null, "290px");
    this.msFormData.slctWhitelist.size = 13;
    
    // append select list
    var tr = buildTdLabel(msTbody, this.msFormData.slctWhitelist.label);
    var td = alib.dom.createElement("td", tr);
    alib.dom.styleSetClass(td, "formValue");
    td.appendChild(this.msFormData.slctWhitelist);
    
    // append Remove Whitelist button
    alib.dom.createElement("br", this.msFormData.slctWhitelist.parentNode)
    var btnRemoveWhitelist = createInputAttribute(alib.dom.createElement("input", this.msFormData.slctWhitelist.parentNode), "button", null, null, null, "Remove Selected Address");    
    btnRemoveWhitelist.m_msFormData = this.msFormData;
    btnRemoveWhitelist.cls = this;
    btnRemoveWhitelist.onclick = function()
    {
        var selectedIndex = this.m_msFormData.slctWhitelist.selectedIndex;
        if(selectedIndex==-1)
            return;
        
        var args = new Array();
        args[args.length] = ['spamId', this.m_msFormData.slctWhitelist.value];
        args[args.length] = ['whitelist', this.m_msFormData.slctWhitelist[selectedIndex].text];
        
        ajax = new CAjax('json');
        ajax.dlg = showDialog("Deleting spam whitelist, please wait...");
        ajax.onload = function(ret)
        {
            this.dlg.hide();
            ALib.statusShowAlert("Spam Whitelist: " + ret.whitelist + " has been Deleted!", 3000, "bottom", "right");
        };
        ajax.exec("/controller/Email/deleteSpam", args);
        
        this.m_msFormData.slctWhitelist.remove(selectedIndex);
    }
    
    // Whitelist Info - div
    var div = this.divLeftHeader(con, "Whitelist Information", "300px");
    var label = alib.dom.createElement("p", div);
    label.innerHTML = "A whitelist entry can be added or removed using the form to the left. Whitelists are the oposite of blacklists - they tell the server to accept any mail from a given address no matter what the content is. Enter a whitelist here to make sure the address will never be put in the spam directory. You may enter either the full email address or use the * wildcard. *@domain.com will allow all users from domain.com username@domain.com would only allow username from domain.com. *domain* will allow any address with the word 'domain' in the address.";
    alib.dom.styleSet(label, "textAlign", "justify");
    alib.dom.styleSet(label, "lineHeight", "20px");
    
    // build spam for blacklist and whitelist
    this.buildSpamList();
    
    divClear(con);    
}    
    
/*************************************************************************
*    Function:    buildSpamList
* 
*    Purpose:    Build Spam Blacklist and Whitelist
**************************************************************************/
Plugin_Messages_Settings.prototype.buildSpamList = function(spamData)
{
    if(!this.msSaveData)
        return;
             
    if(spamData)
    {
        if(spamData.preference=="blacklist_from")
        {
            this.msSaveData.blacklist_from[spamData.blacklist] = new Object();
            this.msSaveData.blacklist_from[spamData.blacklist].id = spamData.spamId;
            this.msSaveData.blacklist_from[spamData.blacklist].value = spamData.blacklist;
            this.msSaveData.blacklist_from[spamData.blacklist].preference = spamData.preference;
            
            // remove whitelist same entry
            if(this.msSaveData.whitelist_from[spamData.blacklist])
                delete this.msSaveData.whitelist_from[spamData.blacklist];            
        }
        else
        {
            this.msSaveData.whitelist_from[spamData.whitelist] = new Object();
            this.msSaveData.whitelist_from[spamData.whitelist].id = spamData.spamId;
            this.msSaveData.whitelist_from[spamData.whitelist].value = spamData.whitelist;
            this.msSaveData.whitelist_from[spamData.whitelist].preference = spamData.preference;
            
            // remove blacklist same entry
            if(this.msSaveData.blacklist_from[spamData.whitelist])
                delete this.msSaveData.blacklist_from[spamData.whitelist];
        }
    }
    
    // Blacklist Saved Data
    this.msFormData.slctBlacklist.options.length = 0;
    for(blackList in this.msSaveData.blacklist_from)
        this.msFormData.slctBlacklist[this.msFormData.slctBlacklist.length] = new Option(this.msSaveData.blacklist_from[blackList].value, this.msSaveData.blacklist_from[blackList].id);
    
    // Whitelist Saved Data    
    this.msFormData.slctWhitelist.options.length = 0;
    for(whitelist in this.msSaveData.whitelist_from)
        this.msFormData.slctWhitelist[this.msFormData.slctWhitelist.length] = new Option(this.msSaveData.whitelist_from[whitelist].value, this.msSaveData.whitelist_from[whitelist].id);
    
}

/*************************************************************************
*    Function:    buildRteInput
* 
*    Purpose:    Build Rich Textbox Editor Input
**************************************************************************/
Plugin_Messages_Settings.prototype.buildRte = function(div, id, value)
{    
    var formDataRte = new Object();
    
    formDataRte = createInputAttribute(alib.dom.createElement("input"), "hidden", id, null, null, value);
    this.rteSignature = new CRte(formDataRte);
    div.innerHTML = "";
    this.rteSignature.print(div, '100%', '100px', value);
    
    return formDataRte;
}

/*************************************************************************
*    Function:    inputFormClear
* 
*    Purpose:    Clear the input form
**************************************************************************/
Plugin_Messages_Settings.prototype.inputFormClear = function(inputForm)
{
    for(var input in inputForm)
    {        
        switch(inputForm[input].type)
        {
            case "button":
                break;
            case "radio":
            case "checkbox":
                inputForm[input].checked = false;
                break;
            case "select-one":
            case "text":
            default:
                inputForm[input].value = "";                     
                break;
        }
    }
}          

/*************************************************************************
*    Function:    divLeftHeader
*
*    Purpose:    creates a div element that will display a header on the left
**************************************************************************/
Plugin_Messages_Settings.prototype.divLeftHeader = function(con, label, width, labelId)
{
    var div = alib.dom.createElement("div", con);
    alib.dom.styleSet(div, "float", "left");
    alib.dom.styleSet(div, "width", width);
    
    var headerLabel = alib.dom.createElement("p", div);
    alib.dom.styleSet(headerLabel, "fontWeight", "bold");
    
    if(label)
        headerLabel.innerHTML = label;
    
    if(labelId)
        headerLabel.id = labelId;
    
    return div;
}
