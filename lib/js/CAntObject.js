/*======================================================================================
    
    Class:        CAntObject

    Purpose:    ANT Objects store all data in ANT

    Author:        joe, sky.stebnicki@aereus.com
                Copyright (c) 2010 Aereus Corporation. All rights reserved.
    
    Usage:        var obj = new CAntObject("customer");

======================================================================================*/

var CAntObjectDefs = new Array();

function CAntObject(type, oid)
{    
    this.title = type; // title of object - customer
    this.titlePl = type; // title of object plural - customers
    this.label = "";    // name of instance of object (like first_name + " " + last_name)
    this.name = type; // legacy, use obj_type below
    this.obj_type = type;
    this.id = (oid) ? oid : null;
    this.loaded = false; // Set to true when object data is fully loaded
    this.nameField = "id"; // The field that serves as the title or name for an object
    this.dirty = false; // This will be set to true the second 'setValue' is called

    this.repressOnSave = false; // Can be used to repress onsave callback
    this.cbProps = new Object(); // Callback properties object
    this.associations = new Array();    // objects for associations
    this.fields = new Array();    
    this.values = new Array(); // Actual values for a specific object
    this.views = new Array();
    this.m_filters = new Array(); // Used for filtering data - especially fkey references
    this.teamFormLayouts = new Array(); // custom form layouts can be defined for each team

    // for adding and removing fields
    this.addfields = new Array();
    this.removefields = new Array();
    this.propertyFormOrder = new Array(); // used to resort fields

    // Add security
    this.security = new Object();
    this.security.view = true;
    this.security.edit = true;
    this.security.del = true;
    this.security.childObjects = new Array();
    
    // Personal user settings - number of objects to show per browser page
    this.showper = 0;

    // Personal user settings - mode of browser {previewV, previewH, table}
    this.browserMode = "table";

	/**
	 * The html message printed in a browser (objectList) when there are no items to display
	 * 
	 * @public
	 * @var {string}
	 */
    this.browserBlankStateMsg = "";

	/**
	 * Generic callback properties buffer
	 *
	 * @var {Object}
	 */
	this.cbData = new Object();
    
    // recurrence object properties
    this.recurrencePattern = null;
    this.recurRules = null; // does this type of object support recurrence
    
    // Determines wheter to load the objects sub field for condition dropdown
    this.loadQuerySubObjects = true;
    
    // arguments for forms opened in new window
    this.newWindowArgs = null;    

	/**
	 * Optional icon name
	 *
	 * This string is set if an icon exists for this object type.
	 *
	 * @var {stirng}
	 */
	this.iconName = null;


	/**
	 * New entity defintiion
	 *
	 * @type {Ant.EntityDefinition}
	 */
	this.def = Ant.EntityDefinitionLoader.get(type);

	// Old definitions
	/*
    var cachedInd = this.getCachedObjDef(type);
    if (cachedInd == -1)
    {
        // Get object definition
        var ajax = new CAjax();
        var url = "/objects/xml_get_objectdef.php?oname=" + type;
        if (typeof Ant != "undefined" && Ant.isMobile)
            url += "&mobile=1";
        var root = ajax.exec(url, null, false);
    }
    else
    {
        var root = CAntObjectDefs[cachedInd].root;
    }
	*/


    //if (root.getNumChildren())
    //{
        //var def = new Object();
        //def.name = type;
        //def.fields = new Array();

   		this.title = this.def.title;

        if ("y" == this.title.charAt(this.title.length - 1))
        {
            this.titlePl = this.title.substr(0, this.title.length - 1) +"ies";
        }
        else if ("s" != this.title.charAt(this.title.length - 1))
            this.titlePl = this.title+"s";
        else
            this.titlePl = this.title;
        
        // Get the name/listTitle field - usually eather a field called either "title" or "name"
        this.nameField= this.def.listTitle;

        // Get views
		this.views = this.def.getViews();
		/*
		var views = this.def.getViews();
        for (var i in views)
        {
            var view = new AntObjectBrowserView(this.name);
			view.fromData(views[i]);
            this.views[this.views.length] = view;
        }
		*/
		/*
        var view_nodes = root.getChildNodeByName("views");
        for (var i = 0; i < view_nodes.getNumChildren(); i++)
        {
            var child = view_nodes.getChildNode(i);

            var view = new AntObjectBrowserView(this.name);
            view.loadFromXml(child);
            this.views[this.views.length] = view;
        }
		*/

		/**
         * Get secuirty
		 * Note: Child object security is temporarily disabled due to performance issues
        var security_nodes = root.getChildNodeByName("security");
        for (var i = 0; i < security_nodes.getNumChildren(); i++)
        {
            var child = security_nodes.getChildNode(i);

            switch (child.m_name)
            {
            case 'child_object':
                this.security.childObjects[this.security.childObjects.length] = child.m_text;
                break;
            }
        }
		*/

		/**
		 * Use defaults for now. Acutally, once we go to progressive load this won't matter
        var buf = unescape(root.getChildNodeValByName("showper"));
        if (buf && buf!="0")
            this.showper = buf;
		 */

		// Not sure if this belongs in the defintion
		if (this.def.browserMode)
            this.browserMode = this.def.browserMode;
		/*
        var buf = unescape(root.getChildNodeValByName("browser_mode"));
        if (buf && buf!="0")
            this.browserMode = buf;

        var buf = unescape(root.getChildNodeValByName("browser_blank_state"));
        if (buf)
            this.browserBlankStateMsg = buf;

        // Get form layout
        this.xmlFormLayout = root.getChildNodeByName("form");
        this.xmlFormLayoutText = unescape(root.getChildNodeValByName("form_layout_text")); // Default text only used for editing
		*/

        // Get recurrence if any
		if (this.def.recurRules)
		{
            this.recurRules = new Object();
            this.recurRules.fieldTimeStart  = this.def.recurRules.field_time_start;
            this.recurRules.fieldTimeEnd    = this.def.recurRules.field_time_end;
            this.recurRules.fieldDateStart  = this.def.recurRules.field_date_start;
            this.recurRules.fieldDateEnd    = this.def.recurRules.field_date_end;
            this.recurRules.fieldRecurId    = this.def.recurRules.field_recur_id; // local field that stores the recur pattern id
		}
		/*
        var recurNode = root.getChildNodeByName("recurrence");
        if (recurNode.getAttribute('hasrecur')=='t')
        {    
            this.recurRules = new Object();
            this.recurRules.fieldTimeStart     = recurNode.getChildNodeValByName("field_time_start");
            this.recurRules.fieldTimeEnd    = recurNode.getChildNodeValByName("field_time_end");
            this.recurRules.fieldDateStart    = recurNode.getChildNodeValByName("field_date_start");
            this.recurRules.fieldDateEnd    = recurNode.getChildNodeValByName("field_date_end");
            this.recurRules.fieldRecurId    = recurNode.getChildNodeValByName("field_recur_id"); // local field that stores the recur pattern id
        }
		*/

		var fields = this.def.getFields();
        for (var i in fields)
        {
			var srcField = fields[i];

            var field = {
				optional_vals : new Array(),
                name : srcField.name,
                title : srcField.title,
                type : srcField.type,
                subtype : srcField.subtype,
                useWhen : srcField.useWhen,
                iconName : "",
                default_value: srcField.getDefault("null"),
                readonly : srcField.readonly,
                required : srcField.required,
				system : srcField.system,
				tabnum : 0,
                fieldsetnum : 0
			}

			if (srcField.optionalValues)
			{
				for (var key in srcField.optionalValues)
				{
					// Very bad design, eventually we just need to return the fields in this.def
					field.optional_vals[field.optional_vals.length] = [
						key, 
						srcField.optionalValues[key],
						false, 	// legacy
						false	// legacy
					];                                                
				}
			}
			/*
			var opt_vals = child.getChildNodeByName("optional_values");
			if (opt_vals)
			{
				for (var j = 0; j < opt_vals.getNumChildren(); j++)
				{
					var val = opt_vals.getChildNode(j);                        
					// check if there's a form posted value, if so, do not display the already selected value from the dropdown                        
				}
			}
			*/

            this.fields[this.fields.length] = field;
        }
        // Loop through fields
		/*
        var field_nodes = root.getChildNodeByName("fields");
        for (var i = 0; i < field_nodes.getNumChildren(); i++)
        {
            var field = new Object();
            field.optional_vals = new Array();

            var child = field_nodes.getChildNode(i);
            
            if (child.m_name == "field")
            {                
                field.name        = unescape(child.getChildNodeValByName("name"));
                field.title        = unescape(child.getChildNodeValByName("title"));
                field.type         = unescape(child.getChildNodeValByName("type"));
                field.subtype    = unescape(child.getChildNodeValByName("subtype"));
                field.useWhen = unescape(child.getChildNodeValByName("use_when"));
                field.iconName        = unescape(child.getChildNodeValByName("icon_name"));
                field.default_value    = unescape(child.getChildNodeValByName("default_value"));
                //field.heiarch    = (child.getChildNodeValByName("heiarch")=='t') ? true : false;
                //field.parent_id    = unescape(child.getChildNodeValByName("parent"));
                field.readonly    = (child.getChildNodeValByName("readonly")=='t')?true:false;
                field.required = (child.getChildNodeValByName("required")=='t')?true:false;
                field.system = (child.getChildNodeValByName("system")=='t')?true:false;
                field.tabnum     = 0;
                field.fieldsetnum = 0;    
                
                var fkey_table     = child.getChildNodeByName("fkey_table");
                if (fkey_table)
                {
                    // TODO: add fkey
                }

                var opt_vals = child.getChildNodeByName("optional_values");
                if (opt_vals)
                {
                    for (var j = 0; j < opt_vals.getNumChildren(); j++)
                    {
                        var val = opt_vals.getChildNode(j);                        
                        // check if there's a form posted value, if so, do not display the already selected value from the dropdown                        
                        field.optional_vals[field.optional_vals.length] = [unescape(val.getAttribute("key")), 
                                                                               unescape(val.getAttribute("title")),
                                                                               (val.getAttribute("heiarch")=='t') ? true : false, 
                                                                               val.getAttribute("parent_id")];                                                
                    }
                }

                this.fields[this.fields.length] = field;
            }
        }
		*/

		/*
        if (cachedInd == -1)
        {
            var def = new Object();
            def.name = type;
            def.root = root;
            CAntObjectDefs[CAntObjectDefs.length] = def;
        }
		*/
    //}

    this.condition = new CAntObjectCond();

	// Now check for extensions to the base object class
	var subClassName = "";
	var onameParts = this.obj_type.split("_");
	for (var i = 0; i < onameParts.length; i++)
	{
		subClassName += onameParts[i].charAt(0).toUpperCase() + onameParts[i].substr(1);
	}

	subClassName = "CAntObject_" + subClassName;

	if (typeof subClassName != "undefined")
	{
		var extFunct = null;

		try
		{
			var extFunct = eval(subClassName);
		}
		catch (e) {}

		// Now extend this object with the subclass
		if (extFunct != null)
			extFunct(this);
	}
}

