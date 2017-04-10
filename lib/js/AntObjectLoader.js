/**
 * @fileoverview This class is responsible for loading all objects for both viewing and editing
 *
 * AntObjectLoader is used to load all types of objets in ANT. This class should not be responsible
 * for printing any ui elements, but rather loading the appropriate forms for each object type.
 *
 * Below is an example:
 * <code>
 * 	var objLoader = new AntObjectLoader("customer");
 * 	objLoader.onclose = function() { // do something when the object is closed  }
 * 	objLoader.print(document.getElementById("body"));
 * </code>
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObjectLoader.
 *
 * @constructor
 * @param {string} obj_type The name of the object type to load
 * @param {number} oid The optional unique id of an object instance to load
 * @param {CAntObject} objInst Optional existing of the object
 */
function AntObjectLoader(obj_type, oid, objInst)
{
	/**
	 * Instance of CAntObject of type obj_type - loads object data if oid is defined
	 *
	 * @type {CAntObject}
	 * @public
	 */
	this.mainObject = new CAntObject(obj_type, oid);

	/**
	 * Unique object id
	 *
	 * This will be set to a number when saving a new object.
	 *
	 * @type {number}
	 * @public
	 */
    this.oid = oid;
    
    this.objType = obj_type;
    this.cloneObject = false;

	this.outerCon = null; // Outer container
	this.mainCon = null; // Inside outcon and holds the outer table
	this.titleCon = null; // Content table used for frame when printed inline
	this.toolbarCon = null; // Div that holds the toolbar if any
	this.noticeCon = null; // Usually hidden but sometimes used for inline notices and browsers (like duplicate dedection)
	this.formCon = null; // inner container where form will be printed
	this.tabs = null; // Can optionally be set by sub-loader, if set then resize will resize the tab rather than the formCon

	this.plugins = new Array();
	this.inline = false; // Can be used to exclude outer content table (usually used for preview)
	this.fEnableClose = true; // Set to false to disable "close" and "save and close"
	this.antView = null; // optional AntView reference
	this.isMobile = (typeof Ant != "undefined" && Ant.isMobile) ? true : false;    
	this.subLoader = null; // The subloader used to build the actual UI form
    
    // arguments for forms opened in new window
    this.newWindowArgs = null;
    
    // arguments for email
    this.emailArgs = new Array();
    
    this.subLoaderParams = new Object(); // Force the Object Loader to load a sub loader

    // Object for storing callback variables
    this.cbData = new Object();

	/**
	 * Additional toolbar entries from plugins
	 *
	 * @var {Object[]} Object with .label, icon, .cb (function)
	 */
	this.pluginToolbarEntries = new Array();

	/**
	 * Form scope
	 *
	 * @protected
	 * @type {string}
	 */
	this.formScope = (this.isMobile) ? "mobile" : "default";

	/**
	 * Hide the toolbar
	 *
	 * @public
	 * @type {bool}
	 */
	this.hideToolbar = false;
}

/**
 * Print the form onto con
 *
 * @this {AntObjectLoader}
 * @param {DOMElement} con The container to print this object loader into - usually a div
 * @param {bool} popup Set to true if we are operating in a new window popup. Hides "Open In New Window" link.
 * @param {string} scope Optionally override the automatic form scope like 'mobile' or 'infobox'
 * @public
 */
AntObjectLoader.prototype.print = function(con, popup, scope)
{
	// Override scope
	if (scope)
		this.formScope = scope;

	this.isPopup = (popup) ? true : false;
	this.outerCon = con;
	this.mainCon = alib.dom.createElement("div", con);
	this.mainCon.className = "objectLoader";

	// Print title
    this.titleCon = alib.dom.createElement("div", this.mainCon);
    if (!this.inline)
    {
        this.titleCon.className = "objectLoaderHeader";
		this.setTitle("Loading");
    }
	else
	{
		// Hide it for all inline usage
		alib.dom.styleSet(this.titleCon, "display", "none");
	}

	// Toolbar container
	this.toolbarCon = alib.dom.createElement("div", this.mainCon);
    if (this.hideToolbar)
		alib.dom.styleSet(this.toolbarCon, "display", "none");

	// Notice container
	this.noticeCon = alib.dom.createElement("div", this.mainCon);

	// Form container
    this.formCon = alib.dom.createElement("div", this.mainCon);
	this.formCon.className = "objectLoaderBody";
	this.formCon.innerHTML = " <div class='loading'></div>";
    
	if (this.oid)
	{
		this.mainObject.frmCls = this;
        this.mainObject.newWindowArgs = this.newWindowArgs;
		this.mainObject.onload = function()
		{
			this.frmCls.onObjectLoaded();
			this.frmCls.loadPlugins(); // this will call buildInterface when done
			this.frmCls.showNotices();
		}

		this.mainObject.load();
	}
	else
	{
        this.loadNewWindowValues();
        this.loadPlugins(); // this will call buildInterface when done
	}
}

/**
 * Print the form onto con in inline mode meaning no full height, toolbar, or header
 *
 * @this {AntObjectLoader}
 * @param {DOMElement} con The container to print this object loader into - usually a div
 * @param {bool} popup Set to true if we are operating in a new window popup. Hides "Open In New Window" link.
 * @param {string} scope Optionally override the automatic form scope like 'mobile' or 'infobox'
 * @public
 */
AntObjectLoader.prototype.printInline = function(con, popup, scope)
{
	this.inline = true;
	this.print(con, popup, scope);
}

/**
 * Resize the visual elements of this object loader
 *
 * @this {AntObjectLoader}
 * @public
 */
AntObjectLoader.prototype.resize = function()
{
	// Do not resize if we are inline
	if (this.inline || this.isMobile)
		return;


	// Default loader processing
	var height = getWorkspaceHeight();
	this.setHeight(height);

	if (this.subLoader)
	{
		if (this.subLoader.resize)
			this.subLoader.resize();
	}
}

