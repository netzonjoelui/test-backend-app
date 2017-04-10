/**
* @fileoverview This sub-loader will load email message compose window
*
* @author	joe, sky.stebnicki@aereus.com
* 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
*/

/**
* Creates an instance of AntObjectLoader_EmailMessageCmp.
*
* @constructor
* @param {CAntObject} obj Handle to object that is being viewed or edited
* @param {AntObjectLoader} loader Handle to base loader class
*/
function AntObjectLoader_EmailMessageCmp(obj, loader)
{
    this.mainObject = obj;
    this.loaderCls = loader;
    this.mainConMin = null; // Minified div for collapsed view
    this.formCon = null; // inner container where form will be printed
	this.infoCon = null; // This is where from, to, accounts, and everything but the body is printed
    this.ctbl = null; // Content table used for frame when printed inline
    this.toolbar = null;
    this.plugins = new Array();
    this.printOuterTable = true; // Can be used to exclude outer content table (usually used for preview)    
    this.headerHeight = 90;
    
    this.emailArgs = new Array();
    this.emailFormData = new Object();     // Date and Time form data    
    this.attachmentUploading = false; // Flag when an attachment is uploading
    this.attachedFiles = new Array();
    this.emailAttachments = new Array();
    
    this.emailAccounts = new Object();    
    this.emailAddress = new Object();
    this.messageDetails = new Array();
    this.userId = null; 
    this.messageId = null; 
    this.inReplyTo = null;
	this.sendTo = null;
    this.mid = this.mainObject.id; 
    this.tid = null;
    this.fid = null; 
    this.replyType = null; 
    this.replyMid = null; 
	this.rteBody = null;
    
	/*
    var functCls = this;
    window.onresize = function()
    {
        functCls.resize();
    }
	*/
}

/**
* Refresh the form
*/
AntObjectLoader_EmailMessageCmp.prototype.refresh = function()
{
}

/**
* Enable to disable edit mode for this loader - does nothing
*
* @param {bool} setmode True for edit mode, false for read mode
*/
AntObjectLoader_EmailMessageCmp.prototype.toggleEdit = function(setmode)
{
}

/**
* Print form on 'con'
*
* @param {DOMElement} con A dom container where the form will be printed
* @param {array} plugis List of plugins that have been loaded for this form
*/
AntObjectLoader_EmailMessageCmp.prototype.print = function(con, plugins)
{
    this.isPopup = (this.loaderCls.isPopup) ? true : false;
    
    this.formCon = con;
    
    // set Email Args
    this.replyMid = (this.emailArgs['replyMid']) ? this.emailArgs['replyMid'] : null;
    this.replyType = (this.emailArgs['replyType']) ? this.emailArgs['replyType'] : null;
    this.mid = (this.emailArgs['mid']) ? this.emailArgs['mid'] : null;
    
    if(this.mainObject.getValue("fid"))
        this.fid = this.mainObject.getValue("fid");
    else
        this.fid = (this.emailArgs['fid']) ? this.emailArgs['fid'] : null;

    var args = new Array();
    args[args.length] = ['reply_mid', this.replyMid];
    args[args.length] = ['reply_type', this.replyType];
    args[args.length] = ['mid', this.mid];
    args[args.length] = ['fid', this.fid];
    args[args.length] = ['objects', this.emailArgs['objects']];
    args[args.length] = ['all_selected', this.emailArgs['allSelected']];
    args[args.length] = ['obj_type', this.emailArgs['objType']];
    args[args.length] = ['send_method', this.emailArgs['sendMethod']];
    args[args.length] = ['using', this.emailArgs['using']];
    
    ajax = new CAjax('json');
    ajax.cls = this;    
    ajax.onload = function(ret)
    {
        if(ret)
        {
            this.cls.userId = ret['userId'];
            this.cls.messageId = ret['messageId'];
            this.cls.messageDetails = ret['messageDetails'];
            this.cls.emailAccounts = ret['emailAccounts'];            
            this.cls.emailAddress = ret['emailAddress'];
            this.cls.inReplyTo = this.cls.messageDetails.in_reply_to;
            this.cls.sendTo = this.cls.messageDetails.send_to;
			this.cls.tid = this.cls.messageDetails.tid;
        }

		if (!this.cls.messageDetails['cmp_to'] && this.cls.mainObject.getValue("send_to"))
			this.cls.messageDetails['cmp_to'] = this.cls.mainObject.getValue("send_to");

		if (!this.cls.messageDetails['subject'] && this.cls.mainObject.getValue("subject"))
			this.cls.messageDetails['subject'] = this.cls.mainObject.getValue("subject");

		if (!this.cls.messageDetails['body'] && this.cls.mainObject.getValue("body"))
			this.cls.messageDetails['body'] = this.cls.mainObject.getValue("body");

    	this.cls.buildInterface();
    };    
    ajax.exec("/controller/Email/getEmailDetails", args);
}

