/**
 * Input class for text fields
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
function AntObject_FieldInput_Text(inptField, con, options)
{
	// If the field has optional values defined then print drop-down
	if (inptField.field.optional_vals && inptField.field.optional_vals.length)
	{
		var subInp = new AntObject_FieldInput_OptionalValues(inptField, con, options);
		return subInp;
	}

	var options = options || new Object();

	if (options.rich)
	{
		//var inp = new CRte();
		var inp = alib.ui.Editor();
		con.inptType = "rte";
	}
	else if (options.multiLine)
	{
		var inp = alib.dom.createElement("textarea");
		con.inptType = "input";
	}
	else
	{
		var inp = alib.dom.createElement("input");
		inp.type = "text";
		con.inptType = "input";
		alib.dom.styleSetClass(inp, "fancy");
	}

	if (options.height)
		alib.dom.styleSet(inp, "height", options.height);

	if (options.width)
		alib.dom.styleSet(inp, "width", options.width);

	if (inptField.value && !options.rich)
		inp.value = inptField.value;

	con.inpRef = inp;
	if (options.rich)
	{
		if (options.plugins)
		{
			if ("cms" == options.plugins)
				inp.cssFiles = [
					"/css/bootstrap.min.css", 
					"/css/bootstrap-theme.min.css"
				];
		}

		inp.print(con, '100%', '250px');
		if (inptField.value)
			inp.setValue(inptField.value);
	}
	else
	{
		con.appendChild(inp);
	}

	alib.dom.styleSet(inp, "width", "99%");

	// Must be added after appended
	if (options.multiLine && !options.rich)
	{
		alib.dom.textAreaAutoResizeHeight(inp, 50, 400);
	}

	// Register change event
	inp.clsRef = this;
	if (options.rich)
	{
		inp.onChange = function() { 
			alib.events.triggerEvent(this.clsRef, "change", {value:this.getValue(), valueName:null});
		}
	}
	else
	{
		// Add blurr text with field name if there is no label - we may use this for mobile too
		if (options.hidelabel)
			inp.placeholder = inptField.field.title;

		inp.onchange = function() { 
			alib.events.triggerEvent(this.clsRef, "change", {value:this.value, valueName:null});
		}
	}

	this.options = options;
	this.inp = inp;
}

/**
 * Set the value of this input
 *
 * @var {string} value The value, numeric if this is a key type like fkey or object
 * @var {string} valueName Optional name of key value if value type is key
 */
AntObject_FieldInput_Text.prototype.setValue = function(value, valueName)
{
}

/**
 * Get the value of this input
 */
AntObject_FieldInput_Text.prototype.getValue = function()
{
}

/**
 * Set the height of multiLine or rich inputs
 *
 * @param {string} height The css height to set
 * @return {bool} true if set, false if not set
 */
AntObject_FieldInput_Text.prototype.setHeight = function(height)
{
	if (!this.inp)
		return false;

	if (this.options.rich)
	{
		if (this.inp.setHeight)
		{
			this.inp.setHeight(height);
			return true;
		}
	}
	
	/* Multiline autogrows so we don't really need this
	if (this.options.multiLine)
	{
		alib.dom.styleSet(this.inp, "height", height);
		return true;
	}
	*/

	return false;
}
