/**
 * Input class for handling boolean fields
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
function AntObject_FieldInput_Bool(fieldCls, con, options)
{
	var inp = alib.dom.createElement("input");
	inp.type = "checkbox";
	inp.checked = (fieldCls.value) ? true : false;
	con.inptType = "checkbox";
	con.inpRef = fieldCls;
	con.appendChild(inp);

	// Register change event
	inp.clsRef = this;
	inp.onclick = function() { 
		alib.events.triggerEvent(this.clsRef, "change", {value:this.checked, valueName:null});
	}
}

/**
 * Set the value of this input
 *
 * @var {string} value The value, numeric if this is a key type like fkey or object
 * @var {string} valueName Optional name of key value if value type is key
 */
AntObject_FieldInput_Bool.prototype.setValue = function(value, valueName)
{
}

/**
 * Get the value of this input
 */
AntObject_FieldInput_Bool.prototype.getValue = function()
{
}

