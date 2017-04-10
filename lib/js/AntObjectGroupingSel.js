/**
 * @fileOverview AntObjectGroupingSel:  Advanced dynamic combo-box/
 */


/**
 * Class constructor
 *
 * @constructor
 */
function AntObjectGroupingSel(label, obj_type, field, value, objref, options)
{
	if (obj_type && field)
	{
		this.mainObjectType = obj_type;
		this.mainObjectField = field;
	}
	else if (obj_type)
	{
		this.mainObjectType = obj_type;
		this.mainObjectField = "";
	}
	else
	{
		return null;
	}
    
    this.mainObject = (objref) ? objref : null;
    this.m_lbl = alib.dom.createElement("span");
    this.m_input = alib.dom.createElement("input");
    this.m_input.type = "hidden";
    this.m_filters = new Array();
    this.value = (value) ? value : "";
    this.opts = (options) ? options : new Object(); 
    this.fieldType = null;
    this.multiVars = new Array();
	this.cbData = new Object();
    
    if(this.mainObject)
    {
        var fieldObj = this.mainObject.getFieldByName(field);
        
        this.fieldType = fieldObj.type;
        if(this.fieldType == "fkey_multi" && obj_type !== "email_thread")
        {
            this.multiVars = this.mainObject.getMultiValues(field);
            label = "Select";
        }
    }
	
    this.m_lbl.innerHTML = (label) ? label : "Select"; //  &#9660;
	if (value && this.fieldType !== "fkey_multi")
	{
		if (field)
			this.getForeignValueLabel(value);
		else
			this.getObjectName(value);
	}
}

/**
* print
*
* @param {object} con The container that will contain the browser
* @param {string} className Optional class to pass to the button
*/
AntObjectGroupingSel.prototype.print = function(con, className)
{
	var clsName = (className) ? className : null;

	if (this.mainObjectType && this.mainObjectField)
	{
		this.printGrouping(con, clsName);
	}
	else
	{
		this.printObject(con);
	}
}

/**
* Set the label text (button) of the selector
*
* @public
* @param {string} txt The text to set the label to
*/
AntObjectGroupingSel.prototype.setLabel = function(txt)
{
	this.m_lbl.innerHTML = txt + " &#9660;";
}

/**
* print
*
* @param {object} con The container that will contain the browser
*/
AntObjectGroupingSel.prototype.printObject = function(con)
{
	switch(this.mainObjectType)
	{
	case 'user':
		var selusr = function(cls)
		{
			var cbrowser = new CUserBrowser();
			cbrowser.dynselcls = cls;
			cbrowser.onSelect = function(cid, name) 
			{
				this.dynselcls.onSelect(cid, name);
				this.dynselcls.setLabel(name);
			}
			cbrowser.showDialog();
		}
		var btn = new CButton(this.m_lbl, selusr, [this]);
		btn.print(con);
		break;
	}

}

/**
* @depricated
*
* @param {object} con The container that will contain the browser
* @param {string} className Optional class to pass to the button
*/
AntObjectGroupingSel.prototype.printTable = function(con, className)
{
	var clsName = (className) ? className : null;

	var dmcon = new CDropdownMenu();
	var dcon = dmcon.addCon();
	dcon.onclick = function() 
    {
        //this.menuref.unloadMe();
    }
	var in_con = alib.dom.createElement("div", dcon);
	alib.dom.styleSet(in_con, "padding-left", "5px");
	alib.dom.styleSet(in_con, "width", "180px");
	alib.dom.styleSet(in_con, "max-height", "300px");
	alib.dom.styleSet(in_con, "overflow", "auto");
	var funct = function(in_con, cls, dropdownCon)
	{
		cls.loadTable(in_con, null, dropdownCon);
	}
	con.appendChild(dmcon.createButtonMenu(this.m_lbl, funct, [in_con, this, dcon], clsName));
}

/**
 * Print filtered menu dropdown
 *
 * @param {object} con The container that will contain the browser
 * @param {string} className Optional class to pass to the button
 */
