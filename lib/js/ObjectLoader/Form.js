/**
 * @fileoverview This is the default sub-loader for object loaders
 *
 * This call parses UIXML definition for object forms. If no xml
 * definition exists, then the class will just list an editable form
 * with basic input elements based on the object field types.
 *
 * @author    joe, sky.stebnicki@aereus.com
 *             Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObjectLoader_Form.
 *
 * @constructor
 * @param {CAntObject} obj Handle to object that is being viewed or edited
 * @param {AntObjectLoader} loader Handle to base loader class
 */
function AntObjectLoader_Form(obj, loader)
{
    this.inputs = new Array();
    this.editMode = (obj.id) ? false : true;
    this.mainObject = obj;
    this.curFieldTbl = null; // Changes to the current col/span
    this.loaderCls = (loader) ? loader : null;    
	/*
    this.tabs = new CTabs();
    this.loaderCls.tabs = this.tabs;
	*/
	this.tabs = null;
    this.loadReports = new Array(); // Used to spool reports to load after form is built
    this.membersObject = null; // Set if members are being managed for this project
    this.attachmentObject = null; // Set if attachment are being managed for this project
    this.toolbar = null; // CToolbar

    this.xmlFormLayout = AntObjectForms.getFormXml(obj.name, loader.formScope); //obj.xmlFormLayout; 
    this.plugins = new Array();

    this.watchChanges = new Array(); // watch for changes and perform an optional action in obj{field, condval, onchange:function)
    
    /**
     * Generic callback properties buffer
     *
     * @var {Object}
     */
    this.cbData = new Object();

	/**
	 * Array of object lists referenced in this form
	 *
	 * @private
	 * @var {Array}
	 */
	this.objectBrowsers = new Array();

	/**
	 * Objects that are refernced by an id in this form
	 *
	 * @private
	 * @var {Array}
	 */
	this.objectsRef = new Array();
}

/**
 * Refresh the form
 */
AntObjectLoader_Form.prototype.refresh = function()
{
    this.toggleEdit(this.editMode);
}

/**
 * Resize form portion
 */
AntObjectLoader_Form.prototype.resize = function()
{
	var parentHeight = alib.dom.getElementHeight(this.loaderCls.formCon);
	var formHeight = alib.dom.getElementHeight(this.formCon);

	// Look for any text areas that have the fillAvailableHeight set to true if in edit mode
	if ((parentHeight - formHeight) == 0 || !this.editMode)
		return;

	for (var i = 0; i < this.inputs.length; i++)
	{
		var inputObj = this.inputs[i];

		if (typeof inputObj.inp != "undefined" && 
			inputObj.ftype == "text" && (inputObj.options.rich == true || inputObj.options.multiLine == true))
		{
			var inpHeight = alib.dom.getElementHeight(inputObj.dv_inp);
			var ret = inputObj.inp.setHeight((inpHeight + (parentHeight - formHeight) - 10) + "px"); // -10 for margins
			if (ret)
				return true;
		}
	}

}

/**
 * Enable to disable edit mode for this loader
 *
 * @param {bool} setmode True for edit mode, false for read mode
 */
AntObjectLoader_Form.prototype.toggleEdit = function(setmode)
{
    if (typeof setmode != "undefined")
        this.editMode = setmode;
    else
        this.editMode = (this.editMode) ? false : true;

	/* NOTE: We now rebuild the toolbar with each toggle
    if (this.editMode)
        this.btnEdit.setText("Finished Editing");
    else
        this.btnEdit.setText("Edit");
	*/

	alib.events.triggerEvent(this, "changemode");

    var lbl = this.mainObject.getLabel();
    if (!lbl)
        lbl = "New " + this.mainObject.title;
    else
        lbl = lbl;
    this.onNameChange(lbl);
    
    // Set ANT View title
    if (this.loaderCls.antView)
        this.loaderCls.antView.setTitle(lbl);

	// Rebuild toolbar
	this.buildToolbar();

    // Rerfresh all the values in the input boxes
    for (var i = 0; i<this.inputs.length; i++)
    {
        if (this.editMode)
        {
            if(this.inputs[i].fieldRow)
                this.inputs[i].fieldRow.setAttribute("style", "");
                
            this.inputs[i].dv_text.style.display = "none";
            this.inputs[i].dv_inp.style.display = "block";

            if (this.inputs[i].dv_inp.inpRef && this.inputs[i].dv_inp.inptType == "input")
            {
                this.inputs[i].dv_inp.inpRef.skiponchange = true; // Prevent loops

                if (this.inputs[i].dv_inp.inpRef.part)
                {
                    this.inputs[i].dv_inp.inpRef.value = this.mainObject.getInputPartValue(this.inputs[i].fname, 
                                                                                           this.mainObject.getValue(this.inputs[i].fname),
                                                                                           this.inputs[i].dv_inp.inpRef.part);
                }
                else
                {
                    this.inputs[i].dv_inp.inpRef.value = this.mainObject.getValue(this.inputs[i].fname);
                }

                this.inputs[i].dv_inp.inpRef.skiponchange = false;
            }

			// Show all hidden fieldsets
			if (this.inputs[i].fieldSet)
				this.inputs[i].fieldSet.show();
        }
        else
        {
            this.inputs[i].dv_inp.style.display = "none";
            this.inputs[i].dv_text.style.display = "block";
            this.setFieldTextDisplay(this.inputs[i].fname, null, this.inputs[i].fieldRow);
        }
    }
    
    // Trigger the edit mode for main object plugins
    this.mainObject.toggleEdit(this.editMode);
    
	// Refresh all object browsers embedded inline
	for (var i = 0; i < this.objectBrowsers.length; i++)
	{
		this.objectBrowsers[i].refresh();
	}
}

/**
 * Print form on 'con'
 *
 * @param {DOMElement} con A dom container where the form will be printed
 * @param {array} plugis List of plugins that have been loaded for this form
 */
AntObjectLoader_Form.prototype.print = function(con, plugins)
{
    if (plugins)
        this.plugins = plugins;

    con.innerHTML = "";
    
    // Set the page title
    this.pageTitle = alib.dom.createElement("div", this.loaderCls.toolbarCon);
    
    // Create a container for form inputes
    this.formCon = alib.dom.createElement("div", con);
    
    // Create Toolbar Buttons
    this.buildToolbar();
    
    //alib.dom.styleSetClass(this.pageTitle, "formTitle");
    this.onNameChange = function(name)
    {
        //this.pageTitle.innerHTML = name;
        if (this.loaderCls && this.loaderCls.ctbl)
            this.loaderCls.ctbl.setTitle(name);
    }    

	// Clear object browsers list
	this.objectBrowsers = new Array();

    var lbl = this.mainObject.getLabel();
    if (!lbl)
        lbl = "New " + this.mainObject.title;
    this.onNameChange(lbl);

    // Set ANT View title
    if (this.loaderCls.antView)
        this.loaderCls.antView.setTitle(lbl);
    
    if (this.xmlFormLayout.m_text == "*")
    {
        var tbl = alib.dom.createElement("table", this.formCon);
        alib.dom.styleSet(tbl, "width", "100%");
        var tbody = alib.dom.createElement("tbody", tbl);

        var fields = this.mainObject.getFields();
        for (var i = 0; i < fields.length; i++)
        {
            var field = fields[i];
            var row = alib.dom.createElement("tr", tbody);
            row.vAlign = "top";

            if (field.name == "account_id") // hidden
                continue;

            this.printField(field, row);
        }
    }
    else
    {
        // Print before form is created because CRte cannot handle being hidden
        if (this.formHasTabs(this.xmlFormLayout))
            this.tabs.print(this.formCon);

        try
        {            
            this.buildForm(this.formCon, this.xmlFormLayout);
            this.formLoaded();
        }
        catch (e)
        {
            alert(e.message+"\n"+"Line: " + e.lineNumber);
        }
    }
    
	// Set viewed
	this.mainObject.setViewed();
}

/**
 * Build toolbar
 */
