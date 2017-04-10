//--------------------------------------------------------------------------
//	CAntObjectView
//--------------------------------------------------------------------------
function CAntObjectView(type)
{
	this.id 			= null;
	this.name			= "";
	this.obj_type 		= type;
	this.description	= "";
	this.fSystem		= false; // if a system wide view then no edit
	this.conditions		= new Array();
	this.sort_order		= new Array();
	this.view_fields	= new Array();
	this.filterKey		= "";
	this.reportId		= ""; // Each report has a unique view
}

CAntObjectView.prototype.loadFromXml = function(xml_node) 
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

CAntObjectView.prototype.fromData = function(data) 
{
	this.id = data.id;
	this.name = data.name;
	this.description = data.description;
	this.fSystem = data.f_system;
	this.fDefault = data.f_default;
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

CAntObjectView.prototype.save = function() 
{
	var args = [["obj_type", this.obj_type], ["name", this.name], ["description", this.description], ["filter_key", this.filterKey], ["report_id", this.reportId]];

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
    
    /*function cbdone(ret, cls)
    {
        if (!ret['error'])
        {
            if (!cls.id)
            {
                cls.id = ret;
            }

            if (!cls.repressOnSave)
                cls.onsave(ret);
        }
        else
            cls.onsaveError();
    }
    var rpc = new CAjaxRpc("/controller/Object/saveView", "saveView", args, cbdone, [this], AJAX_POST, true, "json");*/
    
    ajax = new CAjax('json');
    ajax.cbData.cls = this;
    ajax.onload = function(ret)
    {
        if (!ret['error'])
        {
            if (!this.cbData.cls.id)
                this.cbData.cls.id = ret;

            if (!this.cbData.cls.repressOnSave)
                this.cbData.cls.onsave(ret);
        }
        else
            cls.onsaveError();
    };
    ajax.exec("/controller/Object/saveView", args);
}

CAntObjectView.prototype.reset = function() 
{
	this.conditions		= new Array();
	this.sort_order		= new Array();
	this.view_fields	= new Array();
}

CAntObjectView.prototype.addCondition = function(blogic, fieldName, operator, condValue) 
{
	var ind = this.conditions.length;
	this.conditions[ind] = new Object();
	this.conditions[ind].blogic = blogic;
	this.conditions[ind].fieldName =fieldName;
	this.conditions[ind].operator = operator;
	this.conditions[ind].condValue = condValue;
}

CAntObjectView.prototype.onsave = function(id) 
{
}

CAntObjectView.prototype.onsaveError = function(id) 
{
}