/**
* Callback is fired any time a value changes for the mainObject 
*/
AntObjectLoader_EmailMessageCmp.prototype.onValueChange = function(name, value, valueName)
{	
}

/**
* Callback function used to notify the parent loader if the name of this object has changed
*/
AntObjectLoader_EmailMessageCmp.prototype.onNameChange = function(name)
{
}

/**
* Callback is fired any time a value changes for the mainObject 
*
* @this {AntObjectLoader_EmailMessageCmp}
* @private
*/
AntObjectLoader_EmailMessageCmp.prototype.buildInterface = function()
{	
	this.loaderCls.setTitle("Compose Email Message");

    var tb = new CToolbar();
    this.toolbar = tb;
    if (this.mainObject.security.edit)
    {
        var saveText = "Save Draft";
        if(!this.fid)
        {
            var btn = new CButton("Send", 
                                    function(cls)
                                    { 
                                        cls.sendEmail();
                                    }, [this], "b2");
                                    tb.AddItem(btn.getButton(), "left");
        }
        else
            saveText = "Save Template";
        

        var btn = new CButton(saveText, 
                                function(cls)
                                { 
                                    cls.saveEmail();
                                }, [this], "b1");
                                tb.AddItem(btn.getButton(), "left");
    }

    var btn = new CButton("Add CC", 
                function(cls)
                {
                    alib.dom.styleSet(cls.emailFormData.txtCc.parentNode.parentNode, "display", "table-row");                    
                    cls.resize();
                }, [this], "b1");
                tb.AddItem(btn.getButton(), "left");
    
    var btn = new CButton("Add BCC", 
                function(cls)
                {
                    alib.dom.styleSet(cls.emailFormData.txtBcc.parentNode.parentNode, "display", "table-row");                    
                    cls.resize();
                }, [this], "b1");
                tb.AddItem(btn.getButton(), "left");
    
    var btn = new CButton("Discard", function(cls) { cls.close(); }, [this.loaderCls], "b3");
    if (this.loaderCls.fEnableClose && !this.loaderCls.isMobile)
        tb.AddItem(btn.getButton(), "left");

	/*
    var btn = new CButton("Check Spelling", 
                function(cls)
                {
                    spellCheck('composeForm', 'composeBody');
                }, [this], "b1");
                tb.AddItem(btn.getButton(), "left");
	*/

	/*
    var btn = new CButton("Video Email", 
                        function(cls)
                        {                            
                            var wiz = new CVideoWizard(cls.userId); 
                            wiz.onFinished = function(mid, message)
                            {
                                var mid = cls.mid
                                
                                if (message)
                                    message = message.replace(/\n/g, "<br />");
                                    
                                insertHtml(message + "<br /><br /><a href=\"http://"+document.domain+"/videomail/"+mid+"\"><img src=\"http://"+document.domain+"/images/public/vmailbuttons/watch_video_button.jpg\" border=\"0\" /></a><br /><br />Can't see the image above? <a href=\"http://"+document.domain+"/videomail/"+mid+"\">Click here to view your message</a>")
                            }

                            wiz.showDialog();
                            
                        }, [this], "b1");
    tb.AddItem(btn.getButton(), "left");
	*/

	// Apply a template to the current document
    var btn = alib.ui.Button("Use Template", {
		className:"b1", cls:this,
		onclick:function() {
			var antBrowser = new AntObjectBrowser("html_template");
			antBrowser.cbData.clsRef = this.cls;
			antBrowser.onSelect = function(objId, objLabel) {
				var obj = new CAntObject("html_template", objId);
				obj.cbData.clsRef = this.cbData.clsRef;
				obj.onload = function()
				{
					this.cbData.clsRef.rteBody.setValue(this.getValue("body_html"));
				}
				obj.load();
			}
			antBrowser.displaySelect();
		}
	});                            
    tb.AddItem(btn.getButton(), "left");

    if (this.mid)
	{
        if (this.mainObject.security.del)
   		{
            var btn = new CButton("Delete", function(cls, mid){ cls.deleteObject(mid); }, [this, this.mid], "b1");
            tb.AddItem(btn.getButton(), "left");
        }
    }
    tb.print(this.loaderCls.toolbarCon);

    // Set subject/title bar
    this.onNameChange(this.mainObject.getLabel());

    // Set ANT View title
    if (this.loaderCls.antView)
        this.loaderCls.antView.setTitle(this.mainObject.getLabel());

    this.infoCon = alib.dom.createElement("div", this.formCon);
    this.infoCon.id = "infoCon";
    
    this.emailHeaderCon = alib.dom.createElement("div", this.infoCon);
    this.emailHeaderCon.id = "emailHeaderCon";
    
    this.emailCon = alib.dom.createElement("div", this.emailHeaderCon);
    this.emailCon.id = "emailCon";
    
    // From Input
    var defaultSig = "";
    if(this.emailAccounts[0].num > 1)
    {
        this.emailFormData.txtFrom = createInputAttribute(alib.dom.createElement("select"), null, "use_account", "From", "600px");        
        for(account in this.emailAccounts)
        {
            var currentAccount = this.emailAccounts[account];            
            var selected = false;
            var hasSelected = false;
            
            if(currentAccount.id <= 0)
                continue;
            
            if(!hasSelected)
            {
                if(this.sendTo)
                {
                    if(this.sendTo.indexOf(currentAccount.email_address) >= 0)
                        selected = true;
                        hasSelected = true;
                }
                else
                {
                    if(currentAccount.f_default)
                        selected = true;
                }
            }
            
            if(selected)
                defaultSig = currentAccount.signature;
            
            this.emailFormData.txtFrom[this.emailFormData.txtFrom.length] = new Option(currentAccount.name + " (" + currentAccount.email_address+ ")", 
																					   currentAccount.id, false, selected);
        }
        
        this.emailFormData.txtFrom.cls = this;
        this.emailFormData.txtFrom.onchange = function()
        {
            var currentAccount = this.cls.emailAccounts[this.value];
            var accountSig = "";
            
            var iframe = document.getElementById('CRteIframe');
            var innerDoc = iframe.contentDocument || iframe.contentWindow.document;
            
            innerDoc.getElementById('accountSignature').innerHTML = currentAccount.signature;
        }
    }
    else
    {
        for(account in this.emailAccounts)
        {            
            var currentAccount = this.emailAccounts[account];            
            
            this.emailFormData.txtFrom = createInputAttribute(alib.dom.createElement("input"), "hidden", "use_account", null, "600px", currentAccount.id);
            defaultSig = currentAccount.signature;
                
            break;
        }            
    }
    
    
    var ccAddress = ccAddress = this.messageDetails['cmp_cc'];;
    var bccAddress = bccAddress = this.messageDetails['cmp_bcc'];
    
    if(this.emailArgs['sendMethod']==1) // bulk
    {
        this.emailFormData.txtTo = createInputAttribute(alib.dom.createElement("label"), null, null, "To");
        this.emailFormData.txtTo.inputLabel = "Bulk email will be sent to multiple recipients";
    }
    else // standard
    {
        var toAddress = "";
        if(this.messageDetails['cmp_to'])
            toAddress = this.messageDetails['cmp_to'];

        if(this.emailArgs['sendTo'] && !toAddress)
            toAddress = this.emailArgs['sendTo'];
        
        // To Input
        var txtToAttr = [["type", "text"], ["id", "cmp_to"], ["label", "To"], ["width", "600px"], ["value", toAddress], ["labelWidth", "50px"], ["floatDir", "Left"]];
        this.emailFormData.txtTo = setElementAttr(alib.dom.createElement("input"), txtToAttr);
		alib.dom.styleSetClass(this.emailFormData.txtTo, "fancy");
        
        switch(this.emailArgs['inpField'])
        {
            case 'cmp_cc':
                ccAddress = this.emailAddress.join(", ");                
                break;
            case 'cmp_bcc':
                bccAddress = this.emailAddress.join(", ");
                break;
        }
    }
    
    // CC Input
    this.emailFormData.txtCc = createInputAttribute(alib.dom.createElement("input"), "text", "cmp_cc", "CC", "600px", ccAddress, "50px", "Left");
	alib.dom.styleSetClass(this.emailFormData.txtCc, "fancy");
    
    // BCC Input
    this.emailFormData.txtBcc = createInputAttribute(alib.dom.createElement("input"), "text", "cmp_bcc", "BCC", "600px", bccAddress, "50px", "Left");
	alib.dom.styleSetClass(this.emailFormData.txtBcc, "fancy");
    
    // Subject Input
    this.emailFormData.txtSubject = createInputAttribute(alib.dom.createElement("input"), "text", "cmp_subject", "Subject", "600px", this.messageDetails['subject'], "50px", "Left");
    this.emailFormData.txtSubject.label = "Subject";
    this.emailFormData.txtSubject.floatDir = "Left";
	alib.dom.styleSetClass(this.emailFormData.txtSubject, "fancy");

    // build the input form
    buildFormInput(this.emailFormData, this.emailCon, true);
            
    if(typeof bccAddress == "undefined")
        alib.dom.styleSet(this.emailFormData.txtBcc.parentNode.parentNode, "display", "none");
    
    if(typeof ccAddress == "undefined")
        alib.dom.styleSet(this.emailFormData.txtCc.parentNode.parentNode, "display", "none");        
    
    var autoComplete = new alib.ui.AutoComplete(this.emailFormData.txtTo, {url: "/controller/Contact/getUserContactsEmail"});
    var autoCompleteCc = new alib.ui.AutoComplete(this.emailFormData.txtCc, {url: "/controller/Contact/getUserContactsEmail"});
    var autoCompleteBcc = new alib.ui.AutoComplete(this.emailFormData.txtBcc, {url: "/controller/Contact/getUserContactsEmail"});
    
    // Build Attachments
    var divAttachment = alib.dom.createElement("div", this.emailCon);
    var divButton = alib.dom.createElement("div", divAttachment);
    var divResult = alib.dom.createElement("div", divAttachment);

	var uploader = new AntFsUpload('%tmp%', this.m_dlg);
	uploader.cbData.cls = this;

	uploader.onRemoveUpload = function (fid) 
    {
        this.cbData.cls.attachedFiles = new Array();
        
        for(file in this.m_uploadedFiles)
        {
            var currentFile = this.m_uploadedFiles[file];
            var ind = this.cbData.cls.attachedFiles.length;
            var fileId = currentFile['id'];
            
            if(fileId !== fid)
                this.cbData.cls.attachedFiles[ind] = fileId;
        }
        this.cbData.cls.resize();
    }

    uploader.onUploadStarted = function () 
    { 
        this.cbData.cls.attachmentUploading = true;        
        this.cbData.cls.resize();
    }

    uploader.onQueueComplete = function () 
    { 
        this.cbData.cls.attachmentUploading = false;        
        this.cbData.cls.attachedFiles = new Array();
        
        for(file in this.m_uploadedFiles)
        {
            var currentFile = this.m_uploadedFiles[file];
            var ind = this.cbData.cls.attachedFiles.length;
            
            this.cbData.cls.attachedFiles[ind] = currentFile['id'];
        }        
    }

    uploader.showTmpUpload(divButton, divResult, 'Add Attachment');

    var attachmentDiv = alib.dom.createElement("div", this.emailCon);
    attachmentDiv.id = "attachments";
    
    // attachment
    if(this.messageDetails['attachment'])
    {
        for(attachment in this.messageDetails['attachment'])
        {   
            var currentAttachment = this.messageDetails['attachment'][attachment];
            var currentDiv = alib.dom.createElement("div", attachmentDiv);
            
            var attachmentId = currentAttachment.value;
            var attachmentName = currentAttachment.name;
                                                  
            this.emailAttachments[attachmentId] = createInputAttribute(alib.dom.createElement("input", currentDiv), "checkbox", "attachment_" + attachmentId, null, null, currentAttachment.value);
            
            
            if(this.replyType == "forward" || this.replyType == "draft")
                this.emailAttachments[attachmentId].checked = true;
            
            var labelAttachment = alib.dom.createElement("label", currentDiv);
            labelAttachment.innerHTML = " " + attachmentName;            
        }
    }
    
    // Print email body
    
    // set the rich textbox        
    var divBodyRte = alib.dom.createElement("div", this.infoCon);
    divBodyRte.id = "divBodyRte";
    //alib.dom.styleSet(divBodyRte, "width", "98%");
    
    // Spell Form
    var divSpellForm = alib.dom.createElement("div", divBodyRte);
    this.createSpellForm(divSpellForm);
    
    // Form Compose    
    var rteForm = setElementAttr(alib.dom.createElement("form", divBodyRte), [["name", "composeForm"], ["autocomplete", "off"]]);
    rteForm.onsubmit = function(e)
    {        
        e.preventDefault();        
    }
    
    // Div RTE
    var divBody = alib.dom.createElement("div");
    if(!this.mid && defaultSig)
    {
        //alib.dom.createElement("br", divBody);
        //alib.dom.createElement("br", divBody);
        var divSignature = alib.dom.createElement("div", divBody);
        divSignature.innerHTML = "<br /><br />" + defaultSig;
        divSignature.id = "accountSignature";
    }
    
    // Body Message
    var divBodyMessage = alib.dom.createElement("div", divBody);
    
    if(this.messageDetails['body'])
        divBodyMessage.innerHTML = this.messageDetails['body'];
        
    // build Body rich textbox editor    
    this.emailFormData.txtBody = this.buildRte(divBodyRte, "composeBody", divBody.innerHTML);
    this.resize();
}

