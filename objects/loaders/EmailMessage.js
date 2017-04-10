/**
 * @fileoverview This sub-loader will load email messages for ant object laoder
 *
 * This class is also responsible for creating the compose window for new emails and
 * for editing drafts.
 *
 * @author	joe, sky.stebnicki@aereus.com
 * 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObjectLoader_EmailMessage.
 *
 * @constructor
 * @param {CAntObject} obj Handle to object that is being viewed or edited
 * @param {AntObjectLoader} loader Handle to base loader class
 */
function AntObjectLoader_EmailMessage(obj, loader)
{
	// If we are not loading an email message to view, then load the compose window
	if (!obj.id || obj.getValue("flag_draft") == true)
	{
		return new AntObjectLoader_EmailMessageCmp(obj, loader);
	}

	this.mainObject = obj;
	this.oid = this.mainObject.id;
	this.loaderCls = loader;
	this.outerCon = null; // Outer container
	this.messageBodyCon = null; // The container that will house the actual message body
	this.mainConMin = null; // Minified div for collapsed view
	this.mainCon = null; // Inside outcon and holds the outer table
    this.formCon = null; // inner container where form will be printed
	this.formPopupCon = null;
	this.ctbl = null; // Content table used for frame when printed inline
	this.toolbar = null;
	this.toolbarCon = null;
	this.bodyCon = null;
	this.bodyFormCon = null; // Displays the form
	this.bodyNoticeCon = null; // Right above the form and used for notices and inline duplicate detection
	this.plugins = new Array();
	this.printOuterTable = true; // Can be used to exclude outer content table (usually used for preview)
    this.emailHeaderData = null;
}

/**
 * Refresh the form
 */
AntObjectLoader_EmailMessage.prototype.refresh = function()
{
}

/**
 * Enable to disable edit mode for this loader
 *
 * @param {bool} setmode True for edit mode, false for read mode
 */
AntObjectLoader_EmailMessage.prototype.toggleEdit = function(setmode)
{
}

/**
 * Print form on 'con'
 *
 * @param {DOMElement} con A dom container where the form will be printed
 * @param {array} plugis List of plugins that have been loaded for this form
 */
AntObjectLoader_EmailMessage.prototype.print = function(con, plugins)
{
	if (this.loaderCls.fThreadView)
	{
		this.mainConMin = alib.dom.createElement("div", con);
		alib.dom.styleSet(this.mainConMin, "display", "none");
		alib.dom.styleAddClass(this.mainConMin, "emailThreadMessageCol");
	}

	this.isPopup = (this.loaderCls.isPopup) ? true : false;
	this.outerCon = con;
	this.mainCon = alib.dom.createElement("div", con);
	this.formCon = this.mainCon;

	if (this.loaderCls.fThreadView)
	{
		alib.dom.styleAddClass(this.mainCon, "emailThreadMessageExp");
	}

	this.toolbarCon = alib.dom.createElement("div", this.formCon);
	if (this.isPopup)
		alib.dom.styleSetClass(this.toolbarCon, "popup_toolbar");

	var outer_dv = alib.dom.createElement("div", this.formCon);
	if (this.isPopup)
		outer_dv.setAttribute("id", "bdy_outer");

	this.bodyCon = alib.dom.createElement("div", outer_dv);
	if (this.isPopup)
	{
		this.bodyCon.setAttribute("id", "bdy");
		alib.dom.styleSetClass(this.bodyCon, "popup_body");
	}
	else
		alib.dom.styleSet(this.bodyCon, "margin-top", "5px");
	
	// Notice container
	this.bodyNoticeCon = alib.dom.createElement("div", this.bodyCon);

	// Body container
    this.bodyFormCon = alib.dom.createElement("div", this.bodyCon);
	
    // Con for popup form
    this.formPopupCon = alib.dom.createElement("div", this.bodyCon);

	this.buildInterface();
}