AntObjectGroupingSel.prototype.printGrouping = function(con, className)
{
	var clsName = (className) ? className : "b1";

	var menu = new alib.ui.FilteredMenu();
	alib.events.listen(menu, "onShow", function(evt) {
		evt.data.cls.loadGroupingItems(evt.data.menu);
	}, {cls:this, menu:menu});
	var btn = new alib.ui.MenuButton(this.m_lbl, menu, {className:clsName});
	btn.print(con);
}

/**
* To be overloaded 
*/
AntObjectGroupingSel.prototype.onSelect = function(id, title)
{
}

/**
* To be overloaded 
*/
AntObjectGroupingSel.prototype.onchange = function()
{
}

/**
 * @depricated No just use select
* Select function used when a table is dynamically loaded
*/
AntObjectGroupingSel.prototype.tableSelect = function(id, title)
{
	if (!this.opts.staticLabel && this.fieldType !== "fkey_multi")
		this.m_lbl.innerHTML = title;
        
	this.value = id;
	this.valueName = title;
	this.m_input.value = id;
	this.onSelect(id, title);
	this.onchange();
}

/**
 * Select a grouping id
 *
 * @param {int} id The id of selected grouping
 * @param {string} title The title or label of the grouping
 */
AntObjectGroupingSel.prototype.select = function(id, title)
{
	if (!this.opts.staticLabel && this.fieldType !== "fkey_multi")
		this.m_lbl.innerHTML = title;
        
	this.value = id;
	this.valueName = title;
	this.m_input.value = id;
	this.onSelect(id, title);
	this.onchange();
}

/**
 * Load grouping entries into the menu
 *
 * @param {alib.ui.Menu} menu
 */
AntObjectGroupingSel.prototype.loadGroupingItems = function(menu)
{	

    var args = new Array();

    for (var i = 0; i < this.m_filters.length; i++)
    {
        var cond = this.m_filters[i];
        args[args.length] = [cond.fieldName, cond.value];
    }
    
    if (this.opts.filter)
    {
        var cond_cnt = 1;
        args[args.length] = ["conditions[]", cond_cnt];
        args[args.length] = ["condition_blogic_"+cond_cnt, "and"];
        args[args.length] = ["condition_fieldname_"+cond_cnt, this.opts.filter[0]];
        args[args.length] = ["condition_operator_"+cond_cnt, "is_equal"];
        args[args.length] = ["condition_condvalue_"+cond_cnt, this.opts.filter[1]];
    }
    
    if (this.mainObject)
    {
        var fields = this.mainObject.getFields();
        for (var i = 0; i < fields.length; i++)
            args[args.length] = [fields[i].name, this.mainObject.getValue(fields[i].name)];
    }

    if (this.mainObjectType)
        args[args.length] = ["obj_type", this.mainObjectType];
    if (this.mainObjectField)
        args[args.length] = ["field", this.mainObjectField];
	/*
    if (offset)
        args[args.length] = ["offset", offset];
		*/
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;	
    ajax.cbData.menu = menu;
	ajax.onload = function(ret)
	{
        if(!ret)
        {
            return;
        }

        
        if(ret.error)
        {
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
            return;
        }

		//this.cbData.menu.clear();
        
        // Null Entry
        var nullEntry = new Array();            
        nullEntry[0] = new Object();
        nullEntry[0].id = "";
        nullEntry[0].title = "None / Null";
        nullEntry[0].viewname = "None / Null";
        
		if (!this.cbData.cls.opts.noNull && this.cbData.cls.fieldType !== "fkey_multi")
            this.cbData.cls.populateItems(nullEntry, this.cbData.menu);
        
        this.cbData.cls.populateItems(ret, this.cbData.menu);
	};

	ajax.exec("/controller/Object/getGroupings", args);
}