AntObjectLoader_Form.prototype.buildToolbar = function()
{
	this.loaderCls.toolbarCon.innerHTML = "";

    var tb = new CToolbar();
    this.toolbar = tb;
    
    // Add plugin buttons - position: first
    if (this.loaderCls.pluginToolbarEntries.length)
    {
        for (var i = 0; i < this.loaderCls.pluginToolbarEntries.length; i++)
        {
            var entry = this.loaderCls.pluginToolbarEntries[i];

            if(entry.pos == "first")
            {
                var button = alib.ui.Button(entry.label, {
                    className:"b1", tooltip:entry.label, callback:entry.callback, cbData:entry.cbData,
                    onclick:function() { if (this.callback) this.callback(this.cbData); }
                });
                tb.AddItem(button.getButton(), "left");
            }
        }
    }
    
	// Close button
	var btn = alib.ui.Button("<img src='/images/icons/return_16.png' />", {
		className:"b1", tooltip:"Go back and close this " + this.mainObject.title.toLowerCase(), 
		ldr:this.loaderCls, obj:this.mainObject,
		onclick:function() {
			var close = true;

			if (this.obj.isDirty())
			{
				var close = confirm("Close without saving changes?");
			}

			if (close)
				this.ldr.close();
		}
	});
	if (this.loaderCls.fEnableClose && !this.loaderCls.isMobile) // If mobile version the top back button will serve as a cancel
		tb.AddItem(btn.getButton(), "left");

    if (this.mainObject.security.edit && this.editMode)
    {
		var btn = new CButton("Save &amp; Close", function(cls, close){ cls.saveObject(close); }, [this.loaderCls, true], "b1 grLeft");
		if (this.loaderCls.fEnableClose && !this.loaderCls.isMobile) // If mobile preserve space
			tb.AddItem(btn.getButton(), "left");
        
		// No save and close so don't group
		var saveClass = (!this.loaderCls.fEnableClose || this.loaderCls.isMobile) ? "b2" : "b1 grRight";

        var btn = new CButton("Save Changes", function(cls){ cls.saveObject(false); }, [this.loaderCls], saveClass);
        tb.AddItem(btn.getButton(), "left");

		// Only add cancel edit if this is a new object
		if (this.mainObject.id)
		{
        	this.btnEdit = new CButton("Cancel Edit", function(form) { /* TODO: check for dirty & prompt */ form.toggleEdit(); }, [this], "b1");
        	tb.AddItem(this.btnEdit.getButton(), "left");
		}
    }

    if (this.mainObject.security.edit && !this.editMode)
    {
        this.btnEdit = new CButton("Edit", function(form) { form.toggleEdit(); }, [this], "b2");
        tb.AddItem(this.btnEdit.getButton(), "left");
	}

    if (this.mainObject.id)
    {
        if (this.mainObject.security.del && this.editMode)
        {
            var btn = new CButton("Delete", function(cls, oid){ cls.deleteObject(); }, [this.loaderCls, this.mainObject.id], "b3");
            tb.AddItem(btn.getButton(), "left");
        }
    }    

	// Add print button
	var btn = alib.ui.Button("<img src='/images/icons/print_16.png' />", {
		className:"b1", tooltip:"Print this " + this.mainObject.title, mObject:this.mainObject,
		onclick:function() {
			if (!this.mObject.id)
			{
				alert("Please save changes before printing");
				return;
			}

			window.open("/print/engine.php?obj_type=" + this.mObject.obj_type + "&objects[]=" + this.mObject.id);
		}
	});
    if (!this.loaderCls.isMobile && this.mainObject.name!="dashboard" && this.mainObject.id) // only in desktop mode and no printing dashboards yet
		tb.AddItem(btn.getButton());

	// If user has access to edit this object, and the object is not prive, then add security dropdown
    if (this.mainObject.security.edit && this.editMode && !this.mainObject.def.isPrivate)
    {
        this.ddPermissions = new CDropdownMenu();
        this.ddPermissions.addEntry("Edit Permissions for all " + this.mainObject.titlePl, 
                                    function(cls, form){ loadDacl(null, "/objects/"+cls.mainObject.name); }, 
                                    "/images/icons/permissions_16.png", null, [this.loaderCls, this]);        

		if (this.mainObject.id)
		{
			this.ddPermissions.addEntry("Edit Permissions for this " + this.mainObject.title, 
										function(cls, form){ loadDacl(null, "/objects/"+cls.mainObject.name+"/"+cls.mainObject.id, "/objects/"+cls.mainObject.name); }, 
										"/images/icons/permissions_16.png", null, [this.loaderCls, this]);        
		}

        for (var i = 0; i < this.mainObject.security.childObjects.length; i++)
        {
            var cldobj = new CAntObject(this.mainObject.security.childObjects[i]);
            this.ddPermissions.addEntry("Edit " + cldobj.title + " Permissions", 
                                        function(cls, form, objname){ loadDacl(null, "/objects/"+cls.mainObject.name+"/"+cls.mainObject.id+"/"+objname, "/objects/"+objname); }, 
                                        "/images/icons/permissions_16.png", null, [this.loaderCls, this, this.mainObject.security.childObjects[i]]);
        }

        if (!this.loaderCls.isMobile) // Preserve space
            tb.AddItem(this.ddPermissions.createButtonMenu("Permissions"), "left");

		// Clone Button
		if(this.loaderCls.antView && this.mainObject.id > 0)
		{
			var btn = alib.ui.Button("<img src='/images/icons/merge_10.png' /> Clone", {
				className:"b1", tooltip:"Clone this " + this.mainObject.title, callback:this.loaderCls, mObject:this.mainObject,
				onclick:function() 
				{
					this.callback.antView.options.cloneId = this.mObject.id;
					document.location.hash = "#" + this.callback.cbData.parentPath + "/" + this.mObject.name + ":";
				}
			});
			tb.AddItem(btn.getButton());
		}
    }
    
    // Display the next/prev arrows objects
    var arrowsCon = alib.dom.createElement("div");
    tb.AddItem(arrowsCon, "right");
    this.displayObjectArrows(arrowsCon);
    
    if(!this.loaderCls.isMobile)
    {
        // If we are inline, then display "Open In New Window" button
        if(!this.loaderCls.isPopup)
        {
            var params = 'width=1024,height=768,toolbar=no,menubar=no,scrollbars=yes,location=no,directories=no,status=no,resizable=yes';
            var btn = new CButton("", function(loaderCls) { loaderCls.openInNewWindow(); }, [this.loaderCls], "b1");
            
            var btn = alib.ui.Button("<img src='/images/icons/new_window_16.png' />", 
                        {
                            className:"b1", tooltip:"Click to open this in a new window", callback:this.loaderCls,
                            onclick:function() 
                            {
                                this.callback.openInNewWindow();
                                this.callback.close();
                            }
                        });
            
            tb.AddItem(btn.getButton(), "right");
            
            if(this.mainObject.objectList && this.mainObject.objectList.length > 0)
            {
                for(object in this.mainObject.objectList)
                {
                    var currentObject = this.mainObject.objectList[object];
                }
            }
        }    
    }
    
	// Add plugin buttons - position: last
	if (this.loaderCls.pluginToolbarEntries.length)
	{
		for (var i = 0; i < this.loaderCls.pluginToolbarEntries.length; i++)
		{
			var entry = this.loaderCls.pluginToolbarEntries[i];

            if(entry.pos == "last")
            {
                var button = alib.ui.Button(entry.label, {
                    className:"b1", tooltip:entry.label, callback:entry.callback, cbData:entry.cbData,
                    onclick:function() { if (this.callback) this.callback(this.cbData); }
                });
                tb.AddItem(button.getButton(), "left");
            }
		}
	}
    
    // Print the toolbar inside this.toolbarCon
    tb.print(this.loaderCls.toolbarCon);
}

/**
 * Displays the arrows for next/prev objects
 *
 * @private
 */
AntObjectLoader_Form.prototype.displayObjectArrows = function(con)
{
	if (typeof this.loaderCls.cbData.bwserCls == "undefined" || typeof this.mainObject.id == "undefined" || this.mainObject.id == null || this.mainObject.id == "")
		return;

    var clsObjectList = this.loaderCls.cbData.bwserCls.objectList;
    var clsAntView = this.loaderCls.cbData.antView;
    var clsParentPath = this.loaderCls.cbData.parentPath;
    
    var prevObjectId = null;
    var nextObjectId = null;
    if(clsObjectList && clsObjectList.length > 0)
    {
        for(obj in clsObjectList)
        {
            var currentObject = clsObjectList[obj];
            
            if(nextObjectId)
            {
                nextObjectId = currentObject.id;
                break;
            }
            
            if(this.mainObject.id == currentObject.id)
                nextObjectId = true;
            else
                prevObjectId = currentObject.id;
        }
    }
    
    if(!prevObjectId && !nextObjectId)
        return;
        
    var loadObject = function(objectId, objectType, parentPath)
    {
        if(!objectId)
            return;
        
        document.location.hash = "#" + parentPath + "/" + objectType + ":" + objectId;
    }
    
    // Previous Object
	var btn = alib.ui.Button("<img src='/images/icons/arrow_left_16.png' />", {
				className:"b1 grLeft", tooltip:"Load previous object", cls:this,
				poid:prevObjectId, otype:this.mainObject.name, pPath:clsParentPath,
				onclick:function() 
				{
					if(!this.poid)
						return;
					
					document.location.hash = "#" + this.pPath + "/" + this.otype + ":" + this.poid;
				}
			});
	btn.print(con);

    if(!prevObjectId)
		btn.disable();
	/*
    var btn = new CButton("", loadObject, [prevObjectId, this.mainObject.name, clsParentPath], "b1");
    var btnElem = btn.getButton();
    alib.dom.styleSet(btnElem, "background-image", "url(/images/icons/arrow_back_12.png)");
    alib.dom.styleSet(btnElem, "background-repeat", "no-repeat");
    alib.dom.styleSet(btnElem, "background-position", "center");
    con.appendChild(btnElem);
    
    if(!prevObjectId)
        alib.dom.styleSet(btnElem, "cursor", "no-drop");
	*/
    
    // Next Object
	var btn = alib.ui.Button("<img src='/images/icons/arrow_right_16.png' />", {
				className:"b1 grRight", tooltip:"Load next object", cls:this,
				noid:nextObjectId, otype:this.mainObject.name, pPath:clsParentPath,
				onclick:function() 
				{
					if(!this.noid)
						return;
					
					document.location.hash = "#" + this.pPath + "/" + this.otype + ":" + this.noid;
				}
			});
	btn.print(con);

    if(!nextObjectId)
		btn.disable();

	/*
    var btn = new CButton("", loadObject, [nextObjectId, this.mainObject.name, clsParentPath], "b1");
    var btnElem = btn.getButton();
    alib.dom.styleSet(btnElem, "background-image", "url(/images/icons/arrow_next_12.png)");
    alib.dom.styleSet(btnElem, "background-repeat", "no-repeat");
    alib.dom.styleSet(btnElem, "background-position", "center");    
    con.appendChild(btnElem);
    
    if(!nextObjectId)
        alib.dom.styleSet(btnElem, "cursor", "no-drop");
	*/
}

/**
 * Check if tabs should be printed for this form
 *
 * @private
 */