/*************************************************************************
*    Function:    buildRteInput
* 
*    Purpose:    Build Rich Textbox Editor Input
**************************************************************************/
AntObjectLoader_EmailMessageCmp.prototype.buildRte = function(div, id, value)
{
    formDataRte = createInputAttribute(alib.dom.createElement("input"), "hidden", id, null, null, value);    
    formDataRte.setAttribute("name", id);    

    div.innerHTML = "";
    var height = (alib.dom.getElementHeight(this.formCon) + alib.dom.getElementHeight(this.infoCon) - 10)+"px";
    
    var rte = alib.ui.Editor(formDataRte);
	rte.defaultBlockElement = ""; // No paragraphs when return is pressed
    rte.print(div, '100%', "100%", value);
	this.rteBody = rte;
    
    return formDataRte;
}

/*************************************************************************
*    Function:    createSpellForm
* 
*    Purpose:    Build Rich Textbox Editor Input
**************************************************************************/
AntObjectLoader_EmailMessageCmp.prototype.createSpellForm = function(divSpellForm)
{
    var spellForm = alib.dom.createElement("form", divSpellForm);
    spellForm.id = 'spell_form';
    spellForm.setAttribute("name", "spell_form");
    spellForm.setAttribute("method", "POST");
    spellForm.setAttribute("target", "spellWindow");
    spellForm.setAttribute("action", "/lib/spell/checkspelling.php");    
    
    var formName = alib.dom.createElement("input", spellForm);
    formName.setAttribute("name", "spell_formname");
    formName.setAttribute("type", "hidden");
    
    var formField = alib.dom.createElement("input", spellForm);
    formField.setAttribute("name", "spell_fieldname");
    formField.setAttribute("type", "hidden");
    
    var formString = alib.dom.createElement("input", spellForm);
    formString.setAttribute("name", "spellstring");
    formString.setAttribute("type", "hidden");
}