/**
 * Set the height of this loader
 *
 * @this {AntObjectLoader}
 * @public
 * @param {integer} height The number pixels to set height to (max)
 */
AntObjectLoader.prototype.setHeight = function(height)
{
	// Check to see if subloader has overridden the setHeight function
	if (this.subLoader)
	{
		if (this.subLoader.setHeight)
			return this.subLoader.setHeight();
	}

	height -= alib.dom.getContentHeight(this.titleCon);
	height -= alib.dom.getContentHeight(this.toolbarCon);
	height -= alib.dom.getContentHeight(this.noticeCon);

	height -= 10; // Added for bug with CToolBar class adding 5 unaccounted for 5x

	if (this.tabs)
	{
		this.tabs.setHeight(height);
		alib.dom.styleSet(this.formCon, "height", "auto");
	}
	else
	{
		alib.dom.styleSet(this.formCon, "height", height + "px");
		alib.dom.styleSet(this.formCon, "overflow", "auto");
	}
}

/**
 * Set the header title
 *
 * @public
 * @this {AntObjectBrowser}
 * @param {string} title The title string
 */
AntObjectLoader.prototype.setTitle = function(title)
{
	this.titleCon.innerHTML = "";

	if (this.isPopup)
	{
		document.title = title;
	}
	else if (this.titleCon)
	{
		if (this.antView)
			this.setTitleParentPath();

		var ttl = alib.dom.createElement("div", this.titleCon, title);

		if (this.antView)
			this.antView.getTitle(ttl);
	}
}

/**
* Used to create application breadcrumbs
*/
AntObjectLoader.prototype.setTitleParentPath = function()
{
	// Create breadcrumbs container because it is cleared each time in setTitle
	var bcCon = alib.dom.createElement("div", this.titleCon);
	alib.dom.styleSetClass(bcCon, "breadCrumbs");

	var views = new Array();
	// Traverse backwards over parent views
	if (this.antView.getParentView())
	{
		var currentView = this.antView.getParentView();

		while (currentView)
		{
			views.push(currentView);
			currentView = currentView.getParentView();
		}
	}

	// Loop backwards skipping over the root/application view
	for (var i = (views.length - 2); i >= 0; i--)
	{
		var view = views[i];

		var lnk = alib.dom.createElement("a", bcCon);
		lnk.href = "#"+view.getPath();
		view.getTitle(lnk); // passing the element will bind it to be updated on title change

        var sp = alib.dom.createElement("span", bcCon);
        sp.innerHTML = " / ";
	}

	/*
    if (childView)
	{
    }

    var view = (childView) ? childView : this.antView;
	console.log(view.getPath());

	if (!remPath)
		remPath = view.getPath();


	// Now load active child views into title
    var nextViewName = remPath;
    var postFix = "";
    if (remPath.indexOf("/")!=-1)
  	{
        var parts = remPath.split("/");
        var nextViewName = parts[0];
        if (parts.length > 1)
        {
			// Skip of first which is current view
            for (var i = 1; i < parts.length; i++) 
                {
                if (postFix != "")
                    postFix += "/";
                postFix += parts[i];
            }
        }
    }
	*/
}

/**
 * Print subloader in collapsed mode. Only if subloader class has a method called printCollapsed.
 *
 * @this {AntObjectLoader}
 * @param {DOMElement} con The container to print this object loader into - usually a div
 * @param {bool} popup Set to true if we are operating in a new window popup. Hides "Open In New Window" link.
 * @param {Object} data Properties to forward to collapsed view
 * @public
 */
AntObjectLoader.prototype.printCollapsed = function(con, popup, data)
{    
	this.isPopup = (popup) ? true : false;

	var subLoader = this.getSubloader();

	if (subLoader.printCollapsed)
	{
		subLoader.printCollapsed(con, this.isPopup, data);
	}
}

/**
 * Get the subloader/form
 */
AntObjectLoader.prototype.getSubloader = function()
{
	if (this.subLoader)
		return this.subLoader;
        
	switch (this.mainObject.obj_type)
	{
	case 'email_message':
		this.subLoader = new AntObjectLoader_EmailMessage(this.mainObject, this);
        this.subLoader.emailArgs = this.emailArgs;
		break;
	case 'email_thread':
		this.subLoader = new AntObjectLoader_EmailThread(this.mainObject, this);
		break;
    //case 'user':
        //this.subLoader = new AntObjectLoader_User(this.mainObject, this);
        //break;
    case 'report':        
        this.subLoader = new AntObjectLoader_Report(this.mainObject, this);
        break;
    //case 'dashboard':        
        //this.subLoader = new AntObjectLoader_Dashboard(this.mainObject, this);
        //break;
    //case 'calendar':        
        //this.subLoader = new AntObjectLoader_Calendar(this.mainObject, this);
        //break;
	// Default is to use the UIML forms
	default:
		this.subLoader = new AntObjectLoader_Form(this.mainObject, this);
		break;
	}

	// TODO: This is a temporary hack until subloaders are moved to plugins
	if (this.inline && this.objType=="user")
		this.subLoader = new AntObjectLoader_Form(this.mainObject, this);
	
	this.subLoader.onNameChange = function(name)
	{
		if (this.loaderCls.antView)
			this.loaderCls.antView.setTitle(name);

		this.loaderCls.setTitle(name);
	}

	// Set value change callbacks with subloader
	this.mainObject.loaderCls = this;
	this.mainObject.onValueChange = function(name, value, valueName)
	{
		this.loaderCls.onValueChange(name, value);
		if (this.loaderCls.subLoader)
			this.loaderCls.subLoader.onValueChange(name, value);

		for (var i = 0; i < this.loaderCls.plugins.length; i++)
		{
			if (typeof this.loaderCls.plugins[i].onMainObjectValueChange != "undefined")
			{
				this.loaderCls.plugins[i].onMainObjectValueChange(name, value, valueName);
			}
		}
	}
    
    // Set the toggle edit for plugins
    this.mainObject.loaderCls = this;
    this.mainObject.onToggleEdit = function(setmode)
    {
        for (var i = 0; i < this.loaderCls.plugins.length; i++)
        {
            if (typeof this.loaderCls.plugins[i].onMainObjectToggleEdit != "undefined")
            {
                this.loaderCls.plugins[i].onMainObjectToggleEdit(setmode);
            }
        }
    }

	return this.subLoader;
}