/**
 * Print subloader in collapsed mode. Only if subloader class has a method called printCollapsed.
 *
 * @this {AntObjectLoader_EmailMessage}
 * @param {DOMElement} con The container to print this object loader into - usually a div
 * @param {bool} popup Set to true if we are operating in a new window popup. Hides "Open In New Window" link.
 * @param {Object} data Properties to forward to collapsed view
 * @public
 */
AntObjectLoader_EmailMessage.prototype.printCollapsed = function(con, popup, data)
{    
    if(data)
        this.emailHeaderData = data;
        
	var outerCon = alib.dom.createElement("div", con);
	var colCon = alib.dom.createElement("div", outerCon);
	alib.dom.styleAddClass(colCon, "emailThreadMessageCol");
	colCon.emvCls = this;
	colCon.con = outerCon;
	colCon.popup = popup;
	colCon.onclick = function()
	{
		this.con.innerHTML = "";
		this.emvCls.print(this.con);
	}
	/*var htm = "<div style='float:right;'>"+data.message_date+"</div>"; 
	htm += "<img src='/images/icons/email-add_16.png' /> ";
	htm += data.from;
	htm += " - ";
	htm += data.subject;*/
	colCon.innerHTML = this.emailHeaderCollapse();
}

/**
 * Callback is fired any time a value changes for the mainObject 
 */
AntObjectLoader_EmailMessage.prototype.onValueChange = function(name, value, valueName)
{	
}

/**
 * Callback function used to notify the parent loader if the name of this object has changed
 */
AntObjectLoader_EmailMessage.prototype.onNameChange = function(name)
{
}

/**
 * Displays the header div for email thread - collapse view
 *
 * @this {AntObjectLoader_EmailMessage}
 * @private
 */
AntObjectLoader_EmailMessage.prototype.emailHeaderCollapse = function()
{
    var htm = "";
    if(this.emailHeaderData)
    {
        htm = "<div style='float:right;'>"+this.emailHeaderData.message_date+"</div>"; 
        htm += "<img src='/images/icons/email-add_16.png' /> ";
        htm += this.emailHeaderData.from;
        htm += " - ";
        htm += this.emailHeaderData.subject;
    }
    else
    {            
        htm = "<div style='float:right;'>"+this.mainObject.getValue("message_date")+"</div>"; 
        htm += "<img src='/images/icons/email-add_16.png' /> ";
        htm += this.mainObject.getValue("sent_from");
        htm += " - ";
        htm += this.mainObject.getValue("subject");
    }
    
    return htm;
}

/**
 * Displays the header div for email thread - expand view
 *
 * @this {AntObjectLoader_EmailMessage}
 * @private
 */
AntObjectLoader_EmailMessage.prototype.emailHeaderExpand = function()
{
    var htm = "";
    if(this.emailHeaderData)
    {
        htm = "<div style='float:right;'>"+this.emailHeaderData.message_date+"</div>";         
        htm += this.emailHeaderData.from;
        htm += " - ";
        htm += this.emailHeaderData.subject;
    }
    else
    {            
        htm = "<div style='float:right;'>"+this.mainObject.getValue("message_date")+"</div>";         
        htm += this.mainObject.getValue("sent_from");
        htm += " - ";
        htm += this.mainObject.getValue("subject");
    }
    
    return htm;
        
}

/**
 * Callback is fired any time a value changes for the mainObject 
 *
 * @this {AntObjectLoader_EmailMessage}
 * @private
 */
