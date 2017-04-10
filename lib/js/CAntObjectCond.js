/*======================================================================================
	
	Class:		CAntObjectCond

	Purpose:	CAntObjectCond is an object that stores an array of conditions 
				relating to a specific object type.

	Author:		joe, sky.stebnicki@aereus.com
				Copyright (c) 2010 Aereus Corporation. All rights reserved.
	
	Usage:		// Display advanced condition form for user
				var obj = new CAntObject("customer"); // get object definition
				var conditions = obj.buildAdvancedQuery(document.body);

				// Form will be printed, user can modify values, to get values simply do the following
				for (var i = 0; i < conditions.getNumConditions(); i++)
				{
					var cond = conditions.getCondition(i);
					// Do something with: cond.blogic, cond.fieldName, cond.operator, cond.condValue
				}

				// To load a conditions object into a form for user
				var conditions = new CAntObjectCond();
				conditions.addCondition("and", "first_name", "is_equal", "myfirstname");
				var newConditions = obj.buildAdvancedQuery(document.body, conditions);
				// The form will be built using the values from the second param passed
				

======================================================================================*/


/*************************************************************************************
*	Description:	CAntObjectCond	
*
*	Purpose:		CAntObjectCond is an object that stores an array of conditions 
*					with the following properties:
*					blogic
*					fieldName
*					operator
*					condValue
**************************************************************************************/

function CAntObjectCond()
{
	this.conditions = new Array();
	this.last_id = 1;
}

CAntObjectCond.prototype.addWatchCondition = function(cbLogic, cbFieldName, cbOperator, cbId)
{    
    var id = this.last_id++;
	
	var cond = new Object();
	cond.id = id;
	this.id;

	// and/or
	var bl_funct = function(evnt)
	{
		if (alib.userAgent.ie)
			evnt.srcElement.m_cond.blogic = evnt.srcElement.value
		else
			this.m_cond.blogic = this.value;
	}
	cond.blogic = cbLogic.value;
	cbLogic.m_cond = cond;
	alib.dom.addEvntListener(cbLogic, "change", bl_funct);

	// Field name
	var fn_funct = function(evnt)
	{
		if (alib.userAgent.ie)
			evnt.srcElement.m_cond.fieldName = evnt.srcElement.value
		else
			this.m_cond.fieldName = this.value;
	}
	cond.fieldName = cbFieldName.value;
	cbFieldName.m_cond = cond;
	alib.dom.addEvntListener(cbFieldName, "change", fn_funct);

	// Operator
	var op_funct = function(evnt)
	{
		if (alib.userAgent.ie)
			evnt.srcElement.m_cond.operator = evnt.srcElement.value
		else
			this.m_cond.operator = this.value;
	}
	cond.operator = cbOperator.value;
	cbOperator.m_cond = cond;
	alib.dom.addEvntListener(cbOperator, "change", op_funct);

    cond.condId = cbId; // the id that is retrieved from the database
	this.conditions[this.conditions.length] = cond;

	return id;
}

CAntObjectCond.prototype.addWatchConditionVal = function(cid, valueField)
{
	var cond = this.getConditionById(cid);

	// Value
	if (cond)
	{
        // We need to check if value field is undefined before setting condition value
        if(typeof valueField.value !== "undefined")
            cond.condValue = valueField.value;
            
		valueField.cond = cond;
		var cond_funct = function(evnt)
		{
			if (alib.userAgent.ie)
				evnt.srcElement.cond.condValue = evnt.srcElement.value
			else
				this.cond.condValue = this.value;
		}
		alib.dom.addEvntListener(valueField, "change", cond_funct);
	}
}

CAntObjectCond.prototype.addWatchConditionDynSel = function(cid, sel)
{
	var cond = this.getConditionById(cid);

	// Value
	if (cond)
	{
		cond.condValue = sel.value;
		sel.cond = cond;
		sel.onSelect = function(id, name)
		{
			this.cond.condValue = this.value;
		}
	}
}