/**
 * Load the values for this object
 *
 * @public
 * @param {int} id If set then load id, otherwise try to get this.id
 */
CAntObject.prototype.load = function(id)
{
	var oid = (typeof id != "undefined") ? id : this.id;

    if (oid == null)
        return;

    var ajax = new CAjax("json");
	ajax.m_obj = this;
    ajax.onload = function(objData)
    {
        if (objData)
        {
			this.m_obj.id = objData.id;

            if(objData.security)
            {
                this.m_obj.security.view = objData.security.view;
                this.m_obj.security.edit = objData.security.view;
                this.m_obj.security.del= objData.security.view;
            }

            // Handle iconName
			if (objData.iconName)
				this.m_obj.iconName = objData.iconName;
            
            for (var i = 0; i < this.m_obj.fields.length; i++)
            {
                var field = this.m_obj.fields[i];

                if(this.m_obj.newWindowArgs && typeof this.m_obj.newWindowArgs[field.name] == "undefined")
                    continue;
                
                if (field.type == "fkey_multi" || field.type == "object_multi")
                {
					// check if new window has posted values
					if(this.m_obj.newWindowArgs)
					{
						// split the keys and titles from posted multi values
						var multiKey = unescape_utf8(this.m_obj.newWindowArgs[field.name][0]).split(",");
						var multiTitle = unescape_utf8(this.m_obj.newWindowArgs[field.name + "Multi"][0]).split(",");
						
						// loop thru post the keys and values and set the multi value
						for(var multiArg = 0; multiArg < multiKey.length; multiArg++)
						{
							if(multiKey[multiArg].length)
							{
								this.m_obj.setMultiValue(field.name, multiKey[multiArg], multiTitle[multiArg]);
							
								// Cache the label if not already exists so extra query is not necessary
								var bFound = false;
								for (var j = 0; j < field.optional_vals; j++)
								{
									if (field.optional_vals[j][0] == multiKey[multiArg])
										bFound = true;
								}
								
								if (!bFound)
								{
									var ind = field.optional_vals.length;
									field.optional_vals[ind] = new Array();
									field.optional_vals[ind][0] = multiKey[multiArg];
									field.optional_vals[ind][1] = unescape(multiTitle[multiArg]);
								}
							}                                
						}
					}
					else
					{
						if (objData[field.name + "_fval"])
						{
							for (var fvalid in objData[field.name + "_fval"])
							{
								this.m_obj.setMultiValue(field.name, fvalid, objData[field.name + "_fval"][fvalid]);
							}
						}
					}
                    
                    var divBlankState = document.getElementById('divBlankState');
                    if(divBlankState)
                        divBlankState.parentNode.removeChild(divBlankState);
                        
                }
                else
                {
                    if (field.type == "fkey" || field.type == "object")
                    {   
                        // check if new window has posted values
                        if(this.m_obj.newWindowArgs)
                        {
                            var fieldKey = this.m_obj.newWindowArgs[field.name][0];
                            var fieldValue = this.m_obj.newWindowArgs[field.name + "Fkey"][0];
                            this.m_obj.setValue(field.name, fieldKey, unescape_utf8(fieldValue));
                        }
                        else
                        {
							if (objData[field.name + "_fval"])
							{
								for (var fvalid in objData[field.name + "_fval"])
									this.m_obj.setValue(field.name, fvalid, objData[field.name + "_fval"][fvalid]);

							}
                        }                        
                    }
                    else if (field.type == "bool")
                    {
                        // added newWindowArgs to fetch the edited values from inline form
                        if(this.m_obj.newWindowArgs)
                        {
                            var boolValue = this.m_obj.newWindowArgs[field.name][0];
                            if(boolValue==="t" || boolValue===true)
                                boolValue = true;
                            else if(boolValue==="f" || boolValue===false)
                                boolValue = false;
                                
                            this.m_obj.setValue(field.name, boolValue);
                        }                            
                        else
                            this.m_obj.setValue(field.name, (objData[field.name]==='t' || objData[field.name]===true)?true:false);

						// If readonly flag the edit the security to disable edit
						if (field.name == "f_readonly" && (objData[field.name]=='t' || objData[field.name]===true))
							this.m_obj.security.edit = false;
                    }
                    else
                    {
                        // added newWindowArgs to fetch the edited values from inline form
                        if(this.m_obj.newWindowArgs)
                            this.m_obj.setValue(field.name, unescape_utf8(this.m_obj.newWindowArgs[field.name][0]));
                        else
                            this.m_obj.setValue(field.name, objData[field.name]);
                    }
                }
            }
        }



        // check for recurrence pattern. If exists then load first before calling this.onload
        if (this.m_obj.recurRules !== null && objData.recurrence_pattern !== null)
        {
			this.m_obj.getRecurrencePattern(true, objData.recurrence_pattern);

			/* We do not need to load the recurrence pattern since it is already included in the /svr/entity/get
            if (this.m_obj.getValue(this.m_obj.recurRules.fieldRecurId))
            {
                var rp = this.m_obj.getRecurrencePattern(true);
                rp.objcls = this.m_obj;
                rp.onload = function()
                {
                    this.objcls.onload();
                }
                rp.load(this.m_obj.getValue(this.m_obj.recurRules.fieldRecurId));
            }
            else
            {
                this.m_obj.onload();
            }
            */
        }

		this.m_obj.onload();

        this.m_obj.loaded = true;
        this.m_obj.dirty = false;
    };

    var url = "/svr/entity/get?obj_type="+ this.name +"&id=" + oid;
    ajax.exec(url);
}


/**
 * Set data for this object from data object
 *
 * @public
 * @this {CAntObject}
 */
CAntObject.prototype.setData = function(objData)
{
	if (objData.id)
		this.id = objData.id;

	for (var i = 0; i < this.fields.length; i++)
	{
		var field = this.fields[i];
		
		// Skip over undefined data
		if (typeof objData[field.name] == "undefined")
			continue;

		var fieldData = objData[field.name];

		if (field.type == "fkey_multi" || field.type == "object_multi")
		{
			if (fieldData)
			{
				for (var m = 0; m < fieldData.length; m++)
				{
					if (fieldData[m].key)
					{                                
						this.setMultiValue(field.name, fieldData[m].key, fieldData[m].value);

						// Cache the label if not already exists so extra query is not necessary
						var bFound = false;
						for (var j = 0; j < field.optional_vals; j++)
						{
							if (field.optional_vals[j][0] == fieldData[m].key)
								bFound = true;
						}
						
						if (!bFound)
						{
							var ind = field.optional_vals.length;
							field.optional_vals[ind] = new Array();
							field.optional_vals[ind][0] = fieldData[m].key;
							field.optional_vals[ind][1] = fieldData[m].value;
						}                                
					}
				}  
				
			}
		}
		else
		{
			if (field.type == "fkey" || field.type == "object")
			{   
				this.setValue(field.name, fieldData.key, fieldData.value);
			}
			else if (field.type == "bool" && typeof fieldData === "string")
			{
				this.setValue(field.name, (fieldData=='t')?true:false);
			}
			else
			{
				this.setValue(field.name, fieldData);
			}
		}
	}

	// check for recurrence pattern. If exists then load first before calling this.onload
	// TODO: load recur rules
	/*
	if (this.m_obj.recurRules != null)
	{
		if (this.m_obj.getValue(this.m_obj.recurRules.fieldRecurId))
		{
			var rp = this.m_obj.getRecurrencePattern(true);
			rp.objcls = this.m_obj;
			rp.onload = function()
			{
				this.objcls.onload();
			}
			rp.load(this.m_obj.getValue(this.m_obj.recurRules.fieldRecurId));
		}
	}
	*/

	this.loaded = true;
	this.dirty = false;
}

/**
 * Get raw data for this object from data object
 *
 * @public
 * @this {CAntObject}
 */
CAntObject.prototype.getData = function(objData)
{
	var objData = new Object();
	if (objData.id)
		objData.id = this.id;

	/*
	var sec_view = root.getAttribute("sec_view");
	var sec_edit = root.getAttribute("sec_edit");
	var sec_delete= root.getAttribute("sec_delete");

	this.m_obj.security.view = (sec_view == 't') ? true : false;
	this.m_obj.security.edit = (sec_edit == 't') ? true : false;
	this.m_obj.security.del= (sec_delete == 't') ? true : false;
	*/

	for (var i = 0; i < this.fields.length; i++)
	{
		var field = this.fields[i];
		
		if (field.type == "fkey_multi" || field.type == "object_multi")
		{
			var vals = this.getMultiValues(field.name);
			if (vals)
			{
				objData[field.name] = new Array();

				for (var m = 0; m < vals.length; m++)
				{
					var key = vals[0];
					var val = this.getValueName(field.name, key);

					objData[field.name][objData[field.name].length] = {key:key, value:val};
				}  
				
			}
		}
		else
		{
			if (field.type == "fkey" || field.type == "object" || field.type == "alias")
			{   
				objData[field.name] = {key:this.getValue(field.name), value:this.getValueName(field.name)};
			}
			/*
			else if (field.type == "bool")
			{
				objData[field.name] = (this.getValue(field.name)=='t') ? true : false;
			}
			*/
			else
			{
				objData[field.name] = this.getValue(field.name);
			}
		}
	}

	// Add security obj
	objData.security = new Object();
	objData.security.view = this.security.view;
	objData.security.edit = this.security.edit;
	objData.security["delete"] = this.security.del;
	objData.iconName = this.iconName;
	objData.iconPath = this.getIcon(16, 16); // Default icon for lists

    
    /*if(this.obj_type == "email_thread")
    {
        objData.flag_seen = "f"; // Make sure that new thread entries are unread
    }*/

	// check for recurrence pattern. If exists then load first before calling this.onload
	// TODO: load recur rules
	/*
	if (this.m_obj.recurRules != null)
	{
		if (this.m_obj.getValue(this.m_obj.recurRules.fieldRecurId))
		{
			var rp = this.m_obj.getRecurrencePattern(true);
			rp.objcls = this.m_obj;
			rp.onload = function()
			{
				this.objcls.onload();
			}
			rp.load(this.m_obj.getValue(this.m_obj.recurRules.fieldRecurId));
		}
	}
	*/

	this.loaded = true;
	this.dirty = false;

	return objData;
}