AntObjectLoader_Form.prototype.formHasTabs = function(node)
{
	var hasTabs = false;
    for (var i = 0; i < node.getNumChildren(); i++)
    {
        var child = node.getChildNode(i);
        if (child.m_name == "tab")
			hasTabs = true;
    }

	if (hasTabs && this.tabs == null)
	{
		this.tabs = new CTabs();
		this.loaderCls.tabs = this.tabs;
	}

    return hasTabs;
}

/**
 * Callback is fired any time a value changes for the mainObject 
 */
AntObjectLoader_Form.prototype.onValueChange = function(name, value, valueName)
{    
    for (var i = 0; i < this.watchChanges.length; i++)
    {
        if (this.watchChanges[i].field == name)
        {
            try
            {
                this.watchChanges[i].onchange(value);
            }
            catch (e)
            {
                //alert("AntObjectLoader_Form.prototype.onValueChange : " + e);
            }
        }
    }
    

    // Notifty plugins that the object has been saved
    if (name == "id" && value)
    {
        for (var i = 0; i < this.plugins.length; i++)
        {
			try
			{
				if (this.plugins[i].objectsaved)
					this.plugins[i].objectsaved();
			}
			catch (e)
			{
				alert("Problem calling objectsaved for " + this.plugins[i].name);
			}
        }
    }

    // If zipcode then set remaining fields
    var field = this.mainObject.getFieldByName(name);
    if (!field)
        return false;

    if (field.subtype == "zipcode")
    {
        var pre = "";
        var code_parts = name.split("_");
        if (code_parts.length == 2)
            pre = code_parts[0];

        this.loadAddressCityStateFromZip(value, pre);
    }
}

/**
 * Get city/state from zipcode if applicable
 */
AntObjectLoader_Form.prototype.loadAddressCityStateFromZip = function(zip, pre)
{
    /*var funct = function(ret, cls, pre)
    {
        if (!ret['error'])
        {               
            if (ret['state'])
                cls.loaderCls.setValue(((pre)?pre+"_state":"state"), ret['state']);
            if (ret['city'])
                cls.loaderCls.setValue(((pre)?pre+"_city":"city"), ret['city']);
        }
    }    
    var rpc = new CAjaxRpc("/controller/Customer/custGetZipData", "custGetZipData", [["zipcode", zip]], funct, [this, pre], AJAX_POST, true, "json");*/
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.pre = pre;
    ajax.onload = function(ret)
    {
        if (!ret['error'])
        {               
            if (ret['state'])
                this.cbData.cls.loaderCls.setValue(((this.cbData.pre)?this.cbData.pre+"_state":"state"), ret['state']);
            if (ret['city'])
                this.cbData.cls.loaderCls.setValue(((this.cbData.pre)?this.cbData.pre+"_city":"city"), ret['city']);
        }
    };
    ajax.exec("/controller/Customer/custGetZipData",
                [["zipcode", zip]]);
}

