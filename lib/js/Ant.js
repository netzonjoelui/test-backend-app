/**
 * @fileoverview This is the main Ant class and namespace for all ant classes
 *
 * @author joe, sky.stebnicki@aereus.com
 */

/**
 * Ant namespace
 */
var Ant = {}

/**
 * Flag to indicate if the session is loaded
 *
 * @private
 * @type {bool}
 */
Ant.isSessionLoaded_ = false;

/**
 * Create storage variable for tracking account usage
 *
 * @public
 * @type {Object}
 */
Ant.storage = {
	user_quota: 1000,
	user_used: 0,
	global_quota: 1000,
	global_used: 0
};

/**
 * Current account object
 *
 * @public
 * @type {Object}
 */
Ant.account = {
	id: null,
	name: "",
	companyName: "Netric"
};

/**
 * Current user object
 *
 * @public
 * @type {Object}
 */
Ant.user = {
	id: null,
	name: "",
	fullName: ""
};

/**
 * Current theme
 *
 * @public
 * @type {Object}
 */
Ant.theme = {
	id: null,
	name: "",
	title: ""
};

/**
 * Settings will need to be pulled from backend
 *
 * @public
 * @type {Object}
 */
Ant.settings = {
	email: {
		dropbox_catchall : "sys.netric.com"
	}
};

/**
 * Initialized applications
 *
 * @public
 * @type {Array}
 */
Ant.apps = new Array();

/**
 * Handle to update stream
 *
 * @private
 * @type {Ant.UpdateStream}
 */
Ant.updateStream_ = null;

/**
 * Flag to indicate if we are running in a mobile browser
 *
 * @public
 * @type {bool}
 */
Ant.isMobile = false;

Ant.m_processes = new Array();
Ant.m_appcontainer = document.getElementById('appbody');
Ant.m_document = document;
Ant.m_evwnd = window;
Ant.f_framed = false; // Used if ANT is running in a framed enviroment or false if stand-alone
Ant.m_includes = new Array();
Ant.m_hPopupRef = new Array();
Ant.m_hHinstRef = new Array();

/**
 * Init app session variables
 *
 * When finished onload is called
 *
 * @param {Function} opt_callback Optional callback function to call when session is loaded
 */
Ant.init = function(opt_callback)
{
	// Get current authenticated session information
	var xhr = new alib.net.Xhr();
	alib.events.listen(xhr, "load", function(evt) { 

		Ant.isSessionLoaded_ = true; 
		var resp = this.getResponse();

		// Set data
		if (resp.account)
		{
			Ant.account.id = resp.account.id;
			Ant.account.name = resp.account.name;
			Ant.account.companyName = resp.account.companyName;
		}

		if (resp.user)
		{
			Ant.user.id = resp.user.id;
			Ant.user.name = resp.user.name;
			Ant.user.fullName = resp.user.fullName;
		}

		if (resp.theme)
		{
			Ant.theme.name = resp.theme.name;
		}

		// Callbacks
		Ant.onload(resp);

		// Check for inline callback
		if (opt_callback) 
			opt_callback(resp); 

		// Start keepalive
		Ant.keepAlive();
	});
	xhr.send("/controller/User/getSession");

	// Attach on resize event
	this.antResizeTimer = null;
	alib.dom.addEvent(window, "resize", function(){ 
		if (Ant && !Ant.antResizeTimer)
		{
			Ant.antResizeTimer = window.setTimeout(function() {
				try{ Ant.resizeActiveApp(); } catch(e) {};
				Ant.antResizeTimer = null;
			}, 1000); // Delay for a second so we don't kill the CPU
		}
	});
}

/**
 * Onload callback used when the session vars have been initialized
 */
Ant.onload = function()
{
}

/******************************************************************************
*	Function:	include
*	Purpose:	Include required js files (only once)
*******************************************************************************/
Ant.include = function (path)
{
	// Check if script is already loaded
	var name = "js_cls_" + path;
	if (!document.getElementById(name))
	{
		this.m_includes[name] = false;

		// Load External file into this document
		var fileRef = document.createElement('script');
		fileRef.m_name = name;

		if (alib.userAgent.ie)
		{
			fileRef.onreadystatechange = function () 
			{ 
				if (this.readyState == "complete" || this.readyState == "loaded") 
					Ant.m_includes[this.m_name] = true;
			};
		}
		else
			fileRef.onload = function () {Ant.m_includes[this.m_name] = true; };

		fileRef.type = "text/javascript";
		fileRef.id = name;
		fileRef.src = path;
		document.getElementsByTagName("head")[0].appendChild(fileRef);

		// Loop until document is loaded
		var iTimeout = 30; // if this fails 30 times then skip

		for (var i = 1; this.m_includes[name] != true; i++)
		{
			if (i > iTimeout) // Make sure we don't hang the browser
				return;
					
			this.wait(1000) // 1 second
		}
	}
}