/*************************************************************************
*    Function:    onload
*
*    Purpose:    Over-ride
**************************************************************************/
CAntObject.prototype.onload = function()
{
    // This function exists to be defined before load is called above
}

/*************************************************************************
*    Function:    getCachedObjDef
*
*    Purpose:    Find out of the definition for this object has been cached
*                Defs can take a few seconds to load so caching them is important
**************************************************************************/
CAntObject.prototype.getCachedObjDef = function(obj_name)
{
    for (var i = 0; i < CAntObjectDefs.length; i++)
    {
        if (CAntObjectDefs[i].name == obj_name)
        {
            return i;
        }
    }

    return -1;
}

/**
 * Get the name of this object
 *
 * @return {string} The name of this object based on common name fields like 'name' 'title 'subject'
 */
CAntObject.prototype.getName = function()
{
    if (this.getValue("name"))
        return this.getValue("name");
    else if (this.getValue("title"))
        return this.getValue("title");
    else if (this.getValue("subject"))
        return this.getValue("subject");
    else if (this.getValue("id"))
        return this.getValue("id");
    else
        return "";
}

/**
 * @depricated Use this.getName
 * 
 * Leave in place for backwards compatibility
 */
CAntObject.prototype.getLabel = function()
{
	return this.getName();
}

/**************************************************************************
* Function:     getFields    
*
* Purpose:        Get an array of all fields in the object type
**************************************************************************/
CAntObject.prototype.getFields = function()
{
    var fields = new Array();

    for (var i = 0; i < this.fields.length; i++)
    {
        // Filter "use with" to only show fields marked to use with certain values like when user_id equals 1
        if (this.fields[i].useWhen)
        {
            var useWithParts = this.fields[i].useWhen.split(":");
            if (this.getValue(useWithParts[0]) == useWithParts[1])
                fields[fields.length] = this.fields[i];
        }
        else
        {
            fields[fields.length] = this.fields[i];
        }
    }

    return fields;
}

/**************************************************************************
* Function:     getFieldByName    
*
* Purpose:        Get a specific field by name
**************************************************************************/
CAntObject.prototype.getFieldByName = function(name)
{
    if (name.indexOf(".")!=-1)
    {
        var parts = name.split(".");
        if (parts.length==2)
        {
            var fld = this.getFieldByName(parts[0]);
//        	console.log("Name: " + name);
//        	console.log("Start: " + parts[0]);
//            console.log(fld);
//            console.log("End: " + parts[0]);
            if (fld)
            {
                if (fld.type == "object" && fld.subtype)
                {
                    var tmpobj = new CAntObject(fld.subtype);
                    return tmpobj.getFieldByName(parts[1]);
                }
            }
        }
    }
    else
    {
        for (var i = 0; i < this.fields.length; i++)
        {
            if (this.fields[i].name == name)
                return this.fields[i];
        }
    }

    return null;
}

/**************************************************************************
* Function:     getField
*
* Purpose:        Get a field by index
**************************************************************************/
CAntObject.prototype.getField = function(ind)
{
    return this.fields[ind];
}

/**************************************************************************
* Function:     getNumFields
*
* Purpose:        Get the number of fields this object has
**************************************************************************/
CAntObject.prototype.getNumFields = function()
{
    return this.fields.length;
}

/**************************************************************************
* Function:     buildAdvancedQuery
*
* Purpose:        Create an advanced query form and return a CAntObjectCond obj
*
* Arguments:    con:dom - a container that will contain this form
*                 saved_query - load a saved CAntObjectCond
**************************************************************************/
CAntObject.prototype.buildAdvancedQuery = function(con, saved_query, options)
{
    var opts = (options) ? options : new Object();
    this.dv_querygroups = alib.dom.createElement("div", con);

    var dv = alib.dom.createElement("div", this.dv_querygroups);

    this.tblQuery = new CToolTable();
    this.tblQuery.print(dv);

    // Add query row
    // --------------------------------------
    if (saved_query && saved_query.length)
        this.queryLoadSaved(saved_query, opts);
    //else
    //    this.addQueryRow(true);

    var dv_add = alib.dom.createElement("div", con);
    var a_add = alib.dom.createElement("a", dv_add);
    a_add.cls = this;
    a_add.onclick = function ()
    {
        this.cls.addQueryRow();
    }
    a_add.innerHTML = "Add Condition";
    a_add.href = "javascript:void(0);";

    return this.condition;
}

/**************************************************************************
* Function:     queryLoadSaved
*
* Purpose:        load a saved CAntObjectCond into current this.condition
**************************************************************************/
CAntObject.prototype.queryLoadSaved = function(saved_query, options)
{
    var opts = (options) ? options : new Object();
    this.condition.clearConditions();
    for (var i = 0; i < saved_query.length; i++)
    {
        var first = (i == 0) ? true : false;
        
        // We need to check if fieldname is an object and has subtype (e.g. user_id)
        var field = this.getFieldByName(saved_query[i].fieldName);
        if(field.type == "object" && field.subtype)
        {
            // We need to set the value here so when assigning the condition value later in querySetValueInput()
            // The this.getValue() can get the field value of the fieldname
            this.setValue(saved_query[i].fieldName, saved_query[i].condValue);
        }
        
        // saved_query[i].id is the id that is retrieved from the database
        this.addQueryRow(first, saved_query[i].blogic, saved_query[i].fieldName, saved_query[i].operator, saved_query[i].condValue, opts, saved_query[i].id);
    }
}

/**************************************************************************
* Function:     addQueryRow
*
* Purpose:        Add a row that will be used to build a condition
**************************************************************************/
CAntObject.prototype.addQueryRow = function(first, blogic, fieldName, operator, condValue, options, condId)
{
    var opts = (options) ? options : new Object();
    var rw = this.tblQuery.addRow();

    // First row does not need and/or
    var cbLogic = alib.dom.createElement("select");
    cbLogic[cbLogic.length] = new Option("And", "and", false, (blogic=="and")?true:false);
    cbLogic[cbLogic.length] = new Option("Or", "or", false, (blogic=="or")?true:false);
    rw.addCell(cbLogic, false, "center", "50px");

    // Create condition type drop-down
    var sel_cond = alib.dom.createElement("select");

    // Create value div
    var dv_val = alib.dom.createElement("div");

    // Create fields drop-down
    var sel_name = alib.dom.createElement("select");
    for (var i = 0; i < this.fields.length; i++)
    {
        sel_name[sel_name.length] = new Option(this.fields[i].title, this.fields[i].name, false, (fieldName==this.fields[i].name)?true:false);
        if (this.fields[i].type == "object" && this.fields[i].subtype && this.loadQuerySubObjects)
        {            
            var assoc_obj = new CAntObject(this.fields[i].subtype);
            for (var j = 0; j < assoc_obj.fields.length; j++)
            {
                sel_name[sel_name.length] = new Option(this.fields[i].title + "." + assoc_obj.fields[j].title, 
                                                        this.fields[i].name + "." + assoc_obj.fields[j].name, false, 
                                                        (fieldName==this.fields[i].name + "." + assoc_obj.fields[j].name)?true:false);
            }
        }
    }
    sel_name.m_cond_cb = sel_cond;
    sel_name.m_dv_val = dv_val;
    sel_name.m_cls = this;
    sel_name.opts = opts;
    sel_name.onchange = function()
    {
        this.m_cls.queryGetCondCombo(this.m_cond_cb, this.value);
        this.m_cls.querySetValueInput(this.m_dv_val, this.value, null, this.opts);
    }

    // Set defaults
    this.queryGetCondCombo(sel_cond, sel_name.value);
    // Add condition watch
    var cid = this.condition.addWatchCondition(cbLogic, sel_name, sel_cond, condId);
    dv_val.cid = cid;
    this.querySetValueInput(dv_val, sel_name.value, null, opts);
    
    rw.addCell(sel_name);
    rw.addCell(sel_cond);
    rw.addCell(dv_val);

    var del_dv = alib.dom.createElement("div");
    rw.addCell(del_dv, true, "center");
    var icon = (typeof(Ant)=='undefined') ? "/images/icons/deleteTask.gif" : "/images/themes/" + Ant.theme.name+ "/icons/deleteTask.gif";
    del_dv.innerHTML = "<img border='0' src='"+icon+"' />";
    alib.dom.styleSet(del_dv, "cursor", "pointer");
    del_dv.m_rw = rw;
    del_dv.m_condition = this.condition;
    del_dv.m_cid = cid;
    //del_dv.m_id = id;
    del_dv.onclick = function()
    {
        this.m_condition.delCondition(this.m_cid);
        this.m_rw.deleteRow();
    }

    // Set values
    if (fieldName)
    {
        for (var i = 0; i < sel_name.options.length; i++)
        {
            if (sel_name.options[i].value == fieldName)
            {
                sel_name.selectedIndex = i;
                this.queryGetCondCombo(sel_cond, fieldName);
                this.querySetValueInput(dv_val, fieldName, condValue, opts);
            }
        }
    }

    if (operator)
    {
        sel_cond.m_cond.operator = operator;
        for (var i = 0; i < sel_cond.options.length; i++)
        {
            if (sel_cond.options[i].value == operator)
            {
                sel_cond.selectedIndex = i;
            }
        }
    }

    if (condValue)
    {
        if (dv_val.inptType == "select")
        {
            for (var i = 0; i < dv_val.inpRef.options.length; i++)
            {
                if (dv_val.inpRef.options[i].value == condValue)
                    dv_val.inpRef.selectedIndex = i;
            }
        }
        
        if (dv_val.inptType == "dynselect")
        {
            dv_val.dynSel.onSelect(condValue);
        }
        else if (dv_val.inptType == "rte")
        {
            dv_val.inpRef.setValue(condValue);
        }
        else if (dv_val.inpRef)
        {
            dv_val.inpRef.value = condValue;
        }
    }
}

