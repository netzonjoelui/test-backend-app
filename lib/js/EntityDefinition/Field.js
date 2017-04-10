/**
 * @fileOverview Define entity definition fields
 *
 * This class is a client side mirror of /lib/EntityDefinition/Field on the server side
 *
 * @author:	joe, sky.stebnicki@aereus.com; 
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of Ant.EntityDefinition.Field
 *
 * @constructor
 */
Ant.EntityDefinition.Field = function()
{
	/**
	 * Unique id if the field was loaded from a database
	 *
	 * @public
	 * @type {string}
	 */
	this.id = "";

	/**
	 * Field name (REQUIRED)
	 *
	 * No spaces or special characters allowed. Only alphanum up to 32 characters in length.
	 *
	 * @public
	 * @type {string}
	 */
	this.name = "";

	/**
	 * Human readable title
	 *
	 * If not set then $this->name will be used:
	 *
	 * @public
	 * @type {string}
	 */
	this.title = "";

	/**
	 * The type of field (REQUIRED)
	 *
	 * @public
	 * @type {string}
	 */
	this.type = "";

	/**
	 * The subtype
	 *
	 * @public
	 * @type {string}
	 */
	this.subtype = "";

	/**
	 * Optional mask for formatting value
	 *
	 * @public
	 * @type {string}
	 */
	this.mask = "";

	/**
	 * Is this a required field?
	 *
	 * @public
	 * @var bool
	 */
	this.required = false;

	/**
	 * Is this a system defined field
	 *
	 * Only user fields can be deleted or edited
	 *
	 * @public
	 * @var bool
	 */
	this.system = false;

	/**
	 * If read only the user cannot set this value
	 *
	 * @public
	 * @var bool
	 */
	this.readonly = false;

	/**
	 * This field value must be unique across all objects
	 *
	 * @public
	 * @var bool
	 */
	this.unique = false;

	/**
	 * Optional use_when condition will only display field when condition is met
	 *
	 * This is used for things like custom fields for posts where each feed will have special
	 * custom fields on a global object - posts.
	 *
	 * @public
	 * @type {string}
	 */
	this.useWhen = "";

	/**
	 * Default value to use with this field
	 *
	 * @public
	 * @var array('on', 'value')
	 */
	this.defaultVal = null;

	/**
	 * Optional values
	 *
	 * If an associative array then the id is the key, otherwise the value is used
	 *
	 * @public
	 * @var array
	 */
	this.optionalValues = null;

	/**
	 * Sometimes we need to automatically create foreign reference
	 *
	 * @public
	 * @type {bool}
	 */
	this.autocreate = false;

	/**
	 * If autocreate then the base is used to define where to put the new referenced object
	 *
	 * @public
	 * @type {string}
	 */
	this.autocreatebase = "";

	/**
	 * If autocreate then which field should we use for the name of the new object
	 *
	 * @public
	 * @type {string}
	 */
	this.autocreatename = "";
}

/**
 * Initialize this field from an array
 *
 * The array is typically from a JSON request returning a field in array form
 * which basicallly takes each property in camelCase and converts it to under_score
 * format to keep the data formats clear.
 *
 * @param {Array|Object}
 */
Ant.EntityDefinition.Field.prototype.fromArray = function(data)
{
	this.id = data.id;
	this.name = data.name;
	this.title = data.title;
	this.type = data.type;
	this.subtype = data.subtype;
	this.defaultVal = data["default"];
	this.mask = data.mask;
	this.required = data.required;
	this.system = data.system;
	this.readonly = data.readonly;
	this.unique = data.unique;
	this.useWhen = data.use_when;
	this.optionalValues = data.optional_values;
}

/**
 * Get array object from this field for sending
 *
 * The array is typically from a JSON request returning a field in array form
 * which basicallly takes each property in camelCase and converts it to under_score
 * format to keep the data formats clear.
 *
 * @return {Object}
 */
Ant.EntityDefinition.Field.prototype.toArray = function()
{
	
}

/**
 * Get the default value for this vield
 *
 * @param {string} on The event to set default value on - default to null
 * @return {string}
 */
Ant.EntityDefinition.Field.prototype.getDefault = function(on)
{
	if (!this.defaultVal)
		return "";

	if (this.defaultVal.on == on)
	{
		if (this.defaultVal.value)
			return this.defaultVal.value;
	}

	return "";
}