AntObjectLoader_EmailMessage.prototype.buildInterface = function()
{	
	if (this.loaderCls.fThreadView)
	{
		// Build collapsed View
		// -----------------------------------
		this.mainConMin.emvCls = this;
		this.mainConMin.onclick = function()
		{
			this.emvCls.expandView();
		}
        
        this.mainConMin.innerHTML = this.emailHeaderCollapse();

		// Build header and toolbar for expanded view
		// -----------------------------------
		var tbl = alib.dom.createElement("table", this.toolbarCon);
		var tbody = alib.dom.createElement("tbody", tbl);
		alib.dom.styleSet(tbl, "width", "100%");
		alib.dom.styleSet(tbl, "table-layout", "fixed");

		var row = alib.dom.createElement("tr", tbody);
		var td = alib.dom.createElement("td", row);
		alib.dom.styleSet(td, "width", "50px");
		td.rowSpan = 2;
		td.innerHTML = "<img src='/images/icons/mail_48_short.png' />";
		this.getImage(td);
		var td = alib.dom.createElement("td", row);
        alib.dom.styleSet(td, "width", "100%");
		var fromsub = alib.dom.createElement("div", td);
        alib.dom.styleSet(fromsub, "cursor", "pointer");
		fromsub.appCls = this;
		fromsub.onclick = function() { this.appCls.collapseView(); }
		fromsub.innerHTML = this.emailHeaderExpand();
		var infocon = alib.dom.createElement("div", td);
		alib.dom.styleSet(infocon, "display", "none");
        
        var groupType = this.mainObject.getValueName("mailbox_id");
        
		infocon.innerHTML = "<table>"
						  + "<tr><td>from:</td><td>"+this.mainObject.getValue("sent_from").escapeHTML()+"</td></tr>"
						  + "<tr><td>to:</td><td>"+this.mainObject.getValue("send_to").escapeHTML()+"</td></tr>"
						  + "<tr><td>group:</td><td>"+groupType+"</td></tr>"
						  + "<tr><td>more:</td><td>"
						  + "<a href='/email/message_view_original.awp?mid="+this.oid+"' target='_blank'>original message</a></td></tr>"
						  + "</table>";
		//+ "<tr><td>more:</td><td><a href='/email/message_view_header.awp?mid="+this.oid+"' target='_blank'>full header</a> "

		// Time for the toolbar
		var row = alib.dom.createElement("tr", tbody);
		var td = alib.dom.createElement("td", row);
        
        if(groupType == "Drafts") // email is a draft (saved email)
        {
            var btn = new CButton("Edit", function(cls){ emailComposeOpen(null, [["mid", cls.oid], ["reply_type", 'draft']]); }, [this], "b2 small");
            btn.print(td);
        }
        else
        {
            td.colSpan = 2;
            
            var btn = new CButton("Reply", function(cls){ emailComposeOpen(null, [["reply_mid", cls.oid], ["reply_type", 'reply']]); }, [this], "b2 small grLeft");
            btn.print(td);

            var btn = new CButton("Reply All", function(cls){ emailComposeOpen(null, [["reply_mid", cls.oid], ["reply_type", 'reply_all']]); }, [this], "b1 small grCenter");
            btn.print(td);


            var btn = new CButton("Forward", function(cls){ emailComposeOpen(null, [["reply_mid", cls.oid], ["reply_type", 'forward']]); }, [this], "b1 small grRight");
            btn.print(td);
        }

		var dm_actions = new CDropdownMenu();

		// Create New
		var dm_act = dm_actions.addSubmenu("Create New", null, null, null);
		var dm_sub = dm_act.addEntry("Calendar Event", function(cls){ cls.createObject("calendar_event"); }, "/images/icons/circle_blue.png", null, [this]);
        var dm_sub = dm_act.addEntry("Task", function(cls){ cls.createObject("task"); }, "/images/icons/circle_blue.png", null, [this]);
		var dm_sub = dm_act.addEntry("Case", function(cls){ cls.createObject("case"); }, "/images/icons/circle_blue.png", null, [this]);
		var dm_sub = dm_act.addEntry("Personal Contact", function(cls){ cls.createObject("contact_personal"); }, "/images/icons/circle_blue.png", null, [this]);
		var dm_sub = dm_act.addEntry("Customer", function(cls){ cls.createObject("customer"); }, "/images/icons/circle_blue.png", null, [this]);
		var dm_sub = dm_act.addEntry("Lead", function(cls){ cls.createObject("lead"); }, "/images/icons/circle_blue.png", null, [this]);
		var dm_sub = dm_act.addEntry("Note", function(cls){ cls.createObject("note"); }, "/images/icons/circle_blue.png", null, [this]);
		// Associate With
		var dm_act = dm_actions.addSubmenu("Associate With", null, null, null);
		var dm_sub = dm_act.addEntry("Customer", function(cls){ emailAssocObj(cls.oid, "customer"); }, "/images/icons/circle_blue.png", null, [this]);
		var dm_sub = dm_act.addEntry("Opportunity", function(cls){ emailAssocObj(cls.oid, "opportunity"); }, "/images/icons/circle_blue.png", null, [this]);
		var dm_sub = dm_act.addEntry("Lead", function(cls){ emailAssocObj(cls.oid, "lead"); }, "/images/icons/circle_blue.png", null, [this]);
		var dm_sub = dm_act.addEntry("Project", function(cls){ emailAssocObj(cls.oid, "project"); }, "/images/icons/circle_blue.png", null, [this]);
		var dm_sub = dm_act.addEntry("Task", function(cls){ emailAssocObj(cls.oid, "task"); }, "/images/icons/circle_blue.png", null, [this]);
		var dm_sub = dm_act.addEntry("Case", function(cls){ emailAssocObj(cls.oid, "case"); }, "/images/icons/circle_blue.png", null, [this]);

		td.appendChild(dm_actions.createButtonMenu("Actions",null,null,"b1 small"));

		var btn = new CButton("Print", function(cls){ window.open("/print/engine.php?obj_type=email_message&&format=html&objects[]="+cls.oid); }, [this], "b1 small");
		btn.print(td);

		var btn = new CButton("More Info", function(cls, con) { if (con.style.display=="block") con.style.display = "none"; else con.style.display = "block"; }, [this, infocon], "b1 small");
		btn.print(td);
		//var btn = new CButton("Collapse", function(cls) { cls.collapseView(); }, [this], "b1 small grRight");
		//btn.print(td);
	}
	else
	{
		if (this.isPopup) 
			document.title = "View Message";
		else if (this.ctbl)
			this.ctbl.setTitle("View Message");

		var tb = new CToolbar();
		this.toolbar = tb;
		var btn = new CButton("Close", function(cls) { cls.close(); }, [this.loaderCls], "b1");
		if (this.loaderCls.fEnableClose && !this.loaderCls.isMobile)
			tb.AddItem(btn.getButton(), "left");
		if (this.mainObject.security.edit)
		{
			var btn = new CButton("Reply", function(cls){ emailComposeOpen(null, [["reply_mid", cls.oid], ["reply_type", 'reply']]); }, [this], "b2");
			tb.AddItem(btn.getButton(), "left");

			var btn = new CButton("Reply All", function(cls){ emailComposeOpen(null, [["reply_mid", cls.oid], ["reply_type", 'reply_all']]); }, [this], "b1");
			tb.AddItem(btn.getButton(), "left");

			var btn = new CButton("Forward", function(cls){ emailComposeOpen(null, [["reply_mid", cls.oid], ["reply_type", 'forward']]); }, [this], "b1");
			tb.AddItem(btn.getButton(), "left");

			var btn = new CButton("Print", function(cls){ window.print(); }, [this], "b1");
			tb.AddItem(btn.getButton(), "left");

			// Create New
			var dm_act = new CDropdownMenu();
			var dm_sub = dm_act.addEntry("Calendar Event", function(cls){ cls.createObject("calendar_event"); }, "/images/icons/circle_blue.png", null, [this]);
			var dm_sub = dm_act.addEntry("Task", function(cls){ cls.createObject("task"); }, "/images/icons/circle_blue.png", null, [this]);
			var dm_sub = dm_act.addEntry("Case", function(cls){ cls.createObject("case"); }, "/images/icons/circle_blue.png", null, [this]);
			var dm_sub = dm_act.addEntry("Personal Contact", function(cls){ cls.createObject("contact_personal"); }, "/images/icons/circle_blue.png", null, [this]);
			var dm_sub = dm_act.addEntry("Customer", function(cls){ cls.createObject("customer"); }, "/images/icons/circle_blue.png", null, [this]);
			var dm_sub = dm_act.addEntry("Lead", function(cls){ cls.createObject("lead"); }, "/images/icons/circle_blue.png", null, [this]);
			var dm_sub = dm_act.addEntry("Note", function(cls){ cls.createObject("note"); }, "/images/icons/circle_blue.png", null, [this]);
			tb.AddItem(dm_act.createButtonMenu("Create New"));
			// Associate With
			var dm_act = new CDropdownMenu();
			var dm_sub = dm_act.addEntry("Customer", function(cls){ emailAssocCustomer(cls.oid); }, "/images/icons/circle_blue.png", null, [this]);
			tb.AddItem(dm_act.createButtonMenu("Associate With"));
		}

		if (this.oid)
		{
			if (this.mainObject.security.del)
			{
				var btn = new CButton("Delete", function(cls, oid){ cls.deleteObject(oid); }, [this, this.oid], "b3");
				tb.AddItem(btn.getButton(), "left");
			}
		}
		tb.print(this.loaderCls.toolbarCon);

		// Set subject/title bar
		this.onNameChange(this.mainObject.getLabel());

		// Set ANT View title
		if (this.loaderCls.antView)
			this.loaderCls.antView.setTitle(this.mainObject.getLabel());

		var infocon = alib.dom.createElement("div", this.toolbarCon);
		infocon.innerHTML = "<table>"
						  + "<tr><td>From:</td><td>"+this.mainObject.getValue("sent_from").escapeHTML()+"</td></tr>"
						  + "<tr><td>To:</td><td>"+this.mainObject.getValue("send_to").escapeHTML()+"</td></tr>"
						  + "<tr><td>Group:</td><td>"+this.mainObject.getValueName("mailbox_id")+"</td></tr>"
						  + "<tr><td>More:</td><td>"
						  + "	<a href='/email/message_view_original.awp?mid="+this.oid+"' target='_blank'>original message</a></td></tr>"
						  + "</table>";
	}

	this.bodyFormCon.innerHTML = "";

	// Associations
	var assocdv = alib.dom.createElement("div", this.bodyFormCon);
	alib.dom.styleSet(assocdv, "margin-left", "5px");
	this.getAssociations(assocdv);

	// Print email body
	this.messageBodyCon = alib.dom.createElement("div", this.bodyFormCon);
	alib.dom.styleAddClass(this.messageBodyCon, "emailMessageBody");
	alib.dom.styleAddClass(this.messageBodyCon, "formHtmlBody");
	this.getMessageBody();
	//bdiv.innerHTML = "<iframe src='/email/message_body.php?mid="+this.oid+"' id='message_body_"+this.oid+"' name='message_body_"+this.oid+"' "
							   //+ " style='height:20px;width:100%;border:0;' frameborder='0'></iframe>";
}