/**************************************************************************
* Function:     queryGetCondCombo
*
* Purpose:        Set the options for a select in a query field.
*
* Arguments:    1.    sel:dom.select - the select element
*                 2.    fname:string - the name of the field select is representing
**************************************************************************/
CAntObject.prototype.queryGetCondCombo = function(sel, fname)
{
    for (var m=sel.options.length-1; m>=0; m--) 
        sel.options[m]=null;

    var field = this.getFieldByName(fname);
    
    var type_opts = this.getCondOperators(field.type);
    
    for (var i = 0; i < type_opts.length; i++)
        sel.options[sel.options.length] = new Option(type_opts[i][1], type_opts[i][0]);

    sel.selectedIndex = 0;
    if (sel.m_cond)
        sel.m_cond.operator = sel.value;
}

/*************************************************************************
*    Function:    getCondOperators
*
*     Scope:        Public
*
*    Purpose:    Get a list of operators for a field type
**************************************************************************/
CAntObject.prototype.getCondOperators = function(ftype)
{
    switch (ftype)
    {
    case 'fkey_multi':
    case 'fkey':
        var type_opts = [
                            ["is_equal", "is equal to"],
                            ["is_not_equal", "is not equal to"]
                        ];    
        break;
    case 'number':
    case 'real':
    case 'integer':
        var type_opts = [
                            ["is_equal", "is equal to"],
                            ["is_not_equal", "is not equal to"],
                            ["is_greater", "is greater than"],
                            ["is_less", "is less than"],
                            ["is_greater_or_equal", "is greater than or equal to"],
                            ["is_less_or_equal", "is less than or equal to"],
                            ["begins_with", "begins with"]
                        ];
        break;
    case 'date':
    case 'timestamp':
        var type_opts = [
                            ["is_equal", "is equal to"],
                            ["is_not_equal", "is not equal to"],
                            ["is_greater", "is greater than"],
                            ["is_less", "is less than"],
                            ["day_is_equal", "day is equal to"],
                            ["month_is_equal", "month is equal to"],
                            ["year_is_equal", "year is equal to"],
                            ["is_greater_or_equal", "is greater than or equal to"],
                            ["is_less_or_equal", "is less than or equal to"],
                            ["last_x_days", "within last (x) days"],
                            ["last_x_weeks", "within last (x) weeks"],
                            ["last_x_months", "within last (x) months"],
                            ["last_x_years", "within last (x) years"],
                            ["next_x_days", "within next (x) days"],
                            ["next_x_weeks", "within next (x) weeks"],
                            ["next_x_months", "within next (x) months"],
                            ["next_x_years", "within next (x) years"]
                        ];
        break;
    case 'bool':
        var type_opts = [
                            ["is_equal", "is equal to"],
                            ["is_not_equal", "is not equal to"]
                        ];
        break;
    default: // Text
        var type_opts = [
                            ["is_equal", "is equal to"],
                            ["is_not_equal", "is not equal to"],
                            ["begins_with", "begins with"],
                            ["contains", "contains"]
                        ];
        break;
    }

    return type_opts;
}

/*************************************************************************
*    Function:    querySetValueInput
*
*     Scope:        PRIVATE
*
*    Purpose:    Used to create input for dynamic conditions
**************************************************************************/
CAntObject.prototype.querySetValueInput = function(dv, fname, val_field, options)
{
    var opts = (options) ? options : new Object();
    dv.innerHTML = "";
    
    var field = this.getFieldByName(fname);

    var obj_type = this.name;
    if (fname.indexOf(".")!=-1)
    {
        var parts = fname.split(".");
        if (parts.length==2)
        {
            var fld = this.getFieldByName(parts[0]);
            if (fld)
                var obj_type = fld.subtype;
        }
    }

    if (field.type == "fkey" || field.type == "fkey_multi")
    {
        var sel = new AntObjectGroupingSel("None", obj_type, field.name, val_field);
        for (var f = 0; f < this.m_filters.length; f++)
            sel.setFilter(this.m_filters[f].fieldName, this.m_filters[f].value);

        sel.print(dv);
        this.condition.addWatchConditionDynSel(dv.cid, sel);
        var inp = sel.getInput();
        dv.dynSel = sel;
        dv.inptType = "dynselect";
    }
    else if (field.type == "object" && field.subtype)
    {
        var inp = alib.dom.createElement("div");
        var lbl = alib.dom.createElement("span");
        var objid = this.getValue(field.name); // see if object has already been set to an id
        
        if (objid)
        {
            lbl.innerHTML = "";
            var a = alib.dom.createElement("a", lbl);
            a.href = 'javascript:void(0);';
            a.objid = objid;
            a.obj_type = field.subtype;
            a.onclick = function() 
            {
                loadObjectForm(this.obj_type, this.objid);
            }
            
            var oname = this.getValueName(field.name); 
            if (oname) // See if foreign key value is cached
                a.innerHTML = oname;
            else // The function below will pull the name of the object and populate 'a'.innerHTML
                objectSetNameLabel(field.subtype, objid, a);

            // Add "clear" button if this is not a required field
            if (!field.required)
            {
                var sp = alib.dom.createElement("span", lbl);
                sp.innerHTML = "&nbsp;";

                var aclear = alib.dom.createElement("a", lbl);
                aclear.href = 'javascript:void(0);';
                aclear.mainObject = this;
                aclear.fname = field.name;
                aclear.lbl = lbl;
                aclear.onclick = function() { this.lbl.innerHTML = "None&nbsp;&nbsp;&nbsp;"; this.mainObject.setValue(this.fname, ""); }
                aclear.innerHTML = "<img src='/images/icons/delete_10.png' />";
            }
            
            var sp = alib.dom.createElement("span", lbl);
            sp.innerHTML = "&nbsp;&nbsp;&nbsp;";
            
            // Need to add watch browse
            this.condition.addWatchConditionBrowse(dv.cid, objid);
        }
        else
        {
            lbl.innerHTML = "None Selected&nbsp;&nbsp;&nbsp;";
        }
        
        // Inline function to select a new object
        var selobj = function(field, options, lbl, dv, cls)
        {
            var ob = new AntObjectBrowser(field.subtype);
            ob.cbData.dv = dv;
            ob.cbData.cls = cls;
            ob.cbData.lbl = lbl;
            ob.cbData.field = field;
            ob.cbData.field_name = field.name;
            ob.cbData.obj_type = field.subtype;

            ob.onSelect = function(oid)
            {
                this.cbData.lbl.innerHTML = "";
                this.cbData.cls.setValue(this.cbData.field_name, oid);
                var a = alib.dom.createElement("a", this.cbData.lbl);
                a.href = "javascript:void(0);";
                a.options = {oid:oid, obj_type:this.cbData.obj_type}
                a.onclick = function() { loadObjectForm(this.options.obj_type, this.options.oid); }
                a.innerHTML = oid;

                // Add "clear" button if this is not a required file (kind of redundant but works for now)
                if (!this.cbData.field.required)
                {
                    var sp = alib.dom.createElement("span", this.cbData.lbl);
                    sp.innerHTML = "&nbsp;";

                    var aclear = alib.dom.createElement("a", this.cbData.lbl);
                    aclear.href = 'javascript:void(0);';
                    aclear.mainObject = this.cbData.cls;
                    aclear.fname = this.cbData.field.name;
                    aclear.lbl = this.cbData.lbl;
                    aclear.onclick = function() { this.lbl.innerHTML = "None&nbsp;&nbsp;&nbsp;"; this.mainObject.setValue(this.fname, ""); }
                    aclear.innerHTML = "<img src='/images/icons/delete_10.png' />";
                }

                var sp = alib.dom.createElement("span", this.cbData.lbl);
                sp.innerHTML = "&nbsp;&nbsp;&nbsp;";

                // Get name of object from server 
                this.cbData.cls.condition.addWatchConditionBrowse(this.cbData.dv.cid, oid);
                objectSetNameLabel(this.cbData.obj_type, oid, a);
            }
            ob.displaySelect(options.parent_dlg);    // pass parent dialog
        }

        // Create button to browse for new object of 'subtype' which should be the object_type name
        var btn = new CButton("Select", selobj, [field, opts, lbl, dv, this], "b1");
        inp.appendChild(lbl);
        inp.appendChild(btn.getButton());
    }
    else if (field.type == "bool")
    {
        var inp = alib.dom.createElement("select", dv);
        inp[inp.length] = new Option("True", 't', false, (val_field=='t' || val_field===true)?true:false);
        inp[inp.length] = new Option("False", 'f', false, (val_field!='t' || val_field!==true)?true:false);
        dv.appendChild(inp);
        inp.checked = (val_field == 't' || val_field === true) ? true : false;
        dv.inptType = "select";
        if (val_field)
            inp.val_field = val_field;

        this.condition.addWatchConditionVal(dv.cid, inp);
    }
    else
    {
        if (field.optional_vals && field.optional_vals.length)
        {
            var inp = alib.dom.createElement("select", dv);
            if (val_field)
            {
                inp.val_field = val_field;
            }


            this.buildInputDropDown(inp, field.optional_vals, val_field);

            dv.inptType = "select";
            dv.inpRef = inp;

            this.condition.addWatchConditionVal(dv.cid, inp);
        }
        else
        {
            var inp = alib.dom.createElement("input");
            inp.type = "text";
			//alib.dom.styleSetClass(inp, "fancy");
            if (val_field)
                inp.value = val_field;

            if (field.type == "number" && field.subtype=="double precision")
                inp.maxLength = 15;

            if (val_field)
            {
                var val_fld_funct = function(evnt)
                {
                    if (alib.userAgent.ie)
                        evnt.srcElement.val_field = evnt.srcElement.value;
                    else
                        this.val_field = this.value;
                }

                inp.val_field = val_field;
                alib.dom.addEvntListener(inp, "change", val_fld_funct);
            }

            dv.inptType = "input";
        }
    }


    dv.appendChild(inp);
    dv.inpRef = inp;

    this.condition.addWatchConditionVal(dv.cid, inp);

    return inp;
}