/*************************************************************************
*    Function:    showDialog
* 
*    Purpose:    Build Rich Textbox Editor Input
**************************************************************************/
AntObjectLoader_EmailMessageCmp.prototype.showDialog = function(message)
{
    var dlg = new CDialog();
    var dv_load = document.createElement('div');
    alib.dom.styleSetClass(dv_load, "statusAlert");
    alib.dom.styleSet(dv_load, "text-align", "center");
    dv_load.innerHTML = message;
    dlg.statusDialog(dv_load, 250, 100);
    
    return dlg;
}

/*************************************************************************
*    Function:    saveEmail
* 
*    Purpose:    Build Rich Textbox Editor Input
**************************************************************************/
AntObjectLoader_EmailMessageCmp.prototype.saveEmail = function()
{
    var emailAttachments = this.filterAttachments();
    var args = new Array();
    args[args.length] = ['message_id', this.messageId];
    args[args.length] = ['mid', this.mid];
    args[args.length] = ['tid', this.tid];
    args[args.length] = ['fid', this.fid];
    args[args.length] = ['cmp_to', this.emailFormData.txtTo.value];
    args[args.length] = ['cmp_subject', this.emailFormData.txtSubject.value];
    args[args.length] = ['cmp_cc', this.emailFormData.txtCc.value];
    args[args.length] = ['cmp_bcc', this.emailFormData.txtBcc.value];
    args[args.length] = ['cmpbody', this.emailFormData.txtBody.value];
    args[args.length] = ['in_reply_to', ""];
    args[args.length] = ['uploaded_file', this.attachedFiles];
    args[args.length] = ['email_attachments', emailAttachments];

    var dlg = this.showDialog("Saving email, please wait...");
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.dlg = dlg;
    ajax.onload = function(ret)
    {
        if(ret)
        {
            this.cls.mid = ret.mid;
            this.cls.tid = ret.tid;
            this.dlg.hide();
        }        
    };
    ajax.exec("/controller/Email/saveEmail", args);
}

