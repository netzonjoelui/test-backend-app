/**
 * Render input for a field of type grouping (fkey || fkey_multi)
 *
 * These all have the commmon interface functions:
 * setValue
 * getValue
 */

/**
 * Class constructor
 */
function AntObject_FieldInput_Grouping(fieldInput, con, options)
{
	/**
	 * FieldInput class
	 *
	 * @var {AntObject_FieldInput}
	 */
	this.fieldInput = fieldInput;

	// Create the drop-down for selecting groupings
	// -----------------------------------------------
	var sel = new AntObjectGroupingSel("None", fieldInput.objType, fieldInput.field.name, fieldInput.value, fieldInput.obj, options);
	this.sel = sel;
	for (var f = 0; f < fieldInput.obj.m_filters.length; f++)
		sel.setFilter(fieldInput.obj.m_filters[f].fieldName, fieldInput.obj.m_filters[f].value);

	// Show current group values if in form view mode
	// -----------------------------------------------
	var dv_opt_con = null;
	if (options.mode == "form" && fieldInput.field.type=="fkey_multi")
	{
		dv_opt_con = alib.dom.createElement("span", con);

		// Populate existing values
        for (var m = 0; m < fieldInput.value.length; m++)
        {
            var id = fieldInput.value[m];
			var label = id;
            //var label = valParts[m];
			var label = fieldInput.obj.getValueName(fieldInput.field.name, id);
			if (label == "" || label==null)
				label = id;

            // Look for label in optional vals
            for (var n = 0; n < fieldInput.field.optional_vals.length; n++)
            {
                if (fieldInput.field.optional_vals[n][0] == id)
                    label = fieldInput.field.optional_vals[n][1];
            }

			this.addGroupingItem(dv_opt_con, id, label)
        }
	}

	// Print drop-down
	var inp = sel.getInput();
	sel.print(con);
	con.inptType = "dynselect";
	con.inpRef = sel;


	// Register change event
	sel.clsRef = this;
	sel.dv_opt_con = dv_opt_con;
	sel.onSelect = function() { 
		alib.events.triggerEvent(this.clsRef, "change", {value:this.value, valueName:this.valueName, action:"add"});

		if (this.dv_opt_con)
			this.clsRef.addGroupingItem(this.dv_opt_con, this.value, this.valueName);
	}
}

AntObject_FieldInput_Grouping.prototype.setValue = function(value, valueName)
{
}

AntObject_FieldInput_Grouping.prototype.getValue = function(value, valueName)
{
}

/**
 * Insert a grouping item into the values div
 *
 * @param {DOMElement} con Where to append the item
 * @param {number} id The unique id of the grouping entry
 * @param {string} label The text title of the grouping entry
 */
AntObject_FieldInput_Grouping.prototype.addGroupingItem = function(con, id, label)
{
	var bg = "e3e3e3";
	var fg = "000000";

	// group div
	var dv = alib.dom.createElement("div", con);
	alib.dom.styleSet(dv, "display", "inline-block");
	alib.dom.styleSet(dv, "zoom", "1");
	alib.dom.styleSet(dv, "*display", "inline");
	alib.dom.styleSet(dv, "padding", "3px 5px 3px 5px");
	alib.dom.styleSet(dv, "margin-right", "5px");
	alib.dom.styleSet(dv, "background-color", '#'+bg);
	alib.dom.styleSet(dv, "color", "#"+fg);
	//alib.dom.styleSet(dv, "border-radius", "3px");
	//alib.dom.styleSet(dv, "-webkit-border-radius", "3px");
	//alib.dom.styleSet(dv, "-moz-border-radius", "3px");

	// label span
	var lblsp = alib.dom.createElement("span", dv, label + " | ");

	// Load remote if label not set
	if (id == label)
	{
		var ajax = new CAjax('json');
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
		var args = [["obj_type", this.fieldInput.objType], ["field", this.fieldInput.field.name], ["gid", id]];
		ajax.exec("/controller/Object/getGroupingById", args);
	}

	var alnk = alib.dom.createElement("a", dv);
	alnk.href = "javascript:void(0);";
	alnk.innerHTML = "X";
	alnk.m_id = id;
	alnk.m_label = label;
	alnk.m_div = dv;
	alnk.m_cls = this;
	alnk.m_fieldname = this.fieldInput.field.name;
	alnk.dynSelObject = this.sel;
	alnk.onclick = function()
	{
		this.m_div.style.display='none';
		alib.events.triggerEvent(this.m_cls, "change", {value:this.m_id, valueName:"", action:"remove"});
		
		for(mVar in this.dynSelObject.multiVars)
		{
			var gId = this.dynSelObject.multiVars[mVar];
			if(this.m_id == gId)
				delete this.dynSelObject.multiVars[mVar];
		}
	}
}