/**
 * Display notices such as deleted status
 */
AntObjectLoader.prototype.showNotices = function()
{
	if (this.mainObject.getValue("f_deleted"))
	{
		var dv = alib.dom.createElement("div", this.noticeCon);
		alib.dom.styleSetClass(dv, "error");
		dv.innerHTML = "This "+this.mainObject.title+" has been deleted. ";

		var undelete = alib.dom.createElement("a", dv);
		undelete.href = 'javascript:void(0)';
		undelete.onclick = function() {}
		undelete.innerHTML = "[undelete]";
		undelete.oid= this.mainObject.id;
		undelete.cls = this;
		undelete.onclick = function()
		{
			this.cls.undeleteObject(this.oid, dv);
		}
	}
}

/**
 * Load available plugings for the object type
 *
 * @this {AntObjectLoader}
 * @private
 */
AntObjectLoader.prototype.loadPlugins = function()
{
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if (!ret['error'])
        {
            try
            {                
                for (plugin in ret)
                {
                    var currentPlugin = ret[plugin];
                    this.cls.plugins[this.cls.plugins.length] = eval("("+unescape(currentPlugin)+")");                    
                }
            }
            catch(e)
            {
                alert("Error loading plugin: " + e + " - " + JSON.stringify(ret));
            }
        }
        //this.cls.buildInterface();            
		this.cls.loadForm();
    };
    ajax.exec("/controller/Object/getPlugins", 
                [["obj_type", this.mainObject.name]]);
}

/**
 * Preload the scopped form
 *
 * @this {AntObjectLoader}
 * @private
 */
AntObjectLoader.prototype.loadForm = function()
{
	AntObjectForms.loadForm(this.objType, {context:this, method:"buildInterface"}, null, this.formScope);
}

/**
 * Build interface for object
 *
 * @this {AntObjectLoader}
 * @private
 */
AntObjectLoader.prototype.buildInterface = function()
{
	/* We are now using views to handle title changes in this.setTitle
	if (this.antView)
	{
		if (this.antView)
			this.antView.getTitle(this.titleCon);
	}
	else
	{
		if (this.oid)
		{
			this.setTitle("Edit " + this.mainObject.title);
		}
		else
		{
			this.setTitle("New " + this.mainObject.title);
		}
	}
	*/

	if (this.oid)
	{
		this.setTitle("Edit " + this.mainObject.title);
	}
	else
	{
		this.setTitle("New " + this.mainObject.title);
	}

	var sl = this.getSubloader();
	this.formCon.innerHTML = "";
	sl.print(this.formCon, this.plugins);

	if(this.newWindowArgs)
    {
        sl.toggleEdit(this.newWindowArgs['editMode'][0]);        
    }        
    else
    {
        // if the object is being cloned, set the editmode to always true
        if(this.cloneObject)
        {
            sl.toggleEdit(true);
            this.setTitle("Copied " + this.mainObject.title);
        }
        else
            sl.toggleEdit((this.oid)?false:true);
    }
    
    this.subLoader = sl;

	this.resize();

	// Now that this has been rendered, mark as seen
	this.markSeen();
}

/**
 * Open this object in a new window
 *
 * @this {AntObjectLoader}
 * @public
 */