/*************************************************************************
*    Function:    buildInputDropDown
*
*     Scope:        PRIVATE
*
*    Purpose:    Used to create a multi-val select box
**************************************************************************/
CAntObject.prototype.buildInputDropDown = function(cbMval, optional_vals, val, pnt, pre)
{
    var value = (val) ? val : null;
    var parent_id = (pnt) ? pnt : "";
    var pre_txt = (pre) ? pre : "";
    var spacer = "\u00A0\u00A0"; // Unicode \u00A0 for space
    for (var n = 0; n < optional_vals.length; n++)
    {
        if (optional_vals[n][3] != parent_id)
        {
            continue;
        }

        cbMval[cbMval.length] = new Option(pre_txt+optional_vals[n][1], optional_vals[n][0], false, (value==optional_vals[n][0])?true:false);
        // Check for heiarchy
        if (optional_vals[n][2])
            this.buildInputDropDown(cbMval, optional_vals, value, optional_vals[n][0], pre_txt+spacer);
    }
}

/*************************************************************************
*    Function:    fieldCreateValueInput
*
*     Scope:        Public
*
*    Purpose:    Same as above but condition is not updated, a simple input
*                form element is created for the field and the caller
*                is responsible for capturing and setting the data.
**************************************************************************/
CAntObject.prototype.fieldCreateValueInput = function(dv, fname, val_field, options)
{
    var opts = (options) ? options : new Object();
    dv.innerHTML = "";

    var field = this.getFieldByName(fname);
    
    if (field.type == "fkey" || field.type == "fkey_multi")
    {
        if(field.subtype == "user")
        {
            var browserCon = alib.dom.createElement("div", dv);            
            var userLabel = alib.dom.createElement("label", browserCon);
            
            userLabel.innerHTML = "None Selected";
            
            alib.dom.styleSet(userLabel, "font-size", "12px");
            alib.dom.styleSet(userLabel, "margin-right", "10px");
            
            // add select user button feature
            var selectUser = alib.dom.setElementAttr(alib.dom.createElement("input", browserCon), [["type", "button"], ["value", "Select"]]);
            selectUser.userLabel = userLabel;
            selectUser.userId = 0;
            selectUser.onclick = function()
            {
                var cbrowser = new CUserBrowser();
                cbrowser.selectCls = this;
                cbrowser.onSelect = function(cid, name) 
                {
                    this.selectCls.onSelect(cid);
                    this.selectCls.userLabel.innerHTML = name;
                }
                cbrowser.showDialog();
            }
            
            dv.inptType = "userBrowser";
            dv.inpRef = selectUser;
        }
        else
        {
            var sel = new AntObjectGroupingSel("None", this.name, field.name, val_field, this, opts);
            for (var f = 0; f < this.m_filters.length; f++)
                sel.setFilter(this.m_filters[f].fieldName, this.m_filters[f].value);

            var inp = sel.getInput();
            sel.print(dv);
            dv.inptType = "dynselect";
            dv.inpRef = sel;
        }
    }
	else if (field.type == "object" && field.subtype)
	{
	    var browserCon = alib.dom.createElement("div", dv);            
        var label = alib.dom.createElement("label", browserCon);
        
        label.innerHTML = "None Selected";
        
        alib.dom.styleSet(label, "font-size", "12px");
        alib.dom.styleSet(label, "margin-right", "10px");
        
        // add select user button feature
        var objBrowser = function(objType, label)
        {
            var antBrowser = new AntObjectBrowser(objType);
            antBrowser.onSelect = function(objId, objLabel) 
            {
                label.innerHTML = objLabel;
                objSelect(objId);
            }
            antBrowser.displaySelect();
        }
        
        var btn = new CButton("Select", objBrowser, [field.subtype, label], "b1");
        var selectBtn = btn.getButton();
        browserCon.appendChild(selectBtn);
        
        var objSelect = function(objId)
        {
            selectBtn.onSelect(objId);
        }
        
        dv.inptType = "objectBrowser";
        dv.inpRef = selectBtn;
	}
    else if (field.optional_vals && field.optional_vals.length)
    {
        var inp = alib.dom.createElement("select", dv);
        
        this.buildInputDropDown(inp, field.optional_vals, val_field);

        dv.inptType = "select";
        dv.inpRef = inp;
    }
    else if (field.type == "alias")
    {
        var inp = alib.dom.createElement("select", dv);

        for (var i = 0; i < this.fields.length; i++)
        {
            var fldinst = this.fields[i];
            if (field.subtype == fldinst.subtype && fldinst.type != "alias")
            {
                inp[inp.length] = new Option(fldinst.title, fldinst.name, false, (val_field == fldinst.name)?true:false);
            }
        }

        dv.inptType = "select";
        dv.inpRef = inp;
    }
    else
    {
        if (field.type == "bool")
        {
            var inp = alib.dom.createElement("input");
            inp.type = "checkbox";
            inp.checked = (val_field) ? true : false;

            dv.inptType = "checkbox";
        }
        else
        {
            if (opts.rich)
            {
                //var inp = new CRte();
				var inp = alib.ui.Editor();
                dv.inptType = "rte";
            }
            else if (opts.multiLine)
            {
                var inp = alib.dom.createElement("textarea");
                dv.inptType = "input";
            }
            else
            {
                var inp = alib.dom.createElement("input");
                inp.type = "text";
                dv.inptType = "input";
				alib.dom.styleSetClass(inp, "fancy");
            }

            if (opts.height)
                alib.dom.styleSet(inp, "height", opts.height);

            if (opts.width)
                alib.dom.styleSet(inp, "width", opts.width);

            if (val_field && !opts.rich)
                inp.value = val_field;

            if (field.type == "real")
                inp.maxLength = 15;

        }
        dv.inpRef = inp;
        if (opts.rich)
        {
            inp.print(dv, '100%', '250px');
            if (val_field)
                inp.setValue(val_field);
        }
        else
            dv.appendChild(inp);

        // Add date selector
        if (field.type == "date")
        {
            /*
            var a_CalStart = alib.dom.createElement("span", dv);
            a_CalStart.innerHTML = "<img src='/images/calendar.gif' border='0'>";
            var start_ac = new CAutoCompleteCal(inp, a_CalStart);
            */
            var start_ac = new CAutoCompleteCal(inp);
            alib.dom.styleSet(inp, "width", "100px");
        }
        else if (field.type == "timestamp")
        {
            if (opts.part == "time")
            {
                var start_ac = new CAutoCompleteTime(inp);
                alib.dom.styleSet(inp, "width", "75px");
            }
            else 
            {
                var start_ac = new CAutoCompleteCal(inp);
                alib.dom.styleSet(inp, "width", "100px");
            }

            if (opts.part)
            {
                inp.part = opts.part;
                inp.value = this.getInputPartValue(field.name, val_field, opts.part);
            }
        }
        else if (field.type == "bool")
        {
            alib.dom.styleSet(inp, "width", "15px");
        }
        else
        {
                alib.dom.styleSet(inp, "width", "99%");
        }

        // Must be added after appended
        if (opts.multiLine && !opts.rich)
        {
            alib.dom.textAreaAutoResizeHeight(inp, 50, 400);
        }
    }
    return dv.inpRef;
}

