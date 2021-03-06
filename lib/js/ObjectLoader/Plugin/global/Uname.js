/**
 * @fileoverview This class is a global object form plugin for managing the uname
 *
 * @author     Marl Tumulak, marl.tumulak@aereus.com.
 *             Copyright (c) 2012 Aereus Corporation. All rights reserved.
 */
 
 /**
 * Class constructor
 */
function AntObjectLoader_Uname()
{
    this.data = new Object();
    this.uname = null;
    this.editMode = false;
    
    this.name = "uname";  // should be the same, when calling the plugin
    this.title = "Uname";
    this.mainObject = null;
    
    this.saveParentObject = false; // Flag used when saving. If set to true, then parent object will need to be saved when all done

    // Containers
    this.mainCon = null;
    this.ajax = new CAjax('json'); // This will be used to check the uname as the user types. This will enable us to abort the previous ajax request
    this.ajax.cbData.cls = this;
}

/**
 * Required plugin main function
 */
AntObjectLoader_Uname.prototype.main = function(con)
{
    this.mainCon = con;
    
    if(this.mainObject.id)
    {
        this.editMode = false;
        this.getUname();
    }        
}

/**
 * Print form 
 */
AntObjectLoader_Uname.prototype.buildInterface = function()
{
    this.mainCon.innerHTML = "";
    var unameCon = alib.dom.createElement("div", this.mainCon);
    
    this.data.label = alib.dom.setElementAttr(alib.dom.createElement("span", unameCon), [["innerHTML", "URI: "]]);
    this.data.tooltip = alib.dom.setElementAttr(alib.dom.createElement("img", unameCon), [["src", "/images/icons/help_12.png"]]);
    this.data.input = alib.dom.setElementAttr(alib.dom.createElement("input", unameCon), [["value", this.uname]]);
    this.data.display = alib.dom.setElementAttr(alib.dom.createElement("span", unameCon), [["innerHTML", this.uname]]);
    this.data.status = alib.dom.setElementAttr(alib.dom.createElement("span", unameCon));
    
    // Set the classes
    alib.dom.styleSetClass(this.data.label, "formLabel");
    alib.dom.styleSetClass(this.data.display, "formValue");
    
    // Set inline style
    alib.dom.styleSet(this.data.status, "margin-left", "10px");
    alib.dom.styleSet(this.data.display, "padding-bottom", "0px");
    alib.dom.styleSet(this.data.input, "width", "250px");
    alib.dom.styleSet(this.data.tooltip, "margin-right", "10px");
    alib.dom.styleSet(this.data.tooltip, "cursor", "help"); 
    
    alib.ui.Tooltip(this.data.tooltip, "Each object has a unique name in addition to the unique id. This name is often used in the API to load objects by human readable names rather than by an id. This will be automatically generated by default but you can also manually edit it here.");
    
    // Set input events
    this.data.input.cls = this;
    this.data.input.onchange = function()
    {
        this.cls.checkUname(true);
    }
    
    this.data.input.onkeyup = function()
    {
        this.cls.checkUname(false);
    }
    
    // Trigger the toggle edit
    this.onMainObjectToggleEdit();
}

/**
 * Called from object loader when object is saved.
 *
 * This should take care of saving attached file
 */
AntObjectLoader_Uname.prototype.save = function()
{
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        this.cbData.cls.onsave();
        if(!ret)
        {
            ALib.statusShowAlert("Error occurred while saving uname!", 3000, "bottom", "right");
            return;
        }
        
        if(ret.error)
        {
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");            
            this.cbData.cls.data.status.innerHTML = "";
        }
        
        if(ret.currentName)
        {
            this.cbData.cls.data.input.value = ret.currentName;
            this.cbData.cls.uname = ret.currentName;
        }
    };
    
    var args = new Array();
    
    if(this.data.input)
    {
        args[args.length] = ['objId', this.mainObject.id];
        args[args.length] = ['objType', this.mainObject.obj_type];
        args[args.length] = ['uniqueName', this.data.input.value];
        args[args.length] = ['currentName', this.uname];

		// Pass object current values for namespaces
		var fields = this.mainObject.getFields();
		for (var i in fields)
		{
			if (fields[i].name != "uname" && fields[i].name != "id" && fields[i].type != "fkey_multi" && fields[i].type != "object_multi")
				args[args.length] = [fields[i].name, this.mainObject.getValue(fields[i].name)];
		}

        ajax.exec("/controller/Object/saveUniqueName", args);
    }
    else
        this.onsave();
}

/**
 * onsave callback - should be overridden by parent form
 */
AntObjectLoader_Uname.prototype.onsave = function()
{
}

/**
 * onsave callback - should be overridden by parent form
 */
AntObjectLoader_Uname.prototype.getUname = function()
{
    var args = new Array();    
    args[args.length] = ['objId', this.mainObject.id];
    args[args.length] = ['objType', this.mainObject.obj_type];
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if(ret)
        {
            if(ret.error)
                ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
            else
                this.cbData.cls.uname = ret.uniqueName;
        }
            
        this.cbData.cls.buildInterface();
    };

	// Pass object current values for namespaces
	var fields = this.mainObject.getFields();
	for (var i in fields)
	{
		if (fields[i].name != "uname" && fields[i].name != "id" && fields[i].type != "fkey_multi" && fields[i].type != "object_multi")
    		args[args.length] = [fields[i].name, this.mainObject.getValue(fields[i].name)];
	}

    ajax.exec("/controller/Object/getUniqueName", args);
}

 /**
  * onToggleEdit callback - should be overridden by parent form
  *
  * @public
  * @this {class}
  * @param {boolean} setmode        Determines whether the form is in edit mode or not
  */
AntObjectLoader_Uname.prototype.onMainObjectToggleEdit = function(setmode)
{
    if(!this.data)
        return;
        
    if(typeof setmode == "undefined")
        setmode = this.editMode;
        
    if(setmode)
    {
        alib.dom.styleSet(this.data.input, "display", "inline-block");
        alib.dom.styleSet(this.data.display, "display", "none");
    }
    else
    {
        alib.dom.styleSet(this.data.input, "display", "none");
        alib.dom.styleSet(this.data.display, "display", "inline-block");
    }
}

/**
  * Checks the uname
  *
  * @public
  * @this {class}
  * @param {boolean} onBlur     Determine wheter the function is triggered on blur or not
  */
AntObjectLoader_Uname.prototype.checkUname = function(onBlur)
{
    this.ajax.abort(); // Abort any existing ajax request
    this.ajax.onload = function(ret)
    {
        if(ret['error'])
            ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
        
        if(onBlur && ret['value'] == -1)
        {
            this.cbData.cls.data.input.value = this.cbData.cls.uname;
            this.cbData.cls.data.status.innerHTML = "";
        }
        else
            this.cbData.cls.data.status.innerHTML = ret['message'];
            
    };
    
    var args = new Array();    
    args[args.length] = ['objId', this.mainObject.id];
    args[args.length] = ['objType', this.mainObject.obj_type];
    args[args.length] = ['uniqueName', this.data.input.value];

	// Pass object current values for namespaces
	var fields = this.mainObject.getFields();
	for (var i in fields)
	{
		if (fields[i].type == "fkey" || fields[i].type == "object")
    		args[args.length] = [fields[i].name, this.mainObject.getValue(fields[i].name, true)];
	}

    this.ajax.exec("/controller/Object/verifyUniqueName", args);
}