AntObjectLoader.prototype.openInNewWindow = function()
{
	var dv = alib.dom.createElement("div");
	alib.dom.styleSet(dv, "display", "none");
	
	// dynamic form sent to new window
	var form = alib.dom.createElement("form", dv);
	form.setAttribute("method", "post");        

	// setting form target to a window named 'ObjectWindow'
	form.setAttribute("target", this.mainObject.obj_type + this.oid);
	
	// browse all mainObject properties
	var fields = this.mainObject.getFields();        
	for (var i = 0; i < fields.length; i++)
	{
		var currField = fields[i];
		var objFieldType = currField.type;
		var objFieldName = currField.name;            
		var objFieldValue = this.mainObject.getValue(objFieldName);
		
        if(!objFieldName)
            continue;
        
		switch(objFieldType)
		{
			case "fkey":
			case "object":
				// input field for fkey text value
				objFieldValue = this.mainObject.getValueStr(objFieldName)
				var hiddenField = alib.dom.createElement("input", form);
				hiddenField.setAttribute("name", objFieldName + "Fkey");
				hiddenField.setAttribute("value", objFieldValue);
				
				
				var hiddenField = alib.dom.createElement("input", form);
				hiddenField.setAttribute("name", objFieldName + "FkeyType");
				hiddenField.setAttribute("value", objFieldType + "_reference");
				
				var hiddenField = alib.dom.createElement("input", form);
				hiddenField.setAttribute("name", objFieldName);
				hiddenField.setAttribute("value", rawurlencode(objFieldValue));
				break;
			case "object_multi":
			case "fkey_multi":
				// if the type of input is multi, then we need to get the text values                        
				objFieldValue = this.mainObject.getMultiValues(objFieldName);
				var multiValueArr = objFieldValue.toString().split(",");                    
				var multiValue = "";
				for (var multiX = 0; multiX < multiValueArr.length; multiX++)
				{
					// loop thru optional_vals to get the text values
					for (var n = 0; n < currField.optional_vals.length; n++)
					{
						if (currField.optional_vals[n][0] == multiValueArr[multiX])
						{       
							if(multiValue.length)
								multiValue += ",";
								
							multiValue += currField.optional_vals[n][1];
							break;
						}
					}
				}
				
				// input field for multi text values
				var hiddenField = alib.dom.createElement("input", form);
				hiddenField.setAttribute("name", objFieldName + "Multi");
				hiddenField.setAttribute("value", rawurlencode(multiValue));
				
				var hiddenField = alib.dom.createElement("input", form);
				hiddenField.setAttribute("name", objFieldName + "MultiType");
				hiddenField.setAttribute("value", objFieldType + "_reference");
				
				// input field for multi value
				var hiddenField = alib.dom.createElement("input", form);
				hiddenField.setAttribute("name", objFieldName);
				hiddenField.setAttribute("value", objFieldValue);
				
				break;
			default:
				var hiddenField = alib.dom.createElement("input", form);
				hiddenField.setAttribute("name", objFieldName);
				hiddenField.setAttribute("value", objFieldValue);
				break;
		}
		
		var hiddenField = alib.dom.createElement("input", form);
		hiddenField.setAttribute("name", objFieldName + "Type");
		hiddenField.setAttribute("value", objFieldType);
	}
	
	// set that the new window is clicked
	var hiddenField = alib.dom.createElement("input", form);
	hiddenField.setAttribute("name", "fromInlineForm");
	hiddenField.setAttribute("value", "true");
	
	var hiddenField = alib.dom.createElement("input", form);
	hiddenField.setAttribute("name", "fromInlineFormType");
	hiddenField.setAttribute("value", "bool");
	
	// set if whats the current state of the inline form
	var hiddenField = alib.dom.createElement("input", form);
	hiddenField.setAttribute("name", "editMode");
	hiddenField.setAttribute("value", this.subLoader.editMode);
	
	var hiddenField = alib.dom.createElement("input", form);
	hiddenField.setAttribute("name", "editModeType");
	hiddenField.setAttribute("value", "bool");

	if (this.oid)
		var url = "/obj/"+this.mainObject.name+"/"+this.oid;
	else         
		var url = "/obj/"+this.mainObject.name;
	
	form.setAttribute("action", url);
	document.body.appendChild(dv);
	
 	var params = 'width=1024,height=768,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
	window.open(url, this.mainObject.obj_type + this.oid, params); 
	
	form.submit();
}

/**
 * Add a toolbar entry for a plugin
 *
 * @public
 * @param {string} lbl              The text label to use
 * @param {function} cb             Callback function to be called when the toolbar button is clicked
 * @param {Object} cbdat Optional   data object to be passed as the first argument when button is clicked
 * @param {string} pos Optional     Will determine where to display the toolbar button - Default Value: last
 *                                  Possible Values: first, last
 */
AntObjectLoader.prototype.pluginAddToolbarEntry = function(lbl, cb, cbdat, pos)
{
	var cbdat = cbdat || {};
    
    if(typeof pos == "undefined")
        pos = "last";
    
	this.pluginToolbarEntries.push({label:lbl, callback:cb, cbData:cbdat, pos:pos});
}

/**
 * Save object being edited
 *
 * @public
 * @param {bool} close If set to true close after saving is finished
 * @param {string} recur_stype Optional recur save type to indicate all in series or only this object
 */
AntObjectLoader.prototype.saveObject = function(close, recur_stype)
{
	var close = (typeof close != "undefined") ? close : false;

	// "This [oname] only" and "This And All Future [oname]" and "Cancel" dialog if event is recurring
	if (this.mainObject.recurrencePattern != null && this.mainObject.recurrencePattern.id && !recur_stype)
	{
		var dlg = new CDialog("Save Recurring Series");

		var dv = alib.dom.createElement("div");

		var dv_lbl = alib.dom.createElement("div", dv);
		dv_lbl.innerHTML = "Would you like to save changes to this "+this.mainObject.title+" only or this and future "+this.mainObject.titlePl+"?";
		alib.dom.styleSet(dv_lbl, "padding-bottom", "5px");
		var dv_btn = alib.dom.createElement("div", dv);
		// This object only
		var btn = new CButton("This "+this.mainObject.title+" Only", 
								function(dlg, close, cls) {  dlg.hide(); cls.saveObject(close, "exception"); }, 
								[dlg, close, this], "b1");
		btn.print(dv_btn);
		// This and future
		var btn = new CButton("This &amp; Future "+this.mainObject.titlePl, 
								function(dlg, close, cls) {  dlg.hide(); cls.saveObject(close, "all"); }, 
								[dlg, close, this], "b2");
		btn.print(dv_btn);
		// Don't do anything
		var btn = new CButton("Cancel", function(dlg) { dlg.hide(); }, [dlg], "b3");
		btn.print(dv_btn);
		alib.dom.styleSet(alib.dom.createElement("div", dv), "clear", "both");

		dlg.customDialog(dv, 400, 50);

		return;
	}
	else if (recur_stype && this.mainObject.recurrencePattern)
		this.mainObject.recurrencePattern.save_type = recur_stype;

	// Fire before save event in plugins
	for (var i = 0; i < this.plugins.length; i++)
	{
		if (typeof this.plugins[i].beforesaved != "undefined")
			this.plugins[i].beforesaved();
	}
	
	// Check if a comment needs to be saved in a comments browser
	if (this.subLoader.objectBrowsers)
	{
		for (var i = 0; i < this.subLoader.objectBrowsers.length; i++)
		{
			if (this.subLoader.objectBrowsers[i].commentObj)
			{
				if (this.subLoader.objectBrowsers[i].commentObj.ta_comment.value)
				{
					var cobj = this.subLoader.objectBrowsers[i].commentObj;
					this.subLoader.objectBrowsers[i].saveComment(cobj.ta_comment.value, cobj.t, cobj.con);
				}
			}
		}
	}

	// Create loading div
	var dlg = new CDialog();
	var dv_load = alib.dom.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Saving, please wait...";
	dlg.statusDialog(dv_load, 150, 100);
	this.plugin_saved_processed = 0; // Used to keep track of how many plugins have been processed

	this.mainObject.m_dlg = dlg;
	this.mainObject.m_close = close;
	this.mainObject.olCls = this;
	this.mainObject.onsave = function()
	{
		// Reload values - often times values will update on server save
		this.onload = function() { }
		this.load();

		if (this.olCls.plugins.length)
		{
			for (var i = 0; i < this.olCls.plugins.length; i++)
			{
				if (this.olCls.plugins[i].frmLoaded) // Only save changes for plugins that have been loaded in the form
				{
					this.olCls.plugins[i].statusDlg = this.m_dlg;
					this.olCls.plugins[i].frmClose = this.m_close;
					this.olCls.plugins[i].olCls = this.olCls;
					this.olCls.plugins[i].onsave = function()
					{
						this.olCls.pluginSaved(this.statusDlg, this.frmClose);
					}
					this.olCls.plugins[i].save();
				}
				else
				{
					this.olCls.pluginSaved(this.m_dlg, this.m_close);
				}
			}
		}
		else
		{
			this.olCls.saveDone(this.m_dlg, this.m_close);
		}
        this.olCls.oid = this.id;

		// If object was cloned then copy references and clear
		if (this.olCls.cloneObject)
		{
			var ajax = new CAjax("json");
			ajax.exec("controller/Object/cloneObjectReferences", [
					["obj_type", this.obj_type], ["oid", this.id], ["from_id", this.olCls.cloneObject]
			]);

			this.olCls.cloneObject = false; // Clone is done
		}
	}

	this.mainObject.onsaveError = function(msg)
	{
		this.m_dlg.hide();
		if (msg)
		{
			alert(msg);
			//ALib.statusShowAlert(msg, 3000, "middle", "center");
		}
		else
			ALib.statusShowAlert("ERROR SAVING CHANGES!", 3000, "bottom", "right");
	}
    
    // If this is a cloned object, reset the this.mainObject class
    if(this.cloneObject)
	{
        this.mainObject.id = null;
		this.mainObject.setValue("id", "");
	}
    
	this.mainObject.save();
    this.setTitle(this.mainObject.getValue("name"));
}

