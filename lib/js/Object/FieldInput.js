/**
 * @fileoverview This class handles creating input elements for fields of objects
 *
 * This class is a work in progress. The eventual goal is to replace the following functions of CAntObject.js:
 * fieldGetValueInput
 * fieldCreateValueInput
 * querySetValueInput - maybe working with a new conditions class
 *
 * Example usage
 * <code>
 * var field = new AntObject_FieldInput("customer", "name");
 *
 * </code>
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011-2013 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObject_FieldInput
 *
 * @constructor
 * @param {string|CAntObject} Either a string of the object type or a reference to an object
 * @param {string} fieldName The name of the field in the object definition
 * @param {string} value Value to pupulate the field with
 * @param {Object} options Optional object with additional field options
 */
function AntObject_FieldInput(obj, fieldName, fieldValue, options)
{
	/**
	 * Current field value
	 *
	 * @public
	 * @var {string}
	 */
	this.value = (fieldValue) ? fieldValue : "";

	/**
	 * Current field valueName if type = fkey_, fkey_multi, object, or object_multi
	 *
	 * @public
	 * @var {string}
	 */
	this.valueName = "";

	/**
	 * Object type name
	 *
	 * @private
	 * @var {string}
	 */
	this.objType = (typeof obj == "string") ? obj : obj.obj_type;

	/**
	 * Handle to object definition
	 *
	 * @private
	 * @var {CAntObject}
	 */
	this.obj = (typeof obj == "string") ? new CAntObject(obj) : obj;

	/**
	 * Each field type can have many different options
	 *
	 * @public
	 * @var {Object}
	 */
	this.options = options || {};

	/**
	 * Field name
	 *
	 * @private
	 * @var {string}
	 */
	this.fieldName = fieldName;

	/**
	 * Field definition
	 *
	 * @private
	 * @var {AntObjectField}
	 */
	this.field = this.obj.getFieldByName(fieldName);

	/**
	 * Actual input subclass - one for each type
	 *
	 * @var {AntObject_FieldInput_*}
	 */
	this.input = null;

	/**
	 * Optional validator to load to check values before sending to the server
	 *
	 * @protected
	 * @var {string}
	 */
	this.validator = "";

	// Now bind events to object if set
	if (obj != null && typeof obj != "string")
	{
		alib.events.listen(obj, "fieldchange", {context:this, method:"objectFieldChanged"});
	}
		
	// Generic change event
	alib.events.listen(this, "change", {context:this, method:"onChange"});
}

/**
 * Render the object input element into the dom tree
 *
 * @param {DOMElement} con The parent container
 * @param {Object} options Optional object with additional field options
 */
AntObject_FieldInput.prototype.render = function(con, options)
{
	var opts = (options) ? options : new Object();
    con.innerHTML = "";

	if (!this.value)
		this.value = this.obj.getValue(this.fieldName);

	if (!this.valueName)
		this.valueName = this.obj.getValueName(this.fieldName);

	switch (this.field.type)
	{
	case 'fkey':
	case 'fkey_multi':
		this.input = new AntObject_FieldInput_Grouping(this, con, opts);
		break;
	case 'object':
		this.input = new AntObject_FieldInput_Object(this, con, opts);
		break;
	case 'bool':
		this.input = new AntObject_FieldInput_Bool(this, con, opts);
		break;
	case 'alias':
		this.input = new AntObject_FieldInput_Alias(this, con, opts);
		break;
	case 'date':
		this.input = new AntObject_FieldInput_Date(this, con, opts);
		break;
	case 'timestamp':
		this.input = new AntObject_FieldInput_Timestamp(this, con, opts);
		break;
	case 'number':
	case 'numeric':
	case 'integer':
	case 'float':
		this.input = new AntObject_FieldInput_Number(this, con, opts);
		break;
	case 'text':
	default:
		this.input = new AntObject_FieldInput_Text(this, con, opts);
		break;
	}

	alib.events.listen(this.input, "change", {context:this, method:"checkValidators"});
	
	if (opts.validator)
		this.validator = opts.validator;
}

/**
 * Create a dropdown for values of fkey_multi
 *
 * @depriacted We will be using the grouping select later
 */
AntObject_FieldInput.prototype.buildInputDropDown = function(cbMval, optional_vals, val, pnt, pre)
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