CAntObjectCond.prototype.addWatchConditionObSel = function(cid, ob)
{
	var cond = this.getConditionById(cid);

	// Value
	if (cond)
	{
		cond.condValue = sel.value;
		sel.cond = cond;
		sel.onSelect = function(id, name)
		{
			this.cond.condValue = this.value;
		}

		ob.cond = cond;
		ob.onSelect = function(oid) 
		{ 
			this.cond.condValue = oid;
		}
	}
}

CAntObjectCond.prototype.addWatchConditionBrowse = function(cid, condVal)
{
	var cond = this.getConditionById(cid);
	
	if (cond)
		cond.condValue = condVal;
}

CAntObjectCond.prototype.delCondition = function(id) 
{
	for (var i = 0; i < this.conditions.length; i++)
	{
		if (this.conditions[i].id == id)
			this.conditions.splice(i, 1);
	}
}

CAntObjectCond.prototype.getNumConditions = function() 
{
	return this.conditions.length;
}

CAntObjectCond.prototype.getConditionById = function(id) 
{
	for (var i = 0; i < this.conditions.length; i++)
	{
		if (this.conditions[i].id == id)
			return this.conditions[i];
	}

	return null;
}

CAntObjectCond.prototype.getCondition = function(index) 
{
	return this.conditions[index];
}

CAntObjectCond.prototype.clearConditions = function() 
{
	return this.conditions = new Array();
}

CAntObjectCond.prototype.getCondDesc = function(obj_type)
{
	var obj = (obj_type) ? new CAntObject(obj_type) : null
	var buf = "";
	var con = alib.dom.createElement("span");

	for (var i = 0; i < this.conditions.length; i++)
	{
		if (i > 0)
		{
			var tmp_con = alib.dom.createElement("span", con);
			tmp_con.innerHTML = ", " + this.conditions[i].blogic + " ";
		}

		var ftitle = this.conditions[i].fieldName;
		var opname = this.conditions[i].operator;
		var cndval = this.conditions[i].condValue;
		if (obj)
		{
			var field = obj.getFieldByName(this.conditions[i].fieldName);
			ftitle = field.title;

			var type_opts = obj.getCondOperators(field.type);
			for (var j = 0; j < type_opts.length; j++)
			{
				if (type_opts[j][0] == this.conditions[i].operator)
				{
					opname = type_opts[j][1];
				}
			}

			// replace last/next (x) with value
			if (opname.search(/last \(x\)/i)!=-1 || opname.search(/next \(x\)/i)!=-1)
			{
				opname = opname.replace("(x)", cndval);
				cndval = "";
			}

			var tmp_con = alib.dom.createElement("span", con);
			tmp_con.innerHTML = ftitle + " " + opname + " ";

			if (field.type == "fkey" || field.type == "fkey_multi")
			{
				var valname_con = alib.dom.createElement("span", con);
				this.setForeignValueLabel(obj_type, field.name, cndval, valname_con);
			}
			else
			{
				tmp_con.innerHTML += cndval;
			}
		}
		else
		{
			var tmp_con = alib.dom.createElement("span", con);
			tmp_con.innerHTML = ftitle + " " + opname + " " + cndval;
		}

		//buf += ftitle + " " + opname + " " + cndval;
	}

	return con;
}

/*************************************************************************************
*	Description:	getForeignValueLabel	
*
*	Purpose:		Get the label for a fkey value (used mostly for drop-downs) when
*					object is not loaded. The con will be set with the appropriate value
**************************************************************************************/
CAntObjectCond.prototype.setForeignValueLabel = function(obj_type, field_name, id, con)
{
	if (obj_type)
	{
        ajax = new CAjax('json');
        ajax.cbData.con = con;
        ajax.onload = function(ret)
        {
            if (!ret['error'])
                this.cbData.con.innerHTML = unescape(ret);
        };
        var args = [["obj_type", obj_type], ["field", field_name], ["id", id]];
        ajax.exec("/controller/Object/getFkeyValName", args);
        //var rpc = new CAjaxRpc("/controller/Object/getFkeyValName", "getFkeyValName", args, cbdone, [con], AJAX_POST, true, "json");
	}
}
