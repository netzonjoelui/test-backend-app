/**
 * Input class for number fields
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
function AntObject_FieldInput_Number(inpField, con, options)
{
	// If the field has optional values defined then print drop-down
	if (inpField.field.optional_vals && inpField.field.optional_vals.length)
	{
		var subInp = new AntObject_FieldInput_OptionalValues(inpField, con, options);
		return subInp;
	}

	var inp = alib.dom.createElement("input");
	inp.type = "text";
	con.inptType = "input";
	alib.dom.styleSetClass(inp, "fancy");

	if (options.width)
		alib.dom.styleSet(inp, "width", options.width);
	else
		alib.dom.styleSet(inp, "width", "99%");

	if (inpField.value)
		inp.value = inpField.value;

	if (inpField.field.type == "real")
		inp.maxLength = 15;

	con.inpRef = inp;
	con.appendChild(inp);

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
AntObject_FieldInput_Number.prototype.setValue = function(value, valueName)
{
}

/**
 * Get the value of this input
 */
AntObject_FieldInput_Number.prototype.getValue = function()
{
}
