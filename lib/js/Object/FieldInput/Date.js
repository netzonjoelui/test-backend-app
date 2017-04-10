/**
 * Input class for date fields
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
function AntObject_FieldInput_Date(fieldInput, con, options)
{
	var inp = alib.dom.createElement("input");
	inp.type = "text";
	con.inptType = "input";
	alib.dom.styleSetClass(inp, "fancy");

	if (options.width)
		alib.dom.styleSet(inp, "width", options.width);

	if (fieldInput.value)
		inp.value = fieldInput.value;

	con.inpRef = inp;
	con.appendChild(inp);

	// Add date selector
	var start_ac = new CAutoCompleteCal(inp);
	alib.dom.styleSet(inp, "width", "100px");

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
AntObject_FieldInput_Date.prototype.setValue = function(value, valueName)
{
}

/**
 * Get the value of this input
 */
AntObject_FieldInput_Date.prototype.getValue = function()
{
}