/*************************************************************************
*    Function:    fieldGetValueInput
*
*     Scope:        PUBLIC
*
*    Purpose:    Return an input elment for forms that updates vals for 
*                fields in this object object. No externall onchange is
*                necessary.
**************************************************************************/
CAntObject.prototype.fieldGetValueInput = function(inp_div, fname, options)
{
    var opts = (options) ? options : new Object(); // Set various input options

    inp_div.innerHTML = "";
    var val = this.getValue(fname);

    var field = this.getFieldByName(fname);
    
    this.fieldCreateValueInput(inp_div, field.name, val, opts);
    if (field.type == "fkey_multi")
    {
        var vals = this.getMultiValues(field.name);
        
        // Add dropdown
        var dv = alib.dom.createElement("div", inp_div);
        var dv_opt_con = alib.dom.createElement("div", inp_div);
        dv_opt_con.id = field.name;
        
        inp_div.inpRef.field = field;
        inp_div.inpRef.m_cls = this;
        inp_div.inpRef.dv_opt_con = dv_opt_con;
        inp_div.inpRef.onSelect = function()
        {
            if (this.value != "")
            {
                // Set in global object
                // -------------------------------------
                this.m_cls.setMultiValue(this.field.name, this.value);

                // Add to list of multi-values
                // -------------------------------------
                var label = this.valueName;

                // Look for label in optional vals
                for (var n = 0; n < this.field.optional_vals.length; n++)
                {
                    if (this.field.optional_vals[n][0] == this.value)
                        label = this.field.optional_vals[n][1];
                }

                var dv = alib.dom.createElement("div", this.dv_opt_con);
                dv.innerHTML = unescape(label+"&nbsp;");

                var alnk = alib.dom.createElement("a", dv);
                alnk.href = "javascript:void(0);";
                alnk.innerHTML = "[X]";
                alnk.m_id = this.value;
                alnk.m_label = unescape(label);
                alnk.m_div = dv;
                alnk.m_sel = this;
                alnk.m_fieldname = this.field.name;
                alnk.onclick = function()
                {
                    this.m_div.style.display='none';
                    this.m_sel[this.m_sel.length] = new Option(this.m_label, this.m_id, false, false);
                    this.m_sel.m_cls.delMultiValue(this.m_fieldname, this.m_id);
                }
            }
        }
        
		// Populate existing values
        for (var m = 0; m < vals.length; m++)
        {
            var id = vals[m];
			var label = id;
            //var label = valParts[m];
			var label = this.getValueName(field.name, id);
			if (label == "" || label==null)
				label = id;

            // Look for label in optional vals
            for (var n = 0; n < field.optional_vals.length; n++)
            {
                if (field.optional_vals[n][0] == id)
                    label = field.optional_vals[n][1];
            }

			// group div
            var dv = alib.dom.createElement("div", dv_opt_con);

			// label span
            var lblsp = alib.dom.createElement("span", dv);
            lblsp.innerHTML = label + " ";

			// Load remote if label not set
			if (id == label)
			{
				/*var ajax = new CAjax('json');
				ajax.cbData.lblsp = lblsp;
				ajax.onload = function(ret)
				{
					if(!ret)
						return;
						
					if (!ret['error'])
					{
						this.cbData.lblsp.innerHTML = ret['title'] + " ";
					}
				};

				var args = [["obj_type", this.obj_type], ["field", field.name], ["gid", id]];
				ajax.exec("/controller/Object/getGroupingById", args);*/

				var ajax = new CAjax('json');
				ajax.onload = function(ret) {
					if(!ret)
						return;

					for(var idx in ret.groups) {
						var group = ret.groups[idx];
						if(group.id == id) {
							this.cbData.lblsp.innerHTML = group.name + " ";
							break;
						}
					}
				}.bind(this);

				var args = [
					["obj_type", this.obj_type],
					["field_name", field.name]
				];
				ajax.exec("/svr/entity/getGroupings", args);
			}

            var alnk = alib.dom.createElement("a", dv);
            alnk.href = "javascript:void(0);";
            alnk.innerHTML = "[X]";
            alnk.m_id = id;
            alnk.m_label = label;
            alnk.m_div = dv;
            alnk.m_cls = this;
            alnk.m_fieldname = field.name;
            alnk.dynSelObject = inp_div.inpRef;
            alnk.onclick = function()
            {
                this.m_div.style.display='none';
                this.m_cls.delMultiValue(this.m_fieldname, this.m_id);
                
                for(mVar in this.dynSelObject.multiVars)
                {
                    var gId = this.dynSelObject.multiVars[mVar];
                    if(this.m_id == gId)
                        delete this.dynSelObject.multiVars[mVar];
                }
            }
        }
    }
    else
    {
        if (inp_div.inpRef)
        {
            inp_div.inpRef.cls = this;
            inp_div.inpRef.fieldName = fname;
            //this.setValue(fname, inp_div.inpRef.value);
            switch(inp_div.inptType)
            {
            case "checkbox":
                inp_div.inpRef.onclick = function() { this.cls.setValue(this.fieldName, this.checked); }
                break;
            case "text":
            case "input":
                //alib.dom.styleSet(inp_div.inpRef, "width", "90%");
                inp_div.inpRef.cls = this;
                inp_div.inpRef.onblur = function ()
                {
                    if(this.cls.obj_type == "file" && field.name == "name")
                        checkSpecialCharacters("file", this.value, this);
                }
            case "select":
                inp_div.inpRef.skiponchange = false; // Used to prevent endless loops in setting values
                inp_div.inpRef.onchange = function() 
                { 
                    if (this.skiponchange) return; 
                    
                    var val = this.value;

                    // If the field is a partial (like setting time for a full timestamp) then apply part to whole value
                    if (this.part)
                        val = this.cls.getInputPartFullValue(this.fieldName, val, this.part);

                    this.cls.setValue(this.fieldName, val); 
                }
                break;
            case "dynselect":
                inp_div.inpRef.onSelect = function() { this.cls.setValue(this.fieldName, this.value, this.valueName); }
                //inp_div.inpRef.setDefault(); // set value to first entry
                break;
            case "rte":
                inp_div.inpRef.onChange = function() { this.cls.setValue(this.fieldName, this.getValue()); }
                //inp_div.inpRef.setDefault(); // set value to first entry
                break;
            }
        }
    }
}

/*************************************************************************
*    Function:    save
*
*     Scope:        PUBLIC
*
*    Purpose:    Save values for this object. It does not save definition changes.
**************************************************************************/
CAntObject.prototype.save = function(opts)
{
    var options = (opts) ? opts : new Object();
    var requireFailMessage = false;
    var args = new Object();
    
    for (var i = 0; i < this.fields.length; i++)
    {
        var field = this.fields[i];

        if (field.type == "fkey_multi" || field.type == "object_multi")
        {

			var mvals = this.getMultiValues(field.name);

			if (mvals && mvals.length > 0)
			{
				// Set the multi value
				args[field.name] = mvals;
				args[field.name + "_fval"] = new Object();
				for (var m = 0; m < mvals.length; m++)
				{
					var mvalue =  mvals[m];
					args[field.name + "_fval"][mvalue] = this.getValueName(field.name, mvalue);
				}
			}
            else
            {
                // need to clear field multi, so if there's an existing value it will be completely removed
                args[field.name] = null;
            }
        }
        else if(field.name == "obj_type")
        {
            args["field:obj_type"] = this.getValue(field.name);
        }
        else
        {
            args[field.name] = this.getValue(field.name);
        }

        if (field.required && !this.getValue(field.name) && field.type != "fkey_multi" && field.type != "object_multi")
            requireFailMessage = "ERROR: " + field.title + " is a required field. Be sure to set it before saving changes.";
    }
    
    // Set recurrence
    if (this.recurrencePattern != null)
    {
        // set recur pattern variables in args array

        var obj = new Object();
    
        obj.recur_type = this.recurrencePattern.type;
        obj.obj_type = this.recurrencePattern.obj_type;
        obj.interval = this.recurrencePattern.interval;
        obj.date_start = this.recurrencePattern.dateStart;
        obj.date_end = this.recurrencePattern.dateEnd;
        obj.time_start = this.recurrencePattern.timeStart;
        obj.time_end = this.recurrencePattern.timeEnd;
        obj.day_of_month = this.recurrencePattern.dayOfMonth;
        obj.month_of_year = this.recurrencePattern.monthOfYear;
        obj.day_of_week_mask = this.recurrencePattern.dayOfWeekMask;
        obj.instance = this.recurrencePattern.instance;

        obj.date_processed_to = this.recurrencePattern.dateProcessedTo;
        obj.id = this.recurrencePattern.id;

		obj.day_of_week_mask = 0;
		var weekdays = {
			day1: 1,
			day2: 2,
			day3: 4,
			day4: 8,
			day5: 16,
			day6: 32,
			day7: 64
		}

		// Calculate the day of week mask
		for (var i = 1; i <= 7; i++) {
			if(this.recurrencePattern['day' + i] === "t") {
				obj.day_of_week_mask = obj.day_of_week_mask | weekdays['day' + i];
			}
		}

		/*obj.day1 = this.recurrencePattern.day1;
        obj.day2 = this.recurrencePattern.day2;
        obj.day3 = this.recurrencePattern.day3;
        obj.day4 = this.recurrencePattern.day4;
        obj.day5 = this.recurrencePattern.day5;
        obj.day6 = this.recurrencePattern.day6;
        obj.day7 = this.recurrencePattern.day7;*/

        args["recurrence_pattern"] = obj;
    }

    // A required field is blank
    if (requireFailMessage)
    { 
        this.onsaveError(requireFailMessage);
        return;
    }
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.cbData.options = options;
    ajax.onload = function(data)
    {
		var ret = 0;
        if (!ret['error'])
            ret = parseInt(data.id);

        if (ret > 0)
        {
            try
            {
                if (!this.cbData.cls.id)
                {
                    this.cbData.cls.id = data.id;
                    this.cbData.cls.onValueChange("id", data.id);
					alib.events.triggerEvent(this.cbData.cls, "fieldchange", {fieldName: "id", value:data.id, valueName:data.name || data.id});
                }
            }
            catch(e)
            {
                alert("CAntObject::save::error - " + e);
            }
        }
        else
        {
            if (ret == -2)
                this.cbData.cls.onsaveError("ERROR: You do not have sufficient permissions!");
            else if(ret == -3 )
                this.cbData.cls.onsaveError("ERROR: Unable to update Recurrence Pattern!");
            else 
                this.cbData.cls.onsaveError();
               
            return;                
        }
            
		// Clear dirty flag
		this.cbData.cls.dirty = false;

        if (!this.cbData.options.repressOnSave)
		{
            this.cbData.cls.onsave();
			alib.events.triggerEvent(this.cbData.cls, "save");
		}
    };
    
    // Make sure obj_type argument is set here so it will be overwritten by "obj_type" fields.
    args["obj_type"] = this.name;
    
    if (this.id)
        args["id"] = this.id;

	ajax.exec("/svr/entity/save", JSON.stringify(args));
}

CAntObject.prototype.onsave = function()
{
    // This function exists to be defined before save is called above
}

CAntObject.prototype.onsaveError = function()
{
    // This function exists to be defined before save is called above
}

/************************************************************************
*    Function:    remove
*
*     Scope:        PUBLIC
*
*    Purpose:    Delete object
**************************************************************************/
CAntObject.prototype.remove = function()
{
    if (!this.id)
    {
        this.onremoveError();
        return;
    }
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if (!ret['error'])
            this.cbData.cls.onremove();
        else
            this.cbData.cls.onremoveError();
    };    
    var args = [["obj_type", this.name], ["oid", this.id]];

	// Set recurrence
    if (this.recurrencePattern != null)
        args[args.length] = ['recurrence_save_type', this.recurrencePattern.save_type];   

    ajax.exec("/controller/Object/deleteObject", args);
}

CAntObject.prototype.onremove = function()
{
    // This function exists to be defined before save is called above
}

CAntObject.prototype.onremoveError = function()
{
    // This function exists to be defined before save is called above
}

//--------------------------------------------------------------------------
//    Object Values
//--------------------------------------------------------------------------

CAntObject.prototype.getValueByIdx = function(ind)
{
    var fMulti = (typeof this.create_obj_values[ind][1] == "object_multi") ? true : false;

    return { name:this.create_obj_values[ind][0], value:this.create_obj_values[ind][1], isMulti:fMulti };
}

/*************************************************************************
*    Function:    getInputPartFullValue
*
*    Purpose:    An input can set part of a value. Merge with full value.
*
*    Arguments:    name:string - the name of the field to set
*                value:string - the actual value to set (id if fkey)
*                part:string - the part to set
*
*    Return:        The full value after applying the part
**************************************************************************/
CAntObject.prototype.getInputPartFullValue = function(name, value, part)
{
    var field = this.getFieldByName(name);
    var ret = this.getValue(name);

    // If value has not yet been set
    if (!ret)
        ret = value;

    switch (field.type)
    {
    case 'timestamp':
        var ts = new Date(ret);

        if (part == "time")
        {
            var ret = (ts.getMonth()+1)+"/"+ts.getDate()+"/"+ts.getFullYear() + " " + value;
        }
        else if (part == "date")
        {
            var ret = value + " " + calGetClockTime(ts);
        }

        break;
    }

    return ret;
}