AntObjectLoader_Form.prototype.buildForm = function(con, node)
{
    // NOTE:  if a tag type is added then also add to "buildForm" in aereus.lib.php/CAntObjectApi
    // -----------------------------------------------------------------------------------------
    
    // Build table for grid
    var tbl = alib.dom.createElement("table", con);
    alib.dom.styleSet(tbl, "width", "100%");
    //tbl.cellPadding = 0;
    tbl.cellSpacing = 0;
    var tbody = alib.dom.createElement("tbody", tbl);
    var curRow = null;

    // First find out how many columns we are working with this at this level
    var numcols = 0;
    for (var i = 0; i < node.getNumChildren(); i++)
    {
        var child = node.getChildNode(i);
        if (child.m_name == "column")
            numcols++;
    }
    if (!numcols)
        numcols = 1;

    var curcol = 0;

    // Create form elements
    for (var i = 0; i < node.getNumChildren(); i++)
    {
        var child = node.getChildNode(i);

        var showif = unescape(child.getAttribute("showif"));
        var show = true;
        var onchangeobj = null;
        if (showif)
        {
            var parts = showif.split("=");
            if (parts.length == 2)
            {
                var field = this.mainObject.getFieldByName(parts[0]);
                if (field.type == "bool")
                    parts[1] = (parts[1] == 't') ? true : false;

                var val = this.mainObject.getValue(parts[0]);
                var valName = this.mainObject.getValueName(parts[0]);
                if ((parts[1] == "*" && !val) || ((val != parts[1] && valName != parts[1]) && parts[1]!="*"))
                    show = false;

                onchangeobj = new Object();
                onchangeobj.field = parts[0];
                onchangeobj.condval = parts[1];
                this.watchChanges[this.watchChanges.length] = onchangeobj; 
            }
        }

 
        switch (child.m_name)
        {
        case "tab":
            var tabcon = this.tabs.addTab(unescape(child.getAttribute("name")), function(cls) { cls.refresh(); }, [this]);
            this.buildForm(tabcon, child);
            break;

        case "plugin":
            var pname = unescape(child.getAttribute("name"));
            this.loadPlugin(pname, con);
            break;

        case "helptour":
            var type = child.getAttribute("type");
            var tourId = child.getAttribute("id");

			if (tourId)
			{
				var tourDiv = alib.dom.createElement("div", con);
				tourDiv.setAttribute("data-tour", tourId);
				if (type)
					tourDiv.setAttribute("data-tour-type", type);
			}
			
            break;

        case "recurrence":
            var options = new Object();
            options.hidelabel = (unescape(child.getAttribute("hidelabel"))=='t')?true:false;
            this.loadRecurrence(con, options);
            break;

        case "members":
            var mem = new AntObjectLoader_FormMem();
            this.plugins[this.plugins.length] = mem;
            mem.field = unescape(child.getAttribute("field"));
            this.loadPlugin("members", con); // "members" should be the same as this.name in Mem.js
            break;

        case "reminders":
            var rem = new AntObjectLoader_Reminders();
            this.plugins[this.plugins.length] = rem;
            rem.fieldName = child.getAttribute("field_name");
            rem.addDefault = (child.getAttribute("add_default") == 't') ? true : false;
            this.loadPlugin("reminders", con);
            break;
            
        case "attachments":            
            var objAttachments = new AntObjectLoader_FormAttachments();
            objAttachments.mainObject = this.mainObject;
            this.plugins[this.plugins.length] = objAttachments;            
            this.loadPlugin("attachments", con); // "attachments" should be the same as this.name in Attachment.js
            break;

        case "status_update":            
            var statusUpdate = new AntObjectLoader_StatusUpdate();
            statusUpdate.mainObject = this.mainObject;
            this.plugins[this.plugins.length] = statusUpdate;            
            this.loadPlugin("status_update", con); // "status_update" should be the same as this.name in StatusUpdate.js
            break;

        case "icon":            
            var imgCon = alib.dom.createElement("span", con);
            var width = (child.getAttribute("width")) ? child.getAttribute("width") : 48;
			var icon = this.mainObject.getIcon(48);
			if (icon)
				imgCon.innerHTML = "<img src=\"" + icon + "\" style='width:" + width + "px;' />";
            break;
            
        case "uname":
            var objUname = new AntObjectLoader_Uname();            
            objUname.mainObject = this.mainObject;
            this.plugins[this.plugins.length] = objUname;
            this.loadPlugin("uname", con); // "uname" should be the same as this.name in uname.js
            break;

        case "report":
            var rid = unescape(child.getAttribute("id"));
            var filterby = unescape(child.getAttribute("filterby"));

            if (this.mainObject.id)
            {
                this.loadReports[this.loadReports.length] = {rid:rid, filterby:filterby, con: con};
                //this.loadReport(rid, filterby, con);
            }
            else
            {
                con.innerHTML = "";
                onchangeobj = new Object();
                this.watchChanges[this.watchChanges.length] = onchangeobj; 
                onchangeobj.field = "id";
                onchangeobj.condval = null;
                onchangeobj.cls = this;
                onchangeobj.rid = rid;
                onchangeobj.filterby = filterby;
                onchangeobj.con = con;
                onchangeobj.onchange = function(val)
                {
                    if (val) // is a valid id
                    {
                        this.cls.loadReport(this.rid, this.filterby, this.con);
                    }
                }
            }
            break;

        case "fieldset":
            var titleCon = alib.dom.setElementAttr(alib.dom.createElement("div"), [["innerHTML", unescape(child.getAttribute("name"))]]);
            
            if (child && child.getAttribute("tooltip"))
            {
                var tooltipStr = unescape(child.getAttribute("tooltip"));
                
                var imageCon = alib.dom.setElementAttr(alib.dom.createElement("img", titleCon), [["src", "/images/icons/help_12.png"]]);                
                alib.dom.styleSet(imageCon, "marginLeft", "10px"); 
                alib.dom.styleSet(imageCon, "cursor", "help"); 
                
                // Set Tooltip
                alib.ui.Tooltip(imageCon, tooltipStr);
            }
            
            var frm = new CWindowFrame(titleCon);
            var frmcon = frm.getCon();
            frm.print(con);

			frmcon.fieldsetRef = frm;
            this.buildForm(frmcon, child);
            break;        

        case "objectsref":
            var obj_type = unescape(child.getAttribute("obj_type"));
            var ref_field = unescape(child.getAttribute("ref_field"));
            var ref_this = unescape(child.getAttribute("ref_this"));
            var name = unescape(child.getAttribute("name"));            
            if (!name) name = "View " + obj_type;

            if (obj_type)
            {
				this.objectsRef.push({
					objType: obj_type,
					refField: ref_field
				});

                if (this.mainObject.id && !this.loaderCls.cloneObject)
                {
                    var objb = null;
                    var objb = new AntObjectBrowser(obj_type);
					objb.setObjectContext(this.mainObject); // Load browser in context of this object
		
					this.objectBrowsers[this.objectBrowsers.length] = objb; // store reference for refreshing
                    
                    
                    if (this.loaderCls.antView)
                        objb.setAntView(this.loaderCls.antView);
                        
                    if (ref_field)
                    {
                        objb.setViewsFilter(this.mainObject.name);
                        
                        if(ref_this)
                            objb.setFilter(ref_field, this.mainObject.getValue(ref_this));
                        else                        
                            objb.setFilter(ref_field, this.mainObject.id);
                    }
                    else
                    {
                        objb.setFilter('associations', this.mainObject.name+":"+this.mainObject.id);                       
                    }

					// Add additional filters
					var filter = child.getChildNodeByName("filter")
					if (filter)
					{
						for (var i = 0; i < filter.getNumChildren(); i++)
						{
							var cond = filter.getChildNode(i);
							var blogic = cond.getAttribute("blogic");
							var field = cond.getAttribute("field");
							var operator = cond.getAttribute("operator");
							var value = cond.getAttribute("value");
							objb.addCondition(blogic, field, operator, value);
						}
					}

                    objb.obj_reference = this.mainObject.name+":"+this.mainObject.id;
                    objb.loaderCls = this.loaderCls;

                    if (this.loaderCls.isMobile && this.loaderCls.antView)
                    {
                        var viewRefObjects = this.loaderCls.antView.addView("ref-"+obj_type, {ob:objb});
                        viewRefObjects.render = function()
                        {
                            this.con.innerHTML = "";
                            this.options.ob.setAntView(this);
                            this.options.ob.print(this.con);
                        }
                        viewRefObjects.onshow = function() {  };

                        // Create browse button
                        var entry = alib.dom.createElement("article", con);
                        alib.dom.styleSetClass(entry, "nav");
                        var btn = alib.dom.createElement("a", entry);
                        btn.setAttribute("behavior", "selectable");
                        btn.href = "#" + this.loaderCls.antView.getPath() + "/" + "ref-"+obj_type;
                        btn.innerHTML = "<span class='icon'></span><h2><span class='more'></span>"+name+"</h2>";
                    }
                    else
                    {
                        objb.printInline(con);
                    }
                }
                else
                {
                    this.loaderCls.subLoaderParams.fromFormLoader;
                    if (!this.loaderCls.isMobile)
                        con.innerHTML = "Please save changes to view more details";
                    onchangeobj = new Object();
                    this.watchChanges[this.watchChanges.length] = onchangeobj; 
                    onchangeobj.field = "id";
                    onchangeobj.condval = null;
                    onchangeobj.cls = this;
                    onchangeobj.obj_type = obj_type;
                    onchangeobj.ref_field = ref_field;
                    onchangeobj.con = con;
                    onchangeobj.onchange = function(val)
                    {
                        if (val) // is a valid id
                        {
                            con.innerHTML = "";
                            if (this.ref_field)
                            {
                                var objb = new AntObjectBrowser(this.obj_type);
								this.cls.objectBrowsers[this.cls.objectBrowsers.length] = objb; // store reference for refreshing
                                if (this.cls.loaderCls.antView)
                                    objb.setAntView(this.cls.loaderCls.antView);
                                objb.setFilter(this.ref_field, this.cls.mainObject.id);
                                objb.setViewsFilter(this.cls.mainObject.name);
                                objb.obj_reference = this.cls.mainObject.name+":"+this.cls.mainObject.id;
                                objb.loaderCls = this.cls.loaderCls;
                                objb.printInline(this.con);
                            }
                            else
                            {
                                var objb = new AntObjectBrowser(this.obj_type);
								this.cls.objectBrowsers[this.cls.objectBrowsers.length] = objb; // store reference for refreshing
                                if (this.cls.loaderCls.antView)
                                    objb.setAntView(this.cls.loaderCls.antView);
                                objb.setFilter('associations', this.cls.mainObject.name+":"+this.cls.mainObject.id);
                                objb.obj_reference = this.cls.mainObject.name+":"+this.cls.mainObject.id;
                                objb.loaderCls = this.cls.loaderCls;
                                objb.printInline(this.con);
                            }
                        }
                    }
                }
            }
            break;

        case "spacer":
            var row = alib.dom.createElement("div", con);
            alib.dom.styleSet(row, "height", "5px");
            break;

        case "row":
            var row = alib.dom.createElement("div", con);
            
            var width = unescape(child.getAttribute("width"));
            if (width)
                alib.dom.styleSet(row, "width", width);
            
            if (!show)
            {
                alib.dom.styleSet(row, "display", "none");
            }
            if (onchangeobj)
            {
                onchangeobj.con = row;
                onchangeobj.onchange = function(val)
                {
                    if (val == this.condval)
                        this.con.style.display = "block";
                    else
                        this.con.style.display = "none";
                }
            }

			// Determine if contianer is only visible in edit mode
			var editmodeonly = unescape(child.getAttribute("editmodeonly"));
			if (editmodeonly == 't')
			{
				if (this.editMode == false)
                	alib.dom.styleSet(row, "display", "none");

				alib.events.listen(this, "changemode", function(evnt){ 
					evnt.data.rowCon.style.display = (evnt.data.cls.editMode) ? "block" : "none";
				}, {rowCon:row, cls:this});
			}

            this.buildForm(row, child);
            break;

        case "column":
            if (!curRow)
            {
                curRow = alib.dom.createElement("tr", tbody);
                curRow.vAlign = "top";
            }
            curcol++; // current column index
            var td = alib.dom.createElement("td", curRow);
            var width = unescape(child.getAttribute("width"));
            if (width)
                alib.dom.styleSet(td, "width", width);
            
            if (curcol > 1)
			{
            	var padding = unescape(child.getAttribute("padding"));

				if (padding)
                	alib.dom.styleSet(td, "padding-left", padding);
				else	
                	alib.dom.styleSet(td, "padding-left", "20px");
			}
            if (!show)
            {    
                alib.dom.styleSet(td, "display", "none");
            }
            if (onchangeobj)
            {
                onchangeobj.con = td;
                onchangeobj.onchange = function(val)
                {
                    if (val == this.condval)
                        this.con.style.display = "table-cell";
                    else
                        this.con.style.display = "none";
                }
            }
            
            var colStyle = unescape(child.getAttribute("style"));
            if (colStyle)
            {
                var styleParts = colStyle.split(";");
                for(cStyle in styleParts)
                {
                    var parts = styleParts[cStyle].split(":");
                    alib.dom.styleSet(td, parts[0], parts[1]);
                }
            }
            
            this.buildForm(td, child);
            break;

        case "label":
            var row = alib.dom.createElement("tr", tbody);
            row.vAlign = "top";
            var lbl = alib.dom.createElement("td", row);
            alib.dom.styleSetClass(lbl, "formLabel");

            var fieldName = child.getAttribute("field");
			if (fieldName)
			{
				alib.events.listen(this.mainObject, "fieldchange", function(evnt) { 
					if (evnt.data.fieldName == evnt.data.lblFieldName)
						evnt.data.labelCon.innerHTML = (evnt.data.valueName) ? evnt.data.valueName : evnt.data.value; 
				}, {labelCon:lbl, lblFieldName:fieldName});
				lbl.innerHTML = this.mainObject.getValueName(fieldName);
			}
			else
			{
            	lbl.innerHTML = child.text();
			}
            break;

        case "header":
            var lbl = alib.dom.createElement("div", con);
            alib.dom.styleSetClass(lbl, "headerLabel");

            var fieldName = child.getAttribute("field");
			if (fieldName)
			{
				alib.events.listen(this.mainObject, "fieldchange", function(evnt) { 
					if (evnt.data.fieldName == evnt.data.lblFieldName)
						evnt.data.labelCon.innerHTML = (evnt.data.valueName) ? evnt.data.valueName : evnt.data.value; 
				}, {labelCon:lbl, lblFieldName:fieldName});
				lbl.innerHTML = this.mainObject.getValueName(fieldName);
			}
			else
			{
            	lbl.innerHTML = child.text();
			}
            break;
        
        case "text":
            var textCon = alib.dom.createElement("span", con);
            alib.dom.styleSet(textCon, "marginBottom", "5px");
            
            if(child.getAttribute("class"))
                alib.dom.styleSetClass(textCon, child.getAttribute("class"));

            if (!show)
                alib.dom.styleSet(textCon, "display", "none");

            var fieldName = child.getAttribute("field");
			if (fieldName)
			{
				alib.events.listen(this.mainObject, "fieldchange", function(evnt) { 
					if (evnt.data.fieldName == evnt.data.lblFieldName)
						evnt.data.labelCon.innerHTML = (evnt.data.valueName) ? evnt.data.valueName : evnt.data.value; 
				}, {labelCon:textCon, lblFieldName:fieldName});
				textCon.innerHTML = this.mainObject.getValueName(fieldName);
			}
			else
			{
            	textCon.innerHTML = child.text();
			}
            break;
            
        case "field":
            if(unescape(child.getAttribute("name")) == "uname")
            {
                var objUname = new AntObjectLoader_Uname();            
                objUname.mainObject = this.mainObject;
                this.plugins[this.plugins.length] = objUname;
                this.loadPlugin("uname", con); // "uname" should be the same as this.name in uname.js
                break;
            }
            
            var row = alib.dom.createElement("tr", tbody);
			alib.dom.styleSetClass(row, "formRow");
            row.vAlign = "top";

            var fname = unescape(child.getAttribute("name"));
            var field = this.mainObject.getFieldByName(fname);            
            var options = new Object();
            options.className = child.getAttribute("class");
            options.hidelabel = (unescape(child.getAttribute("hidelabel"))=='t')?true:false;
            options.multiLine = (unescape(child.getAttribute("multiline"))=='t')?true:false;
            options.rich = (unescape(child.getAttribute("rich"))=='t')?true:false;
            options.profileImage = (unescape(child.getAttribute("profile_image"))=='t')?true:false;
			options.mode = "form"; // Can be either input (default) or form
            options.icon = child.getAttribute("icon");
            options.editmodeonly = (child.getAttribute("editmodeonly")=='t') ? true : false;
            options.plugins = child.getAttribute("plugins");

			// Object references can filter browser options based on a value in this field
            options.refField= child.getAttribute("ref_field"); // field in the referenced object
            options.refThis = child.getAttribute("ref_this"); // The field in this object to get value from
            options.refValue = child.getAttribute("ref_value"); // Optional manual set value to query for rather than refThis
			options.refRequired = (child.getAttribute("ref_required")=='t')?true:false; // Do not show "Browse" button untin value in this ref
            
            var labelAttr = unescape(child.getAttribute("label"));
            if(labelAttr)
                field.title = labelAttr;

             if (child.getAttribute("validator"))
				 options.validator = child.getAttribute("validator");
                
            if (child.getAttribute("part"))
                options.part = unescape(child.getAttribute("part"));
            if (child.getAttribute("view")) // if type='object_multi' for alternate default view
                options.viewId = unescape(child.getAttribute("view_id"));

            if (field)
            {
				// Get the direct parent fieldset if available to link to input object
				var currentFieldset = (con.fieldsetRef) ? con.fieldsetRef : null;
                try 
                {
                    this.printField(field, row, options, child, currentFieldset);
                }
                catch (e)
                {
                    alert("There was a problem loading " + field.name + "\n\n" + e);
                }
            }
            break;

        // Print all additional fields
        case "all_additional":
            var fields = this.mainObject.getFields();
            var fieldsPrinted = false;

            
            for (var i = 0; i < fields.length; i++)
            {
                var field = fields[i];
                if (!field.system) // hidden
                {
                    var row = alib.dom.createElement("tr", tbody);
                    row.vAlign = "top";

                    this.printField(field, row, {}, null, (con.fieldsetRef) ? con.fieldsetRef : null);
                    fieldsPrinted = true;
                }                
            }

			// If now additional fields where printed then hide
			if (con.fieldsetRef && fieldsPrinted == false)
				con.fieldsetRef.hide();
            
			/*
            if(!fieldsPrinted)
            {
                var count = 0;
                var currentNode = con;                
                while(currentNode.className !== "CWindowFrameContent")
                {
                    count++;
                    currentNode = currentNode.parentNode;
                    
                    if(count == 10)
                        break;
                }
                
                if(currentNode.className = "CWindowFrameContent")
                {
                    //alib.dom.styleSet(currentNode, "display", "none");
                    //alib.dom.styleSet(currentNode.previousSibling, "display", "none");
                }
            }
			*/
            
            break;
        }
    }
}

