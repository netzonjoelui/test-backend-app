/**
 * @fileoverview Represent object browser views
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObjectBrowserView
 *
 * @constructor
 * @param {string} obj_type The name of the object type to load
 */
function AntObjectBrowserView(obj_type)
{
	this.id 			= null;
	this.name			= "";
	this.obj_type 		= obj_type;
	this.description	= "";
	this.fSystem		= false; // if a system wide view then no edit
	this.conditions		= new Array();
	this.sort_order		= new Array();
	this.view_fields	= new Array();
	this.filterKey		= "";
    this.reportId       = ""; // Each report has a unique view
    this.team_id        = ""; // Is used when scope is set to team
    this.scope          = ""; // The scope of view
	this.f_default		= ""; // Will set the view to default
    this.user_id        = null; // Will be set if the view is created for another user
    
    // This will fix the variable compatible issue when creating new view
    this.fDefault = null;
    this.userName = null;
    this.userid = null;
}

AntObjectBrowserView.prototype.loadFromXml = function(xml_node) 
{
	if (xml_node.m_name == "view")
	{
		this.id = unescape(xml_node.getChildNodeValByName("id"));
		this.name = unescape(xml_node.getChildNodeValByName("name"));
		this.description = unescape(xml_node.getChildNodeValByName("description"));
		this.fSystem = (xml_node.getChildNodeValByName("f_system")=='t')?true:false;
		this.fDefault = (xml_node.getChildNodeValByName("f_default")=='t')?true:false;
		var fk = xml_node.getChildNodeValByName("filter_key");
		this.filterKey = (fk)?unescape(fk):"";

		var view_fields = xml_node.getChildNodeByName("view_fields");
		if (view_fields)
		{
			for (var j = 0; j < view_fields.getNumChildren(); j++)
			{
				var fld = view_fields.getChildNode(j);
				var ind = this.view_fields.length;
				this.view_fields[ind] = new Object();
				this.view_fields[ind].id = null;
				this.view_fields[ind].fieldName = fld.m_text;
			}
		}

		var conditions = xml_node.getChildNodeByName("conditions");
		if (conditions)
		{
			for (var j = 0; j < conditions.getNumChildren(); j++)
			{
				var cnd = conditions.getChildNode(j);
				var ind = this.conditions.length;
				this.conditions[ind] = new Object();
				this.conditions[ind].blogic = unescape(cnd.getChildNodeValByName("blogic"));
				this.conditions[ind].fieldName = unescape(cnd.getChildNodeValByName("field_name"));
				this.conditions[ind].operator = unescape(cnd.getChildNodeValByName("operator"));
				this.conditions[ind].condValue = unescape(cnd.getChildNodeValByName("value"));
			}
		}

		var sort_order = xml_node.getChildNodeByName("sort_order");
		if (sort_order)
		{
			for (var j = 0; j < sort_order.getNumChildren(); j++)
			{
				var order = sort_order.getChildNode(j);
				var ind = this.sort_order.length;
				this.sort_order[ind] = new Object();
				this.sort_order[ind].fieldName = unescape(order.getChildNodeValByName("field_name"));
				this.sort_order[ind].order = unescape(order.getChildNodeValByName("order"));
			}
		}

	}
}

AntObjectBrowserView.prototype.fromData = function(data) 
{
	this.id = data.id;
	this.name = data.name;
	this.description = data.description;
	this.fSystem = data.f_system;
	this.fDefault = data.f_default;
	if (data.filter_key)
		this.filterKey = data.filter_key;

	for (var i in data.view_fields)
	{
		this.view_fields[this.view_fields.length] = {
			id : null, // legacy
			fieldName: data.view_fields[i]
		};
	}

	for (var i in data.conditions)
	{
		this.conditions[this.conditions.length] = {
			blogic : data.conditions[i].blogic,
			fieldName : data.conditions[i].field_name,
			operator : data.conditions[i].operator,
			condValue : data.conditions[i].value
		};
	}

	for (var i in data.sort_order)
	{
		this.sort_order[this.sort_order.length] = {
			fieldName : data.sort_order[i].field_name,
			order : data.sort_order[i].order
		};
	}
}