/*************************************************************************
*    Function:    getInputPartValue
*
*    Purpose:    An input can set part of a value. Extract part from full value.
*
*    Arguments:    name:string - the name of the field to set
*                value:string - the actual value to set (id if fkey)
*                part:string - the part to set
*
*    Return:        Part of the full value. Such as the time from a timestamp
**************************************************************************/
CAntObject.prototype.getInputPartValue = function(name, value, part)
{
    var field = this.getFieldByName(name);
    var ret = value;

	if (!ret)
		return "";

    switch (field.type)
    {
    case 'timestamp':
        var ts = new Date(ret);

        if (part == "time")
        {
            ret = calGetClockTime(ts);
        }
        else if (part == "date")
        {
            ret = (ts.getMonth()+1)+"/"+ts.getDate()+"/"+ts.getFullYear();
        }

        break;
    }

    return ret;
}

/*************************************************************************
*    Function:    setValue
*
*    Purpose:    Set the value of a field
*
*    Arguments:    name:string - the name of the field to set
*                value:string - the actual value to set (id if fkey)
*                valueName:string - the label for an fkey value
**************************************************************************/
CAntObject.prototype.setValue = function(name, value, valueName)
{
    if(typeof name == "undefined")
        return;

	var valueName = valueName || null;

    var field = this.getFieldByName(name);
	if (!field)
		return;

	// Check if this is a multi-field
	if (field.type == "fkey_multi" || field.type == "object_multi")
	{
		if (value instanceof Array)
		{
			for (var j in value)
				this.setMultiValue(name, value[j]);
		}
		else
		{
			this.setMultiValue(name, value, valueName);
		}

		return true;
	}

	// Handle bool conversion
	if (field.type == "bool")
	{
		switch (value)
		{
		case 1:
		case 't':
			case true:
			value = true;
			break;
		case 0:
		case 'f':
			case false:
			value = false;
			break;
		}
	}
    
    // Associated object fields cannot be updated
    if (name.indexOf(".")!=-1)
    {
        return;
    }

    this.dirty = true;

    for (var i = 0; i < this.values.length; i++)
    {
        if (this.values[i][0] == name)
        {
            this.values[i][1] = value;
            if (valueName)
                this.values[i][2] = valueName; // Label for foreign keys

            this.onValueChange(name, value, valueName);
			alib.events.triggerEvent(this, "fieldchange", {fieldName: name, value:value, valueName:valueName});
            return;
        }
    }

    if (!valueName && field && field.type!='fkey' && field.type!='fkey_multi' 
			&& field.type!='object' && field.type!='object_multi' && field.optional_vals)
    {
        for (var i = 0; i < field.optional_vals.length; i++)
        {
            if (field.optional_vals[i][0] == value)
                valueName = field.optional_vals[i][1];
        }
    }

    var ind = this.values.length;
    this.values[ind] = new Array();
    this.values[ind][0] = name;
    this.values[ind][1] = value;
    if (valueName)
        this.values[ind][2] = valueName; // Label for foreign keys
    else
        this.values[ind][2] = null;

    this.onValueChange(name, value, valueName);
	alib.events.triggerEvent(this, "fieldchange", {fieldName: name, value:value, valueName:valueName});
}

/*************************************************************************
*    Function:    getValue
*
*    Purpose:    Get the actual value of an object
**************************************************************************/
CAntObject.prototype.getValue = function(name, debug)
{
    var val = "";

    if (!name)
        return val;

    for (var i = 0; i < this.values.length; i++)
    {
        if (this.values[i][0] == name)
        {
            val = this.values[i][1];
            break;
        }
    }

    var field = this.getFieldByName(name);

    // Check alias
    /*
    if (field && field.type == "alias")
    {
        val = this.getValue(val); // Get aliased value
    }
    */

    // Check optional values for first value
    try
    {
        if (val=="" && field.optional_vals)
        {
            if (field.optional_vals.length)
                val = field.optional_vals[0][0];
        }
    }
    catch(e) {  }
    
    return val;
}

/*************************************************************************
*    Function:    getValueName
*
*    Purpose:    If exists, get the value name (label) of a referenced field
*                Typically, get name from id in an fkey
**************************************************************************/
CAntObject.prototype.getValueName = function(name, val)
{
    var field = this.getFieldByName(name);
    if (field && field.type == "alias")
    {
        if (!val)
            var val = this.getValue(name);
        return this.getValue(val); // Get aliased value
    }

    if (field.type == "object" || field.type == "fkey" || field.type == "object_multi" || field.type == "fkey_multi")
    {
        for (var i = 0; i < this.values.length; i++)
        {
            if (this.values[i][0] == name)
            {
                if (val) // multival
                {
                    for (var m = 0; m < this.values[i][1].length; m++)
                    {
                        if (this.values[i][1][m] == val && this.values[i][2])
                            return this.values[i][2][m];
                    }
                }
                else
                {
                    if (this.values[i][2]!=null && this.values[i][2]!="null")
                        return this.values[i][2];
                }
            }
        }
    }
	else if (field.optional_vals.length)
	{
		for (var i = 0 ; i < field.optional_vals.length; i++)
		{
			if (field.optional_vals[i][0] == this.getValue(name))
			{
				return field.optional_vals[i][1];
			}
		}
	}
    else
    {
        return this.getValue(name);
    }

    // Still not found, query server
    /*
    if (field.type == "object" || field.subtype)
    {
        var val = this.getValue(name);
        if (val)
        {
        }
    }
    */
    
    return "";
}

/*************************************************************************
*    Function:    getValueStr
*
*    Purpose:    If a foreign reference, then return name, otherwise
*                return the value of the field.
**************************************************************************/
CAntObject.prototype.getValueStr = function(name)
{
    var val = this.getValueName(name);
    if (!val)
        val = this.getValue(name);
    
    return val;
}

/*************************************************************************
*    Function:    setMultiValue
*
*    Purpose:    Set a value in a multi-value array
*
*    Arguments:    name:string - the name of the field to set
*                value:string - the actual value to set (id if fkey)
*                valueName:string - the label for an fkey value
**************************************************************************/
CAntObject.prototype.setMultiValue = function(name, value, valueName)
{
	var valueName = valueName || null;

    this.onValueChange(name, value);
	alib.events.triggerEvent(this, "fieldchange", {fieldName: name, value:value, valueName:valueName});

    this.dirty = true;    
    
    // check if valueName has value, if it has value, it means, its an existing data and not a new entry
	/*
	 * NOTE: This was creating an odd bug, there is no reason to delete the optionial val from the actual
	 * field definition
    if(typeof valueName != "undefined")
    {
        var field = this.getFieldByName(name);    
        for (var i = 0; i < field.optional_vals.length; i++)
        {
            // if value is found in optional_vals, we need to remove it from the dropdown
            if (field.optional_vals[i][0] == value)
                field.optional_vals.splice(i, 1);
        }
    }
	*/
    
    // Update value
    for (var i = 0; i < this.values.length; i++)
    {
        // we need to break the loop if its in New Window and has posted values
        if(!this.id && this.newWindowArgs)
            break;            
            
        if (this.values[i][0] == name)
        {
            var bFound = false;
            for (var m = 0; m < this.values[i][1].length; m++)
            {
                if (this.values[i][1][m] == value)
                    bFound == true;
            }

            if (!bFound)
            {                
                var ind = this.values[i][1].length;
                this.values[i][1][ind] = value;                    
                this.values[i][2][ind] = (valueName) ? valueName : null; // Label for foreign keys
            }

            return;
        }
    }

    // New value
    var ind = this.values.length;
    this.values[ind] = new Array();
    this.values[ind][0] = name;
    this.values[ind][1] = new Array();
    // we need to use valueName if its new IC window form
    if(!this.id && this.newWindowArgs)
    {
        this.values[ind][1][0] = valueName;
    }        
    else
        this.values[ind][1][0] = value;
    this.values[ind][2] = new Array();  // Label for foreign keys
    this.values[ind][2][0] = (valueName) ? valueName : null;
}

/*************************************************************************
*    Function:    getMultiValueExists
*
*    Purpose:    Find out if a multi-value.value is alraedy in the array
**************************************************************************/
CAntObject.prototype.getMultiValueExists = function(name, value)
{
    // Check if value is already set
    for (var i = 0; i < this.values.length; i++)
    {
        if (this.values[i][0] == name)
        {
            for (var m = 0; m < this.values[i][1].length; m++)
            {
                if (this.values[i][1][m] == value)
                    return true;
            }
        }
    }

    // Does not exist
    return false;
}

/*************************************************************************
*    Function:    getMultiValues
*
*    Purpose:    Get array of multi-values
**************************************************************************/
CAntObject.prototype.getMultiValues = function(name)
{
    var ret = new Array();
    // Check if value is already set
    for (var i = 0; i < this.values.length; i++)
    {
        if (this.values[i][0] == name)
        {
            for (var m = 0; m < this.values[i][1].length; m++)
            {
                ret[ret.length] = this.values[i][1][m];
            }
        }
    }

    // Does not exist
    return ret;
}

/*************************************************************************
*    Function:    getMultiValueStr
*
*    Purpose:    Get a label for a multi-value array
**************************************************************************/
CAntObject.prototype.getMultiValueStr = function(name)
{
    var ret = "";
    // Check if value is already set
    for (var i = 0; i < this.values.length; i++)
    {
        if (this.values[i][0] == name)
        {
			if (typeof this.values[i][2] != "undefined" && this.values[i][2] != null)
			{
				for (var m = 0; m < this.values[i][2].length; m++)
				{
					if (ret) ret += "; ";
					if (typeof this.values[i][2][m] != "undefined" && this.values[i][2][m] != null)
						ret += this.values[i][2][m];
				}
			}
        }
    }

    return ret;
}