/**
 * Callback function called once the object has been saved
 *
 * @param {CDialog} dlg Passed if browser is in modular(dialog) mode
 * @param {bool} close If true then we can close this object window
 */
AntObjectLoader.prototype.pluginSaved = function(dlg, close)
{
	this.plugin_saved_processed++;
	if (this.plugin_saved_processed == this.plugins.length)
		this.saveDone(dlg, close);
}

/**
 * Initiate object deletion
 */
AntObjectLoader.prototype.deleteObject = function(recur_stype)
{
	// "This [oname] only" and "This And All Future [oname]" and "Cancel" dialog if event is recurring
	if (this.mainObject.recurrencePattern != null && this.mainObject.recurrencePattern.id && !recur_stype)
	{
		var dlg = new CDialog("Recurring Series");

		var dv = alib.dom.createElement("div");

		var dv_lbl = alib.dom.createElement("div", dv);
		dv_lbl.innerHTML = "Would you like to delete this "+this.mainObject.title+" only or this and future "+this.mainObject.titlePl+"?";
		alib.dom.styleSet(dv_lbl, "padding-bottom", "5px");
		var dv_btn = alib.dom.createElement("div", dv);
		// This object only
		var btn = new CButton("This "+this.mainObject.title+" Only", 
								function(dlg, close, cls) {  dlg.hide(); cls.deleteObject("exception"); }, 
								[dlg, close, this], "b1");
		btn.print(dv_btn);
		// This and future
		var btn = new CButton("This &amp; Future "+this.mainObject.titlePl, 
								function(dlg, close, cls) {  dlg.hide(); cls.deleteObject("all"); }, 
								[dlg, close, this], "b2");
		btn.print(dv_btn);
		// Don't do anything
		var btn = new CButton("Cancel", function(dlg) { dlg.hide(); }, [dlg], "b3");
		btn.print(dv_btn);
		alib.dom.styleSet(alib.dom.createElement("div", dv), "clear", "both");

		dlg.customDialog(dv, 400, 50);

		return;
	}
	else if (recur_stype && this.mainObject.recurrencePattern)
	{
		this.mainObject.recurrencePattern.save_type = recur_stype;
		this.deleteObjectDo();
		// User already indicated what they would like to delete so no need to ask them again
		return;
	}

	// Standard delete
	ALib.Dlg.confirmBox("Are you sure you want to delete this "+this.mainObject.title+"?", "Delete " + this.mainObject.title, [this]);
	ALib.Dlg.onConfirmOk = function(cls)
	{
		cls.deleteObjectDo();
	}
}

/**
 * Perform object deletion
 */
AntObjectLoader.prototype.deleteObjectDo = function()
{
	var close = (typeof close != "undefined") ? close : false;

	// Create loading div
	var dlg = new CDialog();
	var dv_load = alib.dom.createElement('div');
	alib.dom.styleSetClass(dv_load, "statusAlert");
	alib.dom.styleSet(dv_load, "text-align", "center");
	dv_load.innerHTML = "Deleting, please wait...";
	dlg.statusDialog(dv_load, 150, 100);

	this.plugin_delete_processed = 0; // Used to keep track of how many plugins have been processed
	this.mainObject.m_dlg = dlg;
	this.mainObject.m_close = close;
	this.mainObject.olCls = this;
	this.mainObject.onremove = function()
	{
		if (this.olCls.plugins.length)
		{
			for (var i = 0; i < this.olCls.plugins.length; i++)
			{
				if (this.olCls.plugins[i].remove) // Only call if remove function exists
				{
					this.olCls.plugins[i].statusDlg = this.m_dlg;
					this.olCls.plugins[i].olCls = this.olCls;
					this.olCls.plugins[i].onremove = function()
					{
						this.olCls.pluginDeleted(this.statusDlg);
					}
					this.olCls.plugins[i].remove();
				}
				else
				{
					this.olCls.pluginDeleted(this.m_dlg);
				}
			}
		}
		else
		{
			this.olCls.deleteDone(this.m_dlg);
		}
	}
	this.mainObject.onremoveError = function()
	{
		this.m_dlg.hide();
		ALib.statusShowAlert("ERROR DELETING OBJECT!", 3000, "bottom", "right");
	}
	this.mainObject.remove();
}

