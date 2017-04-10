/**
 * Input class for timestaml fields
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
function AntObject_FieldInput_Timestamp(fieldCls, con, options)
{
	var inp = alib.dom.createElement("input");
	inp.type = "text";
	con.inptType = "input";
	alib.dom.styleSetClass(inp, "fancy");

	var options = options || new Object();

	if (options.width)
		alib.dom.styleSet(inp, "width", options.width);

	if (fieldCls.value)
		inp.value = fieldCls.value;
	
	con.inpRef = inp;
	con.appendChild(inp);

	if (options.part == "time")
	{
		var start_ac = new CAutoCompleteTime(inp);
		alib.dom.styleSet(inp, "width", "75px");
	}
	else 
	{
		var start_ac = new CAutoCompleteCal(inp);
		alib.dom.styleSet(inp, "width", "100px");
	}

	if (options.part)
	{
		inp.part = options.part;
		
		if (fieldCls.value)
			inp.value = fieldCls.obj.getInputPartValue(fieldCls.field.name, fieldCls.value, options.part);
	}

	// Register change event
	inp.clsRef = this;
	inp.onchange = function() { 
		this.clsRef.triggerChange();
	}

	this.fieldCls = fieldCls;
	this.inp = inp;
	this.options = options || {};
}

/**
 * Set the value of this input
 *
 * @var {string} value The value, numeric if this is a key type like fkey or object
 * @var {string} valueName Optional name of key value if value type is key
 */
AntObject_FieldInput_Timestamp.prototype.setValue = function(value, valueName)
{
}

/**
 * Get the value of this input
 */
AntObject_FieldInput_Timestamp.prototype.getValue = function()
{
}

/**
 * Determine value based on parts
 */
AntObject_FieldInput_Timestamp.prototype.triggerChange = function()
{
	var val = this.inp.value;

	if (this.options.part == "time")
	{
		val = this.fieldCls.obj.getInputPartValue(this.fieldCls.field.name, this.fieldCls.value, "date");
		val += " " + this.inp.value;
	}
	else if (this.options.part == "date")
	{
		val = this.inp.value + " ";
		val += this.fieldCls.obj.getInputPartValue(this.fieldCls.field.name, this.fieldCls.value, "time");
	}

	alib.events.triggerEvent(this, "change", {value:val, valueName:null});
}

/**
 * Determine value based on parts
 */
AntObject_FieldInput_Timestamp.prototype.updateTime = function()
{
	var val = this.fieldCls.obj.getInputPartValue(this.fieldCls.field.name, this.fieldCls.value, "date");
	val += " " + this.inp.value;

	alib.events.triggerEvent(this, "change", {value:val, valueName:null});
}

/**
 * Determine value based on parts
 */
AntObject_FieldInput_Timestamp.prototype.updateDate = function()
{
	var val = this.inp.value + " ";
	val += this.fieldCls.obj.getInputPartValue(this.fieldCls.field.name, this.fieldCls.value, "time");

	alib.events.triggerEvent(this, "change", {value:val, valueName:null});
}