/**
 * @depricated Using load menu items now
* Load foriegn table values
*/
AntObjectGroupingSel.prototype.loadTable = function(con, offset, dropdownCon)
{	
	con.innerHTML = " <div class='loading'></div>";
    
    var args = new Array();

    for (var i = 0; i < this.m_filters.length; i++)
    {
        var cond = this.m_filters[i];
        args[args.length] = [cond.fieldName, cond.value];
    }
    
    if (this.opts.filter)
    {
        var cond_cnt = 1;
        args[args.length] = ["conditions[]", cond_cnt];
        args[args.length] = ["condition_blogic_"+cond_cnt, "and"];
        args[args.length] = ["condition_fieldname_"+cond_cnt, this.opts.filter[0]];
        args[args.length] = ["condition_operator_"+cond_cnt, "is_equal"];
        args[args.length] = ["condition_condvalue_"+cond_cnt, this.opts.filter[1]];
    }
    
    if (this.mainObject)
    {
        var fields = this.mainObject.getFields();
        for (var i = 0; i < fields.length; i++)
            args[args.length] = [fields[i].name, this.mainObject.getValue(fields[i].name)];
    }

    if (this.mainObjectType)
        args[args.length] = ["obj_type", this.mainObjectType];
    if (this.mainObjectField)
        args[args.length] = ["field", this.mainObjectField];
    if (offset)
        args[args.length] = ["offset", offset];
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;	
    ajax.cbData.con = con;
	ajax.cbData.dropdownCon = dropdownCon;
	ajax.onload = function(ret)
	{
        if(!ret)
        {
            this.cbData.con.innerHTML = " <div style='padding:3px;'>None</div>";
            return;
        }
        
        if(ret.error)
        {
            ALib.statusShowAlert(ret.error, 3000, "bottom", "right");
            return;
        }
        
		this.cbData.con.fLoaded = true;
		this.cbData.con.innerHTML = "";
        
        // Null Entry
        var nullEntry = new Array();            
        nullEntry[0] = new Object();
        nullEntry[0].id = "";
        nullEntry[0].title = "None / Null";
        nullEntry[0].viewname = "None / Null";
        
        // Containers
        var divSearchCon = alib.dom.createElement("div", this.cbData.con);
        var divValueCon = alib.dom.createElement("div", this.cbData.con);
        
        // count how many childs
        var childCount = 0;
        for(group in ret)
        {
            if(ret[group].children && ret[group].children.length)
                childCount += ret[group].children.length;
        }
        
        // Remove text search here if data is less then 10
        if((ret.length + childCount) > 10)
        {
            var spanContainer = alib.dom.createElement("span", divSearchCon);
            var textSearch = alib.dom.setElementAttr(alib.dom.createElement("input", spanContainer), [["type", "text"]]);
        
            // Style Set
            alib.dom.styleSet(textSearch, "width", "150px");
            alib.dom.styleSet(textSearch, "margin", "5px 0");
            alib.dom.styleSet(textSearch, "paddingRight", "25px");
            spanContainer.className = "clearIcon";
            
            // span icon
            var spanIcon = alib.dom.createElement("span", spanContainer);
            spanIcon.className = "deleteicon";
            alib.dom.styleSet(spanIcon, "visibility", "hidden");
            
            // span icon onclick
            spanIcon.cls = this;
            spanIcon.textSearch = textSearch;
            spanIcon.onclick = function()
            {
                this.textSearch.value = "";
                this.textSearch.focus();
                alib.dom.styleSet(this, "visibility", "hidden");
                this.textSearch.onkeyup();
            }
            
            textSearch.focus();
            textSearch.cls = this.cbData.cls;
            textSearch.data = ret;
            textSearch.con = divValueCon;
            textSearch.nullEntry = nullEntry;
            textSearch.spanIcon = spanIcon;        
            textSearch.onkeyup = function()
            {
                this.con.innerHTML = "";
                
                if (!this.cls.opts.noNull && this.cls.fieldType !== "fkey_multi")
                    this.cls.populateSelect(this.nullEntry, this.con);
                
                this.cls.populateSelect(this.data, this.con, this.value.toLowerCase());
                
                if(this.value.length > 0)
                    alib.dom.styleSet(this.spanIcon, "visibility", "visible");
                else
                    alib.dom.styleSet(this.spanIcon, "visibility", "hidden");
            }
        }
        
        alib.dom.styleSet(divValueCon, "cursor", "pointer");
        divValueCon.dropdownCon = this.cbData.dropdownCon;
        divValueCon.spanIcon = spanIcon;
        divValueCon.onclick = function()
        {
            alib.dom.styleSet(this.spanIcon, "visibility", "hidden");
            this.dropdownCon.menuref.unloadMe();
        }
        
		if (!this.cbData.cls.opts.noNull && this.cbData.cls.fieldType !== "fkey_multi")
            this.cbData.cls.populateSelect(nullEntry, divValueCon);
        
        this.cbData.cls.populateSelect(ret, divValueCon);
	};

	ajax.exec("/controller/Object/getGroupings", args);
}