AntObjectBrowserView.prototype.save = function() 
{
    var args = new Array();
    args[args.length] = ['obj_type', this.obj_type];
    args[args.length] = ['name', this.name];
    args[args.length] = ['description', this.description];
    args[args.length] = ['filter_key', this.filterKey];
    args[args.length] = ['report_id', this.reportId];
    args[args.length] = ['team_id', this.team_id];
    args[args.length] = ['scope', this.scope];
    args[args.length] = ['f_default', this.f_default];
    args[args.length] = ['user_id', this.user_id];
    
	if (this.id)
		args[args.length] = ["vid", this.id];

	for (var i = 0; i < this.conditions.length; i++)
	{
		var cond = this.conditions[i];
		args[args.length] = ["conditions[]", i];
		args[args.length] = ["condition_blogic_"+i, cond.blogic];
		args[args.length] = ["condition_fieldname_"+i, cond.fieldName];
		args[args.length] = ["condition_operator_"+i, cond.operator];
		args[args.length] = ["condition_condvalue_"+i, cond.condValue];
	}

	for (var i = 0; i < this.sort_order.length; i++)
	{
		var sort_fld = this.sort_order[i];
		args[args.length] = ["sort_order[]", i];
		args[args.length] = ["sort_order_fieldname_"+i, sort_fld.fieldName];
		args[args.length] = ["sort_order_order_"+i, sort_fld.order];
	}

	for (var i = 0; i < this.view_fields.length; i++)
	{
		var fields = this.view_fields[i];
		args[args.length] = ["view_fields[]", i];
		args[args.length] = ["view_field_fieldname_"+i, fields.fieldName];
	}
    
    ajax = new CAjax('json');
    ajax.cls = this;
    ajax.onload = function(ret)
    {
        if(!ret)
        {
            this.cls.onsaveError();
            return;
        }
            
        if (!ret['error'])
        {
            if (!this.cls.id)
                this.cls.id = ret;

            if (!this.cls.repressOnSave)
                this.cls.onsave(ret);
        }
        else
            this.cls.onsaveError();
    };
    ajax.exec("/controller/Object/saveView", args);
}

AntObjectBrowserView.prototype.reset = function() 
{
	this.conditions		= new Array();
	this.sort_order		= new Array();
	this.view_fields	= new Array();
}

/**
 * Add a condition to this view
 */
AntObjectBrowserView.prototype.addCondition = function(blogic, fieldName, operator, condValue) 
{
	var ind = this.conditions.length;
	this.conditions[ind] = new Object();
	this.conditions[ind].blogic = blogic;
	this.conditions[ind].fieldName =fieldName;
	this.conditions[ind].operator = operator;
	this.conditions[ind].condValue = condValue;
}

/**
 * Add order by
 *
 * @return {Object} A handle to the order by object of this view
 */
AntObjectBrowserView.prototype.addOrderBy = function(fieldName, order) 
{
	var ind = this.sort_order.length;
	this.sort_order[ind] = new Object();
	this.sort_order[ind].fieldName = fieldName;
	this.sort_order[ind].order = order;

	return this.sort_order;
}

/**
 * Copy everything from another view except for the id
 *
 * @param {AntObjectBrowserView} view The view to copy from
 */
AntObjectBrowserView.prototype.copyView = function(view)
{
	// Clar current params
	this.reset();

	// Copy conditions
	for (var i = 0; i < view.conditions.length; i++)
	{
		this.addCondition(view.conditions[i].blogic, view.conditions[i].fieldName, view.conditions[i].operator, view.conditions[i].condValue);
	}

	// Sort order
	for (var i = 0; i < view.sort_order.length; i++)
	{
		this.addOrderBy(view.sort_order[i].fieldName, view.sort_order[i].order);
	}

	// View columns
	for (var i = 0; i < view.view_fields.length; i++)
	{
		this.view_fields[this.view_fields.length] = view.view_fields[i];
	}
}

/**
 * Clear conditions
 * 
 */
AntObjectBrowserView.prototype.clearConditions = function() 
{
    this.conditions = new Array();
}

/**
 * Callback fired when view is saved
 */
AntObjectBrowserView.prototype.onsave = function(id) 
{    
}

/**
 * Depricated
 */
AntObjectBrowserView.prototype.onsaveError = function(id) 
{    
}

