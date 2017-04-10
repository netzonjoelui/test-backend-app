/**
 * Input class for fields with manually defined optional values (drop-down)
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
function AntObject_FieldInput_OptionalValues(inptField, con, options)
{
	if (inptField.field.optional_vals && inptField.field.optional_vals.length)
    {
        var inp = alib.dom.createElement("select", con);
        inptField.buildInputDropDown(inp, inptField.field.optional_vals, inptField.value);
    }

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
AntObject_FieldInput_OptionalValues.prototype.setValue = function(value, valueName)
{
}

/**
 * Get the value of this input
 */
AntObject_FieldInput_OptionalValues.prototype.getValue = function()
{
}

/**
 * Create a dropdown for values of fkey_multi
 *
 * @depriacted We will be using the grouping select later
 */
AntObject_FieldInput_OptionalValues.prototype.buildInputDropDown = function(cbMval, optional_vals, val, pnt, pre)
{
    var value = (val) ? val : null;
    var parent_id = (pnt) ? pnt : "";
    var pre_txt = (pre) ? pre : "";
    var spacer = "\u00A0\u00A0"; // Unicode \u00A0 for space
    for (var n = 0; n < optional_vals.length; n++)
    {
        if (optional_vals[n][3] != parent_id)
        {
            continue;
        }

        cbMval[cbMval.length] = new Option(pre_txt+optional_vals[n][1], optional_vals[n][0], false, (value==optional_vals[n][0])?true:false);
        // Check for heiarchy
        if (optional_vals[n][2])
            this.buildInputDropDown(cbMval, optional_vals, value, optional_vals[n][0], pre_txt+spacer);
    }
}