/**
 * Reder a field into the form
 *
 * @param {Object} field The field we are working with
 * @param {DOMElement} row The fieldset current row (fields are always printed in a table)
 * @param {Object} options Optional additional parameters for this field
 * @param {CXmlNode} child The current xml node of the UIML form
 * @param {CWindowFrame} currentFieldset The parent fieldset if available
 */
AntObjectLoader_Form.prototype.printField = function(field, row, options, child, currentFieldset)
{
    var input_obj = null;

    var opts = (options) ? options : new Object();

    // Look for default
    var def = (child) ? unescape(child.getAttribute("default")) : null;
    
    if (!def)
        def = field.default_value;
    
	// Set default values
	// ----------------------------------
    if (!this.mainObject.id && def && !this.mainObject.getValue(field.name))
    {
        // Check for 'now' defaults for timestamps
        if (field.type == 'timestamp' && def == "now")
        {
            var ts = new Date();
            def = (ts.getMonth()+1)+"/"+ts.getDate()+"/"+ts.getFullYear() + " " + calGetClockTime(ts);
        }

        // Check for 'now' defaults for dates
        if (field.type == 'date' && def == "now")
        {
            var ts = new Date();
            def = (ts.getMonth()+1)+"/"+ts.getDate()+"/"+ts.getFullYear();
        }
        
        // check if form is opened in new window and has post variables passed
        if(this.loaderCls.newWindowArgs)
        {
            // change the default values of the input field. The values displayed is not from inline form
            def = unescape_utf8(this.loaderCls.newWindowArgs[field.name][0]);
        }        
        this.mainObject.setValue(field.name, def);
    }
    
    // look for image
    if (field.type == "object" && field.subtype == "user" && child)
    {
        if (unescape(child.getAttribute("showimage"))=='t')
            field.showimage = true;
        else
            field.showimage = false;
    }

	// Hide label if object_multi
    if (field.type == "object_multi" && !opts.hidelabel)
		opts.hidelabel = true;

    var createInputCon = false;
    
    // Print label
    // ------------------------------------        
    if (field.type == "object" && field.subtype == "folder")
    {
        // TD Label
        var td_label = alib.dom.createElement("td", row);
        alib.dom.styleSetClass(td_label, "formLabel");
        
        if(opts.hidelabel=='t' || opts.hidelabel==true)
        {
            td_label.innerHTML = "";
            alib.dom.styleSet(td_label, "width", "0px");
        }
        else
            td_label.innerHTML = field.title;
        
        // TD Input
        var val_td = alib.dom.createElement("td", row);
        val_con = alib.dom.createElement("div", val_td);
        alib.dom.styleSetClass(val_con, "formValue");
        
        var input_obj = new Object();
        
         // Create containers
        input_obj.dv_inp = alib.dom.createElement("div", val_con);
        alib.dom.styleSet(input_obj.dv_inp, "display", (this.editMode)?"block":"none");
        
        input_obj.dv_text = alib.dom.createElement("input", val_con);
        input_obj.dv_text.type = "hidden";
        
        // Input
        var setPathLink = alib.dom.createElement("a", input_obj.dv_inp);
        
        var folderPathCon = alib.dom.createElement("span", input_obj.dv_inp);
        var folderCon = alib.dom.createElement("div", val_con);
        alib.dom.styleSet(folderPathCon, "margin-left", "3px");
        
        var cbrowser = new AntFsOpen();        
        cbrowser.filterType = "folder";
        cbrowser.cbData.field_name = field.name;
        cbrowser.cbData.frmcls = this;
        cbrowser.cbData.folderCon = folderCon;
        cbrowser.cbData.folderPathCon = folderPathCon;
        cbrowser.onSelect = function(folder_id, name, path) 
        {
            this.cbData.frmcls.displayInlineFolder(this.cbData.field_name, folder_id, this.cbData.folderCon);
            this.cbData.frmcls.mainObject.setValue(this.cbData.field_name, folder_id);
            this.cbData.folderPathCon.innerHTML = "Current Folder: " + path;
        }
        
        if(this.mainObject.getValue(field.name))
        {
            cbrowser.setPathById(this.mainObject.getValue(field.name));
            cbrowser.onSetPath = function(path)
            {
                this.cbData.folderPathCon.innerHTML = "Current Folder: " + path;
            }
        }
        
        setPathLink.innerHTML = "[set path]";
        setPathLink.href = "javascript:void(0);";
        setPathLink.cbrowser = cbrowser;
        setPathLink.onclick = function() { this.cbrowser.showDialog(); }
    }
	/*
    else if (field.type == "object_multi" || (field.subtype == "file" && opts.profileImage))
    {
        var val_con = alib.dom.createElement("td", row);
        alib.dom.styleSet(val_con, "vertical-align", "top");
        val_con.colSpan = "2";
        
        createInputCon = true;
    }
	*/
    else
    {
        if (!opts.hidelabel)
        {
            var td_label = alib.dom.createElement("td", row);
            alib.dom.styleSetClass(td_label, "formLabel");
			if (opts.className)
				alib.dom.styleAddClass(td_label, opts.className);
            
            var labelCon = alib.dom.createElement("div", td_label);

			var htm = field.title;

			// Add icon
			if (opts.icon)
				htm = "<img src='" + opts.icon + "' />&nbsp;" + htm;

			// Add label html
			labelCon.innerHTML = htm;
            
			// Add required
            if (field.required)
            {
                var reqsp = alib.dom.createElement("a", labelCon);
                alib.dom.styleSet(reqsp, "color", "red");
                alib.dom.styleSet(reqsp, "font-size", "16px");
                alib.dom.styleSet(reqsp, "text-decoration", "none");
                reqsp.title = "This is a required field";
                reqsp.innerHTML = "*";
            }
            
			// Add tooltip
            if (child && child.m_name == "field" && child.getAttribute("tooltip"))
            {
                var tooltipStr = unescape(child.getAttribute("tooltip"));
                
                var imageCon = alib.dom.setElementAttr(alib.dom.createElement("img", labelCon), [["src", "/images/icons/help_12.png"]]);                
                alib.dom.styleSet(imageCon, "marginLeft", "5px"); 
                alib.dom.styleSet(imageCon, "cursor", "help");
                alib.ui.Tooltip(imageCon, tooltipStr);
            }
        }
        
        var val_td = alib.dom.createElement("td", row);
        val_con = alib.dom.createElement("div", val_td);
        alib.dom.styleSetClass(val_con, "formValue");
		if (opts.className)
			alib.dom.styleAddClass(val_con, opts.className);
        
        createInputCon = true;
    }
    
    if(createInputCon)
    {
        var input_obj = new Object();
        input_obj.options = opts;
        
         // Create containers
        input_obj.dv_label = alib.dom.createElement("div", val_con); // Special Purpose Con
        alib.dom.styleSet(input_obj.dv_label, "display", "none");
        
        input_obj.dv_text = alib.dom.createElement("div", val_con);
        alib.dom.styleSet(input_obj.dv_text, "display", (this.editMode)?"none":"block");

        input_obj.dv_inp = alib.dom.createElement("div", val_con);
        alib.dom.styleSet(input_obj.dv_inp, "display", (this.editMode)?"block":"none");

        
        // Clear Div
        divClear(val_con);
    }

    if (input_obj)
    {
        // Field vars
        input_obj.fname = field.name;
        input_obj.ftype = field.type;
        input_obj.fstype = field.subtype;
		input_obj.options = opts;
		input_obj.fieldSet = (currentFieldset) ? currentFieldset : null;
        this.inputs[this.inputs.length] = input_obj;
    }

    // Print Value
    // ------------------------------------
    if (field.readonly || field.auto)
    {
        if (input_obj) // readonly object_multi needs to be handleded elsewhere
        {
            input_obj.dv_inp.innerHTML = this.mainObject.getValueName(field.name);
            input_obj.dv_text.innerHTML = "";
            this.setFieldTextDisplay(field.name, null, row);
        }
    }
    else if ("object_multi" == field.type)
    {
        if (this.mainObject.id && !this.loaderCls.cloneObject)
        {
            val_con.innerHTML = "";
            this.printFieldObjectMulti(field.name, val_con, opts);
        }
        else
        {
            if (!this.loaderCls.isMobile)
                val_con.innerHTML = "Please save changes to view more details";
            onchangeobj = new Object();
            this.watchChanges[this.watchChanges.length] = onchangeobj; 
            onchangeobj.field = "id";
            onchangeobj.condval = null;
            onchangeobj.cls = this;
            onchangeobj.val_con = val_con;
            onchangeobj.ref_field_name = field.name;
            onchangeobj.opts = opts;
            onchangeobj.onchange = function(val)
            {
                val_con.innerHTML = "";
                if (val) // is a valid id
                {
                    this.val_con.innerHTML = "";
                    this.cls.mainObject.id = val;                    
                    this.cls.printFieldObjectMulti(this.ref_field_name, this.val_con, this.opts);
                }                    
            }
        }
    }
    else if ("object" == field.type && "folder" == field.subtype)
    {
        if (this.mainObject.id)
        {            
            if (this.mainObject.getValue(field.name))
            {
                this.displayInlineFolder(field.name, this.mainObject.getValue(field.name), folderCon);
            }
            else
            {
                ajax = new CAjax('json');
                ajax.cbData.cls = this;
                ajax.cbData.field_name = name;
                ajax.cbData.val_con = val_con;
                ajax.onload = function(ret)
                {
                    if (!ret['error'])
                    {
                        this.cbData.cls.displayInlineFolder(this.cbData.field_name, ret, this.cbData.val_con);
                        this.cbData.cls.mainObject.setValue(this.cbData.field_name, ret);
                    }
                };
                ajax.exec("/controller/Object/getFolderId",
                            [["obj_type", this.mainObject.name], ["field", field.name], ["oid", this.mainObject.id]]);
            }
        }
        else
        {
            setPathLink.innerHTML = "";
            folderPathCon.innerHTML = "Please save changes before editing files";

            onchangeobj = new Object();
            this.watchChanges[this.watchChanges.length] = onchangeobj; 
            onchangeobj.field = "id";
            onchangeobj.condval = null;
            onchangeobj.cls = this;
            onchangeobj.fname = field.name;
            onchangeobj.folderCon = folderCon;
            onchangeobj.setPathLink = setPathLink;            
            onchangeobj.folderPathCon = folderPathCon;            
            onchangeobj.onchange = function(val)
            {
                if (val) // is a valid id
                {
                    this.folderPathCon.innerHTML = "";
                    if(this.cls.editMode)
                        this.setPathLink.innerHTML = "[set path]";

                    ajax = new CAjax('json');
                    ajax.cbData.cls = this.cls;                    
                    ajax.cbData.fname = this.fname;                    
                    ajax.cbData.folderCon = this.folderCon;                    
                    ajax.onload = function(ret)
                    {
                        if (!ret['error'])
                        {
                            this.cbData.cls.displayInlineFolder(this.cbData.field_name, ret, this.cbData.folderCon);
                            this.cbData.cls.mainObject.setValue(this.cbData.field_name, ret);
                        }
                    };
                    ajax.exec("/controller/Object/getFolderId",
                                [["obj_type", this.cls.mainObject.name], ["field", this.fname], ["oid", val]]);
                    
                }
            }
        }
    }
    else if ("fkey" == field.type && "user_files" == field.subtype)
    {
        var selfl = function(field_name, cls, opts, lbl)
        {
            var cbrowser = new AntFsOpen();
            cbrowser.cbData.field = field;
            cbrowser.cbData.field_name = field_name;
            cbrowser.cbData.frmcls = cls;
            cbrowser.cbData.opts = opts;
            cbrowser.cbData.lbl = lbl;
            // Look for folder variable for this object and set if exists
            var fldr = cls.lookForFolderRoot();
            if (fldr != -1)
            {
                cbrowser.setPathById(fldr);
            }
            cbrowser.onSelect = function(fid, name, path) 
            {
                this.cbData.frmcls.mainObject.setValue(this.cbData.field_name, fid);

                var lbl = this.cbData.lbl;

                if (this.cbData.opts.profileImage)
                    lbl.innerHTML = "<img src=\"/files/images/"+fid+"/48\" border='0' />";
                else
                    lbl.innerHTML = "<a href=\"/files/"+fid+"\">"+name+"</a>";
                
                // Add "clear" button
                if (!this.cbData.field.required)
                {
                    var sp = alib.dom.createElement("span", this.cbData.lbl);
                    sp.innerHTML = "&nbsp;";

                    var aclear = alib.dom.createElement("a", this.cbData.lbl);
                    aclear.href = 'javascript:void(0);';
                    aclear.mainObject = this.cbData.frmcls.mainObject;
                    aclear.fname = this.cbData.field.name;
                    aclear.lbl = this.cbData.lbl;
                    aclear.onclick = function() { this.lbl.innerHTML = "None Selected&nbsp;&nbsp;&nbsp;"; this.mainObject.setValue(this.fname, ""); }
                    aclear.innerHTML = "<img src='/images/icons/delete_10.png' />";
                }

                var sp = alib.dom.createElement("span", lbl);
                sp.innerHTML = "&nbsp;&nbsp;&nbsp;";
            }
            cbrowser.showDialog(); 
        }
        
        var fid = this.mainObject.getValue(field.name);
        if (fid)
        {
            if (opts.profileImage)
                input_obj.dv_label.innerHTML = "<img src=\"/files/images/"+fid+"/48\" border='0' />";
            else
                input_obj.dv_label.innerHTML = "<a href=\"/files/"+fid+"\">"+this.mainObject.getValueName(field.name)+"</a>&nbsp;";
                                          
            if (!field.required)
            {
                var aclear = alib.dom.createElement("a", input_obj.dv_inp);
                aclear.href = 'javascript:void(0);';
                aclear.mainObject = this.mainObject;
                aclear.fname = field.name;
                aclear.lbl = input_obj.dv_label;
                aclear.onclick = function() { this.lbl.innerHTML = "None Selected&nbsp;&nbsp;&nbsp;"; this.mainObject.setValue(this.fname, ""); }
                aclear.innerHTML = "<img src='/images/icons/delete_10.png' />";
                var sp = alib.dom.createElement("span", input_obj.dv_inp);
                sp.innerHTML = "&nbsp;&nbsp;&nbsp;";
            }
        }
        else
        {
            input_obj.dv_label.innerHTML = "None Selected&nbsp;&nbsp;&nbsp;";            
        }
            
            
        var btn = new CButton("Select File", selfl, [field.name, this, opts, input_obj.dv_label], "b1");
        btn.print(input_obj.dv_inp);
            
        if (opts.profileImage)
        {
            alib.dom.styleSet(val_con, "text-align", "center");
        }
        
        // Update the style set
        alib.dom.styleSet(input_obj.dv_label, "display", "block");
        
        alib.dom.styleSet(input_obj.dv_label, "float", "left");
        alib.dom.styleSet(input_obj.dv_text, "float", "left");
        alib.dom.styleSet(input_obj.dv_inp, "float", "left");
        
        alib.dom.styleSet(input_obj.dv_inp, "width", "90%px");
        alib.dom.styleSet(input_obj.dv_inp, "margin-top", "-5px");
    }
    else
    {        
        var rich = (opts.rich) ? true : false;
        
        var wdth;
        switch(field.type)
        {
		case "date":
			wdth = "100px";
			break;
		case "bool":
			wdth = "15px";
			break;
		default:
			wdth = "99%";
			break;
        }

		// Look for folder variable for this if a file and set if exists
		if (field.type == "object" && field.subtype == "file")
			opts.folderRoot = this.lookForFolderRoot();
        
        //this.mainObject.fieldGetValueInput(input_obj.dv_inp, field.name, {multiLine:multiLine, width:wdth, rich:rich, part:opts.part});
		var inp = new AntObject_FieldInput(this.mainObject, field.name);
		inp.render(input_obj.dv_inp, opts);
		input_obj.inp = inp;
        this.setFieldTextDisplay(field.name, opts, row);        
    }
}