/**
 * @depricted Now use populateItems
* Display object form/viewer
*/
AntObjectGroupingSel.prototype.populateSelect = function(data, con, filter, spacer)
{
    if(typeof spacer == "undefined")
        spacer = "";
        
    if(data.length)
    {
        for(value in data)
        {
            var currentValue = data[value];
            
            // Filter the Ids that already saved
            var vFound = false;
            for(mVar in this.multiVars)
            {
                var gId = this.multiVars[mVar];
                if(gId == currentValue.id)
                {
                    vFound = true;
                    break;
                }
            }
            
            if(vFound)
                continue;
            
            if(filter)
            {
                if(currentValue.title.toLowerCase().indexOf(filter) !== -1)
                    this.addEntry(con, currentValue, spacer + currentValue.title)
            }
            else
            {
                this.addEntry(con, currentValue, spacer + currentValue.title)
            }
            
            if(currentValue.children && currentValue.children.length)
                this.populateSelect(currentValue.children, con, filter, spacer + "\u00A0\u00A0\u00A0\u00A0");
        }
    }
    else
        con.innerHTML = " <div style='padding: 3px;'>None</div>";
}

/**
 * Add items to menu from the data received from server
 *
 * @param {Array} data Data received from server (JSON objects)
 * @param {alib.ui.Menu} menu The current menu we are working with
 * @param {string} prefix For printing heirarchy / children
 */
AntObjectGroupingSel.prototype.populateItems = function(data, menu, prefix)
{
    if(typeof prefix == "undefined")
        prefix = "";
        
    if(!data.length)
		return;

	for(value in data)
	{
		var currentValue = data[value];
		
		// Filter the Ids that already saved if used for editing a field
		var vFound = false;
		for(mVar in this.multiVars)
		{
			var gId = this.multiVars[mVar];
			if(gId == currentValue.id)
			{
				vFound = true;
				break;
			}
		}

		if(vFound)
			continue;

		// Add menu item if it does not already exist
		var item = menu.getItemById(currentValue.id);
		if (!item)
		{
			var item = new alib.ui.MenuItem(prefix + currentValue.title, {}, currentValue.id);
			item.cbData.id = currentValue.id;
			item.cbData.title = currentValue.title;
			item.cbData.cls = this;
			item.onclick = function() {
				this.cbData.cls.select(this.cbData.id, this.cbData.title);
			};
			menu.addItem(item);
		}
		
		// Traverse children if they exist
		if(currentValue.children && currentValue.children.length)
			this.populateItems(currentValue.children, menu, prefix + currentValue.title + "/");
	}
}

/**
* @depricted Now use populateItems
* Adds the group entry in the dropdown
* 
* @param {DOMElement} con       Container of the dropdown groups
* @param {Object} currentValue  Contains the data of groups
* @param {String} entryValue    The title of groups (may contain spacer for child groups)
*/
AntObjectGroupingSel.prototype.addEntry = function(con, currentValue, entryValue)
{
    var dv = alib.dom.createElement("div", con);            
    dv.cls = this;
    dv.id = currentValue.id;
    dv.title = currentValue.title;
    dv.onclick = function()
    {
        this.cls.tableSelect(this.id, this.title);
    }
    dv.innerHTML = entryValue;
}

