/**
 * @fileoverview Javascript side implementation of the AntObjectList php class
 *
 * This class will load page of 100 objects at once. This means that we have to
 * keep a current offset and dynamically query the server as new pages are needed.
 * 
 * Example:
 * <code>
 * 	var olist = new AntObjectList("customer");
 * 	olist.addCondition("and", "first_name", "is_equal", "Sky");
 * 	olist.addSortOrder("first_name", "desc");
 * 	var num = olist.getObjects();
 * 	for (var i = 0; i < num; i++)
 * 	{
 *		// Get instance of CAntObject
 * 		var obj = olist.getObject(i);
 * 		
 * 		// Get data of object
 * 		var objData = olist.getObjectData(i);
 * 	}
 * </code>
 *
 * @author 	joe, sky.stebnicki@aereus.com.
 * 			Copyright (c) 2011 Aereus Corporation. All rights reserved.
 */

/**
 * Creates an instance of AntObjectList
 *
 * @constructor
 * @param {string} obj_type The name of the object type to load
 */
function AntObjectList(obj_type)
{
	/**
	 * Object type for this list
	 *
	 * @type {string}
	 * @private
	 */
	this.objType = obj_type;

	/**
	 * Array of condition objects {blogic, fieldName, operator, condValue}
	 *
	 * @type {array}
	 * @private
	 */
	this.conditions = new Array();

	/**
	 * Array of sort order objects
	 *
	 * @type {array}
	 * @private
	 */
	this.sortOrder = new Array();

	/**
	 * Array of objects with the following properties: id, revision
	 *
	 * @type {array}
	 * @private
	 */
	this.objects = new Array();

	/**
	 * The current offset of the total number of items
	 *
	 * @type {number}
	 * @private
	 */
	this.offset = 0;

	/**
	 * Number of items to pull each query
	 *
	 * @type {number}
	 * @private
	 */
	this.limit = 100;

	/**
	 * Total number of objects in this query set
	 *
	 * @type {number}
	 * @private
	 */
	this.totalNum = 0;

	/**
	 * Get object asynchronously
	 *
	 * @type {bool}
	 * @public
	 */
	this.async = true;

	/**
	 * Object for storing data for onload callback function
	 *
	 * @type {Object}
	 * @public
	 */
	this.cbData = new Object();

	/**
	 * Pagination data based on offset and limit
	 *
	 * Properties include: next (page), prev (page), desc
	 *
	 * @type {Object}
	 * @public
	 */
	this.pagination = new Object();
}

/**
 * Add a condition to query by
 *
 * @public
 * @this {AntObjectList}
 * @param {string} blogic Either "and" or "or" in relation to the past condition (if any)
 * @param {string} name The name of the field to query against
 * @param {string} operator The string operator
 * @param {string} value The value of the query condition.
 */
AntObjectList.prototype.addCondition = function(blogic, fieldName, operator, condValue)
{
	var cond = new Object();
	cond.blogic = blogic;
	cond.fieldName = fieldName;
	cond.operator = operator;
	cond.condValue = condValue;
	this.conditions[this.conditions.length] = cond;
}

/**
 * Clear conditions
 *
 * @public
 * @this {AntObjectList}
 */
AntObjectList.prototype.clearConditions = function()
{
	this.conditions = new Array();
}

/**
 * Add a sort order to the list. Can add multiple.
 *
 * @public
 * @this {AntObjectList}
 * @param {string} field The field name to sort by
 * @param {string} selorder The direcion of the sort - 'asc' or 'desc'
 */
AntObjectList.prototype.addSortOrder = function(field, selorder)
{
	var sorder = (selorder) ? selorder : "asc";

	var ind = this.sortOrder.length;
	this.sortOrder[ind] = {fieldName:field, order:sorder};
}

/**
 * Clear sort order
 *
 * @public
 * @this {AntObjectList}
 */
AntObjectList.prototype.clearSorting = function()
{
	this.sortOrder = new Array();
}

/**
 * Query the server for a list of objects matching all conditions
 *
 * @public
 * @this {AntObjectList}
 * @param {int} offset Page to start loading
 * param {int} limit Maximum number of objects to return
 */
AntObjectList.prototype.getObjects = function(offset, limit)
{
	if (typeof offset != "undefined")
		this.offset = offset;
	
	if (typeof limit != "undefined")
		this.limit = limit;

	var ajax = new CAjax('json');
	ajax.cbData.cls = this;
	ajax.onload = function(resp)
	{
		this.cbData.cls.totalNum = resp.totalNum;

		if (resp.entities.length)
		{
            this.cbData.cls.objects = resp.entities;
		}

		// With the new /svr/entity/query we do not use pagination anymore
		/*if (resp.pagination)
		{
			this.cbData.cls.pagination.next = resp.pagination.next;
			this.cbData.cls.pagination.prev = resp.pagination.prev;
			this.cbData.cls.pagination.desc = resp.pagination.decs;
		}
		else
		{
			this.cbData.cls.pagination.next = 0;
			this.cbData.cls.pagination.prev = 0;
			this.cbData.cls.pagination.desc = "";
		}*/

		this.cbData.cls.onLoad();
	};

	// Set basic query vars
	var args = [["obj_type", this.objType], ["offset", this.offset], ["limit", this.limit]];


	// Add conditions
	for (var i = 0; i < this.conditions.length; i++)
	{
		var cond = this.conditions[i];

		args[args.length] = ["where[]", [cond.blogic, cond.fieldName, cond.operator, cond.condValue]];
	}
	
	// Get order by
	for (var i = 0; i < this.sortOrder.length; i++)
	{
		args[args.length] = ["order_by[]", this.sortOrder[i].fieldName + "," + this.sortOrder[i].order];
	}

	//ajax.exec("/controller/ObjectList/query", args, this.async);
	ajax.exec("/svr/entity/query", args, this.async);
}

/**
 * Public callback function used to determine when the list has been loaded
 *
 * @public
 * @this {AntObjectList}
 */
AntObjectList.prototype.onLoad = function()
{
}

/**
 * Get number of objects in the current result set
 *
 * @public
 * @this {AntObjectList}
 * @return {number} The number of objects in the current result set
 */
AntObjectList.prototype.getNumObjects = function()
{
	return this.objects.length;
}

/**
 * Crete a new CAntObject at the current index
 *
 * @public
 * @this {AntObjectList}
 * @param {number} idx The index of the object to retrieve
 * @return {CAntObject} Object on success, false on failure
 */
AntObjectList.prototype.getObject = function(idx)
{
	// Make sure we are not outside the boundaries of the index
	if (idx >= this.objects.length)
		return false;

	var obj = new CAntObject(this.objType, this.objects[idx].id);
	obj.setData(this.objects[idx]);
	return obj;
}

/**
 * Get the json data for an object without creating a CAntObject
 *
 * @public
 * @this {AntObjectList}
 * @param {number} idx The index of the object data to retrieve
 * @return {Object} Object on success, false on failure
 */
AntObjectList.prototype.getObjectData = function(idx)
{
	// Make sure we are not outside the boundaries of the index
	if (idx >= this.objects.length)
		return false;

	return this.objects[idx];
}