/**
 * Set pluging deletion processing as complete
 *
 * @param CDialog dlg the "Please wait" dialog to be closed 
 */
AntObjectLoader.prototype.pluginDeleted = function(dlg)
{
	this.plugin_delete_processed++;
	if (this.plugin_delete_processed == this.plugins.length)
		this.deleteDone(dlg);
}

/**
 * All deletion is finalized
 *
 * @param CDialog dlg the "Please wait" dialog to be closed 
 */
AntObjectLoader.prototype.deleteDone = function(dlg)
{
	dlg.hide();
	this.close();
	this.onRemove();
	alib.events.triggerEvent(this, "remove");
}

/**
 * Remove the deleted flag for this object
 */
AntObjectLoader.prototype.undeleteObject = function(oid, dv)
{                          
    ajax = new CAjax('json');
    ajax.cbData.con = this.noticeCon;
    ajax.onload = function(ret)
    {
        ALib.statusShowAlert("Item has been restored!", 3000, "bottom", "right");
        this.cbData.con.removeChild(dv);
    };
    ajax.exec("/controller/Object/undeleteObject",
                [["obj_type", this.mainObject.obj_type], ["oid", oid]]);
}

AntObjectLoader.prototype.hide = function()
{
	this.mainCon.style.display = "none";
}

AntObjectLoader.prototype.show = function()
{
	this.mainCon.style.display = "block";
}

AntObjectLoader.prototype.close = function()
{
	// Cancel all auto-refresh
	if (this.subLoader)
	{
		for (var i in this.subLoader.objectBrowsers)
			this.subLoader.objectBrowsers[i].setAutoRefresh(0);
	}

	alib.events.triggerEvent(this, "close");
	this.onClose();
}

// This function can be over-ridden
AntObjectLoader.prototype.onClose = function()
{
	if (this.isPopup)
		window.close();
}
// This function can be over-ridden
AntObjectLoader.prototype.onSave = function()
{
}
// This function can be over-ridden
AntObjectLoader.prototype.onRemove = function()
{
}

AntObjectLoader.prototype.saveDone = function(dlg, close)
{
	var close = (typeof close != "undefined") ? close : false;

	dlg.hide();

	ALib.statusShowAlert(this.mainObject.title + " Saved!", 3000, "bottom", "right");

	if (!this.oid)
		this.oid = this.mainObject.id;

	if (close)
	{
		this.close();
	}
	else
	{
		var sl = this.getSubloader();
		if (sl)
			sl.toggleEdit(false);
	}
	
	alib.events.triggerEvent(this, "save");
	this.onSave();
}

AntObjectLoader.prototype.onValueChange = function(name, value, valueName)
{
	var field = this.mainObject.getFieldByName(name);
	// Check for duplicates
	if (!this.mainObject.id && field.type == "text")
	{
		this.noticeCon.innerHTML = "";
		var dupdiv = alib.dom.createElement("div", this.noticeCon);
		dupdiv.style.display = "none";

		var fValIsSet = false; // Make sure we do not run query on empty object
		var ob = new AntObjectBrowser(this.mainObject.name);
		ob.limit = 3;
		ob.cbData.antobjloaderDupDiv = dupdiv;
		ob.onLoad = function(numobjs)
		{
			this.cbData.antobjloaderDupDiv.style.display = (numobjs) ? "block" : "none";
		}

		var fields = this.mainObject.getFields();
		for (var i = 0; i < fields.length; i++)
		{
			var field = fields[i];
			var val = this.mainObject.getValue(field.name);
			if (val && val!='Untitled' && val!='New Contact' && field.type == "text" && field.system && field.name !='address_default')
			{
				ob.addCondition('and', field.name, 'contains', val);
				fValIsSet = true;
			}
			else if (val && field.type == "timestamp" && !field.readonly)
			{
				ob.addCondition('and', field.name, 'is_equal', val);
			}
		}

		if (fValIsSet)
		{
			ob.printInline(dupdiv, true, "Possible Duplicates");
			ob.loaderCls = this.loaderCls;
			var closeLnk = alib.dom.createElement("div", dupdiv);
			alib.dom.styleSet(closeLnk, "text-align", "center");
			alib.dom.styleSet(closeLnk, "margin-bottom", "5px");
			var a = alib.dom.createElement("a", closeLnk);
			a.href = "javascript:void(0)";
			a.dupdiv = dupdiv;
			a.onclick = function() { this.dupdiv.style.display = "none"; };
			a.innerHTML = "close";
		}
	}
}

/**
 * Set the value of mainObject or queue it if the object has not finished loading
 *
 * @param {string} feild The name of the field to set
 * @param {mixed} value The value to set the field to
 */
AntObjectLoader.prototype.setValue = function(field, value)
{
	var fdef = this.mainObject.getFieldByName(field);
	if (fdef)
	{
		if (fdef.type == "bool")
		{
			switch (value)
			{
			case "false":
			case "f":
			case "0":
			case "no":
				value = false;
				break;
			case "true":
			case "t":
			case "1":
			case "yes":
				value = true;
			}
		}
	}

	if (this.oid && !this.mainObject.loaded)
	{
		if (!this.queueValues)
			this.queueValues = new Array();

		this.queueValues[this.queueValues.length] = {field:field, value:value};
	}
	else
	{
		this.mainObject.setValue(field, value);
		if (this.subLoader)
			this.subLoader.toggleEdit(this.subLoader.editMode); // refresh values
	}
}