/******************************************************************************
*	Function:	wait
*	Purpose:	Pause execution for a given number of m-seconds
*******************************************************************************/
Ant.wait = function (millis)
{
	date = new Date();
	var curDate = null;

	do { var curDate = new Date(); } 
	while(curDate-date < millis);
}


/******************************************************************************
*	Function:	SetVars
*	Purpose:	Set interval pointers to document and body
*******************************************************************************/
Ant.SetDocumentVars = function ()
{
	this.m_document = document;
	ALib.m_evwnd = window;

	this.m_appcontainer = document.getElementById('appbody');

	return true;
}

/******************************************************************************
*	Function:	setNoBodyOverflow
*	Purpose:	Set no overflow for body
*******************************************************************************/
Ant.setNoBodyOverflow = function()
{
	document.body.style.overflow= "hidden";

	for (var i = 0; i < this.appShells.length; i++)
	{
		if (this.appShells[i].name == this.last_selected)
		{
			this.m_processes[this.appShells[i].process_id].noOverflow = true;
		}
	}
}

/******************************************************************************
*	Function:	setPopupCb
*	Purpose:	Store an object(app) reference so popups can call opener.Ant...
*******************************************************************************/
Ant.setPopupHandle = function(handle, name)
{
	this.m_hPopupRef[name] = handle;
}

/******************************************************************************
*	Function:	getPopupCb
*	Purpose:	Get an object(app) reference so popups can call opener.Ant...
*******************************************************************************/
Ant.getPopupHandle = function(name)
{
	if (this.m_hPopupRef[name])
		return this.m_hPopupRef[name];
	else
		return false;
}

/******************************************************************************
*	Function:	setHinst
*	Purpose:	Store an object(app) reference so popups can call opener.Ant...
*******************************************************************************/
Ant.setHinst = function(handle, name)
{
	this.m_hHinstRef[name] = handle;
}

/******************************************************************************
*	Function:	getHinst
*	Purpose:	Get an object(app) reference so popups can call opener.Ant...
*******************************************************************************/
Ant.getHinst = function(name)
{
	if (this.m_hHinstRef[name])
		return this.m_hHinstRef[name];
	else
		return false;
}

/******************************************************************************
*	Function:	clearHinst
*	Purpose:	Clear hinst to make sure there are no hanging references in mem
*******************************************************************************/
Ant.clearHinst = function(name)
{
	if (this.m_hHinstRef[name])
		this.m_hHinstRef[name] = null;
}

/******************************************************************************
*	Function:	checkNewEmailMessages
*	Purpose:	Get number of new messages for default email account
*******************************************************************************/
Ant.setNewEmailMessages = function()
{
	return true; // TODO: for now we are not loading this

	// Clear any existing timers
	if (this.m_email_check_timer)
		clearTimeout(this.m_email_check_timer);

	if (typeof this.m_email_nmcount == "undefined")
		this.m_email_nmcount = new Array();

	var ajax = new CAjax();
	ajax.m_antcls = this;
	// Set callback once xml is loaded
	ajax.onload = function(root)
	{
		// Get first node
		var num = root.getNumChildren();
		for (var i = 0; i < num; i++)
		{
			var mailbox = root.getChildNode(i);
			var boxid = mailbox.getChildNodeValByName("boxid");
			var newm = mailbox.getChildNodeValByName("newm");
			var useindex = this.m_antcls.m_email_nmcount.length;

			// Check if folder already exists
			for (var i = 0; i < this.m_antcls.m_email_nmcount.length; i++)
			{
				if (this.m_antcls.m_email_nmcount[i][0] == boxid)
				{
					useindex = i;
					break;
				}
			}

			this.m_antcls.m_email_nmcount[useindex] = [boxid, newm];
		}

		this.m_antcls.m_email_check_timer = window.setTimeout("Ant.setNewEmailMessages()", 30000);
	};
	// Get xml file	
	try
	{
		ajax.exec("/email/xml_check_for_new.awp");
	}
	catch (e)
	{
		this.m_email_check_timer = window.setTimeout("Ant.setNewEmailMessages()", 30000);
	}
}

