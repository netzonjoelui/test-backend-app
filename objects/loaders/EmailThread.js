/**
 * @fileoverview This loader will display email threads inside an object loader
 *
 * @author	joe, sky.stebnicki@aereus.com
 * 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObjectLoader_EmailThread.
 *
 * @constructor
 * @param {CAntObject} obj Handle to object that is being viewed or edited
 * @param {AntObjectLoader} loader Handle to base loader class
 */
function AntObjectLoader_EmailThread(obj, loader)
{
	this.mainObject = obj;
	this.loaderCls = loader;
	this.oid = obj.id;
	this.formCon = null; // inner container where form will be printed
	this.ctbl = null; // Content table used for frame when printed inline
	this.customTitleCon = null; // used for inline printing so the subjet is printed above
	this.toolbar = null;
	this.bodyFormCon = null; // Displays the form
	this.bodyGroupCon = null; // Displays the rgoups
	this.plugins = new Array();
	this.printOuterTable = true; // Can be used to exclude outer content table (usually used for preview)
	this.fEnableClose = true; // Set to false to disable "close" and "save and close"
}

/**
 * Refresh the form
 */
AntObjectLoader_EmailThread.prototype.refresh = function()
{
}

/**
 * Enable to disable edit mode for this loader
 *
 * @param {bool} setmode True for edit mode, false for read mode
 */
AntObjectLoader_EmailThread.prototype.toggleEdit = function(setmode)
{
}

/**
 * Print form on 'con'
 *
 * @param {DOMElement} con A dom container where the form will be printed
 * @param {array} plugis List of plugins that have been loaded for this form
 */
AntObjectLoader_EmailThread.prototype.print = function(con, plugins)
{
	con.innerHTML = "";
	this.isPopup = (this.loaderCls.isPopup) ? true : false;
	this.formCon = con;

	// Print title con if we are inline (preview mode)
	if (this.loaderCls.inline)
	{
		this.customTitleCon = alib.dom.createElement("div", this.formCon);
		this.customTitleCon.className = "objectLoaderHeader";

		this.onNameChange = function(name)
		{
			if (this.loaderCls.antView)
				this.loaderCls.antView.setTitle(name);

			this.customTitleCon.innerHTML = name;
		}
	}

	// Groups Con
	this.bodyGroupCon = alib.dom.createElement("div", this.formCon);

	// Form container
	this.bodyFormCon = alib.dom.createElement("div", this.formCon);

	this.buildInterface();
}

/**
 * Callback is fired any time a value changes for the mainObject 
 */
AntObjectLoader_EmailThread.prototype.onValueChange = function(name, value, valueName)
{	
}

/**
 * Callback function used to notify the parent loader if the name of this object has changed
 */
AntObjectLoader_EmailThread.prototype.onNameChange = function(name)
{
}

/**
 * Callback is fired any time a value changes for the mainObject 
 *
 * @private
 */
