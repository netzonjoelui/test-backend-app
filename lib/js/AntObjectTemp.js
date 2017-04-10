/**
 * @fileoverview This class handles setting and getting values for an object template
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2013 Aereus Corporation. All rights reserved.
 */

/**
 * Object template class
 *
 * @param {string} obj_type The type of object this template affects
 * @param {string} tid The optional id of the template we are editing
 */
function AntObjectTemp(obj_type, tid)
{
	/**
	 * Unique object id
	 *
	 * This will be set to a number when saving a new object.
	 *
	 * @public
	 * @type {number}
	 */
    this.tid = tid;
    
	/**
	 * Textual name of the objet type we are working with
	 *
	 * @protected
	 * @type {string}
	 */
    this.objType = obj_type;

	/**
	 * Values
	 *
	 * @type {Array}
	 */
	this.values = new Array();
}

/**
 * Set a value of a field by name
 *
 * @param {string} fname The name of the field
 * @param {string} fval The value to set
 */
AntObjectTemp.prototype.setValue = function(fname, fval)
{
	this.values[fname] = fval;
}

/**
 * Get the value of a field
 *
 * @param {string} fname The name of the field
 */
AntObjectTemp.prototype.getValue = function(fname)
{
	return this.values[fname];
}

/**
 * Append a value to a multi value field
 *
 * @param {string} fname The name of the field
 * @param {string} fval The value to add
 * @param {string} fvalLabel If we are working with fkey_ or objec_ then this is the label of the key to be cached
 */
AntObjectTemp.prototype.addMValue = function(fname, fval, fvalLabel)
{
	if (typeof this.values[fname] != "Array")
		this.values[fname] = new Array();

	this.values[fname].push({
		key: fval,
		value: fvalLabel || ""
	});
}

/**
 * Save this template
 */
AntObjectTemp.prototype.save = function()
{
	alib.events.trigger(this, "saved");
}