/**
 * Collapse this message (if in thread view mode)
 */
AntObjectLoader_EmailMessage.prototype.collapseView = function()
{
	this.mainCon.style.display = "none";
	this.mainConMin.style.display = "block";
}

/**
 * Expand this message (if collapsed and in thread view mode)
 */
AntObjectLoader_EmailMessage.prototype.expandView = function()
{
	this.mainCon.style.display = "block";
	this.mainConMin.style.display = "none";
}

/**
 * Craete an object from this message
 *
 * @param {string} tocreate Object type to create
 */
AntObjectLoader_EmailMessage.prototype.createObject = function(objectType)
{
	var ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.dlg = showDialog("please wait...");
	ajax.cbData.objectType = objectType;        
	ajax.onload = function(ret)
	{
        this.cbData.dlg.hide();
        
        if(!ret)
            return;
        
		if (ret.error)
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
        else
		{
			switch(this.cbData.objectType)
			{
			case 'case':
                args[args.length] = ["title", ret.subject];
				args[args.length] = ["description", ret.body_txt];
                args[args.length] = ["customer_id", ret.customer_id];
				break;
			case 'note':
                args[args.length] = ["name", ret.subject];
				args[args.length] = ["body", ret.body_txt];
				break;
            case "calendar_event":
            case "task":
                args[args.length] = ["name", ret.subject];                
                args[args.length] = ["obj_reference", "email_message:" + this.cbData.cls.oid];
			case 'contact_personal':
			case 'lead':
			case 'customer':
			default:                
				args[args.length] = ["notes", ret.body_txt];
				args[args.length] = ["first_name", ret.first_name];
				args[args.length] = ["last_name", ret.last_name];
				args[args.length] = ["email", ret.email];
				break;
			}
		}
        
        this.cbData.cls.popupForm(this.cbData.objectType, args);
	};
    var args = new Array();    
    args[args.length] = ['mid', this.oid];
    ajax.exec("/controller/Email/getConvFields", args);
}