AntObjectLoader_EmailThread.prototype.buildInterface = function()
{	
	var tb = new CToolbar();
	this.toolbar = tb;
	var btn = new CButton("Close", function(cls) { cls.close(); }, [this.loaderCls], "b1");
	if (this.loaderCls.fEnableClose && !this.loaderCls.isMobile)
		tb.AddItem(btn.getButton(), "left");
	if (this.mainObject.security.edit)
	{
		// Move
		var dv = alib.dom.createElement("div");
		tb.AddItem(dv);
		var dynsel = new AntObjectGroupingSel("Move", "email_thread", 'mailbox_id', null, this.mainObject, {noNull:true, staticLabel:true});
		dynsel.print(dv, "b1 grLeft");
		dynsel.fwdEmailThView = this;
		dynsel.onSelect = function(id, name)
		{
			this.fwdEmailThView.moveThread(id, name, false);
		}
		// Add Groups
		var dv = alib.dom.createElement("div");
		tb.AddItem(dv);
		var dynsel = new AntObjectGroupingSel("Add Group", "email_thread", 'mailbox_id', null, this.mainObject, {noNull:true, staticLabel:true});
		dynsel.print(dv, "b1 grRight");
		dynsel.fwdEmailThView = this;
		dynsel.onSelect = function(id, name)
		{
			this.fwdEmailThView.moveThread(id, name, true);
		}
		
		var btn = new CButton("Print", function(cls){ window.open("/print/engine.php?obj_type=email_thread&objects[]="+cls.oid); }, [this], "b1");
		tb.AddItem(btn.getButton(), "left");
		
		if (!this.isPopup)
		{
			var params = 'width=1024,height=768,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
			var btn = new CButton("New Window", function(cls){ window.open("/obj/email_thread/"+cls.oid, null, params); }, [this], "b1");
			tb.AddItem(btn.getButton(), "right");
		}
	}

	if (this.oid)
	{
		if (this.mainObject.security.del)
		{
			var btn = new CButton("Delete", function(cls, oid){ cls.deleteObject(oid); }, [this.loaderCls, this.oid], "b1");
			tb.AddItem(btn.getButton(), "left");
		}
	}
	tb.print(this.loaderCls.toolbarCon);

	// Set subject/title bar
	this.onNameChange(this.mainObject.getLabel());

	// Set ANT View title
	/*
	if (this.loaderCls.antView)
		this.loaderCls.antView.setTitle(this.mainObject.getLabel());
	*/

	// Print email body
	this.getMessages(this.bodyFormCon);
	this.getGroups();
}

/**
 * Get messages for this thread
 *
 * @param {DOMElement} con The container to print messages in
 * @private
 */
AntObjectLoader_EmailThread.prototype.getMessages = function(con)
{
    con.innerHTML = "Loading...";
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.con = con;
    ajax.onload = function(ret)
    {
        this.cbData.con.innerHTML = "";
        if (ret)
        {
            try
            {                
                if (ret.length)
                {
                    for(message in ret)
                    {
                        var currentMessage = ret[message];
                        // Print object loader
                        var ol = new AntObjectLoader("email_message", currentMessage.id);
                        ol.fEnableClose = false;
                        ol.fThreadView = true;
                        ol.inline = true; // Do not resize and hide title
                        ol.printOuterTable = false;
                        if (currentMessage.flag_seen=='t' && message<(ret.length-1))
                            ol.printCollapsed(this.cbData.con, false, currentMessage);
                        else    
                            ol.print(this.cbData.con);
                    }
                }
                else
                {
                    this.cbData.con.innerHTML = "None available";
                }
            }
            catch(e)
            {
                alert(e);
            }
        }
    };
    ajax.exec("/controller/Email/threadGetMessages",
                [["tid", this.oid]]);
}

/**
 * Get groups for this thread
 *
 * @private
 */