/*************************************************************************
*    Function:    delMultiValue
*
*    Purpose:    Delete an element from a multi-value array
**************************************************************************/
CAntObject.prototype.delMultiValue = function(name, value)
{
    this.dirty = true;

    // Delete value
    for (var i = 0; i < this.values.length; i++)
    {
        if (this.values[i][0] == name)
        {
            for (var m = 0; m < this.values[i][1].length; m++)
            {
                if (this.values[i][1][m] == value)
                {
                    this.values[i][1].splice(m, 1);
                }
            }
        }
    }
}

/*************************************************************************
*    Function:    delMultiValues
*
*    Purpose:    Delete an entire multi-value array
**************************************************************************/
CAntObject.prototype.delMultiValues = function(name)
{
    // Delete all values
    for (var i = 0; i < this.values.length; i++)
    {
        if (this.values[i][0] == name)
        {
            this.values[i][1] = new Array();
        }
    }
}

// Over-ride this function to track changes
CAntObject.prototype.onValueChange = function(name, value, valueName)
{
}

/**
 * Get the object type name of this object
 *
 * @public
 * @param {int} id If set then load id, otherwise try to get this.id
 */
CAntObject.prototype.getObjType = function()
{
	return this.obj_type;
}

//--------------------------------------------------------------------------
//    Object Definition
//--------------------------------------------------------------------------

/**
 * @depriacted
 * joe: We now use Ant.EntityDefinition to manage definitions
CAntObject.prototype.addField = function(field)
{
    this.addfields[this.addfields.length] = field;

    // make sure not in remove queue
    for (var i = 0; i < this.removefields.length; i++)
    {
        if (this.removefields[i] == field.name)
            this.removefields.splice(i, 1);
    }
}

CAntObject.prototype.removeField = function(fname)
{
    this.removefields[this.removefields.length] = fname;

    // make sure not in add queue
    for (var i = 0; i < this.addfields.length; i++)
    {
        if (this.addfields[i].name == fname)
            this.addfields.splice(i, 1);
    }
}

var g_CAntObject_SaveDef = 0;
CAntObject.prototype.saveDefinition = function()
{
    var totalToProcess = this.addfields.length + this.removefields.length + 1; // add 1 for general
    g_CAntObject_SaveDef = 0;
    
	if (this.addfields.length > 0)
    {
        var field = this.addfields[0];

		var ajax = new CAjax("xml");
		ajax.cbData.cls = this;
		ajax.onload = function(ret)
		{
			console.log("added field");
			// Recurrsively call until finished
			this.cbData.cls.saveDefinition();
		};
        
        var args = [["obj_type", this.name], ["name", field.name], ["title", field.title], ["type", field.type], ["subtype", field.subtype],
                    ["fkey_table_key", field.fkey_table_key], ["fkey_multi_tbl", field.fkey_multi_tbl], ["fkey_multi_this", field.fkey_multi_this], 
                    ["fkey_multi_ref", field.fkey_multi_ref], ["fkey_table_title", field.fkey_table_title], ["notes", field.notes],
                    ["required", (field.required)?'t':'f']];

        args[args.length] = ["function", "save_field"];
        ajax.exec("/admin/xml_objectdef_actions.php", args, false);

		// Remove from the addfields queue
		this.addfields.splice(0, 1);
		return; // onload continues processing
    }
	
    if (this.removefields.length > 0)
    {
		var ajax = new CAjax("xml");
		ajax.cbData.cls = this;
		ajax.onload = function(ret)
		{
			console.log("deleted field");
			// Recurrsively call until finished
			this.cbData.cls.saveDefinition();
		};

        var args = [["obj_type", this.name], ["name", this.removefields[0]]];

        args[args.length] = ["function", "delete_field"];
        ajax.exec("/admin/xml_objectdef_actions.php", args, false);

		this.removefields.splice(0, 1);
		return; // onload continues processing
    }

    // Save general
	var ajax = new CAjax("xml");
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
		if (ret)
			this.cbData.cls.onsavedefinition();
		else
			this.cbData.onsavedefinitionError();
	
    };
    var args = [["obj_type", this.name], ["title", this.title], ["form_layout_xml", this.xmlFormLayoutText]];
    var strOrder = "";
    for (var i = 0; i < this.propertyFormOrder.length; i++)
    {
        if (strOrder!="") 
            strOrder += ":";

        strOrder += this.propertyFormOrder[i];
    }

    for (var i = 0; i < this.teamFormLayouts.length; i++)
    {
        args[args.length] = ["xml_team_form_layouts[]", this.teamFormLayouts[i].team_id];
        args[args.length] = ["xml_team_form_layouts_"+this.teamFormLayouts[i].team_id, this.teamFormLayouts[i].xml];
    }
    //ALib.m_debug = true;
    //AJAX_TRACE_RESPONSE = true;
    args[args.length] = ["field_form_order", strOrder];
    args[args.length] = ["function", "save_general"];    
	ajax.debug = true;
    ajax.exec("/admin/xml_objectdef_actions.php", args, false);
	alert(args);
}

// Over ride the below function
CAntObject.prototype.onsavedefinition = function()
{
}

// Over ride the below function
CAntObject.prototype.onsavedefinitionError = function()
{
}
*/

//--------------------------------------------------------------------------
//    Object Views
//--------------------------------------------------------------------------
CAntObject.prototype.getDefaultView = function(filter_key)
{
    var filterKey = (filter_key) ? filter_key : "";

    for (var i = 0; i < this.views.length; i++)
    {
        if (this.views[i].fDefault && filterKey==this.views[i].filterKey)
            return this.views[i];
    }

    // No default found
    if (this.views.length)
        return this.views[0];
}

CAntObject.prototype.defaultViewExists = function(filter_key)
{
    var filterKey = (filter_key) ? filter_key : "";

    for (var i = 0; i < this.views.length; i++)
    {
        if (this.views[i].fDefault && filterKey==this.views[i].filterKey)
            return true;
    }

    return false;
}

CAntObject.prototype.getViewById = function(id)
{
    for (var i = 0; i < this.views.length; i++)
    {
        if (this.views[i].id == id)
            return this.views[i];
    }

    return null;
}

/**
 * Set whether an object has been viewed
 *
 * @public
 */
CAntObject.prototype.setViewed = function()
{
	if (!this.id)
		return;

    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
    };    
    var args = [["obj_type", this.name], ["oid", this.id]];
    ajax.exec("/controller/Object/setViewed", args);
}

/**
 * Get icon for this object
 *
 * @param int width
 * @param int height
 */
CAntObject.prototype.getIcon = function(width, height)
{
	if (this.getValue("image_id"))
	{
		var path = "/antfs/images/" + this.getValue("image_id");
		if (width || height)
		{
			path += "/";
			path += (width) ? width : "0"; // null value if we are setting only height
		}
		if (height)
			path += "/" + height;
		return path;
	}
	else if (this.iconName)
		return "/images/icons/objects/" + this.iconName+ "_" + width + ".png";
}

/**
 * Find out if the object was edited
 *
 * @return {bool} True if the object has been edited since last save
 */
CAntObject.prototype.isDirty = function()
{
	return this.dirty;
}

/*************************************************************************************
*    Description:    setFilter    
*
*    Purpose:        Add a filter for querying data
**************************************************************************************/
CAntObject.prototype.setFilter = function(field, val)
{
    var ind = this.m_filters.length;
    this.m_filters[ind] = new Object();
    this.m_filters[ind].fieldName = field;
    this.m_filters[ind].value = val;
}

CAntObject.prototype.clearConditions = function() 
{
    return this.condition.clearConditions();
}

// This function will be called by form.js to trigger the edit mode type
CAntObject.prototype.toggleEdit = function(setmode)
{
    this.onToggleEdit(setmode);
}

// Over-ride this function to change the display for edit mode
CAntObject.prototype.onToggleEdit = function(setmode)
{
}

/**
 * DEPRICATED - The loading of recurrence pattern is already included in this.load()
 *
 * Load recurrence pattern for this object
 */
CAntObject.prototype.loadRecurrencePattern = function(id)
{
	this.getRecurrencePattern(true, id);
}

/**
 * get recurrence object
 *
 * @param {bool} create Flag that will determine if we will create a recurrence pattern
 * @param {object} recurrencePatternData Contains the data of recurrence pattern that will be used to create a recurrence
 */
CAntObject.prototype.getRecurrencePattern = function(create, recurrencePatternData)
{
	if (this.recurRules==null) // recurrence is not supported for this object type
		return null;

	if(!create) // get pattern object if exists
	{
		if(null == this.recurrencePattern)
			return null;
		this.recurrencePattern.object_type = this.name;
		this.recurrencePattern.object_type_id = this.object_type_id;
		this.recurrencePattern.parentId = this.id;
	}
	else if (this.recurrencePattern)  // return existing pattern
	{
		return this.recurrencePattern;
	}
	else
	{
		// Create a default recurrence pattern
		this.recurrencePattern = new CRecurrencePattern();
		this.recurrencePattern.object_type = this.name;
		this.recurrencePattern.object_type_id = this.object_type_id;
		this.recurrencePattern.parentId = this.id;

		/*
		 if (typeof rpid == 'undefined')
		 var rpid = null;
		 this.recurrencePattern = new CRecurrencePattern();
		 this.recurrencePattern.object_type = this.name;
		 this.recurrencePattern.object_type_id = this.object_type_id;
		 this.recurrencePattern.parentId = this.id;
		 this.recurrencePattern.fieldDateStart = this.recurRules.fieldDateStart;
		 this.recurrencePattern.fieldTimeStart = this.recurRules.fieldTimeStart;
		 this.recurrencePattern.fieldDateEnd = this.recurRules.fieldDateEnd;
		 this.recurrencePattern.fieldTimeEnd = this.recurRules.fieldTimeEnd;
		 this.recurrencePattern.fieldRecurId = this.recurRules.fieldRecurId;
		 if (rpid)
		 this.recurrencePattern.load(rpid);
		 */
	}

	// Check if there is a recurrence pattern data provided
	if(recurrencePatternData)
		this.recurrencePattern.fromArray(recurrencePatternData);

	// Set the rules
	this.recurrencePattern.setRecurrenceRules(this.recurRules);
	return this.recurrencePattern;
}