/******************************************************************************
*	Function:	keepAlive
*	Purpose:	This function checks every 3 minutes to make sure that the timers
*				are all still active
*******************************************************************************/
Ant.keepAlive = function()
{
	try
	{
		if (!this.m_email_check_timer)
			this.setNewEmailMessages();        
	}
	catch(e) {}

	this.m_keepalive_timer = window.setTimeout("top.Ant.keepAlive()", 180000);	// 3 minutes
}

/******************************************************************************
*	Function:	getNewEmailMessages
*	Purpose:	Get number of new messages for default email account
*******************************************************************************/
Ant.getNewEmailMessages = function(boxid)
{
	if (boxid)
	{
		for (var i = 0; i < this.m_email_nmcount.length; i++)
		{
			if (this.m_email_nmcount[i][0] == boxid)
				return this.m_email_nmcount[i][1];
		}
	}
	else
		return this.m_email_nmcount;
}


/******************************************************************************
*	Function:	launchModuleTutorials
*	Purpose:	Load Tutorials page for a specific module
*******************************************************************************/
Ant.launchTutorials = function(module)
{
	var url = '/help/tutorials.php?cat='+module;

	window.open(url, 'tutorials', 'width=300,height=300,toolbar=no,menubar=no,location=no,directories=no,status=no,resizable=yes');
}

/******************************************************************************
*	Function:	getObjectDefinition
*	Purpose:	Load the definition for an ANT object such as customer
*******************************************************************************/
Ant.getObjectDefinition = function(id)
{
	if (typeof this.objectdefs == "undefined")
		this.objectdefs = new Array();
	
	// TODO: copy the syncronous code from lib/js/CAntObject to pull and cache definition
}

/**
 * Get current active app name from hash
 *
 * @public
 * @return {string} The name of the currently active application
 */
Ant.getActiveAppName = function()
{
	var path = document.location.hash.substring(1); // minus the #
	var pathParts = path.split('/');
	return pathParts[0];
}

/**
 * Get current active app from hash
 *
 * @public
 * @return {AntApp} A reference to the currently active application on succes, false on failure
 */
Ant.getActiveApp = function()
{
	var appname = this.getActiveAppName();
	
	if (appname)
	{
		if (typeof this.apps[appname] != "undefined")
			return this.apps[appname];
	}

	// Failed
	return false;
}

/**
 * Check if an application is active by name
 *
 * @public
 * @param {string} appname The unique name of the application to check against
 * @return {bool} true if appname is active, false if another app is active
 */
Ant.appIsActive = function(appname)
{
	var actApp = this.getActiveAppName();
	return (activeApp == appname) ? true : false;
}



/**
 * Call the 'resize' function of the active application
 */
Ant.resizeActiveApp = function()
{
	var activeAppName = this.getActiveAppName();

	if (typeof this.apps[activeAppName] != "undefined")
	{
		if (typeof(this.apps[activeAppName].resize) != "undefined")
			this.apps[activeAppName].resize();
	}
}

/**
 * Update notice badges for window title
 *
 * @param {string} content The content to set
 * @param {string} section The name of the section we are updating
 */
Ant.updateAppTitle = function(content, section)
{
	if (!this.titleSections)
		this.titleSections = new Object();

	var title = "";

	if (!section && content)
		title = content;

	if (section == "notifications")
	{
		if (content != 0)
			title += "(" + content + ") ";
	}

	/*
	if (section == "messages")
	{
	}

	if (section == "chats")
	{
	}
	*/

	//title += "Netric";

	var app = this.getActiveApp();
	if (app)
	{
		if (app.shortTitle == "Untitled")
			title += "Loading...";
		else
			title += app.shortTitle;
	}
	else
	{
		title += this.account.companyName;
	}


	window.document.title = title;
}