/**
 * Print field where type = object_multi
 *
 * @param string fname the name of the field to print
 * @param DOMElement con the container where this field is to be printed
 * @param {Object} options Optional object with options properties for this field
 */
AntObjectLoader_Form.prototype.printFieldObjectMulti = function(fname, con, options)
{
    var field = this.mainObject.getFieldByName(fname);
    if (!field)
        return false;

    var opts = (options) ? options : new Object();

    // Print inline browser because all types are the same
    if (field.subtype)
    {
        var objb = new AntObjectBrowser(field.subtype);
		objb.setObjectContext(this.mainObject); // Load browser in context of this object
		this.objectBrowsers[this.objectBrowsers.length] = objb; // store reference for refreshing

        if (this.loaderCls.antView)
            objb.setAntView(this.loaderCls.antView);
		else
            objb.setAutoRefresh(10000);

        objb.loaderCls = this.loaderCls;
        objb.obj_reference = this.mainObject.name+":"+this.mainObject.id;
        if (field.subtype == "comment" || field.subtype == "activity")
        {
			objb.limit = 50;
            objb.setViewsFilter(this.mainObject.name);

			if (field.subtype == "comment")
            	objb.setFilter('obj_reference', this.mainObject.name+":"+this.mainObject.id);
			else
            	objb.setFilter('associations', this.mainObject.name+":"+this.mainObject.id);

            objb.printComments(con, this.mainObject.name+":"+this.mainObject.id, this.mainObject);
        }
        else
        {
            // Add "Add Exsiting"
            objb.addToolbarAction(function(tb, bcls, options) { options.cls.objectMultiAddExisting(tb, bcls, options); }, 
                                  [{cls:this, fname:field.name, subtype:field.subtype}]);

            // Put "Remove" custom action - will not delete object
            objb.addToolbarAction(function(tb, bcls, options) { options.cls.objectMultiRemove(tb, bcls, options); }, 
                                  [{cls:this, fname:field.name, subtype:field.subtype}]);

            // Hide toolbar actrion
            objb.optCreateNew = false;
            objb.optDelete = false;
            objb.optActions = false;

            // Check to see if we have any values set for this field
            var vals = this.mainObject.getMultiValues(fname);

            if (vals.length)
            {
                for (i = 0; i < vals.length; i++)
                {
                    objb.setFilter('id', vals[i], (i) ? "or" : "and");
                }
            }
            else
            {
                objb.skipLoad = true;
            }

            objb.printInline(con);

        }
    }
    else
    {
        // Print associations
    }
}