/**
 * Get the value of mainObject or queue it if the object has not finished loading
 *
 * @param {string} feild The name of the field to get
 */
AntObjectLoader.prototype.getValue = function(field)
{
	var fdef = this.mainObject.getFieldByName(field);

	if (this.oid && !this.mainObject.loaded)
	{
		if (!this.queueValues)
			this.queueValues = new Array();

		for (var i in this.queueValues)
			if (this.queueValues[i].field == field)
				return this.queueValues[i].value;
	}
	else
	{
		return this.mainObject.getValue(field);
	}

	return null;
}

/*************************************************************************
*	Function:	refreshReferences
*
*	Purpose:	Reload/refresh referenced object of type if inline browser
**************************************************************************/
AntObjectLoader.prototype.refreshReferences = function(otype)
{
}

/*************************************************************************
*	Function:	refreshField
*
*	Purpose:	Reload/refresh value for a field
**************************************************************************/
AntObjectLoader.prototype.refreshField = function(fname)
{
	// 1. get value
	// 2. call this.setValue(fname, value)
}

AntObjectLoader.prototype.onObjectLoaded = function()
{
	console.log(this.mainObject.getValue("groups"));
	if (this.queueValues && this.queueValues.length)
	{
		for (var i = 0; i < this.queueValues.length; i++) {
			this.mainObject.setValue(this.queueValues[i].field, this.queueValues[i].value);
		}

		if (this.subLoader)
			this.form.toggleEdit(this.subLoader.editMode); // refresh values
	}
}

/**
 * Refresh the form values
 */
AntObjectLoader.prototype.refresh = function()
{
	if (this.subLoader)
		this.form.toggleEdit(this.subLoader.editMode); // refresh field values
}

/*************************************************************************
*	Function:	setAntView
*
*	Purpose:	Set AntView for loading objects
**************************************************************************/
AntObjectLoader.prototype.setAntView = function(parentView)
{
	this.antView = parentView;
	this.antView.options.ldrcls = this;
	this.antView.onresize = function()
	{
		this.options.ldrcls.resize();
	}

    this.antView.fromClone = false;
    if(typeof this.antView.options.cloneId != "undefined" && this.antView.options.cloneId != null && this.oid == "")
    {
        this.oid = this.antView.options.cloneId;
        this.mainObject = new CAntObject(this.objType, this.oid);
        
        this.cloneObject = this.antView.options.cloneId;
        this.antView.options.cloneId = null;
        this.antView.fromClone = true;
    }
}

/*************************************************************************
*    Function:    loadNewWindowValues
*
*    Purpose:    Loads the posted values from inline form to new window form
*                This function is only executed if there's now oid specified
**************************************************************************/
AntObjectLoader.prototype.loadNewWindowValues = function()
{
    if(this.newWindowArgs)
    {
        this.mainObject.newWindowArgs = this.newWindowArgs;            
        for(fieldName in this.newWindowArgs)
        {
            var invalidFieldName = false;
            switch(fieldName)                
            {
                case "fromInlineForm":
                case "editMode":
                    invalidFieldName = true;
                    break;
                default:
                    break;
            }
            
            if(invalidFieldName)
                break;
            
            var fieldType = this.newWindowArgs[fieldName][1];
            var fieldValue = this.newWindowArgs[fieldName][0];            
            switch(fieldType)
            {
                case "fkey_reference":
                case "object_reference":
                case "object_multi_reference":                        
                case "fkey_multi_reference":                        
                    break;                    
                case "fkey":
                case "obj":                        
                    var fieldKey = this.newWindowArgs[fieldName][0];
                    var fieldVal = this.newWindowArgs[fieldName + "Fkey"][0];                            
                    this.mainObject.setValue(fieldName, fieldKey, unescape_utf8(fieldVal));
                    break;
                    
                case "fkey_multi":                        
                case "object_multi":                        
                    var multiKey = unescape_utf8(this.newWindowArgs[fieldName][0]).split(",");
                    var multiTitle = unescape_utf8(this.newWindowArgs[fieldName + "Multi"][0]).split(",");
                    for(var multiArg = 0; multiArg < multiKey.length; multiArg++)
                    {
                        if(multiKey[multiArg])
                            this.mainObject.setMultiValue(fieldName, multiKey[multiArg], multiTitle[multiArg]);
                    }                        
                break;
                
                case "bool":                        
                    if(fieldValue=="t")
                        fieldValue = true;
                    else if(fieldValue=="f")
                        fieldValue = false;
                        
                    this.mainObject.setValue(fieldName, fieldValue);
                break;
                
                default:                        
                    this.mainObject.setValue(fieldName, unescape(fieldValue));
                    break;
            }                                
        }
    }
}

/*************************************************************************
*    Function:    buildFormInput
* 
*    Purpose:    Build form inputs inside table
**************************************************************************/
AntObjectLoader.prototype.buildFormInput = function(inputFormData, tbody)
{    
    for(formData in inputFormData)
    {
        // Row Label
        var rowInput = inputFormData[formData];
        
        if(!rowInput)
            continue;
        
        var tr = alib.dom.buildTdLabel(tbody, rowInput.label, rowInput.labelWidth);
        switch(rowInput.type)
        {            
            case "checkbox":
                var td = tr.firstChild;
                td.innerHTML = "";
                td.setAttribute("colspan", 2);
                alib.dom.styleSetClass(td, "formValue");
                td.appendChild(rowInput);
                if(rowInput.label)
                {
                    var label = alib.dom.createElement("label", td);
                    label.innerHTML = rowInput.label;
                }
                break;            
            case "hidden":
                alib.dom.styleSet(tr, "display", "none");
                var td = tr.firstChild;
                td.setAttribute("colspan", 2);
                td.appendChild(rowInput);
                break;            
            default:
                var td = alib.dom.createElement("td", tr);        
                alib.dom.styleSetClass(td, "formValue");                        
                try
                {
                    td.appendChild(rowInput);
                }
                catch(e)
                {
                    continue;
                }
                break;
        }
        
        if(rowInput.inputLabel)
        {
            var label = alib.dom.createElement("label", td);
            label.innerHTML = rowInput.inputLabel;
            alib.dom.styleSet(label, "fontSize", "11px");
        }
    }
    
    // return the last tr
    return tr;
}