/**
* Display object form/viewer
*/
AntObjectGroupingSel.prototype.loadObjectForm = function(id)
{
	var oid = (id) ? id : null;
	switch (this.mainObject.name)
	{
	case "customer":
		custOpen(id);
		break;
	case "lead":
		custLeadOpen(oid);
		break;
	case "opportunity":
		custOppOpen(oid);
		break;
	case "task":
		projTaskOpen(oid);
		break;
	case "case":
		projTicketOpen(oid);
		break;
	default:
		var url = '/obj/'+this.mainObject.name;
		if (oid)
			url += '/'+oid;
		var strWindName = (this.mainObject.name.replace(".", "_"))+'_'+((oid)?oid:'new')
		
		window.open(url, strWindName, 'width=750,height=550,toolbar=no,scrollbars=yes');
		break;
	}
}

/**
* Add a filter for querying data
*/
AntObjectGroupingSel.prototype.setFilter = function(field, val)
{
	var ind = this.m_filters.length;
	this.m_filters[ind] = new Object();
	this.m_filters[ind].fieldName = field;
	this.m_filters[ind].value = val;
}

/**
* get input
*/
AntObjectGroupingSel.prototype.getInput = function()
{
	return this.m_input;
}

/**
* Get the label for a fkey value (used mostly for drop-downs)
*/
AntObjectGroupingSel.prototype.getObjectName = function(id)
{
	if (this.mainObjectType)
	{
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            if(!ret)
                return;
                
            if (ret['error'])
                ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
            else
                this.cbData.cls.m_lbl.innerHTML = unescape(ret) +  "&#9660;";
        };
        var args = [["obj_type", this.mainObjectType], ["id", id]];
        ajax.exec("/controller/Object/getObjName", args);
	}
}

/**
* Get the label for a fkey value (used mostly for drop-downs)
*/
AntObjectGroupingSel.prototype.getForeignValueLabel = function(id)
{
	if (this.mainObjectType)
	{
        ajax = new CAjax('json');
        ajax.cbData.cls = this;
        ajax.onload = function(ret)
        {
            if(!ret)
                return;
                
            if (ret['error'])
                ALib.statusShowAlert(ret['error'], 3000, "bottom", "right");
            else
                this.cbData.cls.m_lbl.innerHTML = unescape(ret) + "&#9660;";
        };
        var args = [["obj_type", this.mainObjectType], ["field", this.mainObjectField], ["id", id]];
        ajax.exec("/controller/Object/getFkeyValName", args);
	}
}

/**
 * @depricated This function is not longer used
 * Get the first value
AntObjectGroupingSel.prototype.setDefault = function()
{
	if (this.value)
		return;

	var ajax = new CAjax();
	ajax.m_browseclass = this;
	ajax.onload = function(root)
	{
		var num = root.getNumChildren();
		if (num)
		{
			for (i = 0; i < num; i++)
			{
				var child = root.getChildNode(i);

				if (child.m_name == "value")
				{
					var key = child.getAttribute("key");
					var title = unescape(child.getAttribute("title"));
					this.m_browseclass.tableSelect(key, title);
				}
			}
		}
	};

	var url = "/objects/xml_dynsel.php?fval=0";
	if (this.mainObjectType)
		url += "&obj_type=" + this.mainObjectType;
	if (this.mainObjectField)
		url += "&field=" + this.mainObjectField;
	url += "&limit=1";

	var args = new Array();

	for (var i = 0; i < this.m_filters.length; i++)
	{
		var cond = this.m_filters[i];
		args[args.length] = [cond.fieldName, cond.value];
	}

	if (this.mainObject)
	{
		var fields = this.mainObject.getFields();
		for (var i = 0; i < fields.length; i++)
		{
			args[args.length] = [fields[i].name, this.mainObject.getValue(fields[i].name)];
		}
	}

	//ALib.m_debug = true;
	//AJAX_TRACE_RESPONSE = true;
	ajax.m_method = AJAX_POST;
	ajax.exec(url, args);
}
*/