/**
 * Add "Add Existing" toolbar button to AntObjectBrowser for object_multi
 *
 * @param CToolbar tb pointer to CToolbar in the object browser
 * @param AntObjectBrowser bcls a reference to the browser class
 * @param object options various options
 */
AntObjectLoader_Form.prototype.objectMultiAddExisting = function(tb, bcls, options)
{
    // Inline function to select a new object
    var selobj = function(cls, browsercls, options)
    {
        var ob = new AntObjectBrowser(options.subtype);
        ob.options.field_name = options.fname;
        ob.options.obj_type = options.subtype;
        ob.options.frmcls = cls;
        ob.options.browsercls = browsercls;
        ob.onSelect = function(oid) 
        {
            this.options.frmcls.mainObject.setMultiValue(this.options.field_name, oid);
            this.options.browsercls.setFilter('id', oid, "or");
            this.options.browsercls.refresh();
        }
        ob.displaySelect();
    }

    var btn = new CButton("Add Existing", selobj, [this, bcls, options], "b2");
    tb.AddItem(btn.getButton());
}

/**
 * Add "Remove" toolbar button to AntObjectBrowser for object_multi
 *
 * @param CToolbar tb pointer to CToolbar in the object browser
 * @param AntObjectBrowser bcls a reference to the browser class
 * @param object options various options
 */
AntObjectLoader_Form.prototype.objectMultiRemove = function(tb, bcls, options)
{
    // Inline function to select a new object
    var selobj = function(cls, browsercls, options)
    {
        var ob = new AntObjectBrowser(options.subtype);
        ob.options.field_name = options.fname;
        ob.options.obj_type = options.subtype;
        ob.options.frmcls = cls;
        ob.options.browsercls = browsercls;
        ob.onSelect = function(oid) 
        {
            this.options.frmcls.mainObject.delMultiValue(this.options.field_name, oid);
            this.options.browsercls.removeFilter("id", oid);
            this.options.browsercls.refresh();
        }
        ob.displaySelect();
    }

    var btn = new CButton("Remove", selobj, [this, bcls, options], "b3");
    tb.AddItem(btn.getButton());
}

/**
 * Render the non-edit mode display for each field
 *
 * @param {string} fname The field name we are displaying
 * @param {Object} options Object containing form options
 * @param {DOMElement} row  Row element of the input field
 */
AntObjectLoader_Form.prototype.setFieldTextDisplay = function(fname, options, row)
{
    var input_obj = null;
    var opts = (options) ? options : new Object();
    var field = this.mainObject.getFieldByName(fname);

    if (!field)
        return;
        
    for (var i = 0; i < this.inputs.length; i++)
    {
        if (this.inputs[i].fname == fname)
        {
            input_obj = this.inputs[i];
            
            if(row)
                input_obj.fieldRow = row;
            
            if ("object" == field.type && "file" == field.subtype) 
			{
                input_obj.dv_text.innerHTML = "";
				input_obj.dv_text.id = "myfiletextdiv";
				
				var value = this.mainObject.getValue(fname);
				var valueName = this.mainObject.getValueName(fname);
				if (value)
				{
					if (input_obj.options.profileImage)
						input_obj.dv_text.innerHTML = "<img src='/antfs/images/" + value + "/48' />";
					else
						input_obj.dv_text.innerHTML = "<a href='/files/" + value + "'>" + valueName + "</a>";
				}
				else
				{
					if (input_obj.options.profileImage)
						input_obj.dv_text.innerHTML = "<img src='/images/icons/objects/files/image_48.png' />";
					else
						input_obj.dv_text.innerHTML = "Not Set";
				}
			}
            else if ("fkey" == field.type || "object" == field.type)
            {
                input_obj.dv_text.innerHTML = "";
                // Show user image
                if (field.subtype=='user' && field.showimage)
                {
                    var imgCon = alib.dom.createElement("span", input_obj.dv_text)
                    // Get id
                    var val = this.mainObject.getValue(field.name)
                    
                    if (val)
                    {                        
                        /*var funct = function(ret, con)
                        {
                            if (!ret['error'])
                                con.innerHTML = "<img src='/files/images/"+ret+"/48/48' align='left' />&nbsp;";
                        }
                        var rpc = new CAjaxRpc("/controller/User/userGetImage", "userGetImage", [["uid", val]], funct, [imgCon], AJAX_POST, true, "json");*/
                        
                        ajax = new CAjax('json');
                        ajax.cbData.imgCon = imgCon;
                        ajax.onload = function(ret)
                        {
                            if (!ret['error'])
                                this.cbData.con.innerHTML = "<img src='/antfs/images/"+ret+"/48' align='left' />&nbsp;";
                        };
                        ajax.exec("/controller/User/userGetImage",
                                    [["uid", val]]);
                    }
                }

                var tmpCon = alib.dom.createElement("span", input_obj.dv_text)
                if ("object" == field.type)
                {
					var fkeyVal = this.mainObject.getValue(field.name);
					var subType = "";

					if (field.subtype)
					{
						subType = field.subtype;
					}
					else
					{
						var refParts = fkeyVal.split(":");
							
						if (refParts.length == 2)
						{
							subType = refParts[0];
							fkeyVal = refParts[1];
						}
					}

					var buf = "";
                    if(fkeyVal && subType)
                    {
						if (!field.subtype)
						{
							var refObj = new CAntObject(subType);

							if (refObj.getIcon(16, 16))
								buf = "<img src='" + refObj.getIcon(16, 16) + "'> ";
							else
								buf = refObj.title + ": ";
						}

                        buf += "<a href=\"javascript:void(0);\" onclick=\"loadObjectForm('" + subType + "', "
                                             + "'" + fkeyVal + "');\">"
                                             + this.mainObject.getValueName(field.name) + "</a>";

						AntObjectInfobox.attach(subType, fkeyVal, tmpCon);
                    }
                    else
                    {
                        buf = "None Selected";
                    }

                	tmpCon.innerHTML = buf;
                }
                else
                {
                    var fkeyVal = this.mainObject.getValueName(field.name);
                    tmpCon.innerHTML = fkeyVal;
                }
            }
            else if ("fkey_multi" == field.type)
            {
                input_obj.dv_text.innerHTML = this.mainObject.getMultiValueStr(field.name);
            }
            else if ("alias" == field.type)
            {
                var buf = "";
                var val = this.mainObject.getValue(field.name);
                if (val)
                {
                    var tmpfld = this.mainObject.getFieldByName(val);
                    if (tmpfld)
                        buf = tmpfld.title;
                }
                input_obj.dv_text.innerHTML = buf;
            }
            else if ("bool" == field.type || "boolean" == field.type)
            {
                var val = this.mainObject.getValue(field.name);
                var buf = (val) ? "Yes" : "No";
                input_obj.dv_text.innerHTML = buf;
            }
            else
            {
                var val = this.mainObject.getValueStr(field.name);                
                if (typeof val == "string" && field.subtype!="html" && !input_obj.options.rich)
                {
                    var re = new RegExp ("\n", 'gi') ;
                    val = val.replace(re, "<br />");
                }

                if (typeof val == "string" && (field.subtype=="html" || input_obj.options.rich))
					alib.dom.styleAddClass(input_obj.dv_text, "formHtmlBody");

                // Check if we are only displaying part of the value for some reason (like times)
                switch (input_obj.options.part)
                {
                case 'time':
                    if (field.type == "timestamp")
                        val = this.mainObject.getInputPartValue(field.name, val, "time");
                    break;
                case 'date':
                    if (field.type == "timestamp")
                        val = this.mainObject.getInputPartValue(field.name, val, "date");
                    break;
                }

                // Activate infocenter_document wikilinks                
                if (this.mainObject.obj_type == "infocenter_document" && field.type == "text")
                    val = this.textActiveWikiLink(val);

				// Convert email addresses into mailto links
				val = this.textActivateLinks(val);

              	input_obj.dv_text.innerHTML = val;
            }
            
			/**
			 * The below code hides fields that are empty
			 */
            if(input_obj.fieldRow && field.type != "object_multi")
            {
                if(!this.editMode &&
				   (input_obj.dv_text.innerHTML.length == 0 || (field.type == "fkey" && fkeyVal.length == 0) || input_obj.options.editmodeonly))
				{
                    alib.dom.styleSet(input_obj.fieldRow, "display", "none");
				}
                else
				{
                    input_obj.fieldRow.setAttribute("style","");
					
					// Set values present because if not set the parent fieldset will be hidden
					if (input_obj.fieldSet)
						input_obj.fieldSet.valuesPresent = true;
				}
            }
			else if (field.type == "object_multi" && input_obj.fieldSet)
				input_obj.fieldSet.valuesPresent = true; // show comments always


			if (input_obj.fieldSet)
			{
				if (typeof input_obj.fieldSet.valuesPresent == "undefined" || input_obj.fieldSet.valuesPresent == false)
					input_obj.fieldSet.hide();
				else
					input_obj.fieldSet.show();
			}
        }
    }
}