/*************************************************************************
*    Function:    buildFormInputDiv
* 
*    Purpose:    Build form inputs inside div
**************************************************************************/
AntObjectLoader.prototype.buildFormInputDiv = function(inputFormData, con, setClear, marginRight)
{
    if(typeof marginRight == "undefined")
        marginRight = "3px";
        
    con.innerHTML = "";
    for(formData in inputFormData)
    {
        // Row Label
        var rowInput = inputFormData[formData];
        
        if(!rowInput)
            continue;
        
        switch(rowInput.type)
        {            
            default:
                var divCon = alib.dom.createElement("div", con);
                alib.dom.styleSet(divCon, "float", "left");
                alib.dom.styleSet(divCon, "marginRight", marginRight);
                try
                {
                    divCon.appendChild(rowInput);
                }
                catch(e)
                {
                    continue;
                }
                
                if(rowInput.label)
                {
                    var label = alib.dom.createElement("label", divCon);                    
                    alib.dom.styleSet(label, "fontSize", "11px");
                    label.innerHTML = rowInput.label;
                    
                    if(rowInput.floatDir)
                        alib.dom.styleSet(label, "float", rowInput.floatDir);
                    else
                        alib.dom.styleSet(label, "float", "right");
                        
                    if(rowInput.labelWidth)
                        alib.dom.styleSet(label, "width", rowInput.labelWidth);
                }
                break;
        }
        
        if(setClear)
        {
            alib.dom.styleSet(divCon, "marginBottom", "5px");
            alib.dom.divClear(con);
        }            
            
    }
    
    // return the last tr
    return divCon;
}

/*************************************************************************
*    Function:    buildDropdown
* 
*    Purpose:    builds the dropdown using the array
**************************************************************************/
AntObjectLoader.prototype.buildDropdown = function(objElement, dataArray, currentValue)
{
    for(data in dataArray)
    {
        var currentData = dataArray[data];
        var objLen = objElement.length;
        var selected = false;
        
        if(typeof currentData == "object")
        {
            var value = currentData[0];
            var text = currentData[1];
        }        
        else
        {
            var value = currentData;
            var text = currentData;
        }
        
        if(currentValue == value)
            selected = true;
        
        objElement[objLen] = new Option(text, value, false, selected);
    }
}

/**
 * Open an object form by id
 *
 * If view is avaiable then a view will be used to load the form
 *
 * @param {string} obj_type Optional manual object type to load. By default this.obj_type is used.
 * @param {int} id The id of the object to load
 * @param {Array} param_fwd Array of array of params [['pname', 'pvalue']]
 */
AntObjectLoader.prototype.loadObjectForm = function(obj_type, id, param_fwd)
{
	var params = (param_fwd) ? param_fwd : new Array();

	var dlg = new CDialog("");

	/*
	for (var i = 0; i < params.length; i++)
	{
		if (params[i][0] == "associations")
			params[params.length] = ["associations[]", params[i][1]];
		else
			params[params.length] = [params[i][0], params[i][1]];
	}
	*/

	var oid = (id) ? id : "";

	var url = '/obj/' + obj_type;
	if (oid)
		url += '/' + oid;

	/*
	var oldScrollTop = alib.dom.getScrollPosTop();
	this.hide();
	*/

	var objfrmCon = alib.dom.createElement("div", this.outerCon);
	alib.dom.styleSet(objfrmCon, "height", "100%");
	alib.dom.styleSet(objfrmCon, "overflow", "auto");
	objfrmCon.cls = this;
	objfrmCon.dlg = dlg;
	//objfrmCon.oldScrollTop = oldScrollTop;
	objfrmCon.close = function()
	{                    
		/*
		this.style.display = "none";
		objfrmCon.cls.show();
		objfrmCon.cls.outerCon.removeChild(this);
		alib.dom.setScrollPosTop(this.oldScrollTop);
		*/
		this.dlg.hide();
	}

	// Print object loader 
	var ol = new AntObjectLoader(obj_type, oid);

	// Set params
	for (var i = 0; i < params.length; i++)
	{
		if (params[i][0] == "associations")
			ol.mainObject.setMultiValue('associations', params[i][1]);
		else
			ol.setValue(params[i][0], params[i][1]);
	}
			
	ol.print(objfrmCon, this.isPopup);
		
	ol.objfrmCon = objfrmCon;
	ol.objBrwsrCls = this;
	ol.onClose = function()
	{
		this.objfrmCon.close();
	}
	ol.onSave = function()
	{
		// TODO: trigger form event to refresh browsers with this type
	}
	ol.onRemove = function()
	{
		// TODO: trigger form event to refresh browsers with this type
	}

	dlg.customDialog(objfrmCon, 900, getWorkspaceHeight());
}

/**
 * Mark this object as seen
 */
AntObjectLoader.prototype.markSeen = function()
{
	if (this.mainObject.id)
	{
		var ajax = new CAjax("json");
		ajax.exec("/controller/Object/markSeen", 
					[["obj_type", this.mainObject.name], ["oid", this.mainObject.id]]);
	}
}