/*************************************************************************
*    Function:    sendEmail
* 
*    Purpose:    Build Rich Textbox Editor Input
**************************************************************************/
AntObjectLoader_EmailMessageCmp.prototype.sendEmail = function()
{
    var emailAttachments = this.filterAttachments();
    var args = new Array();
    args[args.length] = ['message_id', this.messageId];
    args[args.length] = ['mid', this.mid];
    args[args.length] = ['use_account', this.emailFormData.txtFrom.value];
    args[args.length] = ['cmp_to', this.emailFormData.txtTo.value];
    args[args.length] = ['cmp_subject', this.emailFormData.txtSubject.value];
    args[args.length] = ['cmp_cc', this.emailFormData.txtCc.value];
    args[args.length] = ['cmp_bcc', this.emailFormData.txtBcc.value];
    args[args.length] = ['cmpbody', this.emailFormData.txtBody.value];
    args[args.length] = ['uploaded_file', this.attachedFiles];
    args[args.length] = ['email_attachments', emailAttachments];
    args[args.length] = ['in_reply_to', this.inReplyTo];
    
    args[args.length] = ['objects', this.emailArgs['objects']];
    args[args.length] = ['all_selected', this.emailArgs['allSelected']];
    args[args.length] = ['obj_type', this.emailArgs['objType']];
    args[args.length] = ['send_method', this.emailArgs['sendMethod']];
    args[args.length] = ['using', this.emailArgs['using']];
    
    var dlg = this.showDialog("Sending email, please wait...");

    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.dlg = dlg;
    ajax.onload = function(ret)
    {
        this.dlg.hide();
        this.cls.loaderCls.close();
    };
    ajax.exec("/controller/Email/sendEmail", args);
}

