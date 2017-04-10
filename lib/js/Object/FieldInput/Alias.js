/**
 * Input class for handing alias fields
 *
 * These all have the commmon interface functions:
 * setValue
 * getValue
 *
 * Should fire a 'change' event that the parent input can capture
 */

/**
 * Class constructor
 */
function AntObject_FieldInput_Alias(fieldCls, con, options)
{
	var inp = alib.dom.createElement("select", con);

	for (var i = 0; i < fieldCls.obj.fields.length; i++)
	{
		var fldinst = fieldCls.obj.fields[i];
		if (fieldCls.field.subtype == fldinst.subtype && fldinst.type != "alias")
		{
			inp[inp.length] = new Option(fldinst.title, fldinst.name, false, (fieldCls.value == fldinst.name)?true:false);
		}
	}

	con.inptType = "select";
	con.inpRef = inp;

	// Register change event
	inp.clsRef = this;
	inp.onchange = function() { 
		alib.events.triggerEvent(this.clsRef, "change", {value:this.value, valueName:null});
	}
}

/**
 * Set the value of this input
 *
 * @var {string} value The value, numeric if this is a key type like fkey or object
 * @var {string} valueName Optional name of key value if value type is key
 */
AntObject_FieldInput_Alias.prototype.setValue = function(value, valueName)
{
}

/**
 * Get the value of this input
 */
AntObject_FieldInput_Alias.prototype.getValue = function()
{
}