/**
 * Look for wiki links and convert them to clickable links
 *
 * @param {string} val The value to convert
 */
AntObjectLoader_Form.prototype.textActiveWikiLink = function(val)
{
    var buf = val;

	if (!buf || typeof buf != "string")
		return buf;

    // Convert [[id|Title]]
    //var re=/\[\[(.*?)\|(.*?)\]\]/gi
    var re=/\[\[([^|\]]*)?\|(.*?)\]\]/gi
    buf = buf.replace(re, "<a href=\"/obj/infocenter_document/$1\" target=\"_blank\">$2</a>");

    // Convert [[id]] with id
    //var re=/\[\[(.*?)]\]/gi
    var re=/\[\[([0-9]+)]\]/gi
    buf = buf.replace(re, "<a href=\"/obj/infocenter_document/$1$1\" target=\"_blank\">$1</a>");

    // Convert [[id]] with uname
    //var re=/\[\[(.*?)]\]/gi
    var re=/\[\[([a-zA-Z0-9_-]+)]\]/gi
    buf = buf.replace(re, "<a href=\"/obj/infocenter_document/uname:$1\" target=\"_blank\">$1</a>");

    return buf;
}

/**
 * Look for email addresses and convert them to clickable mailto links
 *
 * @param {string} val The value to convert
 */
AntObjectLoader_Form.prototype.textActivateLinks = function(val)
{
    var buf = val;

	if (!buf || typeof buf != "string")
		return buf;

	// Repalce all existing link swith target=blank
    var exp = /(^|>|\s)(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
    buf = buf.replace(/<a\s+href=/gi, '<a target="_blank" href=');
		
	//URLs starting with http://, https://, or ftp://
    //var exp = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
    var exp = /(^|>|\s)(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
    buf = buf.replace(exp, '<a href="$2" target="_blank">$2</a>');

    //URLs starting with "www." (without // before it, or it'd re-link the ones done above).
    exp = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
    buf = buf.replace(exp, '$1<a href="http://$2" target="_blank">$2</a>');

    //Change email addresses to mailto:: links.
    exp = /(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/gim;
	var repWith = "<a href=\"javascript:Ant.Emailer.compose('$1', {obj_type:'" 
					+ this.mainObject.obj_type + "', oid:'" + this.mainObject.id + "'});\">$1</a>"
    buf = buf.replace(exp, repWith);
    //buf = buf.replace(exp, '<a href="mailto:$1">$1</a>');

	// Activate email addresses -- this is what we used before
	//var regEx = /(\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)/;
	//buf = buf.replace(regEx, "<a href=\"mailto:$1\">$1</a>");

    return buf;
}

/**
 * Called once the form has finished rendering
 */
AntObjectLoader_Form.prototype.formLoaded = function()
{
    for (var i = 0; i < this.loadReports.length; i++)
    {
        this.loadReport(this.loadReports[i].rid, this.loadReports[i].filterby, this.loadReports[i].con);
    }

    this.loaderCls.resize();

	// Load any help tours
	Ant.HelpTour.loadTours(this.formCon);
}

AntObjectLoader_Form.prototype.lookForFolderRoot = function()
{
    var fields = this.mainObject.getFields();
    for (var i = 0; i < fields.length; i++)
    {
        var field = fields[i];
        if (field.type == "object"  && field.subtype == "folder")
        {
            var val = this.mainObject.getValue(field.name);
            if (val)
            {
                return val;
            }
        }
    }
    return -1;
}

AntObjectLoader_Form.prototype.loadPlugin = function(pname, con)
{
    var plugin = null;

    for (var i = 0; i < this.plugins.length; i++)
    {
        if (this.plugins[i].name == pname) // pname should be the same as this.name in the plugin
            plugin = this.plugins[i];
    }
    
    if (plugin)
    {
        plugin.formObj = this;
        plugin.mainObject = this.mainObject;
        plugin.olCls = this.loaderCls;
        if (this.loaderCls)
            plugin.toolbar = this.toolbar;
        
        plugin.main(con);
        plugin.frmLoaded = true;
    }
    else
    {
        con.innerHTML = "Plugin not found: " + pname;
    }
}

/**************************************************************************
* Function:     loadRecurrence    
*
* Purpose:        Load recurrence form.
*
* Params:        (element) con : the dom element to print to
**************************************************************************/
AntObjectLoader_Form.prototype.loadRecurrence = function(con, options)
{
    if (!options.hidelabel)
    {
        var lbl = alib.dom.createElement("div", con);
        alib.dom.styleSetClass(lbl, "formLabel");
        alib.dom.styleSet(lbl, "width", "100px");
        alib.dom.styleSet(lbl, "display", "inline-block");
        lbl.innerHTML = "Repeats";
    }

    var link = alib.dom.createElement("a", con);
    link.href = 'javascript:void(0);';
    if (this.mainObject.getRecurrencePattern(false))
    {
        link.innerHTML = this.mainObject.recurrencePattern.getHumanDesc();
    }
    else
    {
        // Set callback to change this label if the recurrencePattern does finally load
        var onchangeobj = new Object();
        this.watchChanges[this.watchChanges.length] = onchangeobj; 
        onchangeobj.link = link;
        onchangeobj.onchange = function(val)
        {
            if (this.mainObject.getRecurrencePattern(false))
                this.link.innerHTML = this.mainObject.recurrencePattern.getHumanDesc();
        }

        link.innerHTML = "Does not repeat";
    }
    link.obj = this.mainObject;
    link.descDiv = link;
    link.onclick = function()
    {
        var rp = this.obj.getRecurrencePattern(true);
        rp.descDiv = this;
        if (rp.fieldDateStart && rp.dateStart == "")
        {
            var field = this.obj.getFieldByName(rp.fieldDateStart);
            if (field)
            {
                var val = this.obj.getValue(rp.fieldDateStart);
                rp.dateStart = this.obj.getInputPartValue(rp.fieldDateStart, val, "date");
            }
        }
            

        rp.onchange = function() 
        { 
            this.descDiv.innerHTML = this.getHumanDesc(); 
        }
        rp.showDialog();
    }
}

/**************************************************************************
* Function:     loadReport    
*
* Purpose:        Load inline report into the object form
**************************************************************************/
AntObjectLoader_Form.prototype.loadReport = function(rid, filterby, con)
{    
    /*var rtp = new CReport();
    rtp.chart_width = (con.offsetWidth < 800) ? con.offsetWidth : 800;
    rtp.con = con;
    rtp.hideloading = true;
    rtp.clswid = this;
    rtp.addFilterCondition("and", filterby, "is_equal", this.mainObject.id);
    rtp.onload = function()
    {
        if (!this.cube)
            return;

        //var div_frm = alib.dom.createElement("div", this.con);
        //this.printCubeMicroForm(div_frm, true);
        var div_chart = alib.dom.createElement("div", this.con);
        this.cube.printChart(div_chart);
    }
    rtp.load(rid);*/
    
    var reportObject = new Report(rid);
    reportObject.cls = this;
    reportObject.displayReportName = false;
    reportObject.cbData.con = con;
    reportObject.cbData.filterby = filterby;    
    reportObject.chartWidth = (con.offsetWidth < 800) ? con.offsetWidth : 800;
    var height = (reportObject.chartWidth / 4) * 3;    
    reportObject.chartHeight = height;
    
    // over-ride the onload function
    reportObject.onload = function(ret)
    {        
        if (this.cbData.filterby)
        {
            if(!this.filterData)
                this.filterData = new Array();
            
            var filterLength = this.filterData.length;
            this.filterData[filterLength] = new Object();
            var reportFilter = this.filterData[filterLength];
            
            reportFilter.blogic = "and";
            reportFilter.fieldName = filterby;
            reportFilter.operator = "is_equal";
            reportFilter.condValue = this.cls.mainObject.id;
        }
        
        var chartCon = alib.dom.createElement("div", this.cbData.con);
        this.print(chartCon);
    }
    
    reportObject.loadReport();
}

AntObjectLoader_Form.prototype.displayInlineFolder = function(field_name, folder_id, con)
{
    if (folder_id && con)
    {
        con.innerHTML = "";
        var browser = new AntObjectBrowser("file");
        browser.setBrowseBy("folder_id", ".", folder_id);
        browser.printInline(con);

		this.objectBrowsers[this.objectBrowsers.length] = browser; // store reference for refreshing
    }
}

AntObjectLoader_Form.prototype.onNameChange = function(name)
{
}