/*************************************************************************
*    Function:    filterAttachments
* 
*    Purpose:    filter the email attachments
**************************************************************************/
AntObjectLoader_EmailMessageCmp.prototype.filterAttachments = function()
{
    var filteredAttachments = "";
    for(attachment in this.emailAttachments)
    {
        var currentAttachment = this.emailAttachments[attachment];
        
        if(currentAttachment.checked)
        {
            if(filteredAttachments.length > 0)
                filteredAttachments += ",";
                
            filteredAttachments += attachment;
        }
    }
    
    return filteredAttachments;
}

/*************************************************************************
*    Function:    resize
* 
*    Purpose:    Resizes the body messsage
**************************************************************************/
AntObjectLoader_EmailMessageCmp.prototype.resize = function()
{    
    var popupHeight = 0;
    if(this.isPopup)
        popupHeight = 100;

    var iframe = document.getElementById('CRteIframe');
    
    var emailConHeight = alib.dom.getContentHeight(this.emailCon);    
    var headerHeights = getHeaderHeights();    
    var height = (getWorkspaceHeight()-(emailConHeight + headerHeights.totalHeaderHeight + popupHeight));
    alib.dom.styleSet(iframe, "height", height + "px");
    
    var infoConHeight = alib.dom.getContentHeight(this.infoCon);
    var height = infoConHeight + headerHeights.totalHeaderHeight + popupHeight;
	//var height = getWorkspaceHeight();
	//alert(height);
    //alib.dom.styleSet(this.formCon, "height", height + "px");
}