/******************************************************************************
*	Function:	ShowLogin
*	Purpose:	Display a login prompt of session has expired
*******************************************************************************/
Ant.ShowLogin = function()
{
	// Make sure form is only shown once
	if (typeof this.loginFrmAct == "undefined")
		this.loginFrmAct = false;
	if (this.loginFrmAct)
		return;
	this.loginFrmAct = true;

	var dlg = new CDialog("Login");
	dlg.f_close = false;
	var dv = alib.dom.createElement("div");
	dlg.customDialog(dv, 300, 200);

	var p = alib.dom.createElement("p", dv);
	p.innerHTML = "Either your session has expired or your internet connection was interrupted. Please log in below to continue working:";

	// Inputs
	// --------------------------------------------
	
	// Name
	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "strong");
	lbl.innerHTML = "User Name:";
	var inpdv = alib.dom.createElement("div", dv);
	var txtName = alib.dom.createElement("input");
	alib.dom.styleSet(txtName, "width", "98%");
	txtName.type = 'text';
	txtName.value = this.user.name;
	inpdv.appendChild(txtName);
    
	// Pass
	var lbl = alib.dom.createElement("div", dv);
	alib.dom.styleSetClass(lbl, "strong");
	lbl.innerHTML = "Password:";
	var inpdv = alib.dom.createElement("div", dv);
	var txtPass = alib.dom.createElement("input");
	alib.dom.styleSet(txtPass, "width", "98%");
	txtPass.type = 'password';
	txtPass.value = "";
	inpdv.appendChild(txtPass);

	var authuser = function(name, password, dlg)
	{
		var args = [["function", "login"], ["name", name.value], ["password", password.value]];
        
        ajax = new CAjax();
        ajax.cbData.cls = this;
        ajax.cbData.dlg = dlg;
        ajax.onload = function(ret)
        {
            if(ret.getNumChildren())
            {
                var ret = ret.getChildNodeValByName("retval")
                if (ret=="OK")
                {
                    this.cbData.dlg.hide();
                    Ant.loginFrmAct = false;
                    return;
                }
            }
            
            alert("There was a problem logging in. Please try again!");
            
        };
        ajax.exec("/security/wapi.php", args); 
	}
    
    // element events
    txtName.authFunc = authuser;
    txtName.cbDlg = dlg;
    txtName.cbPass = txtPass;
    txtName.onkeyup = function(evnt)
    {
        evnt = evnt || window.event;
        
        switch (evnt.keyCode)
        {
            case 13: // Enter
                this.authFunc(this, this.cbPass, this.cbDlg);
                if(evnt.preventDefault)
                    evnt.preventDefault()
                else
                    evnt.returnValue = false;                
                break;
        }
    }
    
    txtPass.authFunc = authuser;
    txtPass.cbDlg = dlg;
    txtPass.cbName = txtName;
    txtPass.onkeyup = function(evnt)
    {
        evnt = evnt || window.event;
        
        switch (evnt.keyCode)
        {
            case 13: // Enter
                this.authFunc(this.cbName, this, this.cbDlg);
                if(evnt.preventDefault)
                    evnt.preventDefault()
                else
                    evnt.returnValue = false;                
                break;
        }
    }

	// Buttons
	// --------------------------------------------
	var bntbar = alib.dom.createElement("div", dv);
	alib.dom.styleSet(bntbar, "margin", "6px 0px 3px 3px");

	var btn = new CButton("OK", authuser, [txtName, txtPass, dlg], "b2");
	btn.print(bntbar);

	var btn = new CButton("Log Out", function(dlg) { document.location = '/logout.php'; }, [dlg], "b3");
	btn.print(bntbar);
}

/******************************************************************************
*    Function:    userCheckin
*    Purpose:    User activity checkin
*******************************************************************************/
Ant.userCheckin = function()
{
    // User Activity Checkin
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if(!ret)
        {
            this.cls.ShowLogin();
            return;
        }
        
        if(ret=="1")
        {            
            window.setTimeout("Ant.userCheckin()", 60000);
        }
        else
            this.cls.ShowLogin();
    };

    var registerActive = 0;
    if (alib.dom.userActive)
        registerActive = 1;

    var args = new Array();
    args[args.length] = ['registerActive', registerActive];
    ajax.exec("/controller/User/userCheckin", args);
}

/******************************************************************************
*    Function:    changeTheme
*    Purpose:    Changes the UI theme
*******************************************************************************/
Ant.changeTheme = function(name)
{
	if (name != this.m_theme && name!="")
	{
		var cssDom = alib.dom.getElementById("ant_css_theme");
		cssDom.href = "/css/ant_" + name + ".css";
		this.m_theme = name;
	}
}

/**
 * Get account base uri
 */
Ant.getBaseUri = function()
{
	// Use the new netric code
	return netric.getBaseUri();
	/*
	var uri = window.location.protocol+'//'+window.location.hostname+(window.location.port ? ':'+window.location.port: '');
	return uri;
	*/
}

/**
 * Get update stream, create it if not already
 *
 * @return {Ant.UpdateStream}
 */
Ant.getUpdateStream = function()
{
	if (this.updateStream_ == null)
		this.updateStream_ = new Ant.UpdateStream();

	return this.updateStream_;
}