/**
 * Get an input DOM element to render in the UI
 *
 * @public
 * @param {Object} options 			Object with the following parameters:
 * 									{
 * 										{DOMElement} domElement An optional container to print this input onto
 * 									}
 * @param {bool} updateObjectVal	If true (default) then update this.obj value when changed.
 * 									Most of the time updating the object is fine, but when multiple inputs
 * 									are needed for an object it might be necessary to have the inputs be independent.
 */
AntObject_FieldInput.prototype.getInput = function(options, updateObjectVal)
{
	var updateObj = (typeof updateObjectVal == "undefined") ? true : updateObjectVal;
	var options = options || new Object();
}

/**
 * Get value of this field
 *
 * @public
 * @return {string|int} If foreign reference like an objet the return int, otherwise a string
 */
AntObject_FieldInput.prototype.getValue = function()
{
	return this.value;
}

/**
 * If foreign key field, then get the value name or label
 *
 * @public
 * @return {string}
 */
AntObject_FieldInput.prototype.getValueName = function()
{
	return this.valueName;
}

/**
 * Set the value of this input
 *
 * @public
 * @param {string|int} val The value to set this input to
 * @param {string} valName If a foreign key the valName can be set to store the label reducing requests
 */
AntObject_FieldInput.prototype.setValue = function(val, valName)
{
	var valName = valName || "";
	this.input.setValue(val, valName);

	this.value = val;
	this.valueName = valName;
}

/**
 * Get the name of this field
 *
 * @public
 * @return {string} The unique name of this field
 */
AntObject_FieldInput.prototype.getName = function()
{
	return this.fieldName;
}

/**
 * Check validators on a change and if pass send to fieldInputChanged
 *
 * @param {alib.events.EventWrapper} evnt The event object
 */
AntObject_FieldInput.prototype.checkValidators = function(evt)
{
	if (this.validator)
	{
		var validator = new AntObject_FieldValidator(this.validator, this);
		validator.cbData.cls = this;
		validator.cbData.evt = evt;
		validator.onValid = function() {
			this.cbData.cls.fieldInputChanged(this.cbData.evt);
		};
		validator.onInvalid = function(msg) {
			alert(msg);
		}
		validator.validate(evt.data.value);
	}
	else
	{
		this.fieldInputChanged(evt);
	}
}

/**
 * Callback function to set the value of an object if the user changes the value of this field
 *
 * @param {alib.events.EventWrapper} evnt The event object
 */
AntObject_FieldInput.prototype.fieldInputChanged = function(evt)
{
	this.supressObjChangePush = true; // do not allow infinite loop where fieldInput -> object -> fieldInput...

	if (this.field.type == "fkey_multi" || this.field.type == "object_multi")
	{
		if (evt.data.action == "remove")
			this.obj.delMultiValue(this.fieldName, evt.data.value);
		else
			this.obj.setMultiValue(this.fieldName, evt.data.value, evt.data.valueName);
	}
	else
	{
		this.obj.setValue(this.fieldName, evt.data.value, evt.data.valueName);
	}

	// Set local values so this.getValue returns valid data
	this.value = evt.data.value;
	this.valueName = evt.data.valueName;

	// Fire change event in this object
	alib.events.triggerEvent(this, "change");

}

/**
 * Callback function to handle when an object value changes
 *
 * @param {alib.events.EventWrapper} evnt The event object
 */
AntObject_FieldInput.prototype.objectFieldChanged = function(evt)
{
	// Only capture if the field this input represents changed
	if (evt.data.fieldName != this.fieldName)
		return;

	// Supress sending change event back to object after updating value
	// or we will end up with an infinite loop
	if (this.supressObjChangePush)
	{
		this.supressObjChangePush = false; // only supress once for the udpate
		return;
	}

	this.setValue(evt.data.value, evt.data.valueName);
}

/*----------------------------------------------------------------------
 * Exposed callback functions.
 *
 * We prefer to use alib.events to capture events triggered
 * to keep from overlap, but in the event only a single
 * caller will need to detect certain events the below
 * function can be overridden.
 *---------------------------------------------------------------------*/

/**
 * Called when the value of this input changes
 *
 * If this is a *_mulit field then this will be called each time a value is added or removed with the third param
 *
 * @public
 * @param {alib.events.EventWrapper} evnt The event object
 */
AntObject_FieldInput.prototype.onChange = function(evnt)
{
}

/*----------------------------------------------------------------------
 * Optional modifiers
 *
 * These may or may not be implemented by subclasses
 *---------------------------------------------------------------------*/

AntObject_FieldInput.prototype.setHeight = function(height)
{
	if (this.input)
	{
		if (this.input.setHeight)
			return this.input.setHeight(height);
	}

	return false;
}