/**
 * Get objects associated with this email
 *
 * @param {DOMElement} con The container to print the associations links into
 */
AntObjectLoader_EmailMessage.prototype.getAssociations = function(con)
{
	/*var funct = function(ret, cls, con)
	{
		if (ret)
		{
			try
			{				
				var buf = "";
				if (ret.length)
					buf += "Associations: ";
				for(association in ret)
				{
                    var currentAssociation = ret[association];
					var parts =  currentAssociation.obj_ref.split(":");
					buf += "<a href='javascript:void(0);' title='Click to open "+unescape(currentAssociation.label)+"' "
						+ "onclick=\"loadObjectForm('"+parts[0]+"', '"+parts[1]+"');\";\">"+currentAssociation.label+"</a> ";
				}
					con.innerHTML = buf;
			}
			catch(e)
			{
				//alert("Error loading associations" + e);
			}
		}
	}	
    var rpc = new CAjaxRpc("/controller/Email/getAssociations", "getAssociations", 
							[["mid", this.oid]], funct, [this, con], AJAX_POST, true, "json");*/
                            
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.con = con;
    ajax.onload = function(ret)
    {
        if (ret)
        {
            try
            {                
                var buf = "";
                if (ret.length)
                    buf += "Associations: ";
                for(association in ret)
                {
                    var currentAssociation = ret[association];
                    var parts =  currentAssociation.obj_ref.split(":");
                    buf += "<a href='javascript:void(0);' title='Click to open "+unescape(currentAssociation.label)+"' "
                        + "onclick=\"loadObjectForm('"+parts[0]+"', '"+parts[1]+"');\";\">"+currentAssociation.label+"</a> ";
                }
                    this.cbData.con.innerHTML = buf;
            }
            catch(e)
            {
                //alert("Error loading associations" + e);
            }
        }
    };
    ajax.exec("/controller/Email/getAssociations",
                [["mid", this.oid]]);
}