AntObjectLoader_EmailThread.prototype.getGroups = function()
{
    alib.dom.styleSet(this.bodyGroupCon, "margin", "3px 0 5px 5px");
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.con = this.bodyGroupCon;
    ajax.onload = function(ret)
    {
        if (ret)
        {
            try
            {
                this.cbData.con.innerHTML = "";
                if (ret.length)
                {
                    for(group in ret)
                    {
                        var currentGroup = ret[group];
                        if (currentGroup.color)
                        {
                            var bg = currentGroup.color;
                            var fg = getColorTextForGroup(currentGroup.color);
                        }
                        else
                        {
                            if (currentGroup.flag_special == 't')
                            {
                                var bg = "e3e3e3";
                                var fg = "000000";
                            }
                            else
                            {
                                var bg = G_GROUP_COLORS[0][1];
                                var fg = getColorTextForGroup(bg);
                            }
                        }

                        var gpdv = alib.dom.createElement("div");
                        alib.dom.styleSet(gpdv, "display", "inline-block");
                        alib.dom.styleSet(gpdv, "zoom", "1");
                        alib.dom.styleSet(gpdv, "*display", "inline");
                        alib.dom.styleSet(gpdv, "padding", "1px 3px 1px 3px");
                        alib.dom.styleSet(gpdv, "margin-right", "5px");
                        alib.dom.styleSet(gpdv, "background-color", '#'+bg);
                        alib.dom.styleSet(gpdv, "color", "#"+fg);
                        alib.dom.styleSet(gpdv, "border-radius", "3px");
                        alib.dom.styleSet(gpdv, "-webkit-border-radius", "3px");
                        alib.dom.styleSet(gpdv, "-moz-border-radius", "3px");

                        //ALib.Effect.round(gpdv, 5);
                        var lbl = alib.dom.createElement("span", gpdv);
                        lbl.innerHTML = currentGroup.name + " | ";
                        var del = alib.dom.createElement("span", gpdv);
                        del.innerHTML = "x";
                        alib.dom.styleSet(del, "cursor", "pointer");
                        del.pdiv = this.cbData.con;
                        del.box = gpdv;
                        del.cls = this.cbData.cls;
                        del.gid = currentGroup.id;
                        del.gname = currentGroup.name;
                        del.onclick = function()
                        {
                            this.cls.removeMailbox(this.gid, this.gname, this.box);
                        }

                        this.cbData.con.appendChild(gpdv);
                    }
                    var gpdv = alib.dom.createElement("div", this.cbData.con);
                }
            }
            catch(e)
            {
                alert(e);
            }
        }
    };
    ajax.exec("/controller/Email/threadGetGroups",
                [["tid", this.oid]]);
}


/**
 * Moves the email message to another thread
 *
 * @public
 * @this {AntObjectLoader_EmailThread}
 * @param {Int} mid     Mailbox Id
 * @param {String} name Mailbox name
 */
AntObjectLoader_EmailThread.prototype.moveThread = function(mid, name, addMailBox)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.name = name;
    ajax.cbData.addMailBox = addMailBox;
    ajax.onload = function(ret)
    {
        if(!ret)
            return;
            
        if(ret.error)
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
        else
        {
            if(this.cbData.addMailBox)
                ALib.statusShowAlert("This message is added to " + this.cbData.name, 3000, "bottom", "right");
            else
                ALib.statusShowAlert("This message is moved to " + this.cbData.name, 3000, "bottom", "right");
        }
            
        this.cbData.cls.getGroups();
    };
    
    var args = new Array();
    args[args.length] = ['obj_type', "email_thread"];
    args[args.length] = ['field_name', "mailbox_id"];
    args[args.length] = ['move_to', mid];
    args[args.length] = ['objects[]', this.oid];
    
    if(addMailBox)
        args[args.length] = ['addMailbox', 1];
    
    ajax.exec("/controller/Object/moveByGrouping", args);
}

/**
 * Remove the associated mailbox
 *
 * @public
 * @this {AntObjectLoader_EmailThread}
 * @param {Int} mid                 Mailbox Id
 * @param {String} name             Mailbox name
 * @param {DOMElement} groupDiv     Group mailbox container
 */
AntObjectLoader_EmailThread.prototype.removeMailbox = function(mailboxId, name, groupDiv)
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.name = name;
    ajax.cbData.groupDiv = groupDiv;
    ajax.onload = function(ret)
    {
        if(!ret)
            return;
            
        if(ret.error)
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
        else
        {
            this.cbData.groupDiv.parentNode.removeChild(this.cbData.groupDiv);
            ALib.statusShowAlert(this.cbData.name + " is removed to this message.", 3000, "bottom", "right");
        }
    };
    
    var args = new Array();
    args[args.length] = ['gid', mailboxId];
    args[args.length] = ['tid', this.oid];
    ajax.exec("/controller/Email/threadDeleteGroup", args);
}