/**
 * Get image for the 'from' of this mesage
 *
 * @param {DOMElement} con The container to print the image into
 */
AntObjectLoader_EmailMessage.prototype.getImage = function(con)
{
	/*var funct = function(ret, cls, con)
	{
		if (!ret['error'] && ret != "-1")
		{			
			con.innerHTML = "<img src='/files/images/"+ret+"/48/48' />"
		}
	}	
    var rpc = new CAjaxRpc("/controller/Email/getEmailUserImage", "getEmailUserImage", 
							[["email", this.mainObject.getValue("sent_from")]], funct, [this, con], AJAX_POST, true, "json");*/
                            
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.con = con;
    ajax.onload = function(ret)
    {
        if (!ret['error'] && ret != "-1")
            this.cbData.con.innerHTML = "<img src='/files/images/"+ret+"/48/48' />"
    };
    ajax.exec("/controller/Email/getEmailUserImage",
                [["email", this.mainObject.getValue("sent_from")]]);
}

/**
 * Get and render the message body
 *
 * @param {string} tocreate Object type to create
 */
AntObjectLoader_EmailMessage.prototype.getMessageBody = function()
{
	this.messageBodyCon.innerHTML = "loading...";

	/*
	var ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.onload = function(msg)
	{
		
	};
	ajax.exec("/controller/Email/getMessageBody", [["mid", this.oid]]);
	*/


	// Poll the server until we get data or timeout
	var xhr = new alib.net.Xhr();

	// Retrieve results
	alib.events.listen(xhr, "load", function(evt) { 

		var reps = this.getResponse();

		evt.data.msgcls.messageBodyCon.innerHTML = "";
		if (reps.body || typeof reps.body == "object")
		{
			// Add body
			// ------------------------------------------------
			var msgPart = alib.dom.createElement("div", evt.data.msgcls.messageBodyCon);
            
            if(typeof reps.body == "object")
                msgPart.innerHTML = reps.cleanBody;
            else
			    msgPart.innerHTML = reps.body;
            
			// Add attachments
			// ------------------------------------------------
			if (reps.attachments.length)
			{
				var attPart = alib.dom.createElement("div", evt.data.msgcls.messageBodyCon);
				attPart.innerHTML = "<br /><br />";

				var tbl = alib.dom.createElement("table", attPart);
				var tbody = alib.dom.createElement("tbody", tbl);

				for (var i = 0; i < reps.attachments.length; i++)
				{
					var tr = alib.dom.createElement("tr", tbody);

					// Preview / icon
					var td = alib.dom.createElement("td", tr);
					td.innerHTML = reps.attachments[i].preview;

					// name
					var td = alib.dom.createElement("td", tr);
					td.innerHTML = reps.attachments[i].name + " ("+reps.attachments[i].size+") ";

					// links
					var td = alib.dom.createElement("td", tr);

					if (reps.attachments[i].link_view)
					{
						var lnk = alib.dom.createElement("a", td);
						lnk.target = "_blank";
						lnk.href = reps.attachments[i].link_view;
						lnk.innerHTML = "view";

						// add spacer
						alib.dom.createElement("span", td, "&nbsp;&nbsp;");
					}

					if (reps.attachments[i].link_download)
					{
						var lnk = alib.dom.createElement("a", td);
						lnk.target = "_blank";
						lnk.href = reps.attachments[i].link_download;
						lnk.innerHTML = "download";
					}
				}
			}
		}
		else
		{
            if(typeof reps == "object")
                evt.data.msgcls.messageBodyCon.innerHTML = "";
            else
			    evt.data.msgcls.messageBodyCon.innerHTML = reps; // will be an error string
		}
	}, {msgcls:this});

	// Timed out
	alib.events.listen(xhr, "error", function(evt) { 
    	evt.data.msgcls.messageBodyCon.innerHTML = "There was a problem loading this message.";
	});

	xhr.send("/controller/Email/getMessageBody?mid=" + this.oid);
}

/**
 * Creates the popup window for actions
 *
 * @param {string} objType  Object type to create
 * @param {array} params    Parameters for created object
 */
AntObjectLoader_EmailMessage.prototype.popupForm = function(objType, params)
{
    this.formPopupCon.innerHTML = "";
    
    var url = '/obj/'+objType;    
    var strWindName = objType + "New";
    var divCon = alib.dom.createElement("div", this.formPopupCon);
    alib.dom.styleSet(divCon, "display", "none");
    alib.dom.styleSet(divCon, "position", "absolute");
    
    var form = alib.dom.createElement("form", divCon);
    form.setAttribute("method", "post");
    form.setAttribute("target", strWindName);
    form.setAttribute("action", url);
    
    if(params)
    {
        for(param in params)
        {
            var currentParam = params[param];
            var hiddenData = [["type", "hidden"], ["name", currentParam[0]], ["value", currentParam[1]]];
            var hiddenField = alib.dom.setElementAttr(alib.dom.createElement("input", form), hiddenData);
        }
    }
    
    window.open(url, strWindName, 'width=950,height=750,toolbar=no,scrollbars=yes');

    try
    {
        form.submit();
    }
    catch(err)
    {
        alert('You must allow popups for this map to work.');
    }
}
